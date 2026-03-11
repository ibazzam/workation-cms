import { BadRequestException, ForbiddenException, Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { LoyaltyService } from '../loyalty/loyalty.service';
import { PrismaService } from '../prisma.service';

type CreateBookingPayload = {
  accommodationId?: unknown;
  transportId?: unknown;
  itineraryTransportIds?: unknown;
  transportFareClassCode?: unknown;
  startDate?: unknown;
  endDate?: unknown;
  guests?: unknown;
};

type RebookPayload = {
  accommodationId?: unknown;
  transportId?: unknown;
  itineraryTransportIds?: unknown;
  transportFareClassCode?: unknown;
  startDate?: unknown;
  endDate?: unknown;
  guests?: unknown;
};

type ValidateItineraryPayload = {
  accommodationId?: unknown;
  startDate?: unknown;
  itineraryTransportIds?: unknown;
};

type BookingLifecycleStatus = 'DRAFT' | 'PENDING' | 'HOLD' | 'CONFIRMED' | 'CANCELLED' | 'REFUNDED';

const ACTIVE_RESERVATION_STATUSES: BookingLifecycleStatus[] = ['PENDING', 'HOLD', 'CONFIRMED'];

@Injectable()
export class BookingsService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly loyaltyService: LoyaltyService,
  ) {}

  async listByUser(userId: string) {
    let bookings: Array<any> = [];

    try {
      bookings = await this.prisma.booking.findMany({
        where: { userId },
        include: {
          accommodation: true,
          transport: true,
          payment: true,
        },
        orderBy: { createdAt: 'desc' },
      });
    } catch {
      try {
        // Fallback to base booking rows when relation hydration fails in partially migrated environments.
        bookings = await this.prisma.booking.findMany({
          where: { userId },
          orderBy: { createdAt: 'desc' },
        });
      } catch {
        // Final fallback for environments with Booking-column drift: query only stable fields.
        const rawRows = await this.prisma.$queryRaw<Array<{
          id: string;
          status: string;
          createdAt: Date;
          startDate: Date | null;
          endDate: Date | null;
          totalPrice: Prisma.Decimal | string | number | null;
        }>>(Prisma.sql`
          SELECT
            "id",
            "status",
            "createdAt",
            "startDate",
            "endDate",
            "totalPrice"
          FROM "Booking"
          WHERE "userId" = ${userId}
          ORDER BY "createdAt" DESC
        `);

        bookings = rawRows.map((row) => ({
          id: row.id,
          status: row.status,
          createdAt: row.createdAt,
          startDate: row.startDate,
          endDate: row.endDate,
          totalPrice: row.totalPrice ?? 0,
          accommodation: null,
          transport: null,
          payment: null,
          holdExpiresAt: null,
          fareLockExpiresAt: null,
        }));
      }
    }

    return bookings.map((booking) => {
      const status = booking.status as BookingLifecycleStatus;
      const holdExpired = booking.holdExpiresAt ? booking.holdExpiresAt.getTime() < Date.now() : false;
      const fareLockExpired = booking.fareLockExpiresAt ? booking.fareLockExpiresAt.getTime() < Date.now() : false;

      return {
        ...booking,
        management: {
          holdExpired,
          fareLockExpired,
          canMoveToHold: this.isAllowedTransition(status, 'HOLD'),
          canConfirm: this.isAllowedTransition(status, 'CONFIRMED') && !holdExpired && !fareLockExpired,
          canCancel: this.isAllowedTransition(status, 'CANCELLED'),
          canRebook: ['HOLD', 'PENDING', 'CONFIRMED'].includes(status),
          canRefund: this.isAllowedTransition(status, 'REFUNDED'),
        },
      };
    });
  }

  async createForUser(userId: string, payload: CreateBookingPayload) {
    const parsedGuests = payload.guests !== undefined ? Number(payload.guests) : 1;
    if (!Number.isInteger(parsedGuests) || parsedGuests <= 0) {
      throw new BadRequestException('guests must be a positive integer');
    }

    const accommodationId = typeof payload.accommodationId === 'string' ? payload.accommodationId : undefined;
    const transportId = typeof payload.transportId === 'string' ? payload.transportId : undefined;
    const itineraryTransportIds = this.parseOptionalTransportIdList(payload.itineraryTransportIds);
    const itineraryLastTransportId = itineraryTransportIds?.[itineraryTransportIds.length - 1];
    const resolvedTransportId = transportId ?? itineraryLastTransportId;

    if (transportId && itineraryLastTransportId && transportId !== itineraryLastTransportId) {
      throw new BadRequestException('transportId must match the final itinerary transport leg when itineraryTransportIds is provided');
    }

    const transportFareClassCode = this.parseOptionalFareClassCode(payload.transportFareClassCode);

    const startDate = this.parseDate(payload.startDate, 'startDate');
    const endDate = this.parseDate(payload.endDate, 'endDate');

    if (startDate && endDate && endDate.getTime() < startDate.getTime()) {
      throw new BadRequestException('endDate must be after or equal to startDate');
    }

    if (accommodationId && !resolvedTransportId) {
      throw new BadRequestException('transportId is required when accommodationId is provided');
    }

    if (accommodationId && (!startDate || !endDate)) {
      throw new BadRequestException('startDate and endDate are required when accommodationId is provided');
    }

    let accommodation: { id: string; islandId: number; price: Prisma.Decimal; rooms: number | null; minStayNights: number } | null = null;
    let accommodationTotalPrice = new Prisma.Decimal(0);
    if (accommodationId) {
      accommodation = await this.prisma.accommodation.findUnique({
        where: { id: accommodationId },
        select: { id: true, islandId: true, price: true, rooms: true, minStayNights: true },
      });

      if (!accommodation) {
        throw new NotFoundException('Accommodation not found');
      }

      const stayNights = this.calculateStayNights(startDate!, endDate!);
      if (stayNights < accommodation.minStayNights) {
        throw new BadRequestException(`Booking does not satisfy minimum stay of ${accommodation.minStayNights} nights`);
      }

      const overlappingBlackout = await this.prisma.accommodationBlackout.findFirst({
        where: {
          accommodationId: accommodation.id,
          startDate: { lt: endDate! },
          endDate: { gt: startDate! },
        },
        orderBy: { startDate: 'asc' },
      });

      if (overlappingBlackout) {
        throw new BadRequestException('Accommodation is unavailable for selected dates due to blackout window');
      }

      if (accommodation.rooms !== null) {
        const overlappingBookingsCount = await this.prisma.booking.count({
          where: {
            accommodationId: accommodation.id,
            status: { in: ACTIVE_RESERVATION_STATUSES },
            startDate: { lt: endDate! },
            endDate: { gt: startDate! },
          },
        });

        if (overlappingBookingsCount >= accommodation.rooms) {
          throw new BadRequestException('Accommodation has no available room inventory for selected dates');
        }
      }

      accommodationTotalPrice = await this.calculateAccommodationPrice(accommodation.id, startDate!, endDate!, accommodation.price);
    }

    let transport: {
      id: string;
      type: string;
      toIslandId: number | null;
      arrival: Date | null;
      capacity: number | null;
      price: Prisma.Decimal;
    } | null = null;

    let selectedFareClass: {
      code: string;
      seats: number | null;
      price: Prisma.Decimal;
    } | null = null;

    if (resolvedTransportId) {
      transport = await this.prisma.transport.findUnique({
        where: { id: resolvedTransportId },
        select: { id: true, type: true, toIslandId: true, arrival: true, capacity: true, price: true },
      });

      if (!transport) {
        throw new NotFoundException('Transport not found');
      }

      const activeDisruption = await this.prisma.transportDisruption.findFirst({
        where: {
          transportId: transport.id,
          resolvedAt: null,
        },
        orderBy: { createdAt: 'desc' },
      });

      if (activeDisruption && (activeDisruption.status === 'CANCELLED' || activeDisruption.status === 'WEATHER_CANCELLED')) {
        const replacementHint = activeDisruption.replacementTransportId
          ? ` Use replacement transport ${activeDisruption.replacementTransportId} if available.`
          : '';
        throw new BadRequestException(`Transport is currently cancelled due to an active disruption.${replacementHint}`);
      }

      if (transportFareClassCode && transport.type !== 'DOMESTIC_FLIGHT') {
        throw new BadRequestException('transportFareClassCode is only supported for DOMESTIC_FLIGHT transports');
      }

      if (transport.type === 'DOMESTIC_FLIGHT') {
        const fareClasses = await this.prisma.transportFareClass.findMany({
          where: { transportId: transport.id },
          select: { code: true, seats: true, price: true },
        });

        if (fareClasses.length > 0) {
          if (!transportFareClassCode) {
            throw new BadRequestException('transportFareClassCode is required for domestic flight bookings');
          }

          selectedFareClass = fareClasses.find((fareClass) => fareClass.code === transportFareClassCode) ?? null;
          if (!selectedFareClass) {
            throw new BadRequestException('transportFareClassCode is invalid for selected domestic flight');
          }

          if (selectedFareClass.seats !== null) {
            const reservedSeatsAggregate = await this.prisma.booking.aggregate({
              where: {
                transportId: transport.id,
                transportFareClassCode: selectedFareClass.code,
                status: { in: ACTIVE_RESERVATION_STATUSES },
              },
              _sum: { guests: true },
            });

            const reservedSeats = reservedSeatsAggregate._sum.guests ?? 0;
            const availableSeats = Math.max(selectedFareClass.seats - reservedSeats, 0);
            if (parsedGuests > availableSeats) {
              throw new BadRequestException('guests exceeds available seats for selected fare class');
            }
          }
        }
      }
    }

    if (accommodation && transport) {
      if (itineraryTransportIds && itineraryTransportIds.length > 0) {
        const itineraryResult = await this.validateItineraryDependencies({
          accommodationId: accommodation.id,
          startDate: startDate!,
          itineraryTransportIds,
        });

        if (!itineraryResult.valid) {
          throw new BadRequestException(itineraryResult.violations[0] ?? 'Itinerary validation failed');
        }
      }

      if (!transport.toIslandId || transport.toIslandId !== accommodation.islandId) {
        throw new BadRequestException('Transport destination must match accommodation island');
      }

      if (startDate && transport.arrival && transport.arrival.getTime() > startDate.getTime()) {
        throw new BadRequestException('Transport arrival must be on or before booking startDate');
      }
    }

    if (transport?.capacity !== null && transport?.capacity !== undefined) {
      const reservedSeatsAggregate = await this.prisma.booking.aggregate({
        where: {
          transportId: transport.id,
          status: { in: ACTIVE_RESERVATION_STATUSES },
        },
        _sum: { guests: true },
      });

      const reservedSeats = reservedSeatsAggregate._sum.guests ?? 0;
      const availableSeats = Math.max(transport.capacity - reservedSeats, 0);
      if (parsedGuests > availableSeats) {
        throw new BadRequestException('guests exceeds available transport seats');
      }
    }

    const transportUnitPrice = selectedFareClass?.price ?? transport?.price ?? new Prisma.Decimal(0);
    const transportTotalPrice = transportUnitPrice.mul(new Prisma.Decimal(parsedGuests));
    const totalPrice = accommodationTotalPrice.plus(transportTotalPrice);
    const holdExpiresAt = this.computeHoldExpiryDate();
    const fareLockExpiresAt = transport ? this.computeFareLockExpiryDate() : null;

    return this.prisma.booking.create({
      data: {
        userId,
        accommodationId: accommodation?.id,
        transportId: transport?.id,
        transportFareClassCode: selectedFareClass?.code,
        startDate,
        endDate,
        guests: parsedGuests,
        totalPrice,
        fareLockUnitPrice: transport ? transportUnitPrice : null,
        fareLockTotalPrice: transport ? transportTotalPrice : null,
        fareLockCurrency: transport ? 'USD' : null,
        fareLockExpiresAt,
        status: 'HOLD',
        holdExpiresAt,
      },
      include: {
        accommodation: true,
        transport: true,
      },
    });
  }

  async validateItineraryForUser(userId: string, payload: ValidateItineraryPayload) {
    const accommodationId = typeof payload.accommodationId === 'string' ? payload.accommodationId : undefined;
    if (!accommodationId) {
      throw new BadRequestException('accommodationId is required');
    }

    const startDate = this.parseDate(payload.startDate, 'startDate');
    if (!startDate) {
      throw new BadRequestException('startDate is required');
    }

    const itineraryTransportIds = this.parseOptionalTransportIdList(payload.itineraryTransportIds);
    if (!itineraryTransportIds || itineraryTransportIds.length === 0) {
      throw new BadRequestException('itineraryTransportIds must contain at least one transport id');
    }

    const result = await this.validateItineraryDependencies({
      accommodationId,
      startDate,
      itineraryTransportIds,
    });

    return {
      userId,
      ...result,
    };
  }

  async getRebookTemplateForUser(userId: string, bookingId: string) {
    const booking = await this.prisma.booking.findUnique({
      where: { id: bookingId },
      include: {
        accommodation: true,
        transport: true,
        payment: true,
      },
    });

    if (!booking) {
      throw new NotFoundException('Booking not found');
    }

    if (booking.userId !== userId) {
      throw new ForbiddenException('Booking does not belong to authenticated user');
    }

    const status = booking.status as BookingLifecycleStatus;
    const canRebook = ['HOLD', 'PENDING', 'CONFIRMED'].includes(status);

    return {
      booking,
      template: {
        canRebook,
        reason: canRebook ? null : 'Only HOLD, PENDING, or CONFIRMED bookings can be rebooked',
        defaults: {
          accommodationId: booking.accommodationId,
          transportId: booking.transportId,
          transportFareClassCode: booking.transportFareClassCode,
          startDate: booking.startDate?.toISOString() ?? null,
          endDate: booking.endDate?.toISOString() ?? null,
          guests: booking.guests,
        },
      },
    };
  }

  private async validateItineraryDependencies(input: {
    accommodationId: string;
    startDate: Date;
    itineraryTransportIds: string[];
  }) {
    const violations: string[] = [];
    const uniqueTransportIds = new Set(input.itineraryTransportIds);
    if (uniqueTransportIds.size !== input.itineraryTransportIds.length) {
      violations.push('itineraryTransportIds cannot contain duplicate transport ids');
    }

    const [accommodation, transports] = await Promise.all([
      this.prisma.accommodation.findUnique({
        where: { id: input.accommodationId },
        select: { id: true, islandId: true },
      }),
      this.prisma.transport.findMany({
        where: { id: { in: input.itineraryTransportIds } },
        select: {
          id: true,
          fromIslandId: true,
          toIslandId: true,
          departure: true,
          arrival: true,
        },
      }),
    ]);

    if (!accommodation) {
      throw new NotFoundException('Accommodation not found');
    }

    const transportById = new Map(transports.map((transport) => [transport.id, transport]));
    const orderedLegs = input.itineraryTransportIds.map((transportId) => transportById.get(transportId) ?? null);
    if (orderedLegs.some((leg) => leg === null)) {
      violations.push('One or more itinerary transports were not found');
    }

    const resolvedLegs = orderedLegs.filter((leg): leg is NonNullable<typeof leg> => leg !== null);
    for (let index = 0; index < resolvedLegs.length; index += 1) {
      const leg = resolvedLegs[index];
      if (!leg.departure || !leg.arrival) {
        violations.push(`Itinerary transport ${leg.id} must include departure and arrival timestamps`);
        continue;
      }

      if (leg.arrival.getTime() < leg.departure.getTime()) {
        violations.push(`Itinerary transport ${leg.id} arrival must be after departure`);
      }

      if (index > 0) {
        const previousLeg = resolvedLegs[index - 1];
        if (previousLeg.toIslandId === null || leg.fromIslandId === null || previousLeg.toIslandId !== leg.fromIslandId) {
          violations.push(`Itinerary leg ${index + 1} must depart from previous leg destination island`);
        }

        if (previousLeg.arrival && leg.departure && previousLeg.arrival.getTime() > leg.departure.getTime()) {
          violations.push(`Itinerary leg ${index + 1} departs before previous leg arrival`);
        }
      }
    }

    const finalLeg = resolvedLegs[resolvedLegs.length - 1] ?? null;
    if (!finalLeg) {
      violations.push('At least one valid itinerary transport is required');
    } else {
      if (finalLeg.toIslandId !== accommodation.islandId) {
        violations.push('Final itinerary destination must match accommodation island');
      }

      if (!finalLeg.arrival) {
        violations.push('Final itinerary transport must have an arrival timestamp');
      } else if (finalLeg.arrival.getTime() > input.startDate.getTime()) {
        violations.push('Final itinerary arrival must be on or before accommodation startDate');
      }
    }

    return {
      valid: violations.length === 0,
      violations,
      itinerary: {
        transportIds: input.itineraryTransportIds,
        accommodationId: accommodation.id,
        accommodationIslandId: accommodation.islandId,
        finalTransportId: finalLeg?.id ?? null,
        finalArrivalAt: finalLeg?.arrival?.toISOString() ?? null,
      },
    };
  }

  async transitionForUser(userId: string, bookingId: string, targetStatus: 'HOLD' | 'CONFIRMED' | 'CANCELLED') {
    const booking = await this.prisma.booking.findUnique({ where: { id: bookingId } });
    if (!booking) {
      throw new NotFoundException('Booking not found');
    }

    if (booking.userId !== userId) {
      throw new ForbiddenException('Booking does not belong to authenticated user');
    }

    return this.transitionBooking(
      booking.id,
      booking.status as BookingLifecycleStatus,
      targetStatus,
      booking.holdExpiresAt,
      booking.fareLockExpiresAt,
    );
  }

  async refundBooking(bookingId: string) {
    const booking = await this.prisma.booking.findUnique({ where: { id: bookingId } });
    if (!booking) {
      throw new NotFoundException('Booking not found');
    }

    return this.transitionBooking(
      booking.id,
      booking.status as BookingLifecycleStatus,
      'REFUNDED',
      booking.holdExpiresAt,
      booking.fareLockExpiresAt,
    );
  }

  async rebookForUser(userId: string, bookingId: string, payload: RebookPayload) {
    const booking = await this.prisma.booking.findUnique({ where: { id: bookingId } });
    if (!booking) {
      throw new NotFoundException('Booking not found');
    }

    if (booking.userId !== userId) {
      throw new ForbiddenException('Booking does not belong to authenticated user');
    }

    const currentStatus = booking.status as BookingLifecycleStatus;
    if (!['HOLD', 'PENDING', 'CONFIRMED'].includes(currentStatus)) {
      throw new BadRequestException('Only HOLD, PENDING, or CONFIRMED bookings can be rebooked');
    }

    const mergedPayload: CreateBookingPayload = {
      accommodationId:
        payload.accommodationId !== undefined
          ? payload.accommodationId
          : booking.accommodationId ?? undefined,
      transportId:
        payload.transportId !== undefined
          ? payload.transportId
          : booking.transportId ?? undefined,
      itineraryTransportIds: payload.itineraryTransportIds,
      transportFareClassCode:
        payload.transportFareClassCode !== undefined
          ? payload.transportFareClassCode
          : booking.transportFareClassCode ?? undefined,
      startDate:
        payload.startDate !== undefined
          ? payload.startDate
          : booking.startDate?.toISOString() ?? undefined,
      endDate:
        payload.endDate !== undefined
          ? payload.endDate
          : booking.endDate?.toISOString() ?? undefined,
      guests:
        payload.guests !== undefined
          ? payload.guests
          : booking.guests ?? undefined,
    };

    await this.prisma.booking.update({
      where: { id: booking.id },
      data: {
        status: 'CANCELLED',
        holdExpiresAt: null,
        fareLockExpiresAt: null,
      },
    });

    try {
      const replacement = await this.createForUser(userId, mergedPayload);
      return {
        rebookedFromBookingId: booking.id,
        replacementBooking: replacement,
      };
    } catch (error) {
      await this.prisma.booking.update({
        where: { id: booking.id },
        data: {
          status: currentStatus,
          holdExpiresAt: booking.holdExpiresAt,
          fareLockExpiresAt: booking.fareLockExpiresAt,
        },
      });
      throw error;
    }
  }

  private async transitionBooking(
    bookingId: string,
    currentStatus: BookingLifecycleStatus,
    targetStatus: 'HOLD' | 'CONFIRMED' | 'CANCELLED' | 'REFUNDED',
    holdExpiresAt: Date | null,
    fareLockExpiresAt: Date | null,
  ) {
    if (currentStatus === targetStatus) {
      return this.prisma.booking.findUnique({
        where: { id: bookingId },
        include: {
          accommodation: true,
          transport: true,
          payment: true,
        },
      });
    }

    if (!this.isAllowedTransition(currentStatus, targetStatus)) {
      throw new BadRequestException(`Cannot transition booking from ${currentStatus} to ${targetStatus}`);
    }

    if (targetStatus === 'CONFIRMED' && holdExpiresAt && holdExpiresAt.getTime() < Date.now()) {
      await this.prisma.booking.update({
        where: { id: bookingId },
        data: {
          status: 'CANCELLED',
          holdExpiresAt: null,
        },
      });
      throw new BadRequestException('Booking hold has expired and was cancelled');
    }

    if (targetStatus === 'CONFIRMED' && fareLockExpiresAt && fareLockExpiresAt.getTime() < Date.now()) {
      await this.prisma.booking.update({
        where: { id: bookingId },
        data: {
          status: 'CANCELLED',
          holdExpiresAt: null,
          fareLockExpiresAt: null,
        },
      });
      throw new BadRequestException('Transport fare lock has expired and booking was cancelled');
    }

    const holdUpdateData: Prisma.BookingUncheckedUpdateInput = {
      status: targetStatus,
      holdExpiresAt: targetStatus === 'HOLD' ? this.computeHoldExpiryDate() : null,
    };

    if (targetStatus === 'HOLD') {
      const booking = await this.prisma.booking.findUnique({
        where: { id: bookingId },
        select: {
          transportId: true,
          transportFareClassCode: true,
          guests: true,
        },
      });

      const guests = booking?.guests ?? 1;
      if (booking?.transportId) {
        const fareLock = await this.resolveTransportFareLock(booking.transportId, booking.transportFareClassCode, guests);
        holdUpdateData.fareLockUnitPrice = fareLock.unitPrice;
        holdUpdateData.fareLockTotalPrice = fareLock.totalPrice;
        holdUpdateData.fareLockCurrency = fareLock.currency;
        holdUpdateData.fareLockExpiresAt = fareLock.expiresAt;
      } else {
        holdUpdateData.fareLockUnitPrice = null;
        holdUpdateData.fareLockTotalPrice = null;
        holdUpdateData.fareLockCurrency = null;
        holdUpdateData.fareLockExpiresAt = null;
      }
    } else {
      holdUpdateData.fareLockExpiresAt = null;
    }

    const updated = await this.prisma.booking.update({
      where: { id: bookingId },
      data: holdUpdateData,
      include: {
        accommodation: true,
        transport: true,
        payment: true,
      },
    });

    if (targetStatus === 'CONFIRMED') {
      await this.loyaltyService.awardPointsForConfirmedBooking(updated.id, 'BOOKING_TRANSITION');
    }

    return updated;
  }

  private isAllowedTransition(
    currentStatus: BookingLifecycleStatus,
    targetStatus: 'HOLD' | 'CONFIRMED' | 'CANCELLED' | 'REFUNDED',
  ): boolean {
    const allowedTransitions: Record<BookingLifecycleStatus, Array<'HOLD' | 'CONFIRMED' | 'CANCELLED' | 'REFUNDED'>> = {
      DRAFT: ['HOLD', 'CANCELLED'],
      PENDING: ['HOLD', 'CONFIRMED', 'CANCELLED'],
      HOLD: ['CONFIRMED', 'CANCELLED'],
      CONFIRMED: ['CANCELLED', 'REFUNDED'],
      CANCELLED: ['REFUNDED'],
      REFUNDED: [],
    };

    return allowedTransitions[currentStatus]?.includes(targetStatus) ?? false;
  }

  private computeHoldExpiryDate() {
    const holdMinutes = this.parsePositiveIntegerEnv('BOOKING_HOLD_MINUTES', 30);
    return new Date(Date.now() + holdMinutes * 60 * 1000);
  }

  private computeFareLockExpiryDate() {
    const fareLockMinutes = this.parsePositiveIntegerEnv('TRANSPORT_FARE_LOCK_MINUTES', this.parsePositiveIntegerEnv('BOOKING_HOLD_MINUTES', 30));
    return new Date(Date.now() + fareLockMinutes * 60 * 1000);
  }

  private async resolveTransportFareLock(transportId: string, fareClassCode: string | null, guests: number) {
    const transport = await this.prisma.transport.findUnique({
      where: { id: transportId },
      select: {
        id: true,
        type: true,
        price: true,
      },
    });

    if (!transport) {
      throw new NotFoundException('Transport not found');
    }

    if (fareClassCode && transport.type !== 'DOMESTIC_FLIGHT') {
      throw new BadRequestException('transportFareClassCode is only supported for DOMESTIC_FLIGHT transports');
    }

    let unitPrice = transport.price;
    if (fareClassCode) {
      const fareClass = await this.prisma.transportFareClass.findFirst({
        where: {
          transportId: transport.id,
          code: fareClassCode,
        },
        select: {
          code: true,
          price: true,
        },
      });

      if (!fareClass) {
        throw new BadRequestException('transportFareClassCode is invalid for selected transport');
      }

      unitPrice = fareClass.price;
    }

    return {
      unitPrice,
      totalPrice: unitPrice.mul(new Prisma.Decimal(guests)),
      currency: 'USD',
      expiresAt: this.computeFareLockExpiryDate(),
    };
  }

  private parsePositiveIntegerEnv(name: string, fallback: number): number {
    const raw = process.env[name];
    if (!raw) {
      return fallback;
    }

    const parsed = Number(raw);
    if (!Number.isInteger(parsed) || parsed <= 0) {
      return fallback;
    }

    return parsed;
  }

  private parseDate(value: unknown, fieldName: string) {
    if (value === undefined || value === null) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException(`${fieldName} must be a valid date string`);
    }

    const parsedDate = new Date(value);
    if (Number.isNaN(parsedDate.getTime())) {
      throw new BadRequestException(`${fieldName} must be a valid date string`);
    }

    return parsedDate;
  }

  private calculateStayNights(startDate: Date, endDate: Date): number {
    const millisecondsPerDay = 24 * 60 * 60 * 1000;
    return Math.ceil((endDate.getTime() - startDate.getTime()) / millisecondsPerDay);
  }

  private parseOptionalFareClassCode(value: unknown): string | undefined {
    if (value === undefined || value === null) {
      return undefined;
    }

    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException('transportFareClassCode must be a non-empty string');
    }

    return value.trim().toUpperCase();
  }

  private parseOptionalTransportIdList(value: unknown): string[] | undefined {
    if (value === undefined || value === null) {
      return undefined;
    }

    if (!Array.isArray(value)) {
      throw new BadRequestException('itineraryTransportIds must be an array of transport ids');
    }

    const parsed = value.map((entry) => {
      if (typeof entry !== 'string' || entry.trim().length === 0) {
        throw new BadRequestException('itineraryTransportIds must contain non-empty string transport ids');
      }

      return entry.trim();
    });

    return parsed;
  }

  private async calculateAccommodationPrice(accommodationId: string, startDate: Date, endDate: Date, baseNightlyPrice: Prisma.Decimal) {
    const stayNights = this.calculateStayNights(startDate, endDate);
    const seasonalRates = await this.prisma.accommodationSeasonalRate.findMany({
      where: {
        accommodationId,
        startDate: { lt: endDate },
        endDate: { gt: startDate },
      },
      orderBy: [{ priority: 'desc' }, { createdAt: 'desc' }],
      select: {
        startDate: true,
        endDate: true,
        nightlyPrice: true,
        minNights: true,
      },
    });

    let total = new Prisma.Decimal(0);
    for (let index = 0; index < stayNights; index += 1) {
      const currentNight = new Date(startDate.getTime() + index * 24 * 60 * 60 * 1000);
      const nextNight = new Date(currentNight.getTime() + 24 * 60 * 60 * 1000);

      const matchedRate = seasonalRates.find((rate) => {
        const withinWindow = rate.startDate.getTime() < nextNight.getTime() && rate.endDate.getTime() > currentNight.getTime();
        const nightsAllowed = rate.minNights === null || stayNights >= rate.minNights;
        return withinWindow && nightsAllowed;
      });

      total = total.plus(matchedRate?.nightlyPrice ?? baseNightlyPrice);
    }

    return total;
  }
}

import { BadRequestException, ForbiddenException, Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../prisma.service';

type TransportUpsertPayload = {
  vendorId?: unknown;
  type?: unknown;
  code?: unknown;
  fromIslandId?: unknown;
  toIslandId?: unknown;
  departure?: unknown;
  arrival?: unknown;
  capacity?: unknown;
  price?: unknown;
  fareClasses?: unknown;
};

type TransportDisruptionPayload = {
  status?: unknown;
  reason?: unknown;
  delayMinutes?: unknown;
  replacementTransportId?: unknown;
  startsAt?: unknown;
};

type NormalizedFareClass = {
  code: string;
  name: string;
  baggageKg: number | null;
  seats: number | null;
  price: Prisma.Decimal;
};

type NormalizedTransportPayload = {
  data: Record<string, unknown>;
  fareClasses?: NormalizedFareClass[];
};

type RequestActor = {
  id?: string;
  role?: string;
  vendorId?: string;
};

@Injectable()
export class TransportsService {
  constructor(private readonly prisma: PrismaService) {}

  private computeRouteRelevanceScore(
    transport: { fromIsland?: { id?: number | null; atollId?: number | null } | null; toIsland?: { id?: number | null; atollId?: number | null } | null },
    filters: { anchorIslandId?: number; preferredAtollId?: number },
  ): number {
    let score = 0;

    if (filters.anchorIslandId !== undefined) {
      if (transport.fromIsland?.id === filters.anchorIslandId) score += 3;
      if (transport.toIsland?.id === filters.anchorIslandId) score += 3;
    }

    if (filters.preferredAtollId !== undefined) {
      if (transport.fromIsland?.atollId === filters.preferredAtollId) score += 2;
      if (transport.toIsland?.atollId === filters.preferredAtollId) score += 2;
    }

    return score;
  }

  async list(filters: {
    fromIslandId?: number;
    toIslandId?: number;
    type?: string;
    date?: string;
    anchorIslandId?: number;
    preferredAtollId?: number;
  }) {
    const dayRange = filters.date ? this.parseDateRange(filters.date) : null;

    const transports = await this.prisma.transport.findMany({
      where: {
        fromIslandId: filters.fromIslandId,
        toIslandId: filters.toIslandId,
        type: filters.type ? filters.type.toUpperCase() : undefined,
        ...(dayRange
          ? {
              departure: {
                gte: dayRange.start,
                lt: dayRange.end,
              },
            }
          : {}),
      },
      include: {
        fromIsland: true,
        toIsland: true,
        vendor: true,
        fareClasses: {
          orderBy: { code: 'asc' },
        },
      },
      orderBy: { departure: 'asc' },
    });

    const withSeatInventory = await this.attachSeatInventory(transports);
    const withFareClassInventory = await this.attachFareClassInventory(withSeatInventory);
    const withDisruption = await this.attachActiveDisruption(withFareClassInventory);

    if (filters.anchorIslandId === undefined && filters.preferredAtollId === undefined) {
      return withDisruption;
    }

    const withRelevance = withDisruption.map((transport) => ({
      ...transport,
      relevanceScore: this.computeRouteRelevanceScore(transport, filters),
      departureEpoch: transport.departure ? new Date(transport.departure).getTime() : Number.MAX_SAFE_INTEGER,
    }));

    withRelevance.sort((a, b) => {
      if (a.relevanceScore !== b.relevanceScore) {
        return b.relevanceScore - a.relevanceScore;
      }

      return a.departureEpoch - b.departureEpoch;
    });

    return withRelevance.map(({ departureEpoch: _departureEpoch, ...transport }) => transport);
  }

  async listSchedule(filters: {
    fromIslandId?: number;
    toIslandId?: number;
    type?: string;
    date?: string;
    anchorIslandId?: number;
    preferredAtollId?: number;
  }) {
    if (!filters.date) {
      throw new BadRequestException('date is required (YYYY-MM-DD)');
    }

    return this.list(filters);
  }

  async listFlightSchedule(filters: {
    fromIslandId?: number;
    toIslandId?: number;
    date?: string;
    anchorIslandId?: number;
    preferredAtollId?: number;
  }) {
    return this.listSchedule({
      ...filters,
      type: 'DOMESTIC_FLIGHT',
    });
  }

  async listFareClasses(id: string) {
    const transport = await this.prisma.transport.findUnique({
      where: { id },
      include: {
        fareClasses: {
          orderBy: { code: 'asc' },
        },
      },
    });

    if (!transport) {
      throw new NotFoundException('Transport not found');
    }

    const withInventory = await this.attachFareClassInventory([
      {
        id: transport.id,
        fareClasses: transport.fareClasses,
      },
    ]);

    return withInventory[0].fareClasses;
  }

  async quote(id: string, options: { guests?: number; fareClassCode?: string }) {
    const guests = options.guests ?? 1;
    if (!Number.isInteger(guests) || guests <= 0) {
      throw new BadRequestException('guests must be a positive integer');
    }

    const transport = await this.prisma.transport.findUnique({
      where: { id },
      include: {
        fareClasses: {
          orderBy: { code: 'asc' },
        },
      },
    });

    if (!transport) {
      throw new NotFoundException('Transport not found');
    }

    const fareClassCode = options.fareClassCode?.toUpperCase();
    const selectedFareClass = fareClassCode
      ? transport.fareClasses.find((fareClass) => fareClass.code.toUpperCase() === fareClassCode)
      : null;

    if (fareClassCode && !selectedFareClass) {
      throw new BadRequestException('fareClassCode is not available for this transport');
    }

    const activeDisruption = await this.prisma.transportDisruption.findFirst({
      where: {
        transportId: transport.id,
        resolvedAt: null,
      },
      orderBy: { createdAt: 'desc' },
      include: { replacementTransport: true },
    });

    const reservedAggregate = await this.prisma.booking.aggregate({
      where: {
        transportId: transport.id,
        status: { in: ['PENDING', 'HOLD', 'CONFIRMED'] },
        transportFareClassCode: selectedFareClass?.code,
      },
      _sum: { guests: true },
    });

    const reservedSeats = reservedAggregate._sum.guests ?? 0;
    const capacity = selectedFareClass?.seats ?? transport.capacity;
    const availableSeats = capacity === null ? null : Math.max(capacity - reservedSeats, 0);

    const unitPrice = selectedFareClass?.price ?? transport.price;
    const totalPrice = unitPrice.mul(new Prisma.Decimal(guests));
    const fareLockMinutes = this.getFareLockMinutes();
    const fareLockExpiresAt = new Date(Date.now() + fareLockMinutes * 60 * 1000);

    const disruptionStatus = activeDisruption?.status?.toUpperCase();
    const serviceUnavailable = disruptionStatus === 'CANCELLED' || disruptionStatus === 'WEATHER_CANCELLED';
    const canBookByCapacity = availableSeats === null ? true : availableSeats >= guests;
    const canBook = canBookByCapacity && !serviceUnavailable;

    return {
      transportId: transport.id,
      guests,
      fareClassCode: selectedFareClass?.code ?? null,
      pricing: {
        unitPrice: String(unitPrice),
        totalPrice: String(totalPrice),
      },
      fareLock: {
        supported: true,
        currency: 'USD',
        lockWindowMinutes: fareLockMinutes,
        lockedUnitPrice: String(unitPrice),
        lockedTotalPrice: String(totalPrice),
        expiresAt: fareLockExpiresAt.toISOString(),
      },
      inventory: {
        capacity,
        reservedSeats,
        availableSeats,
      },
      disruption: activeDisruption
        ? {
            id: activeDisruption.id,
            status: activeDisruption.status,
            reason: activeDisruption.reason,
            delayMinutes: activeDisruption.delayMinutes,
            replacementTransportId: activeDisruption.replacementTransportId,
          }
        : null,
      canBook,
      unavailableReason: !canBook
        ? (serviceUnavailable
            ? `transport is ${disruptionStatus?.toLowerCase()}`
            : 'insufficient seat inventory')
        : null,
    };
  }

  async getById(id: string) {
    const transport = await this.prisma.transport.findUnique({
      where: { id },
      include: {
        fromIsland: true,
        toIsland: true,
        vendor: true,
        fareClasses: {
          orderBy: { code: 'asc' },
        },
      },
    });

    if (!transport) {
      throw new NotFoundException('Transport not found');
    }

    const withSeatInventory = await this.attachSeatInventory([transport]);
    const withFareClassInventory = await this.attachFareClassInventory(withSeatInventory);
    const withDisruption = await this.attachActiveDisruption(withFareClassInventory);
    return withDisruption[0];
  }

  async createDisruption(id: string, payload: TransportDisruptionPayload, actor?: RequestActor) {
    await this.assertVendorScopedAccess(id, undefined, actor);
    await this.ensureTransportExists(id);

    const status = this.parseDisruptionStatus(payload.status);
    const reason = this.parseOptionalNullableString(payload.reason);
    const delayMinutes = this.parseOptionalNullableInt(payload.delayMinutes);
    const replacementTransportId = this.parseOptionalString(payload.replacementTransportId);
    const startsAt = this.parseOptionalNullableDate(payload.startsAt) ?? new Date();

    if (!status) {
      throw new BadRequestException('status is required');
    }

    if (status === 'DELAYED' && (delayMinutes === null || delayMinutes === undefined || delayMinutes <= 0)) {
      throw new BadRequestException('delayMinutes must be a positive integer for DELAYED disruption');
    }

    if (status !== 'DELAYED' && delayMinutes !== null && delayMinutes !== undefined) {
      throw new BadRequestException('delayMinutes is only supported for DELAYED disruption');
    }

    if (replacementTransportId && replacementTransportId === id) {
      throw new BadRequestException('replacementTransportId must be different from disrupted transport id');
    }

    if (replacementTransportId) {
      const replacement = await this.prisma.transport.findUnique({ where: { id: replacementTransportId }, select: { id: true } });
      if (!replacement) {
        throw new BadRequestException('replacementTransportId does not exist');
      }
    }

    await this.prisma.transportDisruption.updateMany({
      where: {
        transportId: id,
        resolvedAt: null,
      },
      data: {
        resolvedAt: new Date(),
      },
    });

    return this.prisma.transportDisruption.create({
      data: {
        transportId: id,
        status,
        reason: reason ?? null,
        delayMinutes: status === 'DELAYED' ? delayMinutes ?? null : null,
        replacementTransportId: replacementTransportId ?? null,
        startsAt,
      },
      include: {
        replacementTransport: true,
      },
    });
  }

  async resolveDisruption(id: string, disruptionId: string, actor?: RequestActor) {
    await this.assertVendorScopedAccess(id, undefined, actor);

    const disruption = await this.prisma.transportDisruption.findUnique({
      where: { id: disruptionId },
      select: { id: true, transportId: true, resolvedAt: true },
    });

    if (!disruption || disruption.transportId !== id) {
      throw new NotFoundException('Transport disruption not found');
    }

    if (disruption.resolvedAt) {
      return this.prisma.transportDisruption.findUniqueOrThrow({
        where: { id: disruption.id },
        include: { replacementTransport: true },
      });
    }

    return this.prisma.transportDisruption.update({
      where: { id: disruption.id },
      data: { resolvedAt: new Date() },
      include: { replacementTransport: true },
    });
  }

  async reaccommodateDisruptedBookings(id: string, disruptionId: string, actor?: RequestActor) {
    await this.assertVendorScopedAccess(id, undefined, actor);

    const disruption = await this.prisma.transportDisruption.findUnique({
      where: { id: disruptionId },
      include: {
        replacementTransport: {
          include: {
            fareClasses: true,
          },
        },
      },
    });

    if (!disruption || disruption.transportId !== id) {
      throw new NotFoundException('Transport disruption not found');
    }

    if (!disruption.replacementTransport) {
      throw new BadRequestException('No replacement transport is configured for this disruption');
    }

    let bookings: Array<{ id: string; guests: number | null; transportFareClassCode: string | null }>;
    try {
      bookings = await this.prisma.booking.findMany({
        where: {
          transportId: id,
          status: { in: ['PENDING', 'HOLD', 'CONFIRMED'] },
        },
        select: {
          id: true,
          guests: true,
          transportFareClassCode: true,
        },
        orderBy: { createdAt: 'asc' },
      });
    } catch (error) {
      if (!this.isBookingSchemaDriftError(error)) {
        throw error;
      }

      const fallbackRows = await this.prisma.$queryRaw<Array<{ id: string; guests: number | null }>>(Prisma.sql`
        SELECT
          "id",
          "guests"
        FROM "Booking"
        WHERE "transportId" = ${id}
          AND "status" IN ('PENDING', 'HOLD', 'CONFIRMED')
        ORDER BY "createdAt" ASC
      `);

      bookings = fallbackRows.map((row) => ({
        id: row.id,
        guests: row.guests,
        transportFareClassCode: null,
      }));
    }

    if (bookings.length === 0) {
      return {
        disruptionId: disruption.id,
        replacementTransportId: disruption.replacementTransport.id,
        scanned: 0,
        moved: 0,
        skipped: 0,
        details: [],
      };
    }

    const existingReplacementReservations = await this.prisma.booking.aggregate({
      where: {
        transportId: disruption.replacementTransport.id,
        status: { in: ['PENDING', 'HOLD', 'CONFIRMED'] },
      },
      _sum: { guests: true },
    });

    let availableSeats = disruption.replacementTransport.capacity === null
      ? Number.POSITIVE_INFINITY
      : Math.max(disruption.replacementTransport.capacity - (existingReplacementReservations._sum.guests ?? 0), 0);

    const replacementFareClassSeatMap = new Map<string, number>();
    for (const fareClass of disruption.replacementTransport.fareClasses) {
      if (fareClass.seats === null) {
        replacementFareClassSeatMap.set(fareClass.code, Number.POSITIVE_INFINITY);
        continue;
      }

      try {
        const reservedForClass = await this.prisma.booking.aggregate({
          where: {
            transportId: disruption.replacementTransport.id,
            transportFareClassCode: fareClass.code,
            status: { in: ['PENDING', 'HOLD', 'CONFIRMED'] },
          },
          _sum: { guests: true },
        });

        replacementFareClassSeatMap.set(fareClass.code, Math.max(fareClass.seats - (reservedForClass._sum.guests ?? 0), 0));
      } catch (error) {
        if (!this.isBookingSchemaDriftError(error)) {
          throw error;
        }

        replacementFareClassSeatMap.set(fareClass.code, fareClass.seats);
      }
    }

    const details: Array<{ bookingId: string; moved: boolean; reason?: string }> = [];
    let moved = 0;
    let skipped = 0;

    for (const booking of bookings) {
      const bookingGuests = booking.guests ?? 1;
      if (bookingGuests > availableSeats) {
        skipped += 1;
        details.push({
          bookingId: booking.id,
          moved: false,
          reason: 'replacement transport seat inventory exhausted',
        });
        continue;
      }

      if (booking.transportFareClassCode) {
        const availableClassSeats = replacementFareClassSeatMap.get(booking.transportFareClassCode);
        if (availableClassSeats === undefined) {
          skipped += 1;
          details.push({
            bookingId: booking.id,
            moved: false,
            reason: 'replacement transport does not support booking fare class',
          });
          continue;
        }

        if (bookingGuests > availableClassSeats) {
          skipped += 1;
          details.push({
            bookingId: booking.id,
            moved: false,
            reason: 'replacement fare class seat inventory exhausted',
          });
          continue;
        }

        replacementFareClassSeatMap.set(booking.transportFareClassCode, availableClassSeats - bookingGuests);
      }

      await this.prisma.booking.update({
        where: { id: booking.id },
        data: {
          transportId: disruption.replacementTransport.id,
        },
      });

      moved += 1;
      availableSeats -= bookingGuests;
      details.push({
        bookingId: booking.id,
        moved: true,
      });
    }

    return {
      disruptionId: disruption.id,
      replacementTransportId: disruption.replacementTransport.id,
      scanned: bookings.length,
      moved,
      skipped,
      details,
    };
  }

  private getFareLockMinutes(): number {
    return this.parsePositiveIntegerEnv('TRANSPORT_FARE_LOCK_MINUTES', this.parsePositiveIntegerEnv('BOOKING_HOLD_MINUTES', 30));
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

  private isBookingSchemaDriftError(error: unknown): boolean {
    if (!(error instanceof Prisma.PrismaClientKnownRequestError)) {
      return false;
    }

    if (error.code === 'P2022') {
      return true;
    }

    const message = (error.message ?? '').toLowerCase();
    return message.includes('transportfareclasscode') || message.includes('booking');
  }

  async create(payload: TransportUpsertPayload, actor?: RequestActor) {
    this.assertVendorScopedCreate(payload, actor);
    const normalized = await this.normalizeTransportPayload(payload, { partial: false });
    const type = typeof normalized.data.type === 'string' ? normalized.data.type : undefined;

    if (normalized.fareClasses && type !== 'DOMESTIC_FLIGHT') {
      throw new BadRequestException('fareClasses can only be provided for DOMESTIC_FLIGHT transports');
    }

    return this.prisma.transport.create({
      data: {
        ...(normalized.data as Prisma.TransportUncheckedCreateInput),
        ...(normalized.fareClasses
          ? {
              fareClasses: {
                create: normalized.fareClasses,
              },
            }
          : {}),
      } as Prisma.TransportCreateInput,
      include: {
        fromIsland: true,
        toIsland: true,
        vendor: true,
        fareClasses: {
          orderBy: { code: 'asc' },
        },
      },
    });
  }

  async update(id: string, payload: TransportUpsertPayload, actor?: RequestActor) {
    await this.assertVendorScopedAccess(id, payload, actor);
    const existing = await this.prisma.transport.findUnique({ where: { id }, select: { id: true, type: true } });
    if (!existing) {
      throw new NotFoundException('Transport not found');
    }

    const normalized = await this.normalizeTransportPayload(payload, { partial: true });
    const effectiveType = typeof normalized.data.type === 'string' ? normalized.data.type : existing.type;

    if (normalized.fareClasses && effectiveType !== 'DOMESTIC_FLIGHT') {
      throw new BadRequestException('fareClasses can only be provided for DOMESTIC_FLIGHT transports');
    }

    if (normalized.fareClasses === undefined) {
      return this.prisma.transport.update({
        where: { id },
        data: normalized.data as Prisma.TransportUncheckedUpdateInput,
        include: {
          fromIsland: true,
          toIsland: true,
          vendor: true,
          fareClasses: {
            orderBy: { code: 'asc' },
          },
        },
      });
    }

    return this.prisma.$transaction(async (tx) => {
      await tx.transport.update({
        where: { id },
        data: normalized.data as Prisma.TransportUncheckedUpdateInput,
      });

      await tx.transportFareClass.deleteMany({ where: { transportId: id } });

      if (normalized.fareClasses.length > 0) {
        await tx.transportFareClass.createMany({
          data: normalized.fareClasses.map((fareClass) => ({
            transportId: id,
            code: fareClass.code,
            name: fareClass.name,
            baggageKg: fareClass.baggageKg,
            seats: fareClass.seats,
            price: fareClass.price,
          })),
        });
      }

      return tx.transport.findUniqueOrThrow({
        where: { id },
        include: {
          fromIsland: true,
          toIsland: true,
          vendor: true,
          fareClasses: {
            orderBy: { code: 'asc' },
          },
        },
      });
    });
  }

  async remove(id: string, actor?: RequestActor) {
    await this.assertVendorScopedAccess(id, undefined, actor);
    await this.ensureTransportExists(id);

    try {
      await this.prisma.transport.delete({ where: { id } });
    } catch (error) {
      if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === 'P2003') {
        throw new BadRequestException('Transport cannot be deleted while referenced by bookings');
      }

      throw error;
    }
  }

  private async ensureTransportExists(id: string) {
    const existing = await this.prisma.transport.findUnique({ where: { id }, select: { id: true } });
    if (!existing) {
      throw new NotFoundException('Transport not found');
    }
  }

  private assertVendorScopedCreate(payload: TransportUpsertPayload, actor?: RequestActor) {
    if (actor?.role !== 'VENDOR') {
      return;
    }

    const scopedVendorId = this.parseActorVendorId(actor.vendorId);
    const providedVendorId = this.parsePayloadVendorId(payload.vendorId);
    if (providedVendorId && providedVendorId !== scopedVendorId) {
      throw new ForbiddenException('Vendor users can only manage their own vendor resources');
    }

    payload.vendorId = scopedVendorId;
  }

  private async assertVendorScopedAccess(id: string, payload: TransportUpsertPayload | undefined, actor?: RequestActor) {
    if (actor?.role !== 'VENDOR') {
      return;
    }

    const scopedVendorId = this.parseActorVendorId(actor.vendorId);
    const existing = await this.prisma.transport.findUnique({ where: { id }, select: { vendorId: true } });
    if (!existing) {
      throw new NotFoundException('Transport not found');
    }

    const existingVendorIdStr = existing.vendorId != null ? existing.vendorId.toString() : '';
    if (existingVendorIdStr !== scopedVendorId) {
      throw new ForbiddenException('Vendor users can only manage their own vendor resources');
    }

    if (payload && Object.prototype.hasOwnProperty.call(payload, 'vendorId')) {
      const providedVendorId = this.parsePayloadVendorId(payload.vendorId);
      if (providedVendorId !== undefined && providedVendorId !== scopedVendorId) {
        throw new ForbiddenException('Vendor users can only manage their own vendor resources');
      }

      payload.vendorId = scopedVendorId;
    }
  }

  private parseActorVendorId(value: unknown): string {
    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new ForbiddenException('Vendor scope is missing for authenticated vendor user');
    }

    return value.trim();
  }

  private parsePayloadVendorId(value: unknown): string | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException('Expected non-empty string value');
    }

    return value.trim();
  }

  private async normalizeTransportPayload(payload: TransportUpsertPayload, options: { partial: boolean }): Promise<NormalizedTransportPayload> {
    const vendorId = this.parseOptionalString(payload.vendorId);
    const type = this.parseOptionalString(payload.type)?.toUpperCase();
    const code = this.parseOptionalNullableString(payload.code);
    const fromIslandId = this.parseOptionalNullablePositiveInt(payload.fromIslandId);
    const toIslandId = this.parseOptionalNullablePositiveInt(payload.toIslandId);
    const departure = this.parseOptionalNullableDate(payload.departure);
    const arrival = this.parseOptionalNullableDate(payload.arrival);
    const capacity = this.parseOptionalNullableInt(payload.capacity);
    const price = this.parseOptionalDecimal(payload.price);
    const fareClasses = this.parseOptionalFareClasses(payload.fareClasses);

    if (!options.partial) {
      if (!type || price === null) {
        throw new BadRequestException('type and price are required');
      }
    }

    if (vendorId) {
      const vendor = await this.prisma.vendor.findUnique({ where: { id: vendorId.toString() }, select: { id: true } });
      if (!vendor) {
        throw new BadRequestException('vendorId does not exist');
      }
    }

    if (fromIslandId) {
      const island = await this.prisma.island.findUnique({ where: { id: fromIslandId }, select: { id: true } });
      if (!island) {
        throw new BadRequestException('fromIslandId does not exist');
      }
    }

    if (toIslandId) {
      const island = await this.prisma.island.findUnique({ where: { id: toIslandId }, select: { id: true } });
      if (!island) {
        throw new BadRequestException('toIslandId does not exist');
      }
    }

    const data: Record<string, unknown> = {};

    if (vendorId !== undefined) data.vendorId = vendorId.toString();
    if (type !== undefined) data.type = type;
    if (code !== undefined) data.code = code;
    if (fromIslandId !== undefined) data.fromIslandId = fromIslandId;
    if (toIslandId !== undefined) data.toIslandId = toIslandId;
    if (departure !== undefined) data.departure = departure;
    if (arrival !== undefined) data.arrival = arrival;
    if (capacity !== undefined) data.capacity = capacity;
    if (price !== null) data.price = price;

    if (Object.keys(data).length === 0) {
      throw new BadRequestException('No updatable fields provided');
    }

    return {
      data,
      fareClasses,
    };
  }

  private parseOptionalFareClasses(value: unknown): NormalizedFareClass[] | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (!Array.isArray(value)) {
      throw new BadRequestException('fareClasses must be an array');
    }

    const normalized = value.map((item) => {
      if (!item || typeof item !== 'object') {
        throw new BadRequestException('Each fare class must be an object');
      }

      const fareClass = item as Record<string, unknown>;
      const code = this.parseOptionalString(fareClass.code)?.toUpperCase();
      const name = this.parseOptionalString(fareClass.name);
      const baggageKg = this.parseOptionalNullableInt(fareClass.baggageKg);
      const seats = this.parseOptionalNullableInt(fareClass.seats);
      const price = this.parseOptionalDecimal(fareClass.price);

      if (!code || !name || price === null) {
        throw new BadRequestException('Each fare class requires code, name, and non-negative price');
      }

      return {
        code,
        name,
        baggageKg: baggageKg ?? null,
        seats: seats ?? null,
        price,
      };
    });

    const seenCodes = new Set<string>();
    for (const fareClass of normalized) {
      if (seenCodes.has(fareClass.code)) {
        throw new BadRequestException('fareClasses contains duplicate code values');
      }

      seenCodes.add(fareClass.code);
    }

    return normalized;
  }

  private parseOptionalString(value: unknown): string | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException('Expected non-empty string value');
    }

    return value.trim();
  }

  private parseOptionalNullableString(value: unknown): string | null | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (value === null) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('Expected string value');
    }

    return value.trim();
  }

  private parseOptionalNullableInt(value: unknown): number | null | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (value === null) {
      return null;
    }

    const parsed = typeof value === 'number' ? value : Number(value);
    if (!Number.isInteger(parsed) || parsed < 0) {
      throw new BadRequestException('Expected non-negative integer value');
    }

    return parsed;
  }

  private parseOptionalNullablePositiveInt(value: unknown): number | null | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (value === null) {
      return null;
    }

    const parsed = typeof value === 'number' ? value : Number(value);
    if (!Number.isInteger(parsed) || parsed <= 0) {
      throw new BadRequestException('Expected positive integer value');
    }

    return parsed;
  }

  private parseOptionalNullableDate(value: unknown): Date | null | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (value === null) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('Expected ISO date string value');
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
      throw new BadRequestException('Invalid date value');
    }

    return parsed;
  }

  private parseOptionalDecimal(value: unknown): Prisma.Decimal | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'number' ? value : Number(value);
    if (!Number.isFinite(parsed) || parsed < 0) {
      throw new BadRequestException('price must be a non-negative number');
    }

    return new Prisma.Decimal(parsed);
  }

  private parseDisruptionStatus(value: unknown): 'DELAYED' | 'CANCELLED' | 'WEATHER_CANCELLED' | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('status must be DELAYED, CANCELLED, or WEATHER_CANCELLED');
    }

    const normalized = value.toUpperCase();
    if (normalized === 'DELAYED' || normalized === 'CANCELLED' || normalized === 'WEATHER_CANCELLED') {
      return normalized;
    }

    throw new BadRequestException('status must be DELAYED, CANCELLED, or WEATHER_CANCELLED');
  }

  private parseDateRange(value: string) {
    const trimmed = value.trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) {
      throw new BadRequestException('date must be in YYYY-MM-DD format');
    }

    const start = new Date(`${trimmed}T00:00:00.000Z`);
    const end = new Date(start.getTime() + 24 * 60 * 60 * 1000);
    return { start, end };
  }

  private async attachSeatInventory<TRow extends { id: string; capacity: number | null; [key: string]: unknown }>(
    transports: TRow[],
  ) {
    if (transports.length === 0) {
      return transports;
    }

    const transportIds = transports.map((transport) => transport.id);
    const groupedBookings = await this.prisma.booking.groupBy({
      by: ['transportId'],
      where: {
        transportId: { in: transportIds },
        status: { in: ['PENDING', 'HOLD', 'CONFIRMED'] },
      },
      _sum: { guests: true },
    });

    const reservedByTransport = new Map<string, number>();
    for (const row of groupedBookings) {
      if (!row.transportId) {
        continue;
      }

      reservedByTransport.set(row.transportId, row._sum.guests ?? 0);
    }

    return transports.map((transport) => {
      const reservedSeats = reservedByTransport.get(transport.id) ?? 0;
      const availableSeats = transport.capacity === null ? null : Math.max(transport.capacity - reservedSeats, 0);

      return {
        ...transport,
        seatInventory: {
          capacity: transport.capacity,
          reservedSeats,
          availableSeats,
          soldOut: availableSeats !== null && availableSeats === 0,
        },
      };
    });
  }

  private async attachFareClassInventory<TRow extends { id: string; fareClasses?: Array<{ code: string; seats: number | null; [key: string]: unknown }> }>(
    transports: TRow[],
  ) {
    if (transports.length === 0) {
      return transports;
    }

    const transportIds = transports.map((transport) => transport.id);
    const groupedBookings = await this.prisma.booking.groupBy({
      by: ['transportId', 'transportFareClassCode'],
      where: {
        transportId: { in: transportIds },
        status: { in: ['PENDING', 'HOLD', 'CONFIRMED'] },
      },
      _sum: { guests: true },
    });

    const reservedByClass = new Map<string, number>();
    for (const row of groupedBookings) {
      if (!row.transportId || !row.transportFareClassCode) {
        continue;
      }

      reservedByClass.set(`${row.transportId}:${row.transportFareClassCode}`, row._sum.guests ?? 0);
    }

    return transports.map((transport) => {
      if (!Array.isArray(transport.fareClasses)) {
        return transport;
      }

      return {
        ...transport,
        fareClasses: transport.fareClasses.map((fareClass) => {
          const reservedSeats = reservedByClass.get(`${transport.id}:${fareClass.code}`) ?? 0;
          const availableSeats = fareClass.seats === null ? null : Math.max(fareClass.seats - reservedSeats, 0);

          return {
            ...fareClass,
            seatInventory: {
              capacity: fareClass.seats,
              reservedSeats,
              availableSeats,
              soldOut: availableSeats !== null && availableSeats === 0,
            },
          };
        }),
      };
    });
  }

  private async attachActiveDisruption<TRow extends { id: string; [key: string]: unknown }>(transports: TRow[]) {
    if (transports.length === 0) {
      return transports;
    }

    const transportIds = transports.map((transport) => transport.id);
    const disruptions = await this.prisma.transportDisruption.findMany({
      where: {
        transportId: { in: transportIds },
        resolvedAt: null,
      },
      include: {
        replacementTransport: true,
      },
      orderBy: { createdAt: 'desc' },
    });

    const activeByTransport = new Map<string, (typeof disruptions)[number]>();
    for (const disruption of disruptions) {
      if (!activeByTransport.has(disruption.transportId)) {
        activeByTransport.set(disruption.transportId, disruption);
      }
    }

    return transports.map((transport) => ({
      ...transport,
      activeDisruption: activeByTransport.get(transport.id) ?? null,
    }));
  }
}


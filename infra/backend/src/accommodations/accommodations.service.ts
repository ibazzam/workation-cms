import { BadRequestException, ForbiddenException, Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../prisma.service';

type AccommodationUpsertPayload = {
  vendorId?: unknown;
  islandId?: unknown;
  title?: unknown;
  slug?: unknown;
  description?: unknown;
  type?: unknown;
  rooms?: unknown;
  minStayNights?: unknown;
  price?: unknown;
};

type AccommodationBlackoutPayload = {
  startDate?: unknown;
  endDate?: unknown;
  reason?: unknown;
};

type AccommodationSeasonalRatePayload = {
  name?: unknown;
  startDate?: unknown;
  endDate?: unknown;
  nightlyPrice?: unknown;
  minNights?: unknown;
  priority?: unknown;
};

type RequestActor = {
  id?: string;
  role?: string;
  vendorId?: string;
};

@Injectable()
export class AccommodationsService {
  constructor(private readonly prisma: PrismaService) {}

  async list(filters: { islandId?: number; q?: string }) {
    return this.prisma.accommodation.findMany({
      where: {
        islandId: filters.islandId,
        OR: filters.q
          ? [
              { title: { contains: filters.q, mode: 'insensitive' } },
              { slug: { contains: filters.q, mode: 'insensitive' } },
            ]
          : undefined,
      },
      include: {
        island: true,
        vendor: true,
      },
      orderBy: { title: 'asc' },
    });
  }

  async getById(id: string) {
    const accommodation = await this.prisma.accommodation.findUnique({
      where: { id },
      include: {
        blackouts: {
          orderBy: { startDate: 'asc' },
        },
        island: true,
        vendor: true,
      },
    });

    if (!accommodation) {
      throw new NotFoundException('Accommodation not found');
    }

    return accommodation;
  }

  async getAvailability(id: string, payload: { startDate?: unknown; endDate?: unknown; roomsRequested?: unknown }) {
    const accommodation = await this.prisma.accommodation.findUnique({
      where: { id },
      select: {
        id: true,
        rooms: true,
        minStayNights: true,
      },
    });

    if (!accommodation) {
      throw new NotFoundException('Accommodation not found');
    }

    const startDate = this.parseRequiredDate(payload.startDate, 'startDate');
    const endDate = this.parseRequiredDate(payload.endDate, 'endDate');
    if (endDate.getTime() <= startDate.getTime()) {
      throw new BadRequestException('endDate must be after startDate');
    }

    const roomsRequested = this.parseOptionalPositiveInt(payload.roomsRequested) ?? 1;
    const stayNights = this.calculateStayNights(startDate, endDate);

    const [overlappingBlackouts, overlappingBookingsCount] = await Promise.all([
      this.prisma.accommodationBlackout.findMany({
        where: {
          accommodationId: id,
          startDate: { lt: endDate },
          endDate: { gt: startDate },
        },
        orderBy: { startDate: 'asc' },
      }),
      this.prisma.booking.count({
        where: {
          accommodationId: id,
          status: { in: ['PENDING', 'HOLD', 'CONFIRMED'] },
          startDate: { lt: endDate },
          endDate: { gt: startDate },
        },
      }),
    ]);

    const blackoutBlocked = overlappingBlackouts.length > 0;
    const minStayBlocked = stayNights < accommodation.minStayNights;
    const availableRooms = accommodation.rooms === null ? null : Math.max(accommodation.rooms - overlappingBookingsCount, 0);
    const inventoryBlocked = availableRooms !== null && availableRooms < roomsRequested;

    return {
      accommodationId: id,
      startDate: startDate.toISOString(),
      endDate: endDate.toISOString(),
      stayNights,
      minStayNights: accommodation.minStayNights,
      roomsRequested,
      roomsTotal: accommodation.rooms,
      roomsBooked: overlappingBookingsCount,
      roomsAvailable: availableRooms,
      blocked: {
        blackout: blackoutBlocked,
        minStay: minStayBlocked,
        inventory: inventoryBlocked,
      },
      available: !blackoutBlocked && !minStayBlocked && !inventoryBlocked,
      blackouts: overlappingBlackouts,
    };
  }

  async getQuote(id: string, payload: { startDate?: unknown; endDate?: unknown; roomsRequested?: unknown }) {
    const accommodation = await this.prisma.accommodation.findUnique({
      where: { id },
      select: {
        id: true,
        price: true,
        rooms: true,
        minStayNights: true,
      },
    });

    if (!accommodation) {
      throw new NotFoundException('Accommodation not found');
    }

    const startDate = this.parseRequiredDate(payload.startDate, 'startDate');
    const endDate = this.parseRequiredDate(payload.endDate, 'endDate');
    if (endDate.getTime() <= startDate.getTime()) {
      throw new BadRequestException('endDate must be after startDate');
    }

    const roomsRequested = this.parseOptionalPositiveInt(payload.roomsRequested) ?? 1;
    const stayNights = this.calculateStayNights(startDate, endDate);

    const [overlappingBlackouts, overlappingBookingsCount, seasonalRates] = await Promise.all([
      this.prisma.accommodationBlackout.findMany({
        where: {
          accommodationId: id,
          startDate: { lt: endDate },
          endDate: { gt: startDate },
        },
        orderBy: { startDate: 'asc' },
      }),
      this.prisma.booking.count({
        where: {
          accommodationId: id,
          status: { in: ['PENDING', 'HOLD', 'CONFIRMED'] },
          startDate: { lt: endDate },
          endDate: { gt: startDate },
        },
      }),
      this.prisma.accommodationSeasonalRate.findMany({
        where: {
          accommodationId: id,
          startDate: { lt: endDate },
          endDate: { gt: startDate },
        },
        orderBy: [{ priority: 'desc' }, { createdAt: 'desc' }],
      }),
    ]);

    const nightlyBreakdown = this.buildNightlyBreakdown({
      startDate,
      endDate,
      baseNightlyPrice: accommodation.price,
      stayNights,
      seasonalRates,
    });

    const quoteTotal = nightlyBreakdown.reduce((sum, night) => sum.plus(night.nightlyPrice), new Prisma.Decimal(0));

    const blackoutBlocked = overlappingBlackouts.length > 0;
    const minStayBlocked = stayNights < accommodation.minStayNights;
    const availableRooms = accommodation.rooms === null ? null : Math.max(accommodation.rooms - overlappingBookingsCount, 0);
    const inventoryBlocked = availableRooms !== null && availableRooms < roomsRequested;

    return {
      accommodationId: id,
      startDate: startDate.toISOString(),
      endDate: endDate.toISOString(),
      stayNights,
      roomsRequested,
      quote: {
        currency: 'USD',
        nightlyTotal: quoteTotal.toNumber(),
        nightlyBreakdown: nightlyBreakdown.map((night) => ({
          date: night.date,
          nightlyPrice: night.nightlyPrice.toNumber(),
          source: night.source,
          seasonalRateId: night.seasonalRateId,
          seasonalRateName: night.seasonalRateName,
        })),
      },
      blocked: {
        blackout: blackoutBlocked,
        minStay: minStayBlocked,
        inventory: inventoryBlocked,
      },
      available: !blackoutBlocked && !minStayBlocked && !inventoryBlocked,
    };
  }

  async create(payload: AccommodationUpsertPayload, actor?: RequestActor) {
    this.assertVendorScopedCreate(payload, actor);
    const normalized = await this.normalizeAccommodationPayload(payload, { partial: false });

    return this.prisma.accommodation.create({
      data: normalized as Prisma.AccommodationUncheckedCreateInput,
      include: {
        island: true,
        vendor: true,
      },
    });
  }

  async update(id: string, payload: AccommodationUpsertPayload, actor?: RequestActor) {
    await this.assertVendorScopedAccess(id, payload, actor);
    await this.ensureAccommodationExists(id);
    const normalized = await this.normalizeAccommodationPayload(payload, { partial: true });

    return this.prisma.accommodation.update({
      where: { id },
      data: normalized as Prisma.AccommodationUncheckedUpdateInput,
      include: {
        island: true,
        vendor: true,
      },
    });
  }

  async remove(id: string, actor?: RequestActor) {
    await this.assertVendorScopedAccess(id, undefined, actor);
    await this.ensureAccommodationExists(id);

    try {
      await this.prisma.accommodation.delete({ where: { id } });
    } catch (error) {
      if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === 'P2003') {
        throw new BadRequestException('Accommodation cannot be deleted while referenced by bookings');
      }

      throw error;
    }
  }

  async createBlackout(id: string, payload: AccommodationBlackoutPayload, actor?: RequestActor) {
    await this.assertVendorScopedAccess(id, undefined, actor);
    await this.ensureAccommodationExists(id);

    const startDate = this.parseRequiredDate(payload.startDate, 'startDate');
    const endDate = this.parseRequiredDate(payload.endDate, 'endDate');
    if (endDate.getTime() <= startDate.getTime()) {
      throw new BadRequestException('endDate must be after startDate');
    }

    const reason = this.parseOptionalNullableString(payload.reason);

    return this.prisma.accommodationBlackout.create({
      data: {
        accommodationId: id,
        startDate,
        endDate,
        reason: reason ?? null,
      },
    });
  }

  async createSeasonalRate(id: string, payload: AccommodationSeasonalRatePayload, actor?: RequestActor) {
    await this.assertVendorScopedAccess(id, undefined, actor);
    await this.ensureAccommodationExists(id);

    const name = this.parseOptionalString(payload.name);
    const startDate = this.parseRequiredDate(payload.startDate, 'startDate');
    const endDate = this.parseRequiredDate(payload.endDate, 'endDate');
    const nightlyPrice = this.parseOptionalDecimal(payload.nightlyPrice);
    const minNights = this.parseOptionalPositiveInt(payload.minNights);
    const priority = this.parseOptionalIntWithDefault(payload.priority, 0);

    if (!name || nightlyPrice === null) {
      throw new BadRequestException('name and nightlyPrice are required');
    }

    if (endDate.getTime() <= startDate.getTime()) {
      throw new BadRequestException('endDate must be after startDate');
    }

    return this.prisma.accommodationSeasonalRate.create({
      data: {
        accommodationId: id,
        name,
        startDate,
        endDate,
        nightlyPrice,
        minNights: minNights ?? null,
        priority,
      },
    });
  }

  async removeBlackout(id: string, blackoutId: string, actor?: RequestActor) {
    await this.assertVendorScopedAccess(id, undefined, actor);

    const blackout = await this.prisma.accommodationBlackout.findUnique({
      where: { id: blackoutId },
      select: { id: true, accommodationId: true },
    });

    if (!blackout || blackout.accommodationId !== id) {
      throw new NotFoundException('Accommodation blackout not found');
    }

    await this.prisma.accommodationBlackout.delete({ where: { id: blackout.id } });
  }

  async removeSeasonalRate(id: string, rateId: string, actor?: RequestActor) {
    await this.assertVendorScopedAccess(id, undefined, actor);

    const seasonalRate = await this.prisma.accommodationSeasonalRate.findUnique({
      where: { id: rateId },
      select: { id: true, accommodationId: true },
    });

    if (!seasonalRate || seasonalRate.accommodationId !== id) {
      throw new NotFoundException('Accommodation seasonal rate not found');
    }

    await this.prisma.accommodationSeasonalRate.delete({ where: { id: seasonalRate.id } });
  }

  private async ensureAccommodationExists(id: string) {
    const existing = await this.prisma.accommodation.findUnique({ where: { id }, select: { id: true } });
    if (!existing) {
      throw new NotFoundException('Accommodation not found');
    }
  }

  private assertVendorScopedCreate(payload: AccommodationUpsertPayload, actor?: RequestActor) {
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

  private async assertVendorScopedAccess(id: string, payload: AccommodationUpsertPayload | undefined, actor?: RequestActor) {
    if (actor?.role !== 'VENDOR') {
      return;
    }

    const scopedVendorId = this.parseActorVendorId(actor.vendorId);
    const existing = await this.prisma.accommodation.findUnique({ where: { id }, select: { vendorId: true } });
    if (!existing) {
      throw new NotFoundException('Accommodation not found');
    }

    if (existing.vendorId !== scopedVendorId) {
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

  private async normalizeAccommodationPayload(payload: AccommodationUpsertPayload, options: { partial: boolean }) {
    const vendorId = this.parseOptionalString(payload.vendorId);
    const islandId = this.parseOptionalInt(payload.islandId);
    const title = this.parseOptionalString(payload.title);
    const slugInput = this.parseOptionalString(payload.slug);
    const description = this.parseOptionalNullableString(payload.description);
    const rawType = this.parseOptionalNullableString(payload.type);
    const type = rawType === undefined ? undefined : rawType === null ? null : rawType.toUpperCase();
    const rooms = this.parseOptionalNullableInt(payload.rooms);
    const minStayNights = this.parseOptionalPositiveInt(payload.minStayNights);
    const price = this.parseOptionalDecimal(payload.price);

    if (!options.partial) {
      if (!vendorId || !islandId || !title || price === null) {
        throw new BadRequestException('vendorId, islandId, title, and price are required');
      }
    }

    if (vendorId) {
      const vendor = await this.prisma.vendor.findUnique({ where: { id: vendorId }, select: { id: true } });
      if (!vendor) {
        throw new BadRequestException('vendorId does not exist');
      }
    }

    if (islandId) {
      const island = await this.prisma.island.findUnique({ where: { id: islandId }, select: { id: true } });
      if (!island) {
        throw new BadRequestException('islandId does not exist');
      }
    }

    const data: Record<string, unknown> = {};

    if (vendorId) data.vendorId = vendorId;
    if (islandId) data.islandId = islandId;
    if (title) data.title = title;
    if (description !== undefined) data.description = description;
    if (type !== undefined) data.type = type;
    if (rooms !== undefined) data.rooms = rooms;
    if (minStayNights !== undefined) data.minStayNights = minStayNights;
    if (price !== null) data.price = price;

    const effectiveSlug = slugInput ?? (title ? this.slugify(title) : undefined);
    if (effectiveSlug) {
      data.slug = effectiveSlug;
    }

    if (Object.keys(data).length === 0) {
      throw new BadRequestException('No updatable fields provided');
    }

    return data;
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

  private parseOptionalInt(value: unknown): number | undefined {
    if (value === undefined) {
      return undefined;
    }

    const parsed = typeof value === 'number' ? value : Number(value);
    if (!Number.isInteger(parsed) || parsed <= 0) {
      throw new BadRequestException('Expected positive integer value');
    }

    return parsed;
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
      throw new BadRequestException('rooms must be a non-negative integer');
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

  private parseRequiredDate(value: unknown, fieldName: string): Date {
    if (typeof value !== 'string') {
      throw new BadRequestException(`${fieldName} must be a valid date string`);
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
      throw new BadRequestException(`${fieldName} must be a valid date string`);
    }

    return parsed;
  }

  private calculateStayNights(startDate: Date, endDate: Date): number {
    const millisecondsPerDay = 24 * 60 * 60 * 1000;
    return Math.ceil((endDate.getTime() - startDate.getTime()) / millisecondsPerDay);
  }

  private parseOptionalPositiveInt(value: unknown): number | undefined {
    if (value === undefined) {
      return undefined;
    }

    const parsed = typeof value === 'number' ? value : Number(value);
    if (!Number.isInteger(parsed) || parsed <= 0) {
      throw new BadRequestException('Expected positive integer value');
    }

    return parsed;
  }

  private parseOptionalIntWithDefault(value: unknown, defaultValue: number): number {
    if (value === undefined) {
      return defaultValue;
    }

    const parsed = typeof value === 'number' ? value : Number(value);
    if (!Number.isInteger(parsed)) {
      throw new BadRequestException('Expected integer value');
    }

    return parsed;
  }

  private buildNightlyBreakdown(options: {
    startDate: Date;
    endDate: Date;
    baseNightlyPrice: Prisma.Decimal;
    stayNights: number;
    seasonalRates: Array<{
      id: string;
      name: string;
      startDate: Date;
      endDate: Date;
      nightlyPrice: Prisma.Decimal;
      minNights: number | null;
    }>;
  }) {
    const nights: Array<{
      date: string;
      nightlyPrice: Prisma.Decimal;
      source: 'BASE' | 'SEASONAL';
      seasonalRateId: string | null;
      seasonalRateName: string | null;
    }> = [];

    const totalNights = options.stayNights;
    for (let index = 0; index < totalNights; index += 1) {
      const currentNight = new Date(options.startDate.getTime() + index * 24 * 60 * 60 * 1000);
      const nextNight = new Date(currentNight.getTime() + 24 * 60 * 60 * 1000);

      const matchedRate = options.seasonalRates.find((rate) => {
        const withinWindow = rate.startDate.getTime() < nextNight.getTime() && rate.endDate.getTime() > currentNight.getTime();
        const nightsAllowed = rate.minNights === null || totalNights >= rate.minNights;
        return withinWindow && nightsAllowed;
      });

      if (matchedRate) {
        nights.push({
          date: currentNight.toISOString().slice(0, 10),
          nightlyPrice: matchedRate.nightlyPrice,
          source: 'SEASONAL',
          seasonalRateId: matchedRate.id,
          seasonalRateName: matchedRate.name,
        });
        continue;
      }

      nights.push({
        date: currentNight.toISOString().slice(0, 10),
        nightlyPrice: options.baseNightlyPrice,
        source: 'BASE',
        seasonalRateId: null,
        seasonalRateName: null,
      });
    }

    return nights;
  }

  private slugify(value: string) {
    return value
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-');
  }
}

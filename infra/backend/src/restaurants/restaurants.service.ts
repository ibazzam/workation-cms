import { BadRequestException, ForbiddenException, Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../prisma.service';

type RestaurantUpsertPayload = {
  vendorId?: unknown;
  islandId?: unknown;
  name?: unknown;
  description?: unknown;
  cuisineType?: unknown;
  totalTables?: unknown;
  minPartySize?: unknown;
  maxPartySize?: unknown;
  depositPolicyType?: unknown;
  depositAmount?: unknown;
  depositCurrency?: unknown;
  active?: unknown;
};

type SeatingWindowUpsertPayload = {
  startAt?: unknown;
  endAt?: unknown;
  tableCountOverride?: unknown;
  minPartySizeOverride?: unknown;
  maxPartySizeOverride?: unknown;
  status?: unknown;
  note?: unknown;
};

type RequestActor = {
  role?: string;
  vendorId?: string;
};

@Injectable()
export class RestaurantsService {
  constructor(private readonly prisma: PrismaService) {}

  async list(filters: {
    islandId?: number;
    vendorId?: string;
    q?: string;
    cuisineType?: string;
    date?: string;
  }) {
    const q = typeof filters.q === 'string' && filters.q.trim().length > 0 ? filters.q.trim() : undefined;
    const cuisineType = this.parseOptionalCuisineType(filters.cuisineType);
    const dayRange = filters.date ? this.parseDayRange(filters.date) : null;

    return this.prisma.restaurant.findMany({
      where: {
        active: true,
        islandId: filters.islandId,
        vendorId: filters.vendorId ? filters.vendorId.toString().trim() : undefined,
        cuisineType: cuisineType ?? undefined,
        ...(q
          ? {
              OR: [
                { name: { contains: q, mode: 'insensitive' } },
                { description: { contains: q, mode: 'insensitive' } },
              ],
            }
          : {}),
        ...(dayRange
          ? {
              seatingWindows: {
                some: {
                  status: 'OPEN',
                  startAt: { gte: dayRange.start, lt: dayRange.end },
                },
              },
            }
          : {}),
      },
      include: {
        vendor: true,
        island: true,
        seatingWindows: {
          where: {
            status: 'OPEN',
            startAt: { gte: new Date() },
          },
          orderBy: { startAt: 'asc' },
          take: 3,
        },
      },
      orderBy: { createdAt: 'desc' },
    });
  }

  async getById(id: string) {
    const restaurant = await this.prisma.restaurant.findUnique({
      where: { id },
      include: {
        vendor: true,
        island: true,
        seatingWindows: {
          orderBy: { startAt: 'asc' },
        },
      },
    });

    if (!restaurant) {
      throw new NotFoundException('Restaurant not found');
    }

    return restaurant;
  }

  async listSeatingWindows(id: string, options: { from?: string; to?: string }) {
    await this.assertRestaurantExists(id);
    const range = this.parseOptionalDateRange(options.from, options.to);

    return this.prisma.restaurantSeatingWindow.findMany({
      where: {
        restaurantId: id,
        ...(range
          ? {
              startAt: { gte: range.from },
              endAt: { lte: range.to },
            }
          : {}),
      },
      orderBy: { startAt: 'asc' },
    });
  }

  async quote(id: string, payload: { windowId?: string; partySize?: string }) {
    const restaurant = await this.prisma.restaurant.findUnique({ where: { id } });
    if (!restaurant) {
      throw new NotFoundException('Restaurant not found');
    }

    if (typeof payload.windowId !== 'string' || payload.windowId.trim().length === 0) {
      throw new BadRequestException('windowId is required');
    }

    const windowId = payload.windowId.trim();
    const partySize = payload.partySize === undefined ? 2 : this.parsePositiveInt(payload.partySize, 'partySize');

    const window = await this.prisma.restaurantSeatingWindow.findUnique({ where: { id: windowId } });
    if (!window || window.restaurantId !== id) {
      throw new NotFoundException('Restaurant seating window not found');
    }

    const minPartySize = window.minPartySizeOverride ?? restaurant.minPartySize;
    const maxPartySize = window.maxPartySizeOverride ?? restaurant.maxPartySize;
    const tableCount = window.tableCountOverride ?? restaurant.totalTables;

    const checks = {
      windowOpen: window.status === 'OPEN',
      windowInFuture: window.startAt.getTime() > Date.now(),
      partySizeMin: partySize >= minPartySize,
      partySizeMax: partySize <= maxPartySize,
      tableCapacity: tableCount > 0,
    };

    const canBook = Object.values(checks).every((value) => value === true);
    const depositAmount = this.computeDepositAmount(restaurant, partySize);

    return {
      restaurantId: restaurant.id,
      windowId: window.id,
      partySize,
      checks,
      canBook,
      seatingPolicy: {
        minPartySize,
        maxPartySize,
        tableCount,
      },
      depositPolicy: {
        type: restaurant.depositPolicyType,
        amount: depositAmount,
        currency: restaurant.depositCurrency,
      },
      window: {
        startAt: window.startAt.toISOString(),
        endAt: window.endAt.toISOString(),
        status: window.status,
        note: window.note,
      },
    };
  }

  async create(payload: RestaurantUpsertPayload, actor?: RequestActor) {
    const normalized = this.normalizeRestaurantPayload(payload, { partial: false, actor });

    return this.prisma.restaurant.create({
      data: normalized as Prisma.RestaurantUncheckedCreateInput,
      include: {
        vendor: true,
        island: true,
      },
    });
  }

  async update(id: string, payload: RestaurantUpsertPayload, actor?: RequestActor) {
    const existing = await this.prisma.restaurant.findUnique({ where: { id } });
    if (!existing) {
      throw new NotFoundException('Restaurant not found');
    }

    this.assertVendorScopedAccess(existing.vendorId, actor);

    const normalized = this.normalizeRestaurantPayload(payload, {
      partial: true,
      actor,
      existingVendorId: existing.vendorId,
    });

    return this.prisma.restaurant.update({
      where: { id },
      data: normalized as Prisma.RestaurantUncheckedUpdateInput,
      include: {
        vendor: true,
        island: true,
      },
    });
  }

  async remove(id: string, actor?: RequestActor) {
    const existing = await this.prisma.restaurant.findUnique({ where: { id } });
    if (!existing) {
      throw new NotFoundException('Restaurant not found');
    }

    this.assertVendorScopedAccess(existing.vendorId, actor);

    await this.prisma.restaurant.delete({ where: { id } });
  }

  async createSeatingWindow(id: string, payload: SeatingWindowUpsertPayload, actor?: RequestActor) {
    const restaurant = await this.prisma.restaurant.findUnique({ where: { id } });
    if (!restaurant) {
      throw new NotFoundException('Restaurant not found');
    }

    this.assertVendorScopedAccess(restaurant.vendorId, actor);

    const normalized = this.normalizeSeatingWindowPayload(payload, { partial: false });

    return this.prisma.restaurantSeatingWindow.create({
      data: {
        restaurantId: restaurant.id,
        ...normalized,
      } as Prisma.RestaurantSeatingWindowUncheckedCreateInput,
    });
  }

  async updateSeatingWindow(id: string, windowId: string, payload: SeatingWindowUpsertPayload, actor?: RequestActor) {
    const restaurant = await this.prisma.restaurant.findUnique({ where: { id } });
    if (!restaurant) {
      throw new NotFoundException('Restaurant not found');
    }

    this.assertVendorScopedAccess(restaurant.vendorId, actor);

    const existingWindow = await this.prisma.restaurantSeatingWindow.findUnique({ where: { id: windowId } });
    if (!existingWindow || existingWindow.restaurantId !== id) {
      throw new NotFoundException('Restaurant seating window not found');
    }

    const normalized = this.normalizeSeatingWindowPayload(payload, { partial: true });

    return this.prisma.restaurantSeatingWindow.update({
      where: { id: windowId },
      data: normalized as Prisma.RestaurantSeatingWindowUncheckedUpdateInput,
    });
  }

  async removeSeatingWindow(id: string, windowId: string, actor?: RequestActor) {
    const restaurant = await this.prisma.restaurant.findUnique({ where: { id } });
    if (!restaurant) {
      throw new NotFoundException('Restaurant not found');
    }

    this.assertVendorScopedAccess(restaurant.vendorId, actor);

    const existingWindow = await this.prisma.restaurantSeatingWindow.findUnique({ where: { id: windowId } });
    if (!existingWindow || existingWindow.restaurantId !== id) {
      throw new NotFoundException('Restaurant seating window not found');
    }

    await this.prisma.restaurantSeatingWindow.delete({ where: { id: windowId } });
  }

  private normalizeRestaurantPayload(
    payload: RestaurantUpsertPayload,
    options: { partial: boolean; actor?: RequestActor; existingVendorId?: string },
  ) {
    const vendorId = this.resolveVendorId(payload.vendorId, options.actor, options.existingVendorId, options.partial);
    const islandId = this.parseOptionalInt(payload.islandId, 'islandId');
    const name = this.parseOptionalString(payload.name, 'name', 180);
    const description = this.parseOptionalNullableText(payload.description, 5000);
    const cuisineType = this.parseOptionalCuisineType(payload.cuisineType);
    const totalTables = this.parseOptionalInt(payload.totalTables, 'totalTables');
    const minPartySize = this.parseOptionalInt(payload.minPartySize, 'minPartySize');
    const maxPartySize = this.parseOptionalInt(payload.maxPartySize, 'maxPartySize');
    const depositPolicyType = this.parseOptionalDepositPolicyType(payload.depositPolicyType);
    const depositAmount = this.parseOptionalMoney(payload.depositAmount, 'depositAmount');
    const depositCurrency = this.parseOptionalCurrency(payload.depositCurrency);
    const active = this.parseOptionalBoolean(payload.active, 'active');

    if (!options.partial) {
      if (!vendorId) throw new BadRequestException('vendorId is required');
      if (!islandId) throw new BadRequestException('islandId is required');
      if (!name) throw new BadRequestException('name is required');
      if (!cuisineType) throw new BadRequestException('cuisineType is required');
      if (!totalTables) throw new BadRequestException('totalTables is required');
      if (!minPartySize) throw new BadRequestException('minPartySize is required');
      if (!maxPartySize) throw new BadRequestException('maxPartySize is required');
      if (!depositPolicyType) throw new BadRequestException('depositPolicyType is required');
      if (depositPolicyType === 'FIXED' && !depositAmount) throw new BadRequestException('depositAmount is required for FIXED policy');
    }

    if (minPartySize !== undefined && maxPartySize !== undefined && minPartySize > maxPartySize) {
      throw new BadRequestException('minPartySize cannot be greater than maxPartySize');
    }

    const data: Record<string, unknown> = {};
    if (vendorId !== undefined) data.vendorId = vendorId.toString();
    if (islandId !== undefined) data.islandId = islandId;
    if (name !== undefined) data.name = name;
    if (description !== undefined) data.description = description;
    if (cuisineType !== undefined) data.cuisineType = cuisineType;
    if (totalTables !== undefined) data.totalTables = totalTables;
    if (minPartySize !== undefined) data.minPartySize = minPartySize;
    if (maxPartySize !== undefined) data.maxPartySize = maxPartySize;
    if (depositPolicyType !== undefined) data.depositPolicyType = depositPolicyType;
    if (depositAmount !== undefined) data.depositAmount = depositAmount;
    if (depositCurrency !== undefined) data.depositCurrency = depositCurrency;
    if (active !== undefined) data.active = active;

    return data;
  }

  private normalizeSeatingWindowPayload(payload: SeatingWindowUpsertPayload, options: { partial: boolean }) {
    const startAt = this.parseOptionalDate(payload.startAt, 'startAt');
    const endAt = this.parseOptionalDate(payload.endAt, 'endAt');
    const tableCountOverride = this.parseOptionalInt(payload.tableCountOverride, 'tableCountOverride');
    const minPartySizeOverride = this.parseOptionalInt(payload.minPartySizeOverride, 'minPartySizeOverride');
    const maxPartySizeOverride = this.parseOptionalInt(payload.maxPartySizeOverride, 'maxPartySizeOverride');
    const status = this.parseOptionalWindowStatus(payload.status);
    const note = this.parseOptionalNullableText(payload.note, 500);

    if (!options.partial) {
      if (!startAt) throw new BadRequestException('startAt is required');
      if (!endAt) throw new BadRequestException('endAt is required');
    }

    if (startAt && endAt && startAt.getTime() >= endAt.getTime()) {
      throw new BadRequestException('startAt must be before endAt');
    }

    if (minPartySizeOverride !== undefined && maxPartySizeOverride !== undefined && minPartySizeOverride > maxPartySizeOverride) {
      throw new BadRequestException('minPartySizeOverride cannot be greater than maxPartySizeOverride');
    }

    const data: Record<string, unknown> = {};
    if (startAt !== undefined) data.startAt = startAt;
    if (endAt !== undefined) data.endAt = endAt;
    if (tableCountOverride !== undefined) data.tableCountOverride = tableCountOverride;
    if (minPartySizeOverride !== undefined) data.minPartySizeOverride = minPartySizeOverride;
    if (maxPartySizeOverride !== undefined) data.maxPartySizeOverride = maxPartySizeOverride;
    if (status !== undefined) data.status = status;
    if (note !== undefined) data.note = note;

    return data;
  }

  private computeDepositAmount(
    restaurant: {
      depositPolicyType: string;
      depositAmount: Prisma.Decimal;
      minPartySize: number;
      maxPartySize: number;
      depositCurrency: string;
    },
    partySize: number,
  ) {
    const policy = restaurant.depositPolicyType.toUpperCase();
    if (policy === 'NONE') {
      return 0;
    }

    const base = Number(restaurant.depositAmount);
    if (policy === 'FIXED') {
      return Number(base.toFixed(2));
    }

    if (policy === 'PER_PERSON') {
      return Number((base * partySize).toFixed(2));
    }

    return 0;
  }

  private async assertRestaurantExists(id: string) {
    const row = await this.prisma.restaurant.findUnique({ where: { id }, select: { id: true } });
    if (!row) {
      throw new NotFoundException('Restaurant not found');
    }
  }

  private assertVendorScopedAccess(resourceVendorId: string, actor?: RequestActor) {
    if (actor?.role !== 'VENDOR') {
      return;
    }

    const scopedVendorId = this.parseActorVendorId(actor.vendorId);
    if (scopedVendorId !== resourceVendorId) {
      throw new ForbiddenException('Vendor users can only manage their own restaurants');
    }
  }

  private resolveVendorId(
    payloadVendorId: unknown,
    actor: RequestActor | undefined,
    existingVendorId: string | undefined,
    partial: boolean,
  ): string | undefined {
    if (actor?.role === 'VENDOR') {
      const scopedVendorId = this.parseActorVendorId(actor.vendorId);
      if (payloadVendorId !== undefined && payloadVendorId !== scopedVendorId) {
        throw new ForbiddenException('Vendor users cannot assign other vendor IDs');
      }
      return scopedVendorId;
    }

    const parsedVendorId = this.parseOptionalString(payloadVendorId, 'vendorId', 120);
    if (parsedVendorId !== undefined) {
      return parsedVendorId;
    }

    if (partial) {
      return undefined;
    }

    return existingVendorId;
  }

  private parseDayRange(value: string) {
    const day = value.trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(day)) {
      throw new BadRequestException('date must be YYYY-MM-DD');
    }

    const start = new Date(`${day}T00:00:00.000Z`);
    const end = new Date(start.getTime() + (24 * 60 * 60 * 1000));
    return { start, end };
  }

  private parseOptionalDateRange(from?: string, to?: string) {
    if (!from && !to) {
      return null;
    }

    if (!from || !to) {
      throw new BadRequestException('from and to must both be provided');
    }

    const fromDate = this.parseDate(from, 'from');
    const toDate = this.parseDate(to, 'to');
    if (fromDate.getTime() > toDate.getTime()) {
      throw new BadRequestException('from must be before or equal to to');
    }

    return { from: fromDate, to: toDate };
  }

  private parseDate(value: unknown, fieldName: string): Date {
    if (typeof value !== 'string') {
      throw new BadRequestException(`${fieldName} must be a valid ISO datetime`);
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
      throw new BadRequestException(`${fieldName} must be a valid ISO datetime`);
    }

    return parsed;
  }

  private parseOptionalDate(value: unknown, fieldName: string): Date | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (value === null) {
      return undefined;
    }

    return this.parseDate(value, fieldName);
  }

  private parsePositiveInt(value: unknown, fieldName: string): number {
    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed) || parsed <= 0) {
      throw new BadRequestException(`${fieldName} must be a positive integer`);
    }

    return parsed;
  }

  private parseOptionalInt(value: unknown, fieldName: string): number | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (value === null) {
      return undefined;
    }

    return this.parsePositiveInt(value, fieldName);
  }

  private parseOptionalBoolean(value: unknown, fieldName: string): boolean | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'boolean') {
      throw new BadRequestException(`${fieldName} must be a boolean`);
    }

    return value;
  }

  private parseOptionalMoney(value: unknown, fieldName: string): Prisma.Decimal | undefined {
    if (value === undefined) {
      return undefined;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isFinite(parsed) || parsed < 0) {
      throw new BadRequestException(`${fieldName} must be a non-negative number`);
    }

    return new Prisma.Decimal(parsed.toFixed(2));
  }

  private parseOptionalCuisineType(value: unknown): string | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException('cuisineType must be a non-empty string');
    }

    return value.trim().toUpperCase();
  }

  private parseOptionalCurrency(value: unknown): 'USD' | 'MVR' | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('depositCurrency must be USD or MVR');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'USD' || normalized === 'MVR') {
      return normalized;
    }

    throw new BadRequestException('depositCurrency must be USD or MVR');
  }

  private parseOptionalDepositPolicyType(value: unknown): 'NONE' | 'FIXED' | 'PER_PERSON' | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('depositPolicyType must be NONE, FIXED, or PER_PERSON');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'NONE' || normalized === 'FIXED' || normalized === 'PER_PERSON') {
      return normalized;
    }

    throw new BadRequestException('depositPolicyType must be NONE, FIXED, or PER_PERSON');
  }

  private parseOptionalWindowStatus(value: unknown): 'OPEN' | 'CLOSED' | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('status must be OPEN or CLOSED');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'OPEN' || normalized === 'CLOSED') {
      return normalized;
    }

    throw new BadRequestException('status must be OPEN or CLOSED');
  }

  private parseOptionalString(value: unknown, fieldName: string, maxLength: number): string | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException(`${fieldName} must be a non-empty string`);
    }

    const normalized = value.trim();
    if (normalized.length > maxLength) {
      throw new BadRequestException(`${fieldName} exceeds max length of ${maxLength}`);
    }

    return normalized;
  }

  private parseOptionalNullableText(value: unknown, maxLength: number): string | null | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (value === null) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('text fields must be strings');
    }

    const normalized = value.trim();
    if (normalized.length === 0) {
      return null;
    }

    if (normalized.length > maxLength) {
      throw new BadRequestException(`text exceeds max length of ${maxLength}`);
    }

    return normalized;
  }

  private parseActorVendorId(value: unknown): string {
    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new ForbiddenException('Vendor scope is missing for authenticated vendor user');
    }

    return value.trim();
  }
}

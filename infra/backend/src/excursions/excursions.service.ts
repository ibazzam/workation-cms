import { BadRequestException, ForbiddenException, Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../prisma.service';

type ExcursionUpsertPayload = {
  vendorId?: unknown;
  islandId?: unknown;
  type?: unknown;
  title?: unknown;
  description?: unknown;
  durationMinutes?: unknown;
  baseCapacity?: unknown;
  equipmentRequired?: unknown;
  equipmentStock?: unknown;
  price?: unknown;
  currency?: unknown;
  active?: unknown;
};

type ExcursionSlotUpsertPayload = {
  startAt?: unknown;
  endAt?: unknown;
  capacityOverride?: unknown;
  equipmentStockOverride?: unknown;
  status?: unknown;
  note?: unknown;
};

type RequestActor = {
  role?: string;
  vendorId?: string | bigint;
};

@Injectable()
export class ExcursionsService {
  constructor(private readonly prisma: PrismaService) {}

  async list(filters: {
    islandId?: number;
    vendorId?: string;
    type?: string;
    q?: string;
    date?: string;
  }) {
    const normalizedType = this.parseOptionalType(filters.type);
    const normalizedQ = typeof filters.q === 'string' && filters.q.trim().length > 0 ? filters.q.trim() : undefined;
    const dayRange = filters.date ? this.parseDateRange(filters.date) : null;

    return this.prisma.excursion.findMany({
      where: {
        active: true,
        islandId: filters.islandId,
        vendorId: filters.vendorId ? filters.vendorId.toString() : undefined,
        type: normalizedType ?? undefined,
        ...(normalizedQ
          ? {
              OR: [
                { title: { contains: normalizedQ, mode: 'insensitive' } },
                { description: { contains: normalizedQ, mode: 'insensitive' } },
              ],
            }
          : {}),
        ...(dayRange
          ? {
              slots: {
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
        slots: {
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
    const excursion = await this.prisma.excursion.findUnique({
      where: { id },
      include: {
        vendor: true,
        island: true,
        slots: {
          orderBy: { startAt: 'asc' },
        },
      },
    });

    if (!excursion) {
      throw new NotFoundException('Excursion not found');
    }

    return excursion;
  }

  async listSlots(id: string, options: { from?: string; to?: string }) {
    await this.assertExcursionExists(id);

    const range = this.parseOptionalSlotRange(options.from, options.to);

    return this.prisma.excursionSlot.findMany({
      where: {
        excursionId: id,
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

  async quote(
    id: string,
    payload: {
      slotId?: string;
      participants?: string;
      equipmentUnitsRequested?: string;
    },
  ) {
    const excursion = await this.prisma.excursion.findUnique({ where: { id } });
    if (!excursion) {
      throw new NotFoundException('Excursion not found');
    }

    if (typeof payload.slotId !== 'string' || payload.slotId.trim().length === 0) {
      throw new BadRequestException('slotId is required');
    }

    const slotId = payload.slotId.trim();
    const participants = payload.participants === undefined ? 1 : this.parsePositiveInt(payload.participants, 'participants');
    const equipmentUnitsRequested = payload.equipmentUnitsRequested === undefined
      ? participants
      : this.parsePositiveInt(payload.equipmentUnitsRequested, 'equipmentUnitsRequested');

    const slot = await this.prisma.excursionSlot.findUnique({ where: { id: slotId } });
    if (!slot || slot.excursionId !== id) {
      throw new NotFoundException('Excursion slot not found');
    }

    const effectiveCapacity = slot.capacityOverride ?? excursion.baseCapacity;
    const effectiveEquipmentStock = slot.equipmentStockOverride ?? excursion.equipmentStock;

    const checks = {
      slotOpen: slot.status === 'OPEN',
      slotInFuture: slot.startAt.getTime() > Date.now(),
      capacityRule: participants <= effectiveCapacity,
      equipmentRule: excursion.equipmentRequired
        ? equipmentUnitsRequested <= effectiveEquipmentStock
        : true,
    };

    const canBook = Object.values(checks).every((value) => value === true);
    const totalPrice = Number(excursion.price.mul(new Prisma.Decimal(participants)).toFixed(2));

    return {
      excursionId: excursion.id,
      slotId: slot.id,
      participants,
      equipmentUnitsRequested: excursion.equipmentRequired ? equipmentUnitsRequested : 0,
      checks,
      canBook,
      pricing: {
        currency: excursion.currency,
        unitPrice: Number(excursion.price.toFixed(2)),
        totalPrice,
      },
      capacity: {
        effectiveCapacity,
        remainingCapacity: Math.max(effectiveCapacity - participants, 0),
      },
      equipment: {
        required: excursion.equipmentRequired,
        effectiveStock: effectiveEquipmentStock,
      },
      slot: {
        startAt: slot.startAt.toISOString(),
        endAt: slot.endAt.toISOString(),
        status: slot.status,
        note: slot.note,
      },
    };
  }

  async create(payload: ExcursionUpsertPayload, actor?: RequestActor) {
    const normalized = this.normalizeExcursionPayload(payload, { partial: false, actor });

    return this.prisma.excursion.create({
      data: normalized as Prisma.ExcursionUncheckedCreateInput,
      include: {
        vendor: true,
        island: true,
      },
    });
  }

  async update(id: string, payload: ExcursionUpsertPayload, actor?: RequestActor) {
    const existing = await this.prisma.excursion.findUnique({ where: { id } });
    if (!existing) {
      throw new NotFoundException('Excursion not found');
    }

    this.assertVendorScopedAccess(existing.vendorId, actor);

    const normalized = this.normalizeExcursionPayload(payload, {
      partial: true,
      actor,
      existingVendorId: existing.vendorId,
    });

    return this.prisma.excursion.update({
      where: { id },
      data: normalized as Prisma.ExcursionUncheckedUpdateInput,
      include: {
        vendor: true,
        island: true,
      },
    });
  }

  async remove(id: string, actor?: RequestActor) {
    const existing = await this.prisma.excursion.findUnique({ where: { id } });
    if (!existing) {
      throw new NotFoundException('Excursion not found');
    }

    this.assertVendorScopedAccess(existing.vendorId, actor);

    await this.prisma.excursion.delete({ where: { id } });
  }

  async createSlot(id: string, payload: ExcursionSlotUpsertPayload, actor?: RequestActor) {
    const excursion = await this.prisma.excursion.findUnique({ where: { id } });
    if (!excursion) {
      throw new NotFoundException('Excursion not found');
    }

    this.assertVendorScopedAccess(excursion.vendorId, actor);

    const normalized = this.normalizeSlotPayload(payload, { partial: false });

    return this.prisma.excursionSlot.create({
      data: {
        excursionId: excursion.id,
        ...normalized,
      } as Prisma.ExcursionSlotUncheckedCreateInput,
    });
  }

  async updateSlot(id: string, slotId: string, payload: ExcursionSlotUpsertPayload, actor?: RequestActor) {
    const excursion = await this.prisma.excursion.findUnique({ where: { id } });
    if (!excursion) {
      throw new NotFoundException('Excursion not found');
    }

    this.assertVendorScopedAccess(excursion.vendorId, actor);

    const slot = await this.prisma.excursionSlot.findUnique({ where: { id: slotId } });
    if (!slot || slot.excursionId !== id) {
      throw new NotFoundException('Excursion slot not found');
    }

    const normalized = this.normalizeSlotPayload(payload, { partial: true });

    return this.prisma.excursionSlot.update({
      where: { id: slotId },
      data: normalized as Prisma.ExcursionSlotUncheckedUpdateInput,
    });
  }

  async removeSlot(id: string, slotId: string, actor?: RequestActor) {
    const excursion = await this.prisma.excursion.findUnique({ where: { id } });
    if (!excursion) {
      throw new NotFoundException('Excursion not found');
    }

    this.assertVendorScopedAccess(excursion.vendorId, actor);

    const slot = await this.prisma.excursionSlot.findUnique({ where: { id: slotId } });
    if (!slot || slot.excursionId !== id) {
      throw new NotFoundException('Excursion slot not found');
    }

    await this.prisma.excursionSlot.delete({ where: { id: slotId } });
  }

  private normalizeExcursionPayload(
    payload: ExcursionUpsertPayload,
    options: { partial: boolean; actor?: RequestActor; existingVendorId?: string },
  ) {
    const vendorId = this.resolveVendorId(payload.vendorId, options.actor, options.existingVendorId, options.partial);
    const islandId = this.parseOptionalInt(payload.islandId, 'islandId');
    const type = this.parseOptionalType(payload.type);
    const title = this.parseOptionalString(payload.title, 'title', 160);
    const description = this.parseOptionalNullableText(payload.description, 5000);
    const durationMinutes = this.parseOptionalInt(payload.durationMinutes, 'durationMinutes');
    const baseCapacity = this.parseOptionalInt(payload.baseCapacity, 'baseCapacity');
    const equipmentRequired = this.parseOptionalBoolean(payload.equipmentRequired, 'equipmentRequired');
    const equipmentStock = this.parseOptionalInt(payload.equipmentStock, 'equipmentStock');
    const price = this.parseOptionalMoney(payload.price, 'price');
    const currency = this.parseOptionalCurrency(payload.currency);
    const active = this.parseOptionalBoolean(payload.active, 'active');

    if (!options.partial) {
      if (!vendorId) throw new BadRequestException('vendorId is required');
      if (!islandId) throw new BadRequestException('islandId is required');
      if (!type) throw new BadRequestException('type is required');
      if (!title) throw new BadRequestException('title is required');
      if (!durationMinutes) throw new BadRequestException('durationMinutes is required');
      if (!baseCapacity) throw new BadRequestException('baseCapacity is required');
      if (!price) throw new BadRequestException('price is required');
    }

    const data: Record<string, unknown> = {};
    if (vendorId !== undefined) data.vendorId = vendorId;
    if (islandId !== undefined) data.islandId = islandId;
    if (type !== undefined) data.type = type;
    if (title !== undefined) data.title = title;
    if (description !== undefined) data.description = description;
    if (durationMinutes !== undefined) data.durationMinutes = durationMinutes;
    if (baseCapacity !== undefined) data.baseCapacity = baseCapacity;
    if (equipmentRequired !== undefined) data.equipmentRequired = equipmentRequired;
    if (equipmentStock !== undefined) data.equipmentStock = equipmentStock;
    if (price !== undefined) data.price = price;
    if (currency !== undefined) data.currency = currency;
    if (active !== undefined) data.active = active;

    return data;
  }

  private normalizeSlotPayload(payload: ExcursionSlotUpsertPayload, options: { partial: boolean }) {
    const startAt = this.parseOptionalDate(payload.startAt, 'startAt');
    const endAt = this.parseOptionalDate(payload.endAt, 'endAt');
    const capacityOverride = this.parseOptionalInt(payload.capacityOverride, 'capacityOverride');
    const equipmentStockOverride = this.parseOptionalInt(payload.equipmentStockOverride, 'equipmentStockOverride');
    const status = this.parseOptionalSlotStatus(payload.status);
    const note = this.parseOptionalNullableText(payload.note, 500);

    if (!options.partial) {
      if (!startAt) throw new BadRequestException('startAt is required');
      if (!endAt) throw new BadRequestException('endAt is required');
    }

    if (startAt && endAt && startAt.getTime() >= endAt.getTime()) {
      throw new BadRequestException('startAt must be before endAt');
    }

    const data: Record<string, unknown> = {};
    if (startAt !== undefined) data.startAt = startAt;
    if (endAt !== undefined) data.endAt = endAt;
    if (capacityOverride !== undefined) data.capacityOverride = capacityOverride;
    if (equipmentStockOverride !== undefined) data.equipmentStockOverride = equipmentStockOverride;
    if (status !== undefined) data.status = status;
    if (note !== undefined) data.note = note;

    return data;
  }

  private async assertExcursionExists(id: string) {
    const row = await this.prisma.excursion.findUnique({ where: { id }, select: { id: true } });
    if (!row) {
      throw new NotFoundException('Excursion not found');
    }
  }

  private assertVendorScopedAccess(resourceVendorId: string, actor?: RequestActor) {
    if (actor?.role !== 'VENDOR') {
      return;
    }
    const scopedVendorId = this.parseActorVendorId(actor.vendorId);
    if (BigInt(scopedVendorId) !== BigInt(resourceVendorId)) {
      throw new ForbiddenException('Vendor users can only manage their own excursions');
    }
  }

  private resolveVendorId(
    payloadVendorId: unknown,
    actor: RequestActor | undefined,
    existingVendorId: string | bigint | undefined,
    partial: boolean,
  ): bigint | undefined {
    if (actor?.role === 'VENDOR') {
      const scopedVendorId = this.parseActorVendorId(actor.vendorId);
      if (payloadVendorId !== undefined && BigInt(String(payloadVendorId)) !== BigInt(scopedVendorId)) {
        throw new ForbiddenException('Vendor users cannot assign other vendor IDs');
      }
      return BigInt(scopedVendorId);
    }

    if (payloadVendorId !== undefined) {
      return BigInt(String(payloadVendorId));
    }

    if (partial) {
      return undefined;
    }

    return existingVendorId !== undefined ? BigInt(existingVendorId) : undefined;
  }

  private parseDateRange(value: string) {
    const day = value.trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(day)) {
      throw new BadRequestException('date must be YYYY-MM-DD');
    }

    const start = new Date(`${day}T00:00:00.000Z`);
    const end = new Date(start.getTime() + (24 * 60 * 60 * 1000));
    return { start, end };
  }

  private parseOptionalSlotRange(from?: string, to?: string) {
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
    if (typeof parsed !== 'number' || !Number.isFinite(parsed) || parsed <= 0) {
      throw new BadRequestException(`${fieldName} must be a positive number`);
    }

    return new Prisma.Decimal(parsed.toFixed(2));
  }

  private parseOptionalType(value: unknown): string | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException('type must be a non-empty string');
    }

    return value.trim().toUpperCase();
  }

  private parseOptionalCurrency(value: unknown): 'USD' | 'MVR' | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('currency must be USD or MVR');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'USD' || normalized === 'MVR') {
      return normalized;
    }

    throw new BadRequestException('currency must be USD or MVR');
  }

  private parseOptionalSlotStatus(value: unknown): 'OPEN' | 'CLOSED' | undefined {
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
    if (typeof value === 'bigint') {
      return value.toString();
    }
    if (typeof value === 'string' && value.trim().length > 0) {
      return value.trim();
    }
    throw new ForbiddenException('Vendor scope is missing for authenticated vendor user');
  }
}

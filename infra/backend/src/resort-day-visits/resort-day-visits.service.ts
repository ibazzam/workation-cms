import { BadRequestException, ForbiddenException, Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../prisma.service';

type ResortDayVisitUpsertPayload = {
  vendorId?: unknown;
  islandId?: unknown;
  title?: unknown;
  description?: unknown;
  quotaPerWindow?: unknown;
  includesTransfer?: unknown;
  transferMode?: unknown;
  transferBundlePrice?: unknown;
  passRestrictionType?: unknown;
  minAllowedAge?: unknown;
  basePrice?: unknown;
  currency?: unknown;
  active?: unknown;
};

type ResortDayVisitWindowUpsertPayload = {
  startAt?: unknown;
  endAt?: unknown;
  quotaOverride?: unknown;
  status?: unknown;
  note?: unknown;
};

type RequestActor = {
  role?: string;
  vendorId?: string;
};

@Injectable()
export class ResortDayVisitsService {
  constructor(private readonly prisma: PrismaService) {}

  async list(filters: {
    islandId?: number;
    vendorId?: string;
    q?: string;
    includesTransfer?: string;
    date?: string;
  }) {
    const q = typeof filters.q === 'string' && filters.q.trim().length > 0 ? filters.q.trim() : undefined;
    const includesTransfer = filters.includesTransfer === undefined
      ? undefined
      : this.parseBoolean(filters.includesTransfer, 'includesTransfer');
    const dayRange = filters.date ? this.parseDayRange(filters.date) : null;

    return this.prisma.resortDayVisit.findMany({
      where: {
        active: true,
        islandId: filters.islandId,
        vendorId: filters.vendorId?.trim() || undefined,
        ...(includesTransfer !== undefined ? { includesTransfer } : {}),
        ...(q
          ? {
              OR: [
                { title: { contains: q, mode: 'insensitive' } },
                { description: { contains: q, mode: 'insensitive' } },
              ],
            }
          : {}),
        ...(dayRange
          ? {
              windows: {
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
        windows: {
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
    const visit = await this.prisma.resortDayVisit.findUnique({
      where: { id },
      include: {
        vendor: true,
        island: true,
        windows: {
          orderBy: { startAt: 'asc' },
        },
      },
    });

    if (!visit) {
      throw new NotFoundException('Resort day visit not found');
    }

    return visit;
  }

  async listWindows(id: string, options: { from?: string; to?: string }) {
    await this.assertResortDayVisitExists(id);
    const range = this.parseOptionalDateRange(options.from, options.to);

    return this.prisma.resortDayVisitWindow.findMany({
      where: {
        resortDayVisitId: id,
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
      windowId?: string;
      passesRequested?: string;
      travelerCategory?: string;
      travelerAge?: string;
      needsTransfer?: string;
    },
  ) {
    const visit = await this.prisma.resortDayVisit.findUnique({ where: { id } });
    if (!visit) {
      throw new NotFoundException('Resort day visit not found');
    }

    if (typeof payload.windowId !== 'string' || payload.windowId.trim().length === 0) {
      throw new BadRequestException('windowId is required');
    }

    const windowId = payload.windowId.trim();
    const passesRequested = payload.passesRequested === undefined
      ? 1
      : this.parsePositiveInt(payload.passesRequested, 'passesRequested');
    const travelerCategory = this.parseOptionalTravelerCategory(payload.travelerCategory) ?? 'TOURIST';
    const travelerAge = payload.travelerAge === undefined
      ? null
      : this.parsePositiveInt(payload.travelerAge, 'travelerAge');
    const needsTransfer = payload.needsTransfer === undefined
      ? false
      : this.parseBoolean(payload.needsTransfer, 'needsTransfer');

    const window = await this.prisma.resortDayVisitWindow.findUnique({ where: { id: windowId } });
    if (!window || window.resortDayVisitId !== id) {
      throw new NotFoundException('Resort day visit window not found');
    }

    const effectiveQuota = window.quotaOverride ?? visit.quotaPerWindow;

    const checks = {
      windowOpen: window.status === 'OPEN',
      windowInFuture: window.startAt.getTime() > Date.now(),
      quotaRule: passesRequested <= effectiveQuota,
      transferRule: !needsTransfer || visit.includesTransfer,
      passRestrictionRule: this.passRestrictionSatisfied(visit.passRestrictionType, travelerCategory, travelerAge, visit.minAllowedAge),
    };

    const canBook = Object.values(checks).every((value) => value === true);
    const baseAmount = Number(visit.basePrice) * passesRequested;
    const transferAmount = needsTransfer ? Number(visit.transferBundlePrice) * passesRequested : 0;

    return {
      resortDayVisitId: visit.id,
      windowId: window.id,
      passesRequested,
      travelerCategory,
      travelerAge,
      needsTransfer,
      checks,
      canBook,
      pricing: {
        currency: visit.currency,
        baseAmount: Number(baseAmount.toFixed(2)),
        transferAmount: Number(transferAmount.toFixed(2)),
        totalAmount: Number((baseAmount + transferAmount).toFixed(2)),
      },
      restrictions: {
        passRestrictionType: visit.passRestrictionType,
        minAllowedAge: visit.minAllowedAge,
      },
      transfer: {
        includesTransfer: visit.includesTransfer,
        transferMode: visit.transferMode,
      },
      window: {
        startAt: window.startAt.toISOString(),
        endAt: window.endAt.toISOString(),
        status: window.status,
        note: window.note,
        effectiveQuota,
      },
    };
  }

  async create(payload: ResortDayVisitUpsertPayload, actor?: RequestActor) {
    const normalized = this.normalizeResortDayVisitPayload(payload, { partial: false, actor });

    return this.prisma.resortDayVisit.create({
      data: normalized as Prisma.ResortDayVisitUncheckedCreateInput,
      include: {
        vendor: true,
        island: true,
      },
    });
  }

  async update(id: string, payload: ResortDayVisitUpsertPayload, actor?: RequestActor) {
    const existing = await this.prisma.resortDayVisit.findUnique({ where: { id } });
    if (!existing) {
      throw new NotFoundException('Resort day visit not found');
    }

    this.assertVendorScopedAccess(existing.vendorId, actor);

    const normalized = this.normalizeResortDayVisitPayload(payload, {
      partial: true,
      actor,
      existingVendorId: existing.vendorId,
    });

    return this.prisma.resortDayVisit.update({
      where: { id },
      data: normalized as Prisma.ResortDayVisitUncheckedUpdateInput,
      include: {
        vendor: true,
        island: true,
      },
    });
  }

  async remove(id: string, actor?: RequestActor) {
    const existing = await this.prisma.resortDayVisit.findUnique({ where: { id } });
    if (!existing) {
      throw new NotFoundException('Resort day visit not found');
    }

    this.assertVendorScopedAccess(existing.vendorId, actor);
    await this.prisma.resortDayVisit.delete({ where: { id } });
  }

  async createWindow(id: string, payload: ResortDayVisitWindowUpsertPayload, actor?: RequestActor) {
    const visit = await this.prisma.resortDayVisit.findUnique({ where: { id } });
    if (!visit) {
      throw new NotFoundException('Resort day visit not found');
    }

    this.assertVendorScopedAccess(visit.vendorId, actor);

    const normalized = this.normalizeWindowPayload(payload, { partial: false });

    return this.prisma.resortDayVisitWindow.create({
      data: {
        resortDayVisitId: visit.id,
        ...normalized,
      } as Prisma.ResortDayVisitWindowUncheckedCreateInput,
    });
  }

  async updateWindow(id: string, windowId: string, payload: ResortDayVisitWindowUpsertPayload, actor?: RequestActor) {
    const visit = await this.prisma.resortDayVisit.findUnique({ where: { id } });
    if (!visit) {
      throw new NotFoundException('Resort day visit not found');
    }

    this.assertVendorScopedAccess(visit.vendorId, actor);

    const window = await this.prisma.resortDayVisitWindow.findUnique({ where: { id: windowId } });
    if (!window || window.resortDayVisitId !== id) {
      throw new NotFoundException('Resort day visit window not found');
    }

    const normalized = this.normalizeWindowPayload(payload, { partial: true });

    return this.prisma.resortDayVisitWindow.update({
      where: { id: windowId },
      data: normalized as Prisma.ResortDayVisitWindowUncheckedUpdateInput,
    });
  }

  async removeWindow(id: string, windowId: string, actor?: RequestActor) {
    const visit = await this.prisma.resortDayVisit.findUnique({ where: { id } });
    if (!visit) {
      throw new NotFoundException('Resort day visit not found');
    }

    this.assertVendorScopedAccess(visit.vendorId, actor);

    const window = await this.prisma.resortDayVisitWindow.findUnique({ where: { id: windowId } });
    if (!window || window.resortDayVisitId !== id) {
      throw new NotFoundException('Resort day visit window not found');
    }

    await this.prisma.resortDayVisitWindow.delete({ where: { id: windowId } });
  }

  private normalizeResortDayVisitPayload(
    payload: ResortDayVisitUpsertPayload,
    options: { partial: boolean; actor?: RequestActor; existingVendorId?: string },
  ) {
    const vendorId = this.resolveVendorId(payload.vendorId, options.actor, options.existingVendorId, options.partial);
    const islandId = this.parseOptionalInt(payload.islandId, 'islandId');
    const title = this.parseOptionalString(payload.title, 'title', 180);
    const description = this.parseOptionalNullableText(payload.description, 5000);
    const quotaPerWindow = this.parseOptionalInt(payload.quotaPerWindow, 'quotaPerWindow');
    const includesTransfer = this.parseOptionalBoolean(payload.includesTransfer, 'includesTransfer');
    const transferMode = this.parseOptionalTransferMode(payload.transferMode);
    const transferBundlePrice = this.parseOptionalMoney(payload.transferBundlePrice, 'transferBundlePrice');
    const passRestrictionType = this.parseOptionalPassRestrictionType(payload.passRestrictionType);
    const minAllowedAge = this.parseOptionalInt(payload.minAllowedAge, 'minAllowedAge');
    const basePrice = this.parseOptionalMoney(payload.basePrice, 'basePrice');
    const currency = this.parseOptionalCurrency(payload.currency);
    const active = this.parseOptionalBoolean(payload.active, 'active');

    if (!options.partial) {
      if (!vendorId) throw new BadRequestException('vendorId is required');
      if (!islandId) throw new BadRequestException('islandId is required');
      if (!title) throw new BadRequestException('title is required');
      if (!quotaPerWindow) throw new BadRequestException('quotaPerWindow is required');
      if (includesTransfer === undefined) throw new BadRequestException('includesTransfer is required');
      if (!passRestrictionType) throw new BadRequestException('passRestrictionType is required');
      if (!basePrice) throw new BadRequestException('basePrice is required');
    }

    const data: Record<string, unknown> = {};
    if (vendorId !== undefined) data.vendorId = vendorId;
    if (islandId !== undefined) data.islandId = islandId;
    if (title !== undefined) data.title = title;
    if (description !== undefined) data.description = description;
    if (quotaPerWindow !== undefined) data.quotaPerWindow = quotaPerWindow;
    if (includesTransfer !== undefined) data.includesTransfer = includesTransfer;
    if (transferMode !== undefined) data.transferMode = transferMode;
    if (transferBundlePrice !== undefined) data.transferBundlePrice = transferBundlePrice;
    if (passRestrictionType !== undefined) data.passRestrictionType = passRestrictionType;
    if (minAllowedAge !== undefined) data.minAllowedAge = minAllowedAge;
    if (basePrice !== undefined) data.basePrice = basePrice;
    if (currency !== undefined) data.currency = currency;
    if (active !== undefined) data.active = active;

    return data;
  }

  private normalizeWindowPayload(payload: ResortDayVisitWindowUpsertPayload, options: { partial: boolean }) {
    const startAt = this.parseOptionalDate(payload.startAt, 'startAt');
    const endAt = this.parseOptionalDate(payload.endAt, 'endAt');
    const quotaOverride = this.parseOptionalInt(payload.quotaOverride, 'quotaOverride');
    const status = this.parseOptionalWindowStatus(payload.status);
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
    if (quotaOverride !== undefined) data.quotaOverride = quotaOverride;
    if (status !== undefined) data.status = status;
    if (note !== undefined) data.note = note;

    return data;
  }

  private passRestrictionSatisfied(
    restrictionType: string,
    travelerCategory: 'RESIDENT' | 'TOURIST',
    travelerAge: number | null,
    minAllowedAge: number,
  ) {
    const normalized = restrictionType.toUpperCase();

    if (normalized === 'NONE') {
      return true;
    }

    if (normalized === 'RESIDENTS_ONLY') {
      return travelerCategory === 'RESIDENT';
    }

    if (normalized === 'TOURISTS_ONLY') {
      return travelerCategory === 'TOURIST';
    }

    if (normalized === 'MIN_AGE') {
      return travelerAge !== null && travelerAge >= minAllowedAge;
    }

    return true;
  }

  private async assertResortDayVisitExists(id: string) {
    const row = await this.prisma.resortDayVisit.findUnique({ where: { id }, select: { id: true } });
    if (!row) {
      throw new NotFoundException('Resort day visit not found');
    }
  }

  private assertVendorScopedAccess(resourceVendorId: string, actor?: RequestActor) {
    if (actor?.role !== 'VENDOR') {
      return;
    }

    const scopedVendorId = this.parseActorVendorId(actor.vendorId);
    if (scopedVendorId !== resourceVendorId) {
      throw new ForbiddenException('Vendor users can only manage their own resort day visits');
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
    if (value === undefined || value === null) {
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
    if (value === undefined || value === null) {
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

  private parseBoolean(value: unknown, fieldName: string): boolean {
    if (typeof value === 'boolean') {
      return value;
    }

    if (typeof value === 'string') {
      const normalized = value.trim().toLowerCase();
      if (normalized === 'true' || normalized === '1' || normalized === 'yes') {
        return true;
      }

      if (normalized === 'false' || normalized === '0' || normalized === 'no') {
        return false;
      }
    }

    throw new BadRequestException(`${fieldName} must be a boolean`);
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

  private parseOptionalTransferMode(value: unknown): 'NONE' | 'SPEEDBOAT' | 'DOMESTIC_FLIGHT' | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('transferMode must be NONE, SPEEDBOAT, or DOMESTIC_FLIGHT');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'NONE' || normalized === 'SPEEDBOAT' || normalized === 'DOMESTIC_FLIGHT') {
      return normalized;
    }

    throw new BadRequestException('transferMode must be NONE, SPEEDBOAT, or DOMESTIC_FLIGHT');
  }

  private parseOptionalPassRestrictionType(value: unknown): 'NONE' | 'RESIDENTS_ONLY' | 'TOURISTS_ONLY' | 'MIN_AGE' | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('passRestrictionType must be NONE, RESIDENTS_ONLY, TOURISTS_ONLY, or MIN_AGE');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'NONE' || normalized === 'RESIDENTS_ONLY' || normalized === 'TOURISTS_ONLY' || normalized === 'MIN_AGE') {
      return normalized;
    }

    throw new BadRequestException('passRestrictionType must be NONE, RESIDENTS_ONLY, TOURISTS_ONLY, or MIN_AGE');
  }

  private parseOptionalTravelerCategory(value: unknown): 'RESIDENT' | 'TOURIST' | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('travelerCategory must be RESIDENT or TOURIST');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'RESIDENT' || normalized === 'TOURIST') {
      return normalized;
    }

    throw new BadRequestException('travelerCategory must be RESIDENT or TOURIST');
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

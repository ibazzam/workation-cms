import { BadRequestException, ForbiddenException, Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../prisma.service';

type VehicleRentalUpsertPayload = {
  vendorId?: unknown;
  pickupIslandId?: unknown;
  dropoffIslandId?: unknown;
  vehicleType?: unknown;
  title?: unknown;
  description?: unknown;
  inventoryTotal?: unknown;
  minDriverAge?: unknown;
  requiresLicense?: unknown;
  acceptedLicenseClasses?: unknown;
  allowsDifferentDropoff?: unknown;
  dailyPrice?: unknown;
  currency?: unknown;
  active?: unknown;
};

type VehicleRentalBlackoutPayload = {
  startDate?: unknown;
  endDate?: unknown;
  unitsBlocked?: unknown;
  reason?: unknown;
};

type RequestActor = {
  role?: string;
  vendorId?: string | bigint;
};

@Injectable()
export class VehicleRentalsService {
  constructor(private readonly prisma: PrismaService) {}

  async list(filters: {
    pickupIslandId?: number;
    dropoffIslandId?: number;
    vehicleType?: string;
    vendorId?: string | bigint;
    q?: string;
  }) {
    const vehicleType = this.parseOptionalVehicleType(filters.vehicleType);
    const q = typeof filters.q === 'string' && filters.q.trim().length > 0 ? filters.q.trim() : undefined;

    return this.prisma.vehicleRental.findMany({
      where: {
        active: true,
        pickupIslandId: filters.pickupIslandId,
        dropoffIslandId: filters.dropoffIslandId,
        vehicleType: vehicleType ?? undefined,
        vendorId: filters.vendorId ? filters.vendorId.toString() : undefined,
        ...(q
          ? {
              OR: [
                { title: { contains: q, mode: 'insensitive' } },
                { description: { contains: q, mode: 'insensitive' } },
              ],
            }
          : {}),
      },
      include: {
        vendor: true,
        pickupIsland: true,
        dropoffIsland: true,
      },
      orderBy: { createdAt: 'desc' },
    });
  }

  async getById(id: string) {
    const rental = await this.prisma.vehicleRental.findUnique({
      where: { id },
      include: {
        vendor: true,
        pickupIsland: true,
        dropoffIsland: true,
        blackouts: {
          orderBy: { startDate: 'asc' },
        },
      },
    });

    if (!rental) {
      throw new NotFoundException('Vehicle rental not found');
    }

    return rental;
  }

  async getAvailability(id: string, payload: { startDate?: string; endDate?: string; unitsRequested?: string }) {
    const rental = await this.prisma.vehicleRental.findUnique({
      where: { id },
      include: {
        blackouts: {
          where: { endDate: { gte: new Date() } },
        },
      },
    });

    if (!rental) {
      throw new NotFoundException('Vehicle rental not found');
    }

    const requestedUnits = payload.unitsRequested === undefined ? 1 : this.parsePositiveInt(payload.unitsRequested, 'unitsRequested');
    const range = this.parseOptionalDateRange(payload.startDate, payload.endDate);
    const blockedUnits = range ? this.computeBlockedUnits(rental.blackouts, range.start, range.end) : 0;
    const availableUnits = Math.max(rental.inventoryTotal - blockedUnits, 0);

    return {
      rentalId: rental.id,
      inventoryTotal: rental.inventoryTotal,
      blockedUnits,
      availableUnits,
      unitsRequested: requestedUnits,
      isAvailable: availableUnits >= requestedUnits,
      window: range
        ? {
            startDate: range.start.toISOString(),
            endDate: range.end.toISOString(),
            days: range.days,
          }
        : null,
    };
  }

  async getQuote(
    id: string,
    payload: {
      startDate?: string;
      endDate?: string;
      unitsRequested?: string;
      driverAge?: string;
      hasLicense?: string;
      licenseClass?: string;
      dropoffIslandId?: number;
    },
  ) {
    const rental = await this.prisma.vehicleRental.findUnique({
      where: { id },
      include: {
        blackouts: true,
      },
    });

    if (!rental) {
      throw new NotFoundException('Vehicle rental not found');
    }

    const unitsRequested = payload.unitsRequested === undefined ? 1 : this.parsePositiveInt(payload.unitsRequested, 'unitsRequested');
    const range = this.parseRequiredDateRange(payload.startDate, payload.endDate);
    const blockedUnits = this.computeBlockedUnits(rental.blackouts, range.start, range.end);
    const availableUnits = Math.max(rental.inventoryTotal - blockedUnits, 0);

    const driverAge = payload.driverAge === undefined ? null : this.parsePositiveInt(payload.driverAge, 'driverAge');
    const hasLicense = payload.hasLicense === undefined ? null : this.parseBoolean(payload.hasLicense, 'hasLicense');
    const licenseClass = typeof payload.licenseClass === 'string' && payload.licenseClass.trim().length > 0
      ? payload.licenseClass.trim().toUpperCase()
      : null;

    const requestedDropoffIslandId = payload.dropoffIslandId ?? rental.dropoffIslandId ?? rental.pickupIslandId;
    const differentDropoff = requestedDropoffIslandId !== rental.pickupIslandId;

    const eligibilityChecks = {
      pickupDropoffRule: !(differentDropoff && !rental.allowsDifferentDropoff),
      ageRule: driverAge === null ? true : driverAge >= rental.minDriverAge,
      licenseRule: rental.requiresLicense ? hasLicense === true : true,
      licenseClassRule: this.matchesLicenseClass(rental.acceptedLicenseClasses, licenseClass),
      inventoryRule: availableUnits >= unitsRequested,
    };

    const canBook = Object.values(eligibilityChecks).every((value) => value === true);
    const unitPrice = Number(rental.dailyPrice);
    const subtotal = unitPrice * range.days * unitsRequested;

    return {
      rentalId: rental.id,
      vehicleType: rental.vehicleType,
      unitsRequested,
      pricing: {
        currency: rental.currency,
        unitDailyPrice: Number(unitPrice.toFixed(2)),
        days: range.days,
        subtotal: Number(subtotal.toFixed(2)),
      },
      availability: {
        inventoryTotal: rental.inventoryTotal,
        blockedUnits,
        availableUnits,
      },
      rules: {
        minDriverAge: rental.minDriverAge,
        requiresLicense: rental.requiresLicense,
        acceptedLicenseClasses: this.parseAcceptedLicenseClasses(rental.acceptedLicenseClasses),
        allowsDifferentDropoff: rental.allowsDifferentDropoff,
      },
      requestedRoute: {
        pickupIslandId: rental.pickupIslandId,
        dropoffIslandId: requestedDropoffIslandId,
      },
      eligibilityChecks,
      canBook,
      window: {
        startDate: range.start.toISOString(),
        endDate: range.end.toISOString(),
        days: range.days,
      },
    };
  }

  async create(payload: VehicleRentalUpsertPayload, actor?: RequestActor) {
    const normalized = this.normalizeUpsertPayload(payload, { partial: false, actor });
    if (normalized.vendorId && typeof normalized.vendorId !== 'string') {
      normalized.vendorId = normalized.vendorId.toString();
    }
    return this.prisma.vehicleRental.create({
      data: normalized as Prisma.VehicleRentalUncheckedCreateInput,
      include: {
        vendor: true,
        pickupIsland: true,
        dropoffIsland: true,
      },
    });
  }

  async update(id: string, payload: VehicleRentalUpsertPayload, actor?: RequestActor) {
    const existing = await this.prisma.vehicleRental.findUnique({ where: { id } });
    if (!existing) {
      throw new NotFoundException('Vehicle rental not found');
    }
    this.assertVendorScopedAccess(BigInt(existing.vendorId), actor);
    const normalized = this.normalizeUpsertPayload(payload, { partial: true, actor, existing });
    if (normalized.vendorId && typeof normalized.vendorId !== 'string') {
      normalized.vendorId = normalized.vendorId.toString();
    }
    return this.prisma.vehicleRental.update({
      where: { id },
      data: normalized as Prisma.VehicleRentalUncheckedUpdateInput,
      include: {
        vendor: true,
        pickupIsland: true,
        dropoffIsland: true,
      },
    });
  }

  async remove(id: string, actor?: RequestActor) {
    const existing = await this.prisma.vehicleRental.findUnique({ where: { id } });
    if (!existing) {
      throw new NotFoundException('Vehicle rental not found');
    }

    this.assertVendorScopedAccess(BigInt(existing.vendorId), actor);

    await this.prisma.vehicleRental.delete({ where: { id } });
  }

  async createBlackout(id: string, payload: VehicleRentalBlackoutPayload, actor?: RequestActor) {
    const rental = await this.prisma.vehicleRental.findUnique({ where: { id } });
    if (!rental) {
      throw new NotFoundException('Vehicle rental not found');
    }

    this.assertVendorScopedAccess(BigInt(rental.vendorId), actor);

    const startDate = this.parseDate(payload.startDate, 'startDate');
    const endDate = this.parseDate(payload.endDate, 'endDate');
    if (startDate.getTime() > endDate.getTime()) {
      throw new BadRequestException('startDate must be before or equal to endDate');
    }

    const unitsBlocked = payload.unitsBlocked === undefined
      ? rental.inventoryTotal
      : this.parsePositiveInt(payload.unitsBlocked, 'unitsBlocked');

    if (unitsBlocked > rental.inventoryTotal) {
      throw new BadRequestException('unitsBlocked cannot exceed inventoryTotal');
    }

    const reason = this.parseOptionalText(payload.reason, 500);

    return this.prisma.vehicleRentalBlackout.create({
      data: {
        vehicleRentalId: rental.id,
        startDate,
        endDate,
        unitsBlocked,
        reason,
      },
    });
  }

  async removeBlackout(id: string, blackoutId: string, actor?: RequestActor) {
    const rental = await this.prisma.vehicleRental.findUnique({ where: { id } });
    if (!rental) {
      throw new NotFoundException('Vehicle rental not found');
    }

    this.assertVendorScopedAccess(BigInt(rental.vendorId), actor);

    const blackout = await this.prisma.vehicleRentalBlackout.findUnique({ where: { id: blackoutId } });
    if (!blackout || blackout.vehicleRentalId !== id) {
      throw new NotFoundException('Vehicle rental blackout not found');
    }

    await this.prisma.vehicleRentalBlackout.delete({ where: { id: blackoutId } });
  }

  private normalizeUpsertPayload(
    payload: VehicleRentalUpsertPayload,
    options: { partial: boolean; actor?: RequestActor; existing?: { vendorId: string | bigint; pickupIslandId: number } },
  ) {
    const vendorId = this.resolveVendorId(payload.vendorId, options.actor, options.existing?.vendorId, options.partial);
    const pickupIslandId = this.parseOptionalInt(payload.pickupIslandId, 'pickupIslandId');
    const dropoffIslandId = payload.dropoffIslandId === null
      ? null
      : this.parseOptionalInt(payload.dropoffIslandId, 'dropoffIslandId');
    const vehicleType = this.parseOptionalVehicleType(payload.vehicleType);
    const title = this.parseOptionalString(payload.title, 'title', 160);
    const description = this.parseOptionalText(payload.description, 5000);
    const inventoryTotal = this.parseOptionalInt(payload.inventoryTotal, 'inventoryTotal');
    const minDriverAge = this.parseOptionalInt(payload.minDriverAge, 'minDriverAge');
    const requiresLicense = this.parseOptionalBoolean(payload.requiresLicense, 'requiresLicense');
    const acceptedLicenseClasses = this.parseOptionalLicenseClasses(payload.acceptedLicenseClasses);
    const allowsDifferentDropoff = this.parseOptionalBoolean(payload.allowsDifferentDropoff, 'allowsDifferentDropoff');
    const dailyPrice = this.parseOptionalMoney(payload.dailyPrice, 'dailyPrice');
    const currency = this.parseOptionalCurrency(payload.currency);
    const active = this.parseOptionalBoolean(payload.active, 'active');

    if (!options.partial) {
      if (!vendorId) throw new BadRequestException('vendorId is required');
      if (!pickupIslandId) throw new BadRequestException('pickupIslandId is required');
      if (!vehicleType) throw new BadRequestException('vehicleType is required (CAR or BIKE)');
      if (!title) throw new BadRequestException('title is required');
      if (!inventoryTotal) throw new BadRequestException('inventoryTotal is required');
      if (!dailyPrice) throw new BadRequestException('dailyPrice is required');
    }

    const data: Record<string, unknown> = {};
    if (vendorId !== undefined) data.vendorId = vendorId;
    if (pickupIslandId !== undefined) data.pickupIslandId = pickupIslandId;
    if (dropoffIslandId !== undefined) data.dropoffIslandId = dropoffIslandId;
    if (vehicleType !== undefined) data.vehicleType = vehicleType;
    if (title !== undefined) data.title = title;
    if (description !== undefined) data.description = description;
    if (inventoryTotal !== undefined) data.inventoryTotal = inventoryTotal;
    if (minDriverAge !== undefined) data.minDriverAge = minDriverAge;
    if (requiresLicense !== undefined) data.requiresLicense = requiresLicense;
    if (acceptedLicenseClasses !== undefined) data.acceptedLicenseClasses = acceptedLicenseClasses;
    if (allowsDifferentDropoff !== undefined) data.allowsDifferentDropoff = allowsDifferentDropoff;
    if (dailyPrice !== undefined) data.dailyPrice = dailyPrice;
    if (currency !== undefined) data.currency = currency;
    if (active !== undefined) data.active = active;

    const effectivePickupIslandId = (data.pickupIslandId as number | undefined) ?? options.existing?.pickupIslandId;
    const effectiveDropoffIslandId = (data.dropoffIslandId as number | null | undefined);
    const effectiveAllowsDifferentDropoff = (data.allowsDifferentDropoff as boolean | undefined) ?? false;

    if (effectivePickupIslandId !== undefined && effectiveDropoffIslandId !== undefined && effectiveDropoffIslandId !== null) {
      const differentDropoff = effectivePickupIslandId !== effectiveDropoffIslandId;
      if (differentDropoff && !effectiveAllowsDifferentDropoff) {
        throw new BadRequestException('dropoffIslandId must equal pickupIslandId when allowsDifferentDropoff is false');
      }
    }

    return data;
  }

  private resolveVendorId(
    payloadVendorId: unknown,
    actor: RequestActor | undefined,
    existingVendorId: string | bigint | undefined,
    partial: boolean,
  ): bigint | undefined {
    if (actor?.role === 'VENDOR') {
      const scopedVendorId = this.parseActorVendorId(actor.vendorId);
      if (payloadVendorId !== undefined && BigInt(String(payloadVendorId)) !== scopedVendorId) {
        throw new ForbiddenException('Vendor users cannot assign other vendor IDs');
      }
      return scopedVendorId;
    }
    if (payloadVendorId !== undefined && payloadVendorId !== null) {
      try {
        return BigInt(String(payloadVendorId));
      } catch {
        throw new BadRequestException('vendorId must be a valid BigInt');
      }
    }
    if (partial) {
      return undefined;
    }
    return typeof existingVendorId === 'string' ? BigInt(existingVendorId) : existingVendorId;
  }

  private assertVendorScopedAccess(resourceVendorId: bigint, actor?: RequestActor) {
    if (actor?.role !== 'VENDOR') {
      return;
    }
    const scopedVendorId = this.parseActorVendorId(actor.vendorId);
    if (scopedVendorId !== resourceVendorId) {
      throw new ForbiddenException('Vendor users can only manage their own vehicle rentals');
    }
  }

  private computeBlockedUnits(
    blackouts: Array<{ startDate: Date; endDate: Date; unitsBlocked: number }>,
    start: Date,
    end: Date,
  ) {
    return blackouts
      .filter((item) => item.startDate.getTime() <= end.getTime() && item.endDate.getTime() >= start.getTime())
      .reduce((sum, item) => sum + item.unitsBlocked, 0);
  }

  private parseRequiredDateRange(startDate?: string, endDate?: string) {
    if (!startDate || !endDate) {
      throw new BadRequestException('startDate and endDate are required (YYYY-MM-DD)');
    }

    return this.parseDateRange(startDate, endDate);
  }

  private parseOptionalDateRange(startDate?: string, endDate?: string) {
    if (!startDate && !endDate) {
      return null;
    }

    if (!startDate || !endDate) {
      throw new BadRequestException('startDate and endDate must both be provided');
    }

    return this.parseDateRange(startDate, endDate);
  }

  private parseDateRange(startDateValue: string, endDateValue: string) {
    const start = this.parseDate(startDateValue, 'startDate');
    const end = this.parseDate(endDateValue, 'endDate');

    if (start.getTime() > end.getTime()) {
      throw new BadRequestException('startDate must be before or equal to endDate');
    }

    const msPerDay = 24 * 60 * 60 * 1000;
    const days = Math.floor((end.getTime() - start.getTime()) / msPerDay) + 1;
    if (days < 1 || days > 30) {
      throw new BadRequestException('rental duration must be between 1 and 30 days');
    }

    return { start, end, days };
  }

  private parseDate(value: unknown, fieldName: string): Date {
    if (typeof value !== 'string') {
      throw new BadRequestException(`${fieldName} must be a valid ISO date`);
    }

    const parsed = new Date(`${value}T00:00:00.000Z`);
    if (Number.isNaN(parsed.getTime())) {
      throw new BadRequestException(`${fieldName} must be a valid ISO date`);
    }

    return parsed;
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

  private parseOptionalVehicleType(value: unknown): 'CAR' | 'BIKE' | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('vehicleType must be CAR or BIKE');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'CAR' || normalized === 'BIKE') {
      return normalized;
    }

    throw new BadRequestException('vehicleType must be CAR or BIKE');
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

  private parseOptionalText(value: unknown, maxLength: number): string | null {
    if (value === undefined) {
      return null;
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

  private parseOptionalLicenseClasses(value: unknown): string | null | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (value === null) {
      return null;
    }

    if (!Array.isArray(value) || value.length === 0) {
      throw new BadRequestException('acceptedLicenseClasses must be a non-empty string array when provided');
    }

    const normalized = value
      .map((item) => (typeof item === 'string' ? item.trim().toUpperCase() : ''))
      .filter((item) => item.length > 0);

    if (normalized.length === 0) {
      throw new BadRequestException('acceptedLicenseClasses must contain valid non-empty strings');
    }

    return Array.from(new Set(normalized)).join(',');
  }

  private parseAcceptedLicenseClasses(value: string | null): string[] {
    if (!value) {
      return [];
    }

    return value.split(',').map((item) => item.trim().toUpperCase()).filter((item) => item.length > 0);
  }

  private matchesLicenseClass(acceptedClassesRaw: string | null, requestedClass: string | null): boolean {
    const acceptedClasses = this.parseAcceptedLicenseClasses(acceptedClassesRaw);
    if (acceptedClasses.length === 0) {
      return true;
    }

    if (!requestedClass) {
      return false;
    }

    return acceptedClasses.includes(requestedClass.toUpperCase());
  }

  private parseActorVendorId(value: unknown): bigint {
    if (typeof value === 'bigint') {
      return value;
    }
    if (typeof value === 'string' && value.trim().length > 0) {
      try {
        return BigInt(value.trim());
      } catch {
        throw new ForbiddenException('Vendor scope is not a valid BigInt');
      }
    }
    throw new ForbiddenException('Vendor scope is missing for authenticated vendor user');
  }
}

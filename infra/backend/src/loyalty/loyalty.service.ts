import { BadRequestException, ForbiddenException, Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../prisma.service';

type RedeemPayload = {
  bookingId?: unknown;
  points?: unknown;
};

type VendorOfferPayload = {
  vendorId?: unknown;
  title?: unknown;
  description?: unknown;
  pointsMultiplier?: unknown;
  discountPercent?: unknown;
  active?: unknown;
  startsAt?: unknown;
  endsAt?: unknown;
};

type RequestActor = {
  id?: string;
  role?: string;
  vendorId?: string | bigint;
};

type LoyaltySettings = {
  enabled: boolean;
  pointsPerUnitSpend: number;
  unitCurrency: 'USD' | 'MVR';
  redemptionValuePerPoint: number;
  minimumPointsToRedeem: number;
};

const COMMERCIAL_SETTINGS_KEY = 'COMMERCIAL_SETTINGS';

@Injectable()
export class LoyaltyService {
  constructor(private readonly prisma: PrismaService) {}

  async getWallet(userId: string) {
    const [settings, account] = await Promise.all([
      this.getLoyaltySettings(),
      this.ensureAccount(userId),
    ]);

    const nextTier = this.computeNextTier(account.lifetimePoints);
    const activeOfferCount = await this.prisma.vendorLoyaltyOffer.count({ where: { active: true } });

    return {
      settings,
      account,
      tierProgress: nextTier,
      interactive: {
        activeVendorOfferCount: activeOfferCount,
        suggestion: settings.enabled
          ? 'Use points redemption on confirmed bookings and prioritize vendors with active multipliers.'
          : 'Loyalty program is currently disabled by admin settings.',
      },
    };
  }

  async getTransactions(userId: string, payload: { limit?: unknown }) {
    const limit = this.parseOptionalPositiveInt(payload.limit) ?? 25;
    const account = await this.ensureAccount(userId);

    const items = await this.prisma.loyaltyTransaction.findMany({
      where: { userId },
      orderBy: { createdAt: 'desc' },
      take: Math.min(limit, 100),
    });

    return {
      account,
      items,
    };
  }

  async redeemForBooking(userId: string, payload: RedeemPayload) {
    const settings = await this.getLoyaltySettings();
    if (!settings.enabled) {
      throw new BadRequestException('Loyalty program is disabled');
    }

    const bookingId = this.parseRequiredString(payload.bookingId, 'bookingId');
    const points = this.parseRequiredPositiveInt(payload.points, 'points');

    if (points < settings.minimumPointsToRedeem) {
      throw new BadRequestException(`Minimum redeem points is ${settings.minimumPointsToRedeem}`);
    }

    const booking = await this.prisma.booking.findUnique({
      where: { id: bookingId },
      select: { id: true, userId: true, totalPrice: true, status: true },
    });

    if (!booking) {
      throw new NotFoundException('Booking not found');
    }

    if (booking.userId !== userId) {
      throw new ForbiddenException('Booking does not belong to authenticated user');
    }

    if (booking.status !== 'HOLD' && booking.status !== 'CONFIRMED') {
      throw new BadRequestException('Only HOLD/CONFIRMED bookings can redeem loyalty points');
    }

    const existingRedemption = await this.prisma.loyaltyTransaction.findFirst({
      where: {
        bookingId,
        userId,
        type: 'REDEEM',
      },
      select: { id: true },
    });

    if (existingRedemption) {
      throw new BadRequestException('Loyalty points already redeemed for this booking');
    }

    const account = await this.ensureAccount(userId);
    if (account.pointsBalance < points) {
      throw new BadRequestException('Insufficient loyalty points');
    }

    const redemptionAmount = new Prisma.Decimal(points).mul(settings.redemptionValuePerPoint);
    const currentTotal = new Prisma.Decimal(booking.totalPrice as any);
    const nextTotal = Prisma.Decimal.max(new Prisma.Decimal(0), currentTotal.minus(redemptionAmount));

    const updated = await this.prisma.$transaction(async (tx) => {
      await tx.loyaltyAccount.update({
        where: { userId },
        data: {
          pointsBalance: { decrement: points },
        },
      });

      const txRow = await tx.loyaltyTransaction.create({
        data: {
          userId,
          bookingId,
          type: 'REDEEM',
          points: -points,
          amount: redemptionAmount,
          currency: settings.unitCurrency,
          description: 'Loyalty redemption applied to booking',
        },
      });

      const updatedBooking = await tx.booking.update({
        where: { id: bookingId },
        data: { totalPrice: nextTotal },
      });

      return {
        transaction: txRow,
        booking: updatedBooking,
      };
    });

    const freshAccount = await this.ensureAccount(userId);
    return {
      redeemedPoints: points,
      account: freshAccount,
      booking: updated.booking,
      transaction: updated.transaction,
    };
  }

  async listVendorOffers(vendorId: string) {
    await this.ensureVendorExists(vendorId);
    return this.prisma.vendorLoyaltyOffer.findMany({
      where: {
        vendorId: BigInt(vendorId),
        active: true,
      },
      orderBy: [{ pointsMultiplier: 'desc' }, { createdAt: 'desc' }],
    });
  }

  async createVendorOffer(payload: VendorOfferPayload, actor?: RequestActor) {
    const normalized = await this.normalizeVendorOfferPayload(payload, { partial: false });
    this.assertVendorOfferScope(normalized.vendorId ? normalized.vendorId.toString() : '', actor);

    return this.prisma.vendorLoyaltyOffer.create({
      data: {
        ...normalized,
        vendorId: normalized.vendorId ? BigInt(normalized.vendorId) : undefined,
      } as Prisma.VendorLoyaltyOfferUncheckedCreateInput,
    });
  }

  async updateVendorOffer(id: string, payload: VendorOfferPayload, actor?: RequestActor) {
    const existing = await this.prisma.vendorLoyaltyOffer.findUnique({ where: { id } });
    if (!existing) {
      throw new NotFoundException('Vendor loyalty offer not found');
    }

    this.assertVendorOfferScope(existing.vendorId ? existing.vendorId.toString() : '', actor);

    const normalized = await this.normalizeVendorOfferPayload(payload, { partial: true });
    if (normalized.vendorId) {
      this.assertVendorOfferScope(normalized.vendorId.toString(), actor);
    }

    return this.prisma.vendorLoyaltyOffer.update({
      where: { id },
      data: {
        ...normalized,
        vendorId: normalized.vendorId ? BigInt(normalized.vendorId) : undefined,
      } as Prisma.VendorLoyaltyOfferUncheckedUpdateInput,
    });
  }

  async removeVendorOffer(id: string, actor?: RequestActor) {
    const existing = await this.prisma.vendorLoyaltyOffer.findUnique({ where: { id } });
    if (!existing) {
      throw new NotFoundException('Vendor loyalty offer not found');
    }

    this.assertVendorOfferScope(existing.vendorId, actor);
    await this.prisma.vendorLoyaltyOffer.delete({ where: { id } });
  }

  async awardPointsForConfirmedBooking(bookingId: string, source: 'BOOKING_TRANSITION' | 'PAYMENT_RECONCILE' | 'PAYMENT_WEBHOOK') {
    const settings = await this.getLoyaltySettings();
    if (!settings.enabled) {
      return null;
    }

    const booking = await this.prisma.booking.findUnique({
      where: { id: bookingId },
      include: {
        accommodation: { select: { vendorId: true } },
        transport: { select: { vendorId: true } },
      },
    });

    if (!booking || booking.status !== 'CONFIRMED') {
      return null;
    }

    const existing = await this.prisma.loyaltyTransaction.findFirst({
      where: {
        bookingId,
        type: 'EARN',
      },
      select: { id: true },
    });

    if (existing) {
      return null;
    }

    const vendorId = booking.accommodation?.vendorId ?? booking.transport?.vendorId ?? null;
    let multiplier = new Prisma.Decimal(1);
    if (vendorId) {
      const now = new Date();
      const activeOffer = await this.prisma.vendorLoyaltyOffer.findFirst({
        where: {
          vendorId,
          active: true,
          OR: [
            { startsAt: null, endsAt: null },
            { startsAt: { lte: now }, endsAt: null },
            { startsAt: null, endsAt: { gte: now } },
            { startsAt: { lte: now }, endsAt: { gte: now } },
          ],
        },
        orderBy: [{ pointsMultiplier: 'desc' }, { createdAt: 'desc' }],
      });

      if (activeOffer) {
        multiplier = new Prisma.Decimal(activeOffer.pointsMultiplier as any);
      }
    }

    const spend = new Prisma.Decimal(booking.totalPrice as any);
    const basePointsDecimal = spend.mul(settings.pointsPerUnitSpend);
    const boostedPointsDecimal = basePointsDecimal.mul(multiplier);
    const points = Math.max(0, Math.floor(boostedPointsDecimal.toNumber()));
    if (points <= 0) {
      return null;
    }

    const result = await this.prisma.$transaction(async (tx) => {
      const account = await tx.loyaltyAccount.upsert({
        where: { userId: booking.userId },
        create: {
          userId: booking.userId,
          pointsBalance: 0,
          lifetimePoints: 0,
          tier: 'BRONZE',
        },
        update: {},
      });

      const nextLifetimePoints = account.lifetimePoints + points;
      const nextTier = this.determineTier(nextLifetimePoints);

      const txRow = await tx.loyaltyTransaction.create({
        data: {
          userId: booking.userId,
          bookingId: booking.id,
          type: 'EARN',
          points,
          amount: spend,
          currency: settings.unitCurrency,
          description: `Booking confirmed (${source})`,
        },
      });

      const updatedAccount = await tx.loyaltyAccount.update({
        where: { userId: booking.userId },
        data: {
          pointsBalance: { increment: points },
          lifetimePoints: { increment: points },
          tier: nextTier,
        },
      });

      return {
        transaction: txRow,
        account: updatedAccount,
      };
    });

    return result;
  }

  private async getLoyaltySettings(): Promise<LoyaltySettings> {
    const record = await this.prisma.appConfig.findUnique({ where: { key: COMMERCIAL_SETTINGS_KEY } });
    const value = record?.value;
    const loyalty = value && typeof value === 'object' && !Array.isArray(value)
      ? (value as any).loyalty
      : undefined;

    if (!loyalty || typeof loyalty !== 'object') {
      return {
        enabled: false,
        pointsPerUnitSpend: 1,
        unitCurrency: 'USD',
        redemptionValuePerPoint: 0.01,
        minimumPointsToRedeem: 100,
      };
    }

    const pointsPerUnitSpend = Number((loyalty as any).pointsPerUnitSpend ?? 1);
    const redemptionValuePerPoint = Number((loyalty as any).redemptionValuePerPoint ?? 0.01);
    const minimumPointsToRedeem = Number((loyalty as any).minimumPointsToRedeem ?? 100);
    const unitCurrencyRaw = typeof (loyalty as any).unitCurrency === 'string' ? (loyalty as any).unitCurrency.toUpperCase() : 'USD';

    return {
      enabled: Boolean((loyalty as any).enabled),
      pointsPerUnitSpend: Number.isFinite(pointsPerUnitSpend) && pointsPerUnitSpend > 0 ? pointsPerUnitSpend : 1,
      unitCurrency: unitCurrencyRaw === 'MVR' ? 'MVR' : 'USD',
      redemptionValuePerPoint: Number.isFinite(redemptionValuePerPoint) && redemptionValuePerPoint > 0 ? redemptionValuePerPoint : 0.01,
      minimumPointsToRedeem: Number.isInteger(minimumPointsToRedeem) && minimumPointsToRedeem > 0 ? minimumPointsToRedeem : 100,
    };
  }

  private async ensureAccount(userId: string) {
    const user = await this.prisma.user.findUnique({ where: { id: userId }, select: { id: true } });
    if (!user) {
      throw new NotFoundException('User not found');
    }

    return this.prisma.loyaltyAccount.upsert({
      where: { userId },
      create: {
        userId,
        pointsBalance: 0,
        lifetimePoints: 0,
        tier: 'BRONZE',
      },
      update: {},
    });
  }

  private computeNextTier(lifetimePoints: number) {
    const currentTier = this.determineTier(lifetimePoints);
    const tiers = [
      { tier: 'BRONZE', threshold: 0 },
      { tier: 'SILVER', threshold: 1000 },
      { tier: 'GOLD', threshold: 5000 },
      { tier: 'PLATINUM', threshold: 15000 },
    ];

    const currentIndex = tiers.findIndex((entry) => entry.tier === currentTier);
    const next = currentIndex >= 0 && currentIndex < tiers.length - 1 ? tiers[currentIndex + 1] : null;
    if (!next) {
      return {
        currentTier,
        nextTier: null,
        pointsToNextTier: 0,
      };
    }

    return {
      currentTier,
      nextTier: next.tier,
      pointsToNextTier: Math.max(next.threshold - lifetimePoints, 0),
    };
  }

  private determineTier(lifetimePoints: number) {
    if (lifetimePoints >= 15000) return 'PLATINUM';
    if (lifetimePoints >= 5000) return 'GOLD';
    if (lifetimePoints >= 1000) return 'SILVER';
    return 'BRONZE';
  }

  private async normalizeVendorOfferPayload(payload: VendorOfferPayload, options: { partial: boolean }) {
    const vendorId = this.parseOptionalString(payload.vendorId);
    const title = this.parseOptionalString(payload.title);
    const description = this.parseOptionalNullableString(payload.description);
    const pointsMultiplier = this.parseOptionalPositiveNumber(payload.pointsMultiplier);
    const discountPercent = this.parseOptionalIntegerRange(payload.discountPercent, 0, 100);
    const active = this.parseOptionalBoolean(payload.active);
    const startsAt = this.parseOptionalNullableDate(payload.startsAt);
    const endsAt = this.parseOptionalNullableDate(payload.endsAt);

    if (!options.partial && (!vendorId || !title)) {
      throw new BadRequestException('vendorId and title are required');
    }

    if (startsAt && endsAt && endsAt.getTime() < startsAt.getTime()) {
      throw new BadRequestException('endsAt must be after startsAt');
    }

    if (vendorId) {
      await this.ensureVendorExists(vendorId);
    }

    const data: Record<string, unknown> = {};
    if (vendorId !== undefined) data.vendorId = vendorId;
    if (title !== undefined) data.title = title;
    if (description !== undefined) data.description = description;
    if (pointsMultiplier !== undefined) data.pointsMultiplier = new Prisma.Decimal(pointsMultiplier);
    if (discountPercent !== undefined) data.discountPercent = discountPercent;
    if (active !== undefined) data.active = active;
    if (startsAt !== undefined) data.startsAt = startsAt;
    if (endsAt !== undefined) data.endsAt = endsAt;

    if (options.partial && Object.keys(data).length === 0) {
      throw new BadRequestException('No updatable fields provided');
    }

    return data;
  }

  private assertVendorOfferScope(vendorId: string, actor?: RequestActor) {
    if (actor?.role !== 'VENDOR') {
      return;
    }

    if (!actor.vendorId || actor.vendorId.trim().length === 0) {
      throw new ForbiddenException('Vendor scope is missing for authenticated vendor user');
    }

    if (actor.vendorId.trim() !== vendorId) {
      throw new ForbiddenException('Vendor users can only manage loyalty offers for their own vendor');
    }
  }

  private parseRequiredString(value: unknown, field: string): string {
    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException(`${field} must be a non-empty string`);
    }

    return value.trim();
  }

  private parseRequiredPositiveInt(value: unknown, field: string): number {
    const parsed = typeof value === 'number' ? value : Number(value);
    if (!Number.isInteger(parsed) || parsed <= 0) {
      throw new BadRequestException(`${field} must be a positive integer`);
    }

    return parsed;
  }

  private parseOptionalPositiveInt(value: unknown): number | undefined {
    if (value === undefined) return undefined;
    return this.parseRequiredPositiveInt(value, 'limit');
  }

  private parseOptionalString(value: unknown): string | undefined {
    if (value === undefined) return undefined;
    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException('Expected non-empty string value');
    }

    return value.trim();
  }

  private parseOptionalNullableString(value: unknown): string | null | undefined {
    if (value === undefined) return undefined;
    if (value === null) return null;
    if (typeof value !== 'string') {
      throw new BadRequestException('Expected string value');
    }

    return value.trim();
  }

  private parseOptionalPositiveNumber(value: unknown): number | undefined {
    if (value === undefined) return undefined;
    const parsed = typeof value === 'number' ? value : Number(value);
    if (!Number.isFinite(parsed) || parsed <= 0) {
      throw new BadRequestException('Expected positive number value');
    }

    return parsed;
  }

  private parseOptionalIntegerRange(value: unknown, min: number, max: number): number | undefined {
    if (value === undefined) return undefined;
    const parsed = typeof value === 'number' ? value : Number(value);
    if (!Number.isInteger(parsed) || parsed < min || parsed > max) {
      throw new BadRequestException(`Expected integer between ${min} and ${max}`);
    }

    return parsed;
  }

  private parseOptionalBoolean(value: unknown): boolean | undefined {
    if (value === undefined) return undefined;
    if (typeof value !== 'boolean') {
      throw new BadRequestException('Expected boolean value');
    }

    return value;
  }

  private parseOptionalNullableDate(value: unknown): Date | null | undefined {
    if (value === undefined) return undefined;
    if (value === null) return null;
    if (typeof value !== 'string') {
      throw new BadRequestException('Expected ISO date string value');
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
      throw new BadRequestException('Invalid date value');
    }

    return parsed;
  }

  private async ensureVendorExists(vendorId: string) {
    const row = await this.prisma.vendor.findUnique({ where: { id: vendorId }, select: { id: true } });
    if (!row) {
      throw new NotFoundException('Vendor not found');
    }
  }
}

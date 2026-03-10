import { BadRequestException, ForbiddenException, Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../prisma.service';

type EmbedPolicy = 'PLATFORM_EMBED' | 'LINK_ONLY' | 'NO_EMBED';
type UgcSafetyStatus = 'SAFE' | 'REVIEW' | 'BLOCKED';
type TrustSafetyStatus = 'CLEAR' | 'UNDER_REVIEW' | 'ESCALATED' | 'ACTIONED';
type ContentQualityStatus = 'GOOD' | 'REVIEW' | 'LOW';

type SocialLinkPayload = {
  targetType?: unknown;
  targetId?: unknown;
  platform?: unknown;
  url?: unknown;
  embedPolicy?: unknown;
  handle?: unknown;
  verified?: unknown;
  displayOrder?: unknown;
  active?: unknown;
};

type RequestActor = {
  id?: string;
  role?: string;
  vendorId?: string;
};

type SocialLinkModerationSnapshot = {
  embedPolicy?: string | null;
  ugcSafetyStatus?: string | null;
  ugcSafetyReason?: string | null;
};

const PLATFORM_HOST_MAP: Record<string, string[]> = {
  INSTAGRAM: ['instagram.com'],
  FACEBOOK: ['facebook.com', 'fb.watch'],
  TIKTOK: ['tiktok.com'],
  X: ['x.com', 'twitter.com'],
  YOUTUBE: ['youtube.com', 'youtu.be'],
  LINKEDIN: ['linkedin.com'],
  WHATSAPP: ['whatsapp.com', 'wa.me'],
  TELEGRAM: ['telegram.org', 't.me', 'telegram.me'],
  WEBSITE: [],
};

const BASE_BLOCKED_SOCIAL_DOMAINS = new Set(['bit.ly', 'tinyurl.com', 'rb.gy']);

type ModerationActionPayload = {
  reasonCode?: unknown;
  reviewerNote?: unknown;
  escalationQueue?: unknown;
};

type SocialLinkModerationEvent = {
  socialLinkId: string;
  action: 'FLAG' | 'HIDE' | 'APPROVE' | 'ESCALATE' | 'QUALITY_REVIEW';
  reasonCode: string | null;
  reviewerNote: string | null;
  escalationQueue: string | null;
  actorUserId: string | null;
  actorRole: string | null;
  createdAt: string;
};

type ModerationQueueQuery = {
  limit?: unknown;
  offset?: unknown;
};

@Injectable()
export class SocialLinksService {
  constructor(private readonly prisma: PrismaService) {}

  async listModerationQueue(status?: string, targetType?: string, qualityStatus?: string, query?: ModerationQueueQuery) {
    const normalizedTrustStatus = this.parseOptionalTrustSafetyStatus(status);
    const normalizedTargetType = this.parseOptionalTargetType(targetType);
    const normalizedQualityStatus = this.parseOptionalContentQualityStatus(qualityStatus);
    const pagination = this.parseModerationPagination(query);

    const queue = await this.prisma.socialLink.findMany({
      where: {
        ...(normalizedTargetType ? { targetType: normalizedTargetType } : {}),
        ...(normalizedTrustStatus
          ? { trustSafetyStatus: normalizedTrustStatus }
          : {
              OR: [
                { active: false },
                { verified: false },
                { ugcSafetyStatus: { not: 'SAFE' } },
                { trustSafetyStatus: { not: 'CLEAR' } },
                { contentQualityStatus: { not: 'GOOD' } },
              ],
            }),
        ...(normalizedQualityStatus ? { contentQualityStatus: normalizedQualityStatus } : {}),
      },
      take: pagination.limit,
      skip: pagination.offset,
      orderBy: [{ updatedAt: 'desc' }, { createdAt: 'desc' }],
    });

    const latestEvents = await this.getLatestModerationEventsBySocialLinkId();

    return queue.map((item) => ({
      ...item,
      moderation: latestEvents.get(item.id) ?? null,
    }));
  }

  async listAccommodationLinks(accommodationId: string) {
    await this.ensureAccommodationExists(accommodationId);
    return this.prisma.socialLink.findMany({
      where: {
        targetType: 'ACCOMMODATION',
        accommodationId,
        active: true,
        verified: true,
        ugcSafetyStatus: 'SAFE',
      },
      orderBy: [{ displayOrder: 'asc' }, { createdAt: 'asc' }],
    });
  }

  async listTransportLinks(transportId: string) {
    await this.ensureTransportExists(transportId);
    return this.prisma.socialLink.findMany({
      where: {
        targetType: 'TRANSPORT',
        transportId,
        active: true,
        verified: true,
        ugcSafetyStatus: 'SAFE',
      },
      orderBy: [{ displayOrder: 'asc' }, { createdAt: 'asc' }],
    });
  }

  async listVendorLinks(vendorId: string) {
    await this.ensureVendorExists(vendorId);
    return this.prisma.socialLink.findMany({
      where: {
        targetType: 'VENDOR',
        vendorId,
        active: true,
        verified: true,
        ugcSafetyStatus: 'SAFE',
      },
      orderBy: [{ displayOrder: 'asc' }, { createdAt: 'asc' }],
    });
  }

  async create(payload: SocialLinkPayload, actor?: RequestActor) {
    const normalized = await this.normalizePayload(payload, { partial: false });
    await this.assertVendorScopedPermission(actor, normalized);

    return this.prisma.socialLink.create({
      data: normalized as Prisma.SocialLinkUncheckedCreateInput,
    });
  }

  async update(id: string, payload: SocialLinkPayload, actor?: RequestActor) {
    const existing = await this.prisma.socialLink.findUnique({ where: { id } });
    if (!existing) {
      throw new NotFoundException('Social link not found');
    }

    await this.assertVendorScopedPermission(actor, {
      targetType: existing.targetType,
      accommodationId: existing.accommodationId ?? undefined,
      transportId: existing.transportId ?? undefined,
      vendorId: existing.vendorId ?? undefined,
    });

    const normalized = await this.normalizePayload(payload, {
      partial: true,
      existingTargetType: existing.targetType,
      existingPlatform: existing.platform,
      existingUrl: existing.url,
      existingModeration: {
        embedPolicy: existing.embedPolicy,
        ugcSafetyStatus: existing.ugcSafetyStatus,
        ugcSafetyReason: existing.ugcSafetyReason,
      },
    });
    const merged = {
      targetType: existing.targetType,
      accommodationId: existing.accommodationId ?? undefined,
      transportId: existing.transportId ?? undefined,
      vendorId: existing.vendorId ?? undefined,
      ...normalized,
    };

    await this.assertVendorScopedPermission(actor, merged);

    return this.prisma.socialLink.update({
      where: { id },
      data: normalized as Prisma.SocialLinkUncheckedUpdateInput,
    });
  }

  async remove(id: string, actor?: RequestActor) {
    const existing = await this.prisma.socialLink.findUnique({ where: { id } });
    if (!existing) {
      throw new NotFoundException('Social link not found');
    }

    await this.assertVendorScopedPermission(actor, {
      targetType: existing.targetType,
      accommodationId: existing.accommodationId ?? undefined,
      transportId: existing.transportId ?? undefined,
      vendorId: existing.vendorId ?? undefined,
    });

    await this.prisma.socialLink.delete({ where: { id } });
  }

  async flag(id: string, actor?: RequestActor, payload?: ModerationActionPayload) {
    const existing = await this.prisma.socialLink.findUnique({
      where: { id },
      select: { id: true, flaggedCount: true },
    });
    if (!existing) {
      throw new NotFoundException('Social link not found');
    }

    const nextFlaggedCount = (existing.flaggedCount ?? 0) + 1;
    const updated = await this.prisma.socialLink.update({
      where: { id },
      data: {
        active: false,
        verified: false,
        ugcSafetyStatus: 'REVIEW',
        trustSafetyStatus: 'UNDER_REVIEW',
        moderationReasonCode: this.parseOptionalModerationReasonCode(payload?.reasonCode),
        moderationReviewerNote: this.parseOptionalReviewerNote(payload?.reviewerNote),
        flaggedCount: nextFlaggedCount,
        lastFlaggedAt: new Date(),
      },
    });

    await this.recordModerationEvent(id, 'FLAG', actor, payload);
    return updated;
  }

  async hide(id: string, actor?: RequestActor, payload?: ModerationActionPayload) {
    await this.ensureSocialLinkExists(id);
    const updated = await this.prisma.socialLink.update({
      where: { id },
      data: {
        active: false,
        trustSafetyStatus: 'ACTIONED',
        moderationReasonCode: this.parseOptionalModerationReasonCode(payload?.reasonCode),
        moderationReviewerNote: this.parseOptionalReviewerNote(payload?.reviewerNote),
        actionedAt: new Date(),
      },
    });

    await this.recordModerationEvent(id, 'HIDE', actor, payload);
    return updated;
  }

  async approve(id: string, actor?: RequestActor, payload?: ModerationActionPayload) {
    const existing = await this.prisma.socialLink.findUnique({
      where: { id },
      select: { id: true, ugcSafetyStatus: true },
    });
    if (!existing) {
      throw new NotFoundException('Social link not found');
    }

    if (existing.ugcSafetyStatus === 'BLOCKED') {
      throw new BadRequestException('Blocked links cannot be approved without URL remediation');
    }

    const updated = await this.prisma.socialLink.update({
      where: { id },
      data: {
        active: true,
        verified: true,
        ugcSafetyStatus: 'SAFE',
        ugcSafetyReason: null,
        trustSafetyStatus: 'CLEAR',
        moderationReasonCode: this.parseOptionalModerationReasonCode(payload?.reasonCode),
        moderationReviewerNote: this.parseOptionalReviewerNote(payload?.reviewerNote),
        actionedAt: new Date(),
      },
    });

    await this.recordModerationEvent(id, 'APPROVE', actor, payload);
    return updated;
  }

  async escalate(id: string, actor?: RequestActor, payload?: ModerationActionPayload) {
    await this.ensureSocialLinkExists(id);
    const escalationQueue = this.parseEscalationQueue(payload?.escalationQueue);

    const updated = await this.prisma.socialLink.update({
      where: { id },
      data: {
        active: false,
        verified: false,
        trustSafetyStatus: 'ESCALATED',
        moderationReasonCode: this.parseOptionalModerationReasonCode(payload?.reasonCode),
        moderationReviewerNote: this.parseOptionalReviewerNote(payload?.reviewerNote),
        escalatedToQueue: escalationQueue,
        escalatedAt: new Date(),
      },
    });

    await this.recordModerationEvent(id, 'ESCALATE', actor, payload);
    return updated;
  }

  async reassessContentQuality(id: string, actor?: RequestActor, payload?: ModerationActionPayload) {
    const existing = await this.prisma.socialLink.findUnique({
      where: { id },
      select: { id: true, platform: true, url: true },
    });
    if (!existing) {
      throw new NotFoundException('Social link not found');
    }

    const quality = this.evaluateContentQuality(existing.url, existing.platform);

    const updated = await this.prisma.socialLink.update({
      where: { id },
      data: {
        contentQualityStatus: quality.status,
        contentQualityScore: quality.score,
        contentQualityNotes: quality.notes,
        qualityReviewedAt: new Date(),
        ...(quality.status === 'LOW'
          ? {
              trustSafetyStatus: 'UNDER_REVIEW' as TrustSafetyStatus,
              verified: false,
            }
          : {}),
      },
    });

    await this.recordModerationEvent(id, 'QUALITY_REVIEW', actor, payload);
    return updated;
  }

  async getModerationHistory(id: string) {
    await this.ensureSocialLinkExists(id);
    const events = await this.readModerationEvents();
    return events.filter((event) => event.socialLinkId === id);
  }

  private async recordModerationEvent(
    socialLinkId: string,
    action: 'FLAG' | 'HIDE' | 'APPROVE' | 'ESCALATE' | 'QUALITY_REVIEW',
    actor?: RequestActor,
    payload?: ModerationActionPayload,
  ) {
    const reasonCode = this.parseOptionalModerationReasonCode(payload?.reasonCode);
    const reviewerNote = this.parseOptionalReviewerNote(payload?.reviewerNote);
    const escalationQueue = this.parseEscalationQueue(payload?.escalationQueue);
    const events = await this.readModerationEvents();

    const nextEvent: SocialLinkModerationEvent = {
      socialLinkId,
      action,
      reasonCode,
      reviewerNote,
      escalationQueue,
      actorUserId: typeof actor?.id === 'string' && actor.id.trim().length > 0 ? actor.id.trim() : null,
      actorRole: typeof actor?.role === 'string' && actor.role.trim().length > 0 ? actor.role.trim() : null,
      createdAt: new Date().toISOString(),
    };

    const next = [nextEvent, ...events].slice(0, 5000);
    await this.prisma.appConfig.upsert({
      where: { key: this.socialModerationEventsKey() },
      update: {
        value: {
          updatedAt: new Date().toISOString(),
          items: next,
        } as unknown as object,
      },
      create: {
        key: this.socialModerationEventsKey(),
        value: {
          updatedAt: new Date().toISOString(),
          items: next,
        } as unknown as object,
      },
    });
  }

  private async readModerationEvents(): Promise<SocialLinkModerationEvent[]> {
    const row = await this.prisma.appConfig.findUnique({ where: { key: this.socialModerationEventsKey() } });
    if (!row) {
      return [];
    }

    const value = row.value as Record<string, unknown>;
    const items = Array.isArray(value.items) ? value.items : [];
    return items as SocialLinkModerationEvent[];
  }

  private async getLatestModerationEventsBySocialLinkId() {
    const events = await this.readModerationEvents();
    const latestById = new Map<string, SocialLinkModerationEvent>();
    for (const event of events) {
      if (!event?.socialLinkId || latestById.has(event.socialLinkId)) {
        continue;
      }

      latestById.set(event.socialLinkId, event);
    }

    return latestById;
  }

  private socialModerationEventsKey() {
    return 'social_links:moderation_events:v1';
  }

  private parseOptionalModerationReasonCode(value: unknown): string | null {
    if (value === undefined || value === null) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('reasonCode must be a string when provided');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized.length === 0) {
      return null;
    }

    const allowed = new Set([
      'SPAM',
      'ABUSIVE_LANGUAGE',
      'HARASSMENT',
      'MISLEADING_CONTENT',
      'INAPPROPRIATE_CONTENT',
      'POLICY_VIOLATION',
      'OTHER',
    ]);

    if (!allowed.has(normalized)) {
      throw new BadRequestException('reasonCode is not supported');
    }

    return normalized;
  }

  private parseOptionalReviewerNote(value: unknown): string | null {
    if (value === undefined || value === null) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('reviewerNote must be a string when provided');
    }

    const normalized = value.trim();
    if (normalized.length === 0) {
      return null;
    }

    if (normalized.length > 500) {
      throw new BadRequestException('reviewerNote exceeds 500 characters');
    }

    return normalized;
  }

  private parseEscalationQueue(value: unknown): string | null {
    if (value === undefined || value === null) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('escalationQueue must be a string when provided');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized.length === 0) {
      return null;
    }

    const allowed = new Set(['TRUST_AND_SAFETY', 'LEGAL', 'FRAUD', 'SUPPORT']);
    if (!allowed.has(normalized)) {
      throw new BadRequestException('escalationQueue is not supported');
    }

    return normalized;
  }

  private parseOptionalTrustSafetyStatus(value: unknown): TrustSafetyStatus | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('status must be CLEAR, UNDER_REVIEW, ESCALATED, or ACTIONED');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'CLEAR' || normalized === 'UNDER_REVIEW' || normalized === 'ESCALATED' || normalized === 'ACTIONED') {
      return normalized;
    }

    throw new BadRequestException('status must be CLEAR, UNDER_REVIEW, ESCALATED, or ACTIONED');
  }

  private parseOptionalContentQualityStatus(value: unknown): ContentQualityStatus | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('qualityStatus must be GOOD, REVIEW, or LOW');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'GOOD' || normalized === 'REVIEW' || normalized === 'LOW') {
      return normalized;
    }

    throw new BadRequestException('qualityStatus must be GOOD, REVIEW, or LOW');
  }

  private parseModerationPagination(query?: ModerationQueueQuery): { limit: number; offset: number } {
    const parsedLimit = this.parseIntegerOrDefault(query?.limit, 50);
    const parsedOffset = this.parseIntegerOrDefault(query?.offset, 0);

    const limit = Math.min(Math.max(parsedLimit, 1), 200);
    const offset = Math.max(parsedOffset, 0);

    return { limit, offset };
  }

  private parseIntegerOrDefault(value: unknown, defaultValue: number): number {
    if (value === undefined || value === null || value === '') {
      return defaultValue;
    }

    const numeric = typeof value === 'number' ? value : Number(value);
    if (!Number.isInteger(numeric)) {
      throw new BadRequestException('Pagination values must be integers');
    }

    return numeric;
  }

  private async normalizePayload(
    payload: SocialLinkPayload,
    options: {
      partial: boolean;
      existingTargetType?: string;
      existingPlatform?: string;
      existingUrl?: string;
      existingModeration?: SocialLinkModerationSnapshot;
    },
  ) {
    const targetType = options.partial
      ? this.parseOptionalTargetType(payload.targetType)
      : this.parseRequiredTargetType(payload.targetType);

    const targetId = this.parseOptionalString(payload.targetId);
    const platform = this.parseOptionalString(payload.platform)?.toUpperCase();
    const url = this.parseOptionalString(payload.url);
    const handle = this.parseOptionalNullableString(payload.handle);
    const embedPolicy = this.parseOptionalEmbedPolicy(payload.embedPolicy);
    const verified = this.parseOptionalBoolean(payload.verified);
    const displayOrder = this.parseOptionalInteger(payload.displayOrder);
    const active = this.parseOptionalBoolean(payload.active);

    const effectiveTargetType = targetType ?? options.existingTargetType;
    if (!options.partial && !targetId) {
      throw new BadRequestException('targetId is required');
    }

    const data: Record<string, unknown> = {};

    if (effectiveTargetType) {
      data.targetType = effectiveTargetType;
    }

    if (targetId !== undefined && effectiveTargetType) {
      if (effectiveTargetType === 'ACCOMMODATION') {
        await this.ensureAccommodationExists(targetId);
        data.accommodationId = targetId;
        data.transportId = null;
        data.vendorId = null;
      } else if (effectiveTargetType === 'TRANSPORT') {
        await this.ensureTransportExists(targetId);
        data.transportId = targetId;
        data.accommodationId = null;
        data.vendorId = null;
      } else if (effectiveTargetType === 'VENDOR') {
        await this.ensureVendorExists(targetId);
        data.vendorId = targetId;
        data.accommodationId = null;
        data.transportId = null;
      }
    }

    if (platform !== undefined) {
      if (!['INSTAGRAM', 'FACEBOOK', 'TIKTOK', 'X', 'YOUTUBE', 'LINKEDIN', 'WHATSAPP', 'TELEGRAM', 'WEBSITE'].includes(platform)) {
        throw new BadRequestException('platform is not supported');
      }
      data.platform = platform;
    }

    if (url !== undefined) {
      this.validatePublicUrl(url);
      data.url = url;
    }

    const effectivePlatform = (
      platform
      ?? (typeof data.platform === 'string' ? data.platform : undefined)
      ?? options.existingPlatform
    ) as string | undefined;
    const effectiveUrl = (
      url
      ?? (typeof data.url === 'string' ? data.url : undefined)
      ?? options.existingUrl
    ) as string | undefined;

    const shouldRecalculateSafety = !options.partial || url !== undefined || platform !== undefined;

    if (effectivePlatform && effectiveUrl && shouldRecalculateSafety) {
      this.validatePlatformUrlCompatibility(effectivePlatform, effectiveUrl);

      const safety = this.evaluateUgcSafety(effectiveUrl);
      data.ugcSafetyStatus = safety.status;
      data.ugcSafetyReason = safety.reason;
      data.trustSafetyStatus = safety.status === 'BLOCKED' ? 'UNDER_REVIEW' : 'CLEAR';

      const quality = this.evaluateContentQuality(effectiveUrl, effectivePlatform);
      data.contentQualityStatus = quality.status;
      data.contentQualityScore = quality.score;
      data.contentQualityNotes = quality.notes;
      data.qualityReviewedAt = new Date();

      if (safety.status === 'BLOCKED') {
        data.active = false;
        data.verified = false;
      }

      data.embedPolicy = embedPolicy
        ?? options.existingModeration?.embedPolicy
        ?? this.defaultEmbedPolicyForPlatform(effectivePlatform);
    } else if (embedPolicy !== undefined) {
      data.embedPolicy = embedPolicy;
    } else if (options.partial && options.existingModeration?.embedPolicy) {
      data.embedPolicy = options.existingModeration.embedPolicy;
    }

    if (options.partial && !shouldRecalculateSafety) {
      if (options.existingModeration?.ugcSafetyStatus) {
        data.ugcSafetyStatus = options.existingModeration.ugcSafetyStatus;
      }

      data.ugcSafetyReason = options.existingModeration?.ugcSafetyReason ?? null;
    }

    if (handle !== undefined) data.handle = handle;
    if (verified !== undefined) data.verified = verified;
    if (displayOrder !== undefined) data.displayOrder = displayOrder;
    if (active !== undefined) data.active = active;

    if (!options.partial) {
      if (!data.platform || !data.url) {
        throw new BadRequestException('platform and url are required');
      }

      if (!data.embedPolicy) {
        data.embedPolicy = this.defaultEmbedPolicyForPlatform(String(data.platform));
      }

      if (!data.ugcSafetyStatus) {
        data.ugcSafetyStatus = 'REVIEW';
      }

      if (!data.trustSafetyStatus) {
        data.trustSafetyStatus = 'CLEAR';
      }

      if (!data.contentQualityStatus) {
        data.contentQualityStatus = 'GOOD';
      }

      if (!data.contentQualityScore) {
        data.contentQualityScore = 100;
      }

      if (data.contentQualityNotes === undefined) {
        data.contentQualityNotes = null;
      }
    }

    if (options.partial && Object.keys(data).length === 0) {
      throw new BadRequestException('No updatable fields provided');
    }

    return data;
  }

  private async assertVendorScopedPermission(actor: RequestActor | undefined, data: Record<string, unknown>) {
    if (actor?.role !== 'VENDOR') {
      return;
    }

    if (typeof actor.vendorId !== 'string' || actor.vendorId.trim().length === 0) {
      throw new ForbiddenException('Vendor scope is missing for authenticated vendor user');
    }

    const scopedVendorId = actor.vendorId.trim();
    const targetType = typeof data.targetType === 'string' ? data.targetType : undefined;
    if (!targetType) {
      return;
    }

    if (targetType === 'VENDOR') {
      const vendorId = typeof data.vendorId === 'string' ? data.vendorId : undefined;
      if (!vendorId || vendorId !== scopedVendorId) {
        throw new ForbiddenException('Vendor users can only manage their own vendor social links');
      }
      return;
    }

    if (targetType === 'ACCOMMODATION') {
      const accommodationId = typeof data.accommodationId === 'string' ? data.accommodationId : undefined;
      if (!accommodationId) {
        return;
      }
      const row = await this.prisma.accommodation.findUnique({ where: { id: accommodationId }, select: { vendorId: true } });
      if (!row || row.vendorId !== scopedVendorId) {
        throw new ForbiddenException('Vendor users can only manage social links for their own accommodations');
      }
      return;
    }

    if (targetType === 'TRANSPORT') {
      const transportId = typeof data.transportId === 'string' ? data.transportId : undefined;
      if (!transportId) {
        return;
      }
      const row = await this.prisma.transport.findUnique({ where: { id: transportId }, select: { vendorId: true } });
      if (!row || row.vendorId !== scopedVendorId) {
        throw new ForbiddenException('Vendor users can only manage social links for their own transports');
      }
    }
  }

  private parseRequiredTargetType(value: unknown): 'ACCOMMODATION' | 'TRANSPORT' | 'VENDOR' {
    if (typeof value !== 'string') {
      throw new BadRequestException('targetType is required');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'ACCOMMODATION' || normalized === 'TRANSPORT' || normalized === 'VENDOR') {
      return normalized;
    }

    throw new BadRequestException('targetType must be ACCOMMODATION, TRANSPORT, or VENDOR');
  }

  private parseOptionalTargetType(value: unknown): 'ACCOMMODATION' | 'TRANSPORT' | 'VENDOR' | undefined {
    if (value === undefined) {
      return undefined;
    }

    return this.parseRequiredTargetType(value);
  }

  private parseOptionalString(value: unknown): string | undefined {
    if (value === undefined) return undefined;
    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException('Expected non-empty string value');
    }

    return value.trim();
  }

  private parseOptionalEmbedPolicy(value: unknown): EmbedPolicy | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('embedPolicy must be PLATFORM_EMBED, LINK_ONLY, or NO_EMBED');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'PLATFORM_EMBED' || normalized === 'LINK_ONLY' || normalized === 'NO_EMBED') {
      return normalized;
    }

    throw new BadRequestException('embedPolicy must be PLATFORM_EMBED, LINK_ONLY, or NO_EMBED');
  }

  private parseOptionalNullableString(value: unknown): string | null | undefined {
    if (value === undefined) return undefined;
    if (value === null) return null;
    if (typeof value !== 'string') {
      throw new BadRequestException('Expected string value');
    }

    return value.trim();
  }

  private parseOptionalBoolean(value: unknown): boolean | undefined {
    if (value === undefined) return undefined;
    if (typeof value !== 'boolean') {
      throw new BadRequestException('Expected boolean value');
    }

    return value;
  }

  private parseOptionalInteger(value: unknown): number | undefined {
    if (value === undefined) return undefined;
    const parsed = typeof value === 'number' ? value : Number(value);
    if (!Number.isInteger(parsed) || parsed < 0) {
      throw new BadRequestException('displayOrder must be a non-negative integer');
    }

    return parsed;
  }

  private validatePublicUrl(value: string) {
    let parsed: URL;

    try {
      parsed = new URL(value);
    } catch {
      throw new BadRequestException('url must be a valid absolute URL');
    }

    if (!['http:', 'https:'].includes(parsed.protocol)) {
      throw new BadRequestException('url protocol must be http or https');
    }

    if (parsed.username || parsed.password) {
      throw new BadRequestException('url must not contain embedded credentials');
    }

    const host = parsed.hostname.toLowerCase();
    if (host === 'localhost' || host.endsWith('.local')) {
      throw new BadRequestException('url host is not allowed for public social links');
    }

    if (
      /^127\./.test(host)
      || /^10\./.test(host)
      || /^192\.168\./.test(host)
      || /^172\.(1[6-9]|2[0-9]|3[0-1])\./.test(host)
    ) {
      throw new BadRequestException('url host is not allowed for public social links');
    }
  }

  private validatePlatformUrlCompatibility(platform: string, value: string) {
    const host = new URL(value).hostname.toLowerCase();

    const allowedHosts = PLATFORM_HOST_MAP[platform] ?? [];
    if (allowedHosts.length === 0) {
      return;
    }

    const compatible = allowedHosts.some((suffix) => host === suffix || host.endsWith(`.${suffix}`));
    if (!compatible) {
      throw new BadRequestException(`url host is not compatible with platform ${platform}`);
    }
  }

  private evaluateUgcSafety(value: string): { status: UgcSafetyStatus; reason: string | null } {
    const host = new URL(value).hostname.toLowerCase();
    const blockedFromEnv = (process.env.SOCIAL_LINK_BLOCKED_DOMAINS ?? '')
      .split(',')
      .map((entry) => entry.trim().toLowerCase())
      .filter((entry) => entry.length > 0);

    const blockedDomains = new Set(BASE_BLOCKED_SOCIAL_DOMAINS);
    for (const domain of blockedFromEnv) {
      blockedDomains.add(domain);
    }

    const isBlocked = Array.from(blockedDomains).some((domain) => host === domain || host.endsWith(`.${domain}`));
    if (isBlocked) {
      return {
        status: 'BLOCKED',
        reason: `Blocked domain: ${host}`,
      };
    }

    return {
      status: 'SAFE',
      reason: null,
    };
  }

  private evaluateContentQuality(url: string, platform: string): { status: ContentQualityStatus; score: number; notes: string | null } {
    const parsed = new URL(url);
    let score = 100;
    const notes: string[] = [];

    if (parsed.pathname === '/' || parsed.pathname.trim() === '') {
      score -= 15;
      notes.push('URL points to profile root without deep content context');
    }

    if (parsed.search.length > 0) {
      score -= 10;
      notes.push('URL contains query parameters that may reduce link durability');
    }

    if (platform === 'WEBSITE') {
      score -= 10;
      notes.push('Website links have lower native social preview fidelity');
    }

    if (parsed.pathname.length > 120) {
      score -= 10;
      notes.push('Very long path may indicate tracking or low-quality share link');
    }

    if (score < 0) {
      score = 0;
    }

    let status: ContentQualityStatus = 'GOOD';
    if (score < 60) {
      status = 'LOW';
    } else if (score < 80) {
      status = 'REVIEW';
    }

    return {
      status,
      score,
      notes: notes.length > 0 ? notes.join('; ') : null,
    };
  }

  private defaultEmbedPolicyForPlatform(platform: string): EmbedPolicy {
    const normalized = platform.trim().toUpperCase();
    if (normalized === 'WEBSITE') {
      return 'LINK_ONLY';
    }

    return 'PLATFORM_EMBED';
  }

  private async ensureAccommodationExists(id: string) {
    const row = await this.prisma.accommodation.findUnique({ where: { id }, select: { id: true } });
    if (!row) {
      throw new NotFoundException('Accommodation not found');
    }
  }

  private async ensureTransportExists(id: string) {
    const row = await this.prisma.transport.findUnique({ where: { id }, select: { id: true } });
    if (!row) {
      throw new NotFoundException('Transport not found');
    }
  }

  private async ensureVendorExists(id: string) {
    const row = await this.prisma.vendor.findUnique({ where: { id }, select: { id: true } });
    if (!row) {
      throw new NotFoundException('Vendor not found');
    }
  }

  private async ensureSocialLinkExists(id: string) {
    const row = await this.prisma.socialLink.findUnique({ where: { id }, select: { id: true } });
    if (!row) {
      throw new NotFoundException('Social link not found');
    }
  }
}

import { BadRequestException, Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { randomUUID } from 'crypto';
import { PrismaService } from '../prisma.service';

type ReviewCreatePayload = {
  targetType?: unknown;
  targetId?: unknown;
  rating?: unknown;
  title?: unknown;
  comment?: unknown;
};

type RequestActor = {
  id?: string;
  role?: string;
};

type ModerationActionPayload = {
  reasonCode?: unknown;
  reviewerNote?: unknown;
  escalationQueue?: unknown;
};

type ModerationQueueQuery = {
  limit?: unknown;
  offset?: unknown;
};

type ReviewModerationEvent = {
  reviewId: string;
  action: 'FLAG' | 'HIDE' | 'PUBLISH' | 'ESCALATE';
  reasonCode: string | null;
  reviewerNote: string | null;
  escalationQueue: string | null;
  actorUserId: string | null;
  actorRole: string | null;
  createdAt: string;
};

@Injectable()
export class ReviewsService {
  constructor(private readonly prisma: PrismaService) {}

  async listModerationQueue(status?: string, targetType?: string, query?: ModerationQueueQuery) {
    const normalized = this.parseOptionalReviewStatus(status);
    const normalizedTargetType = this.parseOptionalTargetTypeFilter(targetType);
    const pagination = this.parseModerationPagination(query);

    const queue = await this.prisma.review.findMany({
      where: {
        ...(normalized
          ? {
              status: normalized,
            }
          : {
              status: {
                in: ['FLAGGED', 'HIDDEN'],
              },
            }),
        ...(normalizedTargetType
          ? {
              targetType: normalizedTargetType,
            }
          : {}),
      },
      include: {
        user: {
          select: {
            id: true,
            name: true,
          },
        },
      },
      take: pagination.limit,
      skip: pagination.offset,
      orderBy: { updatedAt: 'desc' },
    });

    const latestEvents = await this.getLatestModerationEventsByReviewId();

    return queue.map((item) => ({
      ...item,
      moderation: latestEvents.get(item.id) ?? null,
    }));
  }

  async listAccommodationReviews(accommodationId: string) {
    await this.ensureAccommodationExists(accommodationId);

    let items: Array<any> = [];
    try {
      items = await this.prisma.review.findMany({
        where: {
          targetType: 'ACCOMMODATION',
          accommodationId,
          status: 'PUBLISHED',
        },
        include: {
          user: {
            select: {
              id: true,
              name: true,
            },
          },
        },
        orderBy: { createdAt: 'desc' },
      });
    } catch (error) {
      if (!this.isReviewSchemaDriftError(error)) {
        throw error;
      }

      const rawRows = await this.prisma.$queryRaw<Array<{
        id: string;
        rating: number;
        title: string | null;
        comment: string | null;
        status: string;
        createdAt: Date;
      }>>(Prisma.sql`
        SELECT
          "id",
          "rating",
          "title",
          "comment",
          "status",
          "createdAt"
        FROM "Review"
        WHERE "targetType" = 'ACCOMMODATION'
          AND "accommodationId" = ${accommodationId}
          AND "status" = 'PUBLISHED'
        ORDER BY "createdAt" DESC
      `);

      items = rawRows.map((row) => ({
        ...row,
        targetType: 'ACCOMMODATION',
        accommodationId,
        user: null,
      }));
    }

    const ratingSummary = this.computeRatingSummary(items.map((item) => item.rating));

    return {
      targetType: 'ACCOMMODATION',
      targetId: accommodationId,
      ratingSummary,
      items,
    };
  }

  async listTransportReviews(transportId: string) {
    await this.ensureTransportExists(transportId);

    let items: Array<any> = [];
    try {
      items = await this.prisma.review.findMany({
        where: {
          targetType: 'TRANSPORT',
          transportId,
          status: 'PUBLISHED',
        },
        include: {
          user: {
            select: {
              id: true,
              name: true,
            },
          },
        },
        orderBy: { createdAt: 'desc' },
      });
    } catch (error) {
      if (!this.isReviewSchemaDriftError(error)) {
        throw error;
      }

      const rawRows = await this.prisma.$queryRaw<Array<{
        id: string;
        rating: number;
        title: string | null;
        comment: string | null;
        status: string;
        createdAt: Date;
      }>>(Prisma.sql`
        SELECT
          "id",
          "rating",
          "title",
          "comment",
          "status",
          "createdAt"
        FROM "Review"
        WHERE "targetType" = 'TRANSPORT'
          AND "transportId" = ${transportId}
          AND "status" = 'PUBLISHED'
        ORDER BY "createdAt" DESC
      `);

      items = rawRows.map((row) => ({
        ...row,
        targetType: 'TRANSPORT',
        transportId,
        user: null,
      }));
    }

    const ratingSummary = this.computeRatingSummary(items.map((item) => item.rating));

    return {
      targetType: 'TRANSPORT',
      targetId: transportId,
      ratingSummary,
      items,
    };
  }

  async listActivityReviews(activityId: string) {
    const normalizedId = this.parseRequiredString(activityId, 'activityId');

    const items = await this.prisma.review.findMany({
      where: {
        targetType: 'ACTIVITY',
        activityRefId: normalizedId,
        status: 'PUBLISHED',
      },
      include: {
        user: {
          select: {
            id: true,
            name: true,
          },
        },
      },
      orderBy: { createdAt: 'desc' },
    });

    const ratingSummary = this.computeRatingSummary(items.map((item) => item.rating));

    return {
      targetType: 'ACTIVITY',
      targetId: normalizedId,
      ratingSummary,
      items,
    };
  }

  async listServiceReviews(serviceId: string) {
    const normalizedId = this.parseRequiredString(serviceId, 'serviceId');

    const items = await this.prisma.review.findMany({
      where: {
        targetType: 'SERVICE',
        serviceRefId: normalizedId,
        status: 'PUBLISHED',
      },
      include: {
        user: {
          select: {
            id: true,
            name: true,
          },
        },
      },
      orderBy: { createdAt: 'desc' },
    });

    const ratingSummary = this.computeRatingSummary(items.map((item) => item.rating));

    return {
      targetType: 'SERVICE',
      targetId: normalizedId,
      ratingSummary,
      items,
    };
  }

  async create(payload: ReviewCreatePayload, actor?: RequestActor) {
    const userId = this.parseActorUserId(actor?.id);
    const targetType = this.parseTargetType(payload.targetType);
    const targetId = this.parseRequiredString(payload.targetId, 'targetId');
    const rating = this.parseRating(payload.rating);
    const title = this.parseOptionalNullableString(payload.title, { maxLen: 120 });
    const comment = this.parseOptionalNullableString(payload.comment, { maxLen: 2000 });

    if (targetType === 'ACCOMMODATION') {
      await this.ensureAccommodationExists(targetId);
      const existing = await this.prisma.review.findFirst({
        where: {
          userId,
          targetType,
          accommodationId: targetId,
        },
        select: { id: true },
      });

      if (existing) {
        throw new BadRequestException('You have already reviewed this accommodation');
      }

      const verifiedStay = await this.hasVerifiedAccommodationStay(userId, targetId);

      return this.prisma.review.create({
        data: {
          userId,
          targetType,
          accommodationId: targetId,
          rating,
          title,
          comment,
          verifiedStay,
          status: 'PUBLISHED',
        },
        include: {
          user: {
            select: {
              id: true,
              name: true,
            },
          },
        },
      });
    }

    if (targetType === 'TRANSPORT') {
      await this.ensureTransportExists(targetId);
      let existing: { id: string } | null = null;
      try {
        existing = await this.prisma.review.findFirst({
          where: {
            userId,
            targetType,
            transportId: targetId,
          },
          select: { id: true },
        });
      } catch (error) {
        if (!this.isReviewSchemaDriftError(error)) {
          throw error;
        }
      }

      if (existing) {
        throw new BadRequestException('You have already reviewed this transport');
      }

      const verifiedStay = await this.hasVerifiedTransportStay(userId, targetId);

      try {
        return await this.prisma.review.create({
          data: {
            userId,
            targetType,
            transportId: targetId,
            rating,
            title,
            comment,
            verifiedStay,
            status: 'PUBLISHED',
          },
          include: {
            user: {
              select: {
                id: true,
                name: true,
              },
            },
          },
        });
      } catch (error) {
        if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === 'P2002') {
          throw new BadRequestException('You have already reviewed this transport');
        }

        if (!this.isReviewSchemaDriftError(error)) {
          throw error;
        }

        const id = randomUUID();
        await this.prisma.$executeRaw(Prisma.sql`
          INSERT INTO "Review" (
            "id",
            "userId",
            "targetType",
            "transportId",
            "rating",
            "status"
          )
          VALUES (
            ${id},
            ${userId},
            'TRANSPORT',
            ${targetId},
            ${rating},
            'PUBLISHED'
          )
        `);

        return {
          id,
          userId,
          targetType: 'TRANSPORT',
          transportId: targetId,
          rating,
          title: title ?? null,
          comment: comment ?? null,
          verifiedStay: false,
          status: 'PUBLISHED',
          user: {
            id: userId,
            name: null,
          },
        };
      }
    }

    if (targetType === 'ACTIVITY') {
      const existing = await this.prisma.review.findFirst({
        where: {
          userId,
          targetType,
          activityRefId: targetId,
        },
        select: { id: true },
      });

      if (existing) {
        throw new BadRequestException('You have already reviewed this activity');
      }

      return this.prisma.review.create({
        data: {
          userId,
          targetType,
          activityRefId: targetId,
          rating,
          title,
          comment,
          verifiedStay: false,
          status: 'PUBLISHED',
        },
        include: {
          user: {
            select: {
              id: true,
              name: true,
            },
          },
        },
      });
    }

    const existing = await this.prisma.review.findFirst({
      where: {
        userId,
        targetType,
        serviceRefId: targetId,
      },
      select: { id: true },
    });

    if (existing) {
      throw new BadRequestException('You have already reviewed this service');
    }

    return this.prisma.review.create({
      data: {
        userId,
        targetType,
        serviceRefId: targetId,
        rating,
        title,
        comment,
        verifiedStay: false,
        status: 'PUBLISHED',
      },
      include: {
        user: {
          select: {
            id: true,
            name: true,
          },
        },
      },
    });
  }

  async flag(id: string, actor?: RequestActor, payload?: ModerationActionPayload) {
    const existing = await this.prisma.review.findUnique({ where: { id }, select: { id: true, status: true } });
    if (!existing) {
      throw new NotFoundException('Review not found');
    }

    if (existing.status === 'HIDDEN') {
      const unchanged = await this.prisma.review.findUnique({
        where: { id },
        include: {
          user: {
            select: {
              id: true,
              name: true,
            },
          },
        },
      });

      await this.recordModerationEvent(id, 'FLAG', actor, payload);
      return unchanged;
    }

    const current = await this.prisma.review.findUnique({
      where: { id },
      select: { flaggedCount: true },
    });
    const nextFlaggedCount = (current?.flaggedCount ?? 0) + 1;

    const updated = await this.prisma.review.update({
      where: { id },
      data: {
        status: 'FLAGGED',
        trustSafetyStatus: 'UNDER_REVIEW',
        flaggedCount: nextFlaggedCount,
        lastFlaggedAt: new Date(),
      },
      include: {
        user: {
          select: {
            id: true,
            name: true,
          },
        },
      },
    });

    await this.recordModerationEvent(id, 'FLAG', actor, payload);
    return updated;
  }

  async setStatus(id: string, status: 'PUBLISHED' | 'HIDDEN', actor?: RequestActor, payload?: ModerationActionPayload) {
    const existing = await this.prisma.review.findUnique({ where: { id }, select: { id: true } });
    if (!existing) {
      throw new NotFoundException('Review not found');
    }

    const updated = await this.prisma.review.update({
      where: { id },
      data: {
        status,
        trustSafetyStatus: status === 'HIDDEN' ? 'ACTIONED' : 'CLEAR',
        moderationReasonCode: this.parseOptionalModerationReasonCode(payload?.reasonCode),
        moderationReviewerNote: this.parseOptionalReviewerNote(payload?.reviewerNote),
        actionedAt: new Date(),
      },
      include: {
        user: {
          select: {
            id: true,
            name: true,
          },
        },
      },
    });

    await this.recordModerationEvent(id, status === 'HIDDEN' ? 'HIDE' : 'PUBLISH', actor, payload);
    return updated;
  }

  async escalate(id: string, actor?: RequestActor, payload?: ModerationActionPayload) {
    const existing = await this.prisma.review.findUnique({ where: { id }, select: { id: true } });
    if (!existing) {
      throw new NotFoundException('Review not found');
    }

    const escalationQueue = this.parseEscalationQueue(payload?.escalationQueue);
    const reasonCode = this.parseOptionalModerationReasonCode(payload?.reasonCode);
    const reviewerNote = this.parseOptionalReviewerNote(payload?.reviewerNote);

    const updated = await this.prisma.review.update({
      where: { id },
      data: {
        status: 'FLAGGED',
        trustSafetyStatus: 'ESCALATED',
        moderationReasonCode: reasonCode,
        moderationReviewerNote: reviewerNote,
        escalatedToQueue: escalationQueue,
        escalatedAt: new Date(),
      },
      include: {
        user: {
          select: {
            id: true,
            name: true,
          },
        },
      },
    });

    await this.recordModerationEvent(id, 'ESCALATE', actor, payload);
    return updated;
  }

  async getModerationHistory(id: string) {
    const existing = await this.prisma.review.findUnique({ where: { id }, select: { id: true } });
    if (!existing) {
      throw new NotFoundException('Review not found');
    }

    const events = await this.readModerationEvents();
    return events.filter((event) => event.reviewId === id);
  }

  private async recordModerationEvent(
    reviewId: string,
    action: 'FLAG' | 'HIDE' | 'PUBLISH' | 'ESCALATE',
    actor?: RequestActor,
    payload?: ModerationActionPayload,
  ) {
    const reasonCode = this.parseOptionalModerationReasonCode(payload?.reasonCode);
    const reviewerNote = this.parseOptionalReviewerNote(payload?.reviewerNote);
    const escalationQueue = this.parseEscalationQueue(payload?.escalationQueue);
    const events = await this.readModerationEvents();

    const nextEvent: ReviewModerationEvent = {
      reviewId,
      action,
      reasonCode,
      reviewerNote,
      escalationQueue,
      actorUserId: typeof actor?.id === 'string' && actor.id.trim().length > 0 ? actor.id.trim() : null,
      actorRole: typeof actor?.role === 'string' && actor.role.trim().length > 0 ? actor.role.trim() : null,
      createdAt: new Date().toISOString(),
    };

    const next = [nextEvent, ...events].slice(0, 5000);
    try {
      await this.prisma.appConfig.upsert({
        where: { key: this.reviewModerationEventsKey() },
        update: {
          value: {
            updatedAt: new Date().toISOString(),
            items: next,
          } as unknown as object,
        },
        create: {
          key: this.reviewModerationEventsKey(),
          value: {
            updatedAt: new Date().toISOString(),
            items: next,
          } as unknown as object,
        },
      });
    } catch {
      // Moderation history persistence should not fail write-path endpoints.
    }
  }

  private async readModerationEvents(): Promise<ReviewModerationEvent[]> {
    try {
      const row = await this.prisma.appConfig.findUnique({ where: { key: this.reviewModerationEventsKey() } });
      if (!row) {
        return [];
      }

      const value = row.value as Record<string, unknown>;
      const items = Array.isArray(value.items) ? value.items : [];
      return items as ReviewModerationEvent[];
    } catch {
      // When config storage is unavailable, serve queue/history without enrichment.
      return [];
    }
  }

  private async getLatestModerationEventsByReviewId() {
    const events = await this.readModerationEvents();
    const latestById = new Map<string, ReviewModerationEvent>();
    for (const event of events) {
      if (!event?.reviewId || latestById.has(event.reviewId)) {
        continue;
      }

      latestById.set(event.reviewId, event);
    }

    return latestById;
  }

  private reviewModerationEventsKey() {
    return 'reviews:moderation_events:v1';
  }

  private isReviewSchemaDriftError(error: unknown): boolean {
    if (!(error instanceof Prisma.PrismaClientKnownRequestError)) {
      return false;
    }

    if (error.code === 'P2022') {
      return true;
    }

    return (error.message ?? '').toLowerCase().includes('review');
  }

  private isBookingSchemaDriftError(error: unknown): boolean {
    if (!(error instanceof Prisma.PrismaClientKnownRequestError)) {
      return false;
    }

    if (error.code === 'P2022') {
      return true;
    }

    return (error.message ?? '').toLowerCase().includes('booking');
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

  private async hasVerifiedAccommodationStay(userId: string, accommodationId: string) {
    let booking: { id: string } | null = null;
    try {
      booking = await this.prisma.booking.findFirst({
        where: {
          userId,
          accommodationId,
          status: 'CONFIRMED',
        },
        select: { id: true },
      });
    } catch (error) {
      if (!this.isBookingSchemaDriftError(error)) {
        throw error;
      }
    }

    return Boolean(booking);
  }

  private async hasVerifiedTransportStay(userId: string, transportId: string) {
    let booking: { id: string } | null = null;
    try {
      booking = await this.prisma.booking.findFirst({
        where: {
          userId,
          transportId,
          status: 'CONFIRMED',
        },
        select: { id: true },
      });
    } catch (error) {
      if (!this.isBookingSchemaDriftError(error)) {
        throw error;
      }
    }

    return Boolean(booking);
  }

  private computeRatingSummary(ratings: number[]) {
    if (ratings.length === 0) {
      return {
        count: 0,
        average: 0,
      };
    }

    const total = ratings.reduce((sum, value) => sum + value, 0);
    const average = total / ratings.length;

    return {
      count: ratings.length,
      average: Number(average.toFixed(2)),
    };
  }

  private parseActorUserId(value: unknown) {
    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException('Authenticated user is required');
    }

    return value.trim();
  }

  private parseTargetType(value: unknown): 'ACCOMMODATION' | 'TRANSPORT' | 'ACTIVITY' | 'SERVICE' {
    if (typeof value !== 'string') {
      throw new BadRequestException('targetType must be ACCOMMODATION, TRANSPORT, ACTIVITY, or SERVICE');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'ACCOMMODATION' || normalized === 'TRANSPORT' || normalized === 'ACTIVITY' || normalized === 'SERVICE') {
      return normalized;
    }

    throw new BadRequestException('targetType must be ACCOMMODATION, TRANSPORT, ACTIVITY, or SERVICE');
  }

  private parseRating(value: unknown): number {
    const parsed = typeof value === 'number' ? value : Number(value);
    if (!Number.isInteger(parsed) || parsed < 1 || parsed > 5) {
      throw new BadRequestException('rating must be an integer between 1 and 5');
    }

    return parsed;
  }

  private parseOptionalReviewStatus(value: unknown): 'PUBLISHED' | 'FLAGGED' | 'HIDDEN' | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('status must be PUBLISHED, FLAGGED, or HIDDEN');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'PUBLISHED' || normalized === 'FLAGGED' || normalized === 'HIDDEN') {
      return normalized;
    }

    throw new BadRequestException('status must be PUBLISHED, FLAGGED, or HIDDEN');
  }

  private parseOptionalTargetTypeFilter(value: unknown): 'ACCOMMODATION' | 'TRANSPORT' | 'ACTIVITY' | 'SERVICE' | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('targetType must be ACCOMMODATION, TRANSPORT, ACTIVITY, or SERVICE');
    }

    return this.parseTargetType(value);
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

  private parseRequiredString(value: unknown, field: string): string {
    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException(`${field} must be a non-empty string`);
    }

    return value.trim();
  }

  private parseOptionalNullableString(value: unknown, options: { maxLen: number }): string | null | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (value === null) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('Expected string value');
    }

    const trimmed = value.trim();
    if (trimmed.length > options.maxLen) {
      throw new BadRequestException(`String value exceeds ${options.maxLen} characters`);
    }

    return trimmed;
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
}

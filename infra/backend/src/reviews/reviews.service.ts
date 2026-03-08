import { BadRequestException, Injectable, NotFoundException } from '@nestjs/common';
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
};

type ReviewModerationEvent = {
  reviewId: string;
  action: 'FLAG' | 'HIDE' | 'PUBLISH';
  reasonCode: string | null;
  reviewerNote: string | null;
  actorUserId: string | null;
  actorRole: string | null;
  createdAt: string;
};

@Injectable()
export class ReviewsService {
  constructor(private readonly prisma: PrismaService) {}

  async listModerationQueue(status?: string, targetType?: string) {
    const normalized = this.parseOptionalReviewStatus(status);
    const normalizedTargetType = this.parseOptionalTargetTypeFilter(targetType);

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

    const items = await this.prisma.review.findMany({
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

    const items = await this.prisma.review.findMany({
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
      const existing = await this.prisma.review.findFirst({
        where: {
          userId,
          targetType,
          transportId: targetId,
        },
        select: { id: true },
      });

      if (existing) {
        throw new BadRequestException('You have already reviewed this transport');
      }

      const verifiedStay = await this.hasVerifiedTransportStay(userId, targetId);

      return this.prisma.review.create({
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

    const updated = await this.prisma.review.update({
      where: { id },
      data: { status: 'FLAGGED' },
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
      data: { status },
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

  private async recordModerationEvent(
    reviewId: string,
    action: 'FLAG' | 'HIDE' | 'PUBLISH',
    actor?: RequestActor,
    payload?: ModerationActionPayload,
  ) {
    const reasonCode = this.parseOptionalModerationReasonCode(payload?.reasonCode);
    const reviewerNote = this.parseOptionalReviewerNote(payload?.reviewerNote);
    const events = await this.readModerationEvents();

    const nextEvent: ReviewModerationEvent = {
      reviewId,
      action,
      reasonCode,
      reviewerNote,
      actorUserId: typeof actor?.id === 'string' && actor.id.trim().length > 0 ? actor.id.trim() : null,
      actorRole: typeof actor?.role === 'string' && actor.role.trim().length > 0 ? actor.role.trim() : null,
      createdAt: new Date().toISOString(),
    };

    const next = [nextEvent, ...events].slice(0, 5000);
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
  }

  private async readModerationEvents(): Promise<ReviewModerationEvent[]> {
    const row = await this.prisma.appConfig.findUnique({ where: { key: this.reviewModerationEventsKey() } });
    if (!row) {
      return [];
    }

    const value = row.value as Record<string, unknown>;
    const items = Array.isArray(value.items) ? value.items : [];
    return items as ReviewModerationEvent[];
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

  private async hasVerifiedAccommodationStay(userId: string, accommodationId: string) {
    const booking = await this.prisma.booking.findFirst({
      where: {
        userId,
        accommodationId,
        status: 'CONFIRMED',
      },
      select: { id: true },
    });

    return Boolean(booking);
  }

  private async hasVerifiedTransportStay(userId: string, transportId: string) {
    const booking = await this.prisma.booking.findFirst({
      where: {
        userId,
        transportId,
        status: 'CONFIRMED',
      },
      select: { id: true },
    });

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

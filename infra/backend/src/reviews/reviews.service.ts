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
};

@Injectable()
export class ReviewsService {
  constructor(private readonly prisma: PrismaService) {}

  async listModerationQueue(status?: string) {
    const normalized = this.parseOptionalReviewStatus(status);

    return this.prisma.review.findMany({
      where: normalized
        ? {
            status: normalized,
          }
        : {
            status: {
              in: ['FLAGGED', 'HIDDEN'],
            },
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

  async flag(id: string) {
    const existing = await this.prisma.review.findUnique({ where: { id }, select: { id: true, status: true } });
    if (!existing) {
      throw new NotFoundException('Review not found');
    }

    if (existing.status === 'HIDDEN') {
      return this.prisma.review.findUnique({
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
    }

    return this.prisma.review.update({
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
  }

  async setStatus(id: string, status: 'PUBLISHED' | 'HIDDEN') {
    const existing = await this.prisma.review.findUnique({ where: { id }, select: { id: true } });
    if (!existing) {
      throw new NotFoundException('Review not found');
    }

    return this.prisma.review.update({
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

  private parseTargetType(value: unknown): 'ACCOMMODATION' | 'TRANSPORT' {
    if (typeof value !== 'string') {
      throw new BadRequestException('targetType must be ACCOMMODATION or TRANSPORT');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'ACCOMMODATION' || normalized === 'TRANSPORT') {
      return normalized;
    }

    throw new BadRequestException('targetType must be ACCOMMODATION or TRANSPORT');
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

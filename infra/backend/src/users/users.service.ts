import { BadRequestException, Injectable, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../prisma.service';

export type AuthRole =
  | 'USER'
  | 'VENDOR'
  | 'ADMIN'
  | 'ADMIN_SUPER'
  | 'ADMIN_CARE'
  | 'ADMIN_FINANCE';

export type AuthContext = {
  id: string;
  role: AuthRole;
  email?: string;
  vendorId?: string;
};

type UserProfilePreferences = {
  preferredAtollIds: number[];
  preferredServiceCategories: string[];
  travelPace: 'SLOW' | 'BALANCED' | 'FAST';
  budgetBand: 'BUDGET' | 'MID' | 'PREMIUM';
};

type UserProfilePayload = {
  name?: unknown;
  preferences?: unknown;
};

@Injectable()
export class UsersService {
  constructor(private readonly prisma: PrismaService) {}

  async ensureUserFromAuthContext(context: AuthContext) {
    const normalizedEmail = (context.email && context.email.trim().length > 0)
      ? context.email.trim().toLowerCase()
      : `${context.id}@local.workation.test`;

    return this.prisma.user.upsert({
      where: { id: context.id },
      update: {
        email: normalizedEmail,
      },
      create: {
        id: context.id,
        email: normalizedEmail,
      },
    });
  }

  async findById(id: string) {
    return this.prisma.user.findUnique({ where: { id } });
  }

  async getProfile(userId: string) {
    const user = await this.prisma.user.findUnique({
      where: { id: userId },
      select: {
        id: true,
        email: true,
        name: true,
      },
    });

    if (!user) {
      throw new NotFoundException('User not found');
    }

    const preferences = await this.getPreferencesForUser(userId);
    const profileCompleteness = this.computeProfileCompleteness(user, preferences);

    return {
      user,
      preferences,
      profileCompleteness,
    };
  }

  async updateProfile(userId: string, payload: UserProfilePayload) {
    const user = await this.prisma.user.findUnique({
      where: { id: userId },
      select: { id: true },
    });

    if (!user) {
      throw new NotFoundException('User not found');
    }

    const updateData: Record<string, unknown> = {};
    if (Object.prototype.hasOwnProperty.call(payload, 'name')) {
      updateData.name = this.parseOptionalNullableName(payload.name);
    }

    if (Object.keys(updateData).length > 0) {
      await this.prisma.user.update({
        where: { id: userId },
        data: updateData,
      });
    }

    if (Object.prototype.hasOwnProperty.call(payload, 'preferences')) {
      const preferences = this.normalizePreferences(payload.preferences);
      await this.prisma.appConfig.upsert({
        where: { key: this.preferencesKey(userId) },
        create: {
          key: this.preferencesKey(userId),
          value: preferences,
        },
        update: {
          value: preferences,
        },
      });
    }

    return this.getProfile(userId);
  }

  private async getPreferencesForUser(userId: string): Promise<UserProfilePreferences> {
    const stored = await this.prisma.appConfig.findUnique({
      where: { key: this.preferencesKey(userId) },
      select: { value: true },
    });

    return this.normalizePreferences(stored?.value);
  }

  private computeProfileCompleteness(
    user: { email: string; name: string | null },
    preferences: UserProfilePreferences,
  ) {
    const missingFields: string[] = [];
    let score = 0;

    if (user.email && user.email.trim().length > 0) {
      score += 40;
    } else {
      missingFields.push('email');
    }

    if (user.name && user.name.trim().length >= 2) {
      score += 30;
    } else {
      missingFields.push('name');
    }

    if (preferences.preferredAtollIds.length > 0 || preferences.preferredServiceCategories.length > 0) {
      score += 30;
    } else {
      missingFields.push('preferences');
    }

    return {
      score,
      status: score === 100 ? 'COMPLETE' : 'PARTIAL',
      missingFields,
    };
  }

  private normalizePreferences(value: unknown): UserProfilePreferences {
    const source = value && typeof value === 'object' && !Array.isArray(value)
      ? (value as Record<string, unknown>)
      : {};

    const preferredAtollIdsRaw = source.preferredAtollIds;
    const preferredServiceCategoriesRaw = source.preferredServiceCategories;
    const travelPaceRaw = source.travelPace;
    const budgetBandRaw = source.budgetBand;

    const preferredAtollIds = Array.isArray(preferredAtollIdsRaw)
      ? preferredAtollIdsRaw
          .map((entry) => Number(entry))
          .filter((entry) => Number.isInteger(entry) && entry > 0)
      : [];

    const preferredServiceCategories = Array.isArray(preferredServiceCategoriesRaw)
      ? preferredServiceCategoriesRaw
          .filter((entry): entry is string => typeof entry === 'string' && entry.trim().length > 0)
          .map((entry) => entry.trim().toUpperCase())
      : [];

    const travelPace = typeof travelPaceRaw === 'string' ? travelPaceRaw.toUpperCase() : '';
    const budgetBand = typeof budgetBandRaw === 'string' ? budgetBandRaw.toUpperCase() : '';

    if (travelPace && travelPace !== 'SLOW' && travelPace !== 'BALANCED' && travelPace !== 'FAST') {
      throw new BadRequestException('preferences.travelPace must be SLOW, BALANCED, or FAST');
    }

    if (budgetBand && budgetBand !== 'BUDGET' && budgetBand !== 'MID' && budgetBand !== 'PREMIUM') {
      throw new BadRequestException('preferences.budgetBand must be BUDGET, MID, or PREMIUM');
    }

    return {
      preferredAtollIds,
      preferredServiceCategories,
      travelPace: (travelPace || 'BALANCED') as 'SLOW' | 'BALANCED' | 'FAST',
      budgetBand: (budgetBand || 'MID') as 'BUDGET' | 'MID' | 'PREMIUM',
    };
  }

  private parseOptionalNullableName(value: unknown): string | null {
    if (value === null) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('name must be a string or null');
    }

    const trimmed = value.trim();
    if (trimmed.length < 2 || trimmed.length > 120) {
      throw new BadRequestException('name must be between 2 and 120 characters');
    }

    return trimmed;
  }

  private preferencesKey(userId: string) {
    return `user:profile:${userId}`;
  }
}

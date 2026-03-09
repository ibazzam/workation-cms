import { BadRequestException, Injectable, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../prisma.service';

@Injectable()
export class IslandsService {
  constructor(private readonly prisma: PrismaService) {}

  private parseOptionalMetadataField(value: unknown, field: string): string | null | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (value === null) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException(`${field} must be a string when provided`);
    }

    const normalized = value.trim();
    if (normalized.length === 0) {
      return null;
    }

    if (normalized.length > 500) {
      throw new BadRequestException(`${field} must be 500 characters or less`);
    }

    return normalized;
  }

  async listAtolls() {
    return this.prisma.atoll.findMany({
      orderBy: { name: 'asc' },
    });
  }

  async getAtoll(id: number) {
    const atoll = await this.prisma.atoll.findUnique({ where: { id } });
    if (!atoll) {
      throw new NotFoundException('Atoll not found');
    }

    return atoll;
  }

  async listIslands(filters: { atollId?: number; q?: string }) {
    return this.prisma.island.findMany({
      where: {
        atollId: filters.atollId,
        OR: filters.q
          ? [
              { name: { contains: filters.q, mode: 'insensitive' } },
              { slug: { contains: filters.q, mode: 'insensitive' } },
            ]
          : undefined,
      },
      include: {
        atoll: true,
      },
      orderBy: { name: 'asc' },
    });
  }

  async getIsland(id: number) {
    const island = await this.prisma.island.findUnique({
      where: { id },
      include: {
        atoll: true,
      },
    });

    if (!island) {
      throw new NotFoundException('Island not found');
    }

    return island;
  }

  async updateIslandMetadata(id: number, payload: Record<string, unknown>) {
    const island = await this.prisma.island.findUnique({ where: { id } });
    if (!island) {
      throw new NotFoundException('Island not found');
    }

    const facilitiesSummary = this.parseOptionalMetadataField(payload.facilitiesSummary, 'facilitiesSummary');
    const connectivitySummary = this.parseOptionalMetadataField(payload.connectivitySummary, 'connectivitySummary');
    const emergencyServicesInfo = this.parseOptionalMetadataField(payload.emergencyServicesInfo, 'emergencyServicesInfo');

    if (
      facilitiesSummary === undefined
      && connectivitySummary === undefined
      && emergencyServicesInfo === undefined
    ) {
      throw new BadRequestException('At least one metadata field is required');
    }

    return this.prisma.island.update({
      where: { id },
      data: {
        ...(facilitiesSummary !== undefined ? { facilitiesSummary } : {}),
        ...(connectivitySummary !== undefined ? { connectivitySummary } : {}),
        ...(emergencyServicesInfo !== undefined ? { emergencyServicesInfo } : {}),
      },
      include: {
        atoll: true,
      },
    });
  }
}

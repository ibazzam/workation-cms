import { BadRequestException, Injectable, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../prisma.service';

@Injectable()
export class IslandsService {
  constructor(private readonly prisma: PrismaService) {}

  private toRadians(value: number): number {
    return (value * Math.PI) / 180;
  }

  private haversineKm(lat1: number, lng1: number, lat2: number, lng2: number): number {
    const earthRadiusKm = 6371;
    const deltaLat = this.toRadians(lat2 - lat1);
    const deltaLng = this.toRadians(lng2 - lng1);
    const a = Math.sin(deltaLat / 2) ** 2
      + Math.cos(this.toRadians(lat1)) * Math.cos(this.toRadians(lat2)) * Math.sin(deltaLng / 2) ** 2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return earthRadiusKm * c;
  }

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

  async listIslands(filters: { atollId?: number; q?: string; nearLat?: number; nearLng?: number; radiusKm?: number; sort?: string }) {
    const hasNearbySearch = filters.nearLat !== undefined || filters.nearLng !== undefined || filters.radiusKm !== undefined;
    if (hasNearbySearch) {
      if (filters.nearLat === undefined || filters.nearLng === undefined) {
        throw new BadRequestException('nearLat and nearLng are required when using nearby search');
      }

      if (filters.radiusKm !== undefined && (filters.radiusKm <= 0 || filters.radiusKm > 500)) {
        throw new BadRequestException('radiusKm must be between 0 and 500');
      }
    }

    const islands = await this.prisma.island.findMany({
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

    if (!hasNearbySearch) {
      return islands;
    }

    const nearLat = filters.nearLat as number;
    const nearLng = filters.nearLng as number;
    const radiusKm = filters.radiusKm ?? 50;

    const withDistance = islands
      .map((island) => {
        if (island.lat === null || island.lng === null) {
          return { island, distanceKm: null as number | null };
        }

        return {
          island,
          distanceKm: Math.round(this.haversineKm(nearLat, nearLng, island.lat, island.lng) * 100) / 100,
        };
      })
      .filter((entry) => entry.distanceKm !== null && entry.distanceKm <= radiusKm);

    if (filters.sort === 'distance') {
      withDistance.sort((a, b) => (a.distanceKm as number) - (b.distanceKm as number));
    }

    return withDistance.map((entry) => ({
      ...entry.island,
      distanceKm: entry.distanceKm,
    }));
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

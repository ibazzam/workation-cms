import { Injectable, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../prisma.service';

@Injectable()
export class IslandsService {
  constructor(private readonly prisma: PrismaService) {}

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
}

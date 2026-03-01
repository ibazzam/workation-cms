import { BadRequestException, Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../prisma.service';

type CountryUpsertPayload = {
  code?: unknown;
  name?: unknown;
  active?: unknown;
};

@Injectable()
export class CountriesService {
  constructor(private readonly prisma: PrismaService) {}

  async list(filters: { q?: string; active?: boolean | undefined }) {
    return this.prisma.country.findMany({
      where: {
        ...(filters.active === undefined ? {} : { active: filters.active }),
        ...(filters.q
          ? {
              OR: [
                { name: { contains: filters.q, mode: 'insensitive' } },
                { code: { contains: filters.q, mode: 'insensitive' } },
              ],
            }
          : {}),
      },
      orderBy: { name: 'asc' },
    });
  }

  async getById(id: number) {
    const country = await this.prisma.country.findUnique({ where: { id } });
    if (!country) {
      throw new NotFoundException('Country not found');
    }

    return country;
  }

  async create(payload: CountryUpsertPayload) {
    const normalized = this.normalize(payload, { partial: false });

    return this.prisma.country.create({
      data: normalized as Prisma.CountryUncheckedCreateInput,
    });
  }

  async update(id: number, payload: CountryUpsertPayload) {
    await this.ensureCountryExists(id);
    const normalized = this.normalize(payload, { partial: true });

    return this.prisma.country.update({
      where: { id },
      data: normalized as Prisma.CountryUncheckedUpdateInput,
    });
  }

  async remove(id: number) {
    await this.ensureCountryExists(id);
    await this.prisma.country.delete({ where: { id } });
  }

  private async ensureCountryExists(id: number) {
    const exists = await this.prisma.country.findUnique({ where: { id }, select: { id: true } });
    if (!exists) {
      throw new NotFoundException('Country not found');
    }
  }

  private normalize(payload: CountryUpsertPayload, options: { partial: boolean }) {
    const code = this.parseOptionalCode(payload.code);
    const name = this.parseOptionalString(payload.name);
    const active = this.parseOptionalBoolean(payload.active);

    if (!options.partial && (!code || !name)) {
      throw new BadRequestException('code and name are required');
    }

    const data: Record<string, unknown> = {};
    if (code !== undefined) data.code = code;
    if (name !== undefined) data.name = name;
    if (active !== undefined) data.active = active;

    if (Object.keys(data).length === 0) {
      throw new BadRequestException('No updatable fields provided');
    }

    return data;
  }

  private parseOptionalString(value: unknown): string | undefined {
    if (value === undefined) return undefined;
    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException('Expected non-empty string value');
    }

    return value.trim();
  }

  private parseOptionalCode(value: unknown): string | undefined {
    if (value === undefined) return undefined;
    if (typeof value !== 'string' || value.trim().length < 2 || value.trim().length > 3) {
      throw new BadRequestException('country code must be 2-3 letters');
    }

    const normalized = value.trim().toUpperCase();
    if (!/^[A-Z]{2,3}$/.test(normalized)) {
      throw new BadRequestException('country code must contain only letters');
    }

    return normalized;
  }

  private parseOptionalBoolean(value: unknown): boolean | undefined {
    if (value === undefined) return undefined;
    if (typeof value !== 'boolean') {
      throw new BadRequestException('active must be boolean');
    }

    return value;
  }
}

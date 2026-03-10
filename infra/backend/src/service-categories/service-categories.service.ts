import { BadRequestException, Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../prisma.service';

type CategoryScope = 'ACCOMMODATION' | 'TRANSPORT' | 'BOTH' | 'ACTIVITY';
const ALLOWED_SCOPES: ReadonlyArray<CategoryScope> = ['ACCOMMODATION', 'TRANSPORT', 'BOTH', 'ACTIVITY'];

type CategoryUpsertPayload = {
  code?: unknown;
  name?: unknown;
  scope?: unknown;
  active?: unknown;
};

@Injectable()
export class ServiceCategoriesService {
  constructor(private readonly prisma: PrismaService) {}

  async list(filters: { q?: string; scope?: CategoryScope | undefined; active?: boolean | undefined }) {
    return this.prisma.serviceCategory.findMany({
      where: {
        ...(filters.scope ? { scope: filters.scope } : {}),
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
      orderBy: [{ scope: 'asc' }, { name: 'asc' }],
    });
  }

  async getById(id: number) {
    const category = await this.prisma.serviceCategory.findUnique({ where: { id } });
    if (!category) {
      throw new NotFoundException('Service category not found');
    }

    return category;
  }

  async create(payload: CategoryUpsertPayload) {
    const normalized = this.normalize(payload, { partial: false });

    return this.prisma.serviceCategory.create({
      data: normalized as Prisma.ServiceCategoryUncheckedCreateInput,
    });
  }

  async update(id: number, payload: CategoryUpsertPayload) {
    await this.ensureCategoryExists(id);
    const normalized = this.normalize(payload, { partial: true });

    return this.prisma.serviceCategory.update({
      where: { id },
      data: normalized as Prisma.ServiceCategoryUncheckedUpdateInput,
    });
  }

  async remove(id: number) {
    await this.ensureCategoryExists(id);
    await this.prisma.serviceCategory.delete({ where: { id } });
  }

  private async ensureCategoryExists(id: number) {
    const exists = await this.prisma.serviceCategory.findUnique({ where: { id }, select: { id: true } });
    if (!exists) {
      throw new NotFoundException('Service category not found');
    }
  }

  private normalize(payload: CategoryUpsertPayload, options: { partial: boolean }) {
    const code = this.parseOptionalCode(payload.code);
    const name = this.parseOptionalString(payload.name);
    const scope = this.parseOptionalScope(payload.scope);
    const active = this.parseOptionalBoolean(payload.active);

    if (!options.partial && (!code || !name || !scope)) {
      throw new BadRequestException('code, name, and scope are required');
    }

    const data: Record<string, unknown> = {};
    if (code !== undefined) data.code = code;
    if (name !== undefined) data.name = name;
    if (scope !== undefined) data.scope = scope;
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
    if (typeof value !== 'string' || value.trim().length < 2 || value.trim().length > 64) {
      throw new BadRequestException('category code must be 2-64 characters');
    }

    const normalized = value.trim().toUpperCase().replace(/\s+/g, '_');
    if (!/^[A-Z0-9_]+$/.test(normalized)) {
      throw new BadRequestException('category code must use letters, numbers, underscore');
    }

    return normalized;
  }

  private parseOptionalScope(value: unknown): CategoryScope | undefined {
    if (value === undefined) return undefined;
    const errorMessage = `scope must be one of: ${ALLOWED_SCOPES.join(', ')}`;

    if (typeof value !== 'string') {
      throw new BadRequestException(errorMessage);
    }

    const normalized = value.toUpperCase() as CategoryScope;
    if (ALLOWED_SCOPES.includes(normalized)) {
      return normalized;
    }

    throw new BadRequestException(errorMessage);
  }

  private parseOptionalBoolean(value: unknown): boolean | undefined {
    if (value === undefined) return undefined;
    if (typeof value !== 'boolean') {
      throw new BadRequestException('active must be boolean');
    }

    return value;
  }
}

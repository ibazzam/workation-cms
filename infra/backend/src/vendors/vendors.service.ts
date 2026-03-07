import { BadRequestException, ForbiddenException, Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../prisma.service';

type VendorUpsertPayload = {
  name?: unknown;
  email?: unknown;
  phone?: unknown;
};

type RequestActor = {
  id?: string;
  role?: string;
  vendorId?: string;
};

@Injectable()
export class VendorsService {
  constructor(private readonly prisma: PrismaService) {}

  async list(query?: { q?: string }) {
    return this.prisma.vendor.findMany({
      where: query?.q
        ? {
            OR: [
              { name: { contains: query.q, mode: 'insensitive' } },
              { email: { contains: query.q, mode: 'insensitive' } },
            ],
          }
        : undefined,
      orderBy: { name: 'asc' },
    });
  }

  async getById(id: string) {
    const vendor = await this.prisma.vendor.findUnique({ where: { id } });
    if (!vendor) {
      throw new NotFoundException('Vendor not found');
    }

    return vendor;
  }

  async create(payload: VendorUpsertPayload) {
    const normalized = this.normalizeVendorPayload(payload, { partial: false });

    return this.prisma.vendor.create({
      data: normalized as Prisma.VendorUncheckedCreateInput,
    });
  }

  async getOwnProfile(actor: RequestActor) {
    const vendorId = this.parseActorVendorId(actor.vendorId);
    return this.getById(vendorId);
  }

  async updateOwnProfile(actor: RequestActor, payload: VendorUpsertPayload) {
    const vendorId = this.parseActorVendorId(actor.vendorId);
    return this.update(vendorId, payload, actor);
  }

  async update(id: string, payload: VendorUpsertPayload, actor?: RequestActor) {
    this.assertVendorScopedAccess(id, actor);
    await this.ensureVendorExists(id);
    const normalized = this.normalizeVendorPayload(payload, { partial: true });

    return this.prisma.vendor.update({
      where: { id },
      data: normalized as Prisma.VendorUncheckedUpdateInput,
    });
  }

  async remove(id: string) {
    await this.ensureVendorExists(id);

    try {
      await this.prisma.vendor.delete({ where: { id } });
    } catch (error) {
      if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === 'P2003') {
        throw new BadRequestException('Vendor cannot be deleted while linked to services');
      }

      throw error;
    }
  }

  private async ensureVendorExists(id: string) {
    const vendor = await this.prisma.vendor.findUnique({ where: { id }, select: { id: true } });
    if (!vendor) {
      throw new NotFoundException('Vendor not found');
    }
  }

  private assertVendorScopedAccess(id: string, actor?: RequestActor) {
    if (actor?.role !== 'VENDOR') {
      return;
    }

    const vendorId = this.parseActorVendorId(actor.vendorId);
    if (vendorId !== id) {
      throw new ForbiddenException('Vendor users can only manage their own vendor profile');
    }
  }

  private parseActorVendorId(value: unknown): string {
    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new ForbiddenException('Vendor scope is missing for authenticated vendor user');
    }

    return value.trim();
  }

  private normalizeVendorPayload(payload: VendorUpsertPayload, options: { partial: boolean }) {
    const name = this.parseOptionalString(payload.name);
    const email = this.parseOptionalNullableEmail(payload.email);
    const phone = this.parseOptionalNullableString(payload.phone);

    if (!options.partial && !name) {
      throw new BadRequestException('name is required');
    }

    const data: Record<string, unknown> = {};
    if (name !== undefined) data.name = name;
    if (email !== undefined) data.email = email;
    if (phone !== undefined) data.phone = phone;

    if (Object.keys(data).length === 0) {
      throw new BadRequestException('No updatable fields provided');
    }

    return data;
  }

  private parseOptionalString(value: unknown): string | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException('Expected non-empty string value');
    }

    return value.trim();
  }

  private parseOptionalNullableString(value: unknown): string | null | undefined {
    if (value === undefined) {
      return undefined;
    }

    if (value === null) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('Expected string value');
    }

    return value.trim();
  }

  private parseOptionalNullableEmail(value: unknown): string | null | undefined {
    const parsed = this.parseOptionalNullableString(value);
    if (parsed === undefined || parsed === null || parsed === '') {
      return parsed;
    }

    const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(parsed);
    if (!isValid) {
      throw new BadRequestException('email must be a valid email address');
    }

    return parsed.toLowerCase();
  }
}

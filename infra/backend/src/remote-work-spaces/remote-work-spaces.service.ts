import { BadRequestException, ForbiddenException, Injectable, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../prisma.service';

type RemoteWorkspaceUpsertPayload = {
  vendorId?: unknown;
  islandId?: unknown;
  name?: unknown;
  description?: unknown;
  deskInventory?: unknown;
  privateBoothInventory?: unknown;
  dayPassPrice?: unknown;
  weeklyPassPrice?: unknown;
  currency?: unknown;
  minMbps?: unknown;
  connectivityQuality?: unknown;
  hasMeetingRooms?: unknown;
  active?: unknown;
};

type RemoteWorkspacePassWindowUpsertPayload = {
  startAt?: unknown;
  endAt?: unknown;
  deskInventoryOverride?: unknown;
  privateBoothInventoryOverride?: unknown;
  status?: unknown;
  note?: unknown;
};

type RequestActor = {
  role?: string;
  vendorId?: string;
};

@Injectable()
export class RemoteWorkSpacesService {
  constructor(private readonly prisma: PrismaService) {}

  async list(filters: {
    islandId?: number;
    vendorId?: string;
    q?: string;
    requiredMbps?: string;
    meetingRoomRequired?: string;
  }) {
    const q = typeof filters.q === 'string' && filters.q.trim().length > 0 ? filters.q.trim() : undefined;
    const requiredMbps = filters.requiredMbps === undefined
      ? undefined
      : this.parseNonNegativeInt(filters.requiredMbps, 'requiredMbps');
    const meetingRoomRequired = filters.meetingRoomRequired === undefined
      ? undefined
      : this.parseBoolean(filters.meetingRoomRequired, 'meetingRoomRequired');

    return this.prisma.remoteWorkspace.findMany({
      where: {
        active: true,
        islandId: filters.islandId,
        vendorId: filters.vendorId?.trim() || undefined,
        ...(requiredMbps !== undefined ? { minMbps: { gte: requiredMbps } } : {}),
        ...(meetingRoomRequired !== undefined ? { hasMeetingRooms: meetingRoomRequired } : {}),
        ...(q
          ? {
              OR: [
                { name: { contains: q, mode: 'insensitive' } },
                { description: { contains: q, mode: 'insensitive' } },
              ],
            }
          : {}),
      },
      include: {
        vendor: true,
        island: true,
        passWindows: {
          where: {
            status: 'OPEN',
            startAt: { gte: new Date() },
          },
          orderBy: { startAt: 'asc' },
          take: 3,
        },
      },
      orderBy: { createdAt: 'desc' },
    });
  }

  async getById(id: string) {
    const workspace = await this.prisma.remoteWorkspace.findUnique({
      where: { id },
      include: {
        vendor: true,
        island: true,
        passWindows: {
          orderBy: { startAt: 'asc' },
        },
      },
    });

    if (!workspace) {
      throw new NotFoundException('Remote workspace not found');
    }

    return workspace;
  }

  async listPassWindows(id: string, options: { from?: string; to?: string }) {
    await this.assertRemoteWorkspaceExists(id);
    const range = this.parseOptionalDateRange(options.from, options.to);

    return this.prisma.remoteWorkspacePassWindow.findMany({
      where: {
        remoteWorkspaceId: id,
        ...(range
          ? {
              startAt: { gte: range.from },
              endAt: { lte: range.to },
            }
          : {}),
      },
      orderBy: { startAt: 'asc' },
    });
  }

  async quote(
    id: string,
    payload: {
      windowId?: string;
      passesRequested?: string;
      passType?: string;
      deskType?: string;
      requiredMbps?: string;
      meetingRoomNeeded?: string;
    },
  ) {
    const workspace = await this.prisma.remoteWorkspace.findUnique({ where: { id } });
    if (!workspace) {
      throw new NotFoundException('Remote workspace not found');
    }

    if (typeof payload.windowId !== 'string' || payload.windowId.trim().length === 0) {
      throw new BadRequestException('windowId is required');
    }

    const windowId = payload.windowId.trim();
    const passesRequested = payload.passesRequested === undefined
      ? 1
      : this.parsePositiveInt(payload.passesRequested, 'passesRequested');
    const passType = this.parsePassType(payload.passType) ?? 'DAY';
    const deskType = this.parseDeskType(payload.deskType) ?? 'HOT_DESK';
    const requiredMbps = payload.requiredMbps === undefined
      ? null
      : this.parseNonNegativeInt(payload.requiredMbps, 'requiredMbps');
    const meetingRoomNeeded = payload.meetingRoomNeeded === undefined
      ? false
      : this.parseBoolean(payload.meetingRoomNeeded, 'meetingRoomNeeded');

    const window = await this.prisma.remoteWorkspacePassWindow.findUnique({ where: { id: windowId } });
    if (!window || window.remoteWorkspaceId !== id) {
      throw new NotFoundException('Pass window not found');
    }

    const effectiveDeskInventory = window.deskInventoryOverride ?? workspace.deskInventory;
    const effectivePrivateBoothInventory = window.privateBoothInventoryOverride ?? workspace.privateBoothInventory;
    const effectiveInventory = deskType === 'PRIVATE_BOOTH' ? effectivePrivateBoothInventory : effectiveDeskInventory;

    const checks = {
      workspaceActive: workspace.active,
      windowOpen: window.status === 'OPEN',
      windowInFuture: window.startAt.getTime() > Date.now(),
      inventoryRule: passesRequested <= effectiveInventory,
      connectivityRule: requiredMbps === null || workspace.minMbps >= requiredMbps,
      meetingRoomRule: !meetingRoomNeeded || workspace.hasMeetingRooms,
    };

    const canBook = Object.values(checks).every((value) => value === true);
    const unitPrice = passType === 'WEEKLY' ? workspace.weeklyPassPrice : workspace.dayPassPrice;
    const totalAmount = Number(unitPrice) * passesRequested;

    return {
      remoteWorkspaceId: workspace.id,
      windowId: window.id,
      passesRequested,
      passType,
      deskType,
      requiredMbps,
      meetingRoomNeeded,
      checks,
      canBook,
      pricing: {
        currency: workspace.currency,
        unitAmount: Number(Number(unitPrice).toFixed(2)),
        totalAmount: Number(totalAmount.toFixed(2)),
      },
      connectivity: {
        minMbps: workspace.minMbps,
        connectivityQuality: workspace.connectivityQuality,
      },
      inventory: {
        effectiveDeskInventory,
        effectivePrivateBoothInventory,
        effectiveInventory,
      },
      window: {
        startAt: window.startAt.toISOString(),
        endAt: window.endAt.toISOString(),
        status: window.status,
        note: window.note,
      },
    };
  }

  async create(payload: RemoteWorkspaceUpsertPayload, actor?: RequestActor) {
    const normalized = this.normalizeRemoteWorkspacePayload(payload, { partial: false, actor });

    return this.prisma.remoteWorkspace.create({
      data: normalized as any,
      include: {
        vendor: true,
        island: true,
      },
    });
  }

  async update(id: string, payload: RemoteWorkspaceUpsertPayload, actor?: RequestActor) {
    const existing = await this.prisma.remoteWorkspace.findUnique({ where: { id } });
    if (!existing) {
      throw new NotFoundException('Remote workspace not found');
    }

    this.assertVendorScopedAccess(existing.vendorId, actor);

    const normalized = this.normalizeRemoteWorkspacePayload(payload, {
      partial: true,
      actor,
      existingVendorId: existing.vendorId,
    });

    return this.prisma.remoteWorkspace.update({
      where: { id },
      data: normalized as any,
      include: {
        vendor: true,
        island: true,
      },
    });
  }

  async remove(id: string, actor?: RequestActor) {
    const existing = await this.prisma.remoteWorkspace.findUnique({ where: { id } });
    if (!existing) {
      throw new NotFoundException('Remote workspace not found');
    }

    this.assertVendorScopedAccess(existing.vendorId, actor);
    await this.prisma.remoteWorkspace.delete({ where: { id } });
  }

  async createPassWindow(id: string, payload: RemoteWorkspacePassWindowUpsertPayload, actor?: RequestActor) {
    const workspace = await this.prisma.remoteWorkspace.findUnique({ where: { id } });
    if (!workspace) {
      throw new NotFoundException('Remote workspace not found');
    }

    this.assertVendorScopedAccess(workspace.vendorId, actor);

    const normalized = this.normalizePassWindowPayload(payload, { partial: false });

    return this.prisma.remoteWorkspacePassWindow.create({
      data: {
        remoteWorkspaceId: workspace.id,
        ...normalized,
      } as any,
    });
  }

  async updatePassWindow(
    id: string,
    windowId: string,
    payload: RemoteWorkspacePassWindowUpsertPayload,
    actor?: RequestActor,
  ) {
    const workspace = await this.prisma.remoteWorkspace.findUnique({ where: { id } });
    if (!workspace) {
      throw new NotFoundException('Remote workspace not found');
    }

    this.assertVendorScopedAccess(workspace.vendorId, actor);

    const window = await this.prisma.remoteWorkspacePassWindow.findUnique({ where: { id: windowId } });
    if (!window || window.remoteWorkspaceId !== id) {
      throw new NotFoundException('Pass window not found');
    }

    const normalized = this.normalizePassWindowPayload(payload, { partial: true });

    return this.prisma.remoteWorkspacePassWindow.update({
      where: { id: windowId },
      data: normalized as any,
    });
  }

  async removePassWindow(id: string, windowId: string, actor?: RequestActor) {
    const workspace = await this.prisma.remoteWorkspace.findUnique({ where: { id } });
    if (!workspace) {
      throw new NotFoundException('Remote workspace not found');
    }

    this.assertVendorScopedAccess(workspace.vendorId, actor);

    const window = await this.prisma.remoteWorkspacePassWindow.findUnique({ where: { id: windowId } });
    if (!window || window.remoteWorkspaceId !== id) {
      throw new NotFoundException('Pass window not found');
    }

    await this.prisma.remoteWorkspacePassWindow.delete({ where: { id: windowId } });
  }

  private normalizeRemoteWorkspacePayload(
    payload: RemoteWorkspaceUpsertPayload,
    options: { partial: boolean; actor?: RequestActor; existingVendorId?: string },
  ) {
    const vendorId = this.resolveVendorId(payload.vendorId, options.actor, options.existingVendorId, options.partial);
    const islandId = this.parseOptionalInt(payload.islandId, 'islandId');
    const name = this.parseOptionalString(payload.name, 'name', 180);
    const description = this.parseOptionalNullableText(payload.description, 5000);
    const deskInventory = this.parseOptionalNonNegativeInt(payload.deskInventory, 'deskInventory');
    const privateBoothInventory = this.parseOptionalNonNegativeInt(payload.privateBoothInventory, 'privateBoothInventory');
    const dayPassPrice = this.parseOptionalDecimal(payload.dayPassPrice, 'dayPassPrice');
    const weeklyPassPrice = this.parseOptionalDecimal(payload.weeklyPassPrice, 'weeklyPassPrice');
    const currency = this.parseOptionalCurrency(payload.currency);
    const minMbps = this.parseOptionalNonNegativeInt(payload.minMbps, 'minMbps');
    const connectivityQuality = this.parseOptionalConnectivityQuality(payload.connectivityQuality);
    const hasMeetingRooms = this.parseOptionalBoolean(payload.hasMeetingRooms, 'hasMeetingRooms');
    const active = this.parseOptionalBoolean(payload.active, 'active');

    const normalized = {
      ...(vendorId !== undefined ? { vendorId } : {}),
      ...(islandId !== undefined ? { islandId } : {}),
      ...(name !== undefined ? { name } : {}),
      ...(description !== undefined ? { description } : {}),
      ...(deskInventory !== undefined ? { deskInventory } : {}),
      ...(privateBoothInventory !== undefined ? { privateBoothInventory } : {}),
      ...(dayPassPrice !== undefined ? { dayPassPrice } : {}),
      ...(weeklyPassPrice !== undefined ? { weeklyPassPrice } : {}),
      ...(currency !== undefined ? { currency } : {}),
      ...(minMbps !== undefined ? { minMbps } : {}),
      ...(connectivityQuality !== undefined ? { connectivityQuality } : {}),
      ...(hasMeetingRooms !== undefined ? { hasMeetingRooms } : {}),
      ...(active !== undefined ? { active } : {}),
    };

    if (!options.partial) {
      this.assertRequired(normalized.vendorId, 'vendorId');
      this.assertRequired(normalized.islandId, 'islandId');
      this.assertRequired(normalized.name, 'name');
      this.assertRequired(normalized.deskInventory, 'deskInventory');
      this.assertRequired(normalized.privateBoothInventory, 'privateBoothInventory');
      this.assertRequired(normalized.dayPassPrice, 'dayPassPrice');
      this.assertRequired(normalized.weeklyPassPrice, 'weeklyPassPrice');
      this.assertRequired(normalized.currency, 'currency');
      this.assertRequired(normalized.minMbps, 'minMbps');
      this.assertRequired(normalized.connectivityQuality, 'connectivityQuality');
      this.assertRequired(normalized.hasMeetingRooms, 'hasMeetingRooms');
      this.assertRequired(normalized.active, 'active');
    }

    return normalized;
  }

  private normalizePassWindowPayload(
    payload: RemoteWorkspacePassWindowUpsertPayload,
    options: { partial: boolean },
  ) {
    const startAt = this.parseOptionalDate(payload.startAt, 'startAt');
    const endAt = this.parseOptionalDate(payload.endAt, 'endAt');
    const deskInventoryOverride = this.parseOptionalNonNegativeInt(
      payload.deskInventoryOverride,
      'deskInventoryOverride',
    );
    const privateBoothInventoryOverride = this.parseOptionalNonNegativeInt(
      payload.privateBoothInventoryOverride,
      'privateBoothInventoryOverride',
    );
    const status = this.parseOptionalStatus(payload.status);
    const note = this.parseOptionalNullableText(payload.note, 1000);

    if (startAt && endAt && startAt >= endAt) {
      throw new BadRequestException('startAt must be before endAt');
    }

    const normalized = {
      ...(startAt !== undefined ? { startAt } : {}),
      ...(endAt !== undefined ? { endAt } : {}),
      ...(deskInventoryOverride !== undefined ? { deskInventoryOverride } : {}),
      ...(privateBoothInventoryOverride !== undefined ? { privateBoothInventoryOverride } : {}),
      ...(status !== undefined ? { status } : {}),
      ...(note !== undefined ? { note } : {}),
    };

    if (!options.partial) {
      this.assertRequired(normalized.startAt, 'startAt');
      this.assertRequired(normalized.endAt, 'endAt');
      this.assertRequired(normalized.status, 'status');
    }

    return normalized;
  }

  private async assertRemoteWorkspaceExists(id: string) {
    const exists = await this.prisma.remoteWorkspace.findUnique({
      where: { id },
      select: { id: true },
    });

    if (!exists) {
      throw new NotFoundException('Remote workspace not found');
    }
  }

  private assertVendorScopedAccess(vendorId: string, actor?: RequestActor) {
    if (!actor || !actor.role) {
      return;
    }

    if (actor.role !== 'VENDOR') {
      return;
    }

    if (!actor.vendorId || actor.vendorId !== vendorId) {
      throw new ForbiddenException('Vendor scope violation');
    }
  }

  private resolveVendorId(
    value: unknown,
    actor: RequestActor | undefined,
    existingVendorId: string | undefined,
    partial: boolean,
  ) {
    if (actor?.role === 'VENDOR') {
      if (!actor.vendorId) {
        throw new ForbiddenException('Vendor account is not linked to a vendor profile');
      }

      if (value !== undefined) {
        const requestedVendorId = this.parseString(value, 'vendorId');
        if (requestedVendorId !== actor.vendorId) {
          throw new ForbiddenException('Vendors can only manage their own records');
        }
      }

      return actor.vendorId;
    }

    if (value === undefined) {
      return partial ? existingVendorId : undefined;
    }

    return this.parseString(value, 'vendorId');
  }

  private parseOptionalDateRange(from?: string, to?: string) {
    if (!from && !to) {
      return null;
    }

    const parsedFrom = from ? this.parseDate(from, 'from') : new Date(0);
    const parsedTo = to ? this.parseDate(to, 'to') : new Date('9999-12-31T23:59:59.999Z');

    if (parsedFrom >= parsedTo) {
      throw new BadRequestException('from must be before to');
    }

    return { from: parsedFrom, to: parsedTo };
  }

  private parseOptionalDate(value: unknown, fieldName: string) {
    if (value === undefined) {
      return undefined;
    }

    return this.parseDate(value, fieldName);
  }

  private parseDate(value: unknown, fieldName: string) {
    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException(`${fieldName} must be a valid ISO date string`);
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
      throw new BadRequestException(`${fieldName} must be a valid ISO date string`);
    }

    return parsed;
  }

  private parseOptionalInt(value: unknown, fieldName: string) {
    if (value === undefined) {
      return undefined;
    }

    return this.parseInt(value, fieldName);
  }

  private parseOptionalNonNegativeInt(value: unknown, fieldName: string) {
    if (value === undefined) {
      return undefined;
    }

    return this.parseNonNegativeInt(value, fieldName);
  }

  private parsePositiveInt(value: unknown, fieldName: string) {
    const parsed = this.parseInt(value, fieldName);
    if (parsed <= 0) {
      throw new BadRequestException(`${fieldName} must be greater than 0`);
    }
    return parsed;
  }

  private parseNonNegativeInt(value: unknown, fieldName: string) {
    const parsed = this.parseInt(value, fieldName);
    if (parsed < 0) {
      throw new BadRequestException(`${fieldName} must be greater than or equal to 0`);
    }
    return parsed;
  }

  private parseInt(value: unknown, fieldName: string) {
    if ((typeof value !== 'string' && typeof value !== 'number') || `${value}`.trim().length === 0) {
      throw new BadRequestException(`${fieldName} must be an integer`);
    }

    const parsed = Number.parseInt(`${value}`, 10);
    if (!Number.isFinite(parsed) || Number.isNaN(parsed)) {
      throw new BadRequestException(`${fieldName} must be an integer`);
    }

    return parsed;
  }

  private parseOptionalDecimal(value: unknown, fieldName: string) {
    if (value === undefined) {
      return undefined;
    }

    if ((typeof value !== 'string' && typeof value !== 'number') || `${value}`.trim().length === 0) {
      throw new BadRequestException(`${fieldName} must be a valid decimal`);
    }

    const parsed = Number(`${value}`);
    if (!Number.isFinite(parsed) || Number.isNaN(parsed) || parsed < 0) {
      throw new BadRequestException(`${fieldName} must be a non-negative decimal`);
    }

    return parsed.toFixed(2);
  }

  private parseOptionalString(value: unknown, fieldName: string, maxLength: number) {
    if (value === undefined) {
      return undefined;
    }

    const parsed = this.parseString(value, fieldName);
    if (parsed.length > maxLength) {
      throw new BadRequestException(`${fieldName} must be at most ${maxLength} characters`);
    }

    return parsed;
  }

  private parseOptionalNullableText(value: unknown, maxLength: number) {
    if (value === undefined) {
      return undefined;
    }

    if (value === null) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('description must be a string or null');
    }

    const parsed = value.trim();
    if (parsed.length > maxLength) {
      throw new BadRequestException(`description must be at most ${maxLength} characters`);
    }

    return parsed.length === 0 ? null : parsed;
  }

  private parseOptionalCurrency(value: unknown) {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('currency must be a 3-letter code');
    }

    const parsed = value.trim().toUpperCase();
    if (!/^[A-Z]{3}$/.test(parsed)) {
      throw new BadRequestException('currency must be a 3-letter code');
    }

    return parsed;
  }

  private parseOptionalConnectivityQuality(value: unknown) {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('connectivityQuality must be BASIC, GOOD, or PREMIUM');
    }

    const parsed = value.trim().toUpperCase();
    if (!['BASIC', 'GOOD', 'PREMIUM'].includes(parsed)) {
      throw new BadRequestException('connectivityQuality must be BASIC, GOOD, or PREMIUM');
    }

    return parsed;
  }

  private parseOptionalStatus(value: unknown) {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('status must be OPEN, CLOSED, or SOLD_OUT');
    }

    const parsed = value.trim().toUpperCase();
    if (!['OPEN', 'CLOSED', 'SOLD_OUT'].includes(parsed)) {
      throw new BadRequestException('status must be OPEN, CLOSED, or SOLD_OUT');
    }

    return parsed;
  }

  private parsePassType(value: unknown) {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('passType must be DAY or WEEKLY');
    }

    const parsed = value.trim().toUpperCase();
    if (!['DAY', 'WEEKLY'].includes(parsed)) {
      throw new BadRequestException('passType must be DAY or WEEKLY');
    }

    return parsed;
  }

  private parseDeskType(value: unknown) {
    if (value === undefined) {
      return undefined;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('deskType must be HOT_DESK or PRIVATE_BOOTH');
    }

    const parsed = value.trim().toUpperCase();
    if (!['HOT_DESK', 'PRIVATE_BOOTH'].includes(parsed)) {
      throw new BadRequestException('deskType must be HOT_DESK or PRIVATE_BOOTH');
    }

    return parsed;
  }

  private parseOptionalBoolean(value: unknown, fieldName: string) {
    if (value === undefined) {
      return undefined;
    }

    return this.parseBoolean(value, fieldName);
  }

  private parseBoolean(value: unknown, fieldName: string) {
    if (typeof value === 'boolean') {
      return value;
    }

    if (typeof value === 'string') {
      const normalized = value.trim().toLowerCase();
      if (normalized === 'true') {
        return true;
      }
      if (normalized === 'false') {
        return false;
      }
    }

    throw new BadRequestException(`${fieldName} must be true or false`);
  }

  private parseString(value: unknown, fieldName: string) {
    if (typeof value !== 'string') {
      throw new BadRequestException(`${fieldName} must be a string`);
    }

    const parsed = value.trim();
    if (parsed.length === 0) {
      throw new BadRequestException(`${fieldName} cannot be empty`);
    }

    return parsed;
  }

  private assertRequired<T>(value: T | undefined, fieldName: string): asserts value is T {
    if (value === undefined) {
      throw new BadRequestException(`${fieldName} is required`);
    }
  }
}

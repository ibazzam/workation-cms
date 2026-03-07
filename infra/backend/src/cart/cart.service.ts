import { BadRequestException, Injectable } from '@nestjs/common';
import { BookingsService } from '../bookings/bookings.service';
import { PrismaService } from '../prisma.service';

type CartItemServiceType = 'TRANSPORT' | 'ACCOMMODATION';

type CartItem = {
  id: string;
  serviceType: CartItemServiceType;
  transportId?: string;
  transportFareClassCode?: string;
  itineraryTransportIds?: string[];
  accommodationId?: string;
  relatedTransportItemId?: string;
  startDate?: string;
  endDate?: string;
  guests?: number;
  createdAt: string;
};

type CartState = {
  items: CartItem[];
  updatedAt: string;
};

@Injectable()
export class CartService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly bookingsService: BookingsService,
  ) {}

  async getCartForUser(userId: string) {
    return this.readCart(userId);
  }

  async addItemForUser(userId: string, payload: Record<string, unknown>) {
    const normalized = this.normalizeItemPayload(payload);
    const cart = await this.readCart(userId);
    const item: CartItem = {
      id: this.newCartItemId(),
      ...normalized,
      createdAt: new Date().toISOString(),
    };

    const next: CartState = {
      items: [...cart.items, item],
      updatedAt: new Date().toISOString(),
    };

    await this.writeCart(userId, next);
    return next;
  }

  async removeItemForUser(userId: string, itemId: string) {
    if (!itemId || itemId.trim().length === 0) {
      throw new BadRequestException('itemId is required');
    }

    const cart = await this.readCart(userId);
    const nextItems = cart.items.filter((item) => item.id !== itemId.trim());
    const next: CartState = {
      items: nextItems,
      updatedAt: new Date().toISOString(),
    };

    await this.writeCart(userId, next);
    return next;
  }

  async checkoutForUser(userId: string, _payload: Record<string, unknown>, options: { clearCart: boolean }) {
    const cart = await this.readCart(userId);
    if (cart.items.length === 0) {
      throw new BadRequestException('Cart is empty');
    }

    const transportItems = cart.items.filter((item) => item.serviceType === 'TRANSPORT');
    const accommodationItems = cart.items.filter((item) => item.serviceType === 'ACCOMMODATION');
    this.validateCheckoutCoherence(transportItems, accommodationItems);

    const transportItemById = new Map(transportItems.map((item) => [item.id, item]));
    const primaryTransport = transportItems[0];

    const createdBookings: Array<Record<string, unknown>> = [];
    const createdBookingIds: string[] = [];

    try {
      for (const transportItem of transportItems) {
        if (!transportItem.transportId) {
          throw new BadRequestException(`Cart transport item ${transportItem.id} is missing transportId`);
        }

        const created = await this.bookingsService.createForUser(userId, {
          transportId: transportItem.transportId,
          transportFareClassCode: transportItem.transportFareClassCode,
          guests: transportItem.guests,
        });
        createdBookingIds.push(String(created.id));
        createdBookings.push({
          cartItemId: transportItem.id,
          bookingId: created.id,
          status: created.status,
          totalPrice: created.totalPrice,
        });
      }

      for (const accommodationItem of accommodationItems) {
        if (!accommodationItem.accommodationId) {
          throw new BadRequestException(`Cart accommodation item ${accommodationItem.id} is missing accommodationId`);
        }

        if (!accommodationItem.startDate || !accommodationItem.endDate) {
          throw new BadRequestException(`Cart accommodation item ${accommodationItem.id} requires startDate and endDate`);
        }

        const matchedTransport = accommodationItem.relatedTransportItemId
          ? transportItemById.get(accommodationItem.relatedTransportItemId)
          : primaryTransport;

        if (!matchedTransport?.transportId) {
          throw new BadRequestException(`Cart accommodation item ${accommodationItem.id} requires a related transport item`);
        }

        const created = await this.bookingsService.createForUser(userId, {
          accommodationId: accommodationItem.accommodationId,
          transportId: matchedTransport.transportId,
          itineraryTransportIds: matchedTransport.itineraryTransportIds,
          transportFareClassCode: matchedTransport.transportFareClassCode,
          startDate: accommodationItem.startDate,
          endDate: accommodationItem.endDate,
          guests: accommodationItem.guests,
        });
        createdBookingIds.push(String(created.id));

        createdBookings.push({
          cartItemId: accommodationItem.id,
          bookingId: created.id,
          status: created.status,
          totalPrice: created.totalPrice,
        });
      }
    } catch (error) {
      await this.rollbackPartialCheckout(userId, createdBookingIds);
      throw error;
    }

    if (options.clearCart) {
      await this.writeCart(userId, {
        items: [],
        updatedAt: new Date().toISOString(),
      });
    }

    const totalPrice = createdBookings.reduce((sum, item) => sum + Number(item.totalPrice ?? 0), 0);
    return {
      checkout: {
        createdCount: createdBookings.length,
        totalPrice,
        currency: 'USD',
      },
      bookings: createdBookings,
      cartCleared: options.clearCart,
    };
  }

  private normalizeItemPayload(payload: Record<string, unknown>) {
    const serviceType = this.parseServiceType(payload.serviceType);
    const guests = payload.guests === undefined ? 1 : Number(payload.guests);
    const itineraryTransportIds = this.parseOptionalTransportIdList(payload.itineraryTransportIds);

    if (!Number.isInteger(guests) || guests <= 0) {
      throw new BadRequestException('guests must be a positive integer');
    }

    if (serviceType === 'TRANSPORT') {
      const transportId = this.parseRequiredString(payload.transportId, 'transportId');
      const transportFareClassCode = this.parseOptionalString(payload.transportFareClassCode);
      return {
        serviceType,
        transportId,
        transportFareClassCode,
        itineraryTransportIds,
        guests,
      };
    }

    const accommodationId = this.parseRequiredString(payload.accommodationId, 'accommodationId');
    const startDate = this.parseRequiredDateString(payload.startDate, 'startDate');
    const endDate = this.parseRequiredDateString(payload.endDate, 'endDate');
    const relatedTransportItemId = this.parseOptionalString(payload.relatedTransportItemId);

    if (new Date(endDate).getTime() < new Date(startDate).getTime()) {
      throw new BadRequestException('endDate must be after or equal to startDate');
    }

    return {
      serviceType,
      accommodationId,
      startDate,
      endDate,
      relatedTransportItemId,
      guests,
    };
  }

  private async readCart(userId: string): Promise<CartState> {
    const key = this.cartConfigKey(userId);
    const row = await this.prisma.appConfig.findUnique({ where: { key } });
    if (!row) {
      return {
        items: [],
        updatedAt: new Date(0).toISOString(),
      };
    }

    const value = row.value as Record<string, unknown>;
    const items = Array.isArray(value.items) ? (value.items as CartItem[]) : [];
    const updatedAt = typeof value.updatedAt === 'string' ? value.updatedAt : row.updatedAt.toISOString();
    return {
      items,
      updatedAt,
    };
  }

  private async writeCart(userId: string, cart: CartState) {
    const key = this.cartConfigKey(userId);
    await this.prisma.appConfig.upsert({
      where: { key },
      update: {
        value: cart as unknown as object,
      },
      create: {
        key,
        value: cart as unknown as object,
      },
    });
  }

  private cartConfigKey(userId: string) {
    return `cart:user:${userId}`;
  }

  private newCartItemId() {
    return `cart-item-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
  }

  private parseServiceType(value: unknown): CartItemServiceType {
    if (typeof value !== 'string') {
      throw new BadRequestException('serviceType is required');
    }

    const normalized = value.toUpperCase();
    if (normalized === 'TRANSPORT' || normalized === 'ACCOMMODATION') {
      return normalized;
    }

    throw new BadRequestException('serviceType must be TRANSPORT or ACCOMMODATION');
  }

  private parseRequiredString(value: unknown, fieldName: string) {
    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException(`${fieldName} is required`);
    }

    return value.trim();
  }

  private parseOptionalString(value: unknown) {
    if (value === undefined || value === null) {
      return undefined;
    }

    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException('Expected non-empty string value');
    }

    return value.trim();
  }

  private parseRequiredDateString(value: unknown, fieldName: string) {
    if (typeof value !== 'string') {
      throw new BadRequestException(`${fieldName} is required`);
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
      throw new BadRequestException(`${fieldName} must be a valid date string`);
    }

    return value;
  }

  private parseOptionalTransportIdList(value: unknown): string[] | undefined {
    if (value === undefined || value === null) {
      return undefined;
    }

    if (!Array.isArray(value)) {
      throw new BadRequestException('itineraryTransportIds must be an array of transport ids');
    }

    return value.map((entry) => {
      if (typeof entry !== 'string' || entry.trim().length === 0) {
        throw new BadRequestException('itineraryTransportIds must contain non-empty string transport ids');
      }

      return entry.trim();
    });
  }

  private validateCheckoutCoherence(transportItems: CartItem[], accommodationItems: CartItem[]) {
    if (accommodationItems.length > 0 && transportItems.length === 0) {
      throw new BadRequestException('Accommodation cart items require at least one transport cart item');
    }

    const transportIds = new Set(transportItems.map((item) => item.id));
    const hasAmbiguousTransportSelection = transportItems.length > 1;

    for (const accommodationItem of accommodationItems) {
      if (!accommodationItem.relatedTransportItemId && hasAmbiguousTransportSelection) {
        throw new BadRequestException(
          `Cart accommodation item ${accommodationItem.id} must set relatedTransportItemId when multiple transport items exist`,
        );
      }

      if (accommodationItem.relatedTransportItemId && !transportIds.has(accommodationItem.relatedTransportItemId)) {
        throw new BadRequestException(
          `Cart accommodation item ${accommodationItem.id} references missing related transport item ${accommodationItem.relatedTransportItemId}`,
        );
      }
    }
  }

  private async rollbackPartialCheckout(userId: string, bookingIds: string[]) {
    if (bookingIds.length === 0) {
      return;
    }

    // Compensating rollback keeps checkout failures deterministic for users.
    await this.prisma.booking.updateMany({
      where: {
        id: { in: bookingIds },
        userId,
        status: { in: ['HOLD', 'PENDING'] },
      },
      data: {
        status: 'CANCELLED',
        holdExpiresAt: null,
      },
    });
  }
}

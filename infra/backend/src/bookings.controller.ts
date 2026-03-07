import { Body, Controller, Get, NotFoundException, Param, Post, UnprocessableEntityException } from '@nestjs/common';
import { PrismaService } from './prisma.service';

type ItineraryHoldPayload = {
  hold_ids: number[];
  total_price?: number;
};

type CheckoutConfirmPayload = {
  booking_id: number;
};

@Controller('api/v1')
export class BookingsController {
  constructor(private readonly prisma: PrismaService) {}

  @Post('bookings/itinerary-hold')
  async createItineraryHold(@Body() body: ItineraryHoldPayload) {
    if (!Array.isArray(body.hold_ids) || body.hold_ids.length === 0) {
      throw new UnprocessableEntityException('hold_ids must be a non-empty array');
    }

    for (const holdId of body.hold_ids) {
      const hold = await this.prisma.transportHold.findUnique({ where: { id: Number(holdId) } });
      if (!hold) {
        throw new UnprocessableEntityException(`Hold ${holdId} not found`);
      }

      if (hold.status !== 'held' && hold.status !== 'confirmed') {
        throw new UnprocessableEntityException(`Hold ${holdId} is not eligible for itinerary hold`);
      }
    }

    const booking = await this.prisma.itineraryBooking.create({
      data: {
        holdIds: body.hold_ids.map((id) => Number(id)),
        totalPrice: body.total_price ?? 0,
        status: 'draft',
      },
    });

    return {
      booking: this.mapBooking(booking),
      reliability_status: 'itinerary_holds_coherent',
    };
  }

  @Post('checkout/confirm')
  async confirmCheckout(@Body() body: CheckoutConfirmPayload) {
    if (!body.booking_id) {
      throw new UnprocessableEntityException('booking_id is required');
    }

    try {
      const booking = await this.prisma.itineraryBooking.findUnique({ where: { id: Number(body.booking_id) } });
      if (!booking) throw new Error('Booking not found');

      await this.prisma.$transaction(async (tx) => {
        for (const holdId of booking.holdIds) {
          const hold = await tx.transportHold.findUnique({ where: { id: holdId } });
          if (!hold) throw new Error(`Hold ${holdId} not found`);
          if (hold.status !== 'held' && hold.status !== 'confirmed') {
            throw new Error(`Hold ${holdId} not in confirmable state`);
          }

          if (hold.status === 'held') {
            await tx.transportHold.update({
              where: { id: hold.id },
              data: { status: 'confirmed' },
            });
          }
        }

        await tx.itineraryBooking.update({
          where: { id: booking.id },
          data: { status: 'confirmed' },
        });
      });

      const confirmed = await this.prisma.itineraryBooking.findUnique({ where: { id: Number(body.booking_id) } });

      return {
        booking: this.mapBooking(confirmed!),
        checkout_status: 'confirmed',
      };
    } catch (error) {
      const message = (error as Error).message;
      if (message === 'Booking not found') {
        throw new NotFoundException(message);
      }

      throw new UnprocessableEntityException(message);
    }
  }

  @Get('bookings/:id')
  async getBooking(@Param('id') id: string) {
    const booking = await this.prisma.itineraryBooking.findUnique({ where: { id: Number(id) } });
    if (!booking) {
      throw new NotFoundException('Booking not found');
    }

    return this.mapBooking(booking);
  }

  private mapBooking(booking: {
    id: number;
    holdIds: number[];
    status: string;
    totalPrice: any;
    createdAt: Date;
    updatedAt: Date;
  }) {
    return {
      id: booking.id,
      hold_ids: booking.holdIds,
      status: booking.status,
      total_price: Number(booking.totalPrice?.toString ? booking.totalPrice.toString() : booking.totalPrice),
      created_at: booking.createdAt.toISOString(),
      updated_at: booking.updatedAt.toISOString(),
    };
  }
}

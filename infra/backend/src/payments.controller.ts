import { Body, Controller, Get, NotFoundException, Post, Query, UnprocessableEntityException } from '@nestjs/common';
import { PrismaService } from './prisma.service';

type CreateRefundPayload = {
  booking_id: number;
  amount: number;
  reason?: string;
};

type CreateDisputePayload = {
  booking_id: number;
  reason: string;
};

@Controller('api/v1/payments')
export class PaymentsController {
  constructor(private readonly prisma: PrismaService) {}

  @Post('refunds')
  async createRefund(@Body() body: CreateRefundPayload) {
    if (!body.booking_id || !body.amount) {
      throw new UnprocessableEntityException('booking_id and amount are required');
    }

    try {
      const booking = await this.prisma.itineraryBooking.findUnique({ where: { id: Number(body.booking_id) } });
      if (!booking) throw new Error('Booking not found');

      const refund = await this.prisma.paymentRefund.create({
        data: {
          bookingId: Number(body.booking_id),
          amount: Number(body.amount),
          reason: body.reason,
          status: 'processed',
        },
      });

      return {
        refund: {
          id: refund.id,
          booking_id: refund.bookingId,
          amount: Number(refund.amount),
          reason: refund.reason,
          status: refund.status,
          created_at: refund.createdAt.toISOString(),
        },
        reliability_status: 'refund_processed',
      };
    } catch (error) {
      if ((error as Error).message === 'Booking not found') {
        throw new NotFoundException('Booking not found');
      }

      throw new UnprocessableEntityException((error as Error).message);
    }
  }

  @Post('disputes')
  async createDispute(@Body() body: CreateDisputePayload) {
    if (!body.booking_id || !body.reason) {
      throw new UnprocessableEntityException('booking_id and reason are required');
    }

    try {
      const booking = await this.prisma.itineraryBooking.findUnique({ where: { id: Number(body.booking_id) } });
      if (!booking) throw new Error('Booking not found');

      const dispute = await this.prisma.paymentDispute.create({
        data: {
          bookingId: Number(body.booking_id),
          reason: body.reason,
          status: 'opened',
        },
      });

      return {
        dispute: {
          id: dispute.id,
          booking_id: dispute.bookingId,
          reason: dispute.reason,
          status: dispute.status,
          created_at: dispute.createdAt.toISOString(),
        },
        reliability_status: 'dispute_opened',
      };
    } catch (error) {
      if ((error as Error).message === 'Booking not found') {
        throw new NotFoundException('Booking not found');
      }

      throw new UnprocessableEntityException((error as Error).message);
    }
  }

  @Get('settlements/report')
  async settlementReport(@Query('from') _from?: string, @Query('to') _to?: string) {
    const totalBookingsConfirmed = await this.prisma.itineraryBooking.count({ where: { status: 'confirmed' } });
    const refundsAggregate = await this.prisma.paymentRefund.aggregate({ _sum: { amount: true } });
    const openDisputes = await this.prisma.paymentDispute.count({ where: { status: { not: 'resolved' } } });

    return {
      settlement: {
        total_bookings_confirmed: totalBookingsConfirmed,
        total_refunds_amount: Number(refundsAggregate._sum.amount ?? 0),
        open_disputes: openDisputes,
        generated_at: new Date().toISOString(),
      },
      format: 'summary',
    };
  }
}

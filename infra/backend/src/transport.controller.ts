import { Body, Controller, Get, HttpCode, NotFoundException, Param, Post, Query, UnprocessableEntityException } from '@nestjs/common';
import { PrismaService } from './prisma.service';
import { mapHold } from './response-mappers';

type CreateHoldPayload = {
  schedule_id: number;
  seat_class?: string;
  seats: number;
  idempotency_key?: string;
  ttl_seconds?: number;
};

type CreateDisruptionPayload = {
  schedule_id: number;
  type: string;
  severity: string;
  reason?: string;
};

@Controller()
export class TransportController {
  constructor(private readonly prisma: PrismaService) {}

  @Get('api/v1/transport/schedules')
  async listSchedules(@Query('origin') origin?: string, @Query('dest') dest?: string) {
    const originId = origin ? Number(origin) : undefined;
    const destId = dest ? Number(dest) : undefined;

    const schedules = await this.prisma.transportSchedule.findMany({
      where: {
        originIslandId: originId,
        destinationIslandId: destId,
      },
      include: {
        inventories: true,
      },
      orderBy: { departureAt: 'asc' },
    });

    return {
      data: schedules.map((schedule) => {
        const firstInventory = schedule.inventories[0];
        return {
          id: schedule.id,
          origin_island_id: schedule.originIslandId,
          destination_island_id: schedule.destinationIslandId,
          operator: schedule.operator,
          departure_at: schedule.departureAt.toISOString(),
          arrival_at: schedule.arrivalAt.toISOString(),
          seat_class: firstInventory?.seatClass ?? 'standard',
          total_seats: firstInventory?.totalSeats ?? 0,
          reserved_seats: firstInventory?.reservedSeats ?? 0,
        };
      }),
    };
  }

  @Get('api/v1/transport/schedules/:id/inventory')
  async getInventory(@Param('id') id: string) {
    const inventory = await this.prisma.transportInventory.findFirst({
      where: { scheduleId: Number(id) },
      orderBy: { id: 'asc' },
    });
    if (!inventory) throw new NotFoundException('Schedule not found');

    return {
      schedule_id: inventory.scheduleId,
      seat_class: inventory.seatClass,
      total_seats: inventory.totalSeats,
      reserved_seats: inventory.reservedSeats,
      available_seats: Math.max(0, inventory.totalSeats - inventory.reservedSeats),
    };
  }

  @Post('api/v1/transport/holds')
  async createHoldV1(@Body() body: CreateHoldPayload) {
    return this.createHoldInternal(body);
  }

  // Legacy parity path to support WB-201 transition.
  @Post('api/transport/holds')
  async createHoldLegacy(@Body() body: CreateHoldPayload) {
    return this.createHoldInternal(body);
  }

  @Post('api/v1/transport/holds/:holdId/confirm')
  @HttpCode(200)
  async confirmHoldV1(@Param('holdId') holdId: string) {
    return this.confirmHoldInternal(holdId);
  }

  @Post('api/transport/holds/:holdId/confirm')
  @HttpCode(200)
  async confirmHoldLegacy(@Param('holdId') holdId: string) {
    return this.confirmHoldInternal(holdId);
  }

  @Post('api/v1/transport/holds/:holdId/release')
  @HttpCode(200)
  async releaseHoldV1(@Param('holdId') holdId: string) {
    return this.releaseHoldInternal(holdId);
  }

  @Post('api/transport/holds/:holdId/release')
  @HttpCode(200)
  async releaseHoldLegacy(@Param('holdId') holdId: string) {
    return this.releaseHoldInternal(holdId);
  }

  @Post('api/v1/transport/disruptions')
  @HttpCode(201)
  async createDisruption(@Body() body: CreateDisruptionPayload) {
    if (!body.schedule_id || !body.type || !body.severity) {
      throw new UnprocessableEntityException('schedule_id, type, severity are required');
    }

    try {
      const schedule = await this.prisma.transportSchedule.findUnique({ where: { id: Number(body.schedule_id) } });
      if (!schedule) {
        throw new Error('Schedule not found');
      }

      const disruption = await this.prisma.transportDisruption.create({
        data: {
          scheduleId: Number(body.schedule_id),
          type: body.type,
          severity: body.severity,
          reason: body.reason,
        },
      });

      return {
        disruption: {
          id: disruption.id,
          schedule_id: disruption.scheduleId,
          type: disruption.type,
          severity: disruption.severity,
          reason: disruption.reason,
          created_at: disruption.createdAt.toISOString(),
        },
        rebooking_status: 'queued_for_reaccommodation',
      };
    } catch (error) {
      throw new UnprocessableEntityException((error as Error).message);
    }
  }

  private async createHoldInternal(body: CreateHoldPayload) {
    if (!body.schedule_id || !body.seats || Number(body.seats) < 1) {
      throw new UnprocessableEntityException('schedule_id and seats >= 1 are required');
    }

    try {
      const seatClass = body.seat_class ?? 'standard';
      const ttlSeconds = body.ttl_seconds ?? 900;

      const hold = await this.prisma.$transaction(async (tx) => {
        if (body.idempotency_key) {
          const existing = await tx.transportHold.findUnique({ where: { idempotencyKey: body.idempotency_key } });
          if (existing) {
            return existing;
          }
        }

        const inventory = await tx.transportInventory.findUnique({
          where: {
            scheduleId_seatClass: {
              scheduleId: Number(body.schedule_id),
              seatClass,
            },
          },
        });

        if (!inventory) {
          throw new Error('Inventory not found');
        }

        const available = Math.max(0, inventory.totalSeats - inventory.reservedSeats);
        if (available < Number(body.seats)) {
          throw new Error('Not enough seats available');
        }

        await tx.transportInventory.update({
          where: {
            scheduleId_seatClass: {
              scheduleId: Number(body.schedule_id),
              seatClass,
            },
          },
          data: {
            reservedSeats: inventory.reservedSeats + Number(body.seats),
          },
        });

        return tx.transportHold.create({
          data: {
            scheduleId: Number(body.schedule_id),
            seatClass,
            seatsReserved: Number(body.seats),
            status: 'held',
            ttlExpiresAt: new Date(Date.now() + ttlSeconds * 1000),
            idempotencyKey: body.idempotency_key,
          },
        });
      });

      return { hold: mapHold(hold) };
    } catch (error) {
      throw new UnprocessableEntityException((error as Error).message);
    }
  }

  private async confirmHoldInternal(holdId: string) {
    try {
      const existing = await this.prisma.transportHold.findUnique({ where: { id: Number(holdId) } });
      if (!existing) {
        throw new Error('Hold not found');
      }

      const hold = await this.prisma.transportHold.update({
        where: { id: Number(holdId) },
        data: { status: 'confirmed' },
      });

      return { hold: mapHold(hold) };
    } catch (error) {
      if ((error as Error).message === 'Hold not found') {
        throw new NotFoundException('Hold not found');
      }
      throw new UnprocessableEntityException((error as Error).message);
    }
  }

  private async releaseHoldInternal(holdId: string) {
    try {
      const hold = await this.prisma.transportHold.findUnique({ where: { id: Number(holdId) } });
      if (!hold) {
        throw new Error('Hold not found');
      }

      const released = await this.prisma.$transaction(async (tx) => {
        const inventory = await tx.transportInventory.findUnique({
          where: {
            scheduleId_seatClass: {
              scheduleId: hold.scheduleId,
              seatClass: hold.seatClass,
            },
          },
        });

        if (inventory) {
          await tx.transportInventory.update({
            where: {
              scheduleId_seatClass: {
                scheduleId: hold.scheduleId,
                seatClass: hold.seatClass,
              },
            },
            data: {
              reservedSeats: Math.max(0, inventory.reservedSeats - hold.seatsReserved),
            },
          });
        }

        return tx.transportHold.update({
          where: { id: hold.id },
          data: { status: 'released' },
        });
      });

      return { hold: mapHold(released) };
    } catch (error) {
      if ((error as Error).message === 'Hold not found') {
        throw new NotFoundException('Hold not found');
      }
      throw new UnprocessableEntityException((error as Error).message);
    }
  }
}

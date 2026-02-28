import { BadRequestException, Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../prisma.service';

type WorkationPayload = {
  title?: unknown;
  description?: unknown;
  location?: unknown;
  start_date?: unknown;
  end_date?: unknown;
  price?: unknown;
};

@Injectable()
export class WorkationsService {
  constructor(private readonly prisma: PrismaService) {}

  async findAll() {
    return this.prisma.workations.findMany({
      orderBy: { createdAt: 'desc' },
    });
  }

  async findOne(id: number) {
    const record = await this.prisma.workations.findUnique({ where: { id } });
    if (!record) {
      throw new NotFoundException('Workation not found');
    }

    return record;
  }

  async create(payload: WorkationPayload) {
    const validated = this.validateCreatePayload(payload);
    return this.prisma.workations.create({ data: validated });
  }

  async update(id: number, payload: WorkationPayload) {
    await this.findOne(id);
    const validated = this.validateUpdatePayload(payload);
    return this.prisma.workations.update({ where: { id }, data: validated });
  }

  async remove(id: number) {
    await this.findOne(id);
    await this.prisma.workations.delete({ where: { id } });
  }

  private validateCreatePayload(payload: WorkationPayload): Prisma.WorkationsCreateInput {
    const validated = this.validatePayload(payload, false);

    return {
      title: validated.title as string,
      description: validated.description as string | null | undefined,
      location: validated.location as string,
      start_date: validated.start_date as Date,
      end_date: validated.end_date as Date,
      price: validated.price as number,
    };
  }

  private validateUpdatePayload(payload: WorkationPayload): Prisma.WorkationsUpdateInput {
    const validated = this.validatePayload(payload, true);

    return {
      title: validated.title as string | undefined,
      description: validated.description as string | null | undefined,
      location: validated.location as string | undefined,
      start_date: validated.start_date as Date | undefined,
      end_date: validated.end_date as Date | undefined,
      price: validated.price as number | undefined,
    };
  }

  private validatePayload(payload: WorkationPayload, partial: boolean): Record<string, unknown> {
    const validated: Record<string, unknown> = {};

    if (!partial) {
      const requiredFields: Array<keyof WorkationPayload> = ['title', 'location', 'start_date', 'end_date', 'price'];
      for (const field of requiredFields) {
        if (payload[field] === undefined || payload[field] === null) {
          throw new BadRequestException(`${field} is required`);
        }
      }
    }

    if (payload.title !== undefined) {
      if (typeof payload.title !== 'string' || payload.title.trim().length === 0 || payload.title.length > 255) {
        throw new BadRequestException('title must be a non-empty string up to 255 characters');
      }
      validated.title = payload.title;
    }

    if (payload.description !== undefined) {
      if (payload.description !== null && typeof payload.description !== 'string') {
        throw new BadRequestException('description must be a string or null');
      }
      validated.description = payload.description;
    }

    if (payload.location !== undefined) {
      if (typeof payload.location !== 'string' || payload.location.trim().length === 0 || payload.location.length > 255) {
        throw new BadRequestException('location must be a non-empty string up to 255 characters');
      }
      validated.location = payload.location;
    }

    let parsedStartDate: Date | undefined;
    let parsedEndDate: Date | undefined;

    if (payload.start_date !== undefined) {
      if (typeof payload.start_date !== 'string') {
        throw new BadRequestException('start_date must be a valid date string');
      }
      parsedStartDate = new Date(payload.start_date);
      if (Number.isNaN(parsedStartDate.getTime())) {
        throw new BadRequestException('start_date must be a valid date string');
      }
      validated.start_date = parsedStartDate;
    }

    if (payload.end_date !== undefined) {
      if (typeof payload.end_date !== 'string') {
        throw new BadRequestException('end_date must be a valid date string');
      }
      parsedEndDate = new Date(payload.end_date);
      if (Number.isNaN(parsedEndDate.getTime())) {
        throw new BadRequestException('end_date must be a valid date string');
      }
      validated.end_date = parsedEndDate;
    }

    const startDateToCompare = parsedStartDate ?? (validated.start_date as Date | undefined);
    const endDateToCompare = parsedEndDate ?? (validated.end_date as Date | undefined);
    if (startDateToCompare && endDateToCompare && endDateToCompare.getTime() < startDateToCompare.getTime()) {
      throw new BadRequestException('end_date must be after or equal to start_date');
    }

    if (payload.price !== undefined) {
      const numericPrice = Number(payload.price);
      if (!Number.isFinite(numericPrice) || numericPrice < 0) {
        throw new BadRequestException('price must be a number greater than or equal to 0');
      }
      validated.price = numericPrice;
    }

    if (partial && Object.keys(validated).length === 0) {
      throw new BadRequestException('At least one field is required for update');
    }

    return validated;
  }
}

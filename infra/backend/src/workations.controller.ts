import { Body, Controller, Delete, Get, HttpCode, NotFoundException, Param, Post, Put, UnprocessableEntityException } from '@nestjs/common';
import { PrismaService } from './prisma.service';
import { mapWorkation } from './response-mappers';

type WorkationPayload = {
  title: string;
  description?: string;
  location: string;
  start_date: string;
  end_date: string;
  price: number;
};

@Controller('api/workations')
export class WorkationsController {
  constructor(private readonly prisma: PrismaService) {}

  @Get()
  async index() {
    const records = await this.prisma.workation.findMany({ orderBy: { id: 'desc' } });
    return records.map(mapWorkation);
  }

  @Get(':id')
  async show(@Param('id') id: string) {
    const workation = await this.prisma.workation.findUnique({ where: { id: Number(id) } });
    if (!workation) throw new NotFoundException('Workation not found');
    return mapWorkation(workation);
  }

  @Post()
  async store(@Body() body: WorkationPayload) {
    this.validatePayload(body, false);

    const created = await this.prisma.workation.create({
      data: {
        title: body.title,
        description: body.description,
        location: body.location,
        startDate: new Date(body.start_date),
        endDate: new Date(body.end_date),
        price: body.price,
      },
    });

    return mapWorkation(created);
  }

  @Put(':id')
  async update(@Param('id') id: string, @Body() body: Partial<WorkationPayload>) {
    this.validatePayload(body, true);

    const existing = await this.prisma.workation.findUnique({ where: { id: Number(id) } });
    if (!existing) throw new NotFoundException('Workation not found');

    const updated = await this.prisma.workation.update({
      where: { id: Number(id) },
      data: {
        title: body.title,
        description: body.description,
        location: body.location,
        startDate: body.start_date ? new Date(body.start_date) : undefined,
        endDate: body.end_date ? new Date(body.end_date) : undefined,
        price: body.price,
      },
    });

    if (!updated) throw new NotFoundException('Workation not found');
    return mapWorkation(updated);
  }

  @Delete(':id')
  @HttpCode(204)
  async destroy(@Param('id') id: string) {
    const existing = await this.prisma.workation.findUnique({ where: { id: Number(id) } });
    if (!existing) throw new NotFoundException('Workation not found');

    await this.prisma.workation.delete({ where: { id: Number(id) } });
  }

  private validatePayload(payload: Partial<WorkationPayload>, partial: boolean): void {
    const requiredFields: Array<keyof WorkationPayload> = ['title', 'location', 'start_date', 'end_date', 'price'];

    if (!partial) {
      for (const field of requiredFields) {
        if (payload[field] === undefined || payload[field] === null || payload[field] === '') {
          throw new UnprocessableEntityException(`Validation failed: ${field} is required`);
        }
      }
    }

    if (payload.price !== undefined && Number(payload.price) < 0) {
      throw new UnprocessableEntityException('Validation failed: price must be >= 0');
    }
  }
}

import { Module } from '@nestjs/common';
import { PrismaService } from '../prisma.service';
import { ResortDayVisitsController } from './resort-day-visits.controller';
import { ResortDayVisitsService } from './resort-day-visits.service';

@Module({
  controllers: [ResortDayVisitsController],
  providers: [ResortDayVisitsService, PrismaService],
})
export class ResortDayVisitsModule {}

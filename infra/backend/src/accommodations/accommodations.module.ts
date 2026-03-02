import { Module } from '@nestjs/common';
import { PrismaService } from '../prisma.service';
import { AccommodationsController } from './accommodations.controller';
import { AccommodationsService } from './accommodations.service';

@Module({
  controllers: [AccommodationsController],
  providers: [AccommodationsService, PrismaService],
})
export class AccommodationsModule {}

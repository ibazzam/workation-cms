import { Module } from '@nestjs/common';
import { PrismaService } from '../prisma.service';
import { VehicleRentalsController } from './vehicle-rentals.controller';
import { VehicleRentalsService } from './vehicle-rentals.service';

@Module({
  controllers: [VehicleRentalsController],
  providers: [VehicleRentalsService, PrismaService],
})
export class VehicleRentalsModule {}

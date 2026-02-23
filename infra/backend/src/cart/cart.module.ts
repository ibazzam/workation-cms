import { Module } from '@nestjs/common';
import { BookingsModule } from '../bookings/bookings.module';
import { PrismaService } from '../prisma.service';
import { CartController } from './cart.controller';
import { CartService } from './cart.service';

@Module({
  imports: [BookingsModule],
  controllers: [CartController],
  providers: [CartService, PrismaService],
})
export class CartModule {}

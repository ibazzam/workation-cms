import { Module } from '@nestjs/common';
import { PrismaService } from '../prisma.service';
import { WriteRateLimitGuard } from '../security/rate-limit.guard';
import { ReviewsController } from './reviews.controller';
import { ReviewsService } from './reviews.service';

@Module({
  controllers: [ReviewsController],
  providers: [ReviewsService, PrismaService, WriteRateLimitGuard],
})
export class ReviewsModule {}

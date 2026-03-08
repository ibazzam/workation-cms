import { Module } from '@nestjs/common';
import { PrismaService } from '../prisma.service';
import { WriteRateLimitGuard } from '../security/rate-limit.guard';
import { SocialLinksController } from './social-links.controller';
import { SocialLinksService } from './social-links.service';

@Module({
  controllers: [SocialLinksController],
  providers: [SocialLinksService, PrismaService, WriteRateLimitGuard],
})
export class SocialLinksModule {}

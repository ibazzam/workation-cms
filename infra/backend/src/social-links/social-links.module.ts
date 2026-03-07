import { Module } from '@nestjs/common';
import { PrismaService } from '../prisma.service';
import { SocialLinksController } from './social-links.controller';
import { SocialLinksService } from './social-links.service';

@Module({
  controllers: [SocialLinksController],
  providers: [SocialLinksService, PrismaService],
})
export class SocialLinksModule {}

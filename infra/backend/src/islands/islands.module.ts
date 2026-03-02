import { Module } from '@nestjs/common';
import { PrismaService } from '../prisma.service';
import { IslandsController } from './islands.controller';
import { IslandsService } from './islands.service';

@Module({
  controllers: [IslandsController],
  providers: [IslandsService, PrismaService],
})
export class IslandsModule {}

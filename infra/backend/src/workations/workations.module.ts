import { Module } from '@nestjs/common';
import { PrismaService } from '../prisma.service';
import { WorkationsController } from './workations.controller';
import { WorkationsService } from './workations.service';

@Module({
  controllers: [WorkationsController],
  providers: [WorkationsService, PrismaService],
})
export class WorkationsModule {}

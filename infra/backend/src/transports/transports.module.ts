import { Module } from '@nestjs/common';
import { PrismaService } from '../prisma.service';
import { TransportsController } from './transports.controller';
import { TransportsService } from './transports.service';

@Module({
  controllers: [TransportsController],
  providers: [TransportsService, PrismaService],
})
export class TransportsModule {}

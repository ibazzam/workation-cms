import { Module } from '@nestjs/common';
import { PrismaService } from '../prisma.service';
import { ExcursionsController } from './excursions.controller';
import { ExcursionsService } from './excursions.service';

@Module({
  controllers: [ExcursionsController],
  providers: [ExcursionsService, PrismaService],
})
export class ExcursionsModule {}

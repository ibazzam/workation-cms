import { Module } from '@nestjs/common';
import { PrismaService } from '../prisma.service';
import { ServiceCategoriesController } from './service-categories.controller';
import { ServiceCategoriesService } from './service-categories.service';

@Module({
  controllers: [ServiceCategoriesController],
  providers: [ServiceCategoriesService, PrismaService],
})
export class ServiceCategoriesModule {}

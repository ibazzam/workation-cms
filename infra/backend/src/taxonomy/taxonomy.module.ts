import { Module } from '@nestjs/common';
import { PrismaService } from '../prisma.service';
import { TaxonomyController } from './taxonomy.controller';
import { TaxonomyService } from './taxonomy.service';

@Module({
  controllers: [TaxonomyController],
  providers: [TaxonomyService, PrismaService],
})
export class TaxonomyModule {}

import { Module } from '@nestjs/common';
import { PrismaService } from '../prisma.service';
import { AdminWriteAuditService } from './admin-write-audit.service';

@Module({
  providers: [AdminWriteAuditService, PrismaService],
  exports: [AdminWriteAuditService],
})
export class AuditModule {}

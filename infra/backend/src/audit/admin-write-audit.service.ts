import { Injectable } from '@nestjs/common';
import { PrismaService } from '../prisma.service';

type AuditPayload = {
  actorUserId?: string;
  actorRole?: string;
  actorEmail?: string;
  actorVendorId?: string;
  method: string;
  path: string;
  statusCode: number;
  success: boolean;
  requestBody?: unknown;
  errorMessage?: string;
};

@Injectable()
export class AdminWriteAuditService {
  constructor(private readonly prisma: PrismaService) {}

  async record(payload: AuditPayload) {
    const serializedBody = this.serialize(payload.requestBody);

    await this.prisma.adminAuditLog.create({
      data: {
        actorUserId: payload.actorUserId,
        actorRole: payload.actorRole,
        actorEmail: payload.actorEmail,
        actorVendorId: payload.actorVendorId,
        method: payload.method,
        path: payload.path,
        statusCode: payload.statusCode,
        success: payload.success,
        requestBody: serializedBody,
        errorMessage: payload.errorMessage,
      },
    });
  }

  private serialize(value: unknown): string | null {
    if (value === undefined) {
      return null;
    }

    try {
      const raw = JSON.stringify(value);
      if (!raw) {
        return null;
      }

      return raw.length > 4000 ? raw.slice(0, 4000) : raw;
    } catch {
      return null;
    }
  }
}

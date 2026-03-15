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
        // actorVendorId is not in schema, only vendorId is required
        vendorId: payload.actorVendorId ? BigInt(payload.actorVendorId) : 0n,
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
      const redacted = this.redact(value, 0);
      const raw = JSON.stringify(redacted);
      if (!raw) {
        return null;
      }

      return raw.length > 4000 ? raw.slice(0, 4000) : raw;
    } catch {
      return null;
    }
  }

  private redact(value: unknown, depth: number): unknown {
    if (depth > 6) {
      return '[truncated]';
    }

    if (Array.isArray(value)) {
      return value.map((item) => this.redact(item, depth + 1));
    }

    if (!value || typeof value !== 'object') {
      return value;
    }

    const asRecord = value as Record<string, unknown>;
    const output: Record<string, unknown> = {};
    for (const [key, fieldValue] of Object.entries(asRecord)) {
      if (this.isSensitiveKey(key)) {
        output[key] = '[redacted]';
      } else {
        output[key] = this.redact(fieldValue, depth + 1);
      }
    }

    return output;
  }

  private isSensitiveKey(key: string): boolean {
    const normalized = key.trim().toLowerCase();
    return normalized.includes('password')
      || normalized.includes('secret')
      || normalized.includes('token')
      || normalized.includes('authorization')
      || normalized.includes('api_key')
      || normalized.includes('apikey')
      || normalized === 'email'
      || normalized.endsWith('email')
      || normalized === 'phone'
      || normalized.endsWith('phone');
  }
}


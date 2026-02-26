import 'reflect-metadata';
import { NestFactory } from '@nestjs/core';
import { AppModule } from './app.module';
import { AdminWriteAuditService } from './audit/admin-write-audit.service';
import { PrismaService } from './prisma.service';

const ADMIN_ROLES = new Set(['ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE']);
const WRITE_METHODS = new Set(['POST', 'PUT', 'PATCH', 'DELETE']);

function parseTrustProxy(value: string | undefined): boolean | number | string {
  const normalized = (value ?? '').trim();
  if (normalized.length === 0) {
    return process.env.NODE_ENV === 'production';
  }

  if (normalized === 'true') {
    return true;
  }

  if (normalized === 'false') {
    return false;
  }

  const parsedNumber = Number(normalized);
  if (Number.isInteger(parsedNumber) && parsedNumber >= 0) {
    return parsedNumber;
  }

  return normalized;
}

function parseCorsOrigin(value: string | undefined): string[] | boolean {
  const normalized = (value ?? '').trim();
  if (normalized.length === 0) {
    return true;
  }

  return normalized
    .split(',')
    .map((item) => item.trim())
    .filter((item) => item.length > 0);
}

async function bootstrap() {
  const app = await NestFactory.create(AppModule);
  const auditService = app.get(AdminWriteAuditService);
  const httpAdapter = app.getHttpAdapter();
  const httpServer = httpAdapter?.getInstance?.();

  if (typeof httpServer?.set === 'function') {
    httpServer.set('trust proxy', parseTrustProxy(process.env.APP_TRUST_PROXY));
  }

  app.use((request: any, response: any, next: () => void) => {
    response.on('finish', () => {
      const role = typeof request?.user?.role === 'string' ? request.user.role : '';
      const method = typeof request?.method === 'string' ? request.method.toUpperCase() : '';
      if (!ADMIN_ROLES.has(role) || !WRITE_METHODS.has(method)) {
        return;
      }

      const statusCode = Number(response?.statusCode ?? 500);
      const path =
        (typeof request?.originalUrl === 'string' && request.originalUrl.trim().length > 0
          ? request.originalUrl
          : typeof request?.url === 'string'
            ? request.url
            : '') || '';

      void auditService.record({
        actorUserId: typeof request?.user?.id === 'string' ? request.user.id : undefined,
        actorRole: role,
        actorEmail: typeof request?.user?.email === 'string' ? request.user.email : undefined,
        actorVendorId: typeof request?.user?.vendorId === 'string' ? request.user.vendorId : undefined,
        method,
        path,
        statusCode,
        success: statusCode < 400,
        requestBody: request?.body,
        errorMessage: statusCode >= 400 ? response?.statusMessage ?? 'Request failed' : undefined,
      });
    });

    next();
  });

  app.enableCors({
    origin: parseCorsOrigin(process.env.CORS_ORIGIN),
  });
  app.setGlobalPrefix('api/v1');
  // Ensure Prisma connection ready before listening to avoid startup races
  try {
    const prisma = app.get(PrismaService);
    if (prisma && typeof prisma.$connect === 'function') {
      await prisma.$connect();
      console.log('Prisma connection established');
    }
  } catch (err) {
    console.warn('Prisma connect warning:', err instanceof Error ? err.message : err);
  }
  const port = Number(process.env.PORT ?? 3000);
  // Bind explicitly to 0.0.0.0 so the process is reachable from the host
  await app.listen(port, '0.0.0.0');
  console.log(`Workation backend listening on http://0.0.0.0:${port} (PORT=${process.env.PORT ?? 'unset'})`);

  process.on('unhandledRejection', (reason) => {
    console.error('Unhandled Rejection at:', reason);
  });
  process.on('uncaughtException', (err) => {
    console.error('Uncaught Exception:', err);
    process.exit(1);
  });
}

bootstrap();

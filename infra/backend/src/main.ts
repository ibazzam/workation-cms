import 'reflect-metadata';
import { NestFactory } from '@nestjs/core';
import { AppModule } from './app.module';
import { AdminWriteAuditService } from './audit/admin-write-audit.service';

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
    return process.env.NODE_ENV === 'production' ? false : true;
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

  if (process.env.NODE_ENV === 'production' && (process.env.AUTH_ALLOW_HEADER_FALLBACK ?? 'false').toLowerCase() === 'true') {
    console.warn('AUTH_ALLOW_HEADER_FALLBACK is enabled in production. This should be temporary only.');
  }
  app.setGlobalPrefix('api/v1');
  const port = Number(process.env.PORT ?? 3000);
  await app.listen(port);
  console.log(`Workation backend listening on http://localhost:${port}`);
}

bootstrap();

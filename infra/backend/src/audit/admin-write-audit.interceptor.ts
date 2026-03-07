import { CallHandler, ExecutionContext, Injectable, NestInterceptor } from '@nestjs/common';
import { Observable, catchError, tap, throwError } from 'rxjs';
import { AdminWriteAuditService } from './admin-write-audit.service';

const ADMIN_ROLES = new Set(['ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE']);
const WRITE_METHODS = new Set(['POST', 'PUT', 'PATCH', 'DELETE']);

@Injectable()
export class AdminWriteAuditInterceptor implements NestInterceptor {
  constructor(private readonly auditService: AdminWriteAuditService) {}

  intercept(context: ExecutionContext, next: CallHandler): Observable<unknown> {
    if (context.getType() !== 'http') {
      return next.handle();
    }

    const request = context.switchToHttp().getRequest();
    const response = context.switchToHttp().getResponse();
    const role = request?.user?.role;
    const method = typeof request?.method === 'string' ? request.method.toUpperCase() : '';

    if (!ADMIN_ROLES.has(role) || !WRITE_METHODS.has(method)) {
      return next.handle();
    }

    const basePayload = {
      actorUserId: this.optionalString(request?.user?.id),
      actorRole: this.optionalString(role),
      actorEmail: this.optionalString(request?.user?.email),
      actorVendorId: this.optionalString(request?.user?.vendorId),
      method,
      path: this.optionalString(request?.originalUrl) ?? this.optionalString(request?.url) ?? '',
      requestBody: request?.body,
    };

    return next.handle().pipe(
      tap(() => {
        void this.auditService.record({
          ...basePayload,
          statusCode: Number(response?.statusCode ?? 200),
          success: true,
        });
      }),
      catchError((error) => {
        void this.auditService.record({
          ...basePayload,
          statusCode: Number(response?.statusCode ?? error?.status ?? 500),
          success: false,
          errorMessage: this.optionalString(error?.message),
        });

        return throwError(() => error);
      }),
    );
  }

  private optionalString(value: unknown): string | undefined {
    if (typeof value !== 'string') {
      return undefined;
    }

    const trimmed = value.trim();
    return trimmed.length > 0 ? trimmed : undefined;
  }
}

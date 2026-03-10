import { Injectable, NestMiddleware } from '@nestjs/common';
import { randomUUID } from 'crypto';
import { ObservabilityService } from './observability.service';

@Injectable()
export class ObservabilityMiddleware implements NestMiddleware {
  constructor(private readonly observabilityService: ObservabilityService) {}

  use(request: any, response: any, next: () => void): void {
    const start = process.hrtime.bigint();
    const requestIdHeader = request?.headers?.['x-request-id'];
    const requestId = typeof requestIdHeader === 'string' && requestIdHeader.trim().length > 0
      ? requestIdHeader
      : randomUUID();
    const traceId = this.resolveTraceId(request?.headers, requestId);

    response.setHeader('x-request-id', requestId);
    response.setHeader('x-trace-id', traceId);

    response.on('finish', () => {
      const elapsedNs = process.hrtime.bigint() - start;
      const durationMs = Number(elapsedNs) / 1_000_000;
      const method = typeof request?.method === 'string' ? request.method.toUpperCase() : 'UNKNOWN';
      const path =
        (typeof request?.originalUrl === 'string' && request.originalUrl.trim().length > 0
          ? request.originalUrl
          : typeof request?.url === 'string'
            ? request.url
            : '') || '';
      const statusCode = Number(response?.statusCode ?? 500);

      this.observabilityService.record({
        ts: Date.now(),
        method,
        path,
        statusCode,
        durationMs,
      });

      // Emit structured request logs for ingestion by hosted log pipelines.
      console.log(JSON.stringify({
        event: 'http_request',
        requestId,
        traceId,
        method,
        path,
        statusCode,
        durationMs: Number(durationMs.toFixed(2)),
        role: typeof request?.user?.role === 'string' ? request.user.role : null,
        userId: typeof request?.user?.id === 'string' ? request.user.id : null,
        timestamp: new Date().toISOString(),
      }));
    });

    next();
  }

  private resolveTraceId(headers: Record<string, unknown> | undefined, fallback: string): string {
    const traceParent = typeof headers?.traceparent === 'string' ? headers.traceparent.trim() : '';
    if (traceParent.length > 0) {
      const parts = traceParent.split('-');
      if (parts.length >= 2 && /^[0-9a-f]{32}$/i.test(parts[1])) {
        return parts[1].toLowerCase();
      }
    }

    const xTraceId = typeof headers?.['x-trace-id'] === 'string' ? headers['x-trace-id'].trim() : '';
    if (xTraceId.length > 0) {
      return xTraceId;
    }

    return fallback;
  }
}

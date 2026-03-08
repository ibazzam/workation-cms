import { CanActivate, ExecutionContext, HttpException, HttpStatus, Injectable } from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import { WRITE_RATE_LIMIT_KEY, WriteRateLimitOptions } from './rate-limit.decorator';

interface Bucket {
  count: number;
  resetAt: number;
}

@Injectable()
export class WriteRateLimitGuard implements CanActivate {
  private readonly buckets = new Map<string, Bucket>();

  constructor(private readonly reflector: Reflector) {}

  canActivate(context: ExecutionContext): boolean {
    const options = this.reflector.getAllAndOverride<WriteRateLimitOptions>(WRITE_RATE_LIMIT_KEY, [
      context.getHandler(),
      context.getClass(),
    ]);

    if (!options) {
      return true;
    }

    const request = context.switchToHttp().getRequest();
    const response = context.switchToHttp().getResponse();
    const actorId = this.resolveActorId(request);
    const key = `${options.key}:${actorId}`;
    const now = Date.now();

    const current = this.buckets.get(key);
    if (!current || current.resetAt <= now) {
      this.buckets.set(key, { count: 1, resetAt: now + options.windowMs });
      return true;
    }

    if (current.count >= options.max) {
      const retryAfterSeconds = Math.max(1, Math.ceil((current.resetAt - now) / 1000));
      if (response && typeof response.setHeader === 'function') {
        response.setHeader('Retry-After', String(retryAfterSeconds));
      }

      throw new HttpException(
        `Rate limit exceeded for ${options.key}. Retry in ${retryAfterSeconds} seconds.`,
        HttpStatus.TOO_MANY_REQUESTS,
      );
    }

    current.count += 1;
    this.buckets.set(key, current);
    return true;
  }

  private resolveActorId(request: any): string {
    const fromUser = request?.user?.id;
    if (typeof fromUser === 'string' && fromUser.trim().length > 0) {
      return fromUser.trim();
    }

    const fromIp = request?.ip;
    if (typeof fromIp === 'string' && fromIp.trim().length > 0) {
      return fromIp.trim();
    }

    return 'anonymous';
  }
}

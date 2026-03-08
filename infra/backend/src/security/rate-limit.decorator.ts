import { SetMetadata } from '@nestjs/common';

export const WRITE_RATE_LIMIT_KEY = 'writeRateLimit';

export interface WriteRateLimitOptions {
  key: string;
  max: number;
  windowMs: number;
}

export const WriteRateLimit = (options: WriteRateLimitOptions) => SetMetadata(WRITE_RATE_LIMIT_KEY, options);

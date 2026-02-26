import { Controller, Get, HttpException, HttpStatus } from '@nestjs/common';
import { Public } from './auth/decorators/public.decorator';
import { PrismaService } from './prisma.service';

@Controller('health')
@Public()
export class HealthController {
  constructor(private readonly prisma: PrismaService) {}

  @Get()
  async status() {
    // Allow tests or operators to skip DB checks via env var
    if ((process.env.SKIP_DB_HEALTH ?? 'false').toLowerCase() === 'true') {
      return { status: 'ok', timestamp: new Date().toISOString() };
    }

    try {
      // Simple connectivity check
      await this.prisma.$queryRaw`SELECT 1`;
      return { status: 'ok', timestamp: new Date().toISOString() };
    } catch (err) {
      throw new HttpException(
        { status: 'error', timestamp: new Date().toISOString(), detail: 'database-unreachable' },
        HttpStatus.SERVICE_UNAVAILABLE,
      );
    }
  }
}

import 'reflect-metadata';
import { NestFactory } from '@nestjs/core';
import { Module } from '@nestjs/common';
import { HealthController } from './health.controller';
import { WorkationsController } from './workations.controller';
import { TransportController } from './transport.controller';
import { BookingsController } from './bookings.controller';
import { PaymentsController } from './payments.controller';
import { ObservabilityController } from './observability.controller';
import { observabilityStore } from './observability.store';
import { PrismaService } from './prisma.service';

@Module({
  controllers: [HealthController, WorkationsController, TransportController, BookingsController, PaymentsController, ObservabilityController],
  providers: [PrismaService],
})
class AppModule {}

async function bootstrap() {
  const app = await NestFactory.create(AppModule);

  app.use((req: any, res: any, next: () => void) => {
    const start = Date.now();

    res.on('finish', () => {
      observabilityStore.record({
        route: req.originalUrl ?? req.url,
        method: req.method,
        status: res.statusCode,
        durationMs: Date.now() - start,
        at: new Date().toISOString(),
      });
    });

    next();
  });

  app.enableCors();
  const port = Number(process.env.PORT ?? 3000);
  await app.listen(port);
  console.log(`Workation backend (minimal) listening on http://localhost:${port}`);
}

bootstrap();

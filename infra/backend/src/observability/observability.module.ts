import { MiddlewareConsumer, Module, NestModule } from '@nestjs/common';
import { ObservabilityController } from './observability.controller';
import { ObservabilityMiddleware } from './observability.middleware';
import { ObservabilityService } from './observability.service';

@Module({
  controllers: [ObservabilityController],
  providers: [ObservabilityService, ObservabilityMiddleware],
  exports: [ObservabilityService],
})
export class ObservabilityModule implements NestModule {
  configure(consumer: MiddlewareConsumer): void {
    consumer.apply(ObservabilityMiddleware).forRoutes('*');
  }
}

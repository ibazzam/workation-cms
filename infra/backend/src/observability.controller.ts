import { Controller, Get, Header } from '@nestjs/common';
import { observabilityStore } from './observability.store';

@Controller('api/v1/ops')
export class ObservabilityController {
  @Get('slo-summary')
  sloSummary() {
    return {
      slo: observabilityStore.getSummary(),
    };
  }

  @Get('metrics')
  @Header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8')
  metrics() {
    return observabilityStore.toPrometheus();
  }
}

import { Controller, Get, Header } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { ObservabilityService } from './observability.service';

@Controller('ops')
export class ObservabilityController {
  constructor(private readonly observabilityService: ObservabilityService) {}

  @Get('slo-summary')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE')
  sloSummary() {
    return this.observabilityService.getSloSummary();
  }

  @Get('metrics')
  @Public()
  @Header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8')
  metrics() {
    return this.observabilityService.getPrometheusMetrics();
  }

  @Get('alerts')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE')
  alerts() {
    return this.observabilityService.getOperationalAlerts();
  }

  @Get('runbooks')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE')
  runbooks() {
    return this.observabilityService.getRunbookLinks();
  }
}

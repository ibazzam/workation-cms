import { Body, Controller, Get, Post } from '@nestjs/common';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { AdminSettingsService } from './admin-settings.service';

@Controller('admin/settings')
@FeatureDomain('admin-settings')
export class AdminSettingsController {
  constructor(private readonly adminSettingsService: AdminSettingsService) {}

  @Get('commercial')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE')
  async getCommercialSettings() {
    return this.adminSettingsService.getCommercialSettings();
  }

  @Post('commercial')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE')
  async updateCommercialSettings(@Body() body: Record<string, unknown>) {
    return this.adminSettingsService.updateCommercialSettings(body);
  }
}

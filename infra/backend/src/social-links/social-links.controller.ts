import { Body, Controller, Delete, Get, HttpCode, Param, Post, Put, Query, Req, UseGuards } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { WriteRateLimit } from '../security/rate-limit.decorator';
import { WriteRateLimitGuard } from '../security/rate-limit.guard';
import { SocialLinksService } from './social-links.service';

@Controller('social-links')
@FeatureDomain('social-links')
export class SocialLinksController {
  constructor(private readonly socialLinksService: SocialLinksService) {}

  @Get('admin/moderation')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async listModerationQueue(@Query('targetType') targetType?: string) {
    return this.socialLinksService.listModerationQueue(targetType);
  }

  @Get('accommodations/:id')
  @Public()
  async listAccommodationLinks(@Param('id') id: string) {
    return this.socialLinksService.listAccommodationLinks(id);
  }

  @Get('transports/:id')
  @Public()
  async listTransportLinks(@Param('id') id: string) {
    return this.socialLinksService.listTransportLinks(id);
  }

  @Get('vendors/:id')
  @Public()
  async listVendorLinks(@Param('id') id: string) {
    return this.socialLinksService.listVendorLinks(id);
  }

  @Post('admin')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  @UseGuards(WriteRateLimitGuard)
  @WriteRateLimit({ key: 'social:admin:create', max: 20, windowMs: 60_000 })
  async create(@Body() body: Record<string, unknown>, @Req() request: any) {
    return this.socialLinksService.create(body, request.user);
  }

  @Put('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  @UseGuards(WriteRateLimitGuard)
  @WriteRateLimit({ key: 'social:admin:update', max: 30, windowMs: 60_000 })
  async update(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.socialLinksService.update(id, body, request.user);
  }

  @Delete('admin/:id')
  @HttpCode(204)
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  @UseGuards(WriteRateLimitGuard)
  @WriteRateLimit({ key: 'social:admin:delete', max: 20, windowMs: 60_000 })
  async remove(@Param('id') id: string, @Req() request: any) {
    await this.socialLinksService.remove(id, request.user);
    return null;
  }

  @Post(':id/flag')
  @Roles('USER', 'ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE', 'VENDOR')
  @UseGuards(WriteRateLimitGuard)
  @WriteRateLimit({ key: 'social:flag', max: 10, windowMs: 60_000 })
  async flag(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.socialLinksService.flag(id, request.user, body);
  }

  @Post('admin/:id/hide')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  @UseGuards(WriteRateLimitGuard)
  @WriteRateLimit({ key: 'social:admin:hide', max: 30, windowMs: 60_000 })
  async hide(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.socialLinksService.hide(id, request.user, body);
  }

  @Post('admin/:id/approve')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  @UseGuards(WriteRateLimitGuard)
  @WriteRateLimit({ key: 'social:admin:approve', max: 30, windowMs: 60_000 })
  async approve(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.socialLinksService.approve(id, request.user, body);
  }
}

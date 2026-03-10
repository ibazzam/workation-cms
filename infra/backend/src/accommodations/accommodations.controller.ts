import { Body, Controller, Delete, Get, HttpCode, Param, Post, Put, Query, Req } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { AccommodationsService } from './accommodations.service';

@Controller('accommodations')
@FeatureDomain('accommodations')
export class AccommodationsController {
  constructor(private readonly accommodationsService: AccommodationsService) {}

  @Get('admin/moderation')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async listModerationQueue(@Query('status') status?: string) {
    return this.accommodationsService.listModerationQueue(status);
  }

  @Get()
  @Public()
  async list(
    @Query('islandId') islandId?: string,
    @Query('q') q?: string,
  ) {
    const parsedIslandId = islandId !== undefined ? Number(islandId) : undefined;

    return this.accommodationsService.list({
      islandId: Number.isFinite(parsedIslandId) ? parsedIslandId : undefined,
      q: q?.trim() ? q.trim() : undefined,
    });
  }

  @Get(':id')
  @Public()
  async getById(@Param('id') id: string) {
    return this.accommodationsService.getById(id);
  }

  @Get(':id/availability')
  @Public()
  async getAvailability(
    @Param('id') id: string,
    @Query('startDate') startDate?: string,
    @Query('endDate') endDate?: string,
    @Query('roomsRequested') roomsRequested?: string,
  ) {
    return this.accommodationsService.getAvailability(id, {
      startDate,
      endDate,
      roomsRequested,
    });
  }

  @Get(':id/quote')
  @Public()
  async getQuote(
    @Param('id') id: string,
    @Query('startDate') startDate?: string,
    @Query('endDate') endDate?: string,
    @Query('roomsRequested') roomsRequested?: string,
  ) {
    return this.accommodationsService.getQuote(id, {
      startDate,
      endDate,
      roomsRequested,
    });
  }

  @Post('admin')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async create(@Body() body: Record<string, unknown>, @Req() request: any) {
    return this.accommodationsService.create(body, request.user);
  }

  @Put('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async update(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.accommodationsService.update(id, body, request.user);
  }

  @Put('admin/:id/policies')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async updatePolicies(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.accommodationsService.updatePolicies(id, body, request.user);
  }

  @Post('admin/:id/hide')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async hide(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.accommodationsService.setModerationStatus(id, 'HIDDEN', request.user, body);
  }

  @Post('admin/:id/publish')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async publish(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.accommodationsService.setModerationStatus(id, 'APPROVED', request.user, body);
  }

  @Delete('admin/:id')
  @HttpCode(204)
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async remove(@Param('id') id: string, @Req() request: any) {
    await this.accommodationsService.remove(id, request.user);
    return null;
  }

  @Post('admin/:id/blackouts')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async createBlackout(
    @Param('id') id: string,
    @Body() body: Record<string, unknown>,
    @Req() request: any,
  ) {
    return this.accommodationsService.createBlackout(id, body, request.user);
  }

  @Post('admin/:id/seasonal-rates')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async createSeasonalRate(
    @Param('id') id: string,
    @Body() body: Record<string, unknown>,
    @Req() request: any,
  ) {
    return this.accommodationsService.createSeasonalRate(id, body, request.user);
  }

  @Delete('admin/:id/blackouts/:blackoutId')
  @HttpCode(204)
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async removeBlackout(
    @Param('id') id: string,
    @Param('blackoutId') blackoutId: string,
    @Req() request: any,
  ) {
    await this.accommodationsService.removeBlackout(id, blackoutId, request.user);
    return null;
  }

  @Delete('admin/:id/seasonal-rates/:rateId')
  @HttpCode(204)
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async removeSeasonalRate(
    @Param('id') id: string,
    @Param('rateId') rateId: string,
    @Req() request: any,
  ) {
    await this.accommodationsService.removeSeasonalRate(id, rateId, request.user);
    return null;
  }
}

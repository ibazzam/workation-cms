import { Body, Controller, Delete, Get, HttpCode, Param, Post, Put, Query, Req } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { ResortDayVisitsService } from './resort-day-visits.service';

@Controller('resort-day-visits')
@FeatureDomain('resort-day-visits')
export class ResortDayVisitsController {
  constructor(private readonly resortDayVisitsService: ResortDayVisitsService) {}

  @Get()
  @Public()
  async list(
    @Query('islandId') islandId?: string,
    @Query('vendorId') vendorId?: string,
    @Query('q') q?: string,
    @Query('includesTransfer') includesTransfer?: string,
    @Query('date') date?: string,
  ) {
    const parsedIslandId = islandId !== undefined ? Number(islandId) : undefined;

    return this.resortDayVisitsService.list({
      islandId: Number.isFinite(parsedIslandId) ? parsedIslandId : undefined,
      vendorId,
      q,
      includesTransfer,
      date,
    });
  }

  @Get(':id')
  @Public()
  async getById(@Param('id') id: string) {
    return this.resortDayVisitsService.getById(id);
  }

  @Get(':id/windows')
  @Public()
  async listWindows(
    @Param('id') id: string,
    @Query('from') from?: string,
    @Query('to') to?: string,
  ) {
    return this.resortDayVisitsService.listWindows(id, { from, to });
  }

  @Get(':id/quote')
  @Public()
  async quote(
    @Param('id') id: string,
    @Query('windowId') windowId?: string,
    @Query('passesRequested') passesRequested?: string,
    @Query('travelerCategory') travelerCategory?: string,
    @Query('travelerAge') travelerAge?: string,
    @Query('needsTransfer') needsTransfer?: string,
  ) {
    return this.resortDayVisitsService.quote(id, {
      windowId,
      passesRequested,
      travelerCategory,
      travelerAge,
      needsTransfer,
    });
  }

  @Post('admin')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async create(@Body() body: Record<string, unknown>, @Req() request: any) {
    return this.resortDayVisitsService.create(body, request.user);
  }

  @Put('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async update(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.resortDayVisitsService.update(id, body, request.user);
  }

  @Delete('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  @HttpCode(204)
  async remove(@Param('id') id: string, @Req() request: any) {
    await this.resortDayVisitsService.remove(id, request.user);
    return null;
  }

  @Post('admin/:id/windows')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async createWindow(
    @Param('id') id: string,
    @Body() body: Record<string, unknown>,
    @Req() request: any,
  ) {
    return this.resortDayVisitsService.createWindow(id, body, request.user);
  }

  @Put('admin/:id/windows/:windowId')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async updateWindow(
    @Param('id') id: string,
    @Param('windowId') windowId: string,
    @Body() body: Record<string, unknown>,
    @Req() request: any,
  ) {
    return this.resortDayVisitsService.updateWindow(id, windowId, body, request.user);
  }

  @Delete('admin/:id/windows/:windowId')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  @HttpCode(204)
  async removeWindow(
    @Param('id') id: string,
    @Param('windowId') windowId: string,
    @Req() request: any,
  ) {
    await this.resortDayVisitsService.removeWindow(id, windowId, request.user);
    return null;
  }
}

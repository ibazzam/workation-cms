import { Body, Controller, Delete, Get, HttpCode, Param, Post, Put, Query, Req } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { ExcursionsService } from './excursions.service';

@Controller('excursions')
@FeatureDomain('excursions')
export class ExcursionsController {
  constructor(private readonly excursionsService: ExcursionsService) {}

  @Get()
  @Public()
  async list(
    @Query('islandId') islandId?: string,
    @Query('vendorId') vendorId?: string,
    @Query('type') type?: string,
    @Query('q') q?: string,
    @Query('date') date?: string,
  ) {
    const parsedIslandId = islandId !== undefined ? Number(islandId) : undefined;

    return this.excursionsService.list({
      islandId: Number.isFinite(parsedIslandId) ? parsedIslandId : undefined,
      vendorId,
      type,
      q,
      date,
    });
  }

  @Get(':id')
  @Public()
  async getById(@Param('id') id: string) {
    return this.excursionsService.getById(id);
  }

  @Get(':id/slots')
  @Public()
  async listSlots(
    @Param('id') id: string,
    @Query('from') from?: string,
    @Query('to') to?: string,
  ) {
    return this.excursionsService.listSlots(id, { from, to });
  }

  @Get(':id/quote')
  @Public()
  async quote(
    @Param('id') id: string,
    @Query('slotId') slotId?: string,
    @Query('participants') participants?: string,
    @Query('equipmentUnitsRequested') equipmentUnitsRequested?: string,
  ) {
    return this.excursionsService.quote(id, {
      slotId,
      participants,
      equipmentUnitsRequested,
    });
  }

  @Post('admin')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async create(@Body() body: Record<string, unknown>, @Req() request: any) {
    return this.excursionsService.create(body, request.user);
  }

  @Put('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async update(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.excursionsService.update(id, body, request.user);
  }

  @Delete('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  @HttpCode(204)
  async remove(@Param('id') id: string, @Req() request: any) {
    await this.excursionsService.remove(id, request.user);
    return null;
  }

  @Post('admin/:id/slots')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async createSlot(
    @Param('id') id: string,
    @Body() body: Record<string, unknown>,
    @Req() request: any,
  ) {
    return this.excursionsService.createSlot(id, body, request.user);
  }

  @Put('admin/:id/slots/:slotId')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async updateSlot(
    @Param('id') id: string,
    @Param('slotId') slotId: string,
    @Body() body: Record<string, unknown>,
    @Req() request: any,
  ) {
    return this.excursionsService.updateSlot(id, slotId, body, request.user);
  }

  @Delete('admin/:id/slots/:slotId')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  @HttpCode(204)
  async removeSlot(
    @Param('id') id: string,
    @Param('slotId') slotId: string,
    @Req() request: any,
  ) {
    await this.excursionsService.removeSlot(id, slotId, request.user);
    return null;
  }
}

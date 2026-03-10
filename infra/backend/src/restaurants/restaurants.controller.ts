import { Body, Controller, Delete, Get, HttpCode, Param, Post, Put, Query, Req } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { RestaurantsService } from './restaurants.service';

@Controller('restaurants')
@FeatureDomain('restaurants')
export class RestaurantsController {
  constructor(private readonly restaurantsService: RestaurantsService) {}

  @Get()
  @Public()
  async list(
    @Query('islandId') islandId?: string,
    @Query('vendorId') vendorId?: string,
    @Query('q') q?: string,
    @Query('cuisineType') cuisineType?: string,
    @Query('date') date?: string,
  ) {
    const parsedIslandId = islandId !== undefined ? Number(islandId) : undefined;

    return this.restaurantsService.list({
      islandId: Number.isFinite(parsedIslandId) ? parsedIslandId : undefined,
      vendorId,
      q,
      cuisineType,
      date,
    });
  }

  @Get(':id')
  @Public()
  async getById(@Param('id') id: string) {
    return this.restaurantsService.getById(id);
  }

  @Get(':id/windows')
  @Public()
  async listSeatingWindows(
    @Param('id') id: string,
    @Query('from') from?: string,
    @Query('to') to?: string,
  ) {
    return this.restaurantsService.listSeatingWindows(id, { from, to });
  }

  @Get(':id/quote')
  @Public()
  async quote(
    @Param('id') id: string,
    @Query('windowId') windowId?: string,
    @Query('partySize') partySize?: string,
  ) {
    return this.restaurantsService.quote(id, {
      windowId,
      partySize,
    });
  }

  @Post('admin')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async create(@Body() body: Record<string, unknown>, @Req() request: any) {
    return this.restaurantsService.create(body, request.user);
  }

  @Put('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async update(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.restaurantsService.update(id, body, request.user);
  }

  @Delete('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  @HttpCode(204)
  async remove(@Param('id') id: string, @Req() request: any) {
    await this.restaurantsService.remove(id, request.user);
    return null;
  }

  @Post('admin/:id/windows')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async createSeatingWindow(
    @Param('id') id: string,
    @Body() body: Record<string, unknown>,
    @Req() request: any,
  ) {
    return this.restaurantsService.createSeatingWindow(id, body, request.user);
  }

  @Put('admin/:id/windows/:windowId')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async updateSeatingWindow(
    @Param('id') id: string,
    @Param('windowId') windowId: string,
    @Body() body: Record<string, unknown>,
    @Req() request: any,
  ) {
    return this.restaurantsService.updateSeatingWindow(id, windowId, body, request.user);
  }

  @Delete('admin/:id/windows/:windowId')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  @HttpCode(204)
  async removeSeatingWindow(
    @Param('id') id: string,
    @Param('windowId') windowId: string,
    @Req() request: any,
  ) {
    await this.restaurantsService.removeSeatingWindow(id, windowId, request.user);
    return null;
  }
}

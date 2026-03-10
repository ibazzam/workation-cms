import { Body, Controller, Delete, Get, HttpCode, Param, Post, Put, Query, Req } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { VehicleRentalsService } from './vehicle-rentals.service';

@Controller('vehicle-rentals')
@FeatureDomain('vehicle-rentals')
export class VehicleRentalsController {
  constructor(private readonly vehicleRentalsService: VehicleRentalsService) {}

  @Get()
  @Public()
  async list(
    @Query('pickupIslandId') pickupIslandId?: string,
    @Query('dropoffIslandId') dropoffIslandId?: string,
    @Query('vehicleType') vehicleType?: string,
    @Query('vendorId') vendorId?: string,
    @Query('q') q?: string,
  ) {
    const parsedPickupIslandId = pickupIslandId !== undefined ? Number(pickupIslandId) : undefined;
    const parsedDropoffIslandId = dropoffIslandId !== undefined ? Number(dropoffIslandId) : undefined;

    return this.vehicleRentalsService.list({
      pickupIslandId: Number.isFinite(parsedPickupIslandId) ? parsedPickupIslandId : undefined,
      dropoffIslandId: Number.isFinite(parsedDropoffIslandId) ? parsedDropoffIslandId : undefined,
      vehicleType,
      vendorId,
      q,
    });
  }

  @Get(':id')
  @Public()
  async getById(@Param('id') id: string) {
    return this.vehicleRentalsService.getById(id);
  }

  @Get(':id/availability')
  @Public()
  async getAvailability(
    @Param('id') id: string,
    @Query('startDate') startDate?: string,
    @Query('endDate') endDate?: string,
    @Query('unitsRequested') unitsRequested?: string,
  ) {
    return this.vehicleRentalsService.getAvailability(id, {
      startDate,
      endDate,
      unitsRequested,
    });
  }

  @Get(':id/quote')
  @Public()
  async getQuote(
    @Param('id') id: string,
    @Query('startDate') startDate?: string,
    @Query('endDate') endDate?: string,
    @Query('unitsRequested') unitsRequested?: string,
    @Query('driverAge') driverAge?: string,
    @Query('hasLicense') hasLicense?: string,
    @Query('licenseClass') licenseClass?: string,
    @Query('dropoffIslandId') dropoffIslandId?: string,
  ) {
    const parsedDropoffIslandId = dropoffIslandId !== undefined ? Number(dropoffIslandId) : undefined;

    return this.vehicleRentalsService.getQuote(id, {
      startDate,
      endDate,
      unitsRequested,
      driverAge,
      hasLicense,
      licenseClass,
      dropoffIslandId: Number.isFinite(parsedDropoffIslandId) ? parsedDropoffIslandId : undefined,
    });
  }

  @Post('admin')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async create(@Body() body: Record<string, unknown>, @Req() request: any) {
    return this.vehicleRentalsService.create(body, request.user);
  }

  @Put('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async update(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.vehicleRentalsService.update(id, body, request.user);
  }

  @Delete('admin/:id')
  @HttpCode(204)
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async remove(@Param('id') id: string, @Req() request: any) {
    await this.vehicleRentalsService.remove(id, request.user);
    return null;
  }

  @Post('admin/:id/blackouts')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async createBlackout(
    @Param('id') id: string,
    @Body() body: Record<string, unknown>,
    @Req() request: any,
  ) {
    return this.vehicleRentalsService.createBlackout(id, body, request.user);
  }

  @Delete('admin/:id/blackouts/:blackoutId')
  @HttpCode(204)
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async removeBlackout(
    @Param('id') id: string,
    @Param('blackoutId') blackoutId: string,
    @Req() request: any,
  ) {
    await this.vehicleRentalsService.removeBlackout(id, blackoutId, request.user);
    return null;
  }
}

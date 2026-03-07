import { Body, Controller, Delete, Get, HttpCode, Param, Post, Put, Query } from '@nestjs/common';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { VendorsService } from './vendors.service';

type RequestUser = {
  id: string;
  role: 'USER' | 'VENDOR' | 'ADMIN' | 'ADMIN_SUPER' | 'ADMIN_CARE' | 'ADMIN_FINANCE';
  email?: string;
  vendorId?: string;
};

@Controller('vendors')
@FeatureDomain('vendors')
export class VendorsController {
  constructor(private readonly vendorsService: VendorsService) {}

  @Get()
  @Public()
  async list(@Query('q') q?: string) {
    return this.vendorsService.list({ q: q?.trim() || undefined });
  }

  @Get(':id')
  @Public()
  async getById(@Param('id') id: string) {
    return this.vendorsService.getById(id);
  }

  @Get('me')
  @Roles('VENDOR')
  async getOwnProfile(@CurrentUser() user: RequestUser) {
    return this.vendorsService.getOwnProfile(user);
  }

  @Put('me')
  @Roles('VENDOR')
  async updateOwnProfile(
    @CurrentUser() user: RequestUser,
    @Body() body: Record<string, unknown>,
  ) {
    return this.vendorsService.updateOwnProfile(user, body);
  }

  @Post('admin')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async create(@Body() body: Record<string, unknown>) {
    return this.vendorsService.create(body);
  }

  @Put('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async update(
    @Param('id') id: string,
    @Body() body: Record<string, unknown>,
    @CurrentUser() user: RequestUser,
  ) {
    return this.vendorsService.update(id, body, user);
  }

  @Delete('admin/:id')
  @HttpCode(204)
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async remove(@Param('id') id: string) {
    await this.vendorsService.remove(id);
    return null;
  }
}

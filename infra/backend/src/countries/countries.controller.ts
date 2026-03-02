import { Body, Controller, Delete, Get, HttpCode, Param, ParseIntPipe, Post, Put, Query } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { CountriesService } from './countries.service';

@Controller('countries')
@FeatureDomain('countries')
export class CountriesController {
  constructor(private readonly countriesService: CountriesService) {}

  @Get()
  @Public()
  async list(@Query('q') q?: string, @Query('active') active?: string) {
    const activeFilter = active === undefined ? undefined : active.toLowerCase() === 'true';
    return this.countriesService.list({ q: q?.trim() || undefined, active: activeFilter });
  }

  @Get(':id')
  @Public()
  async getById(@Param('id', ParseIntPipe) id: number) {
    return this.countriesService.getById(id);
  }

  @Post('admin')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async create(@Body() body: Record<string, unknown>) {
    return this.countriesService.create(body);
  }

  @Put('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async update(@Param('id', ParseIntPipe) id: number, @Body() body: Record<string, unknown>) {
    return this.countriesService.update(id, body);
  }

  @Delete('admin/:id')
  @HttpCode(204)
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async remove(@Param('id', ParseIntPipe) id: number) {
    await this.countriesService.remove(id);
    return null;
  }
}

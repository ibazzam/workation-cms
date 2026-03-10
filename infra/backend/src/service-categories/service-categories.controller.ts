import { Body, Controller, Delete, Get, HttpCode, Param, ParseIntPipe, Post, Put, Query } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { ServiceCategoriesService } from './service-categories.service';

@Controller('service-categories')
@FeatureDomain('service-categories')
export class ServiceCategoriesController {
  constructor(private readonly serviceCategoriesService: ServiceCategoriesService) {}

  @Get()
  @Public()
  async list(@Query('q') q?: string, @Query('scope') scope?: string, @Query('active') active?: string) {
    const scopeFilter = scope ? scope.toUpperCase() as 'ACCOMMODATION' | 'TRANSPORT' | 'BOTH' | 'ACTIVITY' : undefined;
    const activeFilter = active === undefined ? undefined : active.toLowerCase() === 'true';

    return this.serviceCategoriesService.list({
      q: q?.trim() || undefined,
      scope: scopeFilter,
      active: activeFilter,
    });
  }

  @Get(':id')
  @Public()
  async getById(@Param('id', ParseIntPipe) id: number) {
    return this.serviceCategoriesService.getById(id);
  }

  @Post('admin')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async create(@Body() body: Record<string, unknown>) {
    return this.serviceCategoriesService.create(body);
  }

  @Put('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async update(@Param('id', ParseIntPipe) id: number, @Body() body: Record<string, unknown>) {
    return this.serviceCategoriesService.update(id, body);
  }

  @Delete('admin/:id')
  @HttpCode(204)
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async remove(@Param('id', ParseIntPipe) id: number) {
    await this.serviceCategoriesService.remove(id);
    return null;
  }
}

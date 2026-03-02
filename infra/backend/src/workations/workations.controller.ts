import { Body, Controller, Delete, Get, HttpCode, Param, ParseIntPipe, Post, Put } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { WorkationsService } from './workations.service';

@Controller('workations')
@FeatureDomain('workations')
export class WorkationsController {
  constructor(private readonly workationsService: WorkationsService) {}

  @Get()
  @Public()
  async index() {
    return this.workationsService.findAll();
  }

  @Get(':id')
  @Public()
  async show(@Param('id', ParseIntPipe) id: number) {
    return this.workationsService.findOne(id);
  }

  @Post()
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async store(@Body() body: Record<string, unknown>) {
    return this.workationsService.create(body);
  }

  @Put(':id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async update(@Param('id', ParseIntPipe) id: number, @Body() body: Record<string, unknown>) {
    return this.workationsService.update(id, body);
  }

  @Delete(':id')
  @HttpCode(204)
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async destroy(@Param('id', ParseIntPipe) id: number) {
    await this.workationsService.remove(id);
    return null;
  }
}

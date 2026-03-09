import { Body, Controller, Get, ParseIntPipe, Query, Param, Put } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { IslandsService } from './islands.service';

@Controller()
@FeatureDomain('islands')
export class IslandsController {
  constructor(private readonly islandsService: IslandsService) {}

  @Get('atolls')
  @Public()
  async listAtolls() {
    return this.islandsService.listAtolls();
  }

  @Get('atolls/:id')
  @Public()
  async getAtoll(@Param('id', ParseIntPipe) id: number) {
    return this.islandsService.getAtoll(id);
  }

  @Get('islands')
  @Public()
  async listIslands(
    @Query('atollId') atollId?: string,
    @Query('q') q?: string,
  ) {
    const parsedAtollId = atollId !== undefined ? Number(atollId) : undefined;

    return this.islandsService.listIslands({
      atollId: Number.isFinite(parsedAtollId) ? parsedAtollId : undefined,
      q: q?.trim() ? q.trim() : undefined,
    });
  }

  @Get('islands/:id')
  @Public()
  async getIsland(@Param('id', ParseIntPipe) id: number) {
    return this.islandsService.getIsland(id);
  }

  @Put('islands/admin/:id/metadata')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async updateIslandMetadata(
    @Param('id', ParseIntPipe) id: number,
    @Body() body: Record<string, unknown>,
  ) {
    return this.islandsService.updateIslandMetadata(id, body);
  }
}

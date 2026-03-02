import { Controller, Get, ParseIntPipe, Query, Param } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { IslandsService } from './islands.service';

@Controller()
@Public()
@FeatureDomain('islands')
export class IslandsController {
  constructor(private readonly islandsService: IslandsService) {}

  @Get('atolls')
  async listAtolls() {
    return this.islandsService.listAtolls();
  }

  @Get('atolls/:id')
  async getAtoll(@Param('id', ParseIntPipe) id: number) {
    return this.islandsService.getAtoll(id);
  }

  @Get('islands')
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
  async getIsland(@Param('id', ParseIntPipe) id: number) {
    return this.islandsService.getIsland(id);
  }
}

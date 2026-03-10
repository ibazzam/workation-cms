import { Body, Controller, Delete, Get, HttpCode, Param, Post, Put, Query, Req } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { RemoteWorkSpacesService } from './remote-work-spaces.service';

@Controller('remote-work-spaces')
@FeatureDomain('remote-work-spaces')
export class RemoteWorkSpacesController {
  constructor(private readonly remoteWorkSpacesService: RemoteWorkSpacesService) {}

  @Get()
  @Public()
  async list(
    @Query('islandId') islandId?: string,
    @Query('vendorId') vendorId?: string,
    @Query('q') q?: string,
    @Query('requiredMbps') requiredMbps?: string,
    @Query('meetingRoomRequired') meetingRoomRequired?: string,
  ) {
    const parsedIslandId = islandId !== undefined ? Number(islandId) : undefined;

    return this.remoteWorkSpacesService.list({
      islandId: Number.isFinite(parsedIslandId) ? parsedIslandId : undefined,
      vendorId,
      q,
      requiredMbps,
      meetingRoomRequired,
    });
  }

  @Get(':id')
  @Public()
  async getById(@Param('id') id: string) {
    return this.remoteWorkSpacesService.getById(id);
  }

  @Get(':id/pass-windows')
  @Public()
  async listPassWindows(
    @Param('id') id: string,
    @Query('from') from?: string,
    @Query('to') to?: string,
  ) {
    return this.remoteWorkSpacesService.listPassWindows(id, { from, to });
  }

  @Get(':id/quote')
  @Public()
  async quote(
    @Param('id') id: string,
    @Query('windowId') windowId?: string,
    @Query('passesRequested') passesRequested?: string,
    @Query('passType') passType?: string,
    @Query('deskType') deskType?: string,
    @Query('requiredMbps') requiredMbps?: string,
    @Query('meetingRoomNeeded') meetingRoomNeeded?: string,
  ) {
    return this.remoteWorkSpacesService.quote(id, {
      windowId,
      passesRequested,
      passType,
      deskType,
      requiredMbps,
      meetingRoomNeeded,
    });
  }

  @Post('admin')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async create(@Body() body: Record<string, unknown>, @Req() request: any) {
    return this.remoteWorkSpacesService.create(body, request.user);
  }

  @Put('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async update(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.remoteWorkSpacesService.update(id, body, request.user);
  }

  @Delete('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  @HttpCode(204)
  async remove(@Param('id') id: string, @Req() request: any) {
    await this.remoteWorkSpacesService.remove(id, request.user);
    return null;
  }

  @Post('admin/:id/pass-windows')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async createPassWindow(
    @Param('id') id: string,
    @Body() body: Record<string, unknown>,
    @Req() request: any,
  ) {
    return this.remoteWorkSpacesService.createPassWindow(id, body, request.user);
  }

  @Put('admin/:id/pass-windows/:windowId')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async updatePassWindow(
    @Param('id') id: string,
    @Param('windowId') windowId: string,
    @Body() body: Record<string, unknown>,
    @Req() request: any,
  ) {
    return this.remoteWorkSpacesService.updatePassWindow(id, windowId, body, request.user);
  }

  @Delete('admin/:id/pass-windows/:windowId')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  @HttpCode(204)
  async removePassWindow(
    @Param('id') id: string,
    @Param('windowId') windowId: string,
    @Req() request: any,
  ) {
    await this.remoteWorkSpacesService.removePassWindow(id, windowId, request.user);
    return null;
  }
}

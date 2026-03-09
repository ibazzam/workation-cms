import { Body, Controller, Delete, Get, HttpCode, Param, Patch, Post, Put, Query, Req } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { TransportsService } from './transports.service';

type DisruptionPayload = {
  status?: unknown;
  reason?: unknown;
  delayMinutes?: unknown;
  replacementTransportId?: unknown;
  startsAt?: unknown;
};

@Controller('transports')
@FeatureDomain('transports')
export class TransportsController {
  constructor(private readonly transportsService: TransportsService) {}

  @Get()
  @Public()
  async list(
    @Query('fromIslandId') fromIslandId?: string,
    @Query('toIslandId') toIslandId?: string,
    @Query('type') type?: string,
    @Query('date') date?: string,
    @Query('anchorIslandId') anchorIslandId?: string,
    @Query('preferredAtollId') preferredAtollId?: string,
  ) {
    const parsedFromIslandId = fromIslandId !== undefined ? Number(fromIslandId) : undefined;
    const parsedToIslandId = toIslandId !== undefined ? Number(toIslandId) : undefined;
    const parsedAnchorIslandId = anchorIslandId !== undefined ? Number(anchorIslandId) : undefined;
    const parsedPreferredAtollId = preferredAtollId !== undefined ? Number(preferredAtollId) : undefined;

    return this.transportsService.list({
      fromIslandId: Number.isFinite(parsedFromIslandId) ? parsedFromIslandId : undefined,
      toIslandId: Number.isFinite(parsedToIslandId) ? parsedToIslandId : undefined,
      type: type?.trim() ? type.trim() : undefined,
      date: date?.trim() ? date.trim() : undefined,
      anchorIslandId: Number.isFinite(parsedAnchorIslandId) ? parsedAnchorIslandId : undefined,
      preferredAtollId: Number.isFinite(parsedPreferredAtollId) ? parsedPreferredAtollId : undefined,
    });
  }

  @Get('schedule')
  @Public()
  async listSchedule(
    @Query('date') date?: string,
    @Query('fromIslandId') fromIslandId?: string,
    @Query('toIslandId') toIslandId?: string,
    @Query('type') type?: string,
    @Query('anchorIslandId') anchorIslandId?: string,
    @Query('preferredAtollId') preferredAtollId?: string,
  ) {
    const parsedFromIslandId = fromIslandId !== undefined ? Number(fromIslandId) : undefined;
    const parsedToIslandId = toIslandId !== undefined ? Number(toIslandId) : undefined;
    const parsedAnchorIslandId = anchorIslandId !== undefined ? Number(anchorIslandId) : undefined;
    const parsedPreferredAtollId = preferredAtollId !== undefined ? Number(preferredAtollId) : undefined;

    return this.transportsService.listSchedule({
      date: date?.trim() ? date.trim() : undefined,
      fromIslandId: Number.isFinite(parsedFromIslandId) ? parsedFromIslandId : undefined,
      toIslandId: Number.isFinite(parsedToIslandId) ? parsedToIslandId : undefined,
      type: type?.trim() ? type.trim() : undefined,
      anchorIslandId: Number.isFinite(parsedAnchorIslandId) ? parsedAnchorIslandId : undefined,
      preferredAtollId: Number.isFinite(parsedPreferredAtollId) ? parsedPreferredAtollId : undefined,
    });
  }

  @Get('flights/schedule')
  @Public()
  async listFlightSchedule(
    @Query('date') date?: string,
    @Query('fromIslandId') fromIslandId?: string,
    @Query('toIslandId') toIslandId?: string,
    @Query('anchorIslandId') anchorIslandId?: string,
    @Query('preferredAtollId') preferredAtollId?: string,
  ) {
    const parsedFromIslandId = fromIslandId !== undefined ? Number(fromIslandId) : undefined;
    const parsedToIslandId = toIslandId !== undefined ? Number(toIslandId) : undefined;
    const parsedAnchorIslandId = anchorIslandId !== undefined ? Number(anchorIslandId) : undefined;
    const parsedPreferredAtollId = preferredAtollId !== undefined ? Number(preferredAtollId) : undefined;

    return this.transportsService.listFlightSchedule({
      date: date?.trim() ? date.trim() : undefined,
      fromIslandId: Number.isFinite(parsedFromIslandId) ? parsedFromIslandId : undefined,
      toIslandId: Number.isFinite(parsedToIslandId) ? parsedToIslandId : undefined,
      anchorIslandId: Number.isFinite(parsedAnchorIslandId) ? parsedAnchorIslandId : undefined,
      preferredAtollId: Number.isFinite(parsedPreferredAtollId) ? parsedPreferredAtollId : undefined,
    });
  }

  @Get(':id/fare-classes')
  @Public()
  async listFareClasses(@Param('id') id: string) {
    return this.transportsService.listFareClasses(id);
  }

  @Get(':id/quote')
  @Public()
  async quote(
    @Param('id') id: string,
    @Query('guests') guests?: string,
    @Query('fareClassCode') fareClassCode?: string,
  ) {
    const parsedGuests = guests !== undefined ? Number(guests) : undefined;

    return this.transportsService.quote(id, {
      guests: Number.isFinite(parsedGuests) ? parsedGuests : undefined,
      fareClassCode: fareClassCode?.trim() ? fareClassCode.trim() : undefined,
    });
  }

  @Get(':id')
  @Public()
  async getById(@Param('id') id: string) {
    return this.transportsService.getById(id);
  }

  @Post('admin')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async create(@Body() body: Record<string, unknown>, @Req() request: any) {
    return this.transportsService.create(body, request.user);
  }

  @Put('admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async update(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.transportsService.update(id, body, request.user);
  }

  @Delete('admin/:id')
  @HttpCode(204)
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async remove(@Param('id') id: string, @Req() request: any) {
    await this.transportsService.remove(id, request.user);
    return null;
  }

  @Post('admin/:id/disruptions')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  createDisruption(@Param('id') id: string, @Body() payload: DisruptionPayload, @Req() request: any) {
    return this.transportsService.createDisruption(id, payload, request.user);
  }

  @Patch('admin/:id/disruptions/:disruptionId/resolve')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  resolveDisruption(
    @Param('id') id: string,
    @Param('disruptionId') disruptionId: string,
    @Req() request: any,
  ) {
    return this.transportsService.resolveDisruption(id, disruptionId, request.user);
  }

  @Post('admin/:id/disruptions/:disruptionId/reaccommodate')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  reaccommodateDisruptedBookings(
    @Param('id') id: string,
    @Param('disruptionId') disruptionId: string,
    @Req() request: any,
  ) {
    return this.transportsService.reaccommodateDisruptedBookings(id, disruptionId, request.user);
  }
}

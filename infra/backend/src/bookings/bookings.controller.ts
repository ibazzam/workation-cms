import { Body, Controller, Get, Param, Patch, Post } from '@nestjs/common';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { BookingsService } from './bookings.service';

type RequestUser = {
  id: string;
  role: 'USER' | 'VENDOR' | 'ADMIN' | 'ADMIN_SUPER' | 'ADMIN_CARE' | 'ADMIN_FINANCE';
};

@Controller('bookings')
@FeatureDomain('bookings')
export class BookingsController {
  constructor(private readonly bookingsService: BookingsService) {}

  @Get()
  async mine(@CurrentUser() user: RequestUser) {
    return this.bookingsService.listByUser(user.id);
  }

  @Post()
  async create(
    @CurrentUser() user: RequestUser,
    @Body() body: Record<string, unknown>,
  ) {
    return this.bookingsService.createForUser(user.id, body);
  }

  @Post('itinerary/validate')
  async validateItinerary(
    @CurrentUser() user: RequestUser,
    @Body() body: Record<string, unknown>,
  ) {
    return this.bookingsService.validateItineraryForUser(user.id, body);
  }

  @Patch(':id/hold')
  async moveToHold(
    @CurrentUser() user: RequestUser,
    @Param('id') id: string,
  ) {
    return this.bookingsService.transitionForUser(user.id, id, 'HOLD');
  }

  @Patch(':id/confirm')
  async confirm(
    @CurrentUser() user: RequestUser,
    @Param('id') id: string,
  ) {
    return this.bookingsService.transitionForUser(user.id, id, 'CONFIRMED');
  }

  @Patch(':id/cancel')
  async cancel(
    @CurrentUser() user: RequestUser,
    @Param('id') id: string,
  ) {
    return this.bookingsService.transitionForUser(user.id, id, 'CANCELLED');
  }

  @Post(':id/rebook')
  async rebook(
    @CurrentUser() user: RequestUser,
    @Param('id') id: string,
    @Body() body: Record<string, unknown>,
  ) {
    return this.bookingsService.rebookForUser(user.id, id, body);
  }

  @Get(':id/rebook/template')
  async rebookTemplate(
    @CurrentUser() user: RequestUser,
    @Param('id') id: string,
  ) {
    return this.bookingsService.getRebookTemplateForUser(user.id, id);
  }

  @Patch(':id/refund')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE')
  async refund(@Param('id') id: string) {
    return this.bookingsService.refundBooking(id);
  }
}

import { Body, Controller, Get, Param, Post, Query, Req, UseGuards } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { WriteRateLimit } from '../security/rate-limit.decorator';
import { WriteRateLimitGuard } from '../security/rate-limit.guard';
import { ReviewsService } from './reviews.service';

@Controller('reviews')
@FeatureDomain('reviews')
export class ReviewsController {
  constructor(private readonly reviewsService: ReviewsService) {}

  @Get('admin/moderation')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  async listModerationQueue(@Query('status') status?: string) {
    return this.reviewsService.listModerationQueue(status);
  }

  @Get('accommodations/:id')
  @Public()
  async listAccommodationReviews(@Param('id') id: string) {
    return this.reviewsService.listAccommodationReviews(id);
  }

  @Get('transports/:id')
  @Public()
  async listTransportReviews(@Param('id') id: string) {
    return this.reviewsService.listTransportReviews(id);
  }

  @Post()
  @Roles('USER', 'ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE', 'VENDOR')
  @UseGuards(WriteRateLimitGuard)
  @WriteRateLimit({ key: 'reviews:create', max: 10, windowMs: 60_000 })
  async create(@Body() body: Record<string, unknown>, @Req() request: any) {
    return this.reviewsService.create(body, request.user);
  }

  @Post(':id/flag')
  @Roles('USER', 'ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE', 'VENDOR')
  @UseGuards(WriteRateLimitGuard)
  @WriteRateLimit({ key: 'reviews:flag', max: 10, windowMs: 60_000 })
  async flag(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.reviewsService.flag(id, request.user, body);
  }

  @Post('admin/:id/hide')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  @UseGuards(WriteRateLimitGuard)
  @WriteRateLimit({ key: 'reviews:admin:hide', max: 30, windowMs: 60_000 })
  async hide(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.reviewsService.setStatus(id, 'HIDDEN', request.user, body);
  }

  @Post('admin/:id/publish')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE')
  @UseGuards(WriteRateLimitGuard)
  @WriteRateLimit({ key: 'reviews:admin:publish', max: 30, windowMs: 60_000 })
  async publish(@Param('id') id: string, @Body() body: Record<string, unknown>, @Req() request: any) {
    return this.reviewsService.setStatus(id, 'PUBLISHED', request.user, body);
  }
}

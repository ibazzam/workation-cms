import { Body, Controller, Delete, Get, HttpCode, Param, Post, Put, Query } from '@nestjs/common';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { LoyaltyService } from './loyalty.service';

type RequestUser = {
  id: string;
  role: 'USER' | 'VENDOR' | 'ADMIN' | 'ADMIN_SUPER' | 'ADMIN_CARE' | 'ADMIN_FINANCE';
  vendorId?: string;
};

@Controller('loyalty')
@FeatureDomain('loyalty')
export class LoyaltyController {
  constructor(private readonly loyaltyService: LoyaltyService) {}

  @Get('me')
  async getMyWallet(@CurrentUser() user: RequestUser) {
    return this.loyaltyService.getWallet(user.id);
  }

  @Get('me/transactions')
  async getMyTransactions(@CurrentUser() user: RequestUser, @Query('limit') limit?: string) {
    return this.loyaltyService.getTransactions(user.id, { limit });
  }

  @Post('me/redeem')
  @HttpCode(200)
  async redeem(@CurrentUser() user: RequestUser, @Body() body: Record<string, unknown>) {
    return this.loyaltyService.redeemForBooking(user.id, body);
  }

  @Get('offers/vendors/:vendorId')
  @Public()
  async listVendorOffers(@Param('vendorId') vendorId: string) {
    return this.loyaltyService.listVendorOffers(vendorId);
  }

  @Post('offers/admin')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async createOffer(@CurrentUser() user: RequestUser, @Body() body: Record<string, unknown>) {
    return this.loyaltyService.createVendorOffer(body, user);
  }

  @Put('offers/admin/:id')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async updateOffer(@CurrentUser() user: RequestUser, @Param('id') id: string, @Body() body: Record<string, unknown>) {
    return this.loyaltyService.updateVendorOffer(id, body, user);
  }

  @Delete('offers/admin/:id')
  @HttpCode(204)
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'VENDOR')
  async removeOffer(@CurrentUser() user: RequestUser, @Param('id') id: string) {
    await this.loyaltyService.removeVendorOffer(id, user);
    return null;
  }
}

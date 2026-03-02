import { Body, Controller, Delete, Get, Param, Post, Query } from '@nestjs/common';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { CartService } from './cart.service';

type RequestUser = {
  id: string;
  role: 'USER' | 'VENDOR' | 'ADMIN' | 'ADMIN_SUPER' | 'ADMIN_CARE' | 'ADMIN_FINANCE';
};

@Controller('cart')
@FeatureDomain('bookings')
export class CartController {
  constructor(private readonly cartService: CartService) {}

  @Get()
  async getMine(@CurrentUser() user: RequestUser) {
    return this.cartService.getCartForUser(user.id);
  }

  @Post('items')
  async addItem(@CurrentUser() user: RequestUser, @Body() body: Record<string, unknown>) {
    return this.cartService.addItemForUser(user.id, body);
  }

  @Delete('items/:itemId')
  async removeItem(@CurrentUser() user: RequestUser, @Param('itemId') itemId: string) {
    return this.cartService.removeItemForUser(user.id, itemId);
  }

  @Post('checkout')
  async checkout(
    @CurrentUser() user: RequestUser,
    @Body() body: Record<string, unknown>,
    @Query('clear') clear?: string,
  ) {
    const clearCart = clear === undefined ? true : clear.toLowerCase() !== 'false';
    return this.cartService.checkoutForUser(user.id, body, { clearCart });
  }
}

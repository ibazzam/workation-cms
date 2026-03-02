import { Body, Controller, Get, Put } from '@nestjs/common';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { UsersService } from './users.service';

type RequestUser = {
  id: string;
};

@Controller('users')
export class UsersController {
  constructor(private readonly usersService: UsersService) {}

  @Get('me/profile')
  async getOwnProfile(@CurrentUser() user: RequestUser) {
    return this.usersService.getProfile(user.id);
  }

  @Put('me/profile')
  async updateOwnProfile(@CurrentUser() user: RequestUser, @Body() body: Record<string, unknown>) {
    return this.usersService.updateProfile(user.id, body);
  }
}

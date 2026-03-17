import { Body, Controller, Get, Put, Post, Logger, InternalServerErrorException } from '@nestjs/common';
import { EmailService } from '../email/email.service';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { UsersService } from './users.service';

type RequestUser = {
  id: string;
};

@Controller('users')
export class UsersController {

  private readonly logger = new Logger(UsersController.name);

  constructor(
    private readonly usersService: UsersService,
    private readonly emailService: EmailService,
  ) {}

  @Post('admin/create')
  async adminCreateUser(@Body() body: { email: string; name?: string }) {
    try {
      // 1. Create user (if not exists)
      const user = await this.usersService.createUserWithResetToken(body.email, body.name);
      // 2. Generate reset link
      const resetLink = `${process.env.FRONTEND_URL || 'https://workation.mv'}/reset-password?token=${user.resetToken}`;
      // 3. Send welcome email
      await this.emailService.sendWelcome(user.email, user.name || '', resetLink);
      return { success: true };
    } catch (error) {
      this.logger.error('Failed to create admin user', error.stack || error.message || error);
      // Return error message to frontend for debugging
      throw new InternalServerErrorException(error.message || 'Failed to create user');
    }
  }

  @Get('me/profile')
  async getOwnProfile(@CurrentUser() user: RequestUser) {
    return this.usersService.getProfile(user.id);
  }

  @Put('me/profile')
  async updateOwnProfile(@CurrentUser() user: RequestUser, @Body() body: Record<string, unknown>) {
    return this.usersService.updateProfile(user.id, body);
  }
}
import { Controller, Get } from '@nestjs/common';
import { CurrentUser } from './decorators/current-user.decorator';
import { Roles } from './decorators/roles.decorator';
import { AuthService } from './auth.service';

type RequestUser = {
  id: string;
  role: 'USER' | 'VENDOR' | 'ADMIN' | 'ADMIN_SUPER' | 'ADMIN_CARE' | 'ADMIN_FINANCE';
  email?: string;
};

@Controller('auth')
export class AuthController {
  constructor(private readonly authService: AuthService) {}

  @Get('me')
  async me(@CurrentUser() user: RequestUser) {
    return this.authService.getMe(user.id);
  }

  @Get('admin/ping')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE')
  adminPing() {
    return { status: 'ok', scope: 'admin' };
  }
}

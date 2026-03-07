import { SetMetadata } from '@nestjs/common';
import { ROLES_KEY } from '../auth.constants';
import { AuthRole } from '../../users/users.service';

export const Roles = (...roles: AuthRole[]) => SetMetadata(ROLES_KEY, roles);

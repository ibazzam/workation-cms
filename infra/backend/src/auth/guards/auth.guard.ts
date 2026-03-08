import { CanActivate, ExecutionContext, Injectable, UnauthorizedException } from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import { PUBLIC_ROUTE_KEY, AUTH_USER_EMAIL_HEADER, AUTH_USER_ID_HEADER, AUTH_USER_ROLE_HEADER, AUTH_VENDOR_ID_HEADER } from '../auth.constants';
import { AuthContext, AuthRole, UsersService } from '../../users/users.service';
import { parseBearerToken, verifyHs256Jwt } from '../jwt.util';

const ALLOWED_ROLES: AuthRole[] = [
  'USER',
  'VENDOR',
  'ADMIN',
  'ADMIN_SUPER',
  'ADMIN_CARE',
  'ADMIN_FINANCE',
];

@Injectable()
export class AuthGuard implements CanActivate {
  constructor(
    private readonly reflector: Reflector,
    private readonly usersService: UsersService,
  ) {}

  async canActivate(context: ExecutionContext): Promise<boolean> {
    const isPublic = this.reflector.getAllAndOverride<boolean>(PUBLIC_ROUTE_KEY, [
      context.getHandler(),
      context.getClass(),
    ]);

    if (isPublic) {
      return true;
    }

    const request = context.switchToHttp().getRequest();
    const allowHeaderFallback = (process.env.AUTH_ALLOW_HEADER_FALLBACK ?? 'false').toLowerCase() === 'true';
    const allowHeaderFallbackInProduction = (process.env.AUTH_ALLOW_HEADER_FALLBACK_IN_PRODUCTION ?? 'false').toLowerCase() === 'true';
    const isProduction = (process.env.NODE_ENV ?? '').toLowerCase() === 'production';
    const authorizationHeader = request.headers.authorization;
    const authorization = Array.isArray(authorizationHeader) ? authorizationHeader[0] : authorizationHeader;
    const bearerToken = parseBearerToken(typeof authorization === 'string' ? authorization : undefined);

    if (bearerToken) {
      const jwtSecret = process.env.AUTH_JWT_SECRET;
      if (!jwtSecret) {
        throw new UnauthorizedException('AUTH_JWT_SECRET is not configured');
      }

      let claims: Record<string, unknown>;
      try {
        claims = verifyHs256Jwt(bearerToken, jwtSecret) as Record<string, unknown>;
      } catch (error) {
        throw new UnauthorizedException(error instanceof Error ? error.message : 'Invalid bearer token');
      }

      const rawId = claims.sub ?? claims.userId ?? claims.id;
      const rawRole = claims.role;
      const rawEmail = claims.email;
      const rawVendorId = claims.vendorId;

      if (typeof rawId !== 'string' || rawId.trim().length === 0) {
        throw new UnauthorizedException('Bearer token is missing subject (sub)');
      }

      if (typeof rawRole !== 'string') {
        throw new UnauthorizedException('Bearer token is missing role');
      }

      const role = rawRole.trim().toUpperCase() as AuthRole;
      if (!ALLOWED_ROLES.includes(role)) {
        throw new UnauthorizedException('Invalid role in bearer token');
      }

      const authContext: AuthContext = {
        id: rawId.trim(),
        role,
        email: typeof rawEmail === 'string' ? rawEmail : undefined,
        vendorId: typeof rawVendorId === 'string' && rawVendorId.trim().length > 0 ? rawVendorId.trim() : undefined,
      };

      const ensuredUser = await this.usersService.ensureUserFromAuthContext(authContext);
      request.user = {
        id: ensuredUser.id,
        role: ensuredUser.role,
        email: ensuredUser.email,
        vendorId: authContext.vendorId,
      };

      return true;
    }

    if (!allowHeaderFallback || (isProduction && !allowHeaderFallbackInProduction)) {
      throw new UnauthorizedException('Header-based auth fallback is disabled; use bearer token');
    }

    const userIdHeader = request.headers[AUTH_USER_ID_HEADER];
    const userRoleHeader = request.headers[AUTH_USER_ROLE_HEADER];
    const userEmailHeader = request.headers[AUTH_USER_EMAIL_HEADER];
    const vendorIdHeader = request.headers[AUTH_VENDOR_ID_HEADER];

    const userId = Array.isArray(userIdHeader) ? userIdHeader[0] : userIdHeader;
    const rawRole = Array.isArray(userRoleHeader) ? userRoleHeader[0] : userRoleHeader;
    const userEmail = Array.isArray(userEmailHeader) ? userEmailHeader[0] : userEmailHeader;
    const vendorId = Array.isArray(vendorIdHeader) ? vendorIdHeader[0] : vendorIdHeader;

    if (!userId || typeof userId !== 'string' || userId.trim().length === 0) {
      throw new UnauthorizedException('Missing authentication header: x-user-id');
    }

    if (!rawRole || typeof rawRole !== 'string') {
      throw new UnauthorizedException('Missing authentication header: x-user-role');
    }

    const role = rawRole.trim().toUpperCase() as AuthRole;
    if (!ALLOWED_ROLES.includes(role)) {
      throw new UnauthorizedException('Invalid role in x-user-role header');
    }

    const authContext: AuthContext = {
      id: userId.trim(),
      role,
      email: typeof userEmail === 'string' ? userEmail : undefined,
      vendorId: typeof vendorId === 'string' && vendorId.trim().length > 0 ? vendorId.trim() : undefined,
    };

    const ensuredUser = await this.usersService.ensureUserFromAuthContext(authContext);
    request.user = {
      id: ensuredUser.id,
      role: ensuredUser.role,
      email: ensuredUser.email,
      vendorId: authContext.vendorId,
    };

    return true;
  }
}

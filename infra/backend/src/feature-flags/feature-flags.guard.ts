import { CanActivate, ExecutionContext, Injectable, ServiceUnavailableException } from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import { FEATURE_DOMAIN_KEY } from './feature-domain.decorator';
import { FeatureFlagsService } from './feature-flags.service';

@Injectable()
export class FeatureFlagsGuard implements CanActivate {
  constructor(
    private readonly reflector: Reflector,
    private readonly featureFlagsService: FeatureFlagsService,
  ) {}

  canActivate(context: ExecutionContext): boolean {
    const domain = this.reflector.getAllAndOverride<string>(FEATURE_DOMAIN_KEY, [
      context.getHandler(),
      context.getClass(),
    ]);

    if (!domain) {
      return true;
    }

    if (!this.featureFlagsService.isDomainEnabled(domain)) {
      throw new ServiceUnavailableException(`Domain feature "${domain}" is disabled`);
    }

    return true;
  }
}

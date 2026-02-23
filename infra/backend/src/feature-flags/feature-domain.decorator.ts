import { SetMetadata } from '@nestjs/common';

export const FEATURE_DOMAIN_KEY = 'featureDomain';

export const FeatureDomain = (domain: string) => SetMetadata(FEATURE_DOMAIN_KEY, domain);

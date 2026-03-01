import { Injectable } from '@nestjs/common';

@Injectable()
export class FeatureFlagsService {
  isDomainEnabled(domain: string): boolean {
    const normalizedDomainKey = this.normalizeDomain(domain);
    const specificFlag = this.readBoolean(process.env[`FEATURE_DOMAIN_${normalizedDomainKey}`]);
    if (specificFlag !== undefined) {
      return specificFlag;
    }

    const defaultFlag = this.readBoolean(process.env.FEATURE_FLAGS_DEFAULT);
    return defaultFlag ?? true;
  }

  private normalizeDomain(domain: string): string {
    return domain.trim().toUpperCase().replace(/[^A-Z0-9]+/g, '_');
  }

  private readBoolean(value: string | undefined): boolean | undefined {
    if (value === undefined) {
      return undefined;
    }

    const normalized = value.trim().toLowerCase();
    if (normalized === 'true' || normalized === '1' || normalized === 'yes' || normalized === 'on') {
      return true;
    }

    if (normalized === 'false' || normalized === '0' || normalized === 'no' || normalized === 'off') {
      return false;
    }

    return undefined;
  }
}

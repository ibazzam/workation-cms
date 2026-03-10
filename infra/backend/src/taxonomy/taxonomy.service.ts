import { BadRequestException, Injectable } from '@nestjs/common';
import { PrismaService } from '../prisma.service';

type CanonicalDomain = 'ACCOMMODATION' | 'TRANSPORT' | 'ACTIVITY';
type ServiceCategoryScope = 'ACCOMMODATION' | 'TRANSPORT' | 'BOTH' | 'ACTIVITY';

type CanonicalCategory = {
  code: string;
  name: string;
  domain: CanonicalDomain;
  active: boolean;
  origin: 'BASELINE' | 'CUSTOM';
};

type CanonicalDomainBucket = {
  domain: CanonicalDomain;
  merged: Map<string, CanonicalCategory>;
};

const DOMAIN_ORDER: CanonicalDomain[] = ['ACCOMMODATION', 'TRANSPORT', 'ACTIVITY'];

const BASELINE_CATEGORIES: Record<CanonicalDomain, Array<{ code: string; name: string }>> = {
  ACCOMMODATION: [
    { code: 'GUESTHOUSE', name: 'Guesthouse' },
    { code: 'BOUTIQUE_HOTEL', name: 'Boutique Hotel' },
    { code: 'RESORT_VILLA', name: 'Resort Villa' },
    { code: 'SERVICED_APARTMENT', name: 'Serviced Apartment' },
    { code: 'LIVEABOARD', name: 'Liveaboard' },
  ],
  TRANSPORT: [
    { code: 'PUBLIC_FERRY', name: 'Public Ferry' },
    { code: 'SPEEDBOAT_FERRY', name: 'Speedboat Ferry' },
    { code: 'PRIVATE_SPEEDBOAT', name: 'Private Speedboat' },
    { code: 'DOMESTIC_FLIGHT', name: 'Domestic Flight' },
    { code: 'SEAPLANE', name: 'Seaplane' },
  ],
  ACTIVITY: [
    { code: 'DIVING', name: 'Diving' },
    { code: 'SNORKELING', name: 'Snorkeling' },
    { code: 'SURFING', name: 'Surfing' },
    { code: 'ISLAND_HOPPING', name: 'Island Hopping' },
    { code: 'FISHING', name: 'Fishing' },
    { code: 'WELLNESS', name: 'Wellness' },
    { code: 'CULTURAL_TOUR', name: 'Cultural Tour' },
    { code: 'WATER_SPORTS', name: 'Water Sports' },
  ],
};

@Injectable()
export class TaxonomyService {
  constructor(private readonly prisma: PrismaService) {}

  async listCanonicalCategories(filters: { domain?: CanonicalDomain; active?: boolean | undefined }) {
    const domainFilter = this.parseDomainFilter(filters.domain);
    const requestedDomains = domainFilter ? [domainFilter] : DOMAIN_ORDER;

    const customCategories = await this.prisma.serviceCategory.findMany({
      orderBy: [{ scope: 'asc' }, { name: 'asc' }],
    });

    const domainBuckets = new Map<CanonicalDomain, CanonicalDomainBucket>();
    for (const domain of requestedDomains) {
      const merged = new Map<string, CanonicalCategory>();

      for (const baseline of BASELINE_CATEGORIES[domain]) {
        merged.set(baseline.code, {
          code: baseline.code,
          name: baseline.name,
          domain,
          active: true,
          origin: 'BASELINE',
        });
      }

      domainBuckets.set(domain, { domain, merged });
    }

    for (const category of customCategories) {
      const categoryDomains = this.mapScopeToDomains(category.scope);
      for (const categoryDomain of categoryDomains) {
        const bucket = domainBuckets.get(categoryDomain);
        if (!bucket) {
          continue;
        }

        bucket.merged.set(category.code, {
          code: category.code,
          name: category.name,
          domain: categoryDomain,
          active: category.active,
          origin: 'CUSTOM',
        });
      }
    }

    const domains = requestedDomains.map((domain) => {
      const merged = domainBuckets.get(domain)?.merged ?? new Map<string, CanonicalCategory>();
      const categories = Array.from(merged.values())
        .filter((entry) => (filters.active === undefined ? true : entry.active === filters.active))
        .sort((a, b) => a.name.localeCompare(b.name));

      return {
        domain,
        categories,
      };
    });

    return {
      items: domains,
    };
  }

  private parseDomainFilter(domain: CanonicalDomain | undefined): CanonicalDomain | undefined {
    if (domain === undefined) {
      return undefined;
    }

    if (domain === 'ACCOMMODATION' || domain === 'TRANSPORT' || domain === 'ACTIVITY') {
      return domain;
    }

    throw new BadRequestException('domain must be ACCOMMODATION, TRANSPORT, or ACTIVITY');
  }

  private mapScopeToDomains(scope: string): CanonicalDomain[] {
    if (scope === 'ACCOMMODATION') return ['ACCOMMODATION'];
    if (scope === 'TRANSPORT') return ['TRANSPORT'];
    if (scope === 'ACTIVITY') return ['ACTIVITY'];
    if (scope === 'BOTH') return ['ACCOMMODATION', 'TRANSPORT'];
    return [];
  }
}

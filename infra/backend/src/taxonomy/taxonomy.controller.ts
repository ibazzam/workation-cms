import { Controller, Get, Query } from '@nestjs/common';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { TaxonomyService } from './taxonomy.service';

@Controller('taxonomy')
@FeatureDomain('taxonomy')
export class TaxonomyController {
  constructor(private readonly taxonomyService: TaxonomyService) {}

  @Get('categories')
  @Public()
  async listCategories(@Query('domain') domain?: string, @Query('active') active?: string) {
    const domainFilter = domain ? domain.toUpperCase() as 'ACCOMMODATION' | 'TRANSPORT' | 'ACTIVITY' : undefined;
    const activeFilter = active === undefined ? undefined : active.toLowerCase() === 'true';

    return this.taxonomyService.listCanonicalCategories({
      domain: domainFilter,
      active: activeFilter,
    });
  }
}

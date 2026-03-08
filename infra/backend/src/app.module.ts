import { Module } from '@nestjs/common';
import { APP_GUARD } from '@nestjs/core';
import { AccommodationsModule } from './accommodations/accommodations.module';
import { AuditModule } from './audit/audit.module';
import { AdminSettingsModule } from './admin-settings/admin-settings.module';
import { AuthModule } from './auth/auth.module';
import { AuthGuard } from './auth/guards/auth.guard';
import { RolesGuard } from './auth/guards/roles.guard';
import { BookingsModule } from './bookings/bookings.module';
import { CartModule } from './cart/cart.module';
import { CountriesModule } from './countries/countries.module';
import { FeatureFlagsGuard } from './feature-flags/feature-flags.guard';
import { FeatureFlagsService } from './feature-flags/feature-flags.service';
import { HealthController } from './health.controller';
import { IslandsModule } from './islands/islands.module';
import { PaymentsModule } from './payments/payments.module';
import { PrismaService } from './prisma.service';
import { LoyaltyModule } from './loyalty/loyalty.module';
import { ObservabilityModule } from './observability/observability.module';
import { ReviewsModule } from './reviews/reviews.module';
import { SocialLinksModule } from './social-links/social-links.module';
import { TransportsModule } from './transports/transports.module';
import { UsersModule } from './users/users.module';
import { VendorsModule } from './vendors/vendors.module';
import { ServiceCategoriesModule } from './service-categories/service-categories.module';
import { WorkationsModule } from './workations/workations.module';

@Module({
  imports: [
    UsersModule,
    AuthModule,
    CountriesModule,
    IslandsModule,
    AccommodationsModule,
    AuditModule,
    AdminSettingsModule,
    TransportsModule,
    BookingsModule,
    CartModule,
    PaymentsModule,
    ObservabilityModule,
    LoyaltyModule,
    ReviewsModule,
    SocialLinksModule,
    WorkationsModule,
    VendorsModule,
    ServiceCategoriesModule,
  ],
  controllers: [HealthController],
  providers: [
    PrismaService,
    FeatureFlagsService,
    {
      provide: APP_GUARD,
      useClass: FeatureFlagsGuard,
    },
    {
      provide: APP_GUARD,
      useClass: AuthGuard,
    },
    {
      provide: APP_GUARD,
      useClass: RolesGuard,
    },
  ],
})
export class AppModule {}

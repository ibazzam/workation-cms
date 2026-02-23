import { Module } from '@nestjs/common';
import { LoyaltyModule } from '../loyalty/loyalty.module';
import { PrismaService } from '../prisma.service';
import { BankOfMaldivesAdapter } from './adapters/bank-of-maldives.adapter';
import { MaldivesIslamicBankAdapter } from './adapters/maldives-islamic-bank.adapter';
import { PAYMENT_PROVIDER_BML, PAYMENT_PROVIDER_MIB, PAYMENT_PROVIDER_STRIPE } from './adapters/payment-provider.tokens';
import { PaymentsBackgroundJobsRunner } from './payments-background-jobs.runner';
import { StripeMockAdapter } from './adapters/stripe-mock.adapter';
import { PaymentsController } from './payments.controller';
import { PaymentsReconciliationRunner } from './payments-reconciliation.runner';
import { PaymentsService } from './payments.service';

@Module({
  imports: [LoyaltyModule],
  controllers: [PaymentsController],
  providers: [
    PaymentsService,
    PaymentsReconciliationRunner,
    PaymentsBackgroundJobsRunner,
    PrismaService,
    StripeMockAdapter,
    BankOfMaldivesAdapter,
    MaldivesIslamicBankAdapter,
    { provide: PAYMENT_PROVIDER_STRIPE, useExisting: StripeMockAdapter },
    { provide: PAYMENT_PROVIDER_BML, useExisting: BankOfMaldivesAdapter },
    { provide: PAYMENT_PROVIDER_MIB, useExisting: MaldivesIslamicBankAdapter },
  ],
})
export class PaymentsModule {}

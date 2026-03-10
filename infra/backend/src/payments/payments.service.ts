import { BadRequestException, ForbiddenException, Inject, Injectable, NotFoundException, ServiceUnavailableException, UnauthorizedException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { LoyaltyService } from '../loyalty/loyalty.service';
import { PrismaService } from '../prisma.service';
import { PaymentProviderAdapter, ProviderTransactionStatus } from './adapters/payment-provider.interface';
import { PAYMENT_PROVIDER_BML, PAYMENT_PROVIDER_MIB, PAYMENT_PROVIDER_STRIPE } from './adapters/payment-provider.tokens';

type CreateIntentPayload = {
  bookingId?: unknown;
  provider?: unknown;
  currency?: unknown;
};

type ReconcilePayload = {
  provider?: unknown;
  limit?: unknown;
  dryRun?: unknown;
};

type ReconcileSource = 'ADMIN' | 'SCHEDULER';

type ProviderName = 'STRIPE' | 'BML' | 'MIB';
type BackgroundJobType = 'BOOKING_CONFIRMATION_NOTIFICATION' | 'WEBHOOK_PROCESS_RETRY';

type BackgroundJobsRunnerSnapshot = {
  enabled: boolean;
  running: boolean;
  intervalMs: number;
  lastTickFinishedAt: string | null;
  lastTickError: string | null;
  lastPruneError: string | null;
};

type RefundRequestPayload = {
  bookingId?: unknown;
  paymentId?: unknown;
  amount?: unknown;
  reason?: unknown;
  idempotencyKey?: unknown;
};

type DisputeRequestPayload = {
  bookingId?: unknown;
  paymentId?: unknown;
  reason?: unknown;
  details?: unknown;
  idempotencyKey?: unknown;
};

type SettlementReportPayload = {
  from?: unknown;
  to?: unknown;
  provider?: unknown;
  currency?: unknown;
};

type VendorSettlementReportPayload = {
  from?: unknown;
  to?: unknown;
  provider?: unknown;
  currency?: unknown;
};

type LedgerSyncPayload = {
  from?: unknown;
  to?: unknown;
};

type LedgerReportPayload = {
  from?: unknown;
  to?: unknown;
  provider?: unknown;
  currency?: unknown;
  entryType?: unknown;
  limit?: unknown;
  offset?: unknown;
};

type TaxInvoiceGeneratePayload = {
  bookingId?: unknown;
  paymentId?: unknown;
  idempotencyKey?: unknown;
  taxRate?: unknown;
  issuerName?: unknown;
  issuerTaxId?: unknown;
  notes?: unknown;
};

type TaxInvoiceListPayload = {
  bookingId?: unknown;
  paymentId?: unknown;
  customerEmail?: unknown;
  invoiceNumber?: unknown;
  limit?: unknown;
  offset?: unknown;
};

type RefundQueuePayload = {
  status?: unknown;
  bookingId?: unknown;
  paymentId?: unknown;
  limit?: unknown;
  offset?: unknown;
};

type DisputeQueuePayload = {
  status?: unknown;
  bookingId?: unknown;
  paymentId?: unknown;
  limit?: unknown;
  offset?: unknown;
};

type RefundStatusUpdatePayload = {
  status?: unknown;
  note?: unknown;
};

type DisputeStatusUpdatePayload = {
  status?: unknown;
  note?: unknown;
};

type RefundRecordStatus = 'PENDING' | 'APPROVED' | 'COMPLETED' | 'REJECTED' | 'FAILED';
type DisputeRecordStatus = 'OPEN' | 'UNDER_REVIEW' | 'RESOLVED' | 'REJECTED';
type FinanceLedgerEntryType = 'PAYMENT_CAPTURED' | 'REFUND_COMPLETED';

type FinanceLedgerEntry = {
  id: string;
  externalRef: string;
  entryType: FinanceLedgerEntryType;
  bookingId: string;
  paymentId: string;
  provider: string;
  currency: string;
  grossAmount: number;
  taxAmount: number;
  netAmount: number;
  occurredAt: string;
  source: string;
  createdAt: string;
};

type TaxInvoiceRecord = {
  id: string;
  invoiceNumber: string;
  bookingId: string;
  paymentId: string;
  userId: string;
  customerName: string | null;
  customerEmail: string | null;
  provider: string;
  currency: string;
  subtotalAmount: number;
  taxRate: number;
  taxAmount: number;
  totalAmount: number;
  status: 'ISSUED' | 'VOID';
  issuerName: string;
  issuerTaxId: string;
  notes: string | null;
  idempotencyKey: string | null;
  issuedAt: string;
  createdByUserId: string;
  createdByRole: string;
  createdAt: string;
  updatedAt: string;
};

type RefundRecord = {
  id: string;
  bookingId: string;
  paymentId: string;
  provider: string;
  currency: string;
  requestedAmount: number;
  reason: string | null;
  status: RefundRecordStatus;
  idempotencyKey: string | null;
  actorUserId: string;
  actorRole: string;
  resolvedByUserId?: string;
  resolvedByRole?: string;
  resolutionNote?: string | null;
  resolvedAt?: string | null;
  createdAt: string;
  updatedAt: string;
};

type DisputeRecord = {
  id: string;
  bookingId: string;
  paymentId: string;
  provider: string;
  reason: string;
  details: string | null;
  status: DisputeRecordStatus;
  idempotencyKey: string | null;
  actorUserId: string;
  actorRole: string;
  resolvedByUserId?: string;
  resolvedByRole?: string;
  resolutionNote?: string | null;
  resolvedAt?: string | null;
  createdAt: string;
  updatedAt: string;
};

type ProviderCircuitState = {
  provider: ProviderName;
  consecutiveFailures: number;
  openedAt: string | null;
  openUntil: string | null;
  lastError: string | null;
  lastFailureAt: string | null;
  lastSuccessAt: string | null;
};

type DeadLetterEscalationRecord = {
  id: string;
  type: string;
  jobId: string;
  attempts: number;
  maxAttempts: number;
  escalatedAt: string;
  error: string;
};

@Injectable()
export class PaymentsService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly loyaltyService: LoyaltyService,
    @Inject(PAYMENT_PROVIDER_STRIPE) private readonly stripeAdapter: PaymentProviderAdapter,
    @Inject(PAYMENT_PROVIDER_BML) private readonly bmlAdapter: PaymentProviderAdapter,
    @Inject(PAYMENT_PROVIDER_MIB) private readonly mibAdapter: PaymentProviderAdapter,
  ) {}

  async createIntentForUser(userId: string, payload: CreateIntentPayload) {
    const bookingId = typeof payload.bookingId === 'string' ? payload.bookingId : undefined;
    if (!bookingId) {
      throw new BadRequestException('bookingId is required');
    }

    const provider = this.parseProvider(payload.provider);
    if (!provider) {
      throw new BadRequestException('Unsupported provider. Allowed providers: STRIPE, BML, MIB');
    }

    const currency = this.parseCurrency(payload.currency);
    if (!currency) {
      throw new BadRequestException('Unsupported currency. Allowed currencies: USD, MVR');
    }

    const booking = await this.prisma.booking.findUnique({ where: { id: bookingId } });
    if (!booking) {
      throw new NotFoundException('Booking not found');
    }

    if (booking.userId !== userId) {
      throw new ForbiddenException('Booking does not belong to authenticated user');
    }

    if (booking.status === 'DRAFT') {
      throw new BadRequestException('Booking must be on HOLD before payment intent creation');
    }

    if (booking.status === 'CANCELLED' || booking.status === 'REFUNDED') {
      throw new BadRequestException(`Cannot create payment intent for booking status ${booking.status}`);
    }

    if (booking.status === 'HOLD' && booking.holdExpiresAt && booking.holdExpiresAt.getTime() < Date.now()) {
      await this.prisma.booking.update({
        where: { id: booking.id },
        data: {
          status: 'CANCELLED',
          holdExpiresAt: null,
          fareLockExpiresAt: null,
        },
      });
      throw new BadRequestException('Booking hold has expired and was cancelled');
    }

    if (booking.status === 'HOLD' && booking.fareLockExpiresAt && booking.fareLockExpiresAt.getTime() < Date.now()) {
      await this.prisma.booking.update({
        where: { id: booking.id },
        data: {
          status: 'CANCELLED',
          holdExpiresAt: null,
          fareLockExpiresAt: null,
        },
      });
      throw new BadRequestException('Transport fare lock has expired and booking was cancelled');
    }

    const payableAmount = booking.fareLockTotalPrice ?? booking.totalPrice;

    const existingPayment = await this.prisma.payment.findUnique({ where: { bookingId } });
    if (existingPayment && existingPayment.provider === provider) {
      return {
        created: false,
        payment: existingPayment,
      };
    }

    const adapter = this.getAdapter(provider);
    const intent = await this.createIntentWithHardening(provider, adapter, {
      bookingId,
      amount: Number(payableAmount),
      currency,
      metadata: { bookingId },
    });

    const payment = await this.prisma.payment.upsert({
      where: { bookingId },
      update: {
        provider,
        providerId: intent.providerIntentId,
        amount: payableAmount,
        currency,
        status: intent.status,
      },
      create: {
        bookingId,
        provider,
        providerId: intent.providerIntentId,
        amount: payableAmount,
        currency,
        status: intent.status,
      },
    });

    return {
      created: true,
      payment,
      clientSecret: intent.clientSecret,
    };
  }

  async createRefundRequestForUser(userId: string, payload: RefundRequestPayload) {
    const normalized = this.parseRefundRequestPayload(payload);
    const payment = await this.resolvePaymentForAdjustment(normalized.bookingId, normalized.paymentId);
    const booking = await this.prisma.booking.findUnique({ where: { id: payment.bookingId } });
    if (!booking) {
      throw new NotFoundException('Booking not found');
    }

    if (booking.userId !== userId) {
      throw new ForbiddenException('Booking does not belong to authenticated user');
    }

    return this.createRefundRequest(payment, normalized, {
      actorUserId: userId,
      actorRole: 'USER',
    });
  }

  async createRefundRequestAsAdmin(
    payload: RefundRequestPayload,
    actor: { id: string; role: string },
  ) {
    const normalized = this.parseRefundRequestPayload(payload);
    const payment = await this.resolvePaymentForAdjustment(normalized.bookingId, normalized.paymentId);
    return this.createRefundRequest(payment, normalized, {
      actorUserId: actor.id,
      actorRole: actor.role,
    });
  }

  async createDisputeForUser(userId: string, payload: DisputeRequestPayload) {
    const normalized = this.parseDisputePayload(payload);
    const payment = await this.resolvePaymentForAdjustment(normalized.bookingId, normalized.paymentId);
    const booking = await this.prisma.booking.findUnique({ where: { id: payment.bookingId } });
    if (!booking) {
      throw new NotFoundException('Booking not found');
    }

    if (booking.userId !== userId) {
      throw new ForbiddenException('Booking does not belong to authenticated user');
    }

    if (payment.status !== 'SUCCEEDED') {
      throw new BadRequestException('Disputes can only be opened for succeeded payments');
    }

    const disputes = await this.readDisputeRecords();
    if (normalized.idempotencyKey) {
      const idempotentExisting = disputes.find((record) => record.idempotencyKey === normalized.idempotencyKey);
      if (idempotentExisting) {
        return {
          created: false,
          dispute: idempotentExisting,
        };
      }
    }

    const openExisting = disputes.find((record) => record.paymentId === payment.id && ['OPEN', 'UNDER_REVIEW'].includes(record.status));
    if (openExisting) {
      return {
        created: false,
        dispute: openExisting,
      };
    }

    const nowIso = new Date().toISOString();
    const created: DisputeRecord = {
      id: `dispute_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
      bookingId: payment.bookingId,
      paymentId: payment.id,
      provider: payment.provider,
      reason: normalized.reason,
      details: normalized.details,
      status: 'OPEN',
      idempotencyKey: normalized.idempotencyKey,
      actorUserId: userId,
      actorRole: 'USER',
      createdAt: nowIso,
      updatedAt: nowIso,
    };

    await this.writeDisputeRecords([created, ...disputes]);

    return {
      created: true,
      dispute: created,
    };
  }

  async listRefundRequests(payload: RefundQueuePayload = {}) {
    const statusFilter = this.parseRefundStatusFilter(payload.status);
    const bookingId = this.parseOptionalId(payload.bookingId);
    const paymentId = this.parseOptionalId(payload.paymentId);
    const { limit, offset } = this.parsePagination(payload.limit, payload.offset, 200);

    const refunds = await this.readRefundRecords();
    const filtered = refunds.filter((record) => {
      if (statusFilter && record.status !== statusFilter) {
        return false;
      }

      if (bookingId && record.bookingId !== bookingId) {
        return false;
      }

      if (paymentId && record.paymentId !== paymentId) {
        return false;
      }

      return true;
    });

    const items = filtered.slice(offset, offset + limit);
    return {
      total: filtered.length,
      limit,
      offset,
      items,
    };
  }

  async updateRefundRequestStatus(
    refundId: string,
    payload: RefundStatusUpdatePayload,
    actor: { id: string; role: string },
  ) {
    const normalizedId = this.parseRequiredText(refundId, 'refund id', 120);
    const nextStatus = this.parseRefundStatusTransition(payload.status);
    const note = this.parseOptionalText(payload.note, 1000);

    const refunds = await this.readRefundRecords();
    const index = refunds.findIndex((record) => record.id === normalizedId);
    if (index === -1) {
      throw new NotFoundException('Refund request not found');
    }

    const existing = refunds[index];
    if (existing.status === nextStatus) {
      return {
        updated: false,
        refund: existing,
        booking: null,
      };
    }

    this.ensureRefundTransitionAllowed(existing.status, nextStatus);

    const nowIso = new Date().toISOString();
    const updated: RefundRecord = {
      ...existing,
      status: nextStatus,
      resolutionNote: note,
      resolvedByUserId: actor.id,
      resolvedByRole: actor.role,
      resolvedAt: nowIso,
      updatedAt: nowIso,
    };

    const nextRecords = [...refunds];
    nextRecords[index] = updated;
    await this.writeRefundRecords(nextRecords);

    const bookingAdjustment = await this.applyBookingRefundStatusFromRecords(updated.bookingId, updated.paymentId, nextRecords);

    return {
      updated: true,
      refund: updated,
      booking: bookingAdjustment,
    };
  }

  async listDisputes(payload: DisputeQueuePayload = {}) {
    const statusFilter = this.parseDisputeStatusFilter(payload.status);
    const bookingId = this.parseOptionalId(payload.bookingId);
    const paymentId = this.parseOptionalId(payload.paymentId);
    const { limit, offset } = this.parsePagination(payload.limit, payload.offset, 200);

    const disputes = await this.readDisputeRecords();
    const filtered = disputes.filter((record) => {
      if (statusFilter && record.status !== statusFilter) {
        return false;
      }

      if (bookingId && record.bookingId !== bookingId) {
        return false;
      }

      if (paymentId && record.paymentId !== paymentId) {
        return false;
      }

      return true;
    });

    const items = filtered.slice(offset, offset + limit);
    return {
      total: filtered.length,
      limit,
      offset,
      items,
    };
  }

  async updateDisputeStatus(
    disputeId: string,
    payload: DisputeStatusUpdatePayload,
    actor: { id: string; role: string },
  ) {
    const normalizedId = this.parseRequiredText(disputeId, 'dispute id', 120);
    const nextStatus = this.parseDisputeStatusTransition(payload.status);
    const note = this.parseOptionalText(payload.note, 1000);

    const disputes = await this.readDisputeRecords();
    const index = disputes.findIndex((record) => record.id === normalizedId);
    if (index === -1) {
      throw new NotFoundException('Dispute not found');
    }

    const existing = disputes[index];
    if (existing.status === nextStatus) {
      return {
        updated: false,
        dispute: existing,
      };
    }

    this.ensureDisputeTransitionAllowed(existing.status, nextStatus);

    const nowIso = new Date().toISOString();
    const updated: DisputeRecord = {
      ...existing,
      status: nextStatus,
      resolutionNote: note,
      resolvedByUserId: actor.id,
      resolvedByRole: actor.role,
      resolvedAt: nowIso,
      updatedAt: nowIso,
    };

    const nextRecords = [...disputes];
    nextRecords[index] = updated;
    await this.writeDisputeRecords(nextRecords);

    return {
      updated: true,
      dispute: updated,
    };
  }

  async getSettlementReport(payload: SettlementReportPayload) {
    const dateRange = this.parseSettlementDateRange(payload);
    const providerFilter = this.parseProviderFilter(payload.provider);
    if (payload.provider !== undefined && !providerFilter) {
      throw new BadRequestException('provider must be one of STRIPE, BML, MIB');
    }

    const currencyFilter = this.parseCurrency(payload.currency);
    if (payload.currency !== undefined && !currencyFilter) {
      throw new BadRequestException('currency must be one of USD, MVR');
    }

    const succeededPayments = await this.prisma.payment.findMany({
      where: {
        status: 'SUCCEEDED',
        ...(providerFilter ? { provider: providerFilter } : {}),
        ...(currencyFilter ? { currency: currencyFilter } : {}),
        createdAt: {
          gte: dateRange.from,
          lte: dateRange.to,
        },
      },
      select: {
        id: true,
        provider: true,
        currency: true,
        amount: true,
        createdAt: true,
      },
    });

    const refunds = await this.readRefundRecords();
    const completedRefunds = refunds.filter((record) => {
      const createdAt = new Date(record.createdAt);
      return record.status === 'COMPLETED'
        && createdAt >= dateRange.from
        && createdAt <= dateRange.to
        && (!providerFilter || record.provider === providerFilter)
        && (!currencyFilter || record.currency === currencyFilter);
    });

    const bucketByProviderCurrency = new Map<string, {
      provider: string;
      currency: string;
      grossAmount: number;
      refundedAmount: number;
      netAmount: number;
      paymentsCount: number;
      refundsCount: number;
    }>();

    for (const payment of succeededPayments) {
      const key = `${payment.provider}:${payment.currency}`;
      const existing = bucketByProviderCurrency.get(key) ?? {
        provider: payment.provider,
        currency: payment.currency,
        grossAmount: 0,
        refundedAmount: 0,
        netAmount: 0,
        paymentsCount: 0,
        refundsCount: 0,
      };

      existing.grossAmount += Number(payment.amount);
      existing.netAmount += Number(payment.amount);
      existing.paymentsCount += 1;
      bucketByProviderCurrency.set(key, existing);
    }

    for (const refund of completedRefunds) {
      const key = `${refund.provider}:${refund.currency}`;
      const existing = bucketByProviderCurrency.get(key) ?? {
        provider: refund.provider,
        currency: refund.currency,
        grossAmount: 0,
        refundedAmount: 0,
        netAmount: 0,
        paymentsCount: 0,
        refundsCount: 0,
      };

      existing.refundedAmount += refund.requestedAmount;
      existing.netAmount -= refund.requestedAmount;
      existing.refundsCount += 1;
      bucketByProviderCurrency.set(key, existing);
    }

    const byProviderCurrency = Array.from(bucketByProviderCurrency.values())
      .map((entry) => ({
        ...entry,
        grossAmount: Number(entry.grossAmount.toFixed(2)),
        refundedAmount: Number(entry.refundedAmount.toFixed(2)),
        netAmount: Number(entry.netAmount.toFixed(2)),
      }))
      .sort((a, b) => a.provider.localeCompare(b.provider) || a.currency.localeCompare(b.currency));

    const totals = byProviderCurrency.reduce((acc, item) => {
      acc.grossAmount += item.grossAmount;
      acc.refundedAmount += item.refundedAmount;
      acc.netAmount += item.netAmount;
      acc.paymentsCount += item.paymentsCount;
      acc.refundsCount += item.refundsCount;
      return acc;
    }, {
      grossAmount: 0,
      refundedAmount: 0,
      netAmount: 0,
      paymentsCount: 0,
      refundsCount: 0,
    });

    return {
      window: {
        from: dateRange.from.toISOString(),
        to: dateRange.to.toISOString(),
      },
      filters: {
        provider: providerFilter ?? 'ALL',
        currency: currencyFilter ?? 'ALL',
      },
      totals: {
        grossAmount: Number(totals.grossAmount.toFixed(2)),
        refundedAmount: Number(totals.refundedAmount.toFixed(2)),
        netAmount: Number(totals.netAmount.toFixed(2)),
        paymentsCount: totals.paymentsCount,
        refundsCount: totals.refundsCount,
      },
      byProviderCurrency,
      generatedAt: new Date().toISOString(),
    };
  }

  async getVendorSettlementReport(actorVendorId: unknown, payload: VendorSettlementReportPayload) {
    const vendorId = this.parseActorVendorId(actorVendorId);
    const dateRange = this.parseSettlementDateRange(payload);
    const providerFilter = this.parseProviderFilter(payload.provider);
    if (payload.provider !== undefined && !providerFilter) {
      throw new BadRequestException('provider must be one of STRIPE, BML, MIB');
    }

    const currencyFilter = this.parseCurrency(payload.currency);
    if (payload.currency !== undefined && !currencyFilter) {
      throw new BadRequestException('currency must be one of USD, MVR');
    }

    const succeededPayments = await this.prisma.payment.findMany({
      where: {
        status: 'SUCCEEDED',
        ...(providerFilter ? { provider: providerFilter } : {}),
        ...(currencyFilter ? { currency: currencyFilter } : {}),
        createdAt: {
          gte: dateRange.from,
          lte: dateRange.to,
        },
      },
      select: {
        id: true,
        bookingId: true,
        provider: true,
        currency: true,
        amount: true,
        createdAt: true,
        booking: {
          select: {
            accommodation: {
              select: {
                vendorId: true,
              },
            },
            transport: {
              select: {
                vendorId: true,
              },
            },
          },
        },
      },
    });

    const vendorPayments = succeededPayments.filter((payment) => this.resolveBookingVendorId(payment.booking) === vendorId);
    const vendorPaymentIds = new Set(vendorPayments.map((payment) => payment.id));

    const refunds = await this.readRefundRecords();
    const completedRefunds = refunds.filter((record) => {
      const createdAt = new Date(record.createdAt);
      return record.status === 'COMPLETED'
        && createdAt >= dateRange.from
        && createdAt <= dateRange.to
        && (!providerFilter || record.provider === providerFilter)
        && (!currencyFilter || record.currency === currencyFilter)
        && vendorPaymentIds.has(record.paymentId);
    });

    const payoutFeeRate = this.readRateEnv('PAYMENTS_VENDOR_PAYOUT_FEE_RATE', 0.08);
    const reserveRate = this.readRateEnv('PAYMENTS_VENDOR_PAYOUT_RESERVE_RATE', 0.03);

    const bucketByProviderCurrency = new Map<string, {
      provider: string;
      currency: string;
      grossAmount: number;
      refundedAmount: number;
      netAmount: number;
      feesAmount: number;
      reserveAmount: number;
      estimatedPayoutAmount: number;
      paymentsCount: number;
      refundsCount: number;
    }>();

    for (const payment of vendorPayments) {
      const key = `${payment.provider}:${payment.currency}`;
      const existing = bucketByProviderCurrency.get(key) ?? {
        provider: payment.provider,
        currency: payment.currency,
        grossAmount: 0,
        refundedAmount: 0,
        netAmount: 0,
        feesAmount: 0,
        reserveAmount: 0,
        estimatedPayoutAmount: 0,
        paymentsCount: 0,
        refundsCount: 0,
      };

      existing.grossAmount += Number(payment.amount);
      existing.netAmount += Number(payment.amount);
      existing.paymentsCount += 1;
      bucketByProviderCurrency.set(key, existing);
    }

    for (const refund of completedRefunds) {
      const key = `${refund.provider}:${refund.currency}`;
      const existing = bucketByProviderCurrency.get(key) ?? {
        provider: refund.provider,
        currency: refund.currency,
        grossAmount: 0,
        refundedAmount: 0,
        netAmount: 0,
        feesAmount: 0,
        reserveAmount: 0,
        estimatedPayoutAmount: 0,
        paymentsCount: 0,
        refundsCount: 0,
      };

      existing.refundedAmount += refund.requestedAmount;
      existing.netAmount -= refund.requestedAmount;
      existing.refundsCount += 1;
      bucketByProviderCurrency.set(key, existing);
    }

    const byProviderCurrency = Array.from(bucketByProviderCurrency.values())
      .map((entry) => {
        const feesAmount = Math.max(entry.netAmount, 0) * payoutFeeRate;
        const reserveAmount = Math.max(entry.netAmount, 0) * reserveRate;
        const estimatedPayoutAmount = Math.max(entry.netAmount - feesAmount - reserveAmount, 0);
        return {
          ...entry,
          grossAmount: Number(entry.grossAmount.toFixed(2)),
          refundedAmount: Number(entry.refundedAmount.toFixed(2)),
          netAmount: Number(entry.netAmount.toFixed(2)),
          feesAmount: Number(feesAmount.toFixed(2)),
          reserveAmount: Number(reserveAmount.toFixed(2)),
          estimatedPayoutAmount: Number(estimatedPayoutAmount.toFixed(2)),
        };
      })
      .sort((a, b) => a.provider.localeCompare(b.provider) || a.currency.localeCompare(b.currency));

    const totals = byProviderCurrency.reduce((acc, item) => {
      acc.grossAmount += item.grossAmount;
      acc.refundedAmount += item.refundedAmount;
      acc.netAmount += item.netAmount;
      acc.feesAmount += item.feesAmount;
      acc.reserveAmount += item.reserveAmount;
      acc.estimatedPayoutAmount += item.estimatedPayoutAmount;
      acc.paymentsCount += item.paymentsCount;
      acc.refundsCount += item.refundsCount;
      return acc;
    }, {
      grossAmount: 0,
      refundedAmount: 0,
      netAmount: 0,
      feesAmount: 0,
      reserveAmount: 0,
      estimatedPayoutAmount: 0,
      paymentsCount: 0,
      refundsCount: 0,
    });

    return {
      vendorId,
      window: {
        from: dateRange.from.toISOString(),
        to: dateRange.to.toISOString(),
      },
      filters: {
        provider: providerFilter ?? 'ALL',
        currency: currencyFilter ?? 'ALL',
      },
      payoutModel: {
        feeRate: payoutFeeRate,
        reserveRate,
      },
      totals: {
        grossAmount: Number(totals.grossAmount.toFixed(2)),
        refundedAmount: Number(totals.refundedAmount.toFixed(2)),
        netAmount: Number(totals.netAmount.toFixed(2)),
        feesAmount: Number(totals.feesAmount.toFixed(2)),
        reserveAmount: Number(totals.reserveAmount.toFixed(2)),
        estimatedPayoutAmount: Number(totals.estimatedPayoutAmount.toFixed(2)),
        paymentsCount: totals.paymentsCount,
        refundsCount: totals.refundsCount,
      },
      byProviderCurrency,
      generatedAt: new Date().toISOString(),
    };
  }

  async syncFinanceLedger(payload: LedgerSyncPayload = {}) {
    const dateRange = this.parseSettlementDateRange(payload);
    const taxRate = this.readRateEnv('PAYMENTS_LEDGER_TAX_RATE', 0.08);

    const [existingEntries, succeededPayments, refundRecords] = await Promise.all([
      this.readFinanceLedgerEntries(),
      this.prisma.payment.findMany({
        where: {
          status: 'SUCCEEDED',
          createdAt: {
            gte: dateRange.from,
            lte: dateRange.to,
          },
        },
        select: {
          id: true,
          bookingId: true,
          provider: true,
          currency: true,
          amount: true,
          createdAt: true,
        },
      }),
      this.readRefundRecords(),
    ]);

    const completedRefunds = refundRecords.filter((record) => {
      const createdAt = new Date(record.createdAt);
      return record.status === 'COMPLETED' && createdAt >= dateRange.from && createdAt <= dateRange.to;
    });

    const byExternalRef = new Map(existingEntries.map((entry) => [entry.externalRef, entry]));
    let inserted = 0;
    let updated = 0;

    for (const payment of succeededPayments) {
      const totalAmount = Number(payment.amount);
      const subtotalAmount = totalAmount / (1 + taxRate);
      const taxAmount = totalAmount - subtotalAmount;
      const externalRef = `payment:${payment.id}`;
      const nextEntry: FinanceLedgerEntry = {
        id: byExternalRef.get(externalRef)?.id ?? `ledger_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
        externalRef,
        entryType: 'PAYMENT_CAPTURED',
        bookingId: payment.bookingId,
        paymentId: payment.id,
        provider: payment.provider,
        currency: payment.currency,
        grossAmount: Number(totalAmount.toFixed(2)),
        taxAmount: Number(taxAmount.toFixed(2)),
        netAmount: Number((totalAmount - taxAmount).toFixed(2)),
        occurredAt: payment.createdAt.toISOString(),
        source: 'PAYMENT',
        createdAt: byExternalRef.get(externalRef)?.createdAt ?? new Date().toISOString(),
      };

      if (byExternalRef.has(externalRef)) {
        updated += 1;
      } else {
        inserted += 1;
      }

      byExternalRef.set(externalRef, nextEntry);
    }

    for (const refund of completedRefunds) {
      const totalAmount = Number(refund.requestedAmount);
      const subtotalAmount = totalAmount / (1 + taxRate);
      const taxAmount = totalAmount - subtotalAmount;
      const externalRef = `refund:${refund.id}`;
      const nextEntry: FinanceLedgerEntry = {
        id: byExternalRef.get(externalRef)?.id ?? `ledger_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
        externalRef,
        entryType: 'REFUND_COMPLETED',
        bookingId: refund.bookingId,
        paymentId: refund.paymentId,
        provider: refund.provider,
        currency: refund.currency,
        grossAmount: Number((-totalAmount).toFixed(2)),
        taxAmount: Number((-taxAmount).toFixed(2)),
        netAmount: Number((-(totalAmount - taxAmount)).toFixed(2)),
        occurredAt: refund.createdAt,
        source: 'REFUND',
        createdAt: byExternalRef.get(externalRef)?.createdAt ?? new Date().toISOString(),
      };

      if (byExternalRef.has(externalRef)) {
        updated += 1;
      } else {
        inserted += 1;
      }

      byExternalRef.set(externalRef, nextEntry);
    }

    const items = Array.from(byExternalRef.values())
      .sort((a, b) => new Date(b.occurredAt).getTime() - new Date(a.occurredAt).getTime());
    await this.writeFinanceLedgerEntries(items);

    return {
      synced: true,
      window: {
        from: dateRange.from.toISOString(),
        to: dateRange.to.toISOString(),
      },
      taxRate,
      totals: {
        paymentsSeen: succeededPayments.length,
        refundsSeen: completedRefunds.length,
        inserted,
        updated,
        ledgerEntries: items.length,
      },
      syncedAt: new Date().toISOString(),
    };
  }

  async getFinanceLedgerReport(payload: LedgerReportPayload = {}) {
    const dateRange = this.parseSettlementDateRange(payload);
    const providerFilter = this.parseProviderFilter(payload.provider);
    if (payload.provider !== undefined && !providerFilter) {
      throw new BadRequestException('provider must be one of STRIPE, BML, MIB');
    }

    const currencyFilter = this.parseCurrency(payload.currency);
    if (payload.currency !== undefined && !currencyFilter) {
      throw new BadRequestException('currency must be one of USD, MVR');
    }

    const entryTypeFilter = this.parseLedgerEntryTypeFilter(payload.entryType);
    const { limit, offset } = this.parsePagination(payload.limit, payload.offset, 500);

    const entries = await this.readFinanceLedgerEntries();
    const filtered = entries.filter((entry) => {
      const occurredAt = new Date(entry.occurredAt);
      if (occurredAt < dateRange.from || occurredAt > dateRange.to) {
        return false;
      }

      if (providerFilter && entry.provider !== providerFilter) {
        return false;
      }

      if (currencyFilter && entry.currency !== currencyFilter) {
        return false;
      }

      if (entryTypeFilter && entry.entryType !== entryTypeFilter) {
        return false;
      }

      return true;
    });

    const totals = filtered.reduce((acc, entry) => {
      acc.grossAmount += entry.grossAmount;
      acc.taxAmount += entry.taxAmount;
      acc.netAmount += entry.netAmount;
      return acc;
    }, {
      grossAmount: 0,
      taxAmount: 0,
      netAmount: 0,
    });

    const items = filtered.slice(offset, offset + limit);
    return {
      window: {
        from: dateRange.from.toISOString(),
        to: dateRange.to.toISOString(),
      },
      filters: {
        provider: providerFilter ?? 'ALL',
        currency: currencyFilter ?? 'ALL',
        entryType: entryTypeFilter ?? 'ALL',
      },
      totals: {
        grossAmount: Number(totals.grossAmount.toFixed(2)),
        taxAmount: Number(totals.taxAmount.toFixed(2)),
        netAmount: Number(totals.netAmount.toFixed(2)),
        entriesCount: filtered.length,
      },
      limit,
      offset,
      items,
      generatedAt: new Date().toISOString(),
    };
  }

  async generateTaxInvoice(payload: TaxInvoiceGeneratePayload, actor: { id: string; role: string }) {
    const bookingId = this.parseOptionalId(payload.bookingId);
    const paymentId = this.parseOptionalId(payload.paymentId);
    const idempotencyKey = this.parseOptionalKey(payload.idempotencyKey, 100);
    const taxRate = this.parseOptionalRate(payload.taxRate, 'taxRate') ?? this.readRateEnv('PAYMENTS_TAX_RATE', 0.08);
    const issuerName = this.parseOptionalText(payload.issuerName, 120) ?? process.env.PAYMENTS_TAX_INVOICE_ISSUER_NAME ?? 'Workation Maldives';
    const issuerTaxId = this.parseOptionalText(payload.issuerTaxId, 120) ?? process.env.PAYMENTS_TAX_INVOICE_ISSUER_TAX_ID ?? 'MV-TAX-UNREGISTERED';
    const notes = this.parseOptionalText(payload.notes, 1000);

    const payment = await this.resolvePaymentForAdjustment(bookingId, paymentId);
    if (payment.status !== 'SUCCEEDED') {
      throw new BadRequestException('Tax invoices can only be generated for succeeded payments');
    }

    const booking = await this.prisma.booking.findUnique({
      where: { id: payment.bookingId },
      select: {
        id: true,
        userId: true,
        user: {
          select: {
            name: true,
            email: true,
          },
        },
      },
    });
    if (!booking) {
      throw new NotFoundException('Booking not found');
    }

    const invoices = await this.readTaxInvoiceRecords();
    if (idempotencyKey) {
      const existingByIdempotency = invoices.find((invoice) => invoice.idempotencyKey === idempotencyKey);
      if (existingByIdempotency) {
        return {
          created: false,
          invoice: existingByIdempotency,
        };
      }
    }

    const existingByPayment = invoices.find((invoice) => invoice.paymentId === payment.id && invoice.status === 'ISSUED');
    if (existingByPayment) {
      return {
        created: false,
        invoice: existingByPayment,
      };
    }

    const totalAmount = Number(payment.amount);
    const subtotalAmount = totalAmount / (1 + taxRate);
    const taxAmount = totalAmount - subtotalAmount;

    const now = new Date();
    const issuedAt = now.toISOString();
    const invoiceNumber = this.generateTaxInvoiceNumber(now, invoices);
    const created: TaxInvoiceRecord = {
      id: `taxinv_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
      invoiceNumber,
      bookingId: booking.id,
      paymentId: payment.id,
      userId: booking.userId,
      customerName: booking.user?.name ?? null,
      customerEmail: booking.user?.email ?? null,
      provider: payment.provider,
      currency: payment.currency,
      subtotalAmount: Number(subtotalAmount.toFixed(2)),
      taxRate: Number(taxRate.toFixed(4)),
      taxAmount: Number(taxAmount.toFixed(2)),
      totalAmount: Number(totalAmount.toFixed(2)),
      status: 'ISSUED',
      issuerName,
      issuerTaxId,
      notes,
      idempotencyKey,
      issuedAt,
      createdByUserId: actor.id,
      createdByRole: actor.role,
      createdAt: issuedAt,
      updatedAt: issuedAt,
    };

    await this.writeTaxInvoiceRecords([created, ...invoices]);

    return {
      created: true,
      invoice: created,
    };
  }

  async listTaxInvoices(payload: TaxInvoiceListPayload = {}) {
    const bookingId = this.parseOptionalId(payload.bookingId);
    const paymentId = this.parseOptionalId(payload.paymentId);
    const customerEmail = this.parseOptionalText(payload.customerEmail, 320)?.toLowerCase() ?? null;
    const invoiceNumber = this.parseOptionalText(payload.invoiceNumber, 64)?.toUpperCase() ?? null;
    const { limit, offset } = this.parsePagination(payload.limit, payload.offset, 500);

    const invoices = await this.readTaxInvoiceRecords();
    const filtered = invoices.filter((invoice) => {
      if (bookingId && invoice.bookingId !== bookingId) {
        return false;
      }

      if (paymentId && invoice.paymentId !== paymentId) {
        return false;
      }

      if (customerEmail && (invoice.customerEmail ?? '').toLowerCase() !== customerEmail) {
        return false;
      }

      if (invoiceNumber && invoice.invoiceNumber.toUpperCase() !== invoiceNumber) {
        return false;
      }

      return true;
    });

    return {
      total: filtered.length,
      limit,
      offset,
      items: filtered.slice(offset, offset + limit),
    };
  }

  async listTaxInvoicesForUser(userId: string, payload: TaxInvoiceListPayload = {}) {
    const bookingId = this.parseOptionalId(payload.bookingId);
    const paymentId = this.parseOptionalId(payload.paymentId);
    const invoiceNumber = this.parseOptionalText(payload.invoiceNumber, 64)?.toUpperCase() ?? null;
    const { limit, offset } = this.parsePagination(payload.limit, payload.offset, 200);

    const invoices = await this.readTaxInvoiceRecords();
    const filtered = invoices.filter((invoice) => {
      if (invoice.userId !== userId) {
        return false;
      }

      if (bookingId && invoice.bookingId !== bookingId) {
        return false;
      }

      if (paymentId && invoice.paymentId !== paymentId) {
        return false;
      }

      if (invoiceNumber && invoice.invoiceNumber.toUpperCase() !== invoiceNumber) {
        return false;
      }

      return true;
    });

    return {
      total: filtered.length,
      limit,
      offset,
      items: filtered.slice(offset, offset + limit),
    };
  }

  async processStripeWebhook(signature: string | undefined, payload: unknown) {
    return this.processWebhook('STRIPE', signature, payload);
  }

  async processBmlWebhook(signature: string | undefined, payload: unknown) {
    return this.processWebhook('BML', signature, payload);
  }

  async processMibWebhook(signature: string | undefined, payload: unknown) {
    return this.processWebhook('MIB', signature, payload);
  }

  async getBmlHealthReport() {
    if (!this.bmlAdapter.probeHealth) {
      return {
        provider: 'BML',
        mode: 'connect',
        configured: false,
        reachable: false,
        authenticated: false,
        message: 'BML health probe is not implemented for current adapter',
      };
    }

    const result = await this.bmlAdapter.probeHealth();
    return {
      provider: 'BML',
      mode: 'connect',
      ...result,
    };
  }

  async getMibHealthReport() {
    if (!this.mibAdapter.probeHealth) {
      return {
        provider: 'MIB',
        mode: 'legacy-or-api',
        configured: false,
        reachable: false,
        authenticated: false,
        responseTimeMs: 0,
        message: 'MIB health probe is not implemented for current adapter',
      };
    }

    const result = await this.mibAdapter.probeHealth();
    return {
      provider: 'MIB',
      mode: 'legacy-or-api',
      ...result,
    };
  }

  async reconcilePendingPayments(payload: ReconcilePayload, source: ReconcileSource = 'ADMIN') {
    const providerFilter = this.parseProviderFilter(payload.provider);
    if (payload.provider !== undefined && !providerFilter) {
      throw new BadRequestException('Unsupported provider filter. Allowed: STRIPE, BML, MIB');
    }

    const limit = this.parseLimit(payload.limit);
    if (payload.limit !== undefined && limit === null) {
      throw new BadRequestException('limit must be an integer between 1 and 500');
    }

    const dryRun = Boolean(payload.dryRun);
    const startedAt = new Date();

    const pendingStatuses = ['PENDING', 'REQUIRES_ACTION'];
    const payments = await this.prisma.payment.findMany({
      where: {
        ...(providerFilter ? { provider: providerFilter } : {}),
        status: { in: pendingStatuses },
      },
      orderBy: { createdAt: 'asc' },
      take: limit ?? 100,
    });

    const summary = {
      scanned: payments.length,
      providerFilter: providerFilter ?? 'ALL',
      dryRun,
      reconciled: 0,
      succeeded: 0,
      failed: 0,
      unchanged: 0,
      skipped: 0,
      errors: 0,
      details: [] as Array<{ paymentId: string; provider: string; result: string; note?: string }>,
    };

    try {
      for (const payment of payments) {
        const provider = this.parseProviderFilter(payment.provider);
        if (!provider) {
          summary.skipped += 1;
          summary.details.push({ paymentId: payment.id, provider: payment.provider, result: 'skipped', note: 'unknown provider' });
          continue;
        }

        const adapter = this.getAdapter(provider);
        if (!adapter.fetchTransactionStatus) {
          summary.skipped += 1;
          summary.details.push({ paymentId: payment.id, provider, result: 'skipped', note: 'provider status fetch not supported' });
          continue;
        }

        if (!payment.providerId) {
          summary.skipped += 1;
          summary.details.push({ paymentId: payment.id, provider, result: 'skipped', note: 'missing provider reference' });
          continue;
        }

        try {
          const providerStatus = await this.fetchProviderStatusWithHardening(provider, adapter, payment.providerId!);
          if (!providerStatus || providerStatus.state === 'PENDING') {
            summary.unchanged += 1;
            summary.details.push({ paymentId: payment.id, provider, result: 'unchanged' });
            continue;
          }

          if (!dryRun) {
            if (providerStatus.state === 'SUCCEEDED') {
              await this.prisma.payment.update({ where: { id: payment.id }, data: { status: 'SUCCEEDED' } });
              await this.prisma.booking.update({ where: { id: payment.bookingId }, data: { status: 'CONFIRMED' } });
              await this.loyaltyService.awardPointsForConfirmedBooking(payment.bookingId, 'PAYMENT_RECONCILE');
              await this.enqueueBookingConfirmationJob(payment.bookingId, payment.id, 'RECONCILE');
            }

            if (providerStatus.state === 'FAILED') {
              await this.prisma.payment.update({ where: { id: payment.id }, data: { status: 'FAILED' } });
            }
          }

          summary.reconciled += 1;
          if (providerStatus.state === 'SUCCEEDED') {
            summary.succeeded += 1;
          }
          if (providerStatus.state === 'FAILED') {
            summary.failed += 1;
          }
          summary.details.push({ paymentId: payment.id, provider, result: providerStatus.state.toLowerCase() });
        } catch (error) {
          summary.errors += 1;
          summary.details.push({
            paymentId: payment.id,
            provider,
            result: 'error',
            note: error instanceof Error ? error.message : 'unknown error',
          });
        }
      }

      await this.prisma.paymentReconciliationRun.create({
        data: {
          source,
          providerFilter: summary.providerFilter,
          limitUsed: limit ?? 100,
          dryRun: summary.dryRun,
          status: 'SUCCESS',
          scanned: summary.scanned,
          reconciled: summary.reconciled,
          succeeded: summary.succeeded,
          failed: summary.failed,
          unchanged: summary.unchanged,
          skipped: summary.skipped,
          errors: summary.errors,
          startedAt,
          finishedAt: new Date(),
          durationMs: Date.now() - startedAt.getTime(),
        },
      });

      return summary;
    } catch (error) {
      await this.prisma.paymentReconciliationRun.create({
        data: {
          source,
          providerFilter: providerFilter ?? 'ALL',
          limitUsed: limit ?? 100,
          dryRun,
          status: 'ERROR',
          scanned: summary.scanned,
          reconciled: summary.reconciled,
          succeeded: summary.succeeded,
          failed: summary.failed,
          unchanged: summary.unchanged,
          skipped: summary.skipped,
          errors: summary.errors,
          errorMessage: error instanceof Error ? error.message : 'unknown error',
          startedAt,
          finishedAt: new Date(),
          durationMs: Date.now() - startedAt.getTime(),
        },
      });
      throw error;
    }
  }

  async getReconciliationRunHistory(payload: { limit?: unknown }) {
    const limit = this.parseHistoryLimit(payload.limit);
    if (payload.limit !== undefined && limit === null) {
      throw new BadRequestException('limit must be an integer between 1 and 100');
    }

    return this.prisma.paymentReconciliationRun.findMany({
      orderBy: { createdAt: 'desc' },
      take: limit ?? 20,
    });
  }

  async getReconciliationAlerts(payload: { enabled: boolean; intervalMs: number | null }) {
    const staleMultiplier = this.readPositiveIntEnv('PAYMENTS_RECONCILE_ALERT_STALE_MULTIPLIER', 2);
    const errorStreakThreshold = this.readPositiveIntEnv('PAYMENTS_RECONCILE_ALERT_ERROR_STREAK', 3);
    const errorCountThreshold = this.readPositiveIntEnv('PAYMENTS_RECONCILE_ALERT_ERRORS_THRESHOLD', 5);

    const [lastRun, lastSuccessRun, recentRuns] = await Promise.all([
      this.prisma.paymentReconciliationRun.findFirst({
        orderBy: { createdAt: 'desc' },
      }),
      this.prisma.paymentReconciliationRun.findFirst({
        where: { status: 'SUCCESS' },
        orderBy: { createdAt: 'desc' },
      }),
      this.prisma.paymentReconciliationRun.findMany({
        orderBy: { createdAt: 'desc' },
        take: errorStreakThreshold,
      }),
    ]);

    const nowMs = Date.now();
    const lastRunCompletedAt = lastRun?.finishedAt ?? lastRun?.createdAt ?? null;
    const lastSuccessCompletedAt = lastSuccessRun?.finishedAt ?? lastSuccessRun?.createdAt ?? null;

    const lastRunAgeMs = lastRunCompletedAt ? nowMs - lastRunCompletedAt.getTime() : null;
    const lastSuccessAgeMs = lastSuccessCompletedAt ? nowMs - lastSuccessCompletedAt.getTime() : null;

    const staleThresholdMs = payload.enabled && payload.intervalMs ? payload.intervalMs * staleMultiplier : null;
    const staleSuccess = staleThresholdMs !== null && (lastSuccessAgeMs === null || lastSuccessAgeMs > staleThresholdMs);

    let consecutiveErrorRuns = 0;
    for (const run of recentRuns) {
      if (run.status !== 'ERROR') {
        break;
      }
      consecutiveErrorRuns += 1;
    }

    const errorStreak = consecutiveErrorRuns >= errorStreakThreshold;
    const highErrorsLastRun = Boolean(lastRun && lastRun.errors >= errorCountThreshold);

    const activeAlertKeys = [
      staleSuccess ? 'staleSuccess' : null,
      errorStreak ? 'errorStreak' : null,
      highErrorsLastRun ? 'highErrorsLastRun' : null,
    ].filter((value): value is string => value !== null);

    return {
      status: activeAlertKeys.length === 0 ? 'OK' : 'WARN',
      generatedAt: new Date().toISOString(),
      config: {
        enabled: payload.enabled,
        intervalMs: payload.intervalMs,
        staleMultiplier,
        staleThresholdMs,
        errorStreakThreshold,
        errorCountThreshold,
      },
      checks: {
        staleSuccess: {
          active: staleSuccess,
          lastSuccessAt: lastSuccessCompletedAt ? lastSuccessCompletedAt.toISOString() : null,
          lastSuccessAgeMs,
        },
        errorStreak: {
          active: errorStreak,
          consecutiveErrorRuns,
        },
        highErrorsLastRun: {
          active: highErrorsLastRun,
          lastRunErrors: lastRun?.errors ?? null,
          lastRunAt: lastRunCompletedAt ? lastRunCompletedAt.toISOString() : null,
          lastRunAgeMs,
        },
      },
      activeAlerts: activeAlertKeys,
    };
  }

  async getBackgroundJobsHealth(payload: { recentFailuresLimit?: unknown } = {}) {
    const recentFailuresLimit = this.parseRecentFailuresLimit(payload.recentFailuresLimit);
    if (payload.recentFailuresLimit !== undefined && recentFailuresLimit === null) {
      throw new BadRequestException('recentFailuresLimit must be an integer between 1 and 50');
    }

    const [groupedCounts, nextDueJob, oldestPendingJob, recentFailures] = await Promise.all([
      this.prisma.paymentBackgroundJob.groupBy({
        by: ['status'],
        _count: { _all: true },
      }),
      this.prisma.paymentBackgroundJob.findFirst({
        where: { status: { in: ['PENDING', 'RETRYABLE'] } },
        orderBy: { runAt: 'asc' },
      }),
      this.prisma.paymentBackgroundJob.findFirst({
        where: { status: { in: ['PENDING', 'RETRYABLE'] } },
        orderBy: { createdAt: 'asc' },
      }),
      this.prisma.paymentBackgroundJob.findMany({
        where: { status: 'DEAD' },
        orderBy: { updatedAt: 'desc' },
        take: recentFailuresLimit ?? 10,
      }),
    ]);

    const counts = {
      pending: 0,
      retryable: 0,
      running: 0,
      completed: 0,
      dead: 0,
      total: 0,
    };

    for (const item of groupedCounts) {
      const count = item._count._all;
      counts.total += count;
      if (item.status === 'PENDING') {
        counts.pending = count;
      }
      if (item.status === 'RETRYABLE') {
        counts.retryable = count;
      }
      if (item.status === 'RUNNING') {
        counts.running = count;
      }
      if (item.status === 'COMPLETED') {
        counts.completed = count;
      }
      if (item.status === 'DEAD') {
        counts.dead = count;
      }
    }

    const terminalTotal = counts.completed + counts.dead;
    const nowMs = Date.now();
    const oldestPendingAgeMs = oldestPendingJob ? Math.max(0, nowMs - oldestPendingJob.createdAt.getTime()) : null;
    const retrySuccessRate = terminalTotal > 0 ? counts.completed / terminalTotal : null;
    const deadLetterRate = terminalTotal > 0 ? counts.dead / terminalTotal : null;

    return {
      generatedAt: new Date().toISOString(),
      counts,
      metrics: {
        oldestPendingAgeMs,
        oldestPendingAt: oldestPendingJob?.createdAt?.toISOString() ?? null,
        retrySuccessRate,
        deadLetterRate,
      },
      nextDueAt: nextDueJob?.runAt?.toISOString() ?? null,
      recentFailures: recentFailures.map((job) => ({
        id: job.id,
        type: job.type,
        attempts: job.attempts,
        maxAttempts: job.maxAttempts,
        lastError: job.lastError,
        updatedAt: job.updatedAt.toISOString(),
      })),
    };
  }

  async dispatchOperationalAlerts(payload: {
    reconcileEnabled: boolean;
    reconcileIntervalMs: number | null;
    jobsRunner: BackgroundJobsRunnerSnapshot;
  }) {
    const [reconciliation, jobsHealth] = await Promise.all([
      this.getReconciliationAlerts({
        enabled: payload.reconcileEnabled,
        intervalMs: payload.reconcileIntervalMs,
      }),
      this.getBackgroundJobsHealth({ recentFailuresLimit: 10 }),
    ]);

    const jobsPendingAgeThresholdMs = this.readPositiveIntEnv('PAYMENTS_JOBS_ALERT_PENDING_AGE_MS', 900000);
    const jobsDeadCountThreshold = this.readPositiveIntEnv('PAYMENTS_JOBS_ALERT_DEAD_COUNT', 5);
    const jobsDeadLetterRateThreshold = this.readRateEnv('PAYMENTS_JOBS_ALERT_DEAD_LETTER_RATE', 0.2);
    const jobsStalledTickThresholdMs = this.readPositiveIntEnv('PAYMENTS_JOBS_ALERT_STALLED_TICK_MS', Math.max(payload.jobsRunner.intervalMs * 3, 30000));

    const oldestPendingAgeMs = jobsHealth.metrics.oldestPendingAgeMs;
    const deadCount = jobsHealth.counts.dead;
    const deadLetterRate = jobsHealth.metrics.deadLetterRate;

    const pendingAgeHigh = oldestPendingAgeMs !== null && oldestPendingAgeMs >= jobsPendingAgeThresholdMs;
    const deadCountHigh = deadCount >= jobsDeadCountThreshold;
    const deadLetterRateHigh = deadLetterRate !== null && deadLetterRate >= jobsDeadLetterRateThreshold;

    const lastTickAgeMs = payload.jobsRunner.lastTickFinishedAt
      ? Math.max(0, Date.now() - new Date(payload.jobsRunner.lastTickFinishedAt).getTime())
      : null;
    const runnerStalled = Boolean(
      payload.jobsRunner.enabled
      && !payload.jobsRunner.running
      && lastTickAgeMs !== null
      && lastTickAgeMs >= jobsStalledTickThresholdMs,
    );
    const runnerError = Boolean(payload.jobsRunner.lastTickError || payload.jobsRunner.lastPruneError);

    const activeAlerts: Array<{ key: string; source: 'RECONCILIATION' | 'JOBS'; severity: 'WARN'; message: string }> = [];

    for (const key of reconciliation.activeAlerts) {
      activeAlerts.push({
        key: `reconcile.${key}`,
        source: 'RECONCILIATION',
        severity: 'WARN',
        message: `Reconciliation check triggered: ${key}`,
      });
    }

    if (pendingAgeHigh) {
      activeAlerts.push({
        key: 'jobs.pendingAgeHigh',
        source: 'JOBS',
        severity: 'WARN',
        message: `Oldest pending job age ${oldestPendingAgeMs}ms exceeds threshold ${jobsPendingAgeThresholdMs}ms`,
      });
    }

    if (deadCountHigh) {
      activeAlerts.push({
        key: 'jobs.deadCountHigh',
        source: 'JOBS',
        severity: 'WARN',
        message: `Dead jobs count ${deadCount} exceeds threshold ${jobsDeadCountThreshold}`,
      });
    }

    if (deadLetterRateHigh) {
      activeAlerts.push({
        key: 'jobs.deadLetterRateHigh',
        source: 'JOBS',
        severity: 'WARN',
        message: `Dead-letter rate ${deadLetterRate} exceeds threshold ${jobsDeadLetterRateThreshold}`,
      });
    }

    if (runnerStalled) {
      activeAlerts.push({
        key: 'jobs.runnerStalled',
        source: 'JOBS',
        severity: 'WARN',
        message: `Jobs runner last tick age ${lastTickAgeMs}ms exceeds threshold ${jobsStalledTickThresholdMs}ms`,
      });
    }

    if (runnerError) {
      activeAlerts.push({
        key: 'jobs.runnerError',
        source: 'JOBS',
        severity: 'WARN',
        message: payload.jobsRunner.lastTickError ?? payload.jobsRunner.lastPruneError ?? 'Jobs runner error detected',
      });
    }

    return {
      status: activeAlerts.length === 0 ? 'OK' : 'WARN',
      generatedAt: new Date().toISOString(),
      config: {
        jobsPendingAgeThresholdMs,
        jobsDeadCountThreshold,
        jobsDeadLetterRateThreshold,
        jobsStalledTickThresholdMs,
      },
      checks: {
        reconciliation,
        jobs: {
          pendingAgeHigh: {
            active: pendingAgeHigh,
            oldestPendingAgeMs,
            thresholdMs: jobsPendingAgeThresholdMs,
          },
          deadCountHigh: {
            active: deadCountHigh,
            deadCount,
            threshold: jobsDeadCountThreshold,
          },
          deadLetterRateHigh: {
            active: deadLetterRateHigh,
            deadLetterRate,
            threshold: jobsDeadLetterRateThreshold,
          },
          runnerStalled: {
            active: runnerStalled,
            lastTickFinishedAt: payload.jobsRunner.lastTickFinishedAt,
            lastTickAgeMs,
            thresholdMs: jobsStalledTickThresholdMs,
          },
          runnerError: {
            active: runnerError,
            lastTickError: payload.jobsRunner.lastTickError,
            lastPruneError: payload.jobsRunner.lastPruneError,
          },
        },
      },
      activeAlerts,
    };
  }

  async listBackgroundJobs(payload: { status?: unknown; type?: unknown; limit?: unknown; offset?: unknown } = {}) {
    const status = this.parseBackgroundJobStatus(payload.status);
    if (payload.status !== undefined && status === null) {
      throw new BadRequestException('status must be one of PENDING, RETRYABLE, RUNNING, COMPLETED, DEAD, CANCELLED');
    }

    const type = this.parseBackgroundJobType(payload.type);
    if (payload.type !== undefined && type === null) {
      throw new BadRequestException('type must be a non-empty string up to 64 chars');
    }

    const limit = this.parseBackgroundJobListLimit(payload.limit);
    if (payload.limit !== undefined && limit === null) {
      throw new BadRequestException('limit must be an integer between 1 and 200');
    }

    const offset = this.parseBackgroundJobListOffset(payload.offset);
    if (payload.offset !== undefined && offset === null) {
      throw new BadRequestException('offset must be an integer between 0 and 10000');
    }

    const normalizedLimit = limit ?? 50;
    const normalizedOffset = offset ?? 0;

    const where = {
      ...(status ? { status } : {}),
      ...(type ? { type } : {}),
    };

    const [items, total] = await Promise.all([
      this.prisma.paymentBackgroundJob.findMany({
        where,
        orderBy: { createdAt: 'desc' },
        take: normalizedLimit,
        skip: normalizedOffset,
      }),
      this.prisma.paymentBackgroundJob.count({ where }),
    ]);

    return {
      filters: {
        status: status ?? null,
        type: type ?? null,
      },
      page: {
        limit: normalizedLimit,
        offset: normalizedOffset,
        total,
      },
      items,
    };
  }

  async requeueBackgroundJob(jobId: string, payload: { delaySeconds?: unknown } = {}) {
    const delaySeconds = this.parseBackgroundJobDelaySeconds(payload.delaySeconds);
    if (payload.delaySeconds !== undefined && delaySeconds === null) {
      throw new BadRequestException('delaySeconds must be an integer between 0 and 3600');
    }

    const job = await this.prisma.paymentBackgroundJob.findUnique({ where: { id: jobId } });
    if (!job) {
      throw new NotFoundException('Background job not found');
    }

    if (job.status === 'PENDING' || job.status === 'RETRYABLE') {
      return {
        changed: false,
        id: job.id,
        status: job.status,
        runAt: job.runAt.toISOString(),
      };
    }

    if (job.status === 'RUNNING') {
      throw new BadRequestException('Cannot requeue a RUNNING job');
    }

    const runAt = new Date(Date.now() + (delaySeconds ?? 0) * 1000);
    const updated = await this.prisma.paymentBackgroundJob.update({
      where: { id: job.id },
      data: {
        status: 'PENDING',
        runAt,
        processedAt: null,
        lastError: null,
        attempts: 0,
      },
    });

    return {
      changed: true,
      id: updated.id,
      status: updated.status,
      runAt: updated.runAt.toISOString(),
    };
  }

  async cancelBackgroundJob(jobId: string) {
    const job = await this.prisma.paymentBackgroundJob.findUnique({ where: { id: jobId } });
    if (!job) {
      throw new NotFoundException('Background job not found');
    }

    if (job.status === 'CANCELLED') {
      return {
        changed: false,
        id: job.id,
        status: job.status,
      };
    }

    if (job.status === 'COMPLETED') {
      return {
        changed: false,
        id: job.id,
        status: job.status,
      };
    }

    if (job.status === 'RUNNING') {
      throw new BadRequestException('Cannot cancel a RUNNING job');
    }

    const updated = await this.prisma.paymentBackgroundJob.update({
      where: { id: job.id },
      data: {
        status: 'CANCELLED',
        processedAt: new Date(),
      },
    });

    return {
      changed: true,
      id: updated.id,
      status: updated.status,
    };
  }

  async completeBackgroundJob(jobId: string) {
    const job = await this.prisma.paymentBackgroundJob.findUnique({ where: { id: jobId } });
    if (!job) {
      throw new NotFoundException('Background job not found');
    }

    if (job.status === 'COMPLETED') {
      return {
        changed: false,
        id: job.id,
        status: job.status,
      };
    }

    if (job.status === 'RUNNING') {
      throw new BadRequestException('Cannot force-complete a RUNNING job');
    }

    const updated = await this.prisma.paymentBackgroundJob.update({
      where: { id: job.id },
      data: {
        status: 'COMPLETED',
        processedAt: new Date(),
        lastError: null,
      },
    });

    return {
      changed: true,
      id: updated.id,
      status: updated.status,
    };
  }

  async processDueBackgroundJobs(limit: number) {
    const normalizedLimit = Number.isInteger(limit) && limit > 0 ? Math.min(limit, 100) : 25;
    let processed = 0;

    for (let index = 0; index < normalizedLimit; index += 1) {
      const job = await this.claimNextDueBackgroundJob();
      if (!job) {
        break;
      }

      try {
        await this.executeBackgroundJob(job);
        await this.prisma.paymentBackgroundJob.update({
          where: { id: job.id },
          data: {
            status: 'COMPLETED',
            processedAt: new Date(),
            lastError: null,
          },
        });
      } catch (error) {
        const attempt = job.attempts;
        const maxAttempts = job.maxAttempts;
        const willRetry = attempt < maxAttempts;
        const retryDelayMs = this.computeJobRetryDelayMs(attempt);

        await this.prisma.paymentBackgroundJob.update({
          where: { id: job.id },
          data: {
            status: willRetry ? 'RETRYABLE' : 'DEAD',
            runAt: willRetry ? new Date(Date.now() + retryDelayMs) : job.runAt,
            processedAt: willRetry ? null : new Date(),
            lastError: error instanceof Error ? error.message : 'unknown error',
          },
        });

        if (!willRetry) {
          await this.recordDeadLetterEscalation(job, error, attempt, maxAttempts);
        }
      }

      processed += 1;
    }

    return { processed };
  }

  async pruneCompletedBackgroundJobs(payload: { olderThanHours?: unknown; limit?: unknown } = {}) {
    const olderThanHours = this.parsePruneOlderThanHours(payload.olderThanHours);
    if (payload.olderThanHours !== undefined && olderThanHours === null) {
      throw new BadRequestException('olderThanHours must be an integer between 1 and 8760');
    }

    const limit = this.parsePruneLimit(payload.limit);
    if (payload.limit !== undefined && limit === null) {
      throw new BadRequestException('limit must be an integer between 1 and 2000');
    }

    const normalizedOlderThanHours = olderThanHours ?? 168;
    const normalizedLimit = limit ?? 200;
    const cutoff = new Date(Date.now() - normalizedOlderThanHours * 60 * 60 * 1000);

    const candidateIds = await this.prisma.paymentBackgroundJob.findMany({
      where: {
        status: 'COMPLETED',
        processedAt: { lte: cutoff },
      },
      orderBy: { processedAt: 'asc' },
      take: normalizedLimit,
      select: { id: true },
    });

    if (candidateIds.length === 0) {
      return {
        olderThanHours: normalizedOlderThanHours,
        limit: normalizedLimit,
        cutoffAt: cutoff.toISOString(),
        candidates: 0,
        pruned: 0,
      };
    }

    const deleted = await this.prisma.paymentBackgroundJob.deleteMany({
      where: {
        id: { in: candidateIds.map((item) => item.id) },
      },
    });

    return {
      olderThanHours: normalizedOlderThanHours,
      limit: normalizedLimit,
      cutoffAt: cutoff.toISOString(),
      candidates: candidateIds.length,
      pruned: deleted.count,
    };
  }

  async enqueueBookingConfirmationJob(bookingId: string, paymentId: string, source: 'WEBHOOK' | 'RECONCILE') {
    return this.enqueueBackgroundJob('BOOKING_CONFIRMATION_NOTIFICATION', {
      bookingId,
      paymentId,
      source,
    }, undefined, 5, bookingId);
  }

  private async createRefundRequest(
    payment: { id: string; bookingId: string; provider: string; currency: string; amount: Prisma.Decimal; status: string },
    normalized: { amount: number | null; reason: string | null; idempotencyKey: string | null },
    actor: { actorUserId: string; actorRole: string },
  ) {
    if (payment.status !== 'SUCCEEDED') {
      throw new BadRequestException('Refunds can only be requested for succeeded payments');
    }

    const refunds = await this.readRefundRecords();
    if (normalized.idempotencyKey) {
      const idempotentExisting = refunds.find((record) => record.idempotencyKey === normalized.idempotencyKey);
      if (idempotentExisting) {
        return {
          created: false,
          refund: idempotentExisting,
        };
      }
    }

    const completedOrApprovedAmount = refunds
      .filter((record) => record.paymentId === payment.id && ['APPROVED', 'COMPLETED'].includes(record.status))
      .reduce((sum, record) => sum + record.requestedAmount, 0);
    const pendingAmount = refunds
      .filter((record) => record.paymentId === payment.id && record.status === 'PENDING')
      .reduce((sum, record) => sum + record.requestedAmount, 0);

    const paymentAmount = Number(payment.amount);
    const availableAmount = Math.max(paymentAmount - completedOrApprovedAmount - pendingAmount, 0);
    const requestedAmount = normalized.amount ?? availableAmount;
    if (requestedAmount <= 0) {
      throw new BadRequestException('No refundable balance remains for this payment');
    }

    if (requestedAmount > availableAmount) {
      throw new BadRequestException(`Refund amount exceeds available refundable balance (${availableAmount.toFixed(2)})`);
    }

    const nowIso = new Date().toISOString();
    const created: RefundRecord = {
      id: `refund_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
      bookingId: payment.bookingId,
      paymentId: payment.id,
      provider: payment.provider,
      currency: payment.currency,
      requestedAmount: Number(requestedAmount.toFixed(2)),
      reason: normalized.reason,
      status: 'PENDING',
      idempotencyKey: normalized.idempotencyKey,
      actorUserId: actor.actorUserId,
      actorRole: actor.actorRole,
      createdAt: nowIso,
      updatedAt: nowIso,
    };

    await this.writeRefundRecords([created, ...refunds]);

    return {
      created: true,
      refund: created,
      balances: {
        paymentAmount: Number(paymentAmount.toFixed(2)),
        alreadyCommittedAmount: Number(completedOrApprovedAmount.toFixed(2)),
        pendingAmount: Number((pendingAmount + created.requestedAmount).toFixed(2)),
        remainingAmount: Number((availableAmount - created.requestedAmount).toFixed(2)),
      },
    };
  }

  private async resolvePaymentForAdjustment(bookingId: string | null, paymentId: string | null) {
    if (!bookingId && !paymentId) {
      throw new BadRequestException('bookingId or paymentId is required');
    }

    const paymentByBooking = bookingId
      ? await this.prisma.payment.findUnique({ where: { bookingId } })
      : null;
    const paymentById = paymentId
      ? await this.prisma.payment.findUnique({ where: { id: paymentId } })
      : null;

    const resolved = paymentById ?? paymentByBooking;
    if (!resolved) {
      throw new NotFoundException('Payment not found');
    }

    if (paymentByBooking && paymentById && paymentByBooking.id !== paymentById.id) {
      throw new BadRequestException('bookingId and paymentId refer to different payments');
    }

    return resolved;
  }

  private parseRefundRequestPayload(payload: RefundRequestPayload) {
    const bookingId = this.parseOptionalId(payload.bookingId);
    const paymentId = this.parseOptionalId(payload.paymentId);
    const amount = payload.amount === undefined || payload.amount === null
      ? null
      : this.parsePositiveMoney(payload.amount, 'amount');
    const reason = this.parseOptionalText(payload.reason, 500);
    const idempotencyKey = this.parseOptionalKey(payload.idempotencyKey, 100);

    return {
      bookingId,
      paymentId,
      amount,
      reason,
      idempotencyKey,
    };
  }

  private parseDisputePayload(payload: DisputeRequestPayload) {
    const bookingId = this.parseOptionalId(payload.bookingId);
    const paymentId = this.parseOptionalId(payload.paymentId);
    const reason = this.parseRequiredText(payload.reason, 'reason', 250);
    const details = this.parseOptionalText(payload.details, 2000);
    const idempotencyKey = this.parseOptionalKey(payload.idempotencyKey, 100);

    return {
      bookingId,
      paymentId,
      reason,
      details,
      idempotencyKey,
    };
  }

  private parseRefundStatusFilter(value: unknown): RefundRecordStatus | null {
    if (value === undefined) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('status must be one of PENDING, APPROVED, COMPLETED, REJECTED, FAILED');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'PENDING' || normalized === 'APPROVED' || normalized === 'COMPLETED' || normalized === 'REJECTED' || normalized === 'FAILED') {
      return normalized;
    }

    throw new BadRequestException('status must be one of PENDING, APPROVED, COMPLETED, REJECTED, FAILED');
  }

  private parseDisputeStatusFilter(value: unknown): DisputeRecordStatus | null {
    if (value === undefined) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('status must be one of OPEN, UNDER_REVIEW, RESOLVED, REJECTED');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'OPEN' || normalized === 'UNDER_REVIEW' || normalized === 'RESOLVED' || normalized === 'REJECTED') {
      return normalized;
    }

    throw new BadRequestException('status must be one of OPEN, UNDER_REVIEW, RESOLVED, REJECTED');
  }

  private parseLedgerEntryTypeFilter(value: unknown): FinanceLedgerEntryType | null {
    if (value === undefined) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('entryType must be one of PAYMENT_CAPTURED, REFUND_COMPLETED');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'PAYMENT_CAPTURED' || normalized === 'REFUND_COMPLETED') {
      return normalized;
    }

    throw new BadRequestException('entryType must be one of PAYMENT_CAPTURED, REFUND_COMPLETED');
  }

  private parseRefundStatusTransition(value: unknown): RefundRecordStatus {
    if (typeof value !== 'string') {
      throw new BadRequestException('status is required and must be one of APPROVED, COMPLETED, REJECTED, FAILED');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'APPROVED' || normalized === 'COMPLETED' || normalized === 'REJECTED' || normalized === 'FAILED') {
      return normalized;
    }

    throw new BadRequestException('status must be one of APPROVED, COMPLETED, REJECTED, FAILED');
  }

  private parseDisputeStatusTransition(value: unknown): DisputeRecordStatus {
    if (typeof value !== 'string') {
      throw new BadRequestException('status is required and must be one of UNDER_REVIEW, RESOLVED, REJECTED');
    }

    const normalized = value.trim().toUpperCase();
    if (normalized === 'UNDER_REVIEW' || normalized === 'RESOLVED' || normalized === 'REJECTED') {
      return normalized;
    }

    throw new BadRequestException('status must be one of UNDER_REVIEW, RESOLVED, REJECTED');
  }

  private ensureRefundTransitionAllowed(current: RefundRecordStatus, next: RefundRecordStatus) {
    const transitions: Record<RefundRecordStatus, RefundRecordStatus[]> = {
      PENDING: ['APPROVED', 'REJECTED', 'FAILED'],
      APPROVED: ['COMPLETED', 'FAILED'],
      COMPLETED: [],
      REJECTED: [],
      FAILED: [],
    };

    if (!transitions[current].includes(next)) {
      throw new BadRequestException(`Refund status transition ${current} -> ${next} is not allowed`);
    }
  }

  private ensureDisputeTransitionAllowed(current: DisputeRecordStatus, next: DisputeRecordStatus) {
    const transitions: Record<DisputeRecordStatus, DisputeRecordStatus[]> = {
      OPEN: ['UNDER_REVIEW', 'RESOLVED', 'REJECTED'],
      UNDER_REVIEW: ['RESOLVED', 'REJECTED'],
      RESOLVED: [],
      REJECTED: [],
    };

    if (!transitions[current].includes(next)) {
      throw new BadRequestException(`Dispute status transition ${current} -> ${next} is not allowed`);
    }
  }

  private parsePagination(limitValue: unknown, offsetValue: unknown, maxLimit: number) {
    const limitParsed = this.parseBackgroundJobListLimit(limitValue);
    if (limitValue !== undefined && limitParsed === null) {
      throw new BadRequestException(`limit must be an integer between 1 and ${maxLimit}`);
    }

    const offsetParsed = this.parseBackgroundJobListOffset(offsetValue);
    if (offsetValue !== undefined && offsetParsed === null) {
      throw new BadRequestException('offset must be an integer between 0 and 10000');
    }

    return {
      limit: Math.min(limitParsed ?? 50, maxLimit),
      offset: offsetParsed ?? 0,
    };
  }

  private async applyBookingRefundStatusFromRecords(bookingId: string, paymentId: string, refunds: RefundRecord[]) {
    const payment = await this.prisma.payment.findUnique({
      where: { id: paymentId },
      select: { amount: true },
    });
    if (!payment) {
      return null;
    }

    const completedAmount = refunds
      .filter((record) => record.paymentId === paymentId && record.status === 'COMPLETED')
      .reduce((sum, record) => sum + record.requestedAmount, 0);
    const paymentAmount = Number(payment.amount);
    const fullyRefunded = completedAmount >= (paymentAmount - 0.009);

    const booking = await this.prisma.booking.findUnique({
      where: { id: bookingId },
      select: { id: true, status: true },
    });
    if (!booking) {
      return {
        id: bookingId,
        adjusted: false,
        status: null,
        fullyRefunded,
      };
    }

    if (!fullyRefunded || booking.status === 'REFUNDED') {
      return {
        id: booking.id,
        adjusted: false,
        status: booking.status,
        fullyRefunded,
      };
    }

    const updated = await this.prisma.booking.update({
      where: { id: booking.id },
      data: {
        status: 'REFUNDED',
        holdExpiresAt: null,
        fareLockExpiresAt: null,
      },
      select: {
        id: true,
        status: true,
      },
    });

    return {
      id: updated.id,
      adjusted: true,
      status: updated.status,
      fullyRefunded,
    };
  }

  private parseSettlementDateRange(payload: SettlementReportPayload) {
    const now = new Date();
    const defaultFrom = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
    const from = payload.from !== undefined ? this.parseDate(payload.from, 'from') : defaultFrom;
    const to = payload.to !== undefined ? this.parseDate(payload.to, 'to') : now;

    if (from.getTime() > to.getTime()) {
      throw new BadRequestException('from must be before or equal to to');
    }

    const maxWindowMs = 180 * 24 * 60 * 60 * 1000;
    if (to.getTime() - from.getTime() > maxWindowMs) {
      throw new BadRequestException('date range cannot exceed 180 days');
    }

    return { from, to };
  }

  private async readRefundRecords(): Promise<RefundRecord[]> {
    return this.readJsonArrayConfig<RefundRecord>(this.refundConfigKey());
  }

  private async writeRefundRecords(records: RefundRecord[]) {
    await this.writeJsonArrayConfig(this.refundConfigKey(), records);
  }

  private async readDisputeRecords(): Promise<DisputeRecord[]> {
    return this.readJsonArrayConfig<DisputeRecord>(this.disputeConfigKey());
  }

  private async writeDisputeRecords(records: DisputeRecord[]) {
    await this.writeJsonArrayConfig(this.disputeConfigKey(), records);
  }

  private async readFinanceLedgerEntries(): Promise<FinanceLedgerEntry[]> {
    return this.readJsonArrayConfig<FinanceLedgerEntry>(this.financeLedgerConfigKey());
  }

  private async writeFinanceLedgerEntries(records: FinanceLedgerEntry[]) {
    await this.writeJsonArrayConfig(this.financeLedgerConfigKey(), records);
  }

  private async readTaxInvoiceRecords(): Promise<TaxInvoiceRecord[]> {
    return this.readJsonArrayConfig<TaxInvoiceRecord>(this.taxInvoicesConfigKey());
  }

  private async writeTaxInvoiceRecords(records: TaxInvoiceRecord[]) {
    await this.writeJsonArrayConfig(this.taxInvoicesConfigKey(), records);
  }

  private async readJsonArrayConfig<T>(key: string): Promise<T[]> {
    const row = await this.prisma.appConfig.findUnique({ where: { key } });
    if (!row) {
      return [];
    }

    const value = row.value as Record<string, unknown>;
    const entries = Array.isArray(value.items) ? (value.items as T[]) : [];
    return entries;
  }

  private async writeJsonArrayConfig<T>(key: string, items: T[]) {
    const trimmedItems = items.slice(0, 1000);
    const value = {
      updatedAt: new Date().toISOString(),
      items: trimmedItems,
    };

    await this.prisma.appConfig.upsert({
      where: { key },
      update: {
        value: value as unknown as Prisma.JsonObject,
      },
      create: {
        key,
        value: value as unknown as Prisma.JsonObject,
      },
    });
  }

  private refundConfigKey() {
    return 'payments:refund_requests:v1';
  }

  private disputeConfigKey() {
    return 'payments:disputes:v1';
  }

  private financeLedgerConfigKey() {
    return 'payments:finance-ledger:v1';
  }

  private taxInvoicesConfigKey() {
    return 'payments:tax-invoices:v1';
  }

  private providerCircuitConfigKey(provider: ProviderName) {
    return `payments:provider:circuit:v1:${provider}`;
  }

  private deadLetterEscalationConfigKey() {
    return 'payments:dead-letter-escalations:v1';
  }

  private async processWebhook(
    provider: ProviderName,
    signature: string | undefined,
    payload: unknown,
  ) {
    const adapter = this.getAdapter(provider);
    const webhookSecret = this.getWebhookSecret(provider);
    const event = adapter.verifyAndParseWebhook(payload, signature, webhookSecret);

    if (!event.id || !event.type) {
      throw new UnauthorizedException('Invalid webhook signature or payload');
    }

    let eventRecordId: string;
    try {
      const createdEvent = await this.prisma.paymentWebhookEvent.create({
        data: {
          provider,
          eventId: event.id,
          eventType: event.type,
          payload: event.payload as Prisma.JsonObject,
          status: 'PROCESSED',
        },
      });
      eventRecordId = createdEvent.id;
    } catch (error) {
      if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === 'P2002') {
        return {
          received: true,
          idempotent: true,
        };
      }
      throw error;
    }

    try {
      await this.applyWebhookEvent(provider, adapter, event);
    } catch (error) {
      await this.prisma.paymentWebhookEvent.update({
        where: { id: eventRecordId },
        data: { status: 'FAILED' },
      });

      await this.enqueueWebhookRetryJob({
        provider,
        eventRecordId,
        event,
        errorMessage: error instanceof Error ? error.message : 'unknown error',
      });

      return {
        received: true,
        idempotent: false,
        queuedRetry: true,
      };
    }

    return {
      received: true,
      idempotent: false,
    };
  }

  private async applyWebhookEvent(
    provider: ProviderName,
    adapter: PaymentProviderAdapter,
    event: { id: string; type: string; paymentIntentId?: string; bookingId?: string },
  ) {
    const payment = await this.resolvePaymentFromEvent(provider, event.paymentIntentId, event.bookingId);
    if (!payment) {
      return;
    }

    const providerStatus = await this.resolveProviderStatus(provider, adapter, event.paymentIntentId, payment.providerId ?? undefined);

    if (this.isSucceededEvent(event.type, providerStatus)) {
      await this.prisma.payment.update({
        where: { id: payment.id },
        data: { status: 'SUCCEEDED' },
      });

      await this.prisma.booking.update({
        where: { id: payment.bookingId },
        data: { status: 'CONFIRMED' },
      });

      await this.loyaltyService.awardPointsForConfirmedBooking(payment.bookingId, 'PAYMENT_WEBHOOK');

      await this.enqueueBookingConfirmationJob(payment.bookingId, payment.id, 'WEBHOOK');
    }

    if (this.isFailedEvent(event.type, providerStatus)) {
      await this.prisma.payment.update({
        where: { id: payment.id },
        data: { status: 'FAILED' },
      });
    }
  }

  private async enqueueWebhookRetryJob(payload: {
    provider: ProviderName;
    eventRecordId: string;
    event: { id: string; type: string; paymentIntentId?: string; bookingId?: string };
    errorMessage: string;
  }) {
    const dedupeKey = `${payload.provider}:${payload.event.id}`;
    return this.enqueueBackgroundJob('WEBHOOK_PROCESS_RETRY', payload, undefined, 5, dedupeKey);
  }

  private async enqueueBackgroundJob(
    type: BackgroundJobType,
    payload: Prisma.JsonObject,
    runAt?: Date,
    maxAttempts = 5,
    dedupeKey?: string,
  ) {
    if (!dedupeKey) {
      return this.prisma.paymentBackgroundJob.create({
        data: {
          type,
          status: 'PENDING',
          attempts: 0,
          maxAttempts,
          runAt: runAt ?? new Date(),
          payload,
        },
      });
    }

    const existing = await this.prisma.paymentBackgroundJob.findFirst({
      where: {
        type,
        dedupeKey,
      },
    });
    if (existing) {
      return existing;
    }

    try {
      return await this.prisma.paymentBackgroundJob.create({
        data: {
          type,
          dedupeKey,
          status: 'PENDING',
          attempts: 0,
          maxAttempts,
          runAt: runAt ?? new Date(),
          payload,
        },
      });
    } catch (error) {
      if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === 'P2002') {
        const raced = await this.prisma.paymentBackgroundJob.findFirst({
          where: {
            type,
            dedupeKey,
          },
        });
        if (raced) {
          return raced;
        }
      }

      throw error;
    }
  }

  private async claimNextDueBackgroundJob() {
    const now = new Date();
    return this.prisma.$transaction(async (transaction) => {
      const candidate = await transaction.paymentBackgroundJob.findFirst({
        where: {
          status: { in: ['PENDING', 'RETRYABLE'] },
          runAt: { lte: now },
        },
        orderBy: { createdAt: 'asc' },
      });

      if (!candidate) {
        return null;
      }

      const claimed = await transaction.paymentBackgroundJob.updateMany({
        where: {
          id: candidate.id,
          status: { in: ['PENDING', 'RETRYABLE'] },
          runAt: { lte: now },
        },
        data: {
          status: 'RUNNING',
          attempts: { increment: 1 },
          lastError: null,
        },
      });

      if (claimed.count === 0) {
        return null;
      }

      return transaction.paymentBackgroundJob.findUnique({ where: { id: candidate.id } });
    });
  }

  private async executeBackgroundJob(job: {
    id: string;
    type: string;
    payload: Prisma.JsonValue;
  }) {
    if (job.type === 'BOOKING_CONFIRMATION_NOTIFICATION') {
      const payload = this.parseBookingConfirmationPayload(job.payload);
      if (!payload) {
        throw new Error('invalid booking confirmation job payload');
      }

      await this.executeBookingConfirmationJob(payload);
      return;
    }

    if (job.type === 'WEBHOOK_PROCESS_RETRY') {
      const payload = this.parseWebhookRetryPayload(job.payload);
      if (!payload) {
        throw new Error('invalid webhook retry job payload');
      }

      await this.executeWebhookRetryJob(payload);
      return;
    }

    throw new Error(`unsupported job type: ${job.type}`);
  }

  private async executeBookingConfirmationJob(payload: { bookingId: string; paymentId: string; source: string }) {
    const [booking, payment] = await Promise.all([
      this.prisma.booking.findUnique({ where: { id: payload.bookingId } }),
      this.prisma.payment.findUnique({ where: { id: payload.paymentId } }),
    ]);

    if (!booking) {
      throw new Error('booking not found');
    }

    if (!payment) {
      throw new Error('payment not found');
    }

    if (booking.status !== 'CONFIRMED' || payment.status !== 'SUCCEEDED') {
      throw new Error('booking confirmation prerequisites are not met');
    }
  }

  private async executeWebhookRetryJob(payload: {
    provider: ProviderName;
    eventRecordId: string;
    event: { id: string; type: string; paymentIntentId?: string; bookingId?: string };
  }) {
    const adapter = this.getAdapter(payload.provider);
    await this.applyWebhookEvent(payload.provider, adapter, payload.event);
    await this.prisma.paymentWebhookEvent.update({
      where: { id: payload.eventRecordId },
      data: { status: 'PROCESSED' },
    });
  }

  private parseBookingConfirmationPayload(payload: Prisma.JsonValue): { bookingId: string; paymentId: string; source: string } | null {
    if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
      return null;
    }

    const value = payload as Record<string, unknown>;
    if (typeof value.bookingId !== 'string' || typeof value.paymentId !== 'string' || typeof value.source !== 'string') {
      return null;
    }

    return {
      bookingId: value.bookingId,
      paymentId: value.paymentId,
      source: value.source,
    };
  }

  private parseWebhookRetryPayload(payload: Prisma.JsonValue): {
    provider: ProviderName;
    eventRecordId: string;
    event: { id: string; type: string; paymentIntentId?: string; bookingId?: string };
  } | null {
    if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
      return null;
    }

    const value = payload as Record<string, unknown>;
    const event = value.event;
    if (!event || typeof event !== 'object' || Array.isArray(event)) {
      return null;
    }

    const provider = this.parseProviderFilter(value.provider);
    if (!provider || typeof value.eventRecordId !== 'string') {
      return null;
    }

    const eventValue = event as Record<string, unknown>;
    if (typeof eventValue.id !== 'string' || typeof eventValue.type !== 'string') {
      return null;
    }

    return {
      provider,
      eventRecordId: value.eventRecordId,
      event: {
        id: eventValue.id,
        type: eventValue.type,
        paymentIntentId: typeof eventValue.paymentIntentId === 'string' ? eventValue.paymentIntentId : undefined,
        bookingId: typeof eventValue.bookingId === 'string' ? eventValue.bookingId : undefined,
      },
    };
  }

  private computeJobRetryDelayMs(attempt: number) {
    const maxDelayMs = 10 * 60 * 1000;
    const baseDelayMs = 30 * 1000;
    const delayMs = baseDelayMs * 2 ** Math.max(0, attempt - 1);
    return Math.min(delayMs, maxDelayMs);
  }

  private async resolveProviderStatus(
    provider: ProviderName,
    adapter: PaymentProviderAdapter,
    eventReferenceId?: string,
    paymentReferenceId?: string,
  ): Promise<ProviderTransactionStatus | null> {
    if (provider !== 'BML' || !adapter.fetchTransactionStatus) {
      return null;
    }

    const referenceId = eventReferenceId ?? paymentReferenceId;
    if (!referenceId) {
      return null;
    }

    return this.fetchProviderStatusWithHardening(provider, adapter, referenceId);
  }

  private async fetchWithRetry<T>(factory: () => Promise<T>, retries: number): Promise<T> {
    let attempt = 0;
    let delayMs = 250;

    while (true) {
      try {
        return await factory();
      } catch (error) {
        if (attempt >= retries) {
          throw error;
        }

        await new Promise((resolve) => setTimeout(resolve, delayMs));
        delayMs *= 2;
        attempt += 1;
      }
    }
  }

  private async createIntentWithHardening(
    provider: ProviderName,
    adapter: PaymentProviderAdapter,
    input: { bookingId: string; amount: number; currency: string; metadata?: Record<string, string> },
  ) {
    return this.runProviderOperation(provider, 'create-intent', async () => {
      const timeoutMs = this.readPositiveIntEnv('PAYMENTS_PROVIDER_CREATE_INTENT_TIMEOUT_MS', 8000);
      return this.withTimeout(adapter.createIntent(input), timeoutMs, `create-intent timed out after ${timeoutMs}ms`);
    });
  }

  private async fetchProviderStatusWithHardening(
    provider: ProviderName,
    adapter: PaymentProviderAdapter,
    referenceId: string,
  ) {
    return this.runProviderOperation(provider, 'fetch-status', async () => {
      const timeoutMs = this.readPositiveIntEnv('PAYMENTS_PROVIDER_STATUS_TIMEOUT_MS', 5000);
      return this.fetchWithRetry(
        () => this.withTimeout(adapter.fetchTransactionStatus!(referenceId), timeoutMs, `fetch-status timed out after ${timeoutMs}ms`),
        2,
      );
    });
  }

  private async runProviderOperation<T>(provider: ProviderName, operation: string, factory: () => Promise<T>): Promise<T> {
    const threshold = this.readPositiveIntEnv('PAYMENTS_PROVIDER_CIRCUIT_FAILURE_THRESHOLD', 3);
    const cooldownMs = this.readPositiveIntEnv('PAYMENTS_PROVIDER_CIRCUIT_COOLDOWN_MS', 120000);
    const now = Date.now();

    const state = await this.readProviderCircuitState(provider);
    if (state.openUntil) {
      const openUntilMs = new Date(state.openUntil).getTime();
      if (Number.isFinite(openUntilMs) && openUntilMs > now) {
        throw new ServiceUnavailableException(`Payment provider ${provider} is temporarily unavailable (${operation})`);
      }
    }

    try {
      const result = await factory();
      await this.writeProviderCircuitState(provider, {
        ...state,
        provider,
        consecutiveFailures: 0,
        openedAt: null,
        openUntil: null,
        lastError: null,
        lastSuccessAt: new Date().toISOString(),
      });
      return result;
    } catch (error) {
      const failures = state.consecutiveFailures + 1;
      const shouldOpen = failures >= threshold;
      const openedAt = shouldOpen ? new Date().toISOString() : state.openedAt;
      const openUntil = shouldOpen ? new Date(now + cooldownMs).toISOString() : null;

      await this.writeProviderCircuitState(provider, {
        ...state,
        provider,
        consecutiveFailures: failures,
        openedAt,
        openUntil,
        lastError: error instanceof Error ? error.message : 'unknown error',
        lastFailureAt: new Date().toISOString(),
      });

      throw error;
    }
  }

  private withTimeout<T>(promise: Promise<T>, timeoutMs: number, message: string): Promise<T> {
    return new Promise<T>((resolve, reject) => {
      const timer = setTimeout(() => {
        reject(new Error(message));
      }, timeoutMs);

      promise
        .then((value) => {
          clearTimeout(timer);
          resolve(value);
        })
        .catch((error) => {
          clearTimeout(timer);
          reject(error);
        });
    });
  }

  private async readProviderCircuitState(provider: ProviderName): Promise<ProviderCircuitState> {
    const key = this.providerCircuitConfigKey(provider);
    const row = await this.prisma.appConfig.findUnique({ where: { key } });
    const value = row?.value as Record<string, unknown> | undefined;

    return {
      provider,
      consecutiveFailures: typeof value?.consecutiveFailures === 'number' ? value.consecutiveFailures : 0,
      openedAt: typeof value?.openedAt === 'string' ? value.openedAt : null,
      openUntil: typeof value?.openUntil === 'string' ? value.openUntil : null,
      lastError: typeof value?.lastError === 'string' ? value.lastError : null,
      lastFailureAt: typeof value?.lastFailureAt === 'string' ? value.lastFailureAt : null,
      lastSuccessAt: typeof value?.lastSuccessAt === 'string' ? value.lastSuccessAt : null,
    };
  }

  private async writeProviderCircuitState(provider: ProviderName, state: ProviderCircuitState) {
    await this.prisma.appConfig.upsert({
      where: { key: this.providerCircuitConfigKey(provider) },
      update: {
        value: state as unknown as Prisma.JsonObject,
      },
      create: {
        key: this.providerCircuitConfigKey(provider),
        value: state as unknown as Prisma.JsonObject,
      },
    });
  }

  private async recordDeadLetterEscalation(
    job: { id: string; type: string },
    error: unknown,
    attempts: number,
    maxAttempts: number,
  ) {
    const records = await this.readJsonArrayConfig<DeadLetterEscalationRecord>(this.deadLetterEscalationConfigKey());
    const item: DeadLetterEscalationRecord = {
      id: `dead_letter_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
      type: job.type,
      jobId: job.id,
      attempts,
      maxAttempts,
      escalatedAt: new Date().toISOString(),
      error: error instanceof Error ? error.message : 'unknown error',
    };

    await this.writeJsonArrayConfig<DeadLetterEscalationRecord>(
      this.deadLetterEscalationConfigKey(),
      [item, ...records].slice(0, 200),
    );
  }

  private isSucceededEvent(eventType: string, providerStatus: ProviderTransactionStatus | null): boolean {
    if (providerStatus?.state === 'SUCCEEDED') {
      return true;
    }

    if (providerStatus?.state === 'FAILED') {
      return false;
    }

    return eventType === 'payment_intent.succeeded' || eventType === 'checkout.session.completed';
  }

  private isFailedEvent(eventType: string, providerStatus: ProviderTransactionStatus | null): boolean {
    if (providerStatus?.state === 'FAILED') {
      return true;
    }

    if (providerStatus?.state === 'SUCCEEDED') {
      return false;
    }

    return eventType === 'payment_intent.payment_failed';
  }

  private async resolvePaymentFromEvent(provider: ProviderName, paymentIntentId?: string, bookingId?: string) {
    if (paymentIntentId) {
      const paymentByIntent = await this.prisma.payment.findFirst({
        where: {
          provider,
          providerId: paymentIntentId,
        },
      });

      if (paymentByIntent) {
        return paymentByIntent;
      }
    }

    if (bookingId) {
      return this.prisma.payment.findUnique({ where: { bookingId } });
    }

    return null;
  }

  private resolveBookingVendorId(booking: {
    accommodation: { vendorId: string } | null;
    transport: { vendorId: string | null } | null;
  } | null): string | null {
    if (!booking) {
      return null;
    }

    const accommodationVendorId = booking.accommodation?.vendorId ?? null;
    const transportVendorId = booking.transport?.vendorId ?? null;

    if (accommodationVendorId && transportVendorId) {
      return accommodationVendorId === transportVendorId ? accommodationVendorId : null;
    }

    return accommodationVendorId ?? transportVendorId;
  }

  private parseActorVendorId(value: unknown): string {
    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new ForbiddenException('Vendor scope is missing for authenticated vendor user');
    }

    return value.trim();
  }

  private generateTaxInvoiceNumber(now: Date, existing: TaxInvoiceRecord[]) {
    const y = now.getUTCFullYear();
    const m = String(now.getUTCMonth() + 1).padStart(2, '0');
    const d = String(now.getUTCDate()).padStart(2, '0');
    const prefix = `INV-${y}${m}${d}`;

    let sequence = existing.filter((item) => item.invoiceNumber.startsWith(prefix)).length + 1;
    let invoiceNumber = `${prefix}-${String(sequence).padStart(4, '0')}`;
    const used = new Set(existing.map((item) => item.invoiceNumber));
    while (used.has(invoiceNumber)) {
      sequence += 1;
      invoiceNumber = `${prefix}-${String(sequence).padStart(4, '0')}`;
    }

    return invoiceNumber;
  }

  private getAdapter(provider: ProviderName): PaymentProviderAdapter {
    if (provider === 'BML') {
      return this.bmlAdapter;
    }

    if (provider === 'MIB') {
      return this.mibAdapter;
    }

    return this.stripeAdapter;
  }

  private getWebhookSecret(provider: ProviderName): string {
    if (provider === 'BML') {
      return process.env.BML_API_KEY ?? process.env.BML_WEBHOOK_SECRET ?? 'dev-bml-webhook-secret';
    }

    if (provider === 'MIB') {
      return process.env.MIB_WEBHOOK_SECRET ?? 'dev-mib-webhook-secret';
    }

    return process.env.STRIPE_WEBHOOK_SECRET ?? 'dev-webhook-secret';
  }

  private parseOptionalId(value: unknown): string | null {
    if (value === undefined || value === null) {
      return null;
    }

    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException('id fields must be non-empty strings when provided');
    }

    return value.trim();
  }

  private parsePositiveMoney(value: unknown, fieldName: string): number {
    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isFinite(parsed) || parsed <= 0) {
      throw new BadRequestException(`${fieldName} must be a positive number`);
    }

    return Number(parsed.toFixed(2));
  }

  private parseOptionalText(value: unknown, maxLength: number): string | null {
    if (value === undefined || value === null) {
      return null;
    }

    if (typeof value !== 'string') {
      throw new BadRequestException('text fields must be strings when provided');
    }

    const normalized = value.trim();
    if (normalized.length === 0) {
      return null;
    }

    if (normalized.length > maxLength) {
      throw new BadRequestException(`text exceeds max length of ${maxLength}`);
    }

    return normalized;
  }

  private parseRequiredText(value: unknown, fieldName: string, maxLength: number): string {
    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException(`${fieldName} is required`);
    }

    const normalized = value.trim();
    if (normalized.length > maxLength) {
      throw new BadRequestException(`${fieldName} exceeds max length of ${maxLength}`);
    }

    return normalized;
  }

  private parseOptionalKey(value: unknown, maxLength: number): string | null {
    if (value === undefined || value === null) {
      return null;
    }

    if (typeof value !== 'string' || value.trim().length === 0) {
      throw new BadRequestException('idempotencyKey must be a non-empty string when provided');
    }

    const normalized = value.trim();
    if (normalized.length > maxLength) {
      throw new BadRequestException(`idempotencyKey exceeds max length of ${maxLength}`);
    }

    return normalized;
  }

  private parseOptionalRate(value: unknown, fieldName: string): number | null {
    if (value === undefined || value === null) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isFinite(parsed) || parsed < 0 || parsed > 1) {
      throw new BadRequestException(`${fieldName} must be a number between 0 and 1`);
    }

    return parsed;
  }

  private parseDate(value: unknown, fieldName: string): Date {
    if (typeof value !== 'string') {
      throw new BadRequestException(`${fieldName} must be a valid ISO date string`);
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
      throw new BadRequestException(`${fieldName} must be a valid ISO date string`);
    }

    return parsed;
  }

  private parseProvider(value: unknown): ProviderName | null {
    if (typeof value !== 'string') {
      return 'STRIPE';
    }

    const normalized = value.toUpperCase();
    if (normalized === 'STRIPE' || normalized === 'BML' || normalized === 'MIB') {
      return normalized;
    }

    return null;
  }

  private parseCurrency(value: unknown): 'USD' | 'MVR' | null {
    if (typeof value !== 'string') {
      return 'USD';
    }

    const normalized = value.toUpperCase();
    if (normalized === 'USD' || normalized === 'MVR') {
      return normalized;
    }

    return null;
  }

  private parseProviderFilter(value: unknown): ProviderName | null {
    if (typeof value !== 'string') {
      return null;
    }

    const normalized = value.toUpperCase();
    if (normalized === 'STRIPE' || normalized === 'BML' || normalized === 'MIB') {
      return normalized;
    }

    return null;
  }

  private parseLimit(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    if (typeof value !== 'number' || !Number.isInteger(value)) {
      return null;
    }

    if (value < 1 || value > 500) {
      return null;
    }

    return value;
  }

  private parseHistoryLimit(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed)) {
      return null;
    }

    if (parsed < 1 || parsed > 100) {
      return null;
    }

    return parsed;
  }

  private parseRecentFailuresLimit(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed)) {
      return null;
    }

    if (parsed < 1 || parsed > 50) {
      return null;
    }

    return parsed;
  }

  private parsePruneOlderThanHours(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed)) {
      return null;
    }

    if (parsed < 1 || parsed > 8760) {
      return null;
    }

    return parsed;
  }

  private parsePruneLimit(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed)) {
      return null;
    }

    if (parsed < 1 || parsed > 2000) {
      return null;
    }

    return parsed;
  }

  private parseBackgroundJobStatus(value: unknown): string | null {
    if (value === undefined) {
      return null;
    }

    if (typeof value !== 'string') {
      return null;
    }

    const normalized = value.toUpperCase();
    if (['PENDING', 'RETRYABLE', 'RUNNING', 'COMPLETED', 'DEAD', 'CANCELLED'].includes(normalized)) {
      return normalized;
    }

    return null;
  }

  private parseBackgroundJobType(value: unknown): string | null {
    if (value === undefined) {
      return null;
    }

    if (typeof value !== 'string') {
      return null;
    }

    const normalized = value.trim();
    if (normalized.length === 0 || normalized.length > 64) {
      return null;
    }

    return normalized;
  }

  private parseBackgroundJobListLimit(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed)) {
      return null;
    }

    if (parsed < 1 || parsed > 200) {
      return null;
    }

    return parsed;
  }

  private parseBackgroundJobListOffset(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed)) {
      return null;
    }

    if (parsed < 0 || parsed > 10000) {
      return null;
    }

    return parsed;
  }

  private parseBackgroundJobDelaySeconds(value: unknown): number | null {
    if (value === undefined) {
      return null;
    }

    const parsed = typeof value === 'string' ? Number(value) : value;
    if (typeof parsed !== 'number' || !Number.isInteger(parsed)) {
      return null;
    }

    if (parsed < 0 || parsed > 3600) {
      return null;
    }

    return parsed;
  }

  private readPositiveIntEnv(key: string, fallback: number): number {
    const parsed = Number(process.env[key] ?? fallback);
    if (!Number.isInteger(parsed) || parsed <= 0) {
      return fallback;
    }

    return parsed;
  }

  private readRateEnv(key: string, fallback: number): number {
    const parsed = Number(process.env[key] ?? fallback);
    if (!Number.isFinite(parsed) || parsed < 0 || parsed > 1) {
      return fallback;
    }

    return parsed;
  }
}

import { Body, Controller, Get, Headers, HttpCode, HttpStatus, Param, Post, Query } from '@nestjs/common';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { Public } from '../auth/decorators/public.decorator';
import { FeatureDomain } from '../feature-flags/feature-domain.decorator';
import { Roles } from '../auth/decorators/roles.decorator';
import { PaymentsBackgroundJobsRunner } from './payments-background-jobs.runner';
import { PaymentsReconciliationRunner } from './payments-reconciliation.runner';
import { PaymentsService } from './payments.service';

type RequestUser = {
  id: string;
  role: 'USER' | 'VENDOR' | 'ADMIN' | 'ADMIN_SUPER' | 'ADMIN_CARE' | 'ADMIN_FINANCE';
};

@Controller('payments')
@FeatureDomain('payments')
export class PaymentsController {
  constructor(
    private readonly paymentsService: PaymentsService,
    private readonly reconciliationRunner: PaymentsReconciliationRunner,
    private readonly backgroundJobsRunner: PaymentsBackgroundJobsRunner,
  ) {}

  @Get('admin/bml/health')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE')
  async bmlHealth() {
    return this.paymentsService.getBmlHealthReport();
  }

  @Get('admin/mib/health')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE')
  async mibHealth() {
    return this.paymentsService.getMibHealthReport();
  }

  @Post('admin/reconcile/pending')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE')
  async reconcilePending(
    @Body() body: Record<string, unknown>,
  ) {
    return this.paymentsService.reconcilePendingPayments(body);
  }

  @Post('admin/reconcile/run-now')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE')
  async reconcileRunNow(@Body() body: Record<string, unknown>) {
    return this.reconciliationRunner.runNow(body);
  }

  @Get('admin/reconcile/status')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE')
  async reconcileStatus() {
    return this.reconciliationRunner.getStatusSnapshot();
  }

  @Get('admin/reconcile/history')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE')
  async reconcileHistory(@Query() query: Record<string, unknown>) {
    return this.paymentsService.getReconciliationRunHistory(query);
  }

  @Get('admin/reconcile/alerts')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE')
  async reconcileAlerts() {
    const status = this.reconciliationRunner.getStatusSnapshot();
    return this.paymentsService.getReconciliationAlerts({
      enabled: status.enabled,
      intervalMs: status.intervalMs,
    });
  }

  @Get('admin/alerts')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE', 'ADMIN_FINANCE')
  async operationalAlerts() {
    const reconcileStatus = this.reconciliationRunner.getStatusSnapshot();
    const jobsRunner = this.backgroundJobsRunner.getStatusSnapshot();

    return this.paymentsService.dispatchOperationalAlerts({
      reconcileEnabled: reconcileStatus.enabled,
      reconcileIntervalMs: reconcileStatus.intervalMs,
      jobsRunner: {
        enabled: jobsRunner.enabled,
        running: jobsRunner.running,
        intervalMs: jobsRunner.intervalMs,
        lastTickFinishedAt: jobsRunner.lastTickFinishedAt,
        lastTickError: jobsRunner.lastTickError,
        lastPruneError: jobsRunner.lastPruneError,
      },
    });
  }

  @Get('admin/jobs/health')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE', 'ADMIN_CARE')
  async backgroundJobsHealth(@Query() query: Record<string, unknown>) {
    const queue = await this.paymentsService.getBackgroundJobsHealth({
      recentFailuresLimit: query.recentFailuresLimit,
    });

    return {
      queue,
      runner: this.backgroundJobsRunner.getStatusSnapshot(),
    };
  }

  @Get('admin/jobs')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE', 'ADMIN_CARE')
  async listBackgroundJobs(@Query() query: Record<string, unknown>) {
    return this.paymentsService.listBackgroundJobs({
      status: query.status,
      type: query.type,
      limit: query.limit,
      offset: query.offset,
    });
  }

  @Post('admin/jobs/prune')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE')
  async pruneBackgroundJobs(@Body() body: Record<string, unknown>) {
    return this.paymentsService.pruneCompletedBackgroundJobs({
      olderThanHours: body.olderThanHours,
      limit: body.limit,
    });
  }

  @Post('admin/jobs/:id/requeue')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE')
  async requeueBackgroundJob(@Param('id') id: string, @Body() body: Record<string, unknown>) {
    return this.paymentsService.requeueBackgroundJob(id, {
      delaySeconds: body.delaySeconds,
    });
  }

  @Post('admin/jobs/:id/cancel')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE')
  async cancelBackgroundJob(@Param('id') id: string) {
    return this.paymentsService.cancelBackgroundJob(id);
  }

  @Post('admin/jobs/:id/complete')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE')
  async completeBackgroundJob(@Param('id') id: string) {
    return this.paymentsService.completeBackgroundJob(id);
  }

  @Post('intents')
  async createIntent(
    @CurrentUser() user: RequestUser,
    @Body() body: Record<string, unknown>,
  ) {
    const result = await this.paymentsService.createIntentForUser(user.id, body);
    return {
      created: result.created,
      payment: result.payment,
      clientSecret: result.clientSecret,
    };
  }

  @Post('refunds')
  async requestRefund(
    @CurrentUser() user: RequestUser,
    @Body() body: Record<string, unknown>,
  ) {
    return this.paymentsService.createRefundRequestForUser(user.id, body);
  }

  @Post('admin/refunds')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE')
  async requestRefundAsAdmin(
    @CurrentUser() user: RequestUser,
    @Body() body: Record<string, unknown>,
  ) {
    return this.paymentsService.createRefundRequestAsAdmin(body, {
      id: user.id,
      role: user.role,
    });
  }

  @Post('disputes')
  async openDispute(
    @CurrentUser() user: RequestUser,
    @Body() body: Record<string, unknown>,
  ) {
    return this.paymentsService.createDisputeForUser(user.id, body);
  }

  @Get('admin/settlements/report')
  @Roles('ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE')
  async settlementReport(@Query() query: Record<string, unknown>) {
    return this.paymentsService.getSettlementReport(query);
  }

  @Post('webhooks/stripe')
  @Public()
  @HttpCode(HttpStatus.OK)
  async stripeWebhook(
    @Headers('x-webhook-signature') signature: string | undefined,
    @Body() body: unknown,
  ) {
    return this.paymentsService.processStripeWebhook(signature, body);
  }

  @Post('webhooks/bml')
  @Public()
  @HttpCode(HttpStatus.OK)
  async bmlWebhook(
    @Headers('x-signature-nonce') nonce: string | undefined,
    @Headers('x-signature-timestamp') timestamp: string | undefined,
    @Headers('x-signature') signature: string | undefined,
    @Headers('x-originator') originator: string | undefined,
    @Body() body: unknown,
  ) {
    const signatureBundle = [nonce, timestamp, signature, originator].filter((value) => value !== undefined).join('|');
    return this.paymentsService.processBmlWebhook(signatureBundle, body);
  }

  @Post('webhooks/mib')
  @Public()
  @HttpCode(HttpStatus.OK)
  async mibWebhook(
    @Headers('x-webhook-signature') signature: string | undefined,
    @Body() body: unknown,
  ) {
    return this.paymentsService.processMibWebhook(signature, body);
  }
}

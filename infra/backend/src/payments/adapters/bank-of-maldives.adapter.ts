import { Injectable } from '@nestjs/common';
import { createHash } from 'crypto';
import {
  CreateIntentInput,
  CreateIntentOutput,
  ParsedWebhookEvent,
  PaymentProviderAdapter,
  ProviderHealthStatus,
  ProviderTransactionStatus,
} from './payment-provider.interface';

@Injectable()
export class BankOfMaldivesAdapter implements PaymentProviderAdapter {
  async createIntent(input: CreateIntentInput): Promise<CreateIntentOutput> {
    const liveIntent = (process.env.BML_CONNECT_LIVE_INTENTS ?? 'false').toLowerCase() === 'true';
    if (liveIntent) {
      const liveResult = await this.createLiveIntent(input);
      if (liveResult) {
        return liveResult;
      }
    }

    const entropy = `${Date.now()}-${Math.floor(Math.random() * 1_000_000)}`;
    const providerIntentId = `bml_intent_${entropy}`;
    const clientSecret = `${providerIntentId}_token_${entropy}`;

    return {
      providerIntentId,
      clientSecret,
      status: 'REQUIRES_ACTION',
    };
  }

  private async createLiveIntent(input: CreateIntentInput): Promise<CreateIntentOutput | null> {
    const apiBaseUrl = process.env.BML_API_BASE_URL;
    const apiKey = process.env.BML_API_KEY;
    if (!apiBaseUrl || !apiKey) {
      return null;
    }

    const normalizedBase = apiBaseUrl.replace(/\/+$/, '');
    const createUrl = `${normalizedBase}/public/v2/transactions`;
    const webhookUrl = process.env.BML_WEBHOOK_URL;
    const redirectUrl = process.env.BML_REDIRECT_URL;
    const forceCurrency = process.env.BML_FORCE_CURRENCY;
    const currency = (forceCurrency ?? input.currency ?? 'USD').toUpperCase();
    const amount = this.toMinorUnits(input.amount);

    const payload: Record<string, unknown> = {
      amount,
      currency,
      localId: input.bookingId,
      customerReference: input.metadata?.bookingId ?? input.bookingId,
    };

    if (webhookUrl) {
      payload.webhook = webhookUrl;
    }

    if (redirectUrl) {
      payload.redirectUrl = redirectUrl;
    }

    try {
      const response = await fetch(createUrl, {
        method: 'POST',
        headers: {
          Authorization: apiKey,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      });

      if (!response.ok) {
        return null;
      }

      const data = (await response.json()) as {
        id?: unknown;
        url?: unknown;
        redirectUrl?: unknown;
        securityWord?: unknown;
        state?: unknown;
      };

      const providerIntentId = typeof data.id === 'string' ? data.id : `bml_intent_${Date.now()}`;
      const clientSecret =
        (typeof data.url === 'string' && data.url) ||
        (typeof data.redirectUrl === 'string' && data.redirectUrl) ||
        (typeof data.securityWord === 'string' && data.securityWord) ||
        providerIntentId;

      return {
        providerIntentId,
        clientSecret,
        status: this.mapCreateStatus(data.state),
      };
    } catch {
      return null;
    }
  }

  verifyAndParseWebhook(rawPayload: unknown, signature: string | undefined, secret: string): ParsedWebhookEvent {
    const signatureBundle = this.parseSignatureBundle(signature);
    if (!signatureBundle) {
      return {
        id: '',
        type: '',
        payload: {},
      };
    }

    const { nonce, timestamp, receivedSignature, originator } = signatureBundle;
    const signString = `${nonce}${timestamp}${secret}`;
    const generatedSignature = createHash('sha256').update(signString).digest('hex');

    if (receivedSignature !== generatedSignature) {
      return {
        id: '',
        type: '',
        payload: {},
      };
    }

    if (originator && originator !== 'PomeloPay-Webhooks') {
      return {
        id: '',
        type: '',
        payload: {},
      };
    }

    const payload = (rawPayload ?? {}) as {
      eventId?: unknown;
      eventType?: unknown;
      transactionId?: unknown;
      localId?: unknown;
      customerReference?: unknown;
      data?: { transaction?: { intentId?: unknown; bookingId?: unknown } };
    };

    const eventId = typeof payload.eventId === 'string' ? payload.eventId : '';
    const eventType = typeof payload.eventType === 'string' ? payload.eventType : '';
    const transactionId = typeof payload.transactionId === 'string' ? payload.transactionId : undefined;
    const transaction = payload.data?.transaction ?? {};
    const bookingFromLocalId = typeof payload.localId === 'string' ? payload.localId : undefined;
    const bookingFromCustomerRef = typeof payload.customerReference === 'string' ? payload.customerReference : undefined;

    return {
      id: eventId,
      type: eventType,
      paymentIntentId: transactionId ?? (typeof transaction.intentId === 'string' ? transaction.intentId : undefined),
      bookingId:
        bookingFromLocalId ??
        bookingFromCustomerRef ??
        (typeof transaction.bookingId === 'string' ? transaction.bookingId : undefined),
      payload: payload as Record<string, unknown>,
    };
  }

  async fetchTransactionStatus(providerReferenceId: string): Promise<ProviderTransactionStatus | null> {
    const apiBaseUrl = process.env.BML_API_BASE_URL;
    const apiKey = process.env.BML_API_KEY;
    if (!apiBaseUrl || !apiKey) {
      return null;
    }

    const normalizedBase = apiBaseUrl.replace(/\/+$/, '');
    const transactionUrl = `${normalizedBase}/public/v2/transactions/${encodeURIComponent(providerReferenceId)}`;
    const response = await fetch(transactionUrl, {
      method: 'GET',
      headers: {
        Authorization: apiKey,
      },
    });

    if (!response.ok) {
      return null;
    }

    const payload = (await response.json()) as { id?: unknown; state?: unknown } & Record<string, unknown>;
    const state = this.mapState(payload.state);
    const id = typeof payload.id === 'string' ? payload.id : providerReferenceId;

    return {
      providerReferenceId: id,
      state,
      payload,
    };
  }

  async probeHealth(): Promise<ProviderHealthStatus> {
    const apiBaseUrl = process.env.BML_API_BASE_URL;
    const apiKey = process.env.BML_API_KEY;
    if (!apiBaseUrl || !apiKey) {
      return {
        configured: false,
        reachable: false,
        authenticated: false,
        responseTimeMs: 0,
        message: 'BML_API_BASE_URL or BML_API_KEY is not configured',
      };
    }

    const normalizedBase = apiBaseUrl.replace(/\/+$/, '');
    const probeUrl = `${normalizedBase}/public/v2/transactions/__health_probe__`;
    const startedAt = Date.now();

    try {
      const response = await fetch(probeUrl, {
        method: 'GET',
        headers: {
          Authorization: apiKey,
        },
      });

      if (response.status === 401 || response.status === 403) {
        return {
          configured: true,
          reachable: true,
          authenticated: false,
          statusCode: response.status,
          responseTimeMs: Date.now() - startedAt,
          message: 'BML endpoint reachable, authorization rejected',
        };
      }

      if (response.status === 404 || response.ok) {
        return {
          configured: true,
          reachable: true,
          authenticated: true,
          statusCode: response.status,
          responseTimeMs: Date.now() - startedAt,
          message: 'BML endpoint reachable and authorization accepted',
        };
      }

      return {
        configured: true,
        reachable: true,
        authenticated: false,
        statusCode: response.status,
        responseTimeMs: Date.now() - startedAt,
        message: 'BML endpoint reachable but returned unexpected response',
      };
    } catch {
      return {
        configured: true,
        reachable: false,
        authenticated: false,
        responseTimeMs: Date.now() - startedAt,
        message: 'BML endpoint is not reachable from current network',
      };
    }
  }

  private parseSignatureBundle(
    value: string | undefined,
  ): { nonce: string; timestamp: string; receivedSignature: string; originator?: string } | null {
    if (!value) {
      return null;
    }

    const parts = value.split('|');
    if (parts.length < 3) {
      return null;
    }

    const [nonce, timestamp, receivedSignature, originator] = parts;
    if (!nonce || !timestamp || !receivedSignature) {
      return null;
    }

    return { nonce, timestamp, receivedSignature, originator };
  }

  private mapState(state: unknown): 'PENDING' | 'SUCCEEDED' | 'FAILED' {
    const value = typeof state === 'string' ? state.toUpperCase() : '';
    if (['CONFIRMED', 'SUCCEEDED', 'PAID', 'COMPLETED'].includes(value)) {
      return 'SUCCEEDED';
    }

    if (['FAILED', 'DECLINED', 'REJECTED', 'CANCELLED', 'EXPIRED'].includes(value)) {
      return 'FAILED';
    }

    return 'PENDING';
  }

  private mapCreateStatus(state: unknown): 'REQUIRES_ACTION' | 'SUCCEEDED' | 'FAILED' {
    const status = this.mapState(state);
    if (status === 'SUCCEEDED') {
      return 'SUCCEEDED';
    }

    if (status === 'FAILED') {
      return 'FAILED';
    }

    return 'REQUIRES_ACTION';
  }

  private toMinorUnits(amount: number): number {
    return Math.round(Number(amount) * 100);
  }
}

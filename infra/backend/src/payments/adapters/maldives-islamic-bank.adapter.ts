import { Injectable } from '@nestjs/common';
import { createHash } from 'crypto';
import { CreateIntentInput, CreateIntentOutput, ParsedWebhookEvent, PaymentProviderAdapter, ProviderHealthStatus } from './payment-provider.interface';

@Injectable()
export class MaldivesIslamicBankAdapter implements PaymentProviderAdapter {
  async createIntent(input: CreateIntentInput): Promise<CreateIntentOutput> {
    const legacyMode = (process.env.MIB_LEGACY_MODE ?? 'false').toLowerCase() === 'true';
    if (legacyMode) {
      const legacyResult = this.createLegacyIntent(input);
      if (legacyResult) {
        return legacyResult;
      }
    }

    const entropy = `${Date.now()}-${Math.floor(Math.random() * 1_000_000)}`;
    const providerIntentId = `mib_intent_${entropy}`;
    const clientSecret = `${providerIntentId}_token_${entropy}`;

    return {
      providerIntentId,
      clientSecret,
      status: 'REQUIRES_ACTION',
    };
  }

  private createLegacyIntent(input: CreateIntentInput): CreateIntentOutput | null {
    const host = process.env.MIB_HOST;
    const merRespUrl = process.env.MIB_MER_RESP_URL;
    const acqId = process.env.MIB_ACQ_ID;
    const merId = process.env.MIB_MER_ID;
    const merPassword = process.env.MIB_MER_PASSWORD;
    if (!host || !merRespUrl || !acqId || !merId || !merPassword) {
      return null;
    }

    const defaultCurrencyConfig = this.mapCurrencyToMib(input.currency);
    const purchaseCurrency = process.env.MIB_FORCE_PURCHASE_CURRENCY ?? process.env.MIB_PURCHASE_CURRENCY ?? defaultCurrencyConfig.code;
    const purchaseCurrencyExponent = Number(
      process.env.MIB_PURCHASE_CURRENCY_EXPONENT ?? String(defaultCurrencyConfig.exponent),
    );
    const version = process.env.MIB_VERSION ?? '1';
    const signatureMethod = process.env.MIB_SIGNATURE_METHOD ?? 'SHA1';

    const orderId = `MIB-${input.bookingId}-${Date.now()}`;
    const purchaseAmt = this.formatAmount(input.amount, purchaseCurrencyExponent);

    const signaturePayload = `${merPassword}${merId}${acqId}${orderId}${purchaseAmt}${purchaseCurrency}`;
    const signature = this.sha1Base64(signaturePayload);

    const params = new URLSearchParams({
      Version: version,
      SignatureMethod: signatureMethod,
      MerID: merId,
      AcqID: acqId,
      MerRespURL: merRespUrl,
      PurchaseCurrency: purchaseCurrency,
      PurchaseAmt: purchaseAmt,
      OrderID: orderId,
      Signature: signature,
    });

    const separator = host.includes('?') ? '&' : '?';
    const checkoutUrl = `${host}${separator}${params.toString()}`;

    return {
      providerIntentId: orderId,
      clientSecret: checkoutUrl,
      status: 'REQUIRES_ACTION',
    };
  }

  verifyAndParseWebhook(rawPayload: unknown, signature: string | undefined, secret: string): ParsedWebhookEvent {
    const legacyMode = (process.env.MIB_LEGACY_MODE ?? 'false').toLowerCase() === 'true';
    if (legacyMode) {
      const legacyParsed = this.verifyAndParseLegacyCallback(rawPayload);
      if (legacyParsed) {
        return legacyParsed;
      }
    }

    if (!signature || signature !== secret) {
      return {
        id: '',
        type: '',
        payload: {},
      };
    }

    const payload = (rawPayload ?? {}) as {
      id?: unknown;
      kind?: unknown;
      payment?: { intentId?: unknown; metadata?: { bookingId?: unknown } };
    };

    const eventId = typeof payload.id === 'string' ? payload.id : '';
    const eventType = typeof payload.kind === 'string' ? payload.kind : '';
    const payment = payload.payment ?? {};
    const metadata = payment.metadata ?? {};

    return {
      id: eventId,
      type: eventType,
      paymentIntentId: typeof payment.intentId === 'string' ? payment.intentId : undefined,
      bookingId: typeof metadata.bookingId === 'string' ? metadata.bookingId : undefined,
      payload: payload as Record<string, unknown>,
    };
  }

  private verifyAndParseLegacyCallback(rawPayload: unknown): ParsedWebhookEvent | null {
    const payload = (rawPayload ?? {}) as {
      Signature?: unknown;
      OrderID?: unknown;
      orderId?: unknown;
      ResponseCode?: unknown;
      responseCode?: unknown;
      ReasonCode?: unknown;
      reasonCode?: unknown;
      localId?: unknown;
      customerReference?: unknown;
    };

    const callbackSignature = typeof payload.Signature === 'string' ? payload.Signature : '';
    const orderId =
      typeof payload.OrderID === 'string'
        ? payload.OrderID
        : typeof payload.orderId === 'string'
          ? payload.orderId
          : '';

    if (!callbackSignature || !orderId) {
      return null;
    }

    const acqId = process.env.MIB_ACQ_ID;
    const merId = process.env.MIB_MER_ID;
    const merPassword = process.env.MIB_MER_PASSWORD;
    if (!acqId || !merId || !merPassword) {
      return null;
    }

    const expectedPayload = `${merPassword}${merId}${acqId}${orderId}`;
    const expectedSignature = this.sha1Base64(expectedPayload);
    if (expectedSignature !== callbackSignature) {
      return {
        id: '',
        type: '',
        payload: {},
      };
    }

    const responseCode =
      typeof payload.ResponseCode === 'string'
        ? payload.ResponseCode
        : typeof payload.responseCode === 'string'
          ? payload.responseCode
          : '';
    const reasonCode =
      typeof payload.ReasonCode === 'string'
        ? payload.ReasonCode
        : typeof payload.reasonCode === 'string'
          ? payload.reasonCode
          : '';

    const successCodes = new Set(['1', '00', '0']);
    const eventType = successCodes.has(responseCode) ? 'payment_intent.succeeded' : 'payment_intent.payment_failed';
    const eventId = `mib_cb_${orderId}_${responseCode || reasonCode || 'na'}`;

    return {
      id: eventId,
      type: eventType,
      paymentIntentId: orderId,
      bookingId:
        typeof payload.localId === 'string'
          ? payload.localId
          : typeof payload.customerReference === 'string'
            ? payload.customerReference
            : undefined,
      payload: payload as Record<string, unknown>,
    };
  }

  private formatAmount(value: number, exponent: number): string {
    const scaled = Number(value).toFixed(exponent).replace('.', '');
    return scaled.padStart(12, '0');
  }

  private mapCurrencyToMib(currency: string): { code: string; exponent: number } {
    const normalized = currency.toUpperCase();
    if (normalized === 'MVR') {
      return { code: '462', exponent: 2 };
    }

    if (normalized === 'USD') {
      return { code: '840', exponent: 2 };
    }

    return { code: '462', exponent: 2 };
  }

  private sha1Base64(value: string): string {
    return createHash('sha1').update(value).digest('base64');
  }

  async probeHealth(): Promise<ProviderHealthStatus> {
    const legacyMode = (process.env.MIB_LEGACY_MODE ?? 'false').toLowerCase() === 'true';
    if (legacyMode) {
      return this.probeLegacyHealth();
    }

    return this.probeApiModeHealth();
  }

  private async probeLegacyHealth(): Promise<ProviderHealthStatus> {
    const host = process.env.MIB_HOST;
    const merRespUrl = process.env.MIB_MER_RESP_URL;
    const acqId = process.env.MIB_ACQ_ID;
    const merId = process.env.MIB_MER_ID;
    const merPassword = process.env.MIB_MER_PASSWORD;
    if (!host || !merRespUrl || !acqId || !merId || !merPassword) {
      return {
        configured: false,
        reachable: false,
        authenticated: false,
        responseTimeMs: 0,
        message: 'MIB legacy mode enabled but required credentials are missing',
      };
    }

    const startedAt = Date.now();
    try {
      const response = await fetch(host, {
        method: 'GET',
        signal: AbortSignal.timeout(5000),
      });

      return {
        configured: true,
        reachable: true,
        authenticated: response.status < 500,
        statusCode: response.status,
        responseTimeMs: Date.now() - startedAt,
        message: 'MIB legacy host reachable',
      };
    } catch {
      return {
        configured: true,
        reachable: false,
        authenticated: false,
        responseTimeMs: Date.now() - startedAt,
        message: 'MIB legacy host is not reachable from current network',
      };
    }
  }

  private async probeApiModeHealth(): Promise<ProviderHealthStatus> {
    const apiBaseUrl = process.env.MIB_API_BASE_URL;
    const apiKey = process.env.MIB_API_KEY;
    if (!apiBaseUrl || !apiKey) {
      return {
        configured: false,
        reachable: false,
        authenticated: false,
        responseTimeMs: 0,
        message: 'MIB_API_BASE_URL or MIB_API_KEY is not configured',
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
          message: 'MIB endpoint reachable, authorization rejected',
        };
      }

      if (response.status === 404 || response.ok) {
        return {
          configured: true,
          reachable: true,
          authenticated: true,
          statusCode: response.status,
          responseTimeMs: Date.now() - startedAt,
          message: 'MIB endpoint reachable and authorization accepted',
        };
      }

      return {
        configured: true,
        reachable: true,
        authenticated: false,
        statusCode: response.status,
        responseTimeMs: Date.now() - startedAt,
        message: 'MIB endpoint reachable but returned unexpected response',
      };
    } catch {
      return {
        configured: true,
        reachable: false,
        authenticated: false,
        responseTimeMs: Date.now() - startedAt,
        message: 'MIB endpoint is not reachable from current network',
      };
    }
  }
}

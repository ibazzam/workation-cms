import { Injectable } from '@nestjs/common';
import { CreateIntentInput, CreateIntentOutput, ParsedWebhookEvent, PaymentProviderAdapter } from './payment-provider.interface';

@Injectable()
export class StripeMockAdapter implements PaymentProviderAdapter {
  async createIntent(input: CreateIntentInput): Promise<CreateIntentOutput> {
    const entropy = `${Date.now()}-${Math.floor(Math.random() * 1_000_000)}`;
    const providerIntentId = `pi_mock_${entropy}`;
    const clientSecret = `${providerIntentId}_secret_${entropy}`;

    return {
      providerIntentId,
      clientSecret,
      status: 'REQUIRES_ACTION',
    };
  }

  verifyAndParseWebhook(rawPayload: unknown, signature: string | undefined, secret: string): ParsedWebhookEvent {
    if (!signature || signature !== secret) {
      return {
        id: '',
        type: '',
        payload: {},
      };
    }

    const payload = (rawPayload ?? {}) as {
      id?: unknown;
      type?: unknown;
      data?: { object?: { payment_intent?: unknown; bookingId?: unknown } };
    };

    const eventId = typeof payload.id === 'string' ? payload.id : '';
    const eventType = typeof payload.type === 'string' ? payload.type : '';
    const object = payload.data?.object ?? {};

    return {
      id: eventId,
      type: eventType,
      paymentIntentId: typeof object.payment_intent === 'string' ? object.payment_intent : undefined,
      bookingId: typeof object.bookingId === 'string' ? object.bookingId : undefined,
      payload: payload as Record<string, unknown>,
    };
  }
}

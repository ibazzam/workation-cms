export type CreateIntentInput = {
  bookingId: string;
  amount: number;
  currency: string;
  metadata?: Record<string, string>;
};

export type CreateIntentOutput = {
  providerIntentId: string;
  clientSecret: string;
  status: 'REQUIRES_ACTION' | 'SUCCEEDED' | 'FAILED';
};

export type ParsedWebhookEvent = {
  id: string;
  type: string;
  paymentIntentId?: string;
  bookingId?: string;
  payload: Record<string, unknown>;
};

export type ProviderTransactionStatus = {
  providerReferenceId?: string;
  state: 'PENDING' | 'SUCCEEDED' | 'FAILED';
  payload: Record<string, unknown>;
};

export type ProviderHealthStatus = {
  configured: boolean;
  reachable: boolean;
  authenticated: boolean;
  statusCode?: number;
  responseTimeMs?: number;
  message: string;
};

export interface PaymentProviderAdapter {
  createIntent(input: CreateIntentInput): Promise<CreateIntentOutput>;
  verifyAndParseWebhook(rawPayload: unknown, signature: string | undefined, secret: string): ParsedWebhookEvent;
  fetchTransactionStatus?(providerReferenceId: string): Promise<ProviderTransactionStatus | null>;
  probeHealth?(): Promise<ProviderHealthStatus>;
}

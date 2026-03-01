'use client'

import { useMutation } from '@tanstack/react-query'
import { apiRequest, buildAuthHeaders, normalizeApiBase } from '../api-client'

export type ProviderName = 'STRIPE' | 'BML' | 'MIB'
export type CurrencyCode = 'USD' | 'MVR'

export type PaymentIntentResponse = {
  created: boolean
  clientSecret?: string
  payment?: {
    id: string
    bookingId: string
    provider: ProviderName
    providerId: string | null
    amount: string | number
    currency: CurrencyCode
    status: string
  }
}

export function useCreatePaymentIntent(apiBase: string) {
  const normalizedApiBase = normalizeApiBase(apiBase)

  return useMutation({
    mutationFn: async (input: {
      identity: { userId: string; userRole: string; userEmail: string }
      payload: { bookingId: string; provider: ProviderName; currency: CurrencyCode }
    }) => {
      return apiRequest<PaymentIntentResponse>(`${normalizedApiBase}/payments/intents`, {
        method: 'POST',
        headers: buildAuthHeaders(input.identity),
        body: JSON.stringify(input.payload),
      })
    },
  })
}

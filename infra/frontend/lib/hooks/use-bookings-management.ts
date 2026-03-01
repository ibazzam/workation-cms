'use client'

import { useMutation, useQuery } from '@tanstack/react-query'
import { apiRequest, AuthIdentity, buildAuthHeaders, normalizeApiBase } from '../api-client'

export type BookingRecord = {
  id: string
  status: string
  accommodationId?: string | null
  transportId?: string | null
  transportFareClassCode?: string | null
  startDate?: string | null
  endDate?: string | null
  guests?: number | null
  totalPrice?: string | number
  holdExpiresAt?: string | null
  management?: {
    holdExpired?: boolean
    canMoveToHold?: boolean
    canConfirm?: boolean
    canCancel?: boolean
    canRebook?: boolean
    canRefund?: boolean
  }
}

type RebookTemplateResponse = {
  booking: BookingRecord
  template: {
    canRebook: boolean
    reason: string | null
    defaults: {
      accommodationId: string | null
      transportId: string | null
      transportFareClassCode: string | null
      startDate: string | null
      endDate: string | null
      guests: number | null
    }
  }
}

export function useBookingsList(apiBase: string, identity: AuthIdentity) {
  const normalizedApiBase = normalizeApiBase(apiBase)

  return useQuery({
    queryKey: ['bookings', normalizedApiBase, identity.userId, identity.userRole],
    queryFn: async () => {
      return apiRequest<BookingRecord[]>(`${normalizedApiBase}/bookings`, {
        headers: buildAuthHeaders(identity),
      })
    },
  })
}

export function useBookingAction(apiBase: string, identity: AuthIdentity) {
  const normalizedApiBase = normalizeApiBase(apiBase)

  return useMutation({
    mutationFn: async (input: { bookingId: string; action: 'confirm' | 'cancel' }) => {
      return apiRequest<BookingRecord>(`${normalizedApiBase}/bookings/${input.bookingId}/${input.action}`, {
        method: 'PATCH',
        headers: buildAuthHeaders(identity),
      })
    },
  })
}

export function useRebookTemplate(apiBase: string, identity: AuthIdentity) {
  const normalizedApiBase = normalizeApiBase(apiBase)

  return useMutation({
    mutationFn: async (bookingId: string) => {
      return apiRequest<RebookTemplateResponse>(`${normalizedApiBase}/bookings/${bookingId}/rebook/template`, {
        headers: buildAuthHeaders(identity),
      })
    },
  })
}

export function useRebookMutation(apiBase: string, identity: AuthIdentity) {
  const normalizedApiBase = normalizeApiBase(apiBase)

  return useMutation({
    mutationFn: async (input: {
      bookingId: string
      payload: {
        accommodationId?: string
        transportId?: string
        transportFareClassCode?: string
        startDate?: string
        endDate?: string
        guests?: number
      }
    }) => {
      return apiRequest<{ rebookedFromBookingId: string; replacementBooking: BookingRecord }>(
        `${normalizedApiBase}/bookings/${input.bookingId}/rebook`,
        {
          method: 'POST',
          headers: buildAuthHeaders(identity),
          body: JSON.stringify(input.payload),
        },
      )
    },
  })
}

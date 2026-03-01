'use client'

import { useQuery } from '@tanstack/react-query'
import { apiRequest, normalizeApiBase } from '../api-client'
import { queryKeys } from '../query-keys'

export type Vendor = {
  id: string
  name: string
  email?: string | null
  phone?: string | null
}

export function useVendors(apiBase: string) {
  const normalizedApiBase = normalizeApiBase(apiBase)

  return useQuery({
    queryKey: queryKeys.vendors(normalizedApiBase),
    queryFn: () => apiRequest<Vendor[]>(`${normalizedApiBase}/vendors`),
  })
}

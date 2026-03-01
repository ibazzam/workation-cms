'use client'

import { useQuery } from '@tanstack/react-query'
import { apiRequest, normalizeApiBase } from '../api-client'
import { queryKeys } from '../query-keys'

export type Workation = {
  id: number
  title: string
  description: string | null
  location: string
  start_date: string
  end_date: string
  price: string | number
}

export function useWorkations(apiBase: string) {
  const normalizedApiBase = normalizeApiBase(apiBase)

  return useQuery({
    queryKey: queryKeys.workations(normalizedApiBase),
    queryFn: () => apiRequest<Workation[]>(`${normalizedApiBase}/workations`),
  })
}

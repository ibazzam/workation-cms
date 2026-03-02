'use client'

import { useQuery } from '@tanstack/react-query'
import { apiRequest, normalizeApiBase } from '../api-client'
import { queryKeys } from '../query-keys'

export type Island = {
  id: number
  name: string
  slug: string
  atollId?: number
  atoll?: {
    id: number
    code?: string
    name?: string
  } | null
}

export function useIslands(apiBase: string) {
  const normalizedApiBase = normalizeApiBase(apiBase)

  return useQuery({
    queryKey: queryKeys.islands(normalizedApiBase),
    queryFn: () => apiRequest<Island[]>(`${normalizedApiBase}/islands`),
  })
}

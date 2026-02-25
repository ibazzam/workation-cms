'use client'

import { useQuery } from '@tanstack/react-query'
import { normalizeApiBase } from '../api-client'
import { queryKeys } from '../query-keys'

type BackendStatus = 'reachable' | 'unreachable' | string

export function useBackendStatus(apiBase: string) {
  const normalizedApiBase = normalizeApiBase(apiBase)

  return useQuery<BackendStatus>({
    queryKey: queryKeys.backendStatus(normalizedApiBase),
    queryFn: async () => {
      try {
        const response = await fetch(`${normalizedApiBase}/health`, { cache: 'no-store' })
        return response.ok ? 'reachable' : `http ${response.status}`
      } catch {
        return 'unreachable'
      }
    },
    staleTime: 15000,
  })
}

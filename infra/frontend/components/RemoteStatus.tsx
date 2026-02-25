"use client"
import { normalizeApiBase } from '../lib/api-client'
import { useBackendStatus } from '../lib/hooks/use-backend-status'

export default function RemoteStatus({ apiBase = 'http://localhost:3000/api/v1' }: { apiBase?: string }) {
  const normalizedApiBase = normalizeApiBase(apiBase)
  const { data: status, isLoading } = useBackendStatus(normalizedApiBase)

  return (
    <div className="mt-4 text-sm text-slate-600">Backend: {isLoading ? 'loading...' : (status ?? 'unreachable')}</div>
  )
}

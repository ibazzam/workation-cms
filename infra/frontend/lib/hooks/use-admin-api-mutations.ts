'use client'

import { useMemo } from 'react'
import { useMutation } from '@tanstack/react-query'
import { apiRequest, AuthIdentity, buildAuthHeaders, normalizeApiBase } from '../api-client'

export function useAdminSettingsMutations<TSettings>(apiBase: string, identity: AuthIdentity) {
  const normalizedApiBase = useMemo(() => normalizeApiBase(apiBase), [apiBase])

  const loadSettingsMutation = useMutation({
    mutationFn: async () => {
      return apiRequest<TSettings>(`${normalizedApiBase}/admin/settings/commercial`, {
        headers: buildAuthHeaders(identity),
      })
    },
  })

  const saveSettingsMutation = useMutation({
    mutationFn: async (settings: TSettings) => {
      return apiRequest<TSettings>(`${normalizedApiBase}/admin/settings/commercial`, {
        method: 'POST',
        headers: buildAuthHeaders(identity),
        body: JSON.stringify(settings),
      })
    },
  })

  return {
    loadSettingsMutation,
    saveSettingsMutation,
  }
}

export function useAdminPaymentsMutations<TJobs, TAlerts>(apiBase: string, identity: AuthIdentity) {
  const normalizedApiBase = useMemo(() => normalizeApiBase(apiBase), [apiBase])

  const loadJobsMutation = useMutation({
    mutationFn: async (params: { normalizedLimit: number; normalizedOffset: number; statusFilter: string; typeFilter: string }) => {
      const searchParams = new URLSearchParams()
      if (params.statusFilter) searchParams.set('status', params.statusFilter)
      if (params.typeFilter) searchParams.set('type', params.typeFilter)
      searchParams.set('limit', String(params.normalizedLimit))
      searchParams.set('offset', String(params.normalizedOffset))

      return apiRequest<TJobs>(`${normalizedApiBase}/payments/admin/jobs?${searchParams.toString()}`, {
        headers: buildAuthHeaders(identity),
      })
    },
  })

  const loadAlertsMutation = useMutation({
    mutationFn: async () => {
      return apiRequest<TAlerts>(`${normalizedApiBase}/payments/admin/alerts`, {
        headers: buildAuthHeaders(identity),
      })
    },
  })

  const jobActionMutation = useMutation({
    mutationFn: async (params: { jobId: string; action: 'requeue' | 'cancel' | 'complete'; requeueDelaySeconds: string }) => {
      const body = params.action === 'requeue'
        ? JSON.stringify({ delaySeconds: Number(params.requeueDelaySeconds || '0') })
        : undefined

      return apiRequest<{ changed?: boolean; status?: string; message?: string }>(
        `${normalizedApiBase}/payments/admin/jobs/${params.jobId}/${params.action}`,
        {
          method: 'POST',
          headers: buildAuthHeaders(identity),
          body,
        },
      )
    },
  })

  return {
    loadJobsMutation,
    loadAlertsMutation,
    jobActionMutation,
  }
}

export function useAdminServicesMutations<TEntity extends string>(apiBase: string, identity: AuthIdentity) {
  const normalizedApiBase = useMemo(() => normalizeApiBase(apiBase), [apiBase])

  const loadEntityMutation = useMutation({
    mutationFn: async (entity: TEntity) => {
      return apiRequest<Array<Record<string, unknown>>>(`${normalizedApiBase}/${entity}`, {
        headers: buildAuthHeaders(identity),
      })
    },
  })

  const crudMutation = useMutation({
    mutationFn: async (params: { entity: TEntity; method: 'POST' | 'PUT' | 'DELETE'; id?: string; body?: Record<string, unknown> }) => {
      const path = params.method === 'POST'
        ? `${normalizedApiBase}/${params.entity}/admin`
        : `${normalizedApiBase}/${params.entity}/admin/${params.id}`

      return apiRequest<Record<string, unknown> | null>(path, {
        method: params.method,
        headers: buildAuthHeaders(identity),
        body: params.method === 'DELETE' ? undefined : JSON.stringify(params.body ?? {}),
      })
    },
  })

  return {
    loadEntityMutation,
    crudMutation,
  }
}

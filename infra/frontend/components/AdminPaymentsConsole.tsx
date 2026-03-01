'use client'

import { FormEvent, useState } from 'react'
import { ApiError } from '../lib/api-client'
import { useAdminPaymentsMutations } from '../lib/hooks/use-admin-api-mutations'
import { usePersistedIdentity } from '../lib/hooks/use-persisted-identity'

type AdminRole = 'ADMIN' | 'ADMIN_SUPER' | 'ADMIN_FINANCE' | 'ADMIN_CARE'

type JobsResponse = {
  filters: { status: string | null; type: string | null }
  page: { limit: number; offset: number; total: number }
  items: Array<{
    id: string
    type: string
    status: string
    attempts: number
    maxAttempts: number
    runAt: string
    processedAt: string | null
    lastError: string | null
    createdAt: string
    updatedAt: string
    dedupeKey?: string | null
  }>
}

type AlertsResponse = {
  status: 'OK' | 'WARN'
  generatedAt: string
  activeAlerts: Array<{
    key: string
    source: 'RECONCILIATION' | 'JOBS'
    severity: 'WARN'
    message: string
  }>
}

const ACTION_ROLES: AdminRole[] = ['ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE']

export default function AdminPaymentsConsole({ apiBase }: { apiBase: string }) {
  const {
    userId,
    setUserId,
    userEmail,
    setUserEmail,
    userRole,
    setUserRole,
    identity,
  } = usePersistedIdentity<AdminRole>({
    defaults: {
      userId: 'admin-finance-1',
      userEmail: 'admin-finance-1@example.test',
      userRole: 'ADMIN_FINANCE',
    },
    storageKeyPrefix: 'workation.admin',
  })

  const [statusFilter, setStatusFilter] = useState('')
  const [typeFilter, setTypeFilter] = useState('')
  const [limit, setLimit] = useState('25')
  const [offset, setOffset] = useState('0')
  const [requeueDelaySeconds, setRequeueDelaySeconds] = useState('0')

  const [jobsLoading, setJobsLoading] = useState(false)
  const [alertsLoading, setAlertsLoading] = useState(false)
  const [jobsError, setJobsError] = useState<string | null>(null)
  const [alertsError, setAlertsError] = useState<string | null>(null)
  const [actionError, setActionError] = useState<string | null>(null)
  const [actionInfo, setActionInfo] = useState<string | null>(null)
  const [actionPendingById, setActionPendingById] = useState<Record<string, boolean>>({})

  const [jobsData, setJobsData] = useState<JobsResponse | null>(null)
  const [alertsData, setAlertsData] = useState<AlertsResponse | null>(null)

  const canRunActions = ACTION_ROLES.includes(userRole)

  function getErrorMessage(error: unknown, fallback: string) {
    if (error instanceof ApiError) {
      return error.message
    }

    return fallback
  }

  function parsePositiveInt(value: string, fallback: number) {
    const parsed = Number(value)
    if (!Number.isInteger(parsed) || parsed <= 0) {
      return fallback
    }

    return parsed
  }

  function parseNonNegativeInt(value: string, fallback: number) {
    const parsed = Number(value)
    if (!Number.isInteger(parsed) || parsed < 0) {
      return fallback
    }

    return parsed
  }

  const { loadJobsMutation, loadAlertsMutation, jobActionMutation } = useAdminPaymentsMutations<JobsResponse, AlertsResponse>(
    apiBase,
    identity,
  )

  async function fetchJobs(event?: FormEvent<HTMLFormElement>, options?: { offsetOverride?: number }) {
    event?.preventDefault()
    setJobsLoading(true)
    setJobsError(null)

    const normalizedLimit = parsePositiveInt(limit, 25)
    const normalizedOffset = options?.offsetOverride ?? parseNonNegativeInt(offset, 0)

    try {
      const jobsPayload = await loadJobsMutation.mutateAsync({
        normalizedLimit,
        normalizedOffset,
        statusFilter,
        typeFilter,
      })
      setJobsData(jobsPayload)
      setOffset(String(jobsPayload.page.offset))
      setLimit(String(jobsPayload.page.limit))
    } catch (error) {
      setJobsError(getErrorMessage(error, 'Unable to load jobs list. Check backend connectivity.'))
    } finally {
      setJobsLoading(false)
    }
  }

  async function changePage(direction: 'prev' | 'next') {
    if (!jobsData || jobsLoading) {
      return
    }

    const pageLimit = jobsData.page.limit
    const currentOffset = jobsData.page.offset
    const nextOffset = direction === 'next'
      ? currentOffset + pageLimit
      : Math.max(0, currentOffset - pageLimit)

    if (nextOffset === currentOffset) {
      return
    }

    setOffset(String(nextOffset))
    await fetchJobs(undefined, { offsetOverride: nextOffset })
  }

  async function fetchAlerts() {
    setAlertsLoading(true)
    setAlertsError(null)

    try {
      const payload = await loadAlertsMutation.mutateAsync()
      setAlertsData(payload)
    } catch (error) {
      setAlertsError(getErrorMessage(error, 'Unable to load alerts. Check backend connectivity.'))
    } finally {
      setAlertsLoading(false)
    }
  }

  async function runAction(jobId: string, action: 'requeue' | 'cancel' | 'complete') {
    setActionError(null)
    setActionInfo(null)

    if (!canRunActions) {
      setActionError('Current role is read-only for job actions.')
      return
    }

    if (actionPendingById[jobId]) {
      return
    }

    const previousJobsData = jobsData
    if (previousJobsData) {
      const optimisticRunAt = action === 'requeue'
        ? new Date(Date.now() + parseNonNegativeInt(requeueDelaySeconds, 0) * 1000).toISOString()
        : undefined

      setJobsData({
        ...previousJobsData,
        items: previousJobsData.items.map((job) => {
          if (job.id !== jobId) {
            return job
          }

          if (action === 'requeue') {
            return {
              ...job,
              status: 'PENDING',
              runAt: optimisticRunAt ?? job.runAt,
              lastError: null,
              processedAt: null,
              attempts: 0,
            }
          }

          if (action === 'cancel') {
            return {
              ...job,
              status: 'CANCELLED',
              processedAt: new Date().toISOString(),
            }
          }

          return {
            ...job,
            status: 'COMPLETED',
            processedAt: new Date().toISOString(),
            lastError: null,
          }
        }),
      })
    }

    setActionPendingById((current) => ({ ...current, [jobId]: true }))

    try {
      const payload = await jobActionMutation.mutateAsync({ jobId, action, requeueDelaySeconds })

      setActionInfo(`${action.toUpperCase()} completed for ${jobId} (changed=${String(payload.changed)})`)
      await fetchJobs()
      await fetchAlerts()
    } catch (error) {
      if (previousJobsData) {
        setJobsData(previousJobsData)
      }
      setActionError(getErrorMessage(error, 'Unable to execute action. Check backend connectivity.'))
    } finally {
      setActionPendingById((current) => {
        const next = { ...current }
        delete next[jobId]
        return next
      })
    }
  }

  return (
    <div className="space-y-6">
      <section className="rounded border border-slate-200 bg-white p-4">
        <h3 className="mb-3 text-base font-semibold text-slate-900">Admin identity</h3>
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div>
            <label className="mb-1 block text-sm text-slate-700" htmlFor="admin-user-id">User ID</label>
            <input
              id="admin-user-id"
              className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
              value={userId}
              onChange={(event) => setUserId(event.target.value)}
            />
          </div>
          <div>
            <label className="mb-1 block text-sm text-slate-700" htmlFor="admin-user-email">User Email</label>
            <input
              id="admin-user-email"
              className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
              value={userEmail}
              onChange={(event) => setUserEmail(event.target.value)}
            />
          </div>
        </div>
        <div className="mt-3">
          <label className="mb-1 block text-sm text-slate-700" htmlFor="admin-user-role">Role</label>
          <select
            id="admin-user-role"
            className="w-full rounded border border-slate-300 px-3 py-2 text-sm sm:w-80"
            value={userRole}
            onChange={(event) => setUserRole(event.target.value as AdminRole)}
          >
            <option value="ADMIN">ADMIN</option>
            <option value="ADMIN_SUPER">ADMIN_SUPER</option>
            <option value="ADMIN_FINANCE">ADMIN_FINANCE</option>
            <option value="ADMIN_CARE">ADMIN_CARE</option>
          </select>
        </div>
      </section>

      <section className="rounded border border-slate-200 bg-white p-4">
        <div className="mb-3 flex items-center justify-between gap-2">
          <h3 className="text-base font-semibold text-slate-900">Alerts panel</h3>
          <button
            type="button"
            onClick={fetchAlerts}
            disabled={alertsLoading}
            className="rounded bg-slate-900 px-3 py-2 text-sm font-medium text-white disabled:opacity-60"
          >
            {alertsLoading ? 'Refreshing...' : 'Refresh alerts'}
          </button>
        </div>

        {alertsError ? <p className="text-sm text-rose-700">{alertsError}</p> : null}

        {alertsData ? (
          <>
            <p className="text-sm text-slate-700">
              Status: <span className="font-medium">{alertsData.status}</span> · Generated: {new Date(alertsData.generatedAt).toLocaleString()}
            </p>
            {alertsData.activeAlerts.length === 0 ? (
              <p className="mt-2 text-sm text-emerald-700">No active alerts.</p>
            ) : (
              <ul className="mt-3 space-y-2">
                {alertsData.activeAlerts.map((alert) => (
                  <li key={alert.key} className="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                    <p className="font-medium">{alert.key}</p>
                    <p>{alert.message}</p>
                    <p className="text-xs opacity-80">Source: {alert.source}</p>
                  </li>
                ))}
              </ul>
            )}
          </>
        ) : (
          <p className="text-sm text-slate-600">Run “Refresh alerts” to load dispatcher output.</p>
        )}
      </section>

      <section className="rounded border border-slate-200 bg-white p-4">
        <h3 className="mb-3 text-base font-semibold text-slate-900">Background jobs</h3>

        <form onSubmit={fetchJobs} className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
          <div>
            <label className="mb-1 block text-sm text-slate-700" htmlFor="jobs-status">Status</label>
            <select
              id="jobs-status"
              className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
              value={statusFilter}
              onChange={(event) => setStatusFilter(event.target.value)}
            >
              <option value="">All</option>
              <option value="PENDING">PENDING</option>
              <option value="RETRYABLE">RETRYABLE</option>
              <option value="RUNNING">RUNNING</option>
              <option value="COMPLETED">COMPLETED</option>
              <option value="DEAD">DEAD</option>
              <option value="CANCELLED">CANCELLED</option>
            </select>
          </div>
          <div>
            <label className="mb-1 block text-sm text-slate-700" htmlFor="jobs-type">Type</label>
            <input
              id="jobs-type"
              className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
              value={typeFilter}
              onChange={(event) => setTypeFilter(event.target.value)}
              placeholder="BOOKING_CONFIRMATION_NOTIFICATION"
            />
          </div>
          <div>
            <label className="mb-1 block text-sm text-slate-700" htmlFor="jobs-limit">Limit</label>
            <input
              id="jobs-limit"
              className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
              value={limit}
              onChange={(event) => setLimit(event.target.value)}
            />
          </div>
          <div>
            <label className="mb-1 block text-sm text-slate-700" htmlFor="jobs-offset">Offset</label>
            <input
              id="jobs-offset"
              className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
              value={offset}
              onChange={(event) => setOffset(event.target.value)}
            />
          </div>
          <div className="flex items-end">
            <button
              type="submit"
              disabled={jobsLoading}
              className="w-full rounded bg-slate-900 px-3 py-2 text-sm font-medium text-white disabled:opacity-60"
            >
              {jobsLoading ? 'Loading...' : 'Load jobs'}
            </button>
          </div>
        </form>

        <div className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div>
            <label className="mb-1 block text-sm text-slate-700" htmlFor="requeue-delay">Requeue delay seconds</label>
            <input
              id="requeue-delay"
              className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
              value={requeueDelaySeconds}
              onChange={(event) => setRequeueDelaySeconds(event.target.value)}
            />
          </div>
          <div className="rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
            Action role support: {canRunActions ? 'write actions enabled' : 'read-only role'}
          </div>
        </div>

        {jobsError ? <p className="mt-3 text-sm text-rose-700">{jobsError}</p> : null}
        {actionError ? <p className="mt-2 text-sm text-rose-700">{actionError}</p> : null}
        {actionInfo ? <p className="mt-2 text-sm text-emerald-700">{actionInfo}</p> : null}

        {jobsData ? (
          <div className="mt-4 overflow-x-auto">
            <div className="mb-2 flex flex-wrap items-center justify-between gap-2 text-sm text-slate-600">
              <p>Total: {jobsData.page.total}</p>
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  className="rounded border border-slate-300 px-2 py-1 text-xs disabled:opacity-60"
                  disabled={jobsLoading || jobsData.page.offset <= 0}
                  onClick={() => changePage('prev')}
                >
                  Previous
                </button>
                <span>
                  Offset {jobsData.page.offset} · Limit {jobsData.page.limit}
                </span>
                <button
                  type="button"
                  className="rounded border border-slate-300 px-2 py-1 text-xs disabled:opacity-60"
                  disabled={jobsLoading || jobsData.page.offset + jobsData.page.limit >= jobsData.page.total}
                  onClick={() => changePage('next')}
                >
                  Next
                </button>
              </div>
            </div>
            <table className="min-w-full border border-slate-200 text-sm">
              <thead className="bg-slate-50">
                <tr>
                  <th className="border border-slate-200 px-2 py-2 text-left">ID</th>
                  <th className="border border-slate-200 px-2 py-2 text-left">Type</th>
                  <th className="border border-slate-200 px-2 py-2 text-left">Status</th>
                  <th className="border border-slate-200 px-2 py-2 text-left">Attempts</th>
                  <th className="border border-slate-200 px-2 py-2 text-left">Last Error</th>
                  <th className="border border-slate-200 px-2 py-2 text-left">Actions</th>
                </tr>
              </thead>
              <tbody>
                {jobsData.items.map((job) => (
                  <tr key={job.id}>
                    <td className="border border-slate-200 px-2 py-2 align-top">{job.id}</td>
                    <td className="border border-slate-200 px-2 py-2 align-top">{job.type}</td>
                    <td className="border border-slate-200 px-2 py-2 align-top">{job.status}</td>
                    <td className="border border-slate-200 px-2 py-2 align-top">{job.attempts}/{job.maxAttempts}</td>
                    <td className="border border-slate-200 px-2 py-2 align-top">{job.lastError ?? '—'}</td>
                    <td className="border border-slate-200 px-2 py-2 align-top">
                      <div className="flex flex-wrap gap-2">
                        <button
                          type="button"
                          className="rounded border border-slate-300 px-2 py-1 text-xs"
                          disabled={!canRunActions || Boolean(actionPendingById[job.id])}
                          onClick={() => runAction(job.id, 'requeue')}
                        >
                          {actionPendingById[job.id] ? 'Working...' : 'Requeue'}
                        </button>
                        <button
                          type="button"
                          className="rounded border border-slate-300 px-2 py-1 text-xs"
                          disabled={!canRunActions || Boolean(actionPendingById[job.id])}
                          onClick={() => runAction(job.id, 'cancel')}
                        >
                          Cancel
                        </button>
                        <button
                          type="button"
                          className="rounded border border-slate-300 px-2 py-1 text-xs"
                          disabled={!canRunActions || Boolean(actionPendingById[job.id])}
                          onClick={() => runAction(job.id, 'complete')}
                        >
                          Complete
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <p className="mt-3 text-sm text-slate-600">Run “Load jobs” to fetch queue items.</p>
        )}
      </section>
    </div>
  )
}

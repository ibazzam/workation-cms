'use client'

import { FormEvent, useState } from 'react'
import { ApiError } from '../lib/api-client'
import { CurrencyCode, PaymentIntentResponse, ProviderName, useCreatePaymentIntent } from '../lib/hooks/use-create-payment-intent'
import { useAuthSession } from '../lib/hooks/use-auth-session'
import { usePersistedIdentity } from '../lib/hooks/use-persisted-identity'

export default function PaymentIntentForm({ apiBase }: { apiBase: string }) {
  const [bookingId, setBookingId] = useState('')
  const [provider, setProvider] = useState<ProviderName>('BML')
  const [currency, setCurrency] = useState<CurrencyCode>('USD')
  const [overrideHeaders, setOverrideHeaders] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [result, setResult] = useState<PaymentIntentResponse | null>(null)
  const authSessionQuery = useAuthSession()
  const createIntentMutation = useCreatePaymentIntent(apiBase)

  const {
    userId,
    setUserId,
    userEmail,
    setUserEmail,
    userRole,
    setUserRole,
  } = usePersistedIdentity<'USER' | 'VENDOR' | 'ADMIN'>({
    defaults: {
      userId: 'demo-user-1',
      userEmail: 'demo-user-1@example.test',
      userRole: 'USER',
    },
    storageKeyPrefix: 'workation',
    sessionUser: authSessionQuery.data,
    preferSession: true,
  })

  const resolvedUser = authSessionQuery.data
    ? {
        ...authSessionQuery.data,
        source: 'session' as const,
      }
    : null

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError(null)
    setResult(null)

    const effectiveUser = resolvedUser && !overrideHeaders ? resolvedUser : {
      id: userId,
      email: userEmail,
      role: userRole,
      source: 'manual' as const,
    }

    if (!effectiveUser.id || !effectiveUser.email) {
      setError('User identity is missing. Sign in or provide fallback user values.')
      return
    }

    try {
      const payload = await createIntentMutation.mutateAsync({
        identity: {
          userId: effectiveUser.id,
          userRole: effectiveUser.role,
          userEmail: effectiveUser.email,
        },
        payload: {
          bookingId,
          provider,
          currency,
        },
      })

      setResult(payload)
    } catch (requestError) {
      if (requestError instanceof ApiError) {
        setError(requestError.message)
        return
      }

      setError('Unable to reach backend. Start API server and retry.')
    }
  }

  return (
    <div className="rounded border border-slate-200 bg-white p-4">
      <h3 className="text-base font-semibold text-slate-900 mb-3">Create payment intent</h3>
      <form onSubmit={onSubmit} className="space-y-3">
        <div className="rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
          {authSessionQuery.isPending
            ? 'Resolving signed-in user session...'
            : resolvedUser
              ? `Using signed-in identity: ${resolvedUser.email}`
              : 'No session detected. Using fallback header values below.'}
        </div>

        {resolvedUser ? (
          <label className="flex items-center gap-2 text-sm text-slate-700">
            <input
              type="checkbox"
              checked={overrideHeaders}
              onChange={(event) => setOverrideHeaders(event.target.checked)}
            />
            Override headers manually for testing
          </label>
        ) : null}

        <div>
          <label className="block text-sm text-slate-700 mb-1" htmlFor="booking-id">Booking ID</label>
          <input
            id="booking-id"
            className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
            value={bookingId}
            onChange={(event) => setBookingId(event.target.value)}
            placeholder="booking-id"
            required
          />
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label className="block text-sm text-slate-700 mb-1" htmlFor="provider">Provider</label>
            <select
              id="provider"
              className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
              value={provider}
              onChange={(event) => setProvider(event.target.value as ProviderName)}
            >
              <option value="STRIPE">Stripe</option>
              <option value="BML">Bank of Maldives</option>
              <option value="MIB">Maldives Islamic Bank</option>
            </select>
          </div>
          <div>
            <label className="block text-sm text-slate-700 mb-1" htmlFor="currency">Currency</label>
            <select
              id="currency"
              className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
              value={currency}
              onChange={(event) => setCurrency(event.target.value as CurrencyCode)}
            >
              <option value="USD">USD</option>
              <option value="MVR">MVR</option>
            </select>
          </div>
        </div>

        {!resolvedUser || overrideHeaders ? (
          <>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-sm text-slate-700 mb-1" htmlFor="user-id">User ID</label>
                <input
                  id="user-id"
                  className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                  value={userId}
                  onChange={(event) => setUserId(event.target.value)}
                  required
                />
              </div>
              <div>
                <label className="block text-sm text-slate-700 mb-1" htmlFor="user-email">User Email</label>
                <input
                  id="user-email"
                  className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                  value={userEmail}
                  onChange={(event) => setUserEmail(event.target.value)}
                  required
                />
              </div>
            </div>

            <div>
              <label className="block text-sm text-slate-700 mb-1" htmlFor="user-role">User Role</label>
              <select
                id="user-role"
                className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                value={userRole}
                onChange={(event) => setUserRole(event.target.value as 'USER' | 'VENDOR' | 'ADMIN')}
              >
                <option value="USER">USER</option>
                <option value="VENDOR">VENDOR</option>
                <option value="ADMIN">ADMIN</option>
              </select>
            </div>
          </>
        ) : null}

        <button
          type="submit"
          disabled={createIntentMutation.isPending}
          className="rounded bg-slate-900 px-3 py-2 text-sm font-medium text-white disabled:opacity-60"
        >
          {createIntentMutation.isPending ? 'Creating...' : 'Create intent'}
        </button>
      </form>

      {error ? <p className="mt-3 text-sm text-rose-700">{error}</p> : null}

      {result?.payment ? (
        <div className="mt-4 rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">
          <p>Provider: {result.payment.provider}</p>
          <p>Currency: {result.payment.currency}</p>
          <p>Status: {result.payment.status}</p>
          <p>Client value: {result.clientSecret ?? 'n/a'}</p>
        </div>
      ) : null}
    </div>
  )
}

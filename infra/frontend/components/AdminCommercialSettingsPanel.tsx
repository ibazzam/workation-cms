'use client'

import { FormEvent, useState } from 'react'
import { ApiError } from '../lib/api-client'
import { useAdminSettingsMutations } from '../lib/hooks/use-admin-api-mutations'
import { usePersistedIdentity } from '../lib/hooks/use-persisted-identity'

type AdminRole = 'ADMIN' | 'ADMIN_SUPER' | 'ADMIN_FINANCE' | 'ADMIN_CARE'

type CommercialSettings = {
  currency: {
    baseCurrency: 'USD' | 'MVR'
    supportedCurrencies: Array<'USD' | 'MVR'>
  }
  exchangeRates: {
    rates: Array<{ from: 'USD' | 'MVR'; to: 'USD' | 'MVR'; rate: number }>
    updatedAt: string
  }
  loyalty: {
    enabled: boolean
    pointsPerUnitSpend: number
    unitCurrency: 'USD' | 'MVR'
    redemptionValuePerPoint: number
    minimumPointsToRedeem: number
  }
  metadata?: {
    updatedAt: string | null
  }
}

const WRITE_ROLES: AdminRole[] = ['ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE']

export default function AdminCommercialSettingsPanel({ apiBase }: { apiBase: string }) {
  const {
    userId,
    setUserId,
    userEmail,
    setUserEmail,
    userRole,
    setUserRole,
  } = usePersistedIdentity<AdminRole>({
    defaults: {
      userId: 'admin-finance-1',
      userEmail: 'admin-finance-1@example.test',
      userRole: 'ADMIN_FINANCE',
    },
    storageKeyPrefix: 'workation.admin',
  })

  const [loading, setLoading] = useState(false)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [info, setInfo] = useState<string | null>(null)
  const [settings, setSettings] = useState<CommercialSettings | null>(null)

  const canWrite = WRITE_ROLES.includes(userRole)
  const { loadSettingsMutation, saveSettingsMutation } = useAdminSettingsMutations<CommercialSettings>(apiBase, {
    userId,
    userRole,
    userEmail,
  })

  async function loadSettings() {
    setLoading(true)
    setError(null)
    setInfo(null)

    try {
      const payload = await loadSettingsMutation.mutateAsync()
      setSettings(payload)
    } catch (requestError) {
      if (requestError instanceof ApiError) {
        setError(requestError.message)
        return
      }

      setError('Unable to load settings. Check backend connectivity.')
    } finally {
      setLoading(false)
    }
  }

  async function saveSettings(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!settings) {
      return
    }

    if (!canWrite) {
      setError('Current role is read-only for commercial settings updates.')
      return
    }

    setSaving(true)
    setError(null)
    setInfo(null)

    try {
      const payload = await saveSettingsMutation.mutateAsync(settings)
      setSettings(payload)
      setInfo('Commercial settings saved successfully.')
    } catch (requestError) {
      if (requestError instanceof ApiError) {
        setError(requestError.message)
        return
      }

      setError('Unable to save settings. Check backend connectivity.')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="space-y-6">
      <section className="rounded border border-slate-200 bg-white p-4">
        <h3 className="mb-3 text-base font-semibold text-slate-900">Admin identity</h3>
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div>
            <label className="mb-1 block text-sm text-slate-700" htmlFor="settings-user-id">User ID</label>
            <input
              id="settings-user-id"
              className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
              value={userId}
              onChange={(event) => setUserId(event.target.value)}
            />
          </div>
          <div>
            <label className="mb-1 block text-sm text-slate-700" htmlFor="settings-user-email">User Email</label>
            <input
              id="settings-user-email"
              className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
              value={userEmail}
              onChange={(event) => setUserEmail(event.target.value)}
            />
          </div>
        </div>
        <div className="mt-3">
          <label className="mb-1 block text-sm text-slate-700" htmlFor="settings-user-role">Role</label>
          <select
            id="settings-user-role"
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
        <div className="mb-3 flex items-center gap-2">
          <button
            type="button"
            className="rounded bg-slate-900 px-3 py-2 text-sm font-medium text-white disabled:opacity-60"
            onClick={loadSettings}
            disabled={loading}
          >
            {loading ? 'Loading...' : 'Load settings'}
          </button>
          <span className="text-sm text-slate-600">
            {canWrite ? 'Write access enabled' : 'Read-only role'}
          </span>
        </div>

        {error ? <p className="mb-2 text-sm text-rose-700">{error}</p> : null}
        {info ? <p className="mb-2 text-sm text-emerald-700">{info}</p> : null}

        {settings ? (
          <form onSubmit={saveSettings} className="space-y-6">
            <div className="rounded border border-slate-200 p-3">
              <h4 className="mb-2 text-sm font-semibold text-slate-900">Currency settings</h4>
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                  <label className="mb-1 block text-sm text-slate-700">Base currency</label>
                  <select
                    className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                    value={settings.currency.baseCurrency}
                    onChange={(event) => setSettings((current) => current ? {
                      ...current,
                      currency: {
                        ...current.currency,
                        baseCurrency: event.target.value as 'USD' | 'MVR',
                      },
                    } : current)}
                  >
                    <option value="USD">USD</option>
                    <option value="MVR">MVR</option>
                  </select>
                </div>
                <div>
                  <label className="mb-1 block text-sm text-slate-700">Supported currencies</label>
                  <div className="flex gap-3 rounded border border-slate-300 px-3 py-2 text-sm">
                    {(['USD', 'MVR'] as const).map((code) => (
                      <label key={code} className="flex items-center gap-2">
                        <input
                          type="checkbox"
                          checked={settings.currency.supportedCurrencies.includes(code)}
                          onChange={(event) => {
                            setSettings((current) => {
                              if (!current) return current

                              const next = event.target.checked
                                ? Array.from(new Set([...current.currency.supportedCurrencies, code]))
                                : current.currency.supportedCurrencies.filter((item) => item !== code)

                              return {
                                ...current,
                                currency: {
                                  ...current.currency,
                                  supportedCurrencies: next,
                                },
                              }
                            })
                          }}
                        />
                        {code}
                      </label>
                    ))}
                  </div>
                </div>
              </div>
            </div>

            <div className="rounded border border-slate-200 p-3">
              <h4 className="mb-2 text-sm font-semibold text-slate-900">Exchange rates</h4>
              <div className="space-y-2">
                {settings.exchangeRates.rates.map((rate, index) => (
                  <div key={`${rate.from}-${rate.to}-${index}`} className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <input
                      className="rounded border border-slate-300 px-3 py-2 text-sm"
                      value={rate.from}
                      onChange={(event) => setSettings((current) => {
                        if (!current) return current
                        const nextRates = [...current.exchangeRates.rates]
                        nextRates[index] = { ...nextRates[index], from: event.target.value as 'USD' | 'MVR' }
                        return { ...current, exchangeRates: { ...current.exchangeRates, rates: nextRates } }
                      })}
                    />
                    <input
                      className="rounded border border-slate-300 px-3 py-2 text-sm"
                      value={rate.to}
                      onChange={(event) => setSettings((current) => {
                        if (!current) return current
                        const nextRates = [...current.exchangeRates.rates]
                        nextRates[index] = { ...nextRates[index], to: event.target.value as 'USD' | 'MVR' }
                        return { ...current, exchangeRates: { ...current.exchangeRates, rates: nextRates } }
                      })}
                    />
                    <input
                      className="rounded border border-slate-300 px-3 py-2 text-sm"
                      value={String(rate.rate)}
                      onChange={(event) => setSettings((current) => {
                        if (!current) return current
                        const nextRates = [...current.exchangeRates.rates]
                        nextRates[index] = { ...nextRates[index], rate: Number(event.target.value) }
                        return { ...current, exchangeRates: { ...current.exchangeRates, rates: nextRates } }
                      })}
                    />
                  </div>
                ))}
              </div>
            </div>

            <div className="rounded border border-slate-200 p-3">
              <h4 className="mb-2 text-sm font-semibold text-slate-900">Loyalty program</h4>
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label className="flex items-center gap-2 text-sm text-slate-700">
                  <input
                    type="checkbox"
                    checked={settings.loyalty.enabled}
                    onChange={(event) => setSettings((current) => current ? {
                      ...current,
                      loyalty: { ...current.loyalty, enabled: event.target.checked },
                    } : current)}
                  />
                  Loyalty enabled
                </label>
                <div>
                  <label className="mb-1 block text-sm text-slate-700">Points per unit spend</label>
                  <input
                    className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                    value={String(settings.loyalty.pointsPerUnitSpend)}
                    onChange={(event) => setSettings((current) => current ? {
                      ...current,
                      loyalty: { ...current.loyalty, pointsPerUnitSpend: Number(event.target.value) },
                    } : current)}
                  />
                </div>
                <div>
                  <label className="mb-1 block text-sm text-slate-700">Unit currency</label>
                  <select
                    className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                    value={settings.loyalty.unitCurrency}
                    onChange={(event) => setSettings((current) => current ? {
                      ...current,
                      loyalty: { ...current.loyalty, unitCurrency: event.target.value as 'USD' | 'MVR' },
                    } : current)}
                  >
                    <option value="USD">USD</option>
                    <option value="MVR">MVR</option>
                  </select>
                </div>
                <div>
                  <label className="mb-1 block text-sm text-slate-700">Value per point</label>
                  <input
                    className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                    value={String(settings.loyalty.redemptionValuePerPoint)}
                    onChange={(event) => setSettings((current) => current ? {
                      ...current,
                      loyalty: { ...current.loyalty, redemptionValuePerPoint: Number(event.target.value) },
                    } : current)}
                  />
                </div>
                <div>
                  <label className="mb-1 block text-sm text-slate-700">Minimum points to redeem</label>
                  <input
                    className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
                    value={String(settings.loyalty.minimumPointsToRedeem)}
                    onChange={(event) => setSettings((current) => current ? {
                      ...current,
                      loyalty: { ...current.loyalty, minimumPointsToRedeem: Number(event.target.value) },
                    } : current)}
                  />
                </div>
              </div>
            </div>

            <button
              type="submit"
              disabled={saving || !canWrite}
              className="rounded bg-slate-900 px-3 py-2 text-sm font-medium text-white disabled:opacity-60"
            >
              {saving ? 'Saving...' : 'Save settings'}
            </button>

            {settings.metadata?.updatedAt ? (
              <p className="text-xs text-slate-500">Last updated: {new Date(settings.metadata.updatedAt).toLocaleString()}</p>
            ) : null}
          </form>
        ) : (
          <p className="text-sm text-slate-600">Load settings to view currency, exchange rate, and loyalty configuration.</p>
        )}
      </section>
    </div>
  )
}

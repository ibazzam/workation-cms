'use client'

import { FormEvent, useMemo, useState } from 'react'
import { ApiError } from '../lib/api-client'
import { useAuthSession } from '../lib/hooks/use-auth-session'
import {
  useBookingAction,
  useBookingsList,
  useRebookMutation,
  useRebookTemplate,
} from '../lib/hooks/use-bookings-management'
import { usePersistedIdentity } from '../lib/hooks/use-persisted-identity'

type Role = 'USER' | 'VENDOR' | 'ADMIN'

type RebookFormState = {
  bookingId: string
  accommodationId: string
  transportId: string
  transportFareClassCode: string
  startDate: string
  endDate: string
  guests: string
}

export default function BookingsManagementPanel({ apiBase }: { apiBase: string }) {
  const authSessionQuery = useAuthSession()
  const {
    userId,
    setUserId,
    userEmail,
    setUserEmail,
    userRole,
    setUserRole,
  } = usePersistedIdentity<Role>({
    defaults: {
      userId: 'demo-user-1',
      userEmail: 'demo-user-1@example.test',
      userRole: 'USER',
    },
    storageKeyPrefix: 'workation',
    sessionUser: authSessionQuery.data,
    preferSession: true,
  })

  const identity = useMemo(
    () => ({ userId, userEmail, userRole }),
    [userId, userEmail, userRole],
  )

  const [actionError, setActionError] = useState<string | null>(null)
  const [actionInfo, setActionInfo] = useState<string | null>(null)
  const [templateLoadingBookingId, setTemplateLoadingBookingId] = useState<string | null>(null)
  const [rebookForm, setRebookForm] = useState<RebookFormState | null>(null)

  const bookingsQuery = useBookingsList(apiBase, identity)
  const bookingActionMutation = useBookingAction(apiBase, identity)
  const rebookTemplateMutation = useRebookTemplate(apiBase, identity)
  const rebookMutation = useRebookMutation(apiBase, identity)

  function getMessage(error: unknown, fallback: string) {
    if (error instanceof ApiError) {
      return error.message
    }

    if (error instanceof Error && error.message) {
      return error.message
    }

    return fallback
  }

  async function runAction(bookingId: string, action: 'confirm' | 'cancel') {
    setActionError(null)
    setActionInfo(null)

    try {
      await bookingActionMutation.mutateAsync({ bookingId, action })
      setActionInfo(`${action.toUpperCase()} completed for booking ${bookingId}`)
      await bookingsQuery.refetch()
    } catch (error) {
      setActionError(getMessage(error, `Unable to ${action} booking`))
    }
  }

  async function prepareRebook(bookingId: string) {
    setTemplateLoadingBookingId(bookingId)
    setActionError(null)
    setActionInfo(null)

    try {
      const payload = await rebookTemplateMutation.mutateAsync(bookingId)
      if (!payload.template.canRebook) {
        setActionError(payload.template.reason ?? 'Booking cannot be rebooked')
        return
      }

      setRebookForm({
        bookingId,
        accommodationId: payload.template.defaults.accommodationId ?? '',
        transportId: payload.template.defaults.transportId ?? '',
        transportFareClassCode: payload.template.defaults.transportFareClassCode ?? '',
        startDate: payload.template.defaults.startDate ? payload.template.defaults.startDate.slice(0, 10) : '',
        endDate: payload.template.defaults.endDate ? payload.template.defaults.endDate.slice(0, 10) : '',
        guests: payload.template.defaults.guests ? String(payload.template.defaults.guests) : '',
      })
    } catch (error) {
      setActionError(getMessage(error, 'Unable to load rebook defaults'))
    } finally {
      setTemplateLoadingBookingId(null)
    }
  }

  async function submitRebook(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!rebookForm) {
      return
    }

    setActionError(null)
    setActionInfo(null)

    const guests = Number(rebookForm.guests)
    const payload = {
      ...(rebookForm.accommodationId ? { accommodationId: rebookForm.accommodationId } : {}),
      ...(rebookForm.transportId ? { transportId: rebookForm.transportId } : {}),
      ...(rebookForm.transportFareClassCode ? { transportFareClassCode: rebookForm.transportFareClassCode } : {}),
      ...(rebookForm.startDate ? { startDate: rebookForm.startDate } : {}),
      ...(rebookForm.endDate ? { endDate: rebookForm.endDate } : {}),
      ...(Number.isInteger(guests) && guests > 0 ? { guests } : {}),
    }

    try {
      const result = await rebookMutation.mutateAsync({
        bookingId: rebookForm.bookingId,
        payload,
      })

      setActionInfo(
        `Rebooked ${result.rebookedFromBookingId} -> ${result.replacementBooking.id}`,
      )
      setRebookForm(null)
      await bookingsQuery.refetch()
    } catch (error) {
      setActionError(getMessage(error, 'Unable to rebook booking'))
    }
  }

  return (
    <div className="space-y-6">
      <section className="rounded border border-slate-200 bg-white p-4">
        <h3 className="mb-3 text-base font-semibold">User identity</h3>
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <input
            className="rounded border border-slate-300 px-3 py-2 text-sm"
            value={userId}
            onChange={(event) => setUserId(event.target.value)}
            placeholder="User ID"
          />
          <input
            className="rounded border border-slate-300 px-3 py-2 text-sm"
            value={userEmail}
            onChange={(event) => setUserEmail(event.target.value)}
            placeholder="User Email"
          />
          <select
            className="rounded border border-slate-300 px-3 py-2 text-sm"
            value={userRole}
            onChange={(event) => setUserRole(event.target.value as Role)}
          >
            <option value="USER">USER</option>
            <option value="VENDOR">VENDOR</option>
            <option value="ADMIN">ADMIN</option>
          </select>
        </div>
      </section>

      <section className="rounded border border-slate-200 bg-white p-4">
        <div className="mb-3 flex items-center justify-between gap-2">
          <h3 className="text-base font-semibold">My bookings</h3>
          <button
            type="button"
            className="rounded border border-slate-300 px-3 py-2 text-sm"
            onClick={() => bookingsQuery.refetch()}
            disabled={bookingsQuery.isFetching}
          >
            {bookingsQuery.isFetching ? 'Refreshing...' : 'Refresh'}
          </button>
        </div>

        {actionError ? <p className="mb-2 text-sm text-rose-700">{actionError}</p> : null}
        {actionInfo ? <p className="mb-2 text-sm text-emerald-700">{actionInfo}</p> : null}

        {bookingsQuery.isLoading ? <p className="text-sm text-slate-600">Loading bookings...</p> : null}
        {bookingsQuery.isError ? (
          <p className="text-sm text-rose-700">{getMessage(bookingsQuery.error, 'Unable to load bookings')}</p>
        ) : null}

        {!bookingsQuery.isLoading && bookingsQuery.data?.length === 0 ? (
          <p className="text-sm text-slate-600">No bookings found for the current identity.</p>
        ) : null}

        <ul className="space-y-3">
          {bookingsQuery.data?.map((booking) => (
            <li key={booking.id} className="rounded border border-slate-200 p-3">
              <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                <p className="text-sm font-medium text-slate-900">Booking {booking.id}</p>
                <p className="text-sm text-slate-700">Status: {booking.status}</p>
              </div>
              <p className="text-sm text-slate-600">
                Dates: {booking.startDate ? booking.startDate.slice(0, 10) : 'n/a'} → {booking.endDate ? booking.endDate.slice(0, 10) : 'n/a'}
              </p>
              <p className="text-sm text-slate-600">Guests: {booking.guests ?? 'n/a'}</p>
              <p className="text-sm text-slate-600">Total: {booking.totalPrice ?? 'n/a'}</p>

              <div className="mt-3 flex flex-wrap gap-2">
                <button
                  type="button"
                  className="rounded border border-slate-300 px-2 py-1 text-xs"
                  disabled={!booking.management?.canConfirm || bookingActionMutation.isPending}
                  onClick={() => runAction(booking.id, 'confirm')}
                >
                  Confirm
                </button>
                <button
                  type="button"
                  className="rounded border border-slate-300 px-2 py-1 text-xs"
                  disabled={!booking.management?.canCancel || bookingActionMutation.isPending}
                  onClick={() => runAction(booking.id, 'cancel')}
                >
                  Cancel
                </button>
                <button
                  type="button"
                  className="rounded border border-slate-300 px-2 py-1 text-xs"
                  disabled={!booking.management?.canRebook || templateLoadingBookingId === booking.id}
                  onClick={() => prepareRebook(booking.id)}
                >
                  {templateLoadingBookingId === booking.id ? 'Preparing...' : 'Rebook'}
                </button>
              </div>
            </li>
          ))}
        </ul>
      </section>

      {rebookForm ? (
        <section className="rounded border border-slate-200 bg-white p-4">
          <h3 className="mb-3 text-base font-semibold">Rebook booking {rebookForm.bookingId}</h3>
          <form onSubmit={submitRebook} className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <input
              className="rounded border border-slate-300 px-3 py-2 text-sm"
              placeholder="Accommodation ID"
              value={rebookForm.accommodationId}
              onChange={(event) => setRebookForm((current) => current ? { ...current, accommodationId: event.target.value } : current)}
            />
            <input
              className="rounded border border-slate-300 px-3 py-2 text-sm"
              placeholder="Transport ID"
              value={rebookForm.transportId}
              onChange={(event) => setRebookForm((current) => current ? { ...current, transportId: event.target.value } : current)}
            />
            <input
              className="rounded border border-slate-300 px-3 py-2 text-sm"
              placeholder="Fare class code"
              value={rebookForm.transportFareClassCode}
              onChange={(event) => setRebookForm((current) => current ? { ...current, transportFareClassCode: event.target.value } : current)}
            />
            <input
              className="rounded border border-slate-300 px-3 py-2 text-sm"
              placeholder="Guests"
              value={rebookForm.guests}
              onChange={(event) => setRebookForm((current) => current ? { ...current, guests: event.target.value } : current)}
            />
            <input
              className="rounded border border-slate-300 px-3 py-2 text-sm"
              placeholder="Start date YYYY-MM-DD"
              value={rebookForm.startDate}
              onChange={(event) => setRebookForm((current) => current ? { ...current, startDate: event.target.value } : current)}
            />
            <input
              className="rounded border border-slate-300 px-3 py-2 text-sm"
              placeholder="End date YYYY-MM-DD"
              value={rebookForm.endDate}
              onChange={(event) => setRebookForm((current) => current ? { ...current, endDate: event.target.value } : current)}
            />

            <div className="sm:col-span-2 flex gap-2">
              <button
                type="submit"
                className="rounded bg-slate-900 px-3 py-2 text-sm text-white disabled:opacity-60"
                disabled={rebookMutation.isPending}
              >
                {rebookMutation.isPending ? 'Rebooking...' : 'Submit rebook'}
              </button>
              <button
                type="button"
                className="rounded border border-slate-300 px-3 py-2 text-sm"
                onClick={() => setRebookForm(null)}
              >
                Close
              </button>
            </div>
          </form>
        </section>
      ) : null}
    </div>
  )
}

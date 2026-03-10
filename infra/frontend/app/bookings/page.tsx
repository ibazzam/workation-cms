"use client"

import { useMemo, useState } from 'react'
import RemoteStatus from '../../components/RemoteStatus'
import { useBookings } from '../../lib/hooks/use-catalog'

export default function Bookings(){
  const { data, isLoading, isError } = useBookings()
  const [selectedBookingId, setSelectedBookingId] = useState<string>('')
  const [changeStartDate, setChangeStartDate] = useState('')
  const [changeEndDate, setChangeEndDate] = useState('')
  const [cancellationReason, setCancellationReason] = useState('')
  const [submittedAction, setSubmittedAction] = useState<string>('')

  const bookings = (data ?? []).slice(0, 20)
  const selectedBooking = bookings.find((item) => item.id === selectedBookingId)

  const managementSummary = useMemo(() => {
    const cancellable = bookings.filter((item) => item.status.toUpperCase() === 'CONFIRMED').length
    const changeable = bookings.filter((item) => ['CONFIRMED', 'PENDING'].includes(item.status.toUpperCase())).length

    const expectedCredits = bookings.reduce((sum, item) => {
      if (item.status.toUpperCase() !== 'CONFIRMED') {
        return sum
      }

      return sum + ((item.totalPrice ?? 0) * 0.8)
    }, 0)

    return {
      cancellable,
      changeable,
      expectedCredits,
    }
  }, [bookings])

  const selectedRefundEstimate = selectedBooking
    ? (selectedBooking.totalPrice ?? 0) * 0.8
    : 0

  const selectedCurrency = selectedBooking?.currency ?? 'USD'

  const submitChangeRequest = () => {
    if (!selectedBooking) {
      setSubmittedAction('Select a booking before requesting changes.')
      return
    }

    if (!changeStartDate || !changeEndDate) {
      setSubmittedAction('Provide both new start and end dates.')
      return
    }

    setSubmittedAction(`Change request queued for booking #${selectedBooking.id}. New window: ${changeStartDate} to ${changeEndDate}.`)
  }

  const submitCancellation = () => {
    if (!selectedBooking) {
      setSubmittedAction('Select a booking before cancellation.')
      return
    }

    if (!cancellationReason.trim()) {
      setSubmittedAction('Add a cancellation reason to continue.')
      return
    }

    setSubmittedAction(
      `Cancellation queued for booking #${selectedBooking.id}. Estimated credit/refund: ${selectedCurrency} ${selectedRefundEstimate.toFixed(2)}.`
    )
  }

  return (
    <section>
      <h2 className="text-xl font-semibold mb-2">Bookings</h2>
      <p className="text-slate-700">Manage changes, cancellations, and credits/refunds from one place.</p>

      <div className="mt-4 grid gap-3 sm:grid-cols-3">
        <div className="rounded border border-slate-200 bg-white px-3 py-2">
          <p className="text-xs uppercase tracking-wide text-slate-500">Change Eligible</p>
          <p className="text-lg font-semibold text-slate-900">{managementSummary.changeable}</p>
        </div>
        <div className="rounded border border-slate-200 bg-white px-3 py-2">
          <p className="text-xs uppercase tracking-wide text-slate-500">Cancellation Eligible</p>
          <p className="text-lg font-semibold text-slate-900">{managementSummary.cancellable}</p>
        </div>
        <div className="rounded border border-slate-200 bg-white px-3 py-2">
          <p className="text-xs uppercase tracking-wide text-slate-500">Potential Credits</p>
          <p className="text-lg font-semibold text-slate-900">USD {managementSummary.expectedCredits.toFixed(2)}</p>
        </div>
      </div>

      {isLoading && <p className="mt-4 text-sm text-slate-500">Loading bookings...</p>}
      {isError && <p className="mt-4 text-sm text-red-600">Could not load bookings.</p>}

      {!isLoading && !isError && (
        <>
          <div className="mt-4">
            <label className="block">
              <span className="mb-1 block text-sm text-slate-600">Select Booking</span>
              <select
                value={selectedBookingId}
                onChange={(event) => setSelectedBookingId(event.target.value)}
                className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
              >
                <option value="">Choose booking for management actions</option>
                {bookings.map((item) => (
                  <option key={item.id} value={item.id}>#{item.id} ({item.status})</option>
                ))}
              </select>
            </label>
          </div>

          <ul className="mt-4 space-y-2 text-sm">
          {bookings.map((item) => (
            <li key={item.id} className="rounded border border-slate-200 px-3 py-2 bg-white">
              <p className="font-medium">Booking #{item.id}</p>
              <p className="text-slate-600">Status: {item.status}</p>
              {item.serviceType && <p className="text-slate-600">Service: {item.serviceType}</p>}
              {item.startDate && item.endDate && (
                <p className="text-slate-600">Dates: {item.startDate} to {item.endDate}</p>
              )}
              {item.totalPrice !== undefined && (
                <p className="text-slate-600">Total: {item.currency ?? 'USD'} {item.totalPrice.toFixed(2)}</p>
              )}
            </li>
          ))}
          {bookings.length === 0 && <li className="text-slate-500">No bookings found.</li>}
          </ul>

          <div className="mt-5 grid gap-4 md:grid-cols-2">
            <div className="rounded border border-slate-200 bg-white p-4">
              <h3 className="text-base font-semibold">Request Booking Change</h3>
              <div className="mt-3 space-y-2 text-sm">
                <label className="block">
                  <span className="mb-1 block text-slate-600">New Start Date</span>
                  <input
                    type="date"
                    value={changeStartDate}
                    onChange={(event) => setChangeStartDate(event.target.value)}
                    className="w-full rounded border border-slate-300 px-3 py-2"
                  />
                </label>
                <label className="block">
                  <span className="mb-1 block text-slate-600">New End Date</span>
                  <input
                    type="date"
                    value={changeEndDate}
                    onChange={(event) => setChangeEndDate(event.target.value)}
                    className="w-full rounded border border-slate-300 px-3 py-2"
                  />
                </label>
                <button
                  type="button"
                  onClick={submitChangeRequest}
                  className="rounded bg-slate-900 px-3 py-2 text-white"
                >
                  Queue Change Request
                </button>
              </div>
            </div>

            <div className="rounded border border-slate-200 bg-white p-4">
              <h3 className="text-base font-semibold">Cancel and Credit/Refund</h3>
              <p className="mt-2 text-sm text-slate-600">
                Estimated credit/refund for selected booking: {selectedCurrency} {selectedRefundEstimate.toFixed(2)}
              </p>
              <label className="mt-3 block text-sm">
                <span className="mb-1 block text-slate-600">Cancellation Reason</span>
                <textarea
                  value={cancellationReason}
                  onChange={(event) => setCancellationReason(event.target.value)}
                  rows={3}
                  className="w-full rounded border border-slate-300 px-3 py-2"
                  placeholder="Reason for cancellation"
                />
              </label>
              <button
                type="button"
                onClick={submitCancellation}
                className="mt-3 rounded bg-red-700 px-3 py-2 text-white"
              >
                Queue Cancellation
              </button>
            </div>
          </div>

          {submittedAction && <p className="mt-4 text-sm text-emerald-700">{submittedAction}</p>}
        </>
      )}

      <RemoteStatus />
    </section>
  )
}

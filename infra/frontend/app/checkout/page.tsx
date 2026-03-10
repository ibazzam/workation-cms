"use client"

import { useMemo, useState } from 'react'
import RemoteStatus from '../../components/RemoteStatus'
import {
  useAccommodations,
  useExcursions,
  useTransports,
} from '../../lib/hooks/use-catalog'

export default function CheckoutPage() {
  const accommodationsQuery = useAccommodations()
  const transportsQuery = useTransports()
  const excursionsQuery = useExcursions()

  const accommodations = accommodationsQuery.data ?? []
  const transports = transportsQuery.data ?? []
  const excursions = excursionsQuery.data ?? []

  const [selectedAccommodationId, setSelectedAccommodationId] = useState<string>('')
  const [selectedTransportId, setSelectedTransportId] = useState<string>('')
  const [selectedExcursionId, setSelectedExcursionId] = useState<string>('')

  const selectedAccommodation = accommodations.find((item) => item.id === selectedAccommodationId)
  const selectedTransport = transports.find((item) => item.id === selectedTransportId)
  const selectedExcursion = excursions.find((item) => item.id === selectedExcursionId)

  const summary = useMemo(() => {
    const stayAmount = selectedAccommodation ? 120 : 0
    const transportAmount = selectedTransport?.price ?? 0
    const activityAmount = selectedExcursion?.price ?? 0
    const subtotal = stayAmount + transportAmount + activityAmount
    const serviceFee = subtotal * 0.05
    const total = subtotal + serviceFee

    return {
      stayAmount,
      transportAmount,
      activityAmount,
      subtotal,
      serviceFee,
      total,
      currency: selectedTransport?.currency ?? selectedExcursion?.currency ?? 'USD',
    }
  }, [selectedAccommodation, selectedTransport, selectedExcursion])

  const loading = accommodationsQuery.isLoading || transportsQuery.isLoading || excursionsQuery.isLoading
  const error = accommodationsQuery.isError || transportsQuery.isError || excursionsQuery.isError

  return (
    <section>
      <h2 className="text-xl font-semibold mb-2">Bundle Checkout</h2>
      <p className="text-slate-700">Build a stay + transport + activity bundle before final booking.</p>

      <div className="mt-4 grid gap-4 md:grid-cols-3">
        <label className="block">
          <span className="mb-1 block text-sm text-slate-600">Stay</span>
          <select
            value={selectedAccommodationId}
            onChange={(event) => setSelectedAccommodationId(event.target.value)}
            className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          >
            <option value="">Select accommodation</option>
            {accommodations.map((item) => (
              <option key={item.id} value={item.id}>{item.name}</option>
            ))}
          </select>
        </label>

        <label className="block">
          <span className="mb-1 block text-sm text-slate-600">Transport</span>
          <select
            value={selectedTransportId}
            onChange={(event) => setSelectedTransportId(event.target.value)}
            className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          >
            <option value="">Select transport</option>
            {transports.map((item) => (
              <option key={item.id} value={item.id}>{item.name}</option>
            ))}
          </select>
        </label>

        <label className="block">
          <span className="mb-1 block text-sm text-slate-600">Activity</span>
          <select
            value={selectedExcursionId}
            onChange={(event) => setSelectedExcursionId(event.target.value)}
            className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          >
            <option value="">Select activity</option>
            {excursions.map((item) => (
              <option key={item.id} value={item.id}>{item.title}</option>
            ))}
          </select>
        </label>
      </div>

      {loading && <p className="mt-4 text-sm text-slate-500">Loading checkout catalog...</p>}
      {error && <p className="mt-4 text-sm text-red-600">Could not load checkout data.</p>}

      {!loading && !error && (
        <div className="mt-5 rounded border border-slate-200 bg-white p-4">
          <h3 className="text-base font-semibold">Bundle Summary</h3>
          <ul className="mt-3 space-y-1 text-sm text-slate-700">
            <li>Stay estimate: {summary.currency} {summary.stayAmount.toFixed(2)}</li>
            <li>Transport: {summary.currency} {summary.transportAmount.toFixed(2)}</li>
            <li>Activity: {summary.currency} {summary.activityAmount.toFixed(2)}</li>
            <li>Subtotal: {summary.currency} {summary.subtotal.toFixed(2)}</li>
            <li>Service fee (5%): {summary.currency} {summary.serviceFee.toFixed(2)}</li>
            <li className="font-semibold text-slate-900">Total: {summary.currency} {summary.total.toFixed(2)}</li>
          </ul>
        </div>
      )}

      <RemoteStatus />
    </section>
  )
}

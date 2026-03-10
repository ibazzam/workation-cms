"use client"

import RemoteStatus from '../components/RemoteStatus'
import {
  useAccommodations,
  useBookings,
  useIslands,
  useVendors,
} from '../lib/hooks/use-catalog'

export default function Home() {
  const accommodations = useAccommodations()
  const islands = useIslands()
  const vendors = useVendors()
  const bookings = useBookings()

  const cards = [
    { label: 'Accommodations', value: accommodations.data?.length ?? 0, loading: accommodations.isLoading },
    { label: 'Islands', value: islands.data?.length ?? 0, loading: islands.isLoading },
    { label: 'Vendors', value: vendors.data?.length ?? 0, loading: vendors.isLoading },
    { label: 'Bookings', value: bookings.data?.length ?? 0, loading: bookings.isLoading },
  ]

  return (
    <section>
      <h2 className="text-xl font-semibold mb-2">Welcome to Workation</h2>
      <p className="text-slate-700">Shared typed hooks and query keys are active across all catalog pages.</p>

      <div className="mt-4 grid gap-3 sm:grid-cols-2">
        {cards.map((card) => (
          <div key={card.label} className="rounded border border-slate-200 bg-white px-4 py-3">
            <p className="text-xs uppercase tracking-wide text-slate-500">{card.label}</p>
            <p className="mt-1 text-2xl font-semibold text-slate-900">
              {card.loading ? '...' : card.value}
            </p>
          </div>
        ))}
      </div>

      <RemoteStatus />
    </section>
  )
}

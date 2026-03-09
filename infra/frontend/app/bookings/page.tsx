"use client"

import RemoteStatus from '../../components/RemoteStatus'
import { useBookings } from '../../lib/hooks/use-catalog'

export default function Bookings(){
  const { data, isLoading, isError } = useBookings()
  const bookings = data ?? []

  return (
    <section>
      <h2 className="text-xl font-semibold mb-2">Bookings</h2>
      <p className="text-slate-700">Manage bookings and view history.</p>

      {isLoading && <p className="mt-4 text-sm text-slate-500">Loading bookings...</p>}
      {isError && <p className="mt-4 text-sm text-red-600">Could not load bookings.</p>}

      {!isLoading && !isError && (
        <ul className="mt-4 space-y-2 text-sm">
          {bookings.slice(0, 10).map((item) => (
            <li key={item.id} className="rounded border border-slate-200 px-3 py-2 bg-white">
              <p className="font-medium">Booking #{item.id}</p>
              <p className="text-slate-600">Status: {item.status}</p>
              {item.serviceType && <p className="text-slate-600">Service: {item.serviceType}</p>}
            </li>
          ))}
          {bookings.length === 0 && <li className="text-slate-500">No bookings found.</li>}
        </ul>
      )}

      <RemoteStatus />
    </section>
  )
}

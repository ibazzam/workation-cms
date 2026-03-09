"use client"

import RemoteStatus from '../../components/RemoteStatus'
import { useAccommodations } from '../../lib/hooks/use-catalog'

export default function Accommodations(){
  const { data, isLoading, isError } = useAccommodations()
  const accommodations = data ?? []

  return (
    <section>
      <h2 className="text-xl font-semibold mb-2">Accommodations</h2>
      <p className="text-slate-700">Browse available stays.</p>

      {isLoading && <p className="mt-4 text-sm text-slate-500">Loading accommodations...</p>}
      {isError && <p className="mt-4 text-sm text-red-600">Could not load accommodations.</p>}

      {!isLoading && !isError && (
        <ul className="mt-4 space-y-2 text-sm">
          {accommodations.slice(0, 10).map((item) => (
            <li key={item.id} className="rounded border border-slate-200 px-3 py-2 bg-white">
              <p className="font-medium">{item.name}</p>
              {item.islandName && <p className="text-slate-600">Island: {item.islandName}</p>}
            </li>
          ))}
          {accommodations.length === 0 && <li className="text-slate-500">No accommodations found.</li>}
        </ul>
      )}

      <RemoteStatus />
    </section>
  )
}

"use client"

import RemoteStatus from '../../components/RemoteStatus'
import { useIslands } from '../../lib/hooks/use-catalog'

export default function Islands(){
  const { data, isLoading, isError } = useIslands()

  return (
    <section>
      <h2 className="text-xl font-semibold mb-2">Islands</h2>
      <p className="text-slate-700">Island directory and details.</p>

      {isLoading && <p className="mt-4 text-sm text-slate-500">Loading islands...</p>}
      {isError && <p className="mt-4 text-sm text-red-600">Could not load islands.</p>}

      {!isLoading && !isError && (
        <ul className="mt-4 space-y-2 text-sm">
          {(data ?? []).slice(0, 10).map((item) => (
            <li key={item.id} className="rounded border border-slate-200 px-3 py-2 bg-white">
              <p className="font-medium">{item.name}</p>
              {item.atollName && <p className="text-slate-600">Atoll: {item.atollName}</p>}
            </li>
          ))}
          {(data ?? []).length === 0 && <li className="text-slate-500">No islands found.</li>}
        </ul>
      )}

      <RemoteStatus />
    </section>
  )
}

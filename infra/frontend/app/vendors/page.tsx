"use client"

import RemoteStatus from '../../components/RemoteStatus'
import { useVendors } from '../../lib/hooks/use-catalog'

export default function Vendors(){
  const { data, isLoading, isError } = useVendors()
  const vendors = data ?? []

  return (
    <section>
      <h2 className="text-xl font-semibold mb-2">Vendors</h2>
      <p className="text-slate-700">Vendors and partners listing.</p>

      {isLoading && <p className="mt-4 text-sm text-slate-500">Loading vendors...</p>}
      {isError && <p className="mt-4 text-sm text-red-600">Could not load vendors.</p>}

      {!isLoading && !isError && (
        <ul className="mt-4 space-y-2 text-sm">
          {vendors.slice(0, 10).map((item) => (
            <li key={item.id} className="rounded border border-slate-200 px-3 py-2 bg-white">
              <p className="font-medium">{item.name}</p>
            </li>
          ))}
          {vendors.length === 0 && <li className="text-slate-500">No vendors found.</li>}
        </ul>
      )}

      <RemoteStatus />
    </section>
  )
}

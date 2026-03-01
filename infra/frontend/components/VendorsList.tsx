'use client'

import { useVendors } from '../lib/hooks/use-vendors'

export default function VendorsList({ apiBase }: { apiBase: string }) {
  const { data: items, isLoading, isError, error } = useVendors(apiBase)

  if (isLoading) {
    return <p className="text-sm text-slate-600 mb-4">Loading vendors...</p>
  }

  if (isError) {
    return <p className="text-sm text-rose-700 mb-4">{error instanceof Error ? error.message : 'Unable to load vendors.'}</p>
  }

  if (!items || items.length === 0) {
    return <p className="text-sm text-slate-600 mb-4">No vendors found.</p>
  }

  return (
    <ul className="space-y-3 mb-4">
      {items.map((item) => (
        <li key={item.id} className="rounded border border-slate-200 bg-white p-3">
          <p className="font-medium text-slate-900">{item.name}</p>
          <p className="text-sm text-slate-600">Email: {item.email ?? 'n/a'}</p>
          <p className="text-sm text-slate-700">Phone: {item.phone ?? 'n/a'}</p>
        </li>
      ))}
    </ul>
  )
}

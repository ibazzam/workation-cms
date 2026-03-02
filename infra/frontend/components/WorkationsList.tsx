'use client'

import { useWorkations } from '../lib/hooks/use-workations'

export default function WorkationsList({ apiBase }: { apiBase: string }) {
  const { data: items, isLoading, isError, error } = useWorkations(apiBase)

  if (isLoading) {
    return <p className="text-sm text-slate-600 mb-4">Loading workations...</p>
  }

  if (isError) {
    return <p className="text-sm text-rose-700 mb-4">{error instanceof Error ? error.message : 'Unable to load workations.'}</p>
  }

  if (!items || items.length === 0) {
    return <p className="text-sm text-slate-600 mb-4">No workations found.</p>
  }

  return (
    <ul className="space-y-3 mb-4">
      {items.map((item) => (
        <li key={item.id} className="rounded border border-slate-200 bg-white p-3">
          <p className="font-medium text-slate-900">{item.title}</p>
          <p className="text-sm text-slate-600">{item.location}</p>
          <p className="text-sm text-slate-700">{item.start_date.slice(0, 10)} to {item.end_date.slice(0, 10)}</p>
          <p className="text-sm text-slate-800">USD {Number(item.price).toFixed(2)}</p>
        </li>
      ))}
    </ul>
  )
}

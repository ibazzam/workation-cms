'use client'

import { useIslands } from '../lib/hooks/use-islands'

export default function IslandsList({ apiBase }: { apiBase: string }) {
  const { data: items, isLoading, isError, error } = useIslands(apiBase)

  if (isLoading) {
    return <p className="text-sm text-slate-600 mb-4">Loading islands...</p>
  }

  if (isError) {
    return <p className="text-sm text-rose-700 mb-4">{error instanceof Error ? error.message : 'Unable to load islands.'}</p>
  }

  if (!items || items.length === 0) {
    return <p className="text-sm text-slate-600 mb-4">No islands found.</p>
  }

  return (
    <ul className="space-y-3 mb-4">
      {items.map((item) => (
        <li key={item.id} className="rounded border border-slate-200 bg-white p-3">
          <p className="font-medium text-slate-900">{item.name}</p>
          <p className="text-sm text-slate-600">Slug: {item.slug}</p>
          <p className="text-sm text-slate-700">Atoll: {item.atoll?.name ?? item.atollId ?? 'n/a'}</p>
        </li>
      ))}
    </ul>
  )
}

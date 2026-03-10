"use client"

import { useMemo, useState } from 'react'
import { useAccommodations, useIslands } from '../../lib/hooks/use-catalog'
import RemoteStatus from '../../components/RemoteStatus'

export default function DiscoverPage() {
  const { data: islandsData, isLoading: islandsLoading, isError: islandsError } = useIslands()
  const { data: accommodationsData, isLoading: accommodationsLoading, isError: accommodationsError } = useAccommodations()

  const islands = islandsData ?? []
  const accommodations = accommodationsData ?? []

  const [query, setQuery] = useState('')
  const [selectedAtoll, setSelectedAtoll] = useState('ALL')
  const [selectedIsland, setSelectedIsland] = useState('ALL')

  const atolls = useMemo(() => {
    return Array.from(new Set(islands.map((item) => item.atollName).filter((item): item is string => Boolean(item))))
      .sort((a, b) => a.localeCompare(b))
  }, [islands])

  const islandOptions = useMemo(() => {
    return islands
      .filter((item) => selectedAtoll === 'ALL' || item.atollName === selectedAtoll)
      .sort((a, b) => a.name.localeCompare(b.name))
  }, [islands, selectedAtoll])

  const normalizedQuery = query.trim().toLowerCase()

  const filteredAccommodations = useMemo(() => {
    return accommodations.filter((item) => {
      const islandName = (item.islandName ?? '').toLowerCase()
      const name = item.name.toLowerCase()
      const matchesText =
        normalizedQuery.length === 0 || name.includes(normalizedQuery) || islandName.includes(normalizedQuery)
      const matchesIsland = selectedIsland === 'ALL' || item.islandName === selectedIsland
      const matchesAtoll =
        selectedAtoll === 'ALL'
        || islands.some((island) => island.name === item.islandName && island.atollName === selectedAtoll)

      return matchesText && matchesIsland && matchesAtoll
    })
  }, [accommodations, islands, normalizedQuery, selectedIsland, selectedAtoll])

  const suggestions = useMemo(() => {
    if (selectedAtoll === 'ALL' && selectedIsland === 'ALL') {
      return [
        'Start with an atoll to narrow options by geography.',
        'Then pick an island to refine nearby stays.',
      ]
    }

    if (selectedAtoll !== 'ALL' && selectedIsland === 'ALL') {
      return [
        `You are browsing ${selectedAtoll}. Choose an island for tighter matches.`,
        'Compare island options before selecting a stay.',
      ]
    }

    return [
      `Selected island: ${selectedIsland}. Review matching stays and continue to bookings.`,
      'Tip: use search to find property names faster.',
    ]
  }, [selectedAtoll, selectedIsland])

  return (
    <section>
      <h2 className="text-xl font-semibold mb-2">Discover by Atoll and Island</h2>
      <p className="text-slate-700">Dependency-aware discovery to shortlist stays by geography.</p>

      <div className="mt-4 grid gap-3 md:grid-cols-3">
        <label className="block">
          <span className="mb-1 block text-sm text-slate-600">Search</span>
          <input
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder="Search island or accommodation"
            className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          />
        </label>

        <label className="block">
          <span className="mb-1 block text-sm text-slate-600">Atoll</span>
          <select
            value={selectedAtoll}
            onChange={(event) => {
              setSelectedAtoll(event.target.value)
              setSelectedIsland('ALL')
            }}
            className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          >
            <option value="ALL">All atolls</option>
            {atolls.map((atoll) => (
              <option key={atoll} value={atoll}>{atoll}</option>
            ))}
          </select>
        </label>

        <label className="block">
          <span className="mb-1 block text-sm text-slate-600">Island</span>
          <select
            value={selectedIsland}
            onChange={(event) => setSelectedIsland(event.target.value)}
            className="w-full rounded border border-slate-300 px-3 py-2 text-sm"
          >
            <option value="ALL">All islands</option>
            {islandOptions.map((island) => (
              <option key={island.id} value={island.name}>{island.name}</option>
            ))}
          </select>
        </label>
      </div>

      <div className="mt-4 rounded border border-slate-200 bg-white px-4 py-3">
        <p className="text-sm font-medium text-slate-900">Suggestions</p>
        <ul className="mt-2 list-disc pl-5 text-sm text-slate-600">
          {suggestions.map((entry) => (
            <li key={entry}>{entry}</li>
          ))}
        </ul>
      </div>

      {(islandsLoading || accommodationsLoading) && (
        <p className="mt-4 text-sm text-slate-500">Loading discovery data...</p>
      )}

      {(islandsError || accommodationsError) && (
        <p className="mt-4 text-sm text-red-600">Could not load discovery data.</p>
      )}

      {!islandsLoading && !accommodationsLoading && !islandsError && !accommodationsError && (
        <ul className="mt-4 space-y-2 text-sm">
          {filteredAccommodations.slice(0, 20).map((item) => (
            <li key={item.id} className="rounded border border-slate-200 px-3 py-2 bg-white">
              <p className="font-medium">{item.name}</p>
              {item.islandName && <p className="text-slate-600">Island: {item.islandName}</p>}
            </li>
          ))}
          {filteredAccommodations.length === 0 && (
            <li className="text-slate-500">No matches for the current search and location filters.</li>
          )}
        </ul>
      )}

      <RemoteStatus />
    </section>
  )
}

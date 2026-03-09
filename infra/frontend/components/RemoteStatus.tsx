"use client"
import { useHealthStatus } from "../lib/hooks/use-catalog"

export default function RemoteStatus(){
  const { data, isLoading, isError } = useHealthStatus()

  const label = isLoading
    ? 'loading...'
    : isError
      ? 'unreachable'
      : data?.status ?? 'unknown'

  return (
    <div className="mt-4 text-sm text-slate-600">Backend: {label}</div>
  )
}

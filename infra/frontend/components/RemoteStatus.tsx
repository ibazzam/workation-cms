"use client"
import { useEffect, useState } from "react"

export default function RemoteStatus(){
  const [status, setStatus] = useState<string | null>(null)
  useEffect(()=>{
    let mounted = true
    fetch("http://localhost:3000/")
      .then(res => {
        if(!mounted) return
        setStatus(res.ok ? "reachable" : `http ${res.status}`)
      })
      .catch(()=>{ if(mounted) setStatus("unreachable") })
    return ()=>{ mounted = false }
  },[])
  return (
    <div className="mt-4 text-sm text-slate-600">Backend: {status ?? 'loading...'}</div>
  )
}

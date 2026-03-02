import RemoteStatus from '../../components/RemoteStatus'
import WorkationsList from '../../components/WorkationsList'

export default function Accommodations(){
  const apiBase = process.env.WORKATION_API_BASE_URL ?? 'http://localhost:3000/api/v1'

  return (
    <section>
      <h2 className="text-xl font-semibold mb-2">Accommodations</h2>
      <p className="text-slate-700 mb-4">Live list from <span className="font-medium">/api/v1/workations</span>.</p>
      <WorkationsList apiBase={apiBase} />
      <RemoteStatus apiBase={apiBase} />
    </section>
  )
}

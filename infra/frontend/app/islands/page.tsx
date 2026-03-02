import RemoteStatus from '../../components/RemoteStatus'
import IslandsList from '../../components/IslandsList'

export default function Islands(){
  const apiBase = process.env.WORKATION_API_BASE_URL ?? 'http://localhost:3000/api/v1'

  return (
    <section>
      <h2 className="text-xl font-semibold mb-2">Islands</h2>
      <p className="text-slate-700 mb-4">Island directory and details.</p>
      <IslandsList apiBase={apiBase} />
      <RemoteStatus apiBase={apiBase} />
    </section>
  )
}

import RemoteStatus from '../../components/RemoteStatus'
import VendorsList from '../../components/VendorsList'

export default function Vendors(){
  const apiBase = process.env.WORKATION_API_BASE_URL ?? 'http://localhost:3000/api/v1'

  return (
    <section>
      <h2 className="text-xl font-semibold mb-2">Vendors</h2>
      <p className="text-slate-700 mb-4">Vendors and partners listing.</p>
      <VendorsList apiBase={apiBase} />
      <RemoteStatus apiBase={apiBase} />
    </section>
  )
}

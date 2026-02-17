import RemoteStatus from '../../components/RemoteStatus'

export default function Bookings(){
  return (
    <section>
      <h2 className="text-xl font-semibold mb-2">Bookings</h2>
      <p className="text-slate-700">Manage bookings and view history.</p>
      <RemoteStatus />
    </section>
  )
}

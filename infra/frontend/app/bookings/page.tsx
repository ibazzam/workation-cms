import RemoteStatus from '../../components/RemoteStatus'
import PaymentIntentForm from '../../components/PaymentIntentForm'
import BookingsManagementPanel from '../../components/BookingsManagementPanel'

export default function Bookings(){
  const apiBase = process.env.WORKATION_API_BASE_URL ?? 'http://localhost:3000/api/v1'

  return (
    <section>
      <h2 className="text-xl font-semibold mb-2">Bookings</h2>
      <p className="text-slate-700 mb-4">Manage booking lifecycle and change/rebook flows.</p>
      <BookingsManagementPanel apiBase={apiBase} />
      <PaymentIntentForm apiBase={apiBase} />
      <RemoteStatus apiBase={apiBase} />
    </section>
  )
}

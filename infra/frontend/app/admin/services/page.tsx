import AdminServicesConsole from '../../../components/AdminServicesConsole'

export default function AdminServicesPage() {
  const apiBase = process.env.WORKATION_API_BASE_URL ?? 'http://localhost:3000/api/v1'

  return (
    <section>
      <h2 className="mb-2 text-xl font-semibold">Admin Services Expansion</h2>
      <p className="mb-4 text-slate-700">
        Create, update, and delete vendors, accommodations, and transports for new countries/islands and service categories.
      </p>
      <AdminServicesConsole apiBase={apiBase} />
    </section>
  )
}

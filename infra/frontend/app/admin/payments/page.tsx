import AdminPaymentsConsole from '../../../components/AdminPaymentsConsole'

export default function AdminPaymentsPage() {
  const apiBase = process.env.WORKATION_API_BASE_URL ?? 'http://localhost:3000/api/v1'

  return (
    <section>
      <h2 className="mb-2 text-xl font-semibold">Payments Admin Console</h2>
      <p className="mb-4 text-slate-700">
        Manage background jobs and monitor operational alerts without direct API calls.
      </p>
      <AdminPaymentsConsole apiBase={apiBase} />
    </section>
  )
}

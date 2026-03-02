import AdminCommercialSettingsPanel from '../../../components/AdminCommercialSettingsPanel'

export default function AdminSettingsPage() {
  const apiBase = process.env.WORKATION_API_BASE_URL ?? 'http://localhost:3000/api/v1'

  return (
    <section>
      <h2 className="mb-2 text-xl font-semibold">Admin Commercial Settings</h2>
      <p className="mb-4 text-slate-700">
        Manage supported currencies, exchange rates, and loyalty program rules.
      </p>
      <AdminCommercialSettingsPanel apiBase={apiBase} />
    </section>
  )
}

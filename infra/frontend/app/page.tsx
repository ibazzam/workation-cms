export default function Home() {
  return (
    <section>
      <h2 className="text-xl font-semibold mb-2">Welcome to Workation</h2>
      <p className="text-slate-700">This is a minimal Next.js + TypeScript + Tailwind frontend scaffold.</p>
      <div className="mt-4 flex flex-wrap gap-3 text-sm">
        <a className="rounded border border-slate-300 px-3 py-2" href="/bookings">Bookings</a>
        <a className="rounded border border-slate-300 px-3 py-2" href="/admin/payments">Payments Admin Console</a>
        <a className="rounded border border-slate-300 px-3 py-2" href="/admin/settings">Admin Settings</a>
        <a className="rounded border border-slate-300 px-3 py-2" href="/admin/services">Admin Services CRUD</a>
      </div>
    </section>
  )
}

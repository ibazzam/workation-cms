import '../styles/globals.css'

export const metadata = {
  title: 'Workation',
  description: 'Workation platform'
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body className="min-h-screen bg-slate-50 text-slate-900">
        <div className="max-w-4xl mx-auto p-6">
          <header className="mb-8">
            <h1 className="text-2xl font-bold">Workation</h1>
          </header>
          <main>{children}</main>
        </div>
      </body>
    </html>
  )
}

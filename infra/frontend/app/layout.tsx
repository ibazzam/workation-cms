import '../styles/globals.css'
import Link from 'next/link'
import Providers from './providers'

export const metadata = {
  title: 'Workation',
  description: 'Workation platform'
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body className="min-h-screen bg-slate-50 text-slate-900">
        <Providers>
          <div className="max-w-4xl mx-auto p-6">
            <header className="mb-8 space-y-3">
              <h1 className="text-2xl font-bold">Workation</h1>
              <nav className="flex gap-3 text-sm text-slate-700">
                <Link href="/">Home</Link>
                <Link href="/discover">Discover</Link>
                <Link href="/checkout">Checkout</Link>
                <Link href="/bookings">Bookings</Link>
                <Link href="/accommodations">Accommodations</Link>
                <Link href="/islands">Islands</Link>
                <Link href="/vendors">Vendors</Link>
              </nav>
            </header>
            <main>{children}</main>
          </div>
        </Providers>
      </body>
    </html>
  )
}

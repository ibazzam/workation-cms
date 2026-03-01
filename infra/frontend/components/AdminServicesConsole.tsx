'use client'

import { FormEvent, useState } from 'react'
import { ApiError } from '../lib/api-client'
import { useAdminServicesMutations } from '../lib/hooks/use-admin-api-mutations'
import { usePersistedIdentity } from '../lib/hooks/use-persisted-identity'

type AdminRole = 'ADMIN' | 'ADMIN_SUPER' | 'ADMIN_FINANCE' | 'ADMIN_CARE'
type Entity = 'vendors' | 'accommodations' | 'transports' | 'countries' | 'service-categories'

const WRITE_ROLES: AdminRole[] = ['ADMIN', 'ADMIN_SUPER', 'ADMIN_CARE']

export default function AdminServicesConsole({ apiBase }: { apiBase: string }) {
  const {
    userId,
    setUserId,
    userEmail,
    setUserEmail,
    userRole,
    setUserRole,
    identity,
  } = usePersistedIdentity<AdminRole>({
    defaults: {
      userId: 'admin-care-1',
      userEmail: 'admin-care-1@example.test',
      userRole: 'ADMIN_CARE',
    },
    storageKeyPrefix: 'workation.admin',
  })

  const [vendors, setVendors] = useState<Array<Record<string, unknown>>>([])
  const [accommodations, setAccommodations] = useState<Array<Record<string, unknown>>>([])
  const [transports, setTransports] = useState<Array<Record<string, unknown>>>([])
  const [countries, setCountries] = useState<Array<Record<string, unknown>>>([])
  const [serviceCategories, setServiceCategories] = useState<Array<Record<string, unknown>>>([])

  const [loadingEntity, setLoadingEntity] = useState<Entity | null>(null)
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)

  const [vendorForm, setVendorForm] = useState({ id: '', name: '', email: '', phone: '' })
  const [accommodationForm, setAccommodationForm] = useState({ id: '', vendorId: '', islandId: '', title: '', slug: '', type: '', rooms: '', price: '', description: '' })
  const [transportForm, setTransportForm] = useState({ id: '', vendorId: '', type: '', code: '', fromIslandId: '', toIslandId: '', departure: '', arrival: '', capacity: '', price: '' })
  const [countryForm, setCountryForm] = useState({ id: '', code: '', name: '', active: true })
  const [serviceCategoryForm, setServiceCategoryForm] = useState({ id: '', code: '', name: '', scope: 'BOTH', active: true })

  const canWrite = WRITE_ROLES.includes(userRole)

  function getErrorMessage(error: unknown, fallback: string) {
    if (error instanceof ApiError) {
      return error.message
    }

    return fallback
  }

  const { loadEntityMutation, crudMutation } = useAdminServicesMutations<Entity>(apiBase, identity)

  async function loadEntity(entity: Entity) {
    setLoadingEntity(entity)
    setError(null)
    setMessage(null)

    try {
      const payload = await loadEntityMutation.mutateAsync(entity)
      if (!Array.isArray(payload)) {
        setError(`Unexpected ${entity} response format`)
        return
      }

      if (entity === 'vendors') setVendors(payload)
      if (entity === 'accommodations') setAccommodations(payload)
      if (entity === 'transports') setTransports(payload)
      if (entity === 'countries') setCountries(payload)
      if (entity === 'service-categories') setServiceCategories(payload)
    } catch (error) {
      setError(getErrorMessage(error, `Unable to reach backend while loading ${entity}`))
    } finally {
      setLoadingEntity(null)
    }
  }

  async function submitCrud(entity: Entity, method: 'POST' | 'PUT' | 'DELETE', id: string | undefined, body?: Record<string, unknown>) {
    setError(null)
    setMessage(null)

    if (!canWrite) {
      setError('Current role is read-only for service CRUD actions.')
      return
    }

    try {
      await crudMutation.mutateAsync({
        entity,
        method,
        id,
        body,
      })

      setMessage(`${entity} ${method} succeeded`)
      await loadEntity(entity)
    } catch (error) {
      setError(getErrorMessage(error, `Unable to ${method} ${entity}`))
    }
  }

  async function onVendorCreate(event: FormEvent) {
    event.preventDefault()
    await submitCrud('vendors', 'POST', undefined, {
      name: vendorForm.name,
      email: vendorForm.email || null,
      phone: vendorForm.phone || null,
    })
  }

  async function onVendorUpdate(event: FormEvent) {
    event.preventDefault()
    if (!vendorForm.id) return
    await submitCrud('vendors', 'PUT', vendorForm.id, {
      name: vendorForm.name || undefined,
      email: vendorForm.email || null,
      phone: vendorForm.phone || null,
    })
  }

  async function onVendorDelete() {
    if (!vendorForm.id) return
    await submitCrud('vendors', 'DELETE', vendorForm.id)
  }

  async function onAccommodationCreate(event: FormEvent) {
    event.preventDefault()
    await submitCrud('accommodations', 'POST', undefined, {
      vendorId: accommodationForm.vendorId,
      islandId: Number(accommodationForm.islandId),
      title: accommodationForm.title,
      slug: accommodationForm.slug || undefined,
      type: accommodationForm.type || null,
      rooms: accommodationForm.rooms ? Number(accommodationForm.rooms) : null,
      price: Number(accommodationForm.price),
      description: accommodationForm.description || null,
    })
  }

  async function onAccommodationUpdate(event: FormEvent) {
    event.preventDefault()
    if (!accommodationForm.id) return
    await submitCrud('accommodations', 'PUT', accommodationForm.id, {
      vendorId: accommodationForm.vendorId || undefined,
      islandId: accommodationForm.islandId ? Number(accommodationForm.islandId) : undefined,
      title: accommodationForm.title || undefined,
      slug: accommodationForm.slug || undefined,
      type: accommodationForm.type || undefined,
      rooms: accommodationForm.rooms ? Number(accommodationForm.rooms) : undefined,
      price: accommodationForm.price ? Number(accommodationForm.price) : undefined,
      description: accommodationForm.description || undefined,
    })
  }

  async function onAccommodationDelete() {
    if (!accommodationForm.id) return
    await submitCrud('accommodations', 'DELETE', accommodationForm.id)
  }

  async function onTransportCreate(event: FormEvent) {
    event.preventDefault()
    await submitCrud('transports', 'POST', undefined, {
      vendorId: transportForm.vendorId || undefined,
      type: transportForm.type,
      code: transportForm.code || null,
      fromIslandId: transportForm.fromIslandId ? Number(transportForm.fromIslandId) : null,
      toIslandId: transportForm.toIslandId ? Number(transportForm.toIslandId) : null,
      departure: transportForm.departure || null,
      arrival: transportForm.arrival || null,
      capacity: transportForm.capacity ? Number(transportForm.capacity) : null,
      price: Number(transportForm.price),
    })
  }

  async function onTransportUpdate(event: FormEvent) {
    event.preventDefault()
    if (!transportForm.id) return
    await submitCrud('transports', 'PUT', transportForm.id, {
      vendorId: transportForm.vendorId || undefined,
      type: transportForm.type || undefined,
      code: transportForm.code || undefined,
      fromIslandId: transportForm.fromIslandId ? Number(transportForm.fromIslandId) : undefined,
      toIslandId: transportForm.toIslandId ? Number(transportForm.toIslandId) : undefined,
      departure: transportForm.departure || undefined,
      arrival: transportForm.arrival || undefined,
      capacity: transportForm.capacity ? Number(transportForm.capacity) : undefined,
      price: transportForm.price ? Number(transportForm.price) : undefined,
    })
  }

  async function onTransportDelete() {
    if (!transportForm.id) return
    await submitCrud('transports', 'DELETE', transportForm.id)
  }

  async function onCountryCreate(event: FormEvent) {
    event.preventDefault()
    await submitCrud('countries', 'POST', undefined, {
      code: countryForm.code,
      name: countryForm.name,
      active: countryForm.active,
    })
  }

  async function onCountryUpdate(event: FormEvent) {
    event.preventDefault()
    if (!countryForm.id) return
    await submitCrud('countries', 'PUT', countryForm.id, {
      code: countryForm.code || undefined,
      name: countryForm.name || undefined,
      active: countryForm.active,
    })
  }

  async function onCountryDelete() {
    if (!countryForm.id) return
    await submitCrud('countries', 'DELETE', countryForm.id)
  }

  async function onServiceCategoryCreate(event: FormEvent) {
    event.preventDefault()
    await submitCrud('service-categories', 'POST', undefined, {
      code: serviceCategoryForm.code,
      name: serviceCategoryForm.name,
      scope: serviceCategoryForm.scope,
      active: serviceCategoryForm.active,
    })
  }

  async function onServiceCategoryUpdate(event: FormEvent) {
    event.preventDefault()
    if (!serviceCategoryForm.id) return
    await submitCrud('service-categories', 'PUT', serviceCategoryForm.id, {
      code: serviceCategoryForm.code || undefined,
      name: serviceCategoryForm.name || undefined,
      scope: serviceCategoryForm.scope || undefined,
      active: serviceCategoryForm.active,
    })
  }

  async function onServiceCategoryDelete() {
    if (!serviceCategoryForm.id) return
    await submitCrud('service-categories', 'DELETE', serviceCategoryForm.id)
  }

  return (
    <div className="space-y-6">
      <section className="rounded border border-slate-200 bg-white p-4">
        <h3 className="mb-3 text-base font-semibold">Admin identity</h3>
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" value={userId} onChange={(e) => setUserId(e.target.value)} placeholder="User ID" />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" value={userEmail} onChange={(e) => setUserEmail(e.target.value)} placeholder="User Email" />
        </div>
        <select className="mt-3 w-full rounded border border-slate-300 px-3 py-2 text-sm sm:w-80" value={userRole} onChange={(e) => setUserRole(e.target.value as AdminRole)}>
          <option value="ADMIN">ADMIN</option>
          <option value="ADMIN_SUPER">ADMIN_SUPER</option>
          <option value="ADMIN_CARE">ADMIN_CARE</option>
          <option value="ADMIN_FINANCE">ADMIN_FINANCE</option>
        </select>
      </section>

      <section className="rounded border border-slate-200 bg-white p-4">
        <div className="mb-3 flex flex-wrap gap-2">
          <button className="rounded border border-slate-300 px-3 py-2 text-sm" onClick={() => loadEntity('vendors')} disabled={loadingEntity === 'vendors'}>Load vendors</button>
          <button className="rounded border border-slate-300 px-3 py-2 text-sm" onClick={() => loadEntity('accommodations')} disabled={loadingEntity === 'accommodations'}>Load accommodations</button>
          <button className="rounded border border-slate-300 px-3 py-2 text-sm" onClick={() => loadEntity('transports')} disabled={loadingEntity === 'transports'}>Load transports</button>
          <button className="rounded border border-slate-300 px-3 py-2 text-sm" onClick={() => loadEntity('countries')} disabled={loadingEntity === 'countries'}>Load countries</button>
          <button className="rounded border border-slate-300 px-3 py-2 text-sm" onClick={() => loadEntity('service-categories')} disabled={loadingEntity === 'service-categories'}>Load service categories</button>
        </div>
        {error ? <p className="text-sm text-rose-700">{error}</p> : null}
        {message ? <p className="text-sm text-emerald-700">{message}</p> : null}
      </section>

      <section className="rounded border border-slate-200 bg-white p-4">
        <h3 className="mb-3 text-base font-semibold">Vendors CRUD</h3>
        <form onSubmit={onVendorCreate} className="grid grid-cols-1 gap-2 sm:grid-cols-4">
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Vendor ID (for update/delete)" value={vendorForm.id} onChange={(e) => setVendorForm((v) => ({ ...v, id: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Name" value={vendorForm.name} onChange={(e) => setVendorForm((v) => ({ ...v, name: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Email" value={vendorForm.email} onChange={(e) => setVendorForm((v) => ({ ...v, email: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Phone" value={vendorForm.phone} onChange={(e) => setVendorForm((v) => ({ ...v, phone: e.target.value }))} />
          <button type="submit" className="rounded bg-slate-900 px-3 py-2 text-sm text-white" disabled={!canWrite}>Create</button>
          <button type="button" className="rounded border border-slate-300 px-3 py-2 text-sm" disabled={!canWrite || !vendorForm.id} onClick={onVendorUpdate}>Update by ID</button>
          <button type="button" className="rounded border border-slate-300 px-3 py-2 text-sm" disabled={!canWrite || !vendorForm.id} onClick={onVendorDelete}>Delete by ID</button>
        </form>
        <p className="mt-2 text-sm text-slate-600">Loaded: {vendors.length}</p>
      </section>

      <section className="rounded border border-slate-200 bg-white p-4">
        <h3 className="mb-3 text-base font-semibold">Accommodations CRUD</h3>
        <form onSubmit={onAccommodationCreate} className="grid grid-cols-1 gap-2 sm:grid-cols-3">
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Accommodation ID (for update/delete)" value={accommodationForm.id} onChange={(e) => setAccommodationForm((v) => ({ ...v, id: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Vendor ID" value={accommodationForm.vendorId} onChange={(e) => setAccommodationForm((v) => ({ ...v, vendorId: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Island ID" value={accommodationForm.islandId} onChange={(e) => setAccommodationForm((v) => ({ ...v, islandId: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Title" value={accommodationForm.title} onChange={(e) => setAccommodationForm((v) => ({ ...v, title: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Slug (optional)" value={accommodationForm.slug} onChange={(e) => setAccommodationForm((v) => ({ ...v, slug: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Type (e.g. RESTAURANT)" value={accommodationForm.type} onChange={(e) => setAccommodationForm((v) => ({ ...v, type: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Rooms" value={accommodationForm.rooms} onChange={(e) => setAccommodationForm((v) => ({ ...v, rooms: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Price" value={accommodationForm.price} onChange={(e) => setAccommodationForm((v) => ({ ...v, price: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Description" value={accommodationForm.description} onChange={(e) => setAccommodationForm((v) => ({ ...v, description: e.target.value }))} />
          <button type="submit" className="rounded bg-slate-900 px-3 py-2 text-sm text-white" disabled={!canWrite}>Create</button>
          <button type="button" className="rounded border border-slate-300 px-3 py-2 text-sm" disabled={!canWrite || !accommodationForm.id} onClick={onAccommodationUpdate}>Update by ID</button>
          <button type="button" className="rounded border border-slate-300 px-3 py-2 text-sm" disabled={!canWrite || !accommodationForm.id} onClick={onAccommodationDelete}>Delete by ID</button>
        </form>
        <p className="mt-2 text-sm text-slate-600">Loaded: {accommodations.length}</p>
      </section>

      <section className="rounded border border-slate-200 bg-white p-4">
        <h3 className="mb-3 text-base font-semibold">Transports CRUD</h3>
        <form onSubmit={onTransportCreate} className="grid grid-cols-1 gap-2 sm:grid-cols-3">
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Transport ID (for update/delete)" value={transportForm.id} onChange={(e) => setTransportForm((v) => ({ ...v, id: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Vendor ID (optional)" value={transportForm.vendorId} onChange={(e) => setTransportForm((v) => ({ ...v, vendorId: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Type" value={transportForm.type} onChange={(e) => setTransportForm((v) => ({ ...v, type: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Code" value={transportForm.code} onChange={(e) => setTransportForm((v) => ({ ...v, code: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="From Island ID" value={transportForm.fromIslandId} onChange={(e) => setTransportForm((v) => ({ ...v, fromIslandId: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="To Island ID" value={transportForm.toIslandId} onChange={(e) => setTransportForm((v) => ({ ...v, toIslandId: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Departure ISO datetime" value={transportForm.departure} onChange={(e) => setTransportForm((v) => ({ ...v, departure: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Arrival ISO datetime" value={transportForm.arrival} onChange={(e) => setTransportForm((v) => ({ ...v, arrival: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Capacity" value={transportForm.capacity} onChange={(e) => setTransportForm((v) => ({ ...v, capacity: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Price" value={transportForm.price} onChange={(e) => setTransportForm((v) => ({ ...v, price: e.target.value }))} />
          <button type="submit" className="rounded bg-slate-900 px-3 py-2 text-sm text-white" disabled={!canWrite}>Create</button>
          <button type="button" className="rounded border border-slate-300 px-3 py-2 text-sm" disabled={!canWrite || !transportForm.id} onClick={onTransportUpdate}>Update by ID</button>
          <button type="button" className="rounded border border-slate-300 px-3 py-2 text-sm" disabled={!canWrite || !transportForm.id} onClick={onTransportDelete}>Delete by ID</button>
        </form>
        <p className="mt-2 text-sm text-slate-600">Loaded: {transports.length}</p>
      </section>

      <section className="rounded border border-slate-200 bg-white p-4">
        <h3 className="mb-3 text-base font-semibold">Countries CRUD</h3>
        <form onSubmit={onCountryCreate} className="grid grid-cols-1 gap-2 sm:grid-cols-4">
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Country ID (for update/delete)" value={countryForm.id} onChange={(e) => setCountryForm((v) => ({ ...v, id: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Code (e.g. MV, LK)" value={countryForm.code} onChange={(e) => setCountryForm((v) => ({ ...v, code: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Name" value={countryForm.name} onChange={(e) => setCountryForm((v) => ({ ...v, name: e.target.value }))} />
          <label className="flex items-center gap-2 rounded border border-slate-300 px-3 py-2 text-sm">
            <input type="checkbox" checked={countryForm.active} onChange={(e) => setCountryForm((v) => ({ ...v, active: e.target.checked }))} /> Active
          </label>
          <button type="submit" className="rounded bg-slate-900 px-3 py-2 text-sm text-white" disabled={!canWrite}>Create</button>
          <button type="button" className="rounded border border-slate-300 px-3 py-2 text-sm" disabled={!canWrite || !countryForm.id} onClick={onCountryUpdate}>Update by ID</button>
          <button type="button" className="rounded border border-slate-300 px-3 py-2 text-sm" disabled={!canWrite || !countryForm.id} onClick={onCountryDelete}>Delete by ID</button>
        </form>
        <p className="mt-2 text-sm text-slate-600">Loaded: {countries.length}</p>
      </section>

      <section className="rounded border border-slate-200 bg-white p-4">
        <h3 className="mb-3 text-base font-semibold">Service Categories CRUD</h3>
        <form onSubmit={onServiceCategoryCreate} className="grid grid-cols-1 gap-2 sm:grid-cols-4">
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Category ID (for update/delete)" value={serviceCategoryForm.id} onChange={(e) => setServiceCategoryForm((v) => ({ ...v, id: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Code (e.g. WATER_SPORTS)" value={serviceCategoryForm.code} onChange={(e) => setServiceCategoryForm((v) => ({ ...v, code: e.target.value }))} />
          <input className="rounded border border-slate-300 px-3 py-2 text-sm" placeholder="Name" value={serviceCategoryForm.name} onChange={(e) => setServiceCategoryForm((v) => ({ ...v, name: e.target.value }))} />
          <select className="rounded border border-slate-300 px-3 py-2 text-sm" value={serviceCategoryForm.scope} onChange={(e) => setServiceCategoryForm((v) => ({ ...v, scope: e.target.value }))}>
            <option value="ACCOMMODATION">ACCOMMODATION</option>
            <option value="TRANSPORT">TRANSPORT</option>
            <option value="BOTH">BOTH</option>
          </select>
          <label className="flex items-center gap-2 rounded border border-slate-300 px-3 py-2 text-sm">
            <input type="checkbox" checked={serviceCategoryForm.active} onChange={(e) => setServiceCategoryForm((v) => ({ ...v, active: e.target.checked }))} /> Active
          </label>
          <button type="submit" className="rounded bg-slate-900 px-3 py-2 text-sm text-white" disabled={!canWrite}>Create</button>
          <button type="button" className="rounded border border-slate-300 px-3 py-2 text-sm" disabled={!canWrite || !serviceCategoryForm.id} onClick={onServiceCategoryUpdate}>Update by ID</button>
          <button type="button" className="rounded border border-slate-300 px-3 py-2 text-sm" disabled={!canWrite || !serviceCategoryForm.id} onClick={onServiceCategoryDelete}>Delete by ID</button>
        </form>
        <p className="mt-2 text-sm text-slate-600">Loaded: {serviceCategories.length}</p>
      </section>
    </div>
  )
}

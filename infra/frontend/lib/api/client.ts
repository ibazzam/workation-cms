import { Accommodation, Booking, Excursion, HealthStatus, Island, Transport, Vendor } from './types';

const API_BASE_URL = (process.env.NEXT_PUBLIC_API_BASE_URL ?? 'https://api.workation.mv').replace(/\/$/, '');

async function fetchJsonWithFallback<T>(paths: string[]): Promise<T> {
  let lastError: Error | undefined;

  for (const path of paths) {
    const url = `${API_BASE_URL}${path}`;

    try {
      const response = await fetch(url, {
        headers: { Accept: 'application/json' },
        next: { revalidate: 0 },
      });

      if (!response.ok) {
        if (response.status === 404) {
          continue;
        }

        throw new Error(`Request failed (${response.status}) for ${path}`);
      }

      return (await response.json()) as T;
    } catch (error) {
      lastError = error instanceof Error ? error : new Error('Unknown API request error');
    }
  }

  throw lastError ?? new Error('No API endpoint resolved successfully');
}

function pickCollection(payload: unknown): unknown[] {
  if (Array.isArray(payload)) {
    return payload;
  }

  if (!payload || typeof payload !== 'object') {
    return [];
  }

  const candidate = payload as { data?: unknown; items?: unknown };
  if (Array.isArray(candidate.data)) {
    return candidate.data;
  }

  if (Array.isArray(candidate.items)) {
    return candidate.items;
  }

  return [];
}

function toStringValue(input: unknown, fallback = ''): string {
  if (typeof input === 'string') {
    return input;
  }

  if (typeof input === 'number') {
    return String(input);
  }

  return fallback;
}

function toNumberValue(input: unknown, fallback = 0): number {
  if (typeof input === 'number' && Number.isFinite(input)) {
    return input;
  }

  if (typeof input === 'string' && input.trim().length > 0) {
    const parsed = Number(input);
    if (Number.isFinite(parsed)) {
      return parsed;
    }
  }

  return fallback;
}

export async function fetchHealthStatus(): Promise<HealthStatus> {
  const payload = await fetchJsonWithFallback<{ status?: unknown; timestamp?: unknown } | { data?: { status?: unknown; timestamp?: unknown } }>([
    '/api/v1/health',
  ]);

  const sourceCandidate = 'data' in payload && payload.data ? payload.data : payload;
  const source = (sourceCandidate && typeof sourceCandidate === 'object'
    ? sourceCandidate
    : {}) as Record<string, unknown>;

  return {
    status: toStringValue(source.status, 'unknown'),
    timestamp: toStringValue(source.timestamp) || undefined,
  };
}

export async function fetchAccommodations(): Promise<Accommodation[]> {
  const payload = await fetchJsonWithFallback<unknown>(['/api/v1/accommodations']);

  return pickCollection(payload)
    .map((entry) => {
      const item = (entry ?? {}) as Record<string, unknown>;
      return {
        id: toStringValue(item.id),
        name: toStringValue(item.name, 'Untitled accommodation'),
        islandName: toStringValue(item.islandName ?? item.island_name) || undefined,
      };
    })
    .filter((item) => item.id.length > 0);
}

export async function fetchIslands(): Promise<Island[]> {
  const payload = await fetchJsonWithFallback<unknown>(['/api/v1/islands']);

  return pickCollection(payload)
    .map((entry) => {
      const item = (entry ?? {}) as Record<string, unknown>;
      return {
        id: toStringValue(item.id),
        name: toStringValue(item.name, 'Untitled island'),
        atollName: toStringValue(item.atollName ?? item.atoll_name) || undefined,
      };
    })
    .filter((item) => item.id.length > 0);
}

export async function fetchVendors(): Promise<Vendor[]> {
  const payload = await fetchJsonWithFallback<unknown>(['/api/v1/vendors']);

  return pickCollection(payload)
    .map((entry) => {
      const item = (entry ?? {}) as Record<string, unknown>;
      return {
        id: toStringValue(item.id),
        name: toStringValue(item.name, 'Untitled vendor'),
      };
    })
    .filter((item) => item.id.length > 0);
}

export async function fetchBookings(): Promise<Booking[]> {
  const payload = await fetchJsonWithFallback<unknown>(['/api/v1/bookings']);

  return pickCollection(payload)
    .map((entry) => {
      const item = (entry ?? {}) as Record<string, unknown>;
      return {
        id: toStringValue(item.id),
        status: toStringValue(item.status, 'unknown'),
        serviceType: toStringValue(item.serviceType ?? item.service_type) || undefined,
        createdAt: toStringValue(item.createdAt ?? item.created_at) || undefined,
      };
    })
    .filter((item) => item.id.length > 0);
}

export async function fetchTransports(): Promise<Transport[]> {
  const payload = await fetchJsonWithFallback<unknown>(['/api/v1/transports']);

  return pickCollection(payload)
    .map((entry) => {
      const item = (entry ?? {}) as Record<string, unknown>;
      return {
        id: toStringValue(item.id),
        name: toStringValue(item.name ?? item.code, 'Untitled transport'),
        fromIslandName: toStringValue(item.fromIslandName ?? item.from_island_name) || undefined,
        toIslandName: toStringValue(item.toIslandName ?? item.to_island_name) || undefined,
        price: toNumberValue(item.price, 0),
        currency: toStringValue(item.currency, 'USD'),
      };
    })
    .filter((item) => item.id.length > 0);
}

export async function fetchExcursions(): Promise<Excursion[]> {
  const payload = await fetchJsonWithFallback<unknown>(['/api/v1/excursions']);

  return pickCollection(payload)
    .map((entry) => {
      const item = (entry ?? {}) as Record<string, unknown>;
      return {
        id: toStringValue(item.id),
        title: toStringValue(item.title, 'Untitled activity'),
        islandName: toStringValue(item.islandName ?? item.island_name) || undefined,
        price: toNumberValue(item.price, 0),
        currency: toStringValue(item.currency, 'USD'),
      };
    })
    .filter((item) => item.id.length > 0);
}

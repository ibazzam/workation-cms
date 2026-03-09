import { Accommodation, Booking, HealthStatus, Island, Vendor } from './types';

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

export async function fetchHealthStatus(): Promise<HealthStatus> {
  const payload = await fetchJsonWithFallback<{ status?: unknown; timestamp?: unknown } | { data?: { status?: unknown; timestamp?: unknown } }>([
    '/api/v1/health',
    '/health',
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
  const payload = await fetchJsonWithFallback<unknown>(['/api/v1/accommodations', '/api/accommodations']);

  return pickCollection(payload)
    .map((entry) => {
      const item = (entry ?? {}) as Record<string, unknown>;
      return {
        id: toStringValue(item.id),
        name: toStringValue(item.name, 'Untitled accommodation'),
        islandName: toStringValue(item.islandName ?? item.island_name) || undefined,
      };
    })
    .filter((item): item is Accommodation => item.id.length > 0);
}

export async function fetchIslands(): Promise<Island[]> {
  const payload = await fetchJsonWithFallback<unknown>(['/api/v1/islands', '/api/islands']);

  return pickCollection(payload)
    .map((entry) => {
      const item = (entry ?? {}) as Record<string, unknown>;
      return {
        id: toStringValue(item.id),
        name: toStringValue(item.name, 'Untitled island'),
        atollName: toStringValue(item.atollName ?? item.atoll_name) || undefined,
      };
    })
    .filter((item): item is Island => item.id.length > 0);
}

export async function fetchVendors(): Promise<Vendor[]> {
  const payload = await fetchJsonWithFallback<unknown>(['/api/v1/vendors', '/api/vendors']);

  return pickCollection(payload)
    .map((entry) => {
      const item = (entry ?? {}) as Record<string, unknown>;
      return {
        id: toStringValue(item.id),
        name: toStringValue(item.name, 'Untitled vendor'),
      };
    })
    .filter((item): item is Vendor => item.id.length > 0);
}

export async function fetchBookings(): Promise<Booking[]> {
  const payload = await fetchJsonWithFallback<unknown>(['/api/v1/bookings', '/api/bookings']);

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
    .filter((item): item is Booking => item.id.length > 0);
}

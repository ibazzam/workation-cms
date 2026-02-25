export type AdminRole = 'ADMIN' | 'ADMIN_SUPER' | 'ADMIN_FINANCE' | 'ADMIN_CARE'

export type AuthIdentity = {
  userId: string
  userRole: AdminRole | string
  userEmail: string
}

export class ApiError extends Error {
  status: number
  payload: unknown

  constructor(message: string, status: number, payload: unknown) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.payload = payload
  }
}

export function normalizeApiBase(apiBase: string) {
  return apiBase.replace(/\/+$/, '')
}

export function buildAuthHeaders(identity: AuthIdentity, includeContentType = true): HeadersInit {
  return {
    ...(includeContentType ? { 'Content-Type': 'application/json' } : {}),
    'x-user-id': identity.userId,
    'x-user-role': identity.userRole,
    'x-user-email': identity.userEmail,
  }
}

export async function apiRequest<T>(
  url: string,
  init: RequestInit = {},
): Promise<T> {
  const response = await fetch(url, {
    cache: 'no-store',
    ...init,
  })

  const isJson = response.headers.get('content-type')?.includes('application/json')
  const payload = isJson ? await response.json() : null

  if (!response.ok) {
    const message =
      payload && typeof payload === 'object' && 'message' in payload && payload.message
        ? String(payload.message)
        : `Request failed (${response.status})`

    throw new ApiError(message, response.status, payload)
  }

  return payload as T
}

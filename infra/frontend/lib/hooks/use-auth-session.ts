'use client'

import { useQuery } from '@tanstack/react-query'

export type SessionUser = {
  id: string
  email: string
  role: 'USER' | 'VENDOR' | 'ADMIN'
}

type AuthSessionResponse = {
  user?: {
    id?: string
    sub?: string
    email?: string
    role?: 'USER' | 'VENDOR' | 'ADMIN'
  }
}

export function useAuthSession() {
  return useQuery<SessionUser | null>({
    queryKey: ['auth-session'],
    queryFn: async () => {
      const response = await fetch('/api/auth/session', { cache: 'no-store' })
      if (!response.ok) {
        return null
      }

      const session = (await response.json()) as AuthSessionResponse
      const id = session.user?.id ?? session.user?.sub
      const email = session.user?.email
      const role = session.user?.role ?? 'USER'

      if (!id || !email) {
        return null
      }

      return {
        id,
        email,
        role,
      }
    },
    retry: false,
  })
}

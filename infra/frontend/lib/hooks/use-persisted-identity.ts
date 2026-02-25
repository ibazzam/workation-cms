'use client'

import { useEffect, useState } from 'react'

type SessionIdentity<TRole extends string> = {
  id: string
  email: string
  role: TRole
}

export function usePersistedIdentity<TRole extends string>(options: {
  defaults: {
    userId: string
    userEmail: string
    userRole: TRole
  }
  storageKeyPrefix?: string
  sessionUser?: SessionIdentity<TRole> | null
  preferSession?: boolean
}) {
  const { defaults, storageKeyPrefix = 'workation', sessionUser, preferSession = false } = options

  const [userId, setUserId] = useState(defaults.userId)
  const [userEmail, setUserEmail] = useState(defaults.userEmail)
  const [userRole, setUserRole] = useState<TRole>(defaults.userRole)

  useEffect(() => {
    const storedUserId = window.localStorage.getItem(`${storageKeyPrefix}.userId`)
    const storedUserEmail = window.localStorage.getItem(`${storageKeyPrefix}.userEmail`)
    const storedUserRole = window.localStorage.getItem(`${storageKeyPrefix}.userRole`)

    if (storedUserId) {
      setUserId(storedUserId)
    }

    if (storedUserEmail) {
      setUserEmail(storedUserEmail)
    }

    if (storedUserRole) {
      setUserRole(storedUserRole as TRole)
    }
  }, [storageKeyPrefix])

  useEffect(() => {
    if (!preferSession || !sessionUser) {
      return
    }

    setUserId(sessionUser.id)
    setUserEmail(sessionUser.email)
    setUserRole(sessionUser.role)
  }, [preferSession, sessionUser])

  useEffect(() => {
    if (!userId || !userEmail) {
      return
    }

    window.localStorage.setItem(`${storageKeyPrefix}.userId`, userId)
    window.localStorage.setItem(`${storageKeyPrefix}.userEmail`, userEmail)
    window.localStorage.setItem(`${storageKeyPrefix}.userRole`, userRole)
  }, [storageKeyPrefix, userEmail, userId, userRole])

  return {
    userId,
    setUserId,
    userEmail,
    setUserEmail,
    userRole,
    setUserRole,
    identity: {
      userId,
      userEmail,
      userRole,
    },
  }
}

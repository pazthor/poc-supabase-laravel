'use client'

import { useAuth } from '@/contexts/AuthContext'

export function useUser() {
  const { user, profile, loading } = useAuth()

  return {
    user,
    profile,
    loading,
    isManager: profile?.role === 'manager',
    isAdmin: profile?.role === 'admin',
    isEmployee: profile?.role === 'employee',
  }
}

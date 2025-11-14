// Auth types

export type UserRole = 'manager' | 'employee' | 'admin'

export type AuthUser = {
  id: string
  email: string
  full_name: string | null
  role: UserRole
  avatar_url: string | null
}

export type LoginCredentials = {
  email: string
  password: string
}

export type RegisterData = {
  email: string
  password: string
  full_name: string
  role: UserRole
}

export type AuthResponse = {
  success: boolean
  data?: {
    access_token: string
    refresh_token: string
    user: AuthUser
  }
  error?: string
}

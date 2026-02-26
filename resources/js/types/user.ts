/**
 * User Management Types
 */

export type UserRole = 'admin' | 'viewer'

/**
 * Permission Types
 */
export interface Permission {
  id: number
  name: string
  display_name: string
  group: string
  description?: string
}

export interface GroupedPermissions {
  [group: string]: Permission[]
}

/**
 * Tenant Role Types
 */
export interface TenantRole {
  id: number
  tenant_id: number
  name: string
  display_name: string
  description: string | null
  is_system: boolean
  permissions: Permission[]
  users_count: number
  tenant?: { id: number; name: string }
  created_at: string | null
  updated_at: string | null
}

export interface CreateRoleForm {
  name: string
  display_name: string
  description?: string
  permissions: number[]
}

export interface UpdateRoleForm {
  name?: string
  display_name?: string
  description?: string
  permissions: number[]
}

export interface TenantUser {
  id: number
  name: string
  email: string
  avatar_url: string | null
  role: UserRole
  tenant_role_id?: number | null
  tenant_role?: TenantRole | null
  joined_at: string | null
  invited_at: string | null
  last_activity_at: string | null
  last_login_at: string | null
  is_current_user: boolean
}

export interface UserTenant {
  id: number
  name: string
  slug?: string
  role: UserRole
  joined_at?: string | null
  invited_at?: string | null
}

export interface AdminUser {
  id: number
  name: string
  email: string
  avatar_url: string | null
  is_super_admin: boolean
  tenants_count: number
  tenants: UserTenant[]
  last_login_at: string | null
  created_at: string
}

export interface AdminUserDetail {
  id: number
  name: string
  email: string
  avatar_url: string | null
  is_super_admin: boolean
  default_tenant_id: number | null
  tenants: UserTenant[]
  last_login_at: string | null
  created_at: string
  email_verified_at: string | null
}

export interface UserInvitation {
  id: number
  email: string
  role: UserRole
  invited_by: string | null
  expires_at: string
  created_at: string
}

export interface InvitationVerification {
  valid: boolean
  expired: boolean
  accepted: boolean
  email: string
  role: UserRole
  tenant: {
    id: number
    name: string
  }
  inviter: {
    name: string
  }
  existing_user: boolean
  expires_at: string
}

export interface InviteUserForm {
  email: string
  role: UserRole
  tenant_role_id?: number | null
}

export interface CreateUserForm {
  name: string
  email: string
  password: string
  tenant_id?: number | null
  role?: UserRole
}

export interface UpdateUserForm {
  name?: string
  email?: string
  password?: string
  default_tenant_id?: number | null
}

export interface AcceptInvitationForm {
  name?: string
  password?: string
  password_confirmation?: string
}

export interface AddToTenantForm {
  tenant_id: number
  role: UserRole
}

export interface UserFilters {
  search?: string
  role?: UserRole | ''
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  page?: number
  per_page?: number
}

export interface AdminUserFilters extends UserFilters {
  tenant_id?: number | null
}

export interface PaginatedUsers<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

export interface TenantOption {
  id: number
  name: string
  slug: string
  status: string
}

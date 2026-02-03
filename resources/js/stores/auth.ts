import axios from 'axios'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export interface User {
  id: number
  name: string
  email: string
  default_tenant_id: number
}

export interface Tenant {
  id: number
  name: string
  slug: string
  role?: 'admin' | 'viewer'
  ad_accounts_count?: number
  logo_url?: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const tenants = ref<Tenant[]>([])
  const currentTenant = ref<Tenant | null>(null)
  const loading = ref(false)
  const isSuperAdmin = ref(false)

  // جديد
  const initialized = ref(false)
  let hydrating: Promise<void> | null = null

  const isAuthenticated = computed(() => !!user.value)
  const isAdmin = computed(() => isSuperAdmin.value || currentTenant.value?.role === 'admin')

  async function login(email: string, password: string) {
    loading.value = true
    try {
      const response = await axios.post('/api/auth/login', { email, password })
      const { user: userData, token, tenants: userTenants, is_super_admin } = response.data

      console.log('auth store - storing token:', `${token.substring(0, 20)}... (length: ${token.length})`)

      // Store token
      localStorage.setItem('auth_token', token)
      sessionStorage.setItem('auth_token', token)

      // 24h expiration
      const expirationTime = Date.now() + (24 * 60 * 60 * 1000)
      localStorage.setItem('auth_token_expires', expirationTime.toString())
      sessionStorage.setItem('auth_token_expires', expirationTime.toString())

      // اضبط هيدر axios فورًا (مش window.axios)
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
      axios.defaults.headers.common['Accept'] = 'application/json'

      // Update state
      user.value = userData
      tenants.value = userTenants
      isSuperAdmin.value = is_super_admin

      // Super admins default to "All Tenants" (no tenant selected)
      if (is_super_admin) {
        currentTenant.value = null
        localStorage.removeItem('current_tenant_id')
        sessionStorage.removeItem('current_tenant_id')
        console.log('auth store - super admin detected, showing all tenants')
      } else {
        // Regular users get their default tenant
        const defaultTenant =
          userTenants.find((t: Tenant) => t.id === userData.default_tenant_id) || userTenants[0]
        if (defaultTenant) {
          setCurrentTenant(defaultTenant)
          console.log('auth store - set current tenant:', defaultTenant.name, 'ID:', defaultTenant.id)
        } else {
          console.log('auth store - no default tenant found')
        }
      }

      return response.data
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await axios.post('/api/auth/logout')
    } catch (error) {
      // Ignore logout errors
    } finally {
      // Clear storages
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_token_expires')
      localStorage.removeItem('current_tenant_id')
      sessionStorage.removeItem('auth_token')
      sessionStorage.removeItem('auth_token_expires')
      sessionStorage.removeItem('current_tenant_id')

      // امسح هيدر الأوث
      delete axios.defaults.headers.common['Authorization']

      user.value = null
      tenants.value = []
      currentTenant.value = null
    }
  }

  async function fetchTenants() {
    console.log('auth store - fetchTenants called')
    try {
      const response = await axios.get('/api/tenants')
      const { data: tenantsData, is_super_admin } = response.data

      console.log(
        'auth store - fetchTenants response received, tenants:',
        tenantsData.length,
        'isSuperAdmin:',
        is_super_admin
      )

      tenants.value = tenantsData
      isSuperAdmin.value = is_super_admin

      return response.data
    } catch (error) {
      console.error('Failed to fetch tenants:', error)
      throw error
    }
  }

  async function fetchUser() {
    console.log('auth store - fetchUser called')
    const response = await axios.get('/api/me')
    const { user: userData, tenants: userTenants, current_tenant, is_super_admin } = response.data

    console.log(
      'auth store - fetchUser response received, user:',
      userData.name,
      'tenants:',
      userTenants.length,
      'isSuperAdmin:',
      is_super_admin
    )

    user.value = userData
    isSuperAdmin.value = is_super_admin

    // Fetch tenants from /api/tenants for full information
    try {
      await fetchTenants()
    } catch (error) {
      // Fallback to tenants from /api/me if /api/tenants fails
      console.warn('Failed to fetch tenants from /api/tenants, using tenants from /api/me')
      tenants.value = userTenants
    }

    // Super admins default to "All Tenants" (no tenant selected)
    if (isSuperAdmin.value) {
      currentTenant.value = null
      localStorage.removeItem('current_tenant_id')
      sessionStorage.removeItem('current_tenant_id')
      console.log('auth store - super admin detected, showing all tenants')
    } else if (current_tenant) {
      currentTenant.value = current_tenant
      console.log('auth store - set current tenant from response:', current_tenant.name)
    } else {
      const storedTenantId = getStoredTenantId()
      if (storedTenantId && storedTenantId !== 'null') {
        const tenant = tenants.value.find((t: Tenant) => t.id === parseInt(storedTenantId))
        if (tenant) {
          currentTenant.value = tenant
          console.log('auth store - restored current tenant from storage:', tenant.name)
        }
      }
      if (!currentTenant.value) {
        const defaultTenant =
          tenants.value.find((t: Tenant) => t.id === userData.default_tenant_id) || tenants.value[0]
        if (defaultTenant) {
          setCurrentTenant(defaultTenant)
          console.log('auth store - set default tenant:', defaultTenant.name)
        }
      }
    }

    return response.data
  }

  function setCurrentTenant(tenant: Tenant) {
    currentTenant.value = tenant
    localStorage.setItem('current_tenant_id', tenant.id.toString())
    sessionStorage.setItem('current_tenant_id', tenant.id.toString())
  }

  function getStoredToken(): string | null {
    let token = localStorage.getItem('auth_token')
    let expires = localStorage.getItem('auth_token_expires')

    if (!token || (expires && Date.now() > parseInt(expires))) {
      console.log('auth store - localStorage token not found or expired, trying sessionStorage')
      token = sessionStorage.getItem('auth_token')
      expires = sessionStorage.getItem('auth_token_expires')

      if (token && expires && Date.now() <= parseInt(expires)) {
        localStorage.setItem('auth_token', token)
        localStorage.setItem('auth_token_expires', expires)
        console.log('auth store - restored token from sessionStorage to localStorage')
      }
    }

    if (token && expires && Date.now() > parseInt(expires)) {
      console.log('auth store - token expired, clearing all storages')
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_token_expires')
      sessionStorage.removeItem('auth_token')
      sessionStorage.removeItem('auth_token_expires')
      return null
    }

    return token
  }

  function getStoredTenantId(): string | null {
    return localStorage.getItem('current_tenant_id') || sessionStorage.getItem('current_tenant_id')
  }

  // جديد: تهيئة مبكّرة قبل أول ناڤيجيشن/قبل mount
  async function hydrate(): Promise<void> {
    if (initialized.value) {
      return Promise.resolve()
    }
    
    if (hydrating) {
      return hydrating
    }

    hydrating = new Promise<void>(async (resolve) => {
      try {
        const token = getStoredToken()
        if (token) {
          axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
          axios.defaults.headers.common['Accept'] = 'application/json'
          await fetchUser()
        } else {
          user.value = null
          tenants.value = []
          currentTenant.value = null
        }
      } catch (e) {
        // توكن باطل/منتهي
        delete axios.defaults.headers.common['Authorization']
        localStorage.removeItem('auth_token')
        localStorage.removeItem('auth_token_expires')
        sessionStorage.removeItem('auth_token')
        sessionStorage.removeItem('auth_token_expires')
        user.value = null
        tenants.value = []
        currentTenant.value = null
      } finally {
        initialized.value = true
        hydrating = null
        resolve()
      }
    })

    return hydrating
  }

  return {
    // state
    user,
    tenants,
    currentTenant,
    loading,
    initialized,
    isSuperAdmin,

    // getters
    isAuthenticated,
    isAdmin,

    // actions
    login,
    logout,
    fetchUser,
    fetchTenants,
    setCurrentTenant,
    getStoredToken,
    getStoredTenantId,
    hydrate, // 👈 مهم
  }
})

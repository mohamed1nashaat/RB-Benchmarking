import { useAuthStore } from '@/stores/auth'
import { createRouter, createWebHistory } from 'vue-router'

// Import pages
import Layout from '@/layouts/AppLayout.vue'
import AdAccounts from '@/pages/AdAccounts.vue'
import AdAccountDetail from '@/pages/AdAccountDetail.vue'
import Benchmarks from '@/pages/Benchmarks.vue'
import IndustryOverview from '@/pages/IndustryOverview.vue'
import IndustryManagement from '@/pages/IndustryManagement.vue'
import Clients from '@/pages/Clients.vue'
import ClientDashboard from '@/pages/ClientDashboard.vue'
// import Dashboard from '@/pages/Dashboard.vue' // Hidden
import Integrations from '@/pages/Integrations.vue'
import Settings from '@/pages/Settings.vue'
import Login from '@/pages/Login.vue'
import PrivacyPolicy from '@/pages/PrivacyPolicy.vue'
import TermsOfService from '@/pages/TermsOfService.vue'
import Users from '@/pages/Users.vue'
import RoleManagement from '@/pages/RoleManagement.vue'
import SuperAdminUsers from '@/pages/SuperAdminUsers.vue'
import AcceptInvitation from '@/pages/AcceptInvitation.vue'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/facebook-callback',
    name: 'facebook-callback',
    component: () => import('@/pages/FacebookCallback.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/linkedin-callback',
    name: 'linkedin-callback',
    component: () => import('@/pages/LinkedInCallback.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/privacy-policy',
    name: 'privacy-policy',
    component: PrivacyPolicy,
    meta: { requiresAuth: false }
  },
  {
    path: '/privacy',
    redirect: '/privacy-policy'
  },
  {
    path: '/terms-of-service',
    name: 'terms-of-service',
    component: TermsOfService,
    meta: { requiresAuth: false }
  },
  {
    path: '/terms',
    redirect: '/terms-of-service'
  },
  {
    path: '/',
    component: Layout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/ad-accounts' // Changed from dashboard to ad-accounts
      },
      // Dashboard route hidden
      // {
      //   path: '/dashboard',
      //   name: 'dashboard',
      //   component: Dashboard,
      //   meta: { title: 'Dashboard' }
      // },
      {
        path: '/ad-accounts',
        name: 'ad-accounts',
        component: AdAccounts,
        meta: { title: 'Ad Accounts' }
      },
      {
        path: '/ad-accounts/:id',
        name: 'ad-account-detail',
        component: AdAccountDetail,
        meta: { title: 'Ad Account Details' }
      },
      {
        path: '/benchmarks',
        name: 'benchmarks',
        component: Benchmarks,
        meta: { title: 'Benchmarks' }
      },
      {
        path: '/industry-overview',
        name: 'industry-overview',
        component: IndustryOverview,
        meta: { title: 'Industry Overview' }
      },
      {
        path: '/industry-management',
        name: 'industry-management',
        component: IndustryManagement,
        meta: { title: 'Industry Management' }
      },
      {
        path: '/clients',
        name: 'clients',
        component: Clients,
        meta: { title: 'Clients' }
      },
      {
        path: '/clients/:id/dashboard',
        name: 'client-dashboard',
        component: ClientDashboard,
        meta: { title: 'Client Dashboard' }
      },
      {
        path: '/campaigns/:campaignId/metrics',
        name: 'campaign-metrics',
        component: () => import('@/pages/CampaignMetrics.vue'),
        meta: { title: 'Campaign Metrics' }
      },
      {
        path: '/integrations',
        name: 'integrations',
        component: Integrations,
        meta: { title: 'Platform Management' }
      },
      {
        path: '/settings',
        name: 'settings',
        component: Settings,
        meta: { title: 'Settings' }
      },
      {
        path: '/users',
        name: 'users',
        component: Users,
        meta: { title: 'Users' }
      },
      {
        path: '/roles',
        name: 'roles',
        component: RoleManagement,
        meta: { title: 'Role Management' }
      },
      {
        path: '/admin/users',
        name: 'super-admin-users',
        component: SuperAdminUsers,
        meta: { title: 'User Management' }
      }
    ]
  },
  {
    path: '/invitation/:token',
    name: 'accept-invitation',
    component: AcceptInvitation,
    meta: { requiresAuth: false }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard
router.beforeEach(async (to, from, next) => {
  try {
    const authStore = useAuthStore()
    
    // Wait for auth initialization to complete
    if (!authStore.initialized) {
      await authStore.hydrate()
    }
    
    const requiresAuth = to.matched.some(record => record.meta.requiresAuth !== false)
    
    if (requiresAuth && !authStore.isAuthenticated) {
      next('/login')
    } else if (to.path === '/login' && authStore.isAuthenticated) {
      next('/benchmarks')
    } else {
      next()
    }
  } catch (error) {
    console.error('Navigation guard error:', error)
    // Allow navigation to continue on error
    next()
  }
})

export default router

import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Lazy-loaded views
const LoginView = () => import('../views/LoginView.vue')
const DashboardView = () => import('../views/DashboardView.vue')
const DeviceListView = () => import('../views/devices/DeviceListView.vue')
const DeviceDetailView = () => import('../views/devices/DeviceDetailView.vue')
const TopologyView = () => import('../views/devices/TopologyView.vue')
const BackupListView = () => import('../views/backups/BackupListView.vue')
const JobListView = () => import('../views/jobs/JobListView.vue')
const UserListView = () => import('../views/admin/UserListView.vue')
const AuditLogView = () => import('../views/admin/AuditLogView.vue')

const routes = [
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { requiresAuth: false },
  },
  {
    path: '/',
    redirect: '/dashboard',
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: { requiresAuth: true, title: 'Tableau de bord' },
  },
  {
    path: '/devices',
    name: 'devices',
    component: DeviceListView,
    meta: { requiresAuth: true, title: 'Équipements', permission: 'view_devices' },
  },
  {
    path: '/topology',
    name: 'topology',
    component: TopologyView,
    meta: { requiresAuth: true, title: 'Topologie', permission: 'view_devices' },
  },
  {
    path: '/devices/:id',
    name: 'device-detail',
    component: DeviceDetailView,
    meta: { requiresAuth: true, title: 'Détails Équipement', permission: 'view_devices' },
  },
  {
    path: '/backups',
    name: 'backups',
    component: BackupListView,
    meta: { requiresAuth: true, title: 'Backups', permission: 'view_backups' },
  },
  {
    path: '/automation/jobs',
    name: 'jobs',
    component: JobListView,
    meta: { requiresAuth: true, title: 'Automation Jobs', permission: 'view_jobs' },
  },
  {
    path: '/admin/users',
    name: 'admin-users',
    component: UserListView,
    meta: { requiresAuth: true, title: 'User Management', permission: 'manage_users' },
  },
  {
    path: '/admin/audit-logs',
    name: 'admin-audit-logs',
    component: AuditLogView,
    meta: { requiresAuth: true, title: 'Audit Logs', permission: 'view_audit_logs' },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth !== false && !auth.isAuthenticated) {
    return next({ name: 'login' })
  }

  if (to.name === 'login' && auth.isAuthenticated) {
    return next({ name: 'dashboard' })
  }

  // Permission check
  if (to.meta.permission && !auth.hasPermission(to.meta.permission)) {
    return next({ name: 'dashboard' })
  }

  // Update page title
  document.title = to.meta.title
    ? `${to.meta.title} — Cisco NAMP`
    : 'Cisco NAMP — Network Automation Platform'

  next()
})

export default router

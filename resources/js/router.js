import { createRouter, createWebHistory } from 'vue-router'
import { getToken, getUserRole } from './api'
import Login from './views/Login.vue'
import Register from './views/Register.vue'
import ParentDashboard from './views/ParentDashboard.vue'
import SuperAdminDashboard from './views/SuperAdminDashboard.vue'

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', name: 'login', component: Login, meta: { guest: true } },
  { path: '/register', name: 'register', component: Register, meta: { guest: true } },
  {
    path: '/parent',
    name: 'parent',
    component: ParentDashboard,
    meta: { requiresAuth: true, role: 'parent' },
  },
  {
    path: '/superadmin',
    name: 'superadmin',
    component: SuperAdminDashboard,
    meta: { requiresAuth: true, role: 'superadmin' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const token = getToken()
  const role = getUserRole()

  if (to.meta.requiresAuth && !token) {
    next('/login')
    return
  }

  if (to.meta.requiresAuth && to.meta.role && to.meta.role !== role) {
    next(role === 'superadmin' ? '/superadmin' : '/parent')
    return
  }

  if (to.meta.guest && token) {
    next(role === 'superadmin' ? '/superadmin' : '/parent')
    return
  }

  next()
})

export default router

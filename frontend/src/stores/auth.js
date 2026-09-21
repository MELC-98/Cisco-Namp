import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('namp_user') || 'null'),
    token: localStorage.getItem('namp_token') || null,
    permissions: JSON.parse(localStorage.getItem('namp_permissions') || '[]'),
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    userName: (state) => state.user?.name || '',
    userEmail: (state) => state.user?.email || '',
    userRole: (state) => state.user?.role?.display_name || '',
    roleName: (state) => state.user?.role?.name || '',
    isAdmin: (state) => state.user?.role?.name === 'administrator',
  },

  actions: {
    hasPermission(permission) {
      if (this.isAdmin) return true
      return this.permissions.includes(permission)
    },

    async login(email, password, remember = false) {
      this.loading = true
      this.error = null

      try {
        await api.get('/sanctum/csrf-cookie', { baseURL: '' })
        const response = await api.post('/auth/login', { email, password, remember })
        const { user, token } = response.data

        this.user = user
        this.token = token
        this.permissions = user.permissions || []

        localStorage.setItem('namp_user', JSON.stringify(user))
        localStorage.setItem('namp_token', token)
        localStorage.setItem('namp_permissions', JSON.stringify(this.permissions))

        return true
      } catch (err) {
        this.error = err.response?.data?.message || 'Login failed'
        return false
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        if (this.token) {
          await api.post('/auth/logout')
        }
      } catch {
        // Ignore logout errors
      } finally {
        this.user = null
        this.token = null
        this.permissions = []
        localStorage.removeItem('namp_user')
        localStorage.removeItem('namp_token')
        localStorage.removeItem('namp_permissions')
      }
    },

    async fetchUser() {
      try {
        const response = await api.get('/auth/user')
        this.user = response.data
        this.permissions = response.data.permissions || []
        localStorage.setItem('namp_user', JSON.stringify(this.user))
        localStorage.setItem('namp_permissions', JSON.stringify(this.permissions))
      } catch {
        this.logout()
      }
    },
  },
})

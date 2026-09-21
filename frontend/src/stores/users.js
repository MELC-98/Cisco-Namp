import { defineStore } from 'pinia'
import api from '../services/api'

export const useUserStore = defineStore('users', {
  state: () => ({
    users: [],
    roles: [],
    pagination: {},
    loading: false,
  }),

  actions: {
    async fetchUsers(params = {}) {
      this.loading = true
      try {
        const response = await api.get('/admin/users', { params })
        this.users = response.data.data
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total,
        }
      } finally {
        this.loading = false
      }
    },

    async fetchRoles() {
      const response = await api.get('/admin/roles')
      this.roles = response.data
      return response.data
    },

    async createUser(data) {
      const response = await api.post('/admin/users', data)
      return response.data
    },

    async updateUser(id, data) {
      const response = await api.put(`/admin/users/${id}`, data)
      return response.data
    },

    async deleteUser(id) {
      await api.delete(`/admin/users/${id}`)
    },

    async toggleStatus(id) {
      const response = await api.patch(`/admin/users/${id}/toggle-status`)
      return response.data
    },

    async resetPassword(id, data) {
      const response = await api.post(`/admin/users/${id}/reset-password`, data)
      return response.data
    },
  },
})

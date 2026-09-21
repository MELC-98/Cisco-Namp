import { defineStore } from 'pinia'
import api from '../services/api'

export const useBackupStore = defineStore('backups', {
  state: () => ({ backups: [], pagination: {}, loading: false }),
  actions: {
    async fetchBackups(params = {}) {
      this.loading = true
      try {
        const r = await api.get('/backups', { params })
        this.backups = r.data.data
        this.pagination = { current_page: r.data.current_page, last_page: r.data.last_page, total: r.data.total }
      } finally { this.loading = false }
    },
    async fetchBackup(id) {
      const r = await api.get(`/backups/${id}`)
      return r.data
    },
  },
})

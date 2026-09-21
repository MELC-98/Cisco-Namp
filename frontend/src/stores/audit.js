import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuditStore = defineStore('audit', {
  state: () => ({ logs: [], pagination: {}, loading: false }),
  actions: {
    async fetchLogs(params = {}) {
      this.loading = true
      try {
        const r = await api.get('/admin/audit-logs', { params })
        this.logs = r.data.data
        this.pagination = { current_page: r.data.current_page, last_page: r.data.last_page, total: r.data.total }
      } finally { this.loading = false }
    },
  },
})

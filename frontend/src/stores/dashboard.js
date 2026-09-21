import { defineStore } from 'pinia'
import api from '../services/api'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    stats: null,
    devices: [],
    recentJobs: [],
    recentBackups: [],
    loading: false,
  }),

  actions: {
    async fetchAll() {
      this.loading = true
      try {
        const [stats, devices, jobs, backups] = await Promise.all([
          api.get('/dashboard/stats'),
          api.get('/dashboard/devices'),
          api.get('/dashboard/recent-jobs'),
          api.get('/dashboard/recent-backups'),
        ])
        this.stats = stats.data
        this.devices = devices.data
        this.recentJobs = jobs.data
        this.recentBackups = backups.data
      } catch (err) {
        console.error('Dashboard fetch failed:', err)
      } finally {
        this.loading = false
      }
    },
  },
})

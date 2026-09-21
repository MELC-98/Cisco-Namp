import { defineStore } from 'pinia'
import api from '../services/api'

export const useJobStore = defineStore('jobs', {
  state: () => ({ jobs: [], currentJob: null, pagination: {}, loading: false }),
  actions: {
    async fetchJobs(params = {}) {
      this.loading = true
      try {
        const r = await api.get('/jobs', { params })
        this.jobs = r.data.data
        this.pagination = { current_page: r.data.current_page, last_page: r.data.last_page, total: r.data.total }
      } finally { this.loading = false }
    },
    async fetchJob(id) {
      const r = await api.get(`/jobs/${id}`)
      this.currentJob = r.data
      return r.data
    },
  },
})

import { defineStore } from 'pinia'
import api from '../services/api'

export const useDeviceStore = defineStore('devices', {
  state: () => ({
    devices: [],
    currentDevice: null,
    interfaces: [],
    pagination: {},
    loading: false,
    error: null,
  }),

  actions: {
    async fetchDevices(params = {}) {
      this.loading = true
      try {
        const response = await api.get('/devices', { params })
        this.devices = response.data.data
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total,
          per_page: response.data.per_page,
        }
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to fetch devices'
      } finally {
        this.loading = false
      }
    },

    async fetchDevice(id) {
      this.loading = true
      try {
        const response = await api.get(`/devices/${id}`)
        this.currentDevice = response.data
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to fetch device'
      } finally {
        this.loading = false
      }
    },

    async createDevice(data) {
      const response = await api.post('/devices', data)
      return response.data
    },

    async updateDevice(id, data) {
      const response = await api.put(`/devices/${id}`, data)
      return response.data
    },

    async deleteDevice(id) {
      await api.delete(`/devices/${id}`)
    },

    async testConnection(id) {
      const response = await api.post(`/devices/${id}/test`)
      return response.data
    },

    async discoverDevice(id) {
      const response = await api.post(`/devices/${id}/discover`)
      return response.data
    },

    async fetchInterfaces(id) {
      const response = await api.get(`/devices/${id}/interfaces`)
      this.interfaces = response.data
      return response.data
    },

    async refreshInterfaces(id) {
      const response = await api.post(`/devices/${id}/interfaces/refresh`)
      this.interfaces = response.data.interfaces
      return response.data
    },

    async previewInterfaceConfig(id, data) {
      const response = await api.post(`/devices/${id}/interfaces/preview`, data)
      return response.data
    },

    async deployInterfaceConfig(id, changeId) {
      const response = await api.post(`/devices/${id}/interfaces/deploy`, { change_id: changeId })
      return response.data
    },

    async executeCommand(id, command) {
      const response = await api.post(`/devices/${id}/commands`, { command })
      return response.data
    },

    async fetchCommandHistory(id, params = {}) {
      const response = await api.get(`/devices/${id}/commands`, { params })
      return response.data
    },

    async deployRawConfig(id, configLines) {
      const response = await api.post(`/devices/${id}/configure-raw`, { config_lines: configLines })
      return response.data
    },

    async createBackup(id) {
      const response = await api.post(`/devices/${id}/backups`)
      return response.data
    },
  },
})

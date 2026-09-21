<template>
  <AppLayout>
    <div class="space-y-6 animate-fade-in">
      <h1 class="text-2xl font-bold text-gray-900">Tableau de bord</h1>

      <!-- Stats cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard label="Équipements Total" :value="stats?.total_devices || 0" icon="devices" color="brand" />
        <StatCard label="En ligne" :value="stats?.online_devices || 0" icon="online" color="emerald" />
        <StatCard label="Hors ligne" :value="stats?.offline_devices || 0" icon="offline" color="red" />
        <StatCard label="Routeurs" :value="stats?.routers || 0" icon="router" color="violet" />
        <StatCard label="Commutateurs" :value="stats?.switches || 0" icon="switch" color="sky" />
        <StatCard label="Sauvegardes du jour" :value="stats?.backups_today || 0" icon="backup" color="teal" />
        <StatCard label="Tâches réussies" :value="stats?.successful_jobs || 0" icon="success" color="emerald" />
        <StatCard label="Tâches échouées" :value="stats?.failed_jobs || 0" icon="failed" color="red" />
      </div>

      <!-- Device status + recent activity -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Device status table -->
        <div class="xl:col-span-2 card">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Statut des Équipements</h2>
          <div class="overflow-x-auto">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Équipement</th>
                  <th>IP de gestion</th>
                  <th>Type</th>
                  <th>Modèle</th>
                  <th>Statut</th>
                  <th>Dernière vue</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="device in dashboard.devices" :key="device.id"
                    class="cursor-pointer" @click="$router.push(`/devices/${device.id}`)">
                  <td class="font-medium text-gray-900">{{ device.hostname }}</td>
                  <td class="font-mono text-sm">{{ device.management_ip }}</td>
                  <td>
                    <span class="capitalize">{{ device.category }}</span>
                  </td>
                  <td>{{ device.model || '—' }}</td>
                  <td>
                    <span :class="statusBadgeClass(device.status)">{{ device.status }}</span>
                  </td>
                  <td class="text-gray-500 text-sm">{{ formatDate(device.last_seen_at) }}</td>
                </tr>
                <tr v-if="!dashboard.devices.length">
                  <td colspan="6" class="text-center text-gray-400 py-8">Aucun équipement trouvé</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Recent activity -->
        <div class="space-y-6">
          <!-- Recent Jobs -->
          <div class="card">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Tâches Récentes</h2>
            <div class="space-y-3">
              <div v-for="job in dashboard.recentJobs" :key="job.id"
                   class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                  <p class="text-sm font-medium text-gray-700">{{ job.device || 'Inconnu' }}</p>
                  <p class="text-xs text-gray-400">{{ formatJobType(job.job_type) }} · {{ job.user }}</p>
                </div>
                <span :class="jobBadgeClass(job.status)" class="badge text-xs">{{ job.status }}</span>
              </div>
              <p v-if="!dashboard.recentJobs.length" class="text-sm text-gray-400 text-center py-4">Aucune tâche récente</p>
            </div>
          </div>

          <!-- Recent Backups -->
          <div class="card">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Sauvegardes Récentes</h2>
            <div class="space-y-3">
              <div v-for="backup in dashboard.recentBackups" :key="backup.id"
                   class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <div>
                  <p class="text-sm font-medium text-gray-700">{{ backup.device || 'Inconnu' }}</p>
                  <p class="text-xs text-gray-400">{{ backup.user }} · {{ formatDate(backup.created_at) }}</p>
                </div>
                <span :class="backup.status === 'success' ? 'badge-success' : 'badge-failed'" class="badge text-xs">
                  {{ backup.status }}
                </span>
              </div>
              <p v-if="!dashboard.recentBackups.length" class="text-sm text-gray-400 text-center py-4">Aucune sauvegarde récente</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { useDashboardStore } from '../stores/dashboard'
import AppLayout from '../components/layout/AppLayout.vue'
import StatCard from '../components/common/StatCard.vue'

const dashboard = useDashboardStore()
const stats = computed(() => dashboard.stats)

onMounted(() => {
  dashboard.fetchAll()
})

function statusBadgeClass(status) {
  const map = { online: 'badge badge-online', offline: 'badge badge-offline', warning: 'badge badge-warning', unknown: 'badge badge-unknown' }
  return map[status] || 'badge badge-unknown'
}

function jobBadgeClass(status) {
  const map = { success: 'badge-success', failed: 'badge-failed', running: 'badge-running', pending: 'badge-pending' }
  return map[status] || 'badge-pending'
}

function formatJobType(type) {
  return (type || '').replace(/_/g, ' ').toLowerCase().replace(/\b\w/g, l => l.toUpperCase())
}

function formatDate(date) {
  if (!date) return '—'
  return new Date(date).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

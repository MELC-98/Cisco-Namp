<template>
  <AppLayout>
    <div class="space-y-6 animate-fade-in">
      <h1 class="text-2xl font-bold text-gray-900">Journaux d'Audit</h1>

      <!-- Filters -->
      <div class="flex flex-wrap gap-3">
        <input v-model="search" type="text" placeholder="Rechercher des actions..." class="form-input max-w-xs" @input="debouncedFetch" />
        <select v-model="filterAction" @change="fetchLogs" class="form-select max-w-[200px]">
          <option value="">Toutes les Actions</option>
          <option v-for="a in actions" :key="a" :value="a">{{ a }}</option>
        </select>
        <select v-model="filterStatus" @change="fetchLogs" class="form-select max-w-[150px]">
          <option value="">Tous les Statuts</option>
          <option value="success">Réussi</option>
          <option value="failed">Échoué</option>
        </select>
      </div>

      <div class="card p-0 overflow-hidden">
        <table class="data-table">
          <thead>
            <tr><th>Heure</th><th>Utilisateur</th><th>Action</th><th>Objet</th><th>Description</th><th>IP</th><th>Statut</th></tr>
          </thead>
          <tbody>
            <tr v-for="log in auditStore.logs" :key="log.id">
              <td class="text-sm text-gray-500 whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
              <td class="text-sm">{{ log.user?.name || 'Système' }}</td>
              <td><span class="badge badge-unknown font-mono text-xs">{{ log.action }}</span></td>
              <td class="text-sm">{{ log.object_type ? `${log.object_type}#${log.object_id}` : '—' }}</td>
              <td class="text-sm text-gray-600 max-w-xs truncate">{{ log.description || '—' }}</td>
              <td class="text-sm font-mono text-gray-400">{{ log.source_ip || '—' }}</td>
              <td><span :class="log.status === 'success' ? 'badge badge-success' : 'badge badge-failed'">{{ log.status === 'success' ? 'Réussi' : 'Échoué' }}</span></td>
            </tr>
            <tr v-if="!auditStore.logs.length"><td colspan="7" class="text-center text-gray-400 py-12">Aucun journal d'audit trouvé</td></tr>
          </tbody>
        </table>

        <div v-if="auditStore.pagination.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
          <p class="text-sm text-gray-500">{{ auditStore.pagination.total }} entrées</p>
          <div class="flex gap-1">
            <button v-for="page in Math.min(auditStore.pagination.last_page, 10)" :key="page"
                    @click="fetchLogs(page)"
                    :class="['px-3 py-1 text-sm rounded-md', page === auditStore.pagination.current_page ? 'bg-brand-600 text-white' : 'text-gray-600 hover:bg-gray-100']">
              {{ page }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuditStore } from '../../stores/audit'
import AppLayout from '../../components/layout/AppLayout.vue'

const auditStore = useAuditStore()
const search = ref('')
const filterAction = ref('')
const filterStatus = ref('')

const actions = [
  'LOGIN', 'LOGIN_FAILED', 'LOGOUT',
  'CREATE_USER', 'UPDATE_USER', 'DELETE_USER', 'DISABLE_USER', 'ENABLE_USER', 'RESET_PASSWORD',
  'CREATE_DEVICE', 'UPDATE_DEVICE', 'DELETE_DEVICE',
  'TEST_CONNECTION', 'DISCOVER_DEVICE', 'RUN_COMMAND', 'BACKUP_CONFIG', 'DEPLOY_CONFIG',
]

let debounceTimer
function debouncedFetch() { clearTimeout(debounceTimer); debounceTimer = setTimeout(fetchLogs, 300) }

function fetchLogs(page = 1) {
  auditStore.fetchLogs({ page, search: search.value, action: filterAction.value, status: filterStatus.value, per_page: 25 })
}

onMounted(() => fetchLogs())

function formatDate(d) { if (!d) return '—'; return new Date(d).toLocaleString('fr-FR', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' }) }
</script>

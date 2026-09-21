<template>
  <AppLayout>
    <div class="space-y-6 animate-fade-in">
      <h1 class="text-2xl font-bold text-gray-900">Tâches d'Automatisation</h1>

      <div class="flex flex-wrap gap-3">
        <select v-model="filterStatus" @change="fetchJobs" class="form-select max-w-[150px]">
          <option value="">Tous Statuts</option>
          <option value="success">Réussi</option>
          <option value="failed">Échoué</option>
          <option value="running">En cours</option>
          <option value="pending">En attente</option>
        </select>
        <select v-model="filterType" @change="fetchJobs" class="form-select max-w-[200px]">
          <option value="">Tous Types</option>
          <option value="TEST_CONNECTION">Tester Connexion</option>
          <option value="DISCOVER_DEVICE">Découvrir Équipement</option>
          <option value="BACKUP_CONFIG">Sauvegarder Config</option>
          <option value="RUN_COMMAND">Exécuter Commande</option>
          <option value="CONFIGURE_INTERFACE">Configurer Interface</option>
        </select>
      </div>

      <div class="card p-0 overflow-hidden">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Équipement</th><th>Action</th><th>Utilisateur</th><th>Statut</th><th>Durée</th><th>Date</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-for="job in jobStore.jobs" :key="job.id">
              <td class="font-mono text-sm">#{{ job.id }}</td>
              <td class="font-medium">{{ job.device?.hostname || '—' }}</td>
              <td>{{ formatJobType(job.job_type) }}</td>
              <td>{{ job.user?.name || '—' }}</td>
              <td>
                <span :class="jobBadge(job.status)">
                  <svg v-if="job.status === 'running'" class="w-3 h-3 animate-spin mr-1 inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                  {{ job.status }}
                </span>
              </td>
              <td class="text-sm">{{ job.duration || '—' }}</td>
              <td class="text-sm text-gray-500">{{ formatDate(job.created_at) }}</td>
              <td>
                <button @click="viewJob(job)" class="btn-secondary btn-sm">Détails</button>
              </td>
            </tr>
            <tr v-if="!jobStore.jobs.length"><td colspan="8" class="text-center text-gray-400 py-12">Aucune tâche trouvée</td></tr>
          </tbody>
        </table>

        <div v-if="jobStore.pagination.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
          <p class="text-sm text-gray-500">{{ jobStore.pagination.total }} tâches</p>
          <div class="flex gap-1">
            <button v-for="page in Math.min(jobStore.pagination.last_page, 10)" :key="page"
                    @click="fetchJobs(page)"
                    :class="['px-3 py-1 text-sm rounded-md', page === jobStore.pagination.current_page ? 'bg-brand-600 text-white' : 'text-gray-600 hover:bg-gray-100']">
              {{ page }}
            </button>
          </div>
        </div>
      </div>

      <!-- Job Detail Modal -->
      <div v-if="selectedJob" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="selectedJob = null">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[80vh] overflow-hidden animate-fade-in">
          <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <div>
              <h2 class="text-lg font-bold">Tâche #{{ selectedJob.id }}</h2>
              <p class="text-sm text-gray-500">{{ formatJobType(selectedJob.job_type) }} · {{ selectedJob.device?.hostname }}</p>
            </div>
            <button @click="selectedJob = null" class="p-1 rounded-lg hover:bg-gray-100">✕</button>
          </div>
          <div class="p-6 space-y-4 overflow-y-auto max-h-[60vh]">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div><span class="text-gray-500">Statut:</span> <span :class="jobBadge(selectedJob.status)" class="ml-2">{{ selectedJob.status }}</span></div>
              <div><span class="text-gray-500">Utilisateur:</span> {{ selectedJob.user?.name }}</div>
              <div><span class="text-gray-500">Démarré:</span> {{ formatDate(selectedJob.started_at) }}</div>
              <div><span class="text-gray-500">Durée:</span> {{ selectedJob.duration || '—' }}</div>
            </div>
            <div v-if="selectedJob.output">
              <p class="text-sm font-semibold text-gray-700 mb-2">Résultat</p>
              <pre class="terminal-output">{{ selectedJob.output }}</pre>
            </div>
            <div v-if="selectedJob.error">
              <p class="text-sm font-semibold text-red-600 mb-2">Erreur</p>
              <pre class="bg-red-50 text-red-700 p-4 rounded-lg text-sm overflow-x-auto whitespace-pre-wrap">{{ selectedJob.error }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useJobStore } from '../../stores/jobs'
import AppLayout from '../../components/layout/AppLayout.vue'

const jobStore = useJobStore()
const filterStatus = ref('')
const filterType = ref('')
const selectedJob = ref(null)

function fetchJobs(page = 1) {
  jobStore.fetchJobs({ page, status: filterStatus.value, job_type: filterType.value })
}

onMounted(() => fetchJobs())

async function viewJob(job) {
  selectedJob.value = await jobStore.fetchJob(job.id)
}

function jobBadge(s) {
  const map = { success: 'badge badge-success', failed: 'badge badge-failed', running: 'badge badge-running', pending: 'badge badge-pending', cancelled: 'badge badge-warning' }
  return map[s] || 'badge badge-unknown'
}
function formatJobType(t) { return (t || '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }
function formatDate(d) { if (!d) return '—'; return new Date(d).toLocaleString('fr-FR', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }
</script>

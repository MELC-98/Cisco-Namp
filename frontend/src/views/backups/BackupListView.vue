<template>
  <AppLayout>
    <div class="space-y-6 animate-fade-in">
      <h1 class="text-2xl font-bold text-gray-900">Sauvegardes de Configuration</h1>

      <div class="flex flex-wrap gap-3">
        <select v-model="filterStatus" @change="fetchBackups" class="form-select max-w-[150px]">
          <option value="">Tous Statuts</option>
          <option value="success">Réussi</option>
          <option value="failed">Échoué</option>
        </select>
        <select v-model="filterType" @change="fetchBackups" class="form-select max-w-[180px]">
          <option value="">Tous Types</option>
          <option value="manual">Manuel</option>
          <option value="automatic">Automatique</option>
          <option value="pre_change">Pré-Changement</option>
        </select>
      </div>

      <div class="card p-0 overflow-hidden">
        <table class="data-table">
          <thead><tr><th>Équipement</th><th>Fichier</th><th>Créé par</th><th>Type</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
          <tbody>
            <tr v-for="b in backupStore.backups" :key="b.id">
              <td class="font-medium">{{ b.device?.hostname || '—' }}</td>
              <td class="font-mono text-sm">{{ b.filename }}</td>
              <td>{{ b.user?.name || '—' }}</td>
              <td><span class="badge badge-unknown">{{ b.backup_type }}</span></td>
              <td><span :class="b.status === 'success' ? 'badge badge-success' : 'badge badge-failed'">{{ b.status === 'success' ? 'Réussi' : (b.status === 'failed' ? 'Échoué' : b.status) }}</span></td>
              <td class="text-sm text-gray-500">{{ formatDate(b.created_at) }}</td>
              <td>
                <div class="flex gap-1">
                  <button @click="viewBackup(b)" class="btn-secondary btn-sm">Voir</button>
                  <a :href="`/api/backups/${b.id}/download`" class="btn-secondary btn-sm" v-if="b.status === 'success'">Télécharger</a>
                </div>
              </td>
            </tr>
            <tr v-if="!backupStore.backups.length"><td colspan="7" class="text-center text-gray-400 py-12">Aucune sauvegarde trouvée</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Backup Viewer Modal -->
      <div v-if="viewingBackup" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="viewingBackup = null">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[80vh] overflow-hidden animate-fade-in">
          <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <div>
              <h2 class="text-lg font-bold">{{ viewingBackup.backup?.filename }}</h2>
              <p class="text-sm text-gray-500">{{ viewingBackup.backup?.device?.hostname }} · {{ formatDate(viewingBackup.backup?.created_at) }}</p>
            </div>
            <button @click="viewingBackup = null" class="p-1 rounded-lg hover:bg-gray-100">✕</button>
          </div>
          <pre class="terminal-output m-4 max-h-[60vh]">{{ viewingBackup.content || 'Aucun contenu disponible' }}</pre>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useBackupStore } from '../../stores/backups'
import AppLayout from '../../components/layout/AppLayout.vue'

const backupStore = useBackupStore()
const filterStatus = ref('')
const filterType = ref('')
const viewingBackup = ref(null)

function fetchBackups(page = 1) {
  backupStore.fetchBackups({ page, status: filterStatus.value, backup_type: filterType.value })
}

onMounted(() => fetchBackups())

async function viewBackup(b) {
  const data = await backupStore.fetchBackup(b.id)
  viewingBackup.value = data
}

function formatDate(d) { if (!d) return '—'; return new Date(d).toLocaleString('fr-FR', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }
</script>

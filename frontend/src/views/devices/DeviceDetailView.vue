<template>
  <AppLayout>
    <div v-if="device" class="space-y-6 animate-fade-in">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <button @click="$router.push('/devices')" class="p-1 rounded-lg hover:bg-gray-100 text-gray-400">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <h1 class="text-2xl font-bold text-gray-900">{{ device.hostname }}</h1>
            <span :class="statusBadge(device.status)">{{ device.status }}</span>
          </div>
          <p class="text-sm text-gray-500 mt-1 ml-9">{{ device.management_ip }} · {{ device.vendor }} {{ device.category }} · {{ device.model || 'Modèle Inconnu' }}</p>
        </div>
        <div class="flex items-center gap-2">
          <button @click="testConn" :disabled="actionLoading" class="btn-secondary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            Tester Connexion
          </button>
          <button @click="discover" :disabled="actionLoading" class="btn-secondary btn-sm">Actualiser Infos</button>
          <button v-if="auth.hasPermission('create_backups')" @click="backup" :disabled="actionLoading" class="btn-primary btn-sm">Sauvegarder Config</button>
          <button v-if="auth.hasPermission('configure_devices')" @click="rawConfigModal = true" :disabled="actionLoading" class="btn-primary btn-sm bg-indigo-600 hover:bg-indigo-700">Configurer Équipement</button>
        </div>
      </div>

      <!-- Device info cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="card"><p class="text-xs text-gray-500">OS</p><p class="font-semibold mt-1">{{ device.os_name || '—' }} {{ device.os_version || '' }}</p></div>
        <div class="card"><p class="text-xs text-gray-500">Numéro de Série</p><p class="font-semibold mt-1 font-mono text-sm">{{ device.serial_number || '—' }}</p></div>
        <div class="card"><p class="text-xs text-gray-500">Dernière vue</p><p class="font-semibold mt-1 text-sm">{{ formatDate(device.last_seen_at) }}</p></div>
        <div class="card"><p class="text-xs text-gray-500">Dernière Sauvegarde</p><p class="font-semibold mt-1 text-sm">{{ device.latest_backup ? formatDate(device.latest_backup.created_at) : 'Jamais' }}</p></div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200">
        <nav class="flex gap-6">
          <button v-for="t in tabs" :key="t.id" @click="activeTab = t.id"
                  :class="['py-3 text-sm font-medium border-b-2 transition-colors', activeTab === t.id ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
            {{ t.label }}
          </button>
        </nav>
      </div>

      <!-- Tab: Interfaces -->
      <div v-if="activeTab === 'interfaces'" class="card p-0 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="font-semibold text-gray-900">Interfaces</h2>
          <button @click="refreshInterfaces" class="btn-secondary btn-sm" :disabled="actionLoading">Actualiser</button>
        </div>
        <table class="data-table">
          <thead>
            <tr><th>Interface</th><th>Description</th><th>Adresse IP</th><th>Admin</th><th>Opér</th><th v-if="auth.hasPermission('configure_devices')">Actions</th></tr>
          </thead>
          <tbody>
            <tr v-for="iface in interfaces" :key="iface.id || iface.name">
              <td class="font-mono text-sm font-medium">{{ iface.name }}</td>
              <td>{{ iface.description || '—' }}</td>
              <td class="font-mono text-sm">{{ iface.ip_address || '—' }}</td>
              <td><span :class="iface.admin_status === 'up' ? 'text-emerald-600 font-semibold' : 'text-red-500'">{{ iface.admin_status?.toUpperCase() }}</span></td>
              <td><span :class="iface.oper_status === 'up' ? 'text-emerald-600 font-semibold' : 'text-red-500'">{{ iface.oper_status?.toUpperCase() }}</span></td>
              <td v-if="auth.hasPermission('configure_devices')">
                <button @click="openConfigForm(iface)" class="btn-secondary btn-sm">Configurer</button>
              </td>
            </tr>
            <tr v-if="!interfaces.length"><td :colspan="auth.hasPermission('configure_devices') ? 6 : 5" class="text-center text-gray-400 py-8">Aucune interface. Cliquez sur Actualiser pour découvrir.</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Tab: Commands -->
      <div v-if="activeTab === 'commands'" class="space-y-4">
        <div class="card">
          <h2 class="font-semibold text-gray-900 mb-4">Exécuter une Commande</h2>
          <div class="flex gap-3">
            <select v-model="selectedCommand" class="form-select flex-1">
              <option value="">Sélectionner une commande...</option>
              <option v-for="cmd in approvedCommands" :key="cmd" :value="cmd">{{ cmd }}</option>
            </select>
            <button @click="runCommand" :disabled="!selectedCommand || actionLoading" class="btn-primary">
              {{ actionLoading ? 'En cours...' : 'Exécuter' }}
            </button>
          </div>
          <div v-if="commandOutput" class="mt-4">
            <p class="text-sm text-gray-500 mb-2">Résultat :</p>
            <pre class="terminal-output">{{ commandOutput }}</pre>
          </div>
        </div>
      </div>

      <!-- Tab: Backups -->
      <div v-if="activeTab === 'backups'" class="card p-0 overflow-hidden">
        <table class="data-table">
          <thead><tr><th>Fichier</th><th>Créé par</th><th>Type</th><th>Statut</th><th>Date</th></tr></thead>
          <tbody>
            <tr v-for="b in backups" :key="b.id">
              <td class="font-mono text-sm">{{ b.filename }}</td>
              <td>{{ b.user?.name || '—' }}</td>
              <td><span class="badge badge-unknown">{{ b.backup_type }}</span></td>
              <td><span :class="b.status === 'success' ? 'badge badge-success' : 'badge badge-failed'">{{ b.status }}</span></td>
              <td class="text-sm text-gray-500">{{ formatDate(b.created_at) }}</td>
            </tr>
            <tr v-if="!backups.length"><td colspan="5" class="text-center text-gray-400 py-8">Aucune sauvegarde</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Tab: Jobs -->
      <div v-if="activeTab === 'jobs'" class="card p-0 overflow-hidden">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Type</th><th>Utilisateur</th><th>Statut</th><th>Durée</th><th>Date</th></tr></thead>
          <tbody>
            <tr v-for="j in jobs" :key="j.id">
              <td class="font-mono text-sm">#{{ j.id }}</td>
              <td>{{ formatJobType(j.job_type) }}</td>
              <td>{{ j.user?.name || '—' }}</td>
              <td><span :class="jobBadge(j.status)">{{ j.status }}</span></td>
              <td class="text-sm">{{ j.duration || '—' }}</td>
              <td class="text-sm text-gray-500">{{ formatDate(j.created_at) }}</td>
            </tr>
            <tr v-if="!jobs.length"><td colspan="6" class="text-center text-gray-400 py-8">Aucune tâche</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Interface Config Modal -->
      <div v-if="configModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="configModal = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg animate-fade-in">
          <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-bold text-gray-900">Configurer l'Interface</h2>
            <p class="text-sm text-gray-500 mt-1">{{ configForm.interface_name }} sur {{ device.hostname }}</p>
          </div>
          <div v-if="!configPreview" class="p-6 space-y-4">
            <div><label class="form-label">Description</label><input v-model="configForm.description" class="form-input" /></div>
            <div class="grid grid-cols-2 gap-4">
              <div><label class="form-label">Adresse IPv4</label><input v-model="configForm.ip_address" class="form-input" placeholder="10.50.0.1" /></div>
              <div><label class="form-label">Masque de sous-réseau</label><input v-model="configForm.subnet_mask" class="form-input" placeholder="255.255.255.0" /></div>
            </div>
            <div><label class="form-label">Statut Admin</label>
              <select v-model="configForm.admin_status" class="form-select">
                <option value="up">Activé (no shutdown)</option>
                <option value="down">Désactivé (shutdown)</option>
              </select>
            </div>
            <div class="flex justify-end gap-3 pt-2">
              <button @click="configModal = false" class="btn-secondary">Annuler</button>
              <button @click="previewConfig" :disabled="actionLoading" class="btn-primary">Aperçu Configuration</button>
            </div>
          </div>
          <div v-else class="p-6 space-y-4">
            <p class="text-sm font-semibold text-gray-700">Aperçu de la Configuration</p>
            <pre class="terminal-output">{{ configPreview.config_text }}</pre>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
              ⚠️ Une sauvegarde sera créée automatiquement avant d'appliquer cette configuration.
            </div>
            <div class="flex justify-end gap-3 pt-2">
              <button @click="configPreview = null" class="btn-secondary">Retour</button>
              <button @click="deployConfig" :disabled="actionLoading" class="btn-success">
                {{ actionLoading ? 'Déploiement...' : 'Approuver et Déployer' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Raw Config Modal -->
      <div v-if="rawConfigModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="rawConfigModal = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl animate-fade-in flex flex-col max-h-[90vh]">
          <div class="p-6 border-b border-gray-100 flex-shrink-0">
            <h2 class="text-xl font-bold text-gray-900">Déployer Configuration Brute</h2>
            <p class="text-sm text-gray-500 mt-1">Déployez des lignes de configuration sur {{ device.hostname }}</p>
          </div>
          <div class="p-6 overflow-y-auto flex-1">
            <div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-800">
              <p><strong>Note :</strong> Chaque ligne est exécutée séquentiellement en mode de configuration globale.</p>
              <p class="mt-1 font-mono text-xs bg-blue-100/50 p-2 rounded">vlan 20<br/>name Guest_WiFi<br/>interface g0/2<br/>switchport mode access<br/>switchport access vlan 20</p>
            </div>
            
            <label class="form-label">Commandes de Configuration</label>
            <textarea v-model="rawConfigText" rows="10" class="form-input font-mono text-sm leading-relaxed whitespace-pre w-full resize-y" placeholder="Entrez les commandes de configuration ici..."></textarea>
            
            <div v-if="rawConfigOutput" class="mt-4">
              <p class="text-sm font-semibold text-gray-700 mb-2">Résultat du Déploiement :</p>
              <pre class="terminal-output max-h-64 overflow-y-auto">{{ rawConfigOutput }}</pre>
            </div>
          </div>
          <div class="p-6 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0">
            <button @click="closeRawConfig" class="btn-secondary" :disabled="actionLoading">Fermer</button>
            <button @click="deployRaw" :disabled="actionLoading || !rawConfigText.trim()" class="btn-primary bg-indigo-600 hover:bg-indigo-700">
              {{ actionLoading ? 'Déploiement...' : 'Déployer la Configuration' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading state -->
    <div v-else class="flex items-center justify-center py-20">
      <svg class="w-8 h-8 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
      </svg>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import { useRoute } from 'vue-router'
import { useDeviceStore } from '../../stores/devices'
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'
import AppLayout from '../../components/layout/AppLayout.vue'

const route = useRoute()
const deviceStore = useDeviceStore()
const auth = useAuthStore()
const toast = inject('toast', () => {})

const device = ref(null)
const interfaces = ref([])
const backups = ref([])
const jobs = ref([])
const approvedCommands = ref([])
const selectedCommand = ref('')
const commandOutput = ref('')
const actionLoading = ref(false)
const activeTab = ref('interfaces')

const configModal = ref(false)
const configForm = ref({ interface_name: '', description: '', ip_address: '', subnet_mask: '', admin_status: 'up' })
const configPreview = ref(null)

const rawConfigModal = ref(false)
const rawConfigText = ref('')
const rawConfigOutput = ref('')

const tabs = [
  { id: 'interfaces', label: 'Interfaces' },
  { id: 'commands', label: 'Commandes' },
  { id: 'backups', label: 'Sauvegardes' },
  { id: 'jobs', label: 'Tâches' },
]

onMounted(async () => {
  const id = route.params.id
  device.value = await deviceStore.fetchDevice(id)
  interfaces.value = await deviceStore.fetchInterfaces(id)
  try {
    const cmds = await api.get('/commands/available')
    approvedCommands.value = cmds.data.commands
  } catch {}
  try {
    const b = await api.get('/backups', { params: { device_id: id, per_page: 10 } })
    backups.value = b.data.data || []
  } catch {}
  try {
    const j = await api.get('/jobs', { params: { device_id: id, per_page: 10 } })
    jobs.value = j.data.data || []
  } catch {}
})

async function testConn() {
  actionLoading.value = true
  try {
    const r = await deviceStore.testConnection(route.params.id)
    toast(r.message, r.job?.status === 'success' ? 'success' : 'error')
    device.value = await deviceStore.fetchDevice(route.params.id)
  } catch { toast('Le test de connexion a échoué', 'error') }
  finally { actionLoading.value = false }
}

async function discover() {
  actionLoading.value = true
  try {
    await deviceStore.discoverDevice(route.params.id)
    device.value = await deviceStore.fetchDevice(route.params.id)
    toast('Infos de l\'équipement actualisées', 'success')
  } catch { toast('Échec de l\'actualisation', 'error') }
  finally { actionLoading.value = false }
}

async function backup() {
  actionLoading.value = true
  try {
    const r = await deviceStore.createBackup(route.params.id)
    toast(r.message, r.job?.status === 'success' ? 'success' : 'error')
    const b = await api.get('/backups', { params: { device_id: route.params.id, per_page: 10 } })
    backups.value = b.data.data || []
  } catch { toast('Échec de la sauvegarde', 'error') }
  finally { actionLoading.value = false }
}

async function refreshInterfaces() {
  actionLoading.value = true
  try {
    const r = await deviceStore.refreshInterfaces(route.params.id)
    interfaces.value = r.interfaces || []
    toast('Interfaces actualisées', 'success')
  } catch { toast('Échec de l\'actualisation des interfaces', 'error') }
  finally { actionLoading.value = false }
}

async function runCommand() {
  actionLoading.value = true
  commandOutput.value = ''
  try {
    const r = await deviceStore.executeCommand(route.params.id, selectedCommand.value)
    commandOutput.value = r.result?.output || r.job?.output || 'Aucun résultat'
  } catch (e) { commandOutput.value = 'Erreur : ' + (e.response?.data?.message || 'Échec de la commande') }
  finally { actionLoading.value = false }
}

function openConfigForm(iface) {
  configForm.value = { interface_name: iface.name, description: iface.description || '', ip_address: iface.ip_address || '', subnet_mask: iface.subnet_mask || '', admin_status: iface.admin_status || 'up' }
  configPreview.value = null
  configModal.value = true
}

async function previewConfig() {
  actionLoading.value = true
  try {
    configPreview.value = await deviceStore.previewInterfaceConfig(route.params.id, configForm.value)
  } catch (e) { toast(e.response?.data?.message || 'Échec de l\'aperçu', 'error') }
  finally { actionLoading.value = false }
}

async function deployConfig() {
  actionLoading.value = true
  try {
    const r = await deviceStore.deployInterfaceConfig(route.params.id, configPreview.value.change_id)
    toast(r.message, 'success')
    configModal.value = false
    configPreview.value = null
    interfaces.value = await deviceStore.fetchInterfaces(route.params.id)
  } catch (e) { toast(e.response?.data?.message || 'Échec du déploiement', 'error') }
  finally { actionLoading.value = false }
}

function closeRawConfig() {
  rawConfigModal.value = false
  rawConfigText.value = ''
  rawConfigOutput.value = ''
}

async function deployRaw() {
  actionLoading.value = true
  rawConfigOutput.value = ''
  try {
    const r = await deviceStore.deployRawConfig(route.params.id, rawConfigText.value)
    rawConfigOutput.value = r.result?.output || r.result?.verification || 'Configuration appliquée.'
    toast(r.result?.message || 'Configuration appliquée', 'success')
    
    // Refresh device state that might have been changed
    discover()
  } catch (e) {
    rawConfigOutput.value = 'Erreur : ' + (e.response?.data?.message || 'Échec du déploiement')
    toast('Échec du déploiement', 'error')
  } finally {
    actionLoading.value = false
  }
}

function statusBadge(s) { return { online: 'badge badge-online', offline: 'badge badge-offline', warning: 'badge badge-warning', unknown: 'badge badge-unknown' }[s] || 'badge badge-unknown' }
function jobBadge(s) { return 'badge badge-' + (s || 'unknown') }
function formatJobType(t) { return (t || '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }
function formatDate(d) { if (!d) return '—'; return new Date(d).toLocaleString('fr-FR', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }
</script>

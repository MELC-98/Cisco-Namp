<template>
  <AppLayout>
    <div class="space-y-6 animate-fade-in">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Équipements</h1>
        <button v-if="auth.hasPermission('create_devices')" @click="showForm = true" class="btn-primary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Ajouter un Équipement
        </button>
      </div>

      <!-- Filters -->
      <div class="flex flex-wrap gap-3">
        <input v-model="search" type="text" placeholder="Rechercher nom, IP, modèle..."
               class="form-input max-w-xs" @input="debouncedFetch" />
        <select v-model="filterCategory" @change="fetchDevices" class="form-select max-w-[150px]">
          <option value="">Tous types</option>
          <option value="router">Routeurs</option>
          <option value="switch">Commutateurs</option>
        </select>
        <select v-model="filterStatus" @change="fetchDevices" class="form-select max-w-[150px]">
          <option value="">Tous statuts</option>
          <option value="online">En ligne</option>
          <option value="offline">Hors ligne</option>
          <option value="unknown">Inconnu</option>
        </select>
      </div>

      <!-- Devices table -->
      <div class="card p-0 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nom d'hôte</th>
                <th>IP de gestion</th>
                <th>Type</th>
                <th>Modèle</th>
                <th>Version IOS</th>
                <th>Statut</th>
                <th>Dernière vue</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="device in deviceStore.devices" :key="device.id">
                <td class="font-medium text-gray-900">
                  <router-link :to="`/devices/${device.id}`" class="text-brand-600 hover:text-brand-800 hover:underline">
                    {{ device.hostname }}
                  </router-link>
                </td>
                <td class="font-mono text-sm">{{ device.management_ip }}</td>
                <td><span class="capitalize">{{ device.category }}</span></td>
                <td>{{ device.model || '—' }}</td>
                <td>{{ device.os_version || '—' }}</td>
                <td>
                  <span :class="statusBadge(device.status)">
                    <span :class="statusDot(device.status)" class="mr-1.5"></span>
                    {{ device.status }}
                  </span>
                </td>
                <td class="text-gray-500 text-sm">{{ formatDate(device.last_seen_at) }}</td>
                <td>
                  <div class="flex items-center gap-1">
                    <button @click="$router.push(`/devices/${device.id}`)" class="btn-secondary btn-sm" title="Voir">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </button>
                    <button v-if="auth.hasPermission('test_devices')" @click="testDevice(device)" class="btn-secondary btn-sm" title="Tester la connexion" :disabled="testing === device.id">
                      <svg v-if="testing !== device.id" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                      <svg v-else class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </button>
                    <button v-if="auth.hasPermission('delete_devices')" @click="confirmDelete(device)" class="btn-secondary btn-sm text-red-500 hover:text-red-700" title="Supprimer">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!deviceStore.devices.length && !deviceStore.loading">
                <td colspan="8" class="text-center text-gray-400 py-12">
                  <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" /></svg>
                  <p class="font-medium">Aucun équipement trouvé</p>
                  <p class="text-sm mt-1">Ajoutez votre premier équipement Cisco pour commencer.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="deviceStore.pagination.last_page > 1" class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
          <p class="text-sm text-gray-500">{{ deviceStore.pagination.total }} équipements au total</p>
          <div class="flex gap-1">
            <button v-for="page in deviceStore.pagination.last_page" :key="page"
                    @click="fetchDevices(page)"
                    :class="['px-3 py-1 text-sm rounded-md', page === deviceStore.pagination.current_page ? 'bg-brand-600 text-white' : 'text-gray-600 hover:bg-gray-100']">
              {{ page }}
            </button>
          </div>
        </div>
      </div>

      <!-- Add/Edit Device Modal -->
      <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showForm = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto animate-fade-in">
          <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-bold text-gray-900">{{ editingDevice ? 'Modifier l\'Équipement' : 'Ajouter un Équipement' }}</h2>
          </div>
          <form @submit.prevent="saveDevice" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Nom d'hôte *</label>
                <input v-model="form.hostname" type="text" required class="form-input" placeholder="CORE-RTR-01" />
              </div>
              <div>
                <label class="form-label">IP de gestion *</label>
                <input v-model="form.management_ip" type="text" required class="form-input" placeholder="10.99.0.1" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Catégorie *</label>
                <select v-model="form.category" required class="form-select">
                  <option value="router">Routeur</option>
                  <option value="switch">Commutateur</option>
                </select>
              </div>
              <div>
                <label class="form-label">Type d'équipement *</label>
                <select v-model="form.device_type" required class="form-select">
                  <option value="cisco_ios">Cisco IOS</option>
                  <option value="cisco_xe">Cisco IOS XE</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Port SSH</label>
                <input v-model.number="form.ssh_port" type="number" class="form-input" placeholder="22" />
              </div>
              <div>
                <label class="form-label">Site (Optionnel)</label>
                <input v-model="form.site_id" type="text" class="form-input" placeholder="Ex: DC-01" />
              </div>
            </div>
            <div>
              <label class="form-label">Description</label>
              <input v-model="form.description" type="text" class="form-input" placeholder="Routeur principal — data center" />
            </div>
            <hr class="border-gray-200" />
            <p class="text-sm font-semibold text-gray-700">Identifiants SSH</p>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Utilisateur *</label>
                <input v-model="form.username" type="text" required class="form-input" placeholder="admin" />
              </div>
              <div>
                <label class="form-label">Mot de passe *</label>
                <input v-model="form.password" type="password" :required="!editingDevice" class="form-input" placeholder="••••••••" />
              </div>
            </div>
            <div>
              <label class="form-label">Mot de passe Enable (Optionnel)</label>
              <input v-model="form.enable_secret" type="password" class="form-input" placeholder="••••••••" />
            </div>
            <div v-if="formError" class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ formError }}</div>
            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="showForm = false" class="btn-secondary">Annuler</button>
              <button type="submit" :disabled="saving" class="btn-primary">
                {{ saving ? 'Enregistrement...' : (editingDevice ? 'Mettre à jour' : 'Ajouter') }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Delete confirmation -->
      <div v-if="deleteTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="deleteTarget = null">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 animate-fade-in">
          <h3 class="text-lg font-bold text-gray-900 mb-2">Supprimer l'Équipement</h3>
          <p class="text-sm text-gray-600 mb-6">Êtes-vous sûr de vouloir supprimer <strong>{{ deleteTarget.hostname }}</strong> ? Cette action est irréversible.</p>
          <div class="flex justify-end gap-3">
            <button @click="deleteTarget = null" class="btn-secondary">Annuler</button>
            <button @click="doDelete" class="btn-danger">Supprimer</button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import { useDeviceStore } from '../../stores/devices'
import { useAuthStore } from '../../stores/auth'
import AppLayout from '../../components/layout/AppLayout.vue'

const deviceStore = useDeviceStore()
const auth = useAuthStore()
const toast = inject('toast', () => {})

const search = ref('')
const filterCategory = ref('')
const filterStatus = ref('')
const showForm = ref(false)
const editingDevice = ref(null)
const saving = ref(false)
const formError = ref('')
const testing = ref(null)
const deleteTarget = ref(null)

const form = ref({
  hostname: '', management_ip: '', ssh_port: 22, category: 'router',
  device_type: 'cisco_ios', site_id: '', description: '',
  username: '', password: '', enable_secret: '',
})

let debounceTimer = null
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchDevices, 300)
}

function fetchDevices(page = 1) {
  deviceStore.fetchDevices({
    page, search: search.value,
    category: filterCategory.value, status: filterStatus.value,
  })
}

onMounted(() => fetchDevices())

async function saveDevice() {
  saving.value = true
  formError.value = ''
  try {
    if (editingDevice.value) {
      await deviceStore.updateDevice(editingDevice.value.id, form.value)
      toast('Équipement mis à jour avec succès', 'success')
    } else {
      await deviceStore.createDevice(form.value)
      toast('Équipement ajouté avec succès', 'success')
    }
    showForm.value = false
    editingDevice.value = null
    fetchDevices()
  } catch (err) {
    formError.value = err.response?.data?.message || 'Échec de la sauvegarde'
  } finally {
    saving.value = false
  }
}

async function testDevice(device) {
  testing.value = device.id
  try {
    const result = await deviceStore.testConnection(device.id)
    toast(result.message, result.job?.status === 'success' ? 'success' : 'error')
    fetchDevices()
  } catch { toast('Le test de connexion a échoué', 'error') }
  finally { testing.value = null }
}

function confirmDelete(device) { deleteTarget.value = device }
async function doDelete() {
  try {
    await deviceStore.deleteDevice(deleteTarget.value.id)
    toast('Équipement supprimé', 'success')
    deleteTarget.value = null
    fetchDevices()
  } catch { toast('Échec de la suppression', 'error') }
}

function statusBadge(s) {
  return { online: 'badge badge-online', offline: 'badge badge-offline', warning: 'badge badge-warning', unknown: 'badge badge-unknown' }[s] || 'badge badge-unknown'
}
function statusDot(s) {
  return { online: 'pulse-dot pulse-dot-online', offline: 'pulse-dot pulse-dot-offline' }[s] || ''
}
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('fr-FR', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <AppLayout>
    <div class="space-y-6 animate-fade-in">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Gestion des Utilisateurs</h1>
        <button @click="openCreateForm" class="btn-primary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Créer un Utilisateur
        </button>
      </div>

      <!-- Users table -->
      <div class="card p-0 overflow-hidden">
        <table class="data-table">
          <thead>
            <tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Dernière Connexion</th><th>Créé le</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <tr v-for="user in userStore.users" :key="user.id">
              <td class="font-medium text-gray-900">{{ user.name }}</td>
              <td class="text-gray-600">{{ user.email }}</td>
              <td><span class="badge badge-unknown">{{ user.role?.display_name || '—' }}</span></td>
              <td><span :class="user.status === 'active' ? 'badge badge-online' : 'badge badge-offline'">{{ user.status === 'active' ? 'Actif' : 'Désactivé' }}</span></td>
              <td class="text-sm text-gray-500">{{ formatDate(user.last_login_at) }}</td>
              <td class="text-sm text-gray-500">{{ formatDate(user.created_at) }}</td>
              <td>
                <div class="flex items-center gap-1">
                  <button @click="openEditForm(user)" class="btn-secondary btn-sm">Modifier</button>
                  <button @click="toggleUser(user)" class="btn-secondary btn-sm">{{ user.status === 'active' ? 'Désactiver' : 'Activer' }}</button>
                  <button @click="openResetPw(user)" class="btn-secondary btn-sm">Réinitialiser MDP</button>
                  <button @click="confirmDeleteUser(user)" class="btn-secondary btn-sm text-red-500">Supprimer</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Create/Edit Modal -->
      <div v-if="showForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showForm = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md animate-fade-in">
          <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-bold text-gray-900">{{ editingUser ? 'Modifier Utilisateur' : 'Créer Utilisateur' }}</h2>
          </div>
          <form @submit.prevent="saveUser" class="p-6 space-y-4">
            <div><label class="form-label">Nom Complet *</label><input v-model="form.name" required class="form-input" /></div>
            <div><label class="form-label">Email *</label><input v-model="form.email" type="email" required class="form-input" /></div>
            <div v-if="!editingUser">
              <label class="form-label">Mot de passe *</label><input v-model="form.password" type="password" required minlength="8" class="form-input" />
            </div>
            <div v-if="!editingUser">
              <label class="form-label">Confirmer le Mot de passe *</label><input v-model="form.password_confirmation" type="password" required class="form-input" />
            </div>
            <div>
              <label class="form-label">Rôle *</label>
              <select v-model="form.role_id" required class="form-select">
                <option v-for="role in userStore.roles" :key="role.id" :value="role.id">{{ role.display_name }}</option>
              </select>
            </div>
            <div><label class="form-label">Statut</label>
              <select v-model="form.status" class="form-select">
                <option value="active">Actif</option>
                <option value="disabled">Désactivé</option>
              </select>
            </div>
            <div v-if="formError" class="p-3 bg-red-50 text-red-700 rounded-lg text-sm">{{ formError }}</div>
            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="showForm = false" class="btn-secondary">Annuler</button>
              <button type="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Enregistrement...' : (editingUser ? 'Mettre à jour' : 'Créer') }}</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Reset Password Modal -->
      <div v-if="resetPwUser" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="resetPwUser = null">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 animate-fade-in">
          <h3 class="text-lg font-bold mb-4">Réinitialiser le mot de passe de {{ resetPwUser.name }}</h3>
          <form @submit.prevent="doResetPw" class="space-y-4">
            <div><label class="form-label">Nouveau Mot de passe</label><input v-model="resetPwForm.password" type="password" required minlength="8" class="form-input" /></div>
            <div><label class="form-label">Confirmer le Mot de passe</label><input v-model="resetPwForm.password_confirmation" type="password" required class="form-input" /></div>
            <div class="flex justify-end gap-3"><button type="button" @click="resetPwUser = null" class="btn-secondary">Annuler</button><button type="submit" class="btn-primary">Réinitialiser</button></div>
          </form>
        </div>
      </div>

      <!-- Delete Confirmation -->
      <div v-if="deleteUser" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="deleteUser = null">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 animate-fade-in">
          <h3 class="text-lg font-bold mb-2">Supprimer Utilisateur</h3>
          <p class="text-sm text-gray-600 mb-6">Êtes-vous sûr de vouloir supprimer <strong>{{ deleteUser.email }}</strong> ?</p>
          <div class="flex justify-end gap-3"><button @click="deleteUser = null" class="btn-secondary">Annuler</button><button @click="doDeleteUser" class="btn-danger">Supprimer</button></div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue'
import { useUserStore } from '../../stores/users'
import AppLayout from '../../components/layout/AppLayout.vue'

const userStore = useUserStore()
const toast = inject('toast', () => {})

const showForm = ref(false)
const editingUser = ref(null)
const saving = ref(false)
const formError = ref('')
const form = ref({ name: '', email: '', password: '', password_confirmation: '', role_id: '', status: 'active' })
const resetPwUser = ref(null)
const resetPwForm = ref({ password: '', password_confirmation: '' })
const deleteUser = ref(null)

onMounted(async () => {
  await userStore.fetchRoles()
  await userStore.fetchUsers()
})

function openCreateForm() {
  editingUser.value = null
  form.value = { name: '', email: '', password: '', password_confirmation: '', role_id: userStore.roles[0]?.id || '', status: 'active' }
  formError.value = ''
  showForm.value = true
}

function openEditForm(user) {
  editingUser.value = user
  form.value = { name: user.name, email: user.email, role_id: user.role_id, status: user.status }
  formError.value = ''
  showForm.value = true
}

async function saveUser() {
  saving.value = true; formError.value = ''
  try {
    if (editingUser.value) { await userStore.updateUser(editingUser.value.id, form.value); toast('Utilisateur mis à jour', 'success') }
    else { await userStore.createUser(form.value); toast('Utilisateur créé', 'success') }
    showForm.value = false; userStore.fetchUsers()
  } catch (e) { formError.value = e.response?.data?.message || 'Échec de la sauvegarde de l\'utilisateur' }
  finally { saving.value = false }
}

async function toggleUser(user) {
  try { await userStore.toggleStatus(user.id); toast(`Utilisateur ${user.status === 'active' ? 'désactivé' : 'activé'}`, 'success'); userStore.fetchUsers() }
  catch { toast('Échec du changement de statut', 'error') }
}

function openResetPw(user) { resetPwUser.value = user; resetPwForm.value = { password: '', password_confirmation: '' } }
async function doResetPw() {
  try { await userStore.resetPassword(resetPwUser.value.id, resetPwForm.value); toast('Mot de passe réinitialisé', 'success'); resetPwUser.value = null }
  catch (e) { toast(e.response?.data?.message || 'Échec de la réinitialisation du mot de passe', 'error') }
}

function confirmDeleteUser(user) { deleteUser.value = user }
async function doDeleteUser() {
  try { await userStore.deleteUser(deleteUser.value.id); toast('Utilisateur supprimé', 'success'); deleteUser.value = null; userStore.fetchUsers() }
  catch (e) { toast(e.response?.data?.message || 'Échec de la suppression', 'error') }
}

function formatDate(d) { if (!d) return '—'; return new Date(d).toLocaleString('fr-FR', { month: 'short', day: 'numeric', year: 'numeric' }) }
</script>

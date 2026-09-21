<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 relative overflow-hidden">
    <!-- Animated background elements -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-brand-500/10 rounded-full blur-3xl animate-pulse-slow"></div>
      <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl animate-pulse-slow" style="animation-delay: 1.5s"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-brand-600/5 rounded-full blur-3xl"></div>
    </div>

    <!-- Login card -->
    <div class="relative z-10 w-full max-w-md px-4">
      <div class="bg-white/10 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/20 p-8">
        <!-- Header -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-24 h-24 bg-white rounded-2xl shadow-lg shadow-brand-500/30 mb-4 p-2">
            <img src="/logo.png" alt="Logo" class="w-full h-full object-contain" />
          </div>
          <h1 class="text-2xl font-bold text-white">Cisco NAMP</h1>
          <p class="text-sm text-gray-300 mt-1">Plateforme d'Automatisation et de Gestion Réseau</p>
        </div>

        <!-- Error message -->
        <div v-if="error" class="mb-4 p-3 bg-red-500/20 border border-red-500/40 rounded-lg text-red-200 text-sm flex items-center gap-2 animate-fade-in">
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ error }}
        </div>

        <!-- Login form -->
        <form @submit.prevent="handleLogin" class="space-y-5">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-200 mb-1.5">Adresse Email</label>
            <input id="email" v-model="email" type="email" required autocomplete="email"
                   class="w-full px-4 py-2.5 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
                   placeholder="admin@cisco-namp.local" />
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-200 mb-1.5">Mot de passe</label>
            <input id="password" v-model="password" type="password" required autocomplete="current-password"
                   class="w-full px-4 py-2.5 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all"
                   placeholder="••••••••" />
          </div>

          <div class="flex items-center">
            <input id="remember" v-model="remember" type="checkbox"
                   class="h-4 w-4 rounded border-gray-500 bg-white/10 text-brand-500 focus:ring-brand-500" />
            <label for="remember" class="ml-2 text-sm text-gray-300">Se souvenir de moi</label>
          </div>

          <button type="submit" :disabled="loading"
                  class="w-full py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-lg shadow-brand-500/25 hover:shadow-brand-500/40 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
            <svg v-if="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ loading ? 'Connexion en cours...' : 'Se Connecter' }}
          </button>
        </form>

        <!-- Footer -->
        <p class="text-center text-xs text-gray-400 mt-6">
          Plateforme d'Entreprise Interne · Accès Autorisé Uniquement
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const remember = ref(false)
const loading = ref(false)
const error = ref('')

async function handleLogin() {
  loading.value = true
  error.value = ''

  const success = await auth.login(email.value, password.value, remember.value)

  if (success) {
    router.push({ name: 'dashboard' })
  } else {
    error.value = auth.error || 'Identifiants invalides ou compte désactivé.'
  }

  loading.value = false
}
</script>

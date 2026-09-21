<template>
  <header class="flex items-center justify-between h-16 px-6 bg-white border-b border-gray-200 shadow-sm">
    <!-- Left: hamburger + breadcrumb -->
    <div class="flex items-center gap-4">
      <button @click="$emit('toggle-sidebar')"
              class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors lg:hidden">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <!-- Breadcrumb -->
      <nav class="flex items-center text-sm text-gray-500">
        <span class="font-medium text-gray-900">{{ pageTitle }}</span>
      </nav>
    </div>

    <!-- Right: user menu -->
    <div class="flex items-center gap-4">
      <div class="hidden sm:flex items-center gap-2 text-sm">
        <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-semibold text-xs">
          {{ initials }}
        </div>
        <div class="hidden md:block">
          <p class="font-medium text-gray-700 leading-none">{{ auth.userName }}</p>
          <p class="text-xs text-gray-400 mt-0.5">{{ auth.userRole }}</p>
        </div>
      </div>

      <button @click="handleLogout"
              class="p-1.5 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors"
              title="Logout">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

defineEmits(['toggle-sidebar'])

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const pageTitle = computed(() => route.meta.title || 'Dashboard')
const initials = computed(() => {
  const name = auth.userName || ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

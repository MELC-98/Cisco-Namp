<template>
  <div class="flex h-screen overflow-hidden bg-gray-50">
    <!-- Sidebar -->
    <Sidebar :collapsed="sidebarCollapsed" @toggle="sidebarCollapsed = !sidebarCollapsed" />

    <!-- Main content -->
    <div class="flex flex-1 flex-col overflow-hidden">
      <Topbar @toggle-sidebar="sidebarCollapsed = !sidebarCollapsed" />

      <main class="flex-1 overflow-y-auto p-6">
        <!-- Toast notifications -->
        <div class="fixed top-4 right-4 z-50 space-y-2" id="toast-container">
          <transition-group name="toast">
            <div v-for="toast in toasts" :key="toast.id"
                 :class="toastClass(toast.type)"
                 class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-sm font-medium animate-fade-in max-w-sm">
              <span>{{ toast.message }}</span>
              <button @click="removeToast(toast.id)" class="ml-auto opacity-70 hover:opacity-100">✕</button>
            </div>
          </transition-group>
        </div>

        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, provide, reactive } from 'vue'
import Sidebar from './Sidebar.vue'
import Topbar from './Topbar.vue'

const sidebarCollapsed = ref(false)

// Toast notification system
const toasts = reactive([])
let toastId = 0

function addToast(message, type = 'success', duration = 4000) {
  const id = ++toastId
  toasts.push({ id, message, type })
  setTimeout(() => removeToast(id), duration)
}

function removeToast(id) {
  const index = toasts.findIndex(t => t.id === id)
  if (index > -1) toasts.splice(index, 1)
}

function toastClass(type) {
  const classes = {
    success: 'bg-emerald-600 text-white',
    error: 'bg-red-600 text-white',
    warning: 'bg-amber-500 text-white',
    info: 'bg-blue-600 text-white',
  }
  return classes[type] || classes.info
}

// Provide toast function to all children
provide('toast', addToast)
</script>

<template>
  <aside :class="['flex flex-col bg-sidebar text-white transition-all duration-300 ease-in-out', collapsed ? 'w-16' : 'w-64']"
         class="relative z-30">
    <!-- Logo -->
    <div class="flex items-center h-16 px-4 border-b border-sidebar-border">
      <div class="flex items-center gap-3 overflow-hidden">
        <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center">
          <img src="/logo.png" alt="Logo" class="w-full h-full object-contain" />
        </div>
        <transition name="fade">
          <span v-if="!collapsed" class="text-sm font-bold tracking-wider whitespace-nowrap">CISCO NAMP</span>
        </transition>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 px-2 space-y-1">
      <!-- Dashboard -->
      <SidebarLink to="/dashboard" icon="dashboard" label="Tableau de bord" :collapsed="collapsed" />

      <!-- Infrastructure -->
      <SidebarSection label="Infrastructure" :collapsed="collapsed" v-if="can('view_devices')">
        <SidebarLink to="/devices" icon="devices" label="Tous les Équipements" :collapsed="collapsed" />
        <SidebarLink to="/topology" icon="devices" label="Topologie" :collapsed="collapsed" />
      </SidebarSection>

      <!-- Automation -->
      <SidebarSection label="Automatisation" :collapsed="collapsed" v-if="can('view_jobs')">
        <SidebarLink to="/automation/jobs" icon="jobs" label="Tâches" :collapsed="collapsed" />
      </SidebarSection>

      <!-- Configuration -->
      <SidebarSection label="Configuration" :collapsed="collapsed" v-if="can('view_backups')">
        <SidebarLink to="/backups" icon="backups" label="Sauvegardes" :collapsed="collapsed" />
      </SidebarSection>

      <!-- Administration -->
      <SidebarSection label="Administration" :collapsed="collapsed" v-if="can('manage_users') || can('view_audit_logs')">
        <SidebarLink to="/admin/users" icon="users" label="Utilisateurs" :collapsed="collapsed" v-if="can('manage_users')" />
        <SidebarLink to="/admin/audit-logs" icon="audit" label="Journaux d'Audit" :collapsed="collapsed" v-if="can('view_audit_logs')" />
      </SidebarSection>
    </nav>

    <!-- Collapse toggle -->
    <button @click="$emit('toggle')"
            class="flex items-center justify-center h-10 border-t border-sidebar-border hover:bg-sidebar-hover transition-colors">
      <svg :class="['w-5 h-5 transition-transform', collapsed ? 'rotate-180' : '']"
           fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
      </svg>
    </button>
  </aside>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth'
import SidebarLink from './SidebarLink.vue'
import SidebarSection from './SidebarSection.vue'

defineProps({ collapsed: Boolean })
defineEmits(['toggle'])

const auth = useAuthStore()
const can = (permission) => auth.hasPermission(permission)
</script>

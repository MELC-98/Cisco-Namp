<template>
  <div class="mt-6 mb-2">
    <transition name="fade">
      <p v-if="!collapsed" class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ label }}</p>
    </transition>
    <div class="mt-1 space-y-0.5">
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({ label: String, collapsed: Boolean })
</script>

<template>
  <div class="h-full flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Topologie Réseau</h1>
        <p class="text-sm text-gray-500 mt-1">Vue d'ensemble de l'infrastructure réseau (Simulation Packet Tracer)</p>
      </div>
      <div class="flex gap-3">
        <button @click="refreshTopology" class="btn btn-secondary flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Actualiser
        </button>
      </div>
    </div>
    
    <div class="flex-1 relative min-h-[600px]">
      <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white/80 z-10">
        <div class="animate-spin rounded-full h-12 w-12 border-4 border-brand-500 border-t-transparent"></div>
      </div>
      
      <div ref="networkContainer" class="w-full h-full absolute inset-0 bg-slate-50"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Network } from 'vis-network'
import { useDeviceStore } from '../../stores/devices'

const deviceStore = useDeviceStore()
const networkContainer = ref(null)
const loading = ref(true)
let network = null

onMounted(() => {
  fetchDevicesAndDraw()
})

onBeforeUnmount(() => {
  if (network) {
    network.destroy()
  }
})

const fetchDevicesAndDraw = async () => {
  loading.value = true
  try {
    await deviceStore.fetchDevices({ per_page: 100 }) // Fetch more to show in topology
    const devices = deviceStore.devices
    drawTopology(devices)
  } catch (error) {
    console.error('Erreur lors du chargement des équipements:', error)
  } finally {
    loading.value = false
  }
}

const refreshTopology = () => {
  if (network) {
    network.destroy()
  }
  fetchDevicesAndDraw()
}

const drawTopology = (devices) => {
  // 1. Prepare nodes
  const nodes = devices.map(device => {
    let iconShape = 'image'
    let iconUrl = ''
    
    if (device.category === 'router') {
      iconUrl = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(`
        <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="40" fill="#3b82f6" stroke="#1e3a8a" stroke-width="4"/>
          <path d="M 50 20 L 50 80 M 20 50 L 80 50" stroke="white" stroke-width="4" stroke-dasharray="8,8"/>
          <circle cx="50" cy="50" r="15" fill="#2563eb"/>
          <path d="M 50 35 L 50 20 M 50 65 L 50 80 M 35 50 L 20 50 M 65 50 L 80 50" stroke="white" stroke-width="6" stroke-linecap="round"/>
        </svg>`)
    } else if (device.category === 'switch') {
      iconUrl = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(`
        <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 100 100">
          <rect x="10" y="30" width="80" height="40" rx="5" fill="#10b981" stroke="#065f46" stroke-width="4"/>
          <path d="M 20 45 L 80 45 M 20 55 L 80 55" stroke="white" stroke-width="2"/>
          <path d="M 30 40 L 40 40 M 30 60 L 40 60 M 60 40 L 70 40 M 60 60 L 70 60" stroke="white" stroke-width="3" stroke-linecap="round"/>
        </svg>`)
    } else if (device.category === 'firewall') {
      iconUrl = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(`
        <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 100 100">
          <rect x="10" y="10" width="80" height="80" rx="10" fill="#ef4444" stroke="#7f1d1d" stroke-width="4"/>
          <path d="M 10 35 L 90 35 M 10 65 L 90 65 M 35 10 L 35 35 M 65 35 L 65 65 M 35 65 L 35 90" stroke="white" stroke-width="4"/>
        </svg>`)
    } else if (device.category === 'wireless') {
      iconUrl = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(`
        <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 100 100">
          <circle cx="50" cy="70" r="8" fill="#8b5cf6"/>
          <path d="M 30 50 Q 50 30 70 50 M 15 35 Q 50 0 85 35 M 40 60 Q 50 50 60 60" stroke="#8b5cf6" stroke-width="6" fill="none" stroke-linecap="round"/>
        </svg>`)
    } else {
      iconShape = 'dot'
    }

    return {
      id: device.id,
      label: device.hostname,
      title: `IP: ${device.management_ip}\nLieu: ${device.site?.name || 'Inconnu'}\nModèle: ${device.model}\nStatut: ${device.status}`,
      shape: iconShape,
      image: iconUrl || undefined,
      size: 30,
      font: { color: '#334155', face: 'Inter', size: 14, bold: true },
      color: {
        background: device.status === 'online' ? '#10b981' : '#ef4444',
        border: device.status === 'online' ? '#059669' : '#b91c1c'
      }
    }
  })

  // Add Internet Node
  nodes.push({
    id: 'internet',
    label: 'Internet\n(Maroc Telecom)',
    shape: 'image',
    image: 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(`
      <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 100 100">
        <path d="M 25 60 Q 10 60 15 45 Q 20 25 45 30 Q 55 10 75 25 Q 90 35 80 60 Z" fill="#94a3b8" stroke="#475569" stroke-width="3"/>
      </svg>`),
    size: 40,
    font: { color: '#334155', face: 'Inter', size: 14, bold: true }
  })

  // 2. Prepare edges
  const edges = []
  
  const firewalls = devices.filter(d => d.category === 'firewall')
  const routers = devices.filter(d => d.category === 'router') // Core
  const switches = devices.filter(d => d.category === 'switch')
  const wlcs = devices.filter(d => d.category === 'wireless' && d.hostname.includes('WLC'))
  const aps = devices.filter(d => d.category === 'wireless' && d.hostname.includes('AP'))

  // Internet -> Firewalls
  firewalls.forEach(fw => {
    edges.push({
      from: 'internet',
      to: fw.id,
      color: { color: '#64748b' },
      width: 3
    })
  })

  const core = routers[0]
  if (core) {
    // Firewalls -> Core
    firewalls.forEach(fw => {
      edges.push({
        from: fw.id,
        to: core.id,
        color: { color: '#ef4444' }, // Red for core link
        width: 3
      })
    })

    // Core -> Switches
    switches.forEach(sw => {
      edges.push({
        from: core.id,
        to: sw.id,
        color: { color: '#3b82f6' }, // Blue
        width: 2
      })
    })

    // Core -> WLCs
    wlcs.forEach(wlc => {
      edges.push({
        from: core.id,
        to: wlc.id,
        color: { color: '#8b5cf6' }, // Purple
        width: 2
      })
    })
  }

  // WLCs -> APs
  if (wlcs.length > 0) {
    aps.forEach((ap, idx) => {
      // Load balance between WLCs visually
      const wlc = wlcs[idx % wlcs.length]
      edges.push({
        from: wlc.id,
        to: ap.id,
        color: { color: '#10b981' }, // Green
        width: 2,
        dashes: true
      })
    })
  }

  const data = { nodes, edges }

  // 3. Network options
  const options = {
    physics: {
      stabilization: false,
      barnesHut: {
        gravitationalConstant: -20000,
        springConstant: 0.04,
        springLength: 150
      }
    },
    interaction: {
      hover: true,
      tooltipDelay: 200,
      zoomView: true,
      dragView: true
    },
    edges: {
      smooth: {
        type: 'continuous'
      }
    }
  }

  // 4. Initialize Network
  network = new Network(networkContainer.value, data, options)
}
</script>

<style scoped>
/* vis.js default canvas styles */
.vis-network {
  outline: none;
}
</style>

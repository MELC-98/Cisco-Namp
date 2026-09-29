<template>
  <div class="topology-root" :class="{ 'topology-fullscreen': isFullscreen }" ref="rootEl">
    <!-- Header bar -->
    <div class="topology-header">
      <div class="topology-header-left">
        <div class="topology-title-group">
          <svg class="topology-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <div>
            <h1 class="topology-title">Topologie Réseau</h1>
            <p class="topology-subtitle">FMP Tanger — Infrastructure Réseau Complète</p>
          </div>
        </div>
      </div>
      <div class="topology-header-right">
        <!-- Search -->
        <div class="topology-search-wrapper">
          <svg class="topology-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
          </svg>
          <input v-model="searchQuery" type="text" placeholder="Rechercher un équipement..."
                 class="topology-search" @input="onSearch" />
        </div>
        <!-- Toggle Labels -->
        <button @click="showInterfaceLabels = !showInterfaceLabels; redraw()"
                :class="['topology-btn', showInterfaceLabels ? 'topology-btn-active' : '']"
                title="Afficher/masquer les labels d'interfaces">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
          <span class="topology-btn-label">Labels</span>
        </button>
        <!-- Toggle IPs -->
        <button @click="showIPs = !showIPs; redraw()"
                :class="['topology-btn', showIPs ? 'topology-btn-active' : '']"
                title="Afficher/masquer les adresses IP">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
          <span class="topology-btn-label">IPs</span>
        </button>
        <!-- Refresh -->
        <button @click="refreshTopology" class="topology-btn" title="Actualiser">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="topology-btn-label">Actualiser</span>
        </button>
      </div>
    </div>

    <!-- Main canvas area -->
    <div class="topology-canvas-area">
      <!-- Loading overlay -->
      <div v-if="loading" class="topology-loading">
        <div class="topology-spinner"></div>
        <p>Chargement de la topologie...</p>
      </div>

      <!-- vis-network container -->
      <div ref="networkContainer" class="topology-network"></div>

      <!-- Zoom controls -->
      <div class="topology-zoom-controls">
        <button @click="zoomIn" class="topology-zoom-btn" title="Zoom +">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
        </button>
        <button @click="zoomOut" class="topology-zoom-btn" title="Zoom -">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/></svg>
        </button>
        <div class="topology-zoom-divider"></div>
        <button @click="fitNetwork" class="topology-zoom-btn" title="Ajuster à l'écran">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
        </button>
        <button @click="toggleFullscreen" class="topology-zoom-btn" :title="isFullscreen ? 'Quitter plein écran' : 'Plein écran'">
          <svg v-if="!isFullscreen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/></svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v3a2 2 0 01-2 2H3m18 0h-3a2 2 0 01-2-2V3m0 18v-3a2 2 0 012-2h3M3 16h3a2 2 0 012 2v3"/></svg>
        </button>
        <button @click="exportPNG" class="topology-zoom-btn" title="Exporter en PNG">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
        </button>
      </div>

      <!-- Legend panel -->
      <div class="topology-legend" :class="{ 'topology-legend-collapsed': legendCollapsed }">
        <button @click="legendCollapsed = !legendCollapsed" class="topology-legend-toggle">
          <span class="topology-legend-toggle-text">{{ legendCollapsed ? '▶' : '▼' }} Légende</span>
        </button>
        <div v-show="!legendCollapsed" class="topology-legend-content">
          <div class="topology-legend-section">
            <p class="topology-legend-title">Équipements</p>
            <div class="topology-legend-item"><span class="topology-legend-icon legend-router"></span> Routeur / Core</div>
            <div class="topology-legend-item"><span class="topology-legend-icon legend-switch"></span> Commutateur</div>
            <div class="topology-legend-item"><span class="topology-legend-icon legend-firewall"></span> Pare-feu</div>
            <div class="topology-legend-item"><span class="topology-legend-icon legend-wlc"></span> Contrôleur WiFi (WLC)</div>
            <div class="topology-legend-item"><span class="topology-legend-icon legend-ap"></span> Point d'Accès (AP)</div>
            <div class="topology-legend-item"><span class="topology-legend-icon legend-cloud"></span> Internet / Cloud</div>
          </div>
          <div class="topology-legend-section">
            <p class="topology-legend-title">Liens</p>
            <div class="topology-legend-item"><span class="topology-legend-line" style="background:#ef4444"></span> Fibre (Core)</div>
            <div class="topology-legend-item"><span class="topology-legend-line" style="background:#3b82f6"></span> Ethernet</div>
            <div class="topology-legend-item"><span class="topology-legend-line topology-legend-line-dashed" style="background:#22c55e"></span> Sans fil</div>
            <div class="topology-legend-item"><span class="topology-legend-line" style="background:#94a3b8"></span> WAN / Internet</div>
          </div>
          <div class="topology-legend-section">
            <p class="topology-legend-title">Statut</p>
            <div class="topology-legend-item"><span class="topology-legend-dot" style="background:#22c55e"></span> En ligne</div>
            <div class="topology-legend-item"><span class="topology-legend-dot" style="background:#ef4444"></span> Hors ligne</div>
            <div class="topology-legend-item"><span class="topology-legend-dot" style="background:#f59e0b"></span> Avertissement</div>
            <div class="topology-legend-item"><span class="topology-legend-dot" style="background:#6b7280"></span> Inconnu</div>
          </div>
        </div>
      </div>

      <!-- Stats bar -->
      <div class="topology-stats">
        <div class="topology-stat">
          <span class="topology-stat-dot" style="background:#22c55e"></span>
          <span>{{ onlineCount }} en ligne</span>
        </div>
        <div class="topology-stat">
          <span class="topology-stat-dot" style="background:#ef4444"></span>
          <span>{{ offlineCount }} hors ligne</span>
        </div>
        <div class="topology-stat-divider"></div>
        <span class="topology-stat-total">{{ totalDevices }} équipements · {{ totalLinks }} liens</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { Network } from 'vis-network'
import { DataSet } from 'vis-data'
import { useDeviceStore } from '../../stores/devices'

const deviceStore = useDeviceStore()
const networkContainer = ref(null)
const rootEl = ref(null)
const loading = ref(true)
const searchQuery = ref('')
const showInterfaceLabels = ref(true)
const showIPs = ref(false)
const isFullscreen = ref(false)
const legendCollapsed = ref(false)
let network = null
let nodesDataset = null
let edgesDataset = null
let allDevices = []

// Stats
const onlineCount = computed(() => allDevices.filter(d => d.status === 'online').length)
const offlineCount = computed(() => allDevices.filter(d => d.status !== 'online').length)
const totalDevices = computed(() => allDevices.length)
const totalLinks = ref(0)

// ─── SVG Icon Generators ─────────────────────────────────────
const ICON_SIZE = 48

function svgToDataUrl(svg) {
  return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg)
}

function routerIcon(status) {
  const color = status === 'online' ? '#2563eb' : '#9ca3af'
  const glow = status === 'online' ? '<circle cx="48" cy="48" r="44" fill="none" stroke="#3b82f6" stroke-width="2" opacity="0.3"/>' : ''
  return svgToDataUrl(`<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">
    ${glow}
    <circle cx="48" cy="48" r="36" fill="${color}" stroke="#1e3a8a" stroke-width="3"/>
    <circle cx="48" cy="48" r="12" fill="#1e40af"/>
    <line x1="48" y1="12" x2="48" y2="36" stroke="white" stroke-width="4" stroke-linecap="round"/>
    <line x1="48" y1="60" x2="48" y2="84" stroke="white" stroke-width="4" stroke-linecap="round"/>
    <line x1="12" y1="48" x2="36" y2="48" stroke="white" stroke-width="4" stroke-linecap="round"/>
    <line x1="60" y1="48" x2="84" y2="48" stroke="white" stroke-width="4" stroke-linecap="round"/>
    <polygon points="48,8 52,16 44,16" fill="white"/>
    <polygon points="48,88 52,80 44,80" fill="white"/>
    <polygon points="8,48 16,44 16,52" fill="white"/>
    <polygon points="88,48 80,44 80,52" fill="white"/>
  </svg>`)
}

function switchIcon(status) {
  const color = status === 'online' ? '#059669' : '#9ca3af'
  const glow = status === 'online' ? '<rect x="4" y="24" width="88" height="48" rx="8" fill="none" stroke="#10b981" stroke-width="2" opacity="0.3"/>' : ''
  return svgToDataUrl(`<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">
    ${glow}
    <rect x="8" y="28" width="80" height="40" rx="6" fill="${color}" stroke="#064e3b" stroke-width="3"/>
    <line x1="16" y1="42" x2="80" y2="42" stroke="white" stroke-width="1.5" opacity="0.5"/>
    <line x1="16" y1="54" x2="80" y2="54" stroke="white" stroke-width="1.5" opacity="0.5"/>
    <rect x="18" y="34" width="6" height="6" rx="1" fill="#a7f3d0"/>
    <rect x="28" y="34" width="6" height="6" rx="1" fill="#a7f3d0"/>
    <rect x="38" y="34" width="6" height="6" rx="1" fill="#a7f3d0"/>
    <rect x="48" y="34" width="6" height="6" rx="1" fill="#a7f3d0"/>
    <rect x="58" y="34" width="6" height="6" rx="1" fill="#6ee7b7"/>
    <rect x="68" y="34" width="6" height="6" rx="1" fill="#6ee7b7"/>
    <rect x="18" y="56" width="6" height="6" rx="1" fill="#a7f3d0"/>
    <rect x="28" y="56" width="6" height="6" rx="1" fill="#a7f3d0"/>
    <rect x="38" y="56" width="6" height="6" rx="1" fill="#a7f3d0"/>
    <rect x="48" y="56" width="6" height="6" rx="1" fill="#a7f3d0"/>
    <rect x="58" y="56" width="6" height="6" rx="1" fill="#6ee7b7"/>
    <rect x="68" y="56" width="6" height="6" rx="1" fill="#6ee7b7"/>
  </svg>`)
}

function firewallIcon(status) {
  const color = status === 'online' ? '#dc2626' : '#9ca3af'
  return svgToDataUrl(`<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">
    <rect x="12" y="12" width="72" height="72" rx="10" fill="${color}" stroke="#7f1d1d" stroke-width="3"/>
    <line x1="12" y1="36" x2="84" y2="36" stroke="white" stroke-width="3"/>
    <line x1="12" y1="60" x2="84" y2="60" stroke="white" stroke-width="3"/>
    <line x1="36" y1="12" x2="36" y2="36" stroke="white" stroke-width="3"/>
    <line x1="60" y1="36" x2="60" y2="60" stroke="white" stroke-width="3"/>
    <line x1="36" y1="60" x2="36" y2="84" stroke="white" stroke-width="3"/>
  </svg>`)
}

function wlcIcon(status) {
  const color = status === 'online' ? '#7c3aed' : '#9ca3af'
  return svgToDataUrl(`<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">
    <rect x="12" y="30" width="72" height="36" rx="6" fill="${color}" stroke="#4c1d95" stroke-width="3"/>
    <circle cx="48" cy="48" r="8" fill="white" opacity="0.9"/>
    <path d="M 30 22 Q 48 8 66 22" stroke="${color}" stroke-width="3" fill="none" stroke-linecap="round"/>
    <path d="M 36 26 Q 48 16 60 26" stroke="${color}" stroke-width="2.5" fill="none" stroke-linecap="round"/>
    <rect x="20" y="38" width="8" height="4" rx="1" fill="#c4b5fd"/>
    <rect x="32" y="38" width="8" height="4" rx="1" fill="#c4b5fd"/>
    <rect x="56" y="38" width="8" height="4" rx="1" fill="#c4b5fd"/>
    <rect x="68" y="38" width="8" height="4" rx="1" fill="#c4b5fd"/>
  </svg>`)
}

function apIcon(status) {
  const color = status === 'online' ? '#7c3aed' : '#9ca3af'
  return svgToDataUrl(`<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">
    <circle cx="48" cy="60" r="10" fill="${color}"/>
    <line x1="48" y1="50" x2="48" y2="36" stroke="${color}" stroke-width="3" stroke-linecap="round"/>
    <path d="M 28 30 Q 48 10 68 30" stroke="${color}" stroke-width="4" fill="none" stroke-linecap="round"/>
    <path d="M 34 36 Q 48 20 62 36" stroke="${color}" stroke-width="3" fill="none" stroke-linecap="round"/>
    <path d="M 40 42 Q 48 32 56 42" stroke="${color}" stroke-width="2.5" fill="none" stroke-linecap="round"/>
    <line x1="48" y1="70" x2="48" y2="86" stroke="${color}" stroke-width="3" stroke-linecap="round"/>
    <line x1="38" y1="86" x2="58" y2="86" stroke="${color}" stroke-width="3" stroke-linecap="round"/>
  </svg>`)
}

function cloudIcon() {
  return svgToDataUrl(`<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96">
    <path d="M 24 60 Q 8 60 12 45 Q 16 25 40 30 Q 48 10 68 22 Q 85 30 78 55 Q 76 62 68 62 Z"
          fill="#64748b" stroke="#334155" stroke-width="3"/>
    <circle cx="48" cy="44" r="10" fill="none" stroke="white" stroke-width="2"/>
    <line x1="48" y1="34" x2="48" y2="54" stroke="white" stroke-width="1.5"/>
    <path d="M 38 44 Q 48 36 58 44" stroke="white" stroke-width="1.5" fill="none"/>
    <path d="M 38 44 Q 48 52 58 44" stroke="white" stroke-width="1.5" fill="none"/>
  </svg>`)
}

// ─── Site Zone Colors ──────────────────────────────────────────
const SITE_COLORS = {
  'Internet': { bg: 'rgba(100,116,139,0.08)', border: '#94a3b8' },
  'Salle Serveur': { bg: 'rgba(59,130,246,0.08)', border: '#3b82f6' },
  'Bibliothèque': { bg: 'rgba(34,197,94,0.10)', border: '#22c55e' },
  'Administration': { bg: 'rgba(99,102,241,0.08)', border: '#6366f1' },
  'Centre de Formation': { bg: 'rgba(20,184,166,0.08)', border: '#14b8a6' },
  'Salle de Conférence': { bg: 'rgba(168,85,247,0.08)', border: '#a855f7' },
  'Départements': { bg: 'rgba(239,68,68,0.06)', border: '#ef4444' },
  'Amphis': { bg: 'rgba(59,130,246,0.06)', border: '#60a5fa' },
  'Centre Pédagogique': { bg: 'rgba(245,158,11,0.08)', border: '#f59e0b' },
  'Laboratoires de Recherche (Droite)': { bg: 'rgba(16,185,129,0.06)', border: '#10b981' },
  'Laboratoires de Recherche (Gauche)': { bg: 'rgba(14,165,233,0.06)', border: '#0ea5e9' },
  'L.S / L.G': { bg: 'rgba(249,115,22,0.06)', border: '#f97316' },
  'Inconnu': { bg: 'rgba(107,114,128,0.05)', border: '#6b7280' },
}

// ─── Fixed layout positions per site ────────────────────────────
// The layout is a 2D campus map – positions are in vis-network coordinates
const SITE_POSITIONS = {
  'Internet':                            { x: 0,    y: -500 },
  'Salle Serveur':                       { x: 0,    y: -200 },
  'Administration':                      { x: -350, y: 50 },
  'Bibliothèque':                        { x: 350,  y: 50 },
  'Centre de Formation':                 { x: -600, y: 300 },
  'Salle de Conférence':                 { x: -350, y: 300 },
  'Départements':                        { x: 0,    y: 300 },
  'Amphis':                              { x: 350,  y: 300 },
  'Centre Pédagogique':                  { x: 600,  y: 300 },
  'Laboratoires de Recherche (Droite)':  { x: -300, y: 550 },
  'Laboratoires de Recherche (Gauche)':  { x: 300,  y: 550 },
  'L.S / L.G':                           { x: 0,    y: 550 },
  'Inconnu':                             { x: 600,  y: 550 },
}

// Interface label mapping (simulated — based on typical Cisco port assignments)
function getInterfacePair(fromCategory, toCategory, index) {
  if (fromCategory === 'cloud' && toCategory === 'firewall') {
    return { from: 'WAN', to: `Gi0/${index}` }
  }
  if (fromCategory === 'firewall' && toCategory === 'router') {
    return { from: `Gi0/${index}`, to: `Gi0/${index}` }
  }
  if (fromCategory === 'router' && toCategory === 'switch') {
    return { from: `Fa0/${index + 1}`, to: 'Fa0/1' }
  }
  if (fromCategory === 'router' && toCategory === 'wireless') {
    return { from: `Fa0/${index + 8}`, to: 'Gi0/0' }
  }
  if (fromCategory === 'wireless' && toCategory === 'wireless') {
    return { from: 'CAPWAP', to: 'Radio0' }
  }
  return { from: `Fa0/${index}`, to: `Fa0/${index}` }
}

// ─── Main drawing logic ──────────────────────────────────────
onMounted(() => {
  fetchDevicesAndDraw()
})

onBeforeUnmount(() => {
  if (network) network.destroy()
})

const fetchDevicesAndDraw = async () => {
  loading.value = true
  try {
    await deviceStore.fetchDevices({ per_page: 200 })
    allDevices = deviceStore.devices
    drawTopology(allDevices)
  } catch (error) {
    console.error('Erreur chargement topologie:', error)
  } finally {
    loading.value = false
  }
}

const refreshTopology = () => {
  if (network) network.destroy()
  fetchDevicesAndDraw()
}

const redraw = () => {
  if (network) network.destroy()
  drawTopology(allDevices)
}

const drawTopology = (devices) => {
  // ── Prepare nodes ────────────────────────────────────────
  const nodes = []
  const edges = []

  // Group devices by site
  const siteGroups = {}
  devices.forEach(d => {
    const siteName = d.site?.name || 'Inconnu'
    if (!siteGroups[siteName]) siteGroups[siteName] = []
    siteGroups[siteName].push(d)
  })

  // Position devices within their site zone
  devices.forEach(d => {
    const siteName = d.site?.name || 'Inconnu'
    const sitePos = SITE_POSITIONS[siteName] || { x: 0, y: 0 }
    const siteDevices = siteGroups[siteName]
    const idx = siteDevices.indexOf(d)
    const cols = Math.ceil(Math.sqrt(siteDevices.length))
    const row = Math.floor(idx / cols)
    const col = idx % cols
    const spacing = 100
    const offsetX = (col - (cols - 1) / 2) * spacing
    const offsetY = row * spacing

    let image
    const cat = d.category || 'switch'
    if (cat === 'router') image = routerIcon(d.status)
    else if (cat === 'switch') image = switchIcon(d.status)
    else if (cat === 'firewall') image = firewallIcon(d.status)
    else if (cat === 'wireless' && d.hostname?.includes('WLC')) image = wlcIcon(d.status)
    else if (cat === 'wireless') image = apIcon(d.status)
    else image = switchIcon(d.status)

    const label = showIPs.value
      ? `${d.hostname}\n${d.management_ip}`
      : d.hostname

    nodes.push({
      id: d.id,
      label,
      title: buildTooltip(d),
      shape: 'image',
      image,
      size: cat === 'router' ? 32 : cat === 'firewall' ? 28 : cat === 'wireless' ? 24 : 26,
      x: sitePos.x + offsetX,
      y: sitePos.y + offsetY,
      fixed: { x: true, y: true },
      font: {
        color: '#1e293b',
        face: 'Inter, system-ui, sans-serif',
        size: 11,
        bold: { color: '#0f172a' },
        multi: 'html'
      },
      _category: cat,
      _site: siteName,
      _status: d.status,
    })
  })

  // Internet cloud node
  nodes.push({
    id: 'internet',
    label: showIPs.value ? 'Internet\n(Maroc Telecom)\nWAN' : 'Internet\n(Maroc Telecom)',
    title: '<div style="font-family:Inter,system-ui;padding:8px"><b>🌐 Internet</b><br/>Liaison Fibre Optique<br/>Fournisseur: Maroc Telecom</div>',
    shape: 'image',
    image: cloudIcon(),
    size: 38,
    x: SITE_POSITIONS['Internet'].x,
    y: SITE_POSITIONS['Internet'].y,
    fixed: { x: true, y: true },
    font: { color: '#334155', face: 'Inter, system-ui, sans-serif', size: 12 },
    _category: 'cloud',
    _site: 'Internet',
    _status: 'online',
  })

  // ── Prepare edges ────────────────────────────────────────
  const firewalls = devices.filter(d => d.category === 'firewall')
  const routers = devices.filter(d => d.category === 'router')
  const switches = devices.filter(d => d.category === 'switch')
  const wlcs = devices.filter(d => d.category === 'wireless' && d.hostname?.includes('WLC'))
  const aps = devices.filter(d => d.category === 'wireless' && !d.hostname?.includes('WLC'))

  let edgeIndex = 0

  // Internet → Firewalls
  firewalls.forEach((fw, i) => {
    const iface = getInterfacePair('cloud', 'firewall', i)
    edges.push({
      id: `e${edgeIndex++}`,
      from: 'internet',
      to: fw.id,
      color: { color: '#94a3b8', highlight: '#64748b' },
      width: 3,
      smooth: { type: 'curvedCW', roundness: i * 0.15 },
      label: showInterfaceLabels.value ? `${iface.from} ↔ ${iface.to}` : '',
      font: { size: 9, color: '#64748b', strokeWidth: 3, strokeColor: 'white', face: 'JetBrains Mono, monospace' },
    })
  })

  // Firewalls → Core Router(s)
  const core = routers[0]
  if (core) {
    firewalls.forEach((fw, i) => {
      const iface = getInterfacePair('firewall', 'router', i)
      edges.push({
        id: `e${edgeIndex++}`,
        from: fw.id,
        to: core.id,
        color: { color: '#ef4444', highlight: '#dc2626' },
        width: 3,
        smooth: { type: 'curvedCW', roundness: i * 0.1 },
        label: showInterfaceLabels.value ? `${iface.from} ↔ ${iface.to}` : '',
        font: { size: 9, color: '#b91c1c', strokeWidth: 3, strokeColor: 'white', face: 'JetBrains Mono, monospace' },
      })
    })

    // Core → Switches (grouped by site, one link per group to first switch, then chain)
    const switchesBySite = {}
    switches.forEach(sw => {
      const site = sw.site?.name || 'Inconnu'
      if (!switchesBySite[site]) switchesBySite[site] = []
      switchesBySite[site].push(sw)
    })

    let portIdx = 0
    Object.entries(switchesBySite).forEach(([site, sws]) => {
      // Core → first switch of each site
      const iface = getInterfacePair('router', 'switch', portIdx++)
      edges.push({
        id: `e${edgeIndex++}`,
        from: core.id,
        to: sws[0].id,
        color: { color: '#3b82f6', highlight: '#2563eb' },
        width: 2,
        smooth: { type: 'dynamic' },
        label: showInterfaceLabels.value ? `${iface.from} ↔ ${iface.to}` : '',
        font: { size: 8, color: '#1d4ed8', strokeWidth: 3, strokeColor: 'white', face: 'JetBrains Mono, monospace' },
      })

      // Chain switches within site (daisy chain)
      for (let i = 1; i < sws.length; i++) {
        edges.push({
          id: `e${edgeIndex++}`,
          from: sws[i - 1].id,
          to: sws[i].id,
          color: { color: '#93c5fd', highlight: '#60a5fa' },
          width: 1.5,
          smooth: { type: 'dynamic' },
          label: showInterfaceLabels.value ? `Fa0/${i} ↔ Fa0/1` : '',
          font: { size: 7, color: '#3b82f6', strokeWidth: 2, strokeColor: 'white', face: 'JetBrains Mono, monospace' },
        })
      }
    })

    // Core → WLCs
    wlcs.forEach((wlc, i) => {
      const iface = getInterfacePair('router', 'wireless', i)
      edges.push({
        id: `e${edgeIndex++}`,
        from: core.id,
        to: wlc.id,
        color: { color: '#8b5cf6', highlight: '#7c3aed' },
        width: 2,
        smooth: { type: 'dynamic' },
        label: showInterfaceLabels.value ? `${iface.from} ↔ ${iface.to}` : '',
        font: { size: 8, color: '#6d28d9', strokeWidth: 3, strokeColor: 'white', face: 'JetBrains Mono, monospace' },
      })
    })
  }

  // WLCs → APs
  if (wlcs.length > 0) {
    aps.forEach((ap, i) => {
      const wlc = wlcs[i % wlcs.length]
      const iface = getInterfacePair('wireless', 'wireless', i)
      edges.push({
        id: `e${edgeIndex++}`,
        from: wlc.id,
        to: ap.id,
        color: { color: '#22c55e', highlight: '#16a34a' },
        width: 1.5,
        dashes: [6, 4],
        smooth: { type: 'dynamic' },
        label: showInterfaceLabels.value ? `${iface.from}` : '',
        font: { size: 7, color: '#15803d', strokeWidth: 2, strokeColor: 'white', face: 'JetBrains Mono, monospace' },
      })
    })
  }

  totalLinks.value = edges.length

  // ── Create datasets ─────────────────────────────────────
  nodesDataset = new DataSet(nodes)
  edgesDataset = new DataSet(edges)

  // ── Network options ─────────────────────────────────────
  const options = {
    physics: { enabled: false },
    interaction: {
      hover: true,
      tooltipDelay: 150,
      zoomView: true,
      dragView: true,
      dragNodes: true,
      multiselect: true,
    },
    edges: {
      smooth: { type: 'dynamic' },
      hoverWidth: 2,
    },
    nodes: {
      borderWidth: 0,
      borderWidthSelected: 3,
      chosen: {
        node: function(values) {
          values.shadowSize = 15
          values.shadowColor = 'rgba(59,130,246,0.4)'
        }
      }
    },
  }

  // ── Initialize ─────────────────────────────────────────
  network = new Network(networkContainer.value, { nodes: nodesDataset, edges: edgesDataset }, options)

  // Draw site zone backgrounds
  network.on('beforeDrawing', (ctx) => {
    drawSiteZones(ctx, nodes)
  })

  // Click to navigate
  network.on('doubleClick', (params) => {
    if (params.nodes.length > 0 && params.nodes[0] !== 'internet') {
      // Could navigate to device detail
    }
  })

  // Fit after stabilization
  setTimeout(() => {
    network.fit({ animation: { duration: 800, easingFunction: 'easeInOutQuad' } })
  }, 200)
}

// ─── Draw colored site zones on canvas ───────────────────────
function drawSiteZones(ctx, nodes) {
  const grouped = {}
  nodes.forEach(n => {
    if (!n._site) return
    if (!grouped[n._site]) grouped[n._site] = []
    grouped[n._site].push(n)
  })

  Object.entries(grouped).forEach(([site, siteNodes]) => {
    const colors = SITE_COLORS[site] || SITE_COLORS['Inconnu']
    const padding = 60
    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity

    siteNodes.forEach(n => {
      const x = n.x ?? 0
      const y = n.y ?? 0
      if (x < minX) minX = x
      if (y < minY) minY = y
      if (x > maxX) maxX = x
      if (y > maxY) maxY = y
    })

    const width = Math.max(maxX - minX + padding * 2, 140)
    const height = Math.max(maxY - minY + padding * 2, 100)
    const rx = minX - padding
    const ry = minY - padding

    // Zone background
    ctx.save()
    ctx.fillStyle = colors.bg
    ctx.strokeStyle = colors.border
    ctx.lineWidth = 2
    ctx.setLineDash([])

    // Rounded rect
    const radius = 12
    ctx.beginPath()
    ctx.moveTo(rx + radius, ry)
    ctx.lineTo(rx + width - radius, ry)
    ctx.arcTo(rx + width, ry, rx + width, ry + radius, radius)
    ctx.lineTo(rx + width, ry + height - radius)
    ctx.arcTo(rx + width, ry + height, rx + width - radius, ry + height, radius)
    ctx.lineTo(rx + radius, ry + height)
    ctx.arcTo(rx, ry + height, rx, ry + height - radius, radius)
    ctx.lineTo(rx, ry + radius)
    ctx.arcTo(rx, ry, rx + radius, ry, radius)
    ctx.closePath()
    ctx.fill()
    ctx.stroke()

    // Zone label
    ctx.fillStyle = colors.border
    ctx.font = 'bold 13px Inter, system-ui, sans-serif'
    ctx.textAlign = 'left'
    ctx.textBaseline = 'top'
    ctx.fillText(site, rx + 12, ry + 8)
    ctx.restore()
  })
}

// ─── Tooltip builder ─────────────────────────────────────────
function buildTooltip(device) {
  const statusColor = device.status === 'online' ? '#22c55e' : '#ef4444'
  const statusText = device.status === 'online' ? '🟢 En ligne' : '🔴 Hors ligne'
  const cat = (device.category || '').replace(/^\w/, c => c.toUpperCase())
  return `<div style="font-family:Inter,system-ui,sans-serif;padding:10px 14px;min-width:200px;font-size:13px;line-height:1.6">
    <div style="font-weight:700;font-size:15px;margin-bottom:6px;color:#0f172a">${device.hostname}</div>
    <div style="display:grid;grid-template-columns:auto 1fr;gap:2px 10px">
      <span style="color:#64748b">IP:</span><span style="font-family:JetBrains Mono,monospace;font-size:12px">${device.management_ip}</span>
      <span style="color:#64748b">Type:</span><span>${cat}</span>
      <span style="color:#64748b">Modèle:</span><span>${device.model || '—'}</span>
      <span style="color:#64748b">OS:</span><span>${device.os_name || ''} ${device.os_version || '—'}</span>
      <span style="color:#64748b">Site:</span><span>${device.site?.name || '—'}</span>
      <span style="color:#64748b">Statut:</span><span style="color:${statusColor};font-weight:600">${statusText}</span>
    </div>
  </div>`
}

// ─── Controls ────────────────────────────────────────────────
function zoomIn() {
  if (!network) return
  const scale = network.getScale()
  network.moveTo({ scale: scale * 1.3, animation: { duration: 300, easingFunction: 'easeInOutQuad' } })
}

function zoomOut() {
  if (!network) return
  const scale = network.getScale()
  network.moveTo({ scale: scale * 0.7, animation: { duration: 300, easingFunction: 'easeInOutQuad' } })
}

function fitNetwork() {
  if (!network) return
  network.fit({ animation: { duration: 600, easingFunction: 'easeInOutQuad' } })
}

function toggleFullscreen() {
  isFullscreen.value = !isFullscreen.value
  setTimeout(() => {
    if (network) {
      network.redraw()
      network.fit({ animation: { duration: 400, easingFunction: 'easeInOutQuad' } })
    }
  }, 100)
}

function exportPNG() {
  if (!networkContainer.value) return
  const canvas = networkContainer.value.querySelector('canvas')
  if (!canvas) return
  const link = document.createElement('a')
  link.download = 'topologie-fmp-tanger.png'
  link.href = canvas.toDataURL('image/png')
  link.click()
}

function onSearch() {
  if (!network || !nodesDataset) return
  const q = searchQuery.value.toLowerCase().trim()
  if (!q) {
    // Reset all nodes
    nodesDataset.forEach(n => {
      nodesDataset.update({ id: n.id, opacity: 1.0 })
    })
    network.fit({ animation: { duration: 400 } })
    return
  }

  const matchingIds = []
  nodesDataset.forEach(n => {
    const label = (n.label || '').toLowerCase()
    if (label.includes(q)) {
      matchingIds.push(n.id)
      nodesDataset.update({ id: n.id, opacity: 1.0 })
    } else {
      nodesDataset.update({ id: n.id, opacity: 0.15 })
    }
  })

  if (matchingIds.length > 0) {
    network.focus(matchingIds[0], { scale: 1.2, animation: { duration: 500, easingFunction: 'easeInOutQuad' } })
  }
}
</script>

<style scoped>
/* ============================================================
   Topology View — Premium Cisco Packet Tracer Style
   ============================================================ */

.topology-root {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 4rem);
  background: #f8fafc;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  border: 1px solid #e2e8f0;
}

.topology-fullscreen {
  position: fixed !important;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  height: 100vh !important;
  z-index: 9999;
  border-radius: 0;
}

/* ── Header ──────────────────────────────────────────────── */
.topology-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 24px;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  border-bottom: 1px solid #334155;
  flex-wrap: wrap;
  gap: 12px;
}

.topology-header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.topology-header-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.topology-title-group {
  display: flex;
  align-items: center;
  gap: 12px;
}

.topology-title-icon {
  width: 32px;
  height: 32px;
  color: #60a5fa;
  flex-shrink: 0;
}

.topology-title {
  font-size: 18px;
  font-weight: 700;
  color: white;
  margin: 0;
  letter-spacing: -0.01em;
}

.topology-subtitle {
  font-size: 12px;
  color: #94a3b8;
  margin: 0;
}

/* ── Search ──────────────────────────────────────────────── */
.topology-search-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.topology-search-icon {
  position: absolute;
  left: 10px;
  width: 14px;
  height: 14px;
  color: #94a3b8;
  pointer-events: none;
}

.topology-search {
  padding: 6px 10px 6px 30px;
  border-radius: 8px;
  border: 1px solid #334155;
  background: #1e293b;
  color: #e2e8f0;
  font-size: 12px;
  width: 180px;
  transition: all 0.2s;
  font-family: Inter, system-ui, sans-serif;
}

.topology-search:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
  width: 220px;
}

.topology-search::placeholder {
  color: #64748b;
}

/* ── Buttons ─────────────────────────────────────────────── */
.topology-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border: 1px solid #334155;
  border-radius: 8px;
  background: #1e293b;
  color: #cbd5e1;
  font-size: 12px;
  font-family: Inter, system-ui, sans-serif;
  cursor: pointer;
  transition: all 0.15s;
}

.topology-btn:hover {
  background: #334155;
  color: white;
  border-color: #475569;
}

.topology-btn-active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.topology-btn-active:hover {
  background: #2563eb;
}

.topology-btn svg {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

.topology-btn-label {
  white-space: nowrap;
}

@media (max-width: 768px) {
  .topology-btn-label { display: none; }
  .topology-search { width: 130px; }
}

/* ── Canvas area ─────────────────────────────────────────── */
.topology-canvas-area {
  flex: 1;
  position: relative;
  overflow: hidden;
  background:
    radial-gradient(circle at 50% 50%, rgba(59,130,246,0.03) 0%, transparent 60%),
    linear-gradient(rgba(226,232,240,0.3) 1px, transparent 1px),
    linear-gradient(90deg, rgba(226,232,240,0.3) 1px, transparent 1px);
  background-size: 100% 100%, 40px 40px, 40px 40px;
  background-color: #f8fafc;
}

.topology-network {
  width: 100%;
  height: 100%;
  position: absolute;
  inset: 0;
}

.topology-network :deep(.vis-network) {
  outline: none;
}

/* ── Loading ─────────────────────────────────────────────── */
.topology-loading {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(248,250,252,0.9);
  backdrop-filter: blur(4px);
  z-index: 20;
  gap: 16px;
}

.topology-loading p {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.topology-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ── Zoom controls ───────────────────────────────────────── */
.topology-zoom-controls {
  position: absolute;
  top: 16px;
  right: 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  background: white;
  border-radius: 12px;
  padding: 6px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.06);
  border: 1px solid #e2e8f0;
  z-index: 10;
}

.topology-zoom-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s;
}

.topology-zoom-btn:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.topology-zoom-btn svg {
  width: 16px;
  height: 16px;
}

.topology-zoom-divider {
  height: 1px;
  background: #e2e8f0;
  margin: 2px 4px;
}

/* ── Legend ───────────────────────────────────────────────── */
.topology-legend {
  position: absolute;
  bottom: 56px;
  right: 16px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08), 0 1px 3px rgba(0,0,0,0.04);
  border: 1px solid #e2e8f0;
  z-index: 10;
  min-width: 180px;
  transition: all 0.2s;
}

.topology-legend-collapsed {
  min-width: auto;
}

.topology-legend-toggle {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 10px 14px;
  border: none;
  background: transparent;
  cursor: pointer;
  font-family: Inter, system-ui, sans-serif;
}

.topology-legend-toggle-text {
  font-size: 12px;
  font-weight: 600;
  color: #334155;
}

.topology-legend-content {
  padding: 0 14px 14px;
}

.topology-legend-section {
  margin-bottom: 10px;
}

.topology-legend-section:last-child {
  margin-bottom: 0;
}

.topology-legend-title {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #94a3b8;
  margin: 0 0 6px;
}

.topology-legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  color: #475569;
  padding: 2px 0;
}

.topology-legend-icon {
  width: 16px;
  height: 16px;
  border-radius: 3px;
  flex-shrink: 0;
}

.legend-router {
  background: #3b82f6;
  border-radius: 50%;
}

.legend-switch {
  background: #10b981;
  border-radius: 3px;
}

.legend-firewall {
  background: #ef4444;
  border-radius: 3px;
}

.legend-wlc {
  background: #8b5cf6;
  border-radius: 3px;
}

.legend-ap {
  background: #a78bfa;
  border-radius: 50%;
}

.legend-cloud {
  background: #94a3b8;
  border-radius: 50%;
}

.topology-legend-line {
  width: 20px;
  height: 3px;
  border-radius: 2px;
  flex-shrink: 0;
}

.topology-legend-line-dashed {
  background: repeating-linear-gradient(
    90deg,
    currentColor 0px,
    currentColor 4px,
    transparent 4px,
    transparent 7px
  ) !important;
}

.topology-legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

/* ── Stats bar ───────────────────────────────────────────── */
.topology-stats {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  padding: 10px 20px;
  background: rgba(255,255,255,0.95);
  backdrop-filter: blur(8px);
  border-top: 1px solid #e2e8f0;
  gap: 16px;
  z-index: 10;
}

.topology-stat {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #475569;
  font-weight: 500;
}

.topology-stat-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.topology-stat-divider {
  width: 1px;
  height: 16px;
  background: #e2e8f0;
}

.topology-stat-total {
  font-size: 12px;
  color: #94a3b8;
}

/* ── Tooltip overrides ───────────────────────────────────── */
:deep(.vis-tooltip) {
  background: white !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 12px !important;
  box-shadow: 0 10px 30px rgba(0,0,0,0.12), 0 4px 8px rgba(0,0,0,0.06) !important;
  padding: 0 !important;
  font-family: Inter, system-ui, sans-serif !important;
  max-width: 320px !important;
}
</style>

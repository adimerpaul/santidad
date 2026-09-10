<template>
  <q-page class="player-page">
    <!-- ===== REPRODUCTOR MULTIMEDIA DE FONDO ===== -->
    <div class="media-container">
      <transition name="fade">
        <div
          v-if="playlist.length > 0 && currentAd"
          :key="localMediaId(currentAd)"
          class="fullscreen-media-wrapper"
        >
          <!-- Video Player -->
          <video
            v-if="currentAd.type === 'video'"
            ref="videoPlayer"
            class="fullscreen-media"
            :src="currentAd.url"
            :data-media-id="localMediaId(currentAd)"
            @loadedmetadata="alignVideo"
            autoplay
            muted
            playsinline
            @ended="nextAd"
            @error="onVideoError"
          ></video>

          <!-- Image Player -->
          <img v-else class="fullscreen-media" :src="currentAd.url" alt="Publicidad" />
        </div>

        <!-- Pantalla por defecto si no hay anuncios -->
        <div v-else class="default-bg flex flex-center">
          <div class="text-center">
            <q-icon name="local_pharmacy" size="100px" color="white" class="pharmacy-pulse" />
            <div class="text-h3 text-white q-mt-md font-weight-bold">SANTIDAD-DIVINA S.R.L.</div>
            <div class="text-subtitle1 text-grey q-mt-sm">FARMACIA</div>
          </div>
        </div>
      </transition>
    </div>

    <!-- Pre-carga silenciosa del próximo video para evitar retraso de carga -->
    <video v-if="nextAdUrl" style="display: none" :src="nextAdUrl" preload="auto" muted></video>

    <!-- ===== SUPERPOSICIÓN DE VERIFICACIÓN DE CLIENTE (PRIMER PLANO) ===== -->
    <div class="overlay-container" v-if="clientData.visible || showThanks || qrData.visible">
      <!-- Pantalla de Gracias -->
      <div class="thanks-screen" v-if="showThanks">
        <div class="thanks-check q-mx-auto">
          <svg viewBox="0 0 52 52" class="check-svg">
            <circle class="check-circle" cx="26" cy="26" r="25" fill="none" />
            <path class="check-path" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
          </svg>
        </div>
        <div class="thanks-title">¡Gracias por su compra!</div>
        <div class="thanks-sub">Farmacia SANTIDAD-DIVINA S.R.L. le desea un excelente día</div>
        <div class="thanks-dots">
          <span
            class="dot"
            v-for="n in 3"
            :key="n"
            :style="{ animationDelay: n * 0.2 + 's' }"
          ></span>
        </div>
      </div>

      <!-- Pantalla de Pago con QR (Baneco) -->
      <div class="client-card qr-card" v-else-if="qrData.visible">
        <div class="display-header q-mb-md">
          <div class="pharmacy-logo">
            <q-icon name="local_pharmacy" size="38px" color="white" />
          </div>
          <div class="pharmacy-info">
            <div class="pharmacy-name">SANTIDAD-DIVINA S.R.L.</div>
            <div class="pharmacy-tag">FARMACIA</div>
          </div>
        </div>

        <div class="card-title-container q-mb-sm">
          <q-icon name="qr_code_2" size="22px" style="color: #1b3a5c" />
          <span class="card-title">Pago con Código QR</span>
        </div>

        <div class="card-line q-mb-md"></div>

        <div class="qr-payment-body">
          <img :src="qrData.qrImage" alt="Código QR de pago" class="qr-payment-image" />
          <div class="qr-payment-amount">Bs. {{ qrData.monto }}</div>
          <div class="qr-payment-hint">Escanee el código con su aplicación bancaria para pagar</div>
        </div>

        <div class="display-footer q-mt-lg">
          <div class="footer-left">
            <q-icon name="verified" size="14px" />
            Esperando confirmación de pago...
          </div>
          <div class="footer-right">{{ currentTime }}</div>
        </div>
      </div>

      <!-- Pantalla de Datos del Cliente -->
      <div class="client-card" v-else>
        <!-- Header -->
        <div class="display-header q-mb-md">
          <div class="pharmacy-logo">
            <q-icon name="local_pharmacy" size="38px" color="white" />
          </div>
          <div class="pharmacy-info">
            <div class="pharmacy-name">SANTIDAD-DIVINA S.R.L.</div>
            <div class="pharmacy-tag">FARMACIA</div>
          </div>
        </div>

        <div class="card-title-container q-mb-sm">
          <q-icon name="person_outline" size="22px" style="color: #1b3a5c" />
          <span class="card-title">Datos de Facturación</span>
          <div class="status-badge" :class="{ active: hasClientData }">
            <div class="status-dot"></div>
            {{ hasClientData ? 'Verificando' : 'Esperando' }}
          </div>
        </div>

        <div class="card-line q-mb-md"></div>

        <div class="data-list" v-if="hasClientData">
          <div class="data-row" v-if="clientData.tipoDocumento">
            <div class="row-icon"><q-icon name="description" size="20px" /></div>
            <div class="row-body">
              <div class="row-label">Tipo de Documento</div>
              <div class="row-value">{{ clientData.tipoDocumento }}</div>
            </div>
          </div>

          <div class="data-row">
            <div class="row-icon accent"><q-icon name="fingerprint" size="20px" /></div>
            <div class="row-body">
              <div class="row-label">NIT / Carnet de Identidad</div>
              <div class="row-value bold">{{ clientData.numeroDocumento || '—' }}</div>
            </div>
          </div>

          <div class="data-row" v-if="clientData.complemento">
            <div class="row-icon"><q-icon name="add_circle_outline" size="20px" /></div>
            <div class="row-body">
              <div class="row-label">Complemento</div>
              <div class="row-value">{{ clientData.complemento }}</div>
            </div>
          </div>

          <div class="data-row">
            <div class="row-icon accent"><q-icon name="store" size="20px" /></div>
            <div class="row-body">
              <div class="row-label">Nombre / Razón Social</div>
              <div class="row-value bold">{{ clientData.nombreRazonSocial || '—' }}</div>
            </div>
          </div>

          <div class="data-row" v-if="clientData.email">
            <div class="row-icon"><q-icon name="alternate_email" size="20px" /></div>
            <div class="row-body">
              <div class="row-label">Correo Electrónico</div>
              <div class="row-value">{{ clientData.email }}</div>
            </div>
          </div>
        </div>

        <!-- Estado de espera local -->
        <div class="waiting-state" v-else>
          <q-icon name="medical_information" size="64px" class="waiting-icon" />
          <div class="waiting-text">Confirmando datos en caja...</div>
          <div class="waiting-sub">Los datos de facturación aparecerán aquí para su validación</div>
        </div>

        <!-- Footer -->
        <div class="display-footer q-mt-lg">
          <div class="footer-left">
            <q-icon name="verified" size="14px" />
            Por favor, confirme que sus datos estén correctos con el cajero
          </div>
          <div class="footer-right">{{ currentTime }}</div>
        </div>
      </div>
    </div>

    <div v-if="!playlist.length && !isDownloading" class="flex flex-center" style="height: 100vh">
      <h1 class="text-white text-h2 text-weight-bold">Esperando Publicidad...</h1>
    </div>

    <!-- Indicador de Descarga -->
    <div
      v-if="isDownloading"
      class="absolute-top-right q-pa-md text-white"
      style="z-index: 1000; background: rgba(0, 0, 0, 0.5); border-radius: 0 0 0 10px"
    >
      <q-spinner-dots size="2rem" color="primary" />
      <span class="q-ml-sm">Descargando medios...</span>
    </div>

    <!-- ===== CONFIGURACIÓN / LOGIN INICIAL (MODAL) ===== -->
    <q-dialog v-model="showConfigModal" persistent transition-show="scale" transition-hide="scale">
      <q-card class="config-card q-pa-lg">
        <q-card-section class="text-center">
          <q-icon name="settings" size="48px" color="primary" class="q-mb-sm" />
          <div class="text-h6 text-primary text-bold">Configuración del Terminal PC</div>
          <div class="text-caption text-grey">
            Establezca los parámetros de red para el reproductor
          </div>
        </q-card-section>

        <q-card-section class="q-gutter-md">
          <!-- IP Laravel Server -->
          <q-input
            v-model="config.serverIp"
            label="Servidor Laravel (Laragon)"
            placeholder="http://192.168.100.2:8000"
            filled
            dense
            hint="Ejemplo: http://192.168.100.2:8000"
          />

          <!-- IP Sockets Server -->
          <q-input
            v-model="config.socketIp"
            label="Servidor de WebSockets (Sockets)"
            placeholder="http://192.168.100.2:3000"
            filled
            dense
            hint="Ejemplo: http://192.168.100.2:3000"
          />

          <!-- Cargar Agencias -->
          <div class="row items-center q-gutter-sm">
            <q-btn
              label="Conectar y Cargar Sucursales"
              color="secondary"
              dense
              flat
              icon="sync"
              @click="fetchAgencias"
              class="col-12"
            />
          </div>

          <!-- Agencia selector -->
          <q-select
            v-model="config.agencia"
            :options="agencias"
            option-value="id"
            option-label="nombre"
            label="Seleccionar Sucursal / Agencia *"
            filled
            dense
            emit-value
            map-options
            :loading="loadingAgencias"
            hint="Obligatorio: Identifica en qué sucursal se encuentra esta pantalla"
            :rules="[val => !!val || 'Debe seleccionar una sucursal']"
          />

          <!-- Caja / Terminal selector -->
          <q-select
            v-model="config.caja"
            :options="cajasOptions"
            emit-value
            map-options
            label="Número de Caja / Terminal *"
            filled
            dense
            hint="Caja a la que pertenece esta pantalla (Caja 1, Caja 2, etc.)"
            class="q-mt-sm"
          />
        </q-card-section>

        <q-card-actions align="right" class="q-mt-md">
          <q-btn
            label="Guardar y Ejecutar"
            color="primary"
            :disable="!config.agencia || !config.serverIp || !config.socketIp"
            @click="saveConfiguration"
            class="full-width"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Botón flotante invisible en esquina superior izquierda para re-configurar (hover) o presionando Esc -->
    <div class="settings-gear" @click="openConfig">
      <q-btn round flat icon="settings" color="white" size="sm" />
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { io } from 'socket.io-client'
import { ServerClock, playbackPosition, localMediaId } from '../utils/adSync.mjs'
import { fetchJson, measureVideo } from '../utils/adMedia.mjs'

// Configuración general
const configured = ref(false)
const showConfigModal = ref(false)
const loadingAgencias = ref(false)

const config = ref({
  serverIp: process.env.SERVER_IP || 'http://192.168.100.2:8000',
  socketIp: process.env.SOCKET_IP || 'http://192.168.100.2:3000',
  agencia: null,
  caja: 1,
})

const cajasOptions = [
  { label: 'Caja 1', value: 1 },
  { label: 'Caja 2', value: 2 },
  { label: 'Caja 3', value: 3 },
  { label: 'Caja 4', value: 4 },
]

const agencias = ref([])

// Datos de publicidad
const playlist = ref([])
const currentIndex = ref(0)
const imageTimer = ref(null)
const videoPlayer = ref(null)
const serverClock = new ServerClock()
let syncManifest = null
let playlistBusy = false
let playlistPoll = null
let syncTimer = null
let destroyed = false
const scopeKey = () => config.value.serverIp + '|' + config.value.agencia
let activeScope = null
let legacyCacheAllowed = true
const cacheKey = () => 'pcpubli_sync_cache:' + scopeKey()

// Datos del cliente en caja
const clientData = ref({
  numeroDocumento: '',
  complemento: '',
  nombreRazonSocial: '',
  email: '',
  tipoDocumento: '',
  visible: false,
})

// Datos del QR de pago (Baneco) generado en caja
const qrData = ref({
  qrImage: '',
  monto: '',
  visible: false,
})
const showThanks = ref(false)
const isDownloading = ref(false)

// Reloj y Socket
const currentTime = ref('')
let clockInterval = null
let socketConn = null
let watchdogTimer = null
let heartbeatInterval = null

const currentAd = computed(() => {
  if (playlist.value.length === 0) return null
  return playlist.value[currentIndex.value]
})

const nextAdUrl = computed(() => {
  if (playlist.value.length <= 1) return null
  const nextIndex = (currentIndex.value + 1) % playlist.value.length
  const nextAd = playlist.value[nextIndex]
  if (nextAd && nextAd.type === 'video') {
    return nextAd.url
  }
  return null
})

const hasClientData = computed(() => {
  return (
    clientData.value.numeroDocumento &&
    clientData.value.numeroDocumento !== '' &&
    clientData.value.numeroDocumento !== '0'
  )
})

// === MÉTODOS ===

// Hora de Bolivia
function updateTime() {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('es-BO', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

// Cargar agencias desde el backend Laravel
async function fetchAgencias() {
  if (!config.value.serverIp) return
  loadingAgencias.value = true
  try {
    const cleanIp = config.value.serverIp.replace(/\/$/, '')
    const response = await fetch(`${cleanIp}/api/sucursales`)
    if (response.ok) {
      const data = await response.json()
      agencias.value = data
    }
  } catch (error) {
    console.error('Error fetching sucursales:', error)
  } finally {
    loadingAgencias.value = false
  }
}

// Cargar datos locales de localStorage
function loadLocalConfig() {
  const savedServer = localStorage.getItem('pcpubli_server_ip')
  const savedSocket = localStorage.getItem('pcpubli_socket_ip')
  const savedAgencia = localStorage.getItem('pcpubli_agencia_id')
  const savedCaja = localStorage.getItem('pcpubli_caja_numero')

  if (savedServer && savedSocket && savedAgencia && savedCaja) {
    config.value.serverIp = savedServer
    config.value.socketIp = savedSocket
    config.value.agencia = savedAgencia ? parseInt(savedAgencia, 10) : null
    config.value.caja = parseInt(savedCaja, 10)
    configured.value = true
    return true
  }
  return false
}

// Guardar y aplicar configuración
function saveConfiguration() {
  if (!config.value.serverIp || !config.value.socketIp || !config.value.agencia || !config.value.caja) return

  localStorage.setItem('pcpubli_server_ip', config.value.serverIp)
  localStorage.setItem('pcpubli_socket_ip', config.value.socketIp)
  if (config.value.agencia) {
    localStorage.setItem('pcpubli_agencia_id', config.value.agencia.toString())
  } else {
    localStorage.removeItem('pcpubli_agencia_id')
  }
  localStorage.setItem('pcpubli_caja_numero', (config.value.caja || 1).toString())

  configured.value = true
  showConfigModal.value = false

  // Inicializar todo
  initSocket()
  fetchPlaylist()
}

function openConfig() {
  fetchAgencias()
  showConfigModal.value = true
}

// The legacy endpoint stays available during a rolling backend/app update.
async function readSyncManifest(base, scope) {
  const start = performance.now()
  const manifest = await fetchJson(base + '/api/publicidad-sync?agencia_id=' + config.value.agencia)
  const end = performance.now()
  if (manifest.protocol !== 1 || !Array.isArray(manifest.items) || String(manifest.agencia_id) !== String(config.value.agencia)) {
    throw new Error('Invalid advertising manifest')
  }
  if (scope !== scopeKey() || destroyed) throw new Error('Configuration changed')
  serverClock.sample(manifest.server_time_ms, start, end)
  return manifest
}

async function fetchPlaylist() {
  if (!configured.value || showConfigModal.value || destroyed) return
  const scope = scopeKey()
  const base = config.value.serverIp.replace(/\/$/, '')
  if (activeScope !== scope) {
    activeScope = scope
    syncManifest = null
    serverClock.samples = []
    playlist.value = []
    clearImageTimer()
    loadCachedPlaylist()
  }
  if (playlistBusy) return
  playlistBusy = true
  try {
    let manifest = null
    let data
    try {
      manifest = await readSyncManifest(base, scope)
      data = manifest.items
    } catch (error) {
      // Once synchronized, a temporary outage must not replace the shared cycle.
      if (syncManifest) throw error
      data = await fetchJson(base + '/api/publicidad-actual?agencia_id=' + config.value.agencia)
      if (!Array.isArray(data)) data = []
    }
    if (scope !== scopeKey() || destroyed) return
    const changed = data.map(localMediaId).join(',') !== playlist.value.map(localMediaId).join(',')
    const mediaReady = !window.mediaAPI || (await Promise.all(data.map(ad => window.mediaAPI.exists(localMediaId(ad))))).every(Boolean)
    if (!changed && mediaReady && (!manifest || manifest.version === syncManifest?.version)) {
      syncPlayback()
      return
    }
    isDownloading.value = changed && data.length > 0
    const prepared = []
    const durations = []
    for (const ad of data) {
      if (scope !== scopeKey() || destroyed) return
      const item = { ...ad }
      const id = localMediaId(item)
      if (window.mediaAPI) {
        const result = await window.mediaAPI.download(id, item.type, item.url)
        if (!result?.success) throw new Error(result?.error || 'Media download failed')
        item.url = 'localmedia://' + id.replace(/[\/\\]/g, '_')
      }
      if (manifest && item.type === 'video' && !item.duration_ms) {
        durations.push({ id: item.id, media_version: item.media_version, duration_ms: await measureVideo(item.url) })
      }
      prepared.push(item)
    }
    if (durations.length) {
      if (scope !== scopeKey() || destroyed) return
      for (let i = 0; i < durations.length; i += 100) {
        await fetchJson(base + '/api/publicidad-sync/durations', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ agencia_id: Number(config.value.agencia), items: durations.slice(i, i + 100) }),
        })
      }
      const refreshed = await readSyncManifest(base, scope)
      if (refreshed.items.map(localMediaId).join(',') !== data.map(localMediaId).join(',')) return
      manifest = refreshed
      prepared.forEach((ad, i) => { ad.duration_ms = manifest.items[i].duration_ms })
    }
    if (scope !== scopeKey() || destroyed || (manifest && !manifest.ready)) return
    syncManifest = manifest
    playlist.value = prepared
    currentIndex.value = 0
    localStorage.setItem(cacheKey(), JSON.stringify({ items: prepared, manifest }))
    playCurrentAd()
    if (window.mediaAPI) await window.mediaAPI.cleanup(prepared.map(localMediaId))
  } catch (error) {
    console.error('Advertising update failed; keeping local playback:', error)
    if (!playlist.value.length) loadCachedPlaylist()
  } finally {
    playlistBusy = false
    isDownloading.value = false
    if (!destroyed && scope !== scopeKey()) fetchPlaylist()
  }
}

function loadCachedPlaylist() {
  try {
    let cached = JSON.parse(localStorage.getItem(cacheKey()) || 'null')
    if (!cached && legacyCacheAllowed) {
      const old = JSON.parse(localStorage.getItem('pcpubli_playlist_cache') || 'null')
      if (Array.isArray(old)) {
        cached = { manifest: null, items: old.map(ad => ({ ...ad,
          url: window.mediaAPI ? 'localmedia://' + ad.file_id.replace(/[\/\\]/g, '_') : ad.url,
        })) }
        localStorage.setItem(cacheKey(), JSON.stringify(cached))
      }
    }
    legacyCacheAllowed = false
    if (!cached?.items?.length) return
    playlist.value = cached.items
    syncManifest = cached.manifest
    currentIndex.value = 0
    // Without a fresh server sample, offline startup uses sequential playback.
    // It joins the shared timeline as soon as the connection returns.
    playCurrentAd()
  } catch (error) {
    console.error('Cannot restore advertising cache:', error)
  }
}

function targetPosition() {
  return playbackPosition(syncManifest, serverClock.time)
}

function alignVideo() {
  const video = videoPlayer.value
  if (!video || !currentAd.value || video.dataset.mediaId !== localMediaId(currentAd.value) || video.readyState < 1) return
  const target = targetPosition()
  if (target && target.index === currentIndex.value && Number.isFinite(video.duration)) {
    const seconds = Math.min(target.offsetMs / 1000, Math.max(0, video.duration - 0.05))
    if (Math.abs(video.currentTime - seconds) > 0.4) video.currentTime = seconds
  }
  if (video.paused && !video.error) video.play().catch(() => {})
}

function syncPlayback() {
  const target = targetPosition()
  if (!target || !playlist.value.length) return
  if (currentIndex.value !== target.index) {
    currentIndex.value = target.index
    playCurrentAd()
  } else if (currentAd.value?.type === 'video') {
    alignVideo()
  }
}

function playCurrentAd() {
  clearImageTimer()
  if (!playlist.value.length) return
  const target = targetPosition()
  if (target) currentIndex.value = target.index
  const ad = currentAd.value
  if (!ad) return
  if (ad.type === 'video') {
    nextTick(() => {
      const video = videoPlayer.value
      // A single-item loop reuses its element; other items initialize on metadata.
      if (!target && video?.dataset.mediaId === localMediaId(ad) && video.ended) video.currentTime = 0
      alignVideo()
    })
  } else {
    imageTimer.value = setTimeout(nextAd, Math.max(20, target?.remainingMs ?? 10000))
  }
}

function nextAd() {
  if (!playlist.value.length) return
  if (targetPosition()) {
    playCurrentAd()
  } else {
    currentIndex.value = (currentIndex.value + 1) % playlist.value.length
    playCurrentAd()
  }
}

function clearImageTimer() {
  if (imageTimer.value) clearTimeout(imageTimer.value)
  imageTimer.value = null
}

function onVideoError(e) {
  console.error('Video error playing ad:', e)
  if (socketConn?.connected && currentAd.value) {
    socketConn.emit('terminal_error', {
      error_type: 'video_playback_failed', ad_name: currentAd.value.name,
      file_id: currentAd.value.file_id, url: currentAd.value.url,
      agencia_id: config.value.agencia,
      message: 'No se pudo reproducir: ' + currentAd.value.name,
    })
  }
  // A failed video must not advance this terminal ahead of the common cycle.
  if (!targetPosition()) nextAd()
}

// Verificar si un evento de cobro/QR va dirigido estrictamente a esta pantalla y caja
function isTargetMe(data) {
  // Debe ser un objeto válido (descarta strings de caché antiguo)
  if (!data || typeof data !== 'object') return false

  // Esta terminal DEBE tener sucursal y caja configuradas
  if (!config.value.agencia || !config.value.caja) return false

  // El evento DEBE incluir obligatoriamente agencia_id y caja
  if (data.agencia_id == null || data.caja == null) return false

  // Deben coincidir exactamente la sucursal y la caja
  const matchAgencia = Number(data.agencia_id) === Number(config.value.agencia)
  const matchCaja = Number(data.caja) === Number(config.value.caja)

  return matchAgencia && matchCaja
}

// Configurar WebSockets
function initSocket() {
  if (socketConn) {
    socketConn.disconnect()
  }

  const cleanSocketUrl = config.value.socketIp.replace(/\/$/, '')
  socketConn = io(cleanSocketUrl)

  socketConn.on('connect', () => {
    console.log('Socket conectado con éxito:', socketConn.id)
    registerTerminalRoom()
    sendStatusHeartbeat()
    fetchPlaylist()
  })

  // Escuchar actualizaciones de publicidad
  socketConn.on('new_publicidad', () => {
    console.log('Nueva publicidad recibida')
    fetchPlaylist()
  })

  // Escuchar sincronización de datos de facturación
  socketConn.on('clienteDisplayData', (data) => {
    if (!isTargetMe(data)) return
    console.log('Datos del cliente recibidos por socket para esta caja:', data)
    clientData.value = data
    resetWatchdog()
  })

  // Escuchar datos del QR de pago (Baneco) generado en caja
  socketConn.on('clienteQrData', (data) => {
    if (!isTargetMe(data)) return
    console.log('Datos de QR recibidos por socket para esta caja:', data)
    qrData.value = data
    if (data.visible) resetWatchdog()
  })

  // Escuchar finalización de venta exitosa
  socketConn.on('clienteSaleComplete', (data) => {
    if (!isTargetMe(data)) return
    console.log('Venta completada con éxito para esta caja!')
    showThankYou()
  })

  // Escuchar cierre de la pantalla de facturación
  socketConn.on('clienteDisplayClose', (data) => {
    if (!isTargetMe(data)) return
    console.log('Cierre gracefully solicitado para esta caja')
    closeGracefully()
  })
}

// Latido periódico para monitoreo del terminal (liviano y seguro)
function registerTerminalRoom() {
  if (!socketConn || !socketConn.connected || !config.value.agencia || !config.value.caja) return
  socketConn.emit('register_terminal', {
    agencia_id: config.value.agencia,
    caja: config.value.caja,
  })
}

async function sendStatusHeartbeat() {
  if (!socketConn || !socketConn.connected) return

  let disk = { free: 'N/A', total: 'N/A' }
  if (window.mediaAPI && window.mediaAPI.getDiskSpace) {
    try {
      disk = await window.mediaAPI.getDiskSpace()
    } catch (e) {
      console.error('Error al obtener espacio en disco:', e)
    }
  }

  socketConn.emit('terminal_status', {
    socket_id: socketConn.id,
    agencia_id: config.value.agencia,
    caja: config.value.caja || 1,
    current_ad: currentAd.value ? currentAd.value.name : 'Ninguno',
    current_ad_type: currentAd.value ? currentAd.value.type : 'N/A',
    playlist_count: playlist.value.length,
    disk_free: disk.free,
    disk_total: disk.total,
    online: true,
    timestamp: new Date().toISOString(),
  })
}

// Watchdog para ocultar la tarjeta de cliente si el cajero se desconecta o cierra de golpe.
// Ya no hay heartbeat periódico del front (solo se resetea con cambios reales o un QR
// recién generado), asi que sirve solo como red de seguridad ante caidas del front,
// no como mecanismo activo: debe tolerar una espera larga de pago con QR.
function resetWatchdog() {
  if (watchdogTimer) clearTimeout(watchdogTimer)
  watchdogTimer = setTimeout(() => {
    console.log('Watchdog expirado, cerrando pantalla de cliente por inactividad')
    closeGracefully()
  }, 600000) // 10 minutos de tolerancia sin cambios ni cierre explícito
}

function closeGracefully() {
  if (watchdogTimer) clearTimeout(watchdogTimer)

  // Si había datos de cliente activos (venta completada con éxito)
  if (hasClientData.value && clientData.value.visible) {
    showThankYou()
  } else {
    // Cerrar inmediatamente
    clearClientState()
  }
}

function showThankYou() {
  showThanks.value = true
  setTimeout(() => {
    showThanks.value = false
    clearClientState()
  }, 5000)
}

function clearClientState() {
  clientData.value = {
    numeroDocumento: '',
    complemento: '',
    nombreRazonSocial: '',
    email: '',
    tipoDocumento: '',
    visible: false,
  }
  qrData.value = {
    qrImage: '',
    monto: '',
    visible: false,
  }
}

// Atajo de teclado (Esc) para abrir configuración
function handleKeyPress(e) {
  if (e.key === 'Escape') {
    openConfig()
  }
}

// === LIFECYCLE HOOKS ===

onMounted(() => {
  updateTime()
  clockInterval = setInterval(updateTime, 1000)
  playlistPoll = setInterval(fetchPlaylist, 15000)
  syncTimer = setInterval(syncPlayback, 250)
  window.addEventListener('keydown', handleKeyPress)

  const exists = loadLocalConfig()
  if (exists) {
    initSocket()
    fetchPlaylist()
  } else {
    showConfigModal.value = true
  }

  // Latido del socket cada 60 segundos
  heartbeatInterval = setInterval(sendStatusHeartbeat, 60000)
})

onBeforeUnmount(() => {
  destroyed = true
  clearInterval(playlistPoll)
  clearInterval(syncTimer)
  if (clockInterval) clearInterval(clockInterval)
  if (imageTimer.value) clearTimeout(imageTimer.value)
  if (watchdogTimer) clearTimeout(watchdogTimer)
  if (heartbeatInterval) clearInterval(heartbeatInterval)
  if (socketConn) socketConn.disconnect()
  window.removeEventListener('keydown', handleKeyPress)
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap');

.player-page {
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  background-color: #000;
  position: relative;
  font-family: 'Outfit', sans-serif;
}

/* ===== REPRODUCTOR MULTIMEDIA ===== */
.media-container {
  width: 100vw;
  height: 100vh;
  position: absolute;
  top: 0;
  left: 0;
  z-index: 1;
}

.fullscreen-media {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background-color: #000;
}

.default-bg {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #1b3a5c 0%, #0d1e30 100%);
}

.pharmacy-pulse {
  animation: logoPulse 2.5s infinite ease-in-out;
}

@keyframes logoPulse {
  0%,
  100% {
    transform: scale(1);
    opacity: 0.8;
  }
  50% {
    transform: scale(1.08);
    opacity: 1;
  }
}

/* ===== SUPERPOSICIONES EN PRIMER PLANO ===== */
.overlay-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(13, 30, 48, 0.45); /* Backdrop glassmorphism */
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  animation: overlayFadeIn 0.5s ease forwards;
}

@keyframes overlayFadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* Tarjeta del cliente */
.client-card {
  width: 90%;
  max-width: 680px;
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(27, 58, 92, 0.1);
  border-radius: 24px;
  padding: 32px 38px;
  box-shadow:
    0 10px 40px rgba(0, 0, 0, 0.3),
    0 1px 3px rgba(0, 0, 0, 0.1);
  animation: cardSlideUp 0.5s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
}

@keyframes cardSlideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.display-header {
  display: flex;
  align-items: center;
  gap: 16px;
}

.pharmacy-logo {
  width: 58px;
  height: 58px;
  border-radius: 16px;
  background: linear-gradient(135deg, #1b3a5c, #2a5f8f);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 6px 20px rgba(27, 58, 92, 0.3);
}

.pharmacy-info {
  display: flex;
  flex-direction: column;
}

.pharmacy-name {
  font-size: 24px;
  font-weight: 800;
  color: #1b3a5c;
  letter-spacing: 0.5px;
  line-height: 1.2;
}

.pharmacy-tag {
  font-size: 10px;
  font-weight: 700;
  color: #64b5e2;
  letter-spacing: 4px;
  margin-top: 2px;
}

.card-title-container {
  display: flex;
  align-items: center;
  gap: 10px;
}

.card-title {
  font-size: 17px;
  font-weight: 700;
  color: #1b3a5c;
  flex: 1;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 700;
  color: #888;
  padding: 4px 14px;
  border-radius: 20px;
  background: #eaeaea;
  transition: all 0.4s ease;
}

.status-badge.active {
  color: #1b3a5c;
  background: rgba(100, 181, 226, 0.15);
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #bbb;
  transition: all 0.4s ease;
}

.status-badge.active .status-dot {
  background: #64b5e2;
  animation: dotBreathe 1.5s infinite ease-in-out;
}

@keyframes dotBreathe {
  0%,
  100% {
    opacity: 0.5;
  }
  50% {
    opacity: 1;
  }
}

.card-line {
  height: 1.5px;
  background: linear-gradient(90deg, rgba(27, 58, 92, 0.15), rgba(100, 181, 226, 0.1), transparent);
}

/* Listado de datos */
.data-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.data-row {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  border-radius: 14px;
  background: #f4f8fc;
  border: 1px solid rgba(27, 58, 92, 0.04);
}

.row-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: #e9eff5;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #7da5c4;
  flex-shrink: 0;
}

.row-icon.accent {
  background: linear-gradient(135deg, #e6f1fa, #cbe2f3);
  color: #1b3a5c;
}

.row-body {
  flex: 1;
}

.row-label {
  font-size: 11px;
  font-weight: 700;
  color: #7da5c4;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 2px;
}

.row-value {
  font-size: 19px;
  font-weight: 500;
  color: #1b3a5c;
  word-break: break-all;
}

.row-value.bold {
  font-size: 23px;
  font-weight: 800;
}

/* Pantalla de gracias */
.thanks-screen {
  text-align: center;
  padding: 40px;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 28px;
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.35);
  animation: cardSlideUp 0.6s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
}

.thanks-check {
  width: 100px;
  height: 100px;
  margin-bottom: 24px;
}

.check-svg {
  width: 100%;
  height: 100%;
}

.check-circle {
  stroke: #64b5e2;
  stroke-width: 2.5;
  stroke-dasharray: 166;
  stroke-dashoffset: 166;
  animation: circleAnim 0.6s ease 0.1s forwards;
}

@keyframes circleAnim {
  to {
    stroke-dashoffset: 0;
  }
}

.check-path {
  stroke: #1b3a5c;
  stroke-width: 3.5;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-dasharray: 48;
  stroke-dashoffset: 48;
  animation: checkAnim 0.4s ease 0.6s forwards;
}

@keyframes checkAnim {
  to {
    stroke-dashoffset: 0;
  }
}

.thanks-title {
  font-size: 34px;
  font-weight: 800;
  color: #1b3a5c;
  margin-bottom: 12px;
}

.thanks-sub {
  font-size: 17px;
  font-weight: 500;
  color: #64b5e2;
  max-width: 440px;
  line-height: 1.5;
}

.thanks-dots {
  display: flex;
  gap: 8px;
  justify-content: center;
  margin-top: 28px;
}

.dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #64b5e2;
  animation: dotPulse 1.4s ease-in-out infinite;
}

@keyframes dotPulse {
  0%,
  80%,
  100% {
    opacity: 0.2;
    transform: scale(0.8);
  }
  40% {
    opacity: 1;
    transform: scale(1.2);
  }
}

/* Esperando estado */
.waiting-state {
  text-align: center;
  padding: 40px 20px;
}

.waiting-icon {
  color: #c4d9eb;
  animation: iconBreathe 3s infinite ease-in-out;
}

@keyframes iconBreathe {
  0%,
  100% {
    transform: scale(1);
    opacity: 0.5;
  }
  50% {
    transform: scale(1.08);
    opacity: 0.8;
  }
}

.waiting-text {
  font-size: 19px;
  font-weight: 700;
  color: #7da5c4;
  margin-top: 14px;
}

.waiting-sub {
  font-size: 13px;
  color: #a7c1d6;
  margin-top: 4px;
}

/* Pago con QR (Baneco) */
.qr-payment-body {
  text-align: center;
  padding: 8px 0 16px;
}

.qr-payment-image {
  width: 220px;
  height: 220px;
  object-fit: contain;
  border-radius: 16px;
  border: 1px solid rgba(27, 58, 92, 0.1);
  background: #fff;
  padding: 8px;
}

.qr-payment-amount {
  font-size: 28px;
  font-weight: 800;
  color: #1b3a5c;
  margin-top: 16px;
}

.qr-payment-hint {
  font-size: 14px;
  color: #7da5c4;
  margin-top: 6px;
}

/* Footer de pantalla */
.display-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
  font-weight: 600;
  color: #7da5c4;
}

.footer-left {
  display: flex;
  align-items: center;
  gap: 6px;
}

.footer-right {
  font-variant-numeric: tabular-nums;
  color: #a7c1d6;
}

/* Config modal */
.config-card {
  width: 400px;
  max-width: 95vw;
  border-radius: 20px !important;
}

/* Settings Gear floating invisible button */
.settings-gear {
  position: absolute;
  top: 15px;
  left: 15px;
  z-index: 100;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.settings-gear:hover {
  opacity: 0.5;
}

/* Transición de opacidad suave (fundido cruzado) */
.fullscreen-media-wrapper {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
  background-color: #000;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.8s ease-in-out;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

<template>
  <div class="cliente-display-root">
    <!-- Fondo sutil (de respaldo si no hay publicidad) -->
    <div class="bg-shape bg-shape-1"></div>
    <div class="bg-shape bg-shape-2"></div>
    <div class="bg-pattern"></div>

    <!-- Contenido principal -->
    <div class="cliente-display-container">

      <!-- ===== PANTALLA DE GRACIAS ===== -->
      <div class="thanks-screen" v-show="showThanks">
        <div class="thanks-check">
          <svg viewBox="0 0 52 52" class="check-svg">
            <circle class="check-circle" cx="26" cy="26" r="25" fill="none"/>
            <path class="check-path" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
          </svg>
        </div>
        <div class="thanks-title">¡Gracias por su compra!</div>
        <div class="thanks-sub">Farmacia SANTIDAD-DIVINA S.R.L. le desea un excelente día</div>
        <div class="thanks-dots">
          <span class="dot" v-for="n in 3" :key="n" :style="{ animationDelay: (n * 0.2) + 's' }"></span>
        </div>
      </div>

      <!-- ===== PANTALLA NORMAL ===== -->
      <template v-if="!showThanks">
        <!-- Header -->
        <div class="display-header">
          <div class="pharmacy-logo">
            <q-icon name="local_pharmacy" size="38px" color="white" />
          </div>
          <div class="pharmacy-info">
            <div class="pharmacy-name">SANTIDAD-DIVINA S.R.L.</div>
            <div class="pharmacy-tag">FARMACIA</div>
          </div>
        </div>

        <!-- Tarjeta de datos -->
        <div class="client-card">
          <div class="card-header">
            <q-icon name="person_outline" size="22px" style="color: #1b3a5c" />
            <span class="card-title">Datos del Cliente</span>
            <div class="status-badge" :class="{ active: hasClientData }">
              <div class="status-dot"></div>
              {{ hasClientData ? 'Activo' : 'Esperando' }}
            </div>
          </div>

          <div class="card-line"></div>

          <div class="data-list" v-show="hasClientData">
            <div class="data-row" v-show="clientData.tipoDocumento">
              <div class="row-icon"><q-icon name="description" size="20px" /></div>
              <div class="row-body">
                <div class="row-label">Tipo de Documento</div>
                <div class="row-value">{{ clientData.tipoDocumento || '' }}</div>
              </div>
            </div>

            <div class="data-row">
              <div class="row-icon accent"><q-icon name="fingerprint" size="20px" /></div>
              <div class="row-body">
                <div class="row-label">NIT / Carnet</div>
                <div class="row-value bold">{{ clientData.numeroDocumento || '—' }}</div>
              </div>
            </div>

            <div class="data-row" v-show="clientData.complemento">
              <div class="row-icon"><q-icon name="add_circle_outline" size="20px" /></div>
              <div class="row-body">
                <div class="row-label">Complemento</div>
                <div class="row-value">{{ clientData.complemento || '' }}</div>
              </div>
            </div>

            <div class="data-row">
              <div class="row-icon accent"><q-icon name="store" size="20px" /></div>
              <div class="row-body">
                <div class="row-label">Nombre / Razón Social</div>
                <div class="row-value bold">{{ clientData.nombreRazonSocial || '—' }}</div>
              </div>
            </div>

            <div class="data-row" v-show="clientData.email">
              <div class="row-icon"><q-icon name="alternate_email" size="20px" /></div>
              <div class="row-body">
                <div class="row-label">Correo Electrónico</div>
                <div class="row-value">{{ clientData.email || '' }}</div>
              </div>
            </div>
          </div>

          <!-- Espera (Solo si no hay datos de cliente Y no hay anuncios activos) -->
          <div class="waiting-state" v-show="!hasClientData">
            <q-icon name="medical_information" size="64px" class="waiting-icon" />
            <div class="waiting-text">Esperando datos del cliente</div>
            <div class="waiting-sub">Los datos aparecerán aquí automáticamente</div>
          </div>
        </div>

        <!-- Footer -->
        <div class="display-footer">
          <div class="footer-left">
            <q-icon name="verified" size="14px" />
            Verifique que sus datos sean correctos
          </div>
          <div class="footer-right">{{ currentTime }}</div>
        </div>
      </template>

    </div>
  </div>
</template>

<script>
export default {
  name: 'ClienteDisplayPage',
  data () {
    return {
      clientData: {
        numeroDocumento: '',
        complemento: '',
        nombreRazonSocial: '',
        email: '',
        tipoDocumento: ''
      },
      showThanks: false,
      currentTime: '',
      timeInterval: null
    }
  },
  computed: {
    hasClientData () {
      return this.clientData.numeroDocumento &&
        this.clientData.numeroDocumento !== '' &&
        this.clientData.numeroDocumento !== '0'
    }
  },
  watch: {
  },
  mounted () {
    this.loadClientData()
    window.addEventListener('storage', this.onStorageChange)
    this.updateTime()
    this.timeInterval = setInterval(this.updateTime, 1000)

    // Iniciar latido de ventana activa
    this.startActiveHeartbeat()

    if (typeof BroadcastChannel !== 'undefined') {
      this.channel = new BroadcastChannel('cliente-display-channel')
      this.channel.onmessage = (event) => {
        if (event.data && event.data.type === 'client-data') {
          this.updateClientData(event.data.payload)
        }
        if (event.data && event.data.type === 'sale-complete') {
          this.showThankYou()
        }
        if (event.data && event.data.type === 'display-close') {
          this.closeGracefully()
        }
      }
    }
  },
  beforeUnmount () {
    window.removeEventListener('storage', this.onStorageChange)
    if (this.timeInterval) clearInterval(this.timeInterval)
    this.stopActiveHeartbeat()
    if (this.channel) this.channel.close()
  },
  methods: {
    getPublicidadPlaylist () {
      const agenciaId = this.$route.query.agencia_id || localStorage.getItem('agencia_id')
      this.$axios.get('publicidad-actual', { params: { agencia_id: agenciaId } })
        .then(res => {
          if (Array.isArray(res.data)) {
            // Comparar si la playlist cambió de contenido
            const newIds = res.data.map(d => d.file_id).join(',')
            const oldIds = this.playlist.map(d => d.file_id).join(',')
            if (newIds !== oldIds) {
              this.playlist = res.data
              this.currentIndex = 0
              this.playCurrentAd()
            }
          } else {
            this.playlist = []
            this.clearImageTimer()
          }
        })
        .catch(err => {
          console.error('Error al obtener playlist:', err)
        })
    },
    playCurrentAd () {
      this.clearImageTimer()
      if (this.playlist.length === 0) return

      const ad = this.currentAd
      if (!ad) return

      if (ad.type === 'video') {
        this.$nextTick(() => {
          const video = this.$refs.videoPlayer
          if (video) {
            video.load()
            video.play().catch(e => {
              console.log('Autoplay bloqueado o pausado:', e)
            })
          }
        })
      } else {
        // La imagen dura 10 segundos
        this.imageTimer = setTimeout(() => {
          this.nextAd()
        }, 10000)
      }
    },
    nextAd () {
      if (this.playlist.length === 0) return
      this.currentIndex = (this.currentIndex + 1) % this.playlist.length
      this.playCurrentAd()
    },
    clearImageTimer () {
      if (this.imageTimer) {
        clearTimeout(this.imageTimer)
        this.imageTimer = null
      }
    },
    pauseVideo () {
      this.clearImageTimer()
      const video = this.$refs.videoPlayer
      if (video) {
        video.pause()
      }
    },
    resumeVideoOrPlaylist () {
      this.getPublicidadPlaylist() // Refrescar por si cambió mientras estaba inactivo
      this.$nextTick(() => {
        if (this.playlist.length > 0) {
          const ad = this.currentAd
          if (ad && ad.type === 'video') {
            const video = this.$refs.videoPlayer
            if (video) {
              video.play().catch(e => console.log('Error al reanudar video:', e))
            }
          } else {
            this.playCurrentAd()
          }
        }
      })
    },
    loadClientData () {
      try {
        const data = localStorage.getItem('clienteDisplayData')
        if (data) this.updateClientData(JSON.parse(data))
      } catch (e) {
        console.error('Error loading client data:', e)
      }
    },
    updateClientData (d) {
      if (!d) return
      if (d.numeroDocumento !== undefined) this.clientData.numeroDocumento = d.numeroDocumento
      if (d.complemento !== undefined) this.clientData.complemento = d.complemento
      if (d.nombreRazonSocial !== undefined) this.clientData.nombreRazonSocial = d.nombreRazonSocial
      if (d.email !== undefined) this.clientData.email = d.email
      if (d.tipoDocumento !== undefined) this.clientData.tipoDocumento = d.tipoDocumento
      if (d.visible !== undefined) this.clientData.visible = d.visible
    },
    onStorageChange (event) {
      if (event.key === 'clienteDisplayData') {
        try { this.updateClientData(JSON.parse(event.newValue)) } catch (e) { /* ignore */ }
      }
      if (event.key === 'clienteSaleComplete') {
        this.showThankYou()
      }
      if (event.key === 'clienteDisplayClose') {
        this.closeGracefully()
      }
      if (event.key === 'clienteDisplayHeartbeat') {
        this.lastHeartbeat = Date.now()
      }
    },
    showThankYou () {
      this.showThanks = true
      setTimeout(() => {
        this.showThanks = false
        // Limpiar los datos del cliente para que vuelva a reproducir anuncios
        this.clientData = {
          numeroDocumento: '',
          complemento: '',
          nombreRazonSocial: '',
          email: '',
          tipoDocumento: '',
          visible: false
        }
        window.close()
      }, 5000)
    },
    closeGracefully () {
      // Limpiar los datos del cliente para que vuelva a reproducir anuncios
      this.clientData = {
        numeroDocumento: '',
        complemento: '',
        nombreRazonSocial: '',
        email: '',
        tipoDocumento: '',
        visible: false
      }
      window.close()
    },
    updateTime () {
      const now = new Date()
      this.currentTime = now.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
    },
    startActiveHeartbeat () {
      this.stopActiveHeartbeat()
      this._activeHeartbeatInterval = setInterval(() => {
        localStorage.setItem('clienteDisplayWindowActive', Date.now().toString())
      }, 2000)
      localStorage.setItem('clienteDisplayWindowActive', Date.now().toString())
    },
    stopActiveHeartbeat () {
      if (this._activeHeartbeatInterval) {
        clearInterval(this._activeHeartbeatInterval)
        this._activeHeartbeatInterval = null
      }
    },
    triggerFullscreen () {
      const docEl = document.documentElement
      if (docEl.requestFullscreen) {
        docEl.requestFullscreen().catch(err => {
          console.log('Error al solicitar pantalla completa:', err)
        })
      }
      document.removeEventListener('click', this.triggerFullscreen)
    }
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.cliente-display-root {
  position: fixed;
  top: 0; left: 0;
  width: 100vw; height: 100vh;
  background: linear-gradient(160deg, #ffffff 0%, #f0f6fc 40%, #e6f0fa 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  font-family: 'Inter', 'Segoe UI', sans-serif;
  z-index: 9999;
}

/* Fondo sutil */
.bg-shape {
  position: absolute;
  border-radius: 50%;
  opacity: 0.5;
}
.bg-shape-1 {
  width: 600px; height: 600px;
  background: radial-gradient(circle, rgba(27,58,92,0.07), transparent 70%);
  top: -10%; right: -8%;
}
.bg-shape-2 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, rgba(100,181,226,0.08), transparent 70%);
  bottom: -12%; left: -6%;
}
.bg-pattern {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background-image: radial-gradient(rgba(27,58,92,0.03) 1px, transparent 1px);
  background-size: 32px 32px;
}

/* Contenedor */
.cliente-display-container {
  position: relative;
  z-index: 10;
  width: 88%;
  max-width: 640px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24px;
}

/* ===== PANTALLA DE GRACIAS ===== */
.thanks-screen {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 40px 20px;
  animation: thanksFadeIn 0.6s ease;
}

@keyframes thanksFadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

.thanks-check {
  width: 100px;
  height: 100px;
  margin-bottom: 28px;
}

.check-svg {
  width: 100px;
  height: 100px;
}

.check-circle {
  stroke: #64b5e2;
  stroke-width: 2;
  stroke-dasharray: 166;
  stroke-dashoffset: 166;
  animation: circleAnim 0.6s ease 0.2s forwards;
}

@keyframes circleAnim {
  to { stroke-dashoffset: 0; }
}

.check-path {
  stroke: #1b3a5c;
  stroke-width: 3;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-dasharray: 48;
  stroke-dashoffset: 48;
  animation: checkAnim 0.4s ease 0.7s forwards;
}

@keyframes checkAnim {
  to { stroke-dashoffset: 0; }
}

.thanks-title {
  font-size: 32px;
  font-weight: 800;
  color: #1b3a5c;
  margin-bottom: 12px;
  letter-spacing: -0.5px;
}

.thanks-sub {
  font-size: 16px;
  font-weight: 500;
  color: #64b5e2;
  max-width: 400px;
  line-height: 1.5;
}

.thanks-dots {
  display: flex;
  gap: 8px;
  margin-top: 32px;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #64b5e2;
  animation: dotPulse 1.4s ease-in-out infinite;
}

@keyframes dotPulse {
  0%, 80%, 100% { opacity: 0.2; transform: scale(0.8); }
  40% { opacity: 1; transform: scale(1.2); }
}

/* ===== HEADER ===== */
.display-header {
  display: flex;
  align-items: center;
  gap: 16px;
  animation: slideDown 0.6s ease;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-16px); }
  to { opacity: 1; transform: translateY(0); }
}

.pharmacy-logo {
  width: 62px; height: 62px;
  border-radius: 16px;
  background: linear-gradient(135deg, #1b3a5c, #2a5f8f);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 6px 24px rgba(27,58,92,0.25);
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
  font-size: 11px;
  font-weight: 700;
  color: #64b5e2;
  letter-spacing: 4px;
  margin-top: 2px;
}

/* ===== TARJETA ===== */
.client-card {
  width: 100%;
  background: #ffffff;
  border: 1px solid rgba(27,58,92,0.08);
  border-radius: 20px;
  padding: 28px 32px;
  box-shadow:
    0 4px 24px rgba(27,58,92,0.06),
    0 1px 3px rgba(0,0,0,0.03);
  animation: slideUp 0.5s ease;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}

.card-header {
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-title {
  font-size: 15px;
  font-weight: 700;
  color: #1b3a5c;
  flex: 1;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  font-weight: 600;
  color: #999;
  padding: 4px 12px;
  border-radius: 20px;
  background: #f2f2f2;
  transition: all 0.4s ease;
}

.status-badge.active {
  color: #1b3a5c;
  background: rgba(100,181,226,0.12);
}

.status-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: #ccc;
  transition: all 0.4s ease;
}

.status-badge.active .status-dot {
  background: #64b5e2;
  box-shadow: 0 0 6px rgba(100,181,226,0.5);
}

.card-line {
  height: 1px;
  background: linear-gradient(90deg, rgba(27,58,92,0.12), rgba(100,181,226,0.08), transparent);
  margin: 16px 0 20px;
}

/* ===== DATOS ===== */
.data-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.data-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  border-radius: 12px;
  background: #f7fafd;
  border: 1px solid rgba(27,58,92,0.05);
}

.row-icon {
  width: 40px; height: 40px;
  border-radius: 10px;
  background: #f0f4f8;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #94b3cc;
}

.row-icon.accent {
  background: linear-gradient(135deg, #e6f1fa, #d4e8f6);
  color: #1b3a5c;
}

.row-body {
  flex: 1;
  min-width: 0;
}

.row-label {
  font-size: 11px;
  font-weight: 600;
  color: #94b3cc;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 2px;
}

.row-value {
  font-size: 19px;
  font-weight: 500;
  color: #1b3a5c;
  word-break: break-word;
  min-height: 26px;
  line-height: 26px;
}

.row-value.bold {
  font-size: 22px;
  font-weight: 700;
  color: #1b3a5c;
}

/* ===== ESPERA ===== */
.waiting-state {
  text-align: center;
  padding: 48px 20px;
}

.waiting-icon {
  color: #c8ddef;
  margin-bottom: 16px;
  animation: breathe 3s ease-in-out infinite;
}

@keyframes breathe {
  0%, 100% { opacity: 0.4; transform: scale(1); }
  50% { opacity: 0.7; transform: scale(1.05); }
}

.waiting-text {
  font-size: 18px;
  font-weight: 600;
  color: #94b3cc;
  margin-bottom: 6px;
}

.waiting-sub {
  font-size: 13px;
  color: #b5ccdb;
}

/* ===== FOOTER ===== */
.display-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  padding: 0 4px;
}

.footer-left {
  font-size: 11px;
  font-weight: 600;
  color: #94b3cc;
  display: flex;
  align-items: center;
  gap: 4px;
}

.footer-right {
  font-size: 11px;
  font-weight: 600;
  color: #b5ccdb;
  font-variant-numeric: tabular-nums;
}

/* Responsive */
@media (max-width: 600px) {
  .cliente-display-container { width: 95%; }
  .client-card { padding: 20px; }
  .row-value { font-size: 16px; }
  .row-value.bold { font-size: 18px; }
  .pharmacy-name { font-size: 18px; }
  .thanks-title { font-size: 24px; }
}
</style>

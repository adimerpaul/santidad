<template>
  <div class="publicidad-tv-root">
    <!-- ===== REPRODUCTOR DE PUBLICIDAD (PANTALLA COMPLETA) ===== -->
    <div class="player-container" v-if="agenciaId !== null && playlist.length > 0">
      <video
        v-if="currentAd && currentAd.type === 'video'"
        ref="videoPlayer"
        :src="currentAd.url"
        autoplay
        muted
        playsinline
        @ended="nextAd"
        class="media-content"
      ></video>
      <img
        v-else-if="currentAd && currentAd.type === 'image'"
        :src="currentAd.url"
        class="media-content"
      />

      <!-- Botón de configuración/sucursal en la esquina (sutil) -->
      <div class="gear-btn" @click="showSelector = true">
        <q-icon name="settings" size="20px" color="white" />
      </div>

      <!-- Nombre del anuncio en la esquina -->
      <div class="ad-badge" v-if="currentAd">
        {{ currentAd.name }}
      </div>
    </div>

    <!-- ===== PANTALLA DE CARGA O REPOSO ===== -->
    <div class="waiting-screen" v-else-if="agenciaId !== null && playlist.length === 0">
      <div class="waiting-card">
        <q-icon name="live_tv" size="72px" class="waiting-icon" />
        <div class="waiting-title">Sin Publicidad Activa</div>
        <div class="waiting-sub">No hay anuncios configurados para esta sucursal en el sistema.</div>
        <q-btn label="Cambiar Sucursal" color="primary" class="q-mt-lg" @click="showSelector = true" />
      </div>
    </div>

    <!-- ===== DIALOGO SELECTOR DE SUCURSAL (ESTILO APK) ===== -->
    <q-dialog v-model="showSelector" persistent transition-show="scale" transition-hide="scale">
      <q-card class="selector-card bg-dark text-white">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6 text-bold">Seleccionar Sucursal (TV)</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup v-if="agenciaId !== null" />
        </q-card-section>

        <q-card-section class="q-pt-md">
          <div class="text-caption text-grey-4 q-mb-md">
            Selecciona la sucursal para sincronizar y reproducir la publicidad correspondiente en esta pantalla.
          </div>
          <q-list bordered separator class="rounded-borders bg-grey-9">
            <q-item
              clickable
              v-ripple
              v-for="ag in agencias"
              :key="ag.id"
              @click="selectAgencia(ag.id)"
              :active="agenciaId === ag.id"
              active-class="selected-item"
            >
              <q-item-section avatar>
                <q-icon name="storefront" :color="agenciaId === ag.id ? 'primary' : 'white'" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-bold">{{ ag.nombre }}</q-item-label>
                <q-item-label caption class="text-grey-5">{{ ag.direccion || 'Sin dirección' }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script>
export default {
  name: 'PublicidadTvPage',
  data () {
    return {
      agenciaId: null,
      showSelector: false,
      agencias: [],
      playlist: [],
      currentIndex: 0,
      imageTimer: null,
      playlistInterval: null
    }
  },
  computed: {
    currentAd () {
      if (this.playlist.length === 0) return null
      return this.playlist[this.currentIndex]
    }
  },
  mounted () {
    // Intentar cargar agencia guardada en local
    const savedId = localStorage.getItem('tv_agencia_id')
    if (savedId) {
      this.agenciaId = parseInt(savedId)
      this.getPlaylist()
      this.playlistInterval = setInterval(this.getPlaylist, 30000) // refrescar cada 30s
    } else {
      this.showSelector = true
    }
    this.getAgencias()
  },
  beforeUnmount () {
    this.clearImageTimer()
    if (this.playlistInterval) clearInterval(this.playlistInterval)
  },
  methods: {
    getAgencias () {
      this.$axios.get('agencias')
        .then(res => {
          this.agencias = res.data
        })
        .catch(err => {
          console.error('Error fetching agencias:', err)
        })
    },
    selectAgencia (id) {
      this.agenciaId = id
      localStorage.setItem('tv_agencia_id', id)
      this.showSelector = false
      this.playlist = []
      this.currentIndex = 0
      this.clearImageTimer()
      this.getPlaylist()
      if (this.playlistInterval) clearInterval(this.playlistInterval)
      this.playlistInterval = setInterval(this.getPlaylist, 30000)
    },
    getPlaylist () {
      if (this.agenciaId === null) return
      this.$axios.get('publicidad-actual', { params: { agencia_id: this.agenciaId } })
        .then(res => {
          if (Array.isArray(res.data)) {
            // Comparar si la playlist cambió
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
          console.error('Error fetching playlist:', err)
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
              console.log('Autoplay blocked, retrying...', e)
            })
          }
        })
      } else {
        // Ciclado de imagen cada 10 segundos
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
    }
  }
}
</script>

<style scoped>
.publicidad-tv-root {
  position: fixed;
  top: 0; left: 0;
  width: 100vw; height: 100vh;
  background: #000000;
  overflow: hidden;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* ===== CONTENEDOR PLAYER ===== */
.player-container {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #000000;
}

.media-content {
  width: 100vw;
  height: 100vh;
  object-fit: contain;
  background: #000000;
}

/* ===== CONFIGURACIÓN SUTIL ===== */
.gear-btn {
  position: absolute;
  top: 15px; right: 15px;
  background: rgba(0, 0, 0, 0.2);
  width: 36px; height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.3s ease, background 0.3s ease;
  z-index: 100;
}

.player-container:hover .gear-btn {
  opacity: 1;
}

.gear-btn:hover {
  background: rgba(0, 0, 0, 0.6);
}

.ad-badge {
  position: absolute;
  bottom: 15px;
  left: 15px;
  background: rgba(0, 0, 0, 0.5);
  color: rgba(255, 255, 255, 0.6);
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  pointer-events: none;
  z-index: 90;
}

/* ===== REPOSO / ESPERA ===== */
.waiting-screen {
  width: 100vw;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #121214;
}

.waiting-card {
  text-align: center;
  max-width: 450px;
  padding: 40px;
  background: #1a1a1e;
  border: 1px solid #2a2a30;
  border-radius: 16px;
  color: white;
}

.waiting-icon {
  color: #3f3f46;
  margin-bottom: 20px;
}

.waiting-title {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 10px;
}

.waiting-sub {
  font-size: 14px;
  color: #a1a1aa;
  line-height: 1.5;
}

/* ===== MODAL DE SUCURSALES ===== */
.selector-card {
  width: 450px;
  max-width: 90vw;
  border-radius: 16px;
  border: 1px solid #333;
}

.bg-dark {
  background: #18181b !important;
}

.selected-item {
  background: rgba(25, 118, 210, 0.15) !important;
  color: #26a69a !important;
  border-left: 4px solid #26a69a;
}
</style>

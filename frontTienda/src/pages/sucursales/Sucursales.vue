<template>
  <q-page class="q-pa-none bg-grey-2">
    <!-- ===== Barra superior (igual al principal) ===== -->
    <div class="barra-superior">
      <q-btn flat round dense icon="menu" @click="toggleDrawer" size="md" />

      <div class="search-container">
        <q-input
          v-model="search"
          dense
          outlined
          rounded
          @keyup.enter="buscar"
          placeholder="Buscar Producto / Palabra Clave"
          class="search-input"
        >
          <template v-slot:prepend><q-icon name="search" /></template>
        </q-input>
        <q-btn
          label="Buscar"
          rounded
          class="search-btn"
          :loading="loading"
          @click="buscar"
          no-caps
        />
      </div>
    </div>

    <!-- ☰ Drawer -->
    <div v-if="drawer" class="menu-navegacion">
      <div class="menu-item" @click="navigateTo('/')">
        <q-icon name="home" class="q-mr-sm" /> Inicio
      </div>
      <div class="menu-item" @click="navigateTo('/sucursales')">
        <q-icon name="store" class="q-mr-sm" /> Sucursales
      </div>
    </div>

    <!-- ===== Contenido ===== -->
    <div class="page-wrapper q-pa-md">
      <div class="encabezado">
        <div class="titulo-wrap">
          <img src="images/logo.png" alt="Logo" class="logo" />
          <div>
            <h1 class="titulo">Nuestras Sucursales</h1>
            <div class="subtitulo">Encuentra la más cercana, revisa horarios y contáctanos.</div>
          </div>
        </div>
      </div>

      <div class="row q-col-gutter-md">
        <!-- Lista -->
        <div class="col-12 col-md-5">
          <q-card class="card-elevada">
            <q-card-section class="q-pb-sm">
              <div class="text-h6 q-mb-xs">Sucursales</div>
              <div class="text-caption text-blue-grey-6">Toca una sucursal para centrar el mapa y ver detalles.</div>
            </q-card-section>
            <q-separator />
            <q-card-section class="q-pt-none">
              <q-list separator>
                <q-expansion-item
                  v-for="sucursal in sucursales"
                  :key="sucursal.id"
                  switch-toggle-side
                  expand-separator
                  :header-class="{'row-active': sucursal.id===activeId}"
                  @show="focusSucursal(sucursal, true)"
                >
                  <template v-slot:header>
                    <q-item-section avatar>
                      <q-avatar :color="sucursal.id===activeId ? 'primary' : 'grey-7'"
                                text-color="white" icon="store" />
                    </q-item-section>
                    <q-item-section>
                      <q-item-label class="text-weight-bold">{{ sucursal.nombre }}</q-item-label>
                      <q-item-label caption>{{ sucursal.direccion }}</q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      <q-badge :color="sucursal.id===activeId ? 'primary' : 'blue-6'" outline>Ver</q-badge>
                    </q-item-section>
                  </template>

                  <div class="q-pa-sm">
                    <div class="chips">
                      <q-chip dense icon="location_on" color="grey-2" text-color="blue-grey-9">{{ sucursal.direccion }}</q-chip>
                      <q-chip dense icon="call" color="grey-2" text-color="blue-grey-9">{{ sucursal.telefono }}</q-chip>
                      <q-chip dense icon="schedule" color="grey-2" text-color="blue-grey-9">{{ sucursal.horario }}</q-chip>
                      <q-chip dense icon="event_available" color="grey-2" text-color="blue-grey-9">{{ sucursal.atencion }}</q-chip>
                    </div>

                    <div class="q-mt-sm q-gutter-sm">
                      <q-btn :href="sucursal.whatsapp" target="_blank" color="green-6" icon="fab fa-whatsapp" label="WhatsApp" no-caps dense unelevated />
                      <q-btn :href="sucursal.facebook" target="_blank" color="indigo-7" icon="fab fa-facebook" label="Facebook" no-caps dense unelevated />
                      <q-btn outline color="primary" icon="my_location" label="Centrar en mapa" no-caps dense @click="focusSucursal(sucursal, true)" />
                    </div>
                  </div>
                </q-expansion-item>
              </q-list>
            </q-card-section>
          </q-card>
        </div>

        <!-- Mapa -->
        <div class="col-12 col-md-7">
          <q-card class="card-elevada mapa-card">
            <q-card-section class="q-pb-none flex items-center justify-between">
              <div class="text-h6">Mapa</div>
              <div class="q-gutter-xs">
                <q-btn dense outline no-caps color="primary" icon="near_me" label="Mi ubicación" @click="goToMyLocation"/>
                <q-btn dense outline no-caps color="grey-8" icon="refresh" label="Ajustar a sucursales" @click="fitToAll()"/>
              </div>
            </q-card-section>
            <q-card-section>
              <div class="mapa-wrap" v-if="!loadingMapa">
                <l-map
                  ref="map"
                  :zoom="zoom"
                  :center="center"
                  :use-global-leaflet="false"
                  :options="mapOptions"
                  style="height: 520px; border-radius: 14px; overflow: hidden;"
                >
                  <!-- Capa principal -->
                  <l-tile-layer
                    ref="tilesMain"
                    url="https://mt1.google.com/vt/lyrs=r&x={x}&y={y}&z={z}"
                    layer-type="base"
                  />
                  <!-- Fallback OSM -->
                  <l-tile-layer
                    ref="tilesFallback"
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                    layer-type="overlay"
                    :opacity="fallbackOpacity"
                    attribution="&copy; OpenStreetMap contributors"
                  />

                  <!-- Marcadores -->
                  <l-marker
                    v-for="sucursal in sucursales"
                    :key="sucursal.id"
                    :lat-lng="[Number(sucursal.latitud), Number(sucursal.longitud)]"
                    :icon="sucursal.id===activeId ? icons.active : icons.default"
                    :z-index-offset="sucursal.id===activeId ? 1000 : 0"
                    @click="focusSucursal(sucursal, false)"
                  >
                    <l-popup :options="{autoClose:true, closeButton:true}">
                      <div class="popup">
                        <div class="popup-title">{{ sucursal.nombre }}</div>
                        <div class="popup-sub">{{ sucursal.direccion }}</div>
                        <div class="q-mt-xs q-gutter-xs">
                          <a :href="sucursal.whatsapp" target="_blank" class="popup-btn">WhatsApp</a>
                          <a :href="sucursal.facebook" target="_blank" class="popup-btn alt">Facebook</a>
                        </div>
                      </div>
                    </l-popup>
                    <l-tooltip :content="sucursal.nombre" />
                  </l-marker>

                  <!-- Mi ubicación -->
                  <l-marker v-if="userLatLng" :lat-lng="userLatLng" :icon="icons.user" :z-index-offset="500">
                    <l-tooltip content="Estás aquí" />
                  </l-marker>
                </l-map>
              </div>
              <div v-else class="q-pa-lg flex flex-center">
                <q-spinner-dots size="40px" color="primary" />
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script>
import { nextTick } from 'vue' // ✅ usar nextTick (no this.$nextTick)
import { LMap, LTileLayer, LMarker, LTooltip, LPopup } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'

export default {
  name: 'SucursalesPage',
  components: { LMap, LTileLayer, LMarker, LTooltip, LPopup },
  data () {
    return {
      // buscador
      search: this.$route.query?.q || '',
      loading: false,

      // UI / nav
      drawer: false,

      // mapa
      sucursales: [],
      center: [-17.957072, -67.1217629],
      zoom: 13,
      loadingMapa: false,
      mapOptions: { zoomControl: true, scrollWheelZoom: true, preferCanvas: true },
      fallbackOpacity: 0,

      // estado
      activeId: null,
      userLatLng: null,

      // iconos
      icons: {
        default: null,
        active: null,
        user: null
      },

      // handler para listeners (para remover luego)
      invalidateHandler: null
    }
  },
  created () {
    this.buildIcons()
    this.sucursalesGet()
  },
  mounted () {
    // ✅ sin $once: registra y remueve en beforeUnmount
    this._invalidateHandler = () => {
      const map = this.$refs.map?.leafletObject || this.$refs.map?.mapObject
      if (map) map.invalidateSize()
    }
    window.addEventListener('resize', this._invalidateHandler)
    document.addEventListener('visibilitychange', this._invalidateHandler)
  },
  beforeUnmount () {
    // ✅ reemplaza $once/$off por remover listeners aquí
    if (this._invalidateHandler) {
      window.removeEventListener('resize', this._invalidateHandler)
      document.removeEventListener('visibilitychange', this._invalidateHandler)
    }
  },
  methods: {
    /* ===== buscador ===== */
    toggleDrawer () { this.drawer = !this.drawer },
    navigateTo (ruta) { this.$router.push(ruta); this.drawer = false },

    async buscar () { // ✅ async + await nextTick
      const q = (this.search || '').trim()
      if (!q) return
      this.loading = true
      this.$router.push({ path: '/buscar', query: { q, page: 1 } })
      await nextTick()
      this.loading = false
    },

    /* ===== datos ===== */
    sucursalesGet () {
      this.$q.loading.show()
      this.$axios.get('sucursales')
        .then(({ data }) => {
          this.sucursales = (data || []).map(s => ({
            ...s,
            latitud: Number(s.latitud),
            longitud: Number(s.longitud)
          }))
          this.$nextTick(() => this.fitToAll()) // aquí sí es válido usar callback o cambia a await nextTick()
        })
        .finally(() => this.$q.loading.hide())
    },

    /* ===== mapa ===== */
    fitToAll () {
      const map = this.$refs.map?.leafletObject || this.$refs.map?.mapObject
      if (!map || this.sucursales.length === 0) return
      const bounds = L.latLngBounds(this.sucursales.map(s => [s.latitud, s.longitud]))
      map.fitBounds(bounds, { paddingTopLeft: [340, 40], paddingBottomRight: [40, 40], maxZoom: 16 })
    },
    focusSucursal (s, openPopup) {
      if (!s) return
      this.activeId = s.id
      const map = this.$refs.map?.leafletObject || this.$refs.map?.mapObject
      if (map && map.flyTo) map.flyTo([s.latitud, s.longitud], 16, { duration: 0.6 })
      if (openPopup) {
        // abrir popup del marcador activo
        this.$nextTick(() => {
          const layers = []
          map.eachLayer(l => { if (l.getLatLng && l.openPopup) layers.push(l) })
          const mk = layers.find(l => {
            const ll = l.getLatLng?.()
            return ll && Math.abs(ll.lat - s.latitud) < 1e-6 && Math.abs(ll.lng - s.longitud) < 1e-6
          })
          mk && mk.openPopup()
        })
      }
    },
    goToMyLocation () {
      if (!navigator.geolocation) return
      navigator.geolocation.getCurrentPosition(({ coords }) => {
        this.userLatLng = [coords.latitude, coords.longitude]
        const map = this.$refs.map?.leafletObject || this.$refs.map?.mapObject
        if (map && map.flyTo) map.flyTo(this.userLatLng, 15, { duration: 0.6 })
      })
    },

    /* ===== iconos SVG sin assets externos ===== */
    // buildIcons() mejorado con diseño moderno
    buildIcons () {
      // Pin moderno por defecto
      const mk = (fill = '#2563eb', stroke = '#1e40af', size = 40) => {
        const svg = `
          <svg width="${size}" height="${size}" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
            <!-- Sombra -->
            <circle cx="24" cy="46" r="6" fill="rgba(0,0,0,0.25)" />
            <!-- Pin -->
            <path d="M24 4C15 4 8 11 8 20c0 11 13 23 14.5 25a2 2 0 0 0 3 0C27 43 40 31 40 20c0-9-7-16-16-16z"
                  fill="${fill}" stroke="${stroke}" stroke-width="1.5" />
            <!-- Círculo interno -->
            <circle cx="24" cy="20" r="6" fill="white" stroke="${stroke}" stroke-width="2"/>
          </svg>
        `
        return L.divIcon({
          className: 'mk-modern',
          html: svg,
          iconSize: [size, size],
          iconAnchor: [size / 2, size],
          popupAnchor: [0, -size / 2]
        })
      }

      // Pin activo (más grande y en otro color)
      const active = (size = 48) => mk('#10b981', '#065f46', size)

      // Ubicación del usuario
      const user = (size = 30) => {
        const svg = `
          <svg width="${size}" height="${size}" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" fill="#3b82f6"/>
            <circle cx="12" cy="12" r="4" fill="white"/>
          </svg>`
        return L.divIcon({ className: 'mk-user', html: svg, iconSize: [size, size], iconAnchor: [size / 2, size / 2] })
      }

      this.icons.default = mk('#2563eb', '#1e40af', 40) // Azul elegante
      this.icons.active = active(48) // Verde moderno
      this.icons.user = user(30)
    }
  }
}
</script>

<style scoped>
/* ===== barra superior ===== */
.barra-superior{
  position: fixed; top: 10px; left: 50%; transform: translateX(-50%);
  width: 90vw; max-width: 1100px; z-index: 999;
  background: #fff; display: flex; align-items: center; gap: 12px;
  padding: 6px 12px; border-radius: 12px;
  box-shadow: 0 8px 24px rgba(9, 0, 141, 0.2);
}
.search-container{ display:flex; flex:1; gap:8px; align-items:center; }
.search-input{ flex:1; min-width:120px; }
.search-btn{ width:90px; min-width:60px; }
.search-btn:deep(.q-btn__content){ font-weight:600; }

/* drawer */
.menu-navegacion{
  position: fixed; top:65px; left:26%; transform: translateX(-50%);
  background-color: rgba(255,255,255,.96); border-radius: 10px;
  box-shadow: 0 8px 22px rgba(0,0,0,.12); padding: 12px; z-index: 1200;
  display:flex; flex-direction:column; gap:8px; min-width: 220px;
}
.menu-item{ padding:10px; font-size:16px; font-weight:600; color:#333;
  display:flex; align-items:center; gap:10px; border-radius:8px; cursor:pointer; }
.menu-item:hover{ background:#f5f7fb; }

/* contenido */
.page-wrapper{ padding-top: 88px; max-width: 1280px; margin: 0 auto; }
.titulo-wrap{ display:flex; align-items:center; gap:12px; }
.logo{ width:56px; height:56px; object-fit:contain; border-radius:12px; box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.titulo{ font-size:22px; font-weight:800; color:#0F172A; margin:0; }
.subtitulo{ font-size:13px; color:#64748B; }
.card-elevada{ border-radius: 14px; box-shadow: 0 6px 20px rgba(16,24,40,.10); overflow: hidden; background: #fff; }
.row-active{ background: #eef2ff; }
.chips{ display:flex; flex-wrap:wrap; gap:6px; }

/* popups */
.popup{ min-width: 180px; }
.popup-title{ font-weight: 700; font-size: 14px; margin-bottom: 2px; }
.popup-sub{ font-size: 12px; color: #64748B; }
.popup-btn{ display:inline-block; font-size:12px; padding:4px 8px; border-radius:8px; background:#2563eb; color:#fff; text-decoration:none; }
.popup-btn.alt{ background:#4b5563; }

/* responsive */
@media (max-width: 768px){
  .barra-superior{ width: 95vw; }
  .titulo{ font-size:18px; }
  .logo{ width:46px; height:46px; }
}
</style>

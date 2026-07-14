<template>
  <q-page class="q-pa-md bg-grey-3">
    <!-- Encabezado -->
    <div class="row items-center q-col-gutter-sm q-mb-md">
      <div class="col-12 col-md-auto">
        <div class="text-h6 text-weight-bold">
          <q-icon name="store" class="q-mr-sm" color="primary" size="28px" />
          Agencias
          <q-badge color="primary" class="q-ml-sm">{{ agenciasFiltradas.length }}</q-badge>
        </div>
      </div>
      <q-space />
      <div class="col-12 col-sm-5 col-md-4">
        <q-input
          outlined
          dense
          debounce="300"
          v-model="filter"
          placeholder="Buscar por nombre, dirección o teléfono..."
          bg-color="white"
          clearable
        >
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
      </div>
    </div>

    <div class="row q-col-gutter-md">
      <!-- Mapa general -->
      <div class="col-12 col-md-7 col-lg-8">
        <q-card flat bordered>
          <q-card-section class="row items-center q-py-sm">
            <div class="text-subtitle1 text-weight-bold">
              <q-icon name="map" class="q-mr-xs" color="primary" />
              Mapa de agencias
            </div>
            <q-space />
            <q-btn
              dense
              outline
              no-caps
              color="grey-8"
              icon="zoom_out_map"
              label="Ver todas"
              @click="ajustarMapa"
            />
          </q-card-section>
          <q-separator />
          <l-map
            ref="mapa"
            :zoom="zoom"
            :center="centro"
            :use-global-leaflet="false"
            style="height: 480px; z-index: 0"
          >
            <l-tile-layer
              url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
              attribution="&copy; OpenStreetMap"
            />
            <l-marker
              v-for="a in agenciasConGps"
              :key="a.id"
              :lat-lng="[Number(a.latitud), Number(a.longitud)]"
              :icon="a.id === activaId ? iconos.activo : iconos.normal"
              :z-index-offset="a.id === activaId ? 1000 : 0"
              @click="enfocarAgencia(a)"
            >
              <l-tooltip>{{ a.nombre }}</l-tooltip>
              <l-popup>
                <div class="text-weight-bold">{{ a.nombre }}</div>
                <div v-if="a.direccion">{{ a.direccion }}</div>
                <div v-if="a.telefono"><b>Tel:</b> {{ a.telefono }}</div>
                <div v-if="a.horario"><b>Horario:</b> {{ a.horario }}</div>
              </l-popup>
            </l-marker>
          </l-map>
        </q-card>
      </div>

      <!-- Lista de agencias -->
      <div class="col-12 col-md-5 col-lg-4">
        <!-- Skeletons mientras carga -->
        <template v-if="cargando">
          <q-card v-for="n in 4" :key="n" flat bordered class="q-mb-sm">
            <q-card-section>
              <q-skeleton type="text" width="60%" />
              <q-skeleton type="text" width="90%" />
              <q-skeleton type="text" width="40%" />
            </q-card-section>
          </q-card>
        </template>

        <div v-else-if="agenciasFiltradas.length === 0" class="column items-center q-pa-xl text-grey-6">
          <q-icon name="storefront" size="64px" />
          <div class="text-subtitle1 q-mt-sm">No se encontraron agencias</div>
        </div>

        <q-scroll-area v-else style="height: 535px">
          <q-card
            v-for="a in agenciasFiltradas"
            :key="a.id"
            flat
            bordered
            class="q-mb-sm agencia-card cursor-pointer"
            :class="{ 'agencia-activa': a.id === activaId }"
            @click="enfocarAgencia(a)"
          >
            <q-card-section class="q-pb-xs">
              <div class="row items-center no-wrap">
                <div class="col">
                  <div class="text-weight-bold ellipsis">{{ a.nombre }}</div>
                </div>
                <q-badge
                  :color="a.status === 'ACTIVO' ? 'positive' : 'grey-6'"
                  class="q-ml-xs"
                >
                  {{ a.status || 'INACTIVO' }}
                </q-badge>
                <q-badge v-if="a.sucursal === 1" color="purple" class="q-ml-xs">
                  Casa Matriz
                </q-badge>
              </div>
              <div class="q-mt-xs text-grey-8" style="font-size: 13px">
                <div v-if="a.direccion" class="row items-center no-wrap">
                  <q-icon name="location_on" size="16px" class="q-mr-xs text-grey-6" />
                  <span class="ellipsis-2-lines">{{ a.direccion }}</span>
                </div>
                <div v-if="a.telefono" class="row items-center">
                  <q-icon name="call" size="16px" class="q-mr-xs text-grey-6" />
                  <span class="text-weight-medium">{{ a.telefono }}</span>
                </div>
                <div v-if="a.horario" class="row items-center">
                  <q-icon name="schedule" size="16px" class="q-mr-xs text-grey-6" />
                  <span>{{ a.horario }}</span>
                </div>
                <div v-if="a.atencion" class="row items-center">
                  <q-icon name="event_available" size="16px" class="q-mr-xs text-grey-6" />
                  <span>{{ a.atencion }}</span>
                </div>
              </div>
            </q-card-section>
            <q-separator />
            <q-card-actions class="q-py-xs">
              <q-btn
                v-if="a.whatsapp"
                flat
                round
                dense
                size="sm"
                color="green-7"
                icon="fab fa-whatsapp"
                :href="a.whatsapp"
                target="_blank"
                @click.stop
              >
                <q-tooltip>WhatsApp</q-tooltip>
              </q-btn>
              <q-btn
                v-if="a.facebook"
                flat
                round
                dense
                size="sm"
                color="indigo-7"
                icon="fab fa-facebook"
                :href="a.facebook"
                target="_blank"
                @click.stop
              >
                <q-tooltip>Facebook</q-tooltip>
              </q-btn>
              <q-btn
                v-if="a.gps"
                flat
                round
                dense
                size="sm"
                color="red-7"
                icon="place"
                :href="a.gps"
                target="_blank"
                @click.stop
              >
                <q-tooltip>Google Maps</q-tooltip>
              </q-btn>
              <q-space />
              <q-btn
                flat
                dense
                no-caps
                size="sm"
                color="orange"
                icon="edit"
                label="Editar"
                @click.stop="abrirEditar(a)"
              />
            </q-card-actions>
          </q-card>
        </q-scroll-area>
      </div>
    </div>

    <!-- Dialogo editar -->
    <q-dialog v-model="dialogForm" persistent :maximized="$q.screen.lt.sm" @show="refrescarMapaEdit">
      <q-card style="width: 760px; max-width: 100vw">
        <q-card-section class="bg-primary text-white row items-center q-py-sm">
          <div class="text-subtitle1 text-weight-bold">
            <q-icon name="edit" class="q-mr-sm" />
            Editar agencia
          </div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-md scroll" :style="$q.screen.lt.sm ? 'max-height: calc(100vh - 120px)' : 'max-height: 75vh'">
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-sm-8">
              <q-input v-model="form.nombre" label="Nombre *" outlined dense autofocus>
                <template v-slot:prepend><q-icon name="store" /></template>
              </q-input>
            </div>
            <div class="col-6 col-sm-2">
              <q-select
                v-model="form.status"
                :options="['ACTIVO', 'INACTIVO']"
                label="Estado"
                outlined
                dense
              />
            </div>
            <div class="col-6 col-sm-2 flex items-center">
              <q-toggle v-model="form.sucursal" :true-value="1" :false-value="0" label="Matriz" dense />
            </div>

            <div class="col-12">
              <q-input v-model="form.direccion" label="Dirección" outlined dense>
                <template v-slot:prepend><q-icon name="location_on" /></template>
              </q-input>
            </div>

            <div class="col-12 col-sm-4">
              <q-input v-model="form.telefono" label="Teléfono" outlined dense>
                <template v-slot:prepend><q-icon name="call" /></template>
              </q-input>
            </div>
            <div class="col-12 col-sm-4">
              <q-input v-model="form.atencion" label="Atención" outlined dense placeholder="Lunes a Domingo / Feriados">
                <template v-slot:prepend><q-icon name="event_available" /></template>
              </q-input>
            </div>
            <div class="col-12 col-sm-4">
              <q-input v-model="form.horario" label="Horario" outlined dense placeholder="8:00 am a 10:00 pm">
                <template v-slot:prepend><q-icon name="schedule" /></template>
              </q-input>
            </div>

            <div class="col-12 col-sm-6">
              <q-input v-model="form.whatsapp" label="Link WhatsApp" outlined dense placeholder="https://wa.link/...">
                <template v-slot:prepend><q-icon name="fab fa-whatsapp" color="green-7" /></template>
              </q-input>
            </div>
            <div class="col-12 col-sm-6">
              <q-input v-model="form.facebook" label="Link Facebook" outlined dense placeholder="https://www.facebook.com/...">
                <template v-slot:prepend><q-icon name="fab fa-facebook" color="indigo-7" /></template>
              </q-input>
            </div>

            <div class="col-12">
              <q-input v-model="form.gps" label="Link Google Maps" outlined dense placeholder="https://maps.app.goo.gl/...">
                <template v-slot:prepend><q-icon name="place" color="red-7" /></template>
              </q-input>
            </div>

            <div class="col-6">
              <q-input v-model="form.latitud" label="Latitud" outlined dense type="number" step="any">
                <template v-slot:prepend><q-icon name="my_location" /></template>
              </q-input>
            </div>
            <div class="col-6">
              <q-input v-model="form.longitud" label="Longitud" outlined dense type="number" step="any">
                <template v-slot:prepend><q-icon name="my_location" /></template>
              </q-input>
            </div>

            <div class="col-12">
              <div class="text-caption text-grey-7 q-mb-xs">
                <q-icon name="touch_app" size="16px" />
                Haga clic en el mapa o arrastre el marcador para fijar la ubicación
              </div>
              <l-map
                ref="mapaEdit"
                :zoom="16"
                :center="centroEdit"
                :use-global-leaflet="false"
                style="height: 260px; border-radius: 8px; z-index: 0"
                @click="fijarUbicacion"
              >
                <l-tile-layer
                  url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                  attribution="&copy; OpenStreetMap"
                />
                <l-marker
                  v-if="tieneUbicacion"
                  :lat-lng="[Number(form.latitud), Number(form.longitud)]"
                  :icon="iconos.activo"
                  draggable
                  @moveend="marcadorMovido"
                />
              </l-map>
            </div>
          </div>
        </q-card-section>

        <q-separator />
        <q-card-actions align="right" class="q-pa-md">
          <q-btn flat label="Cancelar" color="negative" icon="cancel" :disable="cargandoGuardar" v-close-popup no-caps />
          <q-btn
            color="orange"
            label="Actualizar"
            icon="edit"
            unelevated
            @click="guardar"
            no-caps
            :loading="cargandoGuardar"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { nextTick } from 'vue'
import { LMap, LTileLayer, LMarker, LTooltip, LPopup } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'

export default {
  name: 'AgenciaPage',
  components: { LMap, LTileLayer, LMarker, LTooltip, LPopup },
  data () {
    return {
      filter: '',
      dialogForm: false,
      cargando: true,
      cargandoGuardar: false,
      editId: null,
      activaId: null,
      zoom: 13,
      centro: [-17.9647, -67.1147], // Oruro
      form: this.formVacio(),
      iconos: { normal: null, activo: null },
      data: []
    }
  },
  computed: {
    agenciasFiltradas () {
      if (!this.filter) return this.data
      const f = this.filter.toLowerCase()
      return this.data.filter(a =>
        (a.nombre || '').toLowerCase().includes(f) ||
        (a.direccion || '').toLowerCase().includes(f) ||
        (a.telefono || '').toLowerCase().includes(f)
      )
    },
    agenciasConGps () {
      return this.agenciasFiltradas.filter(a => a.latitud && a.longitud)
    },
    tieneUbicacion () {
      return this.form.latitud && this.form.longitud
    },
    centroEdit () {
      if (this.tieneUbicacion) {
        return [Number(this.form.latitud), Number(this.form.longitud)]
      }
      return this.centro
    }
  },
  created () {
    this.construirIconos()
    this.misdatos()
  },
  methods: {
    formVacio () {
      return {
        nombre: '',
        sucursal: 0,
        direccion: '',
        telefono: '',
        atencion: '',
        horario: '',
        facebook: '',
        whatsapp: '',
        gps: '',
        latitud: '',
        longitud: '',
        status: 'ACTIVO'
      }
    },
    /* Iconos SVG sin assets externos (mismo estilo que la tienda) */
    construirIconos () {
      const mk = (fill, stroke, size) => {
        const svg = `
          <svg width="${size}" height="${size}" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
            <circle cx="24" cy="46" r="6" fill="rgba(0,0,0,0.25)" />
            <path d="M24 4C15 4 8 11 8 20c0 11 13 23 14.5 25a2 2 0 0 0 3 0C27 43 40 31 40 20c0-9-7-16-16-16z"
                  fill="${fill}" stroke="${stroke}" stroke-width="1.5" />
            <circle cx="24" cy="20" r="6" fill="white" stroke="${stroke}" stroke-width="2"/>
          </svg>`
        return L.divIcon({
          className: 'mk-agencia',
          html: svg,
          iconSize: [size, size],
          iconAnchor: [size / 2, size],
          popupAnchor: [0, -size / 2]
        })
      }
      this.iconos.normal = mk('#2563eb', '#1e40af', 38)
      this.iconos.activo = mk('#10b981', '#065f46', 46)
    },
    misdatos () {
      this.cargando = true
      this.$axios.get('agencias').then((res) => {
        this.data = res.data
        nextTick(() => this.ajustarMapa())
      }).finally(() => {
        this.cargando = false
      })
    },
    ajustarMapa () {
      const mapa = this.$refs.mapa?.leafletObject
      if (!mapa || this.agenciasConGps.length === 0) return
      const bounds = L.latLngBounds(this.agenciasConGps.map(a => [Number(a.latitud), Number(a.longitud)]))
      mapa.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 })
    },
    enfocarAgencia (a) {
      this.activaId = a.id
      if (!a.latitud || !a.longitud) return
      const mapa = this.$refs.mapa?.leafletObject
      if (mapa && mapa.flyTo) {
        mapa.flyTo([Number(a.latitud), Number(a.longitud)], 16, { duration: 0.6 })
      }
    },
    abrirEditar (a) {
      this.editId = a.id
      this.form = {
        ...this.formVacio(),
        ...Object.fromEntries(
          Object.keys(this.formVacio()).map(k => [k, a[k] ?? this.formVacio()[k]])
        ),
        sucursal: a.sucursal ?? 0,
        status: a.status || 'INACTIVO'
      }
      this.dialogForm = true
    },
    refrescarMapaEdit () {
      // Leaflet dentro de un dialogo necesita recalcular su tamaño al mostrarse
      nextTick(() => {
        setTimeout(() => {
          const mapa = this.$refs.mapaEdit?.leafletObject
          if (mapa) mapa.invalidateSize()
        }, 150)
      })
    },
    fijarUbicacion (e) {
      if (!e.latlng) return
      this.form.latitud = e.latlng.lat.toFixed(7)
      this.form.longitud = e.latlng.lng.toFixed(7)
    },
    marcadorMovido (e) {
      const pos = e.target.getLatLng()
      this.form.latitud = pos.lat.toFixed(7)
      this.form.longitud = pos.lng.toFixed(7)
    },
    guardar () {
      if (!String(this.form.nombre || '').trim()) {
        this.$q.notify({ message: 'El nombre es requerido', color: 'red', icon: 'error' })
        return
      }
      this.cargandoGuardar = true
      this.$axios.put('agencias/' + this.editId, this.form)
        .then(() => {
          this.$q.notify({ color: 'green-4', textColor: 'white', icon: 'cloud_done', message: 'Agencia actualizada correctamente' })
          this.dialogForm = false
          this.misdatos()
        }).catch(err => {
          this.$q.notify({ message: err.response?.data?.message ?? 'Error al guardar', icon: 'error', color: 'red' })
        }).finally(() => {
          this.cargandoGuardar = false
        })
    }
  }
}
</script>

<style scoped>
.agencia-card {
  transition: box-shadow 0.2s, border-color 0.2s;
}
.agencia-card:hover {
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.12);
}
.agencia-activa {
  border-color: var(--q-primary);
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.12);
}
.ellipsis-2-lines {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

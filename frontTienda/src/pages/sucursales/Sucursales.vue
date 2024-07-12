<template>
  <q-page class="q-pa-xs bg-grey-3">
    <div class="row">
      <div class="col-12">
        <label class="text-bold text-h6">Sucursales:</label>
      </div>
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <q-list>
              <q-item
                v-for="sucursal in sucursales"
                :key="sucursal.id"
                clickable
                @click="clickDetalleSucursal(sucursal)"
              >
                <q-item-section>
                  <q-item-label>
                    <div class="text-h6">{{sucursal.nombre}}</div>
                    <div class="text-caption">{{sucursal.direccion}}</div>
                  </q-item-label>
                </q-item-section>
                <q-item-section side top>
                  <q-icon name="chevron_right" />
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-6">
        <div id="map" style="height: 500px;">
          <l-map ref="map" v-model:zoom="zoom" :center="[-17.957072,-67.1217629]">
            <l-tile-layer
              url="https://mt1.google.com/vt/lyrs=r&x={x}&y={y}&z={z}"
              layer-type="base"
              name="OpenStreetMap"
            ></l-tile-layer>
            <l-marker
              v-for="sucursal in sucursales"
              :key="sucursal.id"
              :lat-lng="[sucursal.latitud, sucursal.longitud]"
              @click="clickDetalleSucursal(sucursal)">
              <LTooltip :content="sucursal.nombre" />
            </l-marker>
          </l-map>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script>
import { LMap, LTileLayer, LMarker, LTooltip } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import * as L from 'leaflet'
import 'leaflet/dist/images/marker-icon.png'
import 'leaflet/dist/images/marker-shadow.png'

export default {
  name: 'SucursalesPage',
  components: {
    LMap,
    LTileLayer,
    LMarker,
    LTooltip
  },
  data () {
    return {
      sucursales: [],
      l: L,
      zoom: 13
    }
  },
  created () {
    this.sucursalesGet()
  },
  methods: {
    sucursalesGet () {
      this.$q.loading.show()
      this.$axios.get('sucursales').then(response => {
        this.sucursales = response.data
      }).finally(() => {
        this.$q.loading.hide()
      })
    },
    clickDetalleSucursal (sucursal) {
      console.log(sucursal)
    }
  }
}
</script>

<style scoped>
/* Necesario para corregir los íconos de los marcadores de Leaflet */
.leaflet-container {
  height: 100%;
  width: 100%;
}
</style>

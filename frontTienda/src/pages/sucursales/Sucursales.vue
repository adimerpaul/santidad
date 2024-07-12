<template>
  <q-page class="q-pa-xs bg-grey-3">
    <div class="row">
      <div class="col-12">
        <label class="text-bold text-h6">Sucursales:</label>
      </div>
      <div class="col-12 col-md-5">
        <q-card>
          <q-card-section>
            <q-list>
              <q-expansion-item
                v-for="sucursal in sucursales"
                :key="sucursal.id"
                clickable
                @click="clickDetalleSucursal(sucursal)"
                :label="sucursal.nombre"
                :caption="sucursal.direccion"
                class="text-bold"
              >
                <q-item-section>
                  <q-item-label>
<!--                    <div class="text-h6">{{sucursal.nombre}}</div>-->
<!--                    direccion telefono atencion horario whatsapp facebook-->
                    <div class="text-caption">
                      <span class="text-bold text-subtitle2">Direccion</span> {{sucursal.direccion}}
                      <br>
                      <span class="text-bold text-subtitle2">Telefono</span> {{sucursal.telefono}}
                      <br>
                      <span class="text-bold text-subtitle2">Atencion</span> {{sucursal.atencion}}
                      <br>
                      <span class="text-bold text-subtitle2">Horario</span> {{sucursal.horario}}
                      <br>
<!--                      <i class="fa-brands fa-whatsapp"></i>-->
                      <q-btn flat dense color="success" icon="fa-brands fa-whatsapp" :href="sucursal.whatsapp"></q-btn>
                      <span class="text-bold text-subtitle2">Whatsapp</span> {{sucursal.whatsapp}}
                      <br>
                      <q-btn flat dense color="primary" icon="fa-brands fa-facebook" :href="sucursal.facebook"></q-btn>
                      <span class="text-bold text-subtitle2">Facebook</span> {{sucursal.facebook}}
                    </div>
                  </q-item-label>
                </q-item-section>
              </q-expansion-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-7">
        <div id="map" style="height: 500px;" v-if="!loading">
          <l-map ref="map" :zoom="zoom" :center="center">
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
              <l-tooltip :content="sucursal.nombre" />
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
import L from 'leaflet'
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
      center: [-17.957072, -67.1217629],
      l: L,
      zoom: 13,
      primerClick: true,
      loading: false
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
      this.center = [sucursal.latitud, sucursal.longitud]
      this.loading = true
      this.zoom = 16
      this.loading = false
    }
  }
}
</script>

<style scoped>
.leaflet-container {
  height: 100%;
  width: 100%;
}
</style>

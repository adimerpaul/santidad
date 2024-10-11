<template>
  <q-page class="q-pa-xs bg-grey-3">
    <div class="page-wrapper">
      <!-- Carrusel continuo arriba de la barra azul -->
      <div class="carousel-container">
        <div class="image-track">
          <img v-for="(c,i) in carouselsMini" :key="i" :src="`${$url}../images/${c.image}`" alt="Imagen 1" />
        </div>
      </div>
      <!-- Barra azul -->
      <div class="blue-bar">
        <img src="images/logo.png" alt="Logo" class="logo" />
        <!-- Botones a la derecha -->
        <div class="nav-buttons">
          <!-- Icono de carrito de compras -->
          <i class="fas fa-shopping-cart nav-icon"></i>
          <!-- Icono de lupa de búsqueda -->
          <i class="fas fa-search nav-icon"></i>
          <button class="nav-button" @click="$router.push('/')">
            INICIO
          </button>
          <button class="nav-button" @click="$router.push('/sucursales')">
            SUCURSALES
          </button>
        </div>
      </div>

      <!-- Carrusel grande debajo de la barra azul -->
    </div>
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
      carouselsMini: [],
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
    this.carouselsMiniGet()
  },
  methods: {
    carouselsMiniGet () {
      this.$axios.get('carouselsMini').then(response => {
        this.carouselsMini = response.data
      })
    },
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
}
/* Estilos para el carrusel continuo */
.carousel-container {
  width: 100vw;
  height: auto;
  overflow: hidden;
  position: relative;
  margin: 0;
  padding: 0;
  line-height: 0;
}

.image-track {
  display: flex;
  animation: scroll-left 50s linear infinite;
}

.image-track img {
  width: 7%;
  height: auto;
  object-fit: contain;
  margin-right: 125px;
}

/* Ajustes para pantallas pequeñas (tablets y celulares) */
@media only screen and (max-width: 768px) {
  .image-track img {
    width: 20%; /* Aumenta el tamaño de las imágenes en pantallas pequeñas */
    margin-right: 50px; /* Reduce el espacio entre imágenes */
  }
}

@keyframes scroll-left {
  0% { transform: translateX(0); }
  100% { transform: translateX(-75%); }
}
/* Estilos para la barra azul */
.blue-bar {
  background-color: #007bff;
  height: 60px;
  padding: 0 20px;
  display: flex;
  align-items: center;
  justify-content: space-between; /* Distribuye logo a la izquierda y botones a la derecha */
}

/* Mantén los estilos actuales del botón aquí */
.blue-bar button {
  padding: 10px 20px; /* Ajuste del espaciado para pantallas más grandes */
  font-size: 16px;    /* Tamaño de fuente inicial */
  border: none;
  color: white;       /* Color de texto */
  background-color: #0056b3; /* Color de fondo */
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.3s ease;
}

/* Efectos de hover del botón (mantén tus efectos actuales) */
.blue-bar button:hover {
  background-color: #003f7f; /* Color al hacer hover */
  transform: scale(1.05);    /* Efecto de aumento al pasar el mouse */
}

/* Responsividad para pantallas medianas */
@media (max-width: 768px) {
  .blue-bar button {
    padding: 6px 11px;    /* Reducimos el padding */
    font-size: 9px;      /* Reducimos el tamaño de fuente */
  }
}
/* Responsividad para pantallas pequeñas (teléfonos móviles) */
@media (max-width: 480px) {
  .blue-bar button {
    padding: 6px 11px;    /* Reducimos más el padding */
    font-size: 9px;      /* Reducimos más el tamaño de fuente */
  }

  /* Ajustar la barra para pantallas pequeñas si es necesario */
  .blue-bar {
    flex-direction: column;   /* Opcional: Disposición en columna */
    align-items: flex-start;  /* Alinear los elementos a la izquierda en pantallas pequeñas */
    height: auto;             /* Ajustar la altura */
  }
}

.blue-bar .logo {
  width: 100px;
  height: auto;
  margin-right: 20px;
}

.nav-buttons {
  display: flex;
  align-items: center;
}

.nav-button {
  background-color: white;
  color: #007bff;
  border: none;
  padding: 10px 20px;
  margin-left: 10px;
  cursor: pointer;
  border-radius: 5px;
  font-weight: bold;
}

.nav-button:hover {
  background-color: #0056b3;
  color: white;
}

/* Estilo para los íconos de carrito y lupa */
.nav-icon {
  color: white;
  font-size: 20px;
  margin-right: 15px;
  cursor: pointer;
}

.nav-icon:hover {
  color: #0056b3;
}

/* Estilos para el carrusel grande */
.carousel-container-large {
  margin: 0;
  padding: 0;
  width: 100vw;
}

img {
  width: 100%;
  height: auto;
  object-fit: cover;
}

.q-carousel-slide {
  margin: 0;
  padding: 0;
  display: flex;
  justify-content: center;
  align-items: center;
}

/* Asegúrate de que no haya espacio entre los elementos */
.page-wrapper {
  display: flex;
  flex-direction: column;
}

/* Eliminamos cualquier espacio entre elementos */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}
</style>

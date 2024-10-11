<template>
  <q-page class="q-pa-none">
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
    <div class="carousel-container-large">
      <q-carousel
          animated
          v-model="slide"
          arrows
          navigation
          infinite
          transition-prev="slide-right"
          transition-next="slide-left"
          height="auto"
          autoplay
          autoplay-interval="5000"
      >
<!--        <q-carousel-slide :name="1">-->
<!--          <img src="images/1.png" alt="Imagen 1" />-->
<!--        </q-carousel-slide>-->
<!--        <q-carousel-slide :name="2">-->
<!--          <img src="images/1.png" alt="Imagen 2" />-->
<!--        </q-carousel-slide>-->
<!--        <q-carousel-slide :name="3">-->
<!--          <img src="images/1.png" alt="Imagen 3" />-->
<!--        </q-carousel-slide>-->
<!--        <q-carousel-slide :name="4">-->
<!--          <img src="images/1.png" alt="Imagen 4" />-->
<!--        </q-carousel-slide>-->
        <q-carousel-slide v-for="(c,i) in carousels" :key="i" :name="i+1">
          <q-img :src="`${$url}../images/${c.image}`" />
        </q-carousel-slide>
      </q-carousel>
    </div>
<!--    <div class="">-->
<!--      <q-carousel-->
<!--          animated-->
<!--          v-model="slide"-->
<!--          arrows-->
<!--          navigation-->
<!--          height="auto"-->
<!--      >-->
<!--&lt;!&ndash;        <q-carousel-slide :name="1">&ndash;&gt;-->
<!--&lt;!&ndash;          <img src="images/1.png" alt="Imagen 1" />&ndash;&gt;-->
<!--&lt;!&ndash;        </q-carousel-slide>&ndash;&gt;-->
<!--        <q-carousel-slide v-for="(c,i) in carousels" :key="i" :name="i">-->
<!--          <q-img :src="`${$url}../images/${c.image}`" />-->
<!--        </q-carousel-slide>-->
<!--      </q-carousel>-->
<!--&lt;!&ndash;      <pre>{{slide}}</pre>&ndash;&gt;-->
<!--    </div>-->
    <div class="row bg-grey-3">
<!--      <div class="col-12 col-md-2"></div>-->
<!--      <div class="col-12" >-->
<!--        <q-carousel-->
<!--          animated-->
<!--          v-model="slide"-->
<!--          arrows-->
<!--          navigation-->
<!--          navigation-icon="radio_button_unchecked"-->
<!--          control-text-color="primary"-->
<!--          autoplay-->
<!--          infinite-->
<!--          height="auto"-->
<!--        >-->
<!--&lt;!&ndash;          <q-carousel-slide :name="i++" v-for="(c,i) in carousels" :key="i++" class="cursor-pointer q-pa-xs"&ndash;&gt;-->
<!--&lt;!&ndash;                            :img-src="$q.screen.lt.md ?`${$url}../images/${c.imageResponsive}`:`${$url}../images/${c.image}`"&ndash;&gt;-->
<!--&lt;!&ndash;          />&ndash;&gt;-->
<!--          <q-carousel-slide :name="1">-->
<!--            <img src="images/1.png" alt="Imagen 1" />-->
<!--          </q-carousel-slide>-->
<!--          <q-carousel-slide :name="2">-->
<!--            <img src="images/1.png" alt="Imagen 2" />-->
<!--          </q-carousel-slide>-->
<!--          <q-carousel-slide :name="3">-->
<!--            <img src="images/1.png" alt="Imagen 3" />-->
<!--          </q-carousel-slide>-->
<!--          <q-carousel-slide :name="4">-->
<!--            <img src="images/1.png" alt="Imagen 4" />-->
<!--          </q-carousel-slide>-->
<!--        </q-carousel>-->
<!--&lt;!&ndash;        <pre>{{slide}}</pre>&ndash;&gt;-->
<!--&lt;!&ndash;        <div v-for="(c,i) in carousels" :key="i">&ndash;&gt;-->
<!--&lt;!&ndash;&lt;!&ndash;          <pre>{{ i }}</pre>&ndash;&gt;&ndash;&gt;-->
<!--&lt;!&ndash;          <q-img :src="`${$url}../images/${c.image}`" height="10%" :style="`display: ${slide === i ? '' : 'none'}`">&ndash;&gt;-->
<!--&lt;!&ndash;&lt;!&ndash;            colocar 2 botones a la izuiqerda ya ala derecha para&ndash;&gt;&ndash;&gt;-->
<!--&lt;!&ndash;            <div class="absolute-top-left q-ma-xs">&ndash;&gt;-->
<!--&lt;!&ndash;              <q-btn&ndash;&gt;-->
<!--&lt;!&ndash;                flat&ndash;&gt;-->
<!--&lt;!&ndash;                dense&ndash;&gt;-->
<!--&lt;!&ndash;                round&ndash;&gt;-->
<!--&lt;!&ndash;                icon="chevron_left"&ndash;&gt;-->
<!--&lt;!&ndash;                aria-label="Previous"&ndash;&gt;-->
<!--&lt;!&ndash;                color="white"&ndash;&gt;-->
<!--&lt;!&ndash;                @click="slide&#45;&#45;"&ndash;&gt;-->
<!--&lt;!&ndash;                v-if="i>0"&ndash;&gt;-->
<!--&lt;!&ndash;              />&ndash;&gt;-->
<!--&lt;!&ndash;            </div>&ndash;&gt;-->
<!--&lt;!&ndash;            <div class="absolute-top-right q-ma-xs">&ndash;&gt;-->
<!--&lt;!&ndash;              <q-btn&ndash;&gt;-->
<!--&lt;!&ndash;                flat&ndash;&gt;-->
<!--&lt;!&ndash;                dense&ndash;&gt;-->
<!--&lt;!&ndash;                round&ndash;&gt;-->
<!--&lt;!&ndash;                icon="chevron_right"&ndash;&gt;-->
<!--&lt;!&ndash;                aria-label="Next"&ndash;&gt;-->
<!--&lt;!&ndash;                color="white"&ndash;&gt;-->
<!--&lt;!&ndash;                @click="slide++"&ndash;&gt;-->
<!--&lt;!&ndash;                v-if="i<carousels.length-1"&ndash;&gt;-->
<!--&lt;!&ndash;              />&ndash;&gt;-->
<!--&lt;!&ndash;            </div>&ndash;&gt;-->
<!--&lt;!&ndash;          </q-img>&ndash;&gt;-->
<!--&lt;!&ndash;&lt;!&ndash;          <pre>{{c}}</pre>&ndash;&gt;&ndash;&gt;-->
<!--&lt;!&ndash;        </div>&ndash;&gt;-->
<!--      </div>-->
<!--      <div class="col-12 col-md-2"></div>-->
    </div>
    <div class="row q-pa-xs">
      <div class="col-12">
        <div class="text-h6 text-center text-bold no-select flex flex-center">
          <q-input
          v-model="search"
          dense
          style="width: 70%; margin-top: 5px; margin-bottom: 5px;"
          outlined
          bg-color="grey-4"
          rounded
          @keyup.enter="buscar"
          placeholder="Buscar Producto / Palabra Clave"
          class="search-input q-ml-sm">
          <template v-slot:prepend>
            <q-icon name="search" />
          </template>
        </q-input>
          <q-btn
            rounded
            style="width: 90px"
            @click="buscar"
            color="primary"
            label="Buscar"
            :loading="loading"
            no-caps/>
        </div>
      </div>
      <div class="col-12 flex flex-center" v-if="totalPages > 1" >
        <q-pagination
          rounded
          v-if="totalPages > 1"
          v-model="currentPage"
          :max="totalPages"
          :max-pages="6"
          @update:model-value="buscar"
          boundary-numbers
          ellipses
        />
      </div>
      <div class="col-12">
<!--        <q-card>-->
<!--          <q-card-section class="q-pa-xs">-->
          <div class="row cursor-pointer q-pa-md" v-if="$store.products.length>0">
            <div class="col-6 col-md-3 q-pa-md" v-for="p in $store.products" :key="p.id">
              <q-card @click="clickDetalleProducto(p)" flat bordered class="q-pa-md" style="border: 2px solid #00BD73;padding: 5px; margin: 10px">
                <q-img :src="p.imagen.includes('http')?p.imagen:`${$url}../images/${p.imagen}`" width="100%" height="130px">
<!--                  <div class="absolute-bottom text-center text-subtitle2" style="padding: 0px 0px;line-height: 1;">-->
<!--                    {{p.nombre}}-->
<!--                  </div>-->
                  <q-badge color="red" floating style="padding: 10px 10px 5px 5px;margin: 0px" v-if="p.porcentaje">
                    {{p.porcentaje}}%
                  </q-badge>
                </q-img>
                <div class="text-center text-bold" style="line-height: 1;font-size: 14px;height: 30px">
                  {{p.nombre}}
                </div>
                <q-card-section class="q-pa-none q-ma-none">
                  <div class="text-left bg-white" style="font-size: 13px">Bs. {{p.precio}}</div>
                  <q-btn
                    @click="clickDetalleProducto(p)"
                    label="Añadir al carrito"
                    icon="add_shopping_cart"
                    class="full-width"
                    color="green"
                    size="10px"
                    no-caps
                    dense/>
                </q-card-section>
              </q-card>
            </div>
          </div>
        <div class="text-center" v-else>
<!--          <q-icon name="sentiment_very_dissatisfied" size="100px" color="grey-5" />-->
          <div class="text-h6">No hay productos</div>
        </div>
<!--          </q-card-section>-->
<!--        </q-card>-->
      </div>
    </div>
  </q-page>
</template>

<script>
export default {
  name: 'IndexPage',
  data () {
    return {
      slide: 1,
      carousels: [],
      carouselsMini: [],
      // products: [],
      search: '',
      loading: false,
      currentPage: 1,
      totalPages: 1
    }
  },
  created () {
    this.carouselsGet()
    this.buscar()
    // carouselsMini
    this.carouselsMiniGet()
  },
  methods: {
    carouselsMiniGet () {
      this.$axios.get('carouselsMini').then(response => {
        this.carouselsMini = response.data
      })
    },
    clickDetalleProducto (p) {
      this.$router.push('/detalle-producto/' + p.id + '/' + this.espacioCambioGuion(p.nombre))
    },
    espacioCambioGuion (text) {
      return text.replace(/ |\/+/g, '-')
    },
    buscar () {
      this.loading = true
      this.$axios.get('productos', {
        params: {
          search: this.search,
          page: this.currentPage
        }
      }).then(response => {
        this.$store.products = []
        // this.products = response.data.data
        // this.$store.products = response.data.data
        this.totalPages = response.data.last_page
        response.data.data.forEach(p => {
          const esPorcentaje = p.porcentaje > 0
          if (esPorcentaje) {
            const precio = p.precio - (p.precio * p.porcentaje / 100)
            p.precio = precio.toFixed(2)
          }
          this.$store.products.push(p)
        })
      }).finally(() => {
        this.loading = false
      })
    },
    carouselsGet () {
      // this.loading = true
      this.$axios.get('carouselsPage').then(response => {
        this.carousels = response.data
      }).finally(() => {
        // this.loading = false
      })
    }
  }
}
</script>
<style scoped>
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
  height: 60px; /* Ajusta esta altura si es necesario */
  padding: 0 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  max-width: 100vw; /* Evita que la barra se extienda más allá de la ventana */
  box-sizing: border-box;
  overflow: hidden; /* Evita cualquier desbordamiento dentro de la barra */
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

.blue-bar, .q-page {
    width: 100%; /* Aseguramos que todos los contenedores principales se ajusten al ancho de la pantalla */
    max-width: 100vw; /* Limita el ancho máximo al tamaño de la ventana del navegador */
    overflow-x: hidden; /* Evita cualquier desbordamiento horizontal */
}
.search-container {
  width: 50%;
  margin: 40px auto 0 auto; /* Añade 40px de espacio en la parte superior, ajusta según lo que necesites */
  display: flex;
  justify-content: center;
}

@media (max-width: 1024px) {
  .search-container {
    width: 70%;
    margin-top: 30px; /* Ajusta el margen superior para tablets si es necesario */
  }
}

@media (max-width: 768px) {
  .search-container {
    width: 70%;
    margin-top: 20px; /* Ajusta el margen superior para pantallas móviles */
  }
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
img {
  width: 100%;
  height: auto;
  max-height: 566px;
  object-fit: cover;
}
/* Estilos para el carrusel grande */
.carousel-container-large {
  aspect-ratio: 16 / 5; /* Mantiene una proporción de 16:9 */
  width: 100vw;
  overflow: hidden; /* Evitar desbordamiento de contenido */
}

.q-carousel-slide {
  margin: 0;
  padding: 0;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: transform 0.5s ease-in-out; /* Suaviza la transición */
}

</style>

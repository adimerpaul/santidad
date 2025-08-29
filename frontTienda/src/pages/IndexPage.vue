<template>

  <q-page class="q-pa-none">
    <div class="page-wrapper">
      <!-- Carrusel principal -->
 <!-- Carrusel grande debajo de la barra azul -->
      <div class="carousel-container-large">
        <Swiper
          :modules="modules"
          :loop="true"
          :autoplay="{ delay: 5000, disableOnInteraction: false }"
          :speed="700"
          navigation
          pagination
          class="hero-swiper"
        >
          <SwiperSlide v-for="(c,i) in carousels" :key="i">
            <!-- Usa tus mismas rutas con $url -->
            <img :src="`${$url}../images/${c.image}`" alt="" />
          </SwiperSlide>
        </Swiper>
      </div>
        <!-- 🔥 BARRA SUPERIOR con menú hamburguesa y buscador -->
        <div class="barra-superior">
          <!-- Ícono del menú hamburguesa -->
          <q-btn
            flat
            round
            dense
            icon="menu"
            @click="toggleDrawer"
            class="menu-hamburguesa"
            size="md"
          />

          <!-- 🔍 Buscador -->
          <div class="search-container">
            <q-input
              v-model="search"
              dense
              outlined
              rounded
              @keyup.enter="buscar"
              placeholder="Buscar Producto / Palabra Clave"
              class="search-input q-ml-sm"
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
            <q-btn
              label="Buscar"
              rounded
              :loading="loading"
              @click="buscar"
              class="q-ml-sm"
              no-caps
            />
          </div>
        </div>
      <!-- Menú desplegable cuando el drawer está activado -->
 <!-- Menú desplegable cuando el drawer está activado -->
        <div v-if="drawer" class="menu-navegacion">
          <div class="menu-item" @click="navegarA('/')">
            <q-icon name="home" class="q-mr-sm" />
            Inicio
          </div>

          <div class="menu-item" @click="navegarA('/sucursales')">
            <q-icon name="store" class="q-mr-sm" />
            Sucursales
          </div>
            <!-- 🔹 Nueva opción que hace scroll -->
              <div class="menu-item" @click="scrollCategorias">
                <q-icon name="category" class="q-mr-sm" />
                Categorías
              </div>
            </div>
      </div>
    <!-- Nueva sección debajo del carrusel grande -->
<!-- Servicios (reemplaza tu <div class="servicios">...</div>) -->
<Swiper
  class="services-swiper pharma"
  :modules="modules"
  :loop="carousels.length > 1"
  :autoplay="{ delay: 3500, disableOnInteraction: false }"
  :speed="550"
  :space-between="16"
  navigation
  pagination
  :breakpoints="{
    0:     { slidesPerView: 1 },
    640:   { slidesPerView: 2 },
    1024:  { slidesPerView: 3 },
    1280:  { slidesPerView: 4 }
  }"
>
  <SwiperSlide v-for="(s, i) in services" :key="'srv-'+i">
    <article class="servicio-card--pharma" :aria-label="s.title">
      <div class="servicio-header">
        <div class="servicio-icon">
          <q-icon :name="s.icon" size="28px" />
        </div>
        <h3 class="servicio-title">{{ s.title }}</h3>
      </div>

      <p class="servicio-desc">{{ s.desc }}</p>

      <q-btn
        outline
        no-caps
        color="primary"
        class="servicio-cta"
        label="Más información"
      />
    </article>
  </SwiperSlide>
</Swiper>
            <div class="titulo-categorias texto-estilo-comun">
            Explora Nuestros Descuentos
            </div>
<!-- Carrusel de Productos con Swiper -->
<Swiper
  class="descuentos-swiper"
  :key="$q.screen.name + '-' + productosCarrusel.length"
  :modules="modules"
  :loop="productosCarrusel.length > 4"
  :autoplay="{ delay: 3500, disableOnInteraction: false }"
  :speed="600"
  :space-between="16"
  navigation
  pagination
  grabCursor

  :slides-per-view="5"
  :slides-per-group="5"

  :observer="true"
  :observe-parents="true"
  :update-on-window-resize="true"
  :watch-slides-progress="true"
  :breakpoints="{
  0:    { slidesPerView: 1.15, slidesPerGroup: 1,
            centeredSlides: true, centeredSlidesBounds: true,
            slidesOffsetBefore: 24, slidesOffsetAfter: 24 },

    480:  { slidesPerView: 2, slidesPerGroup: 2,
            centeredSlides: true, centeredSlidesBounds: true,
            slidesOffsetBefore: 16, slidesOffsetAfter: 16 },

    768:  { slidesPerView: 3, slidesPerGroup: 3,
            centeredSlides: false, slidesOffsetBefore: 0, slidesOffsetAfter: 0 },

    1024: { slidesPerView: 4, slidesPerGroup: 4,
            centeredSlides: false, slidesOffsetBefore: 0, slidesOffsetAfter: 0 },

    1280: { slidesPerView: 5, slidesPerGroup: 5,
            centeredSlides: false, slidesOffsetBefore: 0, slidesOffsetAfter: 0 }
  }"
>
  <!-- 🔥 Recorremos los productos -->
  <SwiperSlide v-for="p in productosCarrusel" :key="p.id">
    <q-card class="product-card-personalizada small" flat @click="clickDetalleProducto(p)">
      <q-img
        :src="p.imagen.includes('http') ? p.imagen : `${$url}../images/${p.imagen}`"
        style="height: 160px; object-fit: cover; position: relative;"
      >
        <div v-if="p.cantidad === 0" class="out-of-stock-overlay">
          <span class="out-of-stock-text">Sin Stock</span>
        </div>
        <q-badge v-if="p.porcentaje" color="red" floating>
          -{{ p.porcentaje }}%
        </q-badge>
      </q-img>

      <q-card-section class="q-pa-sm text-center">
        <div class="product-name">{{ p.nombre }}</div>
        <div class="product-prices">
          <div class="price-old" v-if="p.precioNormal">
            <span>Antes</span><br />
            <strong>Bs. {{ p.precioNormal }}</strong>
          </div>
          <div class="price-now">
            <span>Ahora</span><br />
            <strong>Bs. {{ p.precio }}</strong>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </SwiperSlide>
</Swiper>

            <div class="categorias-grid">
             <!-- Título categorías -->
            <div id="seccion-categorias" class="titulo-categorias texto-estilo-comun">
            Explorá nuestras categorías
             </div>

    <!-- 📱 SOLO MÓVIL: Swiper de categorías -->
    <div class="categorias-swiper-wrapper">
      <Swiper
        class="categorias-swiper"
        :modules="modules"
        :loop="categoriasVisibles.length > 1"
        :autoplay="{ delay: 3000, disableOnInteraction: false }"
        :speed="500"
        :space-between="12"
        pagination
        :breakpoints="{
          0:   { slidesPerView: 2, spaceBetween: 12 },
          480: { slidesPerView: 3, spaceBetween: 14 },
          640: { slidesPerView: 4, spaceBetween: 16 }
        }"
      >
        <SwiperSlide v-for="(cat, i) in categoriasVisibles" :key="'mobcat-' + cat.id">
          <router-link :to="`/categoria/${cat.id}`" class="categoria-card">
            <img
              :src="`/images/${i + 1}.jpg`"
              class="imagen-categoria"
              @error="$event.target.src = '/images/categoria.jpg'"
            />
            <div class="nombre-categoria">{{ cat.name }}</div>
          </router-link>
        </SwiperSlide>
      </Swiper>
    </div>
        <div class="grid-categorias-limpio">
          <!-- aquí tus <router-link> de cada categoría -->
        </div>
          </div>
          <div class="grid-categorias-limpio">
        <router-link
            v-for="(cat, i) in categoriasVisibles"
            :key="'cat-' + cat.id"
            :to="`/categoria/${cat.id}`"
            class="categoria-card"
          >
            <img
              :src="`/images/${i + 1}.jpg`"
              class="imagen-categoria"
              @error="$event.target.src = '/images/categoria.jpg'"
            />
            <div class="nombre-categoria">{{ cat.name }}</div>
          </router-link>

      </div>
          <!-- Banner “Medio” dinámico -->
<div class="banner-promocional">
  <template v-if="carouselsMedio.length">
    <Swiper
      class="banner-swiper"
      :modules="modules"
      :loop="carouselsMedio.length > 1"
      :autoplay="{ delay: 4000, disableOnInteraction: false }"
      :speed="650"
      navigation
      pagination
    >
      <SwiperSlide v-for="(b, i) in carouselsMedio" :key="'ban-'+i">
        <img
          :src="`${$url}../images/${b.image}`"
          alt="Banner"
          class="banner-imagen"
        />
        <div class="banner-overlay"></div>
      </SwiperSlide>
    </Swiper>
  </template>

  <!-- Fallback si no hay “Medio” -->
  <template v-else>
    <img src="/images/banner.jpg" alt="Banner" class="banner-imagen" />
    <div class="banner-overlay"></div>
  </template>
   </div>
          <!-- Título igual al de descuentos (mismo estilo) -->
    <div class="titulo-categorias texto-estilo-comun">
      Productos más vendidos
    </div>
              <!-- ⚠️ Mismo Swiper que “Nuestros Descuentos”: misma clase, mismos props -->
    <Swiper
      class="descuentos-swiper"
      :key="'top-' + topVendidos.length"
      :modules="modules"
      :loop="topVendidos.length > 4"
      :autoplay="{ delay: 3500, disableOnInteraction: false }"
      :speed="600"
      :space-between="16"
      navigation
      pagination
      grabCursor

      :slides-per-view="5"
      :slides-per-group="5"

      :observer="true"
      :observe-parents="true"
      :update-on-window-resize="true"
      :watch-slides-progress="true"
      :breakpoints="{
        0:    { slidesPerView: 1.15, slidesPerGroup: 1,
                centeredSlides: true, centeredSlidesBounds: true,
                slidesOffsetBefore: 24, slidesOffsetAfter: 24 },

        480:  { slidesPerView: 2, slidesPerGroup: 2,
                centeredSlides: true, centeredSlidesBounds: true,
                slidesOffsetBefore: 16, slidesOffsetAfter: 16 },

        768:  { slidesPerView: 3, slidesPerGroup: 3,
                centeredSlides: false, slidesOffsetBefore: 0, slidesOffsetAfter: 0 },

        1024: { slidesPerView: 4, slidesPerGroup: 4,
                centeredSlides: false, slidesOffsetBefore: 0, slidesOffsetAfter: 0 },

        1280: { slidesPerView: 5, slidesPerGroup: 5,
                centeredSlides: false, slidesOffsetBefore: 0, slidesOffsetAfter: 0 }
      }"
    >
      <SwiperSlide v-for="p in topVendidos" :key="'tv-' + p.id">
        <!-- ⚠️ Mismo card y clases que usas en descuentos -->
        <q-card class="product-card-personalizada small" flat @click="clickDetalleProducto(p)">
          <q-img
            :src="p.imagen.includes('http') ? p.imagen : `${$url}../images/${p.imagen}`"
            style="height: 160px; object-fit: cover; position: relative;"
          >
            <div v-if="Number(p.cantidad) === 0" class="out-of-stock-overlay">
              <span class="out-of-stock-text">Sin Stock</span>
            </div>
            <q-badge v-if="Number(p.porcentaje) > 0" color="red" floating>
              -{{ p.porcentaje }}%
            </q-badge>
          </q-img>

      <q-card-section class="q-pa-sm text-center">
        <div class="product-name">{{ p.nombre }}</div>
        <div class="product-prices">
          <div class="price-old" v-if="p.precioNormal">
            <span>Antes</span><br />
            <strong>Bs. {{ p.precioNormal }}</strong>
          </div>
          <div class="price-now">
            <span>Ahora</span><br />
            <strong>Bs. {{ p.precio }}</strong>
          </div>
        </div>
      </q-card-section>
    </q-card>
  </SwiperSlide>
</Swiper>
            <div class="titulo-categorias texto-estilo-comun">
            Laboratorios y Distribuidoras
            </div>
      <!-- Carrusel continuo abajo de todo -->
      <div class="carousel-container">
        <div class="image-track">
          <img v-for="(c,i) in carouselsMini" :key="i" :src="`${$url}../images/${c.image}`" alt="Imagen" />
        </div>
      </div>
  </q-page>
</template>
<script>
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay, Navigation, Pagination } from 'swiper/modules'

// estilos base de Swiper
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'

export default {
  name: 'IndexPage',
  components: { Swiper, SwiperSlide }, // 👈 AÑADE ESTO
  data () {
    return {
      slide: 0,
      carousels: [],
      carouselsMini: [],
      carouselsMedio: [],
      slideSimple: 0,
      // products: [],
      search: '',
      loading: false,
      currentPage: 1,
      totalPages: 1,
      drawer: false, // 👈 aquí el nuevo dato
      categories: [],
      category: 0,
      pSlide: 0,
      productosCarrusel: [],
      intervalProdCoverflow: null,
      modules: [Autoplay, Navigation, Pagination],
      services: [
        { icon: 'accessibility', title: 'Atención 24/7', desc: 'Estamos disponibles para ti, en cualquier momento' },
        { icon: 'price_check', title: 'Cotiza con nosotros', desc: 'Solicita una cotización rápida y sin compromiso' },
        { icon: 'local_offer', title: 'Descuentos por volumen', desc: 'Aprovecha grandes descuentos en compras a granel' },
        { icon: 'mobile_friendly', title: 'Aplicación Móvil', desc: 'Compra desde donde estés con nuestra app móvil' }
      ],
      topVendidos: []

    }
  },
  watch: {
    '$store.products': {
      handler () { this.buildProductosCarrusel() },
      deep: true
    }
  },
  mounted () {
    this.carouselsGet()
    this.carouselsMiniGet()
    this.getCategories()
    this.buildProductosCarrusel()
    this.cargarDescuentosHome()
    this.fetchTopVendidos()
    this.carouselsMedioGet()
  },
  beforeUnmount () {
    clearInterval(this.intervalCoverflow)
  },
  methods:
    {
      toggleDrawer () {
        this.drawer = !this.drawer
      },
      navegarA (ruta) {
        this.$router.push(ruta)
        this.drawer = false // Cierra el menú después de navegar
      },
      carouselsMiniGet () {
        this.$axios.get('carouselsMini').then(response => {
          this.carouselsMini = response.data
        })
      },
      prevSlide () {
        this.slide = (this.slide - 1 + this.carousels.length) % this.carousels.length
      },
      nextSlide () {
        this.slide = (this.slide + 1) % this.carousels.length
      },
      clickDetalleProducto (p) {
        this.$router.push('/detalle-producto/' + p.id + '/' + this.espacioCambioGuion(p.nombre))
      },
      espacioCambioGuion (text) {
        // Reemplaza espacios y barras por guiones y elimina puntos y comas
        const texto = text.replace(/ |\/|\./g, '-').replace(/,/g, '')
        return texto
      },
      buscar () {
        const term = (this.search || '').trim()
        if (!term) return
        // abre la página de resultados con la query
        this.$router.push({ name: 'buscar', query: { q: term, page: 1 } })
        this.drawer = false
      },
      carouselsGet () {
        // this.loading = true
        this.$axios.get('carouselsPage').then(response => {
          this.carousels = response.data
        }).finally(() => {
          // this.loading = false
        })
      },
      async getCategories () {
        this.loading = true
        try {
          const res = await this.$axios.get('/categories')
          this.categories = res.data
        } catch (err) {
          console.error('❌ Error al cargar categorías', err)
          this.categories = [] // evitar que quede con datos anteriores si falla
        } finally {
          this.loading = false
        }
      },
      buildProductosCarrusel () {
        const src = this.$store.products || []
        const isTrue = v => v === true || v === 1 || v === '1' || v === 'ACTIVO'

        // Solo ACTIVO + EN OFERTA
        this.productosCarrusel = src.filter(p => isTrue(p.activo) && isTrue(p.en_oferta))
      },

      prevProdSlide () {
        const n = this.productosCarrusel.length
        if (!n) return
        this.pSlide = (this.pSlide - 1 + n) % n
      },
      nextProdSlide () {
        const n = this.productosCarrusel.length
        if (!n) return
        this.pSlide = (this.pSlide + 1) % n
      },

      async fetchTopVendidos () {
        try {
          const { data } = await this.$axios.get('top-sellers', { params: { days: 7 } }) // 7 DIAS
          // ⚠️ NORMALIZACIÓN IGUAL A DESCUENTOS:
          this.topVendidos = (data || []).map(p => {
            const x = { ...p }
            x.porcentaje = Number(x.porcentaje || 0)
            x.precio = Number(x.precio)
            if (x.precioNormal != null) x.precioNormal = Number(x.precioNormal)
            return x
          })
        } catch (e) {
          console.error('Error cargando top vendidos', e)
          this.topVendidos = []
        }
      },
      scrollCategorias () {
        const el = document.getElementById('seccion-categorias')
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' })
        }
        this.drawer = false // cerrar el menú después de navegar
      },
      async cargarDescuentosHome () {
        this.loading = true
        try {
          const res = await this.$axios.get('productos', { params: { page: 1, per_page: 200 } })

          this.$store.products = (res.data?.data || res.data || []).map(p => {
            const x = { ...p }

            // ✅ normalizaciones robustas
            x.en_oferta = x.en_oferta === true || x.en_oferta === 'true' || Number(x.en_oferta) === 1
            x.porcentaje = Number(p.porcentaje ?? 0)

            // valores base desde backend
            const precioBase = Number(p.precio ?? 0)
            const precioAntes = (p.precioAntes !== null && p.precioAntes !== undefined && p.precioAntes !== '')
              ? Number(p.precioAntes)
              : null

            // 🧮 lógica de precios “Antes/Ahora”
            if (x.porcentaje > 0) {
              // hay descuento por porcentaje
              if (precioAntes != null && precioAntes > 0) {
                x.precioNormal = Number(precioAntes).toFixed(2) // Antes
                x.precio = (precioAntes * (1 - x.porcentaje / 100)).toFixed(2) // Ahora
              } else {
                x.precioNormal = Number(precioBase).toFixed(2) // Antes = precio base
                x.precio = (precioBase * (1 - x.porcentaje / 100)).toFixed(2) // Ahora
              }
            } else {
              // sin porcentaje; mostrar “Antes” sólo si viene desde el backend
              if (precioAntes != null && precioAntes > 0) {
                x.precioNormal = Number(precioAntes).toFixed(2) // Antes
                x.precio = Number(precioBase).toFixed(2) // Ahora = precio actual
              } else {
                x.precioNormal = null // no mostrar “Antes”
                x.precio = Number(precioBase).toFixed(2)
              }
            }

            return x
          })

          this.buildProductosCarrusel()
        } catch (e) {
          console.error('Error cargando productos home', e)
          this.$store.products = []
          this.productosCarrusel = []
        } finally {
          this.loading = false
        }
      },
      carouselsMedioGet () {
        this.$axios.get('carouselsMedio')
          .then(res => { this.carouselsMedio = res.data || [] })
          .catch(err => {
            console.error('❌ Error carouselsMedio', err)
            this.carouselsMedio = []
          })
      }

    },
  computed: {
    categoriasVisibles () {
      // Omitimos la primera (índice 0) y tomamos solo 8
      return this.categories.slice(1, 9)
    },
    productId () {
      return this.$route.params.id
    }
  }

}

</script>
<style scoped>

/* Estilos para el carrusel continuo */
.carousel-container {
  overflow: hidden;
  position: relative;
  margin: 0;
  padding: 50px;
  line-height: 0;
  margin-top: 0px; /* Espacio superior */
  margin-bottom: 0px; /* Espacio inferior */
}

/* ---- Carrusel mini: tamaño 100% fluido sin breakpoints ---- */

/* 1) Tamaño base “auto” con clamp */
.image-track {
  --logo-h: clamp(40px, 8vw, 85px);   /* alto del logo: min 60px, preferido 8vw, max 140px */
  --gap: calc(var(--logo-h) * .6);     /* separación proporcional al tamaño */
  display: inline-flex;
  width: max-content;
  will-change: transform;
  animation: scroll-left 40s linear infinite;
}

.image-track img {
  height: var(--logo-h);   /* << controla el tamaño con una sola variable */
  width: auto;
  margin-right: var(--gap);/* << espacio entre imágenes ligado al tamaño */
  object-fit: contain;
  transition: transform .3s ease, box-shadow .3s ease;
  transform: rotateY(-10deg) scale(.95);
  filter: drop-shadow(0 5px 10px rgba(0,0,0,.3));
  border-radius: 12px;
}

.image-track img:hover {
  transform: rotateY(0deg) scale(1.06);
  box-shadow: 0 15px 30px rgba(0,0,0,.35);
  z-index: 10;
}

/* Si duplicaste el track para loop perfecto, usa -50% */
@keyframes scroll-left {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

/* Accesibilidad: sin animación si el usuario lo pide */
@media (prefers-reduced-motion: reduce) {
  .image-track { animation-duration: .001ms; animation-iteration-count: 1; }
}

/* CARD DE CADA PRODUCTO*/
.contenedor-productos {
  padding: 0 24px;
  max-width: 90%;
  margin: 0 auto;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 20px;
}

.product-card-personalizada {
  width: 200px;
  height: 320px;
  margin: auto;
  transition: transform 0.3s;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  overflow: hidden;
  border-radius: 12px;
  border: 2px solid #2D9CDB;
}
.product-card-personalizada:hover {
  transform: scale(1.02);
  z-index: 3;
}

/* TEXTO DEL CARD*/

.product-name {
  font-weight: 600;
  font-size: 15px;
  margin-bottom: 8px;
  color: #333;
  height: 40px;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.2;
}

.product-prices {
  display: flex;
  justify-content: center;
  align-items: flex-end;
  gap: 20px;
}

.price-old {
  text-align: right;
  color: #999;
  font-size: 13px;
  text-decoration: line-through;
  line-height: 1.2;
}

.price-now {
  text-align: left;
  color: #2D9CDB;
  font-size: 16px;
  font-weight: bold;
  line-height: 1.2;
}
/* SECCION CATEGORIAS */
.categorias-grid {
  background-color: #f9fafa;
  border-radius: 20px;
  max-width: 95%;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
  padding: 0px 0px;            /* menos padding arriba */
  margin: 0px auto 0px auto;   /* 20px arriba, auto a los lados, 60px abajo */
}

.titulo-categorias {
  font-size: 22px;
  font-weight: bold;
  color: #2D9CDB;
  margin-bottom: 30px;
  text-align: center;
}

/* Ajustamos el ancho del contenedor de categorías */
.grid-categorias-limpio{
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 12px;
  justify-items: stretch;
  align-items: stretch;
  max-width: 1200px;
  width: 100%;
  margin: 0 auto 40px auto;
}

.categoria-card {
  position: relative; /* para poder posicionar el texto */
  overflow: hidden;
  background-color: white;
  border-radius: 14px;
  text-align: center;
  max-width: 400px;              /* ← ancho fijo */
  height: 200px;           /* ← alto fijo */
  padding: 0;             /* 👈 sin padding para que la img llegue al borde */
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  transition:
    transform 0.3s ease,
    box-shadow 0.3s ease,
    background-color 0.3s ease,
    filter 0.3s ease;
  cursor: pointer;
  user-select: none;
  overflow: hidden;
}
/* 📱 Ajustes en pantallas pequeñas */
@media (max-width: 768px) {
  .categoria-card {
    height: 160px;
    max-width: 100%;
    padding: 20px 12px;
  }
}

/* Hover (efecto flotante) */
.categoria-card:hover {
  transform: scale(1.05);
  box-shadow: 0 10px 24px rgba(0,0,0,0.1);
}

/* Active (clic/tap) */
.categoria-card:active {
  filter: brightness(0.9);
  transform: scale(0.98);
}

.categoria-card:hover {
  transform: scale(1.05);
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.icono-categoria {
  color: #2D9CDB;
  margin-bottom: 8px;
}

.nombre-categoria{
  position: absolute;
  inset: 0;                         /* ocupa TODO el card */
  display: flex;
  align-items: center;              /* centra vertical */
  justify-content: center;          /* centra horizontal */
  text-align: center;

  /* fondo negro semitransparente SIEMPRE visible */
  background: rgba(0,0,0,.1);
  backdrop-filter: blur(0px);
  -webkit-backdrop-filter: blur(2px);

  /* tipografía fluida: reduce en pantallas pequeñas */
  font-size: clamp(14px, 2.2vw, 20px);
  line-height: 1.25;
  font-weight: 700;
  color: #fff;
  text-transform: capitalize;
  letter-spacing: .4px;
  text-decoration: none;            /* sin subrayado */

  /* que no choque con los bordes y permita varias líneas */
  padding: clamp(10px, 2.5vh, 24px);
  box-sizing: border-box;
  white-space: normal;
  word-break: break-word;
  hyphens: auto;
  text-wrap: balance;
}

/* Ajustes extra finos por breakpoint */
@media (max-width: 640px){
  .nombre-categoria{
    font-size: clamp(13px, 4.2vw, 16px);
    padding: 10px 14px;
  }
}
@media (max-width: 380px){
  .nombre-categoria{
    font-size: 8.5px; /* mínimo absoluto para muy pequeñas */
  }
}
.imagen-categoria{
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform .35s ease;
  filter: brightness(0.9);         /* un pelín más oscuro para legibilidad */
}
/* Estilo común para el texto */
.texto-estilo-comun {
  font-size: clamp(16px, 4vw, 32px); /* 📱16px en móvil, fluido hasta 32px en desktop */
  font-weight: bold;
  color: #ffffff;
  text-align: center;
  background-color: #2D9CDB;
  padding: clamp(6px, 2vw, 14px) clamp(12px, 3vw, 28px); /* padding también fluido */
  border-radius: 15px;
  box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
  text-transform: uppercase;
  letter-spacing: 1px;
  width: 80vw;
  margin: clamp(10px, 2vw, 30px) auto clamp(20px, 4vw, 60px) auto;
  animation: floatEffect 5s infinite ease-in-out;
  margin-bottom: clamp(10px, 4vw, 10px);

  /* Efectos de flotación */
  position: relative;
  overflow: hidden; /* Esconde cualquier contenido que se desborde */
  animation: floatEffect 5s infinite ease-in-out;
}

/* Efecto de las "tabletas flotantes" */
.texto-estilo-comun::before {
  content: '';
  position: absolute;
  top: -10px;
  left: 50%;
  width: 80px;  /* Tamaño de la tableta */
  height: 40px; /* Tamaño de la tableta */
  background: rgba(255, 255, 255, 0.4); /* Color claro para la tableta */
  border-radius: 10px;
  animation: tabletEffect 4s infinite ease-in-out;
  transform: translateX(-50%);
}

/* Animación para las tabletas flotantes */
@keyframes tabletEffect {
  0% {
    transform: translateX(-50%) translateY(0);
    opacity: 0.6;
  }
  50% {
    transform: translateX(-50%) translateY(20px); /* Flotar hacia abajo */
    opacity: 1;
  }
  100% {
    transform: translateX(-50%) translateY(0);
    opacity: 0.6;
  }
}

/* Agregar más tabletas flotantes (opcional) */
.texto-estilo-comun::after {
  content: '';
  position: absolute;
  top: -30px;
  right: 50%;
  width: 70px;  /* Tamaño de la segunda tableta */
  height: 35px; /* Tamaño de la segunda tableta */
  background: rgba(255, 255, 255, 0.4); /* Color claro para la tableta */
  border-radius: 10px;
  animation: tabletEffect2 5s infinite ease-in-out;
  transform: translateX(50%);
}

/* Animación para otra tableta flotante */
@keyframes tabletEffect2 {
  0% {
    transform: translateX(50%) translateY(0);
    opacity: 0.6;
  }
  50% {
    transform: translateX(50%) translateY(-30px); /* Flotar hacia arriba */
    opacity: 1;
  }
  100% {
    transform: translateX(50%) translateY(0);
    opacity: 0.6;
  }
}
/* 🌊 Modern hover para el texto destacado */
.texto-estilo-comun:hover {
  background-color: #1c6fa4;
  box-shadow: 0px 10px 50px rgba(0, 0, 0, 0.25);
  transform: scale(1.01);
}

/* Estilo para el input
.search-container input {
  flex: 1;
  padding: 8px 14px;
  border-radius: 10px;
  border: 1px solid #ccc;
  font-size: 15px;
  transition: all 0.2s ease;
  background-color: #f5f7fa;
  outline: none;
}
*/

/* Estilo moderno del botón */
.search-container button {
  margin-left: 10px;
  width: 10%; /* 💥 O el porcentaje que necesites */
  min-width: 50px; /* Evita que se haga demasiado pequeño en pantallas chicas */
  background: linear-gradient(135deg, #2D9CDB, #2D9CDB);
  color: white;
  border: none;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(9, 0, 141, 0.2);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.3s ease;
  padding: 8px 0; /* Elimina el padding horizontal y deja solo vertical */
}

.search-container button:hover {
  background: linear-gradient(135deg, #1c6fa4, #155f8c);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-1px);
}
.barra-superior {
  position: fixed;
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
  width: 90vw;
  max-width: 1100px;
  z-index: 999;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 12px;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(9, 0, 141, 0.2);
  gap: 12px;
  flex-wrap: wrap; /* 💥 para móviles */
}

.search-container {
  display: flex;
  flex: 1;
  gap: 8px;
  align-items: center;
  min-width: 250px;
}

/* Input y botón responsivos */
.search-input {
  flex: 1;
  min-width: 120px;
}

.search-container .q-btn {
  width: 90px;
  min-width: 60px;
}
/* Barra superior con el buscador */
.barra-superior {
  position: fixed;
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
  width: 90vw;
  max-width: 1100px;
  z-index: 999;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 12px;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(9, 0, 141, 0.2);
  gap: 12px;
  flex-wrap: wrap;
}
/* Menú flotante */
.menu-navegacion {
  position: fixed;
  top: 65px;  /* Ajusta la posición para que quede debajo de la barra superior */
  left: 26%;
  transform: translateX(-50%);
  background-color: rgba(255, 255, 255, 0.8); /* Fondo semi-transparente */
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  padding: 15px;
  width: auto;  /* El menú ocupa solo el ancho necesario */
  max-width: 1100px;
  z-index: 9999;  /* Asegura que esté por encima de otros elementos */
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* Estilo para cada item del menú */
.menu-item {
  padding: 10px;
  font-size: 18px;
  font-weight: bold;
  color: #333;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  transition: background-color 0.3s ease;
  border-radius: 6px; /* Bordes redondeados */
}

/* Efecto hover para los items del menú */
.menu-item:hover {
  background-color: #f0f0f0;
}

/* Estilo para los íconos */
.q-icon {
  color: #2D9CDB; /* Color azul para los íconos */
  font-size: 20px; /* Ajusta el tamaño del ícono */
}
/* CARD EBAJO DEL CARRUSEL GRANDE*/
/* Contenedor de la sección de servicios */
.servicios {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  padding: 40px 20px;
  background-color: rgb(228, 228, 228);; /* Fondo gris claro */
  border-radius: 15px;
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1); /* Sombra más suave */
  transition: all 0.3s ease-in-out; /* Suaviza la transición */
}

/* Estilo para cada item de servicio */
.servicio-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  width: 22%; /* Controla el tamaño de cada item */
  padding: 20px;
  border-radius: 12px;
  background-color: #ffffff; /* Fondo blanco para cada opción */
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); /* Sombra sutil */
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.servicio-item:hover {
  transform: translateY(-10px); /* Efecto flotante al pasar el mouse */
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2); /* Aumenta la sombra en hover */
}

/* Estilo para los iconos */
.servicio-icon {
  color: #2D9CDB; /* Color azul para los íconos */
  font-size: 48px; /* Aumenta el tamaño de los iconos */
  margin-bottom: 20px;
  transition: transform 0.3s ease; /* Suaviza el efecto del icono */
}

.servicio-icon:hover {
  transform: scale(1.1); /* Aumenta el tamaño del ícono al pasar el mouse */
}

/* Título de cada servicio */
.servicio-titulo {
  font-size: 20px;
  font-weight: bold;
  color: #333;
  margin-bottom: 12px;
  text-transform: capitalize;
  letter-spacing: 1px;
}

/* Descripción de cada servicio */
.servicio-descripcion {
  font-size: 14px;
  color: #777;
  line-height: 1.5;
  font-weight: 300;
  max-width: 200px;
}

/* Responsividad para pantallas pequeñas */
@media (max-width: 768px) {
  .servicios {
    flex-direction: column;
    align-items: center;
  }

  .servicio-item {
    width: 80%; /* Hace que cada item ocupe más espacio en pantallas pequeñas */
    margin-bottom: 30px;
  }
}
/* CUANDO ESTA SIN STOCK */
.out-of-stock-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.2); /* Fondo semitransparente */
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 12px;
}

.out-of-stock-text {
  font-size: 24px;
  color: white;
  font-weight: bold;
  text-transform: uppercase;
  z-index: 10;
}

/* Banner moderno */
.banner-promocional {
  position: relative;
  width: 80%; /* mismo que categorias-grid */
  max-width: 95%; /* asegura que no se pase */
  margin: 40px auto; /* centrado y con espacio vertical */
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.banner-imagen {
  width: 100%;
  height: auto;
  display: block;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.banner-promocional:hover .banner-imagen {
  transform: scale(1.05);
}

/* Capa oscura para el texto */
.banner-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to right, rgba(0,0,0,0.4), rgba(0,0,0,0.2));
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 20px 40px;
  color: white;
}

.banner-titulo {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 8px;
}

.banner-texto {
  font-size: 1.1rem;
  margin-bottom: 20px;
  max-width: 500px;
}

.banner-boton {
  width: 150px;
  font-weight: bold;
}

/* Responsivo */
@media (max-width: 768px) {
  .banner-titulo {
    font-size: 1.5rem;
  }
  .banner-texto {
    font-size: 1rem;
  }
}
/* Coverflow de productos */
.carousel-coverflow-products{
  position: relative;
  width: 100%;
  max-width: 100vw;
  height: 360px;                 /* alto para ver el card completo */
  display: flex;
  justify-content: center;
  align-items: center;
  perspective: 1600px;
  overflow: hidden;
  margin-top: 16px;
  margin-bottom: 56px;
}

/* cada slide es un card con transform 3D */
.product-flow-slide{
  position: absolute;
  width: 240px;                  /* ancho del card en el carrusel */
  transition: transform .6s ease, opacity .6s ease;
  cursor: pointer;
  opacity: .6;
  transform:
    translateX(calc(var(--offset) * 260px))
    scale(calc(1 - (abs(var(--offset)) * .08)))
    rotateY(calc(var(--offset) * -30deg));
  z-index: calc(10 - abs(var(--offset)));
}
.product-flow-slide.active{
  opacity: 1;
  transform: translateX(0) scale(1) rotateY(0);
  z-index: 20;
}

/* reusa tu card pero ajustado al ancho del slide */
.product-card-personalizada.small{
  width: 240px;
  height: 330px;
  margin: 0;
}

/* Responsive */
@media (max-width: 768px){
  .carousel-coverflow-products{ height: 340px; }
  .product-flow-slide{ width: 220px; transform: translateX(calc(var(--offset) * 230px)) scale(calc(1 - (abs(var(--offset)) * .08))) rotateY(calc(var(--offset) * -28deg)); }
  .product-card-personalizada.small{ width: 220px; height: 320px; }
}
@media (max-width: 480px){
  .carousel-coverflow-products{ height: 320px; }
  .product-flow-slide{ width: 200px; transform: translateX(calc(var(--offset) * 210px)) scale(calc(1 - (abs(var(--offset)) * .08))) rotateY(calc(var(--offset) * -26deg)); }
  .product-card-personalizada.small{ width: 200px; height: 300px; }
}

/* Botones ya existentes (.controls) funcionan igual */
/* Asegura que el Swiper ocupe el mismo espacio que tu carrusel ____________________________________________________________________*/
.carousel-container-large {
  width: 100%;
  max-width: 100%;
  overflow: hidden;
  margin: 0 auto;
}

.hero-swiper {
  width: 100%;
  height: 100%;
}

.hero-swiper .swiper-wrapper,
.hero-swiper .swiper-slide {
  width: 100% !important;
  height: 100%;
}

.hero-swiper img {
  width: 100%;
  height: 100%;
  object-fit: cover;       /* 👈 se adapta recortando lo que sobra */
  display: block;
}

/* Puntos y flechas responsivos (opcional) */
:deep(.swiper-button-prev),
:deep(.swiper-button-next) {
  /* sin colores fijos; toma los de tu tema si usas Quasar */
  filter: drop-shadow(0 1px 2px rgba(0,0,0,.35));
}
:deep(.swiper-pagination-bullet) {
  opacity: .6;
}
:deep(.swiper-pagination-bullet-active) {
  opacity: 1;
}
/* Swiper contenedor */
.services-swiper.pharma {
  width: 100%;
  padding: 8px 4px 20px;
}

/* ====== Card estilo farmacia ====== */
.servicio-card--pharma {
  position: relative;
  display: grid;
  grid-template-rows: auto 1fr auto; /* header, desc, cta */
  gap: 12px;

  height: 100%;
  min-height: 40px;

  padding: 18px 16px;
  border-radius: 14px;

  /* Borde sutil y fondo limpio */
  background: #ffffff;
  border: 1px solid rgba(12, 28, 48, 0.08);

  /* Sombra sobria, clínica */
  box-shadow:
    0 1px 2px rgba(16, 24, 40, 0.04),
    0 6px 16px rgba(16, 24, 40, 0.06);

  transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
}

.servicio-card--pharma:hover {
  transform: translateY(-3px);
  box-shadow:
    0 2px 8px rgba(16, 24, 40, 0.06),
    0 14px 28px rgba(212, 221, 240, 0.08);
  border-color: rgba(12, 28, 48, 0.12);
}

/* Header: icono + título */
.servicio-header {
  display: grid;
  grid-template-columns: 48px 1fr;
  align-items: center;
  column-gap: 12px;
}

/* Icono en cápsula suave (azul/verde salud) */
.servicio-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: grid;
  place-items: center;
  color: #2D9CDB;                 /* azul clínico */
  background:
    linear-gradient(180deg, #F2FAFF, #ECF7FF);
  border: 1px solid rgba(45, 156, 219, 0.20);
}

/* Tipografía sobria */
.servicio-title {
  margin: 0;
  font-weight: 800;
  letter-spacing: .2px;
  color: #0F172A;                 /* gris oscuro elegante */
  font-size: 1.02rem;
  line-height: 1.25;
}

.servicio-desc {
  margin: 0;
  color: #475569;                 /* gris medio legible */
  font-size: .95rem;
  line-height: 1.5;
}

/* CTA discreto */
.servicio-cta {
  justify-self: start;
  border-radius: 10px;
  padding: 6px 12px;
  border-color: rgba(13, 161, 247, 0.45) !important;
}

/* Ajuste de bullets/flechas del Swiper */
:deep(.swiper-button-prev),
:deep(.swiper-button-next) {
  filter: drop-shadow(0 1px 2px rgba(0,0,0,.15));
}
:deep(.swiper-pagination-bullet) { opacity: .5; }
:deep(.swiper-pagination-bullet-active) { opacity: .95; }

/* —— Dark mode elegante —— */
@media (prefers-color-scheme: dark) {
  .servicio-card--pharma {
    background: #f1f1f1;
    border-color: rgba(255,255,255,0.06);
    box-shadow:
      0 1px 2px rgba(0,0,0,0.45),
      0 10px 18px rgba(0,0,0,0.35);
  }
  .servicio-card--pharma:hover {
    border-color: rgba(255,255,255,0.1);
    box-shadow:
      0 2px 8px rgba(0,0,0,0.5),
      0 16px 28px rgba(0,0,0,0.45);
  }
  .servicio-title { color: #000000; }
  .servicio-desc  { color: #003474; }
  .servicio-icon {
    background: linear-gradient(180deg, #0E1828, #0D1724);
    border-color: rgba(90, 160, 210, 0.35);
    color: #78C5F3;
  }
}
/* centra el bloque y añade padding lateral */
/* más espacio arriba/abajo y para la paginación */
.descuentos-swiper {
  max-width: 1300px;
  margin: 32px auto 40px;   /* ↑ margen superior e inferior */
  padding: 16px 8px 48px;   /* ↑ padding inferior para bullets/sombra */
}
/* ====== Carrusel de productos (descuentos) ====== */

/* Permite que nada se recorte al hacer hover */
.descuentos-swiper :deep(.swiper),
.descuentos-swiper :deep(.swiper-wrapper),
.descuentos-swiper :deep(.swiper-slide) {
  overflow: visible;
}

/* ---------- Flechas bonitas, centradas y abajo ---------- */
.descuentos-swiper {
  --swiper-navigation-color: #2D9CDB;
  --swiper-navigation-size: 24px;
}

.descuentos-swiper :deep(.swiper-button-prev),
.descuentos-swiper :deep(.swiper-button-next) {
  position: absolute;
  bottom: 48px;                       /* ⬇️ por encima de los bullets */
  left: 50%;
  right: auto;

  width: 52px;                        /* tamaño del botón */
  height: 52px;
  border-radius: 9999px;
  background: #dfdfdf;
  border: 1px solid rgba(45,156,219,.35);
  box-shadow: 0 8px 18px rgba(16,24,40,.16);
  z-index: 11;
  display: grid;
  place-items: center;

  transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}

/* separación desde el centro */
.descuentos-swiper :deep(.swiper-button-prev) { transform: translateX(calc(-50% - 56px)); }
.descuentos-swiper :deep(.swiper-button-next) { transform: translateX(calc(-50% + 56px)); }

/* hover sin “salto” */
.descuentos-swiper :deep(.swiper-button-prev:hover),
.descuentos-swiper :deep(.swiper-button-next:hover) {
  background: #fff;
  box-shadow: 0 12px 26px rgba(16,24,40,.20);
  transform: translateX(var(--tx, 0)) scale(1.07);
}
.descuentos-swiper :deep(.swiper-button-prev:hover) { --tx: calc(-50% - 56px); }
.descuentos-swiper :deep(.swiper-button-next:hover) { --tx: calc(-50% + 56px); }

/* deshabilitado */
.descuentos-swiper :deep(.swiper-button-disabled) {
  opacity: .10;
  box-shadow: none;
  cursor: default;
}

/* ---------- Bullets bien abajo y centrados ---------- */
.descuentos-swiper :deep(.swiper-pagination) {
  bottom: 12px;                       /* borde inferior */
  left: 50%;
  transform: translateX(-50%);
}

/* ---------- Responsivo ---------- */
@media (max-width: 768px) {
  .descuentos-swiper { padding-bottom: 76px; }
  .descuentos-swiper :deep(.swiper-button-prev),
  .descuentos-swiper :deep(.swiper-button-next) {
    width: 44px; height: 44px;
    bottom: 44px;                     /* un poco más arriba que desktop */
  }
  .descuentos-swiper :deep(.swiper-button-prev) { transform: translateX(calc(-50% - 44px)); }
  .descuentos-swiper :deep(.swiper-button-next) { transform: translateX(calc(-50% + 44px)); }
  .descuentos-swiper :deep(.swiper-button-prev:hover) { --tx: calc(-50% - 44px); }
  .descuentos-swiper :deep(.swiper-button-next:hover) { --tx: calc(-50% + 44px); }
  .descuentos-swiper :deep(.swiper-pagination) { bottom: 10px; }
}

/* ===== Categorías: Swiper móvil vs Grid desktop ===== */
.categorias-swiper-wrapper{ display:none; }
.grid-categorias-limpio{
  display:grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 12px; justify-items: stretch; align-items: stretch;
  max-width: 1200px; width: 100%; margin: 0 auto 40px auto;
}
@media (max-width: 768px){
  .grid-categorias-limpio{ display: none; }         /* ocultar grid en móvil */
  .categorias-swiper-wrapper{ display: block; }     /* mostrar swiper en móvil */
  .categorias-swiper{ padding: 4px 8px 16px; }
}

/* ===== Cards de categoría (reutiliza tus estilos) ===== */
.categoria-card{
  position: relative; overflow: hidden; background-color: white; border-radius: 14px; text-align: center;
  max-width: 400px; height: 200px; padding: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  transition: transform .3s ease, box-shadow .3s ease, background-color .3s ease, filter .3s ease;
  cursor: pointer; user-select: none;
}
@media (max-width: 768px){ .categoria-card{ height: 160px; max-width: 100%; } }
.categoria-card:hover{ transform: scale(1.05); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.categoria-card:active{ filter: brightness(0.9); transform: scale(0.98); }
.imagen-categoria{ width:100%; height:100%; object-fit: cover; display:block; transition: transform .35s ease; filter: brightness(0.9); }
.nombre-categoria{
  position:absolute; inset:0; display:flex; align-items:center; justify-content:center; text-align:center;
  background: rgba(0,0,0,.1);
  font-size: clamp(14px, 2.2vw, 20px); line-height:1.25; font-weight:700; color:#fff; text-transform:capitalize; letter-spacing:.4px;
  padding: clamp(10px, 2.5vh, 24px); box-sizing: border-box;
}

</style>
<style>
.q-carousel__control {
  top: 50% !important; /* Centrado vertical */
  transform: translateY(-50%) !important; /* Ajusta desde el centro */
  background-color: rgba(45, 156, 219, 0.4) !important;
  border-radius: 50%;
  backdrop-filter: blur(6px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
  transition: all 0.3s ease;
  width: 48px;
  height: 48px;
  z-index: 10;
}

.q-carousel__control:hover {
  background-color: rgba(45, 156, 219, 0.8) !important;
  transform: translateY(-50%) scale(1.1) !important;
}

.q-carousel__control i {
  font-size: 28px;
  color: white;
}

</style>

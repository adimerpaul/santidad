<template>
  <q-page class="q-pa-none">
    <div class="page-wrapper">
      <!-- Carrusel grande (SIN CAMBIOS: como lo tenías) -->
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
            <img :src="`${$url}../images/${c.image}`" alt="ferta Farmacia Santidad Divina" />
          </SwiperSlide>
        </Swiper>
      </div>
 </div>
    <!-- Servicios -->
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
          outline no-caps color="primary" class="servicio-cta" label="Más información"
          :href="s.link || undefined" :target="s.link ? '_blank' : undefined" rel="noopener"
        />

        </article>
      </SwiperSlide>
    </Swiper>

    <div class="titulo-categorias texto-estilo-comun">Explora Nuestros Descuentos</div>

    <!-- Descuentos -->
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
        1024: { slidesPerView: 4, slidesPerGroup: 4 },
        1280: { slidesPerView: 5, slidesPerGroup: 5 }
      }"
    >
      <SwiperSlide v-for="p in productosCarrusel" :key="p.id">
        <q-card class="product-card-personalizada small" flat @click="clickDetalleProducto(p)">
          <q-img
            :src="p.imagen.includes('http') ? p.imagen : `${$url}../images/${p.imagen}`"
            style="height: 160px; object-fit: cover; position: relative;"
          >
            <div v-if="p.cantidad === 0" class="out-of-stock-overlay">
              <span class="out-of-stock-text">Sin Stock</span>
            </div>
            <q-badge v-if="p.porcentaje" color="red" floating>-{{ p.porcentaje }}%</q-badge>
          </q-img>

          <q-card-section class="q-pa-sm text-center">
            <div class="product-name">{{ p.nombre }}</div>
            <div class="product-prices">
              <div class="price-old" v-if="p.precioNormal">
                <span>Antes</span><br /><strong>Bs. {{ p.precioNormal }}</strong>
              </div>
              <div class="price-now">
                <span>Ahora</span><br /><strong>Bs. {{ p.precio }}</strong>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </SwiperSlide>
    </Swiper>

    <!-- Categorías -->
    <div class="categorias-grid">
      <div id="seccion-categorias" class="titulo-categorias texto-estilo-comun">Explorá nuestras categorías</div>

      <!-- SOLO móvil -->
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
              <div class="nombre-categoria nombre-categoria--movil">{{ cat.name }}</div>
            </router-link>
          </SwiperSlide>
        </Swiper>
      </div>
    </div>

    <!-- Grid (desktop/tablet) -->
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

    <!-- Banner medio -->
    <div class="banner-promocional">
      <template v-if="carouselsMedio.length">
        <Swiper
          class="banner-swiper"
          :modules="modules"
          :loop="carouselsMedio.length > 1"
          :autoplay="{ delay: 4000, disableOnInteraction: false }"
          :speed="650"
          navigation pagination
        >
          <SwiperSlide v-for="(b, i) in carouselsMedio" :key="'ban-'+i">
            <img :src="`${$url}../images/${b.image}`" alt="Promociones Especiales Farmacia" class="banner-imagen" />
            <div class="banner-overlay"></div>
          </SwiperSlide>
        </Swiper>
      </template>
      <template v-else>
        <img src="/images/banner.jpg" alt="Descuentos en Medicamentos" class="banner-imagen" />
        <div class="banner-overlay"></div>
      </template>
    </div>

    <div class="titulo-categorias texto-estilo-comun">Productos más vendidos</div>

    <!-- Más vendidos -->
    <Swiper
      class="descuentos-swiper"
      :key="'top-' + topVendidos.length"
      :modules="modules"
      :loop="topVendidos.length > 4"
      :autoplay="{ delay: 3500, disableOnInteraction: false }"
      :speed="600"
      :space-between="16"
      navigation pagination grabCursor
      :slides-per-view="5" :slides-per-group="5"
      :observer="true" :observe-parents="true"
      :update-on-window-resize="true" :watch-slides-progress="true"
      :breakpoints="{
        0:    { slidesPerView: 1.15, slidesPerGroup: 1,
                centeredSlides: true, centeredSlidesBounds: true,
                slidesOffsetBefore: 24, slidesOffsetAfter: 24 },
        480:  { slidesPerView: 2, slidesPerGroup: 2,
                centeredSlides: true, centeredSlidesBounds: true,
                slidesOffsetBefore: 16, slidesOffsetAfter: 16 },
        768:  { slidesPerView: 3, slidesPerGroup: 3 },
        1024: { slidesPerView: 4, slidesPerGroup: 4 },
        1280: { slidesPerView: 5, slidesPerGroup: 5 }
      }"
    >
      <SwiperSlide v-for="p in topVendidos" :key="'tv-' + p.id">
        <q-card class="product-card-personalizada small" flat @click="clickDetalleProducto(p)">
          <q-img
            :src="p.imagen.includes('http') ? p.imagen : `${$url}../images/${p.imagen}`"
            style="height: 160px; object-fit: cover; position: relative;"
          >
            <div v-if="Number(p.cantidad) === 0" class="out-of-stock-overlay">
              <span class="out-of-stock-text">Sin Stock</span>
            </div>
            <q-badge v-if="Number(p.porcentaje) > 0" color="red" floating>-{{ p.porcentaje }}%</q-badge>
          </q-img>
          <q-card-section class="q-pa-sm text-center">
            <div class="product-name">{{ p.nombre }}</div>
            <div class="product-prices">
              <div class="price-old" v-if="p.precioNormal"><span>Antes</span><br /><strong>Bs. {{ p.precioNormal }}</strong></div>
              <div class="price-now"><span>Ahora</span><br /><strong>Bs. {{ p.precio }}</strong></div>
            </div>
          </q-card-section>
        </q-card>
      </SwiperSlide>
    </Swiper>
<!-- +++ Sección Confianza (nueva) +++ -->
<section class="trust-section" aria-labelledby="trust-title">
  <div class="trust-container">
    <h2 id="trust-title" class="trust-title">
      TE OFRECEMOS LO MEJOR
    </h2>

    <div class="trust-features">
      <article class="trust-card">
        <div class="trust-icon">
          <q-icon name="lightbulb" size="28px" />
        </div>
        <div class="trust-label">
          <strong>Innovación constante</strong>
          <span>Innovación constante para servirte mejor.</span>
        </div>
      </article>

      <article class="trust-card">
        <div class="trust-icon">
          <q-icon name="medical_services" size="28px" />
        </div>
        <div class="trust-label">
          <strong>Atención Profesional</strong>
          <span>Profesionales Farmacéuticos a tu servicio</span>
        </div>
      </article>

      <article class="trust-card">
        <div class="trust-icon">
          <q-icon name="savings" size="28px" />
        </div>
        <div class="trust-label">
          <strong>Precios Solidarios</strong>
          <span>Ahorra todos los días de se la Semana</span>
        </div>
      </article>
    </div>
  </div>
</section>

    <div class="titulo-categorias texto-estilo-comun">Laboratorios y Distribuidoras</div>

    <!-- Carrusel continuo -->
    <div class="carousel-container">
      <div class="image-track">
        <img v-for="(c,i) in carouselsMini" :key="i" :src="`${$url}../images/${c.image}`" alt="Imagen" />
      </div>
    </div>
  </q-page>
</template>

<script>
import { useMeta } from 'quasar'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay, Navigation, Pagination } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'

export default {
  name: 'IndexPage',
  components: { Swiper, SwiperSlide },
  setup () {
    useMeta({
      title: 'Inicio',
      titleTemplate: title => `${title} - Farmacias Santidad Divina`,
      meta: {
        description: { name: 'description', content: 'Bienvenido a Farmacias Santidad Divina. Explora nuestro catálogo de medicamentos, ofertas especiales y sucursales.' },
        keywords: { name: 'keywords', content: 'farmacia inicio, comprar medicamentos, ofertas farmacia' },
        equiv: { 'http-equiv': 'Content-Type', content: 'text/html; charset=UTF-8' }
      }
    })
  },
  data () {
    return {
      slide: 0,
      carousels: [],
      carouselsMini: [],
      carouselsMedio: [],
      slideSimple: 0,
      loading: false,
      currentPage: 1,
      totalPages: 1,
      categories: [],
      category: 0,
      pSlide: 0,
      productosCarrusel: [],
      modules: [Autoplay, Navigation, Pagination],
      services: [
        { icon: 'accessibility', title: 'Envios a Domicilio', desc: 'Pide desde donde estes', link: 'https://wa.link/yowijl' },
        { icon: 'price_check', title: 'Cotiza con nosotros', desc: 'Solicita una cotización rápida y sin compromiso', link: 'https://wa.link/gfbdtu' },
        { icon: 'local_offer', title: 'Descuentos por volumen', desc: 'Aprovecha grandes descuentos en compras en grandes cantidades', link: 'https://wa.link/d5z3ik' },
        { icon: 'mobile_friendly', title: 'Aplicación Móvil', desc: 'Compra desde donde estés con nuestra app móvil', link: 'https://play.google.com/store/apps/details?id=com.adimer.san2' }

      ],
      topVendidos: []
    }
  },
  computed: {
    categoriasVisibles () { return this.categories.slice(1, 9) }
  },
  watch: {
    '$route.hash' (h) {
      if (h === '#seccion-categorias') this.scrollCategoriasEnHome()
    },
    '$store.products': { handler () { this.buildProductosCarrusel() }, deep: true }
  },
  mounted () {
    this.carouselsGet()
    this.carouselsMiniGet()
    this.getCategories()
    this.buildProductosCarrusel()
    this.cargarDescuentosHome()
    this.fetchTopVendidos()
    this.carouselsMedioGet()
    // NUEVO: si ya llega con #seccion-categorias, desplázate
    if (this.$route.hash === '#seccion-categorias') {
      this.scrollCategoriasEnHome()
    }
  },
  beforeUnmount () {
  },
  activated () {
    // NUEVO: si usas <keep-alive>, al reactivar Home revisa el hash
    if (this.$route.hash === '#seccion-categorias') {
      this.scrollCategoriasEnHome()
    }
  },
  methods: {
    /* ==== Utils ==== */
    formatPrice (v) { return Number(v ?? 0).toFixed(2) },
    clickDetalleProducto (p) {
      this.$router.push('/detalle-producto/' + p.id + '/' + this.espacioCambioGuion(p.nombre))
    },
    espacioCambioGuion (text) { return text.replace(/ |\/|\./g, '-').replace(/,/g, '') },

    /* ==== Datos ==== */
    carouselsMiniGet () { this.$axios.get('carouselsMini').then(r => { this.carouselsMini = r.data }) },
    carouselsGet () { this.$axios.get('carouselsPage').then(r => { this.carousels = r.data }) },
    carouselsMedioGet () {
      this.$axios.get('carouselsMedio')
        .then(res => { this.carouselsMedio = res.data || [] })
        .catch(() => { this.carouselsMedio = [] })
    },
    async getCategories () {
      this.loading = true
      try {
        const res = await this.$axios.get('/categories')
        this.categories = res.data
      } catch (err) {
        console.error('❌ Error al cargar categorías', err)
        this.categories = []
      } finally { this.loading = false }
    },
    buildProductosCarrusel () {
      const src = this.$store.products || []
      const isTrue = v => v === true || v === 1 || v === '1' || v === 'ACTIVO'
      this.productosCarrusel = src.filter(p => isTrue(p.activo) && isTrue(p.en_oferta))
    },
    async cargarDescuentosHome () {
      this.loading = true
      try {
        const res = await this.$axios.get('productos', { params: { page: 1, per_page: 200 } })
        this.$store.products = (res.data?.data || res.data || []).map(p => {
          const x = { ...p }
          x.en_oferta = x.en_oferta === true || x.en_oferta === 'true' || Number(x.en_oferta) === 1
          x.porcentaje = Number(p.porcentaje ?? 0)
          const precioBase = Number(p.precio ?? 0)
          const precioAntes = (p.precioAntes !== null && p.precioAntes !== undefined && p.precioAntes !== '')
            ? Number(p.precioAntes) : null
          if (x.porcentaje > 0) {
            if (precioAntes != null && precioAntes > 0) {
              x.precioNormal = Number(precioAntes).toFixed(2)
              x.precio = (precioAntes * (1 - x.porcentaje / 100)).toFixed(2)
            } else {
              x.precioNormal = Number(precioBase).toFixed(2)
              x.precio = (precioBase * (1 - x.porcentaje / 100)).toFixed(2)
            }
          } else {
            if (precioAntes != null && precioAntes > 0) {
              x.precioNormal = Number(precioAntes).toFixed(2)
              x.precio = Number(precioBase).toFixed(2)
            } else {
              x.precioNormal = null
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
      } finally { this.loading = false }
    },
    async fetchTopVendidos () {
      try {
        const { data } = await this.$axios.get('top-sellers', { params: { days: 7 } })
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
    scrollCategoriasEnHome () {
      // espera un tick por si algo está renderizando
      this.$nextTick(() => {
        const el = document.querySelector('#seccion-categorias')
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' })
        } else {
          // pequeño retry por si está montando
          let i = 0
          const timer = setInterval(() => {
            const node = document.querySelector('#seccion-categorias')
            if (node) {
              node.scrollIntoView({ behavior: 'smooth', block: 'start' })
              clearInterval(timer)
            }
            if (++i > 25) clearInterval(timer) // ~2.5s máx
          }, 100)
        }
      })
    }
  }
}

</script>

<style scoped>
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
  object-fit: cover;
  display: block;
}

/* ===== Carrusel continuo logos ===== */
.carousel-container { overflow: hidden; position: relative; margin: 0; padding: 50px; line-height: 0; }
.image-track { --logo-h: clamp(40px, 8vw, 85px); --gap: calc(var(--logo-h) * .6); display: inline-flex; width: max-content; will-change: transform; animation: scroll-left 40s linear infinite; }
.image-track img { height: var(--logo-h); width: auto; margin-right: var(--gap); object-fit: contain; transition: transform .3s, box-shadow .3s; transform: rotateY(-10deg) scale(.95); filter: drop-shadow(0 5px 10px rgba(0,0,0,.3)); border-radius: 12px; }
.image-track img:hover{ transform: rotateY(0) scale(1.06); box-shadow: 0 15px 30px rgba(0,0,0,.35); }
@keyframes scroll-left{ 0%{ transform: translateX(0) } 100%{ transform: translateX(-50%) } }
@media (prefers-reduced-motion: reduce){ .image-track{ animation-duration:.001ms; animation-iteration-count:1 } }

/* ===== Cards producto ===== */
.product-card-personalizada { width: 200px; height: 320px; margin: auto; transition: transform .3s; display:flex; flex-direction:column; justify-content:space-between; overflow:hidden; border-radius:12px; border:2px solid #2D9CDB; }
.product-card-personalizada:hover { transform: scale(1.02); z-index: 3; }
.product-name { font-weight: 600; font-size: 15px; margin-bottom: 8px; color: #333; height: 40px; overflow: hidden; text-overflow: ellipsis; line-height: 1.2; }
.product-prices { display:flex; justify-content:center; align-items:flex-end; gap:20px; }
.price-old { text-align:right; color:#999; font-size:13px; text-decoration:line-through; line-height:1.2; }
.price-now { text-align:left; color:#2D9CDB; font-size:16px; font-weight:bold; line-height:1.2; }

/* ===== Categorías ===== */
.categorias-grid { background:#f9fafa; border-radius:20px; max-width:95%; box-shadow:0 4px 16px rgba(0,0,0,.06); padding:0; margin:0 auto; }
.titulo-categorias { font-size:22px; font-weight:bold; color:#2D9CDB; margin-bottom:30px; text-align:center; }

.grid-categorias-limpio{
  display:grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 12px; justify-items: stretch; align-items: stretch;
  max-width: 1200px; width: 100%; margin: 0 auto 40px;
}

/* Card categoría (desktop) */
.categoria-card { position:relative; overflow:hidden; background:#fff; border-radius:14px; text-align:center; max-width:400px; height:200px; padding:0; box-shadow:0 4px 12px rgba(0,0,0,.05); transition: transform .3s, box-shadow .3s, background-color .3s, filter .3s; cursor:pointer; user-select:none; }
.categoria-card:hover { transform: scale(1.05); box-shadow: 0 10px 24px rgba(0,0,0,0.1); }
.categoria-card:active{ filter: brightness(0.9); transform: scale(0.98); }
.imagen-categoria{ width:100%; height:100%; object-fit: cover; display:block; transition: transform .35s ease; filter: brightness(0.9); }

.nombre-categoria{
  position:absolute; inset:0; display:flex; align-items:center; justify-content:center; text-align:center;
  background: rgba(0,0,0,.1);
  font-size: clamp(14px, 2.2vw, 20px); line-height:1.25; font-weight:700; color:#fff; text-transform:capitalize; letter-spacing:.4px;
  padding: clamp(10px, 2.5vh, 24px);
}
.hero-img{
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

/* En móviles puedes ajustar el alto si quieres un aspecto más "vertical" */
@media (max-width: 600px){
  .hero-viewport{
    height: clamp(180px, 56vw, 380px);
  }
}
/* SOLO móvil: Swiper visible, grid oculto */
.categorias-swiper-wrapper{ display:none; }
@media (max-width: 768px){
  .grid-categorias-limpio{ display:none; }
  .categorias-swiper-wrapper{ display:block; }
  .categorias-swiper{ padding: 4px 8px 16px; }
}
/* Mejor legibilidad del título en móvil */
.nombre-categoria--movil{
  background: rgba(0, 0, 0, 0.62);
  text-shadow: 0 1px 2px rgba(0,0,0,.55);
  font-size: clamp(12px, 4.2vw, 14px);
}

/* ===== Títulos azules ===== */
.texto-estilo-comun{
  font-size: clamp(16px, 4vw, 32px);
  font-weight: bold; color:#fff; text-align:center; background:#2D9CDB;
  padding: clamp(6px, 2vw, 14px) clamp(12px, 3vw, 28px);
  border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,.1);
  text-transform: uppercase; letter-spacing: 1px; width: 80vw;
  margin: clamp(10px, 2vw, 30px) auto clamp(20px, 4vw, 60px);
}

/* Servicios / banners resumidos */
.servicio-card--pharma{ position:relative; display:grid; grid-template-rows:auto 1fr auto; gap:12px; height:100%; padding:18px 16px; border-radius:14px; background:#fff; border:1px solid rgba(12,28,48,.08); box-shadow:0 1px 2px rgba(16,24,40,.04), 0 6px 16px rgba(16,24,40,.06); transition: transform .25s, box-shadow .25s, border-color .25s; }
.servicio-card--pharma:hover{ transform: translateY(-3px); box-shadow: 0 2px 8px rgba(16,24,40,.06), 0 14px 28px rgba(212,221,240,.08); border-color: rgba(12,28,48,.12); }
.servicio-header{ display:grid; grid-template-columns:48px 1fr; align-items:center; column-gap:12px; }
.servicio-icon{ width:48px; height:48px; border-radius:10px; display:grid; place-items:center; color:#2D9CDB; background: linear-gradient(180deg, #F2FAFF, #ECF7FF); border: 1px solid rgba(45,156,219,.20); }
.servicio-title{ margin:0; font-weight:800; color:#0F172A; font-size:1.02rem; }
.servicio-desc{ margin:0; color:#475569; font-size:.95rem; line-height:1.5; }
.servicio-cta{ justify-self:start; border-radius:10px; padding:6px 12px; border-color: rgba(13,161,247,.45) !important; }

.descuentos-swiper{ max-width:1300px; margin:32px auto 40px; padding:16px 8px 48px; }
.descuentos-swiper :deep(.swiper), .descuentos-swiper :deep(.swiper-wrapper), .descuentos-swiper :deep(.swiper-slide){ overflow: visible; }
.descuentos-swiper{ --swiper-navigation-color:#2D9CDB; --swiper-navigation-size:24px; }
.descuentos-swiper :deep(.swiper-button-prev), .descuentos-swiper :deep(.swiper-button-next){
  position:absolute; bottom:48px; left:50%; width:52px; height:52px; border-radius:9999px; background:#dfdfdf; border:1px solid rgba(45,156,219,.35);
  box-shadow:0 8px 18px rgba(16,24,40,.16); z-index:11; display:grid; place-items:center; transition: transform .18s, box-shadow .18s, background .18s;
}
.descuentos-swiper :deep(.swiper-button-prev){ transform: translateX(calc(-50% - 56px)); }
.descuentos-swiper :deep(.swiper-button-next){ transform: translateX(calc(-50% + 56px)); }
.descuentos-swiper :deep(.swiper-pagination){ bottom: 12px; left: 50%; transform: translateX(-50%); }

.out-of-stock-overlay{ position:absolute; inset:0; background: rgba(0,0,0,0.2); display:flex; justify-content:center; align-items:center; border-radius:12px; }
.out-of-stock-text{ font-size:24px; color:#fff; font-weight:bold; text-transform:uppercase; z-index:10; }

.banner-promocional{ position:relative; width:80%; max-width:95%; margin:40px auto; border-radius:16px; overflow:hidden; box-shadow:0 8px 24px rgba(0,0,0,0.2); }
.banner-imagen{ width:100%; height:auto; display:block; object-fit:cover; transition: transform .5s ease; }
.banner-promocional:hover .banner-imagen{ transform: scale(1.05); }
.banner-overlay{ position:absolute; inset:0; background: linear-gradient(to right, rgba(0,0,0,0.4), rgba(0,0,0,0.2)); color:#fff; }

/* SECCION LOGOS Y TEXTO ENCIMIA DE MINI CARRUSEL */
.trust-section{
  position: relative;
  background: linear-gradient(180deg, #E3F4FB 0%, #CBE7F7 100%);
  padding: clamp(24px, 5vw, 56px) 0;
  margin: 32px auto 40px;
  overflow: hidden;
}
.trust-section::before,
.trust-section::after{
  content:"";
  position:absolute;
  width:60vw; height:60vw;
  background: radial-gradient(40% 40% at 50% 50%, rgba(45,156,219,.25), transparent 70%);
  filter: blur(24px);
  pointer-events:none;
}
.trust-section::before{ inset:auto auto -40% -10%; }
.trust-section::after{ inset:-30% -10% auto auto; transform: rotate(15deg); }

.trust-container{ max-width:1200px; margin:0 auto; padding:0 16px; }

.trust-title{
  text-align:center;
  font-weight:800;
  color:#0B3A62;
  letter-spacing:.5px;
  font-size: clamp(18px, 3.4vw, 36px);
  margin: 0 0 clamp(16px, 2.5vw, 28px);
}

.trust-features{
  display:grid;
  grid-template-columns: repeat(3, minmax(0,1fr));
  gap: clamp(12px, 2vw, 24px);
}
@media (max-width: 800px){
  .trust-features{ grid-template-columns: 1fr; }
}

.trust-card{
  display:grid;
  grid-template-columns: 56px 1fr;
  align-items:center;
  gap:14px;
  background: rgba(255,255,255,.65);
  border:1px solid rgba(13,110,253,.18);
  border-radius:16px;
  padding:14px 16px;
  box-shadow:0 6px 20px rgba(2,48,71,.08);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
  animation: trust-fade-up .5s ease both;
}
.trust-card:nth-child(2){ animation-delay:.05s; }
.trust-card:nth-child(3){ animation-delay:.1s; }
.trust-card:hover{
  transform: translateY(-3px);
  box-shadow: 0 12px 26px rgba(2,48,71,.14);
  border-color: rgba(13,110,253,.28);
}

.trust-icon{
  width:56px; height:56px; border-radius:14px;
  display:grid; place-items:center;
  background: linear-gradient(180deg, #F0F8FF, #E3F2FD);
  border:1px solid rgba(45,156,219,.25);
  color:#2D9CDB;
}

.trust-label strong{
  display:block; font-weight:800; color:#0F172A; line-height:1.2;
  font-size: clamp(14px, 2.2vw, 18px);
}
.trust-label span{
  display:block; color:#475569; font-size: clamp(12px, 2vw, 14px);
}

@keyframes trust-fade-up{
  from{ opacity:0; transform: translateY(8px); }
  to{ opacity:1; transform: translateY(0); }
}

</style>

<style>
.q-carousel__control {
  top: 50% !important;
  transform: translateY(-50%) !important;
  background-color: rgba(45, 156, 219, 0.4) !important;
  border-radius: 50%;
  backdrop-filter: blur(6px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
  transition: all 0.3s ease;
  width: 48px; height: 48px; z-index: 10;
}
.q-carousel__control:hover { background-color: rgba(45, 156, 219, 0.8) !important; transform: translateY(-50%) scale(1.1) !important; }
.q-carousel__control i { font-size: 28px; color: white; }
</style>

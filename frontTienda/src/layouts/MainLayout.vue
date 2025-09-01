<template>
  <q-layout view="lHh Lpr lff">
    <!-- ===== Barra superior (buscador + menú + predictivo) ===== -->
    <div class="barra-superior">
      <!-- Menú -->
      <q-btn
        flat round dense icon="menu"
        @click="toggleDrawer" size="md"
      />

      <!-- Buscador + Sugerencias -->
      <div class="search-container" ref="searchWrap">
        <q-input
          v-model="search"
          dense outlined rounded
          placeholder="Buscar Producto / Palabra Clave"
          class="search-input q-ml-sm"
          :debounce="250"
          @update:model-value="onType"
          @keyup.enter.prevent="enterSelect"
          @keydown.down.prevent="move(1)"
          @keydown.up.prevent="move(-1)"
          @focus="maybeOpen"
          @blur="onBlur"
        >
          <template v-slot:prepend>
            <q-icon name="search" />
          </template>
        </q-input>

        <q-btn
          label="Buscar" rounded no-caps
          :loading="loading" class="q-ml-sm"
          @click="buscar"
        />

        <!-- Panel de sugerencias -->
        <div
          v-if="showSuggestions"
          class="predictive-panel"
          :style="panelStyle"
        >
          <div v-if="suggestions.length" class="predictive-list">
            <button
              v-for="(s, idx) in suggestions"
              :key="s.id"
              class="predictive-item"
              :class="{ active: idx === activeIndex }"
              @mousedown.prevent="selectSuggestion(s)"
              type="button"
            >
              <img
                class="predictive-thumb"
                :src="`${$url}../images/${s.imagen}`"
                alt=""
                @error="$event.target.src='/images/productDefault.jpg'"
              />
              <div class="predictive-info">
                <div class="predictive-title">{{ s.title }}</div>

                <div class="predictive-prices">
                  <span v-if="s.precio_antes" class="old">Bs. {{ formatPrice(s.precio_antes) }}</span>
                  <span class="now">Bs. {{ formatPrice(s.precio_ahora ?? s.precio) }}</span>
                  <span v-if="Number(s.porcentaje) > 0" class="badge">-{{ s.porcentaje }}%</span>
                </div>
              </div>
            </button>
          </div>

          <div v-else class="predictive-empty">
            No hay resultados
          </div>

          <div class="predictive-footer">
            <button class="predictive-seeall" @mousedown.prevent="verTodos" type="button">
              Ver todos los resultados
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Overlay para cerrar el menú al hacer clic fuera -->
    <div v-if="drawer" class="overlay-cierra-menu" @click="drawer=false"></div>

    <!-- Menú de navegación -->
    <div v-if="drawer" class="menu-navegacion">
      <div class="menu-item" @click="navegarA('/')">
        <q-icon name="home" class="q-mr-sm" />Inicio
      </div>
      <div class="menu-item" @click="navegarA('/sucursales')">
        <q-icon name="store" class="q-mr-sm" />Sucursales
      </div>
      <div class="menu-item" @click="scrollCategorias">
        <q-icon name="category" class="q-mr-sm" />Categorías
      </div>
    </div>

    <!-- Contenido de páginas -->
    <q-page-container>
      <router-view />
    </q-page-container>

    <!-- FAB Carrito -->
    <q-page-sticky position="bottom-right" class="q-ma-md" :offset="[18, 18]">
      <q-btn color="green" fab icon="fa-solid fa-cart-shopping" @click="clickCarrito">
        <q-badge
          v-if="$store?.carrito.length"
          color="red"
          floating
          :label="$store?.carrito.length"
        />
      </q-btn>
    </q-page-sticky>

    <!-- ===== Footer Moderno ===== -->
    <q-footer class="footer-modern no-border">
      <div class="footer-top q-pa-lg">
        <div class="container grid">
          <!-- Col 1: Marca / Slogan -->
          <div class="col">
            <div class="brand row items-center">
              <img src="/images/logo.png" alt="Logo" class="logo" />
              <div class="q-ml-sm">
                <div class="text-h6 text-bold">Farmacia Santidad Divina</div>
                <div class="text-subtitle2 opacity-70">Precio Solidario</div>
              </div>
            </div>
            <div class="q-mt-md text-body2 opacity-80">
              Atendemos con ética y responsabilidad. Encuentra medicamentos,
              dermocosmética y cuidado personal a precios justos.
            </div>

            <!-- Social -->
            <div class="row q-gutter-sm q-mt-md">
              <q-btn round dense flat class="social" icon="fa-brands fa-facebook" href="https://www.facebook.com/profile.php?id=61562087074524" target="_blank" />
              <q-btn round dense flat class="social" icon="fa-brands fa-instagram" href=" https://www.instagram.com/farmacias_santidad_divina/?igsh=emI5NGl6ODNvejJu/" target="_blank" />
              <q-btn round dense flat class="social" icon="fa-brands fa-tiktok" href="https://www.tiktok.com/@santidad_divina/" target="_blank" />
              <q-btn round dense flat class="social" icon="fa-brands fa-whatsapp" href="https://wa.me/59172319869" target="_blank" />
            </div>
          </div>

          <!-- Col 2: Navegación -->
          <div class="col">
            <div class="col-title">Navegación</div>
            <div class="links">
              <router-link class="link" to="/">Inicio</router-link>
              <router-link class="link" to="/sucursales">Sucursales</router-link>
              <a class="link" href="tel:+59172319869">Llámanos</a>
              <a class="link" href="mailto:contacto@santidad.com">farmaciasantidaddivinacentral@gmail.com</a>
            </div>
          </div>

          <!-- Col 3: Legales -->
          <div class="col">
            <div class="col-title">Legales</div>
            <div class="links">
              <router-link class="link" to="/privacidad">Políticas de Privacidad</router-link>
              <router-link class="link" to="/envio">Política de Envío</router-link>
              <router-link class="link" to="/terminos">Términos y Condiciones</router-link>
              <router-link class="link" to="/quienes-somos">Quiénes Somos</router-link>
            </div>
          </div>

          <!-- Col 4: Contacto rápido -->
          <div class="col">
            <div class="col-title">Contacto</div>
            <div class="text-body2 opacity-80">
              <div class="row items-center q-gutter-x-sm q-mb-xs">
                <q-icon name="place" size="20px" /><span>Oruro, Bolivia</span>
              </div>
              <div class="row items-center q-gutter-x-sm q-mb-xs">
                <q-icon name="schedule" size="20px" /><span>Lun-Dom: 08:00–10:00</span>
              </div>
              <div class="row items-center q-gutter-x-sm">
                <q-icon name="phone" size="20px" /><a class="link" href="tel:+59172319869">+591 72319869</a>
              </div>
            </div>
            <!-- Newsletter -->
            <div class="q-mt-md">
              <q-input dense standout v-model="newsletter" placeholder="Tu correo electrónico" type="email">
                <template #append>
                  <q-btn dense flat icon="send" @click="suscribir" />
                </template>
              </q-input>
              <div v-if="newsletterOk" class="text-positive text-caption q-mt-xs">¡Gracias por suscribirte!</div>
            </div>
          </div>
        </div>
      </div>

      <q-separator dark class="separator" />

      <div class="footer-bottom q-py-sm">
        <div class="container row items-center justify-between">
          <div class="text-caption opacity-70">
            © {{ new Date().getFullYear() }} Farmacia Santidad Divina. Todos los derechos reservados.
          </div>
          <div class="row q-gutter-sm">
            <router-link class="link tiny" to="/privacidad">Privacidad</router-link>
            <router-link class="link tiny" to="/terminos">Términos</router-link>
            <a class="link tiny" href="mailto:legal@santidad.com">farmaciasantidaddivinacentral@gmail.com</a>
          </div>
        </div>
      </div>
    </q-footer>

    <!-- Carrito -->
    <q-dialog v-model="carritoDialog" maximized position="right">
      <q-card style="width: 350px; max-width: 80vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-bold text-h6 row items-center">Carrito de compras</div>
          <q-space />
          <q-btn flat icon="close" @click="clickCarrito" />
        </q-card-section>
        <q-card-section>
          <q-item
            v-for="(item,index) in $store?.carrito"
            :key="item.id"
            clickable
          >
            <q-item-section avatar>
              <q-avatar>
                <q-img :src="item.imagen.includes('http')?item.imagen:`${$url}../images/${item.imagen}`" />
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ item.nombre }}</q-item-label>
              <q-item-label caption>{{ item.cantidad }} x {{ item.precio }}</q-item-label>
            </q-item-section>
            <q-item-section side top>
              <q-btn flat icon="delete" @click="removeCarrito(index)" />
            </q-item-section>
          </q-item>

          <q-btn
            icon="fa-brands fa-whatsapp"
            label="Pedir por WhatsApp"
            color="green"
            no-caps
            class="full-width q-mt-md"
            @click="pedirCarritoWhatsApp"
          />
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-layout>
</template>

<script>
import { defineComponent, nextTick } from 'vue'

export default defineComponent({
  name: 'MainLayout',
  data () {
    return {
      // Tabs existentes
      tab: 'Inicio',
      tabs: [
        { name: 'Inicio', label: 'Inicio', to: '/' },
        { name: 'Sucursales', label: 'Sucursales', to: '/sucursales' }
      ],

      leftDrawerOpen: false,

      // Carrito
      carritoDialog: false,

      // Newsletter
      newsletter: '',
      newsletterOk: false,

      // ====== Buscador & Predictivo (migrado del Index) ======
      search: '',
      loading: false,
      drawer: false,
      suggestions: [],
      showSuggestions: false,
      activeIndex: -1,
      panelWidth: 0
    }
  },
  computed: {
    panelStyle () { return { width: this.panelWidth ? `${this.panelWidth}px` : '100%' } }
  },
  mounted () {
    this.$nextTick(() => {
      this.calcPanelWidth()
      window.addEventListener('resize', this.calcPanelWidth, { passive: true })
      document.addEventListener('click', this.handleClickOutside)
    })

    // Si llega con hash (ej. /#seccion-categorias), intenta hacer scroll
    if (this.$route.hash === '#seccion-categorias') {
      this.scrollToCategoriesWithRetry()
    }
  },
  beforeUnmount () {
    window.removeEventListener('resize', this.calcPanelWidth)
    document.removeEventListener('click', this.handleClickOutside)
  },
  watch: {
    // Reintenta el scroll suave cuando cambia el hash a #seccion-categorias
    '$route.hash' (h) {
      if (h === '#seccion-categorias') this.scrollToCategoriesWithRetry()
    }
  },
  methods: {
    // ===== Newsletter =====
    suscribir () {
      if (!this.newsletter) return
      this.newsletterOk = true
      setTimeout(() => { this.newsletter = ''; this.newsletterOk = false }, 2000)
    },

    // ===== Carrito =====
    pedirCarritoWhatsApp () {
      const carrito = this.$store.carrito
      const mensaje = carrito.reduce((acc, item) => {
        return `${acc}${item.nombre} x ${item.cantidad}\n`
      }, 'Hola, me gustaría pedir los siguientes productos:\n')
      const url = `https://wa.me/59172319869?text=${encodeURIComponent(mensaje)}`
      window.open(url, '_blank')
    },
    clickCarrito () { this.carritoDialog = !this.carritoDialog },
    removeCarrito (index) { this.$store.carrito.splice(index, 1) },

    // ===== Predictivo (migrado del Index) =====
    async onType () {
      const q = this.search.trim()
      if (q.length < 2) {
        this.suggestions = []
        this.showSuggestions = false
        this.activeIndex = -1
        return
      }
      try {
        const { data } = await this.$axios.get('/products/suggest', { params: { q, limit: 8 } })

        // Normalización de precios
        this.suggestions = (data || []).map(p => {
          const x = { ...p }
          x.porcentaje = Number(x.porcentaje || x.descuento || 0)

          const precioBase = Number(x.precio ?? 0)
          const antesRaw = (x.precioAntes ?? x.precioNormal ?? null)
          const precioAntes = (antesRaw !== null && antesRaw !== '' && !isNaN(Number(antesRaw)))
            ? Number(antesRaw) : null

          if (x.porcentaje > 0) {
            const baseAntes = (precioAntes && precioAntes > 0) ? precioAntes : precioBase
            x.precio_antes = baseAntes
            x.precio_ahora = Number(baseAntes * (1 - x.porcentaje / 100))
          } else {
            x.precio_antes = (precioAntes && precioAntes > 0) ? precioAntes : null
            x.precio_ahora = precioBase
          }

          if ((!x.porcentaje || x.porcentaje === 0) && x.precio_antes && x.precio_ahora) {
            const diff = x.precio_antes - x.precio_ahora
            x.porcentaje = (x.precio_antes > 0) ? Math.round((diff / x.precio_antes) * 100) : 0
          }
          return x
        })

        this.activeIndex = this.suggestions.length ? 0 : -1
        this.maybeOpen()
      } catch (e) {
        console.error('Error en sugerencias', e)
        this.suggestions = []
        this.showSuggestions = false
        this.activeIndex = -1
      }
    },
    maybeOpen () {
      if ((this.suggestions || []).length > 0) {
        this.showSuggestions = true
        this.calcPanelWidth()
      }
    },
    onBlur () { setTimeout(() => { this.showSuggestions = false }, 120) },
    handleClickOutside (e) {
      const wrap = this.$refs.searchWrap
      if (wrap && !wrap.contains(e.target)) this.showSuggestions = false
    },
    calcPanelWidth () {
      const wrap = this.$refs.searchWrap
      if (!wrap) return
      const input = wrap.querySelector('.q-field')
      this.panelWidth = input ? input.getBoundingClientRect().width : wrap.getBoundingClientRect().width
    },
    move (dir) {
      if (!this.showSuggestions || !this.suggestions.length) return
      const n = this.suggestions.length
      this.activeIndex = (this.activeIndex + dir + n) % n
    },
    enterSelect () {
      if (!this.showSuggestions || this.activeIndex < 0) { this.buscar(); return }
      const item = this.suggestions[this.activeIndex]
      this.selectSuggestion(item)
    },
    selectSuggestion (s) {
      this.search = s.title
      this.showSuggestions = false
      this.activeIndex = -1
      this.$router.push('/detalle-producto/' + s.id + '/' + this.espacioCambioGuion(s.title))
    },
    verTodos () { this.buscar() },

    // ===== Buscar / navegación =====
    buscar () {
      const term = (this.search || '').trim()
      if (!term) return
      this.$router.push({ name: 'buscar', query: { q: term, page: 1 } })
      this.drawer = false
      this.showSuggestions = false
      this.activeIndex = -1
    },
    toggleDrawer () { this.drawer = !this.drawer },
    navegarA (ruta) { this.$router.push(ruta); this.drawer = false },

    // ===== Categorías (funciona desde cualquier ruta) =====
    scrollCategorias () {
      this.drawer = false
      const hash = '#seccion-categorias'

      // Si ya estoy en Home
      if (this.$route.path === '/') {
        // Ajusta el hash para que scrollBehavior (o el watcher) dispare
        if (this.$route.hash !== hash) {
          this.$router.replace({ hash })
        }
        // De todos modos, intenta con retry para casos sin scrollBehavior
        this.scrollToCategoriesWithRetry()
        return
      }

      // Si estoy en otra ruta, navego a Home + hash y luego intento scroll
      this.$router.push({ path: '/', hash }).then(() => {
        this.scrollToCategoriesWithRetry()
      })
    },

    // Reintento de scroll hasta que el DOM del Index monte la sección
    scrollToCategoriesWithRetry () {
      const hash = '#seccion-categorias'
      let intentos = 0
      const maxIntentos = 20 // ~2s
      const intentar = () => {
        const el = document.querySelector(hash)
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'start' })
          return
        }
        if (intentos++ < maxIntentos) {
          setTimeout(intentar, 100)
        }
      }
      // Espera al siguiente tick por si aún no montó el router-view
      nextTick(() => intentar())
    },

    // ===== Utils =====
    formatPrice (v) { return Number(v ?? 0).toFixed(2) },
    espacioCambioGuion (text) { return text.replace(/ |\/|\./g, '-').replace(/,/g, '') }
  }
})
</script>

<style scoped>
/* ===== Barra superior ===== */
.barra-superior{
  position: fixed; top: 10px; left: 50%; transform: translateX(-50%);
  width: 90vw; max-width: 1100px; z-index: 999; background: #fff;
  display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
  padding: 6px 12px; border-radius: 12px; box-shadow: 0 8px 24px rgba(9, 0, 141, 0.2);
  background: rgba(255, 255, 255, 0.3); /* Transparencia */
  backdrop-filter: blur(10px);           /* Desenfoque vidrio */
  -webkit-backdrop-filter: blur(10px);   /* Compatibilidad Safari */
  border: 1px solid rgba(255, 255, 255, 0.2); /* Borde sutil translúcido */
}
.search-container{ display:flex; flex:1; gap:8px; align-items:center; min-width:250px; position:relative; }
.search-input{ flex:1; min-width: 120px; }
.search-container .q-btn {
  background: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: #000;
  font-weight: 600;
  border-radius: 10px;
  transition: all 0.3s ease;
}
.search-container .q-btn:hover {
  background: rgba(255, 255, 255, 0.45);
  transform: scale(1.05);
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Menú */
.menu-navegacion{
  position: fixed;
  top: calc(10px + 52px);
  left: calc(50% - min(45vw, 550px));
  transform: none;
  background: rgba(255,255,255,.92);
  border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,.1);
  padding:12px; max-width:320px; z-index: 9999; display:flex; flex-direction:column; gap:8px;
}
@media (max-width: 400px){ .menu-navegacion{ left:12px; right:12px; max-width:none; } }
.overlay-cierra-menu{ position: fixed; inset: 0; z-index: 9998; background: transparent; }
.menu-item{ padding:10px; font-size:18px; font-weight:bold; color:#333; display:flex; align-items:center; gap:10px; cursor:pointer; transition: background-color .3s; border-radius:6px; }
.menu-item:hover{ background:#f0f0f0; }
.q-icon{ color:#2D9CDB }

/* ===== Panel de sugerencias ===== */
.predictive-panel{
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  background: #fff;
  border: 1px solid rgba(15,23,42,.12);
  border-radius: 14px;
  box-shadow:
    0 2px 6px rgba(16,24,40,0.06),
    0 18px 40px rgba(16,24,40,0.14);
  overflow: hidden;
  z-index: 10000;
}
.predictive-list{ max-height: 340px; overflow: auto; padding: 6px; }
.predictive-item{
  display: grid; grid-template-columns: 56px 1fr; gap: 12px;
  width: 100%; text-align: left; background: transparent; border: 0;
  padding: 10px 12px; border-radius: 10px; cursor: pointer;
}
.predictive-item:hover,
.predictive-item.active{
  background: #F8FAFC;
}
.predictive-thumb{
  width: 56px; height: 56px; object-fit: contain;
  border-radius: 10px; border: 1px solid rgba(2,32,71,.08);
  background: #fff;
}
.predictive-info{ display: grid; align-content: center; }
.predictive-title{ font-weight: 600; color: #0F172A; font-size: .95rem; line-height: 1.25; }

/* Fila de precios en sugerencia */
.predictive-prices{
  display:flex; align-items:center; gap:8px;
  margin-top:2px; font-size:.92rem;
}
.predictive-prices .old{
  text-decoration: line-through;
  color: #94a3b8;
}
.predictive-prices .now{
  font-weight: 800;
  color: #0ea5e9;
}
.predictive-prices .badge{
  background: #ef4444;
  color: #fff;
  font-size: .75rem;
  padding: 2px 6px;
  border-radius: 6px;
  font-weight: 700;
}
.predictive-empty{ padding: 16px; text-align: center; color: #64748B; font-size: .95rem; }
.predictive-footer{ border-top: 1px solid rgba(15,23,42,.08); padding: 8px; display:flex; justify-content:center; background:#fff; }
.predictive-seeall{ background:#EFF6FF; color:#1D4ED8; border:1px solid rgba(29,78,216,.25); padding:8px 12px; border-radius:10px; font-weight:600; cursor:pointer; }

/* ===== Footer ===== */
.footer-modern {
  background: linear-gradient(165deg, #0b132b 0%, #1c2541 60%, #3a506b 100%);
  color: #fff;
}
.footer-top .container,
.footer-bottom .container {
  max-width: 1200px;
  margin: 0 auto;
}
.grid {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 24px;
}
.col { grid-column: span 12; }
@media (min-width: 600px) {
  .col { grid-column: span 6; }
}
@media (min-width: 1024px) {
  .col { grid-column: span 3; }
}
.brand .logo {
  width: 42px; height: 42px; object-fit: contain; border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0,0,0,.3);
}
.col-title {
  font-weight: 700;
  margin-bottom: 8px;
  letter-spacing: 0.3px;
}
.links { display: flex; flex-direction: column; gap: 6px; }
.link {
  color: #ffffff;
  text-decoration: none;
  opacity: .9;
  transition: opacity .2s, transform .2s;
}
.link:hover { opacity: 1; transform: translateX(2px); }
.link.tiny { font-size: 12px; opacity: .75; }
.opacity-70 { opacity: .7; }
.opacity-80 { opacity: .8; }

.social {
  background: rgba(255,255,255,.08);
  border-radius: 50%;
  transition: transform .15s ease;
}
.social:hover { transform: translateY(-2px); }

.separator { opacity: .15; }

.footer-bottom {
  background: rgba(0,0,0,.18);
  backdrop-filter: saturate(120%) blur(2px);
  padding: 8px 0;
}
</style>

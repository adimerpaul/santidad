<template>
  <q-page class="q-pa-none modern-bg">
    <!-- ===== Barra superior (glass + blur) ===== -->
    <div class="barra-superior glass">
      <q-btn flat round dense icon="menu" @click="toggleDrawer" size="md" class="text-primary" />
      <div class="search-container">
        <q-input
          v-model="search"
          dense
          outlined
          rounded
          @keyup.enter="buscar"
          placeholder="Buscar Producto / Palabra clave"
          class="search-input"
        >
          <template v-slot:prepend>
            <q-icon name="search" />
          </template>
        </q-input>
        <q-btn label="Buscar" rounded :loading="loading" @click="buscar" class="search-btn grad-btn" no-caps />
      </div>
    </div>

    <!-- Menú flotante -->
    <div v-if="drawer" class="menu-navegacion glass">
      <div class="menu-item" @click="navigateTo('/')">
        <q-icon name="home" class="q-mr-sm" />Inicio
      </div>
      <div class="menu-item" @click="navigateTo('/sucursales')">
        <q-icon name="store" class="q-mr-sm" />Sucursales
      </div>
    </div>

    <!-- separador por barra fija -->
    <div class="page-spacer" />

    <div class="container q-px-md q-mx-auto">
      <!-- Migas -->
      <q-breadcrumbs class="q-mb-md text-grey-6">
        <q-breadcrumbs-el label="Inicio" to="/" />
        <q-breadcrumbs-el label="Productos" to="/" />
        <q-breadcrumbs-el :label="product?.nombre || 'Detalle'" />
      </q-breadcrumbs>

      <!-- ===== Skeleton carga ===== -->
      <div v-if="loading" class="q-mt-lg">
        <div class="row q-col-gutter-xl">
          <div class="col-12 col-md-5"><q-skeleton type="rect" class="skeleton-media" /></div>
          <div class="col-12 col-md-7">
            <q-skeleton type="text" class="q-mb-sm" />
            <q-skeleton type="text" width="60%" class="q-mb-md" />
            <q-skeleton type="rect" height="140px" class="q-mb-md" />
            <q-skeleton type="text" />
            <q-skeleton type="text" width="70%" />
          </div>
        </div>
      </div>

      <!-- ===== Detalle ===== -->
      <div v-else class="row q-col-gutter-xl" :key="detailKey">
        <!-- Media -->
        <div class="col-12 col-md-5 flex items-start justify-center">
          <q-card flat class="media-wrap glass soft-shadow">
            <!-- Zona con efecto lupa -->
            <div
              v-if="imgReady"
              class="zoom-area"
              @mousemove="handleZoomMove"
              @mouseenter="zoom.on = true"
              @mouseleave="zoom.on = false"
            >
              <q-img
                :key="imgKey"
                :src="imgSrc"
                :ratio="4/3"
                fit="contain"
                class="media-img"
                :img-style="zoomImgStyle"
                @error="onImgError"
              >
                <q-badge v-if="es_porcentaje" color="red" floating class="text-bold">-{{ product.porcentaje }}%</q-badge>
              </q-img>

              <div class="zoom-hint">
                <q-icon name="search" size="18px" />
              </div>
            </div>

            <div class="media-actions">
              <q-chip dense outline icon="photo" color="primary">Vista</q-chip>
              <q-chip dense outline icon="verified" color="teal">Original</q-chip>
            </div>
          </q-card>
        </div>

        <!-- Info -->
        <div class="col-12 col-md-7">
          <div class="product-title">{{ product?.nombre }}</div>

          <!-- Precio / Stock -->
          <div class="price-card q-mt-sm q-mb-md">
            <div class="now grad-pill">Bs. {{ product?.precio }}</div>
            <div class="before" v-if="es_porcentaje">Antes: Bs. {{ product?.precioNormal }}</div>
            <q-chip v-if="es_porcentaje" color="negative" text-color="white" size="sm" class="q-ml-sm">
              Ahorra Bs. {{ ahorro }}
            </q-chip>
            <q-chip :color="availableStock > 0 ? 'green-5' : 'grey-6'" text-color="white" size="sm" class="q-ml-sm">
              Stock: {{ stockMostrado }}
            </q-chip>
          </div>

          <!-- Meta -->
          <q-card flat class="meta-grid glass soft-shadow q-mb-md">
            <div class="meta-row"><span class="meta-key">Unidad</span><span class="meta-val">{{ product?.unidad || '-' }}</span></div>
            <div class="meta-row"><span class="meta-key">Principio activo</span><span class="meta-val">{{ product?.composicion || '-' }}</span></div>
            <div class="meta-row"><span class="meta-key">Distribuidora</span><span class="meta-val">{{ product?.distribuidora || '-' }}</span></div>
            <div class="meta-row"><span class="meta-key">Laboratorio</span><span class="meta-val">{{ product?.marca || '-' }}</span></div>
            <div class="meta-row"><span class="meta-key">País origen</span><span class="meta-val">{{ product?.paisOrigen || '-' }}</span></div>
            <div class="meta-row"><span class="meta-key">Reg. sanitario</span><span class="meta-val">{{ product?.registroSanitario || '-' }}</span></div>
          </q-card>

          <!-- Cantidad -->
          <div class="qty-row q-mb-md">
            <div class="qty-title">Cantidad</div>

            <div class="qty-controls glass soft-shadow">
              <q-btn
                round unelevated icon="remove"
                class="qty-btn"
                color="primary"
                @click="decCantidad"
                :disable="cantidad <= 1"
              />
              <q-input
                v-model.number="cantidad"
                type="number"
                inputmode="numeric"
                dense
                borderless
                class="qty-input"
                @blur="onCantidadBlur"
                @keyup.enter="addCarrito(product, cantidad)"
                aria-live="polite"
              />
              <q-btn
                round unelevated icon="add"
                class="qty-btn"
                color="primary"
                @click="incCantidad"
                :disable="availableStock <= 0 || cantidad >= availableStock"
              />
              <div class="qty-unit">{{ product?.unidad }}</div>
            </div>

            <div class="total-box glass soft-shadow">
              <div class="total-label">Total</div>
              <div class="total-value">Bs. {{ total }}</div>
            </div>
          </div>

          <!-- CTAs -->
          <div class="row q-col-gutter-sm">
            <div class="col-12 col-sm-4">
              <q-btn class="full-width grad-btn big-cta" icon="add_shopping_cart" label="Añadir al carrito" no-caps
                     @click="addCarrito(product, cantidad)" :disable="availableStock === 0" />
            </div>
            <div class="col-12 col-sm-4">
              <q-btn class="full-width grad-btn-2 big-cta" icon="shopping_cart" label="Comprar ahora" no-caps
                     @click="addCart(product, cantidad)" :disable="availableStock === 0" />
            </div>
            <div class="col-12 col-sm-4">
              <q-btn class="full-width glass-btn big-cta" icon="share" label="Compartir" no-caps @click="share" />
            </div>
          </div>
        </div>
      </div>

      <!-- Descripción -->
      <div class="q-mt-xl">
        <div class="section-title">Descripción</div>
        <q-card flat class="glass soft-shadow q-pa-md">
          <div class="descripcion" v-html="product?.descripcion"></div>
        </q-card>
      </div>

      <!-- Sucursales -->
      <div class="q-mt-xl">
        <div class="section-title">Disponibilidad en sucursales</div>
        <q-list bordered separator class="rounded-borders glass soft-shadow">
          <q-item v-for="s in sucursales" :key="s.id">
            <q-item-section avatar>
              <q-avatar size="28px" color="blue-1" text-color="primary"><q-icon name="store" /></q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">{{ s.nombre }}</q-item-label>
              <q-item-label caption>{{ s.direccion }}</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-badge :color="s.cantidad > 0 ? 'green' : 'grey'" :label="s.cantidad > 0 ? 'Disponible' : 'Sin stock'" />
            </q-item-section>
          </q-item>
        </q-list>
      </div>

      <!-- Relacionados -->
      <div class="q-mt-xl">
        <div class="section-title">Productos relacionados</div>

        <div v-if="relLoading" class="text-center q-my-lg">
          <q-spinner-dots size="50px" color="primary" />
        </div>

        <div v-else-if="relacionados.length === 0" class="text-grey-6 q-mt-sm">
          No se encontraron productos con composición similar.
        </div>

        <div v-else class="contenedor-productos q-mt-md">
          <div class="products-grid">
            <q-card
              v-for="p in relacionados"
              :key="p.id"
              class="product-card-personalizada glass soft-shadow hover-pop"
              flat
              @click="clickDetalleProducto(p)"
            >
              <q-img
                :src="resolveImage(p.imagen)"
                style="height: 160px; object-fit: cover; position: relative;"
              >
                <div v-if="Number(p.cantidad || 0) === 0" class="out-of-stock-overlay">
                  <span class="out-of-stock-text">Sin Stock</span>
                </div>
                <q-badge v-if="Number(p.porcentaje) > 0" color="red" floating>-{{ p.porcentaje }}%</q-badge>
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
          </div>
        </div>
      </div>
    </div>

    <!-- ===== Sticky CTA móvil ===== -->
    <div class="sticky-cta glass soft-shadow" v-if="!loading">
      <div class="sticky-price">
        <div class="label">Total</div>
        <div class="value">Bs. {{ total }}</div>
      </div>
      <div class="sticky-actions">
        <div class="sticky-stepper">
          <q-btn round dense icon="remove" color="primary" @click="decCantidad" :disable="cantidad <= 1" />
          <div class="sticky-qty">{{ cantidad }}</div>
          <q-btn round dense icon="add" color="primary" @click="incCantidad" :disable="availableStock <= 0 || cantidad >= availableStock" />
        </div>
        <q-btn class="grad-btn" icon="add_shopping_cart" label="Añadir" no-caps @click="addCarrito(product, cantidad)" :disable="availableStock === 0" />
      </div>
    </div>
  </q-page>
</template>

<script>
import { useMeta } from 'quasar'
import { getCurrentInstance, nextTick } from 'vue'

export default {
  name: 'DetalleProducto',

  data () {
    return {
      id: this.$route.params.id,
      product: {},
      loading: true,
      cantidad: 1,
      es_porcentaje: false,
      sucursales: [],
      drawer: false,
      search: '',
      // relacionados
      relacionados: [],
      relLoading: false,
      fallbackUsed: false,
      apiError: false,

      // imagen
      imgBust: Date.now(),
      imgReady: false,

      // zoom
      zoom: { on: false, x: 50, y: 50 },

      // control de concurrencia para evitar respuestas viejas
      reqSeq: 0
    }
  },

  setup () {
    const vm = getCurrentInstance()
    useMeta(() => {
      const p = vm?.proxy?.product || {}
      const nombre = p.nombre || 'Santidad Divina'
      const desc = (p.descripcion
        ? p.descripcion + ' Producto disponible en Santidad Divina. Encuéntralo en nuestra farmacia digital.'
        : 'Producto disponible en Santidad Divina. Encuéntralo en nuestra farmacia digital.')
      const imagen = p?.imagen
        ? (String(p.imagen).startsWith('http') ? p.imagen : `${vm.proxy.$url}../images/${p.imagen}`)
        : '/images/placeholder-producto.png'

      return {
        title: nombre,
        description: desc,
        meta: {
          description: { name: 'description', content: desc },
          keywords: { name: 'keywords', content: `medicamento, salud, ${p.nombre || ''}, ${p.marca || ''}, ${p.composicion || ''}` },
          robots: { name: 'robots', content: 'index, follow' },
          'og:title': { property: 'og:title', content: nombre },
          'og:description': { property: 'og:description', content: desc },
          'og:image': { property: 'og:image', content: imagen },
          'og:type': { property: 'og:type', content: 'product' },
          'og:url': { property: 'og:url', content: typeof window !== 'undefined' ? window.location.href : '' }
        },
        link: { canonical: { rel: 'canonical', href: typeof window !== 'undefined' ? window.location.href : '' } }
      }
    })
    return {}
  },

  computed: {
    // Forzar re-montaje del bloque detalle al cambiar id/imagen (evita residuos)
    detailKey () {
      return `${this.id}-${this.imgBust}`
    },
    // Key único para que el <q-img> se re-monte + bust de caché
    imgKey () {
      return `${this.id}-${this.product?.imagen || 'noimg'}-${this.imgBust}`
    },
    imgSrc () {
      const img = this.product?.imagen
      let base = img
        ? (img.includes('http') ? img : `${this.$url}../images/${img}`)
        : '/images/placeholder-producto.png'
      base += (base.includes('?') ? '&' : '?') + 'v=' + this.imgBust
      return base
    },
    ahorro () {
      if (!this.es_porcentaje) return '0.00'
      const before = Number(this.product?.precioNormal || 0)
      const now = Number(this.product?.precio || 0)
      return (before - now).toFixed(2)
    },
    total () {
      const p = Number(this.product?.precio || 0)
      return (p * (Number(this.cantidad) || 0)).toFixed(2)
    },
    // === STOCK: suma por sucursales (robusto al cambiar de producto) ===
    availableStock () {
      return this.sucursales.reduce((acc, s) => acc + Number(s.cantidad || 0), 0)
    },
    stockMostrado () {
      const s = this.availableStock
      return s > 100 ? 100 : s
    },
    // estilos del zoom (aplicados al <img> interno del QImg)
    zoomImgStyle () {
      const style = {
        transition: 'transform .15s ease-out, transform-origin .1s ease-out',
        willChange: 'transform'
      }
      if (this.zoom.on) {
        style.transform = 'scale(2)'
        style.transformOrigin = `${this.zoom.x}% ${this.zoom.y}%`
        style.cursor = 'zoom-out'
      } else {
        style.transform = 'scale(1)'
        style.transformOrigin = 'center center'
        style.cursor = 'zoom-in'
      }
      return style
    }
  },

  watch: {
    '$route.params.id': {
      async handler (newId) {
        this.id = newId
        window.scrollTo({ top: 0, behavior: 'smooth' })
        await this.prepareForNewProduct()
        await this.initLoad()
      }
    },
    'product.imagen' () {
      this.bumpImg()
    }
  },

  async mounted () {
    await this.initLoad()
  },

  methods: {
    async prepareForNewProduct () {
      // limpiar y NO montar imagen hasta tener datos
      this.imgReady = false
      this.imgBust = Date.now()
      this.fallbackUsed = false
      this.product = {}
      this.es_porcentaje = false
      this.cantidad = 1
      // reset visual de cantidades por sucursal hasta que llegue el nuevo detalle
      this.sucursales = this.sucursales.map(s => ({ ...s, cantidad: 0 }))
      this.zoom = { on: false, x: 50, y: 50 }
      await nextTick()
    },

    bumpImg () {
      this.imgBust = Date.now()
    },

    async initLoad () {
      // Traer sucursales si no tenemos
      if (!this.sucursales || this.sucursales.length === 0) {
        await this.getSucursales()
      }
      await this.getProduct()
      await this.fetchRelacionados()
    },

    toggleDrawer () { this.drawer = !this.drawer },
    navigateTo (ruta) { this.$router.push(ruta); this.drawer = false },
    buscar () {
      const term = (this.search || '').trim()
      if (!term) return
      this.$router.push({ name: 'buscar', query: { q: term, page: 1 } })
      this.drawer = false
    },

    onImgError (e) {
      if (this.fallbackUsed) return
      this.fallbackUsed = true
      if (e?.target) e.target.src = '/images/placeholder-producto.png'
      this.bumpImg()
    },

    resolveImage (img) {
      return img
        ? (String(img).includes('http') ? img : `${this.$url}../images/${img}`)
        : '/images/placeholder-producto.png'
    },

    // movimiento del zoom (transform-origin hacia el cursor)
    handleZoomMove (ev) {
      const el = ev.currentTarget
      const rect = el.getBoundingClientRect()
      const x = ((ev.clientX - rect.left) / rect.width) * 100
      const y = ((ev.clientY - rect.top) / rect.height) * 100
      this.zoom.x = Math.max(0, Math.min(100, x))
      this.zoom.y = Math.max(0, Math.min(100, y))
    },

    async getSucursales () {
      const { data } = await this.$axios.get('sucursales')
      // estandarizar con campo cantidad inicial en 0
      this.sucursales = (data || []).map(s => ({ ...s, cantidad: Number(s.cantidad || 0) }))
    },

    async getProduct () {
      const id = this.$route.params.id
      if (!id) return
      const myReq = ++this.reqSeq
      this.loading = true
      this.$q.loading.show()
      try {
        const { data } = await this.$axios.get(`productos/${id}`)
        if (myReq !== this.reqSeq) return // ignorar respuesta vieja

        // Clonar para asegurar reactividad limpia
        this.product = { ...data }

        // precio / porcentaje
        if (Number(this.product.porcentaje) > 0) {
          this.es_porcentaje = true
          this.product.precioNormal = this.product.precio
          this.product.precio = (this.product.precio - (this.product.precio * this.product.porcentaje / 100)).toFixed(2)
        } else {
          this.es_porcentaje = false
        }

        // cantidades por sucursal en listado (mostrar disponibilidad real)
        this.sucursales = this.sucursales.map(s => ({
          ...s,
          cantidad: Number(this.product[`cantidadSucursal${s.id}`] || 0)
        }))

        // reset cantidad
        this.cantidad = 1

        // imagen ok
        this.fallbackUsed = false
        this.bumpImg()
        this.imgReady = true
      } catch (e) {
        if (myReq !== this.reqSeq) return
        this.apiError = true
        this.$q.notify({ type: 'negative', message: e?.response?.data?.message || 'Error cargando producto' })
      } finally {
        if (myReq === this.reqSeq) {
          this.loading = false
          this.$q.loading.hide()
        }
      }
    },

    // ===== Relacionados (igual que tu lógica, sin tocar stock) =====
    async fetchRelacionados () {
      const comp = (this.product?.composicion || '').trim()
      this.relacionados = []
      if (!comp) return

      this.relLoading = true
      try {
        const MAX_ITEMS = 50
        const selfId = Number(this.product.id)
        const selfSubcat = Number(this.product?.subcategory_id) || null
        const REQUIRE_SAME_DOSE = true

        const normForSearch = (s = '') => String(s)
          .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
          .toLowerCase()
          .replace(/([a-z])(\d)/gi, '$1 $2')
          .replace(/(\d)([a-z])/gi, '$1 $2')
          .replace(/[^a-z0-9\s]/g, ' ')
          .replace(/\s+/g, ' ')
          .trim()

        const normForSim = (s = '') =>
          this.norm(String(s).replace(/([a-z])(\d)/gi, '$1 $2').replace(/(\d)([a-z])/gi, '$1 $2'))

        const doseKey = (s = '') => {
          const rx = /\b(\d+(?:[.,]\d+)?)\s*(mg|ml|mcg|µg|ug|g|gr|kg|l|ui|%)\b/gi
          const out = []
          let m
          while ((m = rx.exec(String(s))) !== null) {
            const num = String(parseFloat(m[1].replace(',', '.')))
            const unit = m[2].toLowerCase()
            out.push(`${num}${unit}`)
          }
          return out.sort().join('|')
        }

        const baseDose = doseKey(comp)
        const baseQuery = normForSearch(comp)
        const tokens = Array.from(new Set(baseQuery.split(' ').filter(t => t && t.length >= 3)))

        const queryList = []
        if (baseQuery) queryList.push({ search: baseQuery })
        tokens.slice(0, 5).forEach(t => queryList.push({ search: t }))
        if (selfSubcat) queryList.push({ subcategory_id: selfSubcat })

        const requests = queryList.map(params =>
          this.$axios.get('productos', { params: { ...params, page: 1, per_page: 200 } })
            .then(({ data }) => (data?.data || data?.products?.data || data || []))
            .catch(() => [])
        )
        const results = await Promise.all(requests)

        const poolMap = new Map()
        for (const arr of results) {
          for (const p of arr) {
            const pid = Number(p?.id)
            if (!pid || pid === selfId) continue
            if (!poolMap.has(pid)) poolMap.set(pid, p)
          }
        }
        const pool = Array.from(poolMap.values())
        if (pool.length === 0) { this.relacionados = []; return }

        const a = normForSim(comp)
        const MIN_SIM_WITH_COMP = 0.55
        const MIN_SIM_FALLBACK = 0.35

        const scored = []
        for (const p of pool) {
          if (REQUIRE_SAME_DOSE && baseDose) {
            const candDose = doseKey(p.composicion || p.nombre || '')
            if (candDose !== baseDose) continue
          }

          const b = normForSim(p.composicion || p.nombre || '')
          const s = this.similarity(a, b)
          const hasComp = Boolean((p.composicion || '').trim())
          const score = hasComp ? s : Math.min(s + 0.2, 0.5)
          if (score > 0) scored.push({ p, score, hasComp })
        }

        scored.sort((x, y) => {
          if (y.score !== x.score) return y.score - x.score
          const xs = Number(x.p.subcategory_id) === selfSubcat ? 1 : 0
          const ys = Number(y.p.subcategory_id) === selfSubcat ? 1 : 0
          if (ys !== xs) return ys - xs
          const xp = Number(x.p.precio) || 0
          const yp = Number(y.p.precio) || 0
          return xp - yp
        })

        const seen = new Set()
        const list = []
        for (const { p, score, hasComp } of scored) {
          const pass = hasComp ? (score >= MIN_SIM_WITH_COMP) : (score >= MIN_SIM_FALLBACK)
          if (!pass || seen.has(p.id)) continue

          const np = { ...p }
          if (Number(np.porcentaje) > 0) {
            np.precioNormal = np.precio
            np.precio = (np.precio - (np.precio * np.porcentaje / 100)).toFixed(2)
          }
          list.push(np)
          seen.add(p.id)
          if (list.length >= MAX_ITEMS) break
        }

        this.relacionados = list
      } catch (e) {
        console.error('Error cargando relacionados', e)
        this.relacionados = []
      } finally {
        this.relLoading = false
      }
    },

    // ===== utilidades =====
    norm (str) {
      let s = String(str)
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/([a-z])(\d)/gi, '$1 $2')
        .replace(/(\d)([a-z])/gi, '$1 $2')

      const alias = {
        ibuproneo: 'ibuprofeno',
        acetaminofen: 'paracetamol',
        dc: 'compresion directa',
        sr: 'liberacion sostenida',
        gr: 'gastroresistente',
        reistente: 'gastroresistente'
      }
      for (const [k, v] of Object.entries(alias)) {
        s = s.replace(new RegExp(`\\b${k}\\b`, 'g'), v)
      }
      s = s.replace(/\bgastro\s+resistente\b/g, 'gastroresistente')

      return s
        .replace(/[^a-z0-9\s]/g, ' ')
        .replace(/\b(comprimidos?|tabletas?|capsulas?|capsula[s]?|solucion|jarabe|unguento|crema|spray)\b/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
    },

    similarity (a, b) {
      if (!a || !b) return 0
      const A = Array.from(new Set(a.split(' ').filter(Boolean)))
      const B = Array.from(new Set(b.split(' ').filter(Boolean)))
      if (!A.length || !B.length) return 0

      const lev = (x, y) => {
        if (x === y) return 0
        const m = x.length, n = y.length
        if (!m) return n; if (!n) return m
        const dp = Array.from({ length: m + 1 }, (_, i) =>
          Array.from({ length: n + 1 }, (_, j) => (i === 0 ? j : j === 0 ? i : 0))
        )
        for (let i = 1; i <= m; i++) {
          for (let j = 1; j <= n; j++) {
            const cost = x[i - 1] === y[j - 1] ? 0 : 1
            dp[i][j] = Math.min(dp[i - 1][j] + 1, dp[i][j - 1] + 1, dp[i - 1][j - 1] + cost)
          }
        }
        return dp[m][n]
      }

      const eq = (x, y) => {
        if (x === y) return true
        const d = lev(x, y)
        const L = Math.max(x.length, y.length)
        if (L <= 4) return d <= 1
        if (L <= 7) return d <= 2
        return d <= 3
      }

      const used = new Set()
      let inter = 0
      for (const t of A) {
        for (let j = 0; j < B.length; j++) {
          if (used.has(j)) continue
          if (eq(t, B[j])) { used.add(j); inter++; break }
        }
      }
      const union = new Set([...A, ...B]).size || 1
      return inter / union
    },

    clickDetalleProducto (p) {
      if (String(p.id) === String(this.id)) return
      this.$router.push('/detalle-producto/' + p.id + '/' + this.slugify(p.nombre))
    },
    slugify (text) { return String(text).replace(/ |\/|\./g, '-').replace(/,/g, '') },

    // ===== Cantidad =====
    onCantidadBlur () {
      const n = Number(this.cantidad)
      if (!Number.isFinite(n) || n < 1) this.cantidad = 1
    },
    decCantidad () {
      this.cantidad = Math.max(1, Number(this.cantidad || 1) - 1)
    },
    incCantidad () {
      const max = this.availableStock
      const next = Number(this.cantidad || 1) + 1
      this.cantidad = Math.min(next, max || next)
    },

    // ===== Añadir / Comprar =====
    async addCarrito (product, cantidad) {
      const ok = await this.checkStockBeforeAdd(cantidad)
      if (!ok) return
      const p = { ...product, cantidad }
      this.$store.addCarrito(p)
      this.$alert?.success?.('Producto añadido al carrito')
    },
    async addCart (product, cantidad) {
      const ok = await this.checkStockBeforeAdd(cantidad)
      if (!ok) return
      const text = `Deseo comprar ${cantidad} ${product.nombre} a Bs. ${product.precio} c/u. Total Bs. ${(product.precio * cantidad).toFixed(2)}`
      window.open(`https://wa.me/59172319869?text=${encodeURIComponent(text)}`)
    },
    async checkStockBeforeAdd (cant) {
      const stock = this.availableStock
      const n = Number(cant || 0)
      if (!Number.isFinite(n) || n < 1) {
        this.$q.notify({ type: 'warning', message: 'Cantidad inválida (mínimo 1).' })
        return false
      }
      if (n > stock) {
        this.$q.notify({ type: 'negative', message: `Stock insuficiente. Disponible: ${stock}.` })
        return false
      }
      return true
    },

    share () {
      const shareData = { title: this.product?.nombre, text: this.product?.descripcion, url: window.location.href }
      if (navigator.share) navigator.share(shareData).catch(() => {})
      else this.$q.notify({ message: 'Compartir no soportado en este dispositivo', color: 'grey-7' })
    }
  }
}
</script>

<style scoped>
/* ========= Theme util ========= */
.modern-bg{
  background:
    radial-gradient(1200px 600px at -10% -20%, #e6f4ff 0%, transparent 50%),
    radial-gradient(1000px 500px at 110% -10%, #f1f8ff 0%, transparent 55%),
    #fbfdff;
  min-height: 100vh;
}
.glass{
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  background: rgba(255,255,255,0.75);
  border: 1px solid rgba(13, 94, 171, 0.08);
  border-radius: 14px;
}
.soft-shadow{
  box-shadow:
    0 2px 6px rgba(16,24,40,0.06),
    0 12px 28px rgba(16,24,40,0.08);
}
.grad-btn{
  background: linear-gradient(135deg,#2D9CDB,#1D7FC0);
  color: #fff;
}
.grad-btn:hover{ filter: brightness(1.05); }
.grad-btn-2{
  background: linear-gradient(135deg,#22c55e,#16a34a);
  color:#fff;
}
.glass-btn{
  background: rgba(255,255,255,.8);
  border: 1px solid rgba(0,0,0,.06);
}
.grad-pill{
  background: linear-gradient(135deg,#e0f2ff,#c7e6ff);
  color:#0F172A;
  border-radius: 999px;
  padding: 8px 14px;
  font-weight: 800;
}

/* ===== Layout ===== */
.container { max-width: 1200px; }
.page-spacer { height: 80px; }

/* ===== Top bar ===== */
.barra-superior{
  position: fixed; top: 12px; left: 50%; transform: translateX(-50%);
  width: 92vw; max-width: 1120px; z-index: 999;
  display: flex; align-items: center; gap: 12px; padding: 8px 12px;
}
.search-container{ display:flex; flex:1; gap:8px; align-items:center; }
.search-input{ flex:1; min-width:120px; }
.search-btn{ width:110px; min-width:90px; }

.menu-navegacion{
  position: fixed; top: 72px; left: 25%; transform: translateX(-50%);
  padding: 14px; z-index: 9999; display:flex; flex-direction:column; gap:10px;
}
.menu-item{ padding:10px 12px; font-size:16px; font-weight:600; color:#0f172a; border-radius:10px; cursor:pointer; }
.menu-item:hover{ background:#eef7ff; }

/* ===== Media ===== */
.media-wrap{
  width:100%; max-width:420px;
  padding:14px; border-radius:16px;
}
.media-actions{ display:flex; gap:8px; margin-top:8px; }

/* Contenedor con lupa */
.zoom-area{
  position: relative;
  overflow: hidden;
  border-radius: 12px;
}
.media-img{ width:100%; height:100%; object-fit:contain; border-radius:12px; }
.zoom-hint{
  position: absolute;
  top: 8px; left: 8px;
  background: rgba(255,255,255,.8);
  border: 1px solid rgba(0,0,0,.06);
  border-radius: 999px;
  padding: 4px 6px;
  display: flex; align-items: center; justify-content: center;
}

/* ===== Título / Precio ===== */
.product-title{ font-size:clamp(22px,3.2vw,32px); font-weight:900; color:#0F172A; letter-spacing:.2px; }
.price-card{ display:flex; align-items:center; flex-wrap:wrap; gap:10px 12px; }
.price-card .before{ font-size:14px; color:#94a3b8; text-decoration:line-through; }

/* ===== Meta ===== */
.meta-grid{
  display:grid; grid-template-columns:1fr; gap:8px;
  padding: 12px 14px;
}
.meta-row{ display:flex; gap:10px; align-items:center; }
.meta-key{ width:170px; color:#475569; font-weight:700; }
.meta-val{ color:#0F172A; }

/* ===== Cantidad ===== */
.qty-row{ display:grid; grid-template-columns:1fr auto; gap:12px; align-items:center; }
.qty-title{ font-weight:800; color:#0F172A; }

.qty-controls{
  display:flex; align-items:center; gap:10px;
  padding: 6px 8px; border-radius: 999px;
}
.qty-btn{ width:38px; height:38px; }
.qty-input{
  width:100px; text-align:center; font-weight:700;
  border-radius: 10px; background: transparent;
}
.qty-input :deep(input){ text-align:center; font-weight:800; font-size:16px; }
.qty-unit{ color:#475569; font-weight:700; padding:0 6px; }

.total-box{
  grid-column:1 / -1; justify-self:end;
  padding:10px 16px; border-radius:12px;
  display:flex; gap:12px; align-items:center;
}
.total-label{ color:#475569; font-weight:700; }
.total-value{ color:#0F172A; font-size:20px; font-weight:900; }

/* ===== Secciones ===== */
.section-title{ font-weight:900; color:#0F172A; margin:14px 0 10px; }

/* ===== Grid relacionados ===== */
.contenedor-productos{ padding:0; max-width:100%; margin:0 auto; }
.products-grid{ display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:20px; }
.product-card-personalizada{
  width:200px; height:320px; margin:auto; transition:transform .2s ease, box-shadow .2s ease;
  display:flex; flex-direction:column; justify-content:space-between; overflow:hidden; border-radius:14px; cursor:pointer;
  border:1px solid rgba(45,156,219,.25);
}
.hover-pop:hover{ transform: translateY(-2px) scale(1.01); }
.product-name{ font-weight:700; font-size:15px; margin-bottom:8px; color:#0f172a; height:40px; overflow:hidden; text-overflow:ellipsis; line-height:1.2; }
.product-prices{ display:flex; justify-content:center; align-items:flex-end; gap:20px; }
.price-old{ text-align:right; color:#9aa3af; font-size:13px; text-decoration:line-through; line-height:1.2; }
.price-now{ text-align:left; color:#2D9CDB; font-size:16px; font-weight:900; line-height:1.2; }

/* ===== Sin stock overlay ===== */
.out-of-stock-overlay{
  position:absolute; inset:0; background:rgba(0,0,0,.25);
  display:flex; justify-content:center; align-items:center; border-radius:12px;
}
.out-of-stock-text{ font-size:20px; color:#fff; font-weight:900; text-transform:uppercase; z-index:10; letter-spacing:.5px; }

/* ===== Skeleton ===== */
.skeleton-media{ height:300px; border-radius:16px; }

/* ===== Sticky CTA móvil ===== */
.sticky-cta{
  position: fixed; left: 50%; transform: translateX(-50%);
  bottom: 14px; width: min(92vw, 1100px);
  display: none; align-items:center; justify-content:space-between;
  padding:10px 12px; border-radius:16px; z-index: 999;
}
.sticky-price .label{ font-size:12px; color:#64748b; font-weight:700; }
.sticky-price .value{ font-size:18px; font-weight:900; color:#0F172A; }
.sticky-actions{ display:flex; align-items:center; gap:10px; }
.sticky-stepper{ display:flex; align-items:center; gap:8px; padding:6px 8px; border-radius:999px; background:rgba(255,255,255,.7); }
.sticky-qty{ min-width:28px; text-align:center; font-weight:800; }

.big-cta{ height: 44px; font-weight: 800; letter-spacing: .2px; }

@media (max-width: 768px){
  .sticky-cta{ display:flex; }
}
</style>

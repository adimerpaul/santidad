<template>
  <q-page class="q-pa-none">
    <!-- 🔎 Barra superior -->
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

    <!-- ☰ Drawer simple -->
    <div v-if="drawer" class="menu-navegacion">
      <div class="menu-item" @click="navigateTo('/')">
        <q-icon name="home" class="q-mr-sm" /> Inicio
      </div>
      <div class="menu-item" @click="navigateTo('/sucursales')">
        <q-icon name="store" class="q-mr-sm" /> Sucursales
      </div>
    </div>

    <!-- Contenido -->
    <div class="page-wrapper q-pa-md">
      <div class="encabezado">
        <h1 class="titulo">Resultados para “{{ routeQ }}”</h1>
        <div class="subtitulo" v-if="isFetched">
          {{ productos.length }} resultado(s) · Página {{ currentPage }} / {{ totalPages }}
        </div>
      </div>

      <!-- Paginación (top) -->
      <div v-if="totalPages > 1" class="pagination-controls text-center q-mt-sm">
        <q-btn flat label="Anterior" :disabled="currentPage === 1" @click="cambiarPagina(currentPage - 1)" />
        <span>{{ currentPage }} / {{ totalPages }}</span>
        <q-btn flat label="Siguiente" :disabled="currentPage === totalPages" @click="cambiarPagina(currentPage + 1)" />
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center q-mt-xl">
        <q-spinner-dots size="50px" color="primary" />
      </div>

      <!-- Sin resultados -->
      <div v-else-if="isFetched && productos.length === 0" class="text-center q-mt-xl">
        <q-icon name="sentiment_dissatisfied" size="80px" color="grey-5" />
        <div class="text-h6 q-mt-md">No se encontraron productos</div>
      </div>

      <!-- Grid de resultados -->
      <div v-else class="contenedor-productos">
        <div class="products-grid">
          <q-card
            v-for="p in productos"
            :key="p.id"
            class="product-card-personalizada"
            flat
            @click="clickDetalleProducto(p)"
          >
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

            <!-- CTA segun stock -->
            <q-btn
              v-if="p.cantidad > 0"
              label="Añadir al carrito"
              icon="shopping_cart"
              class="q-mt-sm full-width cta-add"
              glossy
              unelevated
              no-caps
            />
            <q-btn
              v-else
              label="Agotado"
              icon="remove_shopping_cart"
              class="q-mt-sm full-width cta-off"
              unelevated
              no-caps
              disabled
            />
          </q-card>
        </div>

        <!-- Paginación (bottom) -->
        <div v-if="totalPages > 1" class="pagination-controls text-center q-mt-lg">
          <q-btn flat label="Anterior" :disabled="currentPage === 1" @click="cambiarPagina(currentPage - 1)" />
          <span>{{ currentPage }} / {{ totalPages }}</span>
          <q-btn flat label="Siguiente" :disabled="currentPage === totalPages" @click="cambiarPagina(currentPage + 1)" />
        </div>
      </div>
    </div>
  </q-page>
</template>

<script>
export default {
  name: 'BuscarResultados',
  data () {
    return {
      productos: [],
      loading: false,
      isFetched: false,
      drawer: false,
      search: this.$route.query.q || '',
      currentPage: Number(this.$route.query.page) || 1,
      totalPages: 1
    }
  },
  computed: {
    routeQ () { return this.$route.query.q || '' }
  },
  mounted () {
    this.fetchResultados()
  },
  watch: {
    '$route.query.q' () {
      this.search = this.$route.query.q || ''
      this.currentPage = Number(this.$route.query.page) || 1
      this.fetchResultados()
    },
    '$route.query.page' () {
      this.currentPage = Number(this.$route.query.page) || 1
      this.fetchResultados()
    }
  },
  methods: {
    toggleDrawer () { this.drawer = !this.drawer },
    navigateTo (ruta) { this.$router.push(ruta); this.drawer = false },

    buscar () {
      const q = (this.search || '').trim()
      if (!q) return
      this.$router.push({ path: '/buscar', query: { q, page: 1 } })
    },

    async fetchResultados () {
      const q = (this.$route.query.q || '').trim()
      const page = Number(this.$route.query.page) || 1
      if (!q) {
        this.productos = []
        this.isFetched = true
        this.totalPages = 1
        return
      }

      this.loading = true
      this.isFetched = false
      try {
        // usa el mismo endpoint que tu home para buscar
        const { data } = await this.$axios.get('productos', { params: { search: q, page } })

        const items = Array.isArray(data.data) ? data.data : (data.products?.data || [])
        this.totalPages = data.last_page || data.products?.last_page || 1

        this.productos = items.map(p => {
          const x = { ...p }
          if (Number(x.porcentaje) > 0) {
            x.precioNormal = x.precio
            const nuevo = x.precio - (x.precio * x.porcentaje / 100)
            x.precio = Number(nuevo).toFixed(2)
          }
          return x
        })
      } catch (e) {
        console.error('Error buscando productos', e)
        this.productos = []
        this.totalPages = 1
      } finally {
        this.loading = false
        this.isFetched = true
      }
    },

    cambiarPagina (pagina) {
      if (pagina < 1 || pagina > this.totalPages) return
      this.$router.push({ path: '/buscar', query: { q: this.routeQ, page: pagina } })
    },

    clickDetalleProducto (p) {
      this.$router.push('/detalle-producto/' + p.id + '/' + this.espacioCambioGuion(p.nombre))
    },
    espacioCambioGuion (text) {
      return text.replace(/ |\/|\./g, '-').replace(/,/g, '')
    }
  }
}
</script>

<style scoped>
/* ▲ Barra superior fija */
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
.search-container button{
  margin-left:10px; background: linear-gradient(135deg,#2D9CDB,#2D9CDB);
  color:#fff; border:none; border-radius:10px; box-shadow:0 8px 24px rgba(9,0,141,.2);
}

/* ☰ Drawer */
.menu-navegacion{
  position: fixed; top:65px; left:26%; transform: translateX(-50%);
  background-color: rgba(255,255,255,.9); border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,.1); padding: 15px; z-index: 9999;
  display:flex; flex-direction:column; gap:10px;
}
.menu-item{ padding:10px; font-size:18px; font-weight:bold; color:#333;
  display:flex; align-items:center; gap:10px; border-radius:6px; cursor:pointer; }
.menu-item:hover{ background:#f0f0f0; }

/* separador para que el contenido no quede debajo de la barra fija */
.page-wrapper{ padding-top: 88px; }

/* encabezado */
.encabezado{ display:flex; flex-direction:column; gap:6px; margin-bottom:8px; }
.titulo{ font-size:20px; font-weight:800; color:#0F172A; margin:0; }
.subtitulo{ font-size:13px; color:#64748B; }

/* grid y card – mismo look del home */
.contenedor-productos{ padding: 0 24px; max-width: 1200px; margin: 0 auto; }
.products-grid{
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px,1fr));
  gap: 20px;
}
.product-card-personalizada{
  height: 320px;
  display:flex; flex-direction:column; justify-content:space-between;
  overflow:hidden; border-radius:12px; border: 2px solid #2D9CDB;
  transition: transform .25s ease, box-shadow .25s ease;
  box-shadow: 0 2px 10px rgba(16,24,40,.06);
}
.product-card-personalizada:hover{ transform: scale(1.02); z-index:2; box-shadow:0 10px 26px rgba(16,24,40,.14); }

/* textos */
.product-name{
  font-weight:600; font-size:15px; margin-bottom:8px; color:#333;
  height:40px; overflow:hidden; text-overflow:ellipsis; line-height:1.2;
}
.product-prices{ display:flex; justify-content:center; align-items:flex-end; gap:20px; }
.price-old{ text-align:right; color:#999; font-size:13px; text-decoration:line-through; line-height:1.2; }
.price-now{ text-align:left; color:#2D9CDB; font-size:16px; font-weight:bold; line-height:1.2; }

/* estados */
.out-of-stock-overlay{
  position:absolute; inset:0; background: rgba(0,0,0,.2);
  display:flex; align-items:center; justify-content:center; border-radius:12px;
}
.out-of-stock-text{ font-size:22px; color:#fff; font-weight:800; text-transform:uppercase; }

/* CTA */
.cta-add{ background-color:#2D9CDB; color:white; font-weight:700; }
.cta-off{ background-color:#d9534f; color:white; font-weight:700; }

/* paginación */
.pagination-controls{ display:flex; align-items:center; justify-content:center; gap:16px; }
.pagination-controls span{ font-size:14px; color:#475569; }

/* responsive */
@media (max-width: 768px){
  .page-wrapper{ padding-top: 88px; }
  .contenedor-productos{ padding: 0 16px; }
  .products-grid{ gap: 14px; }
}
</style>

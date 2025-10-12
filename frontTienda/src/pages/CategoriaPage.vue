<template>
  <q-page class="q-pa-none">

    <!-- ===== SUBCATEGORÍAS (Desktop) ===== -->
    <aside class="sidebar-subcats">
      <div class="aside-title">Subcategorías</div>

      <q-skeleton v-if="loadingSubcats" type="text" class="q-mb-sm" />
      <q-skeleton v-if="loadingSubcats" type="text" class="q-mb-sm" />
      <q-skeleton v-if="loadingSubcats" type="text" class="q-mb-sm" />

      <template v-else>
        <div
          class="subcat-item"
          :class="{ active: subId === null }"
          @click="seleccionarSubcat(null)"
        >
          <q-icon name="grid_view" size="18px" class="q-mr-sm" /> Todas
        </div>
        <div
          v-for="sc in subcategorias"
          :key="sc.id"
          class="subcat-item"
          :class="{ active: Number(subId) === Number(sc.id) }"
          @click="seleccionarSubcat(sc.id)"
        >
          <q-icon name="label" size="18px" class="q-mr-sm" />
          {{ sc.name }}
        </div>
        <div v-if="!subcategorias.length" class="subcat-empty">(Sin subcategorías)</div>
      </template>
    </aside>

    <!-- ===== SUBCATEGORÍAS (Móvil) ===== -->
    <div class="subcats-mobile">
      <q-btn-dropdown
        outline
        rounded
        no-caps
        icon="category"
        :label="subNombre ? `Subcategoría: ${subNombre}` : 'Subcategorías'"
        content-class="dropdown-subcats"
      >
        <q-list>
          <q-item clickable v-ripple @click="seleccionarSubcat(null)">
            <q-item-section avatar><q-icon name="grid_view" /></q-item-section>
            <q-item-section>Todas</q-item-section>
            <q-item-section side><q-icon v-if="subId===null" name="check" /></q-item-section>
          </q-item>

          <q-item
            v-for="sc in subcategorias"
            :key="sc.id"
            clickable
            v-ripple
            @click="seleccionarSubcat(sc.id)"
          >
            <q-item-section avatar><q-icon name="label" /></q-item-section>
            <q-item-section>{{ sc.name }}</q-item-section>
            <q-item-section side>
              <q-icon v-if="Number(subId) === Number(sc.id)" name="check" />
            </q-item-section>
          </q-item>
        </q-list>
      </q-btn-dropdown>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="page-wrapper q-pa-md contenido">
      <h2 class="text-h5 titulo">
        Categoría: {{ categoriaNombre }} <span v-if="subNombre" class="chip">· {{ subNombre }}</span>
      </h2>

      <!-- Paginación TOP -->
      <div v-if="totalPages > 1" class="pagination-controls text-center q-mt-lg">
        <q-btn flat label="Anterior" :disabled="currentPage === 1" @click="cambiarPagina(currentPage - 1)" />
        <span>{{ currentPage }} / {{ totalPages }}</span>
        <q-btn flat label="Siguiente" :disabled="currentPage === totalPages" @click="cambiarPagina(currentPage + 1)" />
      </div>

      <!-- Spinner -->
      <div v-if="loading" class="text-center q-mt-lg">
        <q-spinner-dots size="50px" color="primary" />
      </div>

      <!-- Sin productos -->
      <div v-else-if="isFetched && productos.length === 0" class="text-center q-mt-lg">
        <q-icon name="sentiment_dissatisfied" size="80px" color="grey-5" />
        <div class="text-h6 q-mt-md">No hay productos en esta categoría</div>
      </div>

      <!-- Grid productos -->
<div v-else class="contenedor-productos">
  <div class="products-grid">
    <q-card
      v-for="p in productos"
      :key="p.id"
      class="product-card-personalizada"
      flat
      @click="clickDetalleProducto(p)"
    >
      <q-img :src="resolverImagen(p)" style="height:160px; object-fit:cover;">
        <!-- Mostramos el estado de 'Sin Stock' si el stock es 0 -->
        <div v-if="p.stockDisponible === 'Sin Stock'" class="out-of-stock-overlay">
          <span class="out-of-stock-text">Sin Stock</span>
        </div>
        <q-badge v-if="Number(p.porcentaje) > 0" color="red" floating>-{{ p.porcentaje }}%</q-badge>
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

      <!-- Mostrar el botón de "Añadir al carrito" si hay stock disponible -->
      <q-btn
        v-if="p.stockDisponible === 'Disponible'"
        label="Añadir al carrito"
        icon="shopping_cart"
        class="q-mt-sm full-width"
        style="background-color:#2D9CDB; color:#fff;"
        glossy
        unelevated
        no-caps
      />
      <!-- Mostrar "Agotado" si no hay stock -->
      <q-btn
        v-else
        label="Agotado"
        icon="remove_shopping_cart"
        class="q-mt-sm full-width"
        style="background-color:#d9534f; color:#fff;"
        unelevated
        no-caps
        disabled
      />
    </q-card>
  </div>

  <!-- Paginación BOTTOM -->
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
  name: 'CategoriaPage',
  props: { id: { type: [String, Number], required: true } }, // category_id
  data () {
    return {
      productos: [],
      subcategorias: [],
      loadingSubcats: false,
      loading: false, // Añadido para la carga de productos
      categoriaNombre: '',
      subNombre: '',
      subId: null, // subcategoría seleccionada
      currentPage: 1,
      totalPages: 1,
      isFetched: false,
      itemsPerPage: 20
    }
  },
  async mounted () {
    await this.fetchCategoria()
    await this.fetchSubcategorias()
    await this.fetchProductos()
  },
  watch: {
    id () {
      // si cambia la categoría
      this.currentPage = 1
      this.subId = null
      this.subNombre = ''
      this.fetchCategoria()
      this.fetchSubcategorias()
      this.fetchProductos()
    }
  },
  methods: {
    async fetchCategoria () {
      try {
        const { data } = await this.$axios.get(`categories/${this.id}`)
        this.categoriaNombre = data.name
      } catch (e) {
        this.categoriaNombre = 'Desconocida'
      }
    },

    async fetchSubcategorias () {
      this.loadingSubcats = true
      try {
        const { data } = await this.$axios.get('subcategories', { params: { category_id: this.id } })
        const lista = Array.isArray(data) ? data : (data.data || [])
        const catIdNum = Number(this.id)
        this.subcategorias = lista.filter(s => Number(s.category_id) === catIdNum)
        this.setSubNombre()
      } catch (e) {
        this.subcategorias = []
        this.subNombre = ''
      } finally {
        this.loadingSubcats = false
      }
    },

    setSubNombre () {
      if (!this.subId && this.subId !== 0) { this.subNombre = ''; return }
      const sc = this.subcategorias.find(s => Number(s.id) === Number(this.subId))
      this.subNombre = sc ? sc.name : ''
    },

    async fetchProductos () {
      this.loading = true
      this.isFetched = false
      this.productos = []

      try {
        const resp = await this.$axios.get('productsSale', {
          params: {
            category: this.id,
            page: this.currentPage,
            subcategory_id: this.subId || undefined,
            subcategory: this.subId || undefined
          }
        })
        const pag = resp.data.products
        const items = (pag && pag.data) ? pag.data : []

        if (items.length > 0) {
          this.productos = items.map(p => {
            const x = { ...p }

            // Verificamos si alguna sucursal tiene stock mayor a 0
            const stockDisponible = [
              p.cantidadSucursal1,
              p.cantidadSucursal2,
              p.cantidadSucursal3,
              p.cantidadSucursal4,
              p.cantidadSucursal5,
              p.cantidadSucursal6,
              p.cantidadSucursal7
            ].some(cantidad => cantidad > 0) // Verifica si alguna sucursal tiene stock > 0

            // Si el stock está disponible en alguna sucursal, lo marcamos como disponible
            x.stockDisponible = stockDisponible ? 'Disponible' : 'Sin Stock'

            return x
          })
          this.totalPages = pag.last_page || 1
        } else {
          this.productos = []
          this.totalPages = 1
        }
      } catch (error) {
        console.error('Error al cargar productos:', error)
        this.productos = []
        this.totalPages = 1
      } finally {
        this.loading = false
        this.isFetched = true
      }
    },

    seleccionarSubcat (id) {
      this.subId = id // null = Todas
      this.currentPage = 1
      this.setSubNombre()
      this.fetchProductos()
    },

    cambiarPagina (pagina) {
      if (pagina < 1 || pagina > this.totalPages) return
      this.currentPage = pagina
      this.fetchProductos()
    },

    resolverImagen (p) {
      const img = p?.imagen
      if (!img) return `${this.$url}../images/noimagen.jpg`
      return img.includes('http') ? img : `${this.$url}../images/${img}`
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

/* ===== SIDEBAR Subcategorías (Desktop) ===== */
.sidebar-subcats{
  position: fixed;
  top: 92px;           /* debajo de la barra superior */
  left: 24px;          /* separado del borde izq */
  bottom: 24px;
  width: 248px;        /* un poco más ancho, moderno */
  overflow:auto;
  background: rgba(255,255,255,.92);
  backdrop-filter: blur(8px);
  border: 1px solid #E5E7EB;
  border-radius: 14px;
  padding: 12px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
}
.aside-title{
  font-weight:800; font-size:16px; color:#0F172A;
  margin-bottom:10px; letter-spacing:.2px;
}
.subcat-item{
  display:flex; align-items:center;
  padding:10px 12px; border-radius:10px; cursor:pointer; font-size:14px;
  color:#334155; transition: background .15s ease, color .15s ease, transform .1s ease;
  margin-bottom:6px; user-select:none;
}
.subcat-item:hover{ background:#F1F5F9; transform: translateX(2px); }
.subcat-item.active{
  background:linear-gradient(180deg, rgba(45,156,219,.15), rgba(45,156,219,.08));
  color:#0B5ED7; font-weight:700; border:1px solid rgba(45,156,219,.35);
}
.subcat-empty{ font-size:12px; color:#94a3b8; padding:6px 2px }

/* ===== SUBCATS Móvil ===== */
.subcats-mobile{
  display: none;
  position: sticky;
  top: 76px;
  z-index: 998;
  padding: 8px 12px;
  margin: 0 auto;
  width: min(96vw, 1100px);
}
.dropdown-subcats{
  min-width: 260px;
}

/* ===== Contenido ===== */
.page-wrapper{ padding-top: 90px; }

.contenido{
  /* Centrar visualmente el bloque de contenido y dejar espacio al sidebar */
  margin-left: calc(248px + 10%);   /* espacio sidebar + separación */
  margin-right: 24px;
  max-width: 1200px;
  width: calc(100% - 24px);
}

/* Título */
.titulo{ display:flex; align-items:center; gap:8px; }
.titulo .chip{ font-size:14px; font-weight:700; color:#2D9CDB }

/* Grid productos y tarjeta */
.contenedor-productos{ padding: 0; }
.products-grid{
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px,1fr));
  gap: 20px;
  max-width: 1000px;          /* ancho legible */
  margin: 0 auto;              /* centrado visual del grid */
}
.product-card-personalizada{
  width: 200px; height: 320px; margin: auto; transition: transform .25s;
  display:flex; flex-direction:column; justify-content:space-between;
  overflow:hidden; border-radius:12px; border:2px solid #2D9CDB;
  background: #fff;
}
.product-card-personalizada:hover{ transform: translateY(-2px); }

/* Texto tarjeta */
.product-name{
  font-weight:600; font-size:15px; margin-bottom:8px; color:#333;
  height:40px; overflow:hidden; text-overflow:ellipsis; line-height:1.2;
}
.product-prices{ display:flex; justify-content:center; align-items:flex-end; gap:20px; }
.price-old{ text-align:right; color:#999; font-size:13px; text-decoration:line-through; line-height:1.2; }
.price-now{ text-align:left; color:#2D9CDB; font-size:16px; font-weight:bold; line-height:1.2; }

/* Sin stock */
.out-of-stock-overlay{
  position:absolute; inset:0; background-color:rgba(0,0,0,.2);
  display:flex; justify-content:center; align-items:center; border-radius:12px;
}
.out-of-stock-text{ font-size:24px; color:white; font-weight:bold; text-transform:uppercase; z-index:10; }

/* Paginación */
.pagination-controls{ display:flex; align-items:center; justify-content:center; gap:16px; }
.pagination-controls span{ font-size:14px; color:#475569; }

/* ===== Responsive ===== */

/* ====== RESPONSIVE FINO ====== */

/* Monitores grandes (>=1200px hasta 1600px) */
@media (max-width: 1600px){
  .contenido{
    margin-left: calc(248px + 40px);
    margin-right: 20px;
  }
  .products-grid{ max-width: 1080px; }
}

/* 1440px */
@media (max-width: 1440px){
  .contenido{
    margin-left: calc(248px + 36px);
    margin-right: 18px;
  }
  .products-grid{ max-width: 980px; }
}

/* 1366px */
@media (max-width: 1366px){
  .contenido{
    margin-left: calc(248px + 32px);
    margin-right: 16px;
  }
  .products-grid{ max-width: 940px; }
}

/* 1280px */
@media (max-width: 1280px){
  .contenido{
    margin-left: calc(248px + 28px);
    margin-right: 14px;
  }
  .products-grid{ max-width: 920px; }
}

/* Tablet horizontal y laptops pequeñas */
@media (max-width: 1200px){
  .contenido{
    margin-left: calc(248px + 24px);
    margin-right: 12px;
  }
  .products-grid{ max-width: 880px; }
}

/* Entre 1100 y 1024 */
@media (max-width: 1100px){
  .contenido{
    margin-left: calc(248px + 20px);
    margin-right: 10px;
  }
  .products-grid{ max-width: 840px; }
}

@media (max-width: 1024px){
  .contenido{
    margin-left: calc(248px + 16px);
    margin-right: 8px;
  }
  .products-grid{ max-width: 800px; }
}

/* <= 992px: sidebar se vuelve móvil (dropdown) y el contenido ocupa todo, bien centrado */
@media (max-width: 992px){
  .sidebar-subcats{ display: none; }
  .subcats-mobile{ display: block; }

  .contenido{
    margin-left: auto;
    margin-right: auto;
    width: min(96vw, 1100px);
  }
  .products-grid{
    max-width: 100%;
    padding: 0 4px;
  }
}

/* <= 600px: grid más cómodo en celulares */
@media (max-width: 600px){
  .products-grid{
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 14px;
  }
  .product-card-personalizada{
    width: 100%;
    height: 310px;
  }
}
</style>

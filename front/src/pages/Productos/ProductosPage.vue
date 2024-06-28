<template>
  <q-page class="bg-grey-2 q-pa-xs">
    <div class="row">
      <div class="col-12 col-md-6 bg-white">
        <q-input label-color="black" outlined v-model="search" label="Buscar producto" dense clearable @update:model-value="productsGet" debounce="500">
          <template v-slot:prepend>
            <q-icon name="search" class="cursor-pointer" />
          </template>
        </q-input>
      </div>
      <div class="col-5 col-md-3">
        <q-btn color="black" no-caps flat icon="o_file_download" @click="downloadReport" :loading="loading">
          <div class="q-page-xs subrayado"> Descargar reporte</div>
        </q-btn>
      </div>
      <div class="col-7 col-md-3 text-right">
        <q-btn :loading="loading" color="green" rounded no-caps icon="add_circle_outline" label="Nuevo producto" @click="showAddProduct">
          <q-tooltip>Crear nuevo producto</q-tooltip>
        </q-btn>
        <q-btn :loading="loading" icon="refresh" dense color="grey-7" flat @click="productsGet">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
      </div>
      <div class="col-12 col-md-6 q-pa-xs">
        <q-card class="">
          <q-card-section class="q-pa-none">
            <q-item>
              <q-item-section avatar>
                <q-btn icon="o_view_in_ar" size="22px" color="grey-7" class="bg-grey-2"  flat />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle2 text-grey">Total referencia</q-item-label>
                <q-item-label class="text-bold text-h6">{{totalProducts}}</q-item-label>
              </q-item-section>
            </q-item>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-6 q-pa-xs">
        <q-card class="">
          <q-card-section class="q-pa-none">
            <q-item>
              <q-item-section avatar>
                <q-btn icon="o_local_atm" size="22px" color="green-7" class="bg-green-2"  flat />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-subtitle2 text-grey">Costo total de inventario</q-item-label>
                <q-item-label :class="`text-bold text-h6 text-${costoTotalProducts>0?'green':'red'}`">{{costoTotalProducts}} Bs</q-item-label>
              </q-item-section>
            </q-item>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-3 q-pa-xs flex flex-center">
        <q-btn outline no-caps icon="o_edit" class="full-width" label="Categoria y sub categoria" @click="categoryDialog=true" />
      </div>
      <div class="col-12 col-md-3 q-pa-xs">
        <q-select class="bg-white" emit-value map-options dense outlined
                  v-model="category" option-value="id" option-label="name" :options="categories"
                  @update:model-value="productsGet"
        >
          <template v-slot:before>
            <q-btn color="green" dense size="15px" flat no-caps icon="o_add_circle_outline" @click="showAddCategory">
              <q-tooltip>Crear categoria</q-tooltip>
            </q-btn>
          </template>
        </q-select>
      </div>
      <div class="col-12 col-md-3 q-pa-xs">
        <q-select class="bg-white" label="Ordenar" dense outlined v-model="order"
                  :options="orders" map-options emit-value
                  option-value="value" option-label="label"
                  @update:model-value="productsGet"
        />
      </div>
      <div class="col-12 col-md-3 q-pa-xs">
        <q-select class="bg-white" label="Agencia" dense outlined v-model="agencia"
                  :options="agencias" map-options emit-value
                  option-value="id" option-label="nombre"
                  @update:model-value="productsGet"
                  />
<!--        :disable="!($store.user.id=='1')"-->
      </div>
      <div class="col-12 flex flex-center">
        <q-pagination
          v-model="current_page"
          :max="last_page"
          :max-pages="6"
          boundary-numbers
          @update:model-value="productsGet"
        />
      </div>
      <div class="col-12">
        <q-card>
          <q-card-section class="q-pa-xs">
            <div class="row cursor-pointer" v-if="products.length>0">
              <div class="col-4 col-md-2" v-for="p in products" :key="p.id">
                <q-card @click="clickDetalleProducto(p)">
                  <q-img :src="p.imagen.includes('http')?p.imagen:`${$url}../images/${p.imagen}`" width="100%" height="100px">
                    <div class="absolute-bottom text-center text-subtitle2" style="padding: 0px 0px;line-height: 1;">
                      {{p.nombre}}
                    </div>
                  </q-img>
                  <q-card-section class="q-pa-none q-ma-none">
                    <div class="text-center text-subtitle2">{{ p.precio }} Bs</div>
                    <div :class="`text-center text-bold text-${p.cantidad<=10?'red':p.cantidad<=20?'yellow-9':'black'}`">{{ p.cantidad }} {{ $q.screen.lt.md?'Dis':'Disponible' }}</div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
            <q-card v-else>
              <q-card-section>
                <div class="row">
                  <div class="col-12 flex flex-center">
                    <q-avatar size="150px" font-size="150px" color="white" text-color="grey" icon="view_in_ar" />
                  </div>
                  <div class="col-12">
                    <div class="text-bold text-grey text-center">No encontramos productos para tu búsqueda.</div>
                    <div class="text-bold text-grey text-center">Intenta con otra palabra o agrega productos a tu Inventario.</div>
                  </div>
                </div>
              </q-card-section>
            </q-card>
          </q-card-section>
        </q-card>
      </div>
    </div>
    <q-dialog v-model="productDialog" position="right" maximized>
      <ProductionOptionComponent :agencias="agencias" :productData="product" :unidadesData="unidades" :productActionData="productAction" v-if="productDialog"
                                 :categories="categories"
                                 :subcategories="subcategories"
                                 @productsGet="productsGet"
                                 @close="productDialog=false"
      />
    </q-dialog>
    <q-dialog v-model="categoryDialog" position="right" maximized>
      <CategoriComponent :categories="categoriesTable" v-if="categoryDialog" @categoriesGet="categoriesGet"/>
    </q-dialog>
    <div id="myElement" class="hidden"></div>
  </q-page>
</template>

<script>
import CategoriComponent from 'pages/Productos/CategoriComponent.vue'
import ProductionOptionComponent from 'pages/Productos/ProductionOptionComponent.vue'
export default {
  name: 'ProductosPage',
  components: {
    CategoriComponent,
    ProductionOptionComponent
  },
  data () {
    return {
      dialogMover: false,
      current_page: 1,
      search: '',
      unidades: [],
      unidadesAll: [],
      last_page: 1,
      loading: false,
      productDialog: false,
      categoryDialog: false,
      productAction: 'create',
      products: [],
      totalProducts: 0,
      agencias: [],
      compra: {},
      agencia: 0,
      product: { cantidad: 0, nombre: '', barra: '', costo: 0, precio: 0, descripcion: '', category_id: 0 },
      category: 0,
      categories: [
        { name: 'Ver todas las categorias', id: 0 }
      ],
      categoriesTable: [],
      categorySelected: {},
      categoriesTableColumns: [
        { name: 'name', label: 'Nombre', field: 'name', align: 'left', sortable: true },
        { name: 'actions', label: 'Acciones', field: 'actions', align: 'right', sortable: false }
      ],
      order: 'id',
      orders: [
        { label: 'Ordenar por', value: 'id' },
        { label: 'Menor precio', value: 'precio asc' },
        { label: 'Mayor precio', value: 'precio desc' },
        { label: 'Menor cantidad', value: 'cantidad asc' },
        { label: 'Mayor cantidad', value: 'cantidad desc' },
        { label: 'Orden alfabetico', value: 'nombre asc' },
        { label: 'Cantidad cero', value: 'cantidad asc' }
      ],
      costoTotalProducts: 0,
      subcategories: []
    }
  },
  created () {
    this.categoriesGet()
    this.subcategoriesGet()
    this.agenciasGet()
    this.productsGet()
    this.unitsGet()
  },
  methods: {
    subcategoriesGet () {
      this.$axios.get('subcategories').then(response => {
        this.subcategories = response.data
      }).catch(error => {
        console.log(error)
      })
    },
    async unitsGet () {
      this.res = await this.$axios.get('unids')
      this.unidadesAll = this.res.data
      this.unidades = this.res.data
    },
    agenciasGet () {
      this.agencias = [{ nombre: 'Selecciona una agencia', id: 0 }]
      this.$axios.get('agencias').then(response => {
        this.agencias = this.agencias.concat(response.data)
      }).catch(error => {
        this.$alert.error(error.response.data.message)
      })
    },
    downloadReport () {
      this.loading = true
      this.$axios.get('productsAll').then(res => {
        const data = [
          {
            columns: [
              { value: 'id', label: 'ID' },
              { value: 'nombre', label: 'Nombre' },
              { value: 'barra', label: 'Código de barras' },
              { value: 'cantidad', label: 'Cantidad' },
              { value: 'costo', label: 'Costo' },
              { value: 'precio', label: 'Precio' },
              { value: 'activo', label: 'Activo' },
              { value: 'imagen', label: 'Imagen' },
              { value: 'descripcion', label: 'Descripción' },
              // { label: 'User', value: 'user' }, // Top level data
              // { label: 'Age', value: (row) => row.age + 'years' }, // Custom format
              { label: 'Categoria', value: (row) => (row.category ? row.category.name || '' : '') } // Run functions
            ],
            content: res.data
          }
        ]
        this.$excel.export(data)
      }).catch(err => {
        console.log(err)
      }).finally(() => {
        this.loading = false
      })
    },
    productsGet () {
      this.loading = true
      this.$axios.get(`products?page=${this.current_page}&search=${this.search}&order=${this.order}&category=${this.category}&agencia=${this.agencia}`).then(res => {
        this.loading = false
        // console.log(res.data.products)
        this.totalProducts = res.data.products.total
        this.products = res.data.products.data
        // console.log(this.products)
        this.last_page = res.data.products.last_page
        this.current_page = res.data.products.current_page
        this.costoTotalProducts = parseFloat(res.data.costoTotal).toFixed(2)
      }).catch(err => {
        this.loading = false
        console.log(err)
      })
    },
    clickDetalleProducto (product) {
      this.product = product
      this.productDialog = true
      this.productAction = 'ver'
    },
    showAddProduct () {
      this.productAction = 'create'
      this.productDialog = true
      this.product = {
        cantidad: 0,
        nombre: '',
        barra: '',
        activo: 'ACTIVO',
        costo: 0,
        precio: 0,
        descripcion: '',
        category_id: 0,
        agencia_id: 0
      }
    },
    categoriesGet () {
      this.categories = [{ name: 'Ver todas las categorias', id: 0 }]
      this.$axios.get('categories').then(response => {
        this.categories = this.categories.concat(response.data)
        this.categoriesTable = response.data
      }).catch(error => {
        console.log(error)
      })
    },
    showAddCategory () {
      this.$q.dialog({
        title: 'Crear categoria',
        message: 'Ingrese el nombre de la categoria',
        prompt: {
          model: '',
          type: 'text'
        }
      }).onOk(data => {
        if (data === '') {
          this.$alert.error('El nombre de la categoria no puede estar vacio')
          return
        }
        this.$axios.post('categories', { name: data }).then(response => {
          this.categoriesGet()
          this.$alert.success('Categoria creada correctamente')
        }).catch(error => {
          console.log(error)
        })
      })
    }
  },
  computed: {
    compraTotal () {
      if (this.compra.quantity && this.compra.price) {
        return this.compra.quantity * this.compra.price
      } else {
        return 0
      }
    }
  }
}
</script>

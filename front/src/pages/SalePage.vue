<template>
  <q-page class="bg-grey-2 q-pa-xs">
    <div class="row">
      <div class="col-12 col-md-8">
        <div class="row">
          <div class="col-12 col-md-6 bg-white">
            <q-input outlined v-model="search" label="Buscar producto" dense clearable @update:model-value="productsGet" debounce="500">
              <template v-slot:prepend>
                <q-icon name="search" class="cursor-pointer" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-6">
            <q-btn :loading="loading" icon="refresh" dense color="grey-7" flat @click="productsGet">
              <q-tooltip>Actualizar</q-tooltip>
            </q-btn>
          </div>
          <div class="col-12 col-md-4 q-pa-xs">
            <q-select class="bg-white" emit-value map-options dense outlined
                      v-model="category" option-value="id" option-label="name" :options="categories"
                      @update:model-value="productsGet"
            >
            </q-select>
          </div>
          <div class="col-12 col-md-4 q-pa-xs">
            <q-select class="bg-white" label="Ordenar" dense outlined v-model="order"
                      :options="orders" map-options emit-value
                      option-value="value" option-label="label"
                      @update:model-value="productsGet"
            />
          </div>
          <div class="col-12 col-md-4 q-pa-xs">
            <q-select class="bg-white" label="Agencia" dense outlined v-model="$store.agencia_id"
                      :options="agencias" map-options emit-value
                      option-value="id" option-label="nombre"
                      @update:model-value="productsGet"
            />
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
              <q-card-section class="q-pa-none">
                <div class="row cursor-pointer" v-if="products.length>0">
                  <div class="col-4 col-md-2" v-for="p in products" :key="p.id">
                    <q-card @click="clickAddSale(p)" class="q-pa-xs" flat bordered>
                      <q-img :src="p.imagen.includes('http')?p.imagen:`${$url}../images/${p.imagen}`" width="100%" height="100px">
                        <div class="absolute-bottom text-center text-subtitle2" style="padding: 0px 0px;">
                          {{p.nombre}}
                        </div>
                        <q-badge v-if="p.cantidadPedida>0" color="yellow-9" floating :label="p.cantidadPedida" style="padding: 5px"/>
                      </q-img>
                      <q-card-section class="q-pa-none q-ma-none">
                        <div class="text-center text-subtitle2">{{ p.precio }} Bs</div>
                        <div :class="p.cantidad<=0?'text-center text-bold text-red':' text-center text-bold'">{{ p.cantidad }} {{ $q.screen.lt.md?'Dis':'Disponible' }}</div>
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
      </div>
      <div class="col-12 col-md-4">
        <q-card>
          <q-card-section class="q-pa-none q-ma-none ">
            <div class="row">
              <div class="col-6 text-h6 q-pt-xs q-pl-lg">Canasta</div>
              <div class="col-6 text-right"><q-btn class="text-subtitle1 text-blue-10 text-bold" style="text-decoration: underline;" label="Vaciar canasta" @click="vaciarCanasta" no-caps flat outline/></div>
            </div>
          </q-card-section>
          <q-separator></q-separator>
          <q-card-section class="q-pa-none q-ma-none" >
            <div v-if="$store.productosVenta.length==0" class="flex flex-center q-pa-lg">
              <q-icon name="o_shopping_basket" color="grey" size="100px"/>
              <div class="q-pa-lg text-grey text-center noSelect">
                Aún no tienes productos en tu canasta. Haz clic sobre un producto para agregarlo.
              </div>
            </div>
            <q-scroll-area v-else style="height: 400px;">
              <q-table dense flat bordered hide-bottom hide-header :rows="$store.productosVenta" :columns="columnsProductosVenta" :rows-per-page-options="[0]">
                <template v-slot:body="props">
                  <q-tr :props="props">
                    <q-td key="borrar" :props="props" style="padding: 0px">
                      <q-btn flat dense @click="deleteProductosVenta(props.row,props.pageIndex)" icon="delete_outline" color="red" size="10px" class="q-pa-none" />
                    </q-td>
                    <q-td key="nombre" :props="props">
                      <div class="row">
                        <div class="col-3">
                          <q-img :src="props.row.imagen.includes('http')?props.row.imagen:`${$url}../images/${props.row.imagen}`" width="40px" height="80px" />
                        </div>
                        <div class="col-9">
                          <div>{{props.row.nombre}}</div>
                          <div class="text-grey">Disponible: {{props.row.cantidad}}</div>
                          <div>
                            <div class="row">
                              <div class="col-8">
                                <q-input v-model="props.row.precioVenta" type="number" @update:model-value="precioVenta(props.row)" dense style="margin: 0px">
                                  <template v-slot:prepend>
                                    <q-icon name="edit" size="xs" />
                                    <div style="font-size: 10px">Bs.</div>
                                  </template>
                                </q-input>
                              </div>
                              <div class="col-2 text-bold flex flex-center">
                                x und
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </q-td>
                    <q-td key="cantidadVenta" :props="props">
                      <q-input dense outlined bottom-slots min="1" v-model="props.row.cantidadVenta" @update:model-value="cambioNumero(props.row,props.pageIndex)" :rules="ruleNumber" type="number" input-class="text-center" required>
                        <template v-slot:prepend>
                          <q-icon style="cursor: pointer" name="remove_circle_outline" @click="removeCantidad(props.row,props.pageIndex)"/>
                        </template>
                        <template v-slot:append>
                          <q-icon style="cursor: pointer" name="add_circle_outline" @click="addCantidad(props.row,props.pageIndex)"/>
                        </template>
                      </q-input>
                      <div class="text-grey">= Bs {{redondeo(props.row.cantidadVenta*props.row.precioVenta)}}</div>
                    </q-td>
                  </q-tr>
                </template>
              </q-table>
            </q-scroll-area>
          </q-card-section>
          <q-card-section >
            <q-list padding bordered dense class="rounded-borders full-width q-pa-none q-ma-none">
              <q-expansion-item
                dense
                dense-toggle
                expand-separator
                label="Total"
              >
                <template v-slot:header>
                  <q-item-section>
                    Total
                  </q-item-section>
                  <q-item-section side>
                    <div class="text-right text-grey-8 text-bold"> <u> Bs {{total}}</u></div>
                  </q-item-section>
                </template>
                <q-card>
                  <q-card-section>
                    <div class="row">
                      <div class="col-7 text-grey">Cantidades de referencia</div>
                      <div class="col-5 text-right">{{$store.productosVenta.length}}</div>
                      <div class="col-7 text-grey">
                        Ganancia
                        <q-icon name="o_info">
                          <q-tooltip anchor="top middle" self="bottom middle" :offset="[10, 10]">
                            Para calcular la ganancia correctamente, deberás cargar el costo unitario de todos los productos desde tu Inventario.
                          </q-tooltip>
                        </q-icon>
                      </div>
                      <div class="col-5 text-right text-green">{{totalganancia}} Bs</div>
                    </div>
                  </q-card-section>
                </q-card>
              </q-expansion-item>
            </q-list>
            <q-btn @click="clickSale" class="full-width" no-caps label="Confirmar venta" :color="$store.productosVenta.length==0?'grey':'warning'" :disable="$store.productosVenta.length==0?true:false"/>
          </q-card-section>
        </q-card>
      </div>
    </div>
</q-page>
</template>
<script>
export default {
  name: 'SalePage',
  data () {
    return {
      current_page: 1,
      last_page: 1,
      ruleNumber: [
        val => (val !== null && val !== '') || 'Por favor escriba su cantidad',
        val => (val >= 0 && val < 10000) || 'Por favor escriba una cantidad real'
      ],
      search: '',
      loading: false,
      products: [],
      totalProducts: 0,
      agencias: [],
      // agencia: 0,
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
      columnsProductosVenta: [
        { label: 'borrar', field: 'borrar', name: 'borrar', align: 'left' },
        { label: 'nombre', field: 'nombre', name: 'nombre', align: 'left' },
        { label: 'cantidadVenta', field: 'cantidadVenta', name: 'cantidadVenta' }
      ],
      orders: [
        { label: 'Ordenar por', value: 'id' },
        { label: 'Menor precio', value: 'precio asc' },
        { label: 'Mayor precio', value: 'precio desc' },
        { label: 'Menor cantidad', value: 'cantidad asc' },
        { label: 'Mayor cantidad', value: 'cantidad desc' },
        { label: 'Orden alfabetico', value: 'nombre asc' }
      ]
    }
  },
  mounted () {
    this.productsGet()
    this.categoriesGet()
    this.agenciasGet()
  },
  methods: {
    precioVenta (n) {
      if (n.precioVenta === '') {
        n.precioVenta = 1
      }
    },
    redondeo (n) {
      return Math.round(n * 100) / 100
    },
    addCantidad (n, i) {
      n.cantidad--
      n.cantidadPedida++
      n.cantidadVenta = parseInt(n.cantidadVenta) + 1
    },
    cambioNumero (n, i) {
      if (n.cantidadVenta !== '') {
        n.cantidad = parseInt(n.cantidadReal) - parseInt(n.cantidadVenta)
        n.cantidadPedida = parseInt(n.cantidadVenta)
      }
      if (n.cantidadVenta === 0) {
        n.cantidad = parseInt(n.cantidadReal) - 1
        n.cantidadVenta = 1
        n.cantidadPedida = 1
      }
    },
    removeCantidad (n, i) {
      n.cantidad++
      n.cantidadPedida--
      if (n.cantidadVenta > 1) {
        n.cantidadVenta = parseInt(n.cantidadVenta) - 1
      } else if (n.cantidadVenta === 1) {
        this.$store.productosVenta.splice(i, 1)
      }
    },
    deleteProductosVenta (p, i) {
      this.$store.productosVenta.splice(i, 1)
      p.cantidad = p.cantidadReal
      p.cantidadVenta = 0
      p.cantidadPedida = 0
    },
    async vaciarCanasta () {
      await this.$store.productosVenta.forEach(p => {
        p.cantidad = p.cantidadReal
        p.cantidadVenta = 0
        p.cantidadPedida = 0
      })
      this.$store.productosVenta = []
    },
    clickAddSale (product) {
      product.cantidad--
      product.cantidadPedida++
      const productVenta = this.$store.productosVenta.find(p => p.id === product.id)
      if (productVenta) {
        productVenta.cantidadVenta++
      } else {
        product.cantidadVenta = 1
        this.$store.productosVenta.push(product)
      }
    },
    agenciasGet () {
      this.agencias = [{ nombre: 'Selecciona una agencia', id: 0 }]
      this.$axios.get('agencias').then(response => {
        this.agencias = this.agencias.concat(response.data)
      }).catch(error => {
        this.$alert.error(error.response.data.message)
      })
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
    productsGet () {
      this.loading = true
      this.products = []
      this.$axios.get(`products?page=${this.current_page}&search=${this.search}&order=${this.order}&category=${this.category}&agencia=${this.$store.agencia_id}`).then(res => {
        this.loading = false
        // console.log(res.data.products)
        this.totalProducts = res.data.products.total
        // this.products = res.data.products.data
        // console.log(this.products)
        this.last_page = res.data.products.last_page
        this.current_page = res.data.products.current_page
        this.costoTotalProducts = parseFloat(res.data.costoTotal).toFixed(2)
        res.data.products.data.forEach(p => {
          p.cantidadPedida = 0
          p.cantidadReal = p.cantidad
          p.precioVenta = p.precio
          this.products.push(p)
        })
      }).catch(err => {
        this.loading = false
        console.log(err)
      })
    }
  },
  computed: {
    total () {
      let s = 0
      this.$store.productosVenta.forEach(p => {
        s = s + parseFloat(p.precioVenta * p.cantidadVenta)
      })
      return s.toFixed(2)
    }
  }
}
</script>

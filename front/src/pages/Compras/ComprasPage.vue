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
          <div class="col-12 col-md-6 flex">
            <q-btn :loading="loading" icon="refresh" dense label="Actualizar" color="indigo" no-caps class="text-bold" @click="productsGet">
              <q-tooltip>Actualizar</q-tooltip>
            </q-btn>
          </div>
          <div class="col-12 col-md-3 q-pa-xs">
            <q-select class="bg-white" emit-value map-options dense outlined
                      v-model="category" option-value="id" option-label="name" :options="categories"
                      @update:model-value="productsGet"
            >
            </q-select>
          </div>
          <div class="col-12 col-md-3 q-pa-xs">
            <q-select class="bg-white" label="Subcategoria" dense outlined v-model="subcategory"
                      :options="subcategories" map-options emit-value
                      option-value="id" option-label="name"
                      @update:model-value="productsGet"
            />
          </div>
          <div class="col-12 col-md-3 q-pa-xs">
            <q-select class="bg-white" label="Ordenar" dense outlined v-model="order"
                      :options="orders" map-options emit-value
                      option-value="value" option-label="label"
                      @update:model-value="productsGet"
            />
          </div>
          <div class="col-12 col-md-3 q-pa-xs">
            <q-select class="bg-white" label="Agencia" dense outlined v-model="$store.agencia_id"
                      :options="agencias" map-options emit-value
                      option-value="id" option-label="nombre"
                      @update:model-value="productsGet"
                      :disable="!($store.user?.agencia_id==1)"
            />
<!--            <pre>{{$store.user?.agencia_id==1}}</pre>-->
<!--            <pre>{{$store.user}}</pre>-->
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
                        <q-badge color="red" floating style="padding: 10px 10px 5px 5px;margin: 0px" v-if="p.porcentaje">
                          {{p.porcentaje}}%
                        </q-badge>
                        <div class="absolute-bottom text-center text-subtitle2" style="padding: 0px 0px;line-height: 1;">
                          {{p.nombre}}
                        </div>
                        <q-badge v-if="p.cantidadPedida>0" color="yellow-9" floating :label="p.cantidadPedida" style="padding: 5px"/>
                      </q-img>
                      <q-card-section class="q-pa-none q-ma-none">
                        <div class="text-center text-subtitle2">
                          {{ p.precio }}
                          <span class="text-red" v-if="p.porcentaje">
                        {{$filters.precioRebajaVenta(p.precio, p.porcentaje)}}
                      </span>
                          Bs
                        </div>
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
              <div class="col-6 text-h6 q-pt-xs q-pl-lg bg-orange">Canasta Compras</div>
              <div class="col-6 text-right"><q-btn class="text-subtitle1 text-blue-10 text-bold" style="text-decoration: underline;" label="Vaciar canasta" @click="vaciarCanasta" no-caps flat outline/></div>
            </div>
          </q-card-section>
          <q-separator></q-separator>
          <q-card-section class="q-pa-none q-ma-none" >
            <div v-if="$store.productosCompra.length==0" class="flex flex-center q-pa-lg">
              <q-icon name="o_shopping_basket" color="grey" size="100px"/>
              <div class="q-pa-lg text-grey text-center noSelect">
                Aún no tienes productos en tu canasta. Haz clic sobre un producto para agregarlo.
              </div>
            </div>
            <q-scroll-area v-else style="height: 400px;">
              <q-table dense flat bordered hide-bottom hide-header :rows="$store.productosCompra" :columns="columnsProductosVenta" :rows-per-page-options="[0]">
                <template v-slot:body="props">
                  <q-tr :props="props">
                    <q-td key="borrar" :props="props" style="padding: 0px;margin: 0px" auto-width>
                      <q-btn flat dense @click="deleteProductosVenta(props.row,props.pageIndex)" icon="delete" color="red" size="10px" class="q-pa-none q-ma-none" />
                    </q-td>
                    <q-td key="nombre" :props="props">
                      <div>
                        <q-img :src="props.row.imagen.includes('http')?props.row.imagen:`${$url}../images/${props.row.imagen}`"
                               width="40px" height="40px"
                               style="padding: 0px; margin: 0px; border-radius: 0px;position: absolute;crop: auto;object-fit: cover;"
                        />
                        <div style="padding-left: 42px">
                          <div class="text-caption" style="max-width: 170px; white-space: normal; overflow-wrap: break-word;line-height: 0.9;">
                            {{props.row.nombre}}
                          </div>
                        </div>
                      </div>
                    </q-td>
                    <q-td key="cantidadVenta" :props="props">
                      <q-input dense outlined class="super-small" v-model="props.row.lote" placeholder="Lote" style="width: 170px;" hide-hint />
                      <q-input dense outlined class="super-small" type="date" v-model="props.row.fechaVencimiento" placeholder="Vencimiento" style="width: 170px;" hide-hint />
                      <q-input dense outlined class="super-small" v-model="props.row.cantidadCompra" type="number" placeholder="Cantidad" style="width: 170px;" hide-hint />
                      <q-input dense outlined class="super-small" v-model="props.row.price" type="number" step="0.01" placeholder="Precio" style="width: 170px;" hide-hint />
                      <div><b>Subtotal:</b> {{(props.row.price*props.row.cantidadCompra).toFixed(2)}} Bs</div>
                    </q-td>
                  </q-tr>
                </template>
              </q-table>
            </q-scroll-area>
          </q-card-section>
          <q-card-section >
            <q-list padding bordered dense class="rounded-borders full-width q-pa-none q-ma-none">
<!--              <q-expansion-item-->
<!--                dense-->
<!--                dense-toggle-->
<!--                expand-separator-->
<!--                label="Total"-->
<!--              >-->
<!--                <template v-slot:header>-->
                  <q-item-section>
                    <div class="row">
                      <div class="col-4 text-grey flex flex-center">Numero Factura</div>
                      <div class="col-8 text-right"><q-input dense outlined v-model="factura" placeholder="Numero Factura" style="width: 170px;" hide-hint /></div>
                      <div class="col-4 text-grey flex flex-center">Agencia</div>
                      <div class="col-8 text-right">
                        <q-select class="bg-white" dense outlined v-model="agencia_id"
                                  :options="agencias" map-options emit-value
                                  option-value="id" option-label="nombre"
                                  :disable="!($store.user?.agencia_id==1)"
                        />
                      </div>
                      <div class="col-4 text-grey flex flex-center">Proveedor</div>
                      <div class="col-8 text-right">
                        <q-select class="bg-white" dense outlined v-model="proveedor_id"
                                  :options="proveedores" map-options emit-value
                                  use-input @filter="filterFn"
                                  option-value="id" option-label="nombreRazonSocial"
                        />
<!--                        <pre>{{proveedores}}</pre>-->
                      </div>
                    </div>
                  </q-item-section>
<!--                  <q-item-section side>-->
<!--                    <q-item-section side>-->
<!--                      -->
<!--                    </q-item-section>-->
<!--                  </q-item-section>-->
<!--                </template>-->
<!--                <q-card>-->
<!--                  <q-card-section>-->
<!--                    <div class="row">-->
<!--                      <div class="col-7 text-grey">Cantidades de referencia</div>-->
<!--                      <div class="col-5 text-right">{{$store.productosCompra.length}}</div>-->
<!--                      <div class="col-7 text-grey">-->
<!--                        Ganancia-->
<!--                        <q-icon name="o_info">-->
<!--                          <q-tooltip anchor="top middle" self="bottom middle" :offset="[10, 10]">-->
<!--                            Para calcular la ganancia correctamente, deberás cargar el costo unitario de todos los productos desde tu Inventario.-->
<!--                          </q-tooltip>-->
<!--                        </q-icon>-->
<!--                      </div>-->
<!--                      <div class="col-5 text-right text-green">{{totalganancia}} Bs</div>-->
<!--                    </div>-->
<!--                  </q-card-section>-->
<!--                </q-card>-->
<!--              </q-expansion-item>-->
            </q-list>
            <q-btn @click="compraInsert" class="full-width" no-caps label="Confirmar compra" :color="$store.productosCompra.length==0?'grey':'warning'" :disable="$store.productosCompra.length==0?true:false" :loading="loading"/>
          </q-card-section>
        </q-card>
      </div>
    </div>
    <div id="myElement" class="hidden"></div>
</q-page>
</template>
<script>
// import { Imprimir } from 'src/addons/Imprimir'
import { date } from 'quasar'

export default {
  name: 'SalePage',
  data () {
    return {
      saleDialog: false,
      client: {},
      aporte: false,
      qr: false,
      documents: [],
      metodoPago: 'Efectivo',
      // textoCambio: 'Aporte',
      document: {},
      factura: '',
      agencia_id: 0,
      current_page: 1,
      last_page: 1,
      ruleNumber: [
        val => (val !== null && val !== '') || 'Por favor escriba su cantidad',
        val => (val >= 0 && val < 10000) || 'Por favor escriba una cantidad real'
      ],
      ruleString: [
        val => (val !== null && val !== '') || 'Por favor escriba su cantidad'
      ],
      search: '',
      efectivo: '',
      loading: false,
      products: [],
      totalProducts: 0,
      agencias: [],
      // agencia: 0,
      product: { cantidad: 0, nombre: '', barra: '', costo: 0, precio: 0, descripcion: '', category_id: 0 },
      category: 0,
      subcategory: 0,
      categories: [
        { name: 'Ver todas las categorias', id: 0 }
      ],
      subcategories: [
        { name: 'Ver todas las sub categorias', id: 0 }
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
      ],
      proveedores: [],
      proveedoresAll: [],
      proveedor_id: 0
    }
  },
  created () {
    this.agencia_id = this.$store.agencia_id
    // this.$store.agencia_id = 0
  },
  mounted () {
    this.proveedoresGet()
    this.productsGet()
    this.categoriesGet()
    this.subcategoriesGet()
    this.agenciasGet()
    this.$axios.get('documents').then(res => {
      // console.log(this.documents)
      res.data.forEach(r => {
        r.label = r.descripcion
      })
      this.documents = res.data
      this.document = this.documents[0]
    })
  },
  methods: {
    subcategoriesGet () {
      this.subcategories = [{ name: 'Ver todas las sub categorias', id: 0 }]
      this.$axios.get('subcategories').then(response => {
        this.subcategories = this.subcategories.concat(response.data)
      }).catch(error => {
        console.log(error)
      })
    },
    filterFn (val, update, abort) {
      // console.log(val)
      // console.log(this.proveedoresAll)
      if (val === '') {
        update(() => {
          this.proveedores = this.proveedoresAll
        })
        return
      }
      const needle = val.toLowerCase()
      update(() => {
        this.proveedores = this.proveedoresAll.filter(v => v.nombreRazonSocial.toLowerCase().indexOf(needle) > -1)
      })
    },
    proveedoresGet () {
      this.$axios.get('providers').then(res => {
        this.proveedores = res.data
        this.proveedoresAll = res.data
      })
    },
    deleteProductosVenta (row, index) {
      this.$store.productosCompra.splice(index, 1)
    },
    async compraInsert () {
      // Verificar que todos tengan lote, fecha de vencimiento y cantidad
      for (const p of this.$store.productosCompra) {
        if (
          p.lote === '' || p.fechaVencimiento === '' || p.cantidadCompra === '' || p.price === '' ||
          p.lote === null || p.fechaVencimiento === null || p.cantidadCompra === null || p.price === null
        ) {
          this.$alert.error('Debes ingresar el lote, la fecha de vencimiento y la cantidad de compra para todos los productos.')
          return false
        }
      }

      // Mostrar diálogo de confirmación
      await this.$q.dialog({
        title: 'Confirmar compra',
        message: `¿Estás seguro de confirmar la compra a <span style="color: red"> <b> ${this.agencias.find(a => a.id === this.agencia_id).nombre}</b></span> con <span style="color: red"> <b>${this.$store.productosCompra.length}</b></span> productos?`,
        html: true,
        cancel: true,
        persistent: true
      }).onOk(async () => {
        try {
          // Realizar la compra
          this.loading = true
          await this.$axios.post('compraInsert', {
            buys: this.$store.productosCompra,
            factura: this.factura,
            agencia_id: this.agencia_id,
            proveedor_id: this.proveedor_id
          })
          this.$alert.success('Compra realizada con éxito')
          this.$store.productosCompra = []
          this.loading = false
          this.productsGet()
          this.factura = ''
          this.agencia_id = 0
        } catch (err) {
          this.$alert.error(err.response.data.message)
          this.loading = false
        }
      })
    },
    async vaciarCanasta () {
      await this.$store.productosCompra.forEach(p => {
        p.cantidad = p.cantidadReal
        p.cantidadVenta = 0
        p.cantidadPedida = 0
      })
      this.$store.productosCompra = []
    },
    clickAddSale (product) {
      // product.cantidad--
      product.cantidadPedida++
      // const productVenta = this.$store.productosCompra.find(p => p.id === product.id)
      // if (productVenta) {
      //   productVenta.cantidadVenta++
      // } else {
      //   product.cantidadVenta = 1
      // this.$store.productosCompra.push(product)
      // }
      this.$store.productosCompra.push({
        id: product.id,
        nombre: product.nombre,
        imagen: product.imagen,
        lote: '',
        fechaVencimiento: date.formatDate(new Date(), 'YYYY-MM-DD'),
        cantidadCompra: ''
      })
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
      // this.$axios.get(`productsSale?page=${this.current_page}&search=${this.search}&order=${this.order}&category=${this.category}&agencia=${this.$store.agencia_id}`).then(res => {
      this.$axios.get('productsSale', {
        params: {
          page: this.current_page,
          search: this.search,
          order: this.order,
          category: this.category,
          subcategory: this.subcategory,
          agencia: this.$store.agencia_id
        }
      }).then(res => {
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
    cambio () {
      if (this.aporte === false) {
        const cambio = parseFloat(this.efectivo === '' ? 0 : this.efectivo) - parseFloat(this.total)
        return Math.round(cambio * 100) / 100
      } else {
        const cambio = parseFloat(this.efectivo === '' ? 0 : this.efectivo) - parseFloat(this.total)
        const entero = Math.floor(cambio)
        const decimal = cambio - entero
        return cambio - decimal
      }
    },
    textoCambio () {
      if (this.aporte === false) {
        return this.cambio < 0 ? 'Aporte' : 'Cambio'
      } else {
        const cambio = parseFloat(this.efectivo === '' ? 0 : this.efectivo) - parseFloat(this.total)
        const entero = Math.floor(cambio)
        const decimal = cambio - entero
        return this.cambio < 0 ? 'Aporte' : 'Bs.' + decimal.toFixed(2)
      }
    },
    cambioDecimal () {
      if (this.aporte === false) {
        return this.cambio < 0 ? 0 : 0
      } else {
        const cambio = parseFloat(this.efectivo === '' ? 0 : this.efectivo) - parseFloat(this.total)
        const entero = Math.floor(cambio)
        const decimal = cambio - entero
        return this.cambio < 0 ? 0 : decimal.toFixed(2)
      }
    },
    total () {
      let s = 0
      this.$store.productosCompra.forEach(p => {
        s = s + parseFloat(p.precioVenta * p.cantidadVenta)
      })
      return s.toFixed(2)
    }
  }
}
</script>
<style lang="scss">
.super-small.q-field--dense {
  .q-field__control-container,
  .q-field__native {
    //padding-top: 10px !important;
  }

  .q-field__control {
    height: 25px !important;
    min-height: 25px !important;
  }

  .q-field__marginal {
    height: 25px !important;
  }

  .q-field__label {
    top: 6px !important;
  }
}
</style>

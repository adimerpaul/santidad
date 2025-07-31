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
            <q-select class="bg-white" emit-value map-options dense outlined
                      v-model="subcategoria" option-value="id" option-label="name" :options="subcategories"
                      @update:model-value="productsGet"
                      label="Subcategoria"
            >
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
            <q-select class="bg-white" label="Agencia" dense outlined v-model="agencia_id"
                      :options="agencias" map-options emit-value
                      option-value="id" option-label="nombre"
                      @update:model-value="productsGet"
                      :disable="!($store.user.id=='1')"
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
                        <q-badge color="red" floating style="padding: 10px 10px 5px 5px;margin: 0px" v-if="p.porcentaje">
                          {{p.porcentaje}}%
                        </q-badge>
                        <div class="absolute-bottom text-center text-subtitle2" style="padding: 0px 0px;line-height: 1;">
                          {{$filters.capitalize(p.nombre)}}
                        </div>
                        <q-badge v-if="p.cantidadPedida>0" color="yellow-9" floating :label="p.cantidadPedida" style="padding: 5px"/>
                      </q-img>
                      <q-card-section class="q-pa-none q-ma-none">
                        <div class="text-center text-subtitle2">
                          {{ p.precio }}
                          <span class="text-red" v-if="p.porcentaje">
                            {{$filters.precioRebajaVenta(p.precio, p.porcentaje)}}
                            ({{ (p.precio - $filters.precioRebajaVenta(p.precio, p.porcentaje)).toFixed(2) }} Bs)
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
              <div class="col-6 text-h6 q-pt-xs q-pl-lg">
                Canasta
                ({{ $store.productosVenta.length }})
              </div>
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
                          <div class="text-caption" style="max-width: 120px; white-space: normal; overflow-wrap: break-word;line-height: 0.9;">
                            {{props.row.nombre}}
                          </div>
                          <div class="text-grey">Dis: {{props.row.cantidad}}
                            (
                            <span style="font-size: 10px">{{props.row.precio}} Bs </span>
                            <span style="font-size: 10px" class="text-red text-bold" v-if="props.row.porcentaje">{{$filters.precioRebajaVenta(props.row.precio, props.row.porcentaje)}} Bs</span>
                            )
                          </div>
                    <q-input
                    v-model="props.row.precioVenta"
                    style="width: 120px"
                    step="0.1"
                    type="number"
                    dense
                    outlined
                    readonly
                  >
                    <template v-slot:prepend>
                      <q-icon name="edit" size="xs" />
                      <div style="font-size: 10px">Bs.</div>
                    </template>
                  </q-input>
                        </div>
                      </div>
                    </q-td>
                    <q-td key="cantidadVenta" :props="props">
                      <q-input dense outlined bottom-slots min="1"  v-model="props.row.cantidadVenta" @update:model-value="cambioNumero(props.row,props.pageIndex)" :rules="ruleNumber" type="number" input-class="text-center" required>
                        <template v-slot:prepend>
                          <q-btn style="cursor: pointer" dense flat icon="remove_circle_outline" @click="removeCantidad(props.row,props.pageIndex)"/>
                        </template>
                        <template v-slot:append>
                          <q-btn style="cursor: pointer" dense flat icon="add_circle_outline" @click="addCantidad(props.row,props.pageIndex)"/>
                        </template>
                      </q-input>
                      <pre>{{props.row.cantidadPedida}}</pre>
<!--                      <input type="number" min="1" v-model="props.row.cantidadVenta" @change="cambioNumero(props.row,props.pageIndex)" style="width: 70px; text-align: center; padding: 0px; margin: 0px; border-radius: 0px; border: 1px solid #ccc;" class="q-pa-xs" required>-->
                      <div class="text-grey">= Bs {{redondeo(props.row.cantidadVenta*props.row.precioVenta)}}</div>
                    </q-td>
                    <q-td key="lotes" :props="props">
                      <div v-for="(lote, index) in props.row.buys" :key="index" class="q-mb-xs">
                        <q-badge color="blue-10" text-color="white" class="q-mr-xs">{{ lote.lote }}</q-badge>
                        <span class="text-grey-8" style="font-size: 12px;">
                          {{ lote.cantidadVendida }}u | {{ lote.price }} Bs <br>
                          {{ lote.dateExpiry }} <input type="number" min="1" v-model="lote.cantidadAVender" style="width: 50px; text-align: center; padding: 0px; margin: 0px; border-radius: 0px; border: 1px solid #ccc;" class="q-pa-xs" required>
                        </span>
                      </div>
<!--                      <pre>-->
<!--                        {{props.row.buys}}-->
<!--                      </pre>-->
<!--                      [-->
<!--                      {-->
<!--                      "id": 36644,-->
<!--                      "user_id": 25,-->
<!--                      "product_id": 33,-->
<!--                      "agencia_id": 3,-->
<!--                      "lote": "CN004942",-->
<!--                      "quantity": 60,-->
<!--                      "total": "876.00",-->
<!--                      "price": "14.60",-->
<!--                      "dateExpiry": "2025-11-23",-->
<!--                      "date": "2024-11-06",-->
<!--                      "time": "20:19:00",-->
<!--                      "factura": "30345",-->
<!--                      "proveedor_id": 15026,-->
<!--                      "user_baja_id": null,-->
<!--                      "cantidadBaja": null,-->
<!--                      "cantidadVendida": 60,-->
<!--                      "sucursal_id_baja": null,-->
<!--                      "description_baja": null,-->
<!--                      "fecha_baja": null,-->
<!--                      "diasPorVencer": 172-->
<!--                      }-->
<!--                      ]-->

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
                        Descuentos
                        <q-icon name="o_info">
                          <q-tooltip anchor="top middle" self="bottom middle" :offset="[10, 10]">
<!--                            Para calcular la ganancia correctamente, deberás cargar el costo unitario de todos los productos desde tu Inventario.-->
                            Si realizas un descuento, este se restará del total de la venta.
                          </q-tooltip>
                        </q-icon>
                      </div>
                      <div class="col-5 text-right text-green">{{totalganancia}} Bs</div>
                      <div class="col-7 text-grey">
                        Monto Sin descuento
                        <q-icon name="o_info">
                          <q-tooltip anchor="top middle" self="bottom middle" :offset="[10, 10]">
                            Para calcular la ganancia correctamente, deberás cargar el costo unitario de todos los productos desde tu Inventario.
                          </q-tooltip>
                        </q-icon>
                      </div>
                      <div class="col-5 text-right text-green">
                        {{parseFloat(total) + parseFloat(totalganancia)}} Bs
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </q-expansion-item>
            </q-list>
            <q-btn @click="clickSale" class="full-width" no-caps label="Confirmar venta" :color="$store.productosVenta.length==0?'grey':'warning'" :disable="$store.productosVenta.length==0?true:false" :loading="loading"/>
          </q-card-section>
        </q-card>
      </div>
    </div>
    <q-dialog v-model="saleDialog" persistent>
      <q-card style="width: 850px; max-width: 90vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Realizar venta</div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-form @submit.prevent="saleInsert">
          <q-card-section>
            <div class="row">
              <div class="col-6 col-md-3">
                <q-input outlined dense label="NIT/CARNET" @keyup="searchClient" required v-model="client.numeroDocumento"   />
              </div>
              <div class="col-6 col-md-3">
                <q-input outlined dense label="Complemento"  @keyup="searchClient" v-model="client.complemento" style="text-transform: uppercase"/>
              </div>
              <div class="col-12 col-md-6">
                <q-input outlined dense label="Nombre Razon Social" required v-model="client.nombreRazonSocial" style="text-transform: uppercase" />
              </div>
              <div class="col-12 col-md-6">
                <q-select v-model="document" outlined dense :options="documents" />
              </div>
              <div class="col-12 col-md-6">
                <q-input outlined dense label="Email"  v-model="client.email" type="email" />
              </div>
            </div>
          </q-card-section>
          <q-separator/>
          <q-card-section>
            <div class="row">
              <div class="col-6 col-md-2">
                <q-input outlined dense label="TOTAL A PAGAR:" readonly v-model="total" :rules="ruleNumber"/>
              </div>
              <div class="col-6 col-md-2">
                <q-input outlined dense label="EFECTIVO BS."  v-model="efectivo" type="number" step="0.01" :rules="ruleNumber"/>
              </div>
              <div class="col-6 col-md-2">
                <q-input outlined dense label="Aporte"  v-model="aporte" type="number" step="0.01" :rules="ruleNumber"/>
              </div>
              <div class="col-6 col-md-2">
                <q-input outlined dense label="Descuento"  v-model="descuento" type="number" step="0.01" :rules="ruleNumber"/>
              </div>
<!--              <div class="col-6 col-md-2">-->
<!--                <q-checkbox v-model="aporte" :label="textoCambio"-->
<!--                            :class="`bg-${parseFloat(efectivo)> parseFloat(total)?'green':'red'} text-white full-width bi-border-all`"-->
<!--                            :disable="parseFloat(efectivo)> parseFloat(total)?false:true">-->
<!--                  <q-tooltip anchor="top middle" self="bottom middle" :offset="[10, 10]">-->
<!--                    Si el cliente paga con un monto mayor al total, se registrará el cambio como un aporte.-->
<!--                  </q-tooltip>-->
<!--                </q-checkbox>-->
<!--              </div>-->
              <div class="col-6 col-md-2">
                <q-input outlined dense label="CAMBIO:" readonly v-model="cambio" bg-color="red" label-color="white"/>
              </div>
              <div class="col-6 col-md-2">
                <q-select dense outlined v-model="metodoPago" label="Metodo de pago"
                          :options="$metodoPago" hint="Metodo de pago del gasto" />
              </div>
            </div>
          </q-card-section>
          <q-separator/>
          <q-card-section>
            <div class="row">
              <div class="col-6">
                <q-btn type="submit" class="full-width" icon="o_add_circle" label="Realizar venta" :loading="loading" no-caps color="green"  />
              </div>
              <div class="col-6">
                <q-btn class="full-width" icon="undo" v-close-popup label="Atras" no-caps color="red" :loading="loading" />
              </div>
            </div>
          </q-card-section>
        </q-form>
      </q-card>
    </q-dialog>
    <div id="myElement" class="hidden"></div>
<!--    Lorem ipsum dolor sit amet, consectetur adipisicing elit. Alias blanditiis, culpa dolorem ea eveniet fugiat fugit, illum ipsa placeat quas repudiandae sunt? Asperiores culpa eum inventore nam odio rem, vero.-->
<!--    <pre>-->
<!--      {{$store.env}}-->
<!--    </pre>-->
</q-page>
</template>
<script>
import { Imprimir } from 'src/addons/Imprimir'

export default {
  name: 'SalePage',
  data () {
    return {
      agencia_id: parseInt(localStorage.getItem('agencia_id')),
      saleDialog: false,
      client: {},
      aporte: 0,
      descuento: 0,
      qr: false,
      documents: [],
      metodoPago: 'Efectivo',
      document: {},
      current_page: 1,
      last_page: 1,
      ruleNumber: [
        val => (val !== null && val !== '') || 'Por favor escriba su cantidad',
        val => (val >= 0 && val < 10000) || 'Por favor escriba una cantidad real'
      ],
      search: '',
      efectivo: '',
      loading: false,
      products: [],
      totalProducts: 0,
      agencias: [],
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
      order: 'cantidad desc',
      columnsProductosVenta: [
        { label: 'borrar', field: 'borrar', name: 'borrar', align: 'left' },
        { label: 'nombre', field: 'nombre', name: 'nombre', align: 'left' },
        { label: 'cantidadVenta', field: 'cantidadVenta', name: 'cantidadVenta' }
        // { label: 'lotes', field: 'lotes', name: 'lotes', align: 'left' }
      ],
      orders: [
        { label: 'Ordenar por', value: 'id' },
        { label: 'Menor precio', value: 'precio asc' },
        { label: 'Mayor precio', value: 'precio desc' },
        { label: 'Menor cantidad', value: 'cantidad asc' },
        { label: 'Mayor cantidad', value: 'cantidad desc' },
        { label: 'Orden alfabetico', value: 'nombre asc' }
      ],
      subcategories: [],
      subcategoria: ''
    }
  },
  mounted () {
    this.productsGet()
    this.categoriesGet()
    this.agenciasGet()
    this.subcategoriesGet()
    this.$axios.get('documents').then(res => {
      res.data.forEach(r => {
        r.label = r.descripcion
      })
      this.documents = res.data
      this.document = this.documents[0]
    })
  },
  methods: {
    subcategoriesGet () {
      this.$axios.get('subcategories').then(response => {
        this.subcategories = response.data
      }).catch(error => {
        console.log(error)
      })
    },
    saleInsert () {
      this.loading = true
      this.client.codigoTipoDocumentoIdentidad = this.document.codigoClasificador
      this.$store.productosVenta.forEach(p => {
        p.subTotal = p.cantidadPedida * p.precioVenta
      })
      const data = {
        montoTotal: this.total,
        client: this.client,
        aporte: this.aporte,
        descuento: this.descuento,
        qr: this.qr,
        efectivo: this.efectivo,
        products: this.$store.productosVenta,
        metodoPago: this.metodoPago,
        agencia_id: this.agencia_id
      }
      this.$axios.post('sales', data).then(res => {
        this.loading = false
        this.$alert.success('Venta realizada con exito')
        this.saleDialog = false
        this.$store.productosVenta = []
        this.client = {}
        this.aporte = false
        this.qr = false
        this.efectivo = ''
        this.products.forEach(p => {
          p.cantidadPedida = 0
        })
        this.totalProducts = 0
        Imprimir.nota(res.data).then(r => {
        })
      }).catch(err => {
        this.loading = false
        this.$alert.error(err.response.data.message)
      })
    },
    clientSearch () {
      this.$axios.post('searchClient', this.client).then(res => {
        if (res.data.nombreRazonSocial !== undefined) {
          this.client.nombreRazonSocial = res.data.nombreRazonSocial
          this.client.email = res.data.email
          this.client.id = res.data.id
          const documento = this.documents.find(r => r.codigoClasificador === res.data.codigoTipoDocumentoIdentidad)
          documento.label = documento.descripcion
          this.document = documento
        }
      })
    },
    searchClient () {
      this.document = this.documents[0]
      this.client.nombreRazonSocial = ''
      this.client.complemento = ''
      this.client.email = ''
      this.client.id = undefined
      if (this.client.numeroDocumento === '0') {
        this.clientSearch()
      } else if (this.client.numeroDocumento.length >= 5) {
        this.clientSearch()
      }
    },
    clickSale () {
      let hayProblema = false
      this.$store.productosVenta.forEach(p => {
        const product = this.products.find(pr => pr.id === p.id)
        if (product) {
          if ((p.cantidadVenta) > product.cantidadReal) {
            this.$alert.error(`No hay suficiente stock de ${product.nombre}`)
            hayProblema = true
            return false
          }
        }
        if (!p.precioVenta || p.precioVenta <= 0) {
          this.$alert.error(`El precio de venta de "${p.nombre}" debe ser mayor a 0.`)
          hayProblema = true
          return false
        }
      })

      if (hayProblema) {
        return false
      }
      this.aporte = 0
      this.descuento = 0
      this.saleDialog = true
      // this.aporte = false
      this.efectivo = 0
      this.qr = false
      this.client = {
        numeroDocumento: '0',
        nombreRazonSocial: 'SN',
        email: '',
        complemento: ''
      }
      this.metodoPago = 'Efectivo'
    },
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
    // clickAddSale (product) {
    //   product.cantidad--
    //   product.cantidadPedida++
    //   // si tiene procentaje colocar el precio mas la rebaja
    //   if (product.porcentaje) {
    //     product.precioVenta = this.$filters.precioRebajaVenta(product.precio, product.porcentaje)
    //   }
    //   const productVenta = this.$store.productosVenta.find(p => p.id === product.id)
    //   if (productVenta) {
    //     productVenta.cantidadVenta++
    //   } else {
    //     product.cantidadVenta = 1
    //     this.$store.productosVenta.push(product)
    //   }
    // },
    async clickAddSale (product) {
      try {
        this.loading = true
        const res = await this.$axios.get(`productos/${product.id}/stock`)
        this.loading = false
        const stockDisponible = res.data.stock

        if (stockDisponible <= 0 || product.cantidad <= 0) {
          this.$q.notify({
            color: 'negative',
            message: 'Producto sin stock disponible',
            icon: 'error',
            position: 'top'
          })
          return
        }

        // ↓ Solo si hay stock, continúa
        product.cantidad--
        // product.cantidadPedida = (product.cantidadPedida || 0) + 1

        if (product.porcentaje) {
          product.precioVenta = this.$filters.precioRebajaVenta(product.precio, product.porcentaje)
        }

        const productVenta = this.$store.productosVenta.find(p => p.id === product.id)
        if (productVenta) {
          productVenta.cantidadVenta++
          productVenta.cantidadPedida++
        } else {
          product.cantidadVenta = 1
          product.cantidadPedida = 1
          this.$store.productosVenta.push(product)
        }
      } catch (error) {
        this.loading = false
        this.$q.notify({
          color: 'negative',
          message: 'Error al verificar stock',
          icon: 'warning'
        })
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
      this.$axios.get(`productsSale?page=${this.current_page}&search=${this.search}&order=${this.order}&category=${this.category}&agencia=${this.agencia_id}&subcategory=${this.subcategoria}`).then(res => {
        this.loading = false
        this.totalProducts = res.data.products.total
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
    totalganancia () {
      let s = 0
      this.$store.productosVenta.forEach(p => {
        s = s + (p.precio - this.$filters.precioRebajaVenta(p.precio, p.porcentaje)) * p.cantidadVenta
      })
      return s.toFixed(2)
    },
    cambio () {
      // consciderara el aporte
      // if (this.aporte === false) {
      //   const cambio = parseFloat(this.efectivo === '' ? 0 : this.efectivo) - parseFloat(this.total)
      //   return Math.round(cambio * 100) / 100
      // } else {
      //   const cambio = parseFloat(this.efectivo === '' ? 0 : this.efectivo) - parseFloat(this.total)
      //   const entero = Math.floor(cambio)
      //   const decimal = cambio - entero
      //   return cambio - decimal
      // }
      const efectivo = parseFloat(this.efectivo === '' ? 0 : this.efectivo)
      const aporte = parseFloat(this.aporte === '' ? 0 : this.aporte)
      const descuento = parseFloat(this.descuento === '' ? 0 : this.descuento)
      const cambio = (efectivo - aporte) - parseFloat(this.total) + descuento
      // if (cambio < 0) {
      //   return 0
      // } else {
      //   return cambio.toFixed(2)
      // }
      return Math.round(cambio * 100) / 100
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
      this.$store.productosVenta.forEach(p => {
        s = s + parseFloat(p.precioVenta * p.cantidadVenta)
      })
      return s.toFixed(2)
    }
  }
}
</script>

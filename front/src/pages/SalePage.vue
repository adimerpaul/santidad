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
             <q-btn
              class="q-ml-sm"
              icon="cloud_download"
              dense
              label="Pedido Online"
              color="teal"
              no-caps
              :loading="loadingPedidoOnline"
              @click="cargarPedidoOnline"
            >
              <q-tooltip>Cargar al carrito desde un número de pedido</q-tooltip>
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
                  <q-card @click="clickAddSale(p)" class="q-pa-xs" flat bordered
                          :class="getProductCardClass(p)">
                    <q-img
                      :src="p.imagen.includes('http')?p.imagen:`${$url}../images/${p.imagen}`"
                      width="100%"
                      height="100px"
                      fit="contain"
                    >
                        <q-badge color="red" floating style="padding: 10px 10px 5px 5px;margin: 0px" v-if="p.porcentaje">
                          {{p.porcentaje}}%
                        </q-badge>
                        <div class="absolute-bottom text-center text-subtitle2" style="padding: 0px 0px;line-height: 1;">
                          {{$filters.capitalize(p.nombre)}}
                        </div>
                        <q-badge v-if="p.cantidadPedida>0" color="yellow-9" floating :label="p.cantidadPedida" style="padding: 5px"/>

                        <!-- Badge de sin stock -->
                        <q-badge v-if="p.cantidadReal <= 0" color="red" floating style="padding: 5px; margin: 5px">
                          SIN STOCK
                        </q-badge>
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
                        <div :class="getStockTextClass(p)">
                          {{ p.cantidadReal }} {{ $q.screen.lt.md?'Dis':'Disponible' }}
                        </div>
                        <div v-if="$store.user?.agencia_id == 1 && p.cantidadAlmacen !== undefined"
                          class="text-center text-caption text-lead">
                        Stock Almacén: {{ p.cantidadAlmacen }}
                      </div>
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
                          <div class="text-grey">Stock Real: {{props.row.cantidadReal}}
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
                      <q-input dense outlined bottom-slots min="1"
                               v-model="props.row.cantidadVenta"
                               @update:model-value="cambioNumero(props.row,props.pageIndex)"
                               :rules="ruleNumber"
                               type="number"
                               input-class="text-center"
                               required>
                        <template v-slot:prepend>
                          <q-btn style="cursor: pointer" dense flat icon="remove_circle_outline" @click="removeCantidad(props.row,props.pageIndex)"/>
                        </template>
                        <template v-slot:append>
                          <q-btn style="cursor: pointer" dense flat icon="add_circle_outline" @click="addCantidad(props.row,props.pageIndex)"/>
                        </template>
                      </q-input>
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
                    <div class="text-right text-grey-8 text-bold"> <u> Bs {{totalConDescuentoSistema}}</u></div>
                  </q-item-section>
                </template>
                <q-card>
                  <q-card-section>
                    <div class="row">
                      <div class="col-7 text-grey">Cantidades de referencia</div>
                      <div class="col-5 text-right">{{$store.productosVenta.length}}</div>
                      <div class="col-7 text-grey">
                        Descuentos del sistema
                        <q-icon name="o_info">
                          <q-tooltip anchor="top middle" self="bottom middle" :offset="[10, 10]">
                            Descuentos aplicados automáticamente por porcentajes en productos
                          </q-tooltip>
                        </q-icon>
                      </div>
                      <div class="col-5 text-right text-green">{{totalDescuentoSistema}} Bs</div>
                      <div class="col-7 text-grey">
                        Monto Sin descuentos
                        <q-icon name="o_info">
                          <q-tooltip anchor="top middle" self="bottom middle" :offset="[10, 10]">
                            Total sin ningún descuento aplicado
                          </q-tooltip>
                        </q-icon>
                      </div>
                      <div class="col-5 text-right text-green">
                        {{totalSinDescuentos}} Bs
                      </div>
                    </div>
                  </q-card-section>
                </q-card>
              </q-expansion-item>
            </q-list>
            <q-btn @click="clickSale" class="full-width" no-caps label="Confirmar venta"
                   :color="$store.productosVenta.length==0?'grey':'warning'"
                   :disable="$store.productosVenta.length==0"
                   :loading="loading"/>

            <!-- ✅ Alerta de productos que sobrepasaron stock -->
            <div v-if="productosSobrepasaronStock.length > 0" class="q-mt-sm">
              <q-banner dense class="bg-red-1 text-red-9">
                <template v-slot:avatar>
                  <q-icon name="warning" color="red" />
                </template>
                <div class="text-caption text-bold">Productos que exceden el stock disponible:</div>
                <div v-for="producto in productosSobrepasaronStock" :key="producto.nombre" class="text-caption">
                  • {{ producto.nombre }}: Solicitado {{ producto.cantidadSolicitada }}, Disponible {{ producto.stockDisponible }}
                </div>
                <div class="text-caption text-bold q-mt-xs">Por favor ajuste las cantidades antes de confirmar la venta.</div>
              </q-banner>
            </div>
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
                <q-input outlined dense label="TOTAL A PAGAR:" readonly v-model="totalFinal" :rules="ruleNumber"/>
              </div>
              <div class="col-6 col-md-2">
                <q-input outlined dense label="EFECTIVO BS." v-model="efectivo" type="number" step="0.01" :rules="ruleNumber"
                         @update:model-value="calcularCambio"/>
              </div>
              <div class="col-6 col-md-2">
                <q-input outlined dense label="Aporte" v-model="aporte" type="number" step="0.01" :rules="ruleNumber"
                         @update:model-value="calcularCambio"/>
              </div>

              <!-- Campo de descuento manual -->
              <div class="col-6 col-md-2">
                <q-input
                  outlined
                  dense
                  label="Descuento (Bs.)"
                  v-model="descuento"
                  type="number"
                  step="0.01"
                  :rules="ruleNumber"
                  @update:model-value="actualizarDesdeMonto"
                >
                  <template v-slot:append>
                    <q-icon name="attach_money" class="cursor-pointer">
                      <q-tooltip>Descuento adicional en monto fijo</q-tooltip>
                    </q-icon>
                  </template>
                </q-input>
              </div>

              <!-- Campo de descuento porcentual -->
              <div class="col-6 col-md-2">
                <q-input
                  outlined
                  dense
                  label="Descuento (%)"
                  v-model="descuentoPorcentaje"
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  :rules="rulePorcentaje"
                  @update:model-value="actualizarDesdePorcentaje"
                >
                  <template v-slot:append>
                    <q-icon name="percent" class="cursor-pointer">
                      <q-tooltip>Descuento adicional en porcentaje</q-tooltip>
                    </q-icon>
                  </template>
                  <template v-slot:hint>
                    <div class="text-caption text-grey">
                      {{ calcularMontoDesdePorcentaje() }} Bs.
                    </div>
                  </template>
                </q-input>
              </div>

              <!-- CAMPO DE CAMBIO CORREGIDO -->
              <div class="col-6 col-md-2">
                <q-input outlined dense label="CAMBIO:" readonly v-model="cambioCalculado"
                         :bg-color="cambioCalculado < 0 ? 'red' : 'green'"
                         label-color="white"/>
              </div>
              <div class="col-6 col-md-2">
                <q-select dense outlined v-model="metodoPago" label="Metodo de pago"
                          :options="$metodoPago" hint="Metodo de pago del gasto" />
              </div>
            </div>

            <!-- Información del descuento aplicado -->
            <div class="row q-mt-sm" v-if="descuento > 0">
              <div class="col-12">
                <q-banner dense class="bg-blue-1 text-blue-9">
                  <template v-slot:avatar>
                    <q-icon name="discount" color="blue" />
                  </template>
                  <div class="text-caption">
                    <strong>Descuento adicional aplicado:</strong> {{ descuento }} Bs.
                    <span v-if="descuentoPorcentaje > 0">({{ descuentoPorcentaje }}% del total sin descuentos)</span>
                  </div>
                  <div class="text-caption">
                    <strong>Total sin descuentos:</strong> {{ totalSinDescuentos }} Bs.<br>
                    <strong>Descuento del sistema:</strong> {{ totalDescuentoSistema }} Bs.<br>
                    <strong>Total con descuento del sistema:</strong> {{ totalConDescuentoSistema }} Bs.<br>
                    <strong>Total final con descuento adicional:</strong> {{ totalFinal }} Bs.
                  </div>
                </q-banner>
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
      descuentoPorcentaje: 0,
      qr: false,
      documents: [],
      metodoPago: 'Efectivo',
      document: {},
      current_page: 1,
      last_page: 1,
      loadingPedidoOnline: false,
      ruleNumber: [
        val => (val !== null && val !== '') || 'Por favor escriba su cantidad',
        val => (val >= 0 && val < 10000) || 'Por favor escriba una cantidad real'
      ],
      rulePorcentaje: [
        val => (val !== null && val !== '') || 'Por favor escriba el porcentaje',
        val => (val >= 0 && val <= 100) || 'El porcentaje debe estar entre 0 y 100'
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
  computed: {
    // ✅ PRODUCTOS QUE SOBREPASARON STOCK (se muestra en tiempo real)
    productosSobrepasaronStock () {
      const productos = []

      this.$store.productosVenta.forEach(product => {
        const stockDisponible = product.cantidadReal
        if (product.cantidadVenta > stockDisponible) {
          productos.push({
            nombre: product.nombre,
            cantidadSolicitada: product.cantidadVenta,
            stockDisponible: stockDisponible
          })
        }
      })

      return productos
    },

    // Total SIN NINGÚN descuento (precio original * cantidad)
    totalSinDescuentos () {
      let s = 0
      this.$store.productosVenta.forEach(p => {
        s = s + parseFloat(p.precio * p.cantidadVenta)
      })
      return s.toFixed(2)
    },

    // Descuento aplicado por el sistema (diferencia entre precio original y precio con descuento)
    totalDescuentoSistema () {
      let s = 0
      this.$store.productosVenta.forEach(p => {
        const precioOriginal = parseFloat(p.precio)
        const precioConDescuento = parseFloat(p.precioVenta)
        s = s + ((precioOriginal - precioConDescuento) * p.cantidadVenta)
      })
      return s.toFixed(2)
    },

    // Total CON descuento del sistema (lo que ya venías usando)
    totalConDescuentoSistema () {
      let s = 0
      this.$store.productosVenta.forEach(p => {
        s = s + parseFloat(p.precioVenta * p.cantidadVenta)
      })
      return s.toFixed(2)
    },

    // Total FINAL con descuento adicional aplicado
    totalFinal () {
      const totalConSistema = parseFloat(this.totalConDescuentoSistema)
      const descAdicional = parseFloat(this.descuento)
      return (totalConSistema - descAdicional).toFixed(2)
    },

    // CAMBIO CALCULADO CORREGIDO
    cambioCalculado () {
      const efectivo = parseFloat(this.efectivo || 0)
      const aporte = parseFloat(this.aporte || 0)
      const totalFinal = parseFloat(this.totalFinal || 0)

      // Fórmula corregida: (Efectivo - Aporte) - Total Final
      const cambio = (efectivo - aporte) - totalFinal

      return Math.round(cambio * 100) / 100
    },

    totalganancia () {
      let s = 0
      this.$store.productosVenta.forEach(p => {
        s = s + (p.precio - this.$filters.precioRebajaVenta(p.precio, p.porcentaje)) * p.cantidadVenta
      })
      return s.toFixed(2)
    },

    // Mantener compatibilidad con código existente
    total () {
      return this.totalConDescuentoSistema
    }
  },
  methods: {
    // ✅ VERIFICAR STOCK AL CONFIRMAR VENTA
    verificarStockCanasta () {
      const productosSinStock = []

      this.$store.productosVenta.forEach(product => {
        const stockDisponible = product.cantidadReal
        if (product.cantidadVenta > stockDisponible) {
          productosSinStock.push({
            nombre: product.nombre,
            cantidadSolicitada: product.cantidadVenta,
            stockDisponible: stockDisponible
          })
        }
      })

      return productosSinStock
    },

    // Clases dinámicas para productos
    getProductCardClass (product) {
      if (product.cantidadReal <= 0) {
        return 'bg-grey-3 cursor-not-allowed'
      }
      return 'bg-white cursor-pointer'
    },

    getStockTextClass (product) {
      if (product.cantidadReal <= 0) {
        return 'text-center text-bold text-red'
      } else if (product.cantidadReal <= 5) {
        return 'text-center text-bold text-orange'
      }
      return 'text-center text-bold'
    },

    // Método para calcular el cambio
    calcularCambio () {
      this.$forceUpdate()
    },

    // Actualizar descuento desde monto fijo
    actualizarDesdeMonto (nuevoMonto) {
      if (nuevoMonto === '' || nuevoMonto === null) {
        this.descuento = 0
        this.descuentoPorcentaje = 0
      } else {
        this.descuento = parseFloat(nuevoMonto)

        // Calcular el porcentaje equivalente sobre el total SIN descuentos
        if (this.totalSinDescuentos > 0) {
          this.descuentoPorcentaje = parseFloat(((this.descuento / this.totalSinDescuentos) * 100).toFixed(2))
        } else {
          this.descuentoPorcentaje = 0
        }
      }
      this.calcularCambio()
    },

    // Actualizar descuento desde porcentaje
    actualizarDesdePorcentaje (nuevoPorcentaje) {
      if (nuevoPorcentaje === '' || nuevoPorcentaje === null) {
        this.descuentoPorcentaje = 0
        this.descuento = 0
      } else {
        this.descuentoPorcentaje = parseFloat(nuevoPorcentaje)

        // Calcular el monto equivalente sobre el total SIN descuentos
        this.descuento = parseFloat((this.totalSinDescuentos * (this.descuentoPorcentaje / 100)).toFixed(2))
      }
      this.calcularCambio()
    },

    // Calcular monto desde porcentaje (para el hint)
    calcularMontoDesdePorcentaje () {
      if (this.descuentoPorcentaje > 0 && this.totalSinDescuentos > 0) {
        return (this.totalSinDescuentos * (this.descuentoPorcentaje / 100)).toFixed(2)
      }
      return '0.00'
    },

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
        montoTotal: this.totalFinal,
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
        this.aporte = 0
        this.qr = false
        this.efectivo = ''
        this.descuento = 0
        this.descuentoPorcentaje = 0
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

    async clickSale () {
      // ✅ VERIFICACIÓN DE STOCK SOLO AL CONFIRMAR
      const productosSinStock = this.verificarStockCanasta()

      if (productosSinStock.length > 0) {
        let mensaje = 'No se puede realizar la venta por falta de stock:\n\n'
        productosSinStock.forEach(producto => {
          mensaje += `• ${producto.nombre}: Solicitado ${producto.cantidadSolicitada}, Disponible ${producto.stockDisponible}\n`
        })
        mensaje += '\nPor favor ajuste las cantidades antes de confirmar la venta.'
        this.$alert.error(mensaje)
        return
      }

      let hayProblema = false
      this.$store.productosVenta.forEach(p => {
        if (!p.precioVenta || p.precioVenta <= 0) {
          this.$alert.error(`El precio de venta de "${p.nombre}" debe ser mayor a 0.`)
          hayProblema = true
        }
      })
      if (hayProblema) return

      try {
        this.loading = true

        // VERIFICACIÓN EN BACKEND
        const productos = this.$store.productosVenta.map(p => ({
          id: p.id,
          cantidadVenta: p.cantidadVenta
        }))

        await this.$axios.post('verificar-stock-venta', {
          productos,
          agencia_id: this.agencia_id
        })

        this.aporte = 0
        this.descuento = 0
        this.descuentoPorcentaje = 0
        this.saleDialog = true
        this.efectivo = 0
        this.qr = false
        this.client = {
          numeroDocumento: '0',
          nombreRazonSocial: 'SN',
          email: '',
          complemento: ''
        }
        this.metodoPago = 'Efectivo'
      } catch (error) {
        this.loading = false
        if (error.response && error.response.data && error.response.data.errores) {
          error.response.data.errores.forEach(msg => {
            this.$alert.error(msg)
          })
        } else {
          this.$alert.error('Error al verificar stock')
        }
      } finally {
        this.loading = false
      }
    },

    // ✅ CORREGIDO: Usa cantidadReal para verificar stock
    async clickAddSale (product) {
      // Verificación inmediata usando cantidadReal
      if (product.cantidadReal <= 0) {
        this.$q.notify({
          color: 'negative',
          message: 'Producto sin stock disponible',
          icon: 'error',
          position: 'top'
        })
        return
      }

      // Verificación de stock considerando lo ya en canasta
      const productoEnCanasta = this.$store.productosVenta.find(p => p.id === product.id)
      const cantidadReservada = productoEnCanasta ? productoEnCanasta.cantidadVenta : 0
      const stockDisponible = product.cantidadReal - cantidadReservada

      if (stockDisponible <= 0) {
        this.$q.notify({
          color: 'negative',
          message: 'Stock insuficiente',
          icon: 'error',
          position: 'top'
        })
        return
      }

      // Solo actualizar el stock visual para mostrar
      product.cantidad = stockDisponible - 1

      if (product.porcentaje) {
        product.precioVenta = this.$filters.precioRebajaVenta(product.precio, product.porcentaje)
      }

      if (productoEnCanasta) {
        productoEnCanasta.cantidadVenta++
        productoEnCanasta.cantidadPedida++
      } else {
        product.cantidadVenta = 1
        product.cantidadPedida = 1
        this.$store.productosVenta.push(product)
      }
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
      n.cantidadPedida++
      n.cantidadVenta = parseInt(n.cantidadVenta) + 1
    },

    // ✅ PERMITE CUALQUIER VALOR - solo valida al confirmar
    cambioNumero (n, i) {
      if (n.cantidadVenta !== '') {
        const nuevaCantidad = parseInt(n.cantidadVenta)

        if (!isNaN(nuevaCantidad)) {
          n.cantidadPedida = nuevaCantidad
        }

        // Si es 0 o negativo, eliminar del carrito
        if (nuevaCantidad <= 0) {
          this.$store.productosVenta.splice(i, 1)
          n.cantidadVenta = 0
          n.cantidadPedida = 0
        }
      }
    },

    removeCantidad (n, i) {
      n.cantidadPedida--
      if (n.cantidadVenta > 1) {
        n.cantidadVenta = parseInt(n.cantidadVenta) - 1
      } else if (n.cantidadVenta === 1) {
        this.$store.productosVenta.splice(i, 1)
      }
    },

    deleteProductosVenta (p, i) {
      this.$store.productosVenta.splice(i, 1)
      p.cantidadVenta = 0
      p.cantidadPedida = 0
    },

    async vaciarCanasta () {
      await this.$store.productosVenta.forEach(p => {
        p.cantidadVenta = 0
        p.cantidadPedida = 0
      })
      this.$store.productosVenta = []
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
          p.cantidadReal = p.cantidad // ✅ Guardar stock real
          p.precioVenta = p.precio
          p.cantidadAlmacen = p.cantidadAlmacen || 0
          this.products.push(p)
        })
      }).catch(err => {
        this.loading = false
        console.log(err)
      })
    },
    cargarPedidoOnline () {
      this.$q.dialog({
        title: 'Pedido Online',
        message: 'Ingrese el número de pedido',
        prompt: {
          model: '',
          type: 'text',
          isValid: val => !!(val && String(val).trim().length > 0)
        },
        cancel: true,
        persistent: true
      }).onOk(val => {
        const raw = String(val || '').trim()
        // 👇 Si son solo dígitos, construye el formato que tu API espera.
        const numero = /^\d+$/.test(raw) ? `PEDIDOWEB_Nº${raw}` : raw
        this.importarPedidoDesdeApi(numero)
      })
    },

    async importarPedidoDesdeApi (orderNumber) {
      try {
        this.loadingPedidoOnline = true

        const encoded = encodeURIComponent(orderNumber)
        // ⚠️ Ajusta la ruta si tu backend usa otra
        const { data: order } = await this.$axios.get(`orders/${encoded}`)

        if (!order || !Array.isArray(order.items) || order.items.length === 0) {
          this.$alert.error('Ese pedido no tiene ítems o no existe.')
          return
        }

        // Para cada item del pedido: solo usamos product_id y quantity
        for (const it of order.items) {
          const cantidad = Number(it.quantity ?? it.cantidad ?? 0)
          const productId = it.product_id ?? it.id ?? it.productId

          if (!productId || cantidad <= 0) continue

          const base = await this.obtenerProductoCanonico(productId)
          if (!base) {
            this.$alert.error(`No se pudo cargar el producto ID ${productId}.`)
            continue
          }

          // Agregar tal cual “click” N veces (aplica tus descuentos y validaciones)
          this.agregarComoClicks(base, cantidad)
        }

        this.$alert.success(`Pedido ${orderNumber} importado a la canasta.`)
      } catch (e) {
        console.error(e)
        this.$alert.error(e?.response?.data?.message || 'No se pudo recuperar el pedido.')
      } finally {
        this.loadingPedidoOnline = false
      }
    },

    async obtenerProductoCanonico (id) {
      // 1) ¿Ya está cargado en tu grid?
      const enGrid = this.products.find(p => p.id === id)
      if (enGrid) {
        // Aseguramos campos que usa tu canasta
        if (!Array.isArray(enGrid.buys)) this.$set(enGrid, 'buys', [])
        if (typeof enGrid.cantidadReal !== 'number') enGrid.cantidadReal = Number(enGrid.cantidad ?? 0)
        return enGrid
      }

      // 2) Si no está en el grid, pedir al backend
      try {
        // ⚠️ Ajusta la ruta si tu API usa otra
        const { data } = await this.$axios.get(`products/${id}`)
        const raw = data?.product || data
        if (!raw) return null

        // Normaliza campos mínimos para que clickAddSale funcione igual que con el grid
        const base = {
          ...raw,
          // stock para las validaciones de clickAddSale
          cantidadReal: Number(raw.cantidadReal ?? raw.cantidad ?? raw.stock ?? 0),
          // precio se recalcula dentro de clickAddSale si hay porcentaje,
          // pero no molesta si lo dejamos así:
          precioVenta: Number(raw.precio),
          porcentaje: Number(raw.porcentaje ?? 0),
          cantidadVenta: 0,
          cantidadPedida: 0,
          buys: Array.isArray(raw.buys) ? raw.buys : []
        }
        return base
      } catch (e) {
        console.error('No se pudo obtener el producto', id, e)
        return null
      }
    },

    agregarComoClicks (producto, cantidad) {
      // Reutiliza TODA tu lógica de clickAddSale (descuento, stock, etc.)
      for (let i = 0; i < cantidad; i++) {
        this.clickAddSale(producto)
      }
    }

  }
}
</script>

<style scoped>
.cursor-not-allowed {
  cursor: not-allowed;
}
</style>

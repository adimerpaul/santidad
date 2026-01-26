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
              icon="inventory"
              dense
              label="Pedido Digital"
              color="deep-orange"
              no-caps
              :loading="loadingPedidoDigital"
              @click="cargarPedidoDigital"
            >
              <q-tooltip>Cargar productos APROBADOS (Comprar) de un pedido</q-tooltip>
            </q-btn>
          </div>

          <div class="col-12 col-md-3 q-pa-xs">
            <q-select class="bg-white" emit-value map-options dense outlined
                      v-model="category" option-value="id" option-label="name" :options="categories"
                      @update:model-value="productsGet"/>
          </div>
          <div class="col-12 col-md-3 q-pa-xs">
            <q-select class="bg-white" label="Subcategoria" dense outlined v-model="subcategory"
                      :options="subcategories" map-options emit-value
                      option-value="id" option-label="name"
                      @update:model-value="productsGet"/>
          </div>
          <div class="col-12 col-md-3 q-pa-xs">
            <q-select class="bg-white" label="Ordenar" dense outlined v-model="order"
                      :options="orders" map-options emit-value
                      option-value="value" option-label="label"
                      @update:model-value="productsGet"/>
          </div>
          <div class="col-12 col-md-3 q-pa-xs">
            <q-select class="bg-white" label="Agencia" dense outlined v-model="$store.agencia_id"
                      :options="agencias" map-options emit-value
                      option-value="id" option-label="nombre"
                      @update:model-value="productsGet"
                      :disable="!($store.user?.agencia_id==1)"/>
          </div>

          <div class="col-12 flex flex-center">
            <q-pagination
              v-model="current_page"
              :max="last_page"
              :max-pages="6"
              boundary-numbers
              @update:model-value="productsGet"/>
          </div>

          <div class="col-12">
            <q-card>
              <q-card-section class="q-pa-none">
                <div class="row cursor-pointer" v-if="products.length>0">
                  <div class="col-4 col-md-2" v-for="p in products" :key="p.id">
                    <q-card @click="clickAddSale(p)" class="q-pa-xs" flat bordered>
                      <q-img
                    :src="p.imagen.includes('http')?p.imagen:`${$url}../images/${p.imagen}`"
                    width="100%"
                    height="160px"
                    fit="contain"
                    class="bg-white q-pa-sm"
                  >
                  <q-badge color="red" floating style="padding: 5px 8px; margin: 0px" v-if="p.porcentaje">
                      -{{p.porcentaje}}%
                    </q-badge>

                    <div class="absolute-bottom text-center text-subtitle2"
                         style="padding: 4px 0px; line-height: 1.1; background: rgba(0,0,0,0.6);">
                      {{$filters.capitalize(p.nombre)}}
                    </div>
                  </q-img>
                      <q-card-section class="q-pa-none q-ma-none">
                        <div class="text-center text-subtitle2">
                          {{ p.precio }}
                          <span class="text-red" v-if="p.porcentaje">
                            {{$filters.precioRebajaVenta(p.precio, p.porcentaje)}}
                          </span>
                          Bs
                        </div>
                        <div :class="p.cantidad<=0?'text-center text-bold text-red':' text-center text-bold'">
                          {{ p.cantidad }} {{ $q.screen.lt.md?'Dis':'Disponible' }}
                        </div>
                        <div v-if="$store.user?.agencia_id == 1 && p.cantidadAlmacen !== undefined"
                             class="text-center text-caption text-blue">
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
              <div class="col-6 text-h6 q-pt-xs q-pl-lg bg-orange">
                Canasta Compras ({{ $store.productosCompra.length }})
              </div>
              <div class="col-6 text-right">
                <q-btn class="text-subtitle1 text-blue-10 text-bold" style="text-decoration: underline;"
                       label="Vaciar canasta" @click="vaciarCanasta" no-caps flat outline/>
              </div>
            </div>
          </q-card-section>

          <q-separator />

          <q-card-section class="q-pa-none q-ma-none">
            <div v-if="$store.productosCompra.length==0" class="flex flex-center q-pa-lg">
              <q-icon name="o_shopping_basket" color="grey" size="100px"/>
              <div class="q-pa-lg text-grey text-center noSelect">
                Aún no tienes productos en tu canasta. Haz clic sobre un producto para agregarlo.
              </div>
            </div>

            <q-scroll-area v-else style="height: 400px;">
              <q-table dense flat bordered hide-bottom hide-header
                       :rows="$store.productosCompra"
                       :columns="columnsProductosVenta"
                       :rows-per-page-options="[0]">
                <template v-slot:body="props">
                  <q-tr :props="props">
                    <q-td key="borrar" :props="props" style="padding: 0px;margin: 0px" auto-width>
                      <q-btn flat dense @click="deleteProductosVenta(props.row,props.pageIndex)"
                             icon="delete" color="red" size="10px" class="q-pa-none q-ma-none" />
                    </q-td>

                    <q-td key="nombre" :props="props">
                      <div>
                        <q-img :src="props.row.imagen.includes('http')?props.row.imagen:`${$url}../images/${props.row.imagen}`"
                               width="40px" height="40px"
                               style="padding:0;margin:0;border-radius:0;position:absolute;object-fit:cover;" />
                        <div style="padding-left: 42px">
                          <div class="text-caption" style="max-width:170px; white-space:normal; overflow-wrap:break-word; line-height:.9;">
                            {{props.row.nombre}}
                          </div>
                        </div>
                      </div>
                    </q-td>

                    <q-td key="cantidadVenta" :props="props">
                      <div class="td-venta">
                        <input v-model="props.row.lote" placeholder="Lote" style="width: 170px;"/><br>

                        <!-- Fila del input de fecha con el texto a la DERECHA (sin empujar nada) -->
                        <div class="date-row">
                          <input type="date"
                                 v-model="props.row.fechaVencimiento"
                                 placeholder="Vencimiento"
                                 style="width: 170px;"
                                 :class="{'invalid-date': props.row._fechaVencimientoError}"
                                 @input="props.row._fechaVencimientoError=false; props.row._tocoFecha=true"
                                 @change="props.row._fechaVencimientoError=false; props.row._tocoFecha=true" />
                          <div class="vence-right-inline">
                            <span class="vence-label"
                                  :class="{'corto-vencimiento': esCortoVencimiento(props.row.fechaVencimiento)}">
                              {{ venceEn(props.row.fechaVencimiento) }}
                            </span>
                            <q-icon v-if="esCortoVencimiento(props.row.fechaVencimiento)"
                                    name="warning" size="16px" class="warning-right"/>
                          </div>
                        </div>
                        <br>

                        <input v-model="props.row.cantidadCompra" type="number" placeholder="Cantidad" style="width: 170px;"/><br>
                        <input v-model="props.row.price" type="number" step="0.01" placeholder="Precio" style="width: 170px;"/><br>

                        <div><b>Subtotal:</b> {{(props.row.price*props.row.cantidadCompra).toFixed(2)}} Bs</div>

                        <!-- Alerta debajo del Subtotal: SOLO tras modificar la fecha -->
                        <q-badge
                          v-if="props.row._tocoFecha && esCortoVencimiento(props.row.fechaVencimiento)"
                          class="alert-under-subtotal q-mt-xs"
                          color="red-5" text-color="white" outline>
                          <q-icon name="warning" size="14px" class="q-mr-xs" />
                          Producto con corto vencimiento
                        </q-badge>
                      </div>
                    </q-td>
                  </q-tr>
                </template>
              </q-table>
            </q-scroll-area>
          </q-card-section>

          <q-card-section>
            <q-list padding bordered dense class="rounded-borders full-width q-pa-none q-ma-none">
              <q-item-section>
                <div class="row">
                  <div class="col-4 text-grey flex flex-center">Numero Factura</div>
                  <div class="col-8 text-right">
                    <q-input dense outlined v-model="factura" placeholder="Numero Factura" style="width: 170px;" hide-hint />
                  </div>

                  <div class="col-4 text-grey flex flex-center">Agencia</div>
                  <div class="col-8 text-right">
                    <q-select class="bg-white" dense outlined v-model="agencia_id"
                              :options="agencias" map-options emit-value
                              option-value="id" option-label="nombre"
                              :disable="!($store.user?.agencia_id==1)"/>
                  </div>

                  <div class="col-4 text-grey flex flex-center">Proveedor</div>
                  <div class="col-8 text-right">
                    <q-select class="bg-white" dense outlined v-model="proveedor_id"
                              :options="proveedores" map-options emit-value
                              use-input @filter="filterFn"
                              option-value="id" option-label="nombreRazonSocial"/>
                  </div>

                  <!-- Checkbox para crear factura -->
                  <div class="col-12 q-mt-md">
                    <q-checkbox
                      v-model="crearFactura"
                      label="Crear factura para esta compra"
                      color="primary"
                    />
                    <q-tooltip>Creará un registro de factura con todos los productos comprados</q-tooltip>
                  </div>
                </div>
              </q-item-section>
            </q-list>

            <q-btn @click="compraInsert" class="full-width" no-caps label="Confirmar compra"
                   :color="$store.productosCompra.length==0?'grey':'warning'"
                   :disable="$store.productosCompra.length==0"
                   :loading="loading"/>
          </q-card-section>
        </q-card>
      </div>
    </div>
    <div id="myElement" class="hidden"></div>

    <!-- Diálogo para datos de factura -->
    <q-dialog v-model="dialogoFactura" persistent>
      <q-card style="min-width: 500px">
        <q-card-section>
          <div class="text-h6">Crear Factura de Compra</div>
          <div class="text-subtitle2 text-grey">Complete los datos de la factura</div>
        </q-card-section>

        <q-card-section class="q-gutter-md">
          <div class="row">
            <div class="col-6">
              <q-input
                v-model="facturaData.numero_factura"
                label="Número de Factura "
                outlined
                dense
              />
            </div>
            <div class="col-6">
              <div class="col-6">
      <q-select
        v-model="facturaData.vendedor_id"
        :options="vendedores"
        label="Vendedor"
        option-label="nombre"
        option-value="id"
        outlined
        dense
        emit-value
        map-options
        bg-color="white"
        :disable="!facturaData.proveedor_id"
        @update:model-value="(val) => {
          // --- ESTA ES LA CLAVE: BUSCAR Y GUARDAR EL NOMBRE ---
          const vend = vendedores.find(v => v.id === val);
          facturaData.vendedor = vend ? vend.nombre : '';
        }"
      >
        <template v-slot:after>
          <q-btn round dense flat icon="add_circle" color="green"
                @click.stop="abrirDialogoVendedor"
                :disable="!facturaData.proveedor_id">
            <q-tooltip>Crear nuevo vendedor aquí</q-tooltip>
          </q-btn>
        </template>

        <template v-slot:no-option>
            <q-item>
              <q-item-section class="text-grey">
                {{ facturaData.proveedor_id ? 'Sin vendedores' : 'Seleccione proveedor primero' }}
              </q-item-section>
            </q-item>
        </template>
      </q-select>
    </div>

            </div>
          </div>

          <q-input
            v-model="facturaData.proveedor"
            label="Proveedor *"
            outlined
            dense
            :rules="[val => !!val || 'Campo requerido']"
            readonly
          />

          <div class="row">
            <div class="col-6">
              <q-input
                v-model="facturaData.fecha_compra"
                type="datetime-local"
                label="Fecha de Compra *"
                outlined
                dense
                :rules="[val => !!val || 'Campo requerido']"
              />
            </div>
            <div class="col-6">
              <q-input
                v-model="facturaData.monto_total"
                label="Monto Total *"
                type="number"
                outlined
                dense
                :rules="[val => !!val || 'Campo requerido']"
              />
            </div>
          </div>

          <div class="row">
            <div class="col-6">
              <q-select
                v-model="facturaData.tipo_pago"
                :options="['Contado', 'Crédito']"
                label="Tipo de Pago *"
                outlined
                dense
                :rules="[val => !!val || 'Campo requerido']"
              />
            </div>
            <div class="col-6">
              <q-select
                v-model="facturaData.metodo_pago"
                :options="['Efectivo', 'Transferencia','QR']"
                label="Método de Pago"
                outlined
                dense
              />
            </div>
<!--            numero_transaccion-->
            <div class="col-6">
              <q-input
                v-model="facturaData.numero_transaccion"
                label="Número de Transacción"
                outlined
                dense
              />
            </div>
          </div>

          <q-input
            v-if="facturaData.tipo_pago === 'Crédito'"
            v-model="facturaData.fecha_vencimiento"
            type="date"
            label="Fecha de Vencimiento"
            outlined
            dense
          />

          <q-input
            v-model="facturaData.observaciones"
            label="Observaciones"
            type="textarea"
            outlined
            dense
            rows="2"
          />

          <div class="text-caption text-grey">
            * Campos obligatorios
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="negative" @click="cancelarFactura" />
          <q-btn label="Crear sin Factura" color="grey" @click="finalizarSinFactura" />
          <q-btn label="Guardar Factura" color="primary" @click="crearFacturaCompleta" :loading="loadingFactura" />
        </q-card-actions>
      </q-card>
    </q-dialog>
    <q-dialog v-model="dialogVendedor" persistent>
      <q-card style="min-width: 350px">
        <q-card-section class="row items-center">
          <div class="text-h6">Nuevo Vendedor</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-form @submit.prevent="guardarNuevoVendedor">
            <div class="row q-col-gutter-sm">
              <div class="col-12">
                <div class="text-caption text-grey">Proveedor asociado:</div>
                <div class="text-bold">{{ nombreProveedorActual }}</div>
              </div>
              <div class="col-12">
                <q-input outlined dense v-model="nuevoVendedor.nombre" label="Nombre Completo"
                         :rules="[val => !!val || 'El nombre es obligatorio']" autofocus />
              </div>
              <div class="col-12">
                <q-input outlined dense v-model="nuevoVendedor.celular" label="Celular" type="number"
                         :rules="[val => !!val || 'El celular es obligatorio']" />
              </div>
            </div>
            <div class="row justify-end q-mt-md">
              <q-btn label="Cancelar" color="grey" flat v-close-popup class="q-mr-sm" />
              <q-btn label="Guardar y Seleccionar" color="primary" type="submit" :loading="loadingVendedor" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
import { date } from 'quasar'

export default {
  name: 'ComprasPage',
  data () {
    return {
      saleDialog: false,
      client: {},
      aporte: false,
      qr: false,
      documents: [],
      metodoPago: 'Efectivo',
      document: {},
      factura: '',
      pedidoId: null,
      agencia_id: 0,
      current_page: 1,
      vendedores: [],
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
      vendedor_id: null,
      proveedor_id: 0,
      crearFactura: true,
      dialogoFactura: false,
      datosCompra: null,
      loadingFactura: false,
      loadingPedidoDigital: false,
      dialogVendedor: false,
      loadingVendedor: false,
      nuevoVendedor: {
        nombre: '',
        celular: ''
      },
      facturaData: {
        numero_factura: '',
        proveedor: '',
        vendedor: '',
        vendedor_id: null,
        fecha_compra: date.formatDate(new Date(), 'YYYY-MM-DDTHH:mm'),
        monto_total: 0,
        tipo_pago: 'Contado',
        metodo_pago: 'Efectivo',
        fecha_vencimiento: '',
        observaciones: '',
        agencia_id: 0,
        proveedor_id: 0
      }
    }
  },
  created () {
    this.agencia_id = this.$store.agencia_id
  },
  mounted () {
    this.proveedoresGet()
    this.productsGet()
    this.categoriesGet()
    this.subcategoriesGet()
    this.agenciasGet()
  },
  methods: {
    subcategoriesGet () {
      this.subcategories = [{ name: 'Ver todas las sub categorias', id: 0 }]
      this.$axios.get('subcategories').then(response => {
        this.subcategories = this.subcategories.concat(response.data)
      }).catch(error => console.log(error))
    },
    filterFn (val, update, abort) {
      if (val === '') {
        update(() => { this.proveedores = this.proveedoresAll })
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
      // Recalcular total
      this.calcularTotalFactura()
    },
    async compraInsert () {
      // Validar que todos los productos tengan datos completos
      for (const p of this.$store.productosCompra) {
        p._fechaVencimientoError = false
        if (
          p.lote === '' || p.fechaVencimiento === '' || p.cantidadCompra === '' || p.price === '' ||
          p.lote === null || p.fechaVencimiento === null || p.cantidadCompra === null || p.price === null
        ) {
          if (!p.fechaVencimiento) p._fechaVencimientoError = true
          this.$alert.error('Debes ingresar el lote, la fecha de vencimiento y la cantidad de compra para todos los productos.')
          return false
        }
        if (!/^\d{4}-\d{2}-\d{2}$/.test(p.fechaVencimiento)) {
          p._fechaVencimientoError = true
          this.$alert.error(`La fecha de vencimiento de "${p.nombre}" debe tener el formato AAAA-MM-DD.`)
          return false
        }
        const [yy, mm, dd] = p.fechaVencimiento.split('-').map(Number)
        const fecha = new Date(yy, mm - 1, dd)
        if (fecha.getFullYear() !== yy || fecha.getMonth() + 1 !== mm || fecha.getDate() !== dd) {
          p._fechaVencimientoError = true
          this.$alert.error(`La fecha de vencimiento de "${p.nombre}" no es válida.`)
          return false
        }
        const hoyDate = new Date()
        const maxDate = new Date(hoyDate.getFullYear() + 10, hoyDate.getMonth(), hoyDate.getDate())
        const hoy = date.formatDate(hoyDate, 'YYYY-MM-DD')
        const fechaStr = date.formatDate(fecha, 'YYYY-MM-DD')
        if (fechaStr <= hoy) {
          p._fechaVencimientoError = true
          this.$alert.error(`El vencimiento de "${p.nombre}" debe ser posterior a hoy.`)
          return false
        }
        if (fecha > maxDate) {
          p._fechaVencimientoError = true
          this.$alert.error(`El vencimiento de "${p.nombre}" es demasiado lejano (máximo 10 años).`)
          return false
        }
      }

      // Validar proveedor seleccionado
      // if (!this.proveedor_id) {
      //   this.$alert.error('Debes seleccionar un proveedor')
      //   return false
      // }

      await this.$q.dialog({
        title: 'Confirmar compra',
        message: `¿Estás seguro de confirmar la compra a <span style="color: red"> <b> ${this.agencias.find(a => a.id === this.agencia_id)?.nombre || 'Almacén'}</b></span> con <span style="color: red"> <b>${this.$store.productosCompra.length}</b></span> productos?`,
        html: true,
        cancel: true,
        persistent: true
      }).onOk(async () => {
        // Calcular total de la compra
        const totalCompra = this.calcularTotalCompra()

        // Si el usuario quiere crear factura, mostrar diálogo
        if (this.crearFactura) {
          // Preparar datos para el diálogo
          this.facturaData.monto_total = totalCompra
          this.facturaData.agencia_id = this.agencia_id
          this.facturaData.proveedor_id = this.proveedor_id
          this.facturaData.proveedor = this.proveedores.find(p => p.id === this.proveedor_id)?.nombreRazonSocial || ''
          this.facturaData.numero_factura = this.factura

          // Guardar datos de compra temporalmente
          this.datosCompra = {
            buys: this.$store.productosCompra.map(p => ({
              id: p.id,
              lote: p.lote,
              fechaVencimiento: p.fechaVencimiento,
              cantidadCompra: p.cantidadCompra,
              price: p.price,
              nombre: p.nombre
            })),
            factura: this.factura,
            agencia_id: this.agencia_id,
            proveedor_id: this.proveedor_id,
            agencia_comprador_id: this.$store.agencia_id,
            crear_factura: true,
            pedido_id: this.pedidoId
          }

          this.dialogoFactura = true
        } else {
          // Procesar compra sin factura
          await this.procesarCompra({
            buys: this.$store.productosCompra,
            factura: this.factura,
            agencia_id: this.agencia_id,
            proveedor_id: this.proveedor_id,
            agencia_comprador_id: this.$store.agencia_id,
            pedido_id: this.pedidoId,
            crear_factura: false
          })
        }
      })
    },
    async procesarCompra (datos) {
      try {
        this.loading = true
        await this.$axios.post('compraInsert', datos)

        this.$alert.success('Compra realizada con éxito')
        this.$store.productosCompra = []
        this.pedidoId = null
        this.loading = false
        this.productsGet()
        this.factura = ''
        this.crearFactura = false
        this.resetFacturaData()
      } catch (err) {
        this.$alert.error(err.response?.data?.message || 'Error al procesar la compra')
        this.loading = false
      }
    },
    async crearFacturaCompleta () {
      try {
        // Validar datos de factura
        if (!this.facturaData.fecha_compra) {
          this.$alert.error('Complete los campos obligatorios de la factura')
          return
        }

        this.loadingFactura = true
        this.datosCompra.vendedor_id = this.facturaData.vendedor_id
        // -----------------------------
        // --- AGREGAR ESTO: Preparar datos para envío ---

        // 1. Primero crear la compra
        const compraResponse = await this.$axios.post('compraInsert', this.datosCompra)

        if (!compraResponse.data.buy_ids) {
          throw new Error('No se obtuvieron IDs de compra')
        }

        // 2. Crear la factura con los IDs de compra
        const facturaRequest = {
          ...this.facturaData,
          detalle_compras: compraResponse.data.buy_ids,
          fecha_compra: this.facturaData.fecha_compra.replace('T', ' '),
          user_id: this.$store.user.id
        }

        await this.$axios.post('facturas', facturaRequest)

        this.$alert.success('Compra y factura creadas exitosamente')

        // Limpiar todo
        this.dialogoFactura = false
        this.$store.productosCompra = []
        this.loadingFactura = false
        this.productsGet()
        this.factura = ''
        this.crearFactura = false
        this.resetFacturaData()
      } catch (err) {
        this.$alert.error(err.response?.data?.message || 'Error al crear factura')
        this.loadingFactura = false
      }
    },
    async finalizarSinFactura () {
      this.dialogoFactura = false
      await this.procesarCompra({
        ...this.datosCompra,
        vendedor_id: this.facturaData.vendedor_id,
        crear_factura: false
      })
    },
    cancelarFactura () {
      this.dialogoFactura = false
      this.$alert.info('Compra cancelada')
    },
    resetFacturaData () {
      this.facturaData = {
        numero_factura: '',
        proveedor: '',
        vendedor: '',
        fecha_compra: date.formatDate(new Date(), 'YYYY-MM-DDTHH:mm'),
        monto_total: 0,
        tipo_pago: 'Contado',
        metodo_pago: 'Efectivo',
        fecha_vencimiento: '',
        observaciones: '',
        agencia_id: 0,
        proveedor_id: 0
      }
    },
    calcularTotalCompra () {
      // return this.$store.productosCompra.reduce((total, p) => {
      //   const precio = parseFloat(p.price) || 0
      //   const cantidad = parseFloat(p.cantidadCompra) || 0
      //   return total + (precio * cantidad)
      // }, 0)
      // returdir el 30 porciento
      let total = 0
      this.$store.productosCompra.forEach(p => {
        const precio = parseFloat(p.price) || 0
        const cantidad = parseFloat(p.cantidadCompra) || 0
        total += precio * cantidad
      })
      // total = total - (total * 0.3)
      total = total / 1.3
      // redonde a 2 decimales
      return Math.round(total * 100) / 100
    },
    calcularTotalFactura () {
      this.facturaData.monto_total = this.calcularTotalCompra()
    },
    generarNumeroFactura () {
      const fecha = new Date()
      const year = fecha.getFullYear().toString().slice(-2)
      const month = (fecha.getMonth() + 1).toString().padStart(2, '0')
      const day = fecha.getDate().toString().padStart(2, '0')
      const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0')
      return `FAC-${year}${month}${day}-${random}`
    },
    async vaciarCanasta () {
      await this.$q.dialog({
        title: 'Vaciar canasta',
        message: '¿Estás seguro de vaciar la canasta de compras?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.$store.productosCompra.forEach(p => {
          p.cantidad = p.cantidadReal
          p.cantidadVenta = 0
          p.cantidadPedida = 0
        })
        this.$store.productosCompra = []
        this.pedidoId = null
        this.facturaData.monto_total = 0
      })
    },
    clickAddSale (product) {
      product.cantidadPedida++
      this.$store.productosCompra.push({
        id: product.id,
        nombre: product.nombre,
        imagen: product.imagen,
        lote: '',
        fechaVencimiento: date.formatDate(new Date(), 'YYYY-MM-DD'),
        cantidadCompra: '',
        _fechaVencimientoError: false,
        _tocoFecha: false,
        price: product.precioVenta,
        cantidadReal: product.cantidad
      })
      // Actualizar total de factura
      this.calcularTotalFactura()
    },
    agenciasGet () {
      this.agencias = [{ nombre: 'Almacen', id: 0 }]
      this.$axios.get('agencias').then(response => {
        this.agencias = this.agencias.concat(response.data)
      }).catch(error => {
        this.$alert.error(error.response?.data?.message || 'Error')
      })
    },
    categoriesGet () {
      this.categories = [{ name: 'Ver todas las categorias', id: 0 }]
      this.$axios.get('categories').then(response => {
        this.categories = this.categories.concat(response.data)
        this.categoriesTable = response.data
      }).catch(error => console.log(error))
    },
    productsGet () {
      this.loading = true
      this.products = []
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
        this.totalProducts = res.data.products.total
        this.last_page = res.data.products.last_page
        this.current_page = res.data.products.current_page
        this.costoTotalProducts = parseFloat(res.data.costoTotal).toFixed(2)
        res.data.products.data.forEach(p => {
          p.cantidadPedida = 0
          p.cantidadReal = p.cantidad
          p.precioVenta = p.precio
          p.cantidadAlmacen = p.cantidadAlmacen || 0
          this.products.push(p)
        })
      }).catch(err => {
        this.loading = false
        console.log(err)
      })
    },
    venceEn (fechaStr) {
      if (!fechaStr || !/^\d{4}-\d{2}-\d{2}$/.test(fechaStr)) return ''
      const [Y, M, D] = fechaStr.split('-').map(Number)
      const target = new Date(Y, M - 1, D)
      if (target.getFullYear() !== Y || target.getMonth() !== M - 1 || target.getDate() !== D) return ''

      const today = new Date()
      let months = (target.getFullYear() - today.getFullYear()) * 12 + (target.getMonth() - today.getMonth())
      if (target.getDate() < today.getDate()) months -= 1

      if (months < 0) return 'Vencido'
      const years = Math.floor(months / 12)
      const rem = months % 12

      if (years === 0) return `${months} ${months === 1 ? 'mes' : 'meses'}`
      if (rem === 0) return `${years} ${years === 1 ? 'año' : 'años'}`
      return `${years} ${years === 1 ? 'año' : 'años'} y ${rem} ${rem === 1 ? 'mes' : 'meses'}`
    },
    esCortoVencimiento (fechaStr) {
      if (!fechaStr || !/^\d{4}-\d{2}-\d{2}$/.test(fechaStr)) return false
      const [Y, M, D] = fechaStr.split('-').map(Number)
      const target = new Date(Y, M - 1, D)
      if (isNaN(target)) return false

      const hoy = new Date()
      let months = (target.getFullYear() - hoy.getFullYear()) * 12 + (target.getMonth() - hoy.getMonth())
      if (target.getDate() < hoy.getDate()) months -= 1

      return months >= 0 && months < 12
    },
    // --- AGREGAR ESTA FUNCIÓN QUE FALTA ---
    cargarPedidoDigital () {
      this.$q.dialog({
        title: 'Cargar Pedido Digital',
        message: 'Ingrese el ID del pedido aprobado',
        prompt: {
          model: '',
          type: 'number',
          isValid: val => !!(val && val > 0)
        },
        cancel: true,
        persistent: true
      }).onOk(val => {
        this.importarPedidoDigital(val)
      })
    },
    // --------------------------------------
    async importarPedidoDigital (pedidoId) {
      try {
        this.loadingPedidoDigital = true

        const { data } = await this.$axios.get(`pedidos/${pedidoId}/detalles`)
        const detalles = data.detalles || data
        this.pedidoId = pedidoId
        // --- NUEVO: Cargar el Proveedor Automáticamente ---
        if (data.proveedor_id) {
          this.proveedor_id = data.proveedor_id
          // El 'watch' de tu código se encargará de llenar el nombre y datos de factura
        }
        // -------------------------------------------------

        if (!detalles || detalles.length === 0) {
          this.$alert.error('El pedido no existe o no tiene productos.')
          return
        }

        let productosAgregados = 0

        detalles.forEach(item => {
          if (item.accion_aplicada === 'COMPRAR' && item.cantidad_aprobada > 0) {
            const productoInfo = item.product
            if (productoInfo) {
              const existente = this.$store.productosCompra.find(p => p.id === productoInfo.id)

              if (existente) {
                // CORRECCIÓN: Usar 'const' en lugar de 'let'
                const cantidadActual = parseInt(existente.cantidadCompra) || 0
                existente.cantidadCompra = cantidadActual + parseInt(item.cantidad_aprobada)
              } else {
                this.$store.productosCompra.push({
                  id: productoInfo.id,
                  nombre: productoInfo.nombre,
                  imagen: productoInfo.imagen,
                  lote: '',
                  fechaVencimiento: date.formatDate(new Date(), 'YYYY-MM-DD'),
                  cantidadCompra: item.cantidad_aprobada,
                  _fechaVencimientoError: false,
                  _tocoFecha: false,
                  price: productoInfo.precio,
                  cantidadReal: productoInfo.cantidad
                })
              }
              productosAgregados++
            }
          }
        })

        this.calcularTotalFactura()

        if (productosAgregados > 0) {
          this.$alert.success(`Se cargaron ${productosAgregados} productos del Pedido #${pedidoId}.`)
        } else {
          this.$q.notify({
            type: 'warning',
            message: 'No hay productos con acción "COMPRAR" en ese pedido.'
          })
        }
      } catch (e) {
        console.error(e)
        this.$alert.error(e.response?.data?.message || 'Error al cargar el pedido digital.')
      } finally {
        this.loadingPedidoDigital = false
      }
    },
    cargarVendedores (proveedorId) {
      // Limpiamos variables
      this.vendedor_id = null
      this.facturaData.vendedor_id = null
      this.vendedores = []

      if (!proveedorId) return

      this.$axios.get(`vendedores-por-proveedor/${proveedorId}`).then(res => {
        this.vendedores = res.data
        // Si solo hay 1 vendedor, lo pre-seleccionamos para la factura
        if (this.vendedores.length === 1) {
          this.facturaData.vendedor_id = this.vendedores[0].id
          this.vendedor_id = this.vendedores[0].id
        }
      }).catch(err => console.error(err))
    },
    // -----------------------------------------------------------
    abrirDialogoVendedor () {
      // Validamos usando el dato que ya tienes en el formulario de factura
      if (!this.facturaData.proveedor_id) {
        this.$q.notify({
          color: 'warning',
          message: 'Primero verifica que haya un proveedor seleccionado en la compra',
          icon: 'warning'
        })
        return
      }
      this.nuevoVendedor = { nombre: '', celular: '' }
      this.dialogVendedor = true
    },
    guardarNuevoVendedor () {
      this.loadingVendedor = true
      const payload = { ...this.nuevoVendedor, client_id: this.facturaData.proveedor_id }

      this.$axios.post('vendedores', payload).then(res => {
        const vendedorCreado = res.data
        this.vendedores.push(vendedorCreado)

        // Asignar ID
        this.facturaData.vendedor_id = vendedorCreado.id
        // --- AGREGAR ESTO TAMBIÉN ---
        this.facturaData.vendedor = vendedorCreado.nombre

        this.$alert.success('Vendedor creado y seleccionado')
        this.dialogVendedor = false
      })
        .catch(err => {
          // AQUI ESTA EL ARREGLO: Usamos 'err' para que el linter no se queje
          console.error('Error creando vendedor:', err)
          this.$alert.error(err.response?.data?.message || 'Error al crear vendedor')
        })
        .finally(() => {
          this.loadingVendedor = false
        })
    }
  },
  computed: {
    nombreProveedorActual () {
      if (!this.facturaData.proveedor_id) return ''
      const prov = this.proveedoresAll.find(p => p.id === this.facturaData.proveedor_id)
      return prov ? prov.nombreRazonSocial : ''
    },
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
  },
  watch: {
    proveedor_id (newVal) {
      this.cargarVendedores(newVal)
      if (newVal && this.proveedores.length > 0) {
        const proveedor = this.proveedores.find(p => p.id === newVal)
        if (proveedor) {
          this.facturaData.proveedor = proveedor.nombreRazonSocial
          this.facturaData.proveedor_id = newVal
        }
      }
    },
    '$store.productosCompra': {
      handler () {
        this.calcularTotalFactura()
      },
      deep: true
    }
  }
}
</script>

<style lang="scss">
.super-small.q-field--dense {
  .q-field__control { height: 25px !important; min-height: 25px !important; }
  .q-field__marginal { height: 25px !important; }
  .q-field__label { top: 6px !important; }
}

input[type="date"].invalid-date {
  background-color: #fa4a4a !important;
  border: 1px solid #000000 !important;
  transition: background-color 0.2s ease, border-color 0.2s ease;
}

/* Contenedor del TD */
.td-venta { position: relative; }

/* Fila del input de fecha: sirve de ancla para el texto a la derecha */
.date-row { position: relative; display: inline-block; }

/* Texto (meses/años) a la DERECHA del input de fecha, sin empujar nada */
.vence-right-inline{
  position: absolute;
  right: 178px;             /* 170px del input + 8px de separación */
  top: 50%;
  transform: translateY(-50%);
  display: inline-flex;
  align-items: center;
  gap: 6px;
  pointer-events: none;    /* no bloquea clics en el input */
  white-space: nowrap;
}

.vence-label { font-size: 12px; color: #666; }
.corto-vencimiento { color: #d9534f; font-weight: 600; }
.warning-right { color: #d9534f; }

/* Alerta debajo del Subtotal */
.alert-under-subtotal{
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 9999px;
  box-shadow: 0 1px 2px rgba(0,0,0,.06);
}
</style>

<template>
  <q-page class="bg-grey-2 q-pa-xs">
    <div class="row">
      <div class="col-12 col-md-8">
        <div class="row">
          <div class="col-12 col-md-6 bg-white q-pa-xs">
            <q-input outlined v-model="search" label="Buscar producto" dense clearable
                     @update:model-value="current_page=1; productsGet()" debounce="500">
              <template v-slot:prepend>
                <q-icon name="search" class="cursor-pointer" />
              </template>
            </q-input>
          </div>

          <div class="col-12 col-md-6 flex q-pa-xs">
            <q-btn :loading="loading" icon="refresh" dense label="Actualizar"
                   color="indigo" no-caps class="text-bold" @click="productsGet">
              <q-tooltip>Actualizar</q-tooltip>
            </q-btn>
          </div>

          <div class="col-12 col-md-2 q-pa-xs">
            <q-select class="bg-white" emit-value map-options dense outlined
                      v-model="category" option-value="id" option-label="name" :options="categories"
                      @update:model-value="current_page=1; productsGet()" label="Categoría">
            </q-select>
          </div>

          <div class="col-12 col-md-2 q-pa-xs">
            <q-select class="bg-white" emit-value map-options dense outlined
                      v-model="subcategoria" option-value="id" option-label="name" :options="subcategories"
                      @update:model-value="current_page=1; productsGet()" label="Subcategoría">
            </q-select>
          </div>

          <div class="col-12 col-md-3 q-pa-xs">
            <q-select class="bg-white" dense outlined
                      v-model="proveedorFiltro"
                      :options="proveedores"
                      option-value="id"
                      option-label="nombreRazonSocial"
                      emit-value
                      map-options
                      clearable
                      use-input
                      @filter="filterProveedores"
                      @update:model-value="current_page=1; productsGet()"
                      label="Filtrar por Proveedor">
                <template v-slot:no-option>
                  <q-item>
                    <q-item-section class="text-grey">Sin resultados</q-item-section>
                  </q-item>
                </template>
            </q-select>
          </div>

          <div class="col-12 col-md-2 q-pa-xs">
            <q-select class="bg-white" label="Ordenar" dense outlined v-model="order"
                      :options="orders" map-options emit-value
                      option-value="value" option-label="label"
                      @update:model-value="productsGet"/>
          </div>

          <div class="col-12 col-md-3 q-pa-xs">
            <q-input dense outlined readonly :model-value="agenciaNombre" label="Sucursal" bg-color="white"/>
          </div>

          <div class="col-12">
            <q-card>
              <q-card-section class="q-pa-none">

                <div class="row justify-center q-py-sm" v-if="last_page > 1">
                  <q-pagination
                    v-model="current_page"
                    :max="last_page"
                    :max-pages="6"
                    boundary-numbers
                    direction-links
                    color="indigo"
                    active-color="indigo"
                    active-text-color="white"
                    @update:model-value="productsGet"
                    dense
                  />
                </div>
                <div class="row cursor-pointer" v-if="products.length>0">
                  <div class="col-4 col-md-2" v-for="p in products" :key="p.id">
                    <q-card @click="clickAddPedido(p)" class="q-pa-xs" flat bordered
                            :class="getProductCardClass(p)">
                      <q-img
                    :src="p.imagen.includes('http')?p.imagen:`${$url}../images/${p.imagen}`"
                    width="100%"
                    height="160px"
                    fit="contain"
                    class="bg-white q-pa-sm"
                  >
                  <q-badge color="red" floating style="padding: 5px 8px; margin: 0px" v-if="p.porcentaje">
                      {{p.porcentaje}}%
                    </q-badge>

                    <div class="absolute-bottom text-center text-subtitle2"
                         style="padding: 4px 0px; line-height: 1.1; background: rgba(0,0,0,0.6);">
                      {{p.nombre}}
                    </div>
                  </q-img>

                      <q-card-section class="q-pa-none q-ma-none">
                        <div class="text-center text-subtitle2">
                          {{ p.precio }} Bs
                        </div>

                        <div class="row items-center justify-center q-gutter-x-xs full-width">
                            <div :class="getStockTextClass(p)">
                                {{ p.cantidad }} {{ $q.screen.lt.md?'Dis':'Disponible' }}
                            </div>
                            <q-btn dense flat round icon="visibility" color="indigo" size="xs"
                                   @click.stop="verStockGlobal(p)">
                                <q-tooltip>Ver stock en todas las sucursales</q-tooltip>
                            </q-btn>
                        </div>

                        <div v-if="agencia_id == 1 && p.cantidadAlmacen !== undefined"
                             class="text-center text-caption text-lead">
                          Stock Almacén: {{ p.cantidadAlmacen }}
                        </div>
                      </q-card-section>
                    </q-card>
                  </div>
                </div>

                <div class="row justify-center q-mt-md q-pb-md" v-if="last_page > 1">
                  <q-pagination
                    v-model="current_page"
                    :max="last_page"
                    :max-pages="6"
                    boundary-numbers
                    direction-links
                    color="indigo"
                    active-color="indigo"
                    active-text-color="white"
                    @update:model-value="productsGet"
                  />
                </div>

                <q-card v-else-if="!loading && products.length===0">
                  <q-card-section>
                    <div class="row">
                      <div class="col-12 flex flex-center">
                        <q-avatar size="150px" font-size="150px" color="white"
                                  text-color="grey" icon="view_in_ar" />
                      </div>
                      <div class="col-12">
                        <div class="text-bold text-grey text-center">No encontramos productos para tu búsqueda.</div>
                        <div class="text-bold text-grey text-center">Intenta con otra palabra o revisa tu Inventario.</div>
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
          <q-card-section class="q-pa-none q-ma-none">
            <div class="row">
              <div class="col-6 text-h6 q-pt-xs q-pl-lg">
                Pedido ({{ productosPedido.length }})
              </div>
              <div class="col-6 text-right">
                <q-btn class="text-subtitle1 text-blue-10 text-bold"
                       style="text-decoration: underline;" label="Vaciar pedido"
                       @click="vaciarPedido" no-caps flat outline/>
              </div>
            </div>
          </q-card-section>
          <q-separator></q-separator>
          <q-card-section class="q-pa-none q-ma-none">
            <div v-if="productosPedido.length==0" class="flex flex-center q-pa-lg">
              <q-icon name="o_shopping_basket" color="grey" size="100px"/>
              <div class="q-pa-lg text-grey text-center noSelect">
                Aún no tienes productos en tu pedido. Haz clic sobre un producto para agregarlo.
              </div>
            </div>
            <q-scroll-area v-else style="height: 400px;">
              <q-table dense flat bordered hide-bottom hide-header
                       :rows="productosPedido" :columns="columnsPedidos" :rows-per-page-options="[0]">
                <template v-slot:body="props">
                  <q-tr :props="props">
                    <q-td key="borrar" :props="props" style="padding: 0px;margin: 0px" auto-width>
                      <q-btn flat dense @click="deleteProductoPedido(props.row,props.pageIndex)"
                             icon="delete" color="red" size="10px" class="q-pa-none q-ma-none" />
                    </q-td>
                    <q-td key="nombre" :props="props">
                      <div>
                        <q-img :src="props.row.imagen.includes('http')?props.row.imagen:`${$url}../images/${props.row.imagen}`"
                               width="40px" height="40px"
                               style="padding: 0px; margin: 0px; border-radius: 0px;position: absolute;crop: auto;object-fit: cover;"/>
                        <div style="padding-left: 42px">
                          <div class="text-caption" style="max-width: 120px; white-space: normal; overflow-wrap: break-word;line-height: 0.9;">
                            {{props.row.nombre}}
                          </div>
                          <div class="text-grey">Stock Actual: {{props.row.cantidad}}</div>
                        </div>
                      </div>
                    </q-td>
                    <q-td key="cantidadPedida" :props="props">
                      <q-input dense outlined bottom-slots min="1"
                               v-model="props.row.cantidadPedida"
                               @update:model-value="cambioNumeroPedido(props.row,props.pageIndex)"
                               :rules="ruleNumber"
                               type="number"
                               input-class="text-center"
                               required>
                        <template v-slot:prepend>
                          <q-btn style="cursor: pointer" dense flat icon="remove_circle_outline"
                                 @click="removeCantidadPedido(props.row,props.pageIndex)"/>
                        </template>
                        <template v-slot:append>
                          <q-btn style="cursor: pointer" dense flat icon="add_circle_outline"
                                 @click="addCantidadPedido(props.row,props.pageIndex)"/>
                        </template>
                      </q-input>
                    </q-td>
                  </q-tr>
                </template>
              </q-table>
            </q-scroll-area>
          </q-card-section>
          <q-card-section>
            <q-list padding bordered dense class="rounded-borders full-width q-pa-none q-ma-none">
              <q-expansion-item dense dense-toggle expand-separator label="Resumen">
                <template v-slot:header>
                  <q-item-section>Total Items</q-item-section>
                  <q-item-section side>
                    <div class="text-right text-grey-8 text-bold">{{totalItemsPedido}}</div>
                  </q-item-section>
                </template>
                <q-card>
                  <q-card-section>
                    <div class="row">
                      <div class="col-7 text-grey">Productos diferentes</div>
                      <div class="col-5 text-right">{{productosPedido.length}}</div>
                      <div class="col-7 text-grey">Total unidades</div>
                      <div class="col-5 text-right text-green">{{totalItemsPedido}}</div>
                    </div>
                  </q-card-section>
                </q-card>
              </q-expansion-item>
            </q-list>
            <q-btn @click="clickConfirmarPedido" class="full-width" no-caps label="Confirmar pedido"
                   :color="productosPedido.length==0?'grey':'primary'"
                   :disable="productosPedido.length==0"
                   :loading="loading"/>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <q-dialog v-model="pedidoDialog" persistent>
      <q-card style="width: 850px; max-width: 90vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Realizar pedido</div>
          <q-space />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-card-section>
        <q-form @submit.prevent="pedidoInsert">
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-input outlined dense label="Solicitante" required
                         v-model="pedido.solicitante" style="text-transform: uppercase" readonly/>
              </div>
              <div class="col-12 col-md-6">
                <q-input outlined dense label="Sucursal"
                         :model-value="agenciaNombre" readonly bg-color="grey-2"/>
              </div>

              <div class="col-12">
                <q-select outlined dense label="Proveedor para este pedido"
                          v-model="pedido.proveedor_id"
                          :options="proveedores"
                          option-value="id"
                          option-label="nombreRazonSocial"
                          emit-value
                          map-options
                          use-input
                          @filter="filterProveedores"
                          @update:model-value="cargarVendedores"
                          :rules="[val => !!val || 'Debe seleccionar un proveedor']"
                          bg-color="white"
                >
                  <template v-slot:prepend>
                    <q-icon name="local_shipping" />
                  </template>
                </q-select>
              </div>

              <div class="col-12">
                <q-select outlined dense label="Vendedor (Opcional)"
                          v-model="pedido.vendedor_id"
                          :options="vendedores"
                          option-value="id"
                          option-label="nombre"
                          emit-value
                          map-options
                          bg-color="white"
                          :disable="!pedido.proveedor_id"
                >
                  <template v-slot:prepend>
                    <q-icon name="badge" />
                  </template>

                  <template v-slot:append>
                    <q-btn round dense flat icon="add_circle" color="green"
                           @click.stop="abrirDialogoVendedor"
                           :disable="!pedido.proveedor_id">
                      <q-tooltip>Crear nuevo vendedor aquí</q-tooltip>
                    </q-btn>
                  </template>

                  <template v-slot:no-option>
                    <q-item>
                      <q-item-section class="text-grey">
                        {{ pedido.proveedor_id ? 'Sin vendedores registrados' : 'Seleccione proveedor primero' }}
                      </q-item-section>
                    </q-item>
                  </template>
                  <template v-slot:option="scope">
                    <q-item v-bind="scope.itemProps">
                      <q-item-section>
                        <q-item-label>{{ scope.opt.nombre }}</q-item-label>
                        <q-item-label caption>{{ scope.opt.celular }}</q-item-label>
                      </q-item-section>
                    </q-item>
                  </template>
                </q-select>
              </div>

              <div class="col-12">
                <q-input outlined dense label="Observaciones (Opcional)" v-model="pedido.observacion"
                         type="textarea" rows="2"/>
              </div>
            </div>
          </q-card-section>
          <q-separator/>
          <q-card-section>
            <div class="row">
              <div class="col-12">
                <div class="text-h6 text-center">Resumen del Pedido</div>
                <q-table dense :rows="productosPedido" :columns="columnsResumenPedido"
                         row-key="id" hide-pagination>
                  <template v-slot:body="props">
                    <q-tr :props="props">
                      <q-td key="producto">{{ props.row.nombre }}</q-td>
                      <q-td key="stock">{{ props.row.cantidad }}</q-td>
                      <q-td key="cantidad">{{ props.row.cantidadPedida }}</q-td>
                    </q-tr>
                  </template>
                </q-table>
              </div>
              <div class="col-12 q-pt-md">
                <div class="text-subtitle1 text-center">
                  Total: <strong>{{productosPedido.length}}</strong> productos |
                  <strong>{{totalItemsPedido}}</strong> unidades
                </div>
              </div>
            </div>
          </q-card-section>
          <q-separator/>
          <q-card-section>
            <div class="row">
              <div class="col-6">
                <q-btn type="submit" class="full-width" icon="o_send" label="Enviar pedido"
                       :loading="loading" no-caps color="green"/>
              </div>
              <div class="col-6">
                <q-btn class="full-width" icon="undo" v-close-popup label="Atrás"
                       no-caps color="red" :loading="loading" />
              </div>
            </div>
          </q-card-section>
        </q-form>
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

    <q-dialog v-model="dialogStock">
      <q-card style="min-width: 350px; max-width: 90vw;">
        <q-card-section class="bg-indigo text-white row items-center">
          <div class="text-h6 ellipsis">{{ productStockSelected.nombre }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <div class="text-subtitle2 q-mb-sm text-center text-grey-8">Disponibilidad en Sucursales</div>
          <q-list bordered separator dense>
            <q-item>
              <q-item-section avatar>
                <q-icon name="warehouse" color="brown" />
              </q-item-section>
              <q-item-section>Almacén Central</q-item-section>
              <q-item-section side>
                <q-badge :color="productStockSelected.cantidadAlmacen > 0 ? 'green' : 'red'">
                  {{ productStockSelected.cantidadAlmacen || 0 }}
                </q-badge>
              </q-item-section>
            </q-item>

            <q-item v-for="agencia in agencias" :key="agencia.id">
              <q-item-section avatar>
                <q-icon name="store" color="indigo" />
              </q-item-section>
              <q-item-section>{{ agencia.nombre }}</q-item-section>
              <q-item-section side>
                <q-badge :color="(productStockSelected['cantidadSucursal'+agencia.id] || 0) > 0 ? 'blue' : 'grey'">
                  {{ productStockSelected['cantidadSucursal'+agencia.id] || 0 }}
                </q-badge>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cerrar" color="indigo" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script>
import { date } from 'quasar'
export default {
  name: 'PedidosPage',
  data () {
    return {
      agencia_id: parseInt(localStorage.getItem('agencia_id')),
      agenciaNombre: '',
      pedidoDialog: false,
      proveedorFiltro: null,

      // Variables para ver stock global
      dialogStock: false,
      productStockSelected: {},

      pedido: {
        solicitante: '',
        observacion: '',
        proveedor_id: null,
        vendedor_id: null
      },

      // VARIABLES PARA EL VENDEDOR
      dialogVendedor: false,
      loadingVendedor: false,
      nuevoVendedor: {
        nombre: '',
        celular: ''
      },

      // Variables para proveedores
      proveedores: [],
      proveedoresAll: [],
      vendedores: [],

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
      agencias: [], // Lista de agencias para el dialogo de stock
      productosPedido: [], // Carrito de pedidos
      category: 0,
      categories: [
        { name: 'Ver todas las categorias', id: 0 }
      ],
      order: 'cantidad desc',
      columnsPedidos: [
        { label: 'borrar', field: 'borrar', name: 'borrar', align: 'left' },
        { label: 'nombre', field: 'nombre', name: 'nombre', align: 'left' },
        { label: 'cantidadPedida', field: 'cantidadPedida', name: 'cantidadPedida' }
      ],
      columnsResumenPedido: [
        { name: 'producto', label: 'Producto', field: 'nombre', align: 'left' },
        { name: 'stock', label: 'Stock Actual', field: 'cantidad', align: 'center' },
        { name: 'cantidad', label: 'Cantidad Pedida', field: 'cantidadPedida', align: 'center' }
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
  computed: {
    totalItemsPedido () {
      let total = 0
      this.productosPedido.forEach(p => {
        total += parseInt(p.cantidadPedida || 0)
      })
      return total
    },
    nombreProveedorActual () {
      if (!this.pedido.proveedor_id) return ''
      const prov = this.proveedoresAll.find(p => p.id === this.pedido.proveedor_id)
      return prov ? prov.nombreRazonSocial : ''
    }
  },
  async mounted () {
    // Primero cargar la información del usuario y agencia
    await this.cargarInformacionUsuario()

    // Luego cargar los datos
    this.productsGet()
    this.categoriesGet()
    this.subcategoriesGet()

    // Cargar proveedores
    this.proveedoresGet()
  },
  methods: {
    // Método para ver stock global
    verStockGlobal (product) {
      this.productStockSelected = product
      this.dialogStock = true
    },

    // --- LÓGICA DE PROVEEDORES ---
    proveedoresGet () {
      this.$axios.get('providers').then(res => {
        this.proveedores = res.data
        this.proveedoresAll = res.data
      }).catch(err => {
        console.error('Error cargando proveedores', err)
      })
    },

    filterProveedores (val, update, abort) {
      if (val === '') {
        update(() => { this.proveedores = this.proveedoresAll })
        return
      }
      const needle = val.toLowerCase()
      update(() => {
        this.proveedores = this.proveedoresAll.filter(v => v.nombreRazonSocial.toLowerCase().indexOf(needle) > -1)
      })
    },
    // Cargar vendedores al cambiar proveedor
    cargarVendedores (proveedorId) {
      this.pedido.vendedor_id = null
      this.vendedores = []

      if (!proveedorId) return

      this.$axios.get(`vendedores-por-proveedor/${proveedorId}`).then(res => {
        this.vendedores = res.data
        // Si solo hay uno, seleccionarlo automático
        if (this.vendedores.length === 1) {
          this.pedido.vendedor_id = this.vendedores[0].id
        }
      }).catch(err => {
        console.error(err)
      })
    },
    // -----------------------------

    // Cargar información del usuario y agencia
    async cargarInformacionUsuario () {
      try {
        // Cargar agencias para obtener el nombre
        const agenciasRes = await this.$axios.get('agencias')
        this.agencias = agenciasRes.data

        // Buscar la agencia del usuario actual
        const agenciaUsuario = this.agencias.find(a => a.id === this.agencia_id)
        if (agenciaUsuario) {
          this.agenciaNombre = agenciaUsuario.nombre
        }

        // Si el usuario está logueado en el store, usar su nombre
        if (this.$store.user && this.$store.user.name) {
          this.pedido.solicitante = this.$store.user.name
        } else {
          try {
            const usersRes = await this.$axios.get(`users/agencia/${this.agencia_id}`)
            if (usersRes.data && usersRes.data.length > 0) {
              // Tomar el primer usuario de esa agencia como solicitante por defecto
              this.pedido.solicitante = usersRes.data[0].name || ''
            }
          } catch (error) {
            console.log('No se pudo obtener usuarios de la agencia, usando valor por defecto')
            this.pedido.solicitante = 'Usuario Sucursal ' + this.agencia_id
          }
        }
      } catch (error) {
        console.error('Error al cargar información del usuario:', error)
        this.agenciaNombre = 'Sucursal ' + this.agencia_id
        this.pedido.solicitante = 'Usuario Sucursal ' + this.agencia_id
      }
    },

    // CLASES DINÁMICAS
    getProductCardClass (product) {
      return 'bg-white cursor-pointer'
    },

    getStockTextClass (product) {
      if (product.cantidad <= 0) {
        return 'text-center text-bold text-red'
      } else if (product.cantidad <= 5) {
        return 'text-center text-bold text-orange'
      }
      return 'text-center text-bold'
    },

    getCantidadEnPedido (productId) {
      const producto = this.productosPedido.find(p => p.id === productId)
      return producto ? producto.cantidadPedida : 0
    },

    clickAddPedido (product) {
      const productoEnPedido = this.productosPedido.find(p => p.id === product.id)

      if (productoEnPedido) {
        productoEnPedido.cantidadPedida++
      } else {
        const productoParaPedido = {
          ...product,
          cantidadPedida: 1
        }
        this.productosPedido.push(productoParaPedido)
      }
    },

    // MANEJO DE CANTIDADES EN PEDIDO
    addCantidadPedido (producto, index) {
      producto.cantidadPedida++
    },

    removeCantidadPedido (producto, index) {
      producto.cantidadPedida--
      if (producto.cantidadPedida <= 0) {
        this.productosPedido.splice(index, 1)
      }
    },

    cambioNumeroPedido (producto, index) {
      if (producto.cantidadPedida !== '') {
        const nuevaCantidad = parseInt(producto.cantidadPedida)

        if (!isNaN(nuevaCantidad)) {
          producto.cantidadPedida = nuevaCantidad
        }

        if (nuevaCantidad <= 0) {
          this.productosPedido.splice(index, 1)
        }
      }
    },

    deleteProductoPedido (producto, index) {
      this.productosPedido.splice(index, 1)
    },

    vaciarPedido () {
      this.$q.dialog({
        title: 'Vaciar pedido',
        message: '¿Estás seguro de vaciar todo el pedido?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.productosPedido = []
      })
    },

    // CONFIRMAR PEDIDO
    clickConfirmarPedido () {
      if (this.productosPedido.length === 0) {
        this.$q.notify({
          color: 'negative',
          message: 'No hay productos en el pedido',
          icon: 'warning'
        })
        return
      }

      // Verificar cantidades válidas
      let hayProblema = false
      this.productosPedido.forEach(p => {
        if (!p.cantidadPedida || p.cantidadPedida <= 0) {
          this.$alert.error(`La cantidad de "${p.nombre}" debe ser mayor a 0.`)
          hayProblema = true
        }
      })
      if (hayProblema) return

      // Abrir diálogo
      this.pedidoDialog = true
    },

    // ENVIAR PEDIDO AL BACKEND
    pedidoInsert () {
      // Validación extra por seguridad
      if (!this.pedido.proveedor_id) {
        this.$q.notify({ color: 'warning', message: 'Debe seleccionar un proveedor' })
        return
      }

      this.loading = true

      // Obtener fecha y hora real (Formato: YYYY-MM-DD HH:mm:ss)
      const fechaPedido = date.formatDate(Date.now(), 'YYYY-MM-DD HH:mm:ss')

      const data = {
        agencia_id: this.agencia_id,
        user_id: this.$store.user?.id || null,
        fecha_pedido: fechaPedido,
        estado: 'PENDIENTE',
        observacion: this.pedido.observacion,

        // NUEVO: Enviar el proveedor seleccionado
        proveedor_id: this.pedido.proveedor_id,
        vendedor_id: this.pedido.vendedor_id,

        // Detalles del pedido
        detalles: this.productosPedido.map(p => ({
          product_id: p.id,
          cantidad: p.cantidadPedida
        }))
      }

      this.$axios.post('pedidos', data).then(res => {
        this.loading = false
        this.$alert.success('Pedido realizado con éxito')
        this.pedidoDialog = false

        // Resetear formulario
        this.productosPedido = []
        this.pedido.observacion = ''
        this.pedido.proveedor_id = null // Resetear proveedor

        // Actualizar lista de productos
        this.productsGet()
      }).catch(err => {
        this.loading = false
        this.$alert.error(err.response?.data?.message || 'Error al realizar el pedido')
      })
    },

    // OBTENER DATOS
    subcategoriesGet () {
      this.$axios.get('subcategories').then(response => {
        this.subcategories = response.data
      }).catch(error => {
        console.log(error)
      })
    },

    categoriesGet () {
      this.categories = [{ name: 'Ver todas las categorias', id: 0 }]
      this.$axios.get('categories').then(response => {
        this.categories = this.categories.concat(response.data)
      }).catch(error => {
        console.log(error)
      })
    },

    productsGet () {
      this.loading = true
      this.products = []
      this.$axios.get(`productsSale?page=${this.current_page}&search=${this.search}&order=${this.order}&category=${this.category}&agencia=${this.agencia_id}&subcategory=${this.subcategoria}&proveedor=${this.proveedorFiltro || 0}`).then(res => {
        this.loading = false
        this.totalProducts = res.data.products.total
        this.last_page = res.data.products.last_page
        this.current_page = res.data.products.current_page

        res.data.products.data.forEach(p => {
          p.cantidadPedida = this.getCantidadEnPedido(p.id) // Mantener cantidad si ya está en pedido
          this.products.push(p)
        })
      }).catch(err => {
        this.loading = false
        console.log(err)
      })
    },

    // MÉTODOS DEL VENDEDOR RÁPIDO
    abrirDialogoVendedor () {
      if (!this.pedido.proveedor_id) {
        this.$q.notify({
          color: 'warning',
          message: 'Primero debes seleccionar un Proveedor',
          icon: 'warning'
        })
        return
      }
      // Limpiar formulario
      this.nuevoVendedor = { nombre: '', celular: '' }
      this.dialogVendedor = true
    },

    guardarNuevoVendedor () {
      this.loadingVendedor = true

      const payload = {
        ...this.nuevoVendedor,
        client_id: this.pedido.proveedor_id // Usamos el ID del proveedor seleccionado
      }

      this.$axios.post('vendedores', payload).then(res => {
        const vendedorCreado = res.data

        // 1. Agregarlo a la lista local de vendedores para que aparezca en el select
        this.vendedores.push(vendedorCreado)

        // 2. Preseleccionarlo automáticamente
        this.pedido.vendedor_id = vendedorCreado.id

        this.$alert.success('Vendedor creado y seleccionado')
        this.dialogVendedor = false
      }).catch(err => {
        console.error(err)
        this.$alert.error(err.response?.data?.message || 'Error al crear vendedor')
      }).finally(() => {
        this.loadingVendedor = false
      })
    }
  }
}
</script>

<style scoped>
.cursor-not-allowed {
  cursor: not-allowed;
}

.q-card {
  transition: transform 0.2s;
}

.q-card:hover {
  transform: translateY(-2px);
}
</style>

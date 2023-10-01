<template>
  <q-page class="bg-grey-2 q-pa-xs">
    <div class="row">
      <div class="col-12 col-md-6 bg-white">
        <q-input outlined v-model="search" label="Buscar producto" dense clearable @update:model-value="productsGet" debounce="500">
          <template v-slot:prepend>
            <q-icon name="search" class="cursor-pointer" />
          </template>
        </q-input>
      </div>
      <div class="col-5 col-md-3">
        <q-btn color="black" no-caps flat icon="o_file_download" @click="downloadReport">
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
        <q-btn outline no-caps icon="o_edit" class="full-width" label="Editar categoria" @click="categoryDialog=true" />
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
        <q-select class="bg-white" label="Agencia" dense outlined v-model="$store.agencia_id"
                  :options="agencias" map-options emit-value
                  option-value="id" option-label="nombre"
                  @update:model-value="productsGet"
                  :disable="!($store.user.id=='1')"/>
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
      <q-card style="width: 500px; max-width: 80vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle2 text-bold text-grey">
            {{ productAction === 'create' ? 'Nuevo producto' : productAction === 'ver' ? 'Detalle de producto' : productAction === 'compra' ? 'Comprar producto' : 'Editar producto' }}
          </div>
          <q-space/>
          <q-btn icon="o_highlight_off" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section>
          <q-form v-if="productAction === 'ver'">
            <div class="flex flex-center">
              <q-img :src="product.imagen.includes('http')?product.imagen:`${$url}../images/${product.imagen}`" width="200px">
                <div class="absolute-bottom text-center text-subtitle2" style="padding: 0px 0px;">
                  {{product.nombre}}
                </div>
              </q-img>
            </div>
            <q-card-section class="q-pa-none q-ma-none">
              <div class="text-center text-subtitle2">{{ product.precio }} Bs</div>
              <div :class="product.cantidad<=0?'text-center text-bold text-red':' text-center text-bold'">{{ product.cantidad }} Disponible</div>
              <div :class="product.cantidadAlmacen<=0?'text-center text-bold text-red':' text-center text-bold'">{{ product.cantidadAlmacen }} Almacen</div>
            </q-card-section>
            <q-card flat bordered class="bg-grey-1">
              <q-card-section>
                <div class="row">
                  <div class="col-12 col-md-6 text-subtitle2 text-bold text-grey">
                      <q-icon name="o_qr_code_2" class="text-grey" size="20px" />
                      Codigo de barras
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="text-grey text-caption text-right">{{ product.barra }}</div>
                  </div>
                  <div class="col-12 col-md-6 text-subtitle2 text-bold text-grey">
                      <q-icon name="o_paid" class="text-grey" size="20px" />
                      Precio
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="text-grey text-caption text-right">{{ product.precio }} Bs</div>
                  </div>
                  <div class="col-12 col-md-6 text-subtitle2 text-bold text-grey">
                      <q-icon name="o_shopping_bag" class="text-grey" size="20px" />
                      Costo
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="text-grey text-caption text-right">{{ product.costo }} Bs</div>
                  </div>
                  <div class="col-12 col-md-6 text-subtitle2 text-bold text-grey">
                    <q-icon name="o_local_shipping" class="text-grey" size="20px" />
                    Agencia
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="text-grey text-caption text-right" v-if="product.agencia">{{ product.agencia.nombre }}</div>
                  </div>
                  <div class="col-12 col-md-6 text-subtitle2 text-bold text-grey">
                      <q-icon name="o_shopping_cart" class="text-grey" size="20px" />
                      Categoria
                  </div>
                  <div class="col-12 col-md-6">
                    <div class="text-grey text-caption text-right" v-if="product.category">{{ product.category.name }}</div>
                  </div>
                </div>
              </q-card-section>
            </q-card>
            <div class="row">
              <div class="col-12 text-subtitle2 text-bold">
                Descripción
              </div>
              <div class="col-12 text-grey q-pa-xs">
                {{ product.descripcion}}
              </div>
              <div class="col-6">
                <q-btn :loading="loading" icon="o_delete" label="Eliminar" rounded dense color="red" @click="productDelete" no-caps class="full-width" />
              </div>
              <div class="col-6">
                <q-btn :loading="loading" icon="o_edit" label="Editar" outline rounded dense color="grey" @click="productAction = 'edit'" no-caps class="full-width" />
              </div>
              <div class="col-12">
                <q-btn :loading="loading" icon="o_shop" label="Agregar a compra" rounded dense color="green" @click="compraClick" no-caps class="full-width q-mt-xs" />
              </div>
              <div class="col-12">
                <div class="row">
                  <div class="col-6 q-pa-xs">
                    <q-card flat bordered class="bg-grey-3">
                      <q-card-section class="q-pa-xs text-bold">
                        Sucursal 1 ( <span class="text-blue">{{product.cantidadSucursal1}}</span> )
                        <div class="text-center">
                          <q-btn :loading="loading" size="12px" icon="shopping_cart" label="Agregar" dense color="green" @click="agregarSucursal(1)" no-caps/>
                          <q-btn :loading="loading" size="12px" icon="auto_awesome_motion" label="Mover" dense color="orange" @click="moverSucursal(1)" no-caps/>
                        </div>
                      </q-card-section>
                    </q-card>
                  </div>
                  <div class="col-6 q-pa-xs">
                    <q-card flat bordered class="bg-grey-3">
                      <q-card-section class="q-pa-xs text-bold">
                        Sucursal 2 ( <span class="text-blue">{{product.cantidadSucursal2}}</span> )
                        <div class="text-center">
                          <q-btn :loading="loading" size="12px" icon="shopping_cart" label="Agregar" dense color="green" @click="agregarSucursal(2)" no-caps/>
                          <q-btn :loading="loading" size="12px" icon="auto_awesome_motion" label="Mover" dense color="orange" @click="moverSucursal(1)" no-caps/>
                        </div>
                      </q-card-section>
                    </q-card>
                  </div>
                  <div class="col-6 q-pa-xs">
                    <q-card flat bordered class="bg-grey-3">
                      <q-card-section class="q-pa-xs text-bold">
                        Sucursal 3 ( <span class="text-blue">{{product.cantidadSucursal3}}</span> )
                        <div class="text-center">
                          <q-btn :loading="loading" size="12px" icon="shopping_cart" label="Agregar" dense color="green" @click="agregarSucursal(3)" no-caps/>
                          <q-btn :loading="loading" size="12px" icon="auto_awesome_motion" label="Mover" dense color="orange" @click="moverSucursal(1)" no-caps/>
                        </div>
                      </q-card-section>
                    </q-card>
                  </div>
                  <div class="col-6 q-pa-xs">
                    <q-card flat bordered class="bg-grey-3">
                      <q-card-section class="q-pa-xs text-bold">
                        Sucursal 4 ( <span class="text-blue">{{product.cantidadSucursal4}}</span> )
                        <div class="text-center">
                          <q-btn :loading="loading" size="12px" icon="shopping_cart" label="Agregar" dense color="green" @click="agregarSucursal(4)" no-caps/>
                          <q-btn :loading="loading" size="12px" icon="auto_awesome_motion" label="Mover" dense color="orange" @click="moverSucursal(1)" no-caps/>
                        </div>
                      </q-card-section>
                    </q-card>
                  </div>
                </div>
              </div>
            </div>
          </q-form>
          <q-form @submit="productSave" v-if="productAction === 'create' || productAction === 'edit'">
            <div class="flex flex-center">
              <q-uploader
                accept=".jpg, .png"
                multiple
                auto-upload
                label="Arrastra una imagen o haz click para seleccionar"
                @uploading="uploadingFn"
                @failed="errorFn"
                ref="uploader"
                max-files="1"
                auto-expand
                :url="`${$url}upload/${product.id}/fileCreate`"
                stack-label="upload image"/>
            </div>
<!--            <q-input outlined v-model="product.imagen" label="Imagen" dense hint="Selecciona una imagen" />-->
            <div class="text-grey text-caption">Te recomendamos que la imagen tenga un tamaño de 500 x 500 px en formato PNG y pese máximo 2MB.</div>
            <q-input outlined v-model="product.nombre" label="Nombre del producto*" dense hint="Recuerda, este debe ser único en tu inventario" :rules="[val => !!val || 'Este campo es requerido']" />
            <q-input outlined v-model="product.barra" label="Código de barras" dense hint="Escríbelo o escanéalo" />
            <q-input outlined v-model="product.cantidad" label="Cantidad" input-class="text-center" dense hint="">
              <template v-slot:append>
                <q-icon name="o_add_circle_outline" @click="cantidadMore" class="cursor-pointer"/>
              </template>
              <template v-slot:prepend>
                <q-icon name="o_remove_circle_outline" @click="cantidadMinus" class="cursor-pointer"/>
              </template>
            </q-input>
            <q-input outlined type="number" step="0.01" v-model="product.costo" label="Costo" dense hint="Valor que pagas al proveedor por el producto"/>
            <q-input outlined type="number" step="0.01" v-model="product.precio" label="Precio*" dense hint="Valor que le cobras a tus clientes por el producto" :rules="[val => !!val || 'Este campo es requerido']"/>
            <q-input outlined type="number" step="0.01" v-model="product.precioAntes" label="Precio antes" dense hint="Valor que le cobrabas a tus clientes por el producto ANTES de la oferta"/>
            <q-select class="bg-white" label="Unidad" dense outlined v-model="product.unidad" :options="unidades" hint="Selecciona una unidad"/>
            <q-select class="bg-white" emit-value map-options label="Categoria" dense outlined v-model="product.category_id" option-value="id" option-label="name" :options="categories" hint="Selecciona una categoria"/>
            <q-input type="textarea" outlined v-model="product.descripcion" label="Descripción" dense hint="Agrega una descripción del producto"/>
<!--            <q-select class="bg-white" emit-value map-options label="Agencia" dense outlined-->
<!--                      v-model="product.agencia_id" option-value="id" option-label="nombre" :options="agencias"-->
<!--                      hint="Selecciona una agencia" :rules="[val => !!val || 'Este campo es requerido']"-->
<!--                      :disable="!($store.user.id=='1')"-->
<!--            />-->
            <div class="text-center borderRoundGrey">
              <q-toggle :label="product.activo" color="green" false-value="INACTIVO" true-value="ACTIVO" v-model="product.activo" class="text-grey-9 text-bold" />
            </div>
            <q-btn class="full-width" rounded
                   :color="!product.nombre || !product.precio ? 'grey' : 'green'"
                   :disable="!product.nombre || !product.precio"
                   label="Guardar" no-caps type="submit" :loading="loading"/>
          </q-form>
          <q-form v-if="productAction === 'compra'" @submit="compraSave">
            <q-input outlined v-model="compra.lote" label="Lote*" dense hint="Escribe el lote del producto" required />
            <q-input outlined v-model="compra.price" label="Precio" dense hint="Valor que le pagaste al proveedor por el producto" type="number" required step="0.01" />
            <q-input outlined v-model="compra.quantity" label="Cantidad" input-class="text-center" dense hint="">
              <template v-slot:append>
                <q-icon name="o_add_circle_outline" @click="compraMore" class="cursor-pointer"/>
              </template>
              <template v-slot:prepend>
                <q-icon name="o_remove_circle_outline" @click="compraMinus" class="cursor-pointer"/>
              </template>
            </q-input>
            <q-input outlined v-model="compraTotal" readonly label="Total" dense hint="Valor total de la compra" />
            <q-input outlined v-model="compra.dateExpiry" label="Fecha de vencimiento" dense hint="Fecha de vencimiento del producto" type="date" />
            <q-btn class="full-width" rounded
                   :color="!compra.lote ? 'grey' : 'green'"
                   :disable="!compra.lote"
                   label="Guardar" no-caps type="submit" :loading="loading"/>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <q-dialog v-model="categoryDialog" position="right" maximized>
      <q-card style="width: 500px; max-width: 80vw;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle2 text-bold text-grey">
            Editar categoria
          </div>
          <q-space/>
          <q-btn icon="o_highlight_off" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section>
          <q-table flat bordered :rows="categoriesTable" hide-header
                   :columns="categoriesTableColumns" :rows-per-page-options="[0]" >
            <template v-slot:body="props">
              <q-tr :props="props">
                <q-td :props="props" key="name">
                  {{props.row.name}}
                </q-td>
                <q-td :props="props" key="actions">
                  <q-btn flat dense round color="grey" icon="o_edit" class="cursor-pointer" @click="categoryEdit(props.row)"/>
                  <q-btn flat dense round color="red" icon="o_delete" class="cursor-pointer" @click="categoryDelete(props.row)"/>
                </q-td>
              </q-tr>
            </template>
          </q-table>
        </q-card-section>
      </q-card>
    </q-dialog>
    <q-dialog v-model="dialogMover">
      <q-card>
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle2 text-bold text-grey">
            Mover producto del sucursal {{delSucursal}} a:
          </div>
          <q-space/>
          <q-btn icon="o_highlight_off" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section>
          <q-form @submit="moverProducto">
          <q-select class="bg-white" emit-value map-options label="Sucursal" dense outlined v-model="lugar" :options="lugares" hint="Selecciona una sucursal"/>
          <q-input outlined type="number" step="0.01" v-model="cantidad" label="Cantidad" dense hint="Cantidad a mover"
                    :rules="[val => !!val || 'Este campo es requerido']"/>
          <q-btn class="full-width" rounded
                 :color="!cantidad ? 'grey' : 'green'"
                 :disable="!cantidad"
                 label="Guardar" no-caps type="submit" :loading="loading"/>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <div id="myElement" class="hidden"></div>
  </q-page>
</template>

<script>
import moment from 'moment'

export default {
  name: 'ProductosPage',
  data () {
    return {
      dialogMover: false,
      lugares: [
        'Almacen',
        'Sucursal 1',
        'Sucursal 2',
        'Sucursal 3',
        'Sucursal 4'
      ],
      delSucursal: {},
      cantidad: 0,
      lugar: 'Almacen',
      current_page: 1,
      search: '',
      unidades: ['UNIDAD', 'PAQUETE', 'SOBRE', 'BOLSA', 'FRASCO', 'SOBRES', 'CAPSULAS', 'PASTILLA', 'TABLETAS', 'OTROS'],
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
        { label: 'Orden alfabetico', value: 'nombre asc' }
      ],
      costoTotalProducts: 0
    }
  },
  created () {
    this.categoriesGet()
    this.agenciasGet()
    this.productsGet()
  },
  methods: {
    moverProducto () {
      this.loading = true
      this.$axios.post('moverProducto', {
        id: this.product.id,
        lugar: this.lugar,
        cantidad: this.cantidad,
        delSucursal: this.delSucursal
      }).then(res => {
        this.loading = false
        this.$alert.success('Producto movido correctamente')
        this.productsGet()
        this.product = res.data
        this.dialogMover = false
      }).catch(err => {
        this.loading = false
        this.$alert.error(err.response.data.message)
      })
    },
    moverSucursal (delSucursal) {
      this.delSucursal = delSucursal
      this.dialogMover = true
      this.lugar = 'Almacen'
      this.cantidad = 0
    },
    agregarSucursal (sucursal) {
      console.log(this.product.cantidadAlmacen)
      if (this.product.cantidadAlmacen <= 0) {
        this.$alert.error('No puedes agregar un producto en sucursal si no tienes en almacen')
        return
      }
      this.$q.dialog({
        title: 'Selecciona la cantidad',
        message: 'Ingresa la cantidad que deseas agregar',
        prompt: {
          model: 1,
          type: 'number'
        }
      }).onOk((data) => {
        this.loading = true
        this.$axios.post('agregarSucursal', {
          sucursal,
          id: this.product.id,
          cantidad: data
        }).then(res => {
          this.loading = false
          this.$alert.success('Producto agregado correctamente')
          this.productsGet()
          this.product = res.data
        }).catch(err => {
          this.loading = false
          this.$alert.error(err.response.data.message)
        })
      })
    },
    compraSave () {
      this.loading = true
      this.$axios.post('buys', this.compra).then(response => {
        this.loading = false
        this.$alert.success('Compra guardada')
        this.productDialog = false
        this.productsGet()
        this.$imprimir.reciboCompra(response.data)
      }).catch(error => {
        this.loading = false
        this.$alert.error(error.response.data.message)
      })
    },
    compraMore () {
      this.compra.quantity++
    },
    compraMinus () {
      if (this.compra.quantity > 1) {
        this.compra.quantity--
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
    compraClick () {
      this.productAction = 'compra'
      this.compra.product_id = this.product.id
      this.compra.lote = ''
      this.compra.price = 1
      this.compra.quantity = 1
      this.compra.dateExpiry = this.dateMoreMonth(6)
    },
    dateMoreMonth (month) {
      const date = moment()
      date.month(date.month() + month)
      return date.format('YYYY-MM-DD')
    },
    productDelete () {
      this.$q.dialog({
        title: 'Eliminar producto',
        message: `¿Estás seguro de eliminar el producto ${this.product.nombre}?`,
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.loading = true
        this.$axios.delete(`products/${this.product.id}`)
          .then(response => {
            this.productDialog = false
            this.loading = false
            this.productsGet()
            this.$alert.success(`El producto ${this.product.nombre} fue eliminado correctamente`)
          })
          .catch(error => {
            this.loading = false
            this.$alert.error(error.response.data.message)
          })
      })
    },
    downloadReport () {
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
          content: this.products
        }
      ]
      this.$excel.export(data)
    },
    categoryDelete (category) {
      this.$q.dialog({
        title: 'Eliminar categoria',
        message: '¿Estás seguro de eliminar esta categoria?',
        cancel: true,
        persistent: true
      }).onOk(() => {
        this.$axios.delete(`categories/${category.id}`).then(res => {
          this.categoriesGet()
          this.$alert.success('Categoria eliminada correctamente')
        }).catch(err => {
          this.$alert.error('No se puede eliminar la categoria porque tiene productos asociados')
          console.log(err)
        })
      })
    },
    categoryEdit (category) {
      this.categorySelected = category
      this.categoryDialog = false
      this.$q.dialog({
        title: 'Editar categoria',
        message: 'Ingresa el nuevo nombre de la categoria',
        prompt: {
          model: category.name,
          type: 'text'
        },
        cancel: true,
        persistent: true
      }).onOk(data => {
        this.$axios.put(`categories/${category.id}`, { name: data }).then(res => {
          this.categoriesGet()
          this.$alert.success('Categoria editada correctamente')
        }).catch(err => {
          console.log(err)
          this.$alert.error('No se pudo editar la categoria')
        })
      })
    },
    productSave () {
      this.loading = true
      if (this.productAction === 'create') {
        this.$axios.post('products', this.product).then(res => {
          this.loading = false
          this.productDialog = false
          this.productsGet()
        }).catch(err => {
          this.loading = false
          console.log(err)
          this.$alert.error(err.response.data.message)
        })
      } else {
        this.$axios.put(`products/${this.product.id}`, this.product).then(res => {
          this.loading = false
          this.productDialog = false
          this.productsGet()
        }).catch(err => {
          this.loading = false
          console.log(err)
        })
      }
    },
    productsGet () {
      this.loading = true
      this.$axios.get(`products?page=${this.current_page}&search=${this.search}&order=${this.order}&category=${this.category}&agencia=${this.$store.agencia_id}`).then(res => {
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
    cantidadMinus () {
      if (this.product.cantidad > 0) {
        this.product.cantidad--
      }
    },
    cantidadMore () {
      this.product.cantidad++
    },
    uploadingFn (e) {
      e.xhr.onload = () => {
        if (e.xhr.readyState === e.xhr.DONE) {
          if (e.xhr.status === 200) {
            // this.dialogPhoto=false
            this.product.imagen = e.xhr.response
          }
        }
      }
    },
    errorFn (err) {
      console.log(err)
      this.$alert.error('Error al subir la imagen, intente nuevamente el nombre no debe contener espacios o ñ')
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
        agencia_id: this.$store.agencia_id
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

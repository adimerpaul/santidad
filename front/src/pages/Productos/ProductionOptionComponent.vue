<template>
  <q-card style="width: 500px; max-width: 80vw;">
    <q-card-section class="row items-center q-pb-none">
      <div class="text-subtitle2 text-bold text-grey">
        {{ productAction === 'create' ? 'Nuevo producto' : productAction === 'ver' ? 'Detalle de producto' : productAction === 'compra' ? 'Comprar producto' : 'Editar producto' }}
<!--        <pre>{{product}}</pre>-->
      </div>
      <q-space/>
      <q-btn icon="o_highlight_off" flat round dense v-close-popup />
    </q-card-section>
    <q-card-section>
      <q-form v-if="productAction === 'ver'">
        <DetailProducts :product="product" />
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
          <div class="col-6">
            <q-btn :loading="loading" icon="o_shop" label="Agregar a compra" rounded dense color="green" @click="compraClick" no-caps class="full-width q-mt-xs" />
          </div>
          <div class="col-6">
            <q-btn :loading="loading" icon="history_edu" label="Historial" rounded dense color="blue"
                   @click="historySucursalProduct(product)" no-caps class="full-width q-mt-xs" v-if="$store.user.id=='1'"/>
          </div>
          <div class="col-12">
            <div class="row">
              <div class="col-6 q-pa-xs" v-for="(s,i) in sucursales" :key="i">
                <q-card flat bordered class="bg-grey-3">
                  <q-card-section class="q-pa-xs text-bold">
                    (<span class="text-blue">{{product['cantidadSucursal'+(i+1)]}}</span> )
                    <q-btn flat rounded size="12px" dense color="indigo" icon="history_edu" @click="historySucursal(s)" no-caps label="Historial"
                           v-if="$store.user.id=='1'"
                           :loading="loading"/>
                    <div class="text-lowercase">{{ s.nombre }}</div>
                    <div class="text-center">
                    </div>
                  </q-card-section>
                </q-card>
              </div>
<!--              <div class="col-6 q-pa-xs">-->
<!--                <q-card flat bordered class="bg-grey-3">-->
<!--                  <q-card-section class="q-pa-xs text-bold">-->
<!--                    Sucursal 1 ( <span class="text-blue">{{product.cantidadSucursal1}}</span> )-->
<!--                    <div class="text-center">-->
<!--                      <q-btn :loading="loading" size="12px" icon="shopping_cart" label="Agregar" dense color="green" @click="agregarSucursal(1)" no-caps/>-->
<!--                      <q-btn :loading="loading" size="12px" icon="auto_awesome_motion" label="Mover" dense color="orange" @click="moverSucursal(1)" no-caps/>-->
<!--                    </div>-->
<!--                  </q-card-section>-->
<!--                </q-card>-->
<!--              </div>-->
<!--              <div class="col-6 q-pa-xs">-->
<!--                <q-card flat bordered class="bg-grey-3">-->
<!--                  <q-card-section class="q-pa-xs text-bold">-->
<!--                    Sucursal 2 ( <span class="text-blue">{{product.cantidadSucursal2}}</span> )-->
<!--                    <div class="text-center">-->
<!--                      <q-btn :loading="loading" size="12px" icon="shopping_cart" label="Agregar" dense color="green" @click="agregarSucursal(2)" no-caps/>-->
<!--                      <q-btn :loading="loading" size="12px" icon="auto_awesome_motion" label="Mover" dense color="orange" @click="moverSucursal(2)" no-caps/>-->
<!--                    </div>-->
<!--                  </q-card-section>-->
<!--                </q-card>-->
<!--              </div>-->
<!--              <div class="col-6 q-pa-xs">-->
<!--                <q-card flat bordered class="bg-grey-3">-->
<!--                  <q-card-section class="q-pa-xs text-bold">-->
<!--                    Sucursal 3 ( <span class="text-blue">{{product.cantidadSucursal3}}</span> )-->
<!--                    <div class="text-center">-->
<!--                      <q-btn :loading="loading" size="12px" icon="shopping_cart" label="Agregar" dense color="green" @click="agregarSucursal(3)" no-caps/>-->
<!--                      <q-btn :loading="loading" size="12px" icon="auto_awesome_motion" label="Mover" dense color="orange" @click="moverSucursal(3)" no-caps/>-->
<!--                    </div>-->
<!--                  </q-card-section>-->
<!--                </q-card>-->
<!--              </div>-->
<!--              <div class="col-6 q-pa-xs">-->
<!--                <q-card flat bordered class="bg-grey-3">-->
<!--                  <q-card-section class="q-pa-xs text-bold">-->
<!--                    Sucursal 4 ( <span class="text-blue">{{product.cantidadSucursal4}}</span> )-->
<!--                    <div class="text-center">-->
<!--                      <q-btn :loading="loading" size="12px" icon="shopping_cart" label="Agregar" dense color="green" @click="agregarSucursal(4)" no-caps/>-->
<!--                      <q-btn :loading="loading" size="12px" icon="auto_awesome_motion" label="Mover" dense color="orange" @click="moverSucursal(4)" no-caps/>-->
<!--                    </div>-->
<!--                  </q-card-section>-->
<!--                </q-card>-->
<!--              </div>-->
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
        <div class="text-grey text-caption">Te recomendamos que la imagen tenga un tamaño de 500 x 500 px en formato PNG y pese máximo 2MB.</div>
        <q-input label-color="black" outlined v-model="product.nombre" label="Nombre del producto*" dense
                 hint="Recuerda, este debe ser único en tu inventario" :rules="[val => !!val || 'Este campo es requerido']"
                 class="uppercase"
        />
        <q-input label-color="black" outlined v-model="product.barra" label="Código de barras" dense hint="Escríbelo o escanéalo" />
        <q-input
          label-color="black"
          outlined
          type="number"
          step="0.01"
          v-model="product.costo"
          label="Costo"
          dense
          hint="Valor que pagas al proveedor por el producto"
          :disable="!isAdmin"
        />
        <div class="row">
          <div class="col-12 col-md-5">
            <q-input
        label-color="black"
        outlined
        type="number"
        step="0.01"
        v-model="product.precio"
        label="Precio*"
        dense
        hint="Valor que le cobras a tus clientes por el producto"
        :disable="!isAdmin"
      />
          </div>
          <div class="col-12 col-md-3">
<!--            <q-input label-color="black" outlined type="number" step="0.01" v-model="product.precioAntes" label="Precio antes" dense hint="Valor que le cobrabas a tus clientes por el producto ANTES de la oferta"/>-->
          <label for="" class="text-red text-caption text-bold">Precio venta</label><br>
          <span class="text-bold">{{precioVenta}}</span>
          </div>
          <div class="col-12 col-md-4" style="line-height:1em;">
            <q-input
            label-color="black"
            outlined
            type="number"
            step="0.01"
            v-model="product.porcentaje"
            label="%"
            dense
            hint="Porcentaje de descuento"
            :disable="!isAdmin"
          />
            <!--            <label for="" class="text-red text-caption text-bold">Porcentaje</label><br>-->
<!--            <span class="text-bold">{{porcentaje}}%</span>-->
          </div>
        </div>
        <q-select class="bg-white" label="Unidad" dense outlined v-model="product.unidad" :options="unidades"
                  hint="Selecciona una unidad" use-input input-debounce="0" @filter="filterUnid">
          <template v-slot:after>
            <q-btn icon="add_circle_outline" flat round dense color="green" @click="addUnit" />
          </template>
          <template v-slot:no-option>
            <q-item>
              <q-item-section class="text-grey">
                No results
              </q-item-section>
            </q-item>
          </template>
        </q-select>
        <q-input label-color="black" outlined v-model="product.registroSanitario" label="Registro sanitario" dense hint="Escribe el registro sanitario"/>
        <q-input label-color="black" outlined v-model="product.paisOrigen" label="Pais de origen" dense hint="Escribe el pais de origen" class="uppercase"/>
        <q-input label-color="black" outlined v-model="product.composicion" label="Principio activo" dense hint="Escribe el principio activo"/>
        <q-input label-color="black" outlined v-model="product.marca" label="Laboratorio" dense hint="Escribe la marca" class="uppercase"/>
        <q-input label-color="black" outlined v-model="product.distribuidora" label="Distribuidora" dense hint="Escribe la distribuidora"/>
        <q-select class="bg-white" emit-value map-options label="Categoria" dense outlined v-model="product.category_id" option-value="id" option-label="name" :options="categories" hint="Selecciona una categoria"/>
        <q-select class="bg-white" emit-value map-options label="Subcategoria" dense outlined v-model="product.subcategory_id" option-value="id" option-label="nameComplete" :options="subcategories" hint="Selecciona una categoria"/>
        <q-input label-color="black" type="textarea" outlined v-model="product.descripcion" label="Descripción" dense hint="Agrega una descripción del producto"/>
        <div class="text-center borderRoundGrey">
          <q-toggle :label="product.activo" color="green" false-value="INACTIVO" true-value="ACTIVO" v-model="product.activo" class="text-grey-9 text-bold" />
        </div>
        <q-btn class="full-width" rounded
               :color="!product.nombre ? 'grey' : 'green'"
               :disable="!product.nombre"
               :label="productAction === 'create' ? 'Guardar' : 'Editar'"
               no-caps
               type="submit"
               :loading="loading"/>
        <q-btn v-if="productAction === 'edit'" class="full-width q-mt-xs" rounded
               outline
               :color="!product.nombre || !product.precio ? 'grey' : 'green'"
               :disable="!product.nombre || !product.precio"
               label="Duplicar" no-caps  :loading="loading" @click="duplicateProduct"/>
      </q-form>
      <q-form v-if="productAction === 'compra'" @submit="compraSave">
        <q-input label-color="black" outlined v-model="compra.lote" label="Lote*" dense hint="Escribe el lote del producto" required />
        <q-input label-color="black" outlined v-model="compra.price" label="Precio" dense hint="Valor que le pagaste al proveedor por el producto" type="number" required step="0.01" />
        <q-input label-color="black" outlined v-model="compra.quantity" label="Cantidad" input-class="text-center" dense hint="">
          <template v-slot:append>
            <q-icon name="o_add_circle_outline" @click="compraMore" class="cursor-pointer"/>
          </template>
          <template v-slot:prepend>
            <q-icon name="o_remove_circle_outline" @click="compraMinus" class="cursor-pointer"/>
          </template>
        </q-input>
        <q-input label-color="black" outlined v-model="compraTotal" readonly label="Total" dense hint="Valor total de la compra" />
        <q-input label-color="black" outlined v-model="compra.dateExpiry" label="Fecha de vencimiento" dense hint="Fecha de vencimiento del producto" type="date" />
        <q-btn class="full-width" rounded
               :color="!compra.lote ? 'grey' : 'green'"
               :disable="!compra.lote"
               label="Guardar" no-caps type="submit" :loading="loading"/>
      </q-form>
    </q-card-section>
    <q-dialog v-model="dialogMover">
      <q-card>
        <q-card-section class="q-pb-none">
          <div class="text-right">
            <q-btn icon="o_highlight_off" flat round dense v-close-popup />
          </div>
          <div class="text-subtitle2 text-bold">
            Mover producto del sucursal {{delSucursal.nombre}} a:
          </div>
        </q-card-section>
        <q-card-section>
          <q-form @submit="moverProducto">
            <q-select class="bg-white" emit-value map-options label="Sucursal" dense outlined v-model="lugar" :options="sucursalesName" hint="Selecciona una sucursal"/>
            <q-input label-color="black" outlined type="number" step="0.01" v-model="cantidad" label="Cantidad" dense hint="Cantidad a mover"
                     :rules="[
                       val => !!val || 'Este campo es requerido',
                        val => val <= product['cantidadSucursal'+delSucursal.id] || 'No puedes mover más de lo que tienes en la sucursal'
                       ]"/>
            <q-input v-model="fecha_entrega_vencimiento" label="Fecha vencimiento" dense outlined type="date" class="bg-white"/>
            <q-btn class="full-width" rounded
                   :color="!cantidad ? 'grey' : 'green'"
                   :disable="!cantidad"
                   label="Guardar" no-caps type="submit" :loading="loading"/>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <q-dialog v-model="dialogAlmacenAgencia">
      <q-card>
        <q-card-section class="q-pb-none">
          <div class="text-right">
            <q-btn icon="o_highlight_off" flat round dense v-close-popup />
          </div>
          <div class="text-subtitle2 text-bold">
            Mover producto del almacen a:
          </div>
        </q-card-section>
        <q-card-section>
          <q-form @submit="moverProductoAlmacen">
<!--            <q-select class="bg-white" emit-value map-options label="Sucursal" dense outlined v-model="lugar" :options="sucursalesName" hint="Selecciona una sucursal"/>-->
            <q-input label-color="black" outlined type="number" step="0.01" v-model="cantidad" label="Cantidad" dense hint="Cantidad a mover"
                     :rules="[val => !!val || 'Este campo es requerido']"/>
            <q-input v-model="fecha_entrega_vencimiento" label="Fecha vencimiento" dense outlined type="date" class="bg-white"/>
            <q-btn class="full-width" rounded
                   :color="!cantidad ? 'grey' : 'green'"
                   :disable="!cantidad"
                   label="Guardar" no-caps type="submit" :loading="loading"/>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <q-dialog v-model="historyDialog">
      <q-card style="width: 900px; max-width: 80vw;">
        <q-card-section class="q-pb-none">
          <div class="text-right">
            <q-btn icon="o_highlight_off" flat round dense v-close-popup />
            </div>
          <div class="text-subtitle2">
            Historial de movimientos <span class="text-bold">{{product.nombre}}</span> de <span class="text-bold text-red">{{sucursalShow.nombre}}</span>
          </div>
        </q-card-section>
        <q-card-section>
          <q-markup-table dense wrap-cells class="pa-ma-none">
            <thead class="pa-ma-none">
            <tr class="pa-ma-none">
              <th class="pa-ma-none">Fecha</th>
              <th class="pa-ma-none">Usuario</th>
              <th class="pa-ma-none">Origen</th>
              <th class="pa-ma-none">Destino</th>
              <th class="pa-ma-none">Cantidad</th>
              <th class="pa-ma-none">Fecha entrega</th>
            </tr>
            </thead>
            <tbody class="pa-ma-none">
            <tr v-for="(h,i) in histories" :key="i" class="pa-ma-none">
              <td class="pa-ma-none text-caption">{{$filters.dateDmYHis(h.fecha+' '+h.hora)}}</td>
              <td class="pa-ma-none text-caption">{{h.user.name}}</td>
              <td class="pa-ma-none text-caption">{{h.agencia_origen ? h.agencia_origen.nombre : 'Almacen'}}</td>
              <td class="pa-ma-none text-caption">{{h.agencia_destino ? h.agencia_destino.nombre : 'Almacen'}}</td>
              <td class="pa-ma-none text-caption">{{h.cantidad}}</td>
              <td class="pa-ma-none text-caption">{{h.fecha_entrega_vencimiento}}</td>
            </tr>
            </tbody>
          </q-markup-table>
<!--          <pre>{{histories}}</pre>-->
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-card>
</template>
<script>
import DetailProducts from 'pages/Productos/DetailProducts.vue'
import moment from 'moment/moment'
export default {
  name: 'ProductOptionComponentPage',
  components: {
    DetailProducts
  },
  props: {
    subcategories: {
      type: Array,
      required: true
    },
    productData: {
      type: Object,
      required: true
    },
    productActionData: {
      type: String,
      required: true
    },
    categories: {
      type: Array,
      required: true
    },
    unidadesData: {
      type: Array,
      required: true
    },
    agencias: {
      type: Array,
      required: true
    }
  },
  data () {
    return {
      lugares: [
        'Almacen',
        'Sucursal 1',
        'Sucursal 2',
        'Sucursal 3',
        'Sucursal 4'
      ],
      sucursales: [],
      sucursalesName: [],
      delSucursal: {},
      dialogAlmacenAgencia: false,
      sucursalEnvio: {},
      cantidad: 0,
      lugar: 'Almacen',
      product: {},
      productAction: '',
      unidades: [],
      unidadesAll: [],
      dialogMover: false,
      compra: {
        lote: '',
        price: 0,
        quantity: 1,
        dateExpiry: ''
      },
      compraTotal: 0,
      loading: false,
      fecha_entrega_vencimiento: '',
      historyDialog: false,
      histories: [],
      sucursalShow: {}
    }
  },
  mounted () {
    this.getSucursales()
    this.product = this.productData
    this.productAction = this.productActionData
    this.unidades = this.unidadesData
    this.unidadesAll = this.unidadesData
    this.compra = {
      lote: '',
      price: 0,
      quantity: 1,
      dateExpiry: ''
    }
    this.compraTotal = 0
  },
  methods: {
    historySucursalProduct (product) {
      this.loading = true
      this.sucursalShow = { nombre: 'Todo' }
      this.$axios.get('historySucursalProduct', {
        params: {
          id: product.id
        }
      }).then(res => {
        this.histories = res.data
        this.historyDialog = true
      }).finally(() => {
        this.loading = false
      })
    },
    historySucursal (sucursal) {
      this.loading = true
      this.sucursalShow = sucursal
      this.$axios.get('historySucursal', {
        params: {
          id: this.product.id,
          sucursal: sucursal.id
        }
      }).then(res => {
        this.histories = res.data
        this.historyDialog = true
      }).finally(() => {
        this.loading = false
      })
    },
    getSucursales () {
      this.sucursalesName = []
      this.sucursalesName.push('Almacen')
      this.$axios.get('agencias').then(res => {
        this.sucursales = res.data
        this.sucursales.forEach(sucursal => {
          this.sucursalesName.push(sucursal.nombre)
        })
        console.log(this.sucursalesName)
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
    addUnit () {
      this.$q.dialog({
        title: 'Crear unidad',
        message: 'Ingresa el nombre de la unidad',
        prompt: {
          model: '',
          type: 'text'
        },
        cancel: true,
        persistent: true
      }).onOk(data => {
        this.$axios.post('unids', { nombre: data }).then(res => {
          this.unitsGet()
          this.$alert.success('Unidad creada correctamente')
        }).catch(err => {
          console.log(err)
          this.$alert.error('No se pudo crear la unidad')
        })
      })
    },
    filterUnid (val, update) {
      if (val === '') {
        update(() => {
          this.unidades = this.unidadesAll
        })
        return
      }
      update(() => {
        const needle = val.toLowerCase()
        this.unidades = this.unidadesAll.filter(v => v.toLowerCase().indexOf(needle) > -1)
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
            // this.productsGet()
            this.$alert.success(`El producto ${this.product.nombre} fue eliminado correctamente`)
            this.$emit('productsGet')
            this.$emit('close')
          })
          .catch(error => {
            this.loading = false
            this.$alert.error(error.response.data.message)
          })
      })
    },
    compraSave () {
      this.loading = true
      this.$axios.post('buys', this.compra).then(response => {
        this.loading = false
        this.$alert.success('Compra guardada')
        this.productDialog = false
        // this.productsGet()
        this.$imprimir.reciboCompra(response.data)
        this.$emit('productsGet')
        this.$emit('close')
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
    duplicateProduct () {
      this.$q.dialog({
        title: 'Duplicar producto',
        message: 'Seguro que deseas duplicar el producto?',
        cancel: true
      }).onOk(data => {
        this.loading = true
        this.$axios.post('duplicateProduct', this.product).then(res => {
          this.loading = false
          this.$alert.success('Producto duplicado correctamente')
          // this.productsGet()
          this.$emit('productsGet')
          this.$emit('close')
          this.product = res.data
          this.productDialog = false
        }).finally(() => {
          this.loading = false
        })
        //   .catch(err => {
        //   this.loading = false
        //   this.$alert.error(err.response.data.message)
        // })
      })
    },
    productSave () {
      this.loading = true
      if (this.productAction === 'create') {
        this.$axios.post('products', this.product).then(res => {
          this.loading = false
          this.productDialog = false
          // this.productsGet()
          this.$alert.success('Producto creado correctamente')
          this.$emit('productsGet')
          this.$emit('close')
        }).catch(err => {
          this.loading = false
          console.log(err)
          this.$alert.error(err.response.data.message)
        })
      } else {
        this.$axios.put(`products/${this.product.id}`, this.product).then(res => {
          this.loading = false
          this.productDialog = false
          // this.productsGet()
          this.$alert.success('Producto editado correctamente')
          this.$emit('productsGet')
          this.$emit('close')
        }).catch(err => {
          this.loading = false
          console.log(err)
        })
      }
    }
  },
  computed: {
    precioVenta () {
      const precio = this.product.precio == null ? 0 : this.product.precio
      const porcentaje = this.product.porcentaje == null ? 0 : this.product.porcentaje
      const precioVenta = (precio - (precio * porcentaje / 100)).toFixed(2)
      return precioVenta
    },
    porcentaje () {
      const precio = this.product.precio == null ? 0 : this.product.precio
      // console.log('precio', precio)
      const precioAntes = this.product.precioAntes == null ? 0 : this.product.precioAntes
      // console.log('precioAntes', precioAntes)
      const porcentaje = ((precioAntes - precio) / precioAntes * 100).toFixed(2)
      // console.log('porcentaje', porcentaje)
      if (porcentaje === '-Infinity') {
        return 0
      }
      return porcentaje
    //   ((product.precioAntes-product.precio)/product.precioAntes*100).toFixed(2)
    },
    isAdmin () {
      return this.$store.user && Number(this.$store.user.id) === 1
    }
  }
}
</script>
<style>
.pa-ma-none {
  padding: 0 !important;
  margin: 0 !important;
}
</style>

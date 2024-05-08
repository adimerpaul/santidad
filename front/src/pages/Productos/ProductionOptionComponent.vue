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
          <div class="col-12">
            <q-btn :loading="loading" icon="o_shop" label="Agregar a compra" rounded dense color="green" @click="compraClick" no-caps class="full-width q-mt-xs" />
          </div>
          <div class="col-12">
            <div class="row">
              <div class="col-6 q-pa-xs" v-for="(s,i) in sucursales" :key="i">
                <q-card flat bordered class="bg-grey-3">
                  <q-card-section class="q-pa-xs text-bold">
                    (<span class="text-blue">{{product['cantidadSucursal'+(i+1)]}}</span> )
                    <div class="text-lowercase">{{ s.nombre }}</div>
                    <div class="text-center">
                      <q-btn :loading="loading" size="12px" icon="shopping_cart" label="Agregar" dense color="green" @click="agregarSucursal(s)" no-caps/>
                      <q-btn :loading="loading" size="12px" icon="auto_awesome_motion" label="Mover" dense color="orange" @click="moverSucursal(s)" no-caps/>
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
        <!--            <q-input label-color="black" outlined v-model="product.imagen" label="Imagen" dense hint="Selecciona una imagen" />-->
        <div class="text-grey text-caption">Te recomendamos que la imagen tenga un tamaño de 500 x 500 px en formato PNG y pese máximo 2MB.</div>
        <q-input label-color="black" outlined v-model="product.nombre" label="Nombre del producto*" dense
                 hint="Recuerda, este debe ser único en tu inventario" :rules="[val => !!val || 'Este campo es requerido']"
                 class="uppercase"
        />
        <q-input label-color="black" outlined v-model="product.barra" label="Código de barras" dense hint="Escríbelo o escanéalo" />
<!--        <q-input label-color="black" outlined v-model="product.cantidad" label="Cantidad" input-class="text-center" dense hint="">-->
<!--          <template v-slot:append>-->
<!--            <q-icon name="o_add_circle_outline" @click="cantidadMore" class="cursor-pointer"/>-->
<!--          </template>-->
<!--          <template v-slot:prepend>-->
<!--            <q-icon name="o_remove_circle_outline" @click="cantidadMinus" class="cursor-pointer"/>-->
<!--          </template>-->
<!--        </q-input>-->
        <q-input label-color="black" outlined type="number" step="0.01" v-model="product.costo" label="Costo" dense hint="Valor que pagas al proveedor por el producto"/>
        <q-input label-color="black" outlined type="number" step="0.01" v-model="product.precio" label="Precio*" dense hint="Valor que le cobras a tus clientes por el producto" :rules="[val => !!val || 'Este campo es requerido']"/>
        <q-input label-color="black" outlined type="number" step="0.01" v-model="product.precioAntes" label="Precio antes" dense hint="Valor que le cobrabas a tus clientes por el producto ANTES de la oferta"/>
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
        <!--            <q-input label-color="black" outlined v-model="product.nombreComun" label="Nombre comun" dense hint="Escribe el nombre comun"/>-->
        <q-input label-color="black" outlined v-model="product.composicion" label="Principio activo" dense hint="Escribe el principio activo"/>
        <q-input label-color="black" outlined v-model="product.marca" label="Laboratorio" dense hint="Escribe la marca" class="uppercase"/>
        <q-input label-color="black" outlined v-model="product.distribuidora" label="Distribuidora" dense hint="Escribe la distribuidora"/>
        <q-select class="bg-white" emit-value map-options label="Categoria" dense outlined v-model="product.category_id" option-value="id" option-label="name" :options="categories" hint="Selecciona una categoria"/>
        <q-select class="bg-white" emit-value map-options label="Subcategoria" dense outlined v-model="product.subcategory_id" option-value="id" option-label="nameComplete" :options="subcategories" hint="Selecciona una categoria"/>
        <q-input label-color="black" type="textarea" outlined v-model="product.descripcion" label="Descripción" dense hint="Agrega una descripción del producto"/>
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
      fecha_entrega_vencimiento: ''
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
    moverProducto () {
      // si es la misma sucursal
      if (this.lugar === this.delSucursal.nombre) {
        this.$alert.error('No puedes mover el producto a la misma sucursal')
        return false
      }
      const data = {
        id: this.product.id,
        lugar: this.lugar,
        cantidad: this.cantidad,
        delSucursal: this.delSucursal.id,
        fecha_entrega_vencimiento: this.fecha_entrega_vencimiento
      }
      console.log('data', data)
      this.loading = true
      this.$axios.post('moverProducto', data).then(res => {
        this.loading = false
        this.$alert.success('Producto movido correctamente')
        // this.productsGet()
        this.$emit('productsGet')
        this.product = res.data
        this.dialogMover = false
        this.$imprimir.reciboTranferencia(this.product.nombre, this.delSucursal.nombre, this.lugar, this.cantidad)
      }).catch(err => {
        this.loading = false
        this.$alert.error(err.response.data.message)
      })
    },
    moverSucursal (delSucursal) {
      this.delSucursal = delSucursal
      this.fecha_entrega_vencimiento = ''
      this.dialogMover = true
      this.lugar = 'Almacen'
      this.cantidad = 0
    },
    moverProductoAlmacen () {
      // agregarSucursal
      // if (this.lugar === 'Almacen') {
      //   this.$alert.error('No puedes mover el producto al almacen')
      //   return false
      // }
      const data = {
        sucursal: this.sucursalEnvio.id,
        id: this.product.id,
        cantidad: this.cantidad,
        fecha_entrega_vencimiento: this.fecha_entrega_vencimiento
      }
      console.log('data', data)
      this.loading = true
      this.$axios.post('agregarSucursal', data).then(res => {
        this.loading = false
        this.$alert.success('Producto movido correctamente')
        // this.productsGet()
        this.$emit('productsGet')
        this.product = res.data
        this.dialogAlmacenAgencia = false
        this.$imprimir.reciboTranferencia(this.product.nombre, this.delSucursal.nombre, this.lugar, this.cantidad)
      }).catch(err => {
        this.loading = false
        this.$alert.error(err.response.data.message)
      })
    },
    agregarSucursal (sucursal) {
      // console.log(sucursal)
      // console.log(this.product.cantidadAlmacen)
      if (this.product.cantidadAlmacen <= 0) {
        this.$alert.error('No puedes agregar un producto en sucursal si no tienes en almacen')
        return true
      }
      this.fecha_entrega_vencimiento = ''
      this.sucursalEnvio = sucursal
      this.dialogAlmacenAgencia = true
      // this.$q.dialog({
      //   title: 'Agregar a ' + sucursal.nombre,
      //   message: 'Ingresa la cantidad que deseas agregar',
      //   prompt: {
      //     model: 1,
      //     type: 'number'
      //   },
      //   cancel: true
      // }).onOk((data) => {
      //   if (this.product.cantidadAlmacen < data) {
      //     this.$alert.error('No puedes agregar más de lo que tienes en almacen')
      //     return
      //   }
      //
      //   this.loading = true
      //   this.$axios.post('agregarSucursal', {
      //     sucursal: sucursal.id,
      //     id: this.product.id,
      //     cantidad: data
      //   }).then(res => {
      //     this.loading = false
      //     this.$alert.success('Producto agregado correctamente')
      //     // this.productsGet()
      //     this.$emit('productsGet')
      //     this.product = res.data
      //   }).catch(err => {
      //     this.loading = false
      //     this.$alert.error(err.response.data.message)
      //   })
      // })
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
  }
}
</script>

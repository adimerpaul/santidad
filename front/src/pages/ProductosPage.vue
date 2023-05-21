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
      <div class="col-5 col-md-2 text-right">
        <q-btn color="black" no-caps flat icon="o_file_download">
          <div class="q-page-xs subrayado"> Descargar reporte</div>
        </q-btn>
      </div>
      <div class="col-7 col-md-4 text-right">
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
                <q-item-label class="text-bold text-h6 text-green-7">Bs {{costoTotalProducts}}</q-item-label>
              </q-item-section>
            </q-item>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-4 q-pa-xs flex flex-center">
        <q-btn outline no-caps icon="o_edit" class="full-width" label="Editar categoria"/>
      </div>
      <div class="col-12 col-md-4 q-pa-xs">
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
      <div class="col-12 col-md-4 q-pa-xs">
        <q-select class="bg-white" label="Ordenar" dense outlined v-model="order"
                  :options="orders" map-options emit-value
                  option-value="value" option-label="label"
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
          <q-card-section>
            <div class="row cursor-pointer" v-if="products.length>0">
              <div class="col-4 col-md-2" v-for="p in products" :key="p.id">
                <q-card @click="clickDetalleProducto(p)">
                  <q-img :src="p.imagen.includes('http')?p.imagen:`${url}../images/${p.imagen}`" width="100%" height="100px">
                    <div class="absolute-bottom text-center text-subtitle2">
                      {{p.nombre}}
                    </div>
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
    <q-dialog v-model="productDialog" position="right" maximized>
      <q-card class="modalWithCard">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-subtitle2 text-bold text-grey">
            {{ productAction === 'create' ? 'Nuevo producto' : 'Editar producto' }}
          </div>
          <q-space/>
          <q-btn icon="o_highlight_off" flat round dense v-close-popup />
        </q-card-section>
        <q-card-section>
          <q-form @submit="productSave">
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
            <q-input outlined v-model="product.costo" label="Costo" dense hint="Valor que pagas al proveedor por el producto"/>
            <q-input outlined v-model="product.precio" label="Precio*" dense hint="Valor que le cobras a tus clientes por el producto" :rules="[val => !!val || 'Este campo es requerido']"/>
            <q-select class="bg-white" emit-value map-options label="Categoria" dense outlined v-model="product.category_id" option-value="id" option-label="name" :options="categories" hint="Selecciona una categoria"/>
            <q-input type="textarea" outlined v-model="product.descripcion" label="Descripción" dense hint="Agrega una descripción del producto"/>
            <q-btn class="full-width" rounded
                   :color="!product.nombre || !product.precio ? 'grey' : 'green'"
                   :disable="!product.nombre || !product.precio"
                   label="Guardar" no-caps type="submit" :loading="loading"/>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
export default {
  name: 'ProductosPage',
  data () {
    return {
      current_page: 1,
      search: '',
      last_page: 1,
      loading: false,
      productDialog: false,
      productAction: 'create',
      products: [],
      totalProducts: 0,
      product: { cantidad: 0, nombre: '', barra: '', costo: 0, precio: 0, descripcion: '', category_id: 0 },
      category: 0,
      categories: [
        { name: 'Ver todas las categorias', id: 0 }
      ],
      order: 'id',
      orders: [
        { label: 'Ordenar por', value: 'id' },
        { label: 'Menor precio', value: 'precio asc' },
        { label: 'Mayor precio', value: 'precio desc' },
        { label: 'Menor cantidad', value: 'cantidad asc' },
        { label: 'Mayor cantidad', value: 'cantidad desc' }
      ],
      costoTotalProducts: 0
    }
  },
  created () {
    this.categoriesGet()
    this.productsGet()
  },
  methods: {
    // handleKeyUp (e) {
    //   console.log(this.search)
    //   // if (e.keyCode === 13) {
    //   //   this.productsGet()
    //   // }
    // },
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
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: err.response.data.message,
            position: 'top'
          })
        })
      } else {
        this.$axios.put('products', this.product).then(res => {
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
      this.$axios.get(`products?page=${this.current_page}&search=${this.search}&order=${this.order}&category=${this.category}`).then(res => {
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
      this.$q.notify({
        color: 'red-4',
        textColor: 'white',
        icon: 'cloud_done',
        position: 'top',
        message: 'Error al subir la imagen, intente nuevamente el nombre no debe contener espacios o ñ'
      })
    },
    showAddProduct () {
      this.productAction = 'create'
      this.productDialog = true
      this.product = { cantidad: 0, nombre: '', barra: '', costo: 0, precio: 0, descripcion: '', category_id: 0 }
    },
    categoriesGet () {
      this.categories = [{ name: 'Ver todas las categorias', id: 0 }]
      this.$axios.get('categories').then(response => {
        this.categories = this.categories.concat(response.data)
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
          this.$q.notify({
            color: 'red-5',
            textColor: 'white',
            icon: 'warning',
            message: 'El nombre de la categoria no puede estar vacio',
            position: 'top'
          })
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
  }
}
</script>

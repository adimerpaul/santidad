<template>
  <q-page class="bg-grey-2 q-pa-xs">
    <div class="row">
      <!-- PRODUCTOS -->
      <div class="col-12 col-md-8">
        <div class="row">
          <div class="col-12 col-md-6 bg-white">
            <q-input
              outlined
              v-model="search"
              label="Buscar producto"
              dense
              clearable
              @update:model-value="productsGet"
              debounce="500"
            >
              <template v-slot:prepend>
                <q-icon name="search" class="cursor-pointer" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-6 flex">
            <q-btn
              :loading="loading"
              icon="refresh"
              dense
              label="Actualizar"
              color="indigo"
              no-caps
              class="text-bold"
              @click="productsGet"
            >
              <q-tooltip>Actualizar</q-tooltip>
            </q-btn>
          </div>
          <div class="col-12 col-md-4 q-pa-xs">
            <q-select
              class="bg-white"
              label="Agencia"
              dense
              outlined
              v-model="$store.agencia_id"
              :options="agencias"
              map-options
              emit-value
              option-value="id"
              option-label="nombre"
              @update:model-value="productsGet"
              :disable="!($store.user?.agencia_id == 1)"
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
                <div class="row cursor-pointer" v-if="products.length > 0">
                  <div class="col-4 col-md-2" v-for="p in products" :key="p.id">
                    <q-card class="q-pa-xs" flat bordered>
                      <div v-if="$store.agencia_id === 1" class="text-center q-pa-xs">
                        <q-btn
                          dense
                          flat
                          no-caps
                          class="bg-green-2 text-green-9 text-bold"
                          label="Agregar de Almacén"
                          @click="clickAddTransferAlmacen(p)"
                        />
                        <div class="text-caption text-grey">Stock almacén: {{ p.cantidadAlmacen }}</div>
                      </div>
                      <q-img
                        :src="p.imagen.includes('http') ? p.imagen : `${$url}../images/${p.imagen}`"
                        width="100%"
                        height="100px"
                        @click="clickAddTransfer(p)"
                      >
                        <div class="absolute-bottom text-center text-subtitle2" style="padding: 0px; line-height: 1;">
                          {{ p.nombre }}
                        </div>
                      </q-img>
                      <q-card-section class="q-pa-none q-ma-none">
                        <div class="text-center text-subtitle2">{{ p.precio }} Bs</div>
                        <div :class="p.cantidad <= 0 ? 'text-center text-bold text-red' : 'text-center text-bold'">
                          {{ p.cantidad }} disponibles
                        </div>
                      </q-card-section>
                    </q-card>
                  </div>
                </div>
                <q-card v-else>
                  <q-card-section>
                    <div class="text-center text-grey">No se encontraron productos</div>
                  </q-card-section>
                </q-card>
              </q-card-section>
            </q-card>
          </div>
        </div>
      </div>

      <!-- CANASTA DE TRANSFERENCIA -->
      <div class="col-12 col-md-4">
        <q-card>
          <q-card-section>
            <div class="text-h6">Canasta de Transferencia</div>
          </q-card-section>
          <q-separator></q-separator>
          <q-card-section class="q-pa-none q-ma-none">
            <div v-if="carrito.length === 0" class="flex flex-center q-pa-lg">
              <q-icon name="o_shopping_basket" color="grey" size="100px" />
              <div class="q-pa-lg text-grey text-center">
                Aún no tienes productos en la canasta.
              </div>
            </div>
            <q-scroll-area v-else style="height: 400px;">
              <q-list>
                <q-item v-for="(item, index) in carrito" :key="index">
                  <q-item-section>
                    <div class="text-bold">{{ item.nombre }}</div>
                    <q-input
                      dense
                      outlined
                      v-model.number="item.cantidad"
                      type="number"
                      label="Cantidad"
                      :rules="[
                        val => val > 0 || 'Debe ser mayor a 0',
                        val => item.origen !== 'almacen' || val <= item.cantidadAlmacen || 'Supera stock del almacén']"
                    />
                    <q-input
                      dense
                      outlined
                      v-model="item.fechaVencimiento"
                      type="date"
                      label="Fecha de vencimiento"
                    />
                  </q-item-section>
                  <q-item-section side>
                    <q-btn flat dense icon="delete" color="red" @click="eliminarDelCarrito(index)" />
                  </q-item-section>
                </q-item>
              </q-list>
            </q-scroll-area>
          </q-card-section>
          <q-card-section>
            <q-select
              class="bg-white"
              dense
              outlined
              use-input
              v-model="agenciaDestino"
              :options="agencias"
              map-options
              emit-value
              option-value="id"
              option-label="nombre"
              label="Sucursal destino"
              @filter="filterAgencias"
            />
            <q-btn
              @click="confirmarTransferencia"
              class="full-width q-mt-md"
              no-caps
              label="Confirmar transferencia"
              :color="carrito.length === 0 ? 'grey' : 'primary'"
              :disable="carrito.length === 0 || !agenciaDestino"
              :loading="loading"
            />
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script>
export default {
  name: 'TransferenciasPage',
  data () {
    return {
      search: '',
      loading: false,
      current_page: 1,
      last_page: 1,
      products: [],
      agencias: [],
      agenciasOriginales: [],
      agenciaDestino: null,
      carrito: []
    }
  },
  mounted () {
    this.productsGet()
    this.getAgencias()
  },
  methods: {
    productsGet () {
      this.loading = true
      this.$axios.get('productsSale', {
        params: {
          page: this.current_page,
          search: this.search,
          agencia: this.$store.agencia_id
        }
      }).then(res => {
        this.products = res.data.products.data
        this.last_page = res.data.products.last_page
        this.current_page = res.data.products.current_page
        this.loading = false
      })
    },
    getAgencias () {
      this.$axios.get('agencias').then(res => {
        this.agencias = res.data
        this.agenciasOriginales = res.data
      })
    },
    filterAgencias (val, update) {
      if (val === '') {
        update(() => {
          this.agencias = this.agenciasOriginales
        })
        return
      }
      const needle = val.toLowerCase()
      update(() => {
        this.agencias = this.agenciasOriginales.filter(a =>
          a.nombre.toLowerCase().includes(needle)
        )
      })
    },
    clickAddTransfer (product) {
      const yaExiste = this.carrito.find(p => p.id === product.id)
      if (!yaExiste) {
        this.carrito.push({
          id: product.id,
          nombre: product.nombre,
          cantidad: 1,
          fechaVencimiento: '',
          origen: 'sucursal'
        })
        this.$q.notify({
          type: 'positive',
          color: 'teal-7',
          icon: 'check_circle',
          message: '✅ Producto seleccionado de Sucursal',
          position: 'top-right',
          timeout: 1500
        })
      } else {
        this.$q.notify({
          type: 'info',
          color: 'blue-8',
          icon: 'info',
          message: 'ℹ️ Este producto ya está en la canasta',
          position: 'top-right',
          timeout: 1500
        })
      }
    },
    clickAddTransferAlmacen (product) {
      const yaExiste = this.carrito.find(p => p.id === product.id)
      if (!yaExiste) {
        if (product.cantidadAlmacen <= 0) {
          this.$q.notify({
            type: 'negative',
            message: 'No hay stock disponible en almacén',
            position: 'top-right'
          })
          return
        }

        this.carrito.push({
          id: product.id,
          nombre: product.nombre,
          cantidad: 1,
          cantidadAlmacen: product.cantidadAlmacen,
          fechaVencimiento: '',
          origen: 'almacen'
        })

        this.$q.notify({
          type: 'positive',
          color: 'green-8',
          icon: 'check_circle',
          message: '✅ Producto seleccionado de Almacén',
          position: 'top-right',
          timeout: 1500
        })
      } else {
        this.$q.notify({
          type: 'info',
          color: 'blue-8',
          icon: 'info',
          message: 'ℹ️ Este producto ya está en la canasta',
          position: 'top-right',
          timeout: 1500
        })
      }
    },
    eliminarDelCarrito (index) {
      this.carrito.splice(index, 1)
    },
    confirmarTransferencia () {
      this.loading = true

      this.$axios.post('transferencias-multiples', {
        agencia_origen_id: this.$store.agencia_id,
        agencia_destino_id: this.agenciaDestino,
        productos: this.carrito
      }).then(() => {
        this.$q.notify({
          type: 'positive',
          message: '✅ Transferencia realizada con éxito',
          position: 'top-right'
        })

        // Preparar datos para impresión consolidada
        const origen = this.carrito[0]?.origen === 'almacen'
          ? 'Almacén'
          : 'Sucursal ' + this.$store.agencia_id
        const destino = 'Sucursal ' + this.agenciaDestino

        const productos = this.carrito.map(p => ({
          nombre: p.nombre,
          cantidad: p.cantidad
        }))

        // Imprimir todos en un solo comprobante
        this.$imprimir.reciboTransferenciaMultiple(productos, origen, destino)

        this.carrito = []
        this.productsGet()
        this.loading = false
      }).catch(err => {
        this.$q.notify({
          type: 'negative',
          message: err.response?.data?.message || '❌ Error en la transferencia',
          position: 'top-right'
        })
        this.loading = false
      })
    }
  }
}
</script>

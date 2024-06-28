<template>
  <q-page>
    <!--  <div class="col-12">-->
    <div class="text-white bg-purple-8 text-center text-h6 text-bold ">Productos vencidos</div>
    <!--  </div>-->
    <div class="row">
      <div class="col-2">
        <q-select v-model="ordenar" :options="ordenarPor" label="Ordenar por" outlined dense @update:model-value="buyGet"/>
      </div>
    </div>
    <div class="flex flex-center">
      <q-pagination
        v-model="currentPage"
        color="primary"
        :max="totalPages"
        :max-pages="6"
        boundary-numbers
        @update:model-value="buyGet"
      />
    </div>
    <q-table dense flat :rows-per-page-options="[0]" :rows="compras" :columns="compraColumns" wrap-cells
             title="Productos por Vencer" :loading="loading">
      <template v-slot:top-right>
        <q-btn icon="get_app" @click="exportExcel" dense color="green" label="Exportar" no-caps size="10px" class="q-mr-sm">
          <q-tooltip>Exportar</q-tooltip>
        </q-btn>
        <q-btn icon="refresh" @click="buyGet" dense color="grey" label="Actualizar" no-caps size="10px" class="q-mr-sm">
          <q-tooltip>Actualizar</q-tooltip>
        </q-btn>
        <q-input outlined dense v-model="search" label="Buscar" class="q-mt-sm" @update:model-value="buyGet" debounce="500">
          <template v-slot:append>
            <q-icon name="search" class="cursor-pointer">
              <q-tooltip>Search</q-tooltip>
            </q-icon>
          </template>
        </q-input>
      </template>
      <template v-slot:body-cell-diasPorVencer="props">
        <q-td :props="props">
          <q-chip :color="props.row.diasPorVencer <= 30 ? 'red' : props.row.diasPorVencer <= 90 ? 'orange' : 'green'"
                  text-color="white" dense :label="`${props.row.diasPorVencer} dias`"/>
        </q-td>
      </template>
      <template v-slot:body-cell-opciones="props">
        <q-td :props="props">
          <q-btn-group>
            <q-btn icon="delete_sweep" @click="productBaja(props.row)" flat round dense color="red">
              <q-tooltip>Dar de baja</q-tooltip>
            </q-btn>
            <q-btn icon="print" @click="print(props.row)" flat round dense color="grey">
              <q-tooltip>Reimprimir</q-tooltip>
            </q-btn>
          </q-btn-group>
        </q-td>
      </template>
    </q-table>
<!--      <pre>{{compras}}</pre>-->
    <div id="myElement" class="hidden"></div>
    <q-dialog v-model="productoDialogBaja">
      <q-card>
        <q-card-section>
          <div class="text-h6">Dar de baja</div>
          <div class="text-caption">¿Estás seguro de dar de baja el producto?</div>
        </q-card-section>
        <q-card-section>

          <q-form>
            <div class="row">
              <div class="col-6">
                <q-input outlined v-model="productoBaja.lote" label="Lote" dense readonly/>
              </div>
              <div class="col-3">
                <q-input outlined v-model="productoBaja.factura" label="Factura" dense readonly/>
              </div>
              <div class="col-3">
                <q-input outlined :model-value="productoBaja.product?.cantidad" label="Cantidad" dense readonly bg-color="green"/>
              </div>
              <div class="col-12">
                <q-input outlined v-model="productoBaja.product.nombre" label="Producto a dar de baja" dense readonly/>
              </div>
              <div class="col-12">
                <q-input outlined :model-value="productoBaja.proveedor?.nombreRazonSocial" label="Proveedor" dense readonly/>
              </div>
              <div class="col-12">
                <q-input outlined v-model="productoBaja.dateExpiry" label="Fecha de Vencimiento" dense readonly/>
              </div>
              <div class="col-4">
                <q-input outlined v-model="productoBaja.quantity" label="Cantidad A dar de baja" type="number" dense bg-color="orange"/>
              </div>
              <div class="col-8">
                <q-select v-model="productoBaja.agencia_id" :options="agencias" label="Agencia" outlined
                          map-options option-value="id" option-label="nombre" dense emit-value/>
              </div>
              <div class="col-12">
                <q-input outlined v-model="productoBaja.description_baja" label="Descripción de la baja" dense/>
              </div>
            </div>
            <q-card-actions align="right">
              <q-btn label="Cancelar" color="red" @click="productoDialogBaja = false" :loading="loading"/>
              <q-btn label="Aceptar" color="green" @click="darBaja" :loading="loading"/>
            </q-card-actions>
<!--            <pre>{{productoBaja}}</pre>-->
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>
<script>
import moment from 'moment'
import { Excel } from 'src/addons/Excel'

export default {
  name: 'ProductosPorVencerPage',
  data () {
    return {
      compras: [],
      search: '',
      loading: false,
      ordenarPor: [
        'Dias para Vencer',
        'Fecha de Vencimiento',
        'Fecha de Compra'
      ],
      ordenar: 'Dias para Vencer',
      currentPage: 1,
      totalPages: 1,
      compraColumns: [
        { name: 'opciones', label: 'Opciones', field: 'opciones', align: 'left' },
        // { name: 'id', label: 'ID', field: 'id', align: 'left' },
        { name: 'lote', label: 'Lote', field: 'lote', align: 'left', sortable: true },
        { name: 'factura', label: 'Factura', field: 'factura', align: 'left', sortable: true },
        { name: 'user_baja_id', label: 'Usuario de Baja', field: (row) => row.user_baja?.name, align: 'left', sortable: true },
        { name: 'agencia', label: 'Agencia', field: (row) => row.agencia?.nombre, align: 'left', sortable: true },
        { name: 'producto', label: 'Producto', field: row => row.product.nombre, align: 'left', sortable: true },
        { name: 'diasPorVencer', label: 'Dias Vencidos', field: 'diasPorVencer', align: 'left', sortable: true },
        { name: 'quantity', label: 'Cantidad', field: 'quantity', align: 'left', sortable: true },
        { name: 'price', label: 'Precio', field: 'price', align: 'left', sortable: true },
        { name: 'total', label: 'Total', field: 'total', align: 'left', sortable: true },
        { name: 'dateExpiry', label: 'Fecha de Vencimiento', field: 'dateExpiry', align: 'left', sortable: true },
        { name: 'date', label: 'Fecha de Compra', field: (row) => row.date + ' ' + row.time, align: 'left', sortable: true },
        // { name: 'time', label: 'Hora de Compra', field: 'time', align: 'left', sortable: true },
        { name: 'user', label: 'Usuario', field: row => row.user.name, align: 'left', sortable: true },
        { name: 'provider', label: 'Proveedor', field: row => row.proveedor?.nombreRazonSocial, align: 'left', sortable: true }
        // { name: 'actions', label: 'Acciones', field: 'actions', align: 'left' }
      ],
      productoBaja: {},
      productoDialogBaja: false,
      agencias: []
    }
  },
  created () {
    this.agenciasGet()
    this.buyGet()
  },
  methods: {
    darBaja () {
      this.$q.dialog({
        title: 'Dar de baja',
        html: true,
        message: '¿Estás seguro de dar de baja el producto?, <b>' + this.productoBaja.product.nombre + '</b> la cantidad de <b style="color: red">' + this.productoBaja.quantity + ' unidades</b>' +
          ' da la  agencia <b>' + this.agencias.find(a => a.id === this.productoBaja.agencia_id).nombre + '</b>',
        ok: {
          push: true,
          color: 'red',
          label: 'Aceptar'
        },
        cancel: {
          push: true,
          color: 'grey',
          label: 'Cancelar'
        }
      }).onOk(() => {
        this.darBajaProducto()
      })
    },
    darBajaProducto () {
      this.loading = true
      this.$axios
        .post('darBaja', {
          id: this.productoBaja.id,
          cantidadBaja: this.productoBaja.quantity,
          sucursal_id_baja: this.productoBaja.agencia_id,
          description_baja: this.productoBaja.description_baja
        })
        .then(res => {
          this.$q.notify({
            color: 'green-4',
            textColor: 'white',
            icon: 'cloud_done',
            message: 'Producto dado de baja'
          })
          this.productoDialogBaja = false
          this.buyGet()
        })
        .catch(e => {
          console.log(e)
        })
        .finally(() => {
          this.loading = false
        })
    },
    agenciasGet () {
      this.$axios
        .get('agencias')
        .then(res => {
          this.agencias = res.data
        })
        .catch(e => {
          console.log(e)
        })
    },
    productBaja (row) {
      this.productoBaja = row
      this.productoDialogBaja = true
    },
    print (row) {
      this.$imprimir.reciboCompra(row)
    },
    dateCalculate (date) {
      const date1 = moment(date)
      const date2 = moment()
      const diff = date1.diff(date2, 'days')
      return diff
    },
    exportExcel () {
      const data = [
        {
          sheet: 'Productos Vencidos',
          columns: [
            { label: 'Lote', value: 'lote' },
            { label: 'Factura', value: 'factura' },
            { label: 'Usuario de Baja', value: 'user_baja_id' },
            { label: 'Agencia', value: 'agencia.nombre' },
            { label: 'Producto', value: 'product.nombre' },
            { label: 'Dias Vencidos', value: 'diasPorVencer' },
            { label: 'Cantidad', value: 'quantity' },
            { label: 'Precio', value: 'price' },
            { label: 'Total', value: 'total' },
            { label: 'Fecha de Vencimiento', value: 'dateExpiry' },
            { label: 'Fecha de Compra', value: 'date' },
            { label: 'Usuario', value: 'user.name' },
            { label: 'Proveedor', value: (row) => row.proveedor?.nombreRazonSocial }
            // { title: 'Factura', key: 'factura' },
            // { title: 'Usuario de Baja', key: 'user_baja_id' },
            // { title: 'Agencia', key: 'agencia.nombre' },
            // { title: 'Producto', key: 'product.nombre' },
            // { title: 'Dias Vencidos', key: 'diasPorVencer' },
            // { title: 'Cantidad', key: 'quantity' },
            // { title: 'Precio', key: 'price' },
            // { title: 'Total', key: 'total' },
            // { title: 'Fecha de Vencimiento', key: 'dateExpiry' },
            // { title: 'Fecha de Compra', key: 'date' },
            // { title: 'Usuario', key: 'user.name' },
            // { title: 'Proveedor', key: 'proveedor.nombreRazonSocial' }
          ],
          content: this.compras
        }
      ]
      Excel.export(data)
    },
    buyGet: function () {
      this.loading = true
      this.compras = []
      this.$axios
        .get('indexVencidos', {
          params: {
            search: this.search,
            order: this.ordenar,
            page: this.currentPage // Agrega la página actual como parámetro
          }
        })
        .then(res => {
          // console.log(res.data.data)
          this.compras = res.data.data // Actualiza las compras con los datos de la página actual
          this.totalPages = res.data.last_page // Actualiza el número total de páginas
        })
        .catch(e => {
          console.log(e)
        })
        .finally(() => {
          this.loading = false
        })
    }
  }
}
</script>
<style scoped>

</style>

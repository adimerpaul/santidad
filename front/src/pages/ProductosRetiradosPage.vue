<template>
  <q-page>
    <!--  <div class="col-12">-->
    <div class="text-white bg-orange-8 text-center text-h6 text-bold ">Productos retirados</div>
    <!--  </div>-->
    <div class="row q-pa-xs">
      <div class="col-2">
        <q-select v-model="ordenar" :options="ordenarPor" label="Ordenar por" outlined dense @update:model-value="buyGet"/>
      </div>
      <div class="col-2">
        <q-input outlined v-model="fecha_inicio" label="Fecha Inicio" type="date" dense @update:model-value="buyGet"/>
      </div>
      <div class="col-2">
        <q-input outlined v-model="fecha_fin" label="Fecha Fin" type="date" dense @update:model-value="buyGet"/>
      </div>
      <div class="col-2 flex flex-center">
        <q-btn icon="fa-solid fa-file-excel" @click="downloadExcel" dense color="green" label="Descargar Excel" no-caps size="10px" class="q-mr-sm">
          <q-tooltip>Descargar Excel</q-tooltip>
        </q-btn>
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
             title="Productos vencidos" :loading="loading">
      <template v-slot:top-right>
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
<!--            <q-btn icon="delete_sweep" @click="productBaja(props.row)" flat round dense color="red">-->
<!--              <q-tooltip>Dar de baja</q-tooltip>-->
<!--            </q-btn>-->
            <q-btn icon="print" @click="print(props.row)" flat round dense color="grey">
              <q-tooltip>Reimprimir</q-tooltip>
            </q-btn>
          </q-btn-group>
        </q-td>
      </template>
      <template v-slot:body-cell-cantidadBaja="props">
        <q-td :props="props">
          <ul style="padding: 0px;margin: 0px">
            <li v-for="item in props.row.buy_detail" :key="item.id" style="width: 250px;font-size: 9px;padding: 0px;margin: 0px">
              {{item.quantity}} {{item.user?.name}} {{$filters.dateDmYHi(item.fecha+' '+item.hora)}}
            </li>
          </ul>
          <div>
            <b>{{props.row.cantidadBaja}}</b>
          </div>
<!--          [-->
<!--          {-->
<!--          "id": 1,-->
<!--          "buy_id": 24382,-->
<!--          "product_id": 294,-->
<!--          "user_id": 1,-->
<!--          "quantity": 1,-->
<!--          "fecha": "2024-09-05",-->
<!--          "hora": "03:00:18",-->
<!--          "user": {-->
<!--          "id": 1,-->
<!--          "name": "Administrador",-->
<!--          "email": "admin@test.com",-->
<!--          "email_verified_at": null,-->
<!--          "agencia_id": 1,-->
<!--          "created_at": null,-->
<!--          "updated_at": "2024-08-01T08:14:08.000000Z"-->
<!--          }-->
<!--          },-->
<!--          <pre>{{props.row.buy_detail}}</pre>-->
        </q-td>
      </template>
    </q-table>
    <!--  <pre>{{compras}}</pre>-->
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
              <div class="col-6">
                <q-input outlined v-model="productoBaja.factura" label="Factura" dense readonly/>
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
            </div>
            <q-card-actions align="right">
              <q-btn label="Cancelar" color="red" @click="productoDialogBaja = false" :loading="loading"/>
              <q-btn label="Aceptar" color="green" @click="darBaja" :loading="loading"/>
            </q-card-actions>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
<!--    <pre>{{compras}}</pre>-->
  </q-page>
</template>
<script>
import moment from 'moment'
import { Excel } from 'src/addons/Excel'

export default {
  name: 'ProductosRetiradosPage',
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
        { name: 'diasPorVencer', label: 'Dias para Vencer', field: 'diasPorVencer', align: 'left', sortable: true },
        { name: 'cantidadBaja', label: 'Cantidad Baja', field: 'cantidadBaja', align: 'left', sortable: true },
        { name: 'description_baja', label: 'Descripcion de Baja', field: 'description_baja', align: 'left', sortable: true },
        { name: 'price', label: 'Precio', field: 'price', align: 'left', sortable: true },
        { name: 'total', label: 'Total', field: 'total', align: 'left', sortable: true },
        { name: 'dateExpiry', label: 'Fecha de Vencimiento', field: 'dateExpiry', align: 'left', sortable: true },
        { name: 'date', label: 'Fecha de Compra', field: (row) => row.date + ' ' + row.time, align: 'left', sortable: true },
        // { name: 'time', label: 'Hora de Compra', field: 'time', align: 'left', sortable: true },
        { name: 'user', label: 'Usuario', field: row => row.user.name, align: 'left', sortable: true },
        { name: 'provider', label: 'Proveedor', field: row => row.proveedor?.nombreRazonSocial, align: 'left', sortable: true },
        { name: 'fecha_baja', label: 'Fecha de Baja', field: 'fecha_baja', align: 'left', sortable: true }
      ],
      productoBaja: {},
      productoDialogBaja: false,
      agencias: [],
      fecha_inicio: moment().format('YYYY-MM-01'),
      fecha_fin: moment().format('YYYY-MM-') + moment().daysInMonth()
    }
  },
  created () {
    this.agenciasGet()
    this.buyGet()
  },
  methods: {
    downloadExcel () {
      const data = [
        {
          // columns: [
          //   { label: "User", value: "user" }, // Top level data
          //   { label: "Age", value: (row) => row.age + " years" }, // Custom format
          //   { label: "Phone", value: (row) => (row.more ? row.more.phone || "" : "") }, // Run functions
          // ],
          columns: [
            { label: 'Lote', value: 'lote' },
            { label: 'Factura', value: 'factura' },
            { label: 'Usuario de Baja', value: (row) => row.user_baja?.name },
            { label: 'Agencia', value: (row) => row.agencia?.nombre },
            { label: 'Producto', value: (row) => row.product.nombre },
            { label: 'Dias para Vencer', value: 'diasPorVencer' },
            { label: 'Cantidad Baja', value: 'cantidadBaja' },
            { label: 'Descripcion de Baja', value: 'description_baja' },
            { label: 'Precio', value: 'price' },
            { label: 'Total', value: 'total' },
            { label: 'Fecha de Vencimiento', value: 'dateExpiry' },
            { label: 'Fecha de Compra', value: (row) => row.date + ' ' + row.time },
            { label: 'Usuario', value: (row) => row.user.name },
            { label: 'Proveedor', value: (row) => row.proveedor?.nombreRazonSocial },
            { label: 'Fecha de Baja', value: 'fecha_baja' }
          ],
          content: this.compras
        }
      ]
      Excel.export(data)
    },
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
          sucursal_id_baja: this.productoBaja.agencia_id
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
    buyGet: function () {
      this.loading = true
      this.compras = []
      this.$axios
        .get('indexVencidosBaja', {
          params: {
            search: this.search,
            order: this.ordenar,
            page: this.currentPage,
            fecha_inicio: this.fecha_inicio,
            fecha_fin: this.fecha_fin
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

<template>
<q-page>
<!--  <div class="col-12">-->
    <div class="text-white bg-red-7 text-center text-h6 text-bold ">Productos por Vencer</div>
<!--  </div>-->
  <div class="row q-pa-xs">
    <div class="col-2">
      <q-select v-model="ordenar" :options="ordenarPor" label="Ordenar por" outlined dense @update:model-value="buyGet"/>
    </div>
    <div class="col-3">
      <q-select v-model="agencia" :options="agencias" label="Agencia" outlined dense
                @update:model-value="buyGet"
                emit-value map-options :option-label="agencia => agencia.nombre" :option-value="agencia => agencia.id"
      />
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
          <q-btn icon="print" @click="print(props.row)" flat round dense color="grey">
            <q-tooltip>Reimprimir</q-tooltip>
          </q-btn>
        </q-btn-group>
      </q-td>
    </template>
  </q-table>
<!--  <pre>{{compras}}</pre>-->
  <div id="myElement" class="hidden"></div>
</q-page>
</template>
<script>
import moment from 'moment'

export default {
  name: 'ProductosPorVencerPage',
  data () {
    return {
      compras: [],
      search: '',
      agencias: [],
      agencia: '',
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
        { name: 'producto', label: 'Producto', field: row => row.product.nombre, align: 'left', sortable: true },
        { name: 'diasPorVencer', label: 'Dias para Vencer', field: 'diasPorVencer', align: 'left', sortable: true },
        { name: 'quantity', label: 'Cantidad', field: 'quantity', align: 'left', sortable: true },
        { name: 'productQuantity', label: 'Cantidad de Producto', field: (row) => row.product?.cantidad, align: 'left', sortable: true },
        { name: 'price', label: 'Precio', field: 'price', align: 'left', sortable: true },
        { name: 'total', label: 'Total', field: 'total', align: 'left', sortable: true },
        { name: 'dateExpiry', label: 'Fecha de Vencimiento', field: 'dateExpiry', align: 'left', sortable: true },
        { name: 'date', label: 'Fecha de Compra', field: (row) => row.date + ' ' + row.time, align: 'left', sortable: true },
        // { name: 'time', label: 'Hora de Compra', field: 'time', align: 'left', sortable: true },
        { name: 'user', label: 'Usuario', field: row => row.user.name, align: 'left', sortable: true },
        { name: 'provider', label: 'Proveedor', field: row => row.proveedor?.nombreRazonSocial, align: 'left', sortable: true },
        { name: 'agencia', label: 'Agencia', field: row => row.agencia?.nombre, align: 'left', sortable: true }
        // { name: 'actions', label: 'Acciones', field: 'actions', align: 'left' }
      ]
    }
  },
  created () {
    this.buyGet()
    this.agenciasGet()
  },
  methods: {
    agenciasGet: function () {
      this.$axios.get('agencias').then(res => {
        this.agencias = res.data
      }).catch(e => {
        console.log(e)
      })
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
        .get('buys', {
          params: {
            search: this.search,
            order: this.ordenar,
            page: this.currentPage,
            agencia_id: this.agencia
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

<template>
<q-page>
  <div class="text-h6 text-bold bg-red-7 text-center text-white">Productos por Vencer</div>
  <q-table dense flat :rows-per-page-options="[0]" :rows="compras" :columns="compraColumns"
           :filter="search" title="Productos por Vencer" :loading="loading">
    <template v-slot:top-right>
      <q-btn icon="refresh" @click="buyGet" flat round dense color="grey">
        <q-tooltip>Actualizar</q-tooltip>
      </q-btn>
      <q-input outlined dense v-model="search" label="Buscar" class="q-mt-sm">
        <template v-slot:append>
          <q-icon name="search" class="cursor-pointer">
            <q-tooltip>Search</q-tooltip>
          </q-icon>
        </template>
      </q-input>
    </template>
    <template v-slot:body-cell-day="props">
      <q-td :props="props">
        <q-chip :color="props.row.timeCalculate <= 14 ? 'red' : 'green'" class="text-white"
                :label="`${props.row.timeCalculate} dias`"/>
      </q-td>
    </template>
  </q-table>
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
      loading: false,
      compraColumns: [
        { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true },
        { name: 'producto', label: 'Producto', field: row => row.product.nombre, align: 'left', sortable: true },
        { name: 'day', label: 'Dias para Vencer', field: 'timeCalculate', align: 'left', sortable: true },
        { name: 'quantity', label: 'Cantidad', field: 'quantity', align: 'left', sortable: true },
        { name: 'price', label: 'Precio', field: 'price', align: 'left', sortable: true },
        { name: 'total', label: 'Total', field: 'total', align: 'left', sortable: true },
        { name: 'dateExpiry', label: 'Fecha de Vencimiento', field: 'dateExpiry', align: 'left', sortable: true },
        { name: 'date', label: 'Fecha de Compra', field: 'date', align: 'left', sortable: true },
        { name: 'time', label: 'Hora de Compra', field: 'time', align: 'left', sortable: true },
        { name: 'user', label: 'Usuario', field: row => row.user.name, align: 'left', sortable: true }
        // { name: 'provider', label: 'Proveedor', field: row => row.provider.name, align: 'left', sortable: true },
        // { name: 'actions', label: 'Acciones', field: 'actions', align: 'left' }
      ]
    }
  },
  created () {
    this.buyGet()
  },
  methods: {
    dateCalculate (date) {
      const date1 = moment(date)
      const date2 = moment()
      const diff = date1.diff(date2, 'days')
      return diff
    },
    buyGet () {
      this.compras = []
      this.loading = true
      this.$axios.get('buys').then(res => {
        res.data.forEach(buy => {
          buy.timeCalculate = this.dateCalculate(buy.dateExpiry)
          this.compras.push(buy)
        })
      }).catch(e => {
        console.log(e)
      }).finally(() => {
        this.loading = false
      })
    }
  }
}
</script>
<style scoped>

</style>

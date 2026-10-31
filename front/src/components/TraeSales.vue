<template>
  <div>
    <q-card flat bordered class="q-mb-md">
      <q-card-section>
        <div class="text-h6">Ventas exclusivas de TRAE</div>
        <div class="text-caption text-grey-7 q-mb-md">La comisión se resta de cada venta. Se excluyen las ventas anuladas y eliminadas.</div>
        <q-form class="row q-col-gutter-md items-start" @submit.prevent="applyFilters">
          <div class="col-12 col-md-4"><q-select v-model="filters.agencia_id" :options="agencias" option-value="id" option-label="nombre" emit-value map-options outlined dense clearable label="Todas las sucursales" /></div>
          <div class="col-6 col-md-3"><q-input v-model="filters.desde" type="date" outlined dense stack-label clearable label="Desde" /></div>
          <div class="col-6 col-md-3"><q-input v-model="filters.hasta" type="date" outlined dense stack-label clearable label="Hasta" :min="filters.desde || undefined" /></div>
          <div class="col-12 col-md-2"><q-btn type="submit" color="deep-purple" icon="filter_alt" label="Filtrar" no-caps :loading="loading" class="full-width" /></div>
          <div class="col-12 row items-center justify-between q-gutter-sm">
            <span class="text-caption text-grey-7">Deja las fechas vacías para consultar todo el historial.</span>
            <q-btn flat color="deep-purple" label="Ver todo" no-caps :disable="loading" @click="showAll" />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
    <q-banner v-if="errorMessage" class="bg-red-1 text-negative rounded-borders q-mb-md">{{ errorMessage }}</q-banner>
    <template v-if="report">
      <div class="text-caption text-grey-7 q-mb-sm">{{ appliedLabel }}</div>
      <div class="row q-col-gutter-md q-mb-md">
        <div v-for="card in cards" :key="card.label" class="col-6 col-lg-3">
          <q-card flat bordered class="full-height" :class="card.net ? 'bg-deep-purple-1' : ''">
            <q-card-section>
              <div class="text-caption text-grey-8"><q-icon :name="card.icon" class="q-mr-xs" />{{ card.label }}</div>
              <div class="text-h5 text-weight-bold" :class="card.net ? 'text-deep-purple' : ''">{{ card.value }}</div>
            </q-card-section>
          </q-card>
        </div>
      </div>
      <q-banner dense class="bg-blue-grey-1 text-grey-8 rounded-borders q-mb-md">
        Neto restante = total vendido − comisión TRAE. Este importe todavía debe cubrir mercadería, impuestos y gastos.
        Cada venta conserva su comisión original (12 % por defecto).
      </q-banner>
      <q-table :rows="report.ventas.data" :columns="columns" row-key="id" flat bordered wrap-cells hide-pagination :rows-per-page-options="[0]" :loading="loading" no-data-label="No hay ventas TRAE para estos filtros">
        <template #body-cell-venta="props"><q-td :props="props"><strong>#{{ props.row.id }}</strong><div class="text-caption text-grey-7">{{ props.row.numeroFactura ? `Factura ${props.row.numeroFactura}` : 'Nota de venta' }}</div></q-td></template>
        <template #body-cell-montoTotal="props"><q-td :props="props">Bs {{ money(props.row.montoTotal) }}</q-td></template>
        <template #body-cell-comision="props"><q-td :props="props">− Bs {{ money(props.row.comision_trae_monto) }}<div class="text-caption text-grey-7">{{ Number(props.row.comision_trae_porcentaje) }} %</div></q-td></template>
        <template #body-cell-neto="props"><q-td :props="props" class="text-weight-bold text-deep-purple">Bs {{ money(props.row.neto) }}</q-td></template>
      </q-table>
      <div class="row items-center justify-between q-pa-md q-gutter-sm">
        <span class="text-caption text-grey-7">{{ report.ventas.total }} ventas · Los totales incluyen todas las páginas del filtro.</span>
        <q-pagination :model-value="report.ventas.current_page" :max="report.ventas.last_page" :max-pages="5" color="deep-purple" boundary-numbers :disable="loading" @update:model-value="changePage" />
      </div>
    </template>
    <div v-else-if="loading" class="text-center q-pa-lg"><q-spinner color="deep-purple" size="32px" /><div class="text-grey-7 q-mt-sm">Consultando ventas TRAE…</div></div>
  </div>
</template>

<script>
export default {
  name: 'TraeSales',
  props: { agencias: { type: Array, default: () => [] } },
  data () {
    return {
      filters: { agencia_id: null, desde: '', hasta: '' },
      appliedFilters: { agencia_id: null, desde: '', hasta: '' },
      report: null,
      loading: false,
      errorMessage: '',
      requestSequence: 0,
      requestAbort: null,
      columns: [
        { name: 'venta', label: 'Venta', field: 'id', align: 'left' },
        { name: 'fecha', label: 'Fecha y hora', field: row => row.fechaEmision?.replace('T', ' ').slice(0, 19), align: 'left' },
        { name: 'sucursal', label: 'Sucursal', field: row => row.agencia?.nombre || 'Sin sucursal', align: 'left' },
        { name: 'metodoPago', label: 'Medio de pago', field: 'metodoPago', align: 'left' },
        { name: 'montoTotal', label: 'Total vendido', field: 'montoTotal', align: 'right' },
        { name: 'comision', label: 'Comisión TRAE', field: 'comision_trae_monto', align: 'right' },
        { name: 'neto', label: 'Neto restante', field: 'neto', align: 'right' }
      ]
    }
  },
  computed: {
    cards () {
      const summary = this.report.resumen
      return [
        { label: 'Ventas TRAE', value: summary.cantidad, icon: 'receipt_long' },
        { label: 'Total vendido', value: `Bs ${this.money(summary.ventas)}`, icon: 'payments' },
        { label: 'Comisión TRAE', value: `− Bs ${this.money(summary.comision)}`, icon: 'delivery_dining' },
        { label: 'Neto restante', value: `Bs ${this.money(summary.neto)}`, icon: 'account_balance_wallet', net: true }
      ]
    },
    appliedLabel () {
      const f = this.appliedFilters
      const branch = f.agencia_id ? this.agencias.find(a => Number(a.id) === Number(f.agencia_id))?.nombre || 'Sucursal seleccionada' : 'Todas las sucursales'
      return `${branch} · ${f.desde || 'Desde el inicio'} → ${f.hasta || 'Sin fecha final'}`
    }
  },
  mounted () { this.loadSales() },
  beforeUnmount () { this.requestSequence++; this.requestAbort?.abort() },
  methods: {
    money (value) { return Number(value || 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
    applyFilters () {
      if (this.filters.desde && this.filters.hasta && this.filters.hasta < this.filters.desde) {
        this.errorMessage = 'La fecha Hasta debe ser igual o posterior a Desde.'
        return
      }
      this.loadSales(1, { ...this.filters })
    },
    showAll () { this.filters = { agencia_id: null, desde: '', hasta: '' }; this.applyFilters() },
    changePage (page) { this.loadSales(page) },
    async loadSales (page = this.report?.ventas.current_page || 1, filters = this.appliedFilters) {
      const sequence = ++this.requestSequence
      this.requestAbort?.abort()
      const controller = new AbortController()
      this.requestAbort = controller
      const applied = { ...filters }
      this.loading = true
      this.errorMessage = ''
      try {
        const { data } = await this.$axios.get('trae/sales', { params: { agencia_id: applied.agencia_id || undefined, desde: applied.desde || undefined, hasta: applied.hasta || undefined, page }, signal: controller.signal })
        if (sequence !== this.requestSequence) return
        this.report = data
        this.appliedFilters = applied
      } catch (e) {
        if (sequence !== this.requestSequence) return
        this.report = null
        const errors = e.response?.data?.errors
        this.errorMessage = (errors && Object.values(errors).flat()[0]) || e.response?.data?.message || 'No se pudieron consultar las ventas TRAE. Intenta actualizar.'
      } finally { if (sequence === this.requestSequence) this.loading = false }
    }
  }
}
</script>

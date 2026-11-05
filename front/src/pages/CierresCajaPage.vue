<template>
  <q-page class="closures-page">
    <header class="page-heading">
      <div class="heading-title">
        <span class="heading-icon"><q-icon name="account_balance_wallet" size="26px" /></span>
        <div><div class="eyebrow">CONTROL DE CAJA</div><h1>Cierres de caja</h1><p>Revisa cada turno y encuentra diferencias a tiempo.</p></div>
      </div>
      <q-btn outline no-caps icon="refresh" :label="$q.screen.lt.sm ? undefined : 'Actualizar'" aria-label="Actualizar historial" :loading="loading || loadingOptions" :disable="!identityKey" @click="refresh" />
    </header>

    <q-banner v-if="optionsError" rounded class="bg-orange-1 text-orange-10 q-mb-md">
      {{ optionsError }}
      <template #action><q-btn flat no-caps label="Reintentar" @click="loadOptions" /></template>
    </q-banner>

    <q-form class="filters-panel" @submit="applyFilters">
      <div class="filter-fields">
        <q-select v-if="isAdmin" v-model="filters.agencia_id" outlined dense emit-value map-options label="Sucursal"
          :options="agencyOptions" option-label="nombre" option-value="id" :loading="loadingOptions" :disable="loadingOptions"
          @update:model-value="onAgencyChange" />
        <div v-else class="branch-label"><q-icon name="storefront" size="20px" /> {{ $store.user?.agencia?.nombre || 'Mi sucursal' }}</div>
        <q-select v-model="filters.user_id" outlined dense emit-value map-options label="Cuenta del turno"
          :options="accountOptions" option-label="name" option-value="id" :loading="loadingOptions" :disable="loadingOptions">
          <template #option="scope"><q-item v-bind="scope.itemProps"><q-item-section><q-item-label>{{ scope.opt.name }}</q-item-label><q-item-label v-if="scope.opt.id" caption>Cuenta #{{ scope.opt.id }}</q-item-label></q-item-section></q-item></template>
        </q-select>
        <q-select v-model="period" outlined dense emit-value map-options label="Período" :options="periodOptions" @update:model-value="onPeriodChange" />
        <div class="filter-actions"><q-btn flat no-caps label="Limpiar" :disable="loadingOptions || !identityKey" @click="clearFilters" /><q-btn unelevated no-caps color="primary" icon="search" label="Aplicar" type="submit" :disable="loadingOptions || !identityKey" /></div>
      </div>
      <div v-if="period === 'custom'" class="custom-dates">
        <q-input v-model="filters.desde" outlined dense type="date" label="Desde" stack-label />
        <q-input v-model="filters.hasta" outlined dense type="date" label="Hasta" stack-label />
      </div>
    </q-form>

    <q-banner v-if="errorMessage" rounded class="bg-red-1 text-red-9 q-mt-md" role="alert">{{ errorMessage }}</q-banner>

    <template v-if="report">
      <div class="scope-label"><q-icon name="filter_alt" size="16px" /> {{ scopeLabel }} <q-spinner v-if="loading" size="16px" /></div>
      <div class="overview">
        <button class="overview-card" :class="{ selected: view === 'todos' }" @click="changeView('todos')">
          <span class="metric-icon neutral"><q-icon name="receipt_long" /></span>
          <span class="metric-copy"><strong>{{ summary.turnos }}</strong><small>Turnos registrados</small><span class="metric-caption">{{ summary.cerrados }} cerrados · {{ summary.abiertos }} abiertos</span></span>
        </button>
        <button v-if="isAdmin" class="overview-card" :class="{ selected: view === 'faltantes' }" @click="changeView('faltantes')">
          <span class="metric-icon shortage"><q-icon name="south_east" /></span>
          <span class="metric-copy"><strong>{{ summary.faltantes }}</strong><small>Faltantes &gt; Bs 20</small><span class="metric-caption">Acumulado {{ money(summary.monto_faltante) }}</span></span>
        </button>
        <button v-if="isAdmin" class="overview-card" :class="{ selected: view === 'sobrantes' }" @click="changeView('sobrantes')">
          <span class="metric-icon surplus"><q-icon name="north_east" /></span>
          <span class="metric-copy"><strong>{{ summary.sobrantes }}</strong><small>Sobrantes &gt; Bs 150</small><span class="metric-caption">Acumulado {{ money(summary.monto_sobrante) }}</span></span>
        </button>
        <button class="overview-card" :class="{ selected: view === 'pendientes' }" @click="changeView('pendientes')">
          <span class="metric-icon pending"><q-icon name="pending_actions" /></span>
          <span class="metric-copy"><strong>{{ summary.pendientes }}</strong><small>Pendientes de arqueo</small><span class="metric-caption">Sin monto confirmado</span></span>
        </button>
      </div>

      <section class="history-panel">
        <div class="history-heading">
          <div><h2>{{ viewTitle }}</h2><p v-if="isAdmin">Diferencia = efectivo contado − efectivo esperado. Los pagos digitales se muestran aparte.</p><p v-else>Consulta los responsables, importes contados y observaciones de cada turno.</p></div>
          <q-select v-model="view" dense outlined emit-value map-options label="Mostrar" :options="viewOptions" @update:model-value="changeView" />
        </div>
        <div v-if="isAdmin" class="anomaly-strip" :class="{ 'has-alerts': summary.anomalias > 0 }">
          <q-icon :name="summary.anomalias ? 'report_problem' : 'fact_check'" size="21px" />
          <span><b>{{ summary.anomalias }} {{ summary.anomalias === 1 ? 'cierre requiere' : 'cierres requieren' }} revisión</b><span class="threshold-note">Faltante &gt; Bs 20 / sobrante &gt; Bs 150 · Solo turnos cerrados</span></span>
          <q-btn v-if="summary.anomalias" flat no-caps label="Ver anomalías" @click="changeView('anomalias')" />
        </div>

        <q-table flat dense :rows="report.cierres.data" :columns="columns" row-key="id" :loading="loading"
          :pagination="pagination" :rows-per-page-options="[15, 30, 50]" @request="onTableRequest"
          no-data-label="No hay turnos que coincidan con estos filtros." loading-label="Consultando cierres…"
          rows-per-page-label="Turnos por página" :grid="$q.screen.lt.md" class="closures-table">
          <template #body-cell-turno="props"><q-td :props="props"><b>#{{ props.row.id }}</b><span class="cell-secondary">{{ formatDate(props.row.fecha_cierre || props.row.fecha_apertura) }}</span></q-td></template>
          <template #body-cell-cuenta="props"><q-td :props="props"><b>{{ props.row.user?.name || 'Cuenta no disponible' }}</b><span class="cell-secondary">{{ props.row.agencia?.nombre || 'Sin sucursal' }}</span></q-td></template>
          <template #body-cell-monto_fisico="props"><q-td :props="props"><b>{{ isClosed(props.row) ? money(props.row.monto_fisico) : 'Sin confirmar' }}</b></q-td></template>
          <template #body-cell-monto_sistema_efectivo="props"><q-td :props="props">{{ isClosed(props.row) ? money(props.row.monto_sistema_efectivo) : '—' }}</q-td></template>
          <template #body-cell-monto_sistema_digital="props"><q-td :props="props">{{ props.row.estado !== 'ABIERTO' ? money(props.row.monto_sistema_digital) : '—' }}</q-td></template>
          <template #body-cell-diferencia="props"><q-td :props="props"><span class="difference" :class="differenceClass(props.row)">{{ differenceLabel(props.row) }}</span></q-td></template>
          <template #body-cell-estado="props"><q-td :props="props"><span class="status-chip" :class="props.row.estado?.toLowerCase()">{{ statusLabel(props.row) }}</span></q-td></template>
          <template #body-cell-detalle="props"><q-td :props="props"><q-btn flat round color="primary" icon="chevron_right" :aria-label="'Ver turno ' + props.row.id" @click="selectedClosure = props.row"><q-tooltip>Ver detalle</q-tooltip></q-btn></q-td></template>
          <template #item="props">
            <div class="mobile-turno q-pa-sm col-12 col-sm-6"><article>
              <div class="row items-center justify-between"><b>Turno #{{ props.row.id }}</b><span class="status-chip" :class="props.row.estado?.toLowerCase()">{{ statusLabel(props.row) }}</span></div>
              <h3>{{ props.row.user?.name || 'Cuenta no disponible' }}</h3><p>{{ props.row.agencia?.nombre }}</p><p>{{ formatDate(props.row.fecha_cierre || props.row.fecha_apertura) }}</p>
              <div class="mobile-money"><span>Contado<b>{{ isClosed(props.row) ? money(props.row.monto_fisico) : 'Sin confirmar' }}</b></span><template v-if="isAdmin"><span>Digital / QR<b>{{ props.row.estado !== 'ABIERTO' ? money(props.row.monto_sistema_digital) : '—' }}</b></span><span>Diferencia<b class="difference" :class="differenceClass(props.row)">{{ differenceLabel(props.row) }}</b></span></template></div>
              <q-btn flat no-caps color="primary" label="Ver detalle" icon-right="chevron_right" @click="selectedClosure = props.row" />
            </article></div>
          </template>
        </q-table>
      </section>
    </template>
    <div v-else-if="loading || !identityKey" class="loading-placeholder"><q-spinner color="primary" size="32px" /><p>Cargando historial de caja…</p></div>

    <q-dialog :model-value="!!selectedClosure" @update:model-value="selectedClosure = null">
      <q-card v-if="selectedClosure" class="closure-detail">
        <q-card-section class="row items-center"><div><div class="eyebrow">DETALLE DEL TURNO</div><h2>Caja #{{ selectedClosure.id }}</h2></div><q-space /><q-btn flat round icon="close" aria-label="Cerrar detalle" v-close-popup /></q-card-section>
        <q-separator />
        <q-card-section>
          <span class="status-chip" :class="selectedClosure.estado?.toLowerCase()">{{ statusLabel(selectedClosure) }}</span>
          <dl class="detail-list"><dt>Cuenta del turno</dt><dd>{{ selectedClosure.user?.name || '—' }} <small>(#{{ selectedClosure.user_id }})</small></dd><dt>Sucursal</dt><dd>{{ selectedClosure.agencia?.nombre || '—' }}</dd><dt>Abrió el turno</dt><dd>{{ formatDate(selectedClosure.fecha_apertura) }}</dd><dt>Cierre / relevo</dt><dd>{{ formatDate(selectedClosure.fecha_cierre) }}</dd><dt>Registró el cierre</dt><dd>{{ selectedClosure.closed_by_user?.name || '—' }}</dd></dl>
          <div class="detail-amounts">
            <div><span>Efectivo contado</span><b>{{ isClosed(selectedClosure) ? money(selectedClosure.monto_fisico) : 'Sin confirmar' }}</b></div>
            <template v-if="isAdmin"><div><span>Efectivo esperado</span><b>{{ money(selectedClosure.monto_sistema_efectivo) }}</b></div><div><span>Pagos digitales</span><b>{{ money(selectedClosure.monto_sistema_digital) }}</b></div><div><span>Total del sistema</span><b>{{ money(selectedClosure.monto_sistema_total) }}</b></div><div class="detail-difference"><span>Diferencia de efectivo</span><b class="difference" :class="differenceClass(selectedClosure)">{{ differenceLabel(selectedClosure) }}</b></div></template>
          </div>
          <div class="detail-notes"><b>Observaciones de apertura</b><p>{{ selectedClosure.observaciones_apertura || 'Sin observaciones' }}</p><b>Observaciones de cierre</b><p>{{ selectedClosure.observaciones || 'Sin observaciones' }}</p></div>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script>
const VIEW_OPTIONS = [
  { value: 'todos', label: 'Todos los turnos' },
  { value: 'anomalias', label: 'Solo anomalías' },
  { value: 'faltantes', label: 'Faltantes > Bs 20' },
  { value: 'sobrantes', label: 'Sobrantes > Bs 150' },
  { value: 'pendientes', label: 'Pendientes de arqueo' },
  { value: 'abiertos', label: 'Turnos abiertos' },
  { value: 'cerrados', label: 'Turnos cerrados' }
]
const emptyFilters = () => ({ agencia_id: '', user_id: '', desde: '', hasta: '' })
const periodDates = (days = 7, now = new Date()) => {
  const parts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', { timeZone: 'America/La_Paz', year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(now).map(part => [part.type, part.value]))
  const hasta = parts.year + '-' + parts.month + '-' + parts.day
  const start = new Date(hasta + 'T12:00:00Z')
  start.setUTCDate(start.getUTCDate() - (days - 1))
  return { desde: start.toISOString().slice(0, 10), hasta }
}
const PERIOD_OPTIONS = [
  { value: 7, label: 'Últimos 7 días' },
  { value: 30, label: 'Últimos 30 días' },
  { value: 50, label: 'Últimos 50 días' },
  { value: 90, label: 'Últimos 90 días' },
  { value: 'all', label: 'Todo el historial' },
  { value: 'custom', label: 'Fechas personalizadas' }
]
export default {
  name: 'CierresCajaPage',
  data () {
    return {
      filters: emptyFilters(),
      appliedFilters: emptyFilters(),
      period: 7,
      periodOptions: PERIOD_OPTIONS,
      agencies: [],
      accounts: [],
      report: null,
      view: 'todos',
      perPage: 15,
      loading: false,
      loadingOptions: false,
      optionsError: '',
      errorMessage: '',
      selectedClosure: null,
      reportAbort: null,
      optionsAbort: null,
      disposed: false
    }
  },
  computed: {
    isAdmin () { return String(this.$store.user?.id) === '1' },
    identityKey () {
      const user = this.$store.user
      return user?.id ? String(user.id) + ':' + String(user.agencia_id || '') : ''
    },
    agencyOptions () { return [{ id: '', nombre: 'Todas las sucursales' }, ...this.agencies] },
    accountOptions () {
      return [{ id: '', name: 'Todas las cuentas' }, ...this.accounts.filter(account =>
        !this.filters.agencia_id || account.agencia_ids.some(id => String(id) === String(this.filters.agencia_id)))]
    },
    summary () { return this.report?.resumen || {} },
    pagination () { return { page: this.report?.cierres.current_page || 1, rowsPerPage: this.perPage, rowsNumber: this.report?.cierres.total || 0 } },
    viewOptions () { return VIEW_OPTIONS.filter(option => this.isAdmin || !['anomalias', 'faltantes', 'sobrantes'].includes(option.value)) },
    viewTitle () { return VIEW_OPTIONS.find(option => option.value === this.view)?.label || 'Historial de turnos' },
    scopeLabel () {
      const filter = this.appliedFilters
      const branch = this.agencies.find(item => String(item.id) === String(filter.agencia_id))?.nombre
      const account = this.accounts.find(item => String(item.id) === String(filter.user_id))?.name
      return [branch || (filter.agencia_id ? 'Sucursal #' + filter.agencia_id : 'Todas las sucursales'),
        account || (filter.user_id ? 'Cuenta #' + filter.user_id : 'Todas las cuentas'),
        filter.desde || filter.hasta ? (filter.desde || 'Inicio') + ' al ' + (filter.hasta || 'Sin límite') : 'Todo el historial'].join(' · ')
    },
    columns () {
      return [
        { name: 'turno', label: 'Turno y fecha', align: 'left' },
        { name: 'cuenta', label: 'Cuenta / sucursal', align: 'left' },
        { name: 'monto_fisico', label: 'Efectivo contado', align: 'right' },
        ...(this.isAdmin ? [
          { name: 'monto_sistema_efectivo', label: 'Efectivo esperado', align: 'right' },
          { name: 'monto_sistema_digital', label: 'Digital / QR', align: 'right' },
          { name: 'diferencia', label: 'Diferencia', align: 'right' }
        ] : []),
        { name: 'estado', label: 'Estado', align: 'left' },
        { name: 'detalle', label: '', align: 'right' }
      ]
    }
  },
  watch: {
    identityKey: {
      immediate: true,
      handler (key) {
        this.reportAbort?.abort()
        this.optionsAbort?.abort()
        this.report = null
        this.selectedClosure = null
        this.agencies = []
        this.accounts = []
        this.period = 7
        this.filters = { ...emptyFilters(), ...periodDates() }
        this.appliedFilters = { ...this.filters }
        this.view = 'todos'
        this.errorMessage = ''
        if (key) {
          this.filters.agencia_id = this.$store.user.agencia_id || ''
          this.appliedFilters = { ...this.filters }
          this.loadOptions()
          this.loadReport()
        }
      }
    }
  },
  beforeUnmount () {
    this.disposed = true
    this.reportAbort?.abort()
    this.optionsAbort?.abort()
  },
  methods: {
    async loadOptions () {
      this.optionsAbort?.abort()
      const controller = new AbortController()
      this.optionsAbort = controller
      this.loadingOptions = true
      this.optionsError = ''
      try {
        const { data } = await this.$axios.get('cash-closures/report-options', { signal: controller.signal })
        if (this.disposed || controller.signal.aborted || this.optionsAbort !== controller) return
        this.agencies = data.agencias
        this.accounts = data.cuentas
      } catch (error) {
        if (!controller.signal.aborted && !this.disposed) this.optionsError = 'No se pudieron cargar las sucursales y cuentas. Pulsa Reintentar.'
      } finally {
        if (this.optionsAbort === controller && !this.disposed) this.loadingOptions = false
      }
    },
    onAgencyChange () {
      if (!this.accountOptions.some(account => String(account.id) === String(this.filters.user_id))) this.filters.user_id = ''
    },
    onPeriodChange (period) {
      if (period === 'custom') return
      Object.assign(this.filters, period === 'all' ? { desde: '', hasta: '' } : periodDates(period))
      this.applyFilters()
    },
    applyFilters () {
      if (this.filters.desde && this.filters.hasta && this.filters.desde > this.filters.hasta) {
        this.errorMessage = 'La fecha Desde no puede ser posterior a Hasta.'
        return
      }
      this.appliedFilters = { ...this.filters }
      this.loadReport()
    },
    clearFilters () {
      this.period = 7
      this.filters = { ...emptyFilters(), ...periodDates() }
      if (!this.isAdmin) this.filters.agencia_id = this.$store.user.agencia_id || ''
      this.view = 'todos'
      this.applyFilters()
    },
    changeView (view) {
      this.view = view
      this.loadReport()
    },
    onTableRequest ({ pagination }) {
      this.perPage = pagination.rowsPerPage
      this.loadReport(pagination.page)
    },
    refresh () {
      this.loadOptions()
      this.loadReport(this.report?.cierres.current_page || 1)
    },
    async loadReport (page = 1) {
      if (!this.identityKey || this.disposed) return
      this.reportAbort?.abort()
      const controller = new AbortController()
      this.reportAbort = controller
      this.loading = true
      this.errorMessage = ''
      this.selectedClosure = null
      // Hide previous scope immediately; a failed request must not label old totals as new ones.
      this.report = null
      try {
        const params = { ...this.appliedFilters, vista: this.view, page, per_page: this.perPage }
        Object.keys(params).forEach(key => { if (params[key] === '') delete params[key] })
        const { data } = await this.$axios.get('cash-closures/report', { params, signal: controller.signal })
        if (this.disposed || controller.signal.aborted || this.reportAbort !== controller) return
        // A concurrent deletion can leave a now-empty last page.
        if (data.cierres.current_page > data.cierres.last_page) {
          await this.loadReport(data.cierres.last_page)
          return
        }
        this.report = data
      } catch (error) {
        if (!controller.signal.aborted && !this.disposed) this.errorMessage = error.response?.data?.message || 'No se pudo cargar el historial. Pulsa Actualizar para reintentar.'
      } finally {
        if (this.reportAbort === controller && !this.disposed) this.loading = false
      }
    },
    isClosed (row) { return row.estado === 'CERRADO' },
    money (value) {
      if (value === null || value === undefined) return '—'
      return 'Bs ' + Number(value).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    },
    differenceClass (row) {
      if (!this.isClosed(row) || row.diferencia === null || row.diferencia === undefined) return ''
      const cents = Math.round(Number(row.diferencia) * 100)
      return cents < 0 ? 'is-shortage' : cents > 0 ? 'is-surplus' : 'is-normal'
    },
    differenceLabel (row) {
      if (!this.isClosed(row) || row.diferencia === null || row.diferencia === undefined) return 'Sin evaluar'
      return (Number(row.diferencia) > 0 ? '+' : '') + this.money(row.diferencia)
    },
    statusLabel (row) { return { ABIERTO: 'Abierto', PENDIENTE: 'Pendiente de arqueo', CERRADO: 'Cerrado' }[row.estado] || row.estado },
    formatDate (value) {
      if (!value) return '—'
      // SQL local timestamps do not carry a zone; preserve their wall-clock time.
      const date = new Date(value.includes('T') ? value : value.replace(' ', 'T'))
      return Number.isNaN(date.getTime()) ? value : date.toLocaleString('es-BO', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
    }
  }
}
</script>

<style scoped>
.closures-page { background: #f5f7fb; padding: 18px; color: #28374d; }
.page-heading, .heading-title { display: flex; align-items: center; gap: 16px; }
.page-heading { justify-content: space-between; margin-bottom: 14px; }
.heading-icon { display: grid; place-items: center; width: 44px; height: 44px; border-radius: 12px; background: #e6eefc; color: #2860bc; flex-shrink: 0; }
.eyebrow { color: #6b7f9d; font-size: 10px; font-weight: 700; letter-spacing: 1.5px; }
h1 { font-size: 24px; line-height: 1.25; letter-spacing: -.6px; font-weight: 700; margin: 2px 0; }
h2 { font-size: 18px; line-height: 1.4; font-weight: 700; margin: 0; }
.page-heading p, .history-heading p { color: #748198; font-size: 12px; margin: 5px 0 0; }
.page-heading > .q-btn { border-radius: 10px; color: #466183; }
.filters-panel, .history-panel { background: #fff; border: 1px solid #e3e8f1; border-radius: 16px; }
.filters-panel { padding: 12px; }
.filter-fields { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1.3fr) minmax(0, 1fr) auto; gap: 10px; align-items: center; }
.filter-actions { display: flex; justify-content: flex-end; gap: 4px; }
.filter-actions .q-btn { border-radius: 8px; }
.custom-dates { display: grid; grid-template-columns: repeat(2, minmax(0, 220px)); gap: 10px; margin-top: 10px; }
.branch-label { display: flex; align-items: center; gap: 8px; }
.scope-label { display: flex; align-items: center; gap: 6px; color: #758399; font-size: 11px; margin: 10px 2px 8px; overflow-wrap: anywhere; }
.overview { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
.overview-card { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: #fff; border: 1px solid #e3e8f1; border-radius: 10px; color: inherit; text-align: left; cursor: pointer; font-family: inherit; transition: border-color .15s, box-shadow .15s; }
.overview-card:hover, .overview-card.selected { border-color: #99b6e8; box-shadow: 0 3px 12px #224b8810; }
.overview-card:focus-visible { outline: 2px solid #2860bc; outline-offset: 3px; }
.overview-card small { display: block; font-size: 11px; color: #6d7a8e; line-height: 1.5; }
.overview-card strong { font-size: 23px; line-height: 1.2; font-weight: 700; grid-column: 1; grid-row: 1 / 3; align-self: center; margin-right: 8px; }
.metric-copy { display: grid; grid-template-columns: auto 1fr; align-items: center; min-width: 0; }
.metric-copy small, .metric-copy .metric-caption { grid-column: 2; }
.overview-card em { font-size: 11px; font-style: normal; font-weight: 400; color: #7b879b; }
.metric-caption { display: block; font-size: 10px; color: #7b879b; line-height: 1.6; }
.metric-icon { width: 28px; height: 28px; display: grid; place-items: center; border-radius: 8px; font-size: 18px; flex-shrink: 0; }
.metric-icon.neutral { background: #edf2fa; color: #4a6b9b; }
.metric-icon.shortage { background: #fff0f1; color: #c34357; }
.metric-icon.surplus { background: #e8f6ee; color: #227448; }
.metric-icon.pending { background: #f1edfc; color: #8060b6; }
.history-heading { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 14px; }
.history-heading > .q-select { min-width: 210px; }
.anomaly-strip { display: flex; align-items: center; gap: 10px; background: #f6f8fc; color: #5d708e; padding: 6px 14px; border-top: 1px solid #edf0f6; font-size: 11px; }
.anomaly-strip.has-alerts { background: #fff7ec; color: #92621e; }
.anomaly-strip > span { flex: 1; }
.threshold-note { display: inline-block; font-size: 10px; margin-left: 10px; opacity: .85; }
.closures-table { border-radius: 0 0 16px 16px; }
.closures-table :deep(thead tr) { background: #f8fafd; }
.closures-table :deep(th) { color: #7a889e; font-size: 11px; font-weight: 600; }
.closures-table :deep(td) { height: 46px; font-size: 12px; border-color: #f0f3f8; padding-top: 6px; padding-bottom: 6px; }
.closures-table :deep(td:nth-child(2)) { white-space: normal; min-width: 180px; max-width: 280px; }
.cell-secondary { display: block; color: #8a95a6; font-size: 10px; margin-top: 3px; }
.difference { display: inline-block; border-radius: 7px; padding: 5px 8px; font-weight: 600; font-size: 12px; }
.is-shortage { background: #fff0f1; color: #c34357; }
.is-surplus { background: #e8f6ee; color: #227448; }
.is-normal { background: #f0f4f8; color: #576b84; }
.status-chip { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: 600; background: #eff3f8; color: #657790; }
.status-chip.abierto { color: #247c70; background: #eaf7f2; }
.status-chip.pendiente { color: #8560aa; background: #f3edfb; }
.loading-placeholder { padding: 70px 20px; text-align: center; color: #7d8ca2; }
.mobile-turno article { border: 1px solid #e5eaf2; border-radius: 10px; padding: 12px; height: 100%; }
.mobile-turno h3 { font-size: 14px; font-weight: 600; line-height: 1.5; margin: 8px 0 3px; }
.mobile-turno p { font-size: 11px; color: #7b879b; margin: 4px 0; }
.mobile-money { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin: 10px 0 4px; font-size: 10px; color: #7b879b; }
.mobile-money b { display: block; font-size: 12px; margin-top: 4px; }
.mobile-money b:not(.difference) { color: #28374d; }
.closure-detail { width: 580px; max-width: 95vw; border-radius: 18px; color: #28374d; }
.closure-detail .q-card__section { padding: 24px; }
.detail-list { display: grid; grid-template-columns: 135px 1fr; gap: 12px; font-size: 12px; margin: 24px 0; }
.detail-list dt { color: #8792a2; }
.detail-list dd { margin: 0; overflow-wrap: anywhere; }
.detail-amounts { background: #f6f8fc; border-radius: 12px; padding: 12px 16px; }
.detail-amounts > div { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 8px 0; font-size: 12px; }
.detail-amounts span { color: #7a889b; }
.detail-difference { border-top: 1px solid #e2e8f0; margin-top: 8px; }
.detail-notes { font-size: 12px; margin-top: 22px; overflow-wrap: anywhere; white-space: pre-wrap; }
.detail-notes p { color: #8390a2; margin: 7px 0 16px; }
@media (max-width: 1100px) { .overview { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 850px) { .closures-page { padding: 14px; } .filter-fields { grid-template-columns: repeat(2, minmax(0, 1fr)); } .history-heading { align-items: flex-start; flex-direction: column; } .history-heading > .q-select { width: 100%; } }
@media (max-width: 599px) { .closures-page { padding: 10px; } .heading-icon, .page-heading .eyebrow, .page-heading p { display: none; } h1 { font-size: 22px; } .page-heading { gap: 8px; margin-bottom: 10px; } .page-heading > .q-btn { width: 36px; min-width: 36px; height: 36px; flex-shrink: 0; } .page-heading > .q-btn :deep(.q-btn__content > .block) { display: none; } .page-heading > .q-btn :deep(.q-icon) { margin: 0; } .filter-fields > :nth-child(-n+2) { grid-column: 1 / -1; } .filter-actions .q-btn { padding-left: 7px; padding-right: 7px; } .overview { gap: 8px; } .overview-card { padding: 8px; gap: 6px; } .overview-card strong { font-size: 20px; margin-right: 5px; } .metric-icon { display: none; } .overview-card small { font-size: 10px; } .metric-caption { font-size: 9px; } .anomaly-strip { flex-wrap: wrap; gap: 6px; } .anomaly-strip > span { min-width: 180px; } .threshold-note { display: block; margin-left: 0; } .anomaly-strip > .q-btn { margin-left: 24px; } .history-heading p { font-size: 10px; } .detail-list { grid-template-columns: 110px 1fr; } }
</style>

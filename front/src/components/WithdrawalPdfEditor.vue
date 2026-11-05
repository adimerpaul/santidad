<template>
  <q-dialog :model-value="modelValue" persistent :maximized="$q.screen.lt.md" @before-show="loadDraft">
    <q-card class="withdrawal-editor" :style="{ width: $q.screen.lt.md ? '100%' : '1480px', maxWidth: $q.screen.lt.md ? '100%' : '96vw' }">
      <header class="editor-header">
        <div><div class="text-caption text-primary text-weight-bold">PRODUCTOS RETIRADOS</div><h2>Preparar PDF</h2><p>Ajusta el documento después de comparar los productos físicos. El stock y las bajas registradas se conservan.</p></div>
        <q-btn flat round icon="close" aria-label="Cerrar preparación del PDF" @click="requestClose" />
      </header>
      <q-separator />
      <section class="editor-content">
        <div class="scope-note"><q-icon name="filter_alt" size="16px" /> {{ scope }}</div>
        <div v-if="loading" class="loading-state"><q-spinner color="primary" size="30px" /><p>Cargando todos los productos del filtro…</p></div>
        <q-banner v-if="errorMessage" rounded class="bg-red-1 text-red-9 q-mb-md" role="alert">
          {{ errorMessage }}
          <template v-if="loadFailed" #action><q-btn flat no-caps label="Reintentar" @click="loadDraft" /></template>
        </q-banner>
        <template v-if="loaded && !loading">
          <div class="document-fields">
            <q-input v-model="title" outlined dense label="Título del documento" maxlength="100" />
            <q-input v-model="notes" outlined dense autogrow label="Observaciones generales para el PDF" maxlength="3000" />
          </div>
          <div class="editor-tools">
            <q-input v-model="search" outlined dense clearable label="Buscar en el borrador"><template #prepend><q-icon name="search" /></template></q-input>
            <div class="editor-count"><b>{{ includedRows.length }}</b> incluidos <span v-if="excludedCount">· {{ excludedCount }} excluidos</span><span>· {{ changedCount }} ajustados</span></div>
            <q-toggle v-model="showExcluded" dense label="Ver excluidos" />
            <q-btn v-if="excludedCount" flat dense no-caps color="primary" label="Incluir todos" @click="includeAll" />
          </div>
          <div class="editor-help">Se cargan todos los resultados del filtro, no solo la página visible. La búsqueda del borrador no cambia qué productos se incluyen en el PDF.</div>
          <q-table flat bordered dense :rows="visibleRows" :columns="columns" row-key="id" v-model:pagination="pagination"
            :rows-per-page-options="[10, 20, 50]" rows-per-page-label="Productos por página" no-data-label="No hay productos para mostrar." class="draft-table">
            <template #body="props">
              <q-tr :props="props" :class="{ 'excluded-row': !props.row.included }"
                @focusin="focusedRowId = props.row.id" @focusout="onRowFocusOut">
                <q-td key="product" :props="props" class="product-cell"><b>{{ props.row.productName }}</b><small>{{ props.row.agency }} · Informe #{{ props.row.reportId }}</small><span v-if="rowChanged(props.row)" class="changed-label">Ajustado para este PDF</span></q-td>
                <q-td key="lot" :props="props"><q-input v-model="props.row.lot" outlined dense maxlength="100" :disable="!props.row.included" :aria-label="'Lote de ' + props.row.productName" /></q-td>
                <q-td key="invoice" :props="props"><q-input v-model="props.row.invoiceNumber" outlined dense maxlength="100" :disable="!props.row.included" :aria-label="'Factura de ' + props.row.productName" /></q-td>
                <q-td key="quantity" :props="props"><q-input v-model="props.row.quantity" outlined dense type="number" step="1" min="-1000000" max="1000000" :disable="!props.row.included" :aria-label="'Cantidad de ' + props.row.productName" /><small class="original-value">Registrado: {{ props.row.original.quantity }}</small></q-td>
                <q-td key="unitPrice" :props="props"><q-input v-model="props.row.unitPrice" outlined dense type="number" step="0.01" min="0" max="1000000" :disable="!props.row.included" :aria-label="'Precio de ' + props.row.productName" /></q-td>
                <q-td key="total" :props="props" class="text-right text-weight-bold">{{ lineMoney(props.row) }}</q-td>
                <q-td key="reason" :props="props"><q-input v-model="props.row.reason" outlined dense autogrow maxlength="120" :disable="!props.row.included" :aria-label="'Motivo de ' + props.row.productName" /></q-td>
                <q-td key="observation" :props="props"><q-input v-model="props.row.description" outlined dense autogrow maxlength="1000" label="Descripción" :disable="!props.row.included" /><q-input v-model="props.row.observation" class="q-mt-xs" outlined dense autogrow maxlength="1000" label="Observación de revisión" :disable="!props.row.included" /></q-td>
                <q-td key="actions" :props="props" class="text-center">
                  <q-btn v-if="props.row.included" flat round dense color="negative" icon="remove_circle_outline" :aria-label="'Excluir ' + props.row.productName" @click="props.row.included = false"><q-tooltip>Quitar del PDF</q-tooltip></q-btn>
                  <q-btn v-else flat round dense color="positive" icon="add_circle_outline" :aria-label="'Incluir ' + props.row.productName" @click="props.row.included = true"><q-tooltip>Volver a incluir</q-tooltip></q-btn>
                  <q-btn v-if="rowChanged(props.row)" flat round dense color="grey-7" icon="undo" :aria-label="'Restaurar ' + props.row.productName" @click="restoreRow(props.row)"><q-tooltip>Restaurar datos registrados</q-tooltip></q-btn>
                </q-td>
              </q-tr>
            </template>
          </q-table>
          <div class="editor-help">Precio fact. inicial = precio normal / 1,30, redondeado a 2 decimales. Total = cantidad × precio fact. Las cantidades negativas se conservan para ajustes de inventario.</div>
        </template>
      </section>
      <footer class="editor-footer">
        <div><span class="text-caption text-grey-7">Total del documento</span><strong>{{ totalMoney }}</strong></div>
        <q-space />
        <q-btn flat no-caps label="Cancelar" @click="requestClose" />
        <q-btn unelevated no-caps color="primary" icon="picture_as_pdf" label="Generar y descargar PDF" :loading="generating" :disable="loading || !loaded || !includedRows.length" @click="download" />
      </footer>
    </q-card>
  </q-dialog>
</template>

<script>
import { createWithdrawalDraft, withdrawalRowChanged, withdrawalLineCents, validateWithdrawalDraft, buildWithdrawalPdf } from 'src/utils/withdrawal-pdf'

const DEFAULT_TITLE = 'Reporte de productos retirados'
export default {
  name: 'WithdrawalPdfEditor',
  props: {
    modelValue: Boolean,
    filters: { type: Object, required: true },
    scope: { type: String, default: '' }
  },
  emits: ['update:modelValue'],
  data () {
    return {
      rows: [],
      title: DEFAULT_TITLE,
      notes: '',
      search: '',
      focusedRowId: null,
      showExcluded: false,
      loaded: false,
      loading: false,
      loadFailed: false,
      generating: false,
      errorMessage: '',
      requestAbort: null,
      disposed: false,
      pagination: { page: 1, rowsPerPage: 20 },
      columns: [
        { name: 'product', label: 'Producto / sucursal', align: 'left' },
        { name: 'lot', label: 'Lote', align: 'left' },
        { name: 'invoice', label: 'N.º factura', align: 'left' },
        { name: 'quantity', label: 'Cantidad', align: 'left' },
        { name: 'unitPrice', label: 'Precio fact. Bs', align: 'left' },
        { name: 'total', label: 'Total Bs', align: 'right' },
        { name: 'reason', label: 'Motivo', align: 'left' },
        { name: 'observation', label: 'Descripción y observaciones', align: 'left' },
        { name: 'actions', label: '', align: 'center' }
      ]
    }
  },
  computed: {
    includedRows () { return this.rows.filter(row => row.included) },
    excludedCount () { return this.rows.length - this.includedRows.length },
    changedCount () { return this.rows.filter(row => row.included && withdrawalRowChanged(row)).length },
    dirty () { return this.title !== DEFAULT_TITLE || this.notes !== '' || this.rows.some(withdrawalRowChanged) },
    visibleRows () {
      const query = String(this.search || '').trim().toLocaleLowerCase()
      return this.rows.filter(row => (this.showExcluded || row.included) &&
        (!query || row.id === this.focusedRowId || [row.productName, row.lot, row.invoiceNumber, row.reason, row.observation, row.agency].join(' ').toLocaleLowerCase().includes(query)))
    },
    totalMoney () {
      const cents = this.includedRows.reduce((sum, row) => sum + withdrawalLineCents(row), 0)
      return Number.isFinite(cents) ? 'Bs ' + (cents / 100).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : 'Revisa los importes'
    },
    identityKey () { return String(this.$store.user?.id || '') + ':' + String(this.$store.user?.agencia_id || '') }
  },
  watch: {
    modelValue (open) { if (!open) this.requestAbort?.abort() },
    identityKey () {
      this.requestAbort?.abort()
      this.rows = []
      this.loaded = false
      this.$emit('update:modelValue', false)
    },
    visibleRows () {
      const lastPage = Math.max(1, Math.ceil(this.visibleRows.length / this.pagination.rowsPerPage))
      if (this.pagination.page > lastPage) this.pagination.page = lastPage
    }
  },
  beforeUnmount () {
    this.disposed = true
    this.requestAbort?.abort()
  },
  methods: {
    rowChanged: withdrawalRowChanged,
    async loadDraft () {
      this.requestAbort?.abort()
      const controller = new AbortController()
      this.requestAbort = controller
      this.loading = true
      this.loaded = false
      this.loadFailed = false
      this.errorMessage = ''
      this.rows = []
      this.title = DEFAULT_TITLE
      this.notes = ''
      this.search = ''
      this.focusedRowId = null
      this.showExcluded = false
      this.pagination = { page: 1, rowsPerPage: 20 }
      try {
        const params = { ...this.filters, page: 1, rowsPerPage: 0, sortBy: 'id', descending: true }
        const { data } = await this.$axios.get('withdrawal-reports/all-items', { params, signal: controller.signal })
        if (this.disposed || controller.signal.aborted || this.requestAbort !== controller || !this.modelValue) return
        // Never silently generate a report from a partial result.
        if (!Array.isArray(data.data) || data.data.length !== Number(data.total)) throw new Error('El servidor devolvió una lista incompleta. Actualiza el filtro y reintenta.')
        this.rows = createWithdrawalDraft(data.data)
        this.loaded = true
      } catch (error) {
        if (!controller.signal.aborted && !this.disposed) {
          this.loadFailed = true
          this.errorMessage = error.response?.data?.message || error.message || 'No se pudieron cargar los productos.'
        }
      } finally {
        if (this.requestAbort === controller && !this.disposed) this.loading = false
      }
    },
    onRowFocusOut (event) {
      if (!event.currentTarget.contains(event.relatedTarget)) this.focusedRowId = null
    },
    restoreRow (row) { Object.assign(row, row.original) },
    includeAll () { this.rows.forEach(row => { row.included = true }) },
    lineMoney (row) {
      const cents = withdrawalLineCents(row)
      return Number.isFinite(cents) ? (cents / 100).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—'
    },
    requestClose () {
      if (this.generating) return
      const close = () => {
        this.requestAbort?.abort()
        this.$emit('update:modelValue', false)
      }
      if (!this.dirty) return close()
      this.$q.dialog({
        title: '¿Cerrar el borrador?',
        message: 'Los ajustes de este documento se descartarán. Las bajas registradas se conservan.',
        cancel: { label: 'Seguir editando', flat: true },
        ok: { label: 'Descartar borrador', color: 'negative' }
      }).onOk(close)
    },
    download () {
      if (this.generating || !this.loaded || this.loading) return
      this.errorMessage = validateWithdrawalDraft(this.rows)
      if (this.errorMessage) return
      this.generating = true
      try {
        const doc = buildWithdrawalPdf({
          rows: this.rows, title: this.title, notes: this.notes, scope: this.scope, preparedBy: this.$store.user?.name
        })
        doc.save('productos_retirados_preparado.pdf')
        this.$q.notify({ color: 'positive', message: 'PDF generado con los productos y ajustes del borrador.', icon: 'check' })
      } catch (error) {
        this.errorMessage = error.message || 'No se pudo generar el PDF. El borrador se conserva para reintentar.'
      } finally {
        this.generating = false
      }
    }
  }
}
</script>

<style scoped>
.withdrawal-editor { width: 1480px; max-width: 96vw; height: 90vh; max-height: 94vh; display: flex; flex-direction: column; color: #2a3b52; border-radius: 16px; }
.editor-header { display: flex; align-items: flex-start; justify-content: space-between; padding: 16px 20px; gap: 12px; }
.editor-header h2 { font-size: 22px; line-height: 1.3; font-weight: 700; margin: 3px 0; }
.editor-header p { color: #7b8799; font-size: 12px; margin: 5px 0 0; }
.editor-content { padding: 14px 20px; flex: 1; overflow: auto; min-height: 0; }
.scope-note { color: #73829a; font-size: 11px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
.document-fields { display: grid; grid-template-columns: 1fr 1.5fr; gap: 12px; }
.editor-tools { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; margin: 14px 0 8px; }
.editor-tools > .q-input { width: 280px; max-width: 100%; }
.editor-count { font-size: 12px; color: #77859b; flex: 1; }
.editor-count b { color: #26558c; }
.editor-help { color: #8793a5; font-size: 10px; margin: 8px 0; }
.draft-table { border-color: #e4e9f1; border-radius: 10px; }
.draft-table :deep(thead) { background: #f4f7fc; color: #75849b; }
.draft-table :deep(td) { padding: 10px 7px; vertical-align: top; }
.draft-table :deep(td:nth-child(2)) { min-width: 115px; }
.draft-table :deep(td:nth-child(3)) { min-width: 130px; }
.draft-table :deep(td:nth-child(4)), .draft-table :deep(td:nth-child(5)) { min-width: 110px; }
.draft-table :deep(td:nth-child(7)) { min-width: 160px; }
.draft-table :deep(td:nth-child(8)) { min-width: 240px; }
.product-cell { min-width: 200px; max-width: 260px; white-space: normal; }
.product-cell b { font-size: 12px; }
.product-cell small { display: block; margin-top: 4px; font-size: 10px; color: #8491a4; }
.original-value { display: block; font-size: 10px; color: #91a0b2; padding-top: 4px; }
.changed-label { display: inline-block; font-size: 9px; color: #9b701a; background: #fff5dc; border-radius: 4px; padding: 2px 5px; margin-top: 5px; }
.excluded-row { background: #f7f8fa; }
.excluded-row .product-cell { opacity: .6; }
.editor-footer { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; padding: 12px 20px; border-top: 1px solid #e5eaf2; background: #f8fafd; }
.editor-footer strong { display: block; font-size: 20px; color: #26558c; }
.editor-footer .q-btn { border-radius: 8px; }
.loading-state { text-align: center; padding: 70px 10px; color: #8391a5; }
@media (max-width: 1023px) { .withdrawal-editor { width: 100%; max-width: 100%; height: 100%; max-height: 100%; border-radius: 0; } }
@media (max-width: 599px) { .editor-header, .editor-content { padding: 12px; } .document-fields { grid-template-columns: 1fr; } .editor-tools { gap: 8px; } .editor-tools > .q-input { width: 100%; } .editor-footer { padding: 10px 12px; } .editor-footer > .q-btn:last-child { width: 100%; } }
</style>

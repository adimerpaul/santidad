const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')
const root = path.join(__dirname, '..')
const utilitySource = fs.readFileSync(path.join(root, 'src/utils/withdrawal-pdf.js'), 'utf8').replace(/^import .*$/gm, '').replace(/export function /g, 'function ')
const ctx = { module: { exports: {} } }
vm.createContext(ctx)
vm.runInContext(utilitySource + '\nmodule.exports = { withdrawalInvoiceNumber, createWithdrawalDraft, withdrawalRowChanged, withdrawalLineCents, validateWithdrawalDraft };', ctx)
const helpers = ctx.module.exports
test('invoice numbers prefer the linked purchase invoice, retain leading zeros and support older purchases', () => {
  assert.equal(helpers.withdrawalInvoiceNumber({ buy: { factura: 'ANTIGUA', purchase_invoice: { numero_factura: '000123' } } }), '000123')
  assert.equal(helpers.withdrawalInvoiceNumber({ buy: { factura: '000045' } }), '000045')
  assert.equal(helpers.withdrawalInvoiceNumber({ buy: { factura: 0, purchase_invoice: { numero_factura: '99001' } } }), '99001')
  assert.equal(helpers.withdrawalInvoiceNumber({ buy: { factura: '  ' } }), 'Sin factura')
  assert.equal(helpers.withdrawalInvoiceNumber({ buy: { factura: 0 } }), 'Sin factura')
  assert.equal(helpers.withdrawalInvoiceNumber({ buy: null }), 'Sin factura')
})
const record = (id = 1) => ({ id, withdrawal_report_id: 10, cantidad: -5, product: { nombre: 'PRODUCTO ' + id, precio: 13 }, buy: { lote: 'LOTE-A' }, agencia: { nombre: 'Matriz' }, tipo: 'VENCIMIENTO', descripcion: 'Descripción original' })
const editorSource = fs.readFileSync(path.join(root, 'src/components/WithdrawalPdfEditor.vue'), 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1].replace(/^import .*$/gm, '').replace('export default', 'module.exports =')
const editorCtx = { module: { exports: {} }, AbortController, ...helpers, buildWithdrawalPdf: args => { editorCtx.lastPdf = args; return { save: () => {} } } }
vm.createContext(editorCtx); vm.runInContext(editorSource, editorCtx)
const component = editorCtx.module.exports
function state () {
  const s = {
    ...component.data(), filters: { agencia_id: [2], mes: 9, anio: 2026, tipo: 'VENCIMIENTO', tipo_reporte: 'INFORMES_MENSUALES', search: 'PRODUCTO' },
    modelValue: true, scope: 'Filtro de prueba', $store: { user: { id: 1, name: 'Administrador' } }, $q: { notify: () => {} }, $emit: () => {}, calls: []
  }
  Object.entries(component.computed).forEach(([key, fn]) => Object.defineProperty(s, key, { get: () => fn.call(s) }))
  Object.entries(component.methods).forEach(([key, fn]) => { s[key] = fn.bind(s) })
  s.$axios = { get: async (url, options) => { s.calls.push({ url, ...options }); return { data: { data: [record(1), record(2)], total: 2 } } } }
  return s
}
test('editing a draft never changes the original withdrawal, product or buy objects', () => {
  const original = record()
  const before = JSON.stringify(original)
  const rows = helpers.createWithdrawalDraft([original])
  rows[0].quantity = -2; rows[0].lot = 'OTRO'; rows[0].description = 'Revisado'; rows[0].observation = 'Dos unidades físicas'; rows[0].unitPrice = 12
  assert.equal(JSON.stringify(original), before)
  assert.equal(rows[0].original.quantity, -5)
  assert.equal(rows[0].original.lot, 'LOTE-A')
  assert.equal(helpers.withdrawalRowChanged(rows[0]), true)
  Object.assign(rows[0], rows[0].original)
  assert.equal(helpers.withdrawalRowChanged(rows[0]), false)
  rows[0].invoiceNumber = '00000999'
  assert.equal(helpers.withdrawalRowChanged(rows[0]), true)
  assert.equal(rows[0].original.invoiceNumber, 'Sin factura')
  assert.equal(JSON.stringify(original), before)
  Object.assign(rows[0], rows[0].original)
  assert.equal(helpers.withdrawalRowChanged(rows[0]), false)
})
test('totals use the displayed unit price in cents and preserve the sign of inventory adjustments', () => {
  const row = helpers.createWithdrawalDraft([{ ...record(), product: { nombre: 'Decimal', precio: 35 } }])[0]
  assert.equal(row.unitPrice, 26.92)
  assert.equal(helpers.withdrawalLineCents(row), -13460)
  row.quantity = 3
  assert.equal(helpers.withdrawalLineCents(row), 8076)
  row.unitPrice = 0.1
  assert.equal(helpers.withdrawalLineCents(row), 30)
})
test('the withdrawal location is independent of the branch that owns the report', () => {
  const base = { ...record(), agencia: null, report: { agencia: { nombre: 'Matriz' } } }
  const [warehouse, branch] = helpers.createWithdrawalDraft([base, { ...base, buy: { agencia: { nombre: 'Norte' } } }])
  assert.equal(warehouse.agency, 'Almacén')
  assert.equal(branch.agency, 'Norte')
})
test('clearing an included amount cannot display a valid zero total', async () => {
  const s = state()
  await s.loadDraft()
  s.rows[0].quantity = ''
  assert.equal(s.lineMoney(s.rows[0]), '—')
  assert.equal(s.totalMoney, 'Revisa los importes')
  assert.ok(helpers.validateWithdrawalDraft(s.rows))
})
test('a row found by invoice stays visible while its number is being corrected', async () => {
  const s = state()
  await s.loadDraft()
  s.search = '00012'
  s.rows[0].invoiceNumber = '00012'
  assert.equal(s.visibleRows.length, 1)
  s.focusedRowId = s.rows[0].id
  s.rows[0].invoiceNumber = '00093'
  assert.equal(s.visibleRows.length, 1)
  s.onRowFocusOut({ currentTarget: { contains: () => false }, relatedTarget: null })
  assert.equal(s.visibleRows.length, 0)
})
test('invalid amounts and empty motives block export, but excluded rows are not validated or counted', () => {
  const row = helpers.createWithdrawalDraft([record()])[0]
  for (const bad of ['', null, 'abc', Infinity, 1.5, 1000001]) {
    row.quantity = bad
    assert.notEqual(helpers.validateWithdrawalDraft([row]), '')
  }
  row.quantity = -2
  for (const bad of ['', null, -1, 'abc', Infinity, 1.001, 1000001]) {
    row.unitPrice = bad
    assert.notEqual(helpers.validateWithdrawalDraft([row]), '')
  }
  row.unitPrice = 10
  assert.equal(helpers.validateWithdrawalDraft([row]), '')
  row.reason = ''
  assert.notEqual(helpers.validateWithdrawalDraft([row]), '')
  row.included = false
  assert.match(helpers.validateWithdrawalDraft([row]), /al menos/)
  assert.equal(helpers.validateWithdrawalDraft([row, helpers.createWithdrawalDraft([record(2)])[0]]), '')
})
test('editor loads every filtered result in one GET rather than using the current table page', async () => {
  const s = state()
  await s.loadDraft()
  assert.equal(s.calls.length, 1)
  assert.equal(s.calls[0].url, 'withdrawal-reports/all-items')
  assert.equal(s.calls[0].params.rowsPerPage, 0)
  assert.equal(s.calls[0].params.page, 1)
  assert.equal(s.calls[0].params.tipo_reporte, 'INFORMES_MENSUALES')
  assert.equal(s.calls[0].params.search, 'PRODUCTO')
  assert.equal(s.calls[0].params.agencia_id[0], 2)
  assert.equal(s.rows.length, 2)
  assert.equal(s.loaded, true)
})
test('partial loads cannot produce an apparently complete PDF', async () => {
  const s = state()
  s.$axios.get = async () => ({ data: { data: [record()], total: 100 } })
  await s.loadDraft()
  assert.equal(s.loaded, false)
  assert.equal(s.rows.length, 0)
  assert.match(s.errorMessage, /incompleta/)
})
test('closed or unmounted editors discard late responses', async () => {
  const s = state()
  let resolve
  s.$axios.get = () => new Promise(r => { resolve = r })
  const request = s.loadDraft()
  s.modelValue = false; component.watch.modelValue.call(s, false)
  resolve({ data: { data: [record()], total: 1 } }); await request
  assert.equal(s.rows.length, 0)
  s.modelValue = true
  const second = s.loadDraft()
  component.beforeUnmount.call(s)
  resolve({ data: { data: [record()], total: 1 } }); await second
  assert.equal(s.rows.length, 0)
})
test('local search does not silently exclude products from PDF, and removed rows can be restored', async () => {
  const s = state()
  await s.loadDraft()
  s.search = 'PRODUCTO 1'
  assert.equal(s.visibleRows.length, 1)
  s.rows[1].quantity = -2
  s.download()
  assert.equal(editorCtx.lastPdf.rows.filter(row => row.included).length, 2)
  s.rows[0].included = false
  assert.equal(s.excludedCount, 1)
  assert.equal(s.includedRows.length, 1)
  s.restoreRow(s.rows[0])
  assert.equal(s.includedRows.length, 2)
  assert.equal(s.rows[0].quantity, -5)
})
test('parent freezes all export filters before the editor opens', () => {
  const parentSource = fs.readFileSync(path.join(root, 'src/pages/BajasPage.vue'), 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1].replace(/^import .*$/gm, '').replace('export default', 'module.exports =')
  const parentCtx = { module: { exports: {} }, WithdrawalPdfEditor: {}, localStorage: { setItem () {} }, ...helpers }
  vm.createContext(parentCtx); vm.runInContext(parentSource, parentCtx)
  const parent = parentCtx.module.exports
  const s = { isAdmin: true, loading: false, $store: { user: { id: 1 } }, filter: { agencia_id: [2], mes: 9, anio: 2026, tipo: 'VENCIMIENTO', tipo_reporte: 'INVENTARIO', search: 'LOTE' }, agencias: [{ id: 2, nombre: 'Sucursal' }], getMesNombre: () => 'Septiembre', formatTipoReporte: () => 'Inventario' }
  parent.methods.openPdfEditor.call(s)
  s.filter.agencia_id.push(3); s.filter.search = 'otro'
  assert.equal(s.pdfEditorFilters.agencia_id.length, 1)
  assert.equal(s.pdfEditorFilters.search, 'LOTE')
  assert.match(s.pdfEditorScope, /Septiembre/)
  assert.equal(s.showPdfEditor, true)
  s.isAdmin = false
  s.$store.user = { id: 2, agencia_id: 1 }
  s.showPdfEditor = false
  parent.methods.openPdfEditor.call(s)
  assert.equal(s.showPdfEditor, false)
  s.tab = 'productos'
  s.productos = [{ id: 1 }]
  s.pagination = {}; s.paginationProductos = {}; s.paginationDevoluciones = {}
  let reports = 0
  s.getReports = () => { reports++ }
  s.getProductos = () => { throw new Error('Unauthorized products query') }
  parent.methods.fetchData.call(s)
  assert.equal(s.tab, 'informes')
  assert.equal(s.productos.length, 0)
  assert.equal(reports, 1)
})

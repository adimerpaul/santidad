const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

const source = fs.readFileSync(path.join(__dirname, '../src/pages/CierresCajaPage.vue'), 'utf8')
  .match(/<script>([\s\S]*?)<\/script>/)[1].replace('export default', 'module.exports =')
const context = { module: { exports: {} }, AbortController }
vm.createContext(context)
vm.runInContext(source, context)
const component = context.module.exports
function reportData (total = 2) {
  return { resumen: { turnos: total, cerrados: total, anomalias: 1 }, cierres: { data: [], current_page: 1, last_page: 1, total } }
}
function state () {
  const s = { ...component.data(), $store: { user: {} }, $axios: {}, calls: [] }
  Object.entries(component.computed).forEach(([key, getter]) => Object.defineProperty(s, key, { get: () => getter.call(s) }))
  Object.entries(component.methods).forEach(([key, fn]) => { s[key] = fn.bind(s) })
  s.$axios.get = async (url, options) => { s.calls.push({ url, ...options }); return { data: url.endsWith('report-options') ? { agencias: [{ id: 1, nombre: 'Matriz' }], cuentas: [] } : reportData() } }
  return s
}
test('late session initialization loads options and report without revisiting the page', async () => {
  const s = state()
  component.watch.identityKey.handler.call(s, s.identityKey)
  assert.equal(s.calls.length, 0)
  s.$store.user = { id: 1, agencia_id: 1 }
  component.watch.identityKey.handler.call(s, s.identityKey)
  await new Promise(resolve => setImmediate(resolve))
  assert.equal(s.calls.length, 2)
  assert.equal(s.agencyOptions.length, 2)
  assert.equal(s.appliedFilters.agencia_id, 1)
  assert.equal(s.period, 7)
  assert.match(s.calls.find(call => call.url.endsWith('/report')).params.desde, /^\d{4}-\d{2}-\d{2}$/)
  assert.equal((new Date(s.appliedFilters.hasta) - new Date(s.appliedFilters.desde)) / 86400000, 6)
  assert.equal(s.report.cierres.total, 2)
})
test('branch changes discard unavailable accounts while preserving historical accounts in that branch', () => {
  const s = state()
  s.accounts = [{ id: 2, name: 'Ana', agencia_ids: [1, 2] }, { id: 3, name: 'Luis', agencia_ids: [1] }]
  s.filters = { agencia_id: 2, user_id: 3 }
  s.onAgencyChange()
  assert.equal(s.filters.user_id, '')
  s.filters.user_id = 2
  s.onAgencyChange()
  assert.equal(s.filters.user_id, 2)
})
test('pagination and anomaly views use applied filters rather than unsaved date edits', async () => {
  const s = state()
  s.$store.user = { id: 1, agencia_id: 1 }
  s.appliedFilters = { agencia_id: 2, user_id: 3, desde: '2026-11-01', hasta: '2026-11-03' }
  s.filters.desde = '2026-12-01'
  s.view = 'anomalias'
  await s.onTableRequest({ pagination: { rowsPerPage: 30, page: 2 } })
  await new Promise(resolve => setImmediate(resolve))
  assert.equal(s.calls[0].params.desde, '2026-11-01')
  assert.equal(s.calls[0].params.agencia_id, 2)
  assert.equal(s.calls[0].params.vista, 'anomalias')
  assert.equal(s.calls[0].params.per_page, 30)
  assert.equal(s.calls[0].params.page, 2)
})
test('obsolete responses and unmounted responses cannot replace current report or options', async () => {
  const s = state()
  s.$store.user = { id: 1 }
  const pending = []
  s.$axios.get = () => new Promise(resolve => pending.push(resolve))
  const old = s.loadReport()
  const current = s.loadReport()
  pending[1]({ data: reportData(8) }); await current
  pending[0]({ data: reportData(4) }); await old
  assert.equal(s.report.resumen.turnos, 8)
  const options = s.loadOptions()
  component.beforeUnmount.call(s)
  pending[2]({ data: { agencias: [{ id: 20 }], cuentas: [] } }); await options
  assert.equal(s.agencies.length, 0)
})
test('changing identity clears prior scope and discards prior privileged report', async () => {
  const s = state()
  s.$store.user = { id: 1, agencia_id: 1 }
  let resolveOld
  s.$axios.get = () => new Promise(resolve => { resolveOld = resolve })
  const old = s.loadReport()
  s.$store.user = {}
  component.watch.identityKey.handler.call(s, '')
  resolveOld({ data: reportData(99) }); await old
  assert.equal(s.report, null)
  assert.equal(s.agencies.length, 0)
})
test('invalid date ranges do not send a request and request failures clear old totals', async () => {
  const s = state()
  s.$store.user = { id: 1 }
  s.filters = { desde: '2026-11-05', hasta: '2026-11-01' }
  s.applyFilters()
  assert.equal(s.calls.length, 0)
  assert.match(s.errorMessage, /posterior/)
  s.report = reportData()
  s.$axios.get = async () => { throw new Error('offline') }
  await s.loadReport()
  assert.equal(s.report, null)
  assert.match(s.errorMessage, /No se pudo/)
})
test('all negative differences are red, all positive differences are green, and unconfirmed shifts are not colored', () => {
  const s = state()
  for (const [difference, expected] of [[-0.01, 'is-shortage'], [-20, 'is-shortage'], [-20.01, 'is-shortage'], [0, 'is-normal'], [0.01, 'is-surplus'], [150, 'is-surplus'], [150.01, 'is-surplus']]) {
    assert.equal(s.differenceClass({ estado: 'CERRADO', diferencia: difference }), expected)
  }
  assert.equal(s.differenceClass({ estado: 'PENDIENTE', diferencia: -900 }), '')
  assert.equal(s.differenceLabel({ estado: 'ABIERTO' }), 'Sin evaluar')
})
test('employee views do not offer anomaly filters or system amount columns', () => {
  const s = state()
  s.$store.user = { id: 2, agencia_id: 1 }
  assert.equal(s.viewOptions.some(option => option.value === 'anomalias'), false)
  assert.equal(s.columns.some(column => column.name === 'diferencia'), false)
  assert.equal(s.columns.some(column => column.name === 'monto_sistema_digital'), false)
})

test('periods count inclusive Bolivia dates even when the browser has a different date or the range crosses a month', () => {
  const range = vm.runInContext("periodDates(7, new Date('2026-11-03T02:00:00Z'))", context)
  assert.equal(range.desde, '2026-10-27')
  assert.equal(range.hasta, '2026-11-02')
  const month = vm.runInContext("periodDates(30, new Date('2026-11-03T15:00:00Z'))", context)
  assert.equal(month.desde, '2026-10-05')
  assert.equal(month.hasta, '2026-11-03')
  const leap = vm.runInContext("periodDates(7, new Date('2028-03-01T15:00:00Z'))", context)
  assert.equal(leap.desde, '2028-02-24')
})

test('quick periods request bounded ranges, all history is explicit, and clearing returns to one week', async () => {
  const s = state()
  s.$store.user = { id: 1 }
  for (const days of [30, 50, 90]) {
    s.period = days
    s.onPeriodChange(days)
    await new Promise(resolve => setImmediate(resolve))
    const params = s.calls.at(-1).params
    assert.equal((new Date(params.hasta) - new Date(params.desde)) / 86400000, days - 1)
  }
  s.onPeriodChange('custom')
  assert.equal(s.calls.length, 3)
  s.onPeriodChange('all')
  await new Promise(resolve => setImmediate(resolve))
  assert.equal(s.calls.at(-1).params.desde, undefined)
  assert.equal(s.calls.at(-1).params.hasta, undefined)
  s.clearFilters()
  await new Promise(resolve => setImmediate(resolve))
  assert.equal(s.period, 7)
  assert.equal((new Date(s.appliedFilters.hasta) - new Date(s.appliedFilters.desde)) / 86400000, 6)
  assert.equal(s.columns.some(column => column.name === 'monto_sistema_digital'), true)
})


const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

const source = fs.readFileSync(path.join(__dirname, '../src/components/TraeSales.vue'), 'utf8')
  .match(/<script>([\s\S]*?)<\/script>/)[1].replace('export default', 'module.exports =')
const context = { module: { exports: {} }, AbortController }
vm.createContext(context)
vm.runInContext(source, context)
const component = context.module.exports
const result = { resumen: { cantidad: 2, ventas: 300, comision: 36, neto: 264 }, ventas: { data: [], current_page: 1, last_page: 2, total: 2 } }

function report () {
  const state = { ...component.data(), calls: [], $axios: {} }
  Object.entries(component.methods).forEach(([name, method]) => { state[name] = method.bind(state) })
  state.$axios.get = async (url, payload) => {
    state.calls.push({ url, ...payload.params })
    return { data: result }
  }
  return state
}

test('report applies branch and date filters and uses the server totals for all pages', async () => {
  const state = report()
  await state.loadSales(1, { agencia_id: 2, desde: '2026-10-01', hasta: '2026-10-31' })
  assert.equal(state.calls[0].url, 'trae/sales')
  assert.equal(state.calls[0].agencia_id, 2)
  assert.equal(state.calls[0].hasta, '2026-10-31')
  assert.equal(state.report.resumen.neto, 264)
  assert.equal(state.loading, false)
})

test('invalid date ranges do not query or replace the existing report', () => {
  const state = report()
  state.report = result
  state.filters = { desde: '2026-10-31', hasta: '2026-10-01' }
  state.applyFilters()
  assert.equal(state.calls.length, 0)
  assert.equal(state.report, result)
  assert.match(state.errorMessage, /posterior/)
})

test('pagination uses applied filters even when draft inputs have been edited', async () => {
  const state = report()
  await state.loadSales(1, { agencia_id: 1, desde: '2026-10-01', hasta: '' })
  state.filters = { agencia_id: 2, desde: '', hasta: '' }
  await state.loadSales(2)
  assert.equal(state.calls[1].agencia_id, 1)
  assert.equal(state.calls[1].desde, '2026-10-01')
  assert.equal(state.calls[1].page, 2)
})

test('late responses cannot replace newer totals or their filter labels', async () => {
  const state = report()
  const pending = []
  const signals = []
  state.$axios.get = (url, options) => { signals.push(options.signal); return new Promise(resolve => pending.push(resolve)) }
  const old = state.loadSales(1, { agencia_id: 1 })
  const current = state.loadSales(1, { agencia_id: 2 })
  assert.equal(signals[0].aborted, true)
  pending[1]({ data: result })
  await current
  pending[0]({ data: { ...result, resumen: { neto: 9999 } } })
  await old
  assert.equal(state.report.resumen.neto, 264)
  assert.equal(state.appliedFilters.agencia_id, 2)
})

test('failed reports remove previous totals so they cannot appear under new filters', async () => {
  const state = report()
  state.report = result
  state.$axios.get = async () => { throw new Error('offline') }
  await state.loadSales(1, { agencia_id: 2 })
  assert.equal(state.report, null)
  assert.match(state.errorMessage, /No se pudieron/)
  assert.equal(state.loading, false)
})

test('unmounting discards an in-flight report response', async () => {
  const state = report()
  let complete
  state.$axios.get = () => new Promise(resolve => { complete = resolve })
  const pending = state.loadSales()
  component.beforeUnmount.call(state)
  assert.equal(state.requestAbort.signal.aborted, true)
  complete({ data: result })
  await pending
  assert.equal(state.report, null)
})

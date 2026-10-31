const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

const script = fs.readFileSync(path.join(__dirname, '../src/pages/TraePage.vue'), 'utf8')
  .match(/<script>([\s\S]*?)<\/script>/)[1].replace(/^import .*$/gm, '').replace('export default', 'module.exports =')
const context = { module: { exports: {} }, TraeSales: {}, TraeValidity: {}, InternalCombos: {}, AbortController, roundSalePrice: value => Math.round(Math.round((value + Number.EPSILON) * 100) / 10) / 10 }
vm.createContext(context)
vm.runInContext(script, context)
const component = context.module.exports
const product = id => ({ id, nombre: `Producto ${id}`, origen: 'GENERAL', precio_original: 130, precio_venta: 130, porcentaje: 0 })

function pricing () {
  const state = { ...component.data(), notices: [], $axios: {}, $refs: {}, $nextTick: fn => fn(), $store: { user: { id: 1 } } }
  state.$q = { notify: item => state.notices.push(item) }
  Object.entries(component.methods).forEach(([key, method]) => { state[key] = method.bind(state) })
  Object.entries(component.computed).forEach(([key, getter]) => Object.defineProperty(state, key, { get: () => getter.call(state) }))
  state.configLoaded = true
  return state
}

test('page selection preserves products chosen on other pages and skips excluded products', () => {
  const state = pricing()
  state.selected = [product(99)]
  state.products = [product(1), product(2), { ...product(3), origen: 'EXCLUIDO' }]
  state.togglePageSelection()
  assert.equal(state.selected.map(p => p.id).join(','), '99,1,2')
  assert.equal(state.pageSelection, true)
  state.togglePageSelection()
  assert.equal(state.selected.map(p => p.id).join(','), '99')
})

test('following a channel preserves own rules without applying or editing them', async () => {
  const state = pricing()
  state.agencias = [{ id: 2, nombre: 'Sucursal Centro' }]
  state.applyConfig({ settings: { descuento_general: 2, comision_porcentaje: 12, fuente_precios: 'physical', fuente_agencia_id: 2 }, rules: [{ id: 1, product_id: 1, porcentaje: 50, activo: 1 }] })
  assert.equal(state.usesOwnPrices, false)
  assert.equal(state.priceSourceLabel, 'Igual que sucursal · Sucursal Centro')
  assert.equal(state.activeRules.length, 0)
  assert.equal(state.rules.length, 1)
  state.$axios.post = async () => assert.fail('No own rules should be written in following mode')
  assert.equal(await state.writeRule({ porcentaje: 20 }), false)
})

test('choosing a price source saves its branch and refreshes prices without changing commission', async () => {
  const state = pricing()
  state.selected = [product(1)]
  state.openSettings()
  state.settingsDraft.fuente_precios = 'web'
  state.settingsDraft.fuente_agencia_id = 2
  state.$axios.put = async (url, payload) => {
    assert.equal(url, 'trae/settings')
    assert.equal(payload.fuente_precios, 'web')
    assert.equal(payload.fuente_agencia_id, 2)
    assert.equal(payload.comision_porcentaje, 12)
    return { data: { settings: payload, rules: [] } }
  }
  state.$axios.get = async () => ({ data: { data: [{ ...product(1), precio_venta: 80, origen: 'HEREDADO_WEB' }], last_page: 1, total: 1 } })
  await state.saveSettings()
  assert.equal(state.settings.fuente_precios, 'web')
  assert.equal(state.products[0].precio_venta, 80)
  assert.equal(state.selected.length, 0)
  assert.equal(state.settingsDialog, false)
})

test('physical source requires a branch and a failed update preserves the previous source', async () => {
  const state = pricing()
  state.openSettings()
  state.settingsDraft.fuente_precios = 'physical'
  let requests = 0
  state.$axios.put = async () => { requests++; throw new Error('offline') }
  await state.saveSettings()
  assert.equal(requests, 0)
  state.settingsDraft.fuente_agencia_id = 1
  await state.saveSettings()
  assert.equal(requests, 1)
  assert.equal(state.usesOwnPrices, true)
  assert.equal(state.settingsDialog, true)
})

test('a stale settings conflict loads current values without retrying or overwriting the new source', async () => {
  const state = pricing()
  state.settings.revision = 'a'.repeat(64)
  state.selected = [product(1)]
  state.openSettings()
  state.settingsDraft.comision_porcentaje = 10
  let writes = 0
  state.$axios.put = async (url, payload) => {
    writes++
    assert.equal(payload.revision, 'a'.repeat(64))
    throw Object.assign(new Error('Conflict'), { response: { status: 409 } })
  }
  state.$axios.get = async url => url === 'trae'
    ? { data: { settings: { descuento_general: 2, comision_porcentaje: 12, fuente_precios: 'app', fuente_agencia_id: null, revision: 'b'.repeat(64) }, rules: [] } }
    : { data: { data: [], last_page: 1, total: 0 } }
  await state.saveSettings()
  assert.equal(writes, 1)
  assert.equal(state.settings.fuente_precios, 'app')
  assert.equal(state.settingsDraft.fuente_precios, 'app')
  assert.equal(state.settingsDraft.comision_porcentaje, 12)
  assert.equal(state.settingsDraft.revision, 'b'.repeat(64))
  assert.equal(state.settingsDialog, true)
  assert.equal(state.selected.length, 0)
  assert.equal(state.notices.at(-1).type, 'warning')
})

test('individual and page selection respect the 200-product server limit', () => {
  const state = pricing()
  state.selected = Array.from({ length: 199 }, (_, i) => product(i + 1))
  state.products = [product(200), product(201)]
  state.togglePageSelection()
  assert.equal(state.selected.length, 200)
  state.toggleProduct(product(202))
  assert.equal(state.selected.length, 200)
  state.toggleProduct(product(1))
  assert.equal(state.selected.length, 199)
})

test('a failed bulk discount preserves the selection for retry', async () => {
  const state = pricing()
  state.selected = [product(1), product(2)]
  state.$axios.post = async () => { throw new Error('offline') }
  await state.applySelected()
  assert.equal(state.selected.length, 2)
  assert.equal(state.saving, false)
  assert.equal(state.notices[0].type, 'negative')
})

test('bulk apply sends only selected IDs and explicit zero without changing global settings', async () => {
  const state = pricing()
  state.selected = [product(1), product(2)]
  state.bulkPercentage = 0
  state.$axios.post = async (url, payload) => {
    assert.equal(url, 'trae/rules')
    assert.equal(payload.product_ids.join(','), '1,2')
    assert.equal(payload.porcentaje, 0)
    assert.equal(payload.activo, true)
    return { data: { settings: { descuento_general: 8, comision_porcentaje: 12 }, rules: [] } }
  }
  state.$axios.get = async () => ({ data: { data: [product(1)], last_page: 1, total: 1 } })
  await state.applySelected()
  assert.equal(state.selected.length, 0)
  assert.equal(state.settings.descuento_general, 8)
})

test('search cancels previous requests and stale results cannot replace the latest catalog or selection', async () => {
  const state = pricing()
  state.selected = [product(99)]
  const requests = []
  state.$axios.get = (url, options) => new Promise(resolve => requests.push({ options, resolve }))
  const first = state.loadProducts()
  state.search = 'nuevo'
  state.perPage = 40
  const second = state.loadProducts()
  assert.equal(requests[0].options.signal.aborted, true)
  assert.equal(requests[1].options.params.per_page, 40)
  requests[1].resolve({ data: { data: [product(2)], last_page: 1, total: 1 } })
  await second
  requests[0].resolve({ data: { data: [product(1)], last_page: 1, total: 1 } })
  await first
  assert.equal(state.products[0].id, 2)
  assert.equal(state.selected[0].id, 99)
  assert.equal(state.loadingProducts, false)
})

test('preview applies discount to the base price and existing currency rounding', () => {
  const state = pricing()
  state.bulkPercentage = 5
  assert.equal(state.previewPrice({ ...product(1), precio_venta: 100 }), 123.5)
  state.bulkPercentage = 0
  assert.equal(state.previewPrice(product(1)), 130)
})

test('editing a category recalls its existing rule and settings drafts do not change live settings', () => {
  const state = pricing()
  state.category = 2
  state.rules = [{ category_id: 2, porcentaje: 8, activo: 0 }]
  state.openCategory()
  assert.equal(state.form.porcentaje, 8)
  assert.equal(state.form.activo, false)
  state.openSettings()
  state.settingsDraft.descuento_general = 50
  assert.equal(state.settings.descuento_general, 0)
})

test('saving is blocked while configuration refresh is in flight', async () => {
  const state = pricing()
  state.loading = true
  state.selected = [product(1)]
  state.$axios.post = async () => { assert.fail('Must wait for configuration refresh') }
  await state.applySelected()
  assert.equal(state.selected.length, 1)
})

test('opening TRAE sales loads branch metadata without fetching pricing or products', () => {
  const state = pricing()
  let branches = 0
  state.loadAgencias = () => { branches++ }
  state.load = () => { assert.fail('Pricing must not block the sales report') }
  component.watch.isAdmin.handler.call(state, true)
  assert.equal(branches, 1)
})

test('pricing loads once on first visit and sales refresh only reloads the report', () => {
  const state = pricing()
  state.configLoaded = false
  let prices = 0
  let sales = 0
  state.load = () => { prices++ }
  state.$refs = { sales: { loadSales () { sales++ } } }
  state.refresh()
  assert.equal(prices, 0)
  assert.equal(sales, 1)
  component.watch.section.call(state, 'pricing')
  assert.equal(prices, 1)
  state.configLoaded = true
  component.watch.section.call(state, 'pricing')
  assert.equal(prices, 1)
})

test('bulk validity serializes local dates and rejects incomplete periods without saving', async () => {
  const state = pricing()
  state.selected = [product(1)]
  state.bulkValidity = { permanente: false, fecha_inicio: '2026-11-01T09:00', fecha_fin: '' }
  state.writeRule = async () => { assert.fail('Invalid periods must not be submitted') }
  await state.applySelected()
  assert.equal(state.selected.length, 1)
  assert.equal(state.notices[0].type, 'negative')
  state.bulkValidity.fecha_fin = '2026-11-02T18:00'
  state.writeRule = async payload => {
    assert.equal(payload.permanente, false)
    assert.equal(payload.fecha_inicio, '2026-11-01 09:00:00')
    assert.equal(payload.fecha_fin, '2026-11-02 18:00:00')
    return true
  }
  await state.applySelected()
  assert.equal(state.selected.length, 0)
})

test('editing recalls scheduled dates and pausing retains validity on the server', () => {
  const state = pricing()
  const rule = { product_id: 1, porcentaje: 5, activo: 1, permanente: 0, fecha_inicio: '2026-11-01 09:00:00', fecha_fin: '2026-11-02 18:00:00' }
  state.editRule(rule)
  assert.equal(state.form.validity.permanente, false)
  assert.equal(state.form.validity.fecha_fin, '2026-11-02T18:00')
  state.writeRule = payload => { assert.equal(payload.activo, false); assert.equal(payload.permanente, undefined) }
  state.toggleRule(rule, false)
})

test('scheduled state follows server time offset and exact expiry, and indefinite clears dates', () => {
  const state = pricing()
  const start = Date.parse('2026-11-01T09:00:00-04:00')
  const rule = { activo: 1, permanente: 0, inicio_ms: start, fin_ms: start + 3600000 }
  state.serverClockOffset = 60000
  state.clockNow = start - 60001
  assert.equal(state.ruleState(rule), 'PROGRAMADA')
  state.clockNow++
  assert.equal(state.ruleState(rule), 'ACTIVA')
  state.clockNow += 3600000
  assert.equal(state.ruleState(rule), 'VENCIDA')
  rule.activo = 0
  assert.equal(state.ruleState(rule), 'PAUSADA')
  const payload = state.validityPayload({ permanente: true, fecha_inicio: '2026-11-01T09:00', fecha_fin: '2026-11-02T18:00' })
  assert.equal(payload.fecha_inicio, null)
  assert.equal(payload.fecha_fin, null)
})

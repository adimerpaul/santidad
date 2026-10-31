const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

const source = fs.readFileSync(path.join(__dirname, '../src/components/InternalCombos.vue'), 'utf8')
const context = { module: { exports: {} }, TraeValidity: {} }
vm.createContext(context)
vm.runInContext(fs.readFileSync(path.join(__dirname, '../src/utils/money.js'), 'utf8').replace(/export /g, ''), context)
vm.runInContext(fs.readFileSync(path.join(__dirname, '../src/utils/internal-combos.js'), 'utf8').replace(/^import .*$/gm, '').replace(/export const/g, 'var'), context)
vm.runInContext(source.match(/<script>([\s\S]*?)<\/script>/)[1].replace(/^import .*$/gm, '').replace('export default', 'module.exports ='), context)
const component = context.module.exports

function editor () {
  const state = { ...component.data(), $axios: {}, $q: { notify () {} }, $emit () {} }
  Object.entries(component.methods).forEach(([key, method]) => { state[key] = method.bind(state) })
  Object.entries(component.computed).forEach(([key, getter]) => Object.defineProperty(state, key, { get: () => getter.call(state) }))
  state.openCreate([
    { id: 1, nombre: 'PANTERA CLASICO X CAJA', precio_original: 5.85 },
    { id: 2, nombre: 'RESSAKA X SOBRE', precio_original: 4.72 }
  ])
  state.form.nombre = 'COMBO 8'
  state.form.modo = 'total'
  state.form.precio_objetivo = 8
  return state
}

test('fixed total shows readable percentages without changing the distributed prices', async () => {
  const state = editor()
  state.$axios.post = async () => ({ data: { items: [
    { product_id: 1, precio_unitario: 4.5 },
    { product_id: 2, precio_unitario: 3.5 }
  ] } })
  await state.calculateTotal()
  assert.equal(state.linePercent(state.form.items[0]), 23.08)
  assert.equal(state.linePercent(state.form.items[1]), 25.85)
  assert.equal(state.totals.total, 8)
  assert.equal(state.previewOK, true)
  // Rendering percentages must not switch modes or recalculate the authoritative prices.
  assert.equal(state.form.modo, 'total')
  assert.equal(state.payload().items.map(p => p.precio_unitario).join(','), '4.5,3.5')
})

test('saving a distributed fixed total preserves the exact Bs 8 payload', async () => {
  const state = editor()
  state.form.items[0].precio_unitario = 4.5
  state.form.items[1].precio_unitario = 3.5
  state.previewOK = true
  let saved
  state.$axios.post = async (url, payload) => { assert.equal(url, 'internal-combos'); saved = payload }
  state.$axios.get = async () => ({ data: { data: [], last_page: 1 } })
  await state.save()
  assert.equal(saved.modo, 'total')
  assert.equal(saved.precio_objetivo, 8)
  assert.equal(saved.items.reduce((sum, p) => sum + p.cantidad * p.precio_unitario, 0), 8)
})

test('derived percentages allow decimal values and rounded 100 percent without native form rejection', () => {
  const input = source.match(/<q-input :model-value="linePercent\(item\)"[^>]+/)[0]
  assert.match(input, /step="any"/)
  assert.match(input, /max="100"/)
  const state = editor()
  assert.equal(state.linePercent({ precio_base: 10000, precio_unitario: 0.1 }), 100)
  assert.equal(state.linePercent({ precio_base: 0, precio_unitario: 0 }), 0)
})

test('editing a unit price does not round away the decimal while typing', () => {
  const state = editor()
  const item = state.form.items[0]
  item.precio_unitario = '4.'
  state.linePrice(item)
  assert.equal(item.precio_unitario, '4.')
  item.precio_unitario = 4.55
  state.linePrice(item)
  assert.equal(item.precio_unitario, 4.55)
  state.normalizeLinePrice(item)
  assert.equal(item.precio_unitario, 4.6)
  assert.equal(state.form.modo, 'producto')
  assert.equal(state.previewOK, false)
})

test('effective discount rounding cannot replace the percentage while it is being typed', () => {
  const state = editor()
  const item = state.form.items[0]
  state.linePercentage(item, '1')
  assert.equal(state.linePercent(item), '1')
  state.linePercentage(item, '10')
  assert.equal(state.linePercent(item), '10')
  assert.equal(item.precio_unitario, 5.3)
  state.finishLinePercentage(item)
  assert.equal(state.linePercent(item), 9.4)
  assert.equal(item.precio_unitario, 5.3)
})

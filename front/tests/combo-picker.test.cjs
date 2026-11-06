const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')
const script = fs.readFileSync(path.join(__dirname, '../src/components/SaleComboPicker.vue'), 'utf8')
  .match(/<script>([\s\S]*?)<\/script>/)[1].replace('export default', 'module.exports =')
const context = { module: { exports: {} } }
vm.createContext(context)
vm.runInContext(script, context)
const component = context.module.exports

function picker () {
  const state = { ...component.data(), agenciaId: 1, disabled: false, $axios: {}, selections: [], errors: [] }
  state.$emit = (event, data) => state.selections.push(data)
  state.$alert = { error: message => state.errors.push(message) }
  Object.entries(component.methods).forEach(([key, method]) => { state[key] = method.bind(state) })
  state.$axios.get = async () => ({ data: { data: [], last_page: 1 } })
  state.dialog = true
  return state
}

test('closing and reopening the picker cannot load an old pending selection', async () => {
  const state = picker()
  let finish
  state.$axios.post = () => new Promise(resolve => { finish = resolve })
  const pending = state.choose({ id: 1 })
  state.dialog = false
  component.watch.dialog.call(state, false)
  state.open()
  finish({ data: { id: 1, nombre: 'Respuesta antigua' } })
  await pending
  assert.equal(state.selections.length, 0)
  assert.equal(state.dialog, true)
})

test('a previous branch catalog cannot replace the current branch catalog', async () => {
  const state = picker()
  let finish
  state.$axios.get = () => new Promise(resolve => { finish = resolve })
  const pending = state.load()
  state.agenciaId = 2
  finish({ data: { data: [{ id: 1, combos_disponibles: 99 }], last_page: 1 } })
  await pending
  assert.equal(state.combos.length, 0)
  assert.equal(state.loading, false)
})

test('leaving the page discards a pending combo selection', async () => {
  const state = picker()
  let finish
  state.$axios.post = () => new Promise(resolve => { finish = resolve })
  const pending = state.choose({ id: 1 })
  component.beforeUnmount.call(state)
  finish({ data: { id: 1 } })
  await pending
  assert.equal(state.selections.length, 0)
})

test('a verified current selection emits the complete recipe once', async () => {
  const state = picker()
  const recipe = { id: 1, items: [{ product_id: 1, cantidad: 2 }] }
  state.$axios.post = async (url, payload) => {
    assert.equal(url, 'internal-combos/1/load')
    assert.equal(payload.canal_venta, 'trae')
    assert.equal(payload.agencia_id, 1)
    return { data: recipe }
  }
  await state.choose({ id: 1 })
  assert.equal(state.selections.length, 1)
  assert.equal(state.selections[0], recipe)
  assert.equal(state.dialog, false)
})

test('replacement selection sends its product ID and emits the verified recipe', async () => {
  const state = picker()
  state.replacementDialog = true
  state.$axios.post = async (url, payload) => {
    assert.equal(payload.reemplazo_product_id, 9)
    return { data: { id: 1, precio_total: 20, items: [{ product_id: 9, producto_original_id: 3, precio_unitario: 5 }] } }
  }
  await state.choose({ id: 1 }, 9)
  assert.equal(state.selections[0].items[0].producto_original_id, 3)
  assert.equal(state.replacementDialog, false)
})

test('closing the replacement dialog discards a pending replacement', async () => {
  const state = picker()
  state.replacementDialog = true
  let finish
  state.$axios.post = () => new Promise(resolve => { finish = resolve })
  const pending = state.choose({ id: 1 }, 9)
  state.replacementDialog = false
  component.watch.replacementDialog.call(state, false)
  finish({ data: { id: 1 } })
  await pending
  assert.equal(state.selections.length, 0)
})

test('stale substitute searches cannot replace a newer search or branch', async () => {
  const state = picker()
  state.replacementDialog = true
  state.replacementCombo = { id: 1 }
  let finish
  state.$axios.get = () => new Promise(resolve => { finish = resolve })
  const pending = state.loadSubstitutes()
  state.agenciaId = 2
  finish({ data: { data: [{ id: 9, stock: 99 }], last_page: 1 } })
  await pending
  assert.equal(state.substitutes.length, 0)
})

test('an exhausted replaceable slot can load a draft but a missing fixed ingredient cannot', () => {
  const state = picker()
  assert.equal(state.canLoad({ combos_disponibles: 0, items: [{ reemplazable: true, stock: 0, cantidad: 1 }, { stock: 2, cantidad: 1 }] }), true)
  assert.equal(state.canLoad({ combos_disponibles: 0, items: [{ reemplazable: true, stock: 2, cantidad: 1 }, { stock: 0, cantidad: 1 }] }), false)
})

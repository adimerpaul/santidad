const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')

const vue = fs.readFileSync(path.join(__dirname, '../src/pages/SalePage.vue'), 'utf8')
const script = vue.match(/<script>([\s\S]*?)<\/script>/)[1]
  .replace(/^import .*$/gm, '').replace(/import\.meta\.env/g, '({})').replace('export default', 'module.exports =')
const context = {
  module: { exports: {} },
  SaleComboPicker: {},
  AbortController,
  crypto: { getRandomValues: values => require('node:crypto').webcrypto.getRandomValues(values) },
  window: { addEventListener () {} },
  localStorage: { getItem () { return null } },
  sessionStorage: { values: new Map(), getItem (key) { return this.values.get(key) || null }, setItem (key, value) { this.values.set(key, value) }, removeItem (key) { this.values.delete(key) } },
  Imprimir: {},
  formatMoney: value => Number(value).toFixed(2),
  formatPayable: value => Number(value).toFixed(1),
  roundCurrency: Number,
  roundPayable: Number,
  hasProductSavings () {}
}
vm.createContext(context)
vm.runInContext(fs.readFileSync(path.join(__dirname, '../src/utils/money.js'), 'utf8').replace(/export /g, ''), context)
vm.runInContext(fs.readFileSync(path.join(__dirname, '../src/utils/internal-combos.js'), 'utf8').replace(/^import .*$/gm, '').replace(/export const/g, 'var'), context)
vm.runInContext(fs.readFileSync(path.join(__dirname, '../src/utils/sale-submission.js'), 'utf8').replace(/export /g, ''), context)
vm.runInContext('var redondearMoneda = roundCurrency', context)
vm.runInContext(script, context)
const component = context.module.exports

function sale () {
  const errors = []
  const state = {
    $store: { canalVenta: 'sucursal', productosVenta: [{ id: 1, precio: '100.00', precioVenta: '92.00', cantidadVenta: 2 }] },
    $alert: { error: message => errors.push(message) },
    agencia_id: 1,
    cambiandoCanal: false,
    saleDialog: false,
    aporte: 2,
    descuento: 3,
    descuentoPorcentaje: 3,
    errors,
    loads: 0,
    productsGet () { this.loads++ },
    $axios: { post: async () => ({ data: { precios: [{ id: 1, precio: 100, precioVenta: 97, porcentajeEfectivo: 3, promocion: 'Descuento TRAE', promocion_id: null }] } }) }
  }
  Object.defineProperty(state, 'canalVenta', { get () { return component.computed.canalVenta.call(this) } })
  Object.entries(component.methods).forEach(([key, method]) => { if (!(key in state)) state[key] = method.bind(state) })
  return state
}

function billing () {
  context.sessionStorage.values.clear()
  const state = sale()
  Object.assign(state, { saleDialog: true, ventaEnviando: false, saleCompleted: false, pendingSale: null, totalFinal: 184,
    client: { numeroDocumento: '1234567', nombreRazonSocial: 'Cliente de prueba' }, document: { codigoClasificador: 1 },
    metodoPago: 'Efectivo', products: [{ id: 1, cantidadReal: 10, cantidad: 10 }], notices: [], facturaPequena: true,
    detenerPollingQr () {}, resetQrState () {}, notifySocket () {} })
  state.$store.user = { id: 1 }
  state.$store.productosVenta[0].cantidadPedida = 2
  state.$alert.success = message => state.notices.push(message)
  state.$q = { notify: notice => state.notices.push(notice) }
  context.Imprimir.factura = context.Imprimir.facturaPequena = context.Imprimir.nota = async () => {}
  return state
}

const registered = { id: 55, venta: 'F', numeroFactura: 1, estado: 'ACTIVO' }

test('double submit sends only one invoice request even if another loader clears loading', async () => {
  const state = billing()
  let finish
  let requests = 0
  state.$axios.post = () => { requests++; return new Promise(resolve => { finish = resolve }) }
  const first = state.saleInsert()
  state.loading = false
  await state.saleInsert()
  assert.equal(requests, 1)
  assert.equal(state.ventaEnviando, true)
  finish({ data: registered })
  await first
  assert.equal(state.saleDialog, false)
  assert.equal(state.pendingSale, null)
  assert.equal(state.$store.productosVenta.length, 0)
  assert.equal(state.products[0].cantidadReal, 8)
})

test('lost response is reconciled with the registered sale without sending another POST', async () => {
  const state = billing()
  const notifications = []
  state.notifySocket = event => notifications.push(event)
  let posts = 0
  state.$axios.post = async () => { posts++; throw new Error('timeout') }
  state.$axios.get = async url => { assert.match(url, /^sale-requests\//); return { data: { ...registered, solicitud_repetida: true } } }
  await state.saleInsert()
  assert.equal(posts, 1)
  assert.equal(state.saleCompleted, true)
  assert.equal(state.pendingSale, null)
  assert.equal(state.errors.length, 0)
  assert.equal(state.products[0].cantidadReal, 10, 'Recovered sales refresh stock instead of subtracting it twice')
  assert.equal(state.loads, 1)
  assert.equal(notifications.includes('stock_actualizado'), false, 'A replay must not subtract stock again in other cash registers')
})

test('an unknown result preserves the UUID and frozen customer/products for an explicit retry', async () => {
  const state = billing()
  const requests = []
  state.$axios.post = async (url, payload) => { requests.push(JSON.stringify(payload)); if (requests.length === 1) throw new Error('offline'); return { data: registered } }
  state.$axios.get = async () => { throw Object.assign(new Error('not found'), { response: { status: 404 } }) }
  await state.saleInsert()
  assert.ok(state.pendingSale)
  assert.equal(context.sessionStorage.values.size, 1)
  state.client.nombreRazonSocial = 'Otro nombre'
  await state.vaciarCanasta()
  assert.equal(state.$store.productosVenta.length, 1)
  await state.saleInsert()
  assert.equal(requests.length, 2)
  assert.equal(requests[0], requests[1])
  assert.equal(state.pendingSale, null)
  assert.equal(context.sessionStorage.values.size, 0)
})

test('reloading a pending sale checks its saved operation and never emits or prints a pending invoice', async () => {
  const state = billing()
  state.prepareSaleRequest()
  const uuid = state.pendingSale.request_id
  state.pendingSale = null
  let gets = 0
  state.$axios.get = async url => { gets++; assert.equal(url, `sale-requests/${uuid}`); return { data: { ...registered, procesamiento_pendiente: true, solicitud_repetida: true } } }
  state.$axios.post = async () => assert.fail('Restoring must only read the saved attempt')
  let prints = 0
  context.Imprimir.facturaPequena = async () => { prints++ }
  state.recoverPendingSale()
  await new Promise(resolve => setImmediate(resolve))
  assert.equal(gets, 1)
  assert.equal(prints, 0)
  assert.equal(state.saleCompleted, true)
  assert.equal(state.notices.at(-1).type, 'warning')
})

test('a printer failure never changes a registered sale into a failed submission', async () => {
  const state = billing()
  let posts = 0
  state.$axios.post = async () => { posts++; return { data: registered } }
  state.$axios.get = async () => assert.fail('Printing errors are not submission errors')
  context.Imprimir.facturaPequena = async () => { throw new Error('printer unavailable') }
  await state.saleInsert()
  assert.equal(posts, 1)
  assert.equal(state.saleCompleted, true)
  assert.equal(state.pendingSale, null)
  assert.match(state.errors[0], /55.*registró.*impresión/)
})

test('a confirmed validation failure allows correcting data while keeping the basket', async () => {
  const state = billing()
  state.$axios.post = async () => { throw Object.assign(new Error('invalid'), { response: { status: 422, data: { message: 'Revise los datos' } } }) }
  state.$axios.get = async () => { throw Object.assign(new Error('not found'), { response: { status: 404 } }) }
  await state.saleInsert()
  assert.equal(state.pendingSale, null)
  assert.equal(state.saleDialog, true)
  assert.equal(state.$store.productosVenta.length, 1)
  assert.equal(state.errors[0], 'Revise los datos')
})

const combo = () => ({ id: 1, nombre: 'COMBO CONVITE', version: 1, items: [
  { product_id: 1, nombre: 'Producto 1', precio_base: 100, precio_unitario: 80, cantidad: 2, stock: 10 },
  { product_id: 2, nombre: 'Producto 2', precio_base: 50, precio_unitario: 40, cantidad: 1, stock: 10 }
] })

function officialComboPrices (payload) {
  return { data: { precios: payload.productos.map(p => ({ id: p.id, line_key: `${p.id}${p.combo_grupo ? ':' + p.combo_grupo : ''}`,
    precio: p.id === 1 ? 100 : 50, precioVenta: p.combo_grupo ? (p.id === 1 ? 80 : 40) : 97, porcentajeEfectivo: p.combo_grupo ? 20 : 3 })) } }
}

test('loading a combo automatically selects TRAE and keeps loose and combo prices separate', async () => {
  const state = sale()
  state.$axios.post = async (url, payload) => officialComboPrices(payload)
  await state.cargarCombo(combo())
  assert.equal(state.canalVenta, 'trae')
  assert.equal(state.$store.productosVenta.length, 3)
  assert.equal(state.$store.productosVenta[0].precioVenta, '97.00')
  assert.equal(state.$store.productosVenta[1].precioVenta, '80.00')
  assert.equal(state.basketQuantity(1), 4)
  assert.match(state.$store.productosVenta[1].combo_grupo, /^[a-f\d]{8}-[a-f\d]{4}-4[a-f\d]{3}-[89ab][a-f\d]{3}-[a-f\d]{12}$/)
  assert.equal(state.aporte, 0)
  assert.equal(state.descuento, 0)
})

test('failed combo stock verification preserves the original basket, prices and channel', async () => {
  const state = sale()
  const original = state.$store.productosVenta
  state.$axios.post = async () => { throw new Error('Stock insuficiente') }
  await state.cargarCombo(combo())
  assert.equal(state.$store.productosVenta, original)
  assert.equal(state.canalVenta, 'sucursal')
  assert.equal(original[0].precioVenta, '92.00')
  assert.equal(state.cambiandoCanal, false)
  assert.equal(state.errors.length, 1)
})

test('a late combo price response cannot load a basket for a different branch', async () => {
  const state = sale()
  const original = state.$store.productosVenta
  let finish
  state.$axios.post = (url, payload) => new Promise(resolve => { finish = () => resolve(officialComboPrices(payload)) })
  const pending = state.cargarCombo(combo())
  state.agencia_id = 2
  finish()
  await pending
  assert.equal(state.$store.productosVenta, original)
  assert.equal(state.canalVenta, 'sucursal')
  assert.equal(state.loads, 0)
})

test('increasing a combo changes every ingredient quantity and keeps loose products intact', async () => {
  const state = sale()
  state.$axios.post = async (url, payload) => officialComboPrices(payload)
  await state.cargarCombo(combo())
  const group = component.computed.comboGroups.call(state)[0]
  await state.ajustarCombo(group, 1)
  assert.equal(state.$store.productosVenta[0].cantidadVenta, 2)
  assert.equal(state.$store.productosVenta[1].cantidadVenta, 4)
  assert.equal(state.$store.productosVenta[2].cantidadVenta, 2)
  assert.equal(state.$store.productosVenta[1].combo_cantidad, 2)
  assert.equal(state.basketQuantity(1), 6)
})

test('checkout discards pending verification after logout, branch change, or quantity editing', async () => {
  for (const mutate of [state => { state.$store.user = { id: 2 } }, state => { state.agencia_id = 2 }, state => { state.$store.productosVenta[0].cantidadVenta++ }]) {
    const state = sale()
    state.$store.user = { id: 1 }
    let finish
    state.$axios.post = (url, payload) => new Promise(resolve => { finish = () => resolve(officialComboPrices(payload)) })
    const pending = state.clickSale()
    mutate(state)
    finish()
    await pending
    assert.equal(state.saleDialog, false)
    assert.equal(state.loading, false)
    assert.equal(state.$store.productosVenta[0].precioVenta, '92.00')
  }
})

test('checkout requires verified prices for every basket line before opening payment', async () => {
  const state = sale()
  state.$axios.post = async () => ({ data: { precios: [] } })
  await state.clickSale()
  assert.equal(state.saleDialog, false)
  assert.equal(state.errors.length, 1)
  assert.equal(state.$store.productosVenta[0].precioVenta, '92.00')
})

test('a late catalog response cannot replace a newer delivery search even if cancellation arrives too late', async () => {
  const state = sale()
  state.$store.canalVenta = 'trae'
  state.current_page = 1
  state.search = 'primero'
  const requests = []
  const shown = []
  state.$axios.get = (url, options) => new Promise(resolve => requests.push({ options, resolve }))
  state.mostrarProductos = data => shown.push(data)
  component.methods.productsGet.call(state)
  state.search = 'segundo'
  component.methods.productsGet.call(state)
  assert.equal(requests[0].options.signal.aborted, true)
  requests[1].resolve({ data: { catalog: 'nuevo' } })
  await new Promise(resolve => setImmediate(resolve))
  requests[0].resolve({ data: { catalog: 'antiguo' } })
  await new Promise(resolve => setImmediate(resolve))
  assert.equal(shown.length, 1)
  assert.equal(shown[0].catalog, 'nuevo')
})

test('leaving the sales page discards a pending delivery catalog response', async () => {
  const state = sale()
  state.$store.canalVenta = 'trae'
  let finish
  let shown = 0
  state.$axios.get = () => new Promise(resolve => { finish = resolve })
  state.mostrarProductos = () => { shown++ }
  component.methods.productsGet.call(state)
  state.ventaDesmontada = true
  finish({ data: {} })
  await new Promise(resolve => setImmediate(resolve))
  assert.equal(shown, 0)
})

test('the first catalog still loads when me resolves after mounting, even with no branch selected yet', async () => {
  const state = sale()
  state.$store.canalVenta = 'trae'
  state.agencia_id = NaN
  let finish
  let shown = 0
  state.$axios.get = () => new Promise(resolve => { finish = resolve })
  state.mostrarProductos = () => { shown++ }
  component.methods.productsGet.call(state)
  state.$store.user = { id: 1 }
  finish({ data: {} })
  await new Promise(resolve => setImmediate(resolve))
  assert.equal(shown, 1)
})

test('switching to TRAE reprices the entire basket and resets extra adjustments', async () => {
  const state = sale()
  await component.methods.cambiarCanalVenta.call(state)
  assert.equal(state.canalVenta, 'trae')
  assert.equal(state.$store.productosVenta[0].precioVenta, '97.00')
  assert.equal(state.$store.productosVenta[0].cantidadVenta, 2)
  assert.equal(state.descuento, 0)
  assert.equal(state.aporte, 0)
  assert.equal(state.loads, 1)
  assert.equal(state.cambiandoCanal, false)
})

test('a failed price check leaves the channel and basket unchanged', async () => {
  const state = sale()
  state.$axios.post = async () => { throw new Error('Sin conexión') }
  await component.methods.cambiarCanalVenta.call(state)
  assert.equal(state.canalVenta, 'sucursal')
  assert.equal(state.$store.productosVenta[0].precioVenta, '92.00')
  assert.equal(state.descuento, 3)
  assert.equal(state.loads, 0)
  assert.equal(state.errors.length, 1)
})

test('online orders cannot be converted to TRAE', async () => {
  const state = sale()
  state.$store.productosVenta[0].online_order_number = 'WEB123'
  state.$axios.post = async () => { assert.fail('No price request expected') }
  await component.methods.cambiarCanalVenta.call(state)
  assert.equal(state.canalVenta, 'sucursal')
  assert.equal(state.errors.length, 1)
})

test('an open payment dialog prevents changing the price channel', async () => {
  const state = sale()
  state.saleDialog = true
  await component.methods.cambiarCanalVenta.call(state)
  assert.equal(state.canalVenta, 'sucursal')
  assert.equal(state.loads, 0)
})

test('leaving TRAE restores physical prices using the server', async () => {
  const state = sale()
  state.$store.canalVenta = 'trae'
  state.$axios.post = async (url, payload) => {
    assert.equal(payload.canal_venta, 'sucursal')
    return { data: { precios: [{ id: 1, precio: 100, precioVenta: 92, porcentajeEfectivo: 8, promocion: null, promocion_id: null }] } }
  }
  await component.methods.cambiarCanalVenta.call(state)
  assert.equal(state.canalVenta, 'sucursal')
  assert.equal(state.$store.productosVenta[0].precioVenta, '92.00')
})

test('clearing a TRAE basket resets the channel for the next customer', async () => {
  const state = sale()
  state.$store.canalVenta = 'trae'
  await component.methods.vaciarCanasta.call(state)
  assert.equal(state.canalVenta, 'sucursal')
  assert.equal(state.$store.productosVenta.length, 0)
  assert.equal(state.loads, 1)
})

test('TRAE cannot be activated while an online order is being imported', async () => {
  const state = sale()
  state.loadingPedidoOnline = true
  state.$axios.post = async () => { assert.fail('The channel must not change during import') }
  await component.methods.cambiarCanalVenta.call(state)
  assert.equal(state.canalVenta, 'sucursal')
  assert.equal(state.errors.length, 0)
})

test('a pending channel change cannot revive a basket after logout', async () => {
  const state = sale()
  state.$store.user = { id: 1 }
  let complete
  state.$axios.post = () => new Promise(resolve => { complete = resolve })
  const pending = component.methods.cambiarCanalVenta.call(state)
  state.$store.user = {}
  state.$store.productosVenta = []
  complete({ data: { precios: [{ id: 1, precio: 100, precioVenta: 97, porcentajeEfectivo: 3 }] } })
  await pending
  assert.equal(state.canalVenta, 'sucursal')
  assert.equal(state.loads, 0)
})

test('leaving the sales page discards a pending channel change', async () => {
  const state = sale()
  let complete
  state.$axios.post = () => new Promise(resolve => { complete = resolve })
  const pending = component.methods.cambiarCanalVenta.call(state)
  state.ventaDesmontada = true
  complete({ data: { precios: [{ id: 1, precio: 100, precioVenta: 97, porcentajeEfectivo: 3 }] } })
  await pending
  assert.equal(state.canalVenta, 'sucursal')
  assert.equal(state.$store.productosVenta[0].precioVenta, '92.00')
  assert.equal(state.loads, 0)
})

const storeSource = fs.readFileSync(path.join(__dirname, '../src/stores/example-store.js'), 'utf8')
  .replace(/^import .*$/gm, '').replace('export const useCounterStore =', 'module.exports =')
const storeContext = { module: { exports: {} }, defineStore: (name, definition) => definition }
vm.createContext(storeContext)
vm.runInContext(storeSource, storeContext)
const resetTraeSale = storeContext.module.exports.actions.resetTraeSale

test('ending a session clears the TRAE basket and channel', () => {
  const store = { canalVenta: 'trae', productosVenta: [{ id: 1 }] }
  resetTraeSale.call(store)
  assert.equal(store.canalVenta, 'sucursal')
  assert.equal(store.productosVenta.length, 0)
})

test('the TRAE session cleanup preserves existing physical sale behavior', () => {
  const items = [{ id: 1 }]
  const store = { canalVenta: 'sucursal', productosVenta: items }
  resetTraeSale.call(store)
  assert.equal(store.canalVenta, 'sucursal')
  assert.equal(store.productosVenta, items)
})

test('replacing a cart combo retains its group and count without duplicating the recipe', async () => {
  const state = sale()
  state.$store.productosVenta = [
    { id: 5, cantidadVenta: 1 },
    { id: 1, combo_id: 7, combo_grupo: 'group', combo_cantidad: 2, cantidadVenta: 2 },
    { id: 3, combo_id: 7, combo_grupo: 'group', combo_cantidad: 2, cantidadVenta: 4 }
  ]
  state.verifyComboBasket = async rows => { state.$store.productosVenta = rows }
  await state.handleComboSelection({ id: 7, nombre: 'Combo', version: 1, items: [
    { product_id: 1, cantidad: 1, precio_unitario: 4, stock: 10 },
    { product_id: 9, producto_original_id: 3, reemplazable: true, cantidad: 2, precio_unitario: 2, stock: 10 }
  ] }, 'group')
  assert.equal(state.$store.productosVenta.length, 3)
  const replacement = state.$store.productosVenta.find(p => p.id === 9)
  assert.equal(replacement.cantidadVenta, 4)
  assert.equal(replacement.combo_producto_original_id, 3)
  assert.equal(replacement.combo_grupo, 'group')
  assert.equal(state.$store.productosVenta.some(p => p.id === 3), false)
})

const test = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const vm = require('node:vm')
const path = require('node:path')
const source = fs.readFileSync(path.join(__dirname, '../src/pages/Compras/ComprasPage.vue'), 'utf8')
const methods = source.slice(source.indexOf('    async registrarCompraParaFactura ()'), source.indexOf('    resetFacturaData ()')).trim().replace(/,$/, '')
const storage = new Map()
const functions = vm.runInNewContext('({' + methods + '})', {
  sessionStorage: {
    getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value),
    removeItem: key => storage.delete(key)
  }
})

function setup({ lostPurchase = false, lostInvoice = false } = {}) {
  storage.clear()
  const calls = [], receipts = new Map(), messages = []
  let failPurchase = lostPurchase, failInvoice = true
  const ctx = {
    ...functions, loadingFactura: false, dialogoFactura: true,
    datosCompra: { request_id: 'stable-key', buys: [{ id: 1, cantidadCompra: 10 }] },
    compraRegistrada: null, compraEnviada: null, facturaEnviada: null,
    facturaData: { fecha_compra: '2026-10-13T12:00', vendedor_id: 1 },
    $store: { user: { id: 1 }, productosCompra: [{ id: 1 }], pedidoId: 1 },
    resetFacturaData() {}, productsGet() {},
    $alert: { error: m => messages.push(m), success() {}, info() {} },
    $axios: { post: async (route, payload) => {
      calls.push({ route, payload: JSON.parse(JSON.stringify(payload)) })
      const key = route + payload.request_id
      if (route === 'compraInsert') {
        if (!receipts.has(key)) receipts.set(key, { buy_ids: [7] })
        if (failPurchase) { failPurchase = false; throw new Error('Respuesta perdida') }
        return { data: receipts.get(key) }
      }
      if (failInvoice) {
        failInvoice = false
        if (lostInvoice) {
          receipts.set(key, { id: 9 }); throw new Error('Respuesta perdida')
        }
        throw { response: { data: { message: 'Factura rechazada' } } }
      }
      if (!receipts.has(key)) receipts.set(key, { id: 9 })
      return { data: receipts.get(key) }
    } }
  }
  return { ctx, calls, receipts, messages }
}

test('retrying a rejected invoice reuses buy IDs without registering the purchase again', async () => {
  const { ctx, calls } = setup()
  await ctx.crearFacturaCompleta()
  assert.equal(ctx.compraRegistrada.buy_ids[0], 7)
  assert.equal(ctx.$store.productosCompra.length, 1)
  await ctx.crearFacturaCompleta()
  assert.equal(calls.filter(c => c.route === 'compraInsert').length, 1)
  assert.deepEqual(calls.filter(c => c.route === 'facturas').map(c => c.payload.detalle_compras), [[7], [7]])
  assert.equal(ctx.$store.productosCompra.length, 0)
})

test('lost purchase response retries the same key and payload even if seller changes', async () => {
  const { ctx, calls } = setup({ lostPurchase: true })
  await ctx.crearFacturaCompleta()
  ctx.facturaData.vendedor_id = 2
  await ctx.crearFacturaCompleta()
  const purchaseCalls = calls.filter(c => c.route === 'compraInsert')
  assert.equal(purchaseCalls.length, 2)
  assert.deepEqual(purchaseCalls[0].payload, purchaseCalls[1].payload)
})

test('lost invoice response retries identically and does not create another purchase', async () => {
  const { ctx, calls, receipts } = setup({ lostInvoice: true })
  await ctx.crearFacturaCompleta()
  ctx.facturaData.monto_total = 999
  await ctx.crearFacturaCompleta()
  assert.equal(calls.filter(c => c.route === 'compraInsert').length, 1)
  const invoiceCalls = calls.filter(c => c.route === 'facturas')
  assert.deepEqual(invoiceCalls[0].payload, invoiceCalls[1].payload)
  assert.equal(receipts.size, 2)
})

test('finishing without an invoice after invoice failure keeps the existing purchase', async () => {
  const { ctx, calls } = setup()
  await ctx.crearFacturaCompleta()
  await ctx.finalizarSinFactura()
  assert.equal(calls.filter(c => c.route === 'compraInsert').length, 1)
  assert.equal(ctx.dialogoFactura, false)
})

test('cancel cannot report cancellation while the purchase response is unknown', async () => {
  const { ctx, messages } = setup({ lostPurchase: true })
  await ctx.crearFacturaCompleta()
  ctx.cancelarFactura()
  assert.equal(ctx.dialogoFactura, true)
  assert.ok(messages.length)
})

test('restoring the pending dialog retains the key and registered purchase across a reload', async () => {
  const { ctx, calls } = setup()
  await ctx.crearFacturaCompleta()
  const restored = { ...ctx, datosCompra: null, compraRegistrada: null, compraEnviada: null, facturaEnviada: null, dialogoFactura: false }
  restored.restaurarCompraPendiente()
  assert.equal(restored.dialogoFactura, true)
  assert.equal(restored.compraRegistrada.buy_ids[0], 7)
  await restored.crearFacturaCompleta()
  assert.equal(calls.filter(c => c.route === 'compraInsert').length, 1)
})

test('two simultaneous save clicks register the purchase only once', async () => {
  const { ctx, calls } = setup()
  await Promise.all([ctx.crearFacturaCompleta(), ctx.crearFacturaCompleta()])
  assert.equal(calls.filter(c => c.route === 'compraInsert').length, 1)
  assert.equal(calls.filter(c => c.route === 'facturas').length, 1)
  assert.equal(ctx.loadingFactura, false)
})

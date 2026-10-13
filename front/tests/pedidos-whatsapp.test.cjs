const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')

const source = fs.readFileSync(path.join(__dirname, '../src/pages/HistorialPedidos.vue'), 'utf8')
const component = new Function(source.match(/<script>([\s\S]*?)<\/script>/)[1].replace('export default', 'return'))()
function context () {
  const ctx = { ...component.data(), esAdmin: false, $q: { notify () {} }, $axios: {} }
  for (const [name, method] of Object.entries(component.methods)) ctx[name] = method.bind(ctx)
  ctx.cargarPedidos = () => {}
  return ctx
}
function deferred () {
  let resolve, reject
  const promise = new Promise((res, rej) => { resolve = res; reject = rej })
  return { promise, resolve, reject }
}
function detail (id, name, quantity) {
  return { id, pedido_id: id, cantidad: quantity, product: { nombre: name } }
}

test('changing orders clears previous details and blocks approval until loading finishes', async () => {
  const ctx = context()
  ctx.detallesPedido = [detail(1, 'Producto uno', 10)]
  ctx.detallesParaModificar = [{ pedido_detail_id: 1 }]
  const request = deferred()
  ctx.$axios.get = url => url.endsWith('/detalles') ? request.promise : Promise.resolve({ data: [] })
  let posts = 0
  ctx.$axios.post = () => { posts++ }
  const loading = ctx.verDetallePedido({ id: 2 })
  assert.deepEqual(ctx.detallesPedido, [])
  assert.deepEqual(ctx.detallesParaModificar, [])
  await ctx.aprobarConModificaciones()
  await ctx.confirmarAccion()
  assert.equal(posts, 0)
  request.resolve({ data: { detalles: [detail(2, 'Producto dos', 3)] } })
  await loading
  assert.equal(ctx.cargandoDetalles, false)
  assert.equal(ctx.detallesPedido[0].id, 2)
})

test('a late response from the first order cannot replace the second order', async () => {
  const ctx = context()
  const first = deferred()
  const second = deferred()
  ctx.$axios.get = url => url === 'pedidos/1/detalles' ? first.promise : url === 'pedidos/2/detalles' ? second.promise : Promise.resolve({ data: [] })
  const a = ctx.verDetallePedido({ id: 1 })
  const b = ctx.verDetallePedido({ id: 2 })
  second.resolve({ data: { detalles: [detail(2, 'Producto dos', 3)] } })
  await b
  first.resolve({ data: { detalles: [detail(1, 'Producto uno', 10)] } })
  await a
  assert.equal(ctx.pedidoSeleccionado.id, 2)
  assert.equal(ctx.detallesPedido[0].id, 2)
})

test('failed loading leaves no products or modifications to approve', async () => {
  const ctx = context()
  ctx.detallesPedido = [detail(1, 'Anterior', 10)]
  ctx.$axios.get = async () => { throw new Error('Sin conexión') }
  const oldError = console.error
  console.error = () => {}
  try { await ctx.verDetallePedido({ id: 2 }) } finally { console.error = oldError }
  await ctx.aprobarConModificaciones()
  assert.deepEqual(ctx.detallesPedido, [])
  assert.deepEqual(ctx.detallesParaModificar, [])
  assert.equal(ctx.dialogAccion, false)
})

test('admin stock and modification requests cannot restore the previous order history', async () => {
  const ctx = context()
  ctx.esAdmin = true
  const oldStock = deferred()
  ctx.$axios.get = async url => {
    if (url === 'pedidos/1/detalles') return { data: { detalles: [{ ...detail(1, 'Anterior', 10), product_id: 10 }] } }
    if (url === 'pedidos/2/detalles') return { data: { detalles: [{ ...detail(2, 'Actual', 3), product_id: 20 }] } }
    if (url === 'products/10/stock-sucursales') return oldStock.promise
    if (url === 'products/20/stock-sucursales') return { data: [] }
    return { data: [{ pedido_id: url.includes('/1/') ? 1 : 2 }] }
  }
  const first = ctx.verDetallePedido({ id: 1 })
  await Promise.resolve()
  await ctx.verDetallePedido({ id: 2 })
  oldStock.resolve({ data: [] })
  await first
  assert.equal(ctx.detallesPedido[0].id, 2)
  assert.equal(ctx.historialModificaciones[0].pedido_id, 2)
  assert.equal(ctx.cargandoDetalles, false)
})

test('two sequential approvals send their own products and quantities, excluding zero quantities', async () => {
  const ctx = context()
  const messages = []
  global.window = { open: url => messages.push(decodeURIComponent(url.split('?text=')[1])) }
  ctx.$axios.get = async url => ({ data: url.endsWith('/detalles') ? { detalles: url.includes('/1/') ? [detail(1, 'Producto uno', 10)] : [detail(2, 'Producto dos', 3), detail(3, 'No comprar', 8)] } : [] })
  ctx.$axios.post = async () => ({ data: {} })
  try {
    for (const id of [1, 2]) {
      await ctx.verDetallePedido({ id, fecha_pedido: '2026-10-11' })
      if (id === 2) ctx.detallesPedido[1].cantidad_aprobada = 0
      await ctx.aprobarConModificaciones()
      ctx.vendedorSeleccionado = { nombre: 'Vendedor', celular: '70000000' }
      await ctx.confirmarAccion()
    }
    assert.match(messages[0], /10 u\.\* — Producto uno/)
    assert.match(messages[1], /Nro Pedido:\* 2/)
    assert.match(messages[1], /3 u\.\* — Producto dos/)
    assert.doesNotMatch(messages[1], /Producto uno|No comprar/)
    assert.deepEqual(ctx.detallesParaModificar, [])
  } finally { delete global.window }
})

test('approval keeps its message snapshot and prevents duplicate submissions', async () => {
  const ctx = context()
  ctx.pedidoSeleccionado = { id: 1 }
  ctx.detallesPedido = [detail(1, 'Original', 5)]
  ctx.accionActual = 'aprobado'
  ctx.vendedorSeleccionado = { nombre: 'Vendedor', celular: '70000000' }
  const request = deferred()
  let posts = 0
  ctx.$axios.post = () => { posts++; return request.promise }
  let snapshot
  ctx.enviarMensajeWhatsapp = (...args) => { snapshot = args }
  const approval = ctx.confirmarAccion()
  await ctx.confirmarAccion()
  await ctx.verDetallePedido({ id: 2 })
  assert.equal(ctx.pedidoSeleccionado.id, 1)
  ctx.detallesPedido[0].cantidad_aprobada = 99
  request.resolve({ data: {} })
  await approval
  assert.equal(posts, 1)
  assert.equal(snapshot[0].id, 1)
  assert.equal(snapshot[1][0].cantidad, 5)
  assert.equal(snapshot[1][0].cantidad_aprobada, undefined)
})

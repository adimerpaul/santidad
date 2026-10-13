const test = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const vm = require('node:vm')
const source = fs.readFileSync(require('node:path').join(__dirname, '../src/pages/SalePage.vue'), 'utf8')
const method = source.slice(source.indexOf('    async importarPedidoDesdeApi ('), source.indexOf('    async obtenerProductoCanonico (')) .trim().replace(/,$/, '')
const importer = vm.runInNewContext('({' + method + '})', { formatMoney: n => Number(n).toFixed(2) }).importarPedidoDesdeApi
function setup(items, basket = [], failId = null) {
  const errors = [], successes = []
  const ctx = {
    loadingPedidoOnline: false, agencia_id: 1, $store: { productosVenta: basket },
    $alert: { error: m => errors.push(m), success: m => successes.push(m) },
    $axios: {
      get: async () => ({ data: { order_number: 'PEDIDOWEB_Nº27', items } }),
      post: async () => ({ data: { precios: items.map(i => ({ id: i.product_id, precio: 2.38, precioVenta: 1.2, porcentajeEfectivo: 50 })) } })
    },
    obtenerProductoCanonico: async id => id === failId ? null : { id, nombre: 'Producto', cantidadReal: 100, buys: [] }
  }
  return { ctx, errors, successes }
}
test('failure on the second product leaves the original basket unchanged', async () => {
  const basket = [{ id: 9, precioVenta: 5 }]
  const { ctx, errors, successes } = setup([{ product_id: 1, quantity: 2 }, { product_id: 2, quantity: 3 }], basket, 2)
  await importer.call(ctx, 'PEDIDOWEB_Nº27')
  assert.equal(basket.length, 1); assert.equal(errors.length, 1); assert.equal(successes.length, 0)
})
test('a second import does not duplicate quantities and quantities remain editable', async () => {
  const { ctx, errors } = setup([{ product_id: 1, quantity: 2 }])
  await importer.call(ctx, 'PEDIDOWEB_Nº27')
  ctx.$store.productosVenta[0].cantidadPedida = 4
  await importer.call(ctx, 'PEDIDOWEB_Nº27')
  assert.equal(ctx.$store.productosVenta.length, 1)
  assert.equal(ctx.$store.productosVenta[0].cantidadPedida, 4)
  assert.equal(errors.length, 1)
})

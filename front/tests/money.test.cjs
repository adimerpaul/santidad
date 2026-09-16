const { test } = require('node:test')
const assert = require('node:assert/strict')
const { readFileSync } = require('node:fs')
const path = require('node:path')
const { parse } = require('@vue/compiler-sfc')

const source = readFileSync(path.join(__dirname, '../src/utils/money.js'), 'utf8')
const moneyUrl = 'data:text/javascript;base64,' + Buffer.from(source).toString('base64')
const money = import(moneyUrl)

test('base price keeps cents and only the discounted sale price rounds to tenths', async () => {
  const { roundCurrency, formatSalePrice } = await money
  const base = roundCurrency(0.46)
  assert.equal(base, 0.46)
  assert.equal(formatSalePrice(base * 0.92), '0.40')
  assert.equal(formatSalePrice(roundCurrency(0.49) * 0.92), '0.50')
  assert.equal(formatSalePrice(base), '0.50')
})

test('sale preview uses the same cent-to-tenth rounding as the backend', async () => {
  const { formatSalePrice } = await money
  assert.equal(formatSalePrice(0.3 * 0.85), '0.30')
  assert.equal(formatSalePrice(10.5 * 0.85), '8.90')
})

test('rounding up one product cannot subtract from savings on another product', async () => {
  const filename = path.join(__dirname, '../src/pages/SalePage.vue')
  const { descriptor } = parse(readFileSync(filename, 'utf8'), { filename })
  // Load the actual computed totals without mounting the page or opening the printer.
  const script = descriptor.script.content.replace("import { Imprimir } from 'src/addons/Imprimir'", '')
    .replace("'src/utils/money'", JSON.stringify(moneyUrl))
  const { default: component } = await import('data:text/javascript;base64,' + Buffer.from(script).toString('base64'))
  const sale = { $store: { productosVenta: [
    { precio: 0.46, precioVenta: 0.4, cantidadVenta: 1 },
    { precio: 0.49, precioVenta: 0.5, cantidadVenta: 1 }
  ] } }
  assert.equal(component.computed.totalDescuentoSistema.call(sale), '0.06')
  assert.equal(component.computed.totalConDescuentoSistema.call(sale), '0.90')
})

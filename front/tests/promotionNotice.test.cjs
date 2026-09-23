const { test } = require('node:test')
const assert = require('node:assert/strict')
const { readFileSync } = require('node:fs')
const path = require('node:path')

// Frontend utilities use ESM; load them without changing the app's CommonJS configuration.
const source = readFileSync(path.join(__dirname, '../src/utils/promotionNotice.js'), 'utf8')
const utilities = import('data:text/javascript;base64,' + Buffer.from(source).toString('base64'))

test('expired notices disappear even without another server response', async () => {
  const { activeNotices } = await utilities
  const notices = [
    { id: 1, inicio_ms: 1000, fin_ms: 5000 },
    { id: 2, inicio_ms: null, fin_ms: null },
    { id: 3, inicio_ms: 6000, fin_ms: 9000 }
  ]
  assert.deepEqual(activeNotices(notices, 4999).map(p => p.id), [1, 2])
  assert.deepEqual(activeNotices(notices, 5000).map(p => p.id), [2])
  assert.deepEqual(activeNotices(notices, 6000).map(p => p.id), [2, 3])
  assert.deepEqual(activeNotices(notices, 9000).map(p => p.id), [2])
})

test('scope identifies all categories, a category, or selected products', async () => {
  const { noticeScope } = await utilities
  assert.equal(noticeScope({ alcance: 'TODAS_CATEGORIAS' }), 'Todas las categorías')
  assert.equal(noticeScope({ alcance: 'CATEGORIA', categoria: 'Higiene' }), 'Higiene')
  assert.equal(noticeScope({ alcance: 'PRODUCTOS', cantidad_productos: 1, productos: ['Jabón'] }), 'Jabón')
  assert.equal(noticeScope({ alcance: 'PRODUCTOS', cantidad_productos: 5, productos: [] }), '5 productos seleccionados')
})

test('dates use Bolivia time and permanent promotions have no end date', async () => {
  const { noticeDate, noticeRemaining } = await utilities
  assert.match(noticeDate(Date.parse('2026-09-12T02:00:00Z')), /^11\/0?9$/)
  assert.equal(noticeRemaining(null, Date.now()), 'Sin fecha de fin')
  assert.equal(noticeRemaining(120000, 60000), 'Quedan 1 min')
})

const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../../public/tienda/tienda.js'), 'utf8');
const stockFunctions = source.slice(source.indexOf('  function tieneStock('), source.indexOf('  function renderCart('));
const sendFunction = source.slice(source.indexOf('  function enviarPedido('), source.indexOf('  if (drawer) {', source.indexOf('  function enviarPedido(')));
function context(items, status = 201) {
  const popup = { closed: false, location: {}, close() { this.closed = true; } };
  const notices = [];
  const ctx = {
    delivery: 'recojo', pickup: { selectedIndex: 0, options: [{ value: '1', dataset: { name: 'Sucursal uno', wa: '59170000000' } }] },
    SD: { api: '/api', whatsapp: '59170000000' }, list: () => items,
    toast: message => notices.push(message),
    window: { open: () => popup }, location: {},
    fetch: async () => ({ ok: status === 201, json: async () => status === 201 ? { order_number: 'PEDIDOWEB_Nº1' } : { errors: { items: ['Stock insuficiente'] } } }),
    mensajePedido: () => 'Mensaje', save() {}, renderCart() {}, closeCart() {},
    cart: {}, popup, notices,
  };
  vm.createContext(ctx);
  vm.runInContext(stockFunctions + sendFunction, ctx);
  return ctx;
}
function button() { return { dataset: {}, style: {}, setAttribute() {}, removeAttribute() {} }; }
async function settle() { for (let i = 0; i < 15; i++) await Promise.resolve(); }

test('stock in another branch and missing stock cannot authorize pickup', () => {
  const ctx = context([]);
  assert.equal(ctx.tieneStock([{ qty: 1, stock: { 1: 0, 2: 10 } }], '1'), false);
  assert.equal(ctx.tieneStock([{ qty: 1 }], '1'), false);
  assert.equal(ctx.tieneStock([{ qty: 3, stock: { 1: 2 } }], '1'), false);
  assert.equal(ctx.tieneStock([{ qty: 2, stock: { 1: 2 } }], '1'), true);
});
test('insufficient local stock blocks the request and WhatsApp', () => {
  const ctx = context([{ qty: 3, stock: { 1: 0 } }]);
  ctx.window.open = () => { throw new Error('Should not open WhatsApp'); };
  ctx.fetch = () => { throw new Error('Should not send request'); };
  ctx.enviarPedido(button());
  assert.equal(ctx.notices.length, 1);
});
test('server rejection closes the popup, keeps the cart and does not open WhatsApp', async () => {
  const ctx = context([{ id: 1, qty: 2, stock: { 1: 2 } }], 422);
  ctx.cart = { 1: 'Preserved' };
  const btn = button();
  ctx.enviarPedido(btn);
  await settle();
  assert.equal(ctx.popup.closed, true);
  assert.equal(ctx.popup.location.href, undefined);
  assert.equal(ctx.cart[1], 'Preserved');
  assert.equal(ctx.notices[0], 'Stock insuficiente');
  assert.equal(btn.dataset.busy, undefined);
});
test('successful registration opens WhatsApp and clears the cart', async () => {
  const ctx = context([{ id: 1, qty: 2, stock: { 1: 2 } }]);
  ctx.enviarPedido(button());
  await settle();
  assert.match(ctx.popup.location.href, /^https:\/\/wa.me\//);
  assert.equal(Object.keys(ctx.cart).length, 0);
});

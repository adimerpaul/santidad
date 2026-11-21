// Requiere Playwright disponible vía NODE_PATH. Usa Vite en un puerto aislado.
// No inicia sesión ni accede al backend. Ejecutar: node tests/customer-support.smoke.cjs
const assert = require('node:assert/strict')
const { chromium } = require('playwright')
const path = require('node:path')
const os = require('node:os')
const { createServer } = require('vite')
const vue = require('@vitejs/plugin-vue')
const { quasar } = require('@quasar/vite-plugin')

async function main () {
  const root = path.resolve(__dirname, '..')
  const server = await createServer({
    configFile: false,
    root,
    cacheDir: path.join(os.tmpdir(), 'santidad-support-vite-test'),
    plugins: [vue(), quasar()],
    resolve: { alias: { src: path.join(root, 'src'), stores: path.join(root, 'src/stores') } },
    server: { host: '127.0.0.1', port: 0 },
    define: { 'process.env.CLIENT': true, 'process.env.SERVER': false, 'process.env.DEV': true }
  })
  let browser
  try {
    await server.listen()
    browser = await chromium.launch({ headless: true, executablePath: process.env.SUPPORT_CHROMIUM_PATH || undefined })
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } })
    const errors = []
    page.on('pageerror', error => { errors.push(error.message); console.error(error.message) })
    page.on('console', message => { if (message.type() === 'error') console.error(message.text()) })
    const base = `http://127.0.0.1:${server.httpServer.address().port}`
    await page.route('**/*', route => new URL(route.request().url()).origin === base ? route.continue() : route.abort())
    let supportCase
    await page.route('**/api/support/**', async route => {
      const request = route.request()
      const pathname = new URL(request.url()).pathname
      const method = request.method()
      if (pathname.endsWith('/realtime-ticket')) return route.fulfill({ json: { ticket: 'demo-ticket' } })
      if (pathname.endsWith('/branches')) return route.fulfill({ json: [] })
      if (pathname.endsWith('/cases') && method === 'GET') {
        return route.fulfill({ json: { data: supportCase ? [supportCase] : [], last_page: 1 } })
      }
      if (pathname.endsWith('/cases') && method === 'POST') {
        assert.deepEqual(request.postDataJSON(), { order_number: 'PEDIDO-PRUEBA' })
        supportCase = {
          id: 1,
          name: 'Cliente web',
          initials: 'CW',
          source: 'web',
          branch: 'Sucursal de prueba',
          branch_id: 1,
          order: { number: 'PEDIDO-PRUEBA', total: '20.00', items: [{ id: 1, text: '1 × Producto' }] },
          mode: 'manual',
          status: 'pending',
          at: new Date().toISOString(),
          messages: []
        }
        return route.fulfill({ json: supportCase })
      }
      if (pathname.endsWith('/cases/1') && method === 'GET') return route.fulfill({ json: supportCase })
      if (pathname.endsWith('/cases/1') && method === 'DELETE') {
        supportCase = null
        return route.fulfill({ status: 204 })
      }
      if (pathname.endsWith('/cases/1/whatsapp-replies') && method === 'POST') {
        const body = request.postDataJSON()
        assert.equal(body.text, 'Respuesta desde Santidad')
        assert.match(body.request_id, /^[\da-f-]{36}$/)
        const reply = { id: 4, author: 'agent', author_initials: 'AR', text: body.text, whatsapp_state: 'accepted', at: new Date().toISOString() }
        supportCase.messages.push(reply)
        return route.fulfill({ status: 201, json: reply })
      }
      if (pathname.endsWith('/cases/1/messages') && method === 'POST') {
        const body = request.postDataJSON()
        assert.deepEqual(body, { author: 'note', text: 'Nota persistente' })
        const message = { ...body, id: 1, at: new Date().toISOString() }
        supportCase.messages.push(message)
        return route.fulfill({ status: 201, json: message })
      }
      await route.fulfill({ status: 404, json: {} })
    })
    await page.goto(`${base}/tests/fixtures/customer-support.html`)
    await page.getByText('La bandeja se actualiza al llegar mensajes.', { exact: true }).waitFor()
    await page.getByText('Ir a otra página', { exact: true }).click()
    await page.getByRole('heading', { name: 'Otra página' }).waitFor()
    await page.getByText('Abrir Atención al cliente', { exact: true }).click()
    await page.getByText('La bandeja se actualiza al llegar mensajes.', { exact: true }).waitFor()
    assert.equal(await page.locator('.conversation-item').count(), 0)
    await page.getByRole('button', { name: 'Probar demostración', exact: true }).click()
    assert.equal(await page.locator('.conversation-item').count(), 3)
    await page.locator('.conversation-item').filter({ hasText: 'Ana' }).click()
    assert.equal(await page.locator('.message-row:not(.outgoing) .customer-avatar').first().textContent(), 'AC')
    await page.locator('.message-row.outgoing .bot-avatar').waitFor()
    const response = page.getByRole('textbox', { name: 'Escribir respuesta', exact: true })
    assert.equal(await response.isDisabled(), true)
    await page.getByRole('button', { name: 'Atender yo', exact: true }).click()
    await response.fill('Borrador exclusivo de Ana')
    await page.locator('.conversation-item').filter({ hasText: 'Luis' }).click()
    assert.equal(await response.inputValue(), '')
    await page.locator('.conversation-item').filter({ hasText: 'Ana' }).click()
    assert.equal(await response.inputValue(), 'Borrador exclusivo de Ana')
    await page.getByRole('button', { name: 'Simular respuesta', exact: true }).click()
    await page.locator('.message-bubble').filter({ hasText: 'Borrador exclusivo de Ana' }).waitFor()
    await page.getByRole('button', { name: 'Nota interna', exact: true }).click()
    await page.getByRole('textbox', { name: 'Escribir nota interna', exact: true }).fill('Nota privada de ejemplo')
    await page.getByRole('button', { name: 'Añadir nota', exact: true }).click()
    assert.equal(await page.locator('.note-bubble').count(), 1)
    await page.getByRole('button', { name: 'Resolver', exact: true }).click()
    await page.getByRole('dialog').getByRole('button', { name: 'Resolver', exact: true }).click()
    assert.equal(await page.getByRole('textbox', { name: 'Escribir nota interna', exact: true }).isDisabled(), true)
    await page.getByRole('button', { name: 'Reabrir', exact: true }).click()
    await page.getByRole('button', { name: 'Activar bot', exact: true }).click()
    await page.getByRole('dialog').getByRole('button', { name: 'Activar bot', exact: true }).click()
    await page.getByRole('button', { name: 'Respuesta', exact: true }).click()
    assert.equal(await response.isDisabled(), true)
    await page.getByRole('button', { name: 'Ver detalles del pedido', exact: true }).click()
    await page.getByRole('dialog').getByText('EJEMPLO-WEB-01', { exact: true }).waitFor()
    await page.getByRole('button', { name: 'Cerrar detalles', exact: true }).click()
    await page.getByRole('dialog').waitFor({ state: 'hidden' })
    if (process.env.SUPPORT_SCREENSHOT_DIR) await page.screenshot({ path: path.join(process.env.SUPPORT_SCREENSHOT_DIR, 'atencion-desktop.png'), fullPage: true })
    await page.setViewportSize({ width: 390, height: 844 })
    await page.getByRole('button', { name: 'Volver a conversaciones', exact: true }).click()
    await page.getByRole('textbox', { name: 'Buscar cliente o pedido', exact: true }).fill('EJEMPLO-APP-02')
    assert.equal(await page.locator('.conversation-item').count(), 1)
    await page.locator('.conversation-item').click()
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true)
    if (process.env.SUPPORT_SCREENSHOT_DIR) await page.screenshot({ path: path.join(process.env.SUPPORT_SCREENSHOT_DIR, 'atencion-mobile.png'), fullPage: true })
    await page.getByRole('button', { name: 'Salir de demostración', exact: true }).click()
    await page.getByRole('dialog').getByRole('button', { name: 'Salir', exact: true }).click()
    assert.equal(await page.locator('.conversation-item').count(), 0)

    await page.setViewportSize({ width: 1440, height: 1000 })
    await page.getByRole('button', { name: 'Nuevo caso', exact: true }).click()
    await page.getByLabel('Número de pedido web o app').fill('PEDIDO-PRUEBA')
    await page.getByRole('button', { name: 'Abrir caso', exact: true }).click()
    await page.getByRole('button', { name: 'Nota interna', exact: true }).click()
    await page.getByRole('textbox', { name: 'Escribir nota interna', exact: true }).fill('Nota persistente')
    await page.getByRole('button', { name: 'Añadir nota', exact: true }).click()
    await page.locator('.note-bubble').filter({ hasText: 'Nota persistente' }).waitFor()
    await page.reload()
    await page.locator('.conversation-item').filter({ hasText: 'Cliente web' }).click()
    await page.locator('.note-bubble').filter({ hasText: 'Nota persistente' }).waitFor()
    supportCase.messages.push({ id: 2, author: 'customer', text: 'Mensaje en vivo', at: new Date().toISOString() })
    await page.evaluate(() => {
      window.fixtureSocket.emit('support_messages', [1])
      window.fixtureSocket.emit('support_messages', [1])
    })
    await page.locator('.message-bubble').filter({ hasText: 'Mensaje en vivo' }).waitFor()
    await page.evaluate(() => window.fixtureSocket.disconnect())
    supportCase.messages.push({ id: 3, author: 'customer', text: 'Mensaje al reconectar', at: new Date().toISOString() })
    await page.evaluate(() => window.fixtureSocket.connect())
    await page.locator('.message-bubble').filter({ hasText: 'Mensaje al reconectar' }).waitFor()
    supportCase.source = 'whatsapp'
    supportCase.order = null
    supportCase.bot_indicator = 'human'
    supportCase.bot_handoff_reason = 'Solicitó atención humana'
    await page.evaluate(() => window.fixtureSocket.emit('support_messages', [1]))
    await page.locator('.mode-bar').getByText('Atención humana', { exact: true }).waitFor()
    await page.locator('.mode-bar').getByText('Solicitó atención humana', { exact: true }).waitFor()
    await page.getByRole('button', { name: 'Respuesta', exact: true }).click()
    await page.getByRole('button', { name: 'Enviar por WhatsApp', exact: true }).waitFor()
    await page.getByRole('textbox', { name: 'Escribir respuesta', exact: true }).fill('Respuesta desde Santidad')
    await page.getByRole('button', { name: 'Enviar por WhatsApp', exact: true }).click()
    await page.locator('.message-bubble').filter({ hasText: 'Respuesta desde Santidad' }).getByText(/Aceptado por WhatsApp/).waitFor()
    assert.equal(await page.locator('.message-row.outgoing .agent-avatar').textContent(), 'AR')
    supportCase.messages.push({ id: 5, author: 'whatsapp_app', text: 'Respuesta desde el celular', at: new Date().toISOString() })
    await page.evaluate(() => window.fixtureSocket.emit('support_messages', [1]))
    await page.locator('.message-row.outgoing').filter({ hasText: 'Respuesta desde el celular' })
      .getByText('Enviado desde WhatsApp Business', { exact: true }).waitFor()
    await page.waitForFunction(() => document.querySelector('.business-avatar img')?.naturalWidth > 0)
    await page.getByRole('textbox', { name: 'Escribir respuesta', exact: true }).fill('Borrador antes de navegar')
    await page.getByText('Ir a otra página', { exact: true }).click()
    const leaveDialog = page.getByRole('dialog').filter({ hasText: 'Hay borradores pendientes' })
    await leaveDialog.getByRole('button', { name: 'Quedarme', exact: true }).click()
    await leaveDialog.waitFor({ state: 'hidden' })
    assert.equal(await page.getByRole('textbox', { name: 'Escribir respuesta', exact: true }).inputValue(), 'Borrador antes de navegar')
    await page.getByText('Ir a otra página', { exact: true }).click()
    await leaveDialog.getByRole('button', { name: 'Salir', exact: true }).click()
    await page.getByRole('heading', { name: 'Otra página' }).waitFor()
    await page.evaluate(() => window.fixtureSignInAsAdmin())
    await page.getByText('Abrir Atención al cliente', { exact: true }).click()
    await page.locator('.conversation-item').filter({ hasText: 'Cliente web' }).click()
    const deleteButton = page.getByRole('button', { name: 'Eliminar chat', exact: true })
    await deleteButton.click()
    const deleteDialog = page.getByRole('dialog').filter({ hasText: 'Esta acción es permanente.' })
    await deleteDialog.getByRole('button', { name: 'Cancelar', exact: true }).click()
    await deleteDialog.waitFor({ state: 'hidden' })
    await page.setViewportSize({ width: 390, height: 844 })
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true)
    await deleteButton.click()
    await deleteDialog.getByRole('button', { name: 'Eliminar chat', exact: true }).click()
    await page.getByText('Todavía no hay casos', { exact: true }).waitFor()
    assert.deepEqual(errors, [])
    console.log('OK: demostración, móvil, pedido, nota, mensajes en vivo y navegación con y sin borrador.')
  } finally {
    if (browser) await browser.close()
    await server.close()
  }
}
main().catch(error => { console.error(error); process.exitCode = 1 })

import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'
import vm from 'node:vm'
import * as configUtils from '../src/utils/terminalConfig.mjs'
import * as syncUtils from '../src/utils/adSync.mjs'
import { localMediaUrl } from '../src/utils/localMedia.mjs'

function terminal() {
  const source = fs.readFileSync(new URL('../src/pages/IndexPage.vue', import.meta.url), 'utf8')
    .split('<script setup>')[1].split('</script>')[0].split('\n').filter(line => !line.startsWith('import ')).join('\n')
  const sockets = []
  const storage = new Map()
  const timers = new Map()
  let timerId = 0
  const context = {
    ...configUtils, ...syncUtils, localMediaUrl, console: { log() {}, warn() {}, error() {} }, process: { env: {} },
    ref: value => ({ value }), computed: getter => ({ get value() { return getter() } }),
    watch: (src, cb, opts) => { if (opts?.immediate) cb(typeof src === 'function' ? src() : src?.value) },
    onMounted() {}, onBeforeUnmount() {}, nextTick: () => Promise.resolve(), window: {},
    fetchJson: async () => [],
    localStorage: { getItem: key => storage.get(key), setItem: (key, value) => storage.set(key, value), removeItem: key => storage.delete(key) },
    setTimeout: fn => { timers.set(++timerId, fn); return timerId }, clearTimeout: id => timers.delete(id),
    io: (url, options) => {
      const socket = { url, options, connected: false, handlers: {},
        on(event, fn) { this.handlers[event] = fn }, removeAllListeners() { this.handlers = {} },
        disconnect() { this.connected = false; this.disconnected = true }, timeout() { return this },
        emit(event, payload, callback) { if (event === 'register_terminal') { this.registration = payload; callback(null, { success: true }) } },
      }
      sockets.push(socket)
      return socket
    },
  }
  vm.createContext(context)
  vm.runInContext(source + '\nfetchPlaylist = () => {}; globalThis.terminal = { config, configDraft, showConfigModal, socketStatus, clientData, qrData, showThanks, saveConfiguration, openConfig, loadLocalConfig, loadCachedPlaylist, playlist, currentIndex, currentAd, syncPlayback, nextAd, playCurrentAd };', context)
  const page = context.terminal
  page.configDraft.value = { serverIp: 'localhost:8000/api', socketIp: 'localhost:3000/socket.io', agencia: 1, caja: 2 }
  page.saveConfiguration()
  return { page, sockets, storage, timers, context }
}

test('editing URLs or branch does not change the running terminal until saved', () => {
  const { page, sockets, storage } = terminal()
  page.openConfig()
  page.configDraft.value.socketIp = 'https://example.test/sockets/'
  page.configDraft.value.agencia = 3
  assert.equal(page.config.value.agencia, 1)
  assert.equal(sockets.length, 1)
  page.saveConfiguration()
  assert.equal(sockets[0].disconnected, true)
  assert.equal(sockets[1].url, 'https://example.test')
  assert.equal(sockets[1].options.path, '/sockets/socket.io')
  assert.equal(storage.get('pcpubli_agencia_id'), '3')
  assert.equal(storage.get('pcpubli_server_ip'), 'http://localhost:8000')
})

test('QR and invoice data become visible on receipt without Escape and only for this terminal', () => {
  const { page, sockets } = terminal()
  const socket = sockets[0]
  socket.connected = true
  socket.handlers.connect()
  assert.equal(socket.registration.caja, 2)
  assert.match(page.socketStatus.value, /Listo para recibir/)
  socket.handlers.clienteQrData({ agencia_id: 1, caja: 1, visible: true, qrImage: 'wrong' })
  assert.equal(page.qrData.value.visible, false)
  socket.handlers.clienteQrData({ agencia_id: 1, caja: 2, visible: true, qrImage: 'data:image/png;base64,example', monto: '15.00' })
  assert.equal(page.qrData.value.visible, true)
  assert.equal(page.showConfigModal.value, false)
  socket.handlers.clienteDisplayData({ agencia_id: 1, caja: 2, visible: true, nombreRazonSocial: 'Cliente de prueba' })
  assert.equal(page.clientData.value.visible, true)
  page.configDraft.value.caja = 3
  page.saveConfiguration()
  assert.equal(page.qrData.value.visible, false)
  assert.equal(page.clientData.value.visible, false)
})

test('the thanks timer from a previous sale cannot erase a newly received QR', () => {
  const { page, sockets, timers } = terminal()
  sockets[0].handlers.clienteSaleComplete({ agencia_id: 1, caja: 2 })
  assert.equal(page.showThanks.value, true)
  const thanksTimer = [...timers.keys()][0]
  sockets[0].handlers.clienteQrData({ agencia_id: 1, caja: 2, visible: true, qrImage: 'new' })
  assert.equal(page.showThanks.value, false)
  assert.equal(timers.has(thanksTimer), false)
  assert.equal(page.qrData.value.visible, true)
})

test('offline startup repairs previously cached local URLs with spaces', () => {
  const { page, storage, context } = terminal()
  context.window.mediaAPI = {}
  storage.set('pcpubli_sync_cache:http://localhost:8000|1', JSON.stringify({
    items: [{ file_id: 'publicidad/24 HRA.png', media_version: 'abc', type: 'image', url: 'localmedia://publicidad_24 HRA.png.abc.png' }],
    manifest: null,
  }))
  page.loadCachedPlaylist()
  assert.equal(page.playlist.value[0].url, 'localmedia://publicidad_24%20HRA.png.abc.png')
})

test('mostrarHora can be toggled and persists in localStorage', () => {
  const { page, storage } = terminal()
  assert.equal(page.config.value.mostrarHora, true)
  assert.equal(storage.get('pcpubli_mostrar_hora'), 'true')

  page.openConfig()
  page.configDraft.value.mostrarHora = false
  page.saveConfiguration()
  assert.equal(page.config.value.mostrarHora, false)
  assert.equal(storage.get('pcpubli_mostrar_hora'), 'false')
})

test('anti-pullback: when an ad finishes, syncPlayback does not pull back to it even if server clock has residual time', () => {
  const { page, context } = terminal()
  page.playlist.value = [
    { file_id: 'publicidad/ad1.png', type: 'image', duration_ms: 10000, name: 'Ad 1' },
    { file_id: 'publicidad/ad2.mp4', type: 'video', duration_ms: 50000, name: 'Ad 2' },
  ]
  page.currentIndex.value = 0

  // Server timeline still points to item 0 with residual time
  context.syncManifest = { ready: true, epoch_ms: 0, items: page.playlist.value }
  context.serverClock = { time: 5000 } // middle of item 0

  // Item 0 finishes naturally via nextAd()
  page.nextAd()

  // Player must have advanced to item 1
  assert.equal(page.currentIndex.value, 1)

  // 1 second later, syncPlayback() runs while server timeline is STILL at item 0 (e.g. at 5500ms)
  context.serverClock = { time: 5500 }
  page.syncPlayback()

  // Anti-pullback must keep the player on item 1 instead of reverting to item 0!
  assert.equal(page.currentIndex.value, 1)
})

test('anti-loop: consecutive play of the same ad triggers watchdog advance', () => {
  const { page } = terminal()
  page.playlist.value = [
    { file_id: 'publicidad/ad1.png', type: 'image', duration_ms: 10000, name: 'Ad 1' },
    { file_id: 'publicidad/ad2.png', type: 'image', duration_ms: 10000, name: 'Ad 2' },
  ]
  page.currentIndex.value = 0

  // First play
  page.playCurrentAd(false)
  assert.equal(page.currentIndex.value, 0)

  // Immediate second play of the same ad (consecutive) triggers anti-loop advancement
  page.playCurrentAd(false)
  assert.equal(page.currentIndex.value, 1)
})


import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'
import vm from 'node:vm'
import { ServerClock, playbackPosition, localMediaId } from '../src/utils/adSync.mjs'

function player() {
  const source = fs.readFileSync(new URL('../src/pages/IndexPage.vue', import.meta.url), 'utf8').split('<script setup>')[1].split('</script>')[0].replace(/^import .*$/gm, '')
  const storage = new Map(), downloads = [], statuses = [], timers = []
  let fail = false
  const context = {
    ref: value => ({ value }), computed: f => ({ get value() { return f() } }), watch() {}, onMounted() {}, onBeforeUnmount() {},
    nextTick: f => Promise.resolve().then(f), ServerClock: class extends ServerClock { constructor() { super(() => 0) } }, playbackPosition, localMediaId,
    localMediaUrl: id => 'localmedia://' + id, backendAddress: s => s, socketTarget: s => ({ url: s }), normalizeTerminalConfig: s => s,
    fetchJson: async () => { throw Error('Unexpected HTTP request') }, measureVideo: async () => 30000,
    performance: { now: () => 0 }, process: { env: {} }, console: { error() {}, warn() {} },
    setTimeout: (f, ms) => { const t = { f, ms }; timers.push(t); return t }, clearTimeout() {}, setInterval() {}, clearInterval() {},
    localStorage: { getItem: k => storage.get(k), setItem: (k, v) => storage.set(k, v) },
    window: { mediaAPI: { download: async id => { downloads.push(id); return { success: !fail } }, cleanup: async () => {} } },
  }
  vm.createContext(context)
  vm.runInContext(source + '\nglobalThis.api = { receiveSchedule, syncPlayback, requestAdClock, get manifest(){return syncManifest}, get playlist(){return playlist.value}, configure(id){config.value.agencia=id; configured.value=true; ensureAdScope()}, clock(time){serverClock.samples=[];serverClock.sample(time,0,0)}, socket(s){socketConn=s} };', context)
  const api = context.api
  api.socket({ emit: (event, data) => statuses.push({ event, data }) })
  api.configure(1)
  return { api, downloads, statuses, timers, fail: value => { fail = value } }
}
const state = (revision, epoch = 1000, ids = [1]) => ({ protocol: 2, ready: true, agencia_id: 1, revision, version: String(revision), epoch_ms: epoch,
  items: ids.map(id => ({ id, type: 'image', name: 'Test', file_id: id + '.png', media_version: String(id), url: 'https://example.com/' + id, duration_ms: 10000 })) })
const settle = () => new Promise(resolve => setImmediate(resolve))

test('cobro prepares once, waits for activation and replays identical files with a new revision', async () => {
  const { api, downloads } = player()
  api.clock(500)
  api.receiveSchedule(state(1)); await settle()
  assert.equal(api.manifest, null)
  api.clock(1500); api.syncPlayback()
  assert.equal(api.manifest.revision, 1)
  const count = downloads.length
  api.receiveSchedule(state(1)); await settle()
  assert.equal(downloads.length, count)
  api.receiveSchedule(state(2, 5000)); await settle()
  assert.equal(api.manifest.revision, 1)
  api.clock(5000); api.syncPlayback()
  assert.equal(api.manifest.revision, 2)
})

test('failed download keeps the complete previous schedule; stale and other-branch events are ignored', async () => {
  const { api, fail, timers } = player()
  api.clock(2000); api.receiveSchedule(state(1)); await settle()
  fail(true); api.receiveSchedule(state(2, 1000, [2, 3])); await settle()
  assert.equal(api.manifest.revision, 1)
  assert.equal(api.playlist.length, 1)
  assert.ok(timers.some(t => t.ms >= 5000))
  fail(false)
  api.receiveSchedule({ ...state(3), agencia_id: 2 }); api.receiveSchedule(state(1)); await settle()
  assert.equal(api.manifest.revision, 1)
})

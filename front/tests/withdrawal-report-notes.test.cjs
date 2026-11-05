const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')
const root = path.join(__dirname, '..')
const source = fs.readFileSync(path.join(root, 'src/components/WithdrawalReportNotes.vue'), 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1].replace('export default', 'module.exports =')
const context = { module: { exports: {} }, AbortController }
vm.createContext(context); vm.runInContext(source, context)
const component = context.module.exports
function state () {
  const s = { ...component.data(), report: { id: 4, agencia_id: 2, estado: 'ABIERTO', observaciones: 'Nota guardada' }, $store: { user: { id: 2, agencia_id: 2 } }, emitted: [], $nextTick: fn => Promise.resolve().then(fn) }
  Object.entries(component.computed).forEach(([name, getter]) => Object.defineProperty(s, name, { get: () => getter.call(s) }))
  Object.entries(component.methods).forEach(([name, method]) => { s[name] = method.bind(s) })
  s.$emit = (event, data) => s.emitted.push({ event, data })
  component.watch.report.handler.call(s, s.report)
  s.$axios = { put: async (url, payload) => ({ data: { id: 4, observaciones: payload.observaciones } }) }
  return s
}
test('saving changes only report notes and records a new saved baseline', async () => {
  const s = state()
  s.draft = 'Conteo físico con dos responsables.'
  const calls = []
  s.$axios.put = async (url, body) => { calls.push({ url, body }); return { data: { id: 4, observaciones: body.observaciones } } }
  await s.save()
  assert.equal(calls.length, 1)
  assert.equal(calls[0].url, 'withdrawal-reports/4')
  assert.equal(calls[0].body.observaciones_originales, 'Nota guardada')
  assert.equal(Object.keys(calls[0].body).length, 2)
  assert.equal(s.dirty, false)
  assert.equal(s.emitted[0].data.observaciones, 'Conteo físico con dos responsables.')
})
test('branch permissions and report state prevent edits in the UI', () => {
  const s = state()
  s.report.estado = 'PENDIENTE'; assert.equal(s.canEdit, false)
  s.$store.user = { id: 1, agencia_id: 1 }; assert.equal(s.canEdit, true)
  s.report.estado = 'REVISADO'; assert.equal(s.canEdit, false)
  s.report.estado = 'ABIERTO'; s.$store.user = { id: 3, agencia_id: 3 }; assert.equal(s.canEdit, false)
})
test('failed saves preserve the draft and a stale save requires loading the saved version', async () => {
  const s = state()
  s.draft = 'Mi borrador'
  s.$axios.put = async () => { throw new Error('offline') }
  await s.save()
  assert.equal(s.draft, 'Mi borrador')
  assert.equal(s.baseline, 'Nota guardada')
  s.$axios.put = async () => { throw { response: { status: 409, data: { message: 'Otra edición', observaciones: 'Cambio de otro usuario' } } } }
  await s.save()
  assert.equal(s.conflict, true)
  assert.equal(s.draft, 'Mi borrador')
  s.discardChanges()
  assert.equal(s.draft, 'Cambio de otro usuario')
  assert.equal(s.dirty, false)
})
test('refreshing product details preserves unsaved notes and detects remote note changes', () => {
  const s = state()
  s.draft = 'Borrador local'
  const before = s.report
  s.report = { ...s.report, items: [{ id: 1 }] }
  component.watch.report.handler.call(s, s.report, before)
  assert.equal(s.draft, 'Borrador local')
  assert.equal(s.conflict, false)
  const next = { ...s.report, observaciones: 'Texto remoto' }
  component.watch.report.handler.call(s, next, s.report)
  assert.equal(s.conflict, true)
  assert.equal(s.draft, 'Borrador local')
})
test('a report change or unmount discards late save responses', async () => {
  const s = state()
  let resolve
  s.$axios.put = () => new Promise(r => { resolve = r })
  s.draft = 'Nuevo'
  const saving = s.save()
  const before = s.report
  s.report = { id: 5, agencia_id: 2, estado: 'ABIERTO', observaciones: 'Otro informe' }
  component.watch.report.handler.call(s, s.report, before)
  resolve({ data: { id: 4, observaciones: 'Nuevo' } }); await saving
  assert.equal(s.draft, 'Otro informe')
  assert.equal(s.emitted.length, 0)
  s.draft = 'Cambio en el segundo'
  const second = s.save()
  component.beforeUnmount.call(s)
  resolve({ data: { id: 5, observaciones: 'Cambio en el segundo' } }); await second
  assert.equal(s.emitted.length, 0)
})
test('a product refresh started before saving cannot restore old notes in the page or PDF', async () => {
  const code = fs.readFileSync(path.join(root, 'src/pages/BajaDetailPage.vue'), 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1].replace(/^import .*$/gm, '').replace('export default', 'module.exports =')
  const ctx = { module: { exports: {} }, WithdrawalReportNotes: {} }
  vm.createContext(ctx); vm.runInContext(code, ctx)
  const methods = ctx.module.exports.methods
  let resolve
  const s = { id: 4, report: { id: 4, observaciones: 'Antes' }, reportNotesRevision: 0, applyAgencyFilterSettings: () => {}, $axios: { get: () => new Promise(r => { resolve = r }) } }
  methods.getReport.call(s)
  methods.onReportNotesUpdated.call(s, { id: 4, observaciones: 'Guardado más tarde' })
  resolve({ data: { id: 4, observaciones: 'Antes', items: [{ id: 5 }] } })
  await new Promise(r => setImmediate(r))
  assert.equal(s.report.observaciones, 'Guardado más tarde')
  assert.equal(s.report.items[0].id, 5)
})

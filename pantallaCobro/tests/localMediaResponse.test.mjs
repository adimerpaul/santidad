import test from 'node:test'
import assert from 'node:assert/strict'
import fs from 'node:fs'
import os from 'node:os'
import path from 'node:path'
import { localMediaResponse } from '../src-electron/localMediaResponse.mjs'

test('local video supports full, partial, suffix and HEAD reads with bounded streams', async () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'ad-range-'))
  const file = path.join(dir, 'video.mp4')
  fs.writeFileSync(file, '0123456789')
  const get = (range, method = 'GET') => localMediaResponse(new Request('localmedia://video.mp4', { method, headers: range ? { Range: range } : {} }), dir)
  try {
    const full = await get(); assert.equal(full.status, 200); assert.equal(await full.text(), '0123456789')
    const partial = await get('bytes=3-5'); assert.equal(partial.status, 206); assert.equal(await partial.text(), '345')
    assert.equal(partial.headers.get('content-range'), 'bytes 3-5/10')
    assert.equal(await (await get('bytes=-3')).text(), '789')
    assert.equal(await (await get('bytes=8-')).text(), '89')
    assert.equal((await get('bytes=10-')).status, 416)
    assert.equal((await get('bytes=8-3')).status, 416)
    const head = await get(null, 'HEAD'); assert.equal(head.headers.get('content-length'), '10'); assert.equal(await head.text(), '')
    assert.equal((await localMediaResponse(new Request('localmedia://..'), dir)).status, 404)
  } finally { fs.unlinkSync(file); fs.rmdirSync(dir) }
})

test('cancel during a pending disk read closes once and ignores its late completion', async (t) => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'ad-cancel-'))
  fs.writeFileSync(path.join(dir, 'video.mp4'), '0123456789')
  let finishRead, markReading
  const reading = new Promise(resolve => { markReading = resolve })
  let closes = 0
  t.mock.method(fs.promises, 'open', async () => ({
    read: async buffer => {
      markReading()
      await new Promise(resolve => { finishRead = resolve })
      buffer.fill(1)
      return { bytesRead: buffer.length }
    },
    close: async () => { closes++ },
  }))
  try {
    const response = await localMediaResponse(new Request('localmedia://video.mp4'), dir)
    const reader = response.body.getReader()
    const pending = reader.read()
    await reading
    await reader.cancel()
    finishRead()
    assert.deepEqual(await pending, { value: undefined, done: true })
    await new Promise(resolve => setImmediate(resolve))
    assert.equal(closes, 1)
  } finally { fs.rmSync(dir, { recursive: true }) }
})

test('repeated range changes and early cancellation keep subsequent playback readable', async () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'ad-switch-'))
  const data = Buffer.alloc(1024 * 1024, 7)
  fs.writeFileSync(path.join(dir, 'video.mp4'), data)
  try {
    for (let i = 0; i < 100; i++) {
      const response = await localMediaResponse(new Request('localmedia://video.mp4', {
        headers: { Range: `bytes=${i}-` },
      }), dir)
      const reader = response.body.getReader()
      if (i % 2) assert((await reader.read()).value.length <= 64 * 1024)
      else {
        const pending = reader.read()
        await reader.cancel()
        await pending
      }
      await reader.cancel()
    }
    const final = await localMediaResponse(new Request('localmedia://video.mp4'), dir)
    assert.deepEqual(Buffer.from(await final.arrayBuffer()), data)
  } finally { fs.rmSync(dir, { recursive: true }) }
})

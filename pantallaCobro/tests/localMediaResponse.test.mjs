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

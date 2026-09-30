import test from 'node:test'
import assert from 'node:assert/strict'
import http from 'node:http'
import fs from 'node:fs'
import os from 'node:os'
import path from 'node:path'
import { downloadToFile } from '../src-electron/mediaDownload.mjs'

test('downloads commit atomically, redirects work and truncated files are discarded', async () => {
  const server = http.createServer((request, response) => {
    if (request.url === '/redirect') { response.writeHead(302, { Location: '/ok' }); response.end(); return }
    if (request.url === '/broken') {
      response.writeHead(200, { 'Content-Length': 10000 })
      response.write('partial')
      setTimeout(() => response.destroy(), 20)
      return
    }
    response.end('complete video')
  })
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve))
  const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'publicidad-sync-'))
  const file = path.join(directory, 'video.mp4')
  try {
    const base = `http://127.0.0.1:${server.address().port}`
    await downloadToFile(base + '/redirect', file)
    assert.equal(fs.readFileSync(file, 'utf8'), 'complete video')
    assert.equal(fs.existsSync(file + '.part'), false)
    await assert.rejects(downloadToFile(base + '/broken', file))
    assert.equal(fs.readFileSync(file, 'utf8'), 'complete video')
    assert.equal(fs.existsSync(file + '.part'), false)
  } finally {
    server.closeAllConnections()
    await new Promise(resolve => server.close(resolve))
    if (fs.existsSync(file)) fs.unlinkSync(file)
    fs.rmSync(directory, { recursive: true, force: true })
  }
})


test('a 200 MiB file streams to disk, is verified, and a bad replacement never overwrites it', async () => {
  const { createHash } = await import('node:crypto')
  const { once } = await import('node:events')
  const { validMediaFile } = await import('../src-electron/mediaDownload.mjs')
  const chunk = Buffer.alloc(1024 * 1024, 7)
  const hash = createHash('sha256')
  for (let i = 0; i < 200; i++) hash.update(chunk)
  const expected = { size: chunk.length * 200, sha256: hash.digest('hex') }
  const server = http.createServer(async (req, res) => {
    if (req.url === '/bad') { res.end('broken'); return }
    res.writeHead(200, { 'Content-Length': expected.size })
    for (let i = 0; i < 200; i++) if (!res.write(chunk)) await once(res, 'drain')
    res.end()
  })
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve))
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'ad-large-'))
  const file = path.join(dir, 'large.mp4')
  const url = 'http://127.0.0.1:' + server.address().port
  try {
    await downloadToFile(url, file, expected)
    assert.equal(await validMediaFile(file, expected), true)
    await assert.rejects(downloadToFile(url + '/bad', file, expected))
    assert.equal(await validMediaFile(file, expected), true)
    assert.equal(fs.existsSync(file + '.part'), false)
  } finally {
    server.closeAllConnections()
    await new Promise(resolve => server.close(resolve))
    if (fs.existsSync(file)) fs.unlinkSync(file)
    fs.rmdirSync(dir)
  }
})

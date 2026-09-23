import test from 'node:test'
import assert from 'node:assert/strict'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { localMediaFileName, localMediaUrl, localMediaNameFromUrl } from '../src/utils/localMedia.mjs'
import { localMediaFileUrl } from '../src-electron/localMediaFile.mjs'

for (const fileId of ['publicidad/24 HRA.png.version.png', 'publicidad/Promoción 8% #1.png', 'publicidad/Oferta %20 literal.mp4', 'publicidad/LOGO.png']) {
  test(`local URL preserves the downloaded filename: ${fileId}`, () => {
    const url = new URL(localMediaUrl(fileId)).href
    const name = localMediaFileName(fileId)
    assert.equal(localMediaNameFromUrl(url), name)
    const mediaDir = path.resolve('test media #1')
    assert.equal(fileURLToPath(localMediaFileUrl(url, mediaDir)), path.join(mediaDir, name))
  })
}

test('local protocol cannot resolve paths outside the media folder', () => {
  for (const url of ['localmedia://..', 'localmedia://..%2Foutside.png', 'localmedia://..%5Coutside.png', 'https://example.test']) {
    assert.throws(() => localMediaNameFromUrl(url))
  }
})

import fs from 'node:fs'
import path from 'node:path'
import { Readable } from 'node:stream'
import { fileURLToPath } from 'node:url'
import { localMediaFileUrl } from './localMediaFile.mjs'

// Explicit range responses allow seeking in large local files without buffering them whole.
export async function localMediaResponse(request, directory) {
  let file, stat
  try {
    file = fileURLToPath(localMediaFileUrl(request.url, directory))
    stat = await fs.promises.stat(file)
    if (!stat.isFile()) return new Response(null, { status: 404 })
  } catch {
    return new Response(null, { status: 404 })
  }
  if (!['GET', 'HEAD'].includes(request.method)) return new Response(null, { status: 405 })
  const types = {
    '.mp4': 'video/mp4',
    '.webm': 'video/webm',
    '.mov': 'video/quicktime',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.png': 'image/png',
    '.gif': 'image/gif',
    '.webp': 'image/webp',
  }
  const headers = {
    'Accept-Ranges': 'bytes',
    'Content-Type': types[path.extname(file).toLowerCase()] || 'application/octet-stream',
  }
  let start = 0,
    end = stat.size - 1,
    status = 200
  const range = request.headers.get('range')
  if (range) {
    const match = /^bytes=(\d*)-(\d*)$/.exec(range)
    const invalid = () =>
      new Response(null, { status: 416, headers: { 'Content-Range': 'bytes */' + stat.size } })
    if (!match || (!match[1] && !match[2]) || !stat.size) return invalid()
    if (!match[1]) {
      const suffix = Number(match[2])
      if (!Number.isSafeInteger(suffix) || suffix <= 0) return invalid()
      start = Math.max(0, stat.size - suffix)
    } else {
      start = Number(match[1])
      end = match[2] ? Math.min(Number(match[2]), end) : end
    }
    if (
      !Number.isSafeInteger(start) ||
      !Number.isSafeInteger(end) ||
      start > end ||
      start >= stat.size
    )
      return invalid()
    status = 206
    headers['Content-Range'] = 'bytes ' + start + '-' + end + '/' + stat.size
  }
  headers['Content-Length'] = String(Math.max(0, end - start + 1))
  if (request.method === 'HEAD' || !stat.size) return new Response(null, { status, headers })
  return new Response(Readable.toWeb(fs.createReadStream(file, { start, end })), {
    status,
    headers,
  })
}

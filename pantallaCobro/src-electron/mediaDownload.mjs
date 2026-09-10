import fs from 'node:fs'
import http from 'node:http'
import https from 'node:https'
import { pipeline } from 'node:stream/promises'

function responseFor(url, redirects = 0) {
  return new Promise((resolve, reject) => {
    const parsed = new URL(url)
    if (!['http:', 'https:'].includes(parsed.protocol)) return reject(new Error('Invalid media URL'))
    const request = (parsed.protocol === 'https:' ? https : http).get(parsed, response => {
      if (response.statusCode >= 300 && response.statusCode < 400 && response.headers.location) {
        response.resume()
        if (redirects >= 5) reject(new Error('Too many media redirects'))
        else resolve(responseFor(new URL(response.headers.location, parsed), redirects + 1))
      } else if (response.statusCode !== 200) {
        response.resume()
        reject(new Error(`Media HTTP ${response.statusCode}`))
      } else resolve(response)
    })
    request.setTimeout(30000, () => request.destroy(new Error('Media download timeout')))
    request.on('error', reject)
  })
}

export async function downloadToFile(url, destination) {
  const partial = destination + '.part'
  try {
    const response = await responseFor(url)
    await pipeline(response, fs.createWriteStream(partial))
    fs.renameSync(partial, destination)
  } catch (error) {
    if (fs.existsSync(partial)) fs.unlinkSync(partial)
    throw error
  }
}

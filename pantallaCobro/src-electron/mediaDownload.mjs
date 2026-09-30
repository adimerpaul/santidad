import fs from 'node:fs'
import { createHash } from 'node:crypto'
import http from 'node:http'
import https from 'node:https'
import { pipeline } from 'node:stream/promises'

function responseFor(url, redirects = 0) {
  return new Promise((resolve, reject) => {
    const parsed = new URL(url)
    if (!['http:', 'https:'].includes(parsed.protocol))
      return reject(new Error('Invalid media URL'))
    const request = (parsed.protocol === 'https:' ? https : http).get(parsed, (response) => {
      if (response.statusCode >= 300 && response.statusCode < 400 && response.headers.location) {
        response.resume()
        if (redirects >= 5) reject(new Error('Too many media redirects'))
        else resolve(responseFor(new URL(response.headers.location, parsed), redirects + 1))
      } else if (response.statusCode !== 200) {
        response.resume()
        reject(new Error(`Media HTTP ${response.statusCode}`))
      } else {
        // Timeout de inactividad de 10 minutos (600,000 ms) en el socket de respuesta
        response.setTimeout(600000, () => {
          response.destroy(new Error('Media download socket timeout'))
        })
        resolve(response)
      }
    })
    // 10 minutos para iniciar la conexión y recibir cabeceras
    request.setTimeout(600000, () => request.destroy(new Error('Media download timeout')))
    request.on('error', reject)
  })
}

export async function downloadToFile(url, destination, expected = {}) {
  const partial = destination + '.part'
  try {
    const response = await responseFor(url)
    await pipeline(response, fs.createWriteStream(partial))
    if (!(await validMediaFile(partial, expected)))
      throw new Error('Incomplete media or checksum mismatch')
    fs.renameSync(partial, destination)
  } catch (error) {
    if (fs.existsSync(partial)) fs.unlinkSync(partial)
    throw error
  }
}

const verifiedFiles = new Map()
export async function validMediaFile(file, expected = {}) {
  if (!fs.existsSync(file)) return false
  const stat = fs.statSync(file)
  if (!stat.size || (Number(expected.size) > 0 && stat.size !== Number(expected.size))) return false
  if (!expected.sha256) return true // Old uploads still benefit from atomic HTTP downloads.
  const key = file + ':' + stat.size + ':' + stat.mtimeMs + ':' + expected.sha256
  if (verifiedFiles.has(key)) return true
  const hash = createHash('sha256')
  for await (const chunk of fs.createReadStream(file)) hash.update(chunk)
  if (hash.digest('hex') !== expected.sha256) return false
  if (verifiedFiles.size > 1000) verifiedFiles.clear()
  verifiedFiles.set(key, true)
  return true
}

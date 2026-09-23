export async function fetchJson(url, options = {}) {
  const response = await fetch(url, { cache: 'no-store', signal: AbortSignal.timeout(10000), ...options })
  if (!response.ok) throw new Error(`HTTP ${response.status}`)
  return response.json()
}

export function measureVideo(url) {
  return new Promise((resolve, reject) => {
    const video = document.createElement('video')
    const finish = (error) => {
      clearTimeout(timer)
      const duration = Math.round(video.duration * 1000)
      video.onloadedmetadata = null
      video.onerror = null
      video.removeAttribute('src')
      video.load()
      if (error || !Number.isFinite(duration) || duration <= 0) reject(error || new Error('Invalid duration'))
      else resolve(duration)
    }
    const timer = setTimeout(() => finish(new Error('Metadata timeout')), 30000)
    video.preload = 'metadata'
    video.onloadedmetadata = () => finish()
    video.onerror = () => finish(new Error('Cannot read video metadata'))
    video.src = url
  })
}

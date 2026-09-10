// Use a monotonic clock after sampling the server: changing the Windows clock
// cannot move playback. Prefer the lowest round trip in a rolling sample window.
export class ServerClock {
  constructor(now = () => performance.now()) {
    this.now = now
    this.samples = []
  }

  sample(serverMs, start, end) {
    if (!Number.isFinite(serverMs) || end < start || end - start > 5000) return
    this.samples.push({ serverMs: serverMs + (end - start) / 2, at: end, rtt: end - start })
    this.samples = this.samples.slice(-5)
  }

  get time() {
    const best = this.samples.reduce((a, b) => !a || b.rtt < a.rtt ? b : a, null)
    return best ? best.serverMs + this.now() - best.at : null
  }
}

export function playbackPosition(manifest, serverMs) {
  if (!manifest?.ready || !manifest.items?.length || !Number.isFinite(serverMs)) return null
  const durations = manifest.items.map(ad => Number(ad.duration_ms))
  if (durations.some(ms => !Number.isInteger(ms) || ms <= 0)) return null
  const cycle = durations.reduce((total, ms) => total + ms, 0)
  let offset = ((serverMs - manifest.epoch_ms) % cycle + cycle) % cycle
  for (let index = 0; index < durations.length; index++) {
    if (offset < durations[index]) return { index, offsetMs: offset, remainingMs: durations[index] - offset }
    offset -= durations[index]
  }
  return null
}

export function localMediaId(ad) {
  return ad.media_version ? `${ad.file_id}.${ad.media_version}.${ad.file_id.split('.').pop()}` : ad.file_id
}

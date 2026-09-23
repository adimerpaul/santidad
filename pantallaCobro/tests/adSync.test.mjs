import test from 'node:test'
import assert from 'node:assert/strict'
import { playbackPosition, ServerClock } from '../src/utils/adSync.mjs'

const manifest = { ready: true, epoch_ms: 0, items: [10000, 23000, 10000].map(duration_ms => ({ duration_ms })) }

test('late startup, exact boundaries and cycle wrap use the same schedule', () => {
  for (const [time, index, offset, remaining] of [
    [0, 0, 0, 10000], [9999, 0, 9999, 1], [10000, 1, 0, 23000],
    [25000, 1, 15000, 8000], [33000, 2, 0, 10000], [43000, 0, 0, 10000],
    [4300025000, 1, 15000, 8000], [-1, 2, 9999, 1],
  ]) assert.deepEqual(playbackPosition(manifest, time), { index, offsetMs: offset, remainingMs: remaining })
})

test('incomplete or offline-without-clock manifests cannot drive synchronization', () => {
  assert.equal(playbackPosition(manifest, null), null)
  assert.equal(playbackPosition({ ...manifest, ready: false }, 10), null)
  assert.equal(playbackPosition({ ...manifest, items: [{ duration_ms: 0 }] }, 10), null)
})

test('different local clock origins and network latency converge; wall clock is irrelevant', () => {
  let aTick = 1000
  let bTick = 80000
  const a = new ServerClock(() => aTick)
  const b = new ServerClock(() => bTick)
  a.sample(25000, 800, 1000)
  b.sample(25000, 79800, 80000)
  assert.equal(a.time, 25100)
  assert.deepEqual(playbackPosition(manifest, a.time), playbackPosition(manifest, b.time))
  aTick += 3000
  bTick += 3000
  a.sample(999999, aTick - 3000, aTick) // noisy sample must not replace the faster one
  assert.equal(a.time, b.time)
  a.sample(999999, aTick - 6000, aTick) // excessive RTT is rejected
  assert.equal(a.time, 28100)
})

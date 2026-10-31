const storageKey = userId => `santidad.pending-sale.${userId}`

export function readPendingSale (userId) {
  const saved = sessionStorage.getItem(storageKey(userId))
  if (!saved) return null
  const pending = JSON.parse(saved)
  if (Number(pending.userId) !== Number(userId) || !pending.request_id || pending.payload?.request_id !== pending.request_id) throw new Error('Hay una venta pendiente que requiere revisión en Movimientos.')
  return pending
}

export function savePendingSale (pending) {
  // Persist before sending: refreshing this tab must reuse the same operation.
  sessionStorage.setItem(storageKey(pending.userId), JSON.stringify(pending))
}

export function clearPendingSale (userId) { sessionStorage.removeItem(storageKey(userId)) }

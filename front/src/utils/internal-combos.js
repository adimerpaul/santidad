import { roundSalePrice, roundCurrency } from './money'

export const createComboGroup = () => {
  // getRandomValues also works when caja is opened over HTTP on the local network.
  const bytes = new Uint8Array(16)
  globalThis.crypto.getRandomValues(bytes)
  bytes[6] = (bytes[6] & 15) | 64
  bytes[8] = (bytes[8] & 63) | 128
  const hex = Array.from(bytes, b => b.toString(16).padStart(2, '0')).join('')
  return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`
}

export const comboLineKey = row => `${row.id}${row.combo_grupo ? ':' + row.combo_grupo : ''}`
export const comboMetadata = row => row.combo_id ? { combo_id: row.combo_id, combo_grupo: row.combo_grupo, combo_cantidad: row.combo_cantidad, combo_version: row.combo_version } : {}
export const comboTotals = items => {
  const base = roundCurrency(items.reduce((n, p) => n + Number(p.precio_base) * Number(p.cantidad), 0))
  const total = roundCurrency(items.reduce((n, p) => n + Number(p.precio_unitario) * Number(p.cantidad), 0))
  return { base, total, ahorro: roundCurrency(Math.max(0, base - total)), porcentaje: base ? Math.max(0, (base - total) * 100 / base) : 0 }
}
export const comboUnitPrice = (base, percentage, excluded) => roundSalePrice(Number(base) * (1 - (excluded ? 0 : Number(percentage)) / 100))

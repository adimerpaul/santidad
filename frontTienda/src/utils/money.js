export function roundCurrency (value) {
  const amount = Number(value)
  return Number.isFinite(amount) ? Math.round((amount + Number.EPSILON) * 100) / 100 : 0
}

export function roundPayable (value) {
  const cents = Math.round((Number(value) + Number.EPSILON) * 100)
  return Math.round(cents / 10) / 10
}

export function roundSalePrice (value) {
  return roundPayable(value)
}

export function formatCurrency (value) {
  return roundCurrency(value).toFixed(2)
}

export function formatPayable (value) {
  return roundPayable(value).toFixed(1)
}

export function formatSalePrice (value) {
  return roundSalePrice(value).toFixed(2)
}

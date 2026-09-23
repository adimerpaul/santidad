export function activeNotices (promotions, now) {
  return promotions.filter(p => (!p.inicio_ms || p.inicio_ms <= now) && (!p.fin_ms || p.fin_ms > now))
}

export function noticeScope (promotion) {
  if (promotion.alcance === 'TODAS_CATEGORIAS') return 'Todas las categorías'
  if (promotion.alcance === 'CATEGORIA') return promotion.categoria || 'Una categoría'
  if (promotion.cantidad_productos === 1) return promotion.productos[0] || '1 producto'
  return `${promotion.cantidad_productos} productos seleccionados`
}

export function noticeDate (timestamp, withTime = false) {
  return new Intl.DateTimeFormat('es-BO', {
    timeZone: 'America/La_Paz',
    day: '2-digit',
    month: '2-digit',
    ...(withTime ? { year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false } : {})
  }).format(new Date(timestamp))
}

export function noticeRemaining (end, now) {
  if (!end) return 'Sin fecha de fin'
  const minutes = Math.max(1, Math.ceil((end - now) / 60000))
  if (minutes < 60) return `Quedan ${minutes} min`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `Quedan ${hours} h`
  const days = Math.floor(hours / 24)
  return `Quedan ${days} día${days === 1 ? '' : 's'}`
}

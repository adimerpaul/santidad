import { jsPDF as JsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const editableFields = ['quantity', 'unitPrice', 'lot', 'invoiceNumber', 'reason', 'description', 'observation', 'included']
export function withdrawalInvoiceNumber (row) {
  for (const value of [row.buy?.purchase_invoice?.numero_factura, row.buy?.factura]) {
    const number = String(value ?? '').trim()
    if (number && number !== '0') return number
  }
  return 'Sin factura'
}

export function createWithdrawalDraft (rows) {
  return rows.map(row => {
    const values = {
      id: row.id,
      reportId: row.withdrawal_report_id || '',
      origin: row.report?.tipo || '',
      agency: row.agencia?.nombre || row.buy?.agencia?.nombre || 'Almacén',
      productName: row.product?.nombre || 'Producto sin nombre',
      lot: row.buy?.lote || '',
      invoiceNumber: withdrawalInvoiceNumber(row),
      quantity: Number(row.cantidad),
      unitPrice: Math.round((Number(row.product?.precio || 0) / 1.3 + Number.EPSILON) * 100) / 100,
      reason: row.tipo || '',
      description: row.descripcion || '',
      observation: '',
      date: row.created_at || '',
      included: true
    }
    return { ...values, original: { ...values } }
  })
}

export function withdrawalRowChanged (row) {
  return editableFields.some(field => String(row[field]) !== String(row.original[field]))
}

export function withdrawalLineCents (row) {
  if (!String(row.unitPrice ?? '').trim() || !String(row.quantity ?? '').trim()) return NaN
  return Math.round(Number(row.unitPrice) * 100) * Number(row.quantity)
}

export function validateWithdrawalDraft (rows) {
  const included = rows.filter(row => row.included)
  if (!included.length) return 'Incluye al menos un producto para generar el PDF.'
  for (const row of included) {
    const label = row.productName || 'Producto'
    if (!String(row.quantity ?? '').trim() || !Number.isInteger(Number(row.quantity)) || Math.abs(Number(row.quantity)) > 1000000) {
      return label + ': ingresa una cantidad entera válida (puede ser negativa para ajustes de inventario).'
    }
    const price = Number(row.unitPrice)
    if (!String(row.unitPrice ?? '').trim() || !Number.isFinite(price) || price < 0 || price > 1000000 || Math.abs(price * 100 - Math.round(price * 100)) > 0.00001) {
      return label + ': el precio debe ser mayor o igual a cero y tener hasta 2 decimales.'
    }
    if (!String(row.reason).trim()) return label + ': indica el motivo.'
    for (const [field, max] of [['lot', 100], ['invoiceNumber', 100], ['reason', 120], ['description', 1000], ['observation', 1000]]) {
      if (String(row[field] || '').length > max) return label + ': el texto es demasiado largo.'
    }
  }
  return ''
}

export function buildWithdrawalPdf ({ rows, title, notes, scope, preparedBy, generatedAt = new Date() }) {
  const error = validateWithdrawalDraft(rows)
  if (error) throw new Error(error)
  if (!String(title || '').trim() || title.length > 100) throw new Error('Escribe un título de hasta 100 caracteres.')
  if (String(notes || '').length > 3000) throw new Error('Las observaciones generales admiten hasta 3000 caracteres.')
  const included = rows.filter(row => row.included)
  const doc = new JsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' })
  const width = doc.internal.pageSize.getWidth()
  const height = doc.internal.pageSize.getHeight()
  const money = cents => (cents / 100).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
  const date = value => {
    if (!value) return ''
    const parsed = new Date(value.includes('T') ? value : value.replace(' ', 'T'))
    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  }
  doc.setFont('helvetica', 'bold')
  doc.setFontSize(16)
  doc.setTextColor(34, 66, 104)
  const heading = doc.splitTextToSize(title.trim(), width - 20)
  doc.text(heading, 10, 14)
  let y = 14 + heading.length * 6
  doc.setFont('helvetica', 'normal')
  doc.setFontSize(8)
  doc.setTextColor(95, 110, 130)
  const context = doc.splitTextToSize(String(scope || ''), width - 20)
  doc.text(context, 10, y)
  y += context.length * 4 + 2
  doc.text('Preparado por: ' + (preparedBy || 'Usuario') + ' | Generado: ' + date(generatedAt.toISOString()), 10, y)
  const table = included.map(row => [
    '#' + row.reportId + '\n' + row.origin,
    row.agency,
    row.productName,
    row.lot || 'N/A',
    String(row.invoiceNumber || '').trim() || 'Sin factura',
    String(row.quantity),
    money(Math.round(Number(row.unitPrice) * 100)),
    money(withdrawalLineCents(row)),
    row.reason,
    [row.description, row.observation && 'Observación: ' + row.observation].filter(Boolean).join('\n'),
    date(row.date)
  ])
  autoTable(doc, {
    startY: y + 5,
    margin: { top: 14, right: 10, bottom: 15, left: 10 },
    head: [['INFORME / ORIGEN', 'SUCURSAL', 'PRODUCTO', 'LOTE', 'FACTURA', 'CANT.', 'P. FACT. Bs', 'TOTAL Bs', 'MOTIVO', 'DESCRIPCIÓN / OBSERVACIONES', 'FECHA']],
    body: table,
    foot: [[{ content: 'Total del documento (Bs)', colSpan: 7, styles: { halign: 'right' } },
      money(included.reduce((sum, row) => sum + withdrawalLineCents(row), 0)),
      { content: included.length + ' productos incluidos', colSpan: 3 }]],
    showFoot: 'lastPage',
    rowPageBreak: 'avoid',
    theme: 'grid',
    styles: { font: 'helvetica', fontSize: 7, cellPadding: 1.7, lineColor: [224, 230, 238], lineWidth: 0.1, overflow: 'linebreak', valign: 'top' },
    headStyles: { fillColor: [38, 85, 140], fontSize: 7, fontStyle: 'bold' },
    footStyles: { fillColor: [236, 242, 250], textColor: [34, 66, 104], fontStyle: 'bold' },
    columnStyles: {
      0: { cellWidth: 20 },
      1: { cellWidth: 20 },
      2: { cellWidth: 43 },
      3: { cellWidth: 17 },
      4: { cellWidth: 21 },
      5: { cellWidth: 12, halign: 'right' },
      6: { cellWidth: 17, halign: 'right' },
      7: { cellWidth: 18, halign: 'right' },
      8: { cellWidth: 24 },
      9: { cellWidth: 62 },
      10: { cellWidth: 23 }
    }
  })
  if (String(notes || '').trim()) {
    autoTable(doc, {
      startY: doc.lastAutoTable.finalY + 5,
      margin: { top: 14, right: 10, bottom: 15, left: 10 },
      head: [['OBSERVACIONES GENERALES']],
      body: [[notes.trim()]],
      theme: 'grid',
      styles: { fontSize: 8, cellPadding: 3, overflow: 'linebreak', lineColor: [224, 230, 238], lineWidth: 0.1 },
      headStyles: { fillColor: [236, 242, 250], textColor: [34, 66, 104] }
    })
  }
  const pageCount = doc.internal.getNumberOfPages()
  for (let page = 1; page <= pageCount; page++) {
    doc.setPage(page)
    doc.setFont('helvetica', 'normal')
    doc.setFontSize(7)
    doc.setTextColor(120, 130, 145)
    doc.text('Documento preparado para revisión física', 10, height - 7)
    doc.text('Página ' + page + ' de ' + pageCount, width - 10, height - 7, { align: 'right' })
  }
  return doc
}

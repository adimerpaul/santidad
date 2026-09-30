import QRCode from 'qrcode'
import { useCounterStore } from 'stores/example-store'
import { Printd } from 'printd'
import conversor from 'conversor-numero-a-letras-es-ar'
import moment from 'moment'
export class Imprimir {
  /**
   * Datos de la sucursal que emite el documento. Se toman de la agencia de la
   * venta (cada agencia tiene su código de sucursal SIAT) y, si falta alguno,
   * se cae a los datos de casa matriz configurados en el .env del backend.
   */
  static emisor (factura) {
    const env = useCounterStore().env || {}
    const agencia = factura.agencia || {}
    const codigoSucursal = Number(
      factura.codigoSucursal ?? agencia.sucursal ?? 0
    )

    return {
      sucursal: codigoSucursal === 0 ? 'Casa Matriz' : `Sucursal ${codigoSucursal}`,
      puntoVenta: Number(factura.codigoPuntoVenta ?? 0),
      direccion: agencia.direccion || env.direccion || '',
      telefono: agencia.telefono || env.telefono || ''
    }
  }

  /**
   * Formato de la representación gráfica igual al rollo que emite SIAT:
   * Arial, títulos centrados en negrita, separadores con guiones y datos del
   * cliente en dos columnas (etiqueta a la derecha, valor a la izquierda).
   */
  static factura (factura) {
    return new Promise((resolve, reject) => {
      const ClaseConversor = conversor.conversorNumerosALetras
      const miConversor = new ClaseConversor()
      const montoTotal = parseFloat(factura.montoTotal) || 0
      const descuento = parseFloat(factura.descuento) || 0
      // SIAT: montoTotal = suma de subtotales - descuentoAdicional
      const subtotal = montoTotal + descuento
      const entero = Math.floor(montoTotal)
      const centavos = String(Math.round((montoTotal - entero) * 100)).padStart(2, '0')
      const literal = miConversor.convertToText(entero)
      const son = literal.charAt(0).toUpperCase() + literal.slice(1)
      const opts = {
        errorCorrectionLevel: 'M',
        type: 'png',
        quality: 0.95,
        width: 110,
        margin: 1,
        color: {
          dark: '#000000',
          light: '#FFF'
        }
      }
      const env = useCounterStore().env
      const emisor = this.emisor(factura)
      const client = factura.client || {}
      const documento = client.numeroDocumento
        ? client.numeroDocumento + (client.complemento ? '-' + client.complemento : '')
        : '0'
      const fecha = factura.fechaEnvioFactura || factura.fechaEmision
      const fechaTexto = fecha ? moment(fecha).format('DD/MM/YYYY hh:mm A') : ''
      const n = v => (parseFloat(v) || 0).toFixed(2)
      QRCode.toDataURL(env.url2 + 'consulta/QR?nit=' + env.nit + '&cuf=' + factura.cuf + '&numero=' + factura.numeroFactura + '&t=2', opts).then(url => {
        let cadena = `${this.headFactura()}
<div class='fac'>
  <div class='c b'>FACTURA</div>
  <div class='c b'>CON DERECHO A CRÉDITO FISCAL</div>
  <div class='c'>${env.razon || ''}</div>
  <div class='c'>${emisor.sucursal}</div>
  <div class='c'>No. Punto de Venta ${emisor.puntoVenta}</div>
  <div class='c'>${emisor.direccion}</div>
  <div class='c'>Tel. ${emisor.telefono}</div>
  <div class='c'>Oruro</div>
  <div class='sep'></div>
  <div class='c b'>NIT</div>
  <div class='c'>${env.nit}</div>
  <div class='c b'>FACTURA N°</div>
  <div class='c'>${factura.numeroFactura}</div>
  <div class='c b'>CÓD. AUTORIZACIÓN</div>
  <div class='c cuf'>${factura.cuf}</div>
  <div class='sep'></div>
  <table class='datos'>
    <tr><td class='et'>NOMBRE/RAZÓN SOCIAL:</td><td>${client.nombreRazonSocial || 'SIN NOMBRE'}</td></tr>
    <tr><td class='et'>NIT/CI/CEX:</td><td>${documento}</td></tr>
    <tr><td class='et'>COD. CLIENTE:</td><td>${client.id || 0}</td></tr>
    <tr><td class='et'>FECHA DE EMISIÓN:</td><td>${fechaTexto}</td></tr>
  </table>
  <div class='sep'></div>
  <div class='c b'>DETALLE</div>
  <table class='det'>`
        factura.details.forEach(r => {
          cadena += `
    <tr><td colspan='2' class='b'>${r.product_id} - ${r.descripcion}</td></tr>
    <tr><td colspan='2'>Unidad de Medida: Unidad (Servicios)</td></tr>
    <tr><td>${n(r.cantidad)} X ${n(r.precioUnitario)} - 0.00</td><td class='r'>${n(r.subTotal)}</td></tr>`
        })
        cadena += `
  </table>
  <table class='tot'>
    <tr><td class='r'>SUBTOTAL Bs</td><td class='r'>${n(subtotal)}</td></tr>
    <tr><td class='r'>(-) DESCUENTO Bs</td><td class='r'>${n(descuento)}</td></tr>
    <tr><td class='r'>TOTAL Bs</td><td class='r'>${n(montoTotal)}</td></tr>
    <tr><td class='r'>(-) MONTO GIFT CARD Bs</td><td class='r'>0.00</td></tr>
    <tr class='b'><td class='r'>MONTO A PAGAR Bs</td><td class='r'>${n(montoTotal)}</td></tr>
    <tr class='b'><td class='r'>IMPORTE BASE CRÉDITO FISCAL</td><td class='r'>${n(montoTotal)}</td></tr>
  </table>
  <div class='son'>Son: ${son} ${centavos}/100 Bolivianos</div>
  <div class='sep'></div>
  <div class='c ley'>ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS, EL USO ILÍCITO SERÁ SANCIONADO PENALMENTE DE ACUERDO A LEY</div>
  <div class='c ley'>${factura.leyenda || ''}</div>
  <div class='c ley'>${factura.siatEnviado
        ? '“Este documento es la Representación Gráfica de un Documento Fiscal Digital emitido en una modalidad de facturación en línea”'
        : '“Este documento es la Representación Gráfica de un Documento Fiscal Digital emitido fuera de línea, verifique su envío con su proveedor o en la página web www.impuestos.gob.bo”'
      }</div>
  <div class='c qr'><img src="${url}"></div>
</div>
</body>
</html>`
        document.getElementById('myElement').innerHTML = cadena
        const d = new Printd()
        d.print(document.getElementById('myElement'))
        resolve(url)
      }).catch(err => {
        reject(err)
      })
    })
  }

  /**
   * Ticket reducido de la factura: emisor arriba, datos básicos a la izquierda
   * y el QR de impuestos a la derecha.
   */
  static facturaPequena (factura) {
    return new Promise((resolve, reject) => {
      const opts = {
        errorCorrectionLevel: 'M',
        type: 'png',
        quality: 0.95,
        width: 130,
        margin: 0,
        color: {
          dark: '#000000',
          light: '#FFF'
        }
      }
      const env = useCounterStore().env
      const emisor = this.emisor(factura)
      const client = factura.client || {}
      const fecha = factura.fechaEnvioFactura || factura.fechaEmision
      QRCode.toDataURL(env.url2 + 'consulta/QR?nit=' + env.nit + '&cuf=' + factura.cuf + '&numero=' + factura.numeroFactura + '&t=2', opts).then(url => {
        const cadena = `<html>
<style>
  .fp { width: 72mm; padding: 2mm; font-family: Arial, Helvetica, sans-serif; font-size: 10px; line-height: 1.2; color: #000; }
  .fp .c { text-align: center; }
  .fp .cab { margin-bottom: 6px; }
  .fp .cuerpo { display: flex; align-items: center; }
  .fp table { border-collapse: collapse; width: 100%; }
  .fp td { padding: 1px 2px; vertical-align: top; }
  .fp .et { width: 42%; }
  .fp .qr { padding-left: 4px; }
  .fp .qr img { width: 32mm; height: 32mm; display: block; }
  .fp .pie { margin-top: 6px; font-size: 9px; }
  @page { margin: 0; }
</style>
<body>
<div class='fp'>
  <div class='c cab'>
    <div>${env.razon || ''}</div>
    <div>${emisor.sucursal.toUpperCase()}</div>
  </div>
  <div class='cuerpo'>
    <table>
      <tr><td class='et'>NIT:</td><td>${env.nit}</td></tr>
      <tr><td class='et'>RAZON SOCIAL:</td><td>${client.nombreRazonSocial || 'SIN NOMBRE'}</td></tr>
      <tr><td class='et'>FACTURA N°:</td><td>${factura.numeroFactura}</td></tr>
      <tr><td class='et'>ORDEN N°:</td><td>${factura.id}</td></tr>
      <tr><td class='et'>FECHA:</td><td>${fecha ? moment(fecha).format('DD/MM/YYYY') : ''}</td></tr>
      <tr><td class='et'>IMPORTE:</td><td>${(parseFloat(factura.montoTotal) || 0).toFixed(2)}</td></tr>
    </table>
    <div class='qr'><img src="${url}"></div>
  </div>
  <div class='c pie'>${factura.siatEnviado ? 'Factura emitida en línea' : 'Factura emitida fuera de línea'}</div>
</div>
</body>
</html>`
        document.getElementById('myElement').innerHTML = cadena
        const d = new Printd()
        d.print(document.getElementById('myElement'))
        resolve(url)
      }).catch(err => {
        reject(err)
      })
    })
  }

  static nota (factura) {
    console.log('factura', factura)
    return new Promise((resolve, reject) => {
      const ClaseConversor = conversor.conversorNumerosALetras
      const miConversor = new ClaseConversor()
      const a = miConversor.convertToText(parseInt(factura.montoTotal))
      // Calcula el total antes de descuentos
      const totalAntesDescuentos = parseFloat(factura.montoTotal) +
                                  parseFloat(factura.descuento_producto || 0) +
                                  parseFloat(factura.descuento || 0)

      const opts = {
        errorCorrectionLevel: 'M',
        type: 'png',
        quality: 0.95,
        width: 100,
        margin: 1,
        color: {
          dark: '#000000',
          light: '#FFF'
        }
      }
      const emisor = this.emisor(factura)
      QRCode.toDataURL(`Fecha: ${factura.fechaEmision} Monto: ${parseFloat(factura.montoTotal).toFixed(2)}`, opts).then(url => {
        let cadena = `${this.head()}
  <div style='padding-left: 0.5cm;padding-right: 0.5cm'>
  <img src="logo.png" alt="logo" style="width: 100px; height: 100px; display: block; margin-left: auto; margin-right: auto;">
      <div class='titulo'>${factura.tipoVenta === 'Egreso' ? 'NOTA DE EGRESO' : 'NOTA DE VENTA'}</div>
      <div class='titulo2'>${emisor.sucursal}<br>
      No. Punto de Venta ${emisor.puntoVenta}<br>
${emisor.direccion}<br>
Tel. ${emisor.telefono}<br>
Oruro</div>
<hr>
<table>
<tr><td class='titder'>NOMBRE/RAZÓN SOCIAL:</td><td class='contenido'>${factura.client ? factura.client.nombreRazonSocial : ''}</td>
</tr><tr><td class='titder'>NIT/CI/CEX:</td><td class='contenido'>${factura.client ? factura.client.numeroDocumento : ''}</td></tr>
<tr><td class='titder'>FECHA DE EMISIÓN:</td><td class='contenido'>${factura.fechaEmision}</td></tr>
</table><hr><div class='titulo'>DETALLE</div>`
        factura.details.forEach(r => {
          cadena += `<div style='font-size: 12px'><b>${r.product_id} ${r.descripcion} </b></div>`
          cadena += `<div>${r.cantidad} ${parseFloat(r.precioUnitario).toFixed(2)}
                    <span style="color: grey;font-size: 7px">
                          ${r?.product?.precio ? parseFloat(r?.product?.precio).toFixed(2) : ''}
                    </span>
                    <span style='float:right'>${parseFloat(r.subTotal).toFixed(2)}</span></div>`
        })
        cadena += `<hr>
      <table style='font-size: 8px;'>
      <tr>
        <td class='titder' style='width: 60%'>MONTO TOTAL Bs</td>
        <td class='conte2'>
        ${totalAntesDescuentos.toFixed(2)}
        </td>
      </tr>
      <tr>
        <td class='titder' style='width: 60%'>APORTE Bs</td>
        <td class='conte2'>${parseFloat(factura.aporte).toFixed(2)}</td>
      </tr>
      <tr style='display: ${factura.descuento ? '' : 'none'}'>
        <td class='titder' style='width: 60%'>DESCUENTO Bs</td>
        <td class='conte2'>${parseFloat(factura.descuento || 0).toFixed(2)}</td>
      </tr>
      <tr style="display: ${factura.descuento_producto ? '' : 'none'}">
        <td class='titder' style='width: 60%'>DESC PROD Bs</td>
        <td class='conte2'>${parseFloat(factura.descuento_producto || 0).toFixed(2)}</td>
      </tr>
      <tr>
        <td class='titder' style='width: 60%'>MONTO A PAGAR Bs</td>
        <td class='conte2'>${parseFloat(factura.montoTotal).toFixed(2)}</td>
      </tr>
      </table>
      <br>
      <div>Son ${a} ${((parseFloat(factura.montoTotal) - Math.floor(parseFloat(factura.montoTotal))) * 100).toFixed(2)} /100 Bolivianos</div><hr>
      <div style='display: flex;justify-content: center;'>
        <img  src="${url}" style="width: 75px; height: 75px; display: block; margin-left: auto; margin-right: auto;">
      </div></div>
      </div>
</body>
</html>`
        document.getElementById('myElement').innerHTML = cadena
        const d = new Printd()
        d.print(document.getElementById('myElement'))
        resolve(url)
      }).catch(err => {
        reject(err)
      })
    })
  }

  static reportTotal (sales, title) {
    const montoIngreso = sales.filter(r => r.tipoVenta === 'Ingreso').reduce((a, b) => a + b.montoTotal, 0)
    const montoEgreso = sales.filter(r => r.tipoVenta === 'Egreso').reduce((a, b) => a + b.montoTotal, 0)
    const montoTotal = montoIngreso - montoEgreso
    console.log('montoTotal', montoTotal)
    return new Promise((resolve, reject) => {
      const ClaseConversor = conversor.conversorNumerosALetras
      const miConversor = new ClaseConversor()
      const montoAbsoluto = Math.abs(montoTotal)
      const a = miConversor.convertToText(parseInt(montoAbsoluto))
      const opts = {
        errorCorrectionLevel: 'M',
        type: 'png',
        quality: 0.95,
        width: 100,
        margin: 1,
        color: {
          dark: '#000000',
          light: '#FFF'
        }
      }
      const env = useCounterStore().env
      QRCode.toDataURL(` Monto: ${parseFloat(montoTotal).toFixed(2)}`, opts).then(url => {
        let cadena = `${this.head()}
  <div style='padding-left: 0.5cm;padding-right: 0.5cm'>
  <img src="logo.png" alt="logo" style="width: 100px; height: 100px; display: block; margin-left: auto; margin-right: auto;">
      <div class='titulo'>title</div>
      <div class='titulo2'>${env.razon} <br>
      Casa Matriz<br>
      No. Punto de Venta 0<br>
${env.direccion}<br>
Tel. ${env.telefono}<br>
Oruro</div>
<hr>
<table>
</table><hr><div class='titulo'>DETALLE</div>`
        sales.forEach(r => {
          cadena += `<div style='font-size: 12px'><b> ${r.user.name} </b></div>`
          cadena += `<div> ${parseFloat(r.montoTotal).toFixed(2)} ${r.tipoVenta}
          <span style='float:right'> ${r.tipoVenta === 'Egreso' ? '-' : ''} ${parseFloat(r.montoTotal).toFixed(2)}</span></div>`
        })
        cadena += `<hr>
      <table style='font-size: 8px;'>
      <tr><td class='titder' style='width: 60%'>SUBTOTAL Bs</td><td class='conte2'>${parseFloat(montoTotal).toFixed(2)}</td></tr>
      </table>
      <br>
      <div>Son ${a} ${((parseFloat(montoTotal) - Math.floor(parseFloat(montoTotal))) * 100).toFixed(2)} /100 Bolivianos</div><hr>
      <div style='display: flex;justify-content: center;'>
        <img  src="${url}" style="width: 75px; height: 75px; display: block; margin-left: auto; margin-right: auto;">
      </div></div>
      </div>
</body>
</html>`
        document.getElementById('myElement').innerHTML = cadena
        const d = new Printd()
        d.print(document.getElementById('myElement'))
        resolve(url)
      }).catch(err => {
        reject(err)
      })
    })
  }

  static reciboCompra (buy) {
    return new Promise((resolve, reject) => {
      const ClaseConversor = conversor.conversorNumerosALetras
      const miConversor = new ClaseConversor()
      const a = miConversor.convertToText(parseInt(buy.total))
      const opts = {
        errorCorrectionLevel: 'M',
        type: 'png',
        quality: 0.95,
        width: 100,
        margin: 1,
        color: {
          dark: '#000000',
          light: '#FFF'
        }
      }
      const env = useCounterStore().env
      QRCode.toDataURL(`Fecha: ${buy.date} Monto: ${parseFloat(buy.total).toFixed(2)}`, opts).then(url => {
        let cadena = `${this.head()}
    <div style='padding-left: 0.5cm;padding-right: 0.5cm'>
    <img src="logo.png" alt="logo" style="width: 100px; height: 100px; display: block; margin-left: auto; margin-right: auto;">
      <div class='titulo'>RECIBO DE COMPRA</div>
      <div class='titulo2'>${env.razon} <br>
      Casa Matriz<br>
      No. Punto de Venta 0<br>
    ${env.direccion}<br>
    Tel. ${env.telefono}<br>
    Oruro</div>
    <hr>
    <table>
    </table><hr><div class='titulo'>DETALLE</div>`
        // factura.details.forEach(r => {
        cadena += `<div style='font-size: 12px'><b>${buy.product_id} ${buy.product.descripcion} </b></div>`
        cadena += `<div>${buy.quantity} ${parseFloat(buy.price).toFixed(2)} 0.00
          //           <span style='float:right'>${parseFloat(buy.total).toFixed(2)}</span></div>`
        // })
        cadena += `<hr>
      <table style='font-size: 8px;'>
      <tr><td class='titder' style='width: 60%'>SUBTOTAL Bs</td><td class='conte2'>${parseFloat(buy.total).toFixed(2)}</td></tr>
      </table>
      <br>
      <div>Son ${a} ${((parseFloat(buy.total) - Math.floor(parseFloat(buy.total))) * 100).toFixed(2)} /100 Bolivianos</div><hr>
      <div style='display: flex;justify-content: center;'>
        <img  src="${url}" style="width: 75px; height: 75px; display: block; margin-left: auto; margin-right: auto;">
      </div></div>
      </div>
    </body>
    </html>`
        document.getElementById('myElement').innerHTML = cadena
        const d = new Printd()
        d.print(document.getElementById('myElement'))
        resolve(url)
      }).catch(err => {
        reject(err)
      })
    })
  }

  static reciboTransferenciaMultiple (productos, nombreOrigen, nombreDestino, fechaHora) {
    return new Promise((resolve, reject) => {
      const opts = {
        errorCorrectionLevel: 'M',
        type: 'png',
        quality: 0.95,
        width: 100,
        margin: 1,
        color: {
          dark: '#000000',
          light: '#FFF'
        }
      }
      const store = useCounterStore()
      const env = store.env || {}

      if (!env.razon) {
        console.warn('⚠️ Variable "env.razon" está vacía o no definida.')
        return reject('Información de entorno no disponible')
      }
      QRCode.toDataURL(`de: ${nombreOrigen} A: ${nombreDestino}`, opts).then(url => {
        const fechaHora = new Date().toLocaleString('es-BO', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit'
        })
        let cadena = `${this.head()}
        <div style='padding-left: 0.5cm;padding-right: 0.5cm'>
          <img src="logo.png" alt="logo" style="width: 100px; height: 100px; display: block; margin: auto;">
          <div class='titulo'>RECIBO DE TRANSFERENCIA</div>
          <div class='titulo2'>${env.razon}<br>Casa Matriz<br>No. Punto de Venta 0<br>
          ${env.direccion}<br>Tel. ${env.telefono}<br>Oruro</div>
          <hr>
          <div class='contenido'><b>Origen:</b> ${nombreOrigen}</div>
          <div class='contenido'><b>Destino:</b> ${nombreDestino}</div>
          <div class='contenido'><b>Fecha y hora:</b> ${fechaHora}</div>
          <hr>
          <div class='titulo'>DETALLE DE PRODUCTOS</div>`
        productos.forEach(p => {
          cadena += `<div style='font-size: 12px'><b>Producto:</b> ${p.nombre}</div>`
          cadena += `<div><b>Cantidad:</b> ${p.cantidad}</div><hr>`
        })
        cadena += `
            </table>
            <div style='display: flex;justify-content: center;'>
              <img  src="${url}" style="width: 75px; height: 75px;">
            </div>
          </div>
          </body>
        </html>`
        const elem = document.getElementById('myElement')
        if (elem) {
          elem.style.display = 'block'
          elem.innerHTML = cadena
          setTimeout(() => {
            const d = new Printd()
            d.print(elem)
            elem.style.display = 'none'
            resolve(url)
          }, 100)
        } else {
          reject('Elemento con id "myElement" no encontrado en el DOM')
        }
      }).catch(err => reject(err))
    })
  }

  static headFactura () {
    return `<html>
<style>
  .fac {
    width: 72mm;
    padding: 0 2mm;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10px;
    line-height: 1.25;
    color: #000;
  }
  .fac .c { text-align: center; }
  .fac .r { text-align: right; }
  .fac .b { font-weight: bold; }
  .fac .cuf { word-break: break-all; }
  .fac .sep { border-top: 1.5px dashed #000; margin: 5px 0; }
  .fac table { width: 100%; border-collapse: collapse; font-size: 10px; }
  .fac td { padding: 1px 0; vertical-align: top; }
  .fac .datos td { padding: 2px 0; }
  .fac .datos .et { font-weight: bold; text-align: right; width: 52%; padding-right: 6px; }
  .fac .det td { padding: 1px 0; }
  .fac .tot { border-top: 1px dotted #000; margin-top: 3px; }
  .fac .tot td:first-child { width: 75%; padding-right: 6px; }
  .fac .son { margin-top: 10px; }
  .fac .ley { font-size: 9px; margin-bottom: 5px; }
  .fac .qr { margin-top: 6px; }
  @page { margin: 0; }
</style>
<body>`
  }

  static head () {
    return `<html>
<style>
      .titulo{
      font-size: 12px;
      text-align: center;
      font-weight: bold;
      }
      .titulo2{
      font-size: 10px;
      text-align: center;
      }
            .titulo3{
      font-size: 10px;
      text-align: center;
      width:70%;
      }
            .contenido{
      font-size: 10px;
      text-align: left;
      }
      .conte2{
      font-size: 10px;
      text-align: right;
      }
      .titder{
      font-size: 12px;
      text-align: right;
      font-weight: bold;
      }
      hr{
  border-top: 1px dashed   ;
}
  table{
    width:100%
  }
  h1 {
    color: black;
    font-family: sans-serif;
  }
  </style>
<body>
<div style="width: 300px;">`
  }
}

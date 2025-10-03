import QRCode from 'qrcode'
import { useCounterStore } from 'stores/example-store'
import { Printd } from 'printd'
import conversor from 'conversor-numero-a-letras-es-ar'
export class Imprimir {
  static factura (factura) {
    return new Promise((resolve, reject) => {
      const ClaseConversor = conversor.conversorNumerosALetras
      const miConversor = new ClaseConversor()
      const a = miConversor.convertToText(parseInt(factura.montoTotal))
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
      QRCode.toDataURL(env.url2 + 'consulta/QR?nit=' + env.nit + '&cuf=' + factura.cuf + '&numero=' + factura.numeroFactura + '&t=2', opts).then(url => {
        let cadena = `${this.head()}
  <div style='padding-left: 0.5cm;padding-right: 0.5cm'>
      <div class='titulo'>FACTURA <br>CON DERECHO A CREDITO FISCAL</div>
      <div class='titulo2'>${env.razon} <br>
      Casa Matriz<br>
      No. Punto de Venta 0<br>
${env.direccion}<br>
Tel. ${env.telefono}<br>
Oruro</div>
<hr>
<div class='titulo'>NIT</div>
<div class='titulo2'>${env.nit}</div>
<div class='titulo'>FACTURA N°</div>
<div class='titulo2'>${factura.numeroFactura}</div>
<div class='titulo'>CÓD. AUTORIZACIÓN</div>
<div class='titulo2'>${factura.cuf}</div>
<hr>
<table>
<tr><td class='titder'>NOMBRE/RAZÓN SOCIAL:</td><td class='contenido'>${factura.client.nombreRazonSocial}</td>
</tr><tr><td class='titder'>NIT/CI/CEX:</td><td class='contenido'>${factura.client.numeroDocumento}</td></tr>
<tr><td class='titder'>COD. CLIENTE:</td ><td class='contenido'>${factura.client.id}</td></tr>
<tr><td class='titder'>FECHA DE EMISIÓN:</td><td class='contenido'>${factura.fechaEmision}</td></tr>
</table><hr><div class='titulo'>DETALLE</div>`
        factura.details.forEach(r => {
          cadena += `<div style='font-size: 12px'><b>${r.product_id} ${r.descripcion} </b></div>`
          cadena += `<div>${r.cantidad} ${parseFloat(r.precioUnitario).toFixed(2)} 0.00
                    <span style='float:right'>${parseFloat(r.subTotal).toFixed(2)}</span></div>`
        })
        cadena += `<hr>
      <table style='font-size: 8px;'>
      <tr><td class='titder' style='width: 60%'>SUBTOTAL Bs</td><td class='conte2'>${parseFloat(factura.montoTotal).toFixed(2)}</td></tr>
      <tr><td class='titder'>DESCUENTO Bs</td><td class='conte2'>0.00</td></tr>
      <tr><td class='titder'>TOTAL Bs</td><td class='conte2'>${parseFloat(factura.montoTotal).toFixed(2)}</td></tr>
      <tr><td class='titder'>MONTO GIFT CARD Bs</td ><td class='conte2'>0.00</td></tr>
      <tr><td class='titder'>MONTO A PAGAR Bs</td><td class='conte2'>${parseFloat(factura.montoTotal).toFixed(2)}</td></tr>
      <tr><td class='titder' style='font-size: 8px'>IMPORTE BASE CRÉDITO FISCAL Bs</td>
      <td class='conte2'>${parseFloat(factura.montoTotal).toFixed(2)}</td></tr>
      </table>
      <br>
      <div>Son ${a} ${((parseFloat(factura.montoTotal) - Math.floor(parseFloat(factura.montoTotal))) * 100).toFixed(2)} /100 Bolivianos</div><hr>
      <div class='titulo2' style='font-size: 9px'>
      ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS,<br>
      EL USO ILÍCITO SERÁ SANCIONADO PENALMENTE DE<br>
      ACUERDO A LEY<br><br>
      ${factura.leyenda} <br><br>
      "Este documento es la Representación Gráfica de un<br>
      Documento Fiscal Digital emitido en una modalidad de<br>
      facturación en línea"</div><br>
      <div style='display: flex;justify-content: center;'> <img  src="${url}" ></div></div>
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

      // Calcula los porcentajes con redondeo
      const porcentajeDescuento = totalAntesDescuentos > 0
        ? Math.round((parseFloat(factura.descuento || 0) / totalAntesDescuentos) * 100)
        : 0

      const porcentajeDescuentoProducto = totalAntesDescuentos > 0
        ? Math.round((parseFloat(factura.descuento_producto || 0) / totalAntesDescuentos) * 100)
        : 0

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
      QRCode.toDataURL(`Fecha: ${factura.fechaEmision} Monto: ${parseFloat(factura.montoTotal).toFixed(2)}`, opts).then(url => {
        let cadena = `${this.head()}
  <div style='padding-left: 0.5cm;padding-right: 0.5cm'>
  <img src="logo.png" alt="logo" style="width: 100px; height: 100px; display: block; margin-left: auto; margin-right: auto;">
      <div class='titulo'>${factura.tipoVenta === 'Egreso' ? 'NOTA DE EGRESO' : 'NOTA DE VENTA'}</div>
      <div class='titulo2'>${env.razon} <br>
      Casa Matriz<br>
      No. Punto de Venta 0<br>
${env.direccion}<br>
Tel. ${env.telefono}<br>
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
        <td class='titder' style='width: 60%'>DESCUENTO ${porcentajeDescuento}% Bs</td>
        <td class='conte2'>${parseFloat(factura.descuento || 0).toFixed(2)}</td>
      </tr>
      <tr style="display: ${factura.descuento_producto ? '' : 'none'}">
        <td class='titder' style='width: 60%'>DESC PROD ${porcentajeDescuentoProducto}% Bs</td>
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

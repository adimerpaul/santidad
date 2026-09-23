function address(value) {
  let text = String(value || '').trim()
  if (!text) throw new Error('Ingrese la dirección del servidor.')
  if (!/^[a-z][a-z\d+.-]*:\/\//i.test(text)) text = 'http://' + text
  let url
  try { url = new URL(text) } catch { throw new Error('La dirección del servidor no es válida.') }
  if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password) {
    throw new Error('Use una dirección http:// o https:// sin credenciales.')
  }
  url.search = ''
  url.hash = ''
  return url.toString().replace(/\/+$/, '')
}

export function backendAddress(value) {
  return address(value).replace(/\/api$/i, '')
}

export function socketAddress(value) {
  return address(value)
}

export function socketTarget(value) {
  const url = new URL(socketAddress(value))
  // Socket.IO interprets a URL path as a namespace. Here it is the server's HTTP path.
  const prefix = url.pathname.replace(/\/+$/, '').replace(/\/socket\.io$/i, '')
  return { url: url.origin, path: `${prefix}/socket.io` }
}

export function normalizeTerminalConfig(value) {
  const agencia = Number(value.agencia)
  const caja = Number(value.caja)
  const mostrarHora = value.mostrarHora !== undefined ? Boolean(value.mostrarHora) : true
  if (!Number.isInteger(agencia) || agencia < 1 || ![1, 2, 3, 4].includes(caja)) {
    throw new Error('Seleccione la sucursal y el número de caja de esta pantalla.')
  }
  return { serverIp: backendAddress(value.serverIp), socketIp: socketAddress(value.socketIp), agencia, caja, mostrarHora }
}


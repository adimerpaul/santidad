import test from 'node:test'
import assert from 'node:assert/strict'
import { backendAddress, socketTarget, normalizeTerminalConfig } from '../src/utils/terminalConfig.mjs'

test('backend accepts a root or API URL without repeating /api', () => {
  assert.equal(backendAddress(' 192.168.1.50:8000/api/ '), 'http://192.168.1.50:8000')
  assert.equal(backendAddress('https://example.test/back/public/api/'), 'https://example.test/back/public')
  assert.equal(backendAddress('https://example.test/'), 'https://example.test')
})

test('socket URLs use the HTTP path instead of silently selecting a namespace', () => {
  assert.deepEqual(socketTarget('http://localhost:3000/'), { url: 'http://localhost:3000', path: '/socket.io' })
  assert.deepEqual(socketTarget('https://example.test/socket.io/?EIO=4'), { url: 'https://example.test', path: '/socket.io' })
  assert.deepEqual(socketTarget('https://example.test/sockets/'), { url: 'https://example.test', path: '/sockets/socket.io' })
})

test('invalid addresses and incomplete terminal identity cannot be applied', () => {
  const config = { serverIp: 'localhost:8000/api', socketIp: 'localhost:3000', agencia: '2', caja: '3' }
  assert.deepEqual(normalizeTerminalConfig(config), { serverIp: 'http://localhost:8000', socketIp: 'http://localhost:3000', agencia: 2, caja: 3, mostrarHora: true })
  assert.throws(() => normalizeTerminalConfig({ ...config, caja: 0 }))
  assert.throws(() => normalizeTerminalConfig({ ...config, serverIp: 'file:///tmp' }))
  assert.throws(() => normalizeTerminalConfig({ ...config, socketIp: '' }))
})

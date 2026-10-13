import test from 'node:test'
import assert from 'node:assert/strict'
import path from 'node:path'
import vm from 'node:vm'
import { createRequire } from 'node:module'
import { fileURLToPath } from 'node:url'
import { buildSync } from 'esbuild'

const require = createRequire(import.meta.url)
const project = fileURLToPath(new URL('../', import.meta.url))
const bundled = buildSync({
  entryPoints: [path.join(project, 'src-electron/cursorLock.mjs')],
  bundle: true, platform: 'node', format: 'cjs', write: false,
}).outputFiles[0].text

function resolveExecutable(existing, resourcesPath = undefined) {
  const module = { exports: {} }
  const mockFs = { existsSync: candidate => existing.includes(candidate) }
  const context = {
    module, exports: module.exports,
    require: name => name === 'node:fs' ? mockFs : require(name),
    process: { resourcesPath, cwd: () => project },
    __dirname: path.join(project, '.quasar/electron'), console,
  }
  vm.runInNewContext(bundled, context)
  return module.exports.getCursorLockExePath(path.join(project, 'test-user-data'))
}

test('Quasar CommonJS bundle resolves the development executable without import.meta.url', () => {
  const exe = path.join(project, 'src-electron/cursor-lock.exe')
  assert.equal(resolveExecutable([exe]), exe)
})

test('packaged Electron keeps the resources executable as its first choice', () => {
  const resources = path.join(project, 'test-resources')
  const exe = path.join(resources, 'cursor-lock.exe')
  assert.equal(resolveExecutable([exe, path.join(project, 'src-electron/cursor-lock.exe')], resources), exe)
})

test('missing executable does not throw when importing the bundled helper', () => {
  assert.equal(resolveExecutable([]), null)
})

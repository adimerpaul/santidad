// Run explicitly with Electron, not with node --test. No production data or visible windows.
const { app, BrowserWindow, protocol, net } = require('electron')
const fs = require('node:fs')
const os = require('node:os')
const path = require('node:path')
const assert = require('node:assert/strict')
const { pathToFileURL } = require('node:url')

const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'pcpubli-media-test-'))
app.setPath('userData', temp)
protocol.registerSchemesAsPrivileged([
  { scheme: 'localmedia', privileges: { stream: true, bypassCSP: true, supportFetchAPI: true } },
])
const deadline = setTimeout(() => { console.error('Electron media test timed out'); app.exit(1) }, 20000)

app.whenReady().then(async () => {
  const { localMediaUrl, localMediaFileName } = await import(pathToFileURL(path.resolve(__dirname, '../src/utils/localMedia.mjs')).href)
  const { localMediaFileUrl } = await import(pathToFileURL(path.resolve(__dirname, '../src-electron/localMediaFile.mjs')).href)
  protocol.handle('localmedia', request => net.fetch(localMediaFileUrl(request.url, temp)))
  const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jMZkAAAAASUVORK5CYII=', 'base64')
  const window = new BrowserWindow({ show: false, webPreferences: { contextIsolation: true, backgroundThrottling: false } })
  await window.loadURL('data:text/html,<html><body></body></html>')
  for (const fileId of ['publicidad/24 HRA.png.version.png', 'publicidad/Promoción 8% #1.png', 'publicidad/Oferta %20 literal.png']) {
    fs.writeFileSync(path.join(temp, localMediaFileName(fileId)), png)
    const url = localMediaUrl(fileId)
    const dimensions = await window.webContents.executeJavaScript(`new Promise((resolve, reject) => {
      const image = new Image();
      image.onload = () => resolve([image.naturalWidth, image.naturalHeight]);
      image.onerror = () => reject(new Error('Image failed to load'));
      image.src = ${JSON.stringify(url)};
      document.body.appendChild(image);
    })`)
    assert.deepEqual(dimensions, [1, 1])
    console.log('PASS Electron image: ' + fileId)
  }

  // Verificar que un video real también cargue metadata vía localmedia://
  const appDataMedia = path.join(process.env.APPDATA, 'PC Publicidad', 'media')
  const videoFile = 'publicidad_LOGO.mp4.19aef4b867c907e434c809d9d2c710196f6ce4bd5cf7e2646f7aa416993df90a.mp4'
  if (fs.existsSync(path.join(appDataMedia, videoFile))) {
    fs.copyFileSync(path.join(appDataMedia, videoFile), path.join(temp, videoFile))
    const videoUrl = 'localmedia://' + encodeURIComponent(videoFile)
    const meta = await window.webContents.executeJavaScript(`new Promise((resolve, reject) => {
      const v = document.createElement('video');
      v.onloadedmetadata = () => resolve({ duration: v.duration, w: v.videoWidth, h: v.videoHeight });
      v.onerror = () => reject(new Error('Video load error: ' + (v.error ? v.error.code : 'unknown')));
      v.src = ${JSON.stringify(videoUrl)};
      document.body.appendChild(v);
    })`)
    assert(meta.duration > 0, 'Video duration must be > 0')
    console.log('PASS Electron video metadata:', meta)
  }

  window.destroy()
  clearTimeout(deadline)
  app.exit(0)
}).catch(error => {
  console.error(error)
  clearTimeout(deadline)
  app.exit(1)
})

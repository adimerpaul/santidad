// Run explicitly with Electron, not with node --test. No production data or visible windows.
const { app, BrowserWindow, protocol } = require('electron')
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
const deadline = setTimeout(() => { console.error('Electron media test timed out'); app.exit(1) }, 60000)
process.on('uncaughtException', error => { console.error('FAIL uncaught exception', error); app.exit(1) })
process.on('unhandledRejection', error => { console.error('FAIL unhandled rejection', error); app.exit(1) })
process.on('warning', warning => { console.error('FAIL runtime warning', warning); app.exit(1) })

app.whenReady().then(async () => {
  const { localMediaUrl, localMediaFileName } = await import(pathToFileURL(path.resolve(__dirname, '../src/utils/localMedia.mjs')).href)
  const { localMediaResponse } = await import(pathToFileURL(path.resolve(__dirname, '../src-electron/localMediaResponse.mjs')).href)
  protocol.handle('localmedia', request => localMediaResponse(request, temp))
  const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jMZkAAAAASUVORK5CYII=', 'base64')
  const window = new BrowserWindow({ show: false, webPreferences: { contextIsolation: true, backgroundThrottling: false } })
  await window.loadURL('data:text/html,<html><body></body></html>')
  fs.writeFileSync(path.join(temp, 'cancel.mp4'), Buffer.alloc(1024 * 1024, 7))
  await window.webContents.executeJavaScript(`(async () => {
    for (let i = 0; i < 100; i++) {
      const response = await fetch('localmedia://cancel.mp4', { headers: { Range: 'bytes=' + i + '-' } });
      const reader = response.body.getReader();
      if (i % 2) await reader.read();
      await reader.cancel();
    }
    const response = await fetch('localmedia://cancel.mp4', { headers: { Range: 'bytes=3-5' } });
    const bytes = new Uint8Array(await response.arrayBuffer());
    if (response.status !== 206 || bytes.length !== 3 || bytes.some(x => x !== 7)) throw new Error('Range failed after cancellation');
  })()`)
  console.log('PASS Electron: 100 cancelled requests and subsequent range playback')
  await window.webContents.executeJavaScript(`(async () => {
    await Promise.all(Array.from({ length: 10 }, async (_, worker) => {
      for (let i = 0; i < 100; i++) {
        const abort = new AbortController();
        const pending = fetch('localmedia://cancel.mp4', {
          signal: abort.signal, headers: { Range: 'bytes=' + (worker * 100 + i) + '-' }
        });
        if (i % 3 === 0) abort.abort();
        try {
          const response = await pending;
          const reader = response.body.getReader();
          const read = reader.read();
          if (i % 3 === 1) abort.abort();
          else await reader.cancel();
          await read;
        } catch (error) { if (error.name !== 'AbortError') throw error; }
      }
    }));
  })()`)
  console.log('PASS Electron: 1000 concurrent abort/cancel requests')
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
  const videoFile = fs.existsSync(appDataMedia) && fs.readdirSync(appDataMedia).find(name => name.endsWith('.mp4'))
  if (videoFile) {
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
    await window.webContents.executeJavaScript(`(async () => {
      for (let i = 0; i < 30; i++) {
        const v = document.createElement('video');
        v.muted = true;
        document.body.appendChild(v);
        await new Promise((resolve, reject) => {
          v.onloadeddata = resolve;
          v.onerror = () => reject(new Error('Video decode failed'));
          v.src = ${JSON.stringify(videoUrl)};
          v.load();
        });
        await v.play();
        v.currentTime = v.duration * ((i % 9) / 10);
        await new Promise(resolve => setTimeout(resolve, 15));
        v.pause();
        v.removeAttribute('src');
        v.load();
        v.remove();
      }
    })()`)
    console.log('PASS Electron: 30 real video play/seek/unload cycles')
  } else {
    console.log('SKIP real video: no cached MP4 available')
  }

  await new Promise(resolve => setTimeout(resolve, 2000))

  window.destroy()
  clearTimeout(deadline)
  app.exit(0)
}).catch(error => {
  console.error(error)
  clearTimeout(deadline)
  app.exit(1)
})

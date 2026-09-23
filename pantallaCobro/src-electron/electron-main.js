import { app, BrowserWindow, ipcMain, protocol, net, Tray, Menu, powerSaveBlocker } from 'electron'
import path from 'path'
import os from 'os'
import fs from 'fs'
import { downloadToFile } from './mediaDownload.mjs'
import { localMediaFileUrl } from './localMediaFile.mjs'
import { lockCursor, unlockCursor, setCursorLockEnabled, isCursorLockEnabled } from './cursorLock.mjs'
import { exec } from 'child_process'

// needed in case process is undefined under Linux
const platform = process.platform || os.platform()

let mainWindow
let mediaDir
let tray = null
let powerBlockerId = null
app.isQuitting = false

import { screen } from 'electron'
import { autoUpdater } from 'electron-updater'

// Registrar protocolo custom ANTES de que la app esté lista
// Esto permite servir archivos locales al renderer via localmedia://nombre.mp4
protocol.registerSchemesAsPrivileged([
  {
    scheme: 'localmedia',
    privileges: {
      stream: true,
      bypassCSP: true,
      supportFetchAPI: true,
    },
  },
])

// Optimización crítica para videos pesados (50MB - 150MB):
// Evitar que Windows o Chromium reduzcan la tasa de cuadros o congelen el decodificador
// mientras el cajero interactúa con el sistema de ventas en la pantalla principal.
app.commandLine.appendSwitch('disable-background-timer-throttling')
app.commandLine.appendSwitch('disable-backgrounding-occluded-windows')
app.commandLine.appendSwitch('disable-renderer-backgrounding')
app.commandLine.appendSwitch('enable-gpu-rasterization')
app.commandLine.appendSwitch('enable-zero-copy')
app.commandLine.appendSwitch('ignore-gpu-blocklist')

// Configurar inicio automático con Windows al compilar la app
if (app.isPackaged) {
  app.setLoginItemSettings({
    openAtLogin: true,
    path: app.getPath('exe'),
    args: [],
  })
}

function getIconPath() {
  if (app.isPackaged) {
    return platform === 'win32'
      ? path.resolve(__dirname, 'icons/icon.ico')
      : path.resolve(__dirname, 'icons/icon.png')
  } else {
    return platform === 'win32'
      ? path.join(process.cwd(), 'src-electron/icons/icon.ico')
      : path.join(process.cwd(), 'src-electron/icons/icon.png')
  }
}

function createWindow() {
  // Obtener pantallas
  const displays = screen.getAllDisplays()

  // Buscar una pantalla extendida (secundaria)
  const externalDisplay = displays.find((display) => {
    return display.bounds.x !== 0 || display.bounds.y !== 0
  })

  const startMinimized = process.argv.includes('--hidden') || process.argv.includes('--minimized')

  let windowOptions = {
    icon: getIconPath(),
    useContentSize: true,
    frame: false, // Sin barra de títulos ni bordes
    fullscreen: true, // Pantalla completa
    autoHideMenuBar: true,
    show: !startMinimized, // Iniciar oculto si arranca con Windows
    skipTaskbar: true, // Ocultar siempre de la barra de tareas (solo visible en el tray)
    webPreferences: {
      contextIsolation: true,
      webSecurity: false,
      backgroundThrottling: false,
      // More info: https://v2.quasar.dev/quasar-cli-vite/developing-electron-apps/electron-preload-script
      preload: path.resolve(__dirname, process.env.QUASAR_ELECTRON_PRELOAD),
    },
  }

  // Si se detecta pantalla extendida, posicionar la ventana en ella
  if (externalDisplay) {
    windowOptions.x = externalDisplay.bounds.x
    windowOptions.y = externalDisplay.bounds.y
    windowOptions.width = externalDisplay.bounds.width
    windowOptions.height = externalDisplay.bounds.height
  }

  mainWindow = new BrowserWindow(windowOptions)

  mainWindow.loadURL(process.env.APP_URL)

  if (process.env.DEBUGGING) {
    // if on DEV or Production with debug enabled
    mainWindow.webContents.openDevTools()
  } else {
    // we're on production; no access to devtools pls
    mainWindow.webContents.on('devtools-opened', () => {
      mainWindow.webContents.closeDevTools()
    })
  }

  // Prevenir cierre de la app al cerrar la ventana, ocultándola en segundo plano (tray)
  mainWindow.on('close', (event) => {
    if (!app.isQuitting) {
      event.preventDefault()
      mainWindow.hide()
      mainWindow.setSkipTaskbar(true)
    }
  })

  mainWindow.on('closed', () => {
    mainWindow = null
  })
}

// ===== PREVENIR INSTANCIAS DUPLICADAS =====
const gotTheLock = app.requestSingleInstanceLock()
if (!gotTheLock) {
  app.quit()
} else {
  app.on('second-instance', () => {
    if (mainWindow) {
      if (mainWindow.isMinimized()) mainWindow.restore()
      mainWindow.show()
      mainWindow.focus()
    }
  })
}

// ===== INICIALIZACIÓN =====
app.whenReady().then(() => {
  // Crear directorio de media local
  mediaDir = path.join(app.getPath('userData'), 'media')
  if (!fs.existsSync(mediaDir)) {
    fs.mkdirSync(mediaDir, { recursive: true })
  }

  // Registrar protocolo localmedia:// para servir archivos locales al renderer
  // Uso: <video src="localmedia://publicidad_xxx.mp4">
  protocol.handle('localmedia', (request) => {
    return net.fetch(localMediaFileUrl(request.url, mediaDir), {
      bypassCustomProtocolHandlers: true,
    })
  })

  // ===== IPC: DESCARGAR archivo de Cloudflare R2 al disco local =====
  ipcMain.handle('media:download', async (event, fileId, type, r2Url) => {
    // fileId ahora es la ruta completa: 'publicidad/nombre.ext'
    // Usamos solo el nombre del archivo para guardarlo localmente
    const safeName = fileId.replace(/[\\/]/g, '_') // 'publicidad/foto.png' -> 'publicidad_foto.png'
    const fileName = safeName
    const filePath = path.join(mediaDir, fileName)

    // Si ya existe, retornar inmediatamente
    if (fs.existsSync(filePath)) {
      console.log(`[Media] Ya existe localmente: ${fileName}`)
      return { success: true, fileName }
    }

    // URL directa de R2: pasada desde el renderer o construida desde el file_id
    const downloadUrl = r2Url

    try {
      await downloadToFile(downloadUrl, filePath)
      return { success: true, fileName }
    } catch (error) {
      console.error('Media download failed:', error.message)
      return { success: false, error: error.message }
    }
  })

  // ===== IPC: VERIFICAR si un archivo existe localmente =====
  ipcMain.handle('media:exists', (event, fileId) => {
    const safeName = fileId.replace(/[\\/]/g, '_')
    return fs.existsSync(path.join(mediaDir, safeName))
  })

  // ===== IPC: ELIMINAR un archivo local =====
  ipcMain.handle('media:delete', (event, fileId) => {
    const safeName = fileId.replace(/[\\/]/g, '_')
    const filePath = path.join(mediaDir, safeName)
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath)
      console.log(`[Media] Eliminado: ${safeName}`)
      return true
    }
    return false
  })

  ipcMain.handle('media:cleanup', (event, activeFileIds) => {
    if (!fs.existsSync(mediaDir)) return []
    const files = fs.readdirSync(mediaDir)
    const deleted = []

    // Convertir los file_ids activos al formato de nombre local
    const activeFileNames = activeFileIds.map((id) => id.replace(/[\\/]/g, '_'))

    for (const file of files) {
      if (!activeFileNames.includes(file)) {
        try {
          fs.unlinkSync(path.join(mediaDir, file))
          deleted.push(file)
          console.log(`[Media] Limpieza: eliminado ${file}`)
        } catch (error) {
          console.warn(
            `[Media] No se pudo eliminar ${file} porque está en uso o bloqueado:`,
            error.message,
          )
        }
      }
    }
    return deleted
  })

  // ===== IPC: OBTENER ESPACIO EN DISCO (Nativo y sin bloqueos) =====
  ipcMain.handle('system:diskspace', () => {
    try {
      const stats = fs.statfsSync(mediaDir || 'C:\\')
      const freeGb = (stats.bfree * stats.bsize) / (1024 * 1024 * 1024)
      const totalGb = (stats.blocks * stats.bsize) / (1024 * 1024 * 1024)
      return {
        free: `${freeGb.toFixed(2)} GB`,
        total: `${totalGb.toFixed(2)} GB`,
      }
    } catch {
      return { free: 'N/A', total: 'N/A' }
    }
  })

  // ===== IPC: COMPROBAR ACTUALIZACIONES MANUALMENTE =====
  ipcMain.handle('updater:check', async () => {
    if (!app.isPackaged) {
      return { status: 'dev', message: 'Modo desarrollo' }
    }
    try {
      const result = await autoUpdater.checkForUpdates()
      return { status: 'ok', updateInfo: result?.updateInfo }
    } catch (err) {
      return { status: 'error', message: err.message }
    }
  })

  // ===== IPC: CONTROL DE PANTALLAS Y CONFIGURACIÓN =====
  ipcMain.handle('window:moveToPrimary', () => {
    moveToPrimaryScreen()
    return true
  })

  ipcMain.handle('window:moveToSecondary', () => {
    moveToSecondaryScreen()
    return true
  })

  ipcMain.handle('app:getVersion', () => {
    return app.getVersion()
  })

  // Evitar suspensión de pantalla mientras reproduzca publicidad
  try {
    powerBlockerId = powerSaveBlocker.start('prevent-display-sleep')
    console.log('[System] Power save blocker activado con ID:', powerBlockerId)
  } catch (e) {
    console.error('[System] No se pudo activar powerSaveBlocker:', e.message)
  }

  createTray()
  createWindow()
  setupAutoUpdater()
})

function moveToPrimaryScreen() {
  unlockCursor(app.getPath('userData'))
  if (!mainWindow) return
  const primaryDisplay = screen.getPrimaryDisplay()
  mainWindow.setFullScreen(false)
  mainWindow.setBounds({
    x: Math.round(primaryDisplay.bounds.x + (primaryDisplay.bounds.width - 850) / 2),
    y: Math.round(primaryDisplay.bounds.y + (primaryDisplay.bounds.height - 720) / 2),
    width: 850,
    height: 720,
  })
  mainWindow.show()
  mainWindow.focus()
}

function moveToSecondaryScreen() {
  if (!mainWindow) return
  const displays = screen.getAllDisplays()
  const externalDisplay = displays.find((d) => d.bounds.x !== 0 || d.bounds.y !== 0)
  if (externalDisplay) {
    mainWindow.setBounds(externalDisplay.bounds)
    mainWindow.setFullScreen(true)
    mainWindow.show()
    // Bloquear el mouse en la pantalla principal del cajero (muro invisible)
    const primary = screen.getPrimaryDisplay()
    lockCursor(primary.bounds, app.getPath('userData'))
  } else {
    mainWindow.setFullScreen(true)
    mainWindow.show()
    unlockCursor(app.getPath('userData'))
  }
}

function createTray() {
  const iconPath = getIconPath()
  tray = new Tray(iconPath)
  const contextMenu = Menu.buildFromTemplate([
    {
      label: '⚙️ Configurar Caja (Traer a esta pantalla)',
      click: () => {
        moveToPrimaryScreen()
        if (mainWindow) {
          mainWindow.webContents.send('window:openConfig')
        }
      },
    },
    {
      label: '📺 Enviar a Pantalla Cliente',
      click: () => {
        moveToSecondaryScreen()
      },
    },
    {
      label: '🔒 Muro Invisible (Bloquear mouse en monitor cajero)',
      type: 'checkbox',
      checked: isCursorLockEnabled(),
      click: (item) => {
        const primary = screen.getPrimaryDisplay()
        setCursorLockEnabled(item.checked, primary.bounds, app.getPath('userData'))
      },
    },
    { type: 'separator' },
    {
      label: 'Mostrar Reproductor',
      click: () => {
        if (mainWindow) {
          mainWindow.show()
        }
      },
    },
    {
      label: 'Ocultar en Segundo Plano',
      click: () => {
        if (mainWindow) {
          mainWindow.hide()
        }
      },
    },
    {
      label: 'Buscar Actualizaciones',
      click: () => {
        if (app.isPackaged) {
          autoUpdater.checkForUpdates().catch((e) => console.warn(e.message))
        }
      },
    },
    { type: 'separator' },
    {
      label: 'Salir de la Aplicación',
      click: () => {
        unlockCursor(app.getPath('userData'))
        app.isQuitting = true
        app.quit()
      },
    },
  ])

  tray.setToolTip('Santidad TV - PC Publicidad')
  tray.setContextMenu(contextMenu)

  // Doble click para restaurar
  tray.on('double-click', () => {
    if (mainWindow) {
      mainWindow.show()
    }
  })
}

app.on('window-all-closed', () => {
  unlockCursor(app.getPath('userData'))
  if (platform !== 'darwin') {
    app.quit()
  }
})

app.on('will-quit', () => {
  unlockCursor(app.getPath('userData'))
})

app.on('activate', () => {
  if (mainWindow === null) {
    createWindow()
  }
})

// ===== ESTADO DEL TERMINAL (PUBLICIDAD VS COBRO/QR) =====
let isTerminalBusy = false
let pendingUpdateInstall = false

function applyPendingUpdate() {
  if (isTerminalBusy) {
    console.log('[Updater] Pantalla ocupada en cobro/QR. Actualización pospuesta hasta volver a publicidad.')
    pendingUpdateInstall = true
    return
  }
  console.log('[Updater] Pantalla en publicidad: aplicando actualización silenciosa al instante...')
  unlockCursor(app.getPath('userData'))
  app.isQuitting = true
  autoUpdater.quitAndInstall(true, true)
}

ipcMain.on('terminal:busyState', (event, busy) => {
  isTerminalBusy = Boolean(busy)
  if (!isTerminalBusy && pendingUpdateInstall) {
    applyPendingUpdate()
  }
})

// ===== AUTO-UPDATER SILENCIOSO (GitHub Releases) =====
function setupAutoUpdater() {
  if (!app.isPackaged) {
    console.log('[Updater] Modo desarrollo: autoUpdater inactivo.')
    return
  }

  // Configuración para que descargue e instale en silencio
  autoUpdater.autoDownload = true
  autoUpdater.autoInstallOnAppQuit = true

  autoUpdater.on('checking-for-update', () => {
    console.log('[Updater] Comprobando si hay actualizaciones en GitHub...')
  })

  autoUpdater.on('update-available', (info) => {
    console.log(
      `[Updater] ¡Nueva versión v${info.version} encontrada! Descargando en segundo plano...`,
    )
  })

  autoUpdater.on('update-not-available', (info) => {
    console.log(`[Updater] Aplicación al día (v${info.version}).`)
  })

  autoUpdater.on('error', (err) => {
    console.warn('[Updater] Error en comprobación/descarga:', err?.message || err)
  })

  autoUpdater.on('download-progress', (progress) => {
    console.log(`[Updater] Descarga en progreso: ${Math.round(progress.percent)}%`)
  })

  autoUpdater.on('update-downloaded', (info) => {
    console.log(`[Updater] Versión v${info.version} descargada.`)
    applyPendingUpdate()
  })

  // Primera comprobación rápida a los 10 segundos del arranque
  setTimeout(() => {
    autoUpdater.checkForUpdates().catch((err) => {
      console.warn('[Updater] Error al comprobar al iniciar:', err?.message || err)
    })
  }, 10000)

  // Comprobación periódica cada 5 minutos para que la actualización sea casi inmediata
  setInterval(
    () => {
      autoUpdater.checkForUpdates().catch((err) => {
        console.warn('[Updater] Error en comprobación periódica:', err?.message || err)
      })
    },
    5 * 60 * 1000,
  )
}

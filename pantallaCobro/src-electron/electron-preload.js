import { contextBridge, ipcRenderer } from 'electron'

contextBridge.exposeInMainWorld('mediaAPI', {
  download: (fileId, type, r2Url) => ipcRenderer.invoke('media:download', fileId, type, r2Url),
  exists: (fileId, type) => ipcRenderer.invoke('media:exists', fileId, type),
  delete: (fileId, type) => ipcRenderer.invoke('media:delete', fileId, type),
  cleanup: (activeFileIds) => ipcRenderer.invoke('media:cleanup', activeFileIds),
  getDiskSpace: () => ipcRenderer.invoke('system:diskspace')
})

contextBridge.exposeInMainWorld('terminalWindowAPI', {
  moveToPrimary: () => ipcRenderer.invoke('window:moveToPrimary'),
  moveToSecondary: () => ipcRenderer.invoke('window:moveToSecondary'),
  getVersion: () => ipcRenderer.invoke('app:getVersion'),
  setBusy: (busy) => ipcRenderer.send('terminal:busyState', busy),
  onOpenConfig: (callback) => ipcRenderer.on('window:openConfig', callback)
})

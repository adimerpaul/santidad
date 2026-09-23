import { spawn, execFile } from 'node:child_process'
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

let lockProcess = null
let isEnabled = true

export function getCursorLockExePath(userDataDir) {
  if (process.resourcesPath) {
    const p = path.join(process.resourcesPath, 'cursor-lock.exe')
    if (fs.existsSync(p)) return p
  }
  const dir = path.dirname(fileURLToPath(import.meta.url))
  const local = path.join(dir, 'cursor-lock.exe')
  if (fs.existsSync(local)) return local

  if (userDataDir) {
    const userExe = path.join(userDataDir, 'cursor-lock.exe')
    if (fs.existsSync(userExe)) return userExe

    const csc = 'C:\\Windows\\Microsoft.NET\\Framework64\\v4.0.30319\\csc.exe'
    if (fs.existsSync(csc)) {
      try {
        const csPath = path.join(userDataDir, 'cursor-lock.cs')
        const code = `using System;using System.Runtime.InteropServices;using System.Threading;class Program{[DllImport("user32.dll")]static extern bool ClipCursor(ref RECT lpRect);[DllImport("user32.dll")]static extern bool ClipCursor(IntPtr lpRect);[StructLayout(LayoutKind.Sequential)]struct RECT{public int Left,Top,Right,Bottom;}static void Main(string[] args){if(args.Length>=4){int l=int.Parse(args[0]),t=int.Parse(args[1]),r=int.Parse(args[2]),b=int.Parse(args[3]);RECT rect=new RECT{Left=l,Top=t,Right=r,Bottom=b};Thread tLoop=new Thread(()=>{while(true){ClipCursor(ref rect);Thread.Sleep(200);}});tLoop.IsBackground=true;tLoop.Start();Console.ReadLine();ClipCursor(IntPtr.Zero);}else{ClipCursor(IntPtr.Zero);}}}`
        fs.writeFileSync(csPath, code, 'utf8')
        const { execFileSync } = require('node:child_process')
        execFileSync(csc, ['/nologo', '/target:winexe', `/out:${userExe}`, csPath], { stdio: 'ignore' })
        if (fs.existsSync(csPath)) fs.unlinkSync(csPath)
        if (fs.existsSync(userExe)) return userExe
      } catch (err) {
        console.warn('[CursorLock] Could not auto-compile cursor-lock.exe:', err.message)
      }
    }
  }
  return null
}

export function lockCursor(bounds, userDataDir) {
  unlockCursor(userDataDir)
  if (!isEnabled || !bounds) return

  const exe = getCursorLockExePath(userDataDir)
  if (!exe) {
    console.warn('[CursorLock] cursor-lock.exe not found')
    return
  }

  const left = Math.round(bounds.x)
  const top = Math.round(bounds.y)
  const right = Math.round(bounds.x + bounds.width)
  const bottom = Math.round(bounds.y + bounds.height)

  try {
    lockProcess = spawn(exe, [String(left), String(top), String(right), String(bottom)], {
      windowsHide: true,
      stdio: ['pipe', 'ignore', 'ignore'],
    })
    lockProcess.on('error', (err) => {
      console.warn('[CursorLock] Error running cursor-lock:', err.message)
      lockProcess = null
    })
  } catch (err) {
    console.warn('[CursorLock] Failed to spawn cursor-lock:', err.message)
  }
}

export function unlockCursor(userDataDir) {
  if (lockProcess) {
    try {
      lockProcess.stdin?.write('\n')
      lockProcess.kill()
    } catch (_) {}
    lockProcess = null
  }
  const exe = getCursorLockExePath(userDataDir)
  if (exe) {
    try {
      execFile(exe, [], { windowsHide: true, timeout: 2000 }, () => {})
    } catch (_) {}
  }
}

export function setCursorLockEnabled(enabled, currentBounds, userDataDir) {
  isEnabled = enabled
  if (enabled && currentBounds) {
    lockCursor(currentBounds, userDataDir)
  } else {
    unlockCursor(userDataDir)
  }
}

export function isCursorLockEnabled() {
  return isEnabled
}

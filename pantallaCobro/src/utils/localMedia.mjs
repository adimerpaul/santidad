export function localMediaFileName(fileId) {
  return fileId.replace(/[\\/]/g, '_')
}

export function localMediaUrl(fileId) {
  return 'localmedia://' + encodeURIComponent(localMediaFileName(fileId))
}

export function localMediaNameFromUrl(url) {
  if (!url.startsWith('localmedia://')) throw new Error('Invalid local media scheme')
  let raw = url.slice('localmedia://'.length)
  if (raw.endsWith('/')) raw = raw.slice(0, -1)
  const queryIdx = raw.indexOf('?')
  if (queryIdx !== -1) raw = raw.slice(0, queryIdx)
  const hashIdx = raw.indexOf('#')
  if (hashIdx !== -1) raw = raw.slice(0, hashIdx)
  const name = decodeURIComponent(raw)
  if (!name || name === '.' || name === '..' || /[\\/\0]/.test(name)) {
    throw new Error('Invalid local media filename')
  }
  return name
}

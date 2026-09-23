import path from 'node:path'
import { pathToFileURL } from 'node:url'
import { localMediaNameFromUrl } from '../src/utils/localMedia.mjs'

export function localMediaFileUrl(url, mediaDir) {
  return pathToFileURL(path.join(mediaDir, localMediaNameFromUrl(url))).href
}

import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const sourceDir = path.resolve(__dirname, '../dist')
const targetDir = path.resolve(__dirname, '../../backend/public')

if (fs.existsSync(sourceDir)) {
  fs.cpSync(sourceDir, targetDir, { recursive: true, force: true })
  console.log(`✓ Synchronized frontend SPA to ${targetDir}`)
} else {
  console.warn(`! Source directory ${sourceDir} does not exist.`)
}

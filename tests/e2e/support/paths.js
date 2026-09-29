import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'

/** Where the suite lives, and the app it drives. */
export const E2E_DIR = resolve(dirname(fileURLToPath(import.meta.url)), '..')
export const SDK_ROOT = resolve(E2E_DIR, '../..')

/** A Laravel app with Primix, the core, every builder and this SDK. */
export const SANDBOX_DIR = process.env.TAGIXO_PRIMIX_SANDBOX_DIR
    || resolve(SDK_ROOT, '../../Projects/tagixo-primix-sandbox')

export const BASE_URL = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8040'

export const STATE_DIR = resolve(E2E_DIR, '.state')
export const SEED_FILE = resolve(STATE_DIR, 'seed.json')
export const AUTH_FILE = resolve(STATE_DIR, 'auth.json')

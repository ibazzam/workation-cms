import { spawn } from 'node:child_process';
import { existsSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export const backendRoot = path.resolve(__dirname, '..', '..');
const compiledEntry = path.join(backendRoot, 'dist', 'main.js');
const configuredPort = process.env.CONTRACT_TEST_PORT;
const defaultPort = 30000 + (process.pid % 10000);
const parsedPort = Number(configuredPort ?? defaultPort);
const port = Number.isFinite(parsedPort) && parsedPort > 0 && parsedPort <= 65535 ? parsedPort : defaultPort;
export const baseUrl = `http://127.0.0.1:${port}/api/v1`;

function loadDotEnvIfPresent() {
  const envPath = path.join(backendRoot, '.env');
  if (!existsSync(envPath)) {
    return;
  }

  const raw = readFileSync(envPath, 'utf8');
  for (const line of raw.split(/\r?\n/)) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) {
      continue;
    }

    const separatorIndex = trimmed.indexOf('=');
    if (separatorIndex <= 0) {
      continue;
    }

    const key = trimmed.slice(0, separatorIndex).trim();
    let value = trimmed.slice(separatorIndex + 1).trim();
    if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
      value = value.slice(1, -1);
    }

    if (!process.env[key]) {
      process.env[key] = value;
    }
  }
}

loadDotEnvIfPresent();

if (!process.env.AUTH_JWT_SECRET) {
  process.env.AUTH_JWT_SECRET = 'dev-auth-secret';
}

if (!process.env.AUTH_ALLOW_HEADER_FALLBACK) {
  process.env.AUTH_ALLOW_HEADER_FALLBACK = 'true';
}

const hasDatabaseUrl = Boolean(process.env.DATABASE_URL);
export const canRun = hasDatabaseUrl && existsSync(compiledEntry);
export const skipReason = 'Set DATABASE_URL and build backend before running contract tests';

async function waitForHealth(timeoutMs = 15000) {
  const start = Date.now();

  while (Date.now() - start < timeoutMs) {
    try {
      const response = await fetch(`${baseUrl}/health`);
      if (response.ok) {
        return;
      }
    } catch {
      // retry until timeout
    }

    await new Promise((resolve) => setTimeout(resolve, 300));
  }

  throw new Error('Backend did not become healthy in time for contract tests');
}

export function registerBackendLifecycle(test) {
  let backendProcess;

  test.before(async () => {
    if (!canRun) {
      return;
    }

    backendProcess = spawn('node', ['dist/main.js'], {
      cwd: backendRoot,
      env: {
        ...process.env,
        PORT: String(port),
      },
      stdio: 'ignore',
    });

    await waitForHealth();
  });

  test.after(async () => {
    if (!backendProcess) {
      return;
    }

    await new Promise((resolve) => {
      let settled = false;
      const finish = () => {
        if (settled) {
          return;
        }

        settled = true;
        resolve();
      };

      backendProcess.once('exit', finish);
      backendProcess.kill('SIGTERM');

      setTimeout(() => {
        if (!settled) {
          backendProcess.kill('SIGKILL');
          finish();
        }
      }, 3000);
    });
  });
}

export function authHeaders(userId, role, email = `${userId}@example.test`, vendorId) {
  const headers = {
    'x-user-id': userId,
    'x-user-role': role,
    'x-user-email': email,
  };

  if (vendorId) {
    headers['x-vendor-id'] = vendorId;
  }

  return headers;
}

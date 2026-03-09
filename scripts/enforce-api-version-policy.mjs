import { readFile } from 'node:fs/promises';
import path from 'node:path';

const repoRoot = process.cwd();

const checks = [
  {
    file: 'infra/backend/src/main.ts',
    requires: ["app.setGlobalPrefix('api/v1')"],
  },
  {
    file: 'infra/frontend/lib/api/client.ts',
    forbidRegex: /['\"]\/api\/(?!v1\/)/g,
  },
  {
    file: 'tests/e2e/live-preflight.mjs',
    forbidRegex: /['\"]\/api\/(?!v1\/)/g,
  },
  {
    file: 'tests/perf/booking-payments-baseline.mjs',
    forbidRegex: /['\"]\/api\/(?!v1\/)/g,
  },
];

async function readText(relativePath) {
  const absolutePath = path.join(repoRoot, relativePath);
  return readFile(absolutePath, 'utf8');
}

async function run() {
  const failures = [];

  for (const check of checks) {
    const text = await readText(check.file);

    if (check.requires) {
      for (const snippet of check.requires) {
        if (!text.includes(snippet)) {
          failures.push(`${check.file}: missing required snippet: ${snippet}`);
        }
      }
    }

    if (check.forbidRegex) {
      const matches = text.match(check.forbidRegex) ?? [];
      if (matches.length > 0) {
        failures.push(`${check.file}: found non-versioned API paths: ${matches.slice(0, 5).join(', ')}`);
      }
    }
  }

  if (failures.length > 0) {
    console.error('API contract/version policy check failed:');
    for (const failure of failures) {
      console.error(`- ${failure}`);
    }
    process.exit(1);
  }

  console.log('API contract/version policy check passed.');
}

run().catch((error) => {
  console.error('API contract/version policy check crashed:', error);
  process.exit(1);
});

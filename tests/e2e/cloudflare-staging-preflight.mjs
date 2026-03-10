import { resolve4 } from 'node:dns/promises';

const baseUrl = process.env.BASE_URL ?? 'https://api.workation.mv';
const url = new URL(baseUrl);

function headerValue(headers, key) {
  return headers.get(key) ?? headers.get(key.toLowerCase()) ?? '';
}

async function checkDns() {
  const records = await resolve4(url.hostname);
  if (!Array.isArray(records) || records.length === 0) {
    throw new Error(`DNS check failed: no A records for ${url.hostname}`);
  }

  console.log(`DNS OK: ${url.hostname} -> ${records.join(', ')}`);
}

async function checkHealthThroughEdge() {
  const res = await fetch(`${baseUrl.replace(/\/$/, '')}/api/v1/health`, {
    method: 'GET',
    headers: { Accept: 'application/json' },
  });

  if (res.status !== 200) {
    throw new Error(`Health check failed with status ${res.status}`);
  }

  const server = headerValue(res.headers, 'server').toLowerCase();
  const cfRay = headerValue(res.headers, 'cf-ray');
  const cfCacheStatus = headerValue(res.headers, 'cf-cache-status');

  if (!server.includes('cloudflare')) {
    throw new Error(`Expected Cloudflare server header, got: ${server || '(empty)'}`);
  }

  if (!cfRay) {
    throw new Error('Missing cf-ray header on health response');
  }

  if (!cfCacheStatus) {
    throw new Error('Missing cf-cache-status header on health response');
  }

  console.log(`Edge health OK: server=${server}, cf-cache-status=${cfCacheStatus}, cf-ray=${cfRay}`);
}

async function checkProtectedWritePath() {
  const res = await fetch(`${baseUrl.replace(/\/$/, '')}/api/v1/bookings`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: '{}',
  });

  if (![401, 403].includes(res.status)) {
    throw new Error(`Expected 401/403 for unauthenticated write probe, got ${res.status}`);
  }

  const server = headerValue(res.headers, 'server').toLowerCase();
  const cfRay = headerValue(res.headers, 'cf-ray');
  const cfCacheStatus = headerValue(res.headers, 'cf-cache-status');

  if (!server.includes('cloudflare')) {
    throw new Error(`Expected Cloudflare server header on write probe, got: ${server || '(empty)'}`);
  }

  if (!cfRay) {
    throw new Error('Missing cf-ray header on write probe response');
  }

  if (!cfCacheStatus) {
    throw new Error('Missing cf-cache-status header on write probe response');
  }

  console.log(`Write-path auth/WAF edge OK: status=${res.status}, cf-cache-status=${cfCacheStatus}, cf-ray=${cfRay}`);
}

async function main() {
  console.log(`Running Cloudflare staging preflight against ${baseUrl}`);
  await checkDns();
  await checkHealthThroughEdge();
  await checkProtectedWritePath();
  console.log('Cloudflare staging preflight passed');
}

main().catch((error) => {
  console.error(`Cloudflare staging preflight failed: ${error.message}`);
  process.exit(1);
});

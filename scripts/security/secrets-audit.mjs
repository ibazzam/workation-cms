import { execSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

const patterns = [
  { name: 'aws_access_key', regex: /AKIA[0-9A-Z]{16}/g },
  { name: 'github_pat', regex: /github_pat_[A-Za-z0-9_]{20,}/g },
  { name: 'github_classic', regex: /ghp_[A-Za-z0-9]{36}/g },
  { name: 'stripe_live', regex: /sk_live_[A-Za-z0-9]{16,}/g },
  { name: 'private_key', regex: /BEGIN (RSA|EC|OPENSSH|PRIVATE) KEY/g },
];

const allowList = [
  'AUTH_JWT_SECRET=dev-auth-secret',
  'BML_WEBHOOK_SECRET=dev-bml-webhook-secret',
  'MIB_WEBHOOK_SECRET=dev-mib-webhook-secret',
  'STRIPE_WEBHOOK_SECRET=dev-webhook-secret',
];

const trackedFilesRaw = execSync('git ls-files', { encoding: 'utf8' });
const trackedFiles = trackedFilesRaw
  .split(/\r?\n/)
  .map((line) => line.trim())
  .filter((line) => line.length > 0)
  .filter((line) => !line.startsWith('vendor/'))
  .filter((line) => !line.startsWith('node_modules/'));

const findings = [];

for (const filePath of trackedFiles) {
  let content = '';
  try {
    content = readFileSync(filePath, 'utf8');
  } catch {
    continue;
  }

  const lines = content.split(/\r?\n/);
  for (let index = 0; index < lines.length; index += 1) {
    const line = lines[index];
    if (allowList.some((allowed) => line.includes(allowed))) {
      continue;
    }

    for (const pattern of patterns) {
      if (pattern.regex.test(line)) {
        findings.push({
          filePath,
          lineNumber: index + 1,
          pattern: pattern.name,
          line: line.trim(),
        });
      }
      pattern.regex.lastIndex = 0;
    }
  }
}

if (findings.length === 0) {
  console.log('Secrets audit passed: no high-risk patterns found in tracked files.');
  process.exit(0);
}

console.error('Secrets audit failed:');
for (const finding of findings) {
  console.error(`${finding.filePath}:${finding.lineNumber} [${finding.pattern}] ${finding.line}`);
}
process.exit(1);

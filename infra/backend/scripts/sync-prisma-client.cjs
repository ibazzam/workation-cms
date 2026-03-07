const fs = require('node:fs');
const path = require('node:path');

const dst = path.resolve(__dirname, '..', 'node_modules', '.prisma', 'client');

// Render can install dependencies under infra/backend/node_modules,
// while local workspace flows may have generated artifacts in repo-root node_modules.
const candidateSources = [
  path.resolve(__dirname, '..', 'node_modules', '.prisma', 'client'),
  path.resolve(__dirname, '..', '..', '..', 'node_modules', '.prisma', 'client'),
];

const src = candidateSources.find((candidate) => fs.existsSync(candidate));

if (!src) {
  console.error('Prisma client source not found in any expected location:');
  for (const candidate of candidateSources) {
    console.error(`- ${candidate}`);
  }
  process.exit(1);
}

if (path.normalize(src) === path.normalize(dst)) {
  console.log(`Prisma client already in place at ${dst}`);
  process.exit(0);
}

fs.mkdirSync(path.dirname(dst), { recursive: true });
fs.cpSync(src, dst, { recursive: true, force: true });

console.log(`Synced Prisma client from ${src} to ${dst}`);

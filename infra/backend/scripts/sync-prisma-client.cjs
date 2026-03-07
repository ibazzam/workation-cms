const fs = require('node:fs');
const path = require('node:path');

const src = path.resolve(__dirname, '..', '..', '..', 'node_modules', '.prisma', 'client');
const dst = path.resolve(__dirname, '..', 'node_modules', '.prisma', 'client');

if (!fs.existsSync(src)) {
  console.error(`Prisma client source not found: ${src}`);
  process.exit(1);
}

fs.mkdirSync(path.dirname(dst), { recursive: true });
fs.cpSync(src, dst, { recursive: true, force: true });

console.log(`Synced Prisma client from ${src} to ${dst}`);

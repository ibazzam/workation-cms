// compute_coverage_summary.cjs
// CommonJS version for projects with "type":"module" in package.json

const fs = require('fs');
const path = require('path');

const inPath = process.argv[2] || 'coverage-final.json';
const outPath = process.argv[3] || 'coverage-summary.json';
const resourceFilter = /resources[\\/]js[\\/]/i;

if (!fs.existsSync(inPath)) {
  console.error('Input coverage file not found:', inPath);
  process.exit(2);
}

const cov = JSON.parse(fs.readFileSync(inPath, 'utf8'));
let totals = { s: { t: 0, c: 0 }, f: { t: 0, c: 0 }, b: { t: 0, c: 0 } };

for (const k of Object.keys(cov)) {
  if (!resourceFilter.test(k)) continue;
  const file = cov[k] || {};
  const s = file.s || {};
  const f = file.f || {};
  const b = file.b || {};
  totals.s.t += Object.keys(s).length;
  totals.s.c += Object.values(s).filter(x => x > 0).length;
  totals.f.t += Object.keys(f).length;
  totals.f.c += Object.values(f).filter(x => x > 0).length;
  totals.b.t += Object.keys(b).reduce((acc, i) => acc + (Array.isArray(b[i]) ? b[i].length : 0), 0);
  totals.b.c += Object.keys(b).reduce((acc, i) => acc + (Array.isArray(b[i]) ? b[i].filter(x => x > 0).length : 0), 0);
}

function pct(ok, tot) { return tot === 0 ? 100 : Math.round((ok / tot) * 100); }

const summary = {
  totals,
  percent: {
    statements: pct(totals.s.c, totals.s.t),
    functions: pct(totals.f.c, totals.f.t),
    branches: pct(totals.b.c, totals.b.t)
  },
  generated_at: new Date().toISOString()
};

fs.writeFileSync(outPath, JSON.stringify(summary, null, 2));
console.log('Wrote', outPath, '->', JSON.stringify(summary));

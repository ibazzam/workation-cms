#!/usr/bin/env node
import fs from 'fs';
import path from 'path';

function findCoverageFile() {
  const candidates = [
    'coverage-final.json',
    path.join('coverage', 'coverage-final.json'),
    'coverage/coverage-final.json'
  ];
  for (const c of candidates) {
    if (fs.existsSync(c)) return c;
  }
  return null;
}

function collectTotals(cov) {
  const totals = { s: { t: 0, c: 0 }, f: { t: 0, c: 0 }, b: { t: 0, c: 0 } };
  for (const k of Object.keys(cov)) {
    if (!k.includes('/resources/js/') && !k.includes('resources\\js')) continue;
    const file = cov[k] || {};
    const s = file.s || {};
    const f = file.f || {};
    const b = file.b || {};
    totals.s.t += Object.keys(s).length;
    totals.s.c += Object.values(s).filter(x => x > 0).length;
    totals.f.t += Object.keys(f).length;
    totals.f.c += Object.values(f).filter(x => x > 0).length;
    totals.b.t += Object.keys(b).reduce((a, i) => a + (Array.isArray(b[i]) ? b[i].length : 0), 0);
    totals.b.c += Object.keys(b).reduce((a, i) => a + (Array.isArray(b[i]) ? b[i].filter(x => x > 0).length : 0), 0);
  }
  return totals;
}

function pct(n, m) { return m ? (100 * n / m) : 0; }

async function run() {
  const mode = process.argv[2] || 'summary'; // 'check' or 'summary'
  const p = findCoverageFile();
  if (!p) {
    if (mode === 'check') {
      console.error('No coverage-final.json found');
      process.exit(1);
    }
    console.log('no coverage json');
    process.exit(0);
  }

  let cov;
  try {
    cov = JSON.parse(fs.readFileSync(p, 'utf8'));
  } catch (err) {
    console.error('Failed to parse coverage JSON:', err && err.message);
    if (mode === 'check') process.exit(1);
    process.exit(0);
  }

  const totals = collectTotals(cov);
  const s = pct(totals.s.c, totals.s.t).toFixed(2);
  const f = pct(totals.f.c, totals.f.t).toFixed(2);
  const b = pct(totals.b.c, totals.b.t).toFixed(2);

  console.log(`Resource JS coverage: statements ${s}%, functions ${f}%, branches ${b}%`);

  if (mode === 'check') {
    const minS = 95, minF = 95, minB = 90;
    if (Number(s) < minS || Number(f) < minF || Number(b) < minB) {
      console.error('Resource JS coverage below thresholds');
      process.exit(2);
    }
    console.log('Coverage thresholds met for resources/js');
    process.exit(0);
  }

  // summary mode: write coverage-summary.json
  const summary = {
    summary: { statements: s, functions: f, branches: b },
    totals
  };
  fs.writeFileSync('coverage-summary.json', JSON.stringify(summary, null, 2));
  console.log('Wrote coverage-summary.json');
}

run();

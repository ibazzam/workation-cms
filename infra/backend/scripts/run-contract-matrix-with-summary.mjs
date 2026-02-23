import { spawn } from 'node:child_process';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';

const suiteOrder = [
  'workations.contract.test.mjs',
  'auth.contract.test.mjs',
  'auth.strict.contract.test.mjs',
  'users.contract.test.mjs',
  'countries.contract.test.mjs',
  'islands.contract.test.mjs',
  'service-categories.contract.test.mjs',
  'reviews.contract.test.mjs',
  'social-links.contract.test.mjs',
  'loyalty.contract.test.mjs',
  'vendors.contract.test.mjs',
  'accommodations.contract.test.mjs',
  'transports.contract.test.mjs',
  'cart.contract.test.mjs',
  'bookings.contract.test.mjs',
  'payments.contract.test.mjs',
  'admin-settings.contract.test.mjs',
  'admin-audit.contract.test.mjs',
  'permissions-matrix.contract.test.mjs',
  'feature-flags.contract.test.mjs'
];

function getLocalDateStamp(dateValue) {
  const year = dateValue.getFullYear();
  const month = String(dateValue.getMonth() + 1).padStart(2, '0');
  const day = String(dateValue.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function parseSuiteCounters(outputText) {
  const testCounts = [];
  const passCounts = [];
  const failCounts = [];

  for (const line of outputText.split(/\r?\n/)) {
    const testsMatch = line.match(/\btests\s+(\d+)\b/i);
    if (testsMatch) {
      testCounts.push(Number(testsMatch[1]));
    }

    const passMatch = line.match(/\bpass\s+(\d+)\b/i);
    if (passMatch) {
      passCounts.push(Number(passMatch[1]));
    }

    const failMatch = line.match(/\bfail\s+(\d+)\b/i);
    if (failMatch) {
      failCounts.push(Number(failMatch[1]));
    }
  }

  const rowCount = Math.min(suiteOrder.length, testCounts.length, passCounts.length, failCounts.length);
  const suites = [];
  for (let index = 0; index < rowCount; index += 1) {
    suites.push({
      suite: suiteOrder[index],
      tests: testCounts[index],
      pass: passCounts[index],
      fail: failCounts[index]
    });
  }

  return {
    suites,
    parseWarning:
      rowCount === suiteOrder.length
        ? null
        : `Expected ${suiteOrder.length} suite counters, parsed ${rowCount}`
  };
}

function sumByKey(items, key) {
  return items.reduce((total, item) => total + (item[key] ?? 0), 0);
}

async function main() {
  const startedAt = new Date();
  const dateStamp = getLocalDateStamp(startedAt);
  const logsDirectory = path.resolve(process.cwd(), 'test-logs');
  const logFileName = `contract-all-${dateStamp}.log`;
  const summaryMarkdownName = `contract-all-${dateStamp}.summary.md`;
  const summaryJsonName = `contract-all-${dateStamp}.summary.json`;
  const logPath = path.join(logsDirectory, logFileName);
  const summaryMarkdownPath = path.join(logsDirectory, summaryMarkdownName);
  const summaryJsonPath = path.join(logsDirectory, summaryJsonName);

  await mkdir(logsDirectory, { recursive: true });

  const command =
    process.platform === 'win32'
      ? {
          executable: 'cmd.exe',
          args: ['/d', '/s', '/c', 'npm run test:contract:all:raw']
        }
      : {
          executable: 'npm',
          args: ['run', 'test:contract:all:raw']
        };

  const childProcess = spawn(command.executable, command.args, {
    cwd: process.cwd(),
    stdio: ['ignore', 'pipe', 'pipe']
  });

  let capturedOutput = '';

  childProcess.stdout.on('data', (chunk) => {
    const textChunk = chunk.toString();
    capturedOutput += textChunk;
    process.stdout.write(textChunk);
  });

  childProcess.stderr.on('data', (chunk) => {
    const textChunk = chunk.toString();
    capturedOutput += textChunk;
    process.stderr.write(textChunk);
  });

  const exitCode = await new Promise((resolve, reject) => {
    childProcess.on('error', reject);
    childProcess.on('close', (code) => resolve(code ?? 1));
  });

  await writeFile(logPath, capturedOutput, 'utf8');

  const parsed = parseSuiteCounters(capturedOutput);
  const totalSuites = parsed.suites.length;
  const totalTests = sumByKey(parsed.suites, 'tests');
  const totalPass = sumByKey(parsed.suites, 'pass');
  const totalFail = sumByKey(parsed.suites, 'fail');
  const result = exitCode === 0 && totalFail === 0 ? 'PASS' : 'FAIL';

  const markdownLines = [
    '# Contract Matrix Summary',
    '',
    `- Generated: ${new Date().toISOString()}`,
    `- Source log: ${logFileName}`,
    `- Total suites: ${totalSuites}`,
    `- Total tests: ${totalTests}`,
    `- Total pass: ${totalPass}`,
    `- Total fail: ${totalFail}`,
    parsed.parseWarning ? `- Parse warning: ${parsed.parseWarning}` : null,
    '',
    '| Suite | Tests | Pass | Fail |',
    '|---|---:|---:|---:|',
    ...parsed.suites.map((suiteSummary) =>
      `| ${suiteSummary.suite} | ${suiteSummary.tests} | ${suiteSummary.pass} | ${suiteSummary.fail} |`
    ),
    '',
    `Overall result: ${result}`
  ].filter(Boolean);

  const jsonSummary = {
    generated: new Date().toISOString(),
    sourceLog: logFileName,
    totals: {
      suites: totalSuites,
      tests: totalTests,
      pass: totalPass,
      fail: totalFail,
      result
    },
    parseWarning: parsed.parseWarning,
    suites: parsed.suites
  };

  await writeFile(summaryMarkdownPath, `${markdownLines.join('\n')}\n`, 'utf8');
  await writeFile(summaryJsonPath, `${JSON.stringify(jsonSummary, null, 2)}\n`, 'utf8');

  console.log(`CONTRACT_LOG_PATH=${path.relative(process.cwd(), logPath).replace(/\\/g, '/')}`);
  console.log(
    `CONTRACT_SUMMARY_MD_PATH=${path.relative(process.cwd(), summaryMarkdownPath).replace(/\\/g, '/')}`
  );
  console.log(
    `CONTRACT_SUMMARY_JSON_PATH=${path.relative(process.cwd(), summaryJsonPath).replace(/\\/g, '/')}`
  );
  console.log(`CONTRACT_TOTAL_TESTS=${totalTests}`);
  console.log(`CONTRACT_TOTAL_FAIL=${totalFail}`);

  process.exit(exitCode);
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
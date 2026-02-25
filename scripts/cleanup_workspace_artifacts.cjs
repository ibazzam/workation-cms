#!/usr/bin/env node
const fs = require('node:fs/promises');
const path = require('node:path');

function parseArgs(argv) {
  const options = {
    execute: false,
    testLogsDays: 7,
    tempDirsDays: 3,
    laravelLogsDays: 14,
  };

  for (const arg of argv) {
    if (arg === '--execute') {
      options.execute = true;
      continue;
    }

    const [key, raw] = arg.split('=');
    if (!raw) continue;

    const value = Number(raw);
    if (!Number.isFinite(value) || value < 0) continue;

    if (key === '--test-logs-days') options.testLogsDays = value;
    if (key === '--temp-dirs-days') options.tempDirsDays = value;
    if (key === '--laravel-logs-days') options.laravelLogsDays = value;
  }

  return options;
}

function isOlderThan(stat, days) {
  if (days <= 0) return true;
  const cutoff = Date.now() - days * 24 * 60 * 60 * 1000;
  return stat.mtimeMs < cutoff;
}

async function pathExists(targetPath) {
  try {
    await fs.access(targetPath);
    return true;
  } catch {
    return false;
  }
}

async function collectFiles(rootDir, matcher, output = []) {
  const entries = await fs.readdir(rootDir, { withFileTypes: true });
  for (const entry of entries) {
    const fullPath = path.join(rootDir, entry.name);
    if (entry.isDirectory()) {
      await collectFiles(fullPath, matcher, output);
      continue;
    }

    if (matcher(fullPath, entry.name)) {
      output.push(fullPath);
    }
  }

  return output;
}

async function maybeDelete(targetPath, execute, deleted) {
  if (execute) {
    await fs.rm(targetPath, { recursive: true, force: true });
  }

  deleted.push(targetPath);
}

async function cleanupTestLogs(repoRoot, options, deleted) {
  const logsDir = path.join(repoRoot, 'infra', 'backend', 'test-logs');
  if (!(await pathExists(logsDir))) return;

  const candidates = await collectFiles(
    logsDir,
    (_fullPath, fileName) =>
      fileName.endsWith('.log') || fileName.endsWith('.summary.json') || fileName.endsWith('.summary.md'),
  );

  for (const filePath of candidates) {
    const stat = await fs.stat(filePath);
    if (!isOlderThan(stat, options.testLogsDays)) continue;
    await maybeDelete(filePath, options.execute, deleted);
  }
}

async function cleanupLaravelLogs(repoRoot, options, deleted) {
  const logsDir = path.join(repoRoot, 'storage', 'logs');
  if (!(await pathExists(logsDir))) return;

  const candidates = await collectFiles(logsDir, (_fullPath, fileName) => fileName.endsWith('.log'));
  for (const filePath of candidates) {
    const stat = await fs.stat(filePath);
    if (!isOlderThan(stat, options.laravelLogsDays)) continue;
    await maybeDelete(filePath, options.execute, deleted);
  }
}

async function cleanupTempDirs(repoRoot, options, deleted) {
  const entries = await fs.readdir(repoRoot, { withFileTypes: true });
  const dirMatchers = [
    /^artifacts_run_/i,
    /^run-\d+-logs$/i,
    /^actions-logs-$/i,
    /^tmp-job-log$/i,
  ];

  for (const entry of entries) {
    if (!entry.isDirectory()) continue;
    if (!dirMatchers.some((matcher) => matcher.test(entry.name))) continue;

    const fullPath = path.join(repoRoot, entry.name);
    const stat = await fs.stat(fullPath);
    if (!isOlderThan(stat, options.tempDirsDays)) continue;

    await maybeDelete(fullPath, options.execute, deleted);
  }
}

async function main() {
  const options = parseArgs(process.argv.slice(2));
  const repoRoot = process.cwd();
  const deleted = [];

  await cleanupTestLogs(repoRoot, options, deleted);
  await cleanupLaravelLogs(repoRoot, options, deleted);
  await cleanupTempDirs(repoRoot, options, deleted);

  const mode = options.execute ? 'EXECUTE' : 'DRY-RUN';
  console.log(`[cleanup] mode=${mode}`);
  console.log(`[cleanup] testLogsDays=${options.testLogsDays} tempDirsDays=${options.tempDirsDays} laravelLogsDays=${options.laravelLogsDays}`);

  if (deleted.length === 0) {
    console.log('[cleanup] nothing to remove');
    return;
  }

  console.log(`[cleanup] ${options.execute ? 'removed' : 'would remove'} ${deleted.length} path(s):`);
  for (const item of deleted) {
    console.log(`- ${path.relative(repoRoot, item).replace(/\\/g, '/')}`);
  }
}

main().catch((error) => {
  console.error('[cleanup] failed:', error);
  process.exit(1);
});

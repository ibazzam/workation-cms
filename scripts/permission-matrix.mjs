import { readdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';

const repoRoot = process.cwd();
const controllersRoot = path.join(repoRoot, 'infra', 'backend', 'src');
const markdownPath = path.join(repoRoot, 'docs', 'auth-permission-matrix.md');
const jsonPath = path.join(repoRoot, 'docs', 'auth-permission-matrix.json');

const ROLE_COLUMNS = [
  'ANONYMOUS',
  'USER',
  'VENDOR',
  'ADMIN',
  'ADMIN_SUPER',
  'ADMIN_CARE',
  'ADMIN_FINANCE',
];

async function listControllerFiles(dirPath) {
  const entries = await readdir(dirPath, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const absolutePath = path.join(dirPath, entry.name);
    if (entry.isDirectory()) {
      files.push(...(await listControllerFiles(absolutePath)));
      continue;
    }

    if (entry.isFile() && entry.name.endsWith('.controller.ts')) {
      files.push(absolutePath);
    }
  }

  return files;
}

function stripQuotes(value) {
  const trimmed = value.trim();
  if (trimmed.length === 0) {
    return '';
  }

  const match = /^['"`]([^'"`]*)['"`]$/.exec(trimmed);
  return match ? match[1] : trimmed;
}

function normalizePathSegment(segment) {
  if (!segment || segment === '/') {
    return '';
  }

  return segment.replace(/^\/+/, '').replace(/\/+$/, '');
}

function buildApiPath(controllerPath, methodPath) {
  const segments = ['api', 'v1'];
  const normalizedController = normalizePathSegment(controllerPath);
  const normalizedMethod = normalizePathSegment(methodPath);

  if (normalizedController) {
    segments.push(normalizedController);
  }

  if (normalizedMethod) {
    segments.push(normalizedMethod);
  }

  return `/${segments.join('/')}`;
}

function parseRolePolicy(decoratorBlock) {
  const rolesMatch = /@Roles\(([^)]*)\)/.exec(decoratorBlock);
  const roles = [];

  if (rolesMatch) {
    const roleRegex = /['"]([^'"]+)['"]/g;
    let roleMatch;
    while ((roleMatch = roleRegex.exec(rolesMatch[1])) !== null) {
      roles.push(roleMatch[1]);
    }
  }

  const isPublic = /@Public\(/.test(decoratorBlock);

  if (isPublic) {
    return {
      mode: 'public',
      roles,
    };
  }

  if (roles.length > 0) {
    return {
      mode: 'roles',
      roles: Array.from(new Set(roles)).sort(),
    };
  }

  return {
    mode: 'authenticated',
    roles: [],
  };
}

function roleAllowedForEndpoint(endpoint, role) {
  if (endpoint.access.mode === 'public') {
    return true;
  }

  if (role === 'ANONYMOUS') {
    return false;
  }

  if (endpoint.access.mode === 'authenticated') {
    return true;
  }

  return endpoint.access.roles.includes(role);
}

function parseEndpoints(fileContent, controllerPath, sourceFile) {
  const endpoints = [];
  const blockRegex = /((?:\s*@\w+(?:\([^\n]*\))?\s*\n)+)\s*(?:async\s+)?\w+\s*\(/g;
  let blockMatch;

  while ((blockMatch = blockRegex.exec(fileContent)) !== null) {
    const decorators = blockMatch[1];
    const routeMatch = /@(Get|Post|Put|Patch|Delete)\(([^)]*)\)/.exec(decorators);
    if (!routeMatch) {
      continue;
    }

    const method = routeMatch[1].toUpperCase();
    const methodPath = stripQuotes(routeMatch[2]);
    const access = parseRolePolicy(decorators);

    endpoints.push({
      method,
      path: buildApiPath(controllerPath, methodPath),
      access,
      source: sourceFile,
    });
  }

  return endpoints;
}

async function buildPermissionMatrix() {
  const files = (await listControllerFiles(controllersRoot)).sort();
  const endpoints = [];

  for (const absolutePath of files) {
    const relativePath = path.relative(repoRoot, absolutePath).replace(/\\/g, '/');
    const content = await readFile(absolutePath, 'utf8');

    const controllerMatch = /@Controller\((['"`])([^'"`]*)\1\)/.exec(content);
    const controllerPath = controllerMatch ? controllerMatch[2] : '';
    const parsed = parseEndpoints(content, controllerPath, relativePath);
    endpoints.push(...parsed);
  }

  endpoints.sort((a, b) => {
    if (a.path !== b.path) {
      return a.path.localeCompare(b.path);
    }

    if (a.method !== b.method) {
      return a.method.localeCompare(b.method);
    }

    return a.source.localeCompare(b.source);
  });

  const matrix = endpoints.map((endpoint) => {
    const roles = Object.fromEntries(ROLE_COLUMNS.map((role) => [role, roleAllowedForEndpoint(endpoint, role)]));

    return {
      method: endpoint.method,
      path: endpoint.path,
      accessMode: endpoint.access.mode,
      allowedRoles: endpoint.access.roles,
      source: endpoint.source,
      roles,
    };
  });

  return {
    roles: ROLE_COLUMNS,
    totalEndpoints: matrix.length,
    endpoints: matrix,
  };
}

function renderMarkdown(matrix) {
  const lines = [];
  lines.push('# Auth Permission Matrix');
  lines.push('');
  lines.push('This document is generated from backend controller decorators in `infra/backend/src/**/*.controller.ts`.');
  lines.push('');
  lines.push('Generation command: `npm run permissions:matrix:write`');
  lines.push('Validation command: `npm run permissions:matrix:check`');
  lines.push('');
  lines.push(`Total endpoint policies: **${matrix.totalEndpoints}**`);
  lines.push('');
  lines.push('Role columns: `ANONYMOUS`, `USER`, `VENDOR`, `ADMIN`, `ADMIN_SUPER`, `ADMIN_CARE`, `ADMIN_FINANCE`');
  lines.push('');
  lines.push('| Method | Path | Access Mode | Allowed Roles | ANONYMOUS | USER | VENDOR | ADMIN | ADMIN_SUPER | ADMIN_CARE | ADMIN_FINANCE | Source |');
  lines.push('|---|---|---|---|---|---|---|---|---|---|---|---|');

  for (const endpoint of matrix.endpoints) {
    lines.push([
      endpoint.method,
      endpoint.path,
      endpoint.accessMode,
      endpoint.allowedRoles.length > 0 ? endpoint.allowedRoles.join(', ') : '-',
      endpoint.roles.ANONYMOUS ? 'Y' : 'N',
      endpoint.roles.USER ? 'Y' : 'N',
      endpoint.roles.VENDOR ? 'Y' : 'N',
      endpoint.roles.ADMIN ? 'Y' : 'N',
      endpoint.roles.ADMIN_SUPER ? 'Y' : 'N',
      endpoint.roles.ADMIN_CARE ? 'Y' : 'N',
      endpoint.roles.ADMIN_FINANCE ? 'Y' : 'N',
      endpoint.source,
    ].map((cell) => String(cell).replace(/\|/g, '\\|')).join(' | ').replace(/^/, '| ').replace(/$/, ' |'));
  }

  lines.push('');
  return `${lines.join('\n')}\n`;
}

async function run() {
  const mode = process.argv.includes('--write') ? 'write' : 'check';
  const matrix = await buildPermissionMatrix();
  const markdown = renderMarkdown(matrix);
  const json = `${JSON.stringify(matrix, null, 2)}\n`;

  if (mode === 'write') {
    await writeFile(markdownPath, markdown, 'utf8');
    await writeFile(jsonPath, json, 'utf8');
    console.log('Permission matrix artifacts written.');
    return;
  }

  const existingMarkdown = await readFile(markdownPath, 'utf8');
  const existingJson = await readFile(jsonPath, 'utf8');

  const failures = [];
  if (existingMarkdown !== markdown) {
    failures.push('docs/auth-permission-matrix.md is out of date. Run: npm run permissions:matrix:write');
  }
  if (existingJson !== json) {
    failures.push('docs/auth-permission-matrix.json is out of date. Run: npm run permissions:matrix:write');
  }

  if (failures.length > 0) {
    console.error('Permission matrix check failed:');
    for (const failure of failures) {
      console.error(`- ${failure}`);
    }
    process.exit(1);
  }

  console.log('Permission matrix check passed.');
}

run().catch((error) => {
  console.error('Permission matrix script failed:', error);
  process.exit(1);
});

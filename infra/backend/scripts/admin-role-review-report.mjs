import { existsSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { PrismaClient } from '@prisma/client';

const ADMIN_ROLES = new Set(['ADMIN', 'ADMIN_SUPER', 'ADMIN_FINANCE', 'ADMIN_CARE']);
const DEFAULT_METADATA_PATH = path.resolve(process.cwd(), 'docs', 'admin-role-assignments.json');

function isLikelyTestUser(email) {
  const normalized = String(email ?? '').trim().toLowerCase();
  if (!normalized) {
    return false;
  }

  return normalized.endsWith('@example.test') || normalized.startsWith('contract-') || normalized.includes('test');
}

function toIsoDateOnly(value) {
  if (!value) {
    return null;
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return null;
  }

  return parsed.toISOString().slice(0, 10);
}

function daysUntil(dateValue) {
  const now = new Date();
  now.setHours(0, 0, 0, 0);

  const target = new Date(dateValue);
  target.setHours(0, 0, 0, 0);

  const diff = target.getTime() - now.getTime();
  return Math.round(diff / (24 * 60 * 60 * 1000));
}

function loadMetadata(filePath) {
  if (!existsSync(filePath)) {
    return { records: [], fileFound: false };
  }

  const raw = readFileSync(filePath, 'utf8');
  if (!raw.trim()) {
    return { records: [], fileFound: true };
  }

  const parsed = JSON.parse(raw);
  if (!Array.isArray(parsed)) {
    throw new Error(`Metadata file must be a JSON array: ${filePath}`);
  }

  return { records: parsed, fileFound: true };
}

function printHeader(title) {
  console.log('');
  console.log(title);
  console.log('-'.repeat(title.length));
}

function printUsers(users) {
  if (users.length === 0) {
    console.log('No admin users found.');
    return;
  }

  for (const user of users) {
    console.log(`${user.role.padEnd(14)}  ${String(user.email).padEnd(42)}  ${user.id}`);
  }
}

async function main() {
  const prisma = new PrismaClient();
  const metadataPath = process.env.ADMIN_ROLE_METADATA_PATH
    ? path.resolve(process.cwd(), process.env.ADMIN_ROLE_METADATA_PATH)
    : DEFAULT_METADATA_PATH;
  const failOnIssues = String(process.env.ADMIN_ROLE_REVIEW_FAIL_ON_ISSUES ?? 'false').toLowerCase() === 'true';
  const includeTestUsers = String(process.env.ADMIN_ROLE_REVIEW_INCLUDE_TEST_USERS ?? 'false').toLowerCase() === 'true';

  try {
    const dbUsers = await prisma.user.findMany({
      where: {
        role: {
          in: Array.from(ADMIN_ROLES),
        },
      },
      orderBy: [{ role: 'asc' }, { email: 'asc' }],
      select: {
        id: true,
        email: true,
        role: true,
        createdAt: true,
      },
    });

    const filteredUsers = includeTestUsers
      ? dbUsers
      : dbUsers.filter((user) => !isLikelyTestUser(user.email));

    const groupedCounts = filteredUsers.reduce((acc, user) => {
      acc[user.role] = (acc[user.role] ?? 0) + 1;
      return acc;
    }, {});

    const { records, fileFound } = loadMetadata(metadataPath);

    const metadataByEmail = new Map();
    for (const record of records) {
      const email = String(record?.email ?? '').trim().toLowerCase();
      if (!email) {
        continue;
      }
      metadataByEmail.set(email, record);
    }

    const dbEmails = new Set(filteredUsers.map((user) => user.email.trim().toLowerCase()));
    const issues = [];

    for (const user of filteredUsers) {
      const emailKey = user.email.trim().toLowerCase();
      const metadata = metadataByEmail.get(emailKey);

      if (!metadata) {
        issues.push(`[MISSING_METADATA] ${user.email} (${user.role}) has no metadata entry.`);
        continue;
      }

      const metadataRole = String(metadata.role ?? '').trim();
      if (metadataRole && metadataRole !== user.role) {
        issues.push(`[ROLE_MISMATCH] ${user.email} DB role=${user.role} metadata role=${metadataRole}.`);
      }

      const accessType = String(metadata.accessType ?? '').trim().toUpperCase();
      const endDate = toIsoDateOnly(metadata.endDate);
      const reviewDate = toIsoDateOnly(metadata.nextReviewDate);

      if (accessType === 'TEMPORARY') {
        if (!endDate) {
          issues.push(`[TEMP_MISSING_END_DATE] ${user.email} is TEMPORARY but has no valid endDate.`);
        } else if (daysUntil(endDate) < 0) {
          issues.push(`[TEMP_EXPIRED] ${user.email} temporary access expired on ${endDate}.`);
        }
      }

      if (!reviewDate) {
        issues.push(`[MISSING_REVIEW_DATE] ${user.email} has no valid nextReviewDate.`);
      } else if (daysUntil(reviewDate) < 0) {
        issues.push(`[REVIEW_OVERDUE] ${user.email} review date ${reviewDate} is overdue.`);
      }
    }

    for (const record of records) {
      const email = String(record?.email ?? '').trim().toLowerCase();
      if (!email) {
        continue;
      }

      if (!includeTestUsers && isLikelyTestUser(email)) {
        continue;
      }

      if (!dbEmails.has(email)) {
        issues.push(`[METADATA_ONLY] ${record.email} exists in metadata but not in current admin users.`);
      }
    }

    console.log('Admin Role Review Report');
    console.log('========================');
    console.log(`Generated at: ${new Date().toISOString()}`);
    console.log(`Metadata file: ${metadataPath} ${fileFound ? '(found)' : '(missing - optional)'}`);
    console.log(`Admin users total (after filter): ${filteredUsers.length}`);
    if (!includeTestUsers) {
      console.log('Test-like users filtered: true (set ADMIN_ROLE_REVIEW_INCLUDE_TEST_USERS=true to include)');
    }

    printHeader('Counts By Role');
    for (const role of Array.from(ADMIN_ROLES)) {
      console.log(`${role.padEnd(14)} ${groupedCounts[role] ?? 0}`);
    }

    printHeader('Admin Users');
    printUsers(filteredUsers);

    printHeader('Review Issues');
    if (issues.length === 0) {
      console.log('No issues found.');
    } else {
      for (const issue of issues) {
        console.log(`- ${issue}`);
      }
    }

    if (issues.length > 0 && failOnIssues) {
      process.exitCode = 2;
    }
  } finally {
    await prisma.$disconnect();
  }
}

main().catch((error) => {
  console.error('Failed to run admin role review report:', error?.message ?? error);
  process.exitCode = 1;
});

const crypto = import crypto from 'crypto';

const secret = process.env.JWT_SECRET;
const now = Math.floor(Date.now() / 1000);

const header = { alg: 'HS256', typ: 'JWT' };
const payload = {
  sub: 'launch-admin', // must match a real admin user in your DB
  role: 'ADMIN_SUPER',
  email: 'admin@workation.mv',
  iat: now,
  exp: now + 3600 // 1 hour expiry
};

function base64url(obj) {
  return Buffer.from(JSON.stringify(obj)).toString('base64url');
}

const h = base64url(header);
const p = base64url(payload);
const sig = crypto.createHmac('sha256', secret).update(`${h}.${p}`).digest('base64url');
process.stdout.write(`${h}.${p}.${sig}`);

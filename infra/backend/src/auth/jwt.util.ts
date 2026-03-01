import { createHmac, timingSafeEqual } from 'crypto';

type JwtClaims = {
  sub?: unknown;
  userId?: unknown;
  id?: unknown;
  email?: unknown;
  role?: unknown;
  exp?: unknown;
  nbf?: unknown;
  iat?: unknown;
  [key: string]: unknown;
};

function toBase64Url(input: Buffer | string): string {
  const raw = Buffer.isBuffer(input) ? input.toString('base64') : Buffer.from(input).toString('base64');
  return raw.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
}

function fromBase64Url(value: string): Buffer {
  const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
  const padding = normalized.length % 4;
  const withPadding = padding === 0 ? normalized : normalized + '='.repeat(4 - padding);
  return Buffer.from(withPadding, 'base64');
}

export function parseBearerToken(value: string | undefined): string | null {
  if (!value) {
    return null;
  }

  const trimmed = value.trim();
  if (!trimmed.toLowerCase().startsWith('bearer ')) {
    return null;
  }

  const token = trimmed.slice(7).trim();
  return token.length > 0 ? token : null;
}

export function verifyHs256Jwt(token: string, secret: string): JwtClaims {
  const parts = token.split('.');
  if (parts.length !== 3) {
    throw new Error('Malformed JWT');
  }

  const [headerPart, payloadPart, signaturePart] = parts;
  const signingInput = `${headerPart}.${payloadPart}`;

  let header: { alg?: unknown; typ?: unknown };
  let payload: JwtClaims;

  try {
    header = JSON.parse(fromBase64Url(headerPart).toString('utf8')) as { alg?: unknown; typ?: unknown };
    payload = JSON.parse(fromBase64Url(payloadPart).toString('utf8')) as JwtClaims;
  } catch {
    throw new Error('Invalid JWT encoding');
  }

  if (header.alg !== 'HS256') {
    throw new Error('Unsupported JWT algorithm');
  }

  const expectedSignature = toBase64Url(
    createHmac('sha256', secret).update(signingInput).digest(),
  );

  const expectedBuffer = Buffer.from(expectedSignature, 'utf8');
  const receivedBuffer = Buffer.from(signaturePart, 'utf8');

  if (expectedBuffer.length !== receivedBuffer.length || !timingSafeEqual(expectedBuffer, receivedBuffer)) {
    throw new Error('Invalid JWT signature');
  }

  const nowSeconds = Math.floor(Date.now() / 1000);

  if (typeof payload.nbf === 'number' && nowSeconds < payload.nbf) {
    throw new Error('JWT not active yet');
  }

  if (typeof payload.exp === 'number' && nowSeconds >= payload.exp) {
    throw new Error('JWT expired');
  }

  return payload;
}

import { Decimal } from '@prisma/client/runtime/library';

type DecimalLike = Decimal | number | string | null | undefined;

function toNumber(value: DecimalLike): number {
  if (value === null || value === undefined) return 0;
  if (typeof value === 'number') return value;
  if (typeof value === 'string') return Number(value);
  return value.toNumber();
}

export function mapWorkation(record: {
  id: number;
  title: string;
  description: string | null;
  location: string;
  startDate: Date;
  endDate: Date;
  price: DecimalLike;
  createdAt: Date;
  updatedAt: Date;
}) {
  return {
    id: record.id,
    title: record.title,
    description: record.description,
    location: record.location,
    start_date: record.startDate.toISOString(),
    end_date: record.endDate.toISOString(),
    price: toNumber(record.price),
    created_at: record.createdAt.toISOString(),
    updated_at: record.updatedAt.toISOString(),
  };
}

export function mapHold(record: {
  id: number;
  scheduleId: number;
  seatClass: string;
  seatsReserved: number;
  status: string;
  ttlExpiresAt: Date;
  idempotencyKey: string | null;
  createdAt: Date;
  updatedAt: Date;
}) {
  return {
    id: record.id,
    schedule_id: record.scheduleId,
    seat_class: record.seatClass,
    seats_reserved: record.seatsReserved,
    status: record.status,
    ttl_expires_at: record.ttlExpiresAt.toISOString(),
    idempotency_key: record.idempotencyKey,
    created_at: record.createdAt.toISOString(),
    updated_at: record.updatedAt.toISOString(),
  };
}

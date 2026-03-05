export default async function transportHoldTest(client) {
  // Minimal flow: create a hold, assert 201 and returned hold object
  console.log('E2E: creating a hold');
  const payload = {
    schedule_id: 1,
    seat_class: 'standard',
    seats: 1,
    idempotency_key: `e2e-${Date.now()}`,
    ttl_seconds: 60
  };

  const res = await client.post('/api/transport/holds', payload).catch((err) => {
    if (err.response) throw new Error(`HTTP ${err.response.status}: ${JSON.stringify(err.response.data)}`);
    throw err;
  });

  if (res.status !== 201) throw new Error('unexpected status ' + res.status);
  const hold = res.data?.hold;
  if (!hold || !hold.id) throw new Error('no hold returned');

  console.log('E2E: created hold', hold.id);

  // confirm hold
  console.log('E2E: confirming hold');
  const conf = await client.post(`/api/transport/holds/${hold.id}/confirm`).catch((err) => {
    if (err.response) throw new Error(`HTTP ${err.response.status}: ${JSON.stringify(err.response.data)}`);
    throw err;
  });
  if (conf.status !== 200) throw new Error('confirm failed: ' + conf.status);

  // release hold
  console.log('E2E: releasing hold');
  const rel = await client.post(`/api/transport/holds/${hold.id}/release`).catch((err) => {
    if (err.response) throw new Error(`HTTP ${err.response.status}: ${JSON.stringify(err.response.data)}`);
    throw err;
  });
  if (rel.status !== 200) throw new Error('release failed: ' + rel.status);

  console.log('E2E: transport hold flow OK');
}

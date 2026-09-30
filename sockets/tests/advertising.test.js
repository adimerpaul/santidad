const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { Server } = require('socket.io');
const { io: connect } = require('../../pantallaCobro/node_modules/socket.io-client');
const { attachAdvertising } = require('../advertising');
const state = (agencia_id, revision = 1) => ({ protocol: 2, agencia_id, revision, version: 'same-files', ready: true, epoch_ms: Date.now() + 5000,
  items: [{ id: 1, file_id: 'x.mp4', media_version: 'abc', url: 'https://example.com/x.mp4', type: 'video', duration_ms: 30000, agencia_id }] });
const ack = (s, event, data) => new Promise((resolve, reject) => s.timeout(1000).emit(event, data, (err, response) => err ? reject(err) : resolve(response)));
const once = (s, event) => new Promise(resolve => s.once(event, resolve));

test('branch delivery, time from memory, revision replay and persistent restart', async () => {
  const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'ad-hub-'));
  const io = new Server(0);
  const hub = attachAdvertising(io, { directory, retryMs: 20 });
  if (!io.httpServer.listening) await once(io.httpServer, 'listening');
  const url = 'http://127.0.0.1:' + io.httpServer.address().port;
  const a = connect(url, { transports: ['websocket'] });
  const b = connect(url, { transports: ['websocket'] });
  try {
    await Promise.all([once(a, 'connect'), once(b, 'connect')]);
    assert.equal((await ack(a, 'ad_register', { agencia_id: 1, revision: 0 })).needs_snapshot, true);
    await ack(b, 'ad_register', { agencia_id: 2, revision: 0 });
    let otherReceived = false; b.on('ad_schedule', () => { otherReceived = true; });
    const received = once(a, 'ad_schedule'); hub.publish(state(1));
    assert.equal((await received).revision, 1);
    a.emit('ad_received', { revision: 1 });
    const clock = await ack(a, 'ad_clock', { revision: 1 });
    assert.equal(clock.schedule, null); assert.ok(Math.abs(clock.server_time_ms - Date.now()) < 1000);
    assert.equal(otherReceived, false);
    const replay = once(a, 'ad_schedule'); hub.publish(state(1, 2));
    assert.equal((await replay).revision, 2); // Play again works with identical media.
    hub.publish(state(1, 1)); // Out-of-order HTTP retries cannot roll back.
    assert.equal((await ack(a, 'ad_clock', { revision: 1 })).schedule.revision, 2);
    a.disconnect(); a.connect(); await once(a, 'connect');
    assert.equal((await ack(a, 'ad_register', { agencia_id: 1, revision: 1 })).schedule.revision, 2);
    hub.close();
    const restored = attachAdvertising(new Server(), { directory });
    assert.equal(JSON.parse(fs.readFileSync(path.join(directory, '1.json'))).revision, 2);
    restored.close();
  } finally {
    a.disconnect(); b.disconnect(); hub.close(); await io.close();
    for (const f of fs.readdirSync(directory)) fs.unlinkSync(path.join(directory, f));
    fs.rmdirSync(directory);
  }
});


test('advertising subscription never changes QR/data isolation by caja', async () => {
  const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'qr-ad-'));
  process.env.AD_STATE_DIR = directory;
  const { server, io, advertising } = require('../index');
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const url = 'http://127.0.0.1:' + server.address().port;
  const clients = [connect(url), connect(url), connect(url)];
  const seen = [[], [], []];
  try {
    await Promise.all(clients.map(c => once(c, 'connect')));
    for (let i = 0; i < clients.length; i++) {
      await ack(clients[i], 'register_terminal', { agencia_id: i < 2 ? 1 : 2, caja: i === 1 ? 2 : 1 });
      await ack(clients[i], 'ad_register', { agencia_id: i < 2 ? 1 : 2 });
      clients[i].onAny((event, data) => seen[i].push({ event, data }));
    }
    for (const event of ['clienteQrData', 'clienteDisplayData', 'clienteDisplayClose', 'clienteSaleComplete']) {
      const delivery = once(clients[0], event);
      const response = await fetch(url + '/notify', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ event, data: { agencia_id: 1, caja: 1, visible: true } }) });
      assert.equal(response.status, 200); await delivery;
    }
    const invalid = await fetch(url + '/notify', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ event: 'clienteQrData', data: { agencia_id: 1 } }) });
    assert.equal(invalid.status, 422);
    assert.equal(seen[1].length, 0); assert.equal(seen[2].length, 0);
    const deliveries = [once(clients[0], 'ad_schedule'), once(clients[1], 'ad_schedule')];
    await fetch(url + '/notify', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ event: 'ad_schedule', data: state(1) }) });
    await Promise.all(deliveries);
    assert.equal(seen[2].length, 0);
  } finally {
    for (const c of clients) c.disconnect(); advertising.close(); await io.close();
    for (const f of fs.readdirSync(directory)) fs.unlinkSync(path.join(directory, f));
    fs.rmdirSync(directory);
  }
});

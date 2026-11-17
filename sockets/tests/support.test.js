const assert = require('node:assert/strict');
const { once } = require('node:events');
const { createHmac } = require('node:crypto');
const test = require('node:test');
const express = require('express');
const { createServer } = require('node:http');
const { Server } = require('socket.io');
const { Manager } = require('../../front/node_modules/socket.io-client');
const attachSupport = require('../support');

test('support uses the shared connection and delivers only to authorized rooms', { timeout: 5000 }, async () => {
  const secret = 'integration-secret';
  const app = express();
  app.use(express.json());
  const server = createServer(app);
  const io = new Server(server);
  attachSupport(app, io, secret);
  server.listen(0, '127.0.0.1');
  await once(server, 'listening');
  const url = 'http://127.0.0.1:' + server.address().port;
  const sockets = [];
  const ticket = room => {
    const body = Buffer.from(JSON.stringify({ room, exp: Math.floor(Date.now() / 1000) + 120 })).toString('base64url');
    return body + '.' + createHmac('sha256', secret).update('support-ticket:' + body).digest('hex');
  };
  function manager() {
    return new Manager(url, { autoConnect: false, reconnection: false, transports: ['websocket'] });
  }
  function open(client, namespace, auth, expected = 'connect') {
    const socket = client.socket(namespace, { auth });
    sockets.push(socket);
    const event = once(socket, expected);
    socket.connect();
    return event.then(() => socket);
  }
  function post(cases, key) {
    return fetch(url + '/support/notify', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: 'Bearer ' + key },
      body: JSON.stringify({ cases }),
    });
  }
  try {
    const shared = manager();
    const root = await open(shared, '/');
    const admin = await open(shared, '/support', { ticket: ticket('admins') });
    assert.equal(admin.io, root.io);
    assert.equal(io.engine.clientsCount, 1);
    const branchOne = await open(manager(), '/support', { ticket: ticket('branch:1') });
    const branchTwo = await open(manager(), '/support', { ticket: ticket('branch:2') });
    const denied = await open(manager(), '/support', { ticket: ticket('admins') + 'x' }, 'connect_error');
    assert.equal(denied.connected, false);
    const adminNotice = once(admin, 'support_messages');
    const oneNotice = once(branchOne, 'support_messages');
    const twoNotice = once(branchTwo, 'support_messages');
    const cases = [{ id: 10, branch: null }, { id: 11, branch: 1 }, { id: 12, branch: 2 }];
    assert.equal((await post(cases, 'wrong')).status, 403);
    assert.equal((await post(cases, secret)).status, 204);
    assert.deepEqual(await adminNotice, [[10, 11, 12]]);
    assert.deepEqual(await oneNotice, [[11]]);
    assert.deepEqual(await twoNotice, [[12]]);
  } finally {
    for (const socket of sockets) socket.disconnect();
    await new Promise(resolve => io.close(resolve));
  }
});

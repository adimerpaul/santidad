const { createHmac, timingSafeEqual } = require('node:crypto');

function equal(left, right) {
  const first = Buffer.from(left);
  const second = Buffer.from(right);
  return first.length === second.length && timingSafeEqual(first, second);
}

function roomFromTicket(ticket, secret) {
  if (typeof ticket !== 'string' || ticket.length > 300 || !secret) return null;

  const [body, signature, extra] = ticket.split('.');
  const expected = createHmac('sha256', secret).update('support-ticket:' + body).digest('hex');
  if (extra !== undefined || !signature || !equal(signature, expected)) return null;

  try {
    const { room, exp } = JSON.parse(Buffer.from(body, 'base64url'));
    return Number.isInteger(exp) && exp > Date.now() / 1000 &&
      typeof room === 'string' && /^(admins|branch:[1-9]\d*)$/.test(room) ? room : null;
  } catch {
    return null;
  }
}

module.exports = function attachSupport(app, io, secret = process.env.SUPPORT_SOCKET_SECRET) {
  const support = io.of('/support');
  support.use((socket, next) => {
    const room = roomFromTicket(socket.handshake.auth?.ticket, secret);
    if (!room) return next(new Error('Atención en vivo no autorizada'));
    socket.data.room = room;
    next();
  });
  support.on('connection', socket => socket.join(socket.data.room));

  app.post('/support/notify', (req, res) => {
    if (!secret || !equal(req.get('authorization') || '', 'Bearer ' + secret)) {
      return res.sendStatus(403);
    }

    const cases = req.body?.cases;
    if (!Array.isArray(cases) || cases.some(item =>
      !Number.isSafeInteger(item?.id) || item.id < 1 ||
      (item.branch !== null && (!Number.isSafeInteger(item.branch) || item.branch < 1)))) {
      return res.sendStatus(422);
    }

    const branches = new Map();
    for (const { id, branch } of cases) {
      if (branch === null) continue;
      if (!branches.has(branch)) branches.set(branch, []);
      branches.get(branch).push(id);
    }
    if (cases.length) support.to('admins').emit('support_messages', cases.map(item => item.id));
    for (const [branch, ids] of branches) {
      support.to('branch:' + branch).emit('support_messages', ids);
    }

    return res.sendStatus(204);
  });
};

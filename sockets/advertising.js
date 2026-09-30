const fs = require('node:fs');
const path = require('node:path');
const { performance } = require('node:perf_hooks');

const agencyId = value => /^\d+$/.test(String(value)) && Number.isSafeInteger(Number(value)) && Number(value) > 0 ? Number(value) : null;
const room = id => 'publicidad:' + id;
function validSchedule(s) {
  return s?.protocol === 2 && agencyId(s.agencia_id) && Number.isSafeInteger(s.revision) && s.revision > 0 &&
    typeof s.version === 'string' && typeof s.ready === 'boolean' && Number.isFinite(s.epoch_ms) &&
    Array.isArray(s.items) && s.items.length <= 2000 && s.items.every(a =>
      a && ['image', 'video'].includes(a.type) && typeof a.file_id === 'string' && typeof a.media_version === 'string' &&
      typeof a.url === 'string' && /^https?:\/\//.test(a.url) &&
      (a.agencia_id == null || Number(a.agencia_id) === Number(s.agencia_id)) &&
      (!s.ready || (Number.isInteger(a.duration_ms) && a.duration_ms > 0)));
}

function attachAdvertising(io, { directory = path.join(__dirname, '.data', 'advertising'), retryMs = 10000 } = {}) {
  fs.mkdirSync(directory, { recursive: true });
  const states = new Map();
  const bootstrapLeases = new Map();
  const origin = Date.now();
  const tick = performance.now();
  const now = () => origin + performance.now() - tick;
  for (const file of fs.readdirSync(directory).filter(f => /^\d+\.json$/.test(f))) {
    try {
      const s = JSON.parse(fs.readFileSync(path.join(directory, file), 'utf8'));
      if (validSchedule(s)) states.set(Number(s.agencia_id), s);
    } catch (error) { console.error('Cannot restore advertising schedule:', file, error.message); }
  }
  const response = (socket, revision) => {
    const state = states.get(socket.data.adAgency);
    let needsSnapshot = false;
    if (!state) {
      const lease = bootstrapLeases.get(socket.data.adAgency);
      if (!lease || lease.until < Date.now() || lease.owner === socket.id) {
        bootstrapLeases.set(socket.data.adAgency, { owner: socket.id, until: Date.now() + 15000 });
        needsSnapshot = true;
      }
    }
    return { success: true, server_time_ms: now(), revision: state?.revision ?? 0,
      needs_snapshot: needsSnapshot, pending_bootstrap: !state && !needsSnapshot, schedule: state && state.revision !== Number(revision) ? state : null };
  };
  function send(socket, state) {
    if (socket.data.adReceived === state.revision) return;
    socket.emit('ad_schedule', state);
    socket.data.adRetryAt = Date.now() + Math.min(60000, 5000 * (2 ** Math.min(socket.data.adAttempts || 0, 4)));
    socket.data.adAttempts = (socket.data.adAttempts || 0) + 1;
  }
  io.on('connection', socket => {
    socket.on('ad_register', (data, ack) => {
      const agency = agencyId(data?.agencia_id);
      if (!agency) return typeof ack === 'function' && ack({ success: false });
      for (const joined of socket.rooms) if (joined.startsWith('publicidad:')) socket.leave(joined);
      socket.join(room(agency));
      socket.data.adAgency = agency;
      socket.data.adReceived = Number(data.revision) || 0;
      socket.data.adAttempts = 0;
      if (typeof ack === 'function') ack(response(socket, data.revision));
    });
    socket.on('ad_clock', (data, ack) => {
      if (typeof ack !== 'function') return;
      if (!socket.data.adAgency) return ack({ success: false });
      ack(response(socket, data?.revision));
    });
    socket.on('ad_received', data => {
      const s = states.get(socket.data.adAgency);
      if (s && Number(data?.revision) === s.revision) {
        socket.data.adReceived = s.revision;
        socket.data.adAttempts = 0;
      }
    });
    socket.on('ad_status', data => {
      if (['ready', 'applied', 'error'].includes(data?.status)) {
        socket.data.adStatus = { revision: Number(data.revision), status: data.status };
      }
    });
  });
  const timer = setInterval(() => {
    for (const socket of io.sockets.sockets.values()) {
      const s = states.get(socket.data.adAgency);
      if (s && socket.data.adReceived !== s.revision && Date.now() >= (socket.data.adRetryAt || 0)) send(socket, s);
    }
  }, retryMs);
  timer.unref();
  return {
    publish(state) {
      if (!validSchedule(state)) throw new Error('Invalid advertising schedule');
      const id = Number(state.agencia_id);
      const old = states.get(id);
      if (old && old.revision >= state.revision) return;
      const file = path.join(directory, id + '.json');
      fs.writeFileSync(file + '.tmp', JSON.stringify(state));
      fs.renameSync(file + '.tmp', file); // Persist before acknowledging Laravel.
      states.set(id, state);
      bootstrapLeases.delete(id);
      for (const socket of io.sockets.sockets.values()) {
        if (socket.data.adAgency === id) {
          socket.data.adAttempts = 0;
          send(socket, state);
        }
      }
    },
    close() { clearInterval(timer); },
  };
}
module.exports = { attachAdvertising, validSchedule };

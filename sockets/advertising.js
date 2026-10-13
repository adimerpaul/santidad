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
    return { success: true, server_time_ms: now(), revision: state?.revision ?? 0,
      needs_snapshot: false, pending_bootstrap: false, schedule: state && state.revision !== Number(revision) ? state : null };
  };
  function send(socket, state) {
    if (socket.data.adReceived === state.revision) return;
    socket.emit('ad_schedule', { ...state, server_time_ms: now() });
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
      if (typeof ack === 'function') ack({ ...response(socket, data.revision), schedule: states.get(agency) ?? null });
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
    // One measurement per missing media version, sent over the existing socket.
    // No API calls, polling or database access from the players.
    socket.on('ad_durations', (data, ack) => {
      const state = states.get(socket.data.adAgency);
      if (!state || state.ready || data?.revision !== state.revision || !Array.isArray(data.items) || data.items.length > 100) {
        return typeof ack === 'function' && ack({ success: false });
      }
      for (const item of data.items) {
        const ad = state.items.find(a => a.id === item.id && a.media_version === item.media_version && a.type === 'video');
        if (ad && !ad.duration_ms && Number.isInteger(item.duration_ms) && item.duration_ms >= 100 && item.duration_ms <= 86400000) ad.duration_ms = item.duration_ms;
      }
      state.ready = state.items.every(ad => Number.isInteger(ad.duration_ms) && ad.duration_ms > 0);
      if (state.ready) state.epoch_ms = now() + 5000;
      persist(state);
      if (state.ready) {
        for (const client of io.sockets.sockets.values()) {
          if (client.data.adAgency === Number(state.agencia_id)) {
            client.data.adReceived = 0;
            send(client, state);
          }
        }
      }
      if (typeof ack === 'function') ack({ success: true });
    });
  });
  const timer = setInterval(() => {
    for (const socket of io.sockets.sockets.values()) {
      const s = states.get(socket.data.adAgency);
      if (s && socket.data.adReceived !== s.revision && Date.now() >= (socket.data.adRetryAt || 0)) send(socket, s);
    }
  }, retryMs);
  timer.unref();
  function persist(state) {
    const file = path.join(directory, state.agencia_id + '.json');
    fs.writeFileSync(file + '.tmp', JSON.stringify(state));
    fs.renameSync(file + '.tmp', file);
    states.set(Number(state.agencia_id), state);
  }
  return {
    publish(state) {
      if (!validSchedule(state)) throw new Error('Invalid advertising schedule');
      const id = Number(state.agencia_id);
      const old = states.get(id);
      if (old && old.revision >= state.revision) return;
      // Reuse measured durations for unchanged media on the next manual/daily run.
      for (const ad of state.items) {
        const previous = old?.items.find(a => a.id === ad.id && a.media_version === ad.media_version);
        if (!ad.duration_ms && previous?.duration_ms) ad.duration_ms = previous.duration_ms;
      }
      state.ready = state.items.every(ad => Number.isInteger(ad.duration_ms) && ad.duration_ms > 0);
      persist(state);
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

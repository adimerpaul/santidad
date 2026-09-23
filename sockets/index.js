const express = require('express');
const http = require('http');
const { Server } = require("socket.io");

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
  cors: {
    origin: "*",
  }
});
app.use(express.json());

// Enable CORS for HTTP requests (like /notify)
app.use((req, res, next) => {
  res.header("Access-Control-Allow-Origin", "*");
  res.header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept");
  res.header("Access-Control-Allow-Methods", "GET, POST, OPTIONS");
  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }
  next();
});
app.get('/', (req, res) => {
  res.send('Socket server running');
});

const TERMINAL_EVENTS = new Set([
  'clienteDisplayData',
  'clienteQrData',
  'clienteSaleComplete',
  'clienteDisplayClose',
]);

function terminalRoom(agenciaId, caja) {
  const agencia = Number.parseInt(agenciaId, 10);
  const numeroCaja = Number.parseInt(caja, 10);
  if (!Number.isInteger(agencia) || agencia < 1 || !Number.isInteger(numeroCaja) || numeroCaja < 1) {
    return null;
  }
  return `terminal:${agencia}:${numeroCaja}`;
}

// Endpoint for Laravel to trigger events
app.post('/notify', (req, res) => {
  const { event, data } = req.body;
  if (typeof event !== 'string' || event.length === 0) {
    return res.status(400).json({ success: false, message: 'Evento no válido' });
  }

  if (TERMINAL_EVENTS.has(event)) {
    const room = terminalRoom(data?.agencia_id, data?.caja);
    if (!room) {
      return res.status(422).json({
        success: false,
        message: 'Los eventos de pantalla requieren agencia_id y caja válidos',
      });
    }
    io.to(room).emit(event, data);
    console.log(`Event emitted to ${room}: ${event}`, data);
    return res.json({ success: true, room });
  }

  io.emit(event, data);
  console.log(`Broadcast event emitted: ${event}`, data);
  return res.json({ success: true });
});

io.on('connection', (socket) => {
  console.log('Client connected:', socket.id);

  socket.on('register_terminal', (terminal, acknowledge) => {
    const room = terminalRoom(terminal?.agencia_id, terminal?.caja);
    if (!room) {
      if (typeof acknowledge === 'function') acknowledge({ success: false });
      return;
    }

    for (const joinedRoom of socket.rooms) {
      if (joinedRoom.startsWith('terminal:')) socket.leave(joinedRoom);
    }
    socket.join(room);
    console.log(`Terminal registered in ${room}:`, socket.id);
    if (typeof acknowledge === 'function') acknowledge({ success: true, room });
  });

  socket.on('disconnect', () => {
    console.log('Client disconnected:', socket.id);
  });
});

const PORT = Number.parseInt(process.env.PORT, 10) || 3000;
server.listen(PORT, '0.0.0.0', () => {
  console.log(`Socket server listening on port http://localhost:${PORT}`);
});

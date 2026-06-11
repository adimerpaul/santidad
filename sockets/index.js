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

app.get('/', (req, res) => {
  res.send('Socket server running');
});

// Endpoint for Laravel to trigger events
app.post('/notify', (req, res) => {
  const { event, data } = req.body;
  io.emit(event, data);
  console.log(`Event emitted: ${event}`, data);
  res.json({ success: true });
});

io.on('connection', (socket) => {
  console.log('Client connected:', socket.id);
  socket.on('disconnect', () => {
    console.log('Client disconnected:', socket.id);
  });
});

const PORT = 3000;
server.listen(PORT, '0.0.0.0', () => {
  console.log(`Socket server listening on port http://localhost:${PORT}`);
});

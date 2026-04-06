const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');

const server = http.createServer();
const io = new Server(server, {
  cors: {
    origin: '*', // Allow all origins for local dev
    methods: ['GET', 'POST']
  }
});

io.on('connection', (socket) => {
  console.log('User connected:', socket.id);

  socket.on('join_room', (orderId) => {
    const roomName = `order_${orderId}`;
    socket.join(roomName);
    console.log(`Socket ${socket.id} joined room: ${roomName}`);
  });

  socket.on('send_message', (data) => {
    // data should contain order_id, message, sender_role, sender_name, attachment_url, created_at
    const roomName = `order_${data.order_id}`;
    socket.to(roomName).emit('receive_message', data);
    console.log(`Message in room ${roomName} from ${data.sender_role}`);
  });

  socket.on('send_notification', (data) => {
    // data should contain order_id, user_name, message_preview
    socket.broadcast.emit('receive_notification', data);
    console.log(`Notification sent for order ${data.order_id}`);
  });

  socket.on('disconnect', () => {
    console.log('User disconnected:', socket.id);
  });
});

const PORT = 3000;
server.listen(PORT, () => {
  console.log(`WebSocket server running on port ${PORT}`);
});

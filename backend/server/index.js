const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');

const app = express();
const API_URL = process.env.API_URL || 'http://127.0.0.1:8000';
app.use(cors());
app.use(express.json());

const server = http.createServer(app);

const io = new Server(server, {
  cors: {
    origin: '*',
  }
});

// Store connected users: { userId: socketId }
const connectedUsers = new Map();

// Throttle DB writes — track last update_app_online.php call per user
// Key: userId, Value: timestamp (ms)
const lastDbUpdate = new Map();
const DB_UPDATE_THROTTLE_MS = 2 * 60 * 1000; // 2 minutes

io.on('connection', (socket) => {
  console.log('A user connected:', socket.id);

  const updateDBStatus = (userId, isOnline, force = false) => {
    // Throttle online DB updates — only write if last call was >2 min ago
    // Always allow offline (disconnect) to write immediately (force=true)
    if (isOnline && !force) {
      const last = lastDbUpdate.get(userId) || 0;
      const now = Date.now();
      if (now - last < DB_UPDATE_THROTTLE_MS) {
        return; // Skip — DB was updated recently via WebSocket
      }
      lastDbUpdate.set(userId, now);
    } else if (!isOnline) {
      lastDbUpdate.delete(userId); // Reset on offline so next online call goes through
    }

    try {
      const data = JSON.stringify({ user_id: userId });
      const endpoint = isOnline ? 'update_app_online.php' : 'mark_offline.php';
      const req = http.request(`${API_URL}/${endpoint}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(data)
        }
      });
      req.on('error', () => {}); // Ignore errors
      req.write(data);
      req.end();
    } catch (err) {
      // Ignore
    }
  };

  // When a user logs in or opens the app
  // IMPORTANT: Always coerce userId to String — JS Map treats 42 !== "42"
  socket.on('register', (userId) => {
    const uid = String(userId);
    connectedUsers.set(uid, socket.id);
    socket.join(`user_${uid}`); // Join personal room for multi-device support
    console.log(`User ${uid} registered with socket ${socket.id} and joined user_${uid}`);
    
    io.emit('user_status', { userId: uid, status: 'online' });
    updateDBStatus(uid, true);
  });

  // When a new message/call happens
  socket.on('send_notification', (data) => {
    const receiverRoom = `user_${data.receiverId}`;
    io.to(receiverRoom).emit('new_notification', data);
    console.log(`Notification sent to ${receiverRoom}`);
  });

  // Ping/Pong for online status
  socket.on('heartbeat', (userId) => {
    const uid = String(userId);
    if (!connectedUsers.has(uid)) {
      connectedUsers.set(uid, socket.id);
      socket.join(`user_${uid}`);
      console.log(`User ${uid} re-registered via heartbeat`);
    }
    io.emit('user_status', { userId: uid, status: 'online' });
    updateDBStatus(uid, true);
  });

  socket.on('check_status', (targetUserId) => {
    const isOnline = connectedUsers.has(String(targetUserId));
    socket.emit('user_status', { userId: targetUserId, status: isOnline ? 'online' : 'offline' });
  });

  socket.on('check_multiple_status', (userIds) => {
    if (Array.isArray(userIds)) {
      const statuses = userIds.map(uid => ({
        userId: uid,
        status: connectedUsers.has(String(uid)) ? 'online' : 'offline'
      }));
      socket.emit('multiple_user_status', statuses);
    }
  });

  // Handle post subscriptions for realtime feed updates
  socket.on('join_post', (postId) => {
    socket.join(`post_${postId}`);
    console.log(`User ${socket.id} joined room post_${postId}`);
  });

  socket.on('leave_post', (postId) => {
    socket.leave(`post_${postId}`);
  });

  // Handle group chat subscriptions
  socket.on('join_group', (groupId) => {
    socket.join(`group_${groupId}`);
    console.log(`User ${socket.id} joined room group_${groupId}`);
  });

  socket.on('leave_group', (groupId) => {
    socket.leave(`group_${groupId}`);
  });

  // Handle typing indicators
  socket.on('typing', (data) => {
    const receiverSocket = connectedUsers.get(String(data.receiverId));
    if (receiverSocket) {
      io.to(receiverSocket).emit('typing', data);
    }
  });

  socket.on('stop_typing', (data) => {
    const receiverSocket = connectedUsers.get(String(data.receiverId));
    if (receiverSocket) {
      io.to(receiverSocket).emit('stop_typing', data);
    }
  });

  socket.on('mark_seen', async (payload) => {
    try {
      const axios = require('axios');
      const FormData = require('form-data');
      const formData = new FormData();
      formData.append('message_id', payload.message_id || '');
      formData.append('my_profile_id', payload.my_profile_id || '');
      formData.append('receiver_id', payload.receiver_id || '');
      formData.append('platform', payload.platform || '');
      
      await axios.post(`${API_URL}/mark_seen.php`, formData, {
        headers: formData.getHeaders()
      });
    } catch (e) {
      console.error("mark_seen error", e.message);
    }
  });
  socket.on('chat:send', async (payload) => {
    console.log("chat:send received", payload);
    try {
      const axios = require('axios');
      const FormData = require('form-data');
      
      const formData = new FormData();
      if (payload.isGroup) {
        formData.append('group_id', String(payload.receiver_id));
        formData.append('sender_id', String(payload.my_profile_id));
      } else {
        formData.append('my_profile_id', String(payload.my_profile_id));
        formData.append('receiver_id', String(payload.receiver_id));
      }
      formData.append('client_message_id', payload.client_message_id);
      formData.append('message', payload.message || '');
      formData.append('platform', payload.platform || 'marriage');
      
      const endpoint = payload.isGroup ? 'send_group_message.php' : 'send_chat_message.php';
      
      // Node forwards the message to the PHP backend which handles MySQL, Redis, Auth, and WS Broadcast
      const res = await axios.post(`${API_URL}/${endpoint}`, formData, {
        headers: formData.getHeaders()
      });
      
      if (res.data && res.data.status === 'success') {
        socket.emit('chat:ack', {
          client_message_id: payload.client_message_id,
          server_id: res.data.id || res.data.client_message_id,
          status: 'sent'
        });
      } else {
        socket.emit('chat:error', {
          client_message_id: payload.client_message_id,
          message: res.data?.message || 'Failed to send'
        });
      }
    } catch (e) {
      console.error("chat:send error", e.message);
      socket.emit('chat:error', {
        client_message_id: payload.client_message_id,
        message: 'Network error'
      });
    }
  });

  socket.on('disconnect', () => {
    console.log('User disconnected:', socket.id);
    for (const [userId, sId] of connectedUsers.entries()) {
      if (sId === socket.id) {
        connectedUsers.delete(userId);
        io.emit('user_status', { userId, status: 'offline' });
        updateDBStatus(userId, false);
        break;
      }
    }
  });
});

// PHP backend can also hit this Node API to trigger socket events
app.post('/api/trigger', (req, res) => {
  console.log('/api/trigger received', req.body);
  const { receiverId, type, payload, room, global } = req.body;
  
  if (global) {
    io.emit(type, payload);
    return res.json({ success: true, message: 'Event emitted globally' });
  }

  if (room) {
    io.to(room).emit(type, payload);
    return res.json({ success: true, message: 'Event emitted to room' });
  }
  
  const receiverSocket = connectedUsers.get(String(receiverId));
  if (receiverSocket) {
    io.to(receiverSocket).emit(type, payload);
    return res.json({ success: true, message: 'Event emitted' });
  }
  
  res.json({ success: false, message: 'User offline' });
});

const PORT = 3000;
server.listen(PORT, () => {
  console.log(`WebSocket Server running on port ${PORT}`);
});


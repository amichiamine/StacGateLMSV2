import type { Express } from "express";
import { createServer, type Server } from "http";
import { WebSocketServer } from "ws";
import WebSocket from "ws";
import session from "express-session";
import apiRoutes from "./api/index";

// Extend session interface
declare module 'express-session' {
  interface SessionData {
    userId: string;
  }
}

export async function registerRoutes(app: Express): Promise<Server> {
  // Session management with better configuration for browser compatibility
  app.use(session({
    secret: process.env.SESSION_SECRET || 'dev-secret-key-StacGateLMS-2025',
    resave: false,
    saveUninitialized: false,
    name: 'connect.sid',
    cookie: { 
      secure: false, // set to true in production with HTTPS
      httpOnly: true, // Security: prevent XSS
      maxAge: 24 * 60 * 60 * 1000, // 24 hours
      sameSite: 'lax'
    },
    rolling: true // Extend session on each request
  }));

  // Mount API routes with debug logging
  console.log('Mounting API routes at /api...');
  app.use('/api', apiRoutes);
  
  // Add explicit debugging for unmatched API routes
  app.use('/api/*', (req, res, next) => {
    console.log(`Unmatched API route: ${req.method} ${req.originalUrl}`);
    res.status(404).json({ error: `API endpoint not found: ${req.method} ${req.originalUrl}` });
  });

  // WebSocket server setup (on specific path to avoid Vite conflicts)
  const server = createServer(app);
  const wss = new WebSocketServer({ 
    server,
    path: '/ws/collaboration' // Specific path for our WebSocket
  });

  // Import and initialize collaboration manager
  const { CollaborationManager } = await import('./websocket/collaborationManager');
  const collaborationManager = new CollaborationManager();

  wss.on('connection', (ws: WebSocket, request) => {
    console.log('New WebSocket connection on /ws/collaboration');
    
    // Extract user info from query parameters or headers
    const url = new URL(request.url!, `http://${request.headers.host}`);
    const userId = url.searchParams.get('userId');
    const userName = url.searchParams.get('userName') || 'Unknown User';
    const userRole = url.searchParams.get('userRole') || 'apprenant';
    const establishmentId = url.searchParams.get('establishmentId') || 'default';

    if (!userId) {
      ws.send(JSON.stringify({
        type: 'error',
        data: { error: 'userId parameter is required', timestamp: new Date().toISOString() }
      }));
      ws.close();
      return;
    }

    // Add user to collaboration system
    collaborationManager.addUser(ws, {
      id: userId,
      name: userName,
      role: userRole,
      establishmentId: establishmentId
    });
    
    ws.on('message', (message: string) => {
      try {
        const data = JSON.parse(message.toString());
        console.log('Received collaboration message:', data.type, data.roomId || 'no-room');
        
        // Handle different types of collaboration messages
        collaborationManager.handleCollaborationMessage(ws, data);
        
      } catch (error) {
        console.error('WebSocket message error:', error);
        ws.send(JSON.stringify({
          type: 'error',
          data: { error: 'Invalid message format', timestamp: new Date().toISOString() }
        }));
      }
    });
    
    ws.on('close', () => {
      console.log('WebSocket connection closed');
      collaborationManager.removeUser(ws);
    });

    ws.on('error', (error) => {
      console.error('WebSocket error:', error);
      collaborationManager.removeUser(ws);
    });
  });

  // Add collaboration stats endpoint
  app.get('/api/collaboration/stats', (req, res) => {
    try {
      const stats = collaborationManager.getSystemStats();
      res.json(stats);
    } catch (error) {
      console.error('Error getting collaboration stats:', error);
      res.status(500).json({ error: 'Failed to get collaboration stats' });
    }
  });

  // Add room stats endpoint
  app.get('/api/collaboration/rooms/:roomId', (req, res) => {
    try {
      const { roomId } = req.params;
      const stats = collaborationManager.getRoomStats(roomId);
      
      if (!stats) {
        return res.status(404).json({ error: 'Room not found' });
      }
      
      res.json(stats);
    } catch (error) {
      console.error('Error getting room stats:', error);
      res.status(500).json({ error: 'Failed to get room stats' });
    }
  });

  return server;
}
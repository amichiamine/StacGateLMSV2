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
    name: 'stacgate.sid',
    cookie: { 
      secure: false, // set to true in production with HTTPS
      httpOnly: false, // Allow JavaScript access to cookies for better browser compatibility
      maxAge: 24 * 60 * 60 * 1000, // 24 hours
      sameSite: 'lax'
    },
    rolling: true // Extend session on each request
  }));

  // Mount API routes
  app.use('/api', apiRoutes);

  // WebSocket server setup (on specific path to avoid Vite conflicts)
  const server = createServer(app);
  const wss = new WebSocketServer({ 
    server,
    path: '/ws/collaboration' // Specific path for our WebSocket
  });

  wss.on('connection', (ws: WebSocket) => {
    console.log('New WebSocket connection on /ws/collaboration');
    
    ws.on('message', (message: string) => {
      try {
        const data = JSON.parse(message.toString());
        console.log('Received collaboration data:', data);
        
        // Broadcast to all connected clients
        wss.clients.forEach(client => {
          if (client !== ws && client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify({
              type: 'broadcast',
              data: data,
              timestamp: new Date().toISOString()
            }));
          }
        });
      } catch (error) {
        console.error('WebSocket message error:', error);
      }
    });
    
    ws.on('close', () => {
      console.log('WebSocket connection closed');
    });

    // Send welcome message
    ws.send(JSON.stringify({
      type: 'connected',
      message: 'Connected to StacGate LMS collaboration server',
      timestamp: new Date().toISOString()
    }));
  });

  return server;
}
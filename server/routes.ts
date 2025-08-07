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

  // WebSocket server setup
  const server = createServer(app);
  const wss = new WebSocketServer({ server });

  wss.on('connection', (ws: WebSocket) => {
    console.log('New WebSocket connection');
    
    ws.on('message', (message: string) => {
      try {
        const data = JSON.parse(message.toString());
        console.log('Received:', data);
        
        // Echo back for now
        ws.send(JSON.stringify({
          type: 'echo',
          data: data
        }));
      } catch (error) {
        console.error('WebSocket message error:', error);
      }
    });
    
    ws.on('close', () => {
      console.log('WebSocket connection closed');
    });
  });

  return server;
}
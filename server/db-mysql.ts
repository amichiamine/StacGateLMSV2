import { drizzle } from 'drizzle-orm/mysql2';
import mysql from 'mysql2/promise';
import * as schema from "@shared/schema-mysql";

// Configuration MySQL
const connectionConfig = {
  host: process.env.DB_HOST || 'localhost',
  port: parseInt(process.env.DB_PORT || '3306'),
  user: process.env.DB_USER || 'root', 
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'stacgate_lms',
  charset: 'utf8mb4',
  ssl: process.env.DB_SSL === 'true' ? { rejectUnauthorized: false } : false
};

// Pool de connexions MySQL
export const connection = mysql.createPool(connectionConfig);

// Instance Drizzle avec MySQL
export const db = drizzle(connection, { schema, mode: 'default' });

// Test de connexion
export async function testConnection() {
  try {
    const [rows] = await connection.execute('SELECT 1 as test');
    console.log('✅ Connexion MySQL établie');
    return true;
  } catch (error) {
    console.error('❌ Erreur connexion MySQL:', error);
    return false;
  }
}
import { Pool, neonConfig } from '@neondatabase/serverless';
import { drizzle } from 'drizzle-orm/neon-serverless';
import { eq, sql } from 'drizzle-orm';
import ws from "ws";
import * as schema from "@shared/schema";
import { Establishment } from "@shared/schema";

neonConfig.webSocketConstructor = ws;

// Interface pour gérer les connexions multiples
interface DatabaseConnection {
  pool: Pool;
  db: ReturnType<typeof drizzle>;
}

export class DatabaseManager {
  private static instance: DatabaseManager;
  private connections: Map<string, DatabaseConnection> = new Map();
  private mainDb: ReturnType<typeof drizzle>;

  private constructor() {
    // Connexion principale pour la gestion globale
    if (!process.env.DATABASE_URL) {
      throw new Error("DATABASE_URL must be set for main database connection");
    }
    
    const mainPool = new Pool({ connectionString: process.env.DATABASE_URL });
    this.mainDb = drizzle({ client: mainPool, schema });
  }

  public static getInstance(): DatabaseManager {
    if (!DatabaseManager.instance) {
      DatabaseManager.instance = new DatabaseManager();
    }
    return DatabaseManager.instance;
  }

  // Récupère la base de données principale (pour gestion des établissements)
  public getMainDb() {
    return this.mainDb;
  }

  // Récupère ou crée une connexion pour un établissement
  public async getEstablishmentDb(establishmentId: string): Promise<ReturnType<typeof drizzle>> {
    // Vérifier si on a déjà une connexion pour cet établissement
    if (this.connections.has(establishmentId)) {
      return this.connections.get(establishmentId)!.db;
    }

    // Récupérer les informations de l'établissement depuis la BD principale
    const establishment = await this.getEstablishmentConfig(establishmentId);
    if (!establishment) {
      throw new Error(`Establishment ${establishmentId} not found`);
    }

    // Utiliser la BD spécifique de l'établissement ou créer une nouvelle
    const establishmentConnectionUrl = establishment.databaseUrl || await this.createEstablishmentDatabase(establishmentId);
    
    if (!establishmentConnectionUrl) {
      throw new Error(`No database URL configured for establishment ${establishmentId}`);
    }

    // Créer la connexion
    const pool = new Pool({ connectionString: establishmentConnectionUrl });
    const db = drizzle({ client: pool, schema });

    // Stocker la connexion
    this.connections.set(establishmentId, { pool, db });

    return db;
  }

  // Récupérer la configuration d'un établissement
  private async getEstablishmentConfig(establishmentId: string): Promise<Establishment & { databaseUrl?: string } | null> {
    try {
      const [establishment] = await this.mainDb
        .select()
        .from(schema.establishments)
        .where(eq(schema.establishments.id, establishmentId));

      if (!establishment) {
        return null;
      }

      // La configuration de BD peut être dans database_url ou settings
      const databaseUrl = establishment.databaseUrl || (establishment.settings as any)?.databaseUrl || process.env.DATABASE_URL;

      return {
        ...establishment,
        databaseUrl
      };
    } catch (error) {
      console.error('Error fetching establishment config:', error);
      return null;
    }
  }

  // Fermer toutes les connexions
  public async closeAllConnections(): Promise<void> {
    for (const [establishmentId, connection] of Array.from(this.connections.entries())) {
      try {
        await connection.pool.end();
        console.log(`Closed connection for establishment: ${establishmentId}`);
      } catch (error) {
        console.error(`Error closing connection for establishment ${establishmentId}:`, error);
      }
    }
    this.connections.clear();
  }

  // Créer une nouvelle base de données pour un établissement
  private async createEstablishmentDatabase(establishmentId: string): Promise<string> {
    // Pour l'instant, on utilise la même BD avec un schéma dédié
    const establishmentDbUrl = process.env.DATABASE_URL;
    
    if (!establishmentDbUrl) {
      throw new Error("Cannot create establishment database without main DATABASE_URL");
    }

    // Créer un schéma dédié pour l'établissement
    const pool = new Pool({ connectionString: establishmentDbUrl });
    
    try {
      const schemaName = `establishment_${establishmentId.replace(/-/g, '_')}`;
      await pool.query(`CREATE SCHEMA IF NOT EXISTS ${schemaName}`);
      
      // Mettre à jour l'établissement avec l'URL de la BD
      await this.mainDb
        .update(schema.establishments)
        .set({ 
          databaseUrl: establishmentDbUrl,
          updatedAt: new Date()
        })
        .where(eq(schema.establishments.id, establishmentId));
        
      console.log(`Created database schema for establishment ${establishmentId}`);
      return establishmentDbUrl;
    } catch (error) {
      console.error(`Failed to create database for establishment ${establishmentId}:`, error);
      throw error;
    } finally {
      await pool.end();
    }
  }

  // Obtenir toutes les connexions actives
  public getActiveConnections(): string[] {
    return Array.from(this.connections.keys());
  }
}

// Export singleton instance
export const databaseManager = DatabaseManager.getInstance();

// Helper functions pour faciliter l'utilisation
export const getMainDb = () => databaseManager.getMainDb();
export const getEstablishmentDb = (establishmentId: string) => databaseManager.getEstablishmentDb(establishmentId);
import { databaseManager } from './database-manager';
import { eq, sql } from 'drizzle-orm';
import * as schema from "@shared/schema";

export class EstablishmentService {
  private static instance: EstablishmentService;

  private constructor() {}

  public static getInstance(): EstablishmentService {
    if (!EstablishmentService.instance) {
      EstablishmentService.instance = new EstablishmentService();
    }
    return EstablishmentService.instance;
  }

  // Créer un nouvel établissement avec sa base de données dédiée
  async createEstablishment(data: {
    name: string;
    slug: string;
    description?: string;
    logo?: string;
    domain?: string;
  }) {
    const mainDb = databaseManager.getMainDb();
    
    // Créer l'établissement dans la BD principale
    const [establishment] = await mainDb
      .insert(schema.establishments)
      .values({
        ...data,
        isActive: true,
        settings: {},
        createdAt: new Date(),
        updatedAt: new Date()
      })
      .returning();

    console.log(`Created establishment: ${establishment.name} (${establishment.id})`);

    // Initialiser la base de données spécifique à l'établissement
    await this.initializeEstablishmentDatabase(establishment.id);

    return establishment;
  }

  // Initialiser la base de données d'un établissement
  private async initializeEstablishmentDatabase(establishmentId: string) {
    try {
      // Obtenir la connexion à la BD de l'établissement
      const establishmentDb = await databaseManager.getEstablishmentDb(establishmentId);
      
      // Créer les données par défaut
      await this.createDefaultData(establishmentId, establishmentDb);
      
      console.log(`Initialized database for establishment: ${establishmentId}`);
    } catch (error) {
      console.error(`Failed to initialize database for establishment ${establishmentId}:`, error);
      throw error;
    }
  }

  // Créer les données par défaut pour un établissement
  private async createDefaultData(establishmentId: string, db: any) {
    try {
      // Créer un utilisateur administrateur par défaut
      const [adminUser] = await db
        .insert(schema.users)
        .values({
          establishmentId,
          email: 'admin@establishment.local',
          username: 'admin',
          firstName: 'Administrateur',
          lastName: 'Établissement',
          role: 'admin',
          isActive: true,
          createdAt: new Date(),
          updatedAt: new Date()
        })
        .returning()
        .catch(() => [null]); // Ignorer si l'utilisateur existe déjà

      // Créer un thème par défaut
      await db
        .insert(schema.themes)
        .values({
          establishmentId,
          name: 'Thème par défaut',
          isActive: true,
          primaryColor: '#6366f1',
          secondaryColor: '#06b6d4',
          accentColor: '#10b981',
          backgroundColor: '#ffffff',
          textColor: '#1f2937',
          fontFamily: 'Inter',
          fontSize: '16px',
          createdAt: new Date(),
          updatedAt: new Date()
        })
        .catch(() => {}); // Ignorer si le thème existe déjà

      console.log(`Created default data for establishment: ${establishmentId}`);
      
      if (adminUser) {
        console.log(`Created admin user: ${adminUser.email}`);
      }
    } catch (error) {
      console.error(`Error creating default data for establishment ${establishmentId}:`, error);
    }
  }

  // Obtenir tous les établissements
  async getAllEstablishments() {
    const mainDb = databaseManager.getMainDb();
    return await mainDb
      .select()
      .from(schema.establishments)
      .where(eq(schema.establishments.isActive, true))
      .orderBy(schema.establishments.createdAt);
  }

  // Obtenir un établissement par ID
  async getEstablishmentById(establishmentId: string) {
    const mainDb = databaseManager.getMainDb();
    const [establishment] = await mainDb
      .select()
      .from(schema.establishments)  
      .where(eq(schema.establishments.id, establishmentId));
    
    return establishment || null;
  }

  // Obtenir un établissement par slug
  async getEstablishmentBySlug(slug: string) {
    const mainDb = databaseManager.getMainDb();
    const [establishment] = await mainDb
      .select()
      .from(schema.establishments)
      .where(eq(schema.establishments.slug, slug));
    
    return establishment || null;
  }

  // Obtenir la base de données d'un établissement
  async getEstablishmentDatabase(establishmentId: string) {
    return await databaseManager.getEstablishmentDb(establishmentId);
  }

  // Mettre à jour un établissement
  async updateEstablishment(establishmentId: string, data: Partial<{
    name: string;
    slug: string;
    description: string;
    logo: string;
    domain: string;
    isActive: boolean;
    settings: any;
  }>) {
    const mainDb = databaseManager.getMainDb();
    
    const [updatedEstablishment] = await mainDb
      .update(schema.establishments)
      .set({
        ...data,
        updatedAt: new Date()
      })
      .where(eq(schema.establishments.id, establishmentId))
      .returning();

    return updatedEstablishment;
  }

  // Supprimer un établissement (soft delete)
  async deleteEstablishment(establishmentId: string) {
    const mainDb = databaseManager.getMainDb();
    
    const [deletedEstablishment] = await mainDb
      .update(schema.establishments)
      .set({
        isActive: false,
        updatedAt: new Date()
      })
      .where(eq(schema.establishments.id, establishmentId))
      .returning();

    // Fermer la connexion à la BD de l'établissement
    const connections = databaseManager.getActiveConnections();
    if (connections.includes(establishmentId)) {
      // Note: Pour une vraie suppression, il faudrait fermer et supprimer la BD
      console.log(`Establishment ${establishmentId} marked as inactive`);
    }

    return deletedEstablishment;
  }

  // Obtenir les statistiques d'un établissement
  async getEstablishmentStats(establishmentId: string) {
    const establishmentDb = await this.getEstablishmentDatabase(establishmentId);
    
    const [userCount] = await establishmentDb
      .select({ count: sql`count(*)` })
      .from(schema.users)
      .where(eq(schema.users.establishmentId, establishmentId));

    const [courseCount] = await establishmentDb
      .select({ count: sql`count(*)` })
      .from(schema.courses) 
      .where(eq(schema.courses.establishmentId, establishmentId));

    const [themeCount] = await establishmentDb
      .select({ count: sql`count(*)` })
      .from(schema.themes)
      .where(eq(schema.themes.establishmentId, establishmentId));

    return {
      users: userCount?.count || 0,
      courses: courseCount?.count || 0,
      themes: themeCount?.count || 0
    };
  }
}

// Export singleton instance
export const establishmentService = EstablishmentService.getInstance();
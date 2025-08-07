import type { Express } from "express";
import { createServer, type Server } from "http";
import { WebSocketServer } from "ws";
import WebSocket from "ws";
import { storage } from "./storage";
import { databaseManager, getMainDb, getEstablishmentDb } from "./database-manager";
import { db } from "./db";
import { establishmentService } from "./establishment-service";
import { requireAuth, requireSuperAdmin, requireAdmin, requireEstablishmentAccess } from "./middleware/auth";
import session from "express-session";
// Removed Replit Auth imports - using local authentication
import { 
  insertUserSchema, 
  insertCourseSchema, 
  insertUserCourseSchema,
  insertEstablishmentSchema,
  insertSimpleThemeSchema,
  insertSimpleCustomizableContentSchema,
  insertSimpleMenuItemSchema,
  insertExportJobSchema,
  insertAssessmentSchema,
  insertAssessmentAttemptSchema
} from "@shared/schema";
import * as schema from "@shared/schema";
import { eq, and, sql, like, ilike, desc, asc } from "drizzle-orm";
import { z } from "zod";
import bcrypt from "bcryptjs";

// Extend session interface
declare module 'express-session' {
  interface SessionData {
    userId: string;
  }
}

const loginSchema = z.object({
  email: z.string().email(),
  password: z.string().min(1),
});

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

  // Auth routes for local authentication
  app.get('/api/auth/user', async (req: any, res) => {
    try {
      if (!req.session?.userId) {
        return res.status(401).json({ message: "Non authentifié" });
      }
      
      const user = await storage.getUser(req.session.userId);
      if (!user) {
        return res.status(401).json({ message: "Utilisateur non trouvé" });
      }
      
      res.json(user);
    } catch (error) {
      console.error("Error fetching user:", error);
      res.status(500).json({ message: "Erreur serveur" });
    }
  });

  // Logout route
  app.post('/api/auth/logout', (req: any, res) => {
    req.session.destroy((err: any) => {
      if (err) {
        return res.status(500).json({ message: "Erreur lors de la déconnexion" });
      }
      res.json({ message: "Déconnecté avec succès" });
    });
  });

  // Portal routes - publicly accessible establishments
  app.get("/api/establishments", async (req, res) => {
    try {
      const establishments = await storage.getAllEstablishments();
      // Filter only active establishments for public portal
      const activeEstablishments = establishments.filter(est => est.isActive);
      res.json(activeEstablishments);
    } catch (error) {
      console.error("Error fetching establishments:", error);
      res.status(500).json({ message: "Failed to fetch establishments" });
    }
  });

  // Route pour récupérer un établissement par slug
  app.get("/api/establishments/slug/:slug", async (req, res) => {
    try {
      const establishment = await storage.getEstablishmentBySlug(req.params.slug);
      if (!establishment || !establishment.isActive) {
        return res.status(404).json({ message: "Establishment not found" });
      }
      res.json(establishment);
    } catch (error) {
      console.error("Error fetching establishment:", error);
      res.status(500).json({ message: "Failed to fetch establishment" });
    }
  });

  // Route pour récupérer le contenu personnalisé d'un établissement
  app.get("/api/establishment-content/:slug/:pageType", async (req, res) => {
    try {
      const { slug, pageType } = req.params;
      
      // Récupérer l'établissement par slug
      const establishment = await storage.getEstablishmentBySlug(slug);
      if (!establishment || !establishment.isActive) {
        return res.status(404).json({ message: "Establishment not found" });
      }

      // Récupérer le contenu personnalisé
      const content = await storage.getCustomizableContentByKey(
        establishment.id, 
        `${pageType}_page`
      );
      
      // Retourner un contenu par défaut si aucun contenu personnalisé n'existe
      const defaultContent = {
        heroTitle: `Bienvenue chez ${establishment.name}`,
        heroDescription: establishment.description || "Découvrez notre offre de formation et rejoignez notre communauté d'apprenants."
      };
      
      res.json(content?.content ? JSON.parse(content.content) : defaultContent);
    } catch (error) {
      console.error("Error fetching establishment content:", error);
      res.status(500).json({ message: "Failed to fetch establishment content" });
    }
  });

  // Local authentication routes
  app.post("/api/auth/login", async (req: any, res) => {
    try {
      const { email, password } = loginSchema.parse(req.body);
      
      // Get default establishment
      const establishments = await storage.getAllEstablishments();
      const defaultEstablishment = establishments[0];
      
      if (!defaultEstablishment) {
        return res.status(500).json({ message: "Aucun établissement configuré" });
      }

      // Check if user exists
      const user = await storage.getUserByEmail(email, defaultEstablishment.id);
      if (!user) {
        return res.status(400).json({ message: "Identifiants invalides" });
      }

      // Verify password with bcrypt
      const isValidPassword = await bcrypt.compare(password, user.password || '');
      if (!isValidPassword) {
        return res.status(400).json({ message: "Identifiants invalides" });
      }

      // Update last login time
      await storage.updateUserLastLogin(user.id);
      
      // Create session
      req.session.userId = user.id;
      
      res.json({ 
        user: { 
          id: user.id, 
          email: user.email, 
          firstName: user.firstName, 
          lastName: user.lastName,
          role: user.role,
          establishmentId: user.establishmentId
        } 
      });
    } catch (error) {
      console.error("Login error:", error);
      res.status(400).json({ message: "Identifiants invalides" });
    }
  });

  app.post("/api/auth/register", async (req: any, res) => {
    try {
      const { email, password, firstName, lastName } = req.body;
      
      if (!email || !password || !firstName || !lastName) {
        return res.status(400).json({ message: "Tous les champs sont requis" });
      }

      // Get default establishment
      const establishments = await storage.getAllEstablishments();
      const defaultEstablishment = establishments[0];
      
      if (!defaultEstablishment) {
        return res.status(500).json({ message: "Aucun établissement configuré" });
      }

      // Check if user already exists
      const existingUser = await storage.getUserByEmail(email, defaultEstablishment.id);
      if (existingUser) {
        return res.status(400).json({ message: "Un compte existe déjà avec cet email" });
      }

      // Hash password
      const hashedPassword = await bcrypt.hash(password, 12);
      
      // Create new user  
      const username = email.split('@')[0];
      const user = await storage.createUser({
        establishmentId: defaultEstablishment.id,
        email,
        username,
        password: hashedPassword,
        firstName,
        lastName,
        role: "apprenant",
      });

      // Create session
      req.session.userId = user.id;
      
      res.json({ 
        user: { 
          id: user.id, 
          email: user.email, 
          firstName: user.firstName, 
          lastName: user.lastName,
          role: user.role
        } 
      });
    } catch (error) {
      console.error("Registration error:", error);
      res.status(500).json({ message: "Erreur lors de la création du compte" });
    }
  });

  // Ancienne route courses supprimée - conflit résolu

  app.get("/api/courses/:id", async (req, res) => {
    try {
      const course = await storage.getCourse(req.params.id);
      if (!course) {
        return res.status(404).json({ message: "Course not found" });
      }
      res.json(course);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch course" });
    }
  });

  app.post("/api/courses", async (req, res) => {
    try {
      const courseData = insertCourseSchema.parse(req.body);
      
      // Get default establishment if not provided
      let establishmentId = courseData.establishmentId;
      if (!establishmentId) {
        const establishments = await storage.getAllEstablishments();
        const defaultEstablishment = establishments[0];
        if (!defaultEstablishment) {
          return res.status(500).json({ message: "No establishment configured" });
        }
        establishmentId = defaultEstablishment.id;
      }
      
      const course = await storage.createCourse({
        ...courseData,
        establishmentId
      });
      res.json(course);
    } catch (error) {
      res.status(400).json({ message: "Failed to create course" });
    }
  });

  // User course routes
  app.get("/api/users/:userId/courses", async (req, res) => {
    try {
      const userCourses = await storage.getUserCourses(req.params.userId);
      
      // Enrich with course details
      const coursesWithDetails = await Promise.all(
        userCourses.map(async (userCourse) => {
          if (!userCourse.courseId) {
            return { ...userCourse, course: null };
          }
          const course = await storage.getCourse(userCourse.courseId);
          return {
            ...userCourse,
            course,
          };
        })
      );
      
      res.json(coursesWithDetails);
    } catch (error) {
      res.status(500).json({ message: "Failed to fetch user courses" });
    }
  });

  app.post("/api/users/:userId/courses", async (req, res) => {
    try {
      const enrollmentData = insertUserCourseSchema.parse({
        userId: req.params.userId,
        courseId: req.body.courseId,
      });
      
      const enrollment = await storage.enrollUserInCourse(enrollmentData);
      res.json(enrollment);
    } catch (error) {
      res.status(400).json({ message: "Failed to enroll in course" });
    }
  });

  app.patch("/api/users/:userId/courses/:courseId/progress", async (req, res) => {
    try {
      const { progress } = req.body;
      
      if (typeof progress !== 'number' || progress < 0 || progress > 100) {
        return res.status(400).json({ message: "Progress must be a number between 0 and 100" });
      }
      
      const userCourse = await storage.updateCourseProgress(
        req.params.userId,
        req.params.courseId,
        progress
      );
      
      if (!userCourse) {
        return res.status(404).json({ message: "Enrollment not found" });
      }
      
      res.json(userCourse);
    } catch (error) {
      res.status(500).json({ message: "Failed to update progress" });
    }
  });

  // Admin routes pour la personnalisation
  app.get("/api/admin/themes", async (req, res) => {
    try {
      // Get default establishment
      const establishments = await storage.getAllEstablishments();
      const defaultEstablishment = establishments[0];
      
      if (!defaultEstablishment) {
        return res.status(500).json({ message: "No establishment configured" });
      }

      const themes = await storage.getThemesByEstablishment(defaultEstablishment.id);
      res.json(themes);
    } catch (error) {
      console.error("Error fetching themes:", error);
      res.status(500).json({ message: "Failed to fetch themes" });
    }
  });

  app.post("/api/admin/themes", async (req, res) => {
    try {
      // Get default establishment
      const establishments = await storage.getAllEstablishments();
      const defaultEstablishment = establishments[0];
      
      if (!defaultEstablishment) {
        return res.status(500).json({ message: "No establishment configured" });
      }

      const themeData = insertSimpleThemeSchema.parse({
        ...req.body,
        establishmentId: defaultEstablishment.id
      });
      
      const theme = await storage.createTheme(themeData);
      res.json(theme);
    } catch (error) {
      console.error("Error creating theme:", error);
      res.status(400).json({ message: "Failed to create theme" });
    }
  });

  app.post("/api/admin/themes/:themeId/activate", async (req, res) => {
    try {
      // Get default establishment
      const establishments = await storage.getAllEstablishments();
      const defaultEstablishment = establishments[0];
      
      if (!defaultEstablishment) {
        return res.status(500).json({ message: "No establishment configured" });
      }

      await storage.activateTheme(req.params.themeId, defaultEstablishment.id);
      res.json({ success: true });
    } catch (error) {
      console.error("Error activating theme:", error);
      res.status(500).json({ message: "Failed to activate theme" });
    }
  });

  app.get("/api/admin/customizable-contents", async (req, res) => {
    try {
      // Get default establishment
      const establishments = await storage.getAllEstablishments();
      const defaultEstablishment = establishments[0];
      
      if (!defaultEstablishment) {
        return res.status(500).json({ message: "No establishment configured" });
      }

      const contents = await storage.getCustomizableContents(defaultEstablishment.id);
      res.json(contents);
    } catch (error) {
      console.error("Error fetching customizable contents:", error);
      res.status(500).json({ message: "Failed to fetch customizable contents" });
    }
  });

  app.patch("/api/admin/customizable-contents/:contentId", async (req, res) => {
    try {
      const { content } = req.body;
      const updatedContent = await storage.updateCustomizableContent(req.params.contentId, { content });
      
      if (!updatedContent) {
        return res.status(404).json({ message: "Content not found" });
      }
      
      res.json(updatedContent);
    } catch (error) {
      console.error("Error updating customizable content:", error);
      res.status(500).json({ message: "Failed to update customizable content" });
    }
  });

  app.get("/api/admin/menu-items", async (req, res) => {
    try {
      // Get default establishment
      const establishments = await storage.getAllEstablishments();
      const defaultEstablishment = establishments[0];
      
      if (!defaultEstablishment) {
        return res.status(500).json({ message: "No establishment configured" });
      }

      const menuItems = await storage.getMenuItems(defaultEstablishment.id);
      res.json(menuItems);
    } catch (error) {
      console.error("Error fetching menu items:", error);
      res.status(500).json({ message: "Failed to fetch menu items" });
    }
  });

  // WYSIWYG API routes
  app.get("/api/admin/pages", async (req, res) => {
    try {
      const establishments = await storage.getAllEstablishments();
      const defaultEstablishment = establishments[0];
      
      if (!defaultEstablishment) {
        return res.status(500).json({ message: "No establishment configured" });
      }

      const pages = await storage.getCustomizablePages(defaultEstablishment.id);
      res.json(pages);
    } catch (error) {
      console.error("Error fetching pages:", error);
      res.status(500).json({ message: "Failed to fetch pages" });
    }
  });

  app.get("/api/admin/pages/:pageName", async (req, res) => {
    try {
      const establishments = await storage.getAllEstablishments();
      const defaultEstablishment = establishments[0];
      
      if (!defaultEstablishment) {
        return res.status(500).json({ message: "No establishment configured" });
      }

      const page = await storage.getCustomizablePageByName(defaultEstablishment.id, req.params.pageName);
      
      if (!page) {
        // Créer une page par défaut si elle n'existe pas
        const defaultPageData = {
          establishmentId: defaultEstablishment.id,
          pageName: req.params.pageName,
          pageTitle: `Page ${req.params.pageName}`,
          pageDescription: `Description de la page ${req.params.pageName}`,
          layout: {
            sections: [
              { type: "header", components: [] },
              { type: "body", components: [] },
              { type: "footer", components: [] }
            ]
          }
        };
        
        const newPage = await storage.createCustomizablePage(defaultPageData);
        return res.json(newPage);
      }
      
      res.json(page);
    } catch (error) {
      console.error("Error fetching page:", error);
      res.status(500).json({ message: "Failed to fetch page" });
    }
  });

  app.patch("/api/admin/pages/:pageName", async (req, res) => {
    try {
      const establishments = await storage.getAllEstablishments();
      const defaultEstablishment = establishments[0];
      
      if (!defaultEstablishment) {
        return res.status(500).json({ message: "No establishment configured" });
      }

      const existingPage = await storage.getCustomizablePageByName(defaultEstablishment.id, req.params.pageName);
      
      if (!existingPage) {
        return res.status(404).json({ message: "Page not found" });
      }

      const updatedPage = await storage.updateCustomizablePage(existingPage.id, req.body);
      res.json(updatedPage);
    } catch (error) {
      console.error("Error updating page:", error);
      res.status(500).json({ message: "Failed to update page" });
    }
  });

  app.get("/api/admin/components", async (req, res) => {
    try {
      const establishments = await storage.getAllEstablishments();
      const defaultEstablishment = establishments[0];
      
      if (!defaultEstablishment) {
        return res.status(500).json({ message: "No establishment configured" });
      }

      const components = await storage.getPageComponents(defaultEstablishment.id);
      res.json(components);
    } catch (error) {
      console.error("Error fetching components:", error);
      res.status(500).json({ message: "Failed to fetch components" });
    }
  });

  // ===== ADMIN ROUTES FOR MULTI-ESTABLISHMENT =====
  
  // Get all establishments
  app.get("/api/admin/establishments", async (req, res) => {
    try {
      const establishments = await storage.getAllEstablishments();
      res.json(establishments);
    } catch (error) {
      console.error("Error fetching establishments:", error);
      res.status(500).json({ message: "Failed to fetch establishments" });
    }
  });

  // Get all users
  app.get("/api/admin/users", async (req, res) => {
    try {
      // For now, get users from default establishment
      const establishments = await storage.getAllEstablishments();
      const defaultEstablishment = establishments[0];
      if (!defaultEstablishment) {
        return res.json([]);
      }
      
      const users = await storage.getUsersByEstablishment(defaultEstablishment.id);
      res.json(users);
    } catch (error) {
      console.error("Error fetching users:", error);
      res.status(500).json({ message: "Failed to fetch users" });
    }
  });

  // Create new establishment
  app.post("/api/admin/establishments", async (req, res) => {
    try {
      const establishmentData = insertEstablishmentSchema.parse(req.body);
      const establishment = await storage.createEstablishment(establishmentData);
      res.json(establishment);
    } catch (error) {
      console.error("Error creating establishment:", error);
      res.status(400).json({ message: "Failed to create establishment" });
    }
  });

  // Create new user
  app.post("/api/admin/users", async (req, res) => {
    try {
      if (!req.session?.userId) {
        return res.status(401).json({ message: "Non authentifié" });
      }

      const result = insertUserSchema.safeParse(req.body);
      if (!result.success) {
        return res.status(400).json({ 
          message: "Données invalides",
          errors: result.error.issues 
        });
      }

      // Check if user already exists
      const existingUser = await storage.getUserByEmail(result.data.email, result.data.establishmentId || '');
      if (existingUser) {
        return res.status(400).json({ message: "Un utilisateur existe déjà avec cet email dans cet établissement" });
      }

      const user = await storage.createUser(result.data);
      res.status(201).json(user);
    } catch (error) {
      console.error("Error creating user:", error);
      res.status(500).json({ message: "Erreur lors de la création de l'utilisateur" });
    }
  });

  // ========== ROUTES D'ADMINISTRATION HIÉRARCHIQUE ==========

  // Route Super Admin uniquement - Gestion globale des établissements
  app.get('/api/super-admin/establishments', requireSuperAdmin, async (req: any, res) => {
    try {
      const establishments = await establishmentService.getAllEstablishments();
      
      // Ajouter les statistiques pour chaque établissement
      const establishmentsWithStats = await Promise.all(
        establishments.map(async (establishment) => {
          try {
            const stats = await establishmentService.getEstablishmentStats(establishment.id);
            return { ...establishment, stats };
          } catch (error) {
            console.error(`Error getting stats for establishment ${establishment.id}:`, error);
            return { 
              ...establishment, 
              stats: { users: 0, courses: 0, themes: 0 } 
            };
          }
        })
      );
      
      res.json(establishmentsWithStats);
    } catch (error) {
      console.error("Error fetching establishments for super admin:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des établissements" });
    }
  });

  // Route Super Admin - Créer un nouvel établissement
  app.post('/api/super-admin/establishments', requireSuperAdmin, async (req: any, res) => {
    try {
      const establishmentData = insertEstablishmentSchema.parse(req.body);
      
      // Créer l'établissement avec sa base de données dédiée
      const sanitizedData = {
        ...establishmentData,
        description: establishmentData.description || undefined,
        logo: establishmentData.logo || undefined,
        domain: establishmentData.domain || undefined
      };
      const establishment = await establishmentService.createEstablishment(sanitizedData);

      res.status(201).json({
        ...establishment,
        message: 'Établissement créé avec succès avec base de données dédiée'
      });
    } catch (error) {
      console.error("Error creating establishment:", error);
      res.status(400).json({ message: "Erreur lors de la création de l'établissement" });
    }
  });

  // Route Super Admin - Gestion globale des utilisateurs de tous les établissements
  app.get('/api/super-admin/users', requireSuperAdmin, async (req: any, res) => {
    try {
      // Récupérer tous les utilisateurs de tous les établissements
      const establishments = await establishmentService.getAllEstablishments();
      const allUsers = [];
      
      for (const establishment of establishments) {
        try {
          const establishmentDb = await establishmentService.getEstablishmentDatabase(establishment.id);
          const users = await establishmentDb
            .select()
            .from(schema.users)
            .where(eq(schema.users.establishmentId, establishment.id));
          
          // Ajouter le nom de l'établissement à chaque utilisateur
          const usersWithEstablishment = users.map(user => ({
            ...user,
            establishmentName: establishment.name
          }));
          
          allUsers.push(...usersWithEstablishment);
        } catch (error) {
          console.error(`Error fetching users for establishment ${establishment.id}:`, error);
        }
      }
      
      res.json(allUsers);
    } catch (error) {
      console.error("Error fetching all users for super admin:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des utilisateurs" });
    }
  });

  // Route Super Admin - Créer un administrateur pour un établissement
  app.post('/api/super-admin/establishment-admin', requireSuperAdmin, async (req: any, res) => {
    try {
      const userData = {
        ...insertUserSchema.parse(req.body),
        role: 'admin' as const
      };
      
      // Créer l'administrateur dans la base de données de l'établissement spécifique
      const establishmentDb = await establishmentService.getEstablishmentDatabase(userData.establishmentId!);
      
      const [user] = await establishmentDb
        .insert(schema.users)
        .values({
          ...userData,
          createdAt: new Date(),
          updatedAt: new Date()
        })
        .returning();
      
      res.json(user);
    } catch (error) {
      console.error("Error creating establishment admin:", error);
      res.status(400).json({ message: "Erreur lors de la création de l'administrateur" });
    }
  });

  // Route Super Admin - Mettre à jour un établissement
  app.put('/api/super-admin/establishments/:id', requireSuperAdmin, async (req: any, res) => {
    try {
      const { id } = req.params;
      const validation = insertEstablishmentSchema.partial().safeParse(req.body);
      
      if (!validation.success) {
        return res.status(400).json({ error: 'Invalid establishment data', details: validation.error });
      }

      const sanitizedUpdateData = {
        ...validation.data,
        description: validation.data.description === null ? undefined : validation.data.description,
        logo: validation.data.logo === null ? undefined : validation.data.logo,
        domain: validation.data.domain === null ? undefined : validation.data.domain
      };
      const establishment = await establishmentService.updateEstablishment(id, sanitizedUpdateData);
      
      if (!establishment) {
        return res.status(404).json({ error: 'Establishment not found' });
      }

      res.json(establishment);
    } catch (error) {
      console.error('Error updating establishment:', error);
      res.status(500).json({ error: 'Failed to update establishment' });
    }
  });

  // Route Super Admin - Supprimer un établissement
  app.delete('/api/super-admin/establishments/:id', requireSuperAdmin, async (req: any, res) => {
    try {
      const { id } = req.params;
      const establishment = await establishmentService.deleteEstablishment(id);
      
      if (!establishment) {
        return res.status(404).json({ error: 'Establishment not found' });
      }

      res.json({ message: 'Establishment deleted successfully', establishment });
    } catch (error) {
      console.error('Error deleting establishment:', error);
      res.status(500).json({ error: 'Failed to delete establishment' });
    }
  });

  // ===== ROUTES DE GESTION DES UTILISATEURS =====

  // Route Super Admin - Créer un utilisateur dans n'importe quel établissement
  app.post('/api/super-admin/users', requireSuperAdmin, async (req: any, res) => {
    try {
      const userData = insertUserSchema.parse(req.body);
      
      if (!userData.establishmentId) {
        return res.status(400).json({ message: "L'ID de l'établissement est requis" });
      }

      const establishmentDb = await establishmentService.getEstablishmentDatabase(userData.establishmentId);
      
      const [user] = await establishmentDb
        .insert(schema.users)
        .values({
          ...userData,
          createdAt: new Date(),
          updatedAt: new Date()
        })
        .returning();
      
      res.json(user);
    } catch (error) {
      console.error("Error creating user:", error);
      res.status(400).json({ message: "Erreur lors de la création de l'utilisateur" });
    }
  });

  // Route Super Admin - Mettre à jour un utilisateur
  app.put('/api/super-admin/users/:id', requireSuperAdmin, async (req: any, res) => {
    try {
      const { id } = req.params;
      const updateData = insertUserSchema.partial().parse(req.body);
      
      // Trouver l'utilisateur dans tous les établissements
      const establishments = await establishmentService.getAllEstablishments();
      let user = null;
      let targetEstablishmentDb = null;
      
      for (const establishment of establishments) {
        try {
          const establishmentDb = await establishmentService.getEstablishmentDatabase(establishment.id);
          const [foundUser] = await establishmentDb
            .select()
            .from(schema.users)
            .where(eq(schema.users.id, id));
          
          if (foundUser) {
            user = foundUser;
            targetEstablishmentDb = establishmentDb;
            break;
          }
        } catch (error) {
          continue;
        }
      }
      
      if (!user || !targetEstablishmentDb) {
        return res.status(404).json({ message: "Utilisateur non trouvé" });
      }
      
      const [updatedUser] = await targetEstablishmentDb
        .update(schema.users)
        .set({
          ...updateData,
          updatedAt: new Date()
        })
        .where(eq(schema.users.id, id))
        .returning();
      
      res.json(updatedUser);
    } catch (error) {
      console.error("Error updating user:", error);
      res.status(400).json({ message: "Erreur lors de la mise à jour de l'utilisateur" });
    }
  });

  // Route Super Admin - Supprimer un utilisateur
  app.delete('/api/super-admin/users/:id', requireSuperAdmin, async (req: any, res) => {
    try {
      const { id } = req.params;
      
      // Trouver l'utilisateur dans tous les établissements
      const establishments = await establishmentService.getAllEstablishments();
      let deleted = false;
      
      for (const establishment of establishments) {
        try {
          const establishmentDb = await establishmentService.getEstablishmentDatabase(establishment.id);
          const result = await establishmentDb
            .delete(schema.users)
            .where(eq(schema.users.id, id))
            .returning();
          
          if (result.length > 0) {
            deleted = true;
            break;
          }
        } catch (error) {
          continue;
        }
      }
      
      if (!deleted) {
        return res.status(404).json({ message: "Utilisateur non trouvé" });
      }
      
      res.json({ message: "Utilisateur supprimé avec succès" });
    } catch (error) {
      console.error("Error deleting user:", error);
      res.status(500).json({ message: "Erreur lors de la suppression de l'utilisateur" });
    }
  });

  // Route Admin - Gestion des utilisateurs de son établissement
  app.get('/api/users', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser) {
        return res.status(401).json({ message: "Utilisateur non authentifié" });
      }

      if (!["super_admin", "admin", "manager"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const users = await storage.getUsersByEstablishment(currentUser.establishmentId!);
      res.json(users);
    } catch (error) {
      console.error("Error fetching users:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des utilisateurs" });
    }
  });

  // Route Admin - Modifier un utilisateur de son établissement
  app.put('/api/admin/users/:id', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { id } = req.params;
      const updateData = insertUserSchema.partial().parse(req.body);
      
      const updatedUser = await storage.updateUser(id, updateData);
      if (!updatedUser) {
        return res.status(404).json({ message: "Utilisateur non trouvé" });
      }

      res.json(updatedUser);
    } catch (error) {
      console.error("Error updating user:", error);
      res.status(500).json({ message: "Erreur lors de la mise à jour de l'utilisateur" });
    }
  });

  // Route Admin - Supprimer un utilisateur de son établissement
  app.delete('/api/admin/users/:id', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { id } = req.params;
      await storage.deleteUser(id);
      res.json({ message: "Utilisateur supprimé avec succès" });
    } catch (error) {
      console.error("Error deleting user:", error);
      res.status(500).json({ message: "Erreur lors de la suppression de l'utilisateur" });
    }
  });

  // ===== ROUTES PERMISSIONS ET RÔLES (Priorité 1) =====

  // Get all permissions
  app.get('/api/admin/permissions', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const permissions = await storage.getAllPermissions();
      res.json(permissions);
    } catch (error) {
      console.error("Error fetching permissions:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des permissions" });
    }
  });

  // Get permissions for a specific role
  app.get('/api/admin/roles/:role/permissions', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { role } = req.params;
      const permissions = await storage.getRolePermissions(role);
      res.json(permissions);
    } catch (error) {
      console.error("Error fetching role permissions:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des permissions du rôle" });
    }
  });

  // Assign permissions to a role
  app.post('/api/admin/roles/:role/permissions', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { role } = req.params;
      const { permissionIds } = req.body;
      
      if (!Array.isArray(permissionIds)) {
        return res.status(400).json({ message: "permissionIds doit être un tableau" });
      }

      await storage.assignRolePermissions(role, permissionIds);
      res.json({ message: "Permissions assignées au rôle avec succès" });
    } catch (error) {
      console.error("Error assigning role permissions:", error);
      res.status(500).json({ message: "Erreur lors de l'assignation des permissions" });
    }
  });

  // Get user permissions
  app.get('/api/admin/users/:userId/permissions', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { userId } = req.params;
      const permissions = await storage.getUserPermissions(userId);
      res.json(permissions);
    } catch (error) {
      console.error("Error fetching user permissions:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des permissions utilisateur" });
    }
  });

  // Assign permissions to a user
  app.post('/api/admin/users/:userId/permissions', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { userId } = req.params;
      const { permissionIds } = req.body;
      
      if (!Array.isArray(permissionIds)) {
        return res.status(400).json({ message: "permissionIds doit être un tableau" });
      }

      await storage.assignUserPermissions(userId, permissionIds);
      res.json({ message: "Permissions assignées à l'utilisateur avec succès" });
    } catch (error) {
      console.error("Error assigning user permissions:", error);
      res.status(500).json({ message: "Erreur lors de l'assignation des permissions utilisateur" });
    }
  });

  // ===== ROUTES CRUD COURS (Finalisation) =====

  // Update course
  app.put('/api/courses/:id', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager", "formateur"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { id } = req.params;
      const updateData = insertCourseSchema.partial().parse(req.body);
      
      const updatedCourse = await storage.updateCourse(id, updateData);
      if (!updatedCourse) {
        return res.status(404).json({ message: "Cours non trouvé" });
      }

      res.json(updatedCourse);
    } catch (error) {
      console.error("Error updating course:", error);
      res.status(500).json({ message: "Erreur lors de la mise à jour du cours" });
    }
  });

  // Delete course (soft delete)
  app.delete('/api/courses/:id', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { id } = req.params;
      await storage.deleteCourse(id);
      res.json({ message: "Cours supprimé avec succès" });
    } catch (error) {
      console.error("Error deleting course:", error);
      res.status(500).json({ message: "Erreur lors de la suppression du cours" });
    }
  });

  // ===== ROUTES MODULES DE COURS (Priorité 2) =====

  // Get modules for a course
  app.get('/api/courses/:courseId/modules', requireAuth, async (req: any, res) => {
    try {
      const { courseId } = req.params;
      const modules = await storage.getCourseModules(courseId);
      res.json(modules);
    } catch (error) {
      console.error("Error fetching course modules:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des modules" });
    }
  });

  // Create a new module for a course
  app.post('/api/courses/:courseId/modules', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager", "formateur"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { courseId } = req.params;
      const moduleData = {
        ...req.body,
        courseId,
        createdBy: req.session.userId
      };

      const module = await storage.createCourseModule(moduleData);
      res.status(201).json(module);
    } catch (error) {
      console.error("Error creating course module:", error);
      res.status(500).json({ message: "Erreur lors de la création du module" });
    }
  });

  // Update a course module
  app.put('/api/courses/:courseId/modules/:moduleId', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager", "formateur"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { moduleId } = req.params;
      const updateData = req.body;

      const updatedModule = await storage.updateCourseModule(moduleId, updateData);
      if (!updatedModule) {
        return res.status(404).json({ message: "Module non trouvé" });
      }

      res.json(updatedModule);
    } catch (error) {
      console.error("Error updating course module:", error);
      res.status(500).json({ message: "Erreur lors de la mise à jour du module" });
    }
  });

  // Delete a course module
  app.delete('/api/courses/:courseId/modules/:moduleId', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager", "formateur"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { moduleId } = req.params;
      const success = await storage.deleteCourseModule(moduleId);
      
      if (!success) {
        return res.status(404).json({ message: "Module non trouvé" });
      }

      res.json({ message: "Module supprimé avec succès" });
    } catch (error) {
      console.error("Error deleting course module:", error);
      res.status(500).json({ message: "Erreur lors de la suppression du module" });
    }
  });

  // Get user progress for course modules
  app.get('/api/users/:userId/courses/:courseId/modules/progress', requireAuth, async (req: any, res) => {
    try {
      const { userId, courseId } = req.params;
      const progress = await storage.getUserModuleProgress(userId, courseId);
      res.json(progress);
    } catch (error) {
      console.error("Error fetching module progress:", error);
      res.status(500).json({ message: "Erreur lors de la récupération de la progression" });
    }
  });

  // Update user progress for a specific module
  app.put('/api/users/:userId/modules/:moduleId/progress', requireAuth, async (req: any, res) => {
    try {
      const { userId, moduleId } = req.params;
      const progressData = req.body;

      const updatedProgress = await storage.updateModuleProgress(userId, moduleId, progressData);
      res.json(updatedProgress);
    } catch (error) {
      console.error("Error updating module progress:", error);
      res.status(500).json({ message: "Erreur lors de la mise à jour de la progression" });
    }
  });

  // Get module progress summary for a course
  app.get('/api/users/:userId/courses/:courseId/progress-summary', requireAuth, async (req: any, res) => {
    try {
      const { userId, courseId } = req.params;
      const summary = await storage.getModuleProgressSummary(userId, courseId);
      res.json(summary);
    } catch (error) {
      console.error("Error fetching progress summary:", error);
      res.status(500).json({ message: "Erreur lors de la récupération du résumé de progression" });
    }
  });

  // ===== ROUTES GESTION DES MENUS (Finalisation) =====

  // Create menu item
  app.post('/api/admin/menu-items', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      // Get default establishment
      const establishments = await storage.getAllEstablishments();
      const defaultEstablishment = establishments[0];
      
      if (!defaultEstablishment) {
        return res.status(500).json({ message: "No establishment configured" });
      }

      const menuItemData = {
        ...req.body,
        establishmentId: defaultEstablishment.id
      };

      const menuItem = await storage.createMenuItem(menuItemData);
      res.status(201).json(menuItem);
    } catch (error) {
      console.error("Error creating menu item:", error);
      res.status(500).json({ message: "Erreur lors de la création de l'élément de menu" });
    }
  });

  // Update menu item
  app.put('/api/admin/menu-items/:id', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { id } = req.params;
      const updateData = req.body;

      const updatedMenuItem = await storage.updateMenuItem(id, updateData);
      if (!updatedMenuItem) {
        return res.status(404).json({ message: "Élément de menu non trouvé" });
      }

      res.json(updatedMenuItem);
    } catch (error) {
      console.error("Error updating menu item:", error);
      res.status(500).json({ message: "Erreur lors de la mise à jour de l'élément de menu" });
    }
  });

  // Delete menu item
  app.delete('/api/admin/menu-items/:id', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { id } = req.params;
      await storage.deleteMenuItem(id);
      res.json({ message: "Élément de menu supprimé avec succès" });
    } catch (error) {
      console.error("Error deleting menu item:", error);
      res.status(500).json({ message: "Erreur lors de la suppression de l'élément de menu" });
    }
  });

  // ===== ROUTES PLUGINS ÉDUCATIFS (Priorité 2) =====

  // Get educational plugins for establishment
  app.get('/api/admin/plugins', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const plugins = await storage.getEducationalPlugins(currentUser.establishmentId!);
      res.json(plugins);
    } catch (error) {
      console.error("Error fetching plugins:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des plugins" });
    }
  });

  // Create educational plugin
  app.post('/api/admin/plugins', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const pluginData = {
        ...req.body,
        establishmentId: currentUser.establishmentId,
        uploadedBy: req.session.userId
      };

      const plugin = await storage.createEducationalPlugin(pluginData);
      res.status(201).json(plugin);
    } catch (error) {
      console.error("Error creating plugin:", error);
      res.status(500).json({ message: "Erreur lors de la création du plugin" });
    }
  });

  // Update educational plugin
  app.put('/api/admin/plugins/:id', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { id } = req.params;
      const updateData = req.body;

      const updatedPlugin = await storage.updateEducationalPlugin(id, updateData);
      if (!updatedPlugin) {
        return res.status(404).json({ message: "Plugin non trouvé" });
      }

      res.json(updatedPlugin);
    } catch (error) {
      console.error("Error updating plugin:", error);
      res.status(500).json({ message: "Erreur lors de la mise à jour du plugin" });
    }
  });

  // Toggle plugin status
  app.patch('/api/admin/plugins/:id/toggle', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { id } = req.params;
      const { isActive } = req.body;

      const updatedPlugin = await storage.togglePluginStatus(id, isActive);
      if (!updatedPlugin) {
        return res.status(404).json({ message: "Plugin non trouvé" });
      }

      res.json(updatedPlugin);
    } catch (error) {
      console.error("Error toggling plugin status:", error);
      res.status(500).json({ message: "Erreur lors de la modification du statut du plugin" });
    }
  });

  // Route Admin - Créer un utilisateur dans son établissement
  app.post('/api/users', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const userData = {
        ...insertUserSchema.parse(req.body),
        establishmentId: currentUser.establishmentId
      };

      const establishmentDb = await establishmentService.getEstablishmentDatabase(currentUser.establishmentId!);
      
      const [user] = await establishmentDb
        .insert(schema.users)
        .values({
          ...userData,
          createdAt: new Date(),
          updatedAt: new Date()
        })
        .returning();
      
      res.json(user);
    } catch (error) {
      console.error("Error creating user:", error);
      res.status(400).json({ message: "Erreur lors de la création de l'utilisateur" });
    }
  });

  // Route Admin - Mettre à jour un utilisateur de son établissement
  app.put('/api/users/:id', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { id } = req.params;
      const updateData = insertUserSchema.partial().parse(req.body);

      const establishmentDb = await establishmentService.getEstablishmentDatabase(currentUser.establishmentId!);
      
      const [updatedUser] = await establishmentDb
        .update(schema.users)
        .set({
          ...updateData,
          updatedAt: new Date()
        })
        .where(eq(schema.users.id, id))
        .returning();
      
      if (!updatedUser) {
        return res.status(404).json({ message: "Utilisateur non trouvé" });
      }
      
      res.json(updatedUser);
    } catch (error) {
      console.error("Error updating user:", error);
      res.status(400).json({ message: "Erreur lors de la mise à jour de l'utilisateur" });
    }
  });

  // Route Admin - Supprimer un utilisateur de son établissement
  app.delete('/api/users/:id', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["super_admin", "admin", "manager"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { id } = req.params;

      const establishmentDb = await establishmentService.getEstablishmentDatabase(currentUser.establishmentId!);
      
      const result = await establishmentDb
        .delete(schema.users)
        .where(eq(schema.users.id, id))
        .returning();
      
      if (result.length === 0) {
        return res.status(404).json({ message: "Utilisateur non trouvé" });
      }
      
      res.json({ message: "Utilisateur supprimé avec succès" });
    } catch (error) {
      console.error("Error deleting user:", error);
      res.status(500).json({ message: "Erreur lors de la suppression de l'utilisateur" });
    }
  });

  // Routes Super Admin - Personnalisation du portail
  app.get('/api/super-admin/portal-contents', requireSuperAdmin, async (req: any, res) => {
    try {
      // Configuration par défaut du contenu du portail
      const defaultPortalContent = [
        {
          id: 'hero_title',
          blockKey: 'hero_title',
          blockType: 'text',
          content: 'Bienvenue sur StacGateLMS',
          isActive: true
        },
        {
          id: 'hero_subtitle',
          blockKey: 'hero_subtitle',
          blockType: 'text',
          content: 'Découvrez notre écosystème éducatif multi-établissements',
          isActive: true
        },
        {
          id: 'hero_description',
          blockKey: 'hero_description',
          blockType: 'textarea',
          content: 'Choisissez votre établissement pour accéder à une expérience d\'apprentissage personnalisée et de qualité.',
          isActive: true
        },
        {
          id: 'cta_text',
          blockKey: 'cta_text',
          blockType: 'text',
          content: 'Explorer les établissements',
          isActive: true
        }
      ];
      
      // Forcer pas de cache
      res.set('Cache-Control', 'no-cache, no-store, must-revalidate');
      res.set('Pragma', 'no-cache');
      res.set('Expires', '0');
      
      res.json(defaultPortalContent);
    } catch (error) {
      console.error('Error fetching portal contents:', error);
      res.status(500).json({ message: 'Erreur lors de la récupération du contenu du portail' });
    }
  });

  app.post('/api/super-admin/portal-content', requireSuperAdmin, async (req: any, res) => {
    try {
      // Pour l'instant, on simule la sauvegarde
      // Dans une implémentation complète, on sauvegarderait dans une table portal_contents
      res.json({ message: 'Contenu du portail sauvegardé avec succès' });
    } catch (error) {
      console.error('Error saving portal content:', error);
      res.status(500).json({ message: 'Erreur lors de la sauvegarde du contenu du portail' });
    }
  });

  app.get('/api/super-admin/portal-themes', requireSuperAdmin, async (req: any, res) => {
    try {
      // Configuration par défaut des thèmes du portail
      const defaultPortalThemes = [
        {
          id: 'portal_theme_default',
          name: 'Thème Bleu Corporate',
          isActive: true,
          primaryColor: '#6366f1',
          secondaryColor: '#8b5cf6',
          accentColor: '#10b981',
          backgroundColor: '#ffffff',
          textColor: '#1f2937',
          fontFamily: 'Inter',
          fontSize: '16px'
        },
        {
          id: 'portal_theme_green',
          name: 'Thème Vert Nature',
          isActive: false,
          primaryColor: '#16a34a',
          secondaryColor: '#84cc16',
          accentColor: '#06b6d4',  
          backgroundColor: '#ffffff',
          textColor: '#1f2937',
          fontFamily: 'Roboto',
          fontSize: '16px'
        }
      ];
      
      res.json(defaultPortalThemes);
    } catch (error) {
      console.error("Error fetching portal themes:", error);
      res.status(500).json({ message: "Failed to fetch portal themes" });
    }
  });

  app.post('/api/super-admin/portal-themes', requireSuperAdmin, async (req: any, res) => {
    try {
      const themeData = req.body;
      // Créer un nouveau thème avec un ID unique
      const newTheme = {
        id: `portal_theme_${Date.now()}`,
        ...themeData,
        isActive: false
      };
      
      // Dans une vraie app, on sauvegarderait en base
      res.status(201).json(newTheme);
    } catch (error) {
      console.error("Error creating portal theme:", error);
      res.status(500).json({ message: "Failed to create portal theme" });
    }
  });

  app.post('/api/super-admin/portal-themes/:id/activate', requireSuperAdmin, async (req: any, res) => {
    try {
      const { id } = req.params;
      // Dans une vraie app, on activerait le thème en base
      res.json({ message: "Theme activated successfully" });
    } catch (error) {
      console.error("Error activating portal theme:", error);
      res.status(500).json({ message: "Failed to activate portal theme" });
    }
  });

  app.patch('/api/super-admin/portal-contents/:id', requireSuperAdmin, async (req: any, res) => {
    try {
      const { id } = req.params;
      const { content } = req.body;
      
      // Dans une vraie app, on mettrait à jour le contenu en base
      const updatedContent = {
        id,
        blockKey: id,
        blockType: 'text',
        content,
        isActive: true
      };
      
      res.json(updatedContent);
    } catch (error) {
      console.error("Error updating portal content:", error);
      res.status(500).json({ message: "Failed to update portal content" });
    }
  });

  app.get('/api/super-admin/portal-menu-items', requireSuperAdmin, async (req: any, res) => {
    try {
      // Configuration par défaut des éléments de menu du portail
      const defaultPortalMenuItems = [
        {
          id: 'menu_home',
          label: 'Accueil',
          url: '/',
          icon: 'home',
          parentId: null,
          sortOrder: 1,
          isActive: true,
          permissions: {}
        },
        {
          id: 'menu_establishments',
          label: 'Établissements',
          url: '/establishments',
          icon: 'building',
          parentId: null,
          sortOrder: 2,
          isActive: true,
          permissions: {}
        },
        {
          id: 'menu_about',
          label: 'À propos',
          url: '/about',
          icon: 'info',
          parentId: null,
          sortOrder: 3,
          isActive: true,
          permissions: {}
        },
        {
          id: 'menu_contact',
          label: 'Contact',
          url: '/contact',
          icon: 'mail',
          parentId: null,
          sortOrder: 4,
          isActive: true,
          permissions: {}
        }
      ];
      
      res.json(defaultPortalMenuItems);
    } catch (error) {
      console.error("Error fetching portal menu items:", error);
      res.status(500).json({ message: "Failed to fetch portal menu items" });
    }
  });

  // Route Admin - Gestion des utilisateurs de son établissement uniquement
  app.get('/api/admin/establishment/:establishmentId/users', requireEstablishmentAccess('establishmentId'), async (req: any, res) => {
    try {
      const { establishmentId } = req.params;
      const users = await storage.getUsersByEstablishment(establishmentId);
      res.json(users);
    } catch (error) {
      console.error("Error fetching establishment users:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des utilisateurs" });
    }
  });

  // Route Admin - Créer un utilisateur dans son établissement
  app.post('/api/admin/establishment/:establishmentId/users', requireEstablishmentAccess('establishmentId'), async (req: any, res) => {
    try {
      const { establishmentId } = req.params;
      const userData = {
        ...insertUserSchema.parse(req.body),
        establishmentId
      };
      const user = await storage.createUser(userData);
      res.json(user);
    } catch (error) {
      console.error("Error creating user in establishment:", error);
      res.status(400).json({ message: "Erreur lors de la création de l'utilisateur" });
    }
  });

  // Route pour obtenir les informations de rôle et permissions de l'utilisateur connecté
  app.get('/api/auth/permissions', requireAuth, async (req: any, res) => {
    try {
      const user = req.user;
      const permissions = {
        role: user.role,
        establishmentId: user.establishmentId,
        canManageAllEstablishments: user.role === 'super_admin',
        canManageOwnEstablishment: ['super_admin', 'admin'].includes(user.role),
        canManageUsers: ['super_admin', 'admin', 'manager'].includes(user.role)
      };
      res.json(permissions);
    } catch (error) {
      console.error("Error fetching user permissions:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des permissions" });
    }
  });

  // ===== ROUTES DE GESTION DES COURS =====
  
  // ===== Removed conflicting route - enhanced version available below =====

  // Créer un nouveau cours
  app.post('/api/courses', requireAuth, async (req: any, res) => {
    try {
      const user = req.user;
      
      // Vérifier les permissions (formateur, admin, manager)
      if (!['formateur', 'admin', 'manager'].includes(user.role)) {
        return res.status(403).json({ error: 'Permissions insuffisantes' });
      }
      
      // Utiliser la base de données principale pour l'instant
      const courseData = {
        title: req.body.title,
        description: req.body.description,
        category: req.body.category,
        level: req.body.level || 'debutant',
        duration: req.body.duration || 0,
        price: req.body.price || '0',
        instructorId: user.id,
        establishmentId: user.establishmentId,
        enrollmentCount: 0,
        rating: "0",
        isPublic: req.body.isPublic || false,
        isActive: true,
        createdAt: new Date(),
        updatedAt: new Date()
      };
      
      const [newCourse] = await db
        .insert(schema.courses)
        .values([courseData])
        .returning();
      
      res.status(201).json(newCourse);
    } catch (error) {
      console.error('Error creating course:', error);
      res.status(500).json({ error: 'Failed to create course' });
    }
  });

  // S'inscrire à un cours
  app.post('/api/courses/:courseId/enroll', requireAuth, async (req: any, res) => {
    try {
      const user = req.user;
      const { courseId } = req.params;
      const establishmentDb = await establishmentService.getEstablishmentDatabase(user.establishmentId);
      
      // Vérifier si le cours existe
      const [course] = await establishmentDb
        .select()
        .from(schema.courses)
        .where(eq(schema.courses.id, courseId));
      
      if (!course) {
        return res.status(404).json({ error: 'Cours non trouvé' });
      }
      
      // Vérifier si l'utilisateur n'est pas déjà inscrit
      const [existingEnrollment] = await establishmentDb
        .select()
        .from(schema.user_courses)
        .where(
          and(
            eq(schema.user_courses.userId, user.id),
            eq(schema.user_courses.courseId, courseId)
          )
        );
      
      if (existingEnrollment) {
        return res.status(400).json({ error: 'Déjà inscrit à ce cours' });
      }
      
      // Créer l'inscription
      const [enrollment] = await establishmentDb
        .insert(schema.user_courses)
        .values({
          userId: user.id,
          courseId: courseId,
          enrolledAt: new Date(),
          progress: 0
        })
        .returning();
      
      // Mettre à jour le nombre d'inscrits
      await establishmentDb
        .update(schema.courses)
        .set({ 
          enrollmentCount: sql`${schema.courses.enrollmentCount} + 1`,
          updatedAt: new Date()
        })
        .where(eq(schema.courses.id, courseId));
      
      res.status(201).json(enrollment);
    } catch (error) {
      console.error('Error enrolling in course:', error);
      res.status(500).json({ error: 'Failed to enroll in course' });
    }
  });

  // Récupérer les sessions de formation
  app.get('/api/training-sessions', requireAuth, async (req: any, res) => {
    try {
      const user = req.user;
      const establishmentDb = await establishmentService.getEstablishmentDatabase(user.establishmentId);
      
      const sessions = await establishmentDb
        .select()
        .from(schema.training_sessions)
        .where(eq(schema.training_sessions.courseId, sql`${schema.courses.id}`))
        .orderBy(schema.training_sessions.startDate);
      
      res.json(sessions);
    } catch (error) {
      console.error('Error fetching training sessions:', error);
      res.status(500).json({ error: 'Failed to fetch training sessions' });
    }
  });

  // ===== ROUTES DES ÉVALUATIONS =====
  
  // Routes des évaluations implémentées avec Storage
  app.get('/api/assessments', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !currentUser.establishmentId) {
        return res.status(401).json({ message: "Utilisateur non authentifié ou sans établissement" });
      }

      const assessments = await storage.getAssessmentsByEstablishment(currentUser.establishmentId);
      res.json(assessments);
    } catch (error) {
      console.error("Error fetching assessments:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des évaluations" });
    }
  });

  // Backup route for existing frontend - with mockdata for compatibility
  app.get('/api/assessments-mock', requireAuth, async (req: any, res) => {
    try {
      const user = req.user;
      const establishmentDb = await establishmentService.getEstablishmentDatabase(user.establishmentId);
      
      // Données d'évaluation de démonstration
      const mockAssessments = [
        {
          id: "assess_1",
          title: "Quiz JavaScript Fondamentaux",
          description: "Testez vos connaissances de base en JavaScript",
          assessmentType: "quiz",
          questions: [
            {
              id: "q1",
              type: "single_choice",
              question: "Quelle est la sortie de console.log(typeof null) ?",
              options: ["null", "undefined", "object", "boolean"],
              correctAnswer: "object",
              points: 1
            },
            {
              id: "q2", 
              type: "multiple_choice",
              question: "Quels sont les types primitifs en JavaScript ?",
              options: ["string", "number", "boolean", "object", "null", "undefined"],
              correctAnswer: ["string", "number", "boolean", "null", "undefined"],
              points: 2
            }
          ],
          maxScore: 100,
          passingScore: 60,
          timeLimit: 30,
          maxAttempts: 3,
          establishmentId: user.establishmentId,
          createdAt: new Date(),
          updatedAt: new Date()
        },
        {
          id: "assess_2",
          title: "Examen React Avancé",
          description: "Évaluation approfondie des concepts React",
          assessmentType: "exam",
          questions: [
            {
              id: "q3",
              type: "true_false",
              question: "React utilise un Virtual DOM pour optimiser les performances",
              correctAnswer: "true",
              points: 1
            }
          ],
          maxScore: 100,
          passingScore: 70,
          timeLimit: 60,
          maxAttempts: 2,
          establishmentId: user.establishmentId,
          createdAt: new Date(),
          updatedAt: new Date()
        }
      ];
      
      res.json(mockAssessments);
    } catch (error) {
      console.error('Error fetching assessments:', error);
      res.status(500).json({ error: 'Failed to fetch assessments' });
    }
  });

  // Créer une nouvelle évaluation
  app.post('/api/assessments', requireAuth, async (req: any, res) => {
    try {
      const user = req.user;
      
      // Vérifier les permissions
      if (!['formateur', 'admin', 'manager'].includes(user.role)) {
        return res.status(403).json({ error: 'Permissions insuffisantes' });
      }
      
      const assessmentData = {
        ...req.body,
        id: `assess_${Date.now()}`,
        establishmentId: user.establishmentId,
        createdBy: user.id,
        createdAt: new Date(),
        updatedAt: new Date()
      };
      
      // Pour le moment, on simule la création
      res.status(201).json(assessmentData);
    } catch (error) {
      console.error('Error creating assessment:', error);
      res.status(500).json({ error: 'Failed to create assessment' });
    }
  });

  // Commencer une tentative d'évaluation
  app.post('/api/assessments/:assessmentId/start', requireAuth, async (req: any, res) => {
    try {
      const user = req.user;
      const { assessmentId } = req.params;
      
      const attempt = {
        id: `attempt_${Date.now()}`,
        assessmentId,
        userId: user.id,
        answers: {},
        score: 0,
        maxScore: 100,
        status: 'in_progress',
        timeSpent: 0,
        startedAt: new Date(),
        createdAt: new Date(),
        updatedAt: new Date()
      };
      
      res.status(201).json(attempt);
    } catch (error) {
      console.error('Error starting assessment:', error);
      res.status(500).json({ error: 'Failed to start assessment' });
    }
  });

  // Soumettre une tentative d'évaluation
  app.post('/api/assessment-attempts/:attemptId/submit', requireAuth, async (req: any, res) => {
    try {
      const user = req.user;
      const { attemptId } = req.params;
      const { answers } = req.body;
      
      // Calculer le score (simulation)
      const score = Math.floor(Math.random() * 40) + 60; // Score entre 60 et 100
      
      const submittedAttempt = {
        id: attemptId,
        answers,
        score,
        status: 'submitted',
        submittedAt: new Date(),
        gradedAt: new Date()
      };
      
      res.json(submittedAttempt);
    } catch (error) {
      console.error('Error submitting assessment:', error);
      res.status(500).json({ error: 'Failed to submit assessment' });
    }
  });

  // Récupérer les tentatives de l'utilisateur
  app.get('/api/assessment-attempts', requireAuth, async (req: any, res) => {
    try {
      const user = req.user;
      
      // Pour le moment, on retourne un tableau vide
      const mockAttempts: any[] = [];
      
      res.json(mockAttempts as any[]);
    } catch (error) {
      console.error('Error fetching assessment attempts:', error);
      res.status(500).json({ error: 'Failed to fetch assessment attempts' });
    }
  });

  // Autres routes d'évaluation (évite la duplication de route GET /api/assessments)

  // Get user's assessment attempts
  app.get("/api/assessments/attempts", async (req: any, res) => {
    try {
      const userId = req.session?.userId;
      const attempts = await storage.getUserAssessmentAttempts(userId);
      res.json(attempts);
    } catch (error) {
      console.error("Error fetching assessment attempts:", error);
      res.status(500).json({ message: "Erreur serveur" });
    }
  });

  // Create new assessment
  app.post("/api/assessments", async (req: any, res) => {
    try {
      const userId = req.session?.userId;
      const user = await storage.getUser(userId);
      
      if (!user || !user.establishmentId) {
        return res.status(400).json({ message: "Établissement non trouvé" });
      }

      // Check if user can create assessments
      const isInstructor = ['formateur', 'admin', 'super_admin'].includes(user.role);
      if (!isInstructor) {
        return res.status(403).json({ message: "Permission refusée" });
      }

      // Déterminer le statut initial selon le rôle
      let initialStatus = "draft";
      if (user.role === 'formateur') {
        // Les formateurs créent en mode brouillon, nécessite validation manager
        initialStatus = "pending_approval";
      } else if (['admin', 'super_admin', 'manager'].includes(user.role)) {
        // Admin/Manager peuvent créer directement approuvé
        initialStatus = "approved";
      }

      const assessmentData = {
        ...req.body,
        establishmentId: user.establishmentId,
        createdBy: userId,
        status: initialStatus,
        // Si auto-approuvé par admin/manager
        ...(initialStatus === "approved" && {
          approvedBy: userId,
          approvedAt: new Date(),
        }),
      };

      const assessment = await storage.createAssessment(assessmentData);
      res.status(201).json(assessment);
    } catch (error) {
      console.error("Error creating assessment:", error);
      res.status(500).json({ message: "Erreur serveur" });
    }
  });

  // Start assessment attempt
  app.post("/api/assessments/:id/start", async (req: any, res) => {
    try {
      const userId = req.session?.userId;
      const assessmentId = req.params.id;
      
      const assessment = await storage.getAssessment(assessmentId);
      if (!assessment) {
        return res.status(404).json({ message: "Évaluation non trouvée" });
      }

      // Check if user has attempts left
      const userAttempts = await storage.getUserAssessmentAttempts(userId, assessmentId);
      if (userAttempts.length >= assessment.maxAttempts) {
        return res.status(400).json({ message: "Limite de tentatives atteinte" });
      }

      // Check due date
      if (assessment.dueDate && new Date() > new Date(assessment.dueDate)) {
        return res.status(400).json({ message: "Date limite dépassée" });
      }

      const attempt = await storage.startAssessmentAttempt(userId, assessmentId);
      res.json(attempt);
    } catch (error) {
      console.error("Error starting assessment:", error);
      res.status(500).json({ message: "Erreur serveur" });
    }
  });

  // Routes de validation pour les managers
  
  // Approuver une évaluation (Manager/Admin seulement)
  app.patch("/api/assessments/:id/approve", async (req: any, res) => {
    try {
      const userId = req.session?.userId;
      const user = await storage.getUser(userId);
      
      if (!user || !['manager', 'admin', 'super_admin'].includes(user.role)) {
        return res.status(403).json({ message: "Permission refusée - Manager requis" });
      }

      const assessmentId = req.params.id;
      const assessment = await storage.getAssessment(assessmentId);
      
      if (!assessment) {
        return res.status(404).json({ message: "Évaluation non trouvée" });
      }

      if (assessment.establishmentId !== user.establishmentId) {
        return res.status(403).json({ message: "Accès interdit" });
      }

      const updatedAssessment = await storage.updateAssessment(assessmentId, {
        status: "approved",
        approvedBy: userId,
        approvedAt: new Date(),
        rejectionReason: null,
      });

      res.json(updatedAssessment);
    } catch (error) {
      console.error("Error approving assessment:", error);
      res.status(500).json({ message: "Erreur serveur" });
    }
  });

  // Rejeter une évaluation (Manager/Admin seulement)
  app.patch("/api/assessments/:id/reject", async (req: any, res) => {
    try {
      const userId = req.session?.userId;
      const user = await storage.getUser(userId);
      
      if (!user || !['manager', 'admin', 'super_admin'].includes(user.role)) {
        return res.status(403).json({ message: "Permission refusée - Manager requis" });
      }

      const assessmentId = req.params.id;
      const { rejectionReason } = req.body;
      
      if (!rejectionReason) {
        return res.status(400).json({ message: "Raison du rejet requise" });
      }

      const assessment = await storage.getAssessment(assessmentId);
      
      if (!assessment) {
        return res.status(404).json({ message: "Évaluation non trouvée" });
      }

      if (assessment.establishmentId !== user.establishmentId) {
        return res.status(403).json({ message: "Accès interdit" });
      }

      const updatedAssessment = await storage.updateAssessment(assessmentId, {
        status: "rejected",
        rejectionReason,
        approvedBy: userId,
        approvedAt: new Date(),
      });

      res.json(updatedAssessment);
    } catch (error) {
      console.error("Error rejecting assessment:", error);
      res.status(500).json({ message: "Erreur serveur" });
    }
  });

  // Récupérer les évaluations en attente de validation (Manager/Admin seulement)
  app.get("/api/assessments/pending", async (req: any, res) => {
    try {
      const userId = req.session?.userId;
      const user = await storage.getUser(userId);
      
      if (!user || !['manager', 'admin', 'super_admin'].includes(user.role)) {
        return res.status(403).json({ message: "Permission refusée - Manager requis" });
      }

      const pendingAssessments = await storage.getPendingAssessmentsByEstablishment(user.establishmentId);
      res.json(pendingAssessments);
    } catch (error) {
      console.error("Error fetching pending assessments:", error);
      res.status(500).json({ message: "Erreur serveur" });
    }
  });

  // PATCH /api/users/:userId - Update user profile
  app.patch("/api/users/:userId", requireAuth, async (req: any, res) => {
    try {
      const { userId } = req.params;
      const currentUser = await storage.getUserById(req.session.userId);
      
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      // Check if user can modify this profile
      if (currentUser.id !== userId && !['admin', 'manager', 'super_admin'].includes(currentUser.role)) {
        return res.status(403).json({ message: "Not authorized to modify this user" });
      }

      const updates = req.body;
      
      // Don't allow changing certain fields
      delete updates.id;
      delete updates.establishmentId;
      delete updates.createdAt;
      delete updates.updatedAt;
      
      // Hash password if provided
      if (updates.password) {
        updates.password = await bcrypt.hash(updates.password, 12);
      }

      const updatedUser = await storage.updateUser(userId, updates);
      
      if (!updatedUser) {
        return res.status(404).json({ message: "User not found" });
      }

      // Return user without password
      const { password: _, ...userWithoutPassword } = updatedUser;
      res.json(userWithoutPassword);
    } catch (error) {
      console.error("Error updating user:", error);
      res.status(500).json({ message: "Failed to update user" });
    }
  });

  // GET /api/users/:userId/preferences - Get user preferences
  app.get("/api/users/:userId/preferences", requireAuth, async (req: any, res) => {
    try {
      const { userId } = req.params;
      const currentUser = await storage.getUserById(req.session.userId);
      
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      // Check if user can access these preferences
      if (currentUser.id !== userId && !['admin', 'manager', 'super_admin'].includes(currentUser.role)) {
        return res.status(403).json({ message: "Not authorized to access this user's preferences" });
      }

      const user = await storage.getUserById(userId);
      if (!user) {
        return res.status(404).json({ message: "User not found" });
      }

      const preferences = user.preferences || {
        theme: 'light',
        language: 'fr',
        notifications: true,
        emailDigest: 'weekly'
      };

      res.json(preferences);
    } catch (error) {
      console.error("Error fetching user preferences:", error);
      res.status(500).json({ message: "Failed to fetch user preferences" });
    }
  });

  // Routes dashboard final ajout
  app.get('/api/dashboard/stats', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !currentUser.establishmentId) {
        return res.status(401).json({ message: "Utilisateur non authentifié ou sans établissement" });
      }

      const stats = await storage.getDashboardStats(currentUser.id, currentUser.establishmentId);
      res.json(stats);
    } catch (error) {
      console.error("Error fetching dashboard stats:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des statistiques" });
    }
  });

  // ===== ROUTES MODULES DE COURS ET PROGRESSION (Priorité 2) =====

  // Get modules for a specific course
  app.get('/api/courses/:courseId/modules', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser) {
        return res.status(401).json({ message: "Utilisateur non authentifié" });
      }

      const { courseId } = req.params;
      const modules = await storage.getCourseModules(courseId);
      res.json(modules);
    } catch (error) {
      console.error("Error fetching course modules:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des modules" });
    }
  });

  // Create a new module for a course
  app.post('/api/courses/:courseId/modules', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["admin", "manager", "formateur", "super_admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { courseId } = req.params;
      const moduleData = {
        ...req.body,
        courseId,
        createdBy: currentUser.id,
      };

      const module = await storage.createCourseModule(moduleData);
      res.status(201).json(module);
    } catch (error) {
      console.error("Error creating course module:", error);
      res.status(500).json({ message: "Erreur lors de la création du module" });
    }
  });

  // Update a course module
  app.put('/api/modules/:moduleId', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["admin", "manager", "formateur", "super_admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { moduleId } = req.params;
      const updatedModule = await storage.updateCourseModule(moduleId, req.body);
      
      if (!updatedModule) {
        return res.status(404).json({ message: "Module non trouvé" });
      }
      
      res.json(updatedModule);
    } catch (error) {
      console.error("Error updating course module:", error);
      res.status(500).json({ message: "Erreur lors de la mise à jour du module" });
    }
  });

  // Delete a course module
  app.delete('/api/modules/:moduleId', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["admin", "manager", "formateur", "super_admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { moduleId } = req.params;
      const deleted = await storage.deleteCourseModule(moduleId);
      
      if (!deleted) {
        return res.status(404).json({ message: "Module non trouvé" });
      }
      
      res.json({ message: "Module supprimé avec succès" });
    } catch (error) {
      console.error("Error deleting course module:", error);
      res.status(500).json({ message: "Erreur lors de la suppression du module" });
    }
  });

  // Get user's progress for all modules or specific course
  app.get('/api/progress/modules', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser) {
        return res.status(401).json({ message: "Utilisateur non authentifié" });
      }

      const { courseId } = req.query;
      const progress = await storage.getUserModuleProgress(currentUser.id, courseId as string);
      res.json(progress);
    } catch (error) {
      console.error("Error fetching module progress:", error);
      res.status(500).json({ message: "Erreur lors de la récupération de la progression" });
    }
  });

  // Update module progress for user
  app.post('/api/progress/modules/:moduleId', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser) {
        return res.status(401).json({ message: "Utilisateur non authentifié" });
      }

      const { moduleId } = req.params;
      const progressData = req.body;
      
      const updatedProgress = await storage.updateModuleProgress(currentUser.id, moduleId, progressData);
      res.json(updatedProgress);
    } catch (error) {
      console.error("Error updating module progress:", error);
      res.status(500).json({ message: "Erreur lors de la mise à jour de la progression" });
    }
  });

  // Get progress summary for a specific course
  app.get('/api/progress/courses/:courseId/summary', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser) {
        return res.status(401).json({ message: "Utilisateur non authentifié" });
      }

      const { courseId } = req.params;
      const summary = await storage.getModuleProgressSummary(currentUser.id, courseId);
      res.json(summary);
    } catch (error) {
      console.error("Error fetching progress summary:", error);
      res.status(500).json({ message: "Erreur lors de la récupération du résumé de progression" });
    }
  });

  // Complete a course
  app.post('/api/courses/:courseId/complete', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser) {
        return res.status(401).json({ message: "Utilisateur non authentifié" });
      }

      const { courseId } = req.params;
      const completedCourse = await storage.completeCourse(currentUser.id, courseId);
      res.json(completedCourse);
    } catch (error) {
      console.error("Error completing course:", error);
      res.status(500).json({ message: "Erreur lors de la finalisation du cours" });
    }
  });

  // Get user certificates
  app.get('/api/certificates', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser) {
        return res.status(401).json({ message: "Utilisateur non authentifié" });
      }

      const certificates = await storage.getUserCertificates(currentUser.id);
      res.json(certificates);
    } catch (error) {
      console.error("Error fetching certificates:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des certificats" });
    }
  });

  // Educational plugins management
  app.get('/api/educational-plugins', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !currentUser.establishmentId) {
        return res.status(401).json({ message: "Utilisateur non authentifié ou sans établissement" });
      }

      const plugins = await storage.getEducationalPlugins(currentUser.establishmentId);
      res.json(plugins);
    } catch (error) {
      console.error("Error fetching educational plugins:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des plugins éducatifs" });
    }
  });

  app.post('/api/educational-plugins', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["admin", "manager", "super_admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const pluginData = {
        ...req.body,
        establishmentId: currentUser.establishmentId,
        createdBy: currentUser.id,
      };

      const plugin = await storage.createEducationalPlugin(pluginData);
      res.status(201).json(plugin);
    } catch (error) {
      console.error("Error creating educational plugin:", error);
      res.status(500).json({ message: "Erreur lors de la création du plugin éducatif" });
    }
  });

  app.put('/api/educational-plugins/:pluginId', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["admin", "manager", "super_admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { pluginId } = req.params;
      const updatedPlugin = await storage.updateEducationalPlugin(pluginId, req.body);
      
      if (!updatedPlugin) {
        return res.status(404).json({ message: "Plugin non trouvé" });
      }
      
      res.json(updatedPlugin);
    } catch (error) {
      console.error("Error updating educational plugin:", error);
      res.status(500).json({ message: "Erreur lors de la mise à jour du plugin" });
    }
  });

  app.post('/api/educational-plugins/:pluginId/toggle', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["admin", "manager", "super_admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { pluginId } = req.params;
      const { isActive } = req.body;
      
      const updatedPlugin = await storage.togglePluginStatus(pluginId, isActive);
      
      if (!updatedPlugin) {
        return res.status(404).json({ message: "Plugin non trouvé" });
      }
      
      res.json(updatedPlugin);
    } catch (error) {
      console.error("Error toggling plugin status:", error);
      res.status(500).json({ message: "Erreur lors du changement de statut du plugin" });
    }
  });

  // ===== ROUTES EXPORTS AVANCÉS ET NOTIFICATIONS (Priorité 3) =====

  // Create bulk export job
  app.post('/api/exports', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["admin", "manager", "super_admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { exportType, filters } = req.body;
      const exportJob = await storage.createBulkExport(
        currentUser.establishmentId!,
        exportType,
        filters,
        currentUser.id
      );

      // Log system activity
      await storage.logSystemActivity(
        currentUser.establishmentId!,
        currentUser.id,
        'export_created',
        { exportType, jobId: exportJob.id }
      );

      res.status(201).json(exportJob);
    } catch (error) {
      console.error("Error creating export job:", error);
      res.status(500).json({ message: "Erreur lors de la création de l'export" });
    }
  });

  // Get export history
  app.get('/api/exports/history', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser) {
        return res.status(401).json({ message: "Utilisateur non authentifié" });
      }

      const { limit } = req.query;
      const history = await storage.getExportHistory(
        currentUser.establishmentId!,
        limit ? parseInt(limit as string) : 50
      );
      
      res.json(history);
    } catch (error) {
      console.error("Error fetching export history:", error);
      res.status(500).json({ message: "Erreur lors de la récupération de l'historique" });
    }
  });

  // Update export job status
  app.put('/api/exports/:jobId/status', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["admin", "manager", "super_admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { jobId } = req.params;
      const { status, downloadUrl, error } = req.body;
      
      const updatedJob = await storage.updateExportJobStatus(jobId, status, downloadUrl, error);
      res.json(updatedJob);
    } catch (error) {
      console.error("Error updating export job:", error);
      res.status(500).json({ message: "Erreur lors de la mise à jour du job d'export" });
    }
  });

  // Export course data
  app.get('/api/courses/:courseId/export', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["admin", "manager", "formateur", "super_admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { courseId } = req.params;
      const courseData = await storage.exportCourseData(courseId);
      
      // Log export activity
      await storage.logSystemActivity(
        currentUser.establishmentId!,
        currentUser.id,
        'course_data_exported',
        { courseId, exportDate: new Date() }
      );

      res.json(courseData);
    } catch (error) {
      console.error("Error exporting course data:", error);
      res.status(500).json({ message: "Erreur lors de l'export des données de cours" });
    }
  });

  // Export user progress data
  app.get('/api/users/:userId/progress/export', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser) {
        return res.status(401).json({ message: "Utilisateur non authentifié" });
      }

      const { userId } = req.params;
      
      // Users can export their own data, or admins can export any user's data
      if (userId !== currentUser.id && !["admin", "manager", "super_admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const progressData = await storage.exportUserProgressData(userId, currentUser.establishmentId!);
      
      // Log export activity
      await storage.logSystemActivity(
        currentUser.establishmentId!,
        currentUser.id,
        'user_progress_exported',
        { targetUserId: userId, exportDate: new Date() }
      );

      res.json(progressData);
    } catch (error) {
      console.error("Error exporting user progress:", error);
      res.status(500).json({ message: "Erreur lors de l'export de la progression utilisateur" });
    }
  });

  // Get establishment analytics
  app.get('/api/analytics/establishment', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["admin", "manager", "super_admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { from, to } = req.query;
      const dateRange = from && to ? {
        from: new Date(from as string),
        to: new Date(to as string)
      } : undefined;

      const analytics = await storage.getEstablishmentAnalytics(currentUser.establishmentId!, dateRange);
      res.json(analytics);
    } catch (error) {
      console.error("Error fetching establishment analytics:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des analytiques" });
    }
  });

  // Get export templates
  app.get('/api/exports/templates', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser) {
        return res.status(401).json({ message: "Utilisateur non authentifié" });
      }

      const templates = await storage.getExportTemplates(currentUser.establishmentId!);
      res.json(templates);
    } catch (error) {
      console.error("Error fetching export templates:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des templates d'export" });
    }
  });

  // Advanced notification routes
  app.post('/api/notifications/bulk', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["admin", "manager", "super_admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { notifications } = req.body;
      const notificationsData = notifications.map((notif: any) => ({
        ...notif,
        establishmentId: currentUser.establishmentId
      }));

      const createdNotifications = await storage.createBulkNotifications(notificationsData);
      res.status(201).json(createdNotifications);
    } catch (error) {
      console.error("Error creating bulk notifications:", error);
      res.status(500).json({ message: "Erreur lors de la création des notifications en lot" });
    }
  });

  app.get('/api/notifications/by-type/:type', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser) {
        return res.status(401).json({ message: "Utilisateur non authentifié" });
      }

      const { type } = req.params;
      const { limit } = req.query;
      
      const notifications = await storage.getNotificationsByType(
        currentUser.establishmentId!,
        type,
        limit ? parseInt(limit as string) : 100
      );
      
      res.json(notifications);
    } catch (error) {
      console.error("Error fetching notifications by type:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des notifications par type" });
    }
  });

  app.post('/api/notifications/mark-read', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser) {
        return res.status(401).json({ message: "Utilisateur non authentifié" });
      }

      const { notificationIds } = req.body;
      const markedCount = await storage.markNotificationsAsRead(notificationIds, currentUser.id);
      
      res.json({ markedCount });
    } catch (error) {
      console.error("Error marking notifications as read:", error);
      res.status(500).json({ message: "Erreur lors du marquage des notifications comme lues" });
    }
  });

  app.get('/api/notifications/unread-count', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser) {
        return res.status(401).json({ message: "Utilisateur non authentifié" });
      }

      const count = await storage.getUnreadNotificationCount(currentUser.id);
      res.json({ count });
    } catch (error) {
      console.error("Error fetching unread notification count:", error);
      res.status(500).json({ message: "Erreur lors de la récupération du nombre de notifications non lues" });
    }
  });

  // Batch operations
  app.post('/api/courses/:courseId/batch-enroll', requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUser(req.session.userId!);
      if (!currentUser || !["admin", "manager", "formateur", "super_admin"].includes(currentUser.role || '')) {
        return res.status(403).json({ message: "Accès refusé" });
      }

      const { courseId } = req.params;
      const { userIds } = req.body;
      
      const enrollments = await storage.batchEnrollUsers(courseId, userIds);
      
      // Log batch enrollment
      await storage.logSystemActivity(
        currentUser.establishmentId!,
        currentUser.id,
        'batch_enrollment',
        { courseId, userCount: userIds.length }
      );

      res.status(201).json(enrollments);
    } catch (error) {
      console.error("Error batch enrolling users:", error);
      res.status(500).json({ message: "Erreur lors de l'inscription en lot des utilisateurs" });
    }
  });

  // PATCH /api/users/:userId/preferences - Update user preferences
  app.patch("/api/users/:userId/preferences", requireAuth, async (req: any, res) => {
    try {
      const { userId } = req.params;
      const currentUser = await storage.getUserById(req.session.userId);
      
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      // Check if user can modify these preferences
      if (currentUser.id !== userId) {
        return res.status(403).json({ message: "Not authorized to modify this user's preferences" });
      }

      const user = await storage.getUserById(userId);
      if (!user) {
        return res.status(404).json({ message: "User not found" });
      }

      const currentPrefs = user.preferences || {};
      const newPreferences = { ...currentPrefs, ...req.body };

      const updatedUser = await storage.updateUser(userId, { preferences: newPreferences });
      
      if (!updatedUser) {
        return res.status(404).json({ message: "User not found" });
      }

      res.json(updatedUser.preferences);
    } catch (error) {
      console.error("Error updating user preferences:", error);
      res.status(500).json({ message: "Failed to update user preferences" });
    }
  });

  // GET /api/dashboard/stats - Dashboard statistics by user role
  app.get("/api/dashboard/stats", requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUserById(req.session.userId);
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      const stats = await storage.getDashboardStats(currentUser.id, currentUser.role, currentUser.establishmentId);
      res.json(stats);
    } catch (error) {
      console.error("Error fetching dashboard stats:", error);
      res.status(500).json({ message: "Failed to fetch dashboard stats" });
    }
  });

  // GET /api/dashboard/widgets - Dashboard widgets by user role
  app.get("/api/dashboard/widgets", requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUserById(req.session.userId);
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      const widgets = await storage.getDashboardWidgets(currentUser.id, currentUser.role, currentUser.establishmentId);
      res.json(widgets);
    } catch (error) {
      console.error("Error fetching dashboard widgets:", error);
      res.status(500).json({ message: "Failed to fetch dashboard widgets" });
    }
  });

  // GET /api/notifications - User notifications
  app.get("/api/notifications", requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUserById(req.session.userId);
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      const notifications = await storage.getUserNotifications(currentUser.id);
      res.json(notifications);
    } catch (error) {
      console.error("Error fetching notifications:", error);
      res.status(500).json({ message: "Failed to fetch notifications" });
    }
  });

  // PATCH /api/notifications/:id/read - Mark notification as read
  app.patch("/api/notifications/:id/read", requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUserById(req.session.userId);
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      const notification = await storage.markNotificationAsRead(req.params.id, currentUser.id);
      if (!notification) {
        return res.status(404).json({ message: "Notification not found" });
      }

      res.json(notification);
    } catch (error) {
      console.error("Error marking notification as read:", error);
      res.status(500).json({ message: "Failed to mark notification as read" });
    }
  });

  // Enhanced courses API with filters and search
  app.get("/api/courses", async (req: any, res) => {
    try {
      const {
        category,
        level,
        search,
        instructor,
        sortBy = 'createdAt',
        sortOrder = 'desc',
        page = 1,
        limit = 10
      } = req.query;

      const currentUser = req.session?.userId ? await storage.getUserById(req.session.userId) : null;
      let establishmentId = currentUser?.establishmentId;

      if (!establishmentId) {
        const establishments = await storage.getAllEstablishments();
        const defaultEstablishment = establishments[0];
        if (!defaultEstablishment) {
          return res.status(500).json({ message: "No establishment configured" });
        }
        establishmentId = defaultEstablishment.id;
      }

      const filters = {
        category,
        level,
        search,
        instructor,
        establishmentId,
        sortBy,
        sortOrder,
        page: parseInt(page),
        limit: parseInt(limit)
      };

      const result = await storage.getCoursesWithFilters(filters);
      res.json(result);
    } catch (error) {
      console.error("Error fetching courses:", error);
      res.status(500).json({ message: "Failed to fetch courses" });
    }
  });

  // GET /api/courses/popular - Popular courses with stats
  app.get("/api/courses/popular", async (req: any, res) => {
    try {
      const { limit = 6 } = req.query;
      
      const currentUser = req.session?.userId ? await storage.getUserById(req.session.userId) : null;
      let establishmentId = currentUser?.establishmentId;

      if (!establishmentId) {
        const establishments = await storage.getAllEstablishments();
        const defaultEstablishment = establishments[0];
        if (!defaultEstablishment) {
          return res.status(500).json({ message: "No establishment configured" });
        }
        establishmentId = defaultEstablishment.id;
      }

      const popularCourses = await storage.getPopularCourses(establishmentId, parseInt(limit));
      res.json(popularCourses);
    } catch (error) {
      console.error("Error fetching popular courses:", error);
      res.status(500).json({ message: "Failed to fetch popular courses" });
    }
  });

  // GET /api/courses/:id/stats - Course statistics
  app.get("/api/courses/:id/stats", requireAuth, async (req: any, res) => {
    try {
      const { id } = req.params;
      const currentUser = await storage.getUserById(req.session.userId);
      
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      // Check if user has permission to view course stats
      if (!['admin', 'manager', 'super_admin', 'formateur'].includes(currentUser.role)) {
        return res.status(403).json({ message: "Not authorized to view course statistics" });
      }

      const stats = await storage.getCourseStats(id);
      if (!stats) {
        return res.status(404).json({ message: "Course not found" });
      }

      res.json(stats);
    } catch (error) {
      console.error("Error fetching course stats:", error);
      res.status(500).json({ message: "Failed to fetch course stats" });
    }
  });

  // GET /api/search - Global search
  app.get("/api/search", requireAuth, async (req: any, res) => {
    try {
      const { q, type, limit = 10 } = req.query;
      
      if (!q || q.trim() === '') {
        return res.status(400).json({ message: "Search query is required" });
      }

      const currentUser = await storage.getUserById(req.session.userId);
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      const results = await storage.globalSearch(q, type, currentUser.establishmentId, parseInt(limit));
      res.json(results);
    } catch (error) {
      console.error("Error performing search:", error);
      res.status(500).json({ message: "Failed to perform search" });
    }
  });

  // Export/Archive routes
  app.get("/api/export/jobs", requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUserById(req.session.userId);
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      const jobs = await storage.getExportJobs(currentUser.id, currentUser.establishmentId);
      res.json(jobs);
    } catch (error) {
      console.error("Error fetching export jobs:", error);
      res.status(500).json({ message: "Failed to fetch export jobs" });
    }
  });

  app.post("/api/export/create", requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUserById(req.session.userId);
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      // Validate request body
      const validatedData = insertExportJobSchema.parse(req.body);
      
      // Generate filename based on type and timestamp
      const timestamp = new Date().toISOString().slice(0, 10);
      const filename = `export_${validatedData.type}_${timestamp}_${Date.now()}.${validatedData.type}`;

      const job = await storage.createExportJob({
        ...validatedData,
        filename,
        userId: currentUser.id,
        establishmentId: currentUser.establishmentId
      });

      res.json(job);
    } catch (error) {
      console.error("Error creating export job:", error);
      res.status(500).json({ message: "Failed to create export job" });
    }
  });

  app.get("/api/export/download/:id", requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUserById(req.session.userId);
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      const job = await storage.getExportJob(req.params.id);
      if (!job || job.userId !== currentUser.id) {
        return res.status(404).json({ message: "Export job not found" });
      }

      if (job.status !== 'completed') {
        return res.status(400).json({ message: "Export not ready for download" });
      }

      // For demo purposes, return a placeholder response
      res.setHeader('Content-Type', 'application/octet-stream');
      res.setHeader('Content-Disposition', `attachment; filename="${job.filename}"`);
      res.send(Buffer.from(`Demo export file for ${job.filename}`));
    } catch (error) {
      console.error("Error downloading export:", error);
      res.status(500).json({ message: "Failed to download export" });
    }
  });

  // WYSIWYG Editor routes
  app.get("/api/wysiwyg/pages", requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUserById(req.session.userId);
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      // For demo purposes, return sample pages
      const pages = [
        {
          id: 'home',
          name: 'Page d\'accueil',
          slug: 'home',
          sections: [],
          lastModified: new Date().toISOString()
        },
        {
          id: 'about',
          name: 'À propos',
          slug: 'about',
          sections: [],
          lastModified: new Date().toISOString()
        }
      ];

      res.json(pages);
    } catch (error) {
      console.error("Error fetching WYSIWYG pages:", error);
      res.status(500).json({ message: "Failed to fetch pages" });
    }
  });

  app.post("/api/wysiwyg/save", requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUserById(req.session.userId);
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      // Check if user has permission to edit pages
      if (!['admin', 'manager', 'super_admin'].includes(currentUser.role)) {
        return res.status(403).json({ message: "Not authorized to edit pages" });
      }

      const pageData = req.body;
      
      // For demo purposes, just return success
      // In real implementation, save to database
      const savedPage = {
        ...pageData,
        id: pageData.id || `page-${Date.now()}`,
        lastModified: new Date().toISOString()
      };

      res.json(savedPage);
    } catch (error) {
      console.error("Error saving WYSIWYG page:", error);
      res.status(500).json({ message: "Failed to save page" });
    }
  });

  // System updates routes
  app.get("/api/system/status", requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUserById(req.session.userId);
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      // Check if user has permission to view system status
      if (!['admin', 'manager', 'super_admin'].includes(currentUser.role)) {
        return res.status(403).json({ message: "Not authorized to view system status" });
      }

      const systemStatus = {
        currentVersion: "2.1.4",
        lastUpdateCheck: new Date().toISOString(),
        nextScheduledUpdate: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(),
        autoUpdatesEnabled: true,
        maintenanceMode: false,
        backupStatus: "completed"
      };

      res.json(systemStatus);
    } catch (error) {
      console.error("Error fetching system status:", error);
      res.status(500).json({ message: "Failed to fetch system status" });
    }
  });

  app.post("/api/system/update", requireAuth, async (req: any, res) => {
    try {
      const currentUser = await storage.getUserById(req.session.userId);
      if (!currentUser) {
        return res.status(401).json({ message: "User not authenticated" });
      }

      // Check if user has permission to perform updates
      if (!['admin', 'super_admin'].includes(currentUser.role)) {
        return res.status(403).json({ message: "Not authorized to perform system updates" });
      }

      const { updateId } = req.body;
      
      // For demo purposes, simulate update process
      // In real implementation, handle actual system update
      
      res.json({ 
        success: true, 
        message: "Update process started",
        updateId 
      });
    } catch (error) {
      console.error("Error starting system update:", error);
      res.status(500).json({ message: "Failed to start system update" });
    }
  });

  // ===== ROUTES POUR DOCUMENTATION ET AIDE =====
  
  // Get help contents for an establishment
  app.get('/api/documentation/help', requireAuth, async (req: any, res) => {
    try {
      const user = req.user;
      const { category, role } = req.query;
      
      const helpContents = await storage.getHelpContents(
        user.establishmentId, 
        role || user.role, 
        category
      );
      
      res.json(helpContents);
    } catch (error) {
      console.error("Error fetching help contents:", error);
      res.status(500).json({ message: "Erreur lors de la récupération de l'aide" });
    }
  });
  
  // Search help content
  app.get('/api/documentation/search', requireAuth, async (req: any, res) => {
    try {
      const user = req.user;
      const { q: query } = req.query;
      
      if (!query) {
        return res.status(400).json({ message: "Paramètre de recherche requis" });
      }
      
      const results = await storage.searchHelpContent(user.establishmentId, query as string, user.role);
      res.json(results);
    } catch (error) {
      console.error("Error searching help content:", error);
      res.status(500).json({ message: "Erreur lors de la recherche dans l'aide" });
    }
  });
  
  // Admin routes for managing help content
  app.post('/api/admin/documentation/help', requireAdmin, async (req: any, res) => {
    try {
      const user = req.user;
      const helpData = {
        ...req.body,
        establishmentId: user.establishmentId
      };
      
      const helpContent = await storage.createHelpContent(helpData);
      res.status(201).json(helpContent);
    } catch (error) {
      console.error("Error creating help content:", error);
      res.status(500).json({ message: "Erreur lors de la création du contenu d'aide" });
    }
  });
  
  app.put('/api/admin/documentation/help/:id', requireAdmin, async (req: any, res) => {
    try {
      const { id } = req.params;
      const updatedContent = await storage.updateHelpContent(id, req.body);
      
      if (!updatedContent) {
        return res.status(404).json({ message: "Contenu d'aide non trouvé" });
      }
      
      res.json(updatedContent);
    } catch (error) {
      console.error("Error updating help content:", error);
      res.status(500).json({ message: "Erreur lors de la mise à jour du contenu d'aide" });
    }
  });
  
  app.delete('/api/admin/documentation/help/:id', requireAdmin, async (req: any, res) => {
    try {
      const { id } = req.params;
      await storage.deleteHelpContent(id);
      res.json({ message: "Contenu d'aide supprimé avec succès" });
    } catch (error) {
      console.error("Error deleting help content:", error);
      res.status(500).json({ message: "Erreur lors de la suppression du contenu d'aide" });
    }
  });
  
  // ===== ROUTES POUR SYSTÈME ET VERSIONS =====
  
  // Get system versions
  app.get('/api/system/versions', async (req, res) => {
    try {
      const versions = await storage.getSystemVersions();
      res.json(versions);
    } catch (error) {
      console.error("Error fetching system versions:", error);
      res.status(500).json({ message: "Erreur lors de la récupération des versions système" });
    }
  });
  
  // Get active system version
  app.get('/api/system/version/active', async (req, res) => {
    try {
      const activeVersion = await storage.getActiveSystemVersion();
      res.json(activeVersion || { version: "1.0.0", title: "Version initiale" });
    } catch (error) {
      console.error("Error fetching active system version:", error);
      res.status(500).json({ message: "Erreur lors de la récupération de la version active" });
    }
  });
  
  // Get maintenance status
  app.get('/api/system/maintenance', async (req, res) => {
    try {
      const maintenanceStatus = await storage.getMaintenanceStatus();
      res.json(maintenanceStatus);
    } catch (error) {
      console.error("Error fetching maintenance status:", error);
      res.status(500).json({ message: "Erreur lors de la récupération du statut de maintenance" });
    }
  });
  
  // Super Admin routes for system management
  app.post('/api/super-admin/system/versions', requireSuperAdmin, async (req: any, res) => {
    try {
      const user = req.user;
      const versionData = {
        ...req.body,
        createdBy: user.id
      };
      
      const newVersion = await storage.createSystemVersion(versionData);
      res.status(201).json(newVersion);
    } catch (error) {
      console.error("Error creating system version:", error);
      res.status(500).json({ message: "Erreur lors de la création de la version système" });
    }
  });
  
  app.post('/api/super-admin/system/versions/:id/activate', requireSuperAdmin, async (req: any, res) => {
    try {
      const { id } = req.params;
      await storage.activateSystemVersion(id);
      res.json({ message: "Version système activée avec succès" });
    } catch (error) {
      console.error("Error activating system version:", error);
      res.status(500).json({ message: "Erreur lors de l'activation de la version système" });
    }
  });
  
  app.post('/api/super-admin/system/maintenance', requireSuperAdmin, async (req: any, res) => {
    try {
      const { enabled, message } = req.body;
      await storage.setMaintenanceMode(enabled, message);
      res.json({ message: `Mode maintenance ${enabled ? 'activé' : 'désactivé'} avec succès` });
    } catch (error) {
      console.error("Error setting maintenance mode:", error);
      res.status(500).json({ message: "Erreur lors de la configuration du mode maintenance" });
    }
  });
  
  // ===== ROUTES POUR BRANDING ÉTABLISSEMENT =====
  
  // Get establishment branding
  app.get('/api/establishments/:id/branding', async (req, res) => {
    try {
      const { id } = req.params;
      const branding = await storage.getEstablishmentBranding(id);
      
      if (!branding) {
        // Return default branding if none exists
        return res.json({
          establishmentId: id,
          primaryColor: '#3b82f6',
          secondaryColor: '#64748b',
          accentColor: '#10b981',
          navigationConfig: {
            showLogo: true,
            showSearch: true,
            menuItems: []
          },
          footerConfig: {
            showCopyright: true,
            customText: '',
            links: []
          }
        });
      }
      
      res.json(branding);
    } catch (error) {
      console.error("Error fetching establishment branding:", error);
      res.status(500).json({ message: "Erreur lors de la récupération du branding de l'établissement" });
    }
  });
  
  // Update establishment branding
  app.put('/api/admin/establishments/:id/branding', requireEstablishmentAccess('id'), async (req: any, res) => {
    try {
      const { id } = req.params;
      
      // Check if branding exists
      let branding = await storage.getEstablishmentBranding(id);
      
      if (!branding) {
        // Create new branding
        const newBranding = await storage.createEstablishmentBranding({
          establishmentId: id,
          ...req.body
        });
        return res.status(201).json(newBranding);
      }
      
      // Update existing branding
      const updatedBranding = await storage.updateEstablishmentBranding(id, req.body);
      res.json(updatedBranding);
    } catch (error) {
      console.error("Error updating establishment branding:", error);
      res.status(500).json({ message: "Erreur lors de la mise à jour du branding de l'établissement" });
    }
  });

  // ===== COLLABORATIVE STUDY GROUPS API =====

  // Create study group
  app.post("/api/study-groups", requireAuth, async (req, res) => {
    try {
      const user = await storage.getUserById(req.session!.userId!);
      if (!user) {
        return res.status(401).json({ message: "User not found" });
      }

      const studyGroupData = {
        ...req.body,
        establishmentId: user.establishmentId,
        createdBy: user.id
      };

      const group = await storage.createStudyGroup(studyGroupData);
      res.status(201).json(group);
    } catch (error) {
      console.error("Error creating study group:", error);
      res.status(500).json({ message: "Failed to create study group" });
    }
  });

  // Get study groups by establishment
  app.get("/api/study-groups", requireAuth, async (req, res) => {
    try {
      const user = await storage.getUserById(req.session!.userId!);
      if (!user) {
        return res.status(401).json({ message: "User not found" });
      }

      const groups = await storage.getStudyGroupsByEstablishment(user.establishmentId!);
      res.json(groups);
    } catch (error) {
      console.error("Error fetching study groups:", error);
      res.status(500).json({ message: "Failed to fetch study groups" });
    }
  });

  // Get specific study group
  app.get("/api/study-groups/:id", requireAuth, async (req, res) => {
    try {
      const group = await storage.getStudyGroupById(req.params.id);
      if (!group) {
        return res.status(404).json({ message: "Study group not found" });
      }

      // Check if user is a member or if group is public
      const user = await storage.getUserById(req.session!.userId!);
      if (!user) {
        return res.status(401).json({ message: "User not found" });
      }

      const members = await storage.getStudyGroupMembers(group.id);
      const isMember = members.some(member => member.userId === user.id);

      if (!group.isPublic && !isMember) {
        return res.status(403).json({ message: "Access denied to private group" });
      }

      res.json({
        ...group,
        members,
        isMember
      });
    } catch (error) {
      console.error("Error fetching study group:", error);
      res.status(500).json({ message: "Failed to fetch study group" });
    }
  });

  // Join study group
  app.post("/api/study-groups/:id/join", requireAuth, async (req, res) => {
    try {
      const user = await storage.getUserById(req.session!.userId!);
      if (!user) {
        return res.status(401).json({ message: "User not found" });
      }

      const member = await storage.joinStudyGroup(req.params.id, user.id);
      res.status(201).json(member);
    } catch (error: any) {
      console.error("Error joining study group:", error);
      res.status(400).json({ message: error.message });
    }
  });

  // Get study group messages
  app.get("/api/study-groups/:id/messages", requireAuth, async (req, res) => {
    try {
      const user = await storage.getUserById(req.session!.userId!);
      if (!user) {
        return res.status(401).json({ message: "User not found" });
      }

      // Verify user is a member
      const members = await storage.getStudyGroupMembers(req.params.id);
      const isMember = members.some(member => member.userId === user.id);
      
      if (!isMember) {
        return res.status(403).json({ message: "Access denied - not a group member" });
      }

      const limit = parseInt(req.query.limit as string) || 50;
      const messages = await storage.getStudyGroupMessages(req.params.id, limit);
      res.json(messages);
    } catch (error) {
      console.error("Error fetching messages:", error);
      res.status(500).json({ message: "Failed to fetch messages" });
    }
  });

  // Post message to study group
  app.post("/api/study-groups/:id/messages", requireAuth, async (req, res) => {
    try {
      const user = await storage.getUserById(req.session!.userId!);
      if (!user) {
        return res.status(401).json({ message: "User not found" });
      }

      // Verify user is a member
      const members = await storage.getStudyGroupMembers(req.params.id);
      const isMember = members.some(member => member.userId === user.id);
      
      if (!isMember) {
        return res.status(403).json({ message: "Access denied - not a group member" });
      }

      const messageData = {
        studyGroupId: req.params.id,
        senderId: user.id,
        ...req.body
      };

      const message = await storage.createMessage(messageData);
      
      // Broadcast message via WebSocket if available
      if (wsServer) {
        const messageForBroadcast = {
          type: 'new_message',
          studyGroupId: req.params.id,
          message
        };
        
        wsServer.clients.forEach(client => {
          if (client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify(messageForBroadcast));
          }
        });
      }

      res.status(201).json(message);
    } catch (error) {
      console.error("Error posting message:", error);
      res.status(500).json({ message: "Failed to post message" });
    }
  });

  // Get study group whiteboards
  app.get("/api/study-groups/:id/whiteboards", requireAuth, async (req, res) => {
    try {
      const user = await storage.getUserById(req.session!.userId!);
      if (!user) {
        return res.status(401).json({ message: "User not found" });
      }

      // Verify user is a member
      const members = await storage.getStudyGroupMembers(req.params.id);
      const isMember = members.some(member => member.userId === user.id);
      
      if (!isMember) {
        return res.status(403).json({ message: "Access denied - not a group member" });
      }

      const whiteboards = await storage.getStudyGroupWhiteboards(req.params.id);
      res.json(whiteboards);
    } catch (error) {
      console.error("Error fetching whiteboards:", error);
      res.status(500).json({ message: "Failed to fetch whiteboards" });
    }
  });

  // Create whiteboard
  app.post("/api/study-groups/:id/whiteboards", requireAuth, async (req, res) => {
    try {
      const user = await storage.getUserById(req.session!.userId!);
      if (!user) {
        return res.status(401).json({ message: "User not found" });
      }

      // Verify user is a member
      const members = await storage.getStudyGroupMembers(req.params.id);
      const isMember = members.some(member => member.userId === user.id);
      
      if (!isMember) {
        return res.status(403).json({ message: "Access denied - not a group member" });
      }

      const whiteboardData = {
        studyGroupId: req.params.id,
        createdBy: user.id,
        ...req.body
      };

      const whiteboard = await storage.createWhiteboard(whiteboardData);
      res.status(201).json(whiteboard);
    } catch (error) {
      console.error("Error creating whiteboard:", error);
      res.status(500).json({ message: "Failed to create whiteboard" });
    }
  });

  // Update whiteboard
  app.put("/api/whiteboards/:id", requireAuth, async (req, res) => {
    try {
      const user = await storage.getUserById(req.session!.userId!);
      if (!user) {
        return res.status(401).json({ message: "User not found" });
      }

      const whiteboard = await storage.updateWhiteboard(req.params.id, req.body.data);
      
      // Broadcast whiteboard update via WebSocket
      if (wsServer) {
        const updateForBroadcast = {
          type: 'whiteboard_update',
          whiteboardId: req.params.id,
          data: req.body.data,
          updatedBy: user.id
        };
        
        wsServer.clients.forEach(client => {
          if (client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify(updateForBroadcast));
          }
        });
      }

      res.json(whiteboard);
    } catch (error) {
      console.error("Error updating whiteboard:", error);
      res.status(500).json({ message: "Failed to update whiteboard" });
    }
  });

  // Setup HTTP server and WebSocket for real-time features
  const httpServer = createServer(app);
  
  // WebSocket server for real-time collaboration
  const wss = new WebSocketServer({ 
    server: httpServer, 
    path: '/ws' 
  });

  // Make wss available in the scope for message broadcasting
  let wsServer = wss;

  wss.on('connection', (ws, req) => {
    console.log('New WebSocket connection established');

    ws.on('message', async (message) => {
      try {
        const data = JSON.parse(message.toString());
        
        switch (data.type) {
          case 'join_group':
            // Join user to a study group room
            ws.studyGroupId = data.studyGroupId;
            ws.userId = data.userId;
            
            // Broadcast user joined
            const joinMessage = {
              type: 'user_joined',
              studyGroupId: data.studyGroupId,
              userId: data.userId,
              timestamp: new Date().toISOString()
            };
            
            wsServer.clients.forEach(client => {
              if (client !== ws && 
                  client.studyGroupId === data.studyGroupId && 
                  client.readyState === WebSocket.OPEN) {
                client.send(JSON.stringify(joinMessage));
              }
            });
            break;

          case 'typing':
            // Broadcast typing indicator
            const typingMessage = {
              type: 'user_typing',
              studyGroupId: data.studyGroupId,
              userId: data.userId,
              isTyping: data.isTyping,
              timestamp: new Date().toISOString()
            };
            
            wsServer.clients.forEach(client => {
              if (client !== ws && 
                  client.studyGroupId === data.studyGroupId && 
                  client.readyState === WebSocket.OPEN) {
                client.send(JSON.stringify(typingMessage));
              }
            });
            break;

          case 'whiteboard_stroke':
            // Broadcast whiteboard drawing in real-time
            const strokeMessage = {
              type: 'whiteboard_stroke',
              studyGroupId: data.studyGroupId,
              stroke: data.stroke,
              userId: data.userId,
              timestamp: new Date().toISOString()
            };
            
            wsServer.clients.forEach(client => {
              if (client !== ws && 
                  client.studyGroupId === data.studyGroupId && 
                  client.readyState === WebSocket.OPEN) {
                client.send(JSON.stringify(strokeMessage));
              }
            });
            break;

          case 'cursor_position':
            // Broadcast cursor position for collaboration
            const cursorMessage = {
              type: 'cursor_position',
              studyGroupId: data.studyGroupId,
              userId: data.userId,
              position: data.position,
              timestamp: new Date().toISOString()
            };
            
            wsServer.clients.forEach(client => {
              if (client !== ws && 
                  client.studyGroupId === data.studyGroupId && 
                  client.readyState === WebSocket.OPEN) {
                client.send(JSON.stringify(cursorMessage));
              }
            });
            break;
        }
      } catch (error) {
        console.error('WebSocket message error:', error);
      }
    });

    ws.on('close', () => {
      console.log('WebSocket connection closed');
      
      // Broadcast user left if they were in a group
      if (ws.studyGroupId && ws.userId) {
        const leaveMessage = {
          type: 'user_left',
          studyGroupId: ws.studyGroupId,
          userId: ws.userId,
          timestamp: new Date().toISOString()
        };
        
        wsServer.clients.forEach(client => {
          if (client.studyGroupId === ws.studyGroupId && 
              client.readyState === WebSocket.OPEN) {
            client.send(JSON.stringify(leaveMessage));
          }
        });
      }
    });

    // Send welcome message
    ws.send(JSON.stringify({
      type: 'connected',
      message: 'WebSocket connected successfully',
      timestamp: new Date().toISOString()
    }));
  });

  return httpServer;
}

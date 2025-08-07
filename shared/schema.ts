import { sql } from "drizzle-orm";
import { pgTable, text, varchar, integer, decimal, timestamp, boolean, jsonb, pgEnum, index } from "drizzle-orm/pg-core";
import { createInsertSchema, createSelectSchema } from "drizzle-zod";
import { z } from "zod";
import { nanoid } from "nanoid";

// Enums pour les rôles et statuts
// Session storage table for Replit Auth
export const sessions = pgTable(
  "sessions",
  {
    sid: varchar("sid").primaryKey(),
    sess: jsonb("sess").notNull(),
    expire: timestamp("expire").notNull(),
  },
  (table) => [index("IDX_session_expire").on(table.expire)],
);

export const userRoleEnum = pgEnum("user_role", ["super_admin", "admin", "manager", "formateur", "apprenant"]);
export const courseTypeEnum = pgEnum("course_type", ["synchrone", "asynchrone"]);
export const sessionStatusEnum = pgEnum("session_status", ["draft", "pending_approval", "approved", "active", "completed", "archived"]);
export const notificationTypeEnum = pgEnum("notification_type", ["course_enrollment", "assessment_graded", "course_published", "assessment_approved", "assessment_rejected", "new_announcement", "system_update", "deadline_reminder"]);
export const studyGroupStatusEnum = pgEnum("study_group_status", ["active", "archived", "scheduled"]);
export const messageTypeEnum = pgEnum("message_type", ["text", "file", "image", "link", "poll", "whiteboard"]);

// Table des établissements (multi-établissements)
// Cette table reste dans la BD principale pour gérer l'accès global
export const establishments = pgTable("establishments", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  name: text("name").notNull(),
  slug: text("slug").notNull().unique(), // URL-friendly identifier
  description: text("description"),
  logo: text("logo"),
  domain: text("domain"), // Custom domain if any
  databaseUrl: text("database_url"), // URL de la BD spécifique à cet établissement
  isActive: boolean("is_active").default(true),
  settings: jsonb("settings"), // Configuration spécifique à l'établissement
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des thèmes pour personnalisation
export const themes = pgTable("themes", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  establishmentId: varchar("establishment_id").references(() => establishments.id),
  name: text("name").notNull(),
  isActive: boolean("is_active").default(false),
  
  // Variables CSS pour personnalisation
  headerSettings: jsonb("header_settings"), // Logo, menu, couleurs header
  bodySettings: jsonb("body_settings"), // Couleurs, polices, backgrounds
  footerSettings: jsonb("footer_settings"), // Copyright, liens, réseaux sociaux
  
  // Thème général
  primaryColor: text("primary_color").default("#6366f1"),
  secondaryColor: text("secondary_color").default("#06b6d4"),
  accentColor: text("accent_color").default("#10b981"),
  backgroundColor: text("background_color").default("#ffffff"),
  textColor: text("text_color").default("#1f2937"),
  
  // Typographie
  fontFamily: text("font_family").default("Inter"),
  fontSize: text("font_size").default("16px"),
  
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des contenus personnalisables avec architecture WYSIWYG
export const customizable_contents = pgTable("customizable_contents", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  establishmentId: varchar("establishment_id").references(() => establishments.id),
  blockKey: text("block_key").notNull(), // Clé unique pour identifier le contenu
  blockType: text("block_type").notNull(), // type du bloc: text, html, image, video, card, hero, etc.
  content: text("content").notNull(), // Contenu réel
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des pages personnalisables (architecture WYSIWYG)
export const customizable_pages = pgTable("customizable_pages", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  establishmentId: varchar("establishment_id").references(() => establishments.id),
  pageName: text("page_name").notNull(), // home, dashboard, courses, trainer, formations, etc.
  pageTitle: text("page_title").notNull(),
  pageDescription: text("page_description"),
  layout: jsonb("layout").notNull(), // Structure complète de la page
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des composants réutilisables
export const page_components = pgTable("page_components", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  establishmentId: varchar("establishment_id").references(() => establishments.id),
  componentName: text("component_name").notNull(),
  componentType: text("component_type").notNull(), // hero, card, list, form, etc.
  componentData: jsonb("component_data").notNull(), // Configuration du composant
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des sections de page (header, body, footer)
export const page_sections = pgTable("page_sections", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  pageId: varchar("page_id").references(() => customizable_pages.id),
  sectionType: text("section_type").notNull(), // header, body, footer
  sectionOrder: integer("section_order").default(0),
  components: jsonb("components").notNull(), // Liste des composants dans cette section
  sectionStyles: jsonb("section_styles"), // Styles CSS personnalisés
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des éléments de menu
export const menu_items = pgTable("menu_items", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  establishmentId: varchar("establishment_id").references(() => establishments.id),
  label: text("label").notNull(),
  url: text("url").notNull(),
  icon: text("icon"),
  parentId: varchar("parent_id"), // Self-reference, will be handled at DB level
  sortOrder: integer("sort_order").default(0),
  isActive: boolean("is_active").default(true),
  permissions: jsonb("permissions"), // role-based visibility
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des utilisateurs pour l'authentification locale
export const users = pgTable("users", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  establishmentId: varchar("establishment_id").references(() => establishments.id),
  email: text("email").notNull().unique(),
  username: text("username").notNull(),
  password: text("password").notNull(),
  firstName: text("first_name"),
  lastName: text("last_name"),
  profileImageUrl: text("profile_image_url"),
  role: userRoleEnum("role").default("apprenant"),
  phoneNumber: text("phone_number"),
  department: text("department"), // Service/département dans l'établissement
  position: text("position"), // Poste/fonction
  bio: text("bio"), // Biographie/présentation
  preferences: jsonb("preferences"), // Préférences utilisateur
  metadata: jsonb("metadata"), // Données additionnelles flexibles
  isActive: boolean("is_active").default(true),
  isEmailVerified: boolean("is_email_verified").default(false),
  permissions: jsonb("permissions"), // Permissions granulaires (legacy)
  lastLoginAt: timestamp("last_login_at"),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des permissions granulaires
export const permissions = pgTable("permissions", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  name: text("name").notNull().unique(), // Nom de la permission
  resource: text("resource").notNull(), // Ressource concernée (users, courses, content, etc.)
  action: text("action").notNull(), // Action (create, read, update, delete, manage)
  description: text("description"),
  createdAt: timestamp("created_at").defaultNow(),
});

// Table de liaison rôles-permissions
export const rolePermissions = pgTable("role_permissions", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  role: userRoleEnum("role").notNull(),
  permissionId: varchar("permission_id").references(() => permissions.id),
  createdAt: timestamp("created_at").defaultNow(),
});

// Table des permissions personnalisées par utilisateur
export const userPermissions = pgTable("user_permissions", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  userId: varchar("user_id").references(() => users.id),
  permissionId: varchar("permission_id").references(() => permissions.id),
  granted: boolean("granted").default(true), // true = accordée, false = révoquée
  grantedBy: varchar("granted_by").references(() => users.id),
  reason: text("reason"), // Raison de l'attribution/révocation
  expiresAt: timestamp("expires_at"), // Permission temporaire
  createdAt: timestamp("created_at").defaultNow(),
});

// Table des espaces formateurs
export const trainer_spaces = pgTable("trainer_spaces", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  establishmentId: varchar("establishment_id").references(() => establishments.id),
  trainerId: varchar("trainer_id").references(() => users.id),
  name: text("name").notNull(),
  description: text("description"),
  isActive: boolean("is_active").default(false), // Nécessite validation
  approvedBy: varchar("approved_by").references(() => users.id),
  approvedAt: timestamp("approved_at"),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des cours étendue
export const courses = pgTable("courses", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  establishmentId: varchar("establishment_id").references(() => establishments.id),
  
  title: text("title").notNull(),
  description: text("description").notNull(),
  shortDescription: text("short_description"),
  category: text("category").notNull(),
  type: text("type").default("asynchrone"),
  
  // Pricing et accès
  price: decimal("price", { precision: 10, scale: 2 }).default("0"),
  isFree: boolean("is_free").default(false),
  
  // Métadonnées
  duration: integer("duration"), // en heures
  level: text("level").default("debutant"), // debutant, intermediaire, avance
  language: text("language").default("fr"),
  tags: text("tags").array(),
  
  // Médias
  imageUrl: text("image_url"),
  thumbnailUrl: text("thumbnail_url"),
  videoTrailerUrl: text("video_trailer_url"),
  
  // Instructor et visibilité
  instructorId: text("instructor_id"),
  isPublic: boolean("is_public").default(false),
  
  // Statut et validation
  isActive: boolean("is_active").default(false),
  
  // Statistiques
  rating: decimal("rating", { precision: 3, scale: 2 }).default("0"),
  enrollmentCount: integer("enrollment_count").default(0),
  
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des sessions de formation
export const training_sessions = pgTable("training_sessions", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  courseId: varchar("course_id").references(() => courses.id),
  name: text("name").notNull(),
  description: text("description"),
  
  // Planification
  startDate: timestamp("start_date"),
  endDate: timestamp("end_date"),
  maxParticipants: integer("max_participants"),
  
  // Visioconférence (pour cours synchrones)
  meetingUrl: text("meeting_url"),
  meetingId: text("meeting_id"),
  meetingPlatform: text("meeting_platform"), // zoom, meet, bigbluebutton
  
  // Statut
  status: sessionStatusEnum("status").default("draft"),
  isArchived: boolean("is_archived").default(false),
  archiveData: jsonb("archive_data"), // Données d'archive complètes
  
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des inscriptions utilisateur-cours
export const user_courses = pgTable("user_courses", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  userId: varchar("user_id").references(() => users.id),
  courseId: varchar("course_id").references(() => courses.id),
  sessionId: varchar("session_id").references(() => training_sessions.id),
  
  // Progression et statut
  progress: integer("progress").default(0), // 0-100%
  status: text("status").default("enrolled"), // enrolled, in_progress, completed, dropped
  
  // Dates importantes
  enrolledAt: timestamp("enrolled_at").defaultNow(),
  startedAt: timestamp("started_at"),
  completedAt: timestamp("completed_at"),
  lastAccessedAt: timestamp("last_accessed_at"),
  
  // Évaluation
  finalGrade: decimal("final_grade", { precision: 5, scale: 2 }),
  certificateUrl: text("certificate_url"),
  
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des modules de cours
export const course_modules = pgTable("course_modules", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  courseId: varchar("course_id").references(() => courses.id),
  title: text("title").notNull(),
  description: text("description"),
  orderIndex: integer("order_index").notNull(),
  
  // Contenu
  contentType: text("content_type"), // video, text, scorm, h5p, quiz
  contentUrl: text("content_url"),
  contentData: jsonb("content_data"), // Métadonnées du contenu
  
  // Paramètres
  duration: integer("duration"), // en minutes
  isRequired: boolean("is_required").default(true),
  isActive: boolean("is_active").default(true),
  
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table pour les plugins pédagogiques
export const educational_plugins = pgTable("educational_plugins", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  establishmentId: varchar("establishment_id").references(() => establishments.id),
  name: text("name").notNull(),
  type: text("type").notNull(), // scorm, h5p, moodle_backup
  version: text("version"),
  filePath: text("file_path"),
  metadata: jsonb("metadata"),
  isActive: boolean("is_active").default(true),
  uploadedBy: varchar("uploaded_by").references(() => users.id),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table de progression des utilisateurs dans les modules
export const user_module_progress = pgTable("user_module_progress", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  userId: varchar("user_id").references(() => users.id, { onDelete: "cascade" }).notNull(),
  moduleId: varchar("module_id").references(() => course_modules.id, { onDelete: "cascade" }).notNull(),
  status: text("status").default("not_started"), // not_started, in_progress, completed, failed
  progressPercentage: integer("progress_percentage").default(0),
  score: integer("score"), // pour les quiz et évaluations
  timeSpent: integer("time_spent").default(0), // en minutes
  attempts: integer("attempts").default(0),
  lastAccessedAt: timestamp("last_accessed_at"),
  completedAt: timestamp("completed_at"),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des évaluations et quiz
export const assessments = pgTable("assessments", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  courseId: varchar("course_id").references(() => courses.id, { onDelete: "cascade" }),
  moduleId: varchar("module_id").references(() => course_modules.id, { onDelete: "cascade" }),
  establishmentId: varchar("establishment_id").references(() => establishments.id, { onDelete: "cascade" }).notNull(),
  title: text("title").notNull(),
  description: text("description"),
  assessmentType: text("assessment_type").notNull(), // quiz, assignment, exam, project
  questions: jsonb("questions"), // structure des questions et réponses
  maxScore: integer("max_score").default(100),
  passingScore: integer("passing_score").default(60),
  timeLimit: integer("time_limit"), // en minutes
  maxAttempts: integer("max_attempts").default(1),
  isPublic: boolean("is_public").default(true),
  dueDate: timestamp("due_date"),
  
  // Système de validation manager
  status: text("status").default("draft"), // draft, pending_approval, approved, rejected
  approvedBy: varchar("approved_by").references(() => users.id),
  approvedAt: timestamp("approved_at"),
  rejectionReason: text("rejection_reason"),
  
  createdBy: varchar("created_by").references(() => users.id),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des tentatives d'évaluation
export const assessment_attempts = pgTable("assessment_attempts", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  assessmentId: varchar("assessment_id").references(() => assessments.id, { onDelete: "cascade" }).notNull(),
  userId: varchar("user_id").references(() => users.id, { onDelete: "cascade" }).notNull(),
  answers: jsonb("answers"), // réponses de l'utilisateur
  score: integer("score").default(0),
  maxScore: integer("max_score").default(100),
  status: text("status").default("in_progress"), // in_progress, submitted, graded, failed
  timeSpent: integer("time_spent").default(0), // en minutes
  startedAt: timestamp("started_at").defaultNow(),
  submittedAt: timestamp("submitted_at"),
  gradedAt: timestamp("graded_at"),
  feedback: text("feedback"),
  gradedBy: varchar("graded_by").references(() => users.id),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des certificats
export const certificates = pgTable("certificates", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  userId: varchar("user_id").references(() => users.id, { onDelete: "cascade" }).notNull(),
  courseId: varchar("course_id").references(() => courses.id, { onDelete: "cascade" }).notNull(),
  establishmentId: varchar("establishment_id").references(() => establishments.id, { onDelete: "cascade" }).notNull(),
  certificateNumber: varchar("certificate_number").unique().notNull(),
  title: text("title").notNull(),
  description: text("description"),
  issueDate: timestamp("issue_date").defaultNow(),
  expiryDate: timestamp("expiry_date"),
  verificationCode: varchar("verification_code").unique(),
  templateUrl: text("template_url"),
  certificateUrl: text("certificate_url"), // PDF généré
  isValid: boolean("is_valid").default(true),
  issuedBy: varchar("issued_by").references(() => users.id),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des notifications
export const notifications = pgTable("notifications", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  userId: varchar("user_id").references(() => users.id, { onDelete: "cascade" }).notNull(),
  establishmentId: varchar("establishment_id").references(() => establishments.id, { onDelete: "cascade" }).notNull(),
  type: notificationTypeEnum("type").notNull(),
  title: text("title").notNull(),
  message: text("message").notNull(),
  data: jsonb("data"), // Additional data for the notification
  isRead: boolean("is_read").default(false),
  readAt: timestamp("read_at"),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Table des jobs d'export/archivage
export const exportJobs = pgTable("export_jobs", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  userId: varchar("user_id").references(() => users.id, { onDelete: "cascade" }).notNull(),
  establishmentId: varchar("establishment_id").references(() => establishments.id, { onDelete: "cascade" }).notNull(),
  type: text("type").notNull(), // 'zip', 'pdf', 'csv', 'sql'
  status: text("status").default("pending"), // 'pending', 'processing', 'completed', 'failed'
  progress: integer("progress").default(0),
  filename: text("filename").notNull(),
  filePath: text("file_path"),
  fileSize: integer("file_size"),
  downloadUrl: text("download_url"),
  config: jsonb("config"), // Configuration de l'export (dateRange, includeData, etc.)
  error: text("error"),
  startedAt: timestamp("started_at"),
  completedAt: timestamp("completed_at"),
  expiresAt: timestamp("expires_at"),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// ===== SCHEMAS ZOD POUR VALIDATION =====

// Schemas d'insertion
export const insertEstablishmentSchema = createInsertSchema(establishments).pick({
  name: true,
  slug: true,
  description: true,
  logo: true,
  domain: true,
  settings: true,
});

export const insertSimpleThemeSchema = z.object({
  establishmentId: z.string(),
  name: z.string(),
  primaryColor: z.string().optional(),
  secondaryColor: z.string().optional(),
  accentColor: z.string().optional(),
  backgroundColor: z.string().optional(),
  textColor: z.string().optional(),
  fontFamily: z.string().optional(),
  fontSize: z.string().optional(),
});

export const insertSimpleCustomizableContentSchema = z.object({
  establishmentId: z.string(),
  blockKey: z.string(),
  blockType: z.string(),
  content: z.string(),
});

export const insertSimpleMenuItemSchema = z.object({
  establishmentId: z.string(),
  label: z.string(),
  url: z.string(),
  icon: z.string().optional(),
  parentId: z.string().optional(),
  sortOrder: z.number().optional(),
  permissions: z.any().optional(),
});

// Schemas WYSIWYG
export const insertCustomizablePageSchema = z.object({
  establishmentId: z.string(),
  pageName: z.string(),
  pageTitle: z.string(),
  pageDescription: z.string().optional(),
  layout: z.any(),
});

// Schema pour les exports
export const insertExportJobSchema = createInsertSchema(exportJobs).pick({
  type: true,
  filename: true,
  config: true,
}).extend({
  type: z.enum(['zip', 'pdf', 'csv', 'sql']),
  config: z.object({
    dateRange: z.object({
      start: z.string(),
      end: z.string(),
    }),
    includeData: z.object({
      courses: z.boolean(),
      users: z.boolean(),
      assessments: z.boolean(),
      results: z.boolean(),
      content: z.boolean(),
    }),
  }),
});

export type InsertExportJob = z.infer<typeof insertExportJobSchema>;
export type ExportJob = typeof exportJobs.$inferSelect;

export const insertPageComponentSchema = z.object({
  establishmentId: z.string(),
  componentName: z.string(),
  componentType: z.string(),
  componentData: z.any(),
});

export const insertPageSectionSchema = z.object({
  pageId: z.string(),
  sectionType: z.string(),
  sectionOrder: z.number().optional(),
  components: z.any(),
  sectionStyles: z.any().optional(),
});

// Types inférés pour les nouvelles tables
export type CustomizablePage = typeof customizable_pages.$inferSelect;
export type InsertCustomizablePage = typeof customizable_pages.$inferInsert;

export type PageComponent = typeof page_components.$inferSelect;
export type InsertPageComponent = typeof page_components.$inferInsert;

export type PageSection = typeof page_sections.$inferSelect;
export type InsertPageSection = typeof page_sections.$inferInsert;

export const insertUserSchema = createInsertSchema(users).pick({
  establishmentId: true,
  email: true,
  username: true,
  password: true,
  firstName: true,
  lastName: true,
  role: true,
  permissions: true,
});

export const insertTrainerSpaceSchema = createInsertSchema(trainer_spaces).pick({
  establishmentId: true,
  trainerId: true,
  name: true,
  description: true,
});

export const insertCourseSchema = createInsertSchema(courses).pick({
  establishmentId: true,
  title: true,
  description: true,
  shortDescription: true,
  category: true,
  type: true,
  price: true,
  isFree: true,
  duration: true,
  level: true,
  language: true,
  tags: true,
  imageUrl: true,
  videoTrailerUrl: true,
});

export const insertTrainingSessionSchema = createInsertSchema(training_sessions).pick({
  courseId: true,
  name: true,
  description: true,
  startDate: true,
  endDate: true,
  maxParticipants: true,
  meetingUrl: true,
  meetingId: true,
  meetingPlatform: true,
});

export const insertAssessmentSchema = createInsertSchema(assessments).pick({
  courseId: true,
  moduleId: true,
  establishmentId: true,
  title: true,
  description: true,
  assessmentType: true,
  questions: true,
  maxScore: true,
  passingScore: true,
  timeLimit: true,
  maxAttempts: true,
  isPublic: true,
  dueDate: true,
  status: true,
  rejectionReason: true,
  createdBy: true,
});

export const insertUserCourseSchema = createInsertSchema(user_courses).pick({
  userId: true,
  courseId: true,
  sessionId: true,
});

export const insertCourseModuleSchema = createInsertSchema(course_modules).pick({
  courseId: true,
  title: true,
  description: true,
  orderIndex: true,
  contentType: true,
  contentUrl: true,
  contentData: true,
  duration: true,
  isRequired: true,
});

export const insertEducationalPluginSchema = createInsertSchema(educational_plugins).pick({
  establishmentId: true,
  name: true,
  type: true,
  version: true,
  filePath: true,
  metadata: true,
  uploadedBy: true,
});

export const insertUserModuleProgressSchema = createInsertSchema(user_module_progress).pick({
  userId: true,
  moduleId: true,
  status: true,
  progressPercentage: true,
  score: true,
  timeSpent: true,
  attempts: true,
});

export const insertAssessmentAttemptSchema = createInsertSchema(assessment_attempts).pick({
  assessmentId: true,
  userId: true,
  answers: true,
  score: true,
  maxScore: true,
  status: true,
  timeSpent: true,
  feedback: true,
  gradedBy: true,
});

export const insertCertificateSchema = createInsertSchema(certificates).pick({
  userId: true,
  courseId: true,
  establishmentId: true,
  certificateNumber: true,
  title: true,
  description: true,
  expiryDate: true,
  verificationCode: true,
  templateUrl: true,
  certificateUrl: true,
  issuedBy: true,
});

// ===== TYPES TYPESCRIPT =====

// Types pour l'authentification locale
export type UpsertUser = typeof users.$inferInsert;
export type Notification = typeof notifications.$inferSelect;
export type InsertNotification = typeof notifications.$inferInsert;

// Types d'insertion
export type InsertEstablishment = z.infer<typeof insertEstablishmentSchema>;
export type InsertSimpleTheme = z.infer<typeof insertSimpleThemeSchema>;
export type InsertSimpleCustomizableContent = z.infer<typeof insertSimpleCustomizableContentSchema>;
export type InsertSimpleMenuItem = z.infer<typeof insertSimpleMenuItemSchema>;
export type InsertUser = z.infer<typeof insertUserSchema>;
export type InsertTrainerSpace = z.infer<typeof insertTrainerSpaceSchema>;
export type InsertCourse = z.infer<typeof insertCourseSchema>;
export type InsertTrainingSession = z.infer<typeof insertTrainingSessionSchema>;
export type InsertAssessment = z.infer<typeof insertAssessmentSchema>;
export type InsertUserCourse = z.infer<typeof insertUserCourseSchema>;
export type InsertCourseModule = z.infer<typeof insertCourseModuleSchema>;
export type InsertEducationalPlugin = z.infer<typeof insertEducationalPluginSchema>;
export type InsertUserModuleProgress = z.infer<typeof insertUserModuleProgressSchema>;
export type InsertAssessmentAttempt = z.infer<typeof insertAssessmentAttemptSchema>;
export type InsertCertificate = z.infer<typeof insertCertificateSchema>;

// Types de sélection
export type Establishment = typeof establishments.$inferSelect;
export type SimpleTheme = {
  id: string;
  establishmentId: string | null;
  name: string;
  isActive: boolean | null;
  primaryColor: string | null;
  secondaryColor: string | null;
  accentColor: string | null;
  backgroundColor: string | null;
  textColor: string | null;
  fontFamily: string | null;
  fontSize: string | null;
  createdAt: Date | null;
  updatedAt: Date | null;
};

export type SimpleCustomizableContent = {
  id: string;
  establishmentId: string | null;
  blockKey: string;
  blockType: string;
  content: string;
  isActive: boolean | null;
  createdAt: Date | null;
  updatedAt: Date | null;
};

export type SimpleMenuItem = {
  id: string;
  establishmentId: string | null;
  label: string;
  url: string;
  icon: string | null;
  parentId: string | null;
  sortOrder: number | null;
  isActive: boolean | null;
  permissions: unknown | null;
  createdAt: Date | null;
  updatedAt: Date | null;
};
export type User = typeof users.$inferSelect;
export type TrainerSpace = typeof trainer_spaces.$inferSelect;
export type Course = typeof courses.$inferSelect;
export type TrainingSession = typeof training_sessions.$inferSelect;
export type UserCourse = typeof user_courses.$inferSelect;
export type CourseModule = typeof course_modules.$inferSelect;
export type EducationalPlugin = typeof educational_plugins.$inferSelect;
export type UserModuleProgress = typeof user_module_progress.$inferSelect;
export type Assessment = typeof assessments.$inferSelect;
export type AssessmentAttempt = typeof assessment_attempts.$inferSelect;
export type Certificate = typeof certificates.$inferSelect;



// Permission tables - À définir plus tard si nécessaire
// export type Permission = typeof permissions.$inferSelect;
// export type RolePermission = typeof rolePermissions.$inferSelect; 
// export type UserPermission = typeof userPermissions.$inferSelect;

// Types étendus pour les vues avec relations
export type UserWithEstablishment = User & {
  establishment?: Establishment;
};

export type CourseWithDetails = Course & {
  establishment?: Establishment;
  trainerSpace?: TrainerSpace;
  trainer?: User;
  modules?: CourseModule[];
  sessions?: TrainingSession[];
};

export type UserCourseWithDetails = UserCourse & {
  user?: User;
  course?: CourseWithDetails;
  session?: TrainingSession;
};

// New tables for documentation and system updates
export const help_contents = pgTable('help_contents', {
  id: text('id').primaryKey().$defaultFn(() => nanoid()),
  establishmentId: text('establishment_id').notNull(),
  title: text('title').notNull(),
  content: text('content').notNull(),
  category: text('category').notNull(), // 'getting-started', 'user-guide', 'admin-guide', etc.
  role: text('role'), // specific role this help is for, null for all roles
  tags: text('tags').array(),
  searchKeywords: text('search_keywords').array(),
  isActive: boolean('is_active').notNull().default(true),
  sortOrder: integer('sort_order').default(0),
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
});

export const system_versions = pgTable('system_versions', {
  id: text('id').primaryKey().$defaultFn(() => nanoid()),
  version: text('version').notNull().unique(),
  title: text('title').notNull(),
  description: text('description').notNull(),
  changelogMarkdown: text('changelog_markdown').notNull(),
  releaseDate: timestamp('release_date').notNull(),
  isActive: boolean('is_active').notNull().default(false),
  isMaintenance: boolean('is_maintenance').notNull().default(false),
  maintenanceMessage: text('maintenance_message'),
  createdBy: text('created_by').notNull(),
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
});

export const establishment_branding = pgTable('establishment_branding', {
  id: text('id').primaryKey().$defaultFn(() => nanoid()),
  establishmentId: text('establishment_id').notNull().unique(),
  logoUrl: text('logo_url'),
  faviconUrl: text('favicon_url'),
  primaryColor: text('primary_color').default('#3b82f6'),
  secondaryColor: text('secondary_color').default('#64748b'),
  accentColor: text('accent_color').default('#10b981'),
  customCss: text('custom_css'),
  navigationConfig: jsonb('navigation_config').$type<{
    showLogo: boolean;
    showSearch: boolean;
    menuItems: Array<{ label: string; url: string; icon: string; }>;
  }>(),
  footerConfig: jsonb('footer_config').$type<{
    showCopyright: boolean;
    customText: string;
    links: Array<{ label: string; url: string; }>;
  }>(),
  isActive: boolean('is_active').notNull().default(true),
  createdAt: timestamp('created_at').notNull().defaultNow(),
  updatedAt: timestamp('updated_at').notNull().defaultNow(),
});

// Zod validation schemas for new tables
export const insertHelpContentSchema = createInsertSchema(help_contents);
export const selectHelpContentSchema = createSelectSchema(help_contents);
export type InsertHelpContent = z.infer<typeof insertHelpContentSchema>;
export type SelectHelpContent = z.infer<typeof selectHelpContentSchema>;

export const insertSystemVersionSchema = createInsertSchema(system_versions);
export const selectSystemVersionSchema = createSelectSchema(system_versions);
export type InsertSystemVersion = z.infer<typeof insertSystemVersionSchema>;
export type SelectSystemVersion = z.infer<typeof selectSystemVersionSchema>;

export const insertEstablishmentBrandingSchema = createInsertSchema(establishment_branding);
export const selectEstablishmentBrandingSchema = createSelectSchema(establishment_branding);
export type InsertEstablishmentBranding = z.infer<typeof insertEstablishmentBrandingSchema>;
export type SelectEstablishmentBranding = z.infer<typeof selectEstablishmentBrandingSchema>;

// ===== COLLABORATIVE STUDY GROUPS =====

// Study Groups table
export const studyGroups = pgTable("study_groups", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  establishmentId: varchar("establishment_id").references(() => establishments.id, { onDelete: "cascade" }).notNull(),
  courseId: varchar("course_id").references(() => courses.id, { onDelete: "cascade" }),
  createdBy: varchar("created_by").references(() => users.id, { onDelete: "cascade" }).notNull(),
  name: text("name").notNull(),
  description: text("description"),
  maxMembers: integer("max_members").default(10),
  currentMembers: integer("current_members").default(1),
  status: studyGroupStatusEnum("status").default("active"),
  isPublic: boolean("is_public").default(true),
  tags: text("tags").array(),
  scheduledDate: timestamp("scheduled_date"),
  endDate: timestamp("end_date"),
  meetingUrl: text("meeting_url"), // For video calls
  settings: jsonb("settings").$type<{
    allowFileSharing: boolean;
    allowScreenShare: boolean;
    allowWhiteboard: boolean;
    allowPolls: boolean;
    moderatorOnly: boolean;
  }>().default({
    allowFileSharing: true,
    allowScreenShare: true,
    allowWhiteboard: true,
    allowPolls: true,
    moderatorOnly: false
  }),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Study Group Members table
export const studyGroupMembers = pgTable("study_group_members", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  studyGroupId: varchar("study_group_id").references(() => studyGroups.id, { onDelete: "cascade" }).notNull(),
  userId: varchar("user_id").references(() => users.id, { onDelete: "cascade" }).notNull(),
  role: text("role").default("member"), // member, moderator, admin
  joinedAt: timestamp("joined_at").defaultNow(),
  lastActive: timestamp("last_active").defaultNow(),
  isActive: boolean("is_active").default(true),
});

// Real-time Messages table
export const studyGroupMessages = pgTable("study_group_messages", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  studyGroupId: varchar("study_group_id").references(() => studyGroups.id, { onDelete: "cascade" }).notNull(),
  senderId: varchar("sender_id").references(() => users.id, { onDelete: "cascade" }).notNull(),
  type: messageTypeEnum("type").default("text"),
  content: text("content").notNull(),
  metadata: jsonb("metadata").$type<{
    fileName?: string;
    fileSize?: number;
    fileUrl?: string;
    imageUrl?: string;
    linkTitle?: string;
    linkDescription?: string;
    pollOptions?: string[];
    pollResults?: Record<string, number>;
    whiteboardData?: any;
    mentions?: string[];
  }>(),
  replyToId: varchar("reply_to_id"),
  editedAt: timestamp("edited_at"),
  isDeleted: boolean("is_deleted").default(false),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Message Reactions table
export const messageReactions = pgTable("message_reactions", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  messageId: varchar("message_id").references(() => studyGroupMessages.id, { onDelete: "cascade" }).notNull(),
  userId: varchar("user_id").references(() => users.id, { onDelete: "cascade" }).notNull(),
  emoji: text("emoji").notNull(), // Unicode emoji
  createdAt: timestamp("created_at").defaultNow(),
});

// Study Sessions table (for scheduled group study sessions)
export const studySessions = pgTable("study_sessions", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  studyGroupId: varchar("study_group_id").references(() => studyGroups.id, { onDelete: "cascade" }).notNull(),
  hostId: varchar("host_id").references(() => users.id, { onDelete: "cascade" }).notNull(),
  title: text("title").notNull(),
  description: text("description"),
  startTime: timestamp("start_time").notNull(),
  endTime: timestamp("end_time").notNull(),
  isRecurring: boolean("is_recurring").default(false),
  recurrencePattern: text("recurrence_pattern"), // weekly, monthly, etc.
  maxParticipants: integer("max_participants"),
  currentParticipants: integer("current_participants").default(0),
  meetingRoomId: varchar("meeting_room_id"), // For WebRTC rooms
  status: text("status").default("scheduled"), // scheduled, active, completed, cancelled
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Study Session Participants table
export const studySessionParticipants = pgTable("study_session_participants", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  sessionId: varchar("session_id").references(() => studySessions.id, { onDelete: "cascade" }).notNull(),
  userId: varchar("user_id").references(() => users.id, { onDelete: "cascade" }).notNull(),
  joinedAt: timestamp("joined_at"),
  leftAt: timestamp("left_at"),
  duration: integer("duration").default(0), // in minutes
  status: text("status").default("registered"), // registered, joined, completed, no_show
});

// Collaborative Whiteboards table
export const whiteboards = pgTable("whiteboards", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  studyGroupId: varchar("study_group_id").references(() => studyGroups.id, { onDelete: "cascade" }).notNull(),
  createdBy: varchar("created_by").references(() => users.id, { onDelete: "cascade" }).notNull(),
  title: text("title").notNull(),
  data: jsonb("data").notNull(), // Canvas drawing data
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Shared Files table
export const sharedFiles = pgTable("shared_files", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  studyGroupId: varchar("study_group_id").references(() => studyGroups.id, { onDelete: "cascade" }).notNull(),
  uploadedBy: varchar("uploaded_by").references(() => users.id, { onDelete: "cascade" }).notNull(),
  fileName: text("file_name").notNull(),
  originalName: text("original_name").notNull(),
  fileSize: integer("file_size").notNull(),
  mimeType: text("mime_type").notNull(),
  fileUrl: text("file_url").notNull(),
  description: text("description"),
  downloads: integer("downloads").default(0),
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow(),
});

// Zod schemas for study groups
export const insertStudyGroupSchema = createInsertSchema(studyGroups).omit({
  id: true,
  currentMembers: true,
  createdAt: true,
  updatedAt: true,
});

export const insertStudyGroupMemberSchema = createInsertSchema(studyGroupMembers).omit({
  id: true,
  joinedAt: true,
  lastActive: true,
});

export const insertStudyGroupMessageSchema = createInsertSchema(studyGroupMessages).omit({
  id: true,
  createdAt: true,
  updatedAt: true,
  isDeleted: true,
});

export const insertStudySessionSchema = createInsertSchema(studySessions).omit({
  id: true,
  currentParticipants: true,
  createdAt: true,
  updatedAt: true,
});

export const insertWhiteboardSchema = createInsertSchema(whiteboards).omit({
  id: true,
  createdAt: true,
  updatedAt: true,
});

export const insertSharedFileSchema = createInsertSchema(sharedFiles).omit({
  id: true,
  downloads: true,
  createdAt: true,
});

// Type exports for study groups
export type StudyGroup = typeof studyGroups.$inferSelect;
export type InsertStudyGroup = z.infer<typeof insertStudyGroupSchema>;

export type StudyGroupMember = typeof studyGroupMembers.$inferSelect;
export type InsertStudyGroupMember = z.infer<typeof insertStudyGroupMemberSchema>;

export type StudyGroupMessage = typeof studyGroupMessages.$inferSelect;
export type InsertStudyGroupMessage = z.infer<typeof insertStudyGroupMessageSchema>;

export type StudySession = typeof studySessions.$inferSelect;
export type InsertStudySession = z.infer<typeof insertStudySessionSchema>;

export type Whiteboard = typeof whiteboards.$inferSelect;
export type InsertWhiteboard = z.infer<typeof insertWhiteboardSchema>;

export type SharedFile = typeof sharedFiles.$inferSelect;
export type InsertSharedFile = z.infer<typeof insertSharedFileSchema>;

// Types with relations
export type StudyGroupWithDetails = StudyGroup & {
  members?: (StudyGroupMember & { user?: User })[];
  course?: Course;
  creator?: User;
  memberCount?: number;
};

export type StudyGroupMessageWithDetails = StudyGroupMessage & {
  sender: {
    id: string;
    firstName: string | null;
    lastName: string | null;
    profileImageUrl: string | null;
  };
  replyTo?: StudyGroupMessage;
  reactions?: (MessageReaction & { user?: User })[];
};

export type MessageReaction = typeof messageReactions.$inferSelect;



import { 
  type User, 
  type UpsertUser,
  type InsertUser,
  type Establishment,
  type InsertEstablishment, 
  type Course, 
  type InsertCourse,
  type UserCourse, 
  type InsertUserCourse,
  type SimpleTheme,
  type InsertSimpleTheme,
  type SimpleCustomizableContent,
  type InsertSimpleCustomizableContent,
  type SimpleMenuItem,
  type InsertSimpleMenuItem,
  type TrainerSpace,
  type InsertTrainerSpace,
  type CourseWithDetails,
  type UserWithEstablishment,
  type Assessment,
  type InsertAssessment,
  type Notification,
  type InsertNotification,
  type InsertExportJob,
  type ExportJob,
  type AssessmentAttempt,
  type InsertAssessmentAttempt,
  type SelectHelpContent,
  type InsertHelpContent,
  type SelectSystemVersion,
  type InsertSystemVersion,
  type SelectEstablishmentBranding,
  type InsertEstablishmentBranding,
  type StudyGroup,
  type InsertStudyGroup,
  type StudyGroupMember,
  type InsertStudyGroupMember,
  type StudyGroupMessage,
  type InsertStudyGroupMessage,
  type StudyGroupWithDetails,
  type StudyGroupMessageWithDetails,
  type Whiteboard,
  type InsertWhiteboard
} from "@shared/schema";
import { db } from "./db";
import { eq, and, or, desc, asc, sql, inArray, count, gte, lte, between } from "drizzle-orm";
import { 
  establishments, 
  users, 
  courses, 
  user_courses, 
  themes,
  customizable_contents,
  customizable_pages,
  page_components,
  page_sections,
  menu_items,
  trainer_spaces,
  assessments,
  assessment_attempts,
  notifications,
  course_modules,
  user_module_progress,
  educational_plugins,
  certificates,
  exportJobs,
  help_contents,
  system_versions,
  establishment_branding,
  studyGroups,
  studyGroupMembers,
  studyGroupMessages,
  whiteboards,
  permissions,
  rolePermissions,
  userPermissions
} from "@shared/schema";

export interface IStorage {
  // Establishment operations
  getEstablishment(id: string): Promise<Establishment | undefined>;
  getEstablishmentBySlug(slug: string): Promise<Establishment | undefined>;
  createEstablishment(establishment: InsertEstablishment): Promise<Establishment>;
  getAllEstablishments(): Promise<Establishment[]>;
  getEstablishments(): Promise<Establishment[]>;
  updateEstablishment(id: string, updates: Partial<InsertEstablishment>): Promise<Establishment | undefined>;
  
  // User operations
  getUser(id: string): Promise<User | undefined>;
  getUserByUsername(username: string, establishmentId: string): Promise<User | undefined>;
  getUserByEmail(email: string, establishmentId: string): Promise<User | undefined>;
  createUser(user: InsertUser): Promise<User>;
  updateUser(id: string, updates: Partial<InsertUser>): Promise<User | undefined>;
  deleteUser(id: string): Promise<void>;
  updateUserLastLogin(userId: string): Promise<void>;
  getUsersByEstablishment(establishmentId: string): Promise<User[]>;
  getAllUsers(): Promise<User[]>;
  getUsers(): Promise<User[]>;
  
  // Replit Auth operations
  upsertUser(user: UpsertUser): Promise<User>;
  
  // Theme operations
  getActiveTheme(establishmentId: string): Promise<SimpleTheme | undefined>;
  getThemesByEstablishment(establishmentId: string): Promise<SimpleTheme[]>;
  createTheme(theme: InsertSimpleTheme): Promise<SimpleTheme>;
  updateTheme(id: string, updates: Partial<InsertSimpleTheme>): Promise<SimpleTheme | undefined>;
  activateTheme(id: string, establishmentId: string): Promise<void>;
  
  // Customizable content operations
  getCustomizableContents(establishmentId: string): Promise<SimpleCustomizableContent[]>;
  getCustomizableContentByKey(establishmentId: string, blockKey: string): Promise<SimpleCustomizableContent | undefined>;
  createCustomizableContent(content: InsertSimpleCustomizableContent): Promise<SimpleCustomizableContent>;
  updateCustomizableContent(id: string, content: Partial<InsertSimpleCustomizableContent>): Promise<SimpleCustomizableContent | undefined>;

  // Menu operations
  getMenuItems(establishmentId: string): Promise<SimpleMenuItem[]>;
  createMenuItem(menuItem: InsertSimpleMenuItem): Promise<SimpleMenuItem>;
  updateMenuItem(id: string, menuItem: Partial<InsertSimpleMenuItem>): Promise<SimpleMenuItem | undefined>;
  deleteMenuItem(id: string): Promise<void>;
  
  // Course operations
  getCourse(id: string): Promise<Course | undefined>;
  getCoursesByEstablishment(establishmentId: string): Promise<CourseWithDetails[]>;
  getCoursesByCategory(category: string, establishmentId: string): Promise<Course[]>;
  createCourse(course: InsertCourse): Promise<Course>;
  updateCourse(id: string, updates: Partial<InsertCourse>): Promise<Course | undefined>;
  deleteCourse(id: string): Promise<void>;
  approveCourse(courseId: string, approvedBy: string): Promise<Course | undefined>;
  
  // Trainer space operations
  getTrainerSpace(id: string): Promise<TrainerSpace | undefined>;
  getTrainerSpacesByEstablishment(establishmentId: string): Promise<TrainerSpace[]>;
  createTrainerSpace(space: InsertTrainerSpace): Promise<TrainerSpace>;
  approveTrainerSpace(spaceId: string, approvedBy: string): Promise<TrainerSpace | undefined>;
  
  // User course operations
  getUserCourses(userId: string): Promise<UserCourse[]>;
  enrollUserInCourse(enrollment: InsertUserCourse): Promise<UserCourse>;
  createUserCourseEnrollment(userId: string, courseId: string, sessionId?: string): Promise<UserCourse>;
  updateCourseProgress(userId: string, courseId: string, progress: number): Promise<UserCourse | undefined>;

  // Assessment operations  
  getAssessment(id: string): Promise<Assessment | undefined>;
  getAssessmentsByEstablishment(establishmentId: string): Promise<Assessment[]>;
  createAssessment(assessment: InsertAssessment): Promise<Assessment>;
  getUserAssessmentAttempts(userId: string, assessmentId?: string): Promise<any[]>;
  startAssessmentAttempt(userId: string, assessmentId: string): Promise<any>;

  // Export operations
  getExportJobs(userId: string, establishmentId: string): Promise<ExportJob[]>;
  createExportJob(job: InsertExportJob & { userId: string; establishmentId: string }): Promise<ExportJob>;
  updateExportJob(id: string, updates: Partial<ExportJob>): Promise<ExportJob | undefined>;
  getExportJob(id: string): Promise<ExportJob | undefined>;

  // Permission operations
  getAllPermissions(): Promise<any[]>;
  getRolePermissions(role: string): Promise<any[]>;
  assignRolePermissions(role: string, permissionIds: string[]): Promise<void>;
  getUserPermissions(userId: string): Promise<any[]>;
  assignUserPermissions(userId: string, permissionIds: string[]): Promise<void>;

  // WYSIWYG operations
  getCustomizablePages(establishmentId: string): Promise<any[]>;
  getCustomizablePageByName(establishmentId: string, pageName: string): Promise<any>;
  createCustomizablePage(page: any): Promise<any>;
  updateCustomizablePage(id: string, updates: any): Promise<any>;
  getPageComponents(establishmentId: string): Promise<any[]>;

  // Additional user operations
  getUserById(id: string): Promise<User | undefined>;

  // Advanced course operations
  getCoursesWithFilters(filters: any): Promise<{ courses: any[], total: number, page: number, totalPages: number }>;
  getPopularCourses(establishmentId: string, limit: number): Promise<any[]>;
  getCourseStats(courseId: string): Promise<any | null>;
  globalSearch(query: string, type: string | undefined, establishmentId: string, limit: number): Promise<any>;
  completeCourse(userId: string, courseId: string): Promise<UserCourse | undefined>;

  // Certificate operations
  generateCourseCertificate(userId: string, courseId: string): Promise<any>;
  getUserCertificates(userId: string): Promise<any[]>;

  // Export and notification operations
  createBulkExport(establishmentId: string, exportType: string, filters: any, createdBy: string): Promise<any>;
  getExportHistory(establishmentId: string, limit?: number): Promise<any[]>;
  createNotification(notificationData: InsertNotification): Promise<Notification>;

  // Dashboard operations
  getDashboardStats(userId: string, establishmentId: string): Promise<any>;
  getDashboardWidgets(userId: string, role: string, establishmentId: string): Promise<any[]>;

  // Assessment operations
  createAssessmentAttempt(insertAttempt: InsertAssessmentAttempt): Promise<AssessmentAttempt>;
  submitAssessmentAttempt(attemptId: string, answers: any, score: number): Promise<AssessmentAttempt | undefined>;

  // Activity logging
  logActivity(establishmentId: string, userId: string, action: string, details: any): Promise<any>;
  
  // Batch operations
  batchEnrollUsers(courseId: string, userIds: string[]): Promise<any[]>;

  // Analytics operations
  getEstablishmentAnalytics(establishmentId: string, dateRange?: { from: Date; to: Date }): Promise<any>;
  getExportTemplates(establishmentId: string): Promise<any[]>;

  // Help content operations
  getHelpContents(establishmentId: string, role?: string, category?: string): Promise<SelectHelpContent[]>;
  getHelpContentById(id: string): Promise<SelectHelpContent | undefined>;
  searchHelpContent(establishmentId: string, query: string, role?: string): Promise<SelectHelpContent[]>;
  createHelpContent(content: InsertHelpContent): Promise<SelectHelpContent>;
  updateHelpContent(id: string, updates: Partial<InsertHelpContent>): Promise<SelectHelpContent | undefined>;
  deleteHelpContent(id: string): Promise<void>;

  // System version operations
  getSystemVersions(): Promise<SelectSystemVersion[]>;
  getActiveSystemVersion(): Promise<SelectSystemVersion | undefined>;
  createSystemVersion(version: InsertSystemVersion): Promise<SelectSystemVersion>;
  activateSystemVersion(id: string): Promise<void>;
  getMaintenanceStatus(): Promise<{ isMaintenance: boolean; message?: string }>;
  setMaintenanceMode(enabled: boolean, message?: string): Promise<void>;

  // Establishment branding operations
  getEstablishmentBranding(establishmentId: string): Promise<SelectEstablishmentBranding | undefined>;
  createEstablishmentBranding(branding: InsertEstablishmentBranding): Promise<SelectEstablishmentBranding>;
  updateEstablishmentBranding(establishmentId: string, updates: Partial<InsertEstablishmentBranding>): Promise<SelectEstablishmentBranding | undefined>;

  // Study group operations
  createStudyGroup(data: InsertStudyGroup): Promise<StudyGroup>;
  getStudyGroupsByEstablishment(establishmentId: string): Promise<StudyGroupWithDetails[]>;
  getStudyGroupById(groupId: string): Promise<StudyGroupWithDetails | null>;
  joinStudyGroup(groupId: string, userId: string): Promise<StudyGroupMember>;
  getStudyGroupMembers(groupId: string): Promise<any[]>;
  createMessage(data: InsertStudyGroupMessage): Promise<StudyGroupMessageWithDetails>;
  getStudyGroupMessages(groupId: string, limit?: number): Promise<StudyGroupMessageWithDetails[]>;

  // Whiteboard operations
  createWhiteboard(data: InsertWhiteboard): Promise<Whiteboard>;
  getStudyGroupWhiteboards(groupId: string): Promise<Whiteboard[]>;
  updateWhiteboard(whiteboardId: string, data: any): Promise<Whiteboard>;
}

export class DatabaseStorage implements IStorage {
  // Establishment operations
  async getEstablishment(id: string): Promise<Establishment | undefined> {
    const [establishment] = await db.select().from(establishments).where(eq(establishments.id, id));
    return establishment;
  }

  async getEstablishmentBySlug(slug: string): Promise<Establishment | undefined> {
    const [establishment] = await db.select().from(establishments).where(eq(establishments.slug, slug));
    return establishment;
  }

  async createEstablishment(insertEstablishment: InsertEstablishment): Promise<Establishment> {
    const [establishment] = await db
      .insert(establishments)
      .values(insertEstablishment)
      .returning();
    return establishment;
  }

  async getAllEstablishments(): Promise<Establishment[]> {
    return await db.select().from(establishments).where(eq(establishments.isActive, true));
  }

  async getEstablishments(): Promise<Establishment[]> {
    return await db.select().from(establishments).where(eq(establishments.isActive, true));
  }

  async updateEstablishment(id: string, updates: Partial<InsertEstablishment>): Promise<Establishment | undefined> {
    const [establishment] = await db
      .update(establishments)
      .set({ ...updates, updatedAt: new Date() })
      .where(eq(establishments.id, id))
      .returning();
    return establishment;
  }

  // Get all users (for super admin purposes)
  async getAllUsers(): Promise<User[]> {
    return await db.select().from(users);
  }

  async getUsers(): Promise<User[]> {
    return await db.select().from(users);
  }

  // Get user by ID (needed for routes)
  async getUserById(id: string): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.id, id));
    return user;
  }

  // User operations
  async getUser(id: string): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.id, id));
    return user;
  }

  async getUserByUsername(username: string, establishmentId: string): Promise<User | undefined> {
    const [user] = await db
      .select()
      .from(users)
      .where(and(eq(users.username, username), eq(users.establishmentId, establishmentId)));
    return user;
  }

  async getUserByEmail(email: string, establishmentId: string): Promise<User | undefined> {
    const [user] = await db
      .select()
      .from(users)
      .where(and(eq(users.email, email), eq(users.establishmentId, establishmentId)));
    return user;
  }

  async createUser(insertUser: InsertUser): Promise<User> {
    const [user] = await db
      .insert(users)
      .values(insertUser)
      .returning();
    return user;
  }

  async updateUser(userId: string, updates: Partial<InsertUser>): Promise<User | undefined> {
    const [user] = await db
      .update(users)
      .set({ ...updates, updatedAt: new Date() })
      .where(eq(users.id, userId))
      .returning();
    return user;
  }

  async deleteUser(userId: string): Promise<void> {
    await db
      .update(users)
      .set({ isActive: false, updatedAt: new Date() })
      .where(eq(users.id, userId));
  }

  async updateUserLastLogin(userId: string): Promise<void> {
    await db
      .update(users)
      .set({ lastLoginAt: new Date(), updatedAt: new Date() })
      .where(eq(users.id, userId));
  }

  async getUsersByEstablishment(establishmentId: string): Promise<User[]> {
    return await db
      .select()
      .from(users)
      .where(and(eq(users.establishmentId, establishmentId), eq(users.isActive, true)));
  }
  
  // Replit Auth implementation
  async upsertUser(userData: UpsertUser): Promise<User> {
    const [user] = await db
      .insert(users)
      .values(userData)
      .onConflictDoUpdate({
        target: users.id,
        set: {
          ...userData,
          updatedAt: new Date(),
        },
      })
      .returning();
    return user;
  }

  // Theme operations
  async getActiveTheme(establishmentId: string): Promise<SimpleTheme | undefined> {
    const [result] = await db
      .select({
        id: themes.id,
        establishmentId: themes.establishmentId,
        name: themes.name,
        isActive: themes.isActive,
        primaryColor: themes.primaryColor,
        secondaryColor: themes.secondaryColor,
        accentColor: themes.accentColor,
        backgroundColor: themes.backgroundColor,
        textColor: themes.textColor,
        fontFamily: themes.fontFamily,
        fontSize: themes.fontSize,
        createdAt: themes.createdAt,
        updatedAt: themes.updatedAt,
      })
      .from(themes)
      .where(and(eq(themes.establishmentId, establishmentId), eq(themes.isActive, true)));
    return result;
  }

  async createTheme(insertTheme: InsertSimpleTheme): Promise<SimpleTheme> {
    const [result] = await db
      .insert(themes)
      .values(insertTheme)
      .returning();
    return {
      id: result.id,
      establishmentId: result.establishmentId,
      name: result.name,
      isActive: result.isActive,
      primaryColor: result.primaryColor,
      secondaryColor: result.secondaryColor,
      accentColor: result.accentColor,
      backgroundColor: result.backgroundColor,
      textColor: result.textColor,
      fontFamily: result.fontFamily,
      fontSize: result.fontSize,
      createdAt: result.createdAt,
      updatedAt: result.updatedAt,
    };
  }

  async updateTheme(id: string, updates: Partial<InsertSimpleTheme>): Promise<SimpleTheme | undefined> {
    const [result] = await db
      .update(themes)
      .set({ ...updates, updatedAt: new Date() })
      .where(eq(themes.id, id))
      .returning();
    
    if (!result) return undefined;
    
    return {
      id: result.id,
      establishmentId: result.establishmentId,
      name: result.name,
      isActive: result.isActive,
      primaryColor: result.primaryColor,
      secondaryColor: result.secondaryColor,
      accentColor: result.accentColor,
      backgroundColor: result.backgroundColor,
      textColor: result.textColor,
      fontFamily: result.fontFamily,
      fontSize: result.fontSize,
      createdAt: result.createdAt,
      updatedAt: result.updatedAt,
    };
  }

  async activateTheme(id: string, establishmentId: string): Promise<void> {
    // Désactiver tous les thèmes de l'établissement
    await db
      .update(themes)
      .set({ isActive: false })
      .where(eq(themes.establishmentId, establishmentId));

    // Activer le thème spécifié
    await db
      .update(themes)
      .set({ isActive: true })
      .where(eq(themes.id, id));
  }

  // Theme operations (nouvelles méthodes)
  async getThemesByEstablishment(establishmentId: string): Promise<SimpleTheme[]> {
    const results = await db
      .select({
        id: themes.id,
        establishmentId: themes.establishmentId,
        name: themes.name,
        isActive: themes.isActive,
        primaryColor: themes.primaryColor,
        secondaryColor: themes.secondaryColor,
        accentColor: themes.accentColor,
        backgroundColor: themes.backgroundColor,
        textColor: themes.textColor,
        fontFamily: themes.fontFamily,
        fontSize: themes.fontSize,
        createdAt: themes.createdAt,
        updatedAt: themes.updatedAt,
      })
      .from(themes)
      .where(eq(themes.establishmentId, establishmentId));
    return results;
  }

  // Customizable content operations (nouvelles méthodes)
  async getCustomizableContents(establishmentId: string): Promise<SimpleCustomizableContent[]> {
    const results = await db
      .select({
        id: customizable_contents.id,
        establishmentId: customizable_contents.establishmentId,
        blockKey: customizable_contents.blockKey,
        blockType: customizable_contents.blockType,
        content: customizable_contents.content,
        isActive: customizable_contents.isActive,
        createdAt: customizable_contents.createdAt,
        updatedAt: customizable_contents.updatedAt,
      })
      .from(customizable_contents)
      .where(and(
        eq(customizable_contents.establishmentId, establishmentId),
        eq(customizable_contents.isActive, true)
      ));
    return results;
  }

  async getCustomizableContentByKey(establishmentId: string, blockKey: string): Promise<SimpleCustomizableContent | undefined> {
    const [result] = await db
      .select({
        id: customizable_contents.id,
        establishmentId: customizable_contents.establishmentId,
        blockKey: customizable_contents.blockKey,
        blockType: customizable_contents.blockType,
        content: customizable_contents.content,
        isActive: customizable_contents.isActive,
        createdAt: customizable_contents.createdAt,
        updatedAt: customizable_contents.updatedAt,
      })
      .from(customizable_contents)
      .where(and(
        eq(customizable_contents.establishmentId, establishmentId),
        eq(customizable_contents.blockKey, blockKey),
        eq(customizable_contents.isActive, true)
      ));
    return result;
  }

  async createCustomizableContent(content: InsertSimpleCustomizableContent): Promise<SimpleCustomizableContent> {
    const [result] = await db
      .insert(customizable_contents)
      .values(content)
      .returning();
    return {
      id: result.id,
      establishmentId: result.establishmentId,
      blockKey: result.blockKey,
      blockType: result.blockType,
      content: result.content,
      isActive: result.isActive,
      createdAt: result.createdAt,
      updatedAt: result.updatedAt,
    };
  }

  async updateCustomizableContent(id: string, content: Partial<InsertSimpleCustomizableContent>): Promise<SimpleCustomizableContent | undefined> {
    const [result] = await db
      .update(customizable_contents)
      .set({ ...content, updatedAt: new Date() })
      .where(eq(customizable_contents.id, id))
      .returning();
    
    if (!result) return undefined;
    
    return {
      id: result.id,
      establishmentId: result.establishmentId,
      blockKey: result.blockKey,
      blockType: result.blockType,
      content: result.content,
      isActive: result.isActive,
      createdAt: result.createdAt,
      updatedAt: result.updatedAt,
    };
  }

  // Menu operations (nouvelles méthodes)
  async getMenuItems(establishmentId: string): Promise<SimpleMenuItem[]> {
    const results = await db
      .select({
        id: menu_items.id,
        establishmentId: menu_items.establishmentId,
        label: menu_items.label,
        url: menu_items.url,
        icon: menu_items.icon,
        parentId: menu_items.parentId,
        sortOrder: menu_items.sortOrder,
        isActive: menu_items.isActive,
        permissions: menu_items.permissions,
        createdAt: menu_items.createdAt,
        updatedAt: menu_items.updatedAt,
      })
      .from(menu_items)
      .where(and(
        eq(menu_items.establishmentId, establishmentId),
        eq(menu_items.isActive, true)
      ))
      .orderBy(menu_items.sortOrder);
    return results;
  }

  async createMenuItem(menuItem: InsertSimpleMenuItem): Promise<SimpleMenuItem> {
    const [result] = await db
      .insert(menu_items)
      .values(menuItem)
      .returning();
    return {
      id: result.id,
      establishmentId: result.establishmentId,
      label: result.label,
      url: result.url,
      icon: result.icon,
      parentId: result.parentId,
      sortOrder: result.sortOrder,
      isActive: result.isActive,
      permissions: result.permissions,
      createdAt: result.createdAt,
      updatedAt: result.updatedAt,
    };
  }

  async updateMenuItem(id: string, menuItem: Partial<InsertSimpleMenuItem>): Promise<SimpleMenuItem | undefined> {
    const [result] = await db
      .update(menu_items)
      .set({ ...menuItem, updatedAt: new Date() })
      .where(eq(menu_items.id, id))
      .returning();
    
    if (!result) return undefined;
    
    return {
      id: result.id,
      establishmentId: result.establishmentId,
      label: result.label,
      url: result.url,
      icon: result.icon,
      parentId: result.parentId,
      sortOrder: result.sortOrder,
      isActive: result.isActive,
      permissions: result.permissions,
      createdAt: result.createdAt,
      updatedAt: result.updatedAt,
    };
  }

  async deleteMenuItem(id: string): Promise<void> {
    await db
      .update(menu_items)
      .set({ isActive: false, updatedAt: new Date() })
      .where(eq(menu_items.id, id));
  }

  // WYSIWYG Pages operations
  async getCustomizablePages(establishmentId: string): Promise<any[]> {
    const results = await db
      .select({
        id: customizable_pages.id,
        establishmentId: customizable_pages.establishmentId,
        pageName: customizable_pages.pageName,
        pageTitle: customizable_pages.pageTitle,
        pageDescription: customizable_pages.pageDescription,
        layout: customizable_pages.layout,
        isActive: customizable_pages.isActive,
        createdAt: customizable_pages.createdAt,
        updatedAt: customizable_pages.updatedAt,
      })
      .from(customizable_pages)
      .where(and(
        eq(customizable_pages.establishmentId, establishmentId),
        eq(customizable_pages.isActive, true)
      ));
    return results;
  }

  async getCustomizablePageByName(establishmentId: string, pageName: string): Promise<any | undefined> {
    const [result] = await db
      .select({
        id: customizable_pages.id,
        establishmentId: customizable_pages.establishmentId,
        pageName: customizable_pages.pageName,
        pageTitle: customizable_pages.pageTitle,
        pageDescription: customizable_pages.pageDescription,
        layout: customizable_pages.layout,
        isActive: customizable_pages.isActive,
        createdAt: customizable_pages.createdAt,
        updatedAt: customizable_pages.updatedAt,
      })
      .from(customizable_pages)
      .where(and(
        eq(customizable_pages.establishmentId, establishmentId),
        eq(customizable_pages.pageName, pageName),
        eq(customizable_pages.isActive, true)
      ));
    return result;
  }

  async createCustomizablePage(pageData: any): Promise<any> {
    const [result] = await db
      .insert(customizable_pages)
      .values(pageData)
      .returning();
    return {
      id: result.id,
      establishmentId: result.establishmentId,
      pageName: result.pageName,
      pageTitle: result.pageTitle,
      pageDescription: result.pageDescription,
      layout: result.layout,
      isActive: result.isActive,
      createdAt: result.createdAt,
      updatedAt: result.updatedAt,
    };
  }

  async updateCustomizablePage(id: string, pageData: any): Promise<any | undefined> {
    const [result] = await db
      .update(customizable_pages)
      .set({ ...pageData, updatedAt: new Date() })
      .where(eq(customizable_pages.id, id))
      .returning();
    
    if (!result) return undefined;
    
    return {
      id: result.id,
      establishmentId: result.establishmentId,
      pageName: result.pageName,
      pageTitle: result.pageTitle,
      pageDescription: result.pageDescription,
      layout: result.layout,
      isActive: result.isActive,
      createdAt: result.createdAt,
      updatedAt: result.updatedAt,
    };
  }

  // Page Components operations
  async getPageComponents(establishmentId: string): Promise<any[]> {
    const results = await db
      .select({
        id: page_components.id,
        establishmentId: page_components.establishmentId,
        componentName: page_components.componentName,
        componentType: page_components.componentType,
        componentData: page_components.componentData,
        isActive: page_components.isActive,
        createdAt: page_components.createdAt,
        updatedAt: page_components.updatedAt,
      })
      .from(page_components)
      .where(and(
        eq(page_components.establishmentId, establishmentId),
        eq(page_components.isActive, true)
      ));
    return results;
  }

  async createPageComponent(componentData: any): Promise<any> {
    const [result] = await db
      .insert(page_components)
      .values(componentData)
      .returning();
    return {
      id: result.id,
      establishmentId: result.establishmentId,
      componentName: result.componentName,
      componentType: result.componentType,
      componentData: result.componentData,
      isActive: result.isActive,
      createdAt: result.createdAt,
      updatedAt: result.updatedAt,
    };
  }

  // Course operations
  async getCourse(id: string): Promise<Course | undefined> {
    const [course] = await db.select().from(courses).where(eq(courses.id, id));
    return course;
  }

  async updateCourse(id: string, updates: Partial<InsertCourse>): Promise<Course | undefined> {
    const [course] = await db
      .update(courses)
      .set({ ...updates, updatedAt: new Date() })
      .where(eq(courses.id, id))
      .returning();
    return course;
  }

  async deleteCourse(id: string): Promise<void> {
    await db
      .update(courses)
      .set({ isActive: false, updatedAt: new Date() })
      .where(eq(courses.id, id));
  }

  async getCoursesByEstablishment(establishmentId: string): Promise<CourseWithDetails[]> {
    const coursesData = await db
      .select({
        course: courses,
        establishment: establishments,
        trainerSpace: trainer_spaces,
        trainer: users,
      })
      .from(courses)
      .leftJoin(establishments, eq(courses.establishmentId, establishments.id))
      .leftJoin(trainer_spaces, eq(courses.instructorId, trainer_spaces.id))
      .leftJoin(users, eq(courses.instructorId, users.id))
      .where(and(eq(courses.establishmentId, establishmentId), eq(courses.isActive, true)));

    return coursesData.map(row => ({
      ...row.course,
      establishment: row.establishment || undefined,
      trainerSpace: row.trainerSpace || undefined,
      trainer: row.trainer || undefined,
    }));
  }

  async getCoursesByCategory(category: string, establishmentId: string): Promise<Course[]> {
    return await db
      .select()
      .from(courses)
      .where(and(
        eq(courses.category, category),
        eq(courses.establishmentId, establishmentId),
        eq(courses.isActive, true)
      ));
  }

  async createCourse(insertCourse: InsertCourse): Promise<Course> {
    const [course] = await db
      .insert(courses)
      .values(insertCourse)
      .returning();
    return course;
  }

  async approveCourse(courseId: string, approvedBy: string): Promise<Course | undefined> {
    const [course] = await db
      .update(courses)
      .set({
        isActive: true,
        updatedAt: new Date(),
      })
      .where(eq(courses.id, courseId))
      .returning();
    return course;
  }

  // Trainer space operations
  async getTrainerSpace(id: string): Promise<TrainerSpace | undefined> {
    const [space] = await db.select().from(trainer_spaces).where(eq(trainer_spaces.id, id));
    return space;
  }

  async getTrainerSpacesByEstablishment(establishmentId: string): Promise<TrainerSpace[]> {
    return await db
      .select()
      .from(trainer_spaces)
      .where(eq(trainer_spaces.establishmentId, establishmentId));
  }

  async createTrainerSpace(insertSpace: InsertTrainerSpace): Promise<TrainerSpace> {
    const [space] = await db
      .insert(trainer_spaces)
      .values(insertSpace)
      .returning();
    return space;
  }

  async approveTrainerSpace(spaceId: string, approvedBy: string): Promise<TrainerSpace | undefined> {
    const [space] = await db
      .update(trainer_spaces)
      .set({
        isActive: true,
        updatedAt: new Date(),
      })
      .where(eq(trainer_spaces.id, spaceId))
      .returning();
    return space;
  }

  // Assessment operations
  async getAssessmentsByEstablishment(establishmentId: string): Promise<Assessment[]> {
    return await db
      .select()
      .from(assessments)
      .where(eq(assessments.establishmentId, establishmentId));
  }

  async getAssessment(id: string): Promise<Assessment | undefined> {
    const [assessment] = await db
      .select()
      .from(assessments)
      .where(eq(assessments.id, id));
    return assessment;
  }

  async createAssessment(insertAssessment: InsertAssessment): Promise<Assessment> {
    const [assessment] = await db
      .insert(assessments)
      .values({
        ...insertAssessment,
        status: "draft",
        createdAt: new Date(),
        updatedAt: new Date(),
      })
      .returning();
    return assessment;
  }

  async updateAssessment(id: string, updates: Partial<InsertAssessment>): Promise<Assessment | undefined> {
    const [assessment] = await db
      .update(assessments)
      .set({ ...updates, updatedAt: new Date() })
      .where(eq(assessments.id, id))
      .returning();
    return assessment;
  }

  async approveAssessment(assessmentId: string, approvedBy: string): Promise<Assessment | undefined> {
    const [assessment] = await db
      .update(assessments)
      .set({
        status: "approved",
        updatedAt: new Date(),
      })
      .where(eq(assessments.id, assessmentId))
      .returning();
    return assessment;
  }

  // Assessment attempts operations
  async getAssessmentAttempts(assessmentId: string, userId?: string): Promise<AssessmentAttempt[]> {
    const conditions = [eq(assessment_attempts.assessmentId, assessmentId)];
    if (userId) {
      conditions.push(eq(assessment_attempts.userId, userId));
    }

    return await db
      .select()
      .from(assessment_attempts)
      .where(and(...conditions))
      .orderBy(assessment_attempts.startedAt);
  }

  async createAssessmentAttempt(insertAttempt: InsertAssessmentAttempt): Promise<AssessmentAttempt> {
    const [attempt] = await db
      .insert(assessment_attempts)
      .values({
        ...insertAttempt,
        status: "in_progress",
        startedAt: new Date(),
        createdAt: new Date(),
        updatedAt: new Date(),
      })
      .returning();
    return attempt;
  }

  async submitAssessmentAttempt(attemptId: string, answers: any, score: number): Promise<AssessmentAttempt | undefined> {
    const [attempt] = await db
      .update(assessment_attempts)
      .set({
        answers,
        score,
        status: "submitted",
        submittedAt: new Date(),
        updatedAt: new Date(),
      })
      .where(eq(assessment_attempts.id, attemptId))
      .returning();
    return attempt;
  }

  // Dashboard statistics operations
  async getDashboardStats(userId: string, establishmentId: string): Promise<any> {
    const user = await this.getUser(userId);
    if (!user) return null;

    // Stats communes
    const [totalCourses] = await db
      .select({ count: sql<number>`count(*)` })
      .from(courses)
      .where(and(eq(courses.establishmentId, establishmentId), eq(courses.isActive, true)));

    const [totalUsers] = await db
      .select({ count: sql<number>`count(*)` })
      .from(users)
      .where(and(eq(users.establishmentId, establishmentId), eq(users.isActive, true)));

    // Stats par rôle
    if (user.role === "apprenant") {
      // Stats pour apprenant
      const [enrolledCourses] = await db
        .select({ count: sql<number>`count(*)` })
        .from(user_courses)
        .where(eq(user_courses.userId, userId));

      const [completedCourses] = await db
        .select({ count: sql<number>`count(*)` })
        .from(user_courses)
        .where(and(eq(user_courses.userId, userId), eq(user_courses.status, "completed")));

      const [averageProgress] = await db
        .select({ avg: sql<number>`avg(progress)` })
        .from(user_courses)
        .where(eq(user_courses.userId, userId));

      return {
        totalCourses: totalCourses.count,
        enrolledCourses: enrolledCourses.count,
        completedCourses: completedCourses.count,
        averageProgress: Math.round(averageProgress.avg || 0),
        role: "apprenant"
      };
    }

    if (["admin", "manager", "super_admin"].includes(user.role || "")) {
      // Stats pour admin/manager
      const [totalAssessments] = await db
        .select({ count: sql<number>`count(*)` })
        .from(assessments)
        .where(eq(assessments.establishmentId, establishmentId));

      const [pendingApprovals] = await db
        .select({ count: sql<number>`count(*)` })
        .from(assessments)
        .where(and(eq(assessments.establishmentId, establishmentId), eq(assessments.status, "pending_approval")));

      return {
        totalCourses: totalCourses.count,
        totalUsers: totalUsers.count,
        totalAssessments: totalAssessments.count,
        pendingApprovals: pendingApprovals.count,
        role: user.role
      };
    }

    return {
      totalCourses: totalCourses.count,
      totalUsers: totalUsers.count,
      role: user.role
    };
  }

  // User course operations
  async getUserCourses(userId: string): Promise<UserCourse[]> {
    return await db
      .select()
      .from(user_courses)
      .where(eq(user_courses.userId, userId));
  }

  async enrollUserInCourse(enrollment: InsertUserCourse): Promise<UserCourse> {
    const [userCourse] = await db
      .insert(user_courses)
      .values(enrollment)
      .returning();
    return userCourse;
  }

  async createUserCourseEnrollment(userId: string, courseId: string, sessionId?: string): Promise<UserCourse> {
    const enrollmentData: any = {
      userId,
      courseId,
      sessionId: sessionId || null,
      status: "enrolled",
      progress: 0,
    };
    
    return await this.enrollUserInCourse(enrollmentData);
  }

  async updateCourseProgress(userId: string, courseId: string, progress: number): Promise<UserCourse | undefined> {
    const updateData: any = {
      progress,
      lastAccessedAt: new Date(),
      updatedAt: new Date(),
    };

    if (progress >= 100) {
      updateData.completedAt = new Date();
      updateData.status = "completed";
    } else if (progress > 0) {
      updateData.status = "in_progress";
      if (!updateData.startedAt) {
        updateData.startedAt = new Date();
      }
    }

    const [userCourse] = await db
      .update(user_courses)
      .set(updateData)
      .where(and(eq(user_courses.userId, userId), eq(user_courses.courseId, courseId)))
      .returning();
    return userCourse;
  }

  // Assessment operations - duplicates removed (already defined above)

  // createAssessment already defined above - removing duplicate

  async getUserAssessmentAttempts(userId: string, assessmentId?: string): Promise<any[]> {
    // For now, return empty array - full implementation would query assessment_attempts table
    return [];
  }

  async startAssessmentAttempt(userId: string, assessmentId: string): Promise<any> {
    // For now, return mock attempt - full implementation would create assessment attempt
    return { 
      id: 'attempt_' + Date.now(),
      attemptId: 'attempt_' + Date.now(),
      userId,
      assessmentId,
      startedAt: new Date().toISOString()
    };
  }

  // Dashboard statistics methods - duplicate removed (already defined above)

  async getDashboardWidgets(userId: string, role: string, establishmentId: string): Promise<any[]> {
    const widgets: any[] = [];

    switch (role) {
      case 'super_admin':
        const establishments = await this.getAllEstablishments();
        const recentUsers = await db.select().from(users).orderBy(desc(users.createdAt)).limit(5);
        
        widgets.push(
          { type: 'establishments', title: 'Établissements', data: establishments },
          { type: 'recent_users', title: 'Utilisateurs récents', data: recentUsers }
        );
        break;
      
      case 'admin':
      case 'manager':
        const recentCourses = await db.select().from(courses)
          .where(eq(courses.establishmentId, establishmentId))
          .orderBy(desc(courses.createdAt)).limit(5);
        const pendingAssessments = await db.select().from(assessments)
          .where(and(eq(assessments.establishmentId, establishmentId), eq(assessments.status, 'pending_approval')))
          .limit(5);
        
        widgets.push(
          { type: 'recent_courses', title: 'Cours récents', data: recentCourses },
          { type: 'pending_assessments', title: 'Évaluations en attente', data: pendingAssessments }
        );
        break;
      
      case 'formateur':
        const instructorCourses = await db.select().from(courses)
          .where(and(eq(courses.establishmentId, establishmentId), eq(courses.instructorId, userId)))
          .limit(5);
        
        widgets.push(
          { type: 'my_courses', title: 'Mes cours', data: instructorCourses }
        );
        break;
      
      case 'apprenant':
        const enrolledCourses = await db.select({
          course: courses,
          progress: user_courses.progress
        }).from(user_courses)
          .innerJoin(courses, eq(user_courses.courseId, courses.id))
          .where(eq(user_courses.userId, userId))
          .limit(5);
        
        widgets.push(
          { type: 'my_progress', title: 'Mes cours', data: enrolledCourses }
        );
        break;
    }

    return widgets;
  }

  // Notifications methods - Real implementation
  async getUserNotifications(userId: string): Promise<Notification[]> {
    return await db.select()
      .from(notifications)
      .where(eq(notifications.userId, userId))
      .orderBy(desc(notifications.createdAt));
  }

  async markNotificationAsRead(notificationId: string, userId: string): Promise<Notification | null> {
    const [notification] = await db.update(notifications)
      .set({ 
        isRead: true, 
        readAt: sql`now()`,
        updatedAt: sql`now()`
      })
      .where(and(
        eq(notifications.id, notificationId),
        eq(notifications.userId, userId)
      ))
      .returning();
    
    return notification || null;
  }

  async createNotification(notificationData: InsertNotification): Promise<Notification> {
    const [notification] = await db.insert(notifications)
      .values(notificationData)
      .returning();
    return notification;
  }

  // Enhanced course methods with filters
  async getCoursesWithFilters(filters: any): Promise<{ courses: any[], total: number, page: number, totalPages: number }> {
    const conditions: any[] = [eq(courses.establishmentId, filters.establishmentId)];
    
    if (filters.category) {
      conditions.push(eq(courses.category, filters.category));
    }
    
    if (filters.level) {
      conditions.push(eq(courses.level, filters.level));
    }
    
    if (filters.search) {
      conditions.push(
        sql`${courses.title} ILIKE ${`%${filters.search}%`} OR ${courses.description} ILIKE ${`%${filters.search}%`}`
      );
    }
    
    if (filters.instructor) {
      conditions.push(eq(courses.instructorId, filters.instructor));
    }

    // Get total count for pagination
    const [{ count: total }] = await db.select({ count: sql<number>`count(*)` }).from(courses).where(and(...conditions));

    // Build query with sorting and pagination
    let queryBuilder = db.select().from(courses).where(and(...conditions));

    // Apply sorting
    if (filters.sortBy === 'title') {
      queryBuilder = queryBuilder.orderBy(filters.sortOrder === 'asc' ? asc(courses.title) : desc(courses.title)) as any;
    } else if (filters.sortBy === 'createdAt') {
      queryBuilder = queryBuilder.orderBy(filters.sortOrder === 'asc' ? asc(courses.createdAt) : desc(courses.createdAt)) as any;
    }

    // Apply pagination
    const offset = (filters.page - 1) * filters.limit;
    const coursesList = await queryBuilder.limit(filters.limit).offset(offset);

    return {
      courses: coursesList,
      total,
      page: filters.page,
      totalPages: Math.ceil(total / filters.limit)
    };
  }

  async getPopularCourses(establishmentId: string, limit: number): Promise<any[]> {
    // Join with user_courses to get enrollment count
    const popularCourses = await db.select({
      id: courses.id,
      title: courses.title,
      description: courses.description,
      category: courses.category,
      level: courses.level,
      duration: courses.duration,
      enrollmentCount: sql<number>`count(${user_courses.id})`
    })
    .from(courses)
    .leftJoin(user_courses, eq(courses.id, user_courses.courseId))
    .where(eq(courses.establishmentId, establishmentId))
    .groupBy(courses.id, courses.title, courses.description, courses.category, courses.level, courses.duration)
    .orderBy(desc(sql`count(${user_courses.id})`))
    .limit(limit);

    return popularCourses;
  }

  async getCourseStats(courseId: string): Promise<any | null> {
    const course = await this.getCourse(courseId);
    if (!course) return null;

    const [{ count: enrollmentCount }] = await db.select({ count: sql<number>`count(*)` })
      .from(user_courses).where(eq(user_courses.courseId, courseId));

    const [{ avg: avgProgress }] = await db.select({ avg: sql<number>`avg(${user_courses.progress})` })
      .from(user_courses).where(eq(user_courses.courseId, courseId));

    return {
      ...course,
      enrollmentCount,
      averageProgress: avgProgress || 0
    };
  }

  // Global search method
  async globalSearch(query: string, type: string | undefined, establishmentId: string, limit: number): Promise<any> {
    const results: any = {};

    if (!type || type === 'courses') {
      results.courses = await db.select().from(courses)
        .where(and(
          eq(courses.establishmentId, establishmentId),
          sql`${courses.title} ILIKE ${`%${query}%`} OR ${courses.description} ILIKE ${`%${query}%`}`
        ))
        .limit(limit);
    }

    if (!type || type === 'users') {
      results.users = await db.select({
        id: users.id,
        firstName: users.firstName,
        lastName: users.lastName,
        email: users.email,
        role: users.role
      }).from(users)
        .where(and(
          eq(users.establishmentId, establishmentId),
          sql`${users.firstName} ILIKE ${`%${query}%`} OR ${users.lastName} ILIKE ${`%${query}%`} OR ${users.email} ILIKE ${`%${query}%`}`
        ))
        .limit(limit);
    }

    if (!type || type === 'assessments') {
      results.assessments = await db.select().from(assessments)
        .where(and(
          eq(assessments.establishmentId, establishmentId),
          sql`${assessments.title} ILIKE ${`%${query}%`} OR ${assessments.description} ILIKE ${`%${query}%`}`
        ))
        .limit(limit);
    }

    return results;
  }

  // Export operations implementation
  async getExportJobs(userId: string, establishmentId: string): Promise<ExportJob[]> {
    return await db.select()
      .from(exportJobs)
      .where(and(
        eq(exportJobs.userId, userId),
        eq(exportJobs.establishmentId, establishmentId)
      ))
      .orderBy(desc(exportJobs.createdAt));
  }

  async createExportJob(job: InsertExportJob & { userId: string; establishmentId: string }): Promise<ExportJob> {
    const [exportJob] = await db.insert(exportJobs)
      .values({
        ...job,
        status: 'pending',
        progress: 0,
        createdAt: new Date(),
        updatedAt: new Date()
      })
      .returning();
    return exportJob;
  }

  async updateExportJob(id: string, updates: Partial<ExportJob>): Promise<ExportJob | undefined> {
    const [exportJob] = await db.update(exportJobs)
      .set({ ...updates, updatedAt: new Date() })
      .where(eq(exportJobs.id, id))
      .returning();
    return exportJob;
  }

  async getExportJob(id: string): Promise<ExportJob | undefined> {
    const [exportJob] = await db.select()
      .from(exportJobs)
      .where(eq(exportJobs.id, id));
    return exportJob;
  }

  // ===== MODULES DE COURS ET PROGRESSION (Priorité 2) =====

  // Course modules operations
  async getCourseModules(courseId: string): Promise<any[]> {
    return await db
      .select()
      .from(course_modules)
      .where(eq(course_modules.courseId, courseId))
      .orderBy(course_modules.title);
  }

  async createCourseModule(moduleData: any): Promise<any> {
    const [module] = await db
      .insert(course_modules)
      .values({
        ...moduleData,
        createdAt: new Date(),
        updatedAt: new Date(),
      })
      .returning();
    return module;
  }

  async updateCourseModule(moduleId: string, updates: any): Promise<any | undefined> {
    const [module] = await db
      .update(course_modules)
      .set({ ...updates, updatedAt: new Date() })
      .where(eq(course_modules.id, moduleId))
      .returning();
    return module;
  }

  async deleteCourseModule(moduleId: string): Promise<boolean> {
    const result = await db
      .delete(course_modules)
      .where(eq(course_modules.id, moduleId))
      .returning();
    return result.length > 0;
  }

  // User module progress operations
  async getUserModuleProgress(userId: string, courseId?: string): Promise<any[]> {
    const conditions = [eq(user_module_progress.userId, userId)];
    if (courseId) {
      // Join avec course_modules pour filtrer par cours
      return await db
        .select({
          progress: user_module_progress,
          module: course_modules
        })
        .from(user_module_progress)
        .innerJoin(course_modules, eq(user_module_progress.moduleId, course_modules.id))
        .where(and(
          eq(user_module_progress.userId, userId),
          eq(course_modules.courseId, courseId)
        ))
        .orderBy(course_modules.title);
    }

    return await db
      .select()
      .from(user_module_progress)
      .where(and(...conditions));
  }

  async updateModuleProgress(userId: string, moduleId: string, progressData: any): Promise<any> {
    // Check if progress record exists
    const [existingProgress] = await db
      .select()
      .from(user_module_progress)
      .where(and(
        eq(user_module_progress.userId, userId),
        eq(user_module_progress.moduleId, moduleId)
      ));

    if (existingProgress) {
      // Update existing progress
      const [updatedProgress] = await db
        .update(user_module_progress)
        .set({
          ...progressData,
          updatedAt: new Date(),
          lastAccessedAt: new Date(),
        })
        .where(and(
          eq(user_module_progress.userId, userId),
          eq(user_module_progress.moduleId, moduleId)
        ))
        .returning();
      return updatedProgress;
    } else {
      // Create new progress record
      const [newProgress] = await db
        .insert(user_module_progress)
        .values({
          userId,
          moduleId,
          ...progressData,
          createdAt: new Date(),
          updatedAt: new Date(),
          lastAccessedAt: new Date(),
        })
        .returning();
      return newProgress;
    }
  }

  async getModuleProgressSummary(userId: string, courseId: string): Promise<any> {
    // Get all modules for the course
    const modules = await this.getCourseModules(courseId);
    
    // Get user's progress for these modules
    const progressData = await this.getUserModuleProgress(userId, courseId);
    
    // Calculate summary statistics
    const totalModules = modules.length;
    const completedModules = progressData.filter(p => p.progress?.isCompleted).length;
    const totalProgress = progressData.reduce((sum, p) => sum + (p.progress?.progressPercentage || 0), 0);
    const averageProgress = totalModules > 0 ? Math.round(totalProgress / totalModules) : 0;

    return {
      totalModules,
      completedModules,
      averageProgress,
      modules: modules.map(module => {
        const progress = progressData.find(p => p.progress?.moduleId === module.id);
        return {
          ...module,
          userProgress: progress?.progress || null
        };
      })
    };
  }

  // Educational plugins operations  
  async getEducationalPlugins(establishmentId: string): Promise<any[]> {
    return await db
      .select()
      .from(educational_plugins)
      .where(and(
        eq(educational_plugins.establishmentId, establishmentId),
        eq(educational_plugins.isActive, true)
      ))
      .orderBy(educational_plugins.name);
  }

  async createEducationalPlugin(pluginData: any): Promise<any> {
    const [plugin] = await db
      .insert(educational_plugins)
      .values({
        ...pluginData,
        createdAt: new Date(),
        updatedAt: new Date(),
      })
      .returning();
    return plugin;
  }

  async updateEducationalPlugin(pluginId: string, updates: any): Promise<any | undefined> {
    const [plugin] = await db
      .update(educational_plugins)
      .set({ ...updates, updatedAt: new Date() })
      .where(eq(educational_plugins.id, pluginId))
      .returning();
    return plugin;
  }

  async togglePluginStatus(pluginId: string, isActive: boolean): Promise<any | undefined> {
    const [plugin] = await db
      .update(educational_plugins)
      .set({ 
        isActive,
        updatedAt: new Date() 
      })
      .where(eq(educational_plugins.id, pluginId))
      .returning();
    return plugin;
  }

  // Course completion and certificates
  async completeCourse(userId: string, courseId: string): Promise<any> {
    // Mark course as completed
    const [userCourse] = await db
      .update(user_courses)
      .set({
        status: "completed",
        progress: 100,
        completedAt: new Date(),
        updatedAt: new Date(),
      })
      .where(and(
        eq(user_courses.userId, userId),
        eq(user_courses.courseId, courseId)
      ))
      .returning();

    // Check if certificate should be generated (simplified check)
    const course = await this.getCourse(courseId);
    if (course) {
      await this.generateCourseCertificate(userId, courseId);
    }

    return userCourse;
  }

  async generateCourseCertificate(userId: string, courseId: string): Promise<any> {
    const user = await this.getUser(userId);
    const course = await this.getCourse(courseId);
    
    if (!user || !course) {
      throw new Error("User or course not found");
    }

    const [certificate] = await db
      .insert(certificates)
      .values({
        userId,
        courseId,
        establishmentId: user.establishmentId || course.establishmentId,
        certificateNumber: `CERT-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
        title: `Certificate of Completion - ${course.title}`,
        description: `Certificate for completing the course: ${course.title}`,
        verificationCode: Math.random().toString(36).substr(2, 12).toUpperCase(),
        issuedBy: userId,
        isValid: true
      })
      .returning();

    return certificate;
  }

  async getUserCertificates(userId: string): Promise<any[]> {
    return await db
      .select({
        certificate: certificates,
        course: courses
      })
      .from(certificates)
      .leftJoin(courses, eq(certificates.courseId, courses.id))
      .where(eq(certificates.userId, userId))
      .orderBy(desc(certificates.issueDate));
  }

  // ===== EXPORTS AVANCÉS ET NOTIFICATIONS (Priorité 3) =====

  // Advanced export operations
  async createBulkExport(establishmentId: string, exportType: string, filters: any, createdBy: string): Promise<any> {
    const [exportJob] = await db
      .insert(exportJobs)
      .values({
        userId: createdBy,
        establishmentId: establishmentId,
        type: exportType,
        status: "pending",
        filename: `export-${Date.now()}.${exportType}`,
        config: filters
      })
      .returning();
    return exportJob;
  }

  async getExportHistory(establishmentId: string, limit: number = 50): Promise<any[]> {
    return await db
      .select({
        exportJob: exportJobs,
        creator: {
          id: users.id,
          firstName: users.firstName,
          lastName: users.lastName,
          email: users.email
        }
      })
      .from(exportJobs)
      .leftJoin(users, eq(exportJobs.userId, users.id))
      .where(eq(exportJobs.establishmentId, establishmentId))
      .orderBy(desc(exportJobs.createdAt))
      .limit(limit);
  }

  async updateExportJobStatus(jobId: string, status: string, downloadUrl?: string, error?: string): Promise<any> {
    const updates: any = {
      status,
      updatedAt: new Date()
    };
    
    if (downloadUrl) updates.downloadUrl = downloadUrl;
    if (error) updates.error = error;
    if (status === 'completed') updates.completedAt = new Date();

    const [updatedJob] = await db
      .update(exportJobs)
      .set(updates)
      .where(eq(exportJobs.id, jobId))
      .returning();
    return updatedJob;
  }

  // Course data export for archiving
  async exportCourseData(courseId: string): Promise<any> {
    const course = await this.getCourse(courseId);
    const modules = await this.getCourseModules(courseId);
    const enrollments = await db
      .select({
        user: {
          id: users.id,
          firstName: users.firstName,
          lastName: users.lastName,
          email: users.email
        },
        enrollment: user_courses,
        progress: sql<any>`json_agg(DISTINCT ${user_module_progress}.*)`
      })
      .from(user_courses)
      .leftJoin(users, eq(user_courses.userId, users.id))
      .leftJoin(user_module_progress, eq(user_module_progress.userId, users.id))
      .where(eq(user_courses.courseId, courseId))
      .groupBy(users.id, user_courses.id);

    return {
      course,
      modules,
      enrollments,
      exportDate: new Date().toISOString()
    };
  }

  // User progress export for individual tracking
  async exportUserProgressData(userId: string, establishmentId: string): Promise<any> {
    const user = await this.getUser(userId);
    const userCourses = await this.getUserCourses(userId);
    const moduleProgress = await this.getUserModuleProgress(userId);
    const assessmentAttempts = await this.getUserAssessmentAttempts(userId);
    const certificates = await this.getUserCertificates(userId);

    return {
      user: {
        id: user?.id,
        firstName: user?.firstName,
        lastName: user?.lastName,
        email: user?.email,
        role: user?.role
      },
      courses: userCourses,
      moduleProgress,
      assessmentAttempts,
      certificates,
      exportDate: new Date().toISOString()
    };
  }

  // Advanced notification system
  async createBulkNotifications(notificationList: any[]): Promise<any[]> {
    const notificationsData = notificationList.map(notif => ({
      ...notif,
      createdAt: new Date(),
      updatedAt: new Date()
    }));

    const createdNotifications = await db
      .insert(notifications)
      .values(notificationsData)
      .returning();
    
    return createdNotifications;
  }

  async getNotificationsByType(establishmentId: string, type: string, limit: number = 100): Promise<any[]> {
    return await db
      .select()
      .from(notifications)
      .where(and(
        eq(notifications.establishmentId, establishmentId),
        eq(notifications.type, type as any)
      ))
      .orderBy(desc(notifications.createdAt))
      .limit(limit);
  }

  async markNotificationsAsRead(notificationIds: string[], userId: string): Promise<number> {
    const result = await db
      .update(notifications)
      .set({ 
        isRead: true,
        readAt: new Date(),
        updatedAt: new Date()
      })
      .where(and(
        sql`${notifications.id} = ANY(${notificationIds})`,
        eq(notifications.userId, userId)
      ))
      .returning();
    
    return result.length;
  }

  async getUnreadNotificationCount(userId: string): Promise<number> {
    const [result] = await db
      .select({ count: sql<number>`count(*)` })
      .from(notifications)
      .where(and(
        eq(notifications.userId, userId),
        eq(notifications.isRead, false)
      ));
    
    return result.count;
  }

  // System activity logs for administrators
  async logSystemActivity(establishmentId: string, userId: string, action: string, details: any): Promise<any> {
    const [notification] = await db
      .insert(notifications)
      .values({
        userId,
        establishmentId,
        type: 'system_update',
        title: `Activity: ${action}`,
        message: JSON.stringify(details)
      })
      .returning();
    return notification;
  }

  // ===== PERMISSIONS ET RÔLES (Priorité 1) =====

  async getAllPermissions(): Promise<any[]> {
    return await db.select().from(permissions).orderBy(permissions.resource, permissions.action);
  }

  async getRolePermissions(role: string): Promise<any[]> {
    return await db
      .select({
        permission: permissions,
        rolePermission: rolePermissions
      })
      .from(rolePermissions)
      .innerJoin(permissions, eq(rolePermissions.permissionId, permissions.id))
      .where(eq(rolePermissions.role, role as any));
  }

  async assignRolePermissions(role: string, permissionIds: string[]): Promise<void> {
    // Remove existing permissions for the role
    await db.delete(rolePermissions).where(eq(rolePermissions.role, role as any));

    // Add new permissions
    const rolePermissionData = permissionIds.map(permissionId => ({
      role: role as any,
      permissionId,
      createdAt: new Date()
    }));

    if (rolePermissionData.length > 0) {
      await db.insert(rolePermissions).values(rolePermissionData);
    }
  }

  async getUserPermissions(userId: string): Promise<any[]> {
    // Get permissions from user's role
    const user = await this.getUser(userId);
    if (!user) return [];

    const rolePerms = await this.getRolePermissions(user.role || 'apprenant');
    
    // Get custom user permissions (overrides)
    const userPerms = await db
      .select({
        permission: permissions,
        userPermission: userPermissions
      })
      .from(userPermissions)
      .innerJoin(permissions, eq(userPermissions.permissionId, permissions.id))
      .where(eq(userPermissions.userId, userId));

    // Merge role permissions with user-specific overrides
    const allPermissions = [...rolePerms];
    
    userPerms.forEach(userPerm => {
      const existingIndex = allPermissions.findIndex(p => p.permission.id === userPerm.permission.id);
      if (existingIndex >= 0) {
        // Override existing permission with user-specific setting
        if (userPerm.userPermission.granted) {
          allPermissions[existingIndex] = userPerm;
        } else {
          // Permission revoked for this user
          allPermissions.splice(existingIndex, 1);
        }
      } else if (userPerm.userPermission.granted) {
        // Add additional permission for this user
        allPermissions.push(userPerm);
      }
    });

    return allPermissions;
  }

  async assignUserPermissions(userId: string, permissionIds: string[]): Promise<void> {
    // Remove existing custom permissions for the user
    await db.delete(userPermissions).where(eq(userPermissions.userId, userId));

    // Add new custom permissions
    const userPermissionData = permissionIds.map(permissionId => ({
      userId,
      permissionId,
      granted: true,
      grantedBy: userId, // TODO: Should be the admin who granted the permission
      createdAt: new Date()
    }));

    if (userPermissionData.length > 0) {
      await db.insert(userPermissions).values(userPermissionData);
    }
  }

  // ===== WYSIWYG ET PAGES PERSONNALISABLES =====
  // (Functions already defined above - removing duplicates)

  // System activity logging (merged with logSystemActivity)
  async logActivity(establishmentId: string, userId: string, action: string, details: any): Promise<any> {
    return await this.logSystemActivity(establishmentId, userId, action, details);
  }

  // Batch operations for course management
  async batchEnrollUsers(courseId: string, userIds: string[]): Promise<any[]> {
    const enrollments = userIds.map(userId => ({
      userId,
      courseId,
      status: 'active' as const,
      progress: 0,
      enrolledAt: new Date(),
      createdAt: new Date(),
      updatedAt: new Date()
    }));

    const createdEnrollments = await db
      .insert(user_courses)
      .values(enrollments)
      .returning();

    // Create notifications for enrolled users
    const course = await this.getCourse(courseId);
    if (course && course.establishmentId) {
      const notificationData = userIds.map(userId => ({
        userId,
        establishmentId: course.establishmentId,
        type: 'course_enrollment' as const,
        title: 'Nouvelle inscription',
        message: `Vous avez été inscrit au cours: ${course.title}`
      }));
      
      await this.createBulkNotifications(notificationData);
    }

    return createdEnrollments;
  }

  // Analytics and reporting for exports
  async getEstablishmentAnalytics(establishmentId: string, dateRange?: { from: Date; to: Date }): Promise<any> {
    let dateFilter = eq(users.establishmentId, establishmentId);
    
    if (dateRange) {
      dateFilter = and(
        eq(users.establishmentId, establishmentId),
        sql`${users.createdAt} BETWEEN ${dateRange.from} AND ${dateRange.to}`
      ) as any;
    }

    const [userStats] = await db
      .select({
        totalUsers: sql<number>`count(*)`,
        activeUsers: sql<number>`count(*) filter (where ${users.isActive} = true)`,
        usersByRole: sql<any>`json_object_agg(${users.role}, count(*))`
      })
      .from(users)
      .where(dateFilter);

    const [courseStats] = await db
      .select({
        totalCourses: sql<number>`count(*)`,
        activeCourses: sql<number>`count(*) filter (where ${courses.isActive} = true)`,
        coursesByCategory: sql<any>`json_object_agg(${courses.category}, count(*))`
      })
      .from(courses)
      .where(eq(courses.establishmentId, establishmentId));

    const [enrollmentStats] = await db
      .select({
        totalEnrollments: sql<number>`count(*)`,
        completedCourses: sql<number>`count(*) filter (where ${user_courses.status} = 'completed')`,
        averageProgress: sql<number>`avg(${user_courses.progress})`
      })
      .from(user_courses)
      .innerJoin(courses, eq(user_courses.courseId, courses.id))
      .where(eq(courses.establishmentId, establishmentId));

    return {
      userStats,
      courseStats,
      enrollmentStats,
      generatedAt: new Date().toISOString()
    };
  }

  // Template management for exports
  async getExportTemplates(establishmentId: string): Promise<any[]> {
    // For now, return predefined templates
    return [
      {
        id: 'course_completion_report',
        name: 'Rapport de complétion des cours',
        type: 'course_data',
        description: 'Export détaillé des completions de cours avec statistiques'
      },
      {
        id: 'user_progress_report',
        name: 'Rapport de progression utilisateur',
        type: 'user_data', 
        description: 'Export complet de la progression par utilisateur'
      },
      {
        id: 'assessment_results_report',
        name: 'Rapport des résultats d\'évaluation',
        type: 'assessment_data',
        description: 'Export des résultats et tentatives d\'évaluation'
      },
      {
        id: 'establishment_analytics',
        name: 'Analytiques de l\'établissement',
        type: 'analytics',
        description: 'Statistiques complètes de l\'établissement'
      }
    ];
  }

  // Help content management
  async getHelpContents(establishmentId: string, role?: string, category?: string): Promise<SelectHelpContent[]> {
    let query = db.select().from(help_contents)
      .where(and(
        eq(help_contents.establishmentId, establishmentId),
        eq(help_contents.isActive, true)
      ))
      .orderBy(help_contents.sortOrder, help_contents.createdAt);

    const results = await query;
    
    return results.filter(content => {
      if (role && content.role && content.role !== role) return false;
      if (category && content.category !== category) return false;
      return true;
    });
  }

  async getHelpContentById(id: string): Promise<SelectHelpContent | undefined> {
    const [result] = await db.select().from(help_contents).where(eq(help_contents.id, id));
    return result;
  }

  async searchHelpContent(establishmentId: string, query: string, role?: string): Promise<SelectHelpContent[]> {
    const results = await db.select().from(help_contents)
      .where(and(
        eq(help_contents.establishmentId, establishmentId),
        eq(help_contents.isActive, true)
      ));

    const searchTerms = query.toLowerCase().split(' ');
    
    return results.filter(content => {
      if (role && content.role && content.role !== role) return false;
      
      const searchText = `${content.title} ${content.content}`.toLowerCase();
      const keywordsText = content.searchKeywords?.join(' ').toLowerCase() || '';
      
      return searchTerms.some(term => 
        searchText.includes(term) || keywordsText.includes(term)
      );
    });
  }

  async createHelpContent(content: InsertHelpContent): Promise<SelectHelpContent> {
    const [result] = await db.insert(help_contents).values(content).returning();
    return result;
  }

  async updateHelpContent(id: string, updates: Partial<InsertHelpContent>): Promise<SelectHelpContent | undefined> {
    const [result] = await db
      .update(help_contents)
      .set({ ...updates, updatedAt: new Date() })
      .where(eq(help_contents.id, id))
      .returning();
    return result;
  }

  async deleteHelpContent(id: string): Promise<void> {
    await db.update(help_contents)
      .set({ isActive: false, updatedAt: new Date() })
      .where(eq(help_contents.id, id));
  }

  // System version management
  async getSystemVersions(): Promise<SelectSystemVersion[]> {
    return await db.select().from(system_versions).orderBy(desc(system_versions.releaseDate));
  }

  async getActiveSystemVersion(): Promise<SelectSystemVersion | undefined> {
    const [result] = await db.select().from(system_versions)
      .where(eq(system_versions.isActive, true))
      .orderBy(desc(system_versions.releaseDate));
    return result;
  }

  async createSystemVersion(version: InsertSystemVersion): Promise<SelectSystemVersion> {
    const [result] = await db.insert(system_versions).values(version).returning();
    return result;
  }

  async activateSystemVersion(id: string): Promise<void> {
    // Deactivate all versions first
    await db.update(system_versions).set({ isActive: false });
    
    // Activate the selected version
    await db.update(system_versions)
      .set({ isActive: true, updatedAt: new Date() })
      .where(eq(system_versions.id, id));
  }

  async getMaintenanceStatus(): Promise<{ isMaintenance: boolean; message?: string }> {
    const [activeVersion] = await db.select().from(system_versions)
      .where(eq(system_versions.isActive, true));
      
    if (!activeVersion) {
      return { isMaintenance: false };
    }
    
    return {
      isMaintenance: activeVersion.isMaintenance,
      message: activeVersion.maintenanceMessage || undefined
    };
  }

  async setMaintenanceMode(enabled: boolean, message?: string): Promise<void> {
    const [activeVersion] = await db.select().from(system_versions)
      .where(eq(system_versions.isActive, true));
      
    if (activeVersion) {
      await db.update(system_versions)
        .set({ 
          isMaintenance: enabled, 
          maintenanceMessage: message,
          updatedAt: new Date() 
        })
        .where(eq(system_versions.id, activeVersion.id));
    }
  }

  // Establishment branding
  async getEstablishmentBranding(establishmentId: string): Promise<SelectEstablishmentBranding | undefined> {
    const [result] = await db.select().from(establishment_branding)
      .where(eq(establishment_branding.establishmentId, establishmentId));
    return result;
  }

  async createEstablishmentBranding(branding: InsertEstablishmentBranding): Promise<SelectEstablishmentBranding> {
    const [result] = await db.insert(establishment_branding).values(branding).returning();
    return result;
  }

  async updateEstablishmentBranding(establishmentId: string, updates: Partial<InsertEstablishmentBranding>): Promise<SelectEstablishmentBranding | undefined> {
    const [result] = await db
      .update(establishment_branding)
      .set({ ...updates, updatedAt: new Date() })
      .where(eq(establishment_branding.establishmentId, establishmentId))
      .returning();
    return result;
  }

  // ===== COLLABORATIVE STUDY GROUPS METHODS =====

  // Study Groups CRUD
  async createStudyGroup(data: InsertStudyGroup): Promise<StudyGroup> {
    const [group] = await db
      .insert(studyGroups)
      .values(data)
      .returning();
    
    // Auto-join creator as admin
    await db.insert(studyGroupMembers).values({
      studyGroupId: group.id,
      userId: data.createdBy,
      role: 'admin',
    });

    return group;
  }

  async getStudyGroupsByEstablishment(establishmentId: string): Promise<StudyGroupWithDetails[]> {
    return await db
      .select()
      .from(studyGroups)
      .where(eq(studyGroups.establishmentId, establishmentId))
      .orderBy(desc(studyGroups.createdAt));
  }

  async getStudyGroupById(groupId: string): Promise<StudyGroupWithDetails | null> {
    const [group] = await db
      .select()
      .from(studyGroups)
      .where(eq(studyGroups.id, groupId));

    return group || null;
  }

  async joinStudyGroup(groupId: string, userId: string): Promise<StudyGroupMember> {
    // Check if already a member
    const existing = await db
      .select()
      .from(studyGroupMembers)
      .where(and(
        eq(studyGroupMembers.studyGroupId, groupId),
        eq(studyGroupMembers.userId, userId)
      ));

    if (existing.length > 0) {
      throw new Error("User is already a member of this group");
    }

    // Add member
    const [member] = await db
      .insert(studyGroupMembers)
      .values({ studyGroupId: groupId, userId, role: 'member' })
      .returning();

    // Update member count
    await db
      .update(studyGroups)
      .set({ currentMembers: sql`${studyGroups.currentMembers} + 1` })
      .where(eq(studyGroups.id, groupId));

    return member;
  }

  async getStudyGroupMembers(groupId: string): Promise<any[]> {
    return await db
      .select({
        id: studyGroupMembers.id,
        studyGroupId: studyGroupMembers.studyGroupId,
        userId: studyGroupMembers.userId,
        role: studyGroupMembers.role,
        joinedAt: studyGroupMembers.joinedAt,
        lastActive: studyGroupMembers.lastActive,
        isActive: studyGroupMembers.isActive,
        user: {
          id: users.id,
          firstName: users.firstName,
          lastName: users.lastName,
          email: users.email,
          profileImageUrl: users.profileImageUrl,
        }
      })
      .from(studyGroupMembers)
      .innerJoin(users, eq(studyGroupMembers.userId, users.id))
      .where(eq(studyGroupMembers.studyGroupId, groupId))
      .orderBy(studyGroupMembers.joinedAt);
  }

  // Study Group Messages CRUD
  async createMessage(data: InsertStudyGroupMessage): Promise<StudyGroupMessageWithDetails> {
    const [message] = await db
      .insert(studyGroupMessages)
      .values([data])
      .returning();

    // Get message with sender details
    const [messageWithSender] = await db
      .select({
        id: studyGroupMessages.id,
        studyGroupId: studyGroupMessages.studyGroupId,
        senderId: studyGroupMessages.senderId,
        type: studyGroupMessages.type,
        content: studyGroupMessages.content,
        metadata: studyGroupMessages.metadata,
        replyToId: studyGroupMessages.replyToId,
        editedAt: studyGroupMessages.editedAt,
        isDeleted: studyGroupMessages.isDeleted,
        createdAt: studyGroupMessages.createdAt,
        updatedAt: studyGroupMessages.updatedAt,
        sender: {
          id: users.id,
          firstName: users.firstName,
          lastName: users.lastName,
          profileImageUrl: users.profileImageUrl,
        }
      })
      .from(studyGroupMessages)
      .innerJoin(users, eq(studyGroupMessages.senderId, users.id))
      .where(eq(studyGroupMessages.id, message.id));

    return messageWithSender as StudyGroupMessageWithDetails;
  }

  async getStudyGroupMessages(groupId: string, limit: number = 50): Promise<StudyGroupMessageWithDetails[]> {
    return await db
      .select({
        id: studyGroupMessages.id,
        studyGroupId: studyGroupMessages.studyGroupId,
        senderId: studyGroupMessages.senderId,
        type: studyGroupMessages.type,
        content: studyGroupMessages.content,
        metadata: studyGroupMessages.metadata,
        replyToId: studyGroupMessages.replyToId,
        editedAt: studyGroupMessages.editedAt,
        isDeleted: studyGroupMessages.isDeleted,
        createdAt: studyGroupMessages.createdAt,
        updatedAt: studyGroupMessages.updatedAt,
        sender: {
          id: users.id,
          firstName: users.firstName,
          lastName: users.lastName,
          profileImageUrl: users.profileImageUrl,
        }
      })
      .from(studyGroupMessages)
      .innerJoin(users, eq(studyGroupMessages.senderId, users.id))
      .where(and(
        eq(studyGroupMessages.studyGroupId, groupId),
        eq(studyGroupMessages.isDeleted, false)
      ))
      .orderBy(desc(studyGroupMessages.createdAt))
      .limit(limit);
  }

  // Whiteboard management
  async createWhiteboard(data: InsertWhiteboard): Promise<Whiteboard> {
    const [whiteboard] = await db
      .insert(whiteboards)
      .values(data)
      .returning();
    return whiteboard;
  }

  async getStudyGroupWhiteboards(groupId: string): Promise<Whiteboard[]> {
    return await db
      .select()
      .from(whiteboards)
      .where(and(
        eq(whiteboards.studyGroupId, groupId),
        eq(whiteboards.isActive, true)
      ))
      .orderBy(desc(whiteboards.updatedAt));
  }

  async updateWhiteboard(whiteboardId: string, data: any): Promise<Whiteboard> {
    const [whiteboard] = await db
      .update(whiteboards)
      .set({ data, updatedAt: new Date() })
      .where(eq(whiteboards.id, whiteboardId))
      .returning();
    return whiteboard;
  }
}

export const storage = new DatabaseStorage();

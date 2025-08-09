import { sql } from "drizzle-orm";
import { mysqlTable, text, varchar, int, decimal, timestamp, boolean, json, mysqlEnum, index } from "drizzle-orm/mysql-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod";

// Enums MySQL
export const userRoleEnum = mysqlEnum("user_role", ["super_admin", "admin", "manager", "formateur", "apprenant"]);
export const courseTypeEnum = mysqlEnum("course_type", ["synchrone", "asynchrone"]);
export const sessionStatusEnum = mysqlEnum("session_status", ["draft", "pending_approval", "approved", "active", "completed", "archived"]);
export const notificationTypeEnum = mysqlEnum("notification_type", ["course_enrollment", "assessment_graded", "course_published", "assessment_approved", "assessment_rejected", "new_announcement", "system_update", "deadline_reminder"]);
export const studyGroupStatusEnum = mysqlEnum("study_group_status", ["active", "archived", "scheduled"]);
export const messageTypeEnum = mysqlEnum("message_type", ["text", "file", "image", "link", "poll", "whiteboard"]);

// Table des sessions pour MySQL
export const sessions = mysqlTable(
  "sessions",
  {
    sid: varchar("sid", { length: 255 }).primaryKey(),
    sess: json("sess").notNull(),
    expire: timestamp("expire").notNull(),
  },
  (table) => [index("IDX_session_expire").on(table.expire)],
);

// Table des établissements
export const establishments = mysqlTable("establishments", {
  id: varchar("id", { length: 36 }).primaryKey().default(sql`(UUID())`),
  name: text("name").notNull(),
  slug: text("slug").notNull().unique(),
  description: text("description"),
  logo: text("logo"),
  domain: text("domain"),
  databaseUrl: text("database_url"),
  isActive: boolean("is_active").default(true),
  settings: json("settings"),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow().onUpdateNow(),
});

// Table des utilisateurs  
export const users = mysqlTable("users", {
  id: varchar("id", { length: 36 }).primaryKey().default(sql`(UUID())`),
  establishmentId: varchar("establishment_id", { length: 36 }).references(() => establishments.id),
  email: varchar("email", { length: 255 }).notNull().unique(),
  username: varchar("username", { length: 100 }),
  firstName: varchar("first_name", { length: 100 }).notNull(),
  lastName: varchar("last_name", { length: 100 }).notNull(),
  password: varchar("password", { length: 255 }).notNull(),
  role: userRoleEnum.default("apprenant"),
  avatar: text("avatar"),
  isActive: boolean("is_active").default(true),
  lastLoginAt: timestamp("last_login_at"),
  emailVerifiedAt: timestamp("email_verified_at"),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow().onUpdateNow(),
});

// Table des cours
export const courses = mysqlTable("courses", {
  id: varchar("id", { length: 36 }).primaryKey().default(sql`(UUID())`),
  establishmentId: varchar("establishment_id", { length: 36 }).references(() => establishments.id),
  title: varchar("title", { length: 255 }).notNull(),
  description: text("description"),
  shortDescription: varchar("short_description", { length: 500 }),
  category: varchar("category", { length: 100 }).default("web"),
  type: courseTypeEnum.default("asynchrone"),
  price: decimal("price", { precision: 10, scale: 2 }).default("0.00"),
  isFree: boolean("is_free").default(true),
  duration: int("duration").default(60),
  level: varchar("level", { length: 20 }).default("debutant"),
  language: varchar("language", { length: 10 }).default("fr"),
  tags: json("tags"),
  imageUrl: text("image_url"),
  thumbnailUrl: text("thumbnail_url"),
  videoTrailerUrl: text("video_trailer_url"),
  instructorId: varchar("instructor_id", { length: 36 }).references(() => users.id),
  isPublic: boolean("is_public").default(true),
  isActive: boolean("is_active").default(true),
  rating: decimal("rating", { precision: 3, scale: 2 }).default("0.00"),
  enrollmentCount: int("enrollment_count").default(0),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow().onUpdateNow(),
});

// Schemas de validation
export const insertEstablishmentSchema = createInsertSchema(establishments);
export const insertUserSchema = createInsertSchema(users);
export const insertCourseSchema = createInsertSchema(courses);

export type InsertEstablishment = z.infer<typeof insertEstablishmentSchema>;
export type InsertUser = z.infer<typeof insertUserSchema>;
export type InsertCourse = z.infer<typeof insertCourseSchema>;

export type SelectEstablishment = typeof establishments.$inferSelect;
export type SelectUser = typeof users.$inferSelect;
export type SelectCourse = typeof courses.$inferSelect;
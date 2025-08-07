import bcrypt from "bcryptjs";
import { storage } from "../storage";
import type { User, InsertUser } from "@shared/schema";

export class AuthService {
  
  /**
   * Authenticate user with email and password
   */
  static async authenticateUser(email: string, password: string, establishmentId: string): Promise<User | null> {
    try {
      // Get user by email
      const user = await storage.getUserByEmail(email, establishmentId);
      if (!user) {
        return null;
      }

      // Verify password
      const isValidPassword = await bcrypt.compare(password, user.password || '');
      if (!isValidPassword) {
        return null;
      }

      // Update last login
      await storage.updateUserLastLogin(user.id);
      
      return user;
    } catch (error) {
      console.error("Authentication error:", error);
      return null;
    }
  }

  /**
   * Hash password for storage
   */
  static async hashPassword(password: string): Promise<string> {
    const saltRounds = 12;
    return await bcrypt.hash(password, saltRounds);
  }

  /**
   * Create new user with hashed password
   */
  static async createUser(userData: Omit<InsertUser, 'password'> & { password: string }): Promise<User> {
    const hashedPassword = await this.hashPassword(userData.password);
    return await storage.createUser({
      ...userData,
      password: hashedPassword
    });
  }

  /**
   * Update user password
   */
  static async updateUserPassword(userId: string, newPassword: string): Promise<void> {
    const hashedPassword = await this.hashPassword(newPassword);
    await storage.updateUser(userId, { password: hashedPassword });
  }

  /**
   * Verify user permissions
   */
  static verifyPermission(user: User, requiredRole: string): boolean {
    const roleHierarchy = {
      'super_admin': 5,
      'admin': 4,
      'manager': 3,
      'formateur': 2,
      'apprenant': 1
    };

    const userLevel = roleHierarchy[user.role as keyof typeof roleHierarchy] || 0;
    const requiredLevel = roleHierarchy[requiredRole as keyof typeof roleHierarchy] || 0;

    return userLevel >= requiredLevel;
  }
}
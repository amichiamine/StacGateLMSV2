import { DatabaseStorage } from "../storage";

export class AnalyticsService {
  constructor(private storage: DatabaseStorage) {}

  /**
   * Get comprehensive analytics for an establishment
   */
  async getEstablishmentAnalytics(establishmentId: string, dateRange?: { from: Date; to: Date }) {
    return await this.storage.getEstablishmentAnalytics(establishmentId, dateRange);
  }

  /**
   * Get dashboard statistics for a user based on their role
   */
  async getDashboardStats(userId: string, establishmentId: string) {
    return await this.storage.getDashboardStats(userId, establishmentId);
  }

  /**
   * Get dashboard widgets for a user based on their role
   */
  async getDashboardWidgets(userId: string, role: string, establishmentId: string) {
    return await this.storage.getDashboardWidgets(userId, role, establishmentId);
  }

  /**
   * Get popular courses for an establishment
   */
  async getPopularCourses(establishmentId: string, limit: number = 10) {
    return await this.storage.getPopularCourses(establishmentId, limit);
  }

  /**
   * Get detailed course statistics
   */
  async getCourseStats(courseId: string) {
    return await this.storage.getCourseStats(courseId);
  }

  /**
   * Log user activity for analytics tracking
   */
  async logActivity(establishmentId: string, userId: string, action: string, details: any) {
    return await this.storage.logActivity(establishmentId, userId, action, details);
  }

  /**
   * Global search across multiple content types
   */
  async globalSearch(query: string, type: string | undefined, establishmentId: string, limit: number = 20) {
    return await this.storage.globalSearch(query, type, establishmentId, limit);
  }
}
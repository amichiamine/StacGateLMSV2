import { storage } from "../storage";
import type { InsertNotification, Notification } from "@shared/schema";

export class NotificationService {
  
  /**
   * Create notification for user
   */
  static async createUserNotification(
    userId: string,
    type: string,
    title: string,
    message: string,
    metadata?: any
  ): Promise<Notification> {
    try {
      const notificationData: InsertNotification = {
        userId,
        type: type as any,
        title,
        message,
        // metadata: metadata ? JSON.stringify(metadata) : undefined, // Removed until schema supports it
        isRead: false
      };
      
      return await storage.createNotification(notificationData);
    } catch (error) {
      console.error("Error creating notification:", error);
      throw new Error("Failed to create notification");
    }
  }

  /**
   * Create notification for multiple users
   */
  static async createBulkNotifications(
    userIds: string[],
    type: string,
    title: string,
    message: string,
    metadata?: any
  ): Promise<Notification[]> {
    try {
      const notifications: Promise<Notification>[] = userIds.map(userId =>
        this.createUserNotification(userId, type, title, message, metadata)
      );
      
      return await Promise.all(notifications);
    } catch (error) {
      console.error("Error creating bulk notifications:", error);
      throw new Error("Failed to create bulk notifications");
    }
  }

  /**
   * Notify course enrollment
   */
  static async notifyCourseEnrollment(userId: string, courseTitle: string) {
    return await this.createUserNotification(
      userId,
      'course_enrollment',
      'Nouvelle inscription',
      `Vous êtes inscrit au cours "${courseTitle}"`
    );
  }

  /**
   * Notify course approval
   */
  static async notifyCourseApproval(instructorId: string, courseTitle: string) {
    return await this.createUserNotification(
      instructorId,
      'course_published',
      'Cours approuvé',
      `Votre cours "${courseTitle}" a été approuvé et publié`
    );
  }

  /**
   * Notify assessment graded
   */
  static async notifyAssessmentGraded(userId: string, assessmentTitle: string, grade: number) {
    return await this.createUserNotification(
      userId,
      'assessment_graded',
      'Évaluation notée',
      `Votre évaluation "${assessmentTitle}" a été notée : ${grade}/100`
    );
  }

  /**
   * Notify system update
   */
  static async notifySystemUpdate(userIds: string[], updateTitle: string, updateDescription: string) {
    return await this.createBulkNotifications(
      userIds,
      'system_update',
      `Mise à jour système : ${updateTitle}`,
      updateDescription
    );
  }

  /**
   * Get user notification summary
   */
  static async getUserNotificationSummary(userId: string) {
    try {
      const notifications = await storage.getNotificationsByUserId(userId);
      
      return {
        total: notifications.length,
        unread: notifications.filter(n => !n.isRead).length,
        byType: notifications.reduce((acc: Record<string, number>, notification: any) => {
          acc[notification.type] = (acc[notification.type] || 0) + 1;
          return acc;
        }, {} as Record<string, number>)
      };
    } catch (error) {
      console.error("Error getting notification summary:", error);
      throw new Error("Failed to get notification summary");
    }
  }
}
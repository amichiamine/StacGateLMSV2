import { storage } from "../storage";
import type { Course, InsertCourse, CourseWithDetails, User } from "@shared/schema";

export class CourseService {
  
  /**
   * Get courses accessible by user based on role
   */
  static async getCoursesForUser(user: User): Promise<CourseWithDetails[]> {
    try {
      const establishmentId = user.establishmentId || '';
      
      // Get all courses for the establishment
      const courses = await storage.getCoursesByEstablishment(establishmentId);
      
      // Filter based on user role
      if (user.role === 'super_admin' || user.role === 'admin') {
        // Admin can see all courses
        return courses;
      } else if (user.role === 'formateur') {
        // Trainers can see their own courses and public courses
        return courses.filter(course => 
          course.instructorId === user.id || course.isPublic
        );
      } else {
        // Students can only see active public courses
        return courses.filter(course => 
          course.isActive && course.isPublic
        );
      }
    } catch (error) {
      console.error("Error fetching courses for user:", error);
      throw new Error("Failed to fetch courses");
    }
  }

  /**
   * Create a new course
   */
  static async createCourse(courseData: InsertCourse, creatorId: string): Promise<Course> {
    try {
      const courseWithCreator = {
        ...courseData,
        instructorId: creatorId
      };
      
      return await storage.createCourse(courseWithCreator);
    } catch (error) {
      console.error("Error creating course:", error);
      throw new Error("Failed to create course");
    }
  }

  /**
   * Approve a course (admin only)
   */
  static async approveCourse(courseId: string, approvedBy: string): Promise<Course | undefined> {
    try {
      return await storage.approveCourse(courseId, approvedBy);
    } catch (error) {
      console.error("Error approving course:", error);
      throw new Error("Failed to approve course");
    }
  }

  /**
   * Get course statistics
   */
  static async getCourseStatistics(establishmentId: string) {
    try {
      const courses = await storage.getCoursesByEstablishment(establishmentId);
      
      return {
        total: courses.length,
        active: courses.filter(c => c.isActive).length,
        public: courses.filter(c => c.isPublic).length,
        draft: courses.filter(c => !c.isActive).length,
        byCategory: courses.reduce((acc, course) => {
          acc[course.category] = (acc[course.category] || 0) + 1;
          return acc;
        }, {} as Record<string, number>)
      };
    } catch (error) {
      console.error("Error getting course statistics:", error);
      throw new Error("Failed to get course statistics");
    }
  }

  /**
   * Enroll user in course
   */
  static async enrollUserInCourse(userId: string, courseId: string) {
    try {
      return await storage.createUserCourseEnrollment({
        userId,
        courseId,
        progress: 0,
        status: 'enrolled'
      });
    } catch (error) {
      console.error("Error enrolling user in course:", error);
      throw new Error("Failed to enroll user in course");
    }
  }
}
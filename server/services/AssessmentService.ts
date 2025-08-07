import { DatabaseStorage } from "../storage";
import type { Assessment, InsertAssessment, AssessmentAttempt, InsertAssessmentAttempt } from "@shared/schema";

export class AssessmentService {
  constructor(private storage: DatabaseStorage) {}

  /**
   * Get assessment by ID
   */
  async getAssessment(id: string): Promise<Assessment | undefined> {
    return await this.storage.getAssessment(id);
  }

  /**
   * Get assessments by establishment
   */
  async getAssessmentsByEstablishment(establishmentId: string): Promise<Assessment[]> {
    return await this.storage.getAssessmentsByEstablishment(establishmentId);
  }

  /**
   * Create a new assessment
   */
  async createAssessment(assessment: InsertAssessment): Promise<Assessment> {
    return await this.storage.createAssessment(assessment);
  }

  /**
   * Get user assessment attempts
   */
  async getUserAssessmentAttempts(userId: string, assessmentId?: string) {
    return await this.storage.getUserAssessmentAttempts(userId, assessmentId);
  }

  /**
   * Start an assessment attempt
   */
  async startAssessmentAttempt(userId: string, assessmentId: string) {
    return await this.storage.startAssessmentAttempt(userId, assessmentId);
  }

  /**
   * Create an assessment attempt
   */
  async createAssessmentAttempt(insertAttempt: InsertAssessmentAttempt): Promise<AssessmentAttempt> {
    return await this.storage.createAssessmentAttempt(insertAttempt);
  }

  /**
   * Submit an assessment attempt
   */
  async submitAssessmentAttempt(attemptId: string, answers: any, score: number): Promise<AssessmentAttempt | undefined> {
    return await this.storage.submitAssessmentAttempt(attemptId, answers, score);
  }

  /**
   * Generate certificate for course completion
   */
  async generateCourseCertificate(userId: string, courseId: string) {
    return await this.storage.generateCourseCertificate(userId, courseId);
  }

  /**
   * Get user certificates
   */
  async getUserCertificates(userId: string) {
    return await this.storage.getUserCertificates(userId);
  }
}
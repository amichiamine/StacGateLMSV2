import { DatabaseStorage } from "../storage";
import type { ExportJob, InsertExportJob } from "@shared/schema";

export class ExportService {
  constructor(private storage: DatabaseStorage) {}

  /**
   * Create a bulk export job
   */
  async createBulkExport(establishmentId: string, exportType: string, filters: any, createdBy: string) {
    return await this.storage.createBulkExport(establishmentId, exportType, filters, createdBy);
  }

  /**
   * Get export history for an establishment
   */
  async getExportHistory(establishmentId: string, limit?: number) {
    return await this.storage.getExportHistory(establishmentId, limit);
  }

  /**
   * Get available export templates
   */
  async getExportTemplates(establishmentId: string) {
    return await this.storage.getExportTemplates(establishmentId);
  }

  /**
   * Get export jobs for a user
   */
  async getExportJobs(userId: string, establishmentId: string): Promise<ExportJob[]> {
    return await this.storage.getExportJobs(userId, establishmentId);
  }

  /**
   * Create an export job
   */
  async createExportJob(job: InsertExportJob & { userId: string; establishmentId: string }): Promise<ExportJob> {
    return await this.storage.createExportJob(job);
  }

  /**
   * Update an export job status
   */
  async updateExportJob(id: string, updates: Partial<ExportJob>): Promise<ExportJob | undefined> {
    return await this.storage.updateExportJob(id, updates);
  }

  /**
   * Get a specific export job
   */
  async getExportJob(id: string): Promise<ExportJob | undefined> {
    return await this.storage.getExportJob(id);
  }

  /**
   * Batch enroll users in a course
   */
  async batchEnrollUsers(courseId: string, userIds: string[]) {
    return await this.storage.batchEnrollUsers(courseId, userIds);
  }
}
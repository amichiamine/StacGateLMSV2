import { DatabaseStorage } from "../storage";
import type { SelectHelpContent, InsertHelpContent } from "@shared/schema";

export class HelpService {
  constructor(private storage: DatabaseStorage) {}

  /**
   * Get help contents for an establishment, optionally filtered by role and category
   */
  async getHelpContents(establishmentId: string, role?: string, category?: string): Promise<SelectHelpContent[]> {
    return await this.storage.getHelpContents(establishmentId, role, category);
  }

  /**
   * Get a specific help content by ID
   */
  async getHelpContentById(id: string): Promise<SelectHelpContent | undefined> {
    return await this.storage.getHelpContentById(id);
  }

  /**
   * Search help content
   */
  async searchHelpContent(establishmentId: string, query: string, role?: string): Promise<SelectHelpContent[]> {
    return await this.storage.searchHelpContent(establishmentId, query, role);
  }

  /**
   * Create new help content
   */
  async createHelpContent(content: InsertHelpContent): Promise<SelectHelpContent> {
    return await this.storage.createHelpContent(content);
  }

  /**
   * Update help content
   */
  async updateHelpContent(id: string, updates: Partial<InsertHelpContent>): Promise<SelectHelpContent | undefined> {
    return await this.storage.updateHelpContent(id, updates);
  }

  /**
   * Delete help content
   */
  async deleteHelpContent(id: string): Promise<void> {
    return await this.storage.deleteHelpContent(id);
  }
}
import { DatabaseStorage } from "../storage";
import type { SelectSystemVersion, InsertSystemVersion, SelectEstablishmentBranding, InsertEstablishmentBranding } from "@shared/schema";

export class SystemService {
  constructor(private storage: DatabaseStorage) {}

  /**
   * Get all system versions
   */
  async getSystemVersions(): Promise<SelectSystemVersion[]> {
    return await this.storage.getSystemVersions();
  }

  /**
   * Get the currently active system version
   */
  async getActiveSystemVersion(): Promise<SelectSystemVersion | undefined> {
    return await this.storage.getActiveSystemVersion();
  }

  /**
   * Create a new system version
   */
  async createSystemVersion(version: InsertSystemVersion): Promise<SelectSystemVersion> {
    return await this.storage.createSystemVersion(version);
  }

  /**
   * Activate a system version
   */
  async activateSystemVersion(id: string): Promise<void> {
    return await this.storage.activateSystemVersion(id);
  }

  /**
   * Get current maintenance status
   */
  async getMaintenanceStatus(): Promise<{ isMaintenance: boolean; message?: string }> {
    return await this.storage.getMaintenanceStatus();
  }

  /**
   * Set maintenance mode
   */
  async setMaintenanceMode(enabled: boolean, message?: string): Promise<void> {
    return await this.storage.setMaintenanceMode(enabled, message);
  }

  /**
   * Get establishment branding
   */
  async getEstablishmentBranding(establishmentId: string): Promise<SelectEstablishmentBranding | undefined> {
    return await this.storage.getEstablishmentBranding(establishmentId);
  }

  /**
   * Create establishment branding
   */
  async createEstablishmentBranding(branding: InsertEstablishmentBranding): Promise<SelectEstablishmentBranding> {
    return await this.storage.createEstablishmentBranding(branding);
  }

  /**
   * Update establishment branding
   */
  async updateEstablishmentBranding(establishmentId: string, updates: Partial<InsertEstablishmentBranding>): Promise<SelectEstablishmentBranding | undefined> {
    return await this.storage.updateEstablishmentBranding(establishmentId, updates);
  }
}
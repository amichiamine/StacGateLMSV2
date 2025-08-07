import { storage } from "../storage";
import type { Establishment, InsertEstablishment, SimpleTheme, SimpleCustomizableContent } from "@shared/schema";

export class EstablishmentService {
  
  /**
   * Get establishment with theme and customization
   */
  static async getEstablishmentWithCustomization(slug: string) {
    try {
      const establishment = await storage.getEstablishmentBySlug(slug);
      if (!establishment) {
        throw new Error("Establishment not found");
      }

      // Get active theme
      const theme = await storage.getActiveTheme(establishment.id);
      
      // Get customizable contents
      const contents = await storage.getCustomizableContents(establishment.id);
      
      return {
        establishment,
        theme,
        contents
      };
    } catch (error) {
      console.error("Error fetching establishment with customization:", error);
      throw new Error("Failed to fetch establishment data");
    }
  }

  /**
   * Update establishment branding
   */
  static async updateEstablishmentBranding(establishmentId: string, brandingData: any) {
    try {
      return await storage.updateEstablishmentBranding(establishmentId, brandingData);
    } catch (error) {
      console.error("Error updating establishment branding:", error);
      throw new Error("Failed to update branding");
    }
  }

  /**
   * Create establishment with default setup
   */
  static async createEstablishmentWithDefaults(establishmentData: InsertEstablishment): Promise<Establishment> {
    try {
      // Create establishment
      const establishment = await storage.createEstablishment(establishmentData);
      
      // Create default theme
      const defaultTheme = {
        establishmentId: establishment.id,
        name: "Thème par défaut",
        isActive: true,
        primaryColor: "#6366f1",
        secondaryColor: "#06b6d4",
        accentColor: "#10b981",
        backgroundColor: "#ffffff",
        textColor: "#1f2937",
        fontFamily: "Inter",
        fontSize: "16px"
      };
      
      await storage.createTheme(defaultTheme);
      
      // Create default menu items
      const defaultMenuItems = [
        {
          establishmentId: establishment.id,
          label: "Accueil",
          url: "/",
          icon: "Home",
          sortOrder: 1
        },
        {
          establishmentId: establishment.id,
          label: "Cours",
          url: "/courses",
          icon: "BookOpen",
          sortOrder: 2
        },
        {
          establishmentId: establishment.id,
          label: "Tableau de bord",
          url: "/dashboard",
          icon: "LayoutDashboard",
          sortOrder: 3
        }
      ];
      
      for (const menuItem of defaultMenuItems) {
        await storage.createMenuItem(menuItem);
      }
      
      return establishment;
    } catch (error) {
      console.error("Error creating establishment with defaults:", error);
      throw new Error("Failed to create establishment");
    }
  }

  /**
   * Get establishment statistics
   */
  static async getEstablishmentStatistics(establishmentId: string) {
    try {
      const users = await storage.getUsersByEstablishment(establishmentId);
      const courses = await storage.getCoursesByEstablishment(establishmentId);
      
      return {
        users: {
          total: users.length,
          active: users.filter(u => u.isActive).length,
          byRole: users.reduce((acc, user) => {
            const role = user.role || 'unknown';
          acc[role] = (acc[role] || 0) + 1;
            return acc;
          }, {} as Record<string, number>)
        },
        courses: {
          total: courses.length,
          active: courses.filter(c => c.isActive).length,
          public: courses.filter(c => c.isPublic).length
        }
      };
    } catch (error) {
      console.error("Error getting establishment statistics:", error);
      throw new Error("Failed to get establishment statistics");
    }
  }
}
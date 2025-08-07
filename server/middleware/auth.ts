import { Request, Response, NextFunction } from 'express';
import { storage } from '../storage';

// Étendre l'interface Request pour inclure l'utilisateur
declare global {
  namespace Express {
    interface Request {
      user?: any;
    }
  }
}

// Middleware d'authentification de base
export const requireAuth = async (req: Request, res: Response, next: NextFunction) => {
  try {
    if (!req.session?.userId) {
      return res.status(401).json({ message: "Non authentifié" });
    }
    
    const user = await storage.getUser(req.session.userId);
    if (!user) {
      return res.status(401).json({ message: "Utilisateur non trouvé" });
    }
    
    req.user = user;
    next();
  } catch (error) {
    console.error("Auth middleware error:", error);
    res.status(500).json({ message: "Erreur d'authentification" });
  }
};

// Middleware pour Super Admin uniquement
export const requireSuperAdmin = async (req: Request, res: Response, next: NextFunction) => {
  try {
    if (!req.session?.userId) {
      return res.status(401).json({ message: "Non authentifié" });
    }
    
    const user = await storage.getUser(req.session.userId);
    if (!user) {
      return res.status(401).json({ message: "Utilisateur non trouvé" });
    }
    
    if (user.role !== 'super_admin') {
      return res.status(403).json({ message: "Accès réservé au Super Administrateur" });
    }
    
    req.user = user;
    next();
  } catch (error) {
    console.error("Super admin middleware error:", error);
    res.status(500).json({ message: "Erreur d'autorisation" });
  }
};

// Middleware pour Admin ou Super Admin
export const requireAdmin = async (req: Request, res: Response, next: NextFunction) => {
  try {
    if (!req.session?.userId) {
      return res.status(401).json({ message: "Non authentifié" });
    }
    
    const user = await storage.getUser(req.session.userId);
    if (!user) {
      return res.status(401).json({ message: "Utilisateur non trouvé" });
    }
    
    if (!['admin', 'super_admin'].includes(user.role || '')) {
      return res.status(403).json({ message: "Accès réservé aux administrateurs" });
    }
    
    req.user = user;
    next();
  } catch (error) {
    console.error("Admin middleware error:", error);
    res.status(500).json({ message: "Erreur d'autorisation" });
  }
};

// Middleware pour vérifier l'accès à un établissement spécifique
export const requireEstablishmentAccess = (establishmentIdParam: string = 'establishmentId') => {
  return async (req: Request, res: Response, next: NextFunction) => {
    try {
      await requireAuth(req, res, () => {});
      
      const requestedEstablishmentId = req.params[establishmentIdParam] || req.body[establishmentIdParam];
      
      // Super Admin a accès à tous les établissements
      if (req.user.role === 'super_admin') {
        return next();
      }
      
      // Admin peut seulement accéder à son établissement
      if (req.user.role === 'admin' && req.user.establishmentId === requestedEstablishmentId) {
        return next();
      }
      
      return res.status(403).json({ message: "Accès non autorisé à cet établissement" });
    } catch (error) {
      console.error("Establishment access middleware error:", error);
      res.status(500).json({ message: "Erreur d'autorisation" });
    }
  };
};

// Helper pour vérifier les permissions
export const hasPermission = (userRole: string, requiredRoles: string[]): boolean => {
  const roleHierarchy = {
    'super_admin': 5,
    'admin': 4,
    'manager': 3,
    'formateur': 2,
    'apprenant': 1
  };
  
  const userLevel = roleHierarchy[userRole as keyof typeof roleHierarchy] || 0;
  const requiredLevel = Math.min(...requiredRoles.map(role => 
    roleHierarchy[role as keyof typeof roleHierarchy] || 0
  ));
  
  return userLevel >= requiredLevel;
};
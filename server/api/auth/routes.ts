import { Router } from "express";
import { storage } from "../../storage";
import { z } from "zod";
import bcrypt from "bcryptjs";

const router = Router();

const loginSchema = z.object({
  email: z.string().email(),
  password: z.string().min(1),
});

// GET /api/auth/user - Get current user
router.get('/user', async (req: any, res) => {
  try {
    if (!req.session?.userId) {
      return res.status(401).json({ message: "Non authentifié" });
    }
    
    const user = await storage.getUser(req.session.userId);
    if (!user) {
      return res.status(401).json({ message: "Utilisateur non trouvé" });
    }
    
    res.json(user);
  } catch (error) {
    console.error("Error fetching user:", error);
    res.status(500).json({ message: "Erreur serveur" });
  }
});

// POST /api/auth/logout - Logout user
router.post('/logout', (req: any, res) => {
  req.session.destroy((err: any) => {
    if (err) {
      console.error("Session destruction error:", err);
      return res.status(500).json({ message: "Erreur lors de la déconnexion" });
    }
    res.clearCookie('stacgate.sid');
    res.json({ message: "Déconnexion réussie" });
  });
});

// POST /api/auth/login - Login user
router.post('/login', async (req: any, res) => {
  try {
    const { email, password } = loginSchema.parse(req.body);
    
    // For auth routes, we'll search across all establishments
    // First, let's get all establishments to search across them
    const establishments = await storage.getAllEstablishments();
    let user = null;
    
    // Search for user across all establishments
    for (const establishment of establishments) {
      user = await storage.getUserByEmail(email, establishment.id);
      if (user) break;
    }
    
    if (!user) {
      return res.status(401).json({ message: "Email ou mot de passe incorrect" });
    }

    const isValidPassword = await bcrypt.compare(password, user.password);
    if (!isValidPassword) {
      return res.status(401).json({ message: "Email ou mot de passe incorrect" });
    }

    // Create session
    req.session.userId = user.id;
    
    // Update last login - commented out as updateUser method may not exist
    // await storage.updateUser(user.id, {
    //   lastLoginAt: new Date(),
    //   updatedAt: new Date()
    // });

    // Remove password from response
    const { password: _, ...userWithoutPassword } = user;
    res.json(userWithoutPassword);
  } catch (error) {
    console.error("Login error:", error);
    res.status(500).json({ message: "Erreur de connexion" });
  }
});

// POST /api/auth/register - Register new user
router.post('/register', async (req: any, res) => {
  try {
    const userData = req.body;
    
    // Validate required fields
    if (!userData.email || !userData.password) {
      return res.status(400).json({ message: "Email et mot de passe requis" });
    }
    
    // Check if user already exists - need to check across establishments
    // For registration, we should require establishmentId in the request
    if (!userData.establishmentId) {
      return res.status(400).json({ message: "Establishment ID requis" });
    }
    
    const existingUser = await storage.getUserByEmail(userData.email, userData.establishmentId);
    if (existingUser) {
      return res.status(400).json({ message: "Un utilisateur avec cet email existe déjà" });
    }

    // Hash password
    const hashedPassword = await bcrypt.hash(userData.password, 12);
    
    // Create user
    const newUser = await storage.createUser({
      ...userData,
      password: hashedPassword,
      role: userData.role || 'apprenant',
      isActive: true,
      isEmailVerified: false
    });

    // Create session
    req.session.userId = newUser.id;

    // Remove password from response
    const { password: _, ...userWithoutPassword } = newUser;
    res.status(201).json(userWithoutPassword);
  } catch (error) {
    console.error("Registration error:", error);
    res.status(500).json({ message: "Erreur lors de l'inscription" });
  }
});

export default router;
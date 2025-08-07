import { Router } from "express";
import { storage } from "../../storage";
import { requireAuth, requireAdmin, requireSuperAdmin } from "../../middleware/auth";

const router = Router();

// GET /api/users - Get users (admin/super-admin only)
router.get('/', requireAuth, requireAdmin, async (req: any, res) => {
  try {
    const user = req.user;
    let users;
    
    if (user.role === 'super_admin') {
      users = await storage.getUsers();
    } else {
      users = await storage.getUsersByEstablishment(user.establishmentId);
    }
    
    res.json(users);
  } catch (error) {
    console.error("Error fetching users:", error);
    res.status(500).json({ message: "Erreur serveur" });
  }
});

// GET /api/users/:id - Get user by ID
router.get('/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const user = await storage.getUser(id);
    
    if (!user) {
      return res.status(404).json({ message: "Utilisateur non trouvé" });
    }
    
    // Remove password from response
    const { password: _, ...userWithoutPassword } = user;
    res.json(userWithoutPassword);
  } catch (error) {
    console.error("Error fetching user:", error);
    res.status(500).json({ message: "Erreur serveur" });
  }
});

// GET /api/users/:id/courses - Get user's courses
router.get('/:id/courses', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const courses = await storage.getUserCourses(id);
    res.json(courses);
  } catch (error) {
    console.error("Error fetching user courses:", error);
    res.status(500).json({ message: "Erreur serveur" });
  }
});

// PUT /api/users/:id - Update user
router.put('/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const updates = req.body;
    
    const user = await storage.updateUser(id, updates);
    if (!user) {
      return res.status(404).json({ message: "Utilisateur non trouvé" });
    }
    
    // Remove password from response
    const { password: _, ...userWithoutPassword } = user;
    res.json(userWithoutPassword);
  } catch (error) {
    console.error("Error updating user:", error);
    res.status(500).json({ message: "Erreur lors de la mise à jour" });
  }
});

// DELETE /api/users/:id - Delete user (super-admin only)
router.delete('/:id', requireAuth, requireSuperAdmin, async (req, res) => {
  try {
    const { id } = req.params;
    await storage.deleteUser(id);
    res.json({ message: "Utilisateur supprimé" });
  } catch (error) {
    console.error("Error deleting user:", error);
    res.status(500).json({ message: "Erreur lors de la suppression" });
  }
});

export default router;
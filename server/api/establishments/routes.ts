import { Router } from "express";
import { storage } from "../../storage";
import { requireAuth, requireAdmin } from "../../middleware/auth";
import { insertEstablishmentSchema } from "@shared/schema";

const router = Router();

// GET /api/establishments - Get all establishments
router.get('/', async (req, res) => {
  try {
    const establishments = await storage.getEstablishments();
    res.json(establishments);
  } catch (error) {
    console.error("Error fetching establishments:", error);
    res.status(500).json({ message: "Erreur serveur" });
  }
});

// GET /api/establishments/slug/:slug - Get establishment by slug
router.get('/slug/:slug', async (req, res) => {
  try {
    const { slug } = req.params;
    const establishment = await storage.getEstablishmentBySlug(slug);
    
    if (!establishment) {
      return res.status(404).json({ message: "Établissement non trouvé" });
    }
    
    res.json(establishment);
  } catch (error) {
    console.error("Error fetching establishment:", error);
    res.status(500).json({ message: "Erreur serveur" });
  }
});

// GET /api/establishments/:id - Get establishment by ID
router.get('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const establishment = await storage.getEstablishment(id);
    
    if (!establishment) {
      return res.status(404).json({ message: "Établissement non trouvé" });
    }
    
    res.json(establishment);
  } catch (error) {
    console.error("Error fetching establishment:", error);
    res.status(500).json({ message: "Erreur serveur" });
  }
});

// POST /api/establishments - Create new establishment
router.post('/', requireAuth, requireAdmin, async (req, res) => {
  try {
    const establishmentData = insertEstablishmentSchema.parse(req.body);
    const establishment = await storage.createEstablishment(establishmentData);
    res.status(201).json(establishment);
  } catch (error) {
    console.error("Error creating establishment:", error);
    res.status(500).json({ message: "Erreur lors de la création" });
  }
});

// PUT /api/establishments/:id - Update establishment
router.put('/:id', requireAuth, requireAdmin, async (req, res) => {
  try {
    const { id } = req.params;
    const updates = req.body;
    
    const establishment = await storage.updateEstablishment(id, updates);
    if (!establishment) {
      return res.status(404).json({ message: "Établissement non trouvé" });
    }
    
    res.json(establishment);
  } catch (error) {
    console.error("Error updating establishment:", error);
    res.status(500).json({ message: "Erreur lors de la mise à jour" });
  }
});

// GET /api/establishments/:slug/content/:pageType - Get establishment content  
router.get('/:slug/content/:pageType', async (req, res) => {
  try {
    const { slug, pageType } = req.params;
    
    // Get establishment by slug first
    const establishment = await storage.getEstablishmentBySlug(slug);
    if (!establishment) {
      return res.status(404).json({ message: "Établissement non trouvé" });
    }
    
    const content = await storage.getCustomizablePageByName(establishment.id, pageType);
    res.json(content || { content: [], isCustomized: false });
  } catch (error) {
    console.error("Error fetching content:", error);
    res.status(500).json({ message: "Erreur serveur" });
  }
});

export default router;
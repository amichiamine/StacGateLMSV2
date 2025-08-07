import { Router } from "express";
import { storage } from "../../storage";
import { requireAuth, requireAdmin } from "../../middleware/auth";
import { insertCourseSchema, insertUserCourseSchema } from "@shared/schema";

const router = Router();

// GET /api/courses - Get all courses
router.get('/', async (req, res) => {
  try {
    const { establishmentId, category } = req.query;
    
    let courses;
    if (establishmentId && category) {
      courses = await storage.getCoursesByCategory(category as string, establishmentId as string);
    } else if (establishmentId) {
      courses = await storage.getCoursesByEstablishment(establishmentId as string);
    } else {
      // For now, return empty array if no establishment specified
      courses = [];
    }
    
    res.json(courses);
  } catch (error) {
    console.error("Error fetching courses:", error);
    res.status(500).json({ message: "Erreur serveur" });
  }
});

// GET /api/courses/:id - Get course by ID
router.get('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const course = await storage.getCourse(id);
    
    if (!course) {
      return res.status(404).json({ message: "Cours non trouvé" });
    }
    
    res.json(course);
  } catch (error) {
    console.error("Error fetching course:", error);
    res.status(500).json({ message: "Erreur serveur" });
  }
});

// POST /api/courses - Create new course
router.post('/', requireAuth, async (req, res) => {
  try {
    const courseData = insertCourseSchema.parse(req.body);
    const course = await storage.createCourse(courseData);
    res.status(201).json(course);
  } catch (error) {
    console.error("Error creating course:", error);
    res.status(500).json({ message: "Erreur lors de la création" });
  }
});

// PUT /api/courses/:id - Update course
router.put('/:id', requireAuth, async (req, res) => {
  try {
    const { id } = req.params;
    const updates = req.body;
    
    const course = await storage.updateCourse(id, updates);
    if (!course) {
      return res.status(404).json({ message: "Cours non trouvé" });
    }
    
    res.json(course);
  } catch (error) {
    console.error("Error updating course:", error);
    res.status(500).json({ message: "Erreur lors de la mise à jour" });
  }
});

// POST /api/courses/:id/approve - Approve course
router.post('/:id/approve', requireAuth, requireAdmin, async (req, res) => {
  try {
    const { id } = req.params;
    const { approvedBy } = req.body;
    
    const course = await storage.approveCourse(id, approvedBy);
    if (!course) {
      return res.status(404).json({ message: "Cours non trouvé" });
    }
    
    res.json(course);
  } catch (error) {
    console.error("Error approving course:", error);
    res.status(500).json({ message: "Erreur lors de l'approbation" });
  }
});

// POST /api/courses/:id/enroll - Enroll user in course
router.post('/:id/enroll', requireAuth, async (req: any, res) => {
  try {
    const { id: courseId } = req.params;
    const { sessionId } = req.body;
    const userId = req.session.userId;
    
    const enrollment = await storage.createUserCourseEnrollment(userId, courseId, sessionId);
    res.status(201).json(enrollment);
  } catch (error) {
    console.error("Error enrolling in course:", error);
    res.status(500).json({ message: "Erreur lors de l'inscription" });
  }
});

export default router;
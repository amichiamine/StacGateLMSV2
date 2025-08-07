import { Router } from "express";
import authRoutes from "./auth/routes";
import establishmentRoutes from "./establishments/routes";
import courseRoutes from "./courses/routes";
import userRoutes from "./users/routes";
import analyticsRoutes from "./analytics/routes";
import exportRoutes from "./exports/routes";
import studyGroupRoutes from "./study-groups/routes";
import helpRoutes from "./help/routes";
import systemRoutes from "./system/routes";
import assessmentRoutes from "./assessments/routes";

const router = Router();

// Mount API routes
router.use('/auth', authRoutes);
router.use('/establishments', establishmentRoutes);
router.use('/courses', courseRoutes);
router.use('/users', userRoutes);
router.use('/analytics', analyticsRoutes);
router.use('/exports', exportRoutes);
router.use('/study-groups', studyGroupRoutes);
router.use('/help', helpRoutes);
router.use('/system', systemRoutes);
router.use('/assessments', assessmentRoutes);

// Health check endpoint
router.get('/health', (req, res) => {
  res.json({ 
    status: 'ok', 
    timestamp: new Date().toISOString(),
    version: '1.0.0'
  });
});

export default router;
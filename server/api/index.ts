import { Router } from "express";
import authRoutes from "./auth/routes";
import establishmentRoutes from "./establishments/routes";
import courseRoutes from "./courses/routes";
import userRoutes from "./users/routes";

const router = Router();

// Mount API routes
router.use('/auth', authRoutes);
router.use('/establishments', establishmentRoutes);
router.use('/courses', courseRoutes);
router.use('/users', userRoutes);

// Health check endpoint
router.get('/health', (req, res) => {
  res.json({ 
    status: 'ok', 
    timestamp: new Date().toISOString(),
    version: '1.0.0'
  });
});

export default router;
import { Router } from "express";
import { DatabaseStorage } from "../../storage";
import { AnalyticsService } from "../../services";

const router = Router();
const storage = new DatabaseStorage();
const analyticsService = new AnalyticsService(storage);

// Get establishment analytics
router.get('/establishments/:establishmentId/analytics', async (req, res) => {
  try {
    const { establishmentId } = req.params;
    const { from, to } = req.query;
    
    const dateRange = from && to ? {
      from: new Date(from as string),
      to: new Date(to as string)
    } : undefined;
    
    const analytics = await analyticsService.getEstablishmentAnalytics(establishmentId, dateRange);
    res.json(analytics);
  } catch (error) {
    console.error('Error fetching establishment analytics:', error);
    res.status(500).json({ error: 'Failed to fetch analytics' });
  }
});

// Get dashboard statistics
router.get('/dashboard/stats', async (req, res) => {
  try {
    const { userId, establishmentId } = req.query;
    
    if (!userId || !establishmentId) {
      return res.status(400).json({ error: 'userId and establishmentId are required' });
    }
    
    const stats = await analyticsService.getDashboardStats(userId as string, establishmentId as string);
    res.json(stats);
  } catch (error) {
    console.error('Error fetching dashboard stats:', error);
    res.status(500).json({ error: 'Failed to fetch dashboard statistics' });
  }
});

// Get dashboard widgets
router.get('/dashboard/widgets', async (req, res) => {
  try {
    const { userId, role, establishmentId } = req.query;
    
    if (!userId || !role || !establishmentId) {
      return res.status(400).json({ error: 'userId, role, and establishmentId are required' });
    }
    
    const widgets = await analyticsService.getDashboardWidgets(userId as string, role as string, establishmentId as string);
    res.json(widgets);
  } catch (error) {
    console.error('Error fetching dashboard widgets:', error);
    res.status(500).json({ error: 'Failed to fetch dashboard widgets' });
  }
});

// Get popular courses
router.get('/establishments/:establishmentId/popular-courses', async (req, res) => {
  try {
    const { establishmentId } = req.params;
    const { limit = '10' } = req.query;
    
    const popularCourses = await analyticsService.getPopularCourses(establishmentId, parseInt(limit as string));
    res.json(popularCourses);
  } catch (error) {
    console.error('Error fetching popular courses:', error);
    res.status(500).json({ error: 'Failed to fetch popular courses' });
  }
});

// Get course statistics
router.get('/courses/:courseId/stats', async (req, res) => {
  try {
    const { courseId } = req.params;
    
    const stats = await analyticsService.getCourseStats(courseId);
    res.json(stats);
  } catch (error) {
    console.error('Error fetching course stats:', error);
    res.status(500).json({ error: 'Failed to fetch course statistics' });
  }
});

// Global search
router.get('/search', async (req, res) => {
  try {
    const { query, type, establishmentId, limit = '20' } = req.query;
    
    if (!query || !establishmentId) {
      return res.status(400).json({ error: 'query and establishmentId are required' });
    }
    
    const results = await analyticsService.globalSearch(
      query as string, 
      type as string | undefined, 
      establishmentId as string, 
      parseInt(limit as string)
    );
    res.json(results);
  } catch (error) {
    console.error('Error performing global search:', error);
    res.status(500).json({ error: 'Search failed' });
  }
});

// Log activity
router.post('/activity', async (req, res) => {
  try {
    const { establishmentId, userId, action, details } = req.body;
    
    if (!establishmentId || !userId || !action) {
      return res.status(400).json({ error: 'establishmentId, userId, and action are required' });
    }
    
    const activity = await analyticsService.logActivity(establishmentId, userId, action, details);
    res.json(activity);
  } catch (error) {
    console.error('Error logging activity:', error);
    res.status(500).json({ error: 'Failed to log activity' });
  }
});

export default router;
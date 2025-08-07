import { Router } from "express";
import { DatabaseStorage } from "../../storage";
import { ExportService } from "../../services";

const router = Router();
const storage = new DatabaseStorage();
const exportService = new ExportService(storage);

// Create bulk export
router.post('/bulk', async (req, res) => {
  try {
    const { establishmentId, exportType, filters, createdBy } = req.body;
    
    if (!establishmentId || !exportType || !createdBy) {
      return res.status(400).json({ error: 'establishmentId, exportType, and createdBy are required' });
    }
    
    const exportJob = await exportService.createBulkExport(establishmentId, exportType, filters, createdBy);
    res.json(exportJob);
  } catch (error) {
    console.error('Error creating bulk export:', error);
    res.status(500).json({ error: 'Failed to create bulk export' });
  }
});

// Get export history
router.get('/history', async (req, res) => {
  try {
    const { establishmentId, limit } = req.query;
    
    if (!establishmentId) {
      return res.status(400).json({ error: 'establishmentId is required' });
    }
    
    const history = await exportService.getExportHistory(
      establishmentId as string, 
      limit ? parseInt(limit as string) : undefined
    );
    res.json(history);
  } catch (error) {
    console.error('Error fetching export history:', error);
    res.status(500).json({ error: 'Failed to fetch export history' });
  }
});

// Get export templates
router.get('/templates', async (req, res) => {
  try {
    const { establishmentId } = req.query;
    
    if (!establishmentId) {
      return res.status(400).json({ error: 'establishmentId is required' });
    }
    
    const templates = await exportService.getExportTemplates(establishmentId as string);
    res.json(templates);
  } catch (error) {
    console.error('Error fetching export templates:', error);
    res.status(500).json({ error: 'Failed to fetch export templates' });
  }
});

// Get export jobs for user - now using real database
router.get('/jobs', async (req, res) => {
  try {
    const { userId, establishmentId } = req.query;
    
    if (!userId || !establishmentId) {
      return res.status(400).json({ error: 'userId and establishmentId are required' });
    }
    
    const jobs = await exportService.getExportJobs(userId as string, establishmentId as string);
    res.json(jobs);
  } catch (error) {
    console.error('Error fetching export jobs:', error);
    res.status(500).json({ error: 'Failed to fetch export jobs' });
  }
});

// Create export job
router.post('/jobs', async (req, res) => {
  try {
    const jobData = req.body;
    
    if (!jobData.userId || !jobData.establishmentId) {
      return res.status(400).json({ error: 'userId and establishmentId are required' });
    }
    
    const job = await exportService.createExportJob(jobData);
    res.json(job);
  } catch (error) {
    console.error('Error creating export job:', error);
    res.status(500).json({ error: 'Failed to create export job' });
  }
});

// Update export job
router.patch('/jobs/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const updates = req.body;
    
    const job = await exportService.updateExportJob(id, updates);
    if (!job) {
      return res.status(404).json({ error: 'Export job not found' });
    }
    
    res.json(job);
  } catch (error) {
    console.error('Error updating export job:', error);
    res.status(500).json({ error: 'Failed to update export job' });
  }
});

// Get specific export job
router.get('/jobs/:id', async (req, res) => {
  try {
    const { id } = req.params;
    
    const job = await exportService.getExportJob(id);
    if (!job) {
      return res.status(404).json({ error: 'Export job not found' });
    }
    
    res.json(job);
  } catch (error) {
    console.error('Error fetching export job:', error);
    res.status(500).json({ error: 'Failed to fetch export job' });
  }
});

// Batch enroll users
router.post('/batch-enroll', async (req, res) => {
  try {
    const { courseId, userIds } = req.body;
    
    if (!courseId || !userIds || !Array.isArray(userIds)) {
      return res.status(400).json({ error: 'courseId and userIds (array) are required' });
    }
    
    const enrollments = await exportService.batchEnrollUsers(courseId, userIds);
    res.json(enrollments);
  } catch (error) {
    console.error('Error batch enrolling users:', error);
    res.status(500).json({ error: 'Failed to batch enroll users' });
  }
});

export default router;
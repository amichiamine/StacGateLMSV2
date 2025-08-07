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

// Get export jobs for user (static data for now - table doesn't exist)
router.get('/jobs', async (req, res) => {
  try {
    const jobs = [
      {
        id: 'job-001',
        type: 'course_data',
        status: 'completed',
        progress: 100,
        createdAt: new Date(Date.now() - 3600000).toISOString(), // 1 hour ago
        completedAt: new Date().toISOString(),
        downloadUrl: '/downloads/course-data-export.zip',
        fileName: 'course-data-export.zip',
        fileSize: 1024000,
        createdBy: 'admin',
        establishmentId: 'est-001-main'
      },
      {
        id: 'job-002', 
        type: 'user_data',
        status: 'processing',
        progress: 45,
        createdAt: new Date(Date.now() - 1800000).toISOString(), // 30 min ago
        completedAt: null,
        downloadUrl: null,
        fileName: 'user-data-export.csv',
        fileSize: null,
        createdBy: 'admin',
        establishmentId: 'est-001-main'
      },
      {
        id: 'job-003', 
        type: 'assessment_data',
        status: 'pending',
        progress: 0,
        createdAt: new Date().toISOString(),
        completedAt: null,
        downloadUrl: null,
        fileName: 'assessment-data-export.xlsx',
        fileSize: null,
        createdBy: 'admin',
        establishmentId: 'est-001-main'
      }
    ];
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
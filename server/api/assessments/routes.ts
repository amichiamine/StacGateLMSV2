import { Router } from "express";
import { DatabaseStorage } from "../../storage";
import { AssessmentService } from "../../services";

const router = Router();
const storage = new DatabaseStorage();
const assessmentService = new AssessmentService(storage);

// Get assessment by ID
router.get('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    
    const assessment = await assessmentService.getAssessment(id);
    if (!assessment) {
      return res.status(404).json({ error: 'Assessment not found' });
    }
    
    res.json(assessment);
  } catch (error) {
    console.error('Error fetching assessment:', error);
    res.status(500).json({ error: 'Failed to fetch assessment' });
  }
});

// Get assessments by establishment
router.get('/establishment/:establishmentId', async (req, res) => {
  try {
    const { establishmentId } = req.params;
    
    const assessments = await assessmentService.getAssessmentsByEstablishment(establishmentId);
    res.json(assessments);
  } catch (error) {
    console.error('Error fetching assessments:', error);
    res.status(500).json({ error: 'Failed to fetch assessments' });
  }
});

// Create assessment
router.post('/', async (req, res) => {
  try {
    const assessmentData = req.body;
    
    if (!assessmentData.title || !assessmentData.establishmentId || !assessmentData.createdBy) {
      return res.status(400).json({ error: 'title, establishmentId, and createdBy are required' });
    }
    
    const assessment = await assessmentService.createAssessment(assessmentData);
    res.json(assessment);
  } catch (error) {
    console.error('Error creating assessment:', error);
    res.status(500).json({ error: 'Failed to create assessment' });
  }
});

// Get user assessment attempts
router.get('/attempts/user/:userId', async (req, res) => {
  try {
    const { userId } = req.params;
    const { assessmentId } = req.query;
    
    const attempts = await assessmentService.getUserAssessmentAttempts(
      userId, 
      assessmentId as string | undefined
    );
    res.json(attempts);
  } catch (error) {
    console.error('Error fetching user assessment attempts:', error);
    res.status(500).json({ error: 'Failed to fetch assessment attempts' });
  }
});

// Start assessment attempt
router.post('/attempts/start', async (req, res) => {
  try {
    const { userId, assessmentId } = req.body;
    
    if (!userId || !assessmentId) {
      return res.status(400).json({ error: 'userId and assessmentId are required' });
    }
    
    const attempt = await assessmentService.startAssessmentAttempt(userId, assessmentId);
    res.json(attempt);
  } catch (error) {
    console.error('Error starting assessment attempt:', error);
    res.status(500).json({ error: 'Failed to start assessment attempt' });
  }
});

// Create assessment attempt
router.post('/attempts', async (req, res) => {
  try {
    const attemptData = req.body;
    
    if (!attemptData.userId || !attemptData.assessmentId) {
      return res.status(400).json({ error: 'userId and assessmentId are required' });
    }
    
    const attempt = await assessmentService.createAssessmentAttempt(attemptData);
    res.json(attempt);
  } catch (error) {
    console.error('Error creating assessment attempt:', error);
    res.status(500).json({ error: 'Failed to create assessment attempt' });
  }
});

// Submit assessment attempt
router.patch('/attempts/:attemptId/submit', async (req, res) => {
  try {
    const { attemptId } = req.params;
    const { answers, score } = req.body;
    
    if (!answers || typeof score !== 'number') {
      return res.status(400).json({ error: 'answers and score are required' });
    }
    
    const attempt = await assessmentService.submitAssessmentAttempt(attemptId, answers, score);
    if (!attempt) {
      return res.status(404).json({ error: 'Assessment attempt not found' });
    }
    
    res.json(attempt);
  } catch (error) {
    console.error('Error submitting assessment attempt:', error);
    res.status(500).json({ error: 'Failed to submit assessment attempt' });
  }
});

// Generate course certificate
router.post('/certificates/generate', async (req, res) => {
  try {
    const { userId, courseId } = req.body;
    
    if (!userId || !courseId) {
      return res.status(400).json({ error: 'userId and courseId are required' });
    }
    
    const certificate = await assessmentService.generateCourseCertificate(userId, courseId);
    res.json(certificate);
  } catch (error) {
    console.error('Error generating certificate:', error);
    res.status(500).json({ error: 'Failed to generate certificate' });
  }
});

// Get user certificates
router.get('/certificates/user/:userId', async (req, res) => {
  try {
    const { userId } = req.params;
    
    const certificates = await assessmentService.getUserCertificates(userId);
    res.json(certificates);
  } catch (error) {
    console.error('Error fetching user certificates:', error);
    res.status(500).json({ error: 'Failed to fetch user certificates' });
  }
});

export default router;
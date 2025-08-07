import { Router } from "express";
import { DatabaseStorage } from "../../storage";
import { HelpService } from "../../services";

const router = Router();
const storage = new DatabaseStorage();
const helpService = new HelpService(storage);

// Get help contents
router.get('/', async (req, res) => {
  try {
    const { establishmentId, role, category } = req.query;
    
    if (!establishmentId) {
      return res.status(400).json({ error: 'establishmentId is required' });
    }
    
    const contents = await helpService.getHelpContents(
      establishmentId as string, 
      role as string | undefined,
      category as string | undefined
    );
    res.json(contents);
  } catch (error) {
    console.error('Error fetching help contents:', error);
    res.status(500).json({ error: 'Failed to fetch help contents' });
  }
});

// Get help content by ID
router.get('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    
    const content = await helpService.getHelpContentById(id);
    if (!content) {
      return res.status(404).json({ error: 'Help content not found' });
    }
    
    res.json(content);
  } catch (error) {
    console.error('Error fetching help content:', error);
    res.status(500).json({ error: 'Failed to fetch help content' });
  }
});

// Search help content
router.get('/search', async (req, res) => {
  try {
    const { establishmentId, query, role } = req.query;
    
    if (!establishmentId || !query) {
      return res.status(400).json({ error: 'establishmentId and query are required' });
    }
    
    const results = await helpService.searchHelpContent(
      establishmentId as string,
      query as string,
      role as string | undefined
    );
    res.json(results);
  } catch (error) {
    console.error('Error searching help content:', error);
    res.status(500).json({ error: 'Help search failed' });
  }
});

// Create help content
router.post('/', async (req, res) => {
  try {
    const contentData = req.body;
    
    if (!contentData.title || !contentData.content || !contentData.establishmentId) {
      return res.status(400).json({ error: 'title, content, and establishmentId are required' });
    }
    
    const content = await helpService.createHelpContent(contentData);
    res.json(content);
  } catch (error) {
    console.error('Error creating help content:', error);
    res.status(500).json({ error: 'Failed to create help content' });
  }
});

// Update help content
router.patch('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const updates = req.body;
    
    const content = await helpService.updateHelpContent(id, updates);
    if (!content) {
      return res.status(404).json({ error: 'Help content not found' });
    }
    
    res.json(content);
  } catch (error) {
    console.error('Error updating help content:', error);
    res.status(500).json({ error: 'Failed to update help content' });
  }
});

// Delete help content
router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    
    await helpService.deleteHelpContent(id);
    res.json({ message: 'Help content deleted successfully' });
  } catch (error) {
    console.error('Error deleting help content:', error);
    res.status(500).json({ error: 'Failed to delete help content' });
  }
});

export default router;
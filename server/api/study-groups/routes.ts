import { Router } from "express";
import { DatabaseStorage } from "../../storage";
import { StudyGroupService } from "../../services";

const router = Router();
const storage = new DatabaseStorage();
const studyGroupService = new StudyGroupService(storage);

// Create study group
router.post('/', async (req, res) => {
  try {
    const groupData = req.body;
    
    if (!groupData.name || !groupData.establishmentId || !groupData.createdBy) {
      return res.status(400).json({ error: 'name, establishmentId, and createdBy are required' });
    }
    
    const group = await studyGroupService.createStudyGroup(groupData);
    res.json(group);
  } catch (error) {
    console.error('Error creating study group:', error);
    res.status(500).json({ error: 'Failed to create study group' });
  }
});

// Get study groups by establishment
router.get('/establishment/:establishmentId', async (req, res) => {
  try {
    const { establishmentId } = req.params;
    
    const groups = await studyGroupService.getStudyGroupsByEstablishment(establishmentId);
    res.json(groups);
  } catch (error) {
    console.error('Error fetching study groups:', error);
    res.status(500).json({ error: 'Failed to fetch study groups' });
  }
});

// Get specific study group
router.get('/:groupId', async (req, res) => {
  try {
    const { groupId } = req.params;
    
    const group = await studyGroupService.getStudyGroupById(groupId);
    if (!group) {
      return res.status(404).json({ error: 'Study group not found' });
    }
    
    res.json(group);
  } catch (error) {
    console.error('Error fetching study group:', error);
    res.status(500).json({ error: 'Failed to fetch study group' });
  }
});

// Join study group
router.post('/:groupId/join', async (req, res) => {
  try {
    const { groupId } = req.params;
    const { userId } = req.body;
    
    if (!userId) {
      return res.status(400).json({ error: 'userId is required' });
    }
    
    const membership = await studyGroupService.joinStudyGroup(groupId, userId);
    res.json(membership);
  } catch (error) {
    console.error('Error joining study group:', error);
    res.status(500).json({ error: 'Failed to join study group' });
  }
});

// Get study group members
router.get('/:groupId/members', async (req, res) => {
  try {
    const { groupId } = req.params;
    
    const members = await studyGroupService.getStudyGroupMembers(groupId);
    res.json(members);
  } catch (error) {
    console.error('Error fetching study group members:', error);
    res.status(500).json({ error: 'Failed to fetch members' });
  }
});

// Create message in study group
router.post('/:groupId/messages', async (req, res) => {
  try {
    const { groupId } = req.params;
    const messageData = { ...req.body, studyGroupId: groupId };
    
    if (!messageData.senderId || !messageData.content) {
      return res.status(400).json({ error: 'senderId and content are required' });
    }
    
    const message = await studyGroupService.createMessage(messageData);
    res.json(message);
  } catch (error) {
    console.error('Error creating message:', error);
    res.status(500).json({ error: 'Failed to create message' });
  }
});

// Get study group messages
router.get('/:groupId/messages', async (req, res) => {
  try {
    const { groupId } = req.params;
    const { limit } = req.query;
    
    const messages = await studyGroupService.getStudyGroupMessages(
      groupId, 
      limit ? parseInt(limit as string) : undefined
    );
    res.json(messages);
  } catch (error) {
    console.error('Error fetching messages:', error);
    res.status(500).json({ error: 'Failed to fetch messages' });
  }
});

// Create whiteboard for study group
router.post('/:groupId/whiteboards', async (req, res) => {
  try {
    const { groupId } = req.params;
    const whiteboardData = { ...req.body, studyGroupId: groupId };
    
    if (!whiteboardData.name || !whiteboardData.createdBy) {
      return res.status(400).json({ error: 'name and createdBy are required' });
    }
    
    const whiteboard = await studyGroupService.createWhiteboard(whiteboardData);
    res.json(whiteboard);
  } catch (error) {
    console.error('Error creating whiteboard:', error);
    res.status(500).json({ error: 'Failed to create whiteboard' });
  }
});

// Get study group whiteboards
router.get('/:groupId/whiteboards', async (req, res) => {
  try {
    const { groupId } = req.params;
    
    const whiteboards = await studyGroupService.getStudyGroupWhiteboards(groupId);
    res.json(whiteboards);
  } catch (error) {
    console.error('Error fetching whiteboards:', error);
    res.status(500).json({ error: 'Failed to fetch whiteboards' });
  }
});

// Update whiteboard
router.patch('/whiteboards/:whiteboardId', async (req, res) => {
  try {
    const { whiteboardId } = req.params;
    const updateData = req.body;
    
    const whiteboard = await studyGroupService.updateWhiteboard(whiteboardId, updateData);
    res.json(whiteboard);
  } catch (error) {
    console.error('Error updating whiteboard:', error);
    res.status(500).json({ error: 'Failed to update whiteboard' });
  }
});

export default router;
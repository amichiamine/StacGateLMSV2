import { Router } from "express";
import { DatabaseStorage } from "../../storage";
import { SystemService } from "../../services";

const router = Router();
const storage = new DatabaseStorage();
const systemService = new SystemService(storage);

// Get system versions
router.get('/versions', async (req, res) => {
  try {
    const versions = await systemService.getSystemVersions();
    res.json(versions);
  } catch (error) {
    console.error('Error fetching system versions:', error);
    res.status(500).json({ error: 'Failed to fetch system versions' });
  }
});

// Get active system version
router.get('/versions/active', async (req, res) => {
  try {
    const activeVersion = await systemService.getActiveSystemVersion();
    res.json(activeVersion);
  } catch (error) {
    console.error('Error fetching active system version:', error);
    res.status(500).json({ error: 'Failed to fetch active system version' });
  }
});

// Create system version
router.post('/versions', async (req, res) => {
  try {
    const versionData = req.body;
    
    if (!versionData.version || !versionData.releaseNotes) {
      return res.status(400).json({ error: 'version and releaseNotes are required' });
    }
    
    const version = await systemService.createSystemVersion(versionData);
    res.json(version);
  } catch (error) {
    console.error('Error creating system version:', error);
    res.status(500).json({ error: 'Failed to create system version' });
  }
});

// Activate system version
router.post('/versions/:id/activate', async (req, res) => {
  try {
    const { id } = req.params;
    
    await systemService.activateSystemVersion(id);
    res.json({ message: 'System version activated successfully' });
  } catch (error) {
    console.error('Error activating system version:', error);
    res.status(500).json({ error: 'Failed to activate system version' });
  }
});

// Get maintenance status
router.get('/maintenance', async (req, res) => {
  try {
    const status = await systemService.getMaintenanceStatus();
    res.json(status);
  } catch (error) {
    console.error('Error fetching maintenance status:', error);
    res.status(500).json({ error: 'Failed to fetch maintenance status' });
  }
});

// Set maintenance mode
router.post('/maintenance', async (req, res) => {
  try {
    const { enabled, message } = req.body;
    
    if (typeof enabled !== 'boolean') {
      return res.status(400).json({ error: 'enabled (boolean) is required' });
    }
    
    await systemService.setMaintenanceMode(enabled, message);
    res.json({ message: `Maintenance mode ${enabled ? 'enabled' : 'disabled'} successfully` });
  } catch (error) {
    console.error('Error setting maintenance mode:', error);
    res.status(500).json({ error: 'Failed to set maintenance mode' });
  }
});

// Get establishment branding
router.get('/branding/:establishmentId', async (req, res) => {
  try {
    const { establishmentId } = req.params;
    
    const branding = await systemService.getEstablishmentBranding(establishmentId);
    res.json(branding);
  } catch (error) {
    console.error('Error fetching establishment branding:', error);
    res.status(500).json({ error: 'Failed to fetch establishment branding' });
  }
});

// Create establishment branding
router.post('/branding', async (req, res) => {
  try {
    const brandingData = req.body;
    
    if (!brandingData.establishmentId) {
      return res.status(400).json({ error: 'establishmentId is required' });
    }
    
    const branding = await systemService.createEstablishmentBranding(brandingData);
    res.json(branding);
  } catch (error) {
    console.error('Error creating establishment branding:', error);
    res.status(500).json({ error: 'Failed to create establishment branding' });
  }
});

// Update establishment branding
router.patch('/branding/:establishmentId', async (req, res) => {
  try {
    const { establishmentId } = req.params;
    const updates = req.body;
    
    const branding = await systemService.updateEstablishmentBranding(establishmentId, updates);
    if (!branding) {
      return res.status(404).json({ error: 'Establishment branding not found' });
    }
    
    res.json(branding);
  } catch (error) {
    console.error('Error updating establishment branding:', error);
    res.status(500).json({ error: 'Failed to update establishment branding' });
  }
});

export default router;
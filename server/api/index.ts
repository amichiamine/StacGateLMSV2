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

// Direct documentation routes (must come before generic routes)
router.get('/documentation/help', async (req, res) => {
  const { role } = req.query;
  
  // Return basic help structure (static data)
  const helpContents = [
    {
      id: 'getting-started',
      title: 'Guide de démarrage',
      content: 'Bienvenue dans StacGate LMS! Ce guide vous aidera à commencer.',
      category: 'general',
      targetRoles: ['apprenant', 'formateur', 'admin', 'super_admin'],
      order: 1
    },
    {
      id: 'navigation',
      title: 'Navigation',
      content: 'Comment naviguer dans la plateforme et accéder aux différentes fonctionnalités.',
      category: 'general',
      targetRoles: ['apprenant', 'formateur', 'admin', 'super_admin'],
      order: 2
    },
    {
      id: 'courses',
      title: 'Gestion des cours',
      content: 'Comment créer, modifier et gérer vos cours sur la plateforme.',
      category: 'courses',
      targetRoles: ['formateur', 'admin', 'super_admin'],
      order: 3
    }
  ];
  
  // Filter by role if specified
  const filteredContents = role 
    ? helpContents.filter(content => content.targetRoles.includes(role as string))
    : helpContents;
  
  res.json(filteredContents);
});

// Search documentation
router.get('/documentation/search', async (req, res) => {
  const { query, role } = req.query;
  
  if (!query || typeof query !== 'string') {
    return res.status(400).json({ error: 'Search query is required' });
  }
  
  const searchResults = [
    {
      id: 'getting-started',
      title: 'Guide de démarrage',
      content: 'Bienvenue dans StacGate LMS! Ce guide vous aidera à commencer.',
      category: 'general',
      relevance: 0.9
    }
  ].filter(result => result.title.toLowerCase().includes(query.toLowerCase()) || 
                     result.content.toLowerCase().includes(query.toLowerCase()));
  
  res.json(searchResults);
});

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

// Establishment content route
router.get('/establishment-content/:slug/:page', async (req, res) => {
  try {
    const { slug, page } = req.params;
    
    // Return basic customizable content structure
    const content = {
      establishmentSlug: slug,
      pageName: page,
      content: {
        title: `Contenu personnalisé pour ${page}`,
        description: `Page ${page} de l'établissement ${slug}`,
        sections: [
          {
            type: 'hero',
            title: `Bienvenue sur ${page}`,
            subtitle: 'Contenu personnalisable pour cette page',
            image: '/images/default-hero.jpg'
          },
          {
            type: 'text',
            content: `Ceci est le contenu par défaut pour la page ${page}. Vous pouvez personnaliser ce contenu via l'administration.`
          }
        ]
      },
      isActive: true,
      lastModified: new Date().toISOString()
    };
    
    res.json(content);
  } catch (error) {
    console.error('Error fetching establishment content:', error);
    res.status(500).json({ error: 'Failed to fetch establishment content' });
  }
});

// Health check endpoint
router.get('/health', (req, res) => {
  res.json({ 
    status: 'ok', 
    timestamp: new Date().toISOString(),
    version: '1.0.0'
  });
});

export default router;
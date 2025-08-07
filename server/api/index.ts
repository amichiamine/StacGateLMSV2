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

// Admin routes for WYSIWYG editor
router.get('/admin/pages/:pageName', async (req, res) => {
  const { pageName } = req.params;
  
  // Return basic page structure for WYSIWYG editor
  const pageData = {
    id: pageName,
    name: pageName,
    title: `Page ${pageName}`,
    content: {
      sections: [
        {
          id: 'hero',
          type: 'hero',
          title: `Bienvenue sur ${pageName}`,
          subtitle: 'Contenu personnalisable',
          backgroundImage: null
        }
      ]
    },
    metadata: {
      description: `Page ${pageName} personnalisée`,
      keywords: [pageName, 'education', 'learning']
    },
    isPublished: true,
    lastModified: new Date().toISOString()
  };
  
  res.json(pageData);
});

router.get('/admin/components', async (req, res) => {
  // Return available components for WYSIWYG editor
  const components = [
    {
      id: 'hero',
      name: 'Section Hero',
      category: 'layout',
      description: 'Section d\'en-tête avec titre et sous-titre'
    },
    {
      id: 'text',
      name: 'Texte',
      category: 'content',
      description: 'Bloc de texte simple'
    },
    {
      id: 'image',
      name: 'Image',
      category: 'media',
      description: 'Image avec description'
    },
    {
      id: 'button',
      name: 'Bouton',
      category: 'interactive',
      description: 'Bouton d\'action'
    }
  ];
  
  res.json(components);
});

// Super admin portal customization routes
router.get('/super-admin/portal-themes', async (req, res) => {
  const themes = [
    {
      id: 'default',
      name: 'Thème par défaut',
      description: 'Thème standard de StacGate LMS',
      colors: {
        primary: '#0066cc',
        secondary: '#f8f9fa',
        accent: '#28a745'
      },
      isActive: true
    },
    {
      id: 'dark',
      name: 'Thème sombre',
      description: 'Thème sombre moderne',
      colors: {
        primary: '#1a1a1a',
        secondary: '#333333',
        accent: '#0084ff'
      },
      isActive: false
    }
  ];
  
  res.json(themes);
});

router.get('/super-admin/portal-contents', async (req, res) => {
  const contents = [
    {
      id: 'welcome',
      type: 'text',
      position: 'hero',
      title: 'Bienvenue sur StacGate LMS',
      content: 'La plateforme d\'apprentissage nouvelle génération',
      isVisible: true,
      order: 1
    },
    {
      id: 'features',
      type: 'list',
      position: 'main',
      title: 'Fonctionnalités',
      content: [
        'Gestion multi-établissements',
        'Cours interactifs',
        'Évaluations avancées',
        'Collaboration temps réel'
      ],
      isVisible: true,
      order: 2
    }
  ];
  
  res.json(contents);
});

router.get('/super-admin/portal-menu-items', async (req, res) => {
  const menuItems = [
    {
      id: 'home',
      label: 'Accueil',
      url: '/',
      icon: 'home',
      isVisible: true,
      order: 1,
      requiresAuth: false
    },
    {
      id: 'courses',
      label: 'Cours',
      url: '/courses',
      icon: 'book',
      isVisible: true,
      order: 2,
      requiresAuth: true
    },
    {
      id: 'dashboard',
      label: 'Tableau de bord',
      url: '/dashboard',
      icon: 'dashboard',
      isVisible: true,
      order: 3,
      requiresAuth: true
    }
  ];
  
  res.json(menuItems);
});

// Export create endpoint
router.post('/export/create', async (req, res) => {
  try {
    const { type, filters, format } = req.body;
    
    if (!type) {
      return res.status(400).json({ error: 'Export type is required' });
    }
    
    // Create mock export job
    const jobId = 'job-' + Date.now();
    const job = {
      id: jobId,
      type: type,
      status: 'pending',
      progress: 0,
      createdAt: new Date().toISOString(),
      completedAt: null,
      downloadUrl: null,
      fileName: `${type}-export.${format || 'zip'}`,
      fileSize: null,
      createdBy: 'current-user',
      establishmentId: 'est-001-main'
    };
    
    // Simulate processing by updating status after a delay
    setTimeout(() => {
      job.status = 'processing';
      job.progress = 25;
    }, 1000);
    
    res.status(201).json(job);
  } catch (error) {
    console.error('Error creating export:', error);
    res.status(500).json({ error: 'Failed to create export' });
  }
});

// Assessments endpoint
router.get('/assessments', async (req, res) => {
  try {
    const assessments = [
      {
        id: 'assess-001',
        title: 'Évaluation JavaScript Fondamentaux',
        description: 'Test des connaissances de base en JavaScript',
        type: 'quiz',
        duration: 30,
        totalQuestions: 15,
        passingScore: 70,
        isActive: true,
        createdAt: new Date().toISOString(),
        establishmentId: 'est-001-main'
      },
      {
        id: 'assess-002',
        title: 'Examen React Avancé',
        description: 'Évaluation avancée des concepts React',
        type: 'exam',
        duration: 90,
        totalQuestions: 25,
        passingScore: 80,
        isActive: true,
        createdAt: new Date().toISOString(),
        establishmentId: 'est-001-main'
      }
    ];
    res.json(assessments);
  } catch (error) {
    console.error('Error fetching assessments:', error);
    res.status(500).json({ error: 'Failed to fetch assessments' });
  }
});

// Assessment attempts endpoint
router.get('/assessment-attempts', async (req, res) => {
  try {
    const { userId, assessmentId } = req.query;
    const attempts = [
      {
        id: 'attempt-001',
        userId: userId || 'user-001',
        assessmentId: assessmentId || 'assess-001',
        score: 85,
        totalQuestions: 15,
        correctAnswers: 13,
        startedAt: new Date(Date.now() - 3600000).toISOString(),
        completedAt: new Date().toISOString(),
        status: 'completed'
      }
    ];
    res.json(attempts);
  } catch (error) {
    console.error('Error fetching assessment attempts:', error);
    res.status(500).json({ error: 'Failed to fetch assessment attempts' });
  }
});

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
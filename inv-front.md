# INVENTAIRE FRONTEND - StacGateLMS

## 1. ARCHITECTURE GÉNÉRALE

### Structure des répertoires
```
client/
├── index.html                    # Point d'entrée HTML
└── src/
    ├── main.tsx                  # Point d'entrée React
    ├── App.tsx                   # Composant racine avec routeur
    ├── index.css                 # Styles globaux et variables CSS
    ├── components/               # Composants partagés
    ├── hooks/                    # Hooks React personnalisés
    ├── lib/                      # Utilitaires et configurations
    └── pages/                    # Pages de l'application
```

## 2. COMPOSANTS UI (SHADCN/UI)

### Composants de base (client/src/components/ui/)
- **accordion.tsx** - Composant accordéon pliable
- **alert-dialog.tsx** - Modales d'alerte
- **alert.tsx** - Messages d'alerte
- **aspect-ratio.tsx** - Contrôle des proportions
- **avatar.tsx** - Images de profil utilisateur
- **badge.tsx** - Étiquettes et badges
- **breadcrumb.tsx** - Navigation par fil d'Ariane
- **button.tsx** - Boutons avec variants
- **calendar.tsx** - Composant calendrier
- **card.tsx** - Cartes de contenu
- **carousel.tsx** - Carrousel d'images/contenu
- **chart.tsx** - Graphiques et visualisations
- **checkbox.tsx** - Cases à cocher
- **collapsible.tsx** - Sections repliables
- **command.tsx** - Palette de commandes
- **context-menu.tsx** - Menus contextuels
- **dialog.tsx** - Modales et dialogues
- **drawer.tsx** - Tiroirs latéraux
- **dropdown-menu.tsx** - Menus déroulants
- **form.tsx** - Composants de formulaire
- **hover-card.tsx** - Cartes au survol
- **input-otp.tsx** - Saisie de codes OTP
- **input.tsx** - Champs de saisie
- **label.tsx** - Étiquettes de champ
- **menubar.tsx** - Barres de menu
- **navigation-menu.tsx** - Menus de navigation
- **pagination.tsx** - Pagination de contenu
- **popover.tsx** - Fenêtres contextuelles
- **progress.tsx** - Barres de progression
- **radio-group.tsx** - Groupes de boutons radio
- **resizable.tsx** - Panneaux redimensionnables
- **scroll-area.tsx** - Zones de défilement
- **select.tsx** - Listes de sélection
- **separator.tsx** - Séparateurs visuels
- **sheet.tsx** - Feuilles latérales
- **sidebar.tsx** - Barres latérales
- **skeleton.tsx** - Squelettes de chargement
- **slider.tsx** - Curseurs de valeur
- **switch.tsx** - Interrupteurs on/off
- **table.tsx** - Tableaux de données
- **tabs.tsx** - Onglets
- **textarea.tsx** - Zones de texte multiligne
- **toast.tsx** - Notifications toast
- **toaster.tsx** - Gestionnaire de toasts
- **toggle-group.tsx** - Groupes de boutons toggle
- **toggle.tsx** - Boutons à bascule
- **tooltip.tsx** - Info-bulles

### Composants spécialisés (client/src/components/)
- **PortalCustomization.tsx** - Personnalisation du portail
- **features-section.tsx** - Section des fonctionnalités
- **footer.tsx** - Pied de page
- **hero-section.tsx** - Section héro d'accueil
- **navigation.tsx** - Navigation principale
- **popular-courses-section.tsx** - Section des cours populaires
- **wysiwyg/** - Éditeur WYSIWYG (répertoire)

## 3. HOOKS PERSONNALISÉS (client/src/hooks/)

- **use-mobile.tsx** - Détection des appareils mobiles
- **use-toast.ts** - Gestion des notifications toast
- **useAuth.ts** - Authentification et gestion utilisateur
- **useTheme.ts** - Gestion du thème sombre/clair

## 4. PAGES DE L'APPLICATION (client/src/pages/)

### Pages principales
- **home.tsx** - Page d'accueil générale
- **landing.tsx** - Page de présentation/landing
- **portal.tsx** - Portail de sélection d'établissement
- **portal-old.tsx** - Ancien portail (legacy)
- **login.tsx** - Page de connexion
- **dashboard.tsx** - Tableau de bord utilisateur
- **not-found.tsx** - Page 404

### Pages administratives
- **admin.tsx** - Interface d'administration
- **super-admin.tsx** - Interface super administrateur
- **user-management.tsx** - Gestion des utilisateurs
- **establishment.tsx** - Gestion des établissements

### Pages pédagogiques
- **courses.tsx** - Catalogue des cours
- **assessments.tsx** - Évaluations et examens
- **study-groups.tsx** - Groupes d'étude

### Pages utilitaires
- **user-manual.tsx** - Manuel utilisateur
- **archive-export.tsx** - Archivage et export
- **system-updates.tsx** - Mises à jour système
- **wysiwyg-editor.tsx** - Éditeur WYSIWYG

## 5. UTILITAIRES ET CONFIGURATION (client/src/lib/)

- **authUtils.ts** - Utilitaires d'authentification
- **queryClient.ts** - Configuration TanStack Query
- **utils.ts** - Fonctions utilitaires générales

## 6. ROUTAGE (Wouter)

### Routes configurées dans App.tsx
```typescript
- "/" → Home (page d'accueil)
- "/portal" → Portal (sélection établissement)
- "/establishment/:slug" → Establishment (vue établissement)
- "/login" → Login (connexion)
- "/dashboard" → Dashboard (tableau de bord)
- "/admin" → AdminPage (administration)
- "/super-admin" → SuperAdminPage (super admin)
- "/user-management" → UserManagement (gestion utilisateurs)
- "/courses" → CoursesPage (cours)
- "/assessments" → AssessmentsPage (évaluations)
- "/manual" → UserManualPage (manuel)
- "/archive" → ArchiveExportPage (archives)
- "/system-updates" → SystemUpdatesPage (MAJ système)
- "/wysiwyg-editor" → WysiwygEditorPage (éditeur)
- "/study-groups" → StudyGroupsPage (groupes d'étude)
- "/*" → NotFound (404)
```

## 7. GESTION D'ÉTAT

### TanStack Query
- **Configuration**: client/src/lib/queryClient.ts
- **Fonctions utilitaires**:
  - `apiRequest()` - Requêtes HTTP avec authentification
  - `getQueryFn()` - Fonction de requête par défaut
  - Gestion automatique du cache et des erreurs 401

### Authentification
- **Hook principal**: useAuth()
- **États gérés**: user, isLoading, isAuthenticated
- **Persistance**: Session cookies avec Express

## 8. STYLING ET THÈME

### Configuration Tailwind CSS
- **Fichier principal**: index.css
- **Variables CSS**: définition des couleurs et thèmes
- **Mode sombre**: support intégré avec next-themes
- **Responsive**: design mobile-first

### Système de thème
- **Couleurs personnalisables** par établissement
- **Variables CSS** dynamiques
- **Support dark/light mode**

## 9. FONCTIONNALITÉS PRINCIPALES

### Authentification
- Connexion locale avec email/mot de passe
- Gestion de session
- Contrôle d'accès par rôle
- Redirection automatique

### Tableau de bord
- Statistiques utilisateur
- Navigation par rôle
- Accès rapide aux fonctions principales
- Interface responsive

### Gestion des cours
- Catalogue de cours
- Inscription et suivi
- Évaluations intégrées
- Progression utilisateur

### Personnalisation
- Thèmes par établissement
- Contenu personnalisable
- Éditeur WYSIWYG
- Branding adaptatif

### Administration
- Gestion multi-utilisateurs
- Contrôle des accès
- Statistiques et rapports
- Archivage et export

## 10. ICÔNES ET ASSETS

### Bibliothèque Lucide React
Plus de 50 icônes utilisées dans l'interface :
- BookOpen, Users, Calendar, Award
- Settings, Shield, FileText, Trophy
- TrendingUp, Clock, MessageSquare
- Archive, RefreshCw, etc.

### Assets personnalisés
- Logos d'établissement
- Images de cours
- Avatars utilisateur
- Médias pédagogiques

## 11. ACCESSIBILITÉ

### Features implémentées
- Navigation au clavier
- ARIA labels sur les composants interactifs
- Contraste de couleurs respecté
- Support des lecteurs d'écran
- Design responsive

### Data-testid
- Attributs data-testid sur tous les éléments interactifs
- Convention de nommage : {action}-{target}
- Éléments dynamiques avec identifiants uniques

## 12. PERFORMANCE

### Optimisations
- Code splitting par pages
- Lazy loading des composants
- Cache TanStack Query optimisé
- Gestion des états de chargement
- Skeleton loading pour l'UX

## 13. INTÉGRATIONS

### APIs backend
- Authentification : /api/auth/*
- Utilisateurs : /api/users/*
- Cours : /api/courses/*
- Établissements : /api/establishments/*
- Contenu : /api/content/*

### WebSocket
- Messages en temps réel
- Notifications push
- Collaboration temps réel

## 14. ERREURS ET DEBUG

### Gestion d'erreurs
- Toast notifications pour les erreurs
- Pages d'erreur dédiées (404)
- Logs client-side
- Retry automatique des requêtes

## 15. CONFORMITÉ ET STANDARDS

### Technologies
- React 18.3.1 avec TypeScript
- Vite comme bundler
- ESLint pour le linting
- Prettier pour le formatage
- Architecture composants modulaire

### Patterns utilisés
- Hooks personnalisés
- Context providers
- Higher-order components
- Render props pattern
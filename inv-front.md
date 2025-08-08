# INVENTAIRE EXHAUSTIF FRONTEND - StacGateLMS

## ARCHITECTURE GÉNÉRALE FRONTEND

### Structure des dossiers
```
client/
├── src/
│   ├── components/          # Composants réutilisables
│   │   ├── ui/             # Composants UI Shadcn/ui (50+ composants)
│   │   ├── wysiwyg/        # Éditeur WYSIWYG (5 composants)
│   │   └── [composants métier] # Composants spécifiques application
│   ├── pages/              # Pages de l'application (20 pages)
│   ├── hooks/              # Hooks personnalisés (5 hooks)
│   ├── lib/                # Utilitaires et configuration
│   ├── main.tsx            # Point d'entrée React
│   ├── App.tsx             # Configuration routage et providers
│   └── index.css           # Styles globaux et thème Glassmorphism
├── index.html              # Template HTML principal
└── [config files]         # Configuration build et types
```

### Technologies Frontend
- **Framework**: React 18 avec TypeScript
- **Build Tool**: Vite avec plugins Replit
- **Routage**: Wouter (route-based)
- **État Global**: TanStack Query v5 pour data fetching
- **Styling**: Tailwind CSS + Glassmorphism custom
- **UI Components**: Shadcn/ui (système de design complet)
- **Forms**: React Hook Form + Zod validation
- **Icons**: Lucide React + React Icons
- **Themes**: Support dark/light avec variables CSS

## PAGES DE L'APPLICATION (20 pages)

### 1. Pages Publiques
#### `/` - Home (home.tsx)
- **Composants principaux**: Navigation, HeroSection, FeaturesSection, PopularCoursesSection, Footer
- **Fonctionnalités**: Landing page avec glassmorphism, présentation plateforme
- **Boutons**: "Commencer", "En savoir plus", navigation mobile
- **Imports**: Navigation, composants section, Lucide icons

#### `/portal` - Portal (portal.tsx)
- **Composants**: Sélecteur d'établissement, PortalCustomization
- **Fonctionnalités**: Multi-tenant, sélection établissement avec personnalisation
- **État**: Establishments query, customization query
- **Imports**: PortalCustomization, useQuery, Card components

#### `/establishment/:slug` - Establishment (establishment.tsx)  
- **Composants**: Navigation personnalisée, contenu dynamique
- **Fonctionnalités**: Page établissement personnalisable avec thème
- **État**: Establishment data, custom content
- **Imports**: Navigation, établissement service

#### `/login` - Login (login.tsx)
- **Composants**: Tabs (connexion/inscription), formulaires avec validation
- **Fonctionnalités**: Authentification, inscription, sélection établissement
- **État**: Forms states, establishments query, loading states
- **Imports**: Tabs, Input, Select, useToast, useQuery
- **Formulaires**: Email/password, establishment selection

### 2. Pages Authentifiées

#### `/dashboard` - Dashboard (dashboard.tsx)
- **Composants**: Navigation, Cards statistiques, Avatars, Badges
- **Fonctionnalités**: Tableau de bord role-based, statistiques temps réel
- **État**: User auth, courses data, users data (selon rôle)
- **Imports**: useAuth, useQuery, Navigation, Card components
- **Sections**: Header glassmorphism, stats cards, quick actions
- **Boutons**: Refresh session, navigation rapide, actions selon rôle

#### `/courses` - Courses (courses.tsx)
- **Composants**: Course cards, filters, modals, tabs
- **Fonctionnalités**: Gestion cours complète, création, inscription
- **État**: Courses query, filters, create modal, enrollment
- **Imports**: Dialog, Tabs, Badge, useAuth, apiRequest
- **Formulaires**: Création cours, filtres, recherche
- **Boutons**: Créer cours, s'inscrire, voir détails, filtres

#### `/admin` - Admin (admin.tsx)
- **Composants**: Tabs multiples, formulaires complexes, PageEditor
- **Fonctionnalités**: Administration complète (thèmes, contenus, menus, utilisateurs)
- **État**: Multiple query states, forms states, modals
- **Imports**: Tabs, Input, Textarea, Select, PageEditor, apiRequest
- **Sections**:
  - Gestion établissements
  - Gestion thèmes  
  - Personnalisation contenus
  - Gestion menus
  - Gestion utilisateurs
  - WYSIWYG Editor
- **Formulaires**: 6+ formulaires complexes avec validation

#### `/super-admin` - Super Admin (super-admin.tsx)
- **Composants**: Portal customization, système administration
- **Fonctionnalités**: Administration globale multi-tenant
- **État**: Global settings, portal themes, system stats
- **Imports**: PortalCustomization, admin components
- **Sections**: Configuration globale, thèmes portail, établissements

#### `/user-management` - User Management (user-management.tsx)
- **Composants**: Tables utilisateurs, modals, filtres
- **Fonctionnalités**: CRUD utilisateurs, rôles, permissions
- **État**: Users query, filters, create/edit modals
- **Imports**: Table, Dialog, Select, Badge components
- **Boutons**: Créer utilisateur, éditer, supprimer, filtres rôles

#### `/analytics` - Analytics (analytics.tsx)
- **Composants**: Charts (Recharts), metrics cards, date pickers
- **Fonctionnalités**: Dashboard analytique temps réel
- **État**: Analytics data query, date ranges, filters
- **Imports**: Recharts, Card, Calendar, date-fns
- **Sections**:
  - Métriques générales
  - Graphiques cours populaires  
  - Activités récentes
  - Stats utilisateurs
- **Graphiques**: BarChart, PieChart, LineChart, AreaChart

#### `/assessments` - Assessments (assessments.tsx)
- **Composants**: Assessment cards, creation forms, attempt tracking
- **Fonctionnalités**: Gestion évaluations, quiz, examens
- **État**: Assessments query, attempts data, create forms
- **Imports**: Card, Dialog, Progress, Badge
- **Boutons**: Créer évaluation, passer test, voir résultats

#### `/study-groups` - Study Groups (study-groups.tsx)
- **Composants**: Group cards, chat interface, member management
- **Fonctionnalités**: Groupes d'étude, collaboration, chat temps réel
- **État**: Groups data, chat messages, member states
- **Imports**: Card, Avatar, Button, collaboration hooks
- **Sections**: Liste groupes, chat interface, gestion membres

#### `/help-center` - Help Center (help-center.tsx)
- **Composants**: Search bar, category filters, documentation cards
- **Fonctionnalités**: Centre aide avec recherche et filtres
- **État**: Help articles query, search state, filters
- **Imports**: Input, Card, Badge, search components
- **Sections**: Recherche, catégories, articles, FAQ

### 3. Pages Spécialisées

#### `/wysiwyg-editor` - WYSIWYG Editor (wysiwyg-editor.tsx)
- **Composants**: PageEditor, ComponentLibrary, Preview
- **Fonctionnalités**: Éditeur visuel pages personnalisées
- **État**: Page data, component library, preview mode
- **Imports**: WYSIWYG components, editor utilities

#### `/archive-export` - Archive Export (archive-export.tsx)
- **Composants**: Export forms, progress bars, download links
- **Fonctionnalités**: Export données, archives, backup
- **État**: Export jobs, progress tracking, file management
- **Imports**: Progress, Button, Select, file utilities

#### `/system-updates` - System Updates (system-updates.tsx)
- **Composants**: Update cards, version info, changelog
- **Fonctionnalités**: Gestion mises à jour système
- **État**: Updates data, version tracking, deployment status
- **Imports**: Card, Badge, version components

#### `/user-manual` - User Manual (user-manual.tsx)
- **Composants**: Manual navigation, content display, search
- **Fonctionnalités**: Manuel utilisateur intégré
- **État**: Manual content, navigation state, search
- **Imports**: Navigation, content display components

#### `/not-found` - Not Found (not-found.tsx)
- **Composants**: Error display, navigation back
- **Fonctionnalités**: Page 404 personnalisée
- **Boutons**: Retour accueil, navigation

## COMPOSANTS RÉUTILISABLES

### Composants Métier (8 composants)

#### Navigation (navigation.tsx)
- **Fonctionnalités**: Navigation responsive avec glassmorphism
- **État**: Mobile menu toggle
- **Éléments**:
  - Logo StacGateLMS avec gradient
  - Menu desktop (Cours, À propos, Contact)
  - Boutons Connexion/Commencer  
  - Menu mobile hamburger avec glassmorphism
  - Overlay mobile avec backdrop blur
- **Imports**: Link (wouter), Button, Lucide icons
- **Styles**: Glass navigation, mobile glass menu

#### CollaborationIndicator (CollaborationIndicator.tsx)
- **Fonctionnalités**: Indicateur collaboration temps réel
- **État**: Collaboration status, participants count
- **Éléments**: Badge status, participant avatars, activity indicator
- **Imports**: useCollaboration, Avatar, Badge

#### PortalCustomization (PortalCustomization.tsx)
- **Fonctionnalités**: Personnalisation portail établissements
- **État**: Theme settings, content customization
- **Éléments**: Color pickers, content editors, preview
- **Imports**: Color components, form controls

#### HeroSection (hero-section.tsx)
- **Fonctionnalités**: Section hero avec glassmorphism
- **Éléments**: Titre principal, sous-titre, CTA buttons
- **Styles**: Gradient backgrounds, glass effects

#### FeaturesSection (features-section.tsx)
- **Fonctionnalités**: Présentation fonctionnalités
- **Éléments**: Feature cards avec icons, descriptions
- **Imports**: Lucide icons, Card components

#### PopularCoursesSection (popular-courses-section.tsx)
- **Fonctionnalités**: Cours populaires homepage
- **État**: Popular courses query
- **Éléments**: Course cards, ratings, enrollment counts

#### Footer (footer.tsx)
- **Fonctionnalités**: Footer site avec liens
- **Éléments**: Copyright, liens légaux, réseaux sociaux
- **Styles**: Glassmorphism footer

### Composants WYSIWYG (5 composants)

#### PageEditor (wysiwyg/PageEditor.tsx)
- **Fonctionnalités**: Éditeur pages principal
- **État**: Page content, component selection, preview mode
- **Éléments**: Toolbar, canvas, properties panel
- **Imports**: ComponentLibrary, PagePreview

#### ComponentLibrary (wysiwyg/ComponentLibrary.tsx)
- **Fonctionnalités**: Bibliothèque composants disponibles
- **État**: Component categories, search, filters
- **Éléments**: Component palette, drag/drop interface

#### ComponentEditor (wysiwyg/ComponentEditor.tsx)
- **Fonctionnalités**: Éditeur propriétés composants
- **État**: Selected component, properties
- **Éléments**: Properties form, style editor

#### PagePreview (wysiwyg/PagePreview.tsx)
- **Fonctionnalités**: Aperçu temps réel page
- **État**: Preview mode, responsive testing
- **Éléments**: Preview iframe, device simulation

#### ColorPicker (wysiwyg/ColorPicker.tsx)
- **Fonctionnalités**: Sélecteur couleurs
- **État**: Color values, palette
- **Éléments**: Color wheel, palette, inputs

### Composants UI Shadcn/ui (50+ composants)

#### Layout & Navigation
- **accordion.tsx**: Composant accordéon extensible
- **breadcrumb.tsx**: Navigation breadcrumb
- **menubar.tsx**: Barre menu horizontal
- **navigation-menu.tsx**: Menu navigation complexe
- **pagination.tsx**: Pagination données
- **sidebar.tsx**: Barre latérale
- **tabs.tsx**: Système onglets

#### Forms & Inputs
- **button.tsx**: Boutons avec variants
- **input.tsx**: Champs saisie
- **textarea.tsx**: Zone texte multiligne
- **label.tsx**: Labels formulaires
- **form.tsx**: Wrapper formulaires avec validation
- **checkbox.tsx**: Cases à cocher
- **radio-group.tsx**: Groupes radio
- **select.tsx**: Listes déroulantes
- **slider.tsx**: Sliders valeurs
- **switch.tsx**: Interrupteurs
- **input-otp.tsx**: Saisie codes OTP
- **calendar.tsx**: Sélecteur dates

#### Display & Feedback
- **card.tsx**: Cartes contenu
- **badge.tsx**: Badges statut
- **avatar.tsx**: Avatars utilisateurs
- **alert.tsx**: Alertes système
- **toast.tsx**: Notifications toast
- **toaster.tsx**: Gestionnaire toasts
- **progress.tsx**: Barres progression
- **skeleton.tsx**: Squelettes chargement
- **table.tsx**: Tableaux données

#### Overlays & Modals
- **dialog.tsx**: Dialogues/modals
- **alert-dialog.tsx**: Dialogues confirmation
- **sheet.tsx**: Panneaux latéraux
- **drawer.tsx**: Tiroirs mobiles
- **popover.tsx**: Popovers
- **hover-card.tsx**: Cartes survol
- **tooltip.tsx**: Info-bulles
- **context-menu.tsx**: Menus contextuels
- **dropdown-menu.tsx**: Menus déroulants

#### Layout & Utilities
- **separator.tsx**: Séparateurs visuels
- **scroll-area.tsx**: Zones défilement
- **resizable.tsx**: Panneaux redimensionnables
- **aspect-ratio.tsx**: Ratios d'aspect
- **collapsible.tsx**: Éléments pliables
- **toggle.tsx**: Boutons bascule
- **toggle-group.tsx**: Groupes bascule

#### Specialized
- **chart.tsx**: Composants graphiques
- **carousel.tsx**: Carrousels images

## HOOKS PERSONNALISÉS (5 hooks)

### useAuth (useAuth.ts)
- **Fonctionnalités**: Gestion authentification utilisateur
- **État**: user, isLoading, isAuthenticated, establishment
- **Méthodes**: login, logout, register, refreshUser
- **Imports**: useQuery, queryClient, authUtils

### useCollaboration (useCollaboration.ts)
- **Fonctionnalités**: Collaboration temps réel WebSocket
- **État**: connection, rooms, participants, messages
- **Méthodes**: joinRoom, leaveRoom, sendMessage, handleCursor
- **Imports**: WebSocket utilities, message types

### useTheme (useTheme.ts)
- **Fonctionnalités**: Gestion thèmes dark/light
- **État**: theme, isDark, isLight
- **Méthodes**: setTheme, toggleTheme, setDark, setLight
- **Imports**: localStorage, CSS variables

### useToast (use-toast.ts)
- **Fonctionnalités**: Système notifications toast
- **État**: toasts array, toast methods
- **Méthodes**: toast, dismiss, update
- **Imports**: Toast reducer, timer utilities

### useMobile (use-mobile.tsx)
- **Fonctionnalités**: Détection écrans mobiles
- **État**: isMobile boolean
- **Méthodes**: Media query responsive
- **Imports**: useEffect, useState, media queries

## LIBRAIRIES ET UTILITAIRES

### Lib (lib/)

#### queryClient.ts
- **Fonctionnalités**: Configuration TanStack Query
- **Exports**: queryClient, apiRequest helper
- **Configuration**: Cache policies, error handling, retries

#### authUtils.ts
- **Fonctionnalités**: Utilitaires authentification
- **Exports**: Token management, role checking, permissions
- **Méthodes**: hasRole, hasPermission, getToken

#### utils.ts
- **Fonctionnalités**: Utilitaires généraux
- **Exports**: cn (className utility), formatters, validators
- **Imports**: clsx, tailwind-merge

## ROUTING ET NAVIGATION

### App.tsx - Configuration routage
```typescript
Routes configurées (wouter):
- "/"              → Home
- "/portal"        → Portal  
- "/establishment/:slug" → Establishment
- "/login"         → Login
- "/dashboard"     → Dashboard
- "/admin"         → AdminPage
- "/super-admin"   → SuperAdminPage
- "/user-management" → UserManagement
- "/courses"       → CoursesPage
- "/assessments"   → AssessmentsPage
- "/manual"        → UserManualPage
- "/archive"       → ArchiveExportPage
- "/system-updates" → SystemUpdatesPage
- "/wysiwyg-editor" → WysiwygEditorPage
- "/study-groups"  → StudyGroupsPage
- "/analytics"     → AnalyticsPage
- "/help-center"   → HelpCenterPage
- default          → NotFound
```

### Providers Configuration
- **QueryClientProvider**: TanStack Query setup
- **TooltipProvider**: Tooltips globaux
- **Toaster**: Notifications système

## THÈMES ET STYLING

### index.css - Thème Glassmorphism
```css
Variables principales:
- --glass-bg: rgba(255, 255, 255, 0.1)
- --glass-border: rgba(255, 255, 255, 0.2)  
- --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37)
- --blur-strength: 10px
- --border-radius: 1rem

Couleurs système:
- --color-primary: 139 92 246 (#8B5CF6)
- --color-secondary: 167 139 250 (#A78BFA)
- --color-accent: 196 181 253 (#C4B5FD)

Classes utilitaires:
- .glassmorphism: Effet verre principal
- .glass-nav: Navigation glassmorphism
- .glass-mobile-menu: Menu mobile glassmorphism

Thèmes disponibles:
- .theme-purple: Thème violet (défaut)
- .theme-blue: Thème bleu
- .theme-green: Thème vert

Support dark mode:
- Variables CSS adaptatives
- Classes .dark avec couleurs alternatives
```

### Tailwind Configuration
- **Colors**: Variables CSS personnalisées
- **Fonts**: Inter font family
- **Animations**: Tailwind CSS animate
- **Plugins**: Typography, forms, etc.

## GESTION D'ÉTAT ET DATA

### TanStack Query Configuration
- **Cache**: 5 minutes par défaut
- **Retries**: 3 tentatives
- **Background refetch**: Activé
- **Error boundaries**: Configurées

### Query Keys Pattern
```typescript
Exemples:
- ['/api/courses'] - Liste cours
- ['/api/courses', courseId] - Cours spécifique
- ['/api/users'] - Liste utilisateurs
- ['/api/analytics', dateRange] - Analytics avec paramètres
- ['/api/establishments'] - Établissements
```

### API Integration
- **Base URL**: `/api` (proxy Vite)
- **Authentication**: Cookies HTTP-only
- **Error handling**: Global error boundaries
- **Loading states**: Par query
- **Optimistic updates**: Sur mutations

## FORMULAIRES ET VALIDATION

### React Hook Form Integration
- **Resolver**: Zod validation
- **Error handling**: Field-level errors
- **Submission**: apiRequest helper
- **Reset**: Form reset after success

### Zod Schemas
- **Shared schemas**: Import from shared/schema.ts
- **Frontend extensions**: .extend() pour validation UI
- **Error messages**: Français localisé

## RESPONSIVE DESIGN

### Breakpoints Tailwind
- **sm**: 640px - Small tablets
- **md**: 768px - Tablets  
- **lg**: 1024px - Small laptops
- **xl**: 1280px - Large screens

### Mobile-First Approach
- **Base**: Mobile layout par défaut
- **Progressive enhancement**: Ajout fonctionnalités desktop
- **Touch-friendly**: Boutons taille minimum 44px
- **Navigation**: Menu hamburger mobile avec glassmorphism

## ICÔNES ET ASSETS

### Lucide React Icons (100+ icons utilisés)
```typescript
Navigation: Menu, X, Home, Settings
Actions: Plus, Trash2, Save, RefreshCw  
Content: BookOpen, FileText, Image, Video
Users: User, Users, Shield, Award
Interface: Calendar, Clock, Search, Star
Status: TrendingUp, AlertCircle, CheckCircle
```

### React Icons (SI)
- **Company logos**: GitHub, Google, Microsoft, etc.
- **Social media**: Twitter, LinkedIn, Facebook

## PERFORMANCE ET OPTIMISATION

### Code Splitting
- **Route-based**: Pages chargées à la demande
- **Component-based**: Composants lourds lazy loadés
- **Dynamic imports**: Fonctionnalités optionnelles

### Memoization
- **useMemo**: Calculs coûteux
- **useCallback**: Fonctions dans deps
- **React.memo**: Composants purs

### Bundle Optimization
- **Tree shaking**: Imports spécifiques
- **Chunk splitting**: Vendors séparés
- **Asset optimization**: Images optimisées

## ACCESSIBILITÉ

### ARIA Support
- **Labels**: aria-label sur composants interactifs
- **Descriptions**: aria-describedby pour contexte
- **States**: aria-expanded, aria-selected
- **Roles**: button, dialog, menu, etc.

### Keyboard Navigation
- **Tab order**: Navigation logique
- **Escape**: Fermeture modals
- **Enter/Space**: Activation boutons
- **Arrow keys**: Navigation listes

### Screen Reader Support
- **Semantic HTML**: Utilisation balises appropriées
- **Headings**: Hiérarchie h1-h6
- **Alt text**: Images descriptives
- **Form labels**: Association explicite

## TESTS ET DEBUGGING

### Data Test IDs
```typescript
Pattern: {action}-{target}
Exemples:
- "button-submit"
- "input-email"  
- "link-profile"
- "card-course-123"
- "row-user-456"
```

### Error Boundaries
- **Global**: Capture erreurs React
- **Query errors**: TanStack Query error handling
- **Toast notifications**: Retour utilisateur
- **Console logging**: Debug développement

## INTERNATIONALISATION

### Préparation i18n
- **Strings**: Externalisables
- **Date formats**: date-fns configuré
- **Number formats**: Localisé français
- **RTL support**: CSS préparé

Cette documentation constitue l'inventaire exhaustif du frontend, couvrant tous les composants, pages, hooks, utilitaires et configurations de l'application StacGateLMS.
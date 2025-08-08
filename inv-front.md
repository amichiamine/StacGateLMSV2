# INVENTAIRE EXHAUSTIF - FRONTEND REACT (StacGateLMS)
*Analyse complète de l'architecture, composants, fonctionnalités et structures React*

## 📁 STRUCTURE GÉNÉRALE DU PROJET FRONTEND

### Dossier racine `/client`
```
client/
├── index.html                 # Point d'entrée HTML principal
├── src/                      # Code source React TypeScript
│   ├── App.tsx              # Composant racine et router principal
│   ├── main.tsx             # Point d'entrée React avec gestion d'erreurs
│   ├── index.css            # Styles globaux et variables CSS
│   ├── components/          # Composants réutilisables
│   ├── pages/               # Pages/vues de l'application
│   ├── hooks/               # Hooks personnalisés React
│   ├── lib/                 # Utilitaires et configurations
│   └── test/                # Tests unitaires
```

## 🎯 ARCHITECTURE REACT

### Configuration de l'Application (App.tsx)
**Composant principal** : `App()`
- **Routeur** : Utilise `wouter` pour la navigation SPA
- **Providers globaux** :
  - `QueryClientProvider` (TanStack Query)
  - `TooltipProvider` (Radix UI)
  - `Toaster` (système de notifications)

### Routes configurées dans le Switch :
1. `/` → Home (redirection intelligente)
2. `/portal` → Portal (page d'accueil publique)
3. `/establishment/:slug` → Establishment (pages d'établissements)
4. `/login` → Login (authentification)
5. `/dashboard` → Dashboard (tableau de bord utilisateur)
6. `/admin` → AdminPage (interface admin)
7. `/super-admin` → SuperAdminPage (interface super admin)
8. `/user-management` → UserManagement (gestion utilisateurs)
9. `/courses` → CoursesPage (gestion des cours)
10. `/assessments` → AssessmentsPage (évaluations)
11. `/manual` → UserManualPage (manuel utilisateur)
12. `/archive` → ArchiveExportPage (archives et exports)
13. `/system-updates` → SystemUpdatesPage (mises à jour système)
14. `/wysiwyg-editor` → WysiwygEditorPage (éditeur visuel)
15. `/study-groups` → StudyGroupsPage (groupes d'étude)
16. `/analytics` → AnalyticsPage (analytics et rapports)
17. `/help-center` → HelpCenterPage (centre d'aide)
18. `/*` → NotFound (page 404)

### Point d'entrée (main.tsx)
- **Rendu React** : `createRoot()` API
- **Gestion d'erreurs globale** :
  - Capture des `unhandledrejection`
  - Filtrage automatique des erreurs API (401, 404, 500)
  - Prévention du spam d'erreurs dans la console

## 📄 PAGES PRINCIPALES

### 1. Home (`/src/pages/home.tsx`)
**Fonctionnalité** : Page de redirection intelligente
- **Hooks utilisés** : `useAuth`, `useQuery`, `useEffect`
- **Logique** :
  - Vérifie l'état d'authentification
  - Redirige vers `/dashboard` si connecté
  - Redirige vers `/portal` si non connecté
- **Interface** : Loading spinner pendant la redirection

### 2. Portal (`/src/pages/portal.tsx`)
**Fonctionnalité** : Page d'accueil publique et vitrine des établissements
- **State management** :
  - `searchTerm` (recherche d'établissements)
  - `selectedCategory` (filtrage par catégorie)
  - `isMobileMenuOpen` (menu mobile)
- **Composants intégrés** : `Navigation`
- **Fonctionnalités** :
  - Recherche en temps réel d'établissements
  - Filtrage par catégories
  - Navigation responsive avec menu mobile
  - Gestion des erreurs réseau robuste
  - Raccourcis clavier (Ctrl+K pour recherche)

### 3. Login (`/src/pages/login.tsx`)
**Fonctionnalité** : Authentification et inscription
- **Interface** : Système de tabs (Connexion/Inscription)
- **State management** :
  - Formulaires de connexion et inscription séparés
  - Gestion des erreurs de validation
  - Loading states
- **Intégration API** :
  - `/api/auth/login` (POST)
  - `/api/auth/register` (POST)
  - `/api/establishments` (GET - pour sélection établissement)
- **Validations** :
  - Email/mot de passe requis
  - Sélection d'établissement obligatoire pour inscription

### 4. Dashboard (`/src/pages/dashboard.tsx`)
**Fonctionnalité** : Tableau de bord principal utilisateur
- **Composants** : `Navigation` intégré
- **Données affichées** :
  - Informations utilisateur personnalisées
  - Statistiques de cours
  - Liens rapides vers fonctionnalités
- **Sécurité** : Redirection automatique si non authentifié
- **Responsive design** : Adaptatif mobile/desktop

### 5. Admin (`/src/pages/admin.tsx`)
**Fonctionnalité** : Interface d'administration complète
- **Sections (Tabs)** :
  - Gestion des établissements
  - Gestion des utilisateurs
  - Gestion des cours
  - Personnalisation des thèmes
  - Gestion du contenu
  - Configuration des menus
- **Intégrations** : `PageEditor` (WYSIWYG)
- **Permissions** : Accès restreint aux administrateurs

### 6. Super Admin (`/src/pages/super-admin.tsx`)
**Fonctionnalité** : Interface super-administrateur
- **Composants intégrés** : `PortalCustomization`
- **Fonctionnalités** :
  - Vue globale de tous les établissements
  - Gestion des administrateurs globaux
  - Personnalisation du portail principal
- **Sécurité** : Permissions super_admin strictes

### 7. Courses (`/src/pages/courses.tsx`)
**Fonctionnalité** : Gestion complète des cours
- **Interface** :
  - Liste des cours avec filtres
  - Modal de création de cours
  - Système de recherche avancée
- **Features** :
  - Création/édition de cours
  - Filtrage par catégorie/niveau
  - Upload d'images et vidéos
  - Gestion des prix et accès
- **Intégration API** : CRUD complet des cours

### 8. Analytics (`/src/pages/analytics.tsx`)
**Fonctionnalité** : Tableaux de bord et statistiques
- **Métriques affichées** :
  - Nombre total d'utilisateurs
  - Cours actifs
  - Inscriptions totales
  - Taux de complétion
- **Features** :
  - Export de données
  - Actualisation en temps réel
  - Cours populaires
- **Visualisations** : Cartes de statistiques animées

### 9. Establishment (`/src/pages/establishment.tsx`)
**Fonctionnalité** : Pages dédiées par établissement
- **Paramètres dynamiques** : `slug` d'établissement
- **Customisation** :
  - Header personnalisé avec logo
  - Contenu spécifique à l'établissement
  - Thèmes visuels adaptés
- **Navigation** : Liens contextuels selon l'état d'authentification

## 🧩 COMPOSANTS PERSONNALISÉS

### Navigation (`/src/components/navigation.tsx`)
**Type** : Composant de navigation global
- **Features** :
  - Navigation responsive avec glassmorphism
  - Menu burger animé pour mobile
  - Links vers pages principales
  - Overlay de menu mobile avec backdrop blur
- **États** : `isMobileMenuOpen`
- **Styling** : Design glassmorphism avec effets de transparence

### CollaborationIndicator (`/src/components/CollaborationIndicator.tsx`)
**Type** : Widget de collaboration temps réel
- **Props** :
  - `roomId`, `roomType`, `resourceId`
  - `showParticipants`, `className`
- **Features** :
  - Connexion WebSocket automatique
  - Affichage des participants en temps réel
  - Notifications de join/leave
  - Gestion d'erreurs robuste
- **Hooks utilisés** : `useCollaboration`, `useToast`

### PortalCustomization (`/src/components/PortalCustomization.tsx`)
**Type** : Interface de personnalisation du portail
- **Sections** :
  - Gestion des thèmes visuels
  - Édition de contenu personnalisable
  - Configuration des menus
  - Intégration WYSIWYG
- **Fonctionnalités** :
  - Création/modification de thèmes
  - Éditeur de couleurs intégré
  - Gestion des polices et tailles
  - Prévisualisation en temps réel

### Hero Section (`/src/components/hero-section.tsx`)
**Type** : Section d'accueil marketing
- **Elements** :
  - Titre principal avec dégradé
  - Call-to-action buttons
  - Effets visuels (blur, gradients)
  - Card de présentation avec rotation
- **Intégrations** : Icons Lucide React

### Features Section (`/src/components/features-section.tsx`)
**Type** : Présentation des fonctionnalités
- **Structure** : Grid responsive de features
- **Data** : Array de features avec icônes et descriptions
- **Styling** : Cartes avec dégradés et hover effects

## 🎨 SYSTÈME WYSIWYG

### PageEditor (`/src/components/wysiwyg/PageEditor.tsx`)
**Type** : Éditeur de pages visuel complet
- **Fonctionnalités principales** :
  - Édition drag-and-drop de composants
  - Prévisualisation en temps réel
  - Sauvegarde automatique
  - Gestion des sections (header, body, footer)
- **State management** :
  - `selectedSection`, `selectedComponent`
  - `editMode`, `previewMode`
- **Intégrations** :
  - `ComponentLibrary` (bibliothèque de composants)
  - `ComponentEditor` (éditeur de propriétés)
  - `PagePreview` (prévisualisation)

### ComponentLibrary (`/src/components/wysiwyg/ComponentLibrary.tsx`)
**Type** : Bibliothèque de composants prédéfinis
- **Catégories** :
  - Mise en page (Hero, Features, Stats, Testimonials)
  - Navigation (Menu, Breadcrumb, Footer)
  - Contenu (Text, Image, Video, Article)
  - E-learning (Course Grid, Progress, Certificates)
  - Business (Pricing, Contact, Team)
- **Interface** : Grille de sélection avec icônes Lucide

### ComponentEditor (`/src/components/wysiwyg/ComponentEditor.tsx`)
**Type** : Éditeur de propriétés de composants
- **Fonctionnalités** :
  - Édition de textes et descriptions
  - Upload d'images avec prévisualisation
  - Sélecteur de couleurs intégré
  - Gestion des arrays (features, témoignages, etc.)
  - Configuration des boutons et liens
- **Composants** : Formulaires dynamiques selon le type

### PagePreview (`/src/components/wysiwyg/PagePreview.tsx`)
**Type** : Rendu temps réel des pages
- **Fonctionnalités** :
  - Rendu complet des composants
  - Styles appliqués en temps réel
  - Support des images et vidéos
  - Preview responsive
- **Composants supportés** : Hero, Features, Stats, Text, Image, Video

## 🔧 HOOKS PERSONNALISÉS

### useAuth (`/src/hooks/useAuth.ts`)
**Type** : Gestion de l'authentification
- **Return** :
  - `user` (données utilisateur)
  - `isLoading` (état de chargement)
  - `isAuthenticated` (booléen d'auth)
- **Intégration** : TanStack Query avec endpoint `/api/auth/user`

### useCollaboration (`/src/hooks/useCollaboration.ts`)
**Type** : Gestion WebSocket et collaboration
- **Props** :
  - `autoConnect`, `onMessage`, `onUserJoined`, `onUserLeft`, `onError`
- **Features** :
  - Connexion WebSocket automatique
  - Gestion des rooms de collaboration
  - Reconnexion automatique
  - Gestion des participants
- **État** :
  - `isConnected`, `isConnecting`, `currentRoom`, `participants`, `error`

### useToast (`/src/hooks/use-toast.ts`)
**Type** : Système de notifications
- **Features** :
  - Gestion d'une queue de toasts
  - Auto-dismiss programmable
  - Types de toast (success, error, warning)
  - Limitation du nombre de toasts

## 📚 BIBLIOTHÈQUE UI (shadcn/ui)

### Composants disponibles dans `/src/components/ui/` :
1. **Layout** : Card, Separator, Accordion, Collapsible
2. **Forms** : Button, Input, Textarea, Label, Form, Checkbox, Switch, Radio Group, Select
3. **Navigation** : Tabs, Breadcrumb, Menubar, Navigation Menu, Pagination
4. **Feedback** : Alert, Toast, Progress, Skeleton
5. **Overlay** : Dialog, Sheet, Popover, Tooltip, Hover Card, Context Menu, Dropdown Menu
6. **Data Display** : Badge, Avatar, Table, Calendar, Chart
7. **Media** : Aspect Ratio, Carousel
8. **Input** : Input OTP, Slider, Command (search)
9. **Layout** : Resizable, Scroll Area, Sidebar

### Configuration et Styling :
- **Base** : Tailwind CSS avec variables CSS personnalisées
- **Variants** : System cva (class-variance-authority)
- **Theming** : Support dark/light mode
- **Responsive** : Mobile-first design

## 🎨 STYLES ET THEMING

### Fichier principal : `index.css`
- **Import** : Font Inter de Google Fonts
- **Variables CSS** :
  - Glassmorphism (`--glass-bg`, `--glass-border`, `--glass-shadow`)
  - Couleurs dynamiques (Primary, Secondary, Accent)
  - Thèmes prédéfinis (Purple, Blue, Green)
- **Classes utilitaires** :
  - `.glassmorphism` (effet verre)
  - `.glass-nav` (navigation transparente)
  - `.glass-mobile-menu` (menu mobile)

### Système de couleurs :
- **Primary** : Purple (#8B5CF6)
- **Secondary** : Light Purple (#A78BFA)
- **Accent** : Pale Purple (#C4B5FD)
- **Success** : Green (#22C55E)
- **Warning** : Orange (#F59E0B)
- **Danger** : Red (#EF4444)

## 🔄 GESTION DES DONNÉES

### TanStack Query (`/src/lib/queryClient.ts`)
**Configuration** :
- **Retry strategy** : Pas de retry sur 4xx, 2 tentatives max sur 5xx
- **Stale time** : 5 minutes
- **Error handling** : Gestion spécifique des erreurs 401/403
- **API requests** : Fonction `apiRequest()` avec credentials

### Patterns de données :
- **Queries** : Récupération de données avec cache automatique
- **Mutations** : Modifications avec invalidation de cache
- **Error states** : Gestion des erreurs réseau et API
- **Loading states** : Skeletons et spinners

## 📱 FONCTIONNALITÉS PRINCIPALES

### 1. **Système d'authentification**
- Login/Register avec validation
- Session management
- Redirections intelligentes
- Protection des routes

### 2. **Multi-tenant (établissements)**
- Pages dédiées par établissement
- Personnalisation visuelle
- Gestion des domaines/slugs
- Contenu spécifique

### 3. **Gestion des cours**
- CRUD complet
- Filtrage et recherche
- Upload de médias
- Catégorisation

### 4. **Interface d'administration**
- Gestion des utilisateurs
- Configuration des thèmes
- Personnalisation du contenu
- Analytics et rapports

### 5. **Éditeur WYSIWYG**
- Création de pages visuelles
- Bibliothèque de composants
- Prévisualisation temps réel
- Sauvegarde automatique

### 6. **Collaboration temps réel**
- WebSockets intégrés
- Rooms de collaboration
- Notifications en temps réel
- Gestion des participants

### 7. **Analytics et rapports**
- Tableaux de bord
- Métriques en temps réel
- Export de données
- Visualisations

## 🧪 TESTS ET QUALITÉ

### Structure de tests (`/src/test/`, `/src/__tests__/`) :
- Tests unitaires avec Vitest
- Tests des hooks (`useAuth.test.tsx`)
- Tests des pages (`dashboard.test.tsx`, `home.test.tsx`)
- Mocking des API calls
- Test d'accessibilité avec `data-testid`

### Attributs de test intégrés :
- `data-testid` sur tous les éléments interactifs
- Patterns de naming cohérents
- Identifiants uniques pour les éléments dynamiques

## 🔧 CONFIGURATION ET BUILD

### Outils de développement :
- **Vite** : Build tool et dev server
- **TypeScript** : Typage statique
- **ESLint** : Linting du code
- **Prettier** : Formatage automatique
- **Tailwind CSS** : Framework CSS

### Alias de chemins configurés :
- `@/` → `client/src/`
- `@shared/` → `shared/`
- `@assets/` → `attached_assets/`

## 📊 MÉTRIQUES DE L'APPLICATION

### Statistiques générales :
- **Pages principales** : 18 routes configurées
- **Composants UI** : 45+ composants shadcn/ui
- **Composants custom** : 15+ composants métier
- **Hooks personnalisés** : 6 hooks principaux
- **Système WYSIWYG** : 25+ types de composants
- **Tests unitaires** : 14+ tests écrits

### Technologies React utilisées :
- **React 18** avec Hooks et Context
- **TypeScript** pour le typage
- **Wouter** pour le routing
- **TanStack Query** pour la gestion des données
- **Radix UI** comme base des composants
- **Lucide React** pour les icônes
- **WebSockets** pour le temps réel

## 🚀 POINTS FORTS DE L'ARCHITECTURE

1. **Modularité** : Composants réutilisables et bien séparés
2. **Type Safety** : TypeScript intégral avec interfaces claires
3. **Performance** : Lazy loading, memoization, cache intelligent
4. **UX/UI** : Design system cohérent avec glassmorphism
5. **Responsive** : Adaptable mobile/tablet/desktop
6. **Accessibility** : Attributs ARIA et navigation clavier
7. **Real-time** : WebSockets intégrés pour la collaboration
8. **Testing** : Coverage des composants critiques
9. **Scalabilité** : Architecture modulaire extensible
10. **Developer Experience** : Outils modernes et workflow optimisé
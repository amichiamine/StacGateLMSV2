# 📋 INVENTAIRE COMPLET FRONTEND - IntraSphere LMS
*Analyse exhaustive effectuée le 07/08/2025*

## 🏗️ ARCHITECTURE GÉNÉRALE

### Structure des Dossiers
```
📁 client/src/ (Legacy - Structure actuelle)
├── 📁 components/        # Composants UI et métier
├── 📁 pages/            # 18 Pages principales
├── 📁 hooks/            # 4 Hooks personnalisés
├── 📁 lib/              # 3 Utilitaires
├── App.tsx              # Router principal
├── main.tsx             # Point d'entrée
└── index.css            # Styles globaux

📁 frontend/src/ (Nouvelle structure IntraSphere)
├── 📁 features/         # Pages organisées par domaines
├── 📁 components/       # Composants hiérarchisés
├── 📁 core/             # Hooks et utilitaires
├── App.tsx              # Router moderne
├── main.tsx             # Point d'entrée
└── index.css            # Styles globaux
```

## 📄 PAGES ET VUES (18 au total)

### 🔐 Domaine AUTH (1 page)
1. **Login** (`/login`)
   - Fonctionnalités: Connexion locale + formulaire
   - Composants: Form, Input, Button
   - Redirections: Dashboard après connexion
   - État: Session management + validation

### 🏠 Pages Principales (6 pages)
2. **Home** (`/`)
   - Fonctionnalités: Redirection intelligente auth/guest
   - Logique: useEffect pour routing conditionnel
   - Animation: Loading spinner
   - Redirections: → `/dashboard` (auth) ou `/portal` (guest)

3. **Landing** (`/`)
   - Sections: Hero, Features, Courses populaires, Footer
   - Composants: HeroSection, FeaturesSection, PopularCoursesSection
   - CTA: "Commencer maintenant" → `/login`
   - Design: Gradients, animations, responsive

4. **Portal** (`/portal`)
   - Fonctionnalités: Découverte établissements publics
   - Composants: Search, Filter, EstablishmentCard
   - API: `GET /api/establishments`
   - États: Loading, Error handling, Empty states
   - Features: Search real-time, filtres catégories

5. **Dashboard** (`/dashboard`)
   - Fonctionnalités: Tableau de bord personnalisé
   - Sections: Stats, Cours récents, Progression
   - Composants: Cards statistiques, Graphiques, Actions rapides
   - Permissions: Authentification requise
   - Navigation: Menu contextuel par rôle

6. **Establishment** (`/establishment/:slug`)
   - Fonctionnalités: Page dédiée par établissement
   - API: `GET /api/establishments/slug/:slug`
   - Contenu: Branding personnalisé, Cours, Inscription
   - Design: Thème personnalisable

7. **Not Found** (`/404`)
   - Fonctionnalités: Page d'erreur 404
   - Design: Illustration + navigation retour

### 👨‍💼 Domaine ADMIN (4 pages)
8. **Admin** (`/admin`)
   - Fonctionnalités: Interface administration complète
   - Sections: Gestion thèmes, contenus, menus, établissements
   - Onglets: Thèmes, Contenus, Menus, Établissements, Utilisateurs
   - Permissions: Admin/Super Admin uniquement
   - Features: CRUD complet pour chaque section

9. **Super Admin** (`/super-admin`)
   - Fonctionnalités: Gestion plateforme globale
   - Sections: Gestion établissements, Utilisateurs globaux
   - Permissions: Super Admin uniquement
   - Features: Création/Suppression établissements

10. **User Management** (`/user-management`)
    - Fonctionnalités: CRUD utilisateurs
    - Sections: Liste, Création, Modification, Permissions
    - Filtres: Par rôle, établissement, statut
    - Bulk actions: Activer/Désactiver, Export

11. **System Updates** (`/system-updates`)
    - Fonctionnalités: Gestion mises à jour système
    - Sections: Historique, Planification, Notes de version
    - Features: Backup, Rollback, Notifications

### 📚 Domaine TRAINING (4 pages)
12. **Courses** (`/courses`)
    - Fonctionnalités: Catalogue complet des cours
    - Sections: Liste, Filtres, Création, Détails
    - Features: Search, Filtres avancés, Preview
    - Modal: Création/Édition cours
    - API: `GET/POST/PUT/DELETE /api/courses`

13. **Assessments** (`/assessments`)
    - Fonctionnalités: Gestion évaluations/examens
    - Sections: Questions, QCM, Corrections automatiques
    - Features: Éditeur de questions, Timer, Résultats
    - Types: QCM, Vrai/Faux, Réponses courtes

14. **Study Groups** (`/study-groups`)
    - Fonctionnalités: Groupes d'étude collaboratifs
    - Features: Chat temps réel, Whiteboard partagé
    - WebSocket: Messages instantanés
    - Gestion: Création, Invitation, Modération

15. **User Manual** (`/user-manual`)
    - Fonctionnalités: Documentation utilisateur
    - Sections: Guides, Tutoriels, FAQ
    - Navigation: Sidebar, Recherche, Tags
    - Format: Markdown + Vidéos intégrées

### 📝 Domaine CONTENT (2 pages)
16. **WYSIWYG Editor** (`/wysiwyg-editor`)
    - Fonctionnalités: Éditeur visuel pages personnalisables
    - Features: Drag & Drop, Composants pré-faits
    - Sections: ComponentLibrary, PageEditor, Preview
    - Sauvegarde: Auto-save, Versions, Publication

17. **Archive Export** (`/archive-export`)
    - Fonctionnalités: Export données/contenus
    - Formats: PDF, Excel, ZIP
    - Filtres: Date, Type, Établissement
    - Features: Progression, Téléchargement, Historique

### 🔧 Pages Legacy
18. **Portal Old** (`/portal-old`)
    - Fonctionnalités: Ancienne version du portail
    - Statut: À migrer vers nouvelle structure

## 🧩 COMPOSANTS UI (47+ composants Shadcn/UI)

### Composants de Base (15)
- **Button** - Variants: default, destructive, outline, secondary, ghost, link
- **Card** - CardHeader, CardContent, CardFooter, CardTitle, CardDescription
- **Input** - Text, Email, Password, Number, Search
- **Label** - Form labels avec association
- **Badge** - Status indicators, Tags
- **Avatar** - Profile pictures avec fallback
- **Skeleton** - Loading placeholders
- **Separator** - Dividers visuels
- **Progress** - Barres de progression
- **Switch** - Toggle on/off
- **Checkbox** - Sélection multiple
- **Radio Group** - Sélection exclusive
- **Slider** - Input numérique à glissement
- **Toggle** - Bouton état activé/désactivé
- **Tooltip** - Info-bulles au hover

### Composants de Navigation (8)
- **Tabs** - TabsList, TabsContent, TabsTrigger
- **Navigation Menu** - Menu principal avec sous-menus
- **Breadcrumb** - Fil d'Ariane navigation
- **Pagination** - Navigation pages multiples
- **Sidebar** - Navigation latérale collapsible
- **Menubar** - Barre de menu horizontal
- **Context Menu** - Menu clic droit
- **Command** - Palette de commandes (Ctrl+K)

### Composants de Formulaire (8)
- **Form** - Wrapper React Hook Form
- **Textarea** - Texte multi-lignes
- **Select** - Dropdown avec options multiples
- **Calendar** - Sélecteur de dates
- **Input OTP** - Code de vérification
- **Combobox** - Autocomplete + création
- **Date Picker** - Sélection date/heure
- **File Input** - Upload de fichiers

### Composants de Mise en Page (10)
- **Dialog** - Modales avec overlay
- **Sheet** - Panneau latéral coulissant
- **Popover** - Contenu flottant positionné
- **Hover Card** - Cartes d'info au hover
- **Dropdown Menu** - Menus déroulants
- **Alert Dialog** - Confirmations destructives
- **Drawer** - Tiroir mobile-friendly
- **Accordion** - Sections repliables
- **Collapsible** - Contenu masquable
- **Resizable** - Panneaux redimensionnables

### Composants Avancés (6)
- **Table** - Tableaux avec tri/filtres
- **Chart** - Graphiques avec Recharts
- **Carousel** - Galeries d'images
- **Scroll Area** - Zone de scroll personnalisée
- **Aspect Ratio** - Ratios responsives
- **Alert** - Messages système

## 🔧 COMPOSANTS MÉTIER (12 composants)

### Marketing/Landing (6)
1. **HeroSection** - Section principale avec CTA
2. **FeaturesSection** - Présentation fonctionnalités (3 features)
3. **PopularCoursesSection** - Cours populaires (3 cours)
4. **Navigation** - Menu principal avec branding
5. **Footer** - Pied de page avec liens
6. **PortalCustomization** - Personnalisation portails

### WYSIWYG/Éditeur (5)
7. **PageEditor** - Interface d'édition visuelle
8. **ComponentLibrary** - Bibliothèque composants
9. **ComponentEditor** - Éditeur propriétés composant
10. **PagePreview** - Prévisualisation temps réel
11. **ColorPicker** - Sélecteur couleurs avancé

### Dashboard (1)
12. **Dashboard Components** - Widgets tableau de bord

## 🪝 HOOKS PERSONNALISÉS (4)

1. **useAuth** (`hooks/useAuth.ts`)
   - Fonctionnalités: Gestion authentification
   - États: user, isLoading, isAuthenticated
   - Méthodes: login, logout, refresh
   - API: `GET /api/auth/user`

2. **useTheme** (`hooks/useTheme.ts`)
   - Fonctionnalités: Gestion thème sombre/clair
   - États: theme (light/dark/system)
   - Méthodes: setTheme, toggleTheme
   - Persistance: LocalStorage

3. **useToast** (`hooks/use-toast.ts`)
   - Fonctionnalités: Notifications utilisateur
   - Types: Success, Error, Warning, Info
   - Méthodes: toast(), dismiss()
   - UI: Toaster component

4. **useMobile** (`hooks/use-mobile.tsx`)
   - Fonctionnalités: Détection appareil mobile
   - Breakpoint: < 768px
   - Hook: useState + useEffect + matchMedia

## 🛠️ UTILITAIRES (3 fichiers)

1. **queryClient.ts** (`lib/queryClient.ts`)
   - Fonctionnalités: Configuration TanStack Query
   - Configs: Cache, Retry, Stale time
   - Méthodes: apiRequest pour fetch standardisé

2. **utils.ts** (`lib/utils.ts`)
   - Fonctionnalités: Utilitaires généraux
   - Méthodes: cn (className merger), formatters
   - Dependencies: clsx, tailwind-merge

3. **authUtils.ts** (`lib/authUtils.ts`)
   - Fonctionnalités: Helpers authentification
   - Méthodes: Validation tokens, Permissions
   - Sécurité: Headers, CSRF protection

## 🎨 STYLING ET THÈMES

### Système de Design
- **Framework**: Tailwind CSS + CSS Variables
- **Components**: Shadcn/UI avec variants
- **Thème**: Dark/Light mode avec système préférences
- **Couleurs**: Palette personnalisable par établissement
- **Typographie**: Inter font system avec tailles responsives
- **Animations**: Framer Motion + CSS transitions

### Variables CSS (index.css)
```css
:root {
  --primary: hsl(222, 84%, 65%);
  --secondary: hsl(210, 40%, 95%);
  --accent: hsl(210, 40%, 95%);
  --destructive: hsl(0, 84%, 60%);
  --border: hsl(214, 32%, 91%);
  --input: hsl(214, 32%, 91%);
  --ring: hsl(222, 84%, 65%);
  /* ... plus de variables */
}
```

## 🗂️ NAVIGATION ET ROUTING

### Routes Principales (13 routes)
```typescript
<Route path="/" component={Home} />
<Route path="/portal" component={Portal} />
<Route path="/establishment/:slug" component={Establishment} />
<Route path="/login" component={Login} />
<Route path="/dashboard" component={Dashboard} />
<Route path="/admin" component={AdminPage} />
<Route path="/super-admin" component={SuperAdminPage} />
<Route path="/user-management" component={UserManagement} />
<Route path="/courses" component={CoursesPage} />
<Route path="/assessments" component={AssessmentsPage} />
<Route path="/manual" component={UserManualPage} />
<Route path="/archive" component={ArchiveExportPage} />
<Route path="/system-updates" component={SystemUpdatesPage} />
<Route path="/wysiwyg-editor" component={WysiwygEditorPage} />
<Route path="/study-groups" component={StudyGroupsPage} />
<Route component={NotFound} />
```

### Navigation Contextuelle
- **Menu principal**: Adapté selon rôle utilisateur
- **Breadcrumbs**: Fil d'Ariane automatique
- **Sidebar**: Navigation rapide par section
- **Mobile**: Hamburger menu responsive

## 📊 GESTION D'ÉTAT

### TanStack Query (React Query v5)
- **Cache**: Gestion automatique avec invalidation
- **Mutations**: POST/PUT/DELETE avec optimistic updates
- **Background**: Refetch automatique
- **Offline**: Support mode hors ligne

### Queries Principales
```typescript
// Authentification
useQuery({ queryKey: ['/api/auth/user'] })

// Établissements  
useQuery({ queryKey: ['/api/establishments'] })

// Cours
useQuery({ queryKey: ['/api/courses'] })

// Utilisateurs (admin only)
useQuery({ queryKey: ['/api/users'] })

// Thèmes/Personnalisation
useQuery({ queryKey: ['/api/admin/themes'] })
useQuery({ queryKey: ['/api/admin/customizable-contents'] })
```

### État Local (useState)
- Formulaires avec React Hook Form
- UI states (modals, loading, selections)
- Filtres et recherches temps réel
- Préférences utilisateur

## 🔌 INTÉGRATIONS API

### Endpoints Consommés
1. **Auth**: `/api/auth/*` - Authentification
2. **Establishments**: `/api/establishments/*` - Établissements
3. **Courses**: `/api/courses/*` - Gestion cours
4. **Users**: `/api/users/*` - Gestion utilisateurs
5. **Admin**: `/api/admin/*` - Administration
6. **Export**: `/api/export/*` - Exports données

### Méthodes HTTP
- **GET**: Récupération données
- **POST**: Création ressources  
- **PUT**: Mise à jour complète
- **DELETE**: Suppression ressources

## 📱 RESPONSIVE DESIGN

### Breakpoints Tailwind
- **sm**: 640px+ (Mobile landscape)
- **md**: 768px+ (Tablet)
- **lg**: 1024px+ (Desktop)
- **xl**: 1280px+ (Large desktop)
- **2xl**: 1536px+ (Extra large)

### Adaptations Mobile
- Navigation hamburger
- Cards stack verticalement
- Touch-friendly buttons (44px min)
- Swipe gestures pour carousels
- Modal fullscreen sur mobile

## 🔒 SÉCURITÉ ET PERMISSIONS

### Authentification
- Session-based auth avec cookies
- Redirection automatique si non connecté
- Refresh token automatique
- Protection CSRF

### Autorisations par Rôle
- **Super Admin**: Accès total
- **Admin**: Gestion établissement
- **Manager**: Gestion utilisateurs/contenu
- **Formateur**: Création cours
- **Apprenant**: Consultation cours

### Protection Routes
```typescript
// Vérification auth avant rendu
useEffect(() => {
  if (!isLoading && !isAuthenticated) {
    window.location.href = "/login";
  }
}, [isAuthenticated, isLoading]);
```

## 🚀 PERFORMANCES

### Optimisations
- Code splitting par route
- Lazy loading composants lourds
- Image optimization avec next/image pattern
- Memoization avec React.memo
- Virtualization pour grandes listes

### Bundle Size
- Shadcn/UI: ~50KB gzippé
- React Query: ~15KB gzippé
- Lucide Icons: Tree-shaken ~5KB
- Total estimé: ~200KB initial

## 📈 MÉTRIQUES D'USAGE

### Fonctionnalités Principales
- **Dashboard**: Vue principale post-connexion
- **Courses**: Catalogue central apprentissage
- **Portal**: Découverte établissements
- **Admin**: Gestion configuration
- **WYSIWYG**: Personnalisation contenu

### Interaction Utilisateur
- Recherche temps réel
- Filtres dynamiques
- Formulaires avec validation
- Drag & drop (WYSIWYG)
- Chat temps réel (WebSocket)

---

## ✅ RÉSUMÉ QUANTITATIF

- 📄 **18 Pages/Vues** (4 domaines métier)
- 🧩 **59 Composants** (47 UI + 12 métier)  
- 🪝 **4 Hooks** personnalisés
- 🛠️ **3 Utilitaires** essentiels
- 🎯 **15 Routes** principales
- 🔌 **6 Groupes API** endpoints
- 📱 **5 Breakpoints** responsive
- 🔒 **5 Niveaux** permissions

*Frontend IntraSphere - Interface moderne, modulaire et scalable*
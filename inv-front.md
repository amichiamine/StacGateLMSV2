# INVENTAIRE EXHAUSTIF FRONTEND - PHP StacGateLMS

## ANALYSE STRUCTURELLE FRONTEND

### 📁 ARCHITECTURE DES DOSSIERS
```
php-migration/
├── assets/css/                      # Ressources CSS
├── includes/                        # Composants partagés (header/footer)
├── pages/                           # Pages principales
├── uploads/                         # Uploads utilisateurs (à créer)
├── cache/                           # Cache système (à créer)
└── logs/                           # Logs système (à créer)
```

### 🎨 SYSTÈME CSS & THÈMES

#### **assets/css/glassmorphism.css** (558 lignes)
- **Variables CSS dynamiques** : 35 variables personnalisables
- **Couleurs principales** : --color-primary, --color-secondary, --color-accent
- **Effets glassmorphism** : --glass-bg, --glass-border, --glass-shadow, --glass-backdrop
- **Gradients** : --gradient-primary, --gradient-secondary, --gradient-glass
- **Mode sombre** : .dark avec redéfinition variables
- **Classes utilitaires** : 45+ classes (.glassmorphism, .glass-card, .glass-button, etc.)
- **Grid responsive** : .grid-2, .grid-3, .grid-4 avec auto-fit
- **Animations** : @keyframes fadeIn, slideIn + classes .animate-fade-in
- **Responsive** : @media queries pour mobile/tablet

#### **Composants CSS Glassmorphism**
1. `.glassmorphism` - Conteneur principal avec blur
2. `.glass-nav` - Navigation transparente  
3. `.glass-card` - Cartes avec hover effects
4. `.glass-button` - Boutons avec animations
5. `.glass-input` - Champs de saisie transparents
6. `.badge` - Badges colorés par statut
7. `.hero-section` - Section d'accueil
8. `.nav-menu` - Menu de navigation
9. `.mobile-menu` - Menu mobile overlay

### 📱 COMPOSANTS PARTAGÉS

#### **includes/header.php** (400+ lignes)
**Variables PHP disponibles** :
- `$currentUser` - Utilisateur connecté
- `$isAuthenticated` - État connexion
- `$currentEstablishment` - Établissement actuel  
- `$activeTheme` - Thème personnalisé
- `$csrfToken` - Token sécurité
- `$flashMessage` - Messages temporaires

**Navigation adaptative** :
- Menu public : Accueil, Établissements, Connexion
- Menu apprenant : Dashboard, Cours, Groupes, Aide
- Menu formateur : + Évaluations, Groupes d'étude
- Menu manager : + Analytics, Gestion utilisateurs
- Menu admin : + Administration
- Menu super_admin : + Super Admin, Système

**Menu utilisateur dropdown** :
- Profil avec avatar/initiales
- Nom + rôle + établissement
- Centre d'aide
- Toggle thème dark/light
- Déconnexion

**JavaScript intégré** :
- `toggleMobileMenu()` - Menu mobile
- `toggleUserMenu()` - Dropdown utilisateur
- `toggleTheme()` - Mode sombre
- `apiRequest()` - Requêtes AJAX avec CSRF
- `showToast()` - Notifications
- Gestion cookies thème

#### **includes/footer.php** (200+ lignes)
**Sections footer** :
- Logo + description app
- Navigation rapide selon rôle
- Support et aide
- Informations système (PHP, DB, établissement)
- Copyright + liens légaux

**JavaScript footer** :
- `updateTime()` - Horloge temps réel
- Smooth scroll pour ancres
- `animateOnScroll()` - Animations intersection
- Validation formulaires globale
- Auto-resize textareas
- Confirmation actions destructrices

### 📄 PAGES PRINCIPALES

#### **pages/home.php** (300+ lignes)
**Sections** :
1. **Hero Section** - Titre + sous-titre + CTA buttons
2. **Statistiques** - 3 cards avec métriques (établissements, cours, support)
3. **Fonctionnalités** - 6 cards avec icônes SVG :
   - Multi-tenant
   - Design glassmorphism  
   - Analytics temps réel
   - Évaluations avancées
   - Collaboration
   - Éditeur WYSIWYG
4. **Cours populaires** - Grid avec données dynamiques
5. **CTA final** - Section engagement

**Éléments interactifs** :
- Boutons CTA vers /portal et /login
- Cards hover effects
- Animations séquentielles
- Responsive grid

#### **pages/login.php** (400+ lignes)
**Formulaires** :
1. **Connexion** :
   - Sélecteur établissement (dropdown)
   - Email + mot de passe
   - Case "Se souvenir"
   - Lien mot de passe oublié
   
2. **Inscription** :
   - Sélecteur établissement
   - Prénom + nom (grid 2 colonnes)
   - Email + mot de passe + confirmation
   - Checkbox conditions d'utilisation

**JavaScript** :
- `switchTab()` - Toggle login/register
- `togglePassword()` - Visibilité mot de passe
- Validation temps réel mots de passe
- Validation côté client

**Validation PHP** :
- POST action='login' / action='register'
- Validator::make() avec règles
- Gestion erreurs + messages success
- Authentification via AuthService

#### **pages/dashboard.php** (500+ lignes)
**Structure adaptative selon rôle** :

**Header commun** :
- Message de bienvenue personnalisé
- Badge de rôle coloré
- Nom établissement

**Métriques rapides** (grid-4) :
- Utilisateurs total/apprenants
- Cours disponibles  
- Inscriptions
- Actifs ce mois

**Colonne gauche - contenu par rôle** :
1. **Apprenant** :
   - "Mes cours" avec progression
   - Barres de progression visuelles
   - Boutons "Continuer"

2. **Formateur** :
   - "Mes cours enseignés"
   - Compteurs inscrits + notes
   - Badges statut actif/inactif

3. **Manager/Admin** :
   - Analytics établissement
   - Taux d'activité + cours actifs
   - Lien analytics complète

**Actions rapides** (grid-2 adaptée) :
- Apprenant : Parcourir cours, Groupes étude, Aide, Actualiser
- Formateur : Créer cours, Évaluations, Aide, Actualiser  
- Manager+ : Gestion utilisateurs, Analytics, Aide, Actualiser

**Colonne droite** :
- Cours populaires (top 5)
- Activités récentes (8 dernières)

**JavaScript** :
- `refreshDashboard()` - Reload complet
- Auto-refresh 5 minutes
- Animations progressive cards

### 🔧 FONCTIONNALITÉS FRONTEND

#### **Navigation & Routing**
- Liens conditionnels selon authentification
- Menus adaptatifs selon rôles
- Breadcrumbs (à implémenter)
- URLs propres (/dashboard, /courses, etc.)

#### **Authentification UI**
- Formulaires login/register
- Validation temps réel
- Messages erreurs/succès
- Redirections automatiques
- Gestion sessions

#### **Thèmes & Personnalisation**  
- Variables CSS dynamiques via PHP
- Mode sombre/clair avec cookies
- Thèmes par établissement
- Couleurs personnalisables
- Glassmorphism effects

#### **Interactions JavaScript**
- Toggle menus mobile/desktop
- Dropdown utilisateur
- Validation formulaires
- Requêtes AJAX avec apiRequest()
- Notifications toast
- Animations scroll

#### **Responsive Design**
- Mobile-first approche
- Breakpoints : 768px, 480px
- Grid adaptatives
- Menu mobile overlay
- Touch-friendly boutons

### 📊 COMPOSANTS VISUELS

#### **Cards & Containers**
- `.glass-card` - Cartes principales
- `.glassmorphism` - Conteneurs blur
- `.hero-section` - Section accueil
- `.nav-container` - Conteneur navigation

#### **Boutons & Interactions**
- `.glass-button` - Bouton principal
- `.glass-button-secondary` - Bouton secondaire  
- `.tab-button` - Onglets formulaires
- Hover effects + transitions

#### **Formulaires**
- `.glass-input` - Champs transparents
- `.form-group` - Groupes de champs
- `.form-label` - Labels
- `.form-error` - Messages erreur

#### **Badges & Status**
- `.badge` - Badge par défaut
- `.badge-success` - Vert
- `.badge-warning` - Orange
- `.badge-error` - Rouge

#### **Layout & Grid**
- `.container` - Conteneur principal
- `.grid` - Grid de base
- `.grid-2/3/4` - Grids spécifiques
- Responsive avec auto-fit

### 🎯 ÉLÉMENTS INTERACTIFS

#### **Menus & Navigation**
1. Menu principal adaptatif selon rôle
2. Menu mobile hamburger
3. Dropdown utilisateur
4. Liens conditionnels

#### **Formulaires**
1. Login/register avec onglets
2. Sélecteurs établissement
3. Validation temps réel
4. Messages feedback

#### **Boutons d'Action**
1. CTA hero section
2. Actions rapides dashboard  
3. Boutons cours (Continuer, Voir, Gérer)
4. Toggle thème

#### **Animations & Transitions**
1. Fade-in séquentiel
2. Hover effects cards
3. Smooth scroll
4. Loading states

### 🔍 PAGES MANQUANTES (Référencées mais non créées)

#### **Pages Publiques**
- `/portal` - Sélecteur établissements
- `/establishment/{slug}` - Page établissement
- `/404` - Page erreur

#### **Pages Authentifiées**
- `/courses` - Liste/gestion cours
- `/admin` - Administration  
- `/super-admin` - Super administration
- `/user-management` - Gestion utilisateurs
- `/analytics` - Analytics détaillées
- `/assessments` - Évaluations
- `/study-groups` - Groupes d'étude
- `/help-center` - Centre d'aide
- `/wysiwyg-editor` - Éditeur WYSIWYG
- `/archive-export` - Archives/exports
- `/system-updates` - Mises à jour système
- `/user-manual` - Manuel utilisateur

### 📱 RESPONSIVE & MOBILE

#### **Breakpoints définis** :
- Desktop : > 768px
- Tablet : 480px - 768px  
- Mobile : < 480px

#### **Adaptations Mobile** :
- Navigation hamburger
- Grid single column
- Font sizes réduits
- Padding ajustés
- Touch targets 44px min

### 🎨 DESIGN SYSTEM

#### **Couleurs** (RGB format pour CSS vars) :
- Primary : 139 92 246 (#8B5CF6)
- Secondary : 167 139 250 (#A78BFA)
- Accent : 196 181 253 (#C4B5FD)
- Background : 255 255 255 / 17 24 39 (dark)
- Text : 31 41 55 / 243 244 246 (dark)

#### **Typography** :
- Font family : Inter + fallbacks
- Font sizes : 16px base + rem scaling
- Font weights : 300-800

#### **Spacing** :
- Base unit : 1rem
- Padding classes : p-4, p-6, p-8
- Margin classes : mb-4, mb-6, mb-8, mt-4, mt-6, mt-8

#### **Border Radius** :
- Base : 0.5rem
- Glass : 1rem  
- Full : 9999px (pills)

### 🔧 UTILITAIRES FRONTEND

#### **Classes CSS utilitaires** :
- Text : .text-center, .text-left, .text-right
- Font : .font-bold, .font-semibold, .font-medium
- Spacing : .mb-4, .mb-6, .mb-8, .mt-4, .mt-6, .mt-8
- Padding : .p-4, .p-6, .p-8
- Border : .rounded, .rounded-lg
- Shadow : .shadow, .shadow-lg

#### **JavaScript utilitaires** :
- `window.apiRequest()` - Requêtes AJAX
- `window.showToast()` - Notifications
- `APP_CONFIG` global - Configuration
- Event listeners globaux
- Validation formulaires

### 📋 RÉSUMÉ COMPTEURS FRONTEND

- **Fichiers CSS** : 1 (glassmorphism.css)
- **Pages PHP** : 3 créées + 15 référencées = 18 total
- **Composants partagés** : 2 (header.php, footer.php)
- **Classes CSS** : 45+ utilitaires + composants
- **Variables CSS** : 35 variables personnalisables
- **Fonctions JavaScript** : 15+ fonctions utilitaires
- **Composants UI** : 20+ types (cards, buttons, inputs, etc.)
- **Animations** : 5 types (fade-in, slide-in, hover, etc.)
- **Breakpoints responsive** : 3 niveaux
- **Rôles supportés** : 5 niveaux (super_admin → apprenant)
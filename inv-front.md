# 🔍 INVENTAIRE EXHAUSTIF - FRONTEND (StacGateLMS)

**Date d'analyse :** 08 août 2025  
**Architecture :** React TypeScript avec Vite  
**Status :** Structure active et fonctionnelle  

---

## 🏗️ ARCHITECTURE FRONTEND

### 📁 **STRUCTURE PRINCIPALE - CLIENT/**
```
client/
├── index.html              # Point d'entrée HTML
├── src/                    # Code source React TypeScript
│   ├── App.tsx             # Routeur principal + configuration
│   ├── main.tsx            # Point d'entrée React
│   ├── index.css           # Styles globaux + CSS variables
│   ├── components/         # Composants métier (6 + 47 UI)
│   ├── pages/              # Pages applicatives (19 pages)
│   ├── hooks/              # Hooks personnalisés (5 hooks)
│   └── lib/                # Utilitaires et configuration (3 modules)
```

---

## 📄 **PAGES APPLICATIVES** (19 pages)

### 🔐 **AUTHENTIFICATION & ACCUEIL**
- `landing.tsx` - Page d'atterrissage marketing
- `home.tsx` - Page d'accueil authentifiée
- `login.tsx` - Interface de connexion
- `portal.tsx` - Portail établissements (liste + recherche)
- `not-found.tsx` - Page 404

### 📊 **TABLEAUX DE BORD**
- `dashboard.tsx` - Tableau de bord principal (rôle-spécifique)
- `admin.tsx` - Administration établissement
- `super-admin.tsx` - Super administration globale
- `analytics.tsx` - Statistiques et métriques

### 🎓 **FORMATION & COURS**
- `courses.tsx` - Gestion des cours
- `assessments.tsx` - Évaluations et examens
- `study-groups.tsx` - Groupes d'étude collaboratifs

### 👥 **GESTION UTILISATEURS**
- `user-management.tsx` - Administration utilisateurs
- `establishment.tsx` - Profil d'établissement

### 🔧 **OUTILS & CONFIGURATION**
- `wysiwyg-editor.tsx` - Éditeur WYSIWYG personnalisation
- `archive-export.tsx` - Export et archivage données
- `system-updates.tsx` - Mises à jour système
- `help-center.tsx` - Centre d'aide
- `user-manual.tsx` - Manuel utilisateur

---

## 🧩 **COMPOSANTS MÉTIER** (6 composants)

### 🎨 **INTERFACE PRINCIPALE**
- `navigation.tsx` - Navigation principale avec glassmorphism
- `hero-section.tsx` - Section héro marketing
- `features-section.tsx` - Section fonctionnalités
- `popular-courses-section.tsx` - Cours populaires
- `footer.tsx` - Pied de page

### 🔄 **COLLABORATION TEMPS RÉEL**
- `CollaborationIndicator.tsx` - Indicateur collaboration WebSocket
- `PortalCustomization.tsx` - Personnalisation portail

---

## 🎨 **COMPOSANTS UI SHADCN** (47 composants)

### 📝 **FORMULAIRES & ENTRÉES**
- `form.tsx` - Wrapper formulaires React Hook Form
- `input.tsx` - Champs de saisie
- `textarea.tsx` - Zone de texte multi-lignes
- `input-otp.tsx` - Champ OTP/code de vérification
- `checkbox.tsx` - Cases à cocher
- `radio-group.tsx` - Groupes radio
- `select.tsx` - Sélecteurs dropdown
- `switch.tsx` - Interrupteurs
- `slider.tsx` - Curseurs de valeur
- `label.tsx` - Étiquettes de champs

### 🖼️ **AFFICHAGE & MISE EN PAGE**
- `card.tsx` - Cartes de contenu
- `badge.tsx` - Badges de statut
- `avatar.tsx` - Avatars utilisateurs
- `button.tsx` - Boutons interactifs
- `separator.tsx` - Séparateurs visuels
- `skeleton.tsx` - Squelettes de chargement
- `progress.tsx` - Barres de progression
- `table.tsx` - Tableaux de données
- `aspect-ratio.tsx` - Ratios d'aspect

### 🎠 **NAVIGATION & INTERACTION**
- `navigation-menu.tsx` - Menus de navigation
- `breadcrumb.tsx` - Fil d'Ariane
- `pagination.tsx` - Pagination
- `tabs.tsx` - Onglets
- `accordion.tsx` - Accordéons
- `collapsible.tsx` - Éléments repliables
- `carousel.tsx` - Carrousels
- `scroll-area.tsx` - Zones de défilement

### 💬 **MODALES & POPUPS**
- `dialog.tsx` - Dialogues modaux
- `alert-dialog.tsx` - Dialogues d'alerte
- `drawer.tsx` - Tiroirs latéraux
- `sheet.tsx` - Panneaux glissants
- `popover.tsx` - Popups contextuels
- `tooltip.tsx` - Info-bulles
- `hover-card.tsx` - Cartes au survol
- `context-menu.tsx` - Menus contextuels
- `dropdown-menu.tsx` - Menus déroulants
- `menubar.tsx` - Barres de menu

### 🔧 **UTILITAIRES & AVANCÉS**
- `command.tsx` - Interface de commandes
- `toggle.tsx` - Boutons bascule
- `toggle-group.tsx` - Groupes de bascule
- `calendar.tsx` - Calendrier
- `chart.tsx` - Graphiques
- `resizable.tsx` - Panneaux redimensionnables
- `sidebar.tsx` - Barres latérales
- `alert.tsx` - Alertes de notification
- `toast.tsx` - Notifications toast
- `toaster.tsx` - Gestionnaire de toasts

---

## 🎨 **COMPOSANTS WYSIWYG** (5 composants)

### ✏️ **ÉDITEUR PERSONNALISATION**
- `PageEditor.tsx` - Éditeur de pages complet
- `PagePreview.tsx` - Prévisualisation en temps réel
- `ComponentEditor.tsx` - Éditeur de composants
- `ComponentLibrary.tsx` - Bibliothèque de composants
- `ColorPicker.tsx` - Sélecteur de couleurs

---

## 🔗 **HOOKS PERSONNALISÉS** (5 hooks)

### 🔐 **AUTHENTIFICATION & ÉTAT**
- `useAuth.ts` - Gestion authentification utilisateur
- `useTheme.ts` - Gestion thèmes clair/sombre

### 🔄 **TEMPS RÉEL & COLLABORATION**
- `useCollaboration.ts` - Collaboration WebSocket

### 📱 **INTERFACE UTILISATEUR**
- `use-mobile.tsx` - Détection mobile/responsive
- `use-toast.ts` - Gestion notifications toast

---

## 🔧 **UTILITAIRES & CONFIGURATION** (3 modules)

### 🌐 **GESTION DONNÉES**
- `queryClient.ts` - Configuration TanStack Query

### 🔐 **AUTHENTIFICATION**
- `authUtils.ts` - Utilitaires authentification

### 🎨 **STYLING**
- `utils.ts` - Utilitaires CSS (cn, clsx, tailwind-merge)

---

## 🚀 **TECHNOLOGIES & DÉPENDANCES**

### ⚛️ **FRAMEWORK PRINCIPAL**
- React 18 + TypeScript
- Vite (build tool)
- Wouter (routage léger)

### 🎨 **STYLING & UI**
- Tailwind CSS + CSS variables
- Shadcn/ui components
- Lucide React (icônes)
- Framer Motion (animations)
- Glassmorphism design

### 📊 **GESTION ÉTAT & DONNÉES**
- TanStack Query (cache + synchronisation)
- React Hook Form + Zod (formulaires)
- Wouter (navigation)

### 🔄 **TEMPS RÉEL**
- WebSocket natif (collaboration)
- React Context (état global)

### 🧰 **UTILITAIRES**
- Date-fns (dates)
- Clsx + Tailwind-merge (CSS)
- Class-variance-authority (variants)

---

## 🔗 **INTÉGRATIONS & APIS**

### 🌐 **COMMUNICATION BACKEND**
- `/api/*` - Routes API REST
- WebSocket `/ws/collaboration` - Temps réel
- Session-based authentication

### 📁 **GESTION FICHIERS**
- Uppy.js (upload avancé)
- Google Cloud Storage intégration

---

## 📐 **ARCHITECTURE PATTERNS**

### 🏗️ **STRUCTURE**
- Component-driven development
- Pages séparées par domaine métier
- Hooks personnalisés pour logique réutilisable
- Configuration centralisée (query client, auth)

### 🎨 **DESIGN SYSTEM**
- Shadcn/ui base components
- CSS variables pour thèmes
- Glassmorphism + animations
- Mobile-first responsive

### 🔄 **GESTION ÉTAT**
- Server state: TanStack Query
- Client state: React hooks + Context
- Formulaires: React Hook Form + Zod
- Real-time: WebSocket + Context

---

## 🚨 **POINTS D'ATTENTION**

### ✅ **POINTS FORTS**
- Architecture moderne et scalable
- Components réutilisables bien structurés
- Gestion d'état optimisée
- Design system cohérent
- Temps réel intégré

### ⚠️ **AMÉLIORATIONS POSSIBLES**
- Tests unitaires manquants
- Documentation composants à enrichir
- Optimisation bundle size possible
- Error boundaries à implémenter
- Accessibilité à auditer

---

## 📊 **MÉTRIQUES**

- **Pages totales :** 19
- **Composants métier :** 6
- **Composants UI :** 47 + 5 WYSIWYG = 52
- **Hooks personnalisés :** 5
- **Modules utilitaires :** 3
- **Routes définies :** 16 routes principales
- **Intégrations externes :** 4 (WebSocket, GCS, Auth, Query)

**Total fichiers frontend analysés :** ~100 fichiers
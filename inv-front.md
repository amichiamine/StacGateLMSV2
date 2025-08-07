# 📋 INVENTAIRE EXHAUSTIF - FRONTEND (CLIENT/)

**Projet :** StacGateLMS - Plateforme e-learning multi-établissements  
**Version :** 1.0.0  
**Date d'analyse :** 07 Janvier 2025  
**Statut projet :** En développement actif  

---

## 🏗️ ARCHITECTURE FRONTEND

### 📁 **STRUCTURE PRINCIPALE - CLIENT/**
```
client/
├── index.html                 # Point d'entrée HTML
├── src/
│   ├── App.tsx               # Router principal et configuration
│   ├── main.tsx              # Point d'entrée React
│   ├── index.css            # Styles globaux et variables CSS
│   ├── components/          # Composants réutilisables
│   ├── hooks/               # Hooks personnalisés
│   ├── lib/                 # Utilitaires et configurations
│   └── pages/               # Pages complètes de l'application
```

---

## 🎨 TECHNOLOGIES ET STACK FRONTEND

### **Frameworks & Libraries Core**
- **React 18.3.1** - Framework frontend principal
- **TypeScript** - Langage principal avec types stricts
- **Vite 5.4.19** - Build tool et dev server
- **Wouter 3.3.5** - Router léger pour navigation SPA

### **UI & Styling**
- **Tailwind CSS 3.4.17** - Framework CSS utility-first
- **Tailwind CSS Animate 1.0.7** - Animations CSS
- **Radix UI** (38 composants) - Primitives UI accessibles
- **Lucide React 0.453.0** - Icônes SVG (1000+ icônes)
- **Framer Motion 11.13.1** - Animations avancées
- **Next Themes 0.4.6** - Gestion mode sombre/clair

### **State Management & Data**
- **TanStack Query 5.60.5** - Gestion état serveur et cache
- **React Hook Form 7.55.0** - Gestion formulaires
- **Hookform Resolvers 3.10.0** - Validation schemas
- **Zod 3.24.2** - Validation TypeScript-first

### **Charts & Visualization**
- **Recharts 2.15.2** - Graphiques et analytics
- **Embla Carousel 8.6.0** - Carousels responsives

### **File Upload & Media**
- **Uppy** (6 packages) - Upload fichiers avancé
- **Google Cloud Storage** - Stockage cloud files

---

## 📋 COMPOSANTS UI (38 COMPOSANTS SHADCN)

### **Layout & Navigation (8)**
- `accordion.tsx` - Accordéons collapsibles
- `breadcrumb.tsx` - Fil d'ariane navigation
- `navigation-menu.tsx` - Menus navigation complexes
- `menubar.tsx` - Barres de menu horizontales
- `sidebar.tsx` - Barres latérales
- `separator.tsx` - Séparateurs visuels
- `resizable.tsx` - Panneaux redimensionnables
- `scroll-area.tsx` - Zones de défilement custom

### **Form Controls (11)**
- `button.tsx` - Boutons avec variants
- `input.tsx` - Champs de saisie
- `textarea.tsx` - Zones de texte
- `label.tsx` - Labels accessibles
- `form.tsx` - Composant formulaire intégré
- `checkbox.tsx` - Cases à cocher
- `radio-group.tsx` - Boutons radio
- `switch.tsx` - Interrupteurs
- `slider.tsx` - Curseurs de valeur
- `select.tsx` - Listes de sélection
- `input-otp.tsx` - Saisie codes OTP

### **Data Display (8)**
- `table.tsx` - Tableaux de données
- `card.tsx` - Conteneurs d'information
- `badge.tsx` - Badges et étiquettes
- `avatar.tsx` - Images de profil
- `calendar.tsx` - Sélecteur de dates
- `chart.tsx` - Graphiques intégrés
- `aspect-ratio.tsx` - Ratios d'images
- `progress.tsx` - Barres de progression

### **Overlay Components (7)**
- `dialog.tsx` - Modales et popups
- `alert-dialog.tsx` - Dialogues de confirmation
- `sheet.tsx` - Panneaux latéraux
- `popover.tsx` - Info-bulles avancées
- `tooltip.tsx` - Info-bulles simples
- `context-menu.tsx` - Menus contextuels
- `dropdown-menu.tsx` - Menus déroulants

### **Interactive & Feedback (4)**
- `toggle.tsx` - Boutons bascule
- `toggle-group.tsx` - Groupes de bascules
- `collapsible.tsx` - Contenus collapsibles
- `command.tsx` - Palette de commandes

---

## 📱 PAGES APPLICATIVES (18 PAGES)

### **Pages Publiques (4)**
1. **`home.tsx`** - Page d'accueil générale
2. **`landing.tsx`** - Landing page marketing
3. **`portal.tsx`** - Portail multi-établissements
4. **`establishment.tsx`** - Page spécifique établissement

### **Authentication (2)**
5. **`login.tsx`** - Connexion utilisateurs
6. **`not-found.tsx`** - Erreur 404

### **User Dashboard (1)**
7. **`dashboard.tsx`** - Tableau de bord utilisateur

### **Content Management (4)**
8. **`courses.tsx`** - Gestion des cours
9. **`assessments.tsx`** - Évaluations et quiz
10. **`study-groups.tsx`** - Groupes d'étude collaboratifs
11. **`wysiwyg-editor.tsx`** - Éditeur WYSIWYG pages

### **Administration (5)**
12. **`admin.tsx`** - Administration établissement
13. **`super-admin.tsx`** - Super administration
14. **`user-management.tsx`** - Gestion utilisateurs
15. **`archive-export.tsx`** - Archivage et export
16. **`system-updates.tsx`** - Mises à jour système

### **Documentation & Support (2)**
17. **`user-manual.tsx`** - Manuel utilisateur
18. **`portal-old.tsx`** - Ancienne version portail (legacy)

---

## 🔧 COMPOSANTS MÉTIER SPÉCIALISÉS (11)

### **Portal Customization (1)**
- `PortalCustomization.tsx` - Personnalisation portails

### **Landing Page Components (5)**
- `hero-section.tsx` - Section héro marketing
- `features-section.tsx` - Présentation fonctionnalités
- `popular-courses-section.tsx` - Cours populaires
- `footer.tsx` - Pied de page
- `navigation.tsx` - Navigation principale

### **WYSIWYG Editor (5)**
- `wysiwyg/PageEditor.tsx` - Éditeur de pages complet
- `wysiwyg/PagePreview.tsx` - Aperçu en temps réel
- `wysiwyg/ComponentEditor.tsx` - Éditeur composants
- `wysiwyg/ComponentLibrary.tsx` - Bibliothèque composants
- `wysiwyg/ColorPicker.tsx` - Sélecteur couleurs

---

## 🪝 HOOKS PERSONNALISÉS (4)

### **Authentication & User**
- `useAuth.ts` - Gestion authentification utilisateur
- `useTheme.ts` - Basculement thème sombre/clair

### **UI & Interactions**
- `use-toast.ts` - Notifications toast
- `use-mobile.tsx` - Détection appareil mobile

---

## 📚 UTILITAIRES & CONFIGURATIONS (3)

### **API & Data**
- `lib/queryClient.ts` - Configuration TanStack Query
- `lib/authUtils.ts` - Utilitaires authentification
- `lib/utils.ts` - Fonctions utilitaires génériques

---

## 🎯 FONCTIONNALITÉS PRINCIPALES

### **Multi-Tenant & Personnalisation**
- ✅ Support multi-établissements
- ✅ Thèmes personnalisables par établissement
- ✅ Contenus WYSIWYG éditables
- ✅ Menus configurables
- ✅ Portail centralisé établissements

### **Gestion Utilisateurs**
- ✅ Système de rôles (5 niveaux)
- ✅ Authentification par établissement  
- ✅ Gestion permissions granulaires
- ✅ Profils utilisateurs complets

### **Gestion Formation**
- ✅ Cours synchrones/asynchrones
- ✅ Modules de cours structurés
- ✅ Évaluations et quiz
- ✅ Groupes d'étude collaboratifs
- ✅ Système de progression

### **Administration Avancée**
- ✅ Tableau de bord analytics
- ✅ Gestion utilisateurs multi-établissements
- ✅ Export/archivage des données
- ✅ Mises à jour système
- ✅ Manuel utilisateur intégré

### **Collaboration & Temps Réel**
- ✅ WebSocket pour collaboration
- ✅ Groupes d'étude en temps réel
- ✅ Messagerie intégrée
- ✅ Tableau blanc collaboratif

---

## 📊 MÉTRIQUES TECHNIQUES

### **Bundle & Performance**
- **Build Tool:** Vite (build ultra-rapide)
- **Tree Shaking:** Optimisé
- **Code Splitting:** Routes automatique
- **Hot Reload:** Développement
- **TypeScript:** 100% typé

### **Accessibilité**
- **Radix UI:** Composants accessibles ARIA
- **Keyboard Navigation:** Support complet
- **Screen Readers:** Compatible
- **Color Contrast:** WCAG 2.1 AA

### **Responsiveness**
- **Mobile First:** Design adaptatif
- **Breakpoints:** Tailwind standards
- **Touch Friendly:** Interfaces tactiles
- **Progressive Enhancement:** Dégradation gracieuse

---

## 🚨 POINTS D'ATTENTION

### **Compatibilité**
- ✅ Moderne browsers (ES2020+)
- ✅ Mobile/tablet optimisé
- ⚠️ IE non supporté (attendu)

### **Performance**
- ✅ Lazy loading pages
- ✅ Query cache optimisé
- ⚠️ Bundle size à surveiller (nombreuses dépendances)

### **Sécurité Frontend**
- ✅ Types TypeScript stricts
- ✅ Validation côté client
- ⚠️ Variables sensibles VITE_ prefixées

---

## 🔗 ROUTES APPLICATIVES (12 ROUTES)

1. `/` - Home page
2. `/portal` - Portail établissements
3. `/establishment/:slug` - Page établissement
4. `/login` - Authentification
5. `/dashboard` - Tableau de bord
6. `/admin` - Administration
7. `/super-admin` - Super administration
8. `/user-management` - Gestion utilisateurs
9. `/courses` - Gestion cours
10. `/assessments` - Évaluations
11. `/manual` - Documentation
12. `/archive` - Export/archivage
13. `/system-updates` - Mises à jour
14. `/wysiwyg-editor` - Éditeur WYSIWYG
15. `/study-groups` - Groupes d'étude

---

## 🎨 PERSONNALISATION GRAPHIQUE

### **Thèmes Supportés**
- Mode sombre/clair automatique
- Couleurs primaires personnalisables
- Polices configurables
- Layouts adaptatifs

### **Éditeur WYSIWYG**
- Composants drag & drop
- Aperçu temps réel
- Bibliothèque composants
- Sélecteur couleurs avancé

---

**🏁 TOTAL FRONTEND:**
- **38 Composants UI** (Shadcn/Radix)
- **18 Pages applicatives** 
- **11 Composants métier**
- **4 Hooks personnalisés**
- **3 Utilitaires core**
- **15 Routes actives**
- **50+ Dépendances**

---
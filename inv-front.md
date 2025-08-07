# INVENTAIRE FRONTEND - StacGateLMS
*Généré le 07/08/2025 - Analyse complète après réorganisation*

## ARCHITECTURE FRONTEND

### Structure des dossiers
```
client/
├── src/
│   ├── components/          # Composants React
│   │   ├── ui/             # Composants UI Shadcn
│   │   ├── wysiwyg/        # Éditeur WYSIWYG
│   │   └── [fichiers]      # Composants métier
│   ├── pages/              # Pages de l'application
│   ├── hooks/              # Hooks React personnalisés
│   └── lib/                # Utilitaires et configurations
└── index.html              # Point d'entrée HTML
```

## INVENTAIRE DÉTAILLÉ DES COMPOSANTS

### 1. COMPOSANTS UI SHADCN (47 composants)

#### Navigation & Layout
1. **navigation-menu.tsx** - Menu principal de navigation
2. **sidebar.tsx** - Barre latérale 
3. **breadcrumb.tsx** - Fil d'Ariane
4. **menubar.tsx** - Barre de menu
5. **sheet.tsx** - Panneau latéral coulissant

#### Formulaires & Saisie
6. **form.tsx** - Composant formulaire avec validation
7. **input.tsx** - Champ de saisie texte
8. **textarea.tsx** - Zone de texte multilignes
9. **button.tsx** - Boutons avec variantes
10. **checkbox.tsx** - Cases à cocher
11. **radio-group.tsx** - Boutons radio groupés
12. **select.tsx** - Listes déroulantes
13. **switch.tsx** - Interrupteurs
14. **slider.tsx** - Curseurs de valeur
15. **input-otp.tsx** - Saisie de code OTP
16. **label.tsx** - Étiquettes de champs

#### Affichage de données
17. **table.tsx** - Tableaux de données
18. **card.tsx** - Cartes d'information
19. **badge.tsx** - Badges et étiquettes
20. **avatar.tsx** - Photos de profil
21. **skeleton.tsx** - Placeholders de chargement
22. **progress.tsx** - Barres de progression
23. **chart.tsx** - Graphiques et diagrammes

#### Interactions & Feedback
24. **dialog.tsx** - Modales et dialogues
25. **alert-dialog.tsx** - Dialogues de confirmation
26. **popover.tsx** - Fenêtres contextuelles
27. **tooltip.tsx** - Info-bulles
28. **hover-card.tsx** - Cartes au survol
29. **context-menu.tsx** - Menus contextuels
30. **dropdown-menu.tsx** - Menus déroulants
31. **command.tsx** - Palette de commandes
32. **toast.tsx** - Notifications toast
33. **toaster.tsx** - Gestionnaire de notifications
34. **alert.tsx** - Alertes d'information

#### Organisation & Groupement
35. **tabs.tsx** - Onglets
36. **accordion.tsx** - Accordéons
37. **collapsible.tsx** - Éléments pliables
38. **separator.tsx** - Séparateurs visuels
39. **aspect-ratio.tsx** - Ratios d'aspect
40. **scroll-area.tsx** - Zones de défilement
41. **resizable.tsx** - Panneaux redimensionnables

#### Contrôles avancés
42. **calendar.tsx** - Calendrier de sélection de date
43. **toggle.tsx** - Boutons bascule
44. **toggle-group.tsx** - Groupes de boutons bascule
45. **pagination.tsx** - Navigation par pages
46. **drawer.tsx** - Tiroirs coulissants
47. **carousel.tsx** - Carrousels d'images

### 2. COMPOSANTS MÉTIER (6 composants)

#### Spécialisés E-learning
1. **PortalCustomization.tsx**
   - Personnalisation de l'interface établissement
   - Configuration des thèmes et couleurs
   - Gestion du branding

2. **features-section.tsx**  
   - Section présentation des fonctionnalités
   - Mise en valeur des capacités de la plateforme

3. **hero-section.tsx**
   - Section héroïque page d'accueil
   - Présentation principale de l'application

4. **footer.tsx**
   - Pied de page personnalisable
   - Liens et informations établissement

5. **navigation.tsx**
   - Navigation principale adaptative
   - Menu responsive multi-niveau

6. **popular-courses-section.tsx**
   - Section des cours populaires
   - Affichage des formations tendances

### 3. ÉDITEUR WYSIWYG
Dossier `wysiwyg/` (contenu non détaillé mais présent)
- Éditeur de contenu riche
- Interface de création de pages personnalisées

## INVENTAIRE DES PAGES (16 pages)

### 1. PAGES PUBLIQUES
1. **landing.tsx** - Page d'atterrissage publique
2. **home.tsx** - Page d'accueil connectée
3. **not-found.tsx** - Page 404

### 2. AUTHENTIFICATION  
4. **login.tsx** - Page de connexion

### 3. PAGES UTILISATEUR
5. **dashboard.tsx** - Tableau de bord utilisateur
6. **courses.tsx** - Catalogue des cours
7. **assessments.tsx** - Évaluations et examens
8. **study-groups.tsx** - Groupes d'étude
9. **portal.tsx** - Portail utilisateur principal
10. **portal-old.tsx** - Ancienne version du portail

### 4. PAGES ÉDITEUR
11. **wysiwyg-editor.tsx** - Éditeur de pages
12. **establishment.tsx** - Gestion établissement

### 5. PAGES ADMINISTRATION
13. **admin.tsx** - Interface administration
14. **super-admin.tsx** - Interface super administrateur  
15. **user-management.tsx** - Gestion des utilisateurs
16. **user-manual.tsx** - Manuel utilisateur
17. **archive-export.tsx** - Export et archivage
18. **system-updates.tsx** - Mises à jour système

## INVENTAIRE DES HOOKS (4 hooks)

### Hooks personnalisés
1. **useAuth.ts** - Gestion de l'authentification
   - États de connexion
   - Informations utilisateur
   - Actions de connexion/déconnexion

2. **useTheme.ts** - Gestion des thèmes
   - Basculement mode sombre/clair
   - Persistance des préférences

3. **use-mobile.tsx** - Détection mobile
   - Responsive design
   - Adaptation interface

4. **use-toast.ts** - Notifications
   - Gestion des messages toast
   - File d'attente de notifications

## INVENTAIRE DES UTILITAIRES (3 fichiers)

### Utilitaires et configurations
1. **utils.ts** - Utilitaires génériques
   - Helpers de formatage
   - Fonctions communes

2. **queryClient.ts** - Configuration TanStack Query
   - Client de requêtes API
   - Gestion du cache

3. **authUtils.ts** - Utilitaires d'authentification
   - Helpers de session
   - Validation des rôles

## ROUTES PRINCIPALES (13 routes identifiées)

### Routes publiques
- `/` - Landing page
- `/login` - Connexion

### Routes protégées utilisateur
- `/dashboard` - Tableau de bord
- `/courses` - Cours
- `/assessments` - Évaluations  
- `/study-groups` - Groupes d'étude
- `/portal` - Portail
- `/user-manual` - Manuel

### Routes administration
- `/admin` - Administration
- `/super-admin` - Super admin
- `/user-management` - Gestion utilisateurs
- `/establishment` - Gestion établissement
- `/wysiwyg-editor` - Éditeur

## TECHNOLOGIES FRONTEND

### Stack principal
- **React 18** - Framework UI
- **TypeScript** - Typage statique
- **Vite** - Build tool et dev server
- **Wouter** - Routage léger
- **TanStack Query** - Gestion d'état serveur

### Styling & UI
- **Tailwind CSS** - Framework CSS utilitaire
- **Shadcn/UI** - Bibliothèque de composants
- **Framer Motion** - Animations
- **Lucide React** - Icônes
- **React Icons** - Icônes complémentaires

### Formulaires & Validation
- **React Hook Form** - Gestion de formulaires
- **Zod** - Validation de schémas
- **@hookform/resolvers** - Intégration validateurs

### Fonctionnalités avancées
- **Date-fns** - Manipulation de dates
- **React Day Picker** - Sélecteur de date
- **Input OTP** - Codes de vérification
- **Recharts** - Graphiques
- **Embla Carousel** - Carrousels

## ANALYSE DE COMPATIBILITÉ

### Points forts
✅ **Architecture modulaire** bien organisée
✅ **Composants réutilisables** avec Shadcn/UI  
✅ **TypeScript complet** pour la sécurité
✅ **Responsive design** adaptatif
✅ **Gestion d'état moderne** avec TanStack Query
✅ **Validation côté client** robuste

### Points d'amélioration identifiés
⚠️ **Tests unitaires manquants** pour les composants
⚠️ **Documentation des composants** métier à améliorer
⚠️ **Optimisation des performances** à analyser
⚠️ **Accessibilité** à vérifier sur composants custom

## RÉSUMÉ STATISTIQUE

- **Total composants UI** : 47 (Shadcn)
- **Total composants métier** : 6
- **Total pages** : 16 
- **Total hooks** : 4
- **Total utilitaires** : 3
- **Routes principales** : 13
- **Dépendances** : ~60 packages frontend

---
*Inventaire généré automatiquement - StacGateLMS Frontend Analysis*
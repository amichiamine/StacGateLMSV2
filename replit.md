# IntraSphere - Plateforme E-learning Moderne

## Description du Projet
IntraSphere est une plateforme d'apprentissage moderne et flexible (LMS) qui offre une expérience d'apprentissage complète et adaptative pour plusieurs établissements avec des capacités administratives robustes. 

**Nouvelle architecture organisée par domaines métier pour une meilleure maintenabilité et évolutivité.**

## Technologies Clés
- **Frontend**: React TypeScript avec design responsive avancé
- **Backend**: Node.js Express avec architecture multi-tenant
- **Base de données**: PostgreSQL avec Drizzle ORM
- **Gestion d'état**: Tanstack Query pour une gestion efficace des données
- **Contrôle d'accès**: Système de contrôle d'accès basé sur les rôles
- **UI**: Shadcn/ui + Tailwind CSS pour une interface moderne

## Architecture du Projet

### Structure Moderne par Domaines Métier
```
IntraSphere/
├── frontend/                 # Interface utilisateur React
│   ├── src/
│   │   ├── components/      # Composants React
│   │   │   ├── ui/          # Composants shadcn/ui
│   │   │   ├── layout/      # Composants de mise en page  
│   │   │   └── dashboard/   # Composants métier
│   │   ├── features/        # Pages organisées par domaine
│   │   │   ├── auth/        # Authentification
│   │   │   ├── admin/       # Administration
│   │   │   ├── content/     # Gestion contenu
│   │   │   └── training/    # Formation et cours
│   │   └── core/            # Hooks et utilitaires
│   └── public/              # Assets statiques
├── backend/                  # API Node.js/Express  
│   ├── src/
│   │   ├── routes/          # Endpoints API
│   │   ├── services/        # Logique métier
│   │   │   ├── AuthService.ts   # Service d'authentification
│   │   │   ├── CourseService.ts # Service de gestion des cours
│   │   │   ├── EstablishmentService.ts # Service établissements
│   │   │   └── NotificationService.ts  # Service notifications
│   │   ├── middleware/      # Auth/Sécurité/Logs
│   │   └── data/            # Storage et modèles
│   └── migrations/          # Migrations base de données
├── shared/                   # Types TypeScript partagés
│   └── schema.ts            # Schémas Drizzle ORM et validations Zod
├── config/                   # Configuration globale
│   ├── drizzle.config.ts    # Configuration base de données
│   ├── tailwind.config.ts   # Styles et thèmes  
│   └── vite.config.ts       # Build et développement
└── deployment/               # Configurations de déploiement
    ├── docker/              # Configuration Docker
    ├── cpanel/              # Configuration cPanel/hébergement web
    └── vscode/              # Configuration VS Code
```

## Fonctionnalités Principales

### Gestion Multi-établissements
- Architecture multi-tenant complète
- Gestion centralisée des établissements
- Isolation des données par établissement

### Système de Rôles
- **Super Admin**: Gestion globale de la plateforme
- **Admin**: Gestion d'un établissement
- **Manager**: Gestion des utilisateurs et contenus
- **Formateur**: Création et gestion des cours
- **Apprenant**: Accès aux cours et formations

### Interface Utilisateur
- Design moderne avec Shadcn/ui
- Responsive design mobile-first
- Thème sombre/clair avec switcher
- Navigation intuitive et accessible

## Changements Récents

### 07/08/2025 - Migration vers Architecture IntraSphere + Analyse Exhaustive
**Actions effectuées :**

#### 1. Réorganisation Architecturale Majeure ✓
- ✓ Migration complète vers structure par domaines métier
- ✓ Frontend séparé (/frontend/) avec organisation par features
- ✓ Backend isolé (/backend/) avec architecture en couches
- ✓ Configuration centralisée (/config/)
- ✓ Types partagés optimisés (/shared/)

#### 2. Structure par Domaines ✓
- ✓ **Features Auth** - Authentification et sessions
- ✓ **Features Admin** - Administration et supervision  
- ✓ **Features Content** - Gestion contenu et WYSIWYG
- ✓ **Features Training** - Cours, évaluations, groupes d'étude
- ✓ **Composants hiérarchisés** - ui/ → layout/ → dashboard/

#### 3. Services et Couches ✓
- ✓ Couche de services métier maintenue (AuthService, CourseService, EstablishmentService, NotificationService)
- ✓ Routes organisées par domaine métier
- ✓ Middleware centralisé (Auth/Sécurité/Logs)
- ✓ Couche data isolée (Storage/Models)

#### 4. Configuration Moderne ✓
- ✓ **Vite séparé** pour le frontend
- ✓ **Package.json dédié** par environnement
- ✓ **Tailwind configuré** pour le frontend
- ✓ **TypeScript optimisé** avec paths aliases

#### 5. Documentation et Inventaires Complets ✓
- ✓ **ARCHITECTURE.md** - Documentation architecture complète
- ✓ **inv-front.md** - Inventaire exhaustif Frontend (18 pages, 59 composants, 4 hooks, 3 utilitaires)
- ✓ **inv-back.md** - Inventaire exhaustif Backend (23 tables, 4 services, 40+ endpoints)
- ✓ **Analyse détaillée** de toutes les fonctionnalités, composants, et capacités

**Découvertes de l'Analyse :**
- **Frontend** : 18 pages/vues organisées en 4 domaines métier, 59 composants (47 UI + 12 métier)
- **Backend** : Architecture multi-tenant robuste avec 23 tables et 4 services spécialisés
- **Capacités** : Système complet LMS avec WYSIWYG, évaluations, groupes d'étude, chat temps réel
- **Sécurité** : Authentification locale, permissions granulaires, middleware sécurisé
- **Performance** : TanStack Query, WebSocket, cache automatique, multi-tenant optimisé

**Résultat :**
- **Architecture nouvelle génération** organisée par domaines métier
- **Documentation complète** - Tous les éléments catalogués et analysés
- **Vision claire** du scope et des capacités de la plateforme
- **Base solide** pour les décisions d'optimisation et de réorganisation
- **Inventaire détaillé** prêt pour la phase de migration/optimisation

## Configuration de Développement

### Démarrage
```bash
npm run dev
```

### Base de Données
- PostgreSQL configuré via Drizzle ORM
- Migrations automatiques avec `npm run db:push`
- Schémas définis dans `shared/schema.ts`

### Workflow Replit
- Workflow "Start application" configuré pour `npm run dev`
- Serveur Express sur port 5000
- Frontend Vite intégré

## Préférences Utilisateur
*Aucune préférence spécifique documentée pour le moment*

## Notes Techniques
- Utilisation exclusive des outils Replit
- Pas d'environnements virtuels ou Docker
- Base de données de développement uniquement
- Architecture basée sur les principes fullstack modernes
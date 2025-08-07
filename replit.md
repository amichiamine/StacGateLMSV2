# StacGateLMS - Plateforme E-learning

## Description du Projet
StacGateLMS est une plateforme d'apprentissage moderne et flexible (LMS) qui offre une expérience d'apprentissage complète et adaptative pour plusieurs établissements avec des capacités administratives robustes.

## Technologies Clés
- **Frontend**: React TypeScript avec design responsive avancé
- **Backend**: Node.js Express avec architecture multi-tenant
- **Base de données**: PostgreSQL avec Drizzle ORM
- **Gestion d'état**: Tanstack Query pour une gestion efficace des données
- **Contrôle d'accès**: Système de contrôle d'accès basé sur les rôles
- **UI**: Shadcn/ui + Tailwind CSS pour une interface moderne

## Architecture du Projet

### Structure Optimisée (Avec couche services et déploiement)
```
StacGateLMS/
├── client/                    # Frontend React TypeScript
│   ├── src/components/       # Composants UI (shadcn/ui)
│   ├── src/pages/           # Pages de l'application
│   ├── src/hooks/           # Hooks React personnalisés
│   └── src/lib/             # Utilitaires et configurations
├── server/                   # Backend Express TypeScript
│   ├── middleware/          # Authentification et autorisation
│   ├── services/            # Couche de logique métier
│   │   ├── AuthService.ts   # Service d'authentification
│   │   ├── CourseService.ts # Service de gestion des cours
│   │   ├── EstablishmentService.ts # Service établissements
│   │   └── NotificationService.ts  # Service notifications
│   ├── routes.ts            # Définitions des endpoints API
│   ├── storage.ts           # Couche d'accès aux données
│   └── database-manager.ts  # Gestion base de données multi-établissements
├── shared/                   # Schémas et types partagés
│   └── schema.ts            # Schémas Drizzle ORM et validations Zod
├── deployment/               # Configurations de déploiement
│   ├── docker/              # Configuration Docker
│   ├── cpanel/              # Configuration cPanel/hébergement web
│   └── vscode/              # Configuration VS Code
├── node_modules/            # Dépendances (auto-générées)
├── package.json             # Configuration du projet
├── vite.config.ts           # Configuration Vite
├── tailwind.config.ts       # Configuration Tailwind
└── drizzle.config.ts        # Configuration Drizzle ORM
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

### 07/08/2025 - Réorganisation Complète et Analyse Approfondie
**Actions effectuées :**

#### 1. Réorganisation Architecturale ✓
- ✓ Création de la couche de services métier (server/services/)
- ✓ AuthService.ts, CourseService.ts, EstablishmentService.ts, NotificationService.ts  
- ✓ Configuration pour déploiements multiples (deployment/)
- ✓ Docker, cPanel, VS Code configurations
- ✓ Structure optimisée pour développement et production

#### 2. Correction des Erreurs LSP ⚡
- ✓ Erreurs réduites de 133 à 69 (-48% d'amélioration)
- ✓ shared/schema.ts complètement corrigé (0 erreurs)
- ✓ Imports nanoid et types Assessment/AssessmentAttempt corrigés
- ✓ Services nouvellement créés avec corrections types
- ⚠️ storage.ts reste à finaliser (69 erreurs restantes sur tables permissions)

#### 3. Inventaires Exhaustifs Créés ✓
- ✓ **inv-front.md** - 502 lignes d'analyse frontend complète
  - 47 composants UI Shadcn, 6 composants métier, 16 pages
  - 4 hooks personnalisés, 13 routes principales
- ✓ **inv-back.md** - 625 lignes d'analyse backend complète  
  - 4 services métier, 98+ méthodes storage, 57+ endpoints API
  - 23 tables de base, architecture multi-tenant

#### 4. Documentation Mise à Jour ✓
- ✓ replit.md actualisé avec nouvelle architecture
- ✓ Structure projet documentée avec services et déploiement
- ✓ Analyse de compatibilité frontend ↔ backend effectuée

**Résultat :**
- Architecture considérablement renforcée avec couche services
- Documentation exhaustive de tous les composants/fonctionnalités
- Configurations de déploiement multi-environnement
- Base solide pour développements futurs
- Erreurs LSP réduites significativement

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
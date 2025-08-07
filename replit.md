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

### Structure Finale (Post-nettoyage)
```
StacGateLMS/
├── client/                    # Frontend React TypeScript
│   ├── src/components/       # Composants UI (shadcn/ui)
│   ├── src/pages/           # Pages de l'application
│   ├── src/hooks/           # Hooks React personnalisés
│   └── src/lib/             # Utilitaires et configurations
├── server/                   # Backend Express TypeScript
│   ├── middleware/          # Authentification et autorisation
│   ├── routes.ts            # Définitions des endpoints API
│   ├── storage.ts           # Couche d'accès aux données
│   └── database-manager.ts  # Gestion base de données multi-établissements
├── shared/                   # Schémas et types partagés
│   └── schema.ts            # Schémas Drizzle ORM et validations Zod
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

### 07/08/2025 - Nettoyage Complet du Projet
**Actions effectuées :**
- ✓ Suppression de toute la documentation (.md)
- ✓ Suppression des scripts personnalisés (.sh)
- ✓ Suppression des installateurs (.php)
- ✓ Suppression des assets temporaires
- ✓ Nettoyage de la structure du projet

**Résultat :**
- Projet maintenant focalisé uniquement sur le code fonctionnel
- Structure simplifiée et optimisée
- Taille du projet réduite significativement
- Focus sur l'application React/Express pure

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
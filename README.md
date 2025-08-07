# IntraSphere - Modern Learning Management System
*Nouvelle architecture organisée par domaines métier*

## 🏗️ Architecture Nouvelle Génération

### Structure Modulaire par Domaines
```
IntraSphere/
├── 📁 frontend/          → Interface utilisateur React
│   ├── 📁 src/
│   │   ├── 📁 components/
│   │   │   ├── 📁 ui/          → Composants shadcn/ui
│   │   │   ├── 📁 layout/      → Composants de mise en page  
│   │   │   └── 📁 dashboard/   → Composants métier
│   │   ├── 📁 features/        → Pages organisées par domaine
│   │   │   ├── 📁 auth/        → Authentification
│   │   │   ├── 📁 admin/       → Administration
│   │   │   ├── 📁 content/     → Gestion contenu
│   │   │   └── 📁 training/    → Formation et cours
│   │   └── 📁 core/            → Hooks et utilitaires
│   └── 📁 public/              → Assets statiques
├── 📁 backend/           → API Node.js/Express  
│   ├── 📁 src/
│   │   ├── 📁 routes/          → Endpoints API
│   │   ├── 📁 services/        → Logique métier
│   │   ├── 📁 middleware/      → Auth/Sécurité/Logs
│   │   └── 📁 data/            → Storage et modèles
│   └── 📁 migrations/          → Migrations base de données
├── 📁 shared/            → Types TypeScript partagés
└── 📁 config/            → Configuration globale
```

## ✨ Avantages de cette Architecture

### Pour le Développement
- **🎯 Séparation claire** - Frontend/Backend complètement isolés
- **📦 Organisation par domaine** - Features regroupées logiquement  
- **🔄 Réutilisabilité** - Composants et services modulaires
- **👥 Travail en équipe** - Spécialisation frontend/backend possible

### Pour la Maintenance
- **🔍 Localisation facile** - Trouvez rapidement ce que vous cherchez
- **📈 Scalabilité** - Ajoutez des features sans collision
- **🧪 Tests isolés** - Testez par domaine métier
- **🚀 Déploiement flexible** - Frontend statique + API séparée possible

## 🚀 Démarrage Rapide

### Développement Local
```bash
# Installation des dépendances
npm install

# Frontend (Port 3000)
cd frontend && npm run dev

# Backend (Port 5000)  
cd backend && npm run dev

# Ou les deux simultanément
npm run dev
```

### Base de Données
```bash
# Migration de la base
npm run db:push

# Génération des types
npm run db:generate
```

## 📋 Features par Domaine

### 🔐 Auth (/features/auth/)
- Connexion/Déconnexion
- Gestion sessions
- Réinitialisation mots de passe

### 👨‍💼 Admin (/features/admin/)
- Gestion établissements
- Administration utilisateurs  
- Supervision système
- Mises à jour plateforme

### 📝 Content (/features/content/)
- Éditeur WYSIWYG
- Personnalisation portail
- Gestion du branding
- Pages personnalisables

### 🎓 Training (/features/training/)
- Catalogue des cours
- Évaluations et examens
- Groupes d'étude
- Manuel utilisateur

## 🛠️ Stack Technique

### Frontend
- **React 18** + TypeScript
- **Vite** - Build tool moderne
- **TanStack Query** - Gestion état serveur
- **Shadcn/UI** - Composants UI
- **Tailwind CSS** - Styling utilitaire

### Backend  
- **Node.js** + Express + TypeScript
- **Drizzle ORM** - Mapping objet-relationnel
- **PostgreSQL** - Base de données
- **Socket.IO** - Temps réel
- **Architecture en couches** - Routes → Services → Data

### Infrastructure
- **Multi-tenant** - Support multi-établissement
- **WebSocket** - Fonctionnalités temps réel
- **Session management** - Authentification robuste
- **File upload** - Gestion des fichiers

## 📈 Migration Réussie

Cette nouvelle structure remplace l'ancienne organisation `client/server` par une approche moderne et scalable :

- ✅ **47 composants UI** organisés par catégorie
- ✅ **16 pages** réorganisées par domaine métier  
- ✅ **4 services métier** avec séparation des responsabilités
- ✅ **98+ méthodes** d'accès aux données optimisées
- ✅ **23 tables** de base de données multi-tenant

## 🔧 Configuration

Toute la configuration est centralisée dans `/config/` :
- `drizzle.config.ts` - Configuration base de données
- `tailwind.config.ts` - Styles et thèmes  
- `vite.config.ts` - Build et développement
- `components.json` - Configuration Shadcn/UI

---
*IntraSphere - Learning Management System avec architecture moderne par domaines métier*
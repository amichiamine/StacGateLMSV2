# 🏗️ Architecture IntraSphere - État Actuel
*Scan complet effectué le 07/08/2025*

## 📊 Vue d'ensemble
✅ **Duplication `shared/shared` corrigée**  
✅ **Structure moderne par domaines métier**  
✅ **Frontend/Backend séparés**  
✅ **Configuration centralisée**  

## 🗂️ Structure Détaillée

### 📁 Frontend (/frontend/)
```
frontend/
├── src/
│   ├── App.tsx              # Router principal
│   ├── main.tsx             # Point d'entrée React
│   ├── index.css            # Styles globaux
│   ├── components/          # Composants UI
│   │   ├── ui/             # 47+ composants Shadcn/UI
│   │   ├── layout/         # Mise en page (navigation, footer)
│   │   └── dashboard/      # Composants métier
│   ├── features/           # Pages par domaine métier
│   │   ├── auth/           # 🔐 Authentification
│   │   ├── admin/          # 👨‍💼 Administration (4 pages)
│   │   ├── content/        # 📝 Gestion contenu (3 pages)
│   │   ├── training/       # 🎓 Formation (4 pages)
│   │   ├── dashboard.tsx   # 📊 Tableau de bord
│   │   ├── home.tsx        # 🏠 Page accueil
│   │   ├── landing.tsx     # 🌟 Page landing
│   │   └── archive-export.tsx # 📦 Export archives
│   └── core/               # Utilitaires centralisés
│       ├── hooks/          # 4 hooks personnalisés
│       └── lib/            # QueryClient, utils, auth
├── public/                 # Assets statiques
├── package.json           # Config frontend dédiée
├── vite.config.ts         # Build Vite optimisé
└── tailwind.config.ts     # Styles Tailwind
```

### 📁 Backend (/backend/)
```
backend/
├── src/
│   ├── index.ts           # Serveur Express principal
│   ├── db.ts              # Connexion base de données
│   ├── init-database.ts   # Initialisation DB
│   ├── routes/            # Endpoints API
│   │   ├── routes.ts      # Routes principales
│   │   └── index.ts       # Export centralisé
│   ├── services/          # Logique métier
│   │   ├── AuthService.ts         # 🔐 Authentification
│   │   ├── CourseService.ts       # 📚 Gestion cours
│   │   ├── EstablishmentService.ts # 🏢 Établissements
│   │   └── NotificationService.ts # 📧 Notifications
│   ├── middleware/        # Sécurité et auth
│   │   └── auth.ts        # Middleware authentification
│   └── data/              # Couche d'accès données
│       ├── storage.ts     # Interface storage
│       └── database-manager.ts # Gestionnaire multi-tenant
├── migrations/            # Migrations DB
└── package.json          # Config backend dédiée
```

### 📁 Configuration (/config/)
```
config/
├── drizzle.config.ts      # Configuration Drizzle ORM
├── tailwind.config.ts     # Thèmes et variables CSS
├── vite.config.ts         # Build et développement
├── components.json        # Config Shadcn/UI
├── tsconfig.json          # TypeScript global
└── postcss.config.js      # PostCSS processing
```

### 📁 Types Partagés (/shared/)
```
shared/
└── schema.ts             # ✅ Schémas Drizzle + validations Zod
                         # 23 tables, relations multi-tenant
                         # Types insert/select pour cohérence
```

## 🔄 États des Migrations

### ✅ **Terminé**
- **Frontend** → Structure par features organisée
- **Backend** → Architecture en couches respectée  
- **Shared** → Duplication corrigée (plus de `/shared/shared/`)
- **Config** → Centralisée dans `/config/`

### 🚧 **En Transition (Legacy)**
- **`/client/`** → Ancienne structure (à supprimer après validation)
- **`/server/`** → Anciens fichiers (à supprimer après validation)

### ⚠️ **À Corriger**
- **LSP Errors** → 5 erreurs restantes dans backend
- **Imports** → Paths à mettre à jour pour nouvelle structure
- **Workflow** → Configuration pour architecture séparée

## 🎯 Domaines Métier Organisés

### 🔐 **Auth Domain** (`/features/auth/`)
- Connexion/Déconnexion
- Gestion sessions utilisateur
- Réinitialisation mots de passe

### 👨‍💼 **Admin Domain** (`/features/admin/`)
- **admin.tsx** → Interface administration principale
- **super-admin.tsx** → Gestion globale plateforme
- **user-management.tsx** → CRUD utilisateurs
- **system-updates.tsx** → Mises à jour système

### 📝 **Content Domain** (`/features/content/`)
- **portal.tsx** → Personnalisation portail
- **wysiwyg-editor.tsx** → Éditeur de contenu
- **establishment.tsx** → Configuration établissement

### 🎓 **Training Domain** (`/features/training/`)
- **courses.tsx** → Catalogue et gestion cours
- **assessments.tsx** → Évaluations et examens
- **study-groups.tsx** → Collaboration apprenants
- **user-manual.tsx** → Documentation utilisateur

## 🔧 Avantages Architecture Actuelle

### **Développement**
- ✅ **Séparation nette** Frontend ↔ Backend
- ✅ **Organisation par domaine** - Localisation facile
- ✅ **Composants réutilisables** - UI centralisée
- ✅ **Types partagés** - Cohérence garantie

### **Maintenance**
- ✅ **Scalabilité** - Ajout features sans collision
- ✅ **Tests ciblés** - Isolation par domaine
- ✅ **Déploiement flexible** - Frontend statique possible

### **Équipe**
- ✅ **Spécialisation** - Frontend/Backend séparés
- ✅ **Parallélisation** - Développement simultané
- ✅ **Onboarding** - Structure claire et documentée

---
*Architecture IntraSphere - Moderne, Scalable, Maintenable*
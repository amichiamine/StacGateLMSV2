# 🚀 Installation StacGateLMS - Version React/Node.js

## ⚡ Installation Express (2 minutes)

### Windows (Double-clic)
```bash
# Téléchargez le projet et exécutez :
start-express.bat
```

### Linux/Mac (Une commande)
```bash
# Téléchargez le projet et exécutez :
chmod +x start-express.sh
./start-express.sh
```

### Installation Interactive
```bash
# Lancement de l'assistant d'installation
npm run install:express

# Ou étape par étape :
npm run check:requirements
npm run setup:env
npm run db:push
npm run seed:admin
npm run dev
```

## 📋 Prérequis

### Système requis
- **Node.js 18+** avec NPM
- **PostgreSQL** (local, Docker, ou cloud)
- **Git** (optionnel)
- **Docker** (optionnel pour PostgreSQL)

### Vérification automatique
```bash
# Diagnostic complet du système
node scripts/check-requirements.js
```

## 🗄️ Configuration Base de Données

### Option 1: PostgreSQL Docker (Recommandé pour développement)
```bash
# Démarrage automatique avec Docker Compose
docker-compose -f docker-compose.dev.yml up -d

# Variables d'environnement automatiques :
DATABASE_URL=postgresql://stacgate:stacgate123@localhost:5433/stacgatelms
```

### Option 2: PostgreSQL Local
```bash
# Installation PostgreSQL
# Ubuntu/Debian: sudo apt install postgresql postgresql-contrib
# macOS: brew install postgresql
# Windows: https://www.postgresql.org/download/windows/

# Configuration dans .env :
DATABASE_URL=postgresql://username:password@localhost:5432/stacgatelms
```

### Option 3: Service Cloud
```bash
# Services supportés :
# - Neon (https://neon.tech/)
# - Supabase (https://supabase.com/)
# - Railway (https://railway.app/)
# - PlanetScale MySQL (avec adaptateur)

# Configuration dans .env :
DATABASE_URL=postgresql://user:pass@host:5432/database
```

## ⚙️ Installation Détaillée

### 1. Clone et Installation
```bash
# Clonage du repository
git clone <repository-url>
cd stacgatelms

# Installation des dépendances
npm install
```

### 2. Configuration Environnement
```bash
# Génération du fichier .env
node scripts/setup-environment.js

# Ou création manuelle :
cp .env.example .env
# Éditez .env avec vos paramètres
```

### 3. Base de Données
```bash
# Démarrage PostgreSQL Docker (optionnel)
docker-compose -f docker-compose.dev.yml up -d

# Migration des tables
npm run db:push

# Création du compte super admin
node scripts/seed-admin.js

# Installation des données de démo (optionnel)
node scripts/seed-demo.js
```

### 4. Démarrage
```bash
# Mode développement (frontend + backend)
npm run dev

# Ou séparément :
npm run dev:server    # Backend uniquement
npm run dev:client    # Frontend uniquement
```

## 🛠️ Scripts Disponibles

### Installation et Configuration
```bash
npm run install:express    # Assistant d'installation interactif
npm run check:requirements # Vérification des prérequis
npm run setup:env          # Configuration .env interactive
```

### Base de Données
```bash
npm run db:push            # Migration des tables
npm run db:studio          # Interface d'administration Drizzle
npm run seed:admin         # Création compte super admin
npm run seed:demo          # Installation données de démo
node scripts/reinit-database.js  # Réinitialisation complète
```

### Développement
```bash
npm run dev               # Développement complet
npm run build             # Build de production
npm run start:production  # Démarrage production
npm test                  # Tests unitaires
npm run lint              # Vérification code
```

## 🌐 Déploiement Production

### Variables d'Environnement Critiques
```env
NODE_ENV=production
DATABASE_URL=postgresql://user:pass@host:port/db
JWT_SECRET=your-super-secret-key
SESSION_SECRET=another-secret-key
APP_URL=https://votre-domaine.com
```

### Déploiement Vercel/Netlify
```bash
# Build optimisé
npm run build

# Variables d'environnement à configurer :
# - DATABASE_URL
# - JWT_SECRET
# - SESSION_SECRET
# - APP_URL
```

### Déploiement VPS/Serveur
```bash
# Installation Node.js sur serveur
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Clonage et configuration
git clone <repo> && cd stacgatelms
npm install --production
npm run build

# Démarrage avec PM2
npm install -g pm2
pm2 start npm --name "stacgate" -- run start:production
pm2 startup
pm2 save
```

### Docker Production
```bash
# Construction de l'image
docker build -t stacgatelms .

# Démarrage avec docker-compose
docker-compose up -d
```

## 🔧 Dépannage

### Erreurs Communes

#### "Node.js non trouvé"
```bash
# Vérification de l'installation
node --version  # Doit être 18+
npm --version

# Installation si nécessaire :
# https://nodejs.org/
```

#### "Database connection failed"
```bash
# Vérification PostgreSQL
psql --version

# Test de connexion
psql -h localhost -p 5432 -U username -d database

# Démarrage Docker si utilisé
docker-compose -f docker-compose.dev.yml up -d postgres
```

#### "Port already in use"
```bash
# Ports par défaut :
# - Backend: 5000
# - Frontend: 3000
# - PostgreSQL Docker: 5433

# Vérification des ports
lsof -i :5000
lsof -i :3000

# Modification dans .env si nécessaire
PORT=5001
VITE_PORT=3001
```

### Logs et Diagnostic
```bash
# Vérification complète
node scripts/check-requirements.js

# Logs de l'application
tail -f logs/app.log

# Logs Docker PostgreSQL
docker logs stacgate-postgres-dev

# Statut des services
docker-compose -f docker-compose.dev.yml ps
```

## 📊 Interface d'Administration

### Accès aux Outils
- **Application** : http://localhost:3000
- **API Backend** : http://localhost:5000
- **Documentation API** : http://localhost:5000/api-docs
- **Drizzle Studio** : `npm run db:studio`
- **Adminer PostgreSQL** : http://localhost:8081 (si Docker)

### Comptes par Défaut
- **Super Admin** : Configuré lors de l'installation
- **Comptes de démo** : Si données de démo installées
  - admin@stacgate.academy / demo123
  - marie.formateur@stacgate.academy / demo123
  - sophie.apprenant@stacgate.academy / demo123

## 🎯 Fonctionnalités Disponibles

### Core LMS
- ✅ Multi-établissements avec isolation des données
- ✅ Gestion utilisateurs avec 5 rôles (Super Admin, Admin, Manager, Formateur, Apprenant)
- ✅ Création et gestion de cours
- ✅ Système d'évaluations et quiz
- ✅ Groupes d'étude collaboratifs
- ✅ Suivi des progressions en temps réel

### Interface et UX
- ✅ Design moderne avec glassmorphism
- ✅ Interface responsive (mobile-first)
- ✅ Thèmes personnalisables
- ✅ Mode sombre/clair
- ✅ Notifications en temps réel

### Technique
- ✅ API REST complète (35+ endpoints)
- ✅ Architecture multi-tenant native
- ✅ Sécurité enterprise-grade
- ✅ Tests automatisés complets
- ✅ Documentation OpenAPI/Swagger

## 📞 Support et Communauté

### Ressources
- 📖 **Documentation** : Incluse dans l'application
- 🔧 **Centre d'aide** : `/help-center`
- ⚙️ **Administration** : `/admin` et `/super-admin`

### Développement
- 🐛 **Issues** : Signalement de bugs
- 💡 **Discussions** : Suggestions et améliorations
- 📝 **Contributions** : Guide de contribution
- 🧪 **Tests** : Suite de tests complète

---

**🎉 Installation terminée !**  
*Votre plateforme d'apprentissage moderne est opérationnelle*

**📱 Accès rapide :**
- Frontend: http://localhost:3000
- Backend: http://localhost:5000
- Documentation: http://localhost:5000/api-docs
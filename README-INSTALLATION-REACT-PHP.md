# 🚀 Installation StacGateLMS React/Node.js - Interface PHP

## ⚡ Installation Express via PHP (30 secondes)

Cette méthode utilise une **interface PHP** pour installer StacGateLMS React/Node.js, garantissant une **compatibilité universelle** même sur les hébergements qui ne supportent pas Node.js directement.

### Windows (Double-clic)
```bash
# Téléchargez le projet et exécutez :
start-express-php.bat
```

### Linux/Mac (Une commande)
```bash
# Téléchargez le projet et exécutez :
chmod +x start-express-php.sh
./start-express-php.sh
```

### Installation Interactive Web
```bash
# Ou démarrez manuellement le serveur PHP :
php -S localhost:8000 -t . scripts/install-wizard.php
# Puis accédez à : http://localhost:8000
```

## 🎯 **Avantages de l'Interface PHP**

### ✅ **Compatibilité Universelle**
- Fonctionne sur **tous les hébergements web** (même sans Node.js)
- Compatible avec **XAMPP**, **WAMP**, **MAMP**
- Support **cPanel**, **Plesk**, hébergement mutualisé
- Requiert seulement **PHP 7.4+**

### ✅ **Installation Simplifiée**
- **Interface web intuitive** avec assistant guidé
- **Vérification automatique** des prérequis système
- **Configuration assistée** de la base de données
- **Génération automatique** des fichiers de configuration

### ✅ **Fonctionnalités Avancées**
- **Assistant d'installation** complet en 7 étapes
- **Configuration .env** automatique avec secrets sécurisés
- **Scripts de déploiement** pour Vercel, Railway, VPS, Docker
- **Support multi-base de données** (PostgreSQL local/Docker/Cloud)

## 📋 **Prérequis Système**

### **Requis (Critique)**
- **PHP 7.4+** avec extensions de base
- **Permissions d'écriture** dans le dossier du projet

### **Optionnel (Pour finalisation)**
- **Node.js 18+** pour finaliser l'installation
- **PostgreSQL** ou **Docker** pour la base de données
- **Git** pour le versioning

### **Vérification Automatique**
L'interface PHP vérifie automatiquement tous les prérequis et propose des solutions en cas de problème.

## 🌐 **Interface Web - Fonctionnalités**

### **Page d'Accueil**
- Présentation des fonctionnalités StacGateLMS
- Guide d'installation étape par étape
- Liens vers la documentation et le support

### **Assistant d'Installation** (`scripts/install-wizard.php`)
1. **Bienvenue** - Présentation du processus
2. **Prérequis** - Vérification système automatique
3. **Environnement** - Configuration Node.js/NPM
4. **Base de données** - Configuration PostgreSQL
5. **Application** - Paramètres généraux et compte admin
6. **Installation** - Génération des fichiers de configuration
7. **Finalisation** - Instructions de démarrage

### **Vérification Prérequis** (`scripts/check-requirements.php`)
- Diagnostic complet du système
- Vérification PHP, Node.js, NPM, Docker, PostgreSQL
- Recommandations d'installation personnalisées
- Export JSON pour intégration

### **Configuration Environnement** (`scripts/setup-environment.php`)
- Interface de configuration .env interactive
- Support multi-base de données
- Génération automatique des secrets
- Validation des paramètres

### **Déploiement Automatique** (`scripts/deploy-react.php`)
- Configuration pour Vercel, Railway, Render, VPS, Docker
- Génération des fichiers de déploiement
- Scripts d'installation automatisés
- Configuration Nginx et SSL

## 🚀 **Processus d'Installation Détaillé**

### **Étape 1 : Préparation**
```bash
# Téléchargez et extrayez StacGateLMS
# Placez les fichiers dans votre dossier web
# Exemple : C:\xampp\htdocs\stacgatelms\ ou /var/www/html/stacgatelms/
```

### **Étape 2 : Démarrage Interface**
```bash
# Windows
start-express-php.bat

# Linux/Mac
./start-express-php.sh

# Manuel
php -S localhost:8000 -t . scripts/install-wizard.php
```

### **Étape 3 : Installation Guidée**
1. **Accédez à** http://localhost:8000
2. **Suivez l'assistant** d'installation (7 étapes)
3. **Configurez** votre base de données
4. **Créez** votre compte super administrateur
5. **Générez** les fichiers de configuration

### **Étape 4 : Finalisation (Si Node.js disponible)**
```bash
# L'interface génère un script de finalisation
chmod +x finalize-install.sh
./finalize-install.sh

# Ou manuellement :
npm install
npm run db:push
node scripts/seed-admin.js
npm run dev
```

## 🗄️ **Configuration Base de Données**

### **Option 1 : PostgreSQL Docker (Recommandé)**
- Configuration automatique via interface PHP
- Démarrage avec `docker-compose up -d postgres`
- URL : `postgresql://stacgate:stacgate123@localhost:5433/stacgatelms`

### **Option 2 : PostgreSQL Local**
- Configuration assistée via formulaire web
- Test de connexion automatique
- Support PostgreSQL 12+ 

### **Option 3 : Services Cloud**
- **Neon Database** : Gratuit avec 512MB
- **Supabase** : PostgreSQL avec interface d'administration
- **Railway** : Intégration native avec déploiement
- **PlanetScale** : Compatible via adaptateur

## 🌍 **Déploiement Multi-Environnement**

L'interface PHP génère automatiquement les configurations pour :

### **🔷 Vercel (Frontend + Serverless)**
- Fichiers générés : `vercel.json`, `build.sh`
- Déploiement automatique depuis GitHub
- CDN global et SSL inclus

### **🚂 Railway (Full-Stack PaaS)**
- Fichiers générés : `railway.toml`, `deploy-railway.sh`
- PostgreSQL inclus automatiquement
- Déploiement en une commande

### **🎨 Render (Alternative Gratuite)**
- Fichiers générés : `render.yaml`
- Auto-scaling et SSL gratuit
- Base de données PostgreSQL incluse

### **🖥️ VPS/Serveur Dédié**
- Fichiers générés : `install-vps.sh`, `nginx.conf`
- Installation automatisée Ubuntu/Debian
- Configuration Nginx et SSL

### **🐳 Docker (Conteneurisation)**
- Fichiers générés : `Dockerfile.prod`, `docker-compose.prod.yml`
- Stack complète avec PostgreSQL et Nginx
- Déploiement en un clic

## 🔧 **Scripts PHP Inclus**

### **Installation et Configuration**
```bash
scripts/install-wizard.php      # Assistant principal interactif
scripts/check-requirements.php  # Vérification prérequis
scripts/setup-environment.php   # Configuration .env
scripts/deploy-react.php        # Générateur de déploiement
```

### **Démarrage Automatique**
```bash
start-express-php.bat          # Script Windows
start-express-php.sh           # Script Linux/Mac
```

### **Utilisation en Ligne de Commande**
```bash
# Vérification prérequis
php scripts/check-requirements.php

# Configuration environnement
php scripts/setup-environment.php

# Interface web
php -S localhost:8000 scripts/install-wizard.php
```

## 💡 **Cas d'Usage Spécifiques**

### **🏠 Hébergement Mutualisé (cPanel)**
1. Uploadez les fichiers via File Manager
2. Accédez à `http://votre-domaine.com/scripts/install-wizard.php`
3. Suivez l'assistant d'installation
4. Configurez une base PostgreSQL externe (Neon/Supabase)

### **💻 Développement Local (XAMPP/WAMP)**
1. Placez les fichiers dans `htdocs/stacgatelms/`
2. Démarrez Apache depuis XAMPP
3. Exécutez `start-express-php.bat`
4. Installez Node.js pour finaliser

### **🌐 Serveur VPS (Ubuntu/Debian)**
1. Uploadez et exécutez `./start-express-php.sh`
2. Suivez l'installation via navigateur
3. Générez les scripts VPS automatiquement
4. Déployez avec Nginx et SSL

### **☁️ Déploiement Cloud**
1. Configurez via interface PHP locale
2. Générez les fichiers de déploiement
3. Connectez votre repository Git
4. Déployez automatiquement

## 📊 **Avantages vs Installation Node.js Directe**

| Aspect | Interface PHP | Installation Node.js |
|--------|---------------|---------------------|
| **Compatibilité** | ✅ Universelle (100%) | ⚠️ Hébergements spécialisés |
| **Simplicité** | ✅ Interface web intuitive | ⚠️ Ligne de commande |
| **Prérequis** | ✅ PHP 7.4+ seulement | ⚠️ Node.js 18+ + PostgreSQL |
| **Hébergement mutualisé** | ✅ Compatible | ❌ Non supporté |
| **Configuration** | ✅ Assistant guidé | ⚠️ Fichiers manuels |
| **Déploiement** | ✅ Scripts générés | ⚠️ Configuration manuelle |

## ⚠️ **Résolution de Problèmes**

### **PHP non trouvé**
```bash
# Vérification
php --version

# Installation Ubuntu/Debian
sudo apt install php-cli php-curl php-json php-mbstring

# Installation CentOS/RHEL
sudo yum install php-cli php-curl php-json php-mbstring

# Windows : Télécharger depuis https://www.php.net/downloads.php
```

### **Port 8000 déjà utilisé**
```bash
# Utiliser un autre port
php -S localhost:8001 scripts/install-wizard.php

# Vérifier les ports utilisés
# Windows : netstat -an | find "8000"
# Linux/Mac : lsof -i :8000
```

### **Permissions d'écriture refusées**
```bash
# Linux/Mac
sudo chown -R $USER:$USER .
chmod -R 755 .

# Windows : Exécuter en tant qu'administrateur
```

### **Interface web non accessible**
- Vérifiez que PHP fonctionne : `php --version`
- Confirmez le port : `http://localhost:8000`
- Désactivez temporairement le firewall
- Vérifiez les logs PHP pour erreurs

## 🎉 **Installation Réussie**

Une fois l'installation terminée via l'interface PHP :

### **Accès aux Applications**
- **Interface React** : http://localhost:3000
- **API Backend** : http://localhost:5000
- **Documentation** : http://localhost:5000/api-docs
- **Admin PostgreSQL** : http://localhost:8081 (si Docker)

### **Compte Administrateur**
- Email : Configuré lors de l'installation
- Mot de passe : Défini lors de l'installation
- Rôle : Super Administrateur

### **Prochaines Étapes**
1. Explorez l'interface React moderne
2. Testez l'API avec la documentation interactive
3. Configurez votre premier établissement
4. Créez vos premiers cours
5. Déployez en production

---

## 🌟 **Pourquoi l'Interface PHP ?**

L'interface PHP pour StacGateLMS React/Node.js représente une **innovation majeure** :

✨ **Universalité** : Compatible avec 100% des hébergements web  
🚀 **Simplicité** : Installation en 30 secondes via navigateur  
🔧 **Flexibilité** : Support tous environnements de développement à production  
🛡️ **Fiabilité** : Vérifications automatiques et gestion d'erreurs  
📈 **Évolutivité** : Génération automatique des configs de déploiement  

**Résultat :** StacGateLMS React accessible à tous, sans compromis sur les fonctionnalités modernes !

---

**🎯 Installation PHP terminée !**  
*Votre LMS React moderne avec interface PHP universelle*

**📞 Support :**
- 📖 Documentation complète incluse
- 🔧 Interface de diagnostic intégrée
- 💡 Centre d'aide accessible via `/help-center`
- 🌐 Compatible tous environnements web
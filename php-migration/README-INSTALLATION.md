# 🚀 Installation StacGateLMS - Version PHP

## ⚡ Installation Express (1-Click)

### Windows
```bash
# Téléchargez le projet et exécutez :
start-windows.bat
```

### Linux/Mac
```bash
# Téléchargez le projet et exécutez :
chmod +x start-linux.sh
./start-linux.sh
```

## 📋 Prérequis

### Système requis
- **PHP 8.1+** avec extensions : PDO, mbstring, json, session
- **Base de données** : MySQL 5.7+, PostgreSQL 13+, ou SQLite (intégré)
- **Serveur web** : Apache, Nginx, ou serveur PHP intégré

### Extensions PHP recommandées
- `pdo_mysql` - Support MySQL/MariaDB
- `pdo_pgsql` - Support PostgreSQL
- `pdo_sqlite` - Support SQLite (inclus par défaut)
- `openssl` - Sécurité et chiffrement
- `curl` - Requêtes HTTP
- `gd` ou `imagick` - Manipulation d'images

## 🎯 Installation Manuelle

### 1. Téléchargement
```bash
# Via Git
git clone https://github.com/stacgate/lms-php.git
cd lms-php

# Ou téléchargez et extrayez l'archive ZIP
```

### 2. Vérification des prérequis
```bash
php install/check-requirements.php
```

### 3. Configuration (optionnelle)
```bash
# Créer le fichier .env
php install/create-env.php

# Ou copiez et modifiez :
cp .env.example .env
```

### 4. Installation avec Composer (recommandée)
```bash
composer install --no-dev --optimize-autoloader
```

### 5. Installation via interface web
```bash
# Démarrez un serveur local
php -S localhost:8000

# Accédez à l'installateur
http://localhost:8000/install.php
```

## 🌐 Déploiement Production

### Hébergement cPanel
1. **Uploadez** tous les fichiers dans `public_html/`
2. **Accédez** à `http://votre-domaine.com/install.php`
3. **Suivez** l'assistant d'installation
4. **Supprimez** le dossier `install/` après installation

### Serveur VPS/Dédié
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.1 php8.1-cli php8.1-pdo php8.1-mbstring php8.1-mysql

# Configuration Apache/Nginx
# Pointez DocumentRoot vers le dossier du projet
```

### Docker
```bash
# Utilisation du fichier docker-compose.yml
docker-compose up -d
```

## ⚙️ Configuration

### Base de données
Le système supporte 3 types de bases de données :

#### SQLite (Recommandé pour débuter)
```env
DB_TYPE=sqlite
DB_NAME=database.sqlite
```

#### MySQL/MariaDB
```env
DB_TYPE=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=stacgatelms
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe
```

#### PostgreSQL
```env
DB_TYPE=postgresql
DB_HOST=localhost
DB_PORT=5432
DB_NAME=stacgatelms
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe
```

### Variables d'environnement importantes
```env
# Application
APP_NAME="Votre LMS"
APP_URL=https://votre-domaine.com
APP_ENV=production

# Sécurité
SESSION_SECRET=votre-clé-secrète-unique
CSRF_SECRET=votre-clé-csrf

# Email
MAIL_HOST=smtp.votre-serveur.com
MAIL_PORT=587
MAIL_USERNAME=votre-email
MAIL_PASSWORD=votre-mot-de-passe
```

## 🔧 Dépannage

### Erreurs courantes

#### "Extension PDO manquante"
```bash
# Ubuntu/Debian
sudo apt install php8.1-pdo

# CentOS/RHEL
sudo yum install php-pdo
```

#### "Dossier non accessible en écriture"
```bash
# Linux/Mac
chmod 755 cache logs uploads
chown www-data:www-data cache logs uploads

# Ou via interface web
# Créez les dossiers et configurez les permissions
```

#### "Base de données inaccessible"
1. Vérifiez les identifiants dans `.env`
2. Testez la connexion via l'installateur web
3. Assurez-vous que le serveur de base de données fonctionne

### Logs et diagnostic
```bash
# Vérification complète du système
php install/check-requirements.php

# Logs d'erreur
tail -f logs/error.log

# Logs d'application
tail -f logs/app.log
```

## 📚 Après l'installation

### Comptes par défaut
- **Super Admin** : Configuré lors de l'installation
- **Démo** (si activées) : voir documentation

### Prochaines étapes
1. **Connectez-vous** avec votre compte super administrateur
2. **Explorez** l'interface d'administration
3. **Configurez** vos établissements
4. **Créez** vos premiers cours
5. **Invitez** vos utilisateurs

### Sécurité importante
```bash
# Supprimez les fichiers d'installation
rm -rf install/
rm install.php

# Ou via .htaccess (déjà configuré automatiquement)
```

## 🆘 Support

### Documentation
- 📖 Manuel utilisateur : `/manual`
- 🔧 Centre d'aide : `/help-center`
- ⚙️ Administration : `/super-admin`

### Communauté
- 🐛 **Issues** : Signalez les bugs
- 💡 **Suggestions** : Proposez des améliorations
- 📝 **Documentation** : Contribuez à la documentation

## 🔄 Mise à jour

### Sauvegarde
```bash
# Base de données
mysqldump -u user -p database > backup.sql

# Fichiers
tar -czf backup-files.tar.gz uploads/ cache/ .env
```

### Mise à jour
```bash
# Sauvegarde
cp -r . ../backup-$(date +%Y%m%d)

# Téléchargez la nouvelle version
# Restaurez votre .env et dossier uploads/
# Relancez l'installation si nécessaire
```

---

**🎉 Votre LMS est maintenant prêt !**  
*Plateforme d'apprentissage moderne et sécurisée*
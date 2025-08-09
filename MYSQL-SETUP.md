# Configuration MySQL pour StacGateLMS

## 🔹 Version PHP (MySQL Ready)

La version PHP supporte MySQL nativement. Configurez les variables d'environnement :

```bash
# Variables d'environnement PHP
DB_TYPE=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=stacgate_lms
DB_USERNAME=votre_user
DB_PASSWORD=votre_password
DB_CHARSET=utf8mb4
```

## 🔹 Version React/Node.js (Adaptation MySQL)

### 1. Installation des dépendances MySQL
```bash
npm install mysql2
```

### 2. Variables d'environnement Node.js
```bash
# .env
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=votre_password
DB_NAME=stacgate_lms
DB_SSL=false
```

### 3. Utilisation du schéma MySQL
```typescript
// Remplacer dans server/index.ts
import { db } from "./db-mysql";
import * as schema from "@shared/schema-mysql";
```

### 4. Migration des données
```bash
# Générer les migrations MySQL
npx drizzle-kit generate --config=drizzle-mysql.config.ts

# Appliquer les migrations
npx drizzle-kit migrate --config=drizzle-mysql.config.ts
```

## 🔹 Création de la base de données MySQL

```sql
-- Créer la base de données
CREATE DATABASE stacgate_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Créer un utilisateur dédié
CREATE USER 'stacgate_user'@'localhost' IDENTIFIED BY 'password_secure';
GRANT ALL PRIVILEGES ON stacgate_lms.* TO 'stacgate_user'@'localhost';
FLUSH PRIVILEGES;
```

## 🔹 Avantages MySQL vs PostgreSQL vs SQLite

| Aspect | MySQL | PostgreSQL | SQLite |
|--------|--------|------------|--------|
| **Performance** | Excellente pour lecture | Excellente pour écriture complexe | Très rapide pour petites DB |
| **Hébergement** | Largement supporté | Cloud moderne privilégié | Fichier local |
| **Fonctionnalités** | Standard SQL + extensions | Fonctionnalités avancées | Basique mais robuste |
| **Écosystème** | Très mature, documentation riche | Modern, JSON natif | Simple, portable |

## 🔹 Scripts de migration

Pour migrer de SQLite vers MySQL :
```bash
# Export SQLite
sqlite3 database.sqlite .dump > backup.sql

# Import vers MySQL (après adaptation)
mysql -u stacgate_user -p stacgate_lms < backup_mysql.sql
```

La plateforme StacGateLMS est maintenant **compatible avec les 3 SGBD** :
- ✅ **PostgreSQL** (Production cloud)
- ✅ **MySQL** (Hébergement traditionnel) 
- ✅ **SQLite** (Développement local)
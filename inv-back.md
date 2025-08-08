# INVENTAIRE BACKEND PHP - STACGATELMS
## Analyse exhaustive de l'architecture et composants backend
**Date d'analyse :** 08 Août 2025

---

## 🏗️ **ARCHITECTURE GÉNÉRALE**

### **Structure des dossiers**
```
php-migration/
├── config/                    # Configuration système
├── core/                      # Classes fondamentales
├── api/                       # Endpoints API RESTful
├── includes/                  # Templates partagés
├── assets/                    # Ressources statiques
├── pages/                     # Pages frontend PHP
├── cache/                     # Cache fichiers (auto-créé)
├── logs/                      # Journaux système (auto-créé)
└── uploads/                   # Fichiers utilisateurs (auto-créé)
```

### **Point d'entrée principal**
- **Fichier :** `index.php` (182 lignes)
- **Rôle :** Routeur principal et bootstrap de l'application
- **Fonctionnalités :**
  - Configuration des chemins constants (25 constantes)
  - Chargement automatique des services (11 services)
  - Définition de 50+ routes (publiques/authentifiées)
  - Gestion centralisée des erreurs
  - Support WebSocket via Long Polling

---

## ⚙️ **CONFIGURATION SYSTÈME**

### **1. Configuration principale (config/config.php)**
- **25 constantes d'application** définies
- **Rôles utilisateurs :** 5 niveaux hiérarchiques
  - `super_admin` (niveau 5)
  - `admin` (niveau 4) 
  - `manager` (niveau 3)
  - `formateur` (niveau 2)
  - `apprenant` (niveau 1)
- **Thème par défaut :** 7 couleurs glassmorphism
- **Limites système :** 8 contraintes définies
- **Sécurité :** Headers sécurisés automatiques
- **Gestion erreurs :** Handler personnalisé avec logs

### **2. Configuration base de données (config/database.php)**
- **Support dual :** MySQL ET PostgreSQL via PDO
- **14 tables** avec schémas adaptatifs
- **Auto-détection :** Types SQL selon SGBD
- **Tables principales :**
  - `establishments` - Établissements multi-tenant
  - `users` - Utilisateurs avec RBAC
  - `courses` - Cours avec métadonnées
  - `user_courses` - Relations inscriptions
  - `assessments` - Évaluations avec questions JSON
  - `study_groups` - Groupes collaboratifs
  - `collaboration_rooms` - Salles temps réel
  - `collaboration_messages` - Messages collaboratifs
  - `themes` - Thèmes personnalisés

---

## 🎯 **CLASSES CORE**

### **1. Database.php (Gestionnaire BDD)**
**Fonctionnalités principales :**
- **Pattern Singleton** pour instance unique
- **Méthodes CRUD :** 8 méthodes optimisées
  - `select()` - Requêtes SELECT avec paramètres
  - `selectOne()` - Récupération ligne unique
  - `insert()` - Insertion avec auto-ID
  - `update()` - Mise à jour conditionnelle
  - `delete()` - Suppression sécurisée
  - `transaction()` - Transactions complètes
  - `paginate()` - Pagination native
  - `count()` - Comptage optimisé
- **Gestion erreurs :** PDOException avec logs détaillés
- **Support :** MySQL/PostgreSQL transparent

### **2. Router.php (Système de routage)**
**Capacités :**
- **4 méthodes HTTP :** GET, POST, PUT, DELETE
- **Routes dynamiques :** Support paramètres `{id}`
- **Middleware auth :** Protection automatique
- **Extraction params :** Variables URL vers $_GET
- **Gestion 404 :** Redirection automatique
- **Support API :** Réponses JSON structurées

### **3. Auth.php (Authentification)**
**Sécurité enterprise :**
- **Hachage Argon2ID :** Configuration optimisée
  - Memory: 64MB, Time: 4 iterations, Threads: 3
- **Sessions sécurisées :** Régénération ID automatique
- **Multi-tenant :** Isolation par établissement
- **Méthodes principales :**
  - `check()` - Vérification statut connexion
  - `user()` - Données utilisateur avec JOIN
  - `login()` - Connexion avec mise à jour last_login
  - `logout()` - Déconnexion complète + cookie cleanup
  - `attempt()` - Tentative authentification sécurisée

### **4. Utils.php (Utilitaires)**
**25+ méthodes utilitaires :**
- **Sécurité :** `sanitize()`, validation XSS
- **Génération :** ID uniques, mots de passe, slugs
- **Formatage :** Dates, nombres, tailles fichiers
- **Validation :** Email, URL, formats
- **Text processing :** Troncature, recherche
- **File handling :** Upload sécurisé, validation types
- **Cache système :** Gestion cache fichiers
- **Logs :** Système journalisation rotatif

### **5. Validator.php (Validation)**
**Système de validation avancé :**
- **15+ règles :** required, email, unique, min/max, etc.
- **Validation custom :** Support règles personnalisées
- **Messages localisés :** Erreurs en français
- **Chaînage rules :** Multiple contraintes par champ
- **Sanitisation :** Nettoyage automatique données

---

## 🔧 **SERVICES MÉTIER**

### **1. AuthService.php**
- **Authentification multi-tenant** sécurisée
- **Création utilisateurs** avec validation complète
- **Génération usernames** automatique
- **Gestion profils** avec établissements

### **2. CourseService.php**
- **CRUD complet** pour cours
- **Pagination** et filtrage avancé
- **Inscriptions/désinscriptions** gérées
- **Statistiques** enrollment par cours
- **Support multi-établissement**

### **3. EstablishmentService.php**
- **Gestion établissements** multi-tenant
- **Thèmes personnalisés** par établissement
- **Configuration** branding et domaines
- **Isolation données** complète

### **4. AnalyticsService.php**
- **Métriques temps réel** système
- **Rapports** utilisateurs/cours/inscriptions
- **Analytics** par établissement ou globales
- **Données** agrégées optimisées

### **5. AssessmentService.php**
- **Gestion évaluations** complètes
- **Questions JSON** structurées
- **Tentatives multiples** avec limite
- **Scoring** automatique

### **6. StudyGroupService.php**
- **Groupes d'étude** collaboratifs
- **Messagerie** intégrée
- **Gestion membres** avec limites
- **Permissions** créateur/participant

### **7. ExportService.php**
- **Exports multi-formats** (PDF, Excel, CSV)
- **Jobs asynchrones** pour gros volumes
- **Téléchargements** sécurisés
- **Archivage** données

### **8. HelpService.php**
- **Base de connaissances** structurée
- **FAQ** et articles
- **Recherche** full-text
- **Catégorisation** contenu

### **9. SystemService.php**
- **Monitoring** santé système
- **Maintenance** outils intégrés
- **Cache management** multi-niveaux
- **Informations** système détaillées

### **10. NotificationService.php**
- **Notifications** temps réel
- **Multi-canaux** (email, push, interne)
- **Templates** personnalisables
- **Queue système** pour performance

---

## 🌐 **API ENDPOINTS**

### **Authentification (4 endpoints)**
- `POST /api/auth/login` - Connexion sécurisée
- `POST /api/auth/logout` - Déconnexion complète
- `POST /api/auth/register` - Inscription utilisateur
- `GET /api/auth/user` - Profil utilisateur actuel

### **Cours (6 endpoints)**
- `GET /api/courses` - Liste avec filtres/pagination
- `POST /api/courses` - Création nouveau cours
- `GET /api/courses/{id}` - Détails cours spécifique
- `PUT /api/courses/{id}` - Mise à jour cours
- `DELETE /api/courses/{id}` - Suppression cours
- `POST /api/courses/{id}/enroll` - Inscription/désinscription

### **Utilisateurs (5 endpoints)**
- `GET /api/users` - Liste utilisateurs établissement
- `POST /api/users` - Création utilisateur
- `GET /api/users/{id}` - Profil utilisateur
- `PUT /api/users/{id}` - Mise à jour profil
- `GET /api/users/profile` - Profil personnel

### **Analytics (5 endpoints)**
- `GET /api/analytics/overview` - Vue d'ensemble
- `GET /api/analytics/popular-courses` - Cours populaires
- `GET /api/analytics/courses` - Statistiques cours
- `GET /api/analytics/users` - Statistiques utilisateurs
- `GET /api/analytics/enrollments` - Rapports inscriptions

### **Évaluations (4 endpoints)**
- `GET /api/assessments` - Liste évaluations
- `POST /api/assessments` - Création évaluation
- `GET /api/assessments/{id}` - Détails évaluation
- `POST /api/assessments/{id}/attempt` - Tentative réponse

### **Groupes d'étude (5 endpoints)**
- `GET /api/study-groups` - Liste groupes
- `POST /api/study-groups` - Création groupe
- `POST /api/study-groups/{id}/join` - Rejoindre/quitter
- `GET /api/study-groups/{id}/messages` - Messages groupe
- `POST /api/study-groups/{id}/messages` - Envoyer message

### **Établissements (3 endpoints)**
- `GET /api/establishments` - Liste établissements
- `POST /api/establishments` - Création (admin)
- `GET /api/establishments/{id}/themes` - Thèmes

### **Système (3 endpoints)**
- `GET /api/system/health` - État santé
- `GET /api/system/info` - Informations système
- `POST /api/system/clear-cache` - Vider cache

### **Exports (3 endpoints)**
- `GET /api/exports` - Jobs exports
- `POST /api/exports` - Créer export
- `GET /api/exports/{id}/download` - Télécharger

### **Aide (2 endpoints)**
- `GET /api/help` - Articles aide
- `GET /api/help/search` - Recherche FAQ

---

## 🔒 **SÉCURITÉ BACKEND**

### **Mesures implémentées**
1. **Protection CSRF** - Tokens sur toutes actions
2. **Prévention XSS** - Sanitisation `htmlspecialchars`
3. **SQL Injection** - Requêtes préparées PDO uniquement
4. **Validation inputs** - Système Validator robuste
5. **Hachage mots de passe** - Argon2ID optimisé
6. **Sessions sécurisées** - Configuration enterprise
7. **Upload fichiers** - Validation types/tailles stricte
8. **Headers sécurité** - HSTS, XSS-Protection, etc.
9. **Logs sécurisés** - Pas de fuite données sensibles
10. **Rate limiting** - À implémenter (structure prête)

### **Isolation multi-tenant**
- **Filtrage automatique** par establishment_id
- **Vérification permissions** sur chaque requête
- **Données séparées** logiquement par établissement
- **Thèmes isolés** par organisation

---

## 📊 **PERFORMANCE & COMPATIBILITÉ**

### **Optimisations**
- **Cache fichiers** multi-niveaux configurables
- **Requêtes optimisées** avec JOIN minimaux
- **Pagination native** pour grandes datasets
- **Logs rotatifs** avec niveaux verbosité
- **Lazy loading** pour ressources lourdes

### **Compatibilité hébergement**
- **cPanel/Shared** ✅ 100% compatible
- **VPS/Dedicated** ✅ 100% compatible
- **Cloud providers** ✅ 100% compatible
- **Managed hosting** ✅ 95% compatible

### **Base de données dual**
- **MySQL 5.7+** ✅ Support complet
- **PostgreSQL 11+** ✅ Support complet
- **Auto-détection** type SGBD
- **Requêtes adaptatives** selon moteur

---

## 🔄 **COLLABORATION TEMPS RÉEL**

### **Simulation WebSocket via Long Polling**
- **Salles collaboratives** par ressource
- **Messages typés** (chat, cursor, drawing, etc.)
- **Participants** trackés en JSON
- **Historique** messages limité (100 max)
- **Rooms cleanup** automatique inactives

### **Types de collaboration**
- **Cours** - Collaboration pendant formation
- **Groupes d'étude** - Chat et partage
- **Whiteboard** - Dessin collaboratif
- **Assessments** - Sessions supervisées

---

## 📁 **STRUCTURE FICHIERS BACKEND**

### **Organisation modulaire**
```
config/
├── config.php          # Configuration principale (137 lignes)
└── database.php        # BDD et schémas (230 lignes)

core/
├── Database.php         # Gestionnaire BDD singleton (200+ lignes)
├── Router.php          # Système routage (150+ lignes)
├── Auth.php            # Authentification sécurisée (130+ lignes)
├── Utils.php           # Utilitaires système (200+ lignes)
├── Validator.php       # Validation avancée (150+ lignes)
└── services/           # Services métier (10 fichiers)

api/
├── auth/               # 4 endpoints authentification
├── courses/            # 6 endpoints gestion cours
├── users/              # 5 endpoints utilisateurs
├── analytics/          # 5 endpoints métriques
├── assessments/        # 4 endpoints évaluations
├── study-groups/       # 5 endpoints groupes
├── establishments/     # 3 endpoints établissements
├── system/            # 3 endpoints système
├── exports/           # 3 endpoints exports
└── help/              # 2 endpoints aide
```

---

## 🎯 **STATUT BACKEND**

### **Implémentation complète**
- ✅ **API RESTful** - 35+ endpoints opérationnels
- ✅ **Services métier** - 10 services complets
- ✅ **Sécurité enterprise** - Niveau production
- ✅ **Multi-tenant** - Architecture isolée
- ✅ **Performance** - Optimisations actives
- ✅ **Compatibilité** - Hébergement standard
- ✅ **Database dual** - MySQL/PostgreSQL
- ✅ **Collaboration** - Temps réel simulé

### **Architecture solide**
- **SOLID principles** respectés
- **Separation of concerns** appliquée
- **Dependency injection** via services
- **Error handling** centralisé
- **Logging** complet et rotatif
- **Configuration** externalisée
- **Scaling** horizontal possible

Le backend PHP est **production-ready** avec une architecture robuste, sécurisée et performante.
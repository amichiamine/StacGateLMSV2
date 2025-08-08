# INVENTAIRE EXHAUSTIF - BACKEND API (StacGateLMS)
*Analyse complète de l'architecture serveur, routes API, middleware et base de données*

## 📁 STRUCTURE GÉNÉRALE DU BACKEND

### Dossier racine `/server`
```
server/
├── index.ts              # Point d'entrée principal du serveur
├── routes.ts             # Configuration des routes et WebSocket
├── db.ts                 # Configuration base de données (Neon/Drizzle)
├── storage.ts            # Interface et implémentation storage
├── vite.ts               # Configuration Vite pour développement
├── api/                  # Routes API organisées par domaine
├── middleware/           # Middleware Express personnalisés
├── websocket/            # Gestion WebSocket et collaboration
└── docs/                 # Documentation Swagger
```

## 🚀 SERVEUR PRINCIPAL

### Point d'entrée (`index.ts`)
**Technologies** : Express.js, Node.js, TypeScript
- **Middleware appliqués** :
  - `setupSecurity()` (Helmet, Compression)
  - `requestMonitoring()` (logs de performance)
  - `apiLimiter` (rate limiting sur /api)
  - Body parsing (JSON/URL-encoded, limite 10MB)
- **Configuration** :
  - Logging automatique des requêtes API
  - Gestion d'erreurs globale
  - Port configuré via `process.env.PORT` (défaut: 5000)
  - Host: `0.0.0.0` pour accessibilité réseau

### Configuration des routes (`routes.ts`)
**Fonctionnalités principales** :
- **Session management** : `express-session` avec configuration sécurisée
- **API mounting** : Toutes les routes API sous `/api`
- **WebSocket server** : Path `/ws/collaboration` pour temps réel
- **Collaboration manager** : Gestion des rooms et participants

#### Configuration de session :
```typescript
{
  secret: process.env.SESSION_SECRET,
  resave: false,
  saveUninitialized: false,
  name: 'stacgate.sid',
  cookie: { 
    secure: false, // HTTPS en production
    httpOnly: false, // Compatibilité browser
    maxAge: 24 * 60 * 60 * 1000, // 24h
    sameSite: 'lax'
  },
  rolling: true // Extension session sur requête
}
```

## 🔌 API ROUTES STRUCTURE

### Router principal (`/server/api/index.ts`)
**Domaines organisés** :
1. **auth** (`/api/auth/*`) - Authentification et autorisation
2. **establishments** (`/api/establishments/*`) - Gestion établissements
3. **courses** (`/api/courses/*`) - Gestion des cours
4. **users** (`/api/users/*`) - Gestion utilisateurs
5. **analytics** (`/api/analytics/*`) - Analytics et rapports
6. **exports** (`/api/exports/*`) - Exports de données
7. **study-groups** (`/api/study-groups/*`) - Groupes d'étude
8. **help** (`/api/help/*`) - Documentation et aide
9. **system** (`/api/system/*`) - Mises à jour système
10. **assessments** (`/api/assessments/*`) - Évaluations

### Routes spéciales intégrées :
- **Documentation help** : `/api/documentation/help`
- **Search documentation** : `/api/documentation/search`
- **Admin portal customization** : `/api/admin/portal-*`
- **Super admin portals** : `/api/super-admin/portal-*`
- **Export facilities** : `/api/export/*`

## 🗄️ BASE DE DONNÉES ET STORAGE

### Configuration DB (`db.ts`)
**Technologies** : Neon Serverless PostgreSQL + Drizzle ORM
- **Driver** : `@neondatabase/serverless`
- **WebSocket** : Configuration `neonConfig.webSocketConstructor = ws`
- **Pool de connexions** : Pool configuré avec `DATABASE_URL`
- **Schema** : Import global depuis `@shared/schema`

### Interface Storage (`storage.ts`)
**Pattern** : Repository pattern avec interface `IStorage`

#### Opérations par domaine :

**Establishments** :
- `getEstablishment(id)`, `getEstablishmentBySlug(slug)`
- `createEstablishment()`, `updateEstablishment()`
- `getAllEstablishments()`, `getEstablishments()`

**Users** :
- `getUser(id)`, `getUserByUsername()`, `getUserByEmail()`
- `createUser()`, `updateUser()`, `deleteUser()`
- `getUsersByEstablishment()`, `getUsersWithEstablishment()`
- `searchUsers()`, `getUserPermissions()`

**Courses** :
- `getCourse(id)`, `getCoursesByEstablishment()`
- `createCourse()`, `updateCourse()`, `deleteCourse()`
- `searchCourses()`, `getCourseWithDetails()`

**User-Course Relations** :
- `enrollUserInCourse()`, `unenrollUserFromCourse()`
- `getUserCourses()`, `getCourseUsers()`
- `updateUserCourseProgress()`

**Themes & Customization** :
- `getThemes()`, `createTheme()`, `updateTheme()`
- `getCustomizableContents()`, `updateCustomizableContent()`
- `getMenuItems()`, `createMenuItem()`, `updateMenuItem()`

**Advanced Features** :
- **Assessments** : CRUD complet des évaluations
- **Study Groups** : Gestion collaborative des groupes
- **Analytics** : Récupération de métriques
- **Permissions** : Système de permissions granulaires
- **Notifications** : Système de notifications
- **Export Jobs** : Gestion des tâches d'export

## 🔐 MIDDLEWARE ET SÉCURITÉ

### Middleware d'authentification (`middleware/auth.ts`)
**Middleware disponibles** :

1. **`requireAuth`** - Authentification de base
   - Vérifie `req.session.userId`
   - Récupère et attache `req.user`
   - Retourne 401 si non authentifié

2. **`requireSuperAdmin`** - Super administrateur uniquement
   - Vérifie authentification + rôle `super_admin`
   - Retourne 403 si pas les bonnes permissions

3. **`requireAdmin`** - Administrateur ou super admin
   - Vérifie rôles `admin` ou `super_admin`
   - Gestion d'établissement spécifique

4. **`requireEstablishmentAccess`** - Accès établissement
   - Vérifie l'appartenance à un établissement
   - Contrôle d'accès granulaire

### Middleware de sécurité (`middleware/security.ts`)
**Composants** :

1. **Compression** :
   - Niveau 6, seuil 1024 bytes
   - Filtrage par headers `x-no-compression`

2. **Helmet Security Headers** :
   - Content Security Policy (désactivé en dev)
   - HSTS (production uniquement)
   - Frame Guard, XSS Filter, No Sniff
   - Configuration développement friendly

3. **CORS Configuration** :
   - Origins autorisées configurables via `ALLOWED_ORIGINS`
   - Credentials supportés
   - Fallback localhost en développement

### Rate Limiting (`middleware/rateLimiter.ts`)
- **Cible** : Routes `/api/*`
- **Configuration** : Basée sur express-rate-limit
- **Gestion** : Headers `X-Forwarded-For` avec trust proxy

### Monitoring (`middleware/monitoring.ts`)
- **Métriques** : Temps de réponse, status codes
- **Logging** : Performance des requêtes API
- **Format** : `{METHOD} {PATH} {STATUS} in {DURATION}ms`

## 🌐 WEBSOCKET ET COLLABORATION

### Collaboration Manager (`websocket/collaborationManager.ts`)
**Fonctionnalités** :
- **Room Management** : Création/gestion des salles de collaboration
- **User Tracking** : Suivi des participants en temps réel
- **Message Routing** : Distribution des messages par room
- **Event Handling** : Join/Leave notifications

**Types de messages** :
- `connected` - Confirmation de connexion
- `room_joined` - Utilisateur rejoint une room
- `user_joined` - Notification nouveau participant
- `user_left` - Notification départ participant
- `error` - Gestion d'erreurs WebSocket

**Paramètres de connexion** :
- `userId` (requis)
- `userName` (nom d'affichage)
- `userRole` (rôle utilisateur)
- `establishmentId` (établissement)

## 📊 ENDPOINTS API DÉTAILLÉS

### Authentication (`/api/auth/*`)
- `POST /login` - Connexion utilisateur
- `POST /register` - Inscription nouveau compte
- `POST /logout` - Déconnexion
- `GET /user` - Récupération utilisateur courant
- `GET /permissions` - Permissions utilisateur

### Establishments (`/api/establishments/*`)
- `GET /` - Liste tous les établissements
- `GET /:id` - Détails établissement par ID
- `GET /slug/:slug` - Détails par slug
- `POST /` - Création nouvel établissement
- `PUT /:id` - Mise à jour établissement

### Courses (`/api/courses/*`)
- `GET /` - Liste des cours (avec filtres)
- `GET /:id` - Détails cours spécifique
- `POST /` - Création nouveau cours
- `PUT /:id` - Modification cours
- `DELETE /:id` - Suppression cours
- `POST /:id/enroll` - Inscription à un cours

### Users (`/api/users/*`)
- `GET /` - Liste utilisateurs (avec pagination)
- `GET /:id` - Profil utilisateur
- `POST /` - Création utilisateur
- `PUT /:id` - Modification profil
- `DELETE /:id` - Suppression compte

### Analytics (`/api/analytics/*`)
- `GET /dashboard/stats` - Statistiques tableau de bord
- `GET /establishments/:id/analytics` - Analytics établissement
- `GET /establishments/:id/popular-courses` - Cours populaires
- `GET /users/:id/progress` - Progression utilisateur

### Admin Routes (`/api/admin/*`)
- `GET /portal-themes` - Thèmes portail
- `POST /portal-themes` - Création thème
- `GET /portal-contents` - Contenus personnalisables
- `PUT /portal-contents/:id` - Modification contenu
- `GET /portal-menus` - Configuration menus

### Super Admin (`/api/super-admin/*`)
- `GET /establishments` - Tous les établissements
- `GET /users` - Tous les utilisateurs
- `POST /establishments` - Création établissement
- `GET /portal-themes` - Gestion thèmes globaux

## 🔧 MIDDLEWARE STACK COMPLET

### Ordre d'application des middleware :
1. **Security** (`setupSecurity`) - Headers sécurité + compression
2. **Monitoring** (`requestMonitoring`) - Métriques performance
3. **Rate Limiting** (`apiLimiter`) - Protection DDoS sur /api
4. **Body Parsing** - JSON + URL-encoded (10MB max)
5. **Session** - Gestion des sessions utilisateur
6. **Request Logging** - Logs détaillés des API calls
7. **Routes** - Montage des routes API
8. **Error Handling** - Gestion globale des erreurs
9. **Static/Vite** - Serveur de fichiers statiques

## 📚 SCHÉMA DE BASE DE DONNÉES

### Tables principales (via Drizzle Schema) :
- **`establishments`** - Établissements d'enseignement
- **`users`** - Comptes utilisateurs
- **`courses`** - Catalogue de cours
- **`user_courses`** - Relations inscriptions
- **`themes`** - Thèmes visuels personnalisés
- **`customizable_contents`** - Contenus éditables
- **`menu_items`** - Menus de navigation
- **`assessments`** - Évaluations et quiz
- **`study_groups`** - Groupes d'étude collaboratifs
- **`notifications`** - Système de notifications
- **`permissions`** - Permissions granulaires
- **`export_jobs`** - Tâches d'export asynchrones

### Relations clés :
- Users ↔ Establishments (many-to-one)
- Users ↔ Courses (many-to-many via user_courses)
- Courses ↔ Establishments (many-to-one)
- Users ↔ Permissions (many-to-many)
- StudyGroups ↔ Users (many-to-many)

## 🛠️ OUTILS ET CONFIGURATION

### Technologies Backend :
- **Runtime** : Node.js avec TypeScript
- **Framework** : Express.js
- **Base de données** : PostgreSQL (Neon Serverless)
- **ORM** : Drizzle ORM
- **WebSocket** : ws library
- **Session** : express-session
- **Sécurité** : Helmet + compression
- **Documentation** : Swagger/OpenAPI

### Variables d'environnement requises :
- `DATABASE_URL` - Connexion PostgreSQL
- `SESSION_SECRET` - Clé de session
- `PORT` - Port d'écoute (défaut: 5000)
- `NODE_ENV` - Environnement (development/production)
- `ALLOWED_ORIGINS` - CORS origins autorisées

## 📈 FONCTIONNALITÉS AVANCÉES

### 1. **Multi-tenant Architecture**
- Isolation par établissement
- Permissions granulaires par organisation
- Personnalisation visuelle par tenant

### 2. **Real-time Collaboration**
- WebSocket intégré au serveur principal
- Gestion des rooms par ressource
- Notifications temps réel

### 3. **Analytics & Monitoring**
- Métriques de performance intégrées
- Logging automatique des API
- Dashboard de supervision

### 4. **Export System**
- Jobs asynchrones d'export
- Support multiple formats
- Gestion des tâches longues

### 5. **Permissions System**
- Contrôle d'accès basé sur les rôles
- Permissions granulaires par ressource
- Héritage d'établissement

## 🔍 PATTERNS ARCHITECTURAUX

### 1. **Repository Pattern**
- Interface `IStorage` abstraite
- Implémentation concrète avec Drizzle
- Séparation logique métier/données

### 2. **Middleware Chain**
- Composition de middleware modulaires
- Ordre d'exécution défini
- Responsabilités séparées

### 3. **Domain Organization**
- Routes organisées par domaine métier
- Séparation des préoccupations
- Modules indépendants

### 4. **Error Handling**
- Gestion centralisée des erreurs
- Codes de statut HTTP appropriés
- Messages d'erreur consistants

## 📊 MÉTRIQUES DU BACKEND

### Statistiques générales :
- **Routes API** : 50+ endpoints
- **Middleware** : 8 middleware principaux
- **Tables DB** : 15+ tables principales
- **WebSocket Events** : 6 types d'événements
- **Permissions** : Système RBAC complet
- **Security** : Headers + Rate limiting + Session

### Performance :
- **Request Logging** : Temps de réponse automatique
- **Connection Pooling** : PostgreSQL optimisé
- **Compression** : Réponses compressées
- **Caching** : Session-based caching

## 🚀 POINTS FORTS DE L'ARCHITECTURE

1. **Modularité** : Organisation claire par domaines
2. **Sécurité** : Multiple couches de protection
3. **Scalabilité** : Architecture serverless-ready
4. **Real-time** : WebSocket intégré nativement
5. **Multi-tenant** : Support établissements multiples
6. **Type Safety** : TypeScript intégral
7. **Database** : ORM moderne avec migrations
8. **Monitoring** : Logs et métriques intégrés
9. **Documentation** : API documentée avec Swagger
10. **Development** : Hot reload avec Vite intégration
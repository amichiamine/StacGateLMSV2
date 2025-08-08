# INVENTAIRE EXHAUSTIF BACKEND - VERSION REACT/NODE.JS
*Analyse détaillée de la structure backend StacGateLMS React/TypeScript*

## RÉSUMÉ EXÉCUTIF
- **Architecture** : Node.js/Express avec TypeScript
- **ORM** : Drizzle avec PostgreSQL
- **Structure** : API REST avec couche de services
- **Sessions** : express-session avec PostgreSQL
- **WebSocket** : Collaboration temps réel
- **Middleware** : Auth, CORS, logging
- **Total fichiers analysés** : 47 fichiers backend

---

## 1. ARCHITECTURE GÉNÉRALE

### 1.1 Structure de répertoires
```
server/
├── api/                    # Routes API modulaires (11 modules)
├── services/               # Couche métier (10 services)
├── middleware/             # Middleware auth
├── websocket/              # Collaboration temps réel
├── *.ts                   # Fichiers principaux (8 fichiers)
```

### 1.2 Points d'entrée
- **Principal** : `server/index.ts` - Configuration Express
- **Routes** : `server/routes.ts` - Enregistrement des routes
- **API** : `server/api/index.ts` - Router principal API
- **BD** : `server/db.ts` - Configuration Drizzle
- **Storage** : `server/storage.ts` - Interface de stockage

---

## 2. COUCHE API (Routes REST)

### 2.1 Modules API (/api/)
1. **auth/routes.ts** : Authentification et sessions
2. **establishments/routes.ts** : Gestion établissements
3. **courses/routes.ts** : Gestion des cours
4. **users/routes.ts** : Gestion utilisateurs
5. **analytics/routes.ts** : Statistiques et rapports
6. **assessments/routes.ts** : Évaluations et examens
7. **exports/routes.ts** : Export de données
8. **study-groups/routes.ts** : Groupes d'étude
9. **help/routes.ts** : Centre d'aide
10. **system/routes.ts** : Administration système
11. **api/index.ts** : Router principal avec endpoints statiques

### 2.2 Endpoints principaux
```typescript
// Authentification
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/user

// Établissements
GET    /api/establishments
POST   /api/establishments
GET    /api/establishments/:id
PUT    /api/establishments/:id

// Cours
GET    /api/courses
POST   /api/courses
GET    /api/courses/:id
PUT    /api/courses/:id

// Utilisateurs
GET    /api/users
POST   /api/users
GET    /api/users/:id
PUT    /api/users/:id

// Administration
GET    /api/admin/themes
POST   /api/admin/themes
GET    /api/admin/customizable-contents
GET    /api/admin/menu-items
GET    /api/admin/pages/:pageName
GET    /api/admin/components

// Super Admin
GET    /api/super-admin/portal-themes
GET    /api/super-admin/portal-contents
GET    /api/super-admin/portal-menu-items

// Exports
POST   /api/export/create
GET    /api/exports
GET    /api/exports/:id

// Collaboration
GET    /api/collaboration/stats
GET    /api/collaboration/rooms/:roomId

// Évaluations
GET    /api/assessments
GET    /api/assessment-attempts

// Documentation
GET    /api/documentation/help
GET    /api/documentation/search

// Système
GET    /api/health
```

---

## 3. COUCHE SERVICES (Business Logic)

### 3.1 Services métier
1. **AuthService.ts** : Authentification et hachage
2. **CourseService.ts** : Logique métier cours
3. **EstablishmentService.ts** : Gestion établissements
4. **AnalyticsService.ts** : Calculs statistiques
5. **AssessmentService.ts** : Évaluations
6. **ExportService.ts** : Génération exports
7. **HelpService.ts** : Contenu d'aide
8. **StudyGroupService.ts** : Groupes d'étude
9. **SystemService.ts** : Administration système
10. **NotificationService.ts** : Notifications
11. **services/index.ts** : Export centralisé

### 3.2 Fonctionnalités clés par service

#### AuthService
```typescript
- authenticateUser(email, password, establishmentId)
- hashPassword(password)
- createUser(userData)
- updateUserPassword(userId, newPassword)
- verifyPermission(user, requiredRole)
```

#### CourseService
```typescript
- getCoursesForUser(user)
- createCourse(courseData, creatorId)
- approveCourse(courseId, approvedBy)
- getCourseStatistics(establishmentId)
- enrollUserInCourse(userId, courseId)
```

#### EstablishmentService
```typescript
- createEstablishment(data)
- getEstablishmentStats(id)
- updateSettings(id, settings)
- validateSlug(slug)
```

---

## 4. COUCHE DONNÉES (Storage & ORM)

### 4.1 Interface IStorage (storage.ts)
- **Taille** : 500+ lignes d'interface
- **Opérations** : 80+ méthodes CRUD
- **Modules** : Establishments, Users, Courses, Themes, etc.

### 4.2 Catégories d'opérations
1. **Establishment operations** (8 méthodes)
2. **User operations** (12 méthodes)
3. **Course operations** (15 méthodes)
4. **Theme operations** (6 méthodes)
5. **Content operations** (8 méthodes)
6. **Assessment operations** (10 méthodes)
7. **Export operations** (5 méthodes)
8. **Analytics operations** (6 méthodes)
9. **Study Group operations** (12 méthodes)
10. **Notification operations** (4 méthodes)

### 4.3 Schéma de base de données (shared/schema.ts)
- **Tables principales** : 25+ tables
- **Enums** : 5 types énumérés
- **Relations** : Clés étrangères complexes
- **Types** : Insert/Select schemas avec Zod

```typescript
// Tables principales
- establishments
- users
- courses
- themes
- customizable_contents
- assessments
- study_groups
- notifications
- export_jobs
- help_contents
- system_versions
- establishment_branding
- whiteboards
- permissions
```

---

## 5. MIDDLEWARE ET SÉCURITÉ

### 5.1 Middleware Auth (middleware/auth.ts)
- Vérification des sessions
- Contrôle d'accès basé sur les rôles
- Protection des routes sensibles

### 5.2 Configuration Session
```typescript
// Session express avec PostgreSQL
- Secret key configurable
- Durée : 24 heures
- Rolling sessions
- Cookie settings optimisés
```

### 5.3 Sécurité
- Hash bcrypt (12 rounds)
- Sessions sécurisées
- Validation Zod
- Protection CSRF

---

## 6. WEBSOCKET ET TEMPS RÉEL

### 6.1 Collaboration Manager
- **Fichier** : `websocket/collaborationManager.ts`
- **Fonctionnalités** :
  - Gestion des salles
  - Messages temps réel
  - Présence utilisateurs
  - Tableau blanc collaboratif

### 6.2 Endpoints WebSocket
```
ws://host/ws/collaboration
```

### 6.3 Types de messages
- Connection/disconnection
- Room join/leave
- Real-time messaging
- Whiteboard updates
- User presence

---

## 7. CONFIGURATION ET INFRASTRUCTURE

### 7.1 Fichiers de configuration
- **drizzle.config.ts** : Configuration ORM
- **server/vite.ts** : Intégration Vite
- **server/db.ts** : Connexion PostgreSQL

### 7.2 Variables d'environnement
```
DATABASE_URL
SESSION_SECRET
PORT (défaut: 5000)
NODE_ENV
```

### 7.3 Scripts de base de données
- **server/init-database.ts** : Initialisation
- **server/database-manager.ts** : Gestionnaire
- **scripts/reinit-database.js** : Réinitialisation

---

## 8. GESTION DES ERREURS ET LOGGING

### 8.1 Gestion d'erreurs
- Middleware global d'erreur
- Status codes appropriés
- Messages d'erreur structurés
- Logging des erreurs

### 8.2 Logging API
- Requêtes API tracées
- Durée des requêtes
- Réponses JSON capturées
- Format standardisé

---

## 9. FONCTIONNALITÉS AVANCÉES

### 9.1 Multi-tenant
- Établissements isolés
- Données contextualisées
- Thèmes personnalisés
- Contenu modulable

### 9.2 WYSIWYG et personnalisation
- Pages customizables
- Composants modulaires
- Thèmes visuels
- Contenu dynamique

### 9.3 Système d'exports
- Jobs asynchrones
- Formats multiples
- Suivi du progrès
- Téléchargements sécurisés

### 9.4 Analytics
- Statistiques détaillées
- Rapports multi-niveaux
- Métriques temps réel
- Tableaux de bord

---

## 10. COMPARAISON AVEC VERSION PHP

### 10.1 Avantages version Node.js
- **TypeScript** : Typage fort, meilleure maintenabilité
- **Drizzle ORM** : Requêtes type-safe, migrations automatiques
- **Architecture modulaire** : Services séparés, responsabilités claires
- **WebSocket natif** : Collaboration temps réel intégrée
- **Middleware moderne** : Express ecosystem riche

### 10.2 Complexité accrue
- **Structure plus sophistiquée** : 47 fichiers vs 25 PHP
- **Couches multiples** : API/Services/Storage vs direct PHP
- **Configuration avancée** : TypeScript, Drizzle, WebSocket
- **Dépendances nombreuses** : npm ecosystem

---

## 11. RECOMMANDATIONS TECHNIQUES

### 11.1 Points forts à conserver
1. Architecture en couches claire
2. Séparation API/Services/Storage
3. TypeScript pour la robustesse
4. WebSocket pour le temps réel
5. ORM moderne avec Drizzle

### 11.2 Améliorations possibles
1. Documentation API automatique (Swagger)
2. Tests unitaires et d'intégration
3. Rate limiting et throttling
4. Cache Redis pour performances
5. Monitoring et métriques

### 11.3 Migration considerations
- **Complexité** : Version Node.js plus complexe mais plus maintenant
- **Performance** : Meilleure avec ORM optimisé
- **Scalabilité** : Architecture plus adaptée à la croissance
- **Développement** : TypeScript améliore l'expérience développeur

---

*Analyse complétée le 08/08/2025*
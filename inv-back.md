# INVENTAIRE EXHAUSTIF BACKEND - StacGateLMS

## ARCHITECTURE GÉNÉRALE BACKEND

### Structure des dossiers
```
server/
├── api/                     # Routes API organisées par domaine
│   ├── analytics/          # Analytics et métriques (1 route)
│   ├── assessments/        # Évaluations et examens (1 route)
│   ├── auth/               # Authentification (1 route)
│   ├── courses/            # Gestion des cours (1 route)
│   ├── establishments/     # Établissements multi-tenant (1 route)
│   ├── exports/            # Exports et archives (1 route)
│   ├── help/               # Centre d'aide (1 route)
│   ├── study-groups/       # Groupes d'étude (1 route)
│   ├── system/             # Administration système (1 route)
│   ├── users/              # Gestion utilisateurs (1 route)
│   └── index.ts            # Routeur API principal
├── services/               # Couche services métier (10 services)
│   ├── AnalyticsService.ts
│   ├── AssessmentService.ts
│   ├── AuthService.ts
│   ├── CourseService.ts
│   ├── EstablishmentService.ts
│   ├── ExportService.ts
│   ├── HelpService.ts
│   ├── NotificationService.ts
│   ├── StudyGroupService.ts
│   ├── SystemService.ts
│   └── index.ts
├── middleware/             # Middlewares Express
│   └── auth.ts
├── websocket/              # WebSocket collaboration
│   └── collaborationManager.ts
├── index.ts                # Point d'entrée serveur
├── routes.ts               # Configuration routes principales
├── storage.ts              # Interface stockage IStorage
├── db.ts                   # Configuration base de données
├── vite.ts                 # Configuration Vite SSR
├── init-database.ts        # Initialisation BD
├── database-manager.ts     # Gestionnaire BD
├── establishment-service.ts # Service établissements legacy
└── replitAuth.ts           # Authentification Replit (legacy)
```

### Technologies Backend
- **Runtime**: Node.js avec TypeScript
- **Framework**: Express.js
- **Base de données**: PostgreSQL avec Drizzle ORM
- **Sessions**: Express-session avec cookies HTTP-only
- **Authentification**: Bcrypt pour hachage mots de passe
- **WebSocket**: ws library pour collaboration temps réel
- **Validation**: Zod schemas
- **ORM**: Drizzle ORM avec requêtes type-safe

## POINTS D'ENTRÉE ET CONFIGURATION

### index.ts - Serveur principal
- **Port**: 5000 par défaut
- **Middlewares globaux**:
  - express.json() pour parsing JSON
  - express.urlencoded() pour parsing forms
  - Custom logging middleware avec timing
- **Configuration**: registerRoutes() et setupVite()
- **Imports**: Express, routes, vite configuration

### routes.ts - Configuration routes et WebSocket
- **Session management**:
  - Secret: SESSION_SECRET env ou 'dev-secret-key-StacGateLMS-2025'
  - Cookie: 'stacgate.sid', 24h expiration, lax sameSite
  - Store: Memory store par défaut
- **API mounting**: '/api' prefix pour toutes les routes
- **WebSocket server**:
  - Path: '/ws/collaboration'
  - CollaborationManager pour gestion temps réel
  - Paramètres URL: userId, userName, userRole, establishmentId
- **Endpoints collaboration**:
  - GET /api/collaboration/stats
  - GET /api/collaboration/rooms/:roomId

### db.ts - Configuration base de données
- **Connexion**: DATABASE_URL PostgreSQL
- **ORM**: Drizzle ORM initialisé
- **Exports**: db instance pour requêtes

## ROUTES API PAR DOMAINE (11 domaines)

### 1. API Index (api/index.ts)
#### Endpoints directs
- **GET /api/documentation/help**: Documentation aide par rôle
- **GET /api/documentation/search**: Recherche documentation
- **GET /api/admin/pages/:pageName**: Pages WYSIWYG
- **GET /api/admin/components**: Composants WYSIWYG
- **GET /api/super-admin/portal-themes**: Thèmes portail
- **GET /api/super-admin/portal-contents**: Contenus portail
- **GET /api/super-admin/portal-menu-items**: Éléments menu
- **POST /api/export/create**: Création exports
- **GET /api/assessments**: Liste évaluations
- **GET /api/assessment-attempts**: Tentatives évaluations
- **GET /api/establishment-content/:slug/:page**: Contenu établissement
- **GET /api/health**: Health check

#### Routeurs montés
```typescript
/auth            → authRoutes
/establishments  → establishmentRoutes
/courses         → courseRoutes
/users           → userRoutes
/analytics       → analyticsRoutes
/exports         → exportRoutes
/study-groups    → studyGroupRoutes
/help            → helpRoutes
/system          → systemRoutes
/assessments     → assessmentRoutes
```

### 2. Auth Routes (api/auth/routes.ts)
- **GET /api/auth/user**: Utilisateur actuel avec session
- **POST /api/auth/login**: Authentification multi-établissement
- **POST /api/auth/logout**: Déconnexion avec destruction session
- **POST /api/auth/register**: Inscription nouvel utilisateur
- **Validation**: Zod schemas pour email/password
- **Sécurité**: Bcrypt password hashing, session management

### 3. Establishments Routes (api/establishments/routes.ts)
- **GET /api/establishments**: Liste tous établissements
- **GET /api/establishments/:id**: Établissement par ID
- **POST /api/establishments**: Création établissement
- **PUT /api/establishments/:id**: Mise à jour établissement
- **GET /api/establishments/slug/:slug**: Établissement par slug
- **Validation**: Zod schemas pour données établissement
- **Middleware**: requireAuth pour certaines opérations

### 4. Courses Routes (api/courses/routes.ts)
- **GET /api/courses**: Liste cours avec filtres établissement
- **GET /api/courses/:id**: Cours par ID avec détails
- **POST /api/courses**: Création nouveau cours
- **PUT /api/courses/:id**: Mise à jour cours
- **DELETE /api/courses/:id**: Suppression cours
- **POST /api/courses/:id/enroll**: Inscription à un cours
- **GET /api/courses/:id/enrollments**: Inscriptions cours
- **Validation**: Schémas cours complets avec prix, durée, niveau
- **Autorisation**: Basée sur rôles utilisateur

### 5. Users Routes (api/users/routes.ts)
- **GET /api/users**: Liste utilisateurs avec filtres
- **GET /api/users/:id**: Utilisateur par ID
- **POST /api/users**: Création utilisateur
- **PUT /api/users/:id**: Mise à jour utilisateur
- **DELETE /api/users/:id**: Suppression utilisateur
- **GET /api/users/establishment/:id**: Utilisateurs par établissement
- **Validation**: Schémas utilisateur avec rôles et permissions
- **Sécurité**: Hash mot de passe, validation rôles

### 6. Analytics Routes (api/analytics/routes.ts)
- **GET /api/analytics/overview**: Métriques générales
- **GET /api/analytics/courses**: Statistiques cours populaires
- **GET /api/analytics/users**: Activité utilisateurs
- **GET /api/analytics/enrollments**: Inscriptions par période
- **GET /api/analytics/activities**: Activités récentes système
- **Filtres**: Date range, établissement, type activité
- **Format**: JSON avec métriques temps réel

### 7. Exports Routes (api/exports/routes.ts)
- **GET /api/exports**: Liste jobs export
- **POST /api/exports**: Création nouveau job export
- **GET /api/exports/:id**: Status job export
- **DELETE /api/exports/:id**: Suppression job export
- **GET /api/exports/:id/download**: Téléchargement fichier
- **Types**: Courses, users, analytics, full_backup
- **Formats**: CSV, JSON, XML, ZIP

### 8. Study Groups Routes (api/study-groups/routes.ts)
- **GET /api/study-groups**: Liste groupes d'étude
- **POST /api/study-groups**: Création groupe
- **GET /api/study-groups/:id**: Détails groupe
- **PUT /api/study-groups/:id**: Mise à jour groupe
- **DELETE /api/study-groups/:id**: Suppression groupe
- **POST /api/study-groups/:id/join**: Rejoindre groupe
- **POST /api/study-groups/:id/leave**: Quitter groupe
- **GET /api/study-groups/:id/messages**: Messages groupe
- **POST /api/study-groups/:id/messages**: Nouveau message

### 9. Help Routes (api/help/routes.ts)
- **GET /api/help/contents**: Articles aide par rôle
- **GET /api/help/contents/:id**: Article spécifique
- **POST /api/help/contents**: Création article aide
- **PUT /api/help/contents/:id**: Mise à jour article
- **DELETE /api/help/contents/:id**: Suppression article
- **GET /api/help/search**: Recherche articles
- **Filtres**: Catégorie, rôle cible, langue

### 10. System Routes (api/system/routes.ts)
- **GET /api/system/info**: Informations système
- **GET /api/system/versions**: Versions disponibles
- **POST /api/system/update**: Déclenchement mise à jour
- **GET /api/system/logs**: Logs système
- **GET /api/system/health**: Health check détaillé
- **POST /api/system/maintenance**: Mode maintenance
- **Permissions**: Super admin uniquement

### 11. Assessments Routes (api/assessments/routes.ts)
- **GET /api/assessments**: Liste évaluations
- **POST /api/assessments**: Création évaluation
- **GET /api/assessments/:id**: Détails évaluation
- **PUT /api/assessments/:id**: Mise à jour évaluation
- **DELETE /api/assessments/:id**: Suppression évaluation
- **POST /api/assessments/:id/attempt**: Nouvelle tentative
- **GET /api/assessments/:id/attempts**: Tentatives utilisateur
- **POST /api/assessments/:id/submit**: Soumission réponses

## SERVICES MÉTIER (10 services)

### 1. AuthService (services/AuthService.ts)
#### Méthodes principales
- **authenticateUser(email, password, establishmentId)**: Authentification
- **hashPassword(password)**: Hachage bcrypt
- **createUser(userData)**: Création avec hash
- **updateUserPassword(userId, newPassword)**: Mise à jour MDP
- **verifyPermission(user, requiredRole)**: Vérification permissions

#### Logique métier
- **Hiérarchie rôles**: super_admin(5) > admin(4) > manager(3) > formateur(2) > apprenant(1)
- **Sécurité**: Salt rounds = 12 pour bcrypt
- **Session**: Mise à jour lastLoginAt

### 2. CourseService (services/CourseService.ts)
#### Fonctionnalités
- **CRUD cours**: Création, lecture, mise à jour, suppression
- **Gestion inscriptions**: Enrollment, unenrollment
- **Modules cours**: Progression, tracking temps
- **Validation**: Prix, durée, prérequis
- **Permissions**: Basée sur rôles et établissement

### 3. EstablishmentService (services/EstablishmentService.ts)
#### Fonctionnalités multi-tenant
- **CRUD établissements**: Gestion complète
- **Thèmes personnalisés**: Couleurs, fonts, logos
- **Contenu personnalisable**: WYSIWYG, blocs
- **Menus dynamiques**: Navigation personnalisée
- **Branding**: Logo, domaine, settings

### 4. AnalyticsService (services/AnalyticsService.ts)
#### Métriques collectées
- **Cours**: Popularité, taux completion, ratings
- **Utilisateurs**: Activité, connexions, progression
- **Établissements**: Stats multi-tenant
- **Système**: Performance, erreurs, usage
- **Exports**: Rapports personnalisés

### 5. AssessmentService (services/AssessmentService.ts)
#### Gestion évaluations
- **Types**: Quiz, examens, évaluations continues
- **Questions**: Multiple choice, text, code
- **Notation**: Automatique et manuelle
- **Tentatives**: Limitées, tracking temps
- **Rapports**: Statistiques performance

### 6. StudyGroupService (services/StudyGroupService.ts)
#### Collaboration groupes
- **CRUD groupes**: Création, gestion membres
- **Messages temps réel**: Chat, notifications
- **Whiteboard**: Collaboration visuelle
- **Permissions**: Modérateurs, participants
- **Integration**: Cours, projets, ressources

### 7. ExportService (services/ExportService.ts)
#### Gestion exports
- **Types**: Données utilisateurs, cours, analytics
- **Formats**: CSV, JSON, XML, PDF, ZIP
- **Jobs asynchrones**: Queue, progress tracking
- **Sécurité**: Permissions, encryption
- **Archives**: Backup complet système

### 8. HelpService (services/HelpService.ts)
#### Centre d'aide
- **Contenu adaptatif**: Par rôle et établissement
- **Recherche**: Full-text search, filtres
- **Catégories**: Navigation hiérarchique
- **Versioning**: Contenu versioned
- **Analytics**: Usage, recherches populaires

### 9. SystemService (services/SystemService.ts)
#### Administration système
- **Monitoring**: Performance, santé
- **Mises à jour**: Versioning, déploiements
- **Maintenance**: Mode, backup, restore
- **Logs**: Agrégation, analysis
- **Configuration**: Settings globaux

### 10. NotificationService (services/NotificationService.ts)
#### Système notifications
- **Types**: Email, push, in-app
- **Templates**: Personnalisables par établissement
- **Scheduling**: Envois programmés
- **Preferences**: Utilisateur, global
- **Tracking**: Delivery, read status

## INTERFACE STORAGE (storage.ts)

### IStorage Interface - 40+ méthodes

#### Establishment Operations (7 méthodes)
```typescript
getEstablishment(id): Promise<Establishment>
getEstablishmentBySlug(slug): Promise<Establishment>
createEstablishment(data): Promise<Establishment>
getAllEstablishments(): Promise<Establishment[]>
updateEstablishment(id, updates): Promise<Establishment>
```

#### User Operations (10 méthodes)
```typescript
getUser(id): Promise<User>
getUserByEmail(email, establishmentId): Promise<User>
getUserByUsername(username, establishmentId): Promise<User>
createUser(user): Promise<User>
updateUser(id, updates): Promise<User>
deleteUser(id): Promise<void>
updateUserLastLogin(userId): Promise<void>
getUsersByEstablishment(establishmentId): Promise<User[]>
upsertUser(user): Promise<User>
```

#### Course Operations (8 méthodes)
```typescript
getCourse(id): Promise<Course>
getCoursesByEstablishment(establishmentId): Promise<CourseWithDetails[]>
createCourse(course): Promise<Course>
updateCourse(id, updates): Promise<Course>
deleteCourse(id): Promise<void>
enrollUserInCourse(userId, courseId): Promise<UserCourse>
getUserCourses(userId): Promise<CourseWithDetails[]>
getCourseEnrollments(courseId): Promise<UserWithEstablishment[]>
```

#### Theme Operations (5 méthodes)
```typescript
getActiveTheme(establishmentId): Promise<SimpleTheme>
getThemesByEstablishment(establishmentId): Promise<SimpleTheme[]>
createTheme(theme): Promise<SimpleTheme>
updateTheme(id, updates): Promise<SimpleTheme>
activateTheme(id, establishmentId): Promise<void>
```

#### Content Management (4 méthodes)
```typescript
getCustomizableContents(establishmentId): Promise<SimpleCustomizableContent[]>
getCustomizableContentByKey(establishmentId, blockKey): Promise<SimpleCustomizableContent>
createCustomizableContent(content): Promise<SimpleCustomizableContent>
updateCustomizableContent(id, content): Promise<SimpleCustomizableContent>
```

#### Menu Operations (4 méthodes)
```typescript
getMenuItems(establishmentId): Promise<SimpleMenuItem[]>
createMenuItem(menuItem): Promise<SimpleMenuItem>
updateMenuItem(id, menuItem): Promise<SimpleMenuItem>
deleteMenuItem(id): Promise<void>
```

#### Assessment Operations (6+ méthodes)
```typescript
getAssessments(establishmentId): Promise<Assessment[]>
createAssessment(assessment): Promise<Assessment>
getAssessmentAttempts(userId, assessmentId): Promise<AssessmentAttempt[]>
recordAssessmentAttempt(attempt): Promise<AssessmentAttempt>
```

#### Analytics Operations (5+ méthodes)
```typescript
getAnalyticsOverview(establishmentId): Promise<any>
getPopularCourses(establishmentId): Promise<any>
getUserActivities(establishmentId): Promise<any>
getEnrollmentStats(establishmentId): Promise<any>
```

#### Study Group Operations (8+ méthodes)
```typescript
getStudyGroups(establishmentId): Promise<StudyGroupWithDetails[]>
createStudyGroup(group): Promise<StudyGroup>
joinStudyGroup(userId, groupId): Promise<StudyGroupMember>
getStudyGroupMessages(groupId): Promise<StudyGroupMessageWithDetails[]>
createStudyGroupMessage(message): Promise<StudyGroupMessage>
```

## WEBSOCKET COLLABORATION (websocket/collaborationManager.ts)

### CollaborationManager Class

#### Structures de données
```typescript
interface User {
  id: string;
  name: string;
  role: string;
  establishmentId: string;
}

interface Room {
  id: string;
  type: 'course' | 'studygroup' | 'whiteboard' | 'assessment';
  resourceId: string;
  establishmentId: string;
  participants: Map<string, ParticipantData>;
  lastActivity: Date;
}
```

#### Méthodes principales
- **addUser(ws, user)**: Ajout utilisateur au système
- **removeUser(ws)**: Suppression et cleanup
- **joinRoom(ws, roomId, type, resourceId)**: Rejoindre salle
- **leaveRoom(ws, roomId)**: Quitter salle
- **handleCollaborationMessage(ws, data)**: Gestion messages
- **broadcastToRoom(roomId, message, exclude)**: Diffusion
- **getSystemStats()**: Statistiques système
- **getRoomStats(roomId)**: Statistiques salle

#### Types de messages
- **join_room**: Rejoindre salle collaboration
- **leave_room**: Quitter salle
- **cursor_move**: Mouvement curseur temps réel
- **text_change**: Modifications texte
- **whiteboard_draw**: Dessin tableau blanc
- **chat_message**: Messages chat
- **typing_indicator**: Indicateur de frappe
- **user_join/leave**: Notifications participants

#### Gestion des salles
- **Types**: Course, StudyGroup, Whiteboard, Assessment
- **Isolation**: Par établissement
- **Permissions**: Basées sur rôles utilisateur
- **Cleanup**: Automatique sur déconnexion
- **Stats**: Participants, activité, durée

## MIDDLEWARE

### auth.ts - Middleware authentification
```typescript
requireAuth: Vérification session utilisateur
requireRole: Vérification rôle minimum
requirePermission: Vérification permission spécifique
checkEstablishmentAccess: Accès établissement
```

#### Logique d'autorisation
- **Session**: Vérification req.session.userId
- **User**: Récupération données utilisateur
- **Role hierarchy**: Super admin > Admin > Manager > Formateur > Apprenant
- **Establishment**: Isolation multi-tenant
- **Permissions**: Granulaires par resource/action

## SCHÉMAS ET VALIDATION

### Zod Schemas utilisés
```typescript
loginSchema: email + password validation
registerSchema: user creation avec establishment
courseSchema: cours avec prix, durée, niveau
establishmentSchema: nom, slug, description
assessmentSchema: évaluation avec questions
```

### Types TypeScript partagés
- **Import**: @shared/schema.ts pour cohérence
- **Drizzle**: Types générés automatiquement
- **Validation**: Zod schemas pour runtime
- **Interface**: IStorage pour abstraction

## BASE DE DONNÉES

### Configuration Drizzle
- **Connexion**: PostgreSQL via DATABASE_URL
- **Migrations**: Push schema directement
- **Relations**: Foreign keys avec cascade
- **Indexes**: Optimisation requêtes fréquentes

### Tables principales (25+ tables)
```sql
-- Multi-tenant
establishments, themes, customizable_contents, menu_items

-- Users & Auth  
users, sessions, permissions, rolePermissions, userPermissions

-- Learning
courses, course_modules, user_courses, user_module_progress

-- Assessment
assessments, assessment_attempts

-- Collaboration
studyGroups, studyGroupMembers, studyGroupMessages, whiteboards

-- System
notifications, exportJobs, help_contents, system_versions
```

### Relations clés
- **Establishment → Users**: Multi-tenant isolation
- **Course → Modules**: Hierarchical content
- **User → Courses**: Enrollment tracking
- **StudyGroup → Messages**: Chat history
- **Assessment → Attempts**: Performance tracking

## SÉCURITÉ

### Authentification
- **Bcrypt**: Salt rounds 12 pour passwords
- **Sessions**: HTTP-only cookies avec expiration
- **CSRF**: Protection via SameSite cookies
- **Validation**: Zod schemas pour toutes entrées

### Autorisation
- **Role-based**: Hiérarchie permissions
- **Resource-based**: Accès par établissement
- **Granular**: Permissions spécifiques actions
- **Middleware**: Vérifications systématiques

### Data Protection
- **Encryption**: Passwords hashés
- **Isolation**: Multi-tenant data separation
- **Validation**: Input sanitization
- **Logging**: Security events tracking

## PERFORMANCE

### Optimisations
- **Indexes**: DB queries optimisées
- **Caching**: Session et query caching
- **Pagination**: Large datasets
- **Lazy loading**: Relations optionnelles

### Monitoring
- **Logging**: Request timing et errors
- **Health checks**: System status
- **Analytics**: Usage patterns
- **Alerts**: Performance thresholds

## DÉPLOIEMENT ET MAINTENANCE

### Configuration environnement
```env
DATABASE_URL: PostgreSQL connection
SESSION_SECRET: Session encryption key  
NODE_ENV: development/production
PORT: Server port (default 5000)
```

### Scripts maintenance
- **init-database.ts**: Initialisation BD
- **database-manager.ts**: Gestion migrations
- **Health checks**: Status endpoints
- **Backup**: Export jobs automatisés

Cette documentation constitue l'inventaire exhaustif du backend, couvrant toutes les routes, services, middlewares, WebSocket, storage et configurations de l'application StacGateLMS.
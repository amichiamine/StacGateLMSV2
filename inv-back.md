# INVENTAIRE BACKEND - StacGateLMS

## 1. ARCHITECTURE GÉNÉRALE

### Structure des répertoires
```
server/
├── index.ts                     # Point d'entrée principal
├── routes.ts                    # Définition des endpoints API
├── storage.ts                   # Couche d'accès aux données
├── database-manager.ts          # Gestion multi-établissements
├── establishment-service.ts     # Services établissement
├── db.ts                        # Configuration base de données
├── init-database.ts             # Initialisation DB
├── replitAuth.ts                # Authentification Replit (legacy)
├── vite.ts                      # Configuration Vite
└── middleware/
    └── auth.ts                  # Middleware d'authentification
```

### Configuration partagée
```
shared/
└── schema.ts                    # Schémas Drizzle ORM + validations Zod
```

## 2. CONFIGURATION ET DÉMARRAGE

### Point d'entrée (server/index.ts)
- **Express server** sur port 5000 (configurable via PORT)
- **WebSocket server** intégré
- **Middleware de session** avec express-session
- **Gestion d'erreurs** centralisée
- **Configuration Vite** pour le développement
- **Serveur statique** pour la production

### Configuration base de données (server/db.ts)
- **Neon PostgreSQL** avec WebSocket support
- **Drizzle ORM** configuration
- **Pool de connexions** optimisé
- **Schémas importés** depuis shared/schema.ts

## 3. GESTION DES DONNÉES

### Couche d'abstraction (server/storage.ts)
Interface `IStorage` avec plus de 80 méthodes :

#### **Gestion des établissements**
- `getEstablishment(id)` - Récupérer un établissement
- `getEstablishmentBySlug(slug)` - Récupérer par slug
- `createEstablishment(data)` - Créer établissement
- `getAllEstablishments()` - Lister tous établissements

#### **Gestion des utilisateurs**
- `getUser(id)` - Récupérer utilisateur
- `getUserByEmail(email, establishmentId)` - Recherche par email
- `getUserByUsername(username, establishmentId)` - Recherche par username
- `createUser(data)` - Créer utilisateur
- `updateUser(id, updates)` - Mettre à jour
- `deleteUser(id)` - Supprimer
- `updateUserLastLogin(userId)` - MAJ dernière connexion
- `getUsersByEstablishment(establishmentId)` - Utilisateurs par établissement
- `getAllUsers()` - Tous utilisateurs
- `upsertUser(data)` - Créer ou mettre à jour

#### **Gestion des thèmes**
- `getActiveTheme(establishmentId)` - Thème actif
- `getThemesByEstablishment(establishmentId)` - Thèmes d'établissement
- `createTheme(data)` - Créer thème
- `updateTheme(id, updates)` - Mettre à jour thème
- `activateTheme(id, establishmentId)` - Activer thème

#### **Contenu personnalisable**
- `getCustomizableContents(establishmentId)` - Contenus personnalisables
- `getCustomizableContentByKey(establishmentId, key)` - Contenu par clé
- `createCustomizableContent(data)` - Créer contenu
- `updateCustomizableContent(id, data)` - Mettre à jour contenu

#### **Gestion des menus**
- `getMenuItems(establishmentId)` - Éléments de menu
- `createMenuItem(data)` - Créer élément menu
- `updateMenuItem(id, data)` - Mettre à jour élément
- `deleteMenuItem(id)` - Supprimer élément

#### **Gestion des cours**
- `getCourse(id)` - Récupérer cours
- `getCoursesByEstablishment(establishmentId)` - Cours par établissement
- `getCoursesByCategory(category, establishmentId)` - Cours par catégorie
- `createCourse(data)` - Créer cours
- `updateCourse(id, updates)` - Mettre à jour cours
- `deleteCourse(id)` - Supprimer cours
- `approveCourse(courseId, approvedBy)` - Approuver cours

#### **Espaces formateurs**
- `getTrainerSpace(id)` - Espace formateur
- `getTrainerSpacesByEstablishment(establishmentId)` - Espaces par établissement
- `createTrainerSpace(data)` - Créer espace
- `updateTrainerSpace(id, updates)` - Mettre à jour
- `approveTrainerSpace(id, approvedBy)` - Approuver espace

#### **Gestion des évaluations**
- `getAssessment(id)` - Récupérer évaluation
- `getAssessmentsByEstablishment(establishmentId)` - Évaluations par établissement
- `createAssessment(data)` - Créer évaluation
- `updateAssessment(id, updates)` - Mettre à jour
- `deleteAssessment(id)` - Supprimer
- `approveAssessment(id, approvedBy)` - Approuver

#### **Tentatives d'évaluation**
- `getAssessmentAttempt(id)` - Récupérer tentative
- `getAssessmentAttemptsByUser(userId, assessmentId)` - Tentatives par utilisateur
- `createAssessmentAttempt(data)` - Créer tentative
- `updateAssessmentAttempt(id, updates)` - Mettre à jour
- `submitAssessmentAttempt(id, answers)` - Soumettre tentative

#### **Système de notifications**
- `getNotificationsByUser(userId)` - Notifications utilisateur
- `createNotification(data)` - Créer notification
- `markNotificationAsRead(id)` - Marquer comme lue
- `markAllNotificationsAsRead(userId)` - Marquer toutes comme lues

#### **Groupes d'étude**
- `getStudyGroup(id)` - Récupérer groupe
- `getStudyGroupsByUser(userId)` - Groupes par utilisateur
- `createStudyGroup(data)` - Créer groupe
- `addStudyGroupMember(data)` - Ajouter membre
- `removeStudyGroupMember(groupId, userId)` - Retirer membre
- `getStudyGroupMessages(groupId)` - Messages du groupe
- `createStudyGroupMessage(data)` - Créer message

#### **Tableaux blancs collaboratifs**
- `getWhiteboard(id)` - Récupérer tableau blanc
- `getWhiteboardsByGroup(groupId)` - Tableaux par groupe
- `createWhiteboard(data)` - Créer tableau
- `updateWhiteboardContent(id, content)` - MAJ contenu

#### **Jobs d'export et archivage**
- `createExportJob(data)` - Créer job d'export
- `getExportJob(id)` - Récupérer job
- `updateExportJobStatus(id, status)` - MAJ statut job

#### **Aide et documentation**
- `getHelpContents(category)` - Contenus d'aide
- `createHelpContent(data)` - Créer contenu d'aide
- `updateHelpContent(id, updates)` - Mettre à jour

#### **Versioning système**
- `getCurrentSystemVersion()` - Version système actuelle
- `createSystemVersion(data)` - Créer version
- `getSystemVersionHistory()` - Historique versions

#### **Branding établissement**
- `getEstablishmentBranding(establishmentId)` - Branding par établissement
- `updateEstablishmentBranding(establishmentId, data)` - MAJ branding

## 4. ENDPOINTS API (server/routes.ts)

### Authentification
- **POST /api/auth/login** - Connexion utilisateur
- **GET /api/auth/user** - Récupérer utilisateur connecté
- **POST /api/auth/logout** - Déconnexion

### Établissements (Public)
- **GET /api/establishments** - Lister établissements actifs
- **GET /api/establishments/slug/:slug** - Établissement par slug
- **GET /api/establishment-content/:slug/:pageType** - Contenu personnalisé

### Utilisateurs (Protégé)
- **GET /api/users** - Lister utilisateurs (admin requis)
- **POST /api/users** - Créer utilisateur (admin requis)
- **PUT /api/users/:id** - Mettre à jour utilisateur
- **DELETE /api/users/:id** - Supprimer utilisateur (admin requis)
- **GET /api/users/establishment/:id** - Utilisateurs par établissement

### Cours
- **GET /api/courses** - Lister cours
- **POST /api/courses** - Créer cours (formateur requis)
- **PUT /api/courses/:id** - Mettre à jour cours
- **DELETE /api/courses/:id** - Supprimer cours
- **POST /api/courses/:id/approve** - Approuver cours (admin requis)
- **POST /api/courses/:id/enroll** - S'inscrire au cours
- **GET /api/courses/category/:category** - Cours par catégorie

### Thèmes et personnalisation
- **GET /api/themes/:establishmentId** - Thèmes par établissement
- **POST /api/themes** - Créer thème (admin requis)
- **PUT /api/themes/:id** - Mettre à jour thème
- **POST /api/themes/:id/activate** - Activer thème

### Contenu personnalisable
- **GET /api/customizable-contents/:establishmentId** - Contenus personnalisables
- **POST /api/customizable-contents** - Créer contenu (admin requis)
- **PUT /api/customizable-contents/:id** - Mettre à jour contenu

### Menus
- **GET /api/menu-items/:establishmentId** - Éléments de menu
- **POST /api/menu-items** - Créer élément (admin requis)
- **PUT /api/menu-items/:id** - Mettre à jour élément
- **DELETE /api/menu-items/:id** - Supprimer élément

### Espaces formateurs
- **GET /api/trainer-spaces** - Espaces formateurs
- **POST /api/trainer-spaces** - Créer espace (formateur requis)
- **PUT /api/trainer-spaces/:id** - Mettre à jour espace
- **POST /api/trainer-spaces/:id/approve** - Approuver espace (admin requis)

### Évaluations
- **GET /api/assessments** - Lister évaluations
- **POST /api/assessments** - Créer évaluation (formateur requis)
- **PUT /api/assessments/:id** - Mettre à jour évaluation
- **DELETE /api/assessments/:id** - Supprimer évaluation
- **POST /api/assessments/:id/approve** - Approuver évaluation (admin requis)

### Tentatives d'évaluation
- **POST /api/assessment-attempts** - Créer tentative
- **PUT /api/assessment-attempts/:id** - Mettre à jour tentative
- **POST /api/assessment-attempts/:id/submit** - Soumettre tentative
- **GET /api/assessment-attempts/user/:userId/:assessmentId** - Tentatives utilisateur

### Notifications
- **GET /api/notifications** - Notifications utilisateur
- **POST /api/notifications** - Créer notification (admin requis)
- **PUT /api/notifications/:id/read** - Marquer comme lue
- **PUT /api/notifications/read-all** - Marquer toutes comme lues

### Groupes d'étude
- **GET /api/study-groups** - Groupes d'étude utilisateur
- **POST /api/study-groups** - Créer groupe
- **POST /api/study-groups/:id/members** - Ajouter membre
- **DELETE /api/study-groups/:id/members/:userId** - Retirer membre
- **GET /api/study-groups/:id/messages** - Messages du groupe
- **POST /api/study-groups/:id/messages** - Créer message

### Tableaux blancs
- **GET /api/whiteboards/group/:groupId** - Tableaux par groupe
- **POST /api/whiteboards** - Créer tableau
- **PUT /api/whiteboards/:id** - Mettre à jour contenu

### Jobs d'export
- **POST /api/export-jobs** - Créer job d'export (admin requis)
- **GET /api/export-jobs/:id** - Statut du job

### Aide et documentation
- **GET /api/help-contents** - Contenus d'aide
- **POST /api/help-contents** - Créer contenu d'aide (admin requis)
- **PUT /api/help-contents/:id** - Mettre à jour contenu

### Versions système
- **GET /api/system-versions/current** - Version actuelle
- **GET /api/system-versions/history** - Historique versions
- **POST /api/system-versions** - Créer version (super_admin requis)

### Branding
- **GET /api/branding/:establishmentId** - Branding établissement
- **PUT /api/branding/:establishmentId** - Mettre à jour branding (admin requis)

## 5. MIDDLEWARE D'AUTHENTIFICATION (server/middleware/auth.ts)

### Middlewares disponibles
- **requireAuth** - Authentification requise
- **requireSuperAdmin** - Rôle super_admin requis
- **requireAdmin** - Rôle admin requis (inclut super_admin)
- **requireEstablishmentAccess** - Accès à l'établissement requis

### Gestion des sessions
- **express-session** avec stockage en mémoire (développement)
- **Cookies sécurisés** avec configuration appropriée
- **Gestion automatique** de l'expiration
- **Rolling sessions** pour prolonger automatiquement

## 6. GESTION MULTI-ÉTABLISSEMENTS (server/database-manager.ts)

### Architecture
- **DatabaseManager singleton** pour gérer les connexions
- **Connexion principale** pour la gestion globale
- **Connexions dédiées** par établissement
- **Pool de connexions** optimisé par établissement

### Fonctionnalités
- **Isolation des données** par établissement
- **Schémas dédiés** (optionnel)
- **Configuration dynamique** des connexions
- **Gestion automatique** des connexions

## 7. SCHÉMAS DE DONNÉES (shared/schema.ts)

### Tables principales

#### **Sessions système**
- `sessions` - Gestion des sessions utilisateur

#### **Établissements et structure**
- `establishments` - Informations établissements
- `themes` - Thèmes personnalisables
- `customizable_contents` - Contenus personnalisables
- `customizable_pages` - Pages personnalisables
- `page_components` - Composants réutilisables
- `page_sections` - Sections de page
- `menu_items` - Éléments de menu

#### **Utilisateurs et permissions**
- `users` - Utilisateurs du système
- `permissions` - Permissions disponibles
- `rolePermissions` - Permissions par rôle
- `userPermissions` - Permissions personnalisées

#### **Formation et pédagogie**
- `courses` - Cours et formations
- `training_sessions` - Sessions de formation
- `user_courses` - Inscriptions utilisateur
- `course_modules` - Modules de cours
- `user_module_progress` - Progression utilisateur
- `educational_plugins` - Plugins pédagogiques
- `certificates` - Certificats délivrés

#### **Espaces de travail**
- `trainer_spaces` - Espaces formateurs

#### **Évaluations**
- `assessments` - Évaluations et examens
- `assessment_attempts` - Tentatives d'évaluation

#### **Communication**
- `notifications` - Système de notifications
- `studyGroups` - Groupes d'étude
- `studyGroupMembers` - Membres des groupes
- `studyGroupMessages` - Messages des groupes
- `whiteboards` - Tableaux blancs collaboratifs

#### **Système**
- `exportJobs` - Jobs d'archivage et export
- `help_contents` - Contenu d'aide
- `system_versions` - Versions du système
- `establishment_branding` - Personnalisation visuelle

### Énumérations (Enums)
- **userRoleEnum** : super_admin, admin, manager, formateur, apprenant
- **courseTypeEnum** : synchrone, asynchrone
- **sessionStatusEnum** : draft, pending_approval, approved, active, completed, archived
- **notificationTypeEnum** : course_enrollment, assessment_graded, course_published, etc.
- **studyGroupStatusEnum** : active, archived, scheduled
- **messageTypeEnum** : text, file, image, link, poll, whiteboard

### Schémas de validation Zod
Plus de 20 schémas de validation pour les insertions :
- `insertUserSchema`, `insertCourseSchema`, `insertEstablishmentSchema`
- `insertSimpleThemeSchema`, `insertSimpleCustomizableContentSchema`
- `insertAssessmentSchema`, `insertStudyGroupSchema`, etc.

## 8. WEBSOCKET (Intégré dans server/index.ts)

### Fonctionnalités
- **Messages temps réel** pour les groupes d'étude
- **Notifications push** instantanées
- **Collaboration** sur tableaux blancs
- **Statuts de présence** utilisateur

## 9. SERVICES MÉTIER

### Service établissement (server/establishment-service.ts)
- Logique métier spécifique aux établissements
- Gestion de la personnalisation
- Configuration dynamique

### Gestionnaire de base de données (server/database-manager.ts)
- Connexions multi-établissements
- Pool de connexions optimisé
- Gestion automatique des ressources

## 10. SÉCURITÉ

### Authentification
- **Hachage bcrypt** des mots de passe
- **Sessions sécurisées** avec express-session
- **Validation des entrées** avec Zod
- **Protection CSRF** intégrée

### Autorisation
- **Contrôle d'accès** basé sur les rôles (RBAC)
- **Permissions granulaires** par ressource
- **Isolation des données** par établissement
- **Middleware de sécurité** sur tous les endpoints sensibles

### Validation
- **Schémas Zod** pour toutes les entrées
- **Validation côté serveur** obligatoire
- **Sanitisation des données** automatique
- **Gestion d'erreurs** centralisée

## 11. PERFORMANCE

### Base de données
- **Pool de connexions** Neon PostgreSQL
- **Requêtes optimisées** avec Drizzle ORM
- **Index** sur les colonnes fréquemment utilisées
- **Pagination** automatique pour les grandes listes

### Cache et optimisations
- **Cache session** en mémoire (développement)
- **Requêtes préparées** via Drizzle
- **Lazy loading** des relations
- **Compression** des réponses JSON

## 12. MONITORING ET LOGS

### Logging
- **Console.log** pour le développement
- **Gestion d'erreurs** centralisée
- **Logs des requêtes** Express
- **Tracking des performances** intégré

### Monitoring
- **Statut des connexions** DB
- **Métriques WebSocket** 
- **Suivi des sessions** utilisateur
- **Monitoring des jobs** d'export

## 13. CONFIGURATION

### Variables d'environnement
- **DATABASE_URL** - Connexion PostgreSQL
- **SESSION_SECRET** - Clé de chiffrement des sessions  
- **PORT** - Port du serveur (défaut: 5000)
- **NODE_ENV** - Environnement (development/production)

### Configuration par défaut
- **Timeout des sessions** : 24h
- **Port d'écoute** : 5000 (ou PORT env)
- **Host** : 0.0.0.0 (accessible)
- **Credentials** : include (CORS)

## 14. DÉPLOIEMENT ET BUILD

### Scripts disponibles
- **npm run dev** - Développement avec hot-reload
- **npm run build** - Build production (Vite + esbuild)
- **npm run start** - Démarrage production
- **npm run db:push** - Application des migrations

### Configuration build
- **esbuild** pour le backend (server/index.ts → dist/)
- **Vite** pour le frontend intégré
- **Bundle ESM** avec dépendances externes
- **Optimisation** pour la production

## 15. INTÉGRATIONS TIERCES

### Base de données
- **Neon PostgreSQL** serverless
- **Drizzle ORM** avec WebSocket support
- **Pool de connexions** optimisé

### Authentification
- **Express-session** pour les sessions
- **bcryptjs** pour le hachage
- **Middleware personnalisé** pour l'autorisation

### WebSocket
- **ws library** pour WebSocket natif
- **Intégration Express** transparente
- **Broadcasting** aux groupes

## 16. ARCHITECTURE MULTI-TENANT

### Stratégie d'isolation
- **Base de données partagée** avec clé établissement
- **Schémas dédiés** optionnels par établissement
- **Connexions séparées** pour de gros établissements
- **Configuration dynamique** par tenant

### Gestion des ressources
- **Pool de connexions** par établissement
- **Cache séparé** par tenant
- **Isolation des données** garantie
- **Performance** optimisée par tenant
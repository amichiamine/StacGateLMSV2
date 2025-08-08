# INVENTAIRE EXHAUSTIF BACKEND - PHP StacGateLMS

## ANALYSE STRUCTURELLE BACKEND

### 📁 ARCHITECTURE DES DOSSIERS
```
php-migration/
├── config/                         # Configuration système
├── core/                           # Classes core framework
├── core/services/                  # Services métier
├── api/                           # Endpoints API (référencés, non créés)
├── uploads/                       # Uploads utilisateurs (à créer)
├── cache/                         # Cache fichiers (à créer)
└── logs/                         # Logs système (à créer)
```

### ⚙️ CONFIGURATION SYSTÈME

#### **config/config.php** (75+ lignes)
**Constantes définies** :
- `APP_NAME` = 'StacGateLMS'
- `APP_VERSION` = '1.0.0'  
- `APP_ENV` = development/production
- `APP_DEBUG` = boolean selon environnement
- `SESSION_LIFETIME` = 24h
- `PASSWORD_SALT_ROUNDS` = 12 (Argon2ID)
- `CSRF_TOKEN_NAME` = '_token'
- `BASE_URL` = URL base application
- `UPLOAD_MAX_SIZE` = 10MB
- `ALLOWED_FILE_TYPES` = array[7] extensions

**Configuration email** :
- `MAIL_HOST/PORT/USERNAME/PASSWORD/FROM`

**Système de rôles** :
```php
USER_ROLES = [
    'super_admin' => 5,
    'admin' => 4, 
    'manager' => 3,
    'formateur' => 2,
    'apprenant' => 1
]
```

**Thème par défaut** :
```php
DEFAULT_THEME_COLORS = [
    'primary' => '#8B5CF6',
    'secondary' => '#A78BFA',
    'accent' => '#C4B5FD', 
    'background' => '#FFFFFF',
    'text' => '#1F2937'
]
```

**Limites système** :
- `MAX_COURSES_PER_ESTABLISHMENT` = 1000
- `MAX_USERS_PER_ESTABLISHMENT` = 10000  
- `API_RATE_LIMIT` = 100 req/min

**Cache & Logs** :
- `CACHE_ENABLED/LIFETIME/PATH`
- `LOG_ENABLED/PATH/LEVEL`

**Collaboration temps réel** :
- `COLLABORATION_ENABLED` = true
- `POLL_INTERVAL` = 2 secondes
- `MAX_ROOM_PARTICIPANTS` = 50

#### **config/database.php** (100+ lignes)
**Configuration multi-SGBD** :
- Support MySQL et PostgreSQL
- Variables d'environnement : `DB_TYPE`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`
- Options PDO configurées :
  - `ATTR_ERRMODE` = EXCEPTION
  - `ATTR_DEFAULT_FETCH_MODE` = ASSOC
  - `ATTR_EMULATE_PREPARES` = false
- Constante `IS_POSTGRESQL` pour requêtes conditionnelles

### 🏗️ CLASSES CORE FRAMEWORK

#### **core/Database.php** (400+ lignes)
**Singleton PDO** avec méthodes :

**Connexion** :
- `getInstance()` - Singleton pattern
- `connect()` - Connexion PDO
- `getDSN()` - DSN selon SGBD
- `getPDO()` - Accès direct PDO

**Requêtes de base** :
- `select($sql, $params)` - SELECT multiple
- `selectOne($sql, $params)` - SELECT single
- `insert($table, $data)` - INSERT avec lastInsertId
- `update($table, $data, $where, $whereParams)` - UPDATE
- `delete($table, $where, $params)` - DELETE
- `execute($sql, $params)` - Requête libre

**Méthodes avancées** :
- `insertWithTimestamps($table, $data)` - INSERT + created_at/updated_at
- `updateWithTimestamps($table, $data, $where, $whereParams)` - UPDATE + updated_at
- `count($table, $where, $params)` - COUNT
- `exists($table, $where, $params)` - EXISTS boolean
- `paginate($sql, $params, $page, $perPage)` - Pagination avec meta

**Transactions** :
- `beginTransaction()`, `commit()`, `rollback()`

#### **core/Router.php** (200+ lignes)
**Système de routage** :
- `get($route, $handler, $requireAuth)` - Route GET
- `post($route, $handler, $requireAuth)` - Route POST  
- `put($route, $handler, $requireAuth)` - Route PUT
- `delete($route, $handler, $requireAuth)` - Route DELETE
- `handleRequest()` - Traitement requête
- Support paramètres URL : `{id}`, `{slug}`
- Middleware authentification automatique
- Gestion 404 automatique

#### **core/Auth.php** (200+ lignes)
**Authentification & Sessions** :
- `check()` - Vérifier connexion
- `user()` - Utilisateur connecté avec établissement
- `id()` - ID utilisateur  
- `login($user)` - Connexion + régénération session
- `logout()` - Déconnexion complète
- `hashPassword($password)` - Hash Argon2ID sécurisé
- `verifyPassword($password, $hash)` - Vérification
- `attempt($email, $password, $establishmentId)` - Tentative connexion
- `requireAuth()` - Middleware authentification
- `hasRole($role)` - Vérification rôle/permission
- `can($permission, $establishment_id)` - Permissions granulaires
- Mise à jour `last_login_at` automatique

#### **core/Validator.php** (300+ lignes)
**Système de validation** style Laravel :

**Méthodes principales** :
- `make($data, $rules)` - Créer validateur
- `validate()` - Exécuter validation
- `getErrors()` - Obtenir erreurs
- `getValidatedData()` - Données validées

**Règles supportées** :
- `required` - Obligatoire
- `email` - Email valide
- `min:X` - Longueur minimum
- `max:X` - Longueur maximum  
- `numeric` - Numérique
- `integer` - Entier
- `in:val1,val2` - Dans liste
- `unique:table,column,except` - Unique en DB
- `confirmed` - Confirmation champ
- `url` - URL valide
- `alpha` - Lettres seulement
- `alpha_num` - Lettres + chiffres
- `regex:pattern` - Expression régulière
- `date` - Date valide
- `boolean` - Booléen
- `json` - JSON valide

**Méthodes utilitaires** :
- `validateOrFail()` - Validation avec exception
- `setMessages()` - Messages personnalisés
- `hasError($field)` - Vérifier erreur champ

#### **core/Utils.php** (500+ lignes)
**Utilitaires généraux** :

**Sécurité** :
- `sanitize($data)` - Nettoyer données (XSS)
- `generateId($length)` - ID unique
- `generatePassword($length)` - Mot de passe aléatoire

**Formatage** :
- `generateSlug($text)` - Slug URL-friendly
- `formatDate($date, $format)` - Formatage date
- `timeAgo($date)` - Temps relatif ("il y a X")
- `formatNumber($number, $decimals)` - Formatage nombre
- `formatFileSize($bytes)` - Taille fichier humaine
- `truncate($text, $length, $suffix)` - Tronquer texte

**Validation** :
- `isValidEmail($email)` - Validation email
- `isValidUrl($url)` - Validation URL
- `contains($haystack, $needle, $caseSensitive)` - Recherche texte

**Système** :
- `getClientIp()` - IP client réelle
- `getUserAgent()` - User agent
- `isMobile()` - Détection mobile

**Cache simple** :
- `cache($key, $data, $ttl)` - Get/Set cache
- `forgetCache($key)` - Supprimer cache
- `clearCache()` - Vider cache

**Logs** :
- `log($message, $level)` - Écrire log

**Messages flash** :
- `redirectWithMessage($url, $message, $type)` - Redirection + message
- `getFlashMessage()` - Récupérer message

**Upload fichiers** :
- `uploadFile($file, $destination, $allowedTypes)` - Upload sécurisé

**Export données** :
- `arrayToCsv($array, $delimiter)` - Array vers CSV

**Couleurs** :
- `generateRandomColor()` - Couleur hex aléatoire
- `isLightColor($color)` - Détecter couleur claire

### 🎯 SERVICES MÉTIER (10 Services)

#### **core/services/AuthService.php** (300+ lignes)
**Gestion utilisateurs** :
- `authenticate($email, $password, $establishmentId)` - Authentification
- `createUser($userData)` - Création utilisateur
- `getUserById($id)` - Utilisateur par ID
- `getUserByEmail($email, $establishmentId)` - Par email
- `updateUser($id, $updateData)` - Mise à jour
- `deleteUser($id)` - Suppression (avec vérifications)
- `getUsersByEstablishment($establishmentId, $page, $perPage, $search)` - Liste paginée
- `getAllUsers($page, $perPage, $search, $role)` - Tous utilisateurs (super admin)
- `hasPermission($userId, $requiredRole)` - Vérification permission
- `changePassword($userId, $currentPassword, $newPassword)` - Changement mot de passe
- `generateUsername($firstName, $lastName)` - Username unique
- `getUserStats($establishmentId)` - Statistiques utilisateurs

#### **core/services/EstablishmentService.php** (400+ lignes)
**Gestion établissements** :
- `getAllEstablishments($activeOnly)` - Liste établissements
- `getEstablishmentById($id)` - Par ID
- `getEstablishmentBySlug($slug)` - Par slug
- `createEstablishment($data)` - Création
- `updateEstablishment($id, $data)` - Mise à jour
- `deleteEstablishment($id)` - Suppression
- `getThemes($establishmentId)` - Thèmes établissement
- `getActiveTheme($establishmentId)` - Thème actif
- `createTheme($establishmentId, $themeData)` - Nouveau thème
- `activateTheme($themeId, $establishmentId)` - Activer thème
- `getCustomizableContents($establishmentId)` - Contenus personnalisables
- `getMenuItems($establishmentId)` - Éléments menu
- `getEstablishmentStats($establishmentId)` - Statistiques
- `generateUniqueSlug($name)` - Slug unique
- `createDefaultTheme($establishmentId)` - Thème par défaut

#### **core/services/CourseService.php** (500+ lignes)
**Gestion cours** :
- `getCoursesByEstablishment($establishmentId, $page, $perPage, $filters)` - Cours par établissement
- `getCourseById($id)` - Cours par ID
- `createCourse($data)` - Création cours
- `updateCourse($id, $data)` - Mise à jour
- `deleteCourse($id)` - Suppression
- `enrollUser($userId, $courseId)` - Inscription utilisateur
- `unenrollUser($userId, $courseId)` - Désinscription
- `getUserCourses($userId, $page, $perPage)` - Cours utilisateur
- `getCourseEnrollments($courseId, $page, $perPage)` - Inscriptions cours
- `updateProgress($userId, $courseId, $progress)` - Progression
- `getPopularCourses($establishmentId, $limit)` - Cours populaires
- `searchCourses($searchTerm, $establishmentId, $page, $perPage)` - Recherche
- `getCourseStats($establishmentId)` - Statistiques cours

#### **core/services/AnalyticsService.php** (600+ lignes)
**Analytics & métriques** :
- `getOverview($establishmentId)` - Vue d'ensemble
- `getPopularCourses($establishmentId, $limit)` - Cours populaires avec stats
- `getUserActivities($establishmentId, $limit)` - Activités utilisateurs
- `getEnrollmentStats($establishmentId, $period)` - Statistiques inscriptions
- `getCategoryDistribution($establishmentId)` - Répartition catégories
- `getProgressStats($establishmentId)` - Statistiques progression
- `getInstructorPerformance($establishmentId)` - Performance instructeurs
- `getRealTimeMetrics($establishmentId)` - Métriques temps réel
- `exportAnalytics($establishmentId, $format)` - Export données

#### **core/services/AssessmentService.php** (500+ lignes)
**Évaluations & examens** :
- `getAssessmentsByEstablishment($establishmentId, $page, $perPage, $filters)` - Liste
- `getAssessmentById($id)` - Par ID avec questions JSON
- `createAssessment($data)` - Création avec questions
- `updateAssessment($id, $data)` - Mise à jour
- `deleteAssessment($id)` - Suppression
- `startAttempt($assessmentId, $userId)` - Démarrer tentative
- `submitAnswers($attemptId, $answers)` - Soumettre réponses
- `getUserAttempts($assessmentId, $userId)` - Tentatives utilisateur
- `getAssessmentAttempts($assessmentId, $page, $perPage)` - Toutes tentatives
- `calculateScore($questions, $answers)` - Calcul score automatique
- `getAssessmentStats($assessmentId)` - Statistiques évaluation
- `getGeneralStats($establishmentId)` - Stats générales

#### **core/services/StudyGroupService.php** (500+ lignes)
**Groupes d'étude** :
- `getStudyGroupsByEstablishment($establishmentId, $page, $perPage, $filters)` - Liste
- `getStudyGroupById($id)` - Par ID avec compteur membres
- `createStudyGroup($data)` - Création + créateur auto-modérateur
- `updateStudyGroup($id, $data)` - Mise à jour
- `deleteStudyGroup($id)` - Suppression avec nettoyage
- `joinGroup($groupId, $userId)` - Rejoindre groupe
- `leaveGroup($groupId, $userId)` - Quitter groupe
- `getGroupMembers($groupId, $page, $perPage)` - Membres
- `sendMessage($groupId, $userId, $message)` - Message groupe
- `getGroupMessages($groupId, $page, $perPage)` - Messages
- `getUserGroups($userId, $page, $perPage)` - Groupes utilisateur
- `promoteToModerator($groupId, $userId, $promoterId)` - Promotion modérateur
- `getStudyGroupStats($establishmentId)` - Statistiques

#### **core/services/ExportService.php** (600+ lignes)
**Exports & archives** :
- `createExportJob($data)` - Créer job export
- `getExportJob($id)` - Job par ID
- `getExportJobs($establishmentId, $page, $perPage)` - Liste jobs
- `processExport($jobId)` - Traitement export (simulation arrière-plan)
- `exportUsers($establishmentId, $filters)` - Export utilisateurs
- `exportCourses($establishmentId, $filters)` - Export cours
- `exportAnalytics($establishmentId, $filters)` - Export analytics
- `exportAssessments($establishmentId, $filters)` - Export évaluations
- `exportStudyGroups($establishmentId, $filters)` - Export groupes
- `exportFullBackup($establishmentId)` - Backup complet
- `generateCSV/JSON/XML/PDF/ZIP($data, $filename)` - Formats multiples
- `downloadExport($jobId)` - Téléchargement
- `deleteExportJob($jobId)` - Suppression
- `cleanupOldExports($daysOld)` - Nettoyage

#### **core/services/HelpService.php** (500+ lignes)
**Centre d'aide** :
- `getHelpContentsByEstablishment($establishmentId, $role, $page, $perPage, $filters)` - Contenus
- `getHelpContentById($id)` - Par ID
- `createHelpContent($data)` - Création contenu
- `updateHelpContent($id, $data)` - Mise à jour
- `deleteHelpContent($id)` - Suppression
- `searchHelpContent($establishmentId, $searchTerm, $role, $page, $perPage)` - Recherche avec scoring
- `incrementViewCount($id)` - Compteur vues
- `getCategories($establishmentId)` - Catégories disponibles
- `getPopularContent($establishmentId, $limit, $role)` - Contenu populaire
- `getRecentContent($establishmentId, $limit, $role)` - Contenu récent
- `getContentByCategory($establishmentId, $category, $role, $page, $perPage)` - Par catégorie
- `getFAQ($establishmentId, $role, $limit)` - FAQ
- `reorderContent($establishmentId, $categoryOrders)` - Réorganisation
- `getHelpStats($establishmentId)` - Statistiques
- `duplicateToEstablishment($contentId, $targetEstablishmentId, $authorId)` - Duplication

#### **core/services/SystemService.php** (500+ lignes)
**Administration système** :
- `getSystemInfo()` - Informations système complètes
- `healthCheck()` - Vérification santé système
- `getVersions()` - Versions et historique
- `checkForUpdates()` - Vérification mises à jour
- `recordVersion($version, $changes)` - Enregistrer version
- `getLogs($level, $page, $perPage)` - Consultation logs
- `cleanupLogs($daysOld)` - Nettoyage logs
- `setMaintenanceMode($enabled, $message)` - Mode maintenance
- `isMaintenanceMode()` - Vérifier maintenance
- `optimizeDatabase()` - Optimisation BDD
- `clearCache()` - Nettoyage cache
- `getSystemStats()` - Statistiques système
- `getUptime()` - Temps de fonctionnement

#### **core/services/NotificationService.php** (400+ lignes)
**Notifications** :
- `createNotification($data)` - Création notification
- `getNotificationById($id)` - Par ID
- `getUserNotifications($userId, $page, $perPage, $unreadOnly)` - Utilisateur
- `markAsRead($notificationId, $userId)` - Marquer lu
- `markAllAsRead($userId)` - Tout marquer lu
- `deleteNotification($notificationId, $userId)` - Suppression
- `getUnreadCount($userId)` - Compteur non lus
- `notifyUsers($userIds, $title, $message, $type, $actionUrl)` - Notification multiple
- `notifyEstablishment($establishmentId, $title, $message, $type, $actionUrl, $roles)` - Établissement
- `notifyNewCourseEnrollment($courseId, $userId)` - Auto-notification inscription
- `notifyCourseCompletion($courseId, $userId)` - Auto-notification completion
- `notifyNewAssessment($assessmentId, $userIds)` - Auto-notification évaluation
- `notifyStudyGroupMessage($groupId, $senderUserId, $message)` - Auto-notification message
- `notifySystemMaintenance($message, $scheduledAt)` - Notification maintenance
- `cleanupOldNotifications($daysOld)` - Nettoyage
- `getNotificationStats($userId)` - Statistiques
- `sendEmailNotification($userId, $subject, $message, $actionUrl)` - Email (si configuré)

### 🔗 SYSTÈME DE ROUTAGE

#### **index.php** (150 lignes) - Point d'entrée
**Configuration** :
- Autoloading toutes les classes core et services
- Initialisation Router
- Définition 50+ routes

**Routes publiques** :
- `GET /` → pages/home.php
- `GET /portal` → pages/portal.php (manquante)
- `GET /establishment/{slug}` → pages/establishment.php (manquante)
- `GET /login` → pages/login.php
- `POST /api/auth/login` → api/auth/login.php (manquante)
- `POST /api/auth/register` → api/auth/register.php (manquante)

**Routes authentifiées** (15 pages) :
- Dashboard, courses, admin, super-admin, user-management
- Analytics, assessments, study-groups, help-center
- WYSIWYG-editor, archive-export, system-updates, user-manual

**Routes API authentifiées** (40+ endpoints) :
- Auth : user, logout
- Courses : CRUD + enroll
- Users : CRUD
- Analytics : overview, courses, users, enrollments
- Assessments : CRUD + attempt
- Study-groups : CRUD + join + messages
- Exports : CRUD + download
- Help : CRUD + search
- System : info, health, maintenance
- Establishments : CRUD + themes
- Collaboration : poll, send, stats

### 📊 STRUCTURE DE DONNÉES

#### **Tables Implicites** (Référencées dans services) :
1. `users` - Utilisateurs
2. `establishments` - Établissements  
3. `courses` - Cours
4. `user_courses` - Inscriptions cours
5. `assessments` - Évaluations
6. `assessment_attempts` - Tentatives évaluation
7. `study_groups` - Groupes d'étude
8. `study_group_members` - Membres groupes
9. `study_group_messages` - Messages groupes
10. `themes` - Thèmes établissements
11. `help_contents` - Contenus aide
12. `notifications` - Notifications
13. `export_jobs` - Jobs export
14. `system_versions` - Versions système

#### **Champs Standards** (Auto-gérés) :
- `id` - Clé primaire
- `created_at` - Date création
- `updated_at` - Date modification
- `is_active` - Statut actif
- `establishment_id` - Multi-tenant

### 🔐 SYSTÈME DE SÉCURITÉ

#### **Authentification** :
- Hachage Argon2ID sécurisé
- Sessions sécurisées avec régénération ID
- Middleware authentification automatique
- Mise à jour last_login_at

#### **Autorisation** :
- Système 5 rôles hiérarchiques
- Permissions granulaires par établissement
- Vérifications rôles dans services
- Isolation multi-tenant stricte

#### **Protection** :
- Tokens CSRF pour formulaires
- Validation données entrantes (Validator)
- Sanitisation XSS (Utils::sanitize)
- Requêtes préparées PDO
- Upload fichiers sécurisé

#### **Sessions** :
- Configuration sécurisée PHP
- Lifetime 24h configurable
- Nettoyage automatique logout
- Protection fixation session

### 📈 ANALYTICS & MONITORING

#### **Métriques disponibles** :
- Vue d'ensemble : utilisateurs, cours, inscriptions
- Cours populaires avec stats détaillées  
- Activités utilisateurs temps réel
- Statistiques inscriptions par période
- Répartition catégories cours
- Progression et taux completion
- Performance instructeurs
- Métriques système

#### **Logging** :
- Logs par niveau (DEBUG, INFO, ERROR)
- Rotation automatique
- Consultation via SystemService
- Nettoyage configuré

#### **Cache** :
- Cache fichier simple
- TTL configurable
- Méthodes CRUD complètes
- Nettoyage automatique

### 🔄 FONCTIONNALITÉS TEMPS RÉEL

#### **Collaboration** (Simulation) :
- Long polling configuré (2s)
- Salles par type (course, studygroup, whiteboard, assessment)
- Messages temps réel
- Limite participants (50)
- Historique messages (100)

#### **Notifications** :
- Notifications automatiques (inscription, completion, etc.)
- Support email si configuré
- Nettoyage automatique
- Statistiques complètes

### 📤 EXPORTS & ARCHIVES

#### **Formats supportés** :
- CSV avec délimiteur configurable
- JSON formaté
- XML avec structure
- HTML/PDF (basique)
- ZIP avec multiple formats

#### **Types exports** :
- Utilisateurs avec filtres
- Cours avec métadonnées
- Analytics complètes
- Évaluations et tentatives
- Groupes d'étude
- Backup complet établissement

### 🛠️ UTILITAIRES & HELPERS

#### **Fonctions globales manquantes** :
- `generateCSRFToken()` - Référencée mais non définie
- Gestion mode maintenance (fichier JSON)
- Migrations base de données
- Seeder données de test

#### **APIs manquantes** :
- Tous les endpoints définis dans Router (40+)
- WebSocket réel (actuellement simulation)
- Authentification OAuth/SAML
- Intégrations externes

### 📋 RÉSUMÉ COMPTEURS BACKEND

- **Fichiers configuration** : 2 (config.php, database.php)
- **Classes core** : 5 (Database, Router, Auth, Validator, Utils)
- **Services métier** : 10 services complets
- **Méthodes total** : 200+ méthodes publiques
- **Routes définies** : 50+ routes (15 pages + 40+ API)
- **Tables implicites** : 14 tables principales
- **Constantes config** : 30+ constantes
- **Fonctionnalités sécurité** : 8 niveaux protection
- **Formats export** : 5 formats supportés
- **Rôles & permissions** : 5 niveaux hiérarchiques
- **Validations** : 15+ règles validation
- **Logs & monitoring** : 4 niveaux logs + analytics

### ⚠️ ÉLÉMENTS MANQUANTS CRITIQUES

#### **APIs non implémentées** :
- Dossier `/api/` complet (40+ endpoints)
- Authentification endpoints
- CRUD endpoints tous services
- WebSocket réel

#### **Pages manquantes** :
- 15 pages référencées non créées
- Formulaires CRUD
- Interfaces admin avancées

#### **Infrastructure** :
- Migrations base données
- Seeds données test  
- Scripts déploiement
- Configuration serveur

#### **Sécurité avancée** :
- Rate limiting implémentation
- Authentification 2FA
- Audit logs sécurité
- Backup automatisé
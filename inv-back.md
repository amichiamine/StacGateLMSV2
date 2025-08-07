# INVENTAIRE BACKEND - StacGateLMS
*Généré le 07/08/2025 - Analyse complète après réorganisation*

## ARCHITECTURE BACKEND

### Structure des dossiers
```
server/
├── middleware/             # Middlewares Express
├── services/               # Couche logique métier  
├── database-manager.ts     # Gestionnaire base multi-tenant
├── db.ts                   # Configuration base de données
├── index.ts                # Point d'entrée serveur
├── routes.ts               # Définition des routes API
├── storage.ts              # Couche d'accès aux données
├── init-database.ts        # Initialisation base
├── replitAuth.ts           # Authentification Replit
├── establishment-service.ts # Service établissements
└── vite.ts                 # Serveur Vite intégré
```

## INVENTAIRE DÉTAILLÉ DU BACKEND

### 1. COUCHE DE SERVICES (4 services)

#### Services métier nouvellement créés
1. **AuthService.ts** (257 lignes)
   - `authenticateUser()` - Authentification utilisateur
   - `hashPassword()` - Hashage de mots de passe  
   - `createUser()` - Création d'utilisateur avec hash
   - `updateUserPassword()` - Mise à jour mot de passe
   - `verifyPermission()` - Vérification des permissions

2. **CourseService.ts** (108 lignes)
   - `getCoursesForUser()` - Cours selon rôle utilisateur
   - `createCourse()` - Création de cours
   - `approveCourse()` - Approbation de cours
   - `getCourseStatistics()` - Statistiques des cours
   - `enrollUserInCourse()` - Inscription utilisateur

3. **EstablishmentService.ts** (132 lignes)
   - `getEstablishmentWithCustomization()` - Établissement + personnalisation
   - `updateEstablishmentBranding()` - Mise à jour du branding
   - `createEstablishmentWithDefaults()` - Création avec config par défaut
   - `getEstablishmentStatistics()` - Statistiques établissement

4. **NotificationService.ts** (120 lignes)
   - `createUserNotification()` - Notification utilisateur
   - `createBulkNotifications()` - Notifications groupées
   - `notifyCourseEnrollment()` - Notification inscription cours
   - `notifyCourseApproval()` - Notification approbation cours
   - `notifyAssessmentGraded()` - Notification note évaluation
   - `notifySystemUpdate()` - Notification mise à jour système
   - `getUserNotificationSummary()` - Résumé notifications utilisateur

### 2. COUCHE D'ACCÈS AUX DONNÉES (80+ méthodes)

#### Gestion des Établissements (10 méthodes)
1. `getAllEstablishments()` - Liste tous les établissements
2. `getEstablishment(id)` - Établissement par ID
3. `getEstablishmentBySlug(slug)` - Établissement par slug
4. `createEstablishment(data)` - Création établissement
5. `updateEstablishment(id, data)` - Mise à jour établissement
6. `deleteEstablishment(id)` - Suppression établissement
7. `getEstablishmentBranding(id)` - Branding établissement
8. `updateEstablishmentBranding(id, data)` - MAJ branding
9. `getEstablishmentSettings(id)` - Paramètres établissement
10. `updateEstablishmentSettings(id, data)` - MAJ paramètres

#### Gestion des Utilisateurs (15 méthodes)
11. `getUser(id)` - Utilisateur par ID
12. `getUserByUsername(username, estId)` - Par nom utilisateur
13. `getUserByEmail(email, estId)` - Par email
14. `createUser(data)` - Création utilisateur
15. `updateUser(id, data)` - Mise à jour utilisateur
16. `deleteUser(id)` - Suppression utilisateur
17. `updateUserLastLogin(id)` - MAJ dernière connexion
18. `getUsersByEstablishment(estId)` - Utilisateurs d'un établissement
19. `getUsersByRole(role, estId)` - Utilisateurs par rôle
20. `activateUser(id)` - Activation utilisateur
21. `deactivateUser(id)` - Désactivation utilisateur
22. `getUserProfile(id)` - Profil utilisateur complet
23. `updateUserProfile(id, data)` - MAJ profil
24. `getUserPreferences(id)` - Préférences utilisateur
25. `updateUserPreferences(id, data)` - MAJ préférences

#### Gestion des Thèmes (8 méthodes)
26. `getThemes(estId)` - Thèmes d'un établissement  
27. `getActiveTheme(estId)` - Thème actif
28. `createTheme(data)` - Création thème
29. `updateTheme(id, data)` - Mise à jour thème
30. `deleteTheme(id)` - Suppression thème
31. `activateTheme(id)` - Activation thème
32. `cloneTheme(id, newName)` - Clonage thème
33. `getThemePresets()` - Thèmes prédéfinis

#### Gestion du Contenu Personnalisable (10 méthodes)
34. `getCustomizableContents(estId)` - Contenus personnalisables
35. `getCustomizableContent(estId, key)` - Contenu par clé
36. `createCustomizableContent(data)` - Création contenu
37. `updateCustomizableContent(id, data)` - MAJ contenu
38. `deleteCustomizableContent(id)` - Suppression contenu
39. `getCustomizablePages(estId)` - Pages personnalisables
40. `getCustomizablePageByName(estId, name)` - Page par nom
41. `createCustomizablePage(data)` - Création page
42. `updateCustomizablePage(id, data)` - MAJ page
43. `getPageComponents(estId)` - Composants de page

#### Gestion des Menus (8 méthodes)
44. `getMenuItems(estId)` - Éléments de menu
45. `getMenuItem(id)` - Élément par ID
46. `createMenuItem(data)` - Création élément menu
47. `updateMenuItem(id, data)` - MAJ élément menu
48. `deleteMenuItem(id)` - Suppression élément menu
49. `reorderMenuItems(estId, order)` - Réorganisation menu
50. `getMenuTree(estId)` - Arborescence menu
51. `getActiveMenuItems(estId)` - Éléments actifs seulement

#### Gestion des Cours (12 méthodes)
52. `getCourse(id)` - Cours par ID
53. `getCoursesByEstablishment(estId)` - Cours d'un établissement
54. `getCoursesByCategory(cat, estId)` - Cours par catégorie
55. `createCourse(data)` - Création cours
56. `updateCourse(id, data)` - Mise à jour cours
57. `deleteCourse(id)` - Suppression cours
58. `approveCourse(id, approvedBy)` - Approbation cours
59. `getPublicCourses(estId)` - Cours publics
60. `searchCourses(query, estId)` - Recherche cours
61. `getCourseModules(courseId)` - Modules d'un cours
62. `enrollUserInCourse(userId, courseId)` - Inscription cours
63. `getUserCourseProgress(userId, courseId)` - Progression utilisateur

#### Gestion des Espaces Formateurs (5 méthodes)
64. `getTrainerSpaces(estId)` - Espaces formateurs
65. `getTrainerSpace(id)` - Espace par ID
66. `createTrainerSpace(data)` - Création espace
67. `approveTrainerSpace(id, approvedBy)` - Approbation espace
68. `getTrainerSpacesByUser(userId)` - Espaces d'un formateur

#### Gestion des Évaluations (15 méthodes)
69. `getAssessmentsByEstablishment(estId)` - Évaluations d'un établissement
70. `getAssessment(id)` - Évaluation par ID
71. `createAssessment(data)` - Création évaluation
72. `updateAssessment(id, data)` - MAJ évaluation
73. `approveAssessment(id, approvedBy)` - Approbation évaluation
74. `getAssessmentAttempts(assessId, userId?)` - Tentatives d'évaluation
75. `createAssessmentAttempt(data)` - Création tentative
76. `submitAssessmentAttempt(id, answers, score)` - Soumission tentative
77. `gradeAssessmentAttempt(id, grade, feedback)` - Notation tentative
78. `getAssessmentResults(assessId)` - Résultats évaluation
79. `getAssessmentStatistics(assessId)` - Statistiques évaluation
80. `getPendingAssessments(estId)` - Évaluations en attente
81. `getAssessmentsByUser(userId)` - Évaluations d'un utilisateur
82. `getAssessmentsByInstructor(instructorId)` - Évaluations d'un formateur
83. `duplicateAssessment(id, newTitle)` - Duplication évaluation

#### Gestion des Certificats (5 méthodes)
84. `generateCertificate(userId, courseId, data)` - Génération certificat
85. `getCertificate(id)` - Certificat par ID
86. `getUserCertificates(userId)` - Certificats d'un utilisateur
87. `verifyCertificate(code)` - Vérification certificat
88. `revokeCertificate(id, reason)` - Révocation certificat

#### Gestion des Notifications (10 méthodes)
89. `getUserNotifications(userId)` - Notifications utilisateur
90. `createNotification(data)` - Création notification
91. `markNotificationAsRead(id, userId)` - Marquer comme lu
92. `markAllNotificationsAsRead(userId)` - Tout marquer comme lu
93. `deleteNotification(id, userId)` - Suppression notification
94. `getNotificationsByType(type, userId)` - Par type
95. `getUnreadNotificationsCount(userId)` - Nombre de non lues
96. `createBulkNotifications(userIds, data)` - Création groupée
97. `getNotificationSettings(userId)` - Paramètres notifications
98. `updateNotificationSettings(userId, settings)` - MAJ paramètres

### 3. ENDPOINTS API (50+ routes)

#### Authentification (8 routes)
- `POST /api/auth/login` - Connexion
- `POST /api/auth/logout` - Déconnexion  
- `GET /api/auth/user` - Utilisateur connecté
- `POST /api/auth/register` - Inscription
- `POST /api/auth/forgot-password` - Mot de passe oublié
- `POST /api/auth/reset-password` - Reset mot de passe
- `POST /api/auth/change-password` - Changement mot de passe
- `POST /api/auth/verify-email` - Vérification email

#### Établissements (10 routes)
- `GET /api/establishments` - Liste établissements
- `GET /api/establishments/:id` - Établissement par ID
- `POST /api/establishments` - Création établissement
- `PUT /api/establishments/:id` - MAJ établissement  
- `DELETE /api/establishments/:id` - Suppression établissement
- `GET /api/establishments/:id/branding` - Branding
- `PUT /api/establishments/:id/branding` - MAJ branding
- `GET /api/establishments/:id/settings` - Paramètres
- `PUT /api/establishments/:id/settings` - MAJ paramètres
- `GET /api/establishments/:id/stats` - Statistiques

#### Utilisateurs (12 routes)
- `GET /api/users` - Liste utilisateurs
- `GET /api/users/:id` - Utilisateur par ID
- `POST /api/users` - Création utilisateur
- `PUT /api/users/:id` - MAJ utilisateur
- `DELETE /api/users/:id` - Suppression utilisateur
- `GET /api/users/:id/profile` - Profil utilisateur
- `PUT /api/users/:id/profile` - MAJ profil
- `GET /api/users/:id/preferences` - Préférences
- `PUT /api/users/:id/preferences` - MAJ préférences
- `POST /api/users/:id/activate` - Activation
- `POST /api/users/:id/deactivate` - Désactivation
- `GET /api/users/:id/dashboard` - Dashboard utilisateur

#### Cours (15 routes)
- `GET /api/courses` - Liste des cours
- `GET /api/courses/:id` - Cours par ID
- `POST /api/courses` - Création cours
- `PUT /api/courses/:id` - MAJ cours
- `DELETE /api/courses/:id` - Suppression cours
- `POST /api/courses/:id/approve` - Approbation cours
- `POST /api/courses/:id/enroll` - Inscription cours
- `GET /api/courses/:id/modules` - Modules du cours
- `GET /api/courses/category/:cat` - Cours par catégorie
- `GET /api/courses/search` - Recherche cours
- `GET /api/courses/:id/progress` - Progression cours
- `GET /api/courses/:id/stats` - Statistiques cours
- `POST /api/courses/:id/duplicate` - Duplication cours
- `GET /api/courses/public` - Cours publics
- `GET /api/courses/my-courses` - Mes cours

#### Évaluations (12 routes)  
- `GET /api/assessments` - Liste évaluations
- `GET /api/assessments/:id` - Évaluation par ID
- `POST /api/assessments` - Création évaluation
- `PUT /api/assessments/:id` - MAJ évaluation
- `DELETE /api/assessments/:id` - Suppression évaluation
- `POST /api/assessments/:id/approve` - Approbation évaluation
- `GET /api/assessments/:id/attempts` - Tentatives évaluation
- `POST /api/assessments/:id/attempt` - Nouvelle tentative
- `PUT /api/assessments/attempts/:id/submit` - Soumission tentative
- `PUT /api/assessments/attempts/:id/grade` - Notation tentative
- `GET /api/assessments/:id/results` - Résultats évaluation
- `GET /api/assessments/pending` - Évaluations en attente

#### WebSocket (Temps réel)
- **Connexions WebSocket** pour groupes d'étude
- **Messages temps réel** entre utilisateurs
- **Notifications push** en direct
- **Collaboration en temps réel** sur documents

### 4. SCHÉMA DE BASE DE DONNÉES (16 tables principales)

#### Tables core système
1. **establishments** - Établissements d'enseignement
2. **users** - Utilisateurs de la plateforme
3. **simple_themes** - Thèmes visuels personnalisables
4. **simple_customizable_contents** - Contenus personnalisables
5. **simple_menu_items** - Éléments de menu personnalisés

#### Tables contenu éducatif
6. **courses** - Cours et formations
7. **course_modules** - Modules de cours
8. **trainer_spaces** - Espaces des formateurs
9. **training_sessions** - Sessions de formation
10. **assessments** - Évaluations et examens
11. **assessment_attempts** - Tentatives d'évaluation
12. **certificates** - Certificats générés

#### Tables interaction utilisateur
13. **user_courses** - Inscriptions aux cours
14. **user_module_progress** - Progression dans les modules
15. **notifications** - Notifications utilisateur
16. **educational_plugins** - Plugins éducatifs

#### Tables avancées (nouveaux)
17. **help_contents** - Contenus d'aide
18. **system_versions** - Versions du système
19. **establishment_branding** - Branding des établissements
20. **study_groups** - Groupes d'étude
21. **study_group_members** - Membres des groupes
22. **study_group_messages** - Messages des groupes
23. **export_jobs** - Tâches d'export

### 5. MIDDLEWARE ET SÉCURITÉ

#### Middleware d'authentification (auth.ts)
- **Vérification des sessions** utilisateur
- **Contrôle des rôles** et permissions
- **Protection des routes** sensibles
- **Gestion des tokens** de session

#### Sécurité implémentée
- **Hashage des mots de passe** avec bcrypt
- **Sessions sécurisées** avec express-session
- **Validation des données** avec Zod
- **Protection CORS** configurée
- **Sanitisation des entrées** utilisateur

### 6. TECHNOLOGIES BACKEND

#### Stack principal
- **Node.js** - Runtime JavaScript
- **Express.js** - Framework web
- **TypeScript** - Typage statique
- **Drizzle ORM** - Mapping objet-relationnel
- **PostgreSQL** - Base de données
- **WebSocket** - Communication temps réel

#### Authentification & Sécurité
- **bcryptjs** - Hashage mots de passe
- **express-session** - Gestion sessions
- **connect-pg-simple** - Store sessions PostgreSQL
- **passport** - Authentification multi-stratégies

#### Utilitaires & Outils
- **nanoid** - Génération d'IDs uniques
- **date-fns** - Manipulation dates
- **zod** - Validation schémas
- **memoizee** - Cache fonctions

## ANALYSE DE COMPATIBILITÉ FRONTEND ↔ BACKEND

### COMPATIBILITÉS CONFIRMÉES ✅

#### Authentification
- **Sessions partagées** entre frontend et backend
- **Types d'utilisateurs** synchronisés via shared/schema.ts
- **États d'authentification** cohérents (useAuth ↔ routes auth)

#### Gestion des données
- **Schémas Zod partagés** pour validation côtés client/serveur
- **Types TypeScript** identiques via shared/schema.ts
- **Structure des réponses API** conforme aux attentes frontend

#### Fonctionnalités utilisateur
- **Pages admin** ↔ endpoints administration
- **Gestion cours** ↔ CourseService + routes courses
- **Notifications** ↔ NotificationService + WebSocket
- **Établissements** ↔ EstablishmentService + routes establishments

### INCOMPATIBILITÉS IDENTIFIÉES ❌

#### Méthodes manquantes côté backend
- **`getPendingAssessmentsByEstablishment()`** - Référencée côté routes mais non implémentée
- **`createUserCourse()`** - Appelée dans CourseService mais n'existe pas
- **`getNotificationsByUser()`** - Utilisée dans NotificationService mais non définie

#### Champs de schéma incohérents  
- **Champs `status`** - Référencés dans le code mais absents de certaines tables
- **Champ `metadata`** - Utilisé dans notifications mais non défini dans schéma
- **Champs `isActive`** - Présence incohérente entre tables

#### Types et imports manquants
- **Tables permissions** - Référencées mais non importées correctement
- **Types Assessment/AssessmentAttempt** - Doublons et références manquantes
- **Imports nanoid** - Utilisé mais non importé dans schema.ts

## RÉSUMÉ STATISTIQUE

### Couches et services
- **Services métier** : 4 (AuthService, CourseService, EstablishmentService, NotificationService)
- **Méthodes storage** : 98+ méthodes d'accès aux données
- **Routes API** : 57+ endpoints REST
- **Tables de base** : 23 tables principales + relations

### Lignes de code
- **Fichier principal routes.ts** : ~3800 lignes
- **Fichier storage.ts** : ~2200 lignes  
- **Services combinés** : ~617 lignes
- **Configuration et utils** : ~500 lignes
- **Total backend estimé** : ~7100+ lignes

### Fonctionnalités supportées
- **Multi-tenant** complet avec isolation des données
- **Authentification robuste** avec rôles et permissions
- **API REST complète** pour toutes les fonctionnalités
- **WebSocket** pour interactions temps réel
- **Système de notifications** avancé
- **Gestion de fichiers** et exports
- **Personnalisation interface** par établissement

---
*Inventaire généré automatiquement - StacGateLMS Backend Analysis*
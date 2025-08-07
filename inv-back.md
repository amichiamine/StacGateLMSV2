# INVENTAIRE BACKEND - ARCHITECTURE CONSOLIDÉE

## 📊 RÉSUMÉ ARCHITECTURE BACKEND

**Analyse effectuée le :** 07/08/2025
**Architecture :** SERVER/ (structure unique consolidée)
**Total fichiers :** 15 fichiers TypeScript
**Status serveur :** ✅ Fonctionnel (Express port 5000)
**Erreurs LSP :** ❌ 71 erreurs dans storage.ts (nouvelles erreurs détectées)

---

## 🏗️ STRUCTURE BACKEND CONSOLIDÉE

```
server/
├── services/              # 4 services métier spécialisés
│   ├── AuthService.ts        # Authentification & sécurité
│   ├── CourseService.ts      # Gestion cours & formations
│   ├── EstablishmentService.ts # Multi-tenant management
│   ├── NotificationService.ts  # Système notifications
│   └── index.ts              # Export centralisé
├── middleware/            # Couche sécurité
│   └── auth.ts              # Middleware authentification
├── routes.ts              # 154+ endpoints API REST
├── storage.ts             # Interface data access (❌ 71 erreurs)
├── db.ts                  # Configuration PostgreSQL
├── database-manager.ts    # Gestion multi-tenant BDD
├── establishment-service.ts # Service établissement legacy
├── index.ts              # Point d'entrée Express
├── init-database.ts      # Initialisation BDD
├── replitAuth.ts         # Auth Replit (legacy)
└── vite.ts               # Intégration frontend
```

---

## 🚀 SERVICES MÉTIER (4 SERVICES)

### **AuthService.ts - Authentification & Sécurité**
| Méthode | Fonction | Status |
|---------|----------|--------|
| `authenticateUser()` | Auth multi-tenant | ✅ |
| `hashPassword()` | Hash bcrypt sécurisé | ✅ |
| `createUser()` | Création avec hash | ✅ |
| `updateUserPassword()` | Mise à jour MDP | ✅ |
| `verifyPermission()` | RBAC hiérarchique | ✅ |

### **CourseService.ts - Formation & Cours**
| Méthode | Fonction | Status |
|---------|----------|--------|
| `createCourse()` | Création cours | ✅ |
| `getCoursesByEstablishment()` | Cours par établissement | ✅ |
| `enrollUserInCourse()` | Inscription utilisateur | ✅ |
| `updateCourseProgress()` | Progression tracking | ✅ |
| `generateCertificate()` | Génération certificats | ✅ |

### **EstablishmentService.ts - Multi-Tenant**
| Méthode | Fonction | Status |
|---------|----------|--------|
| `createEstablishment()` | Création établissement | ✅ |
| `updateEstablishment()` | Mise à jour | ✅ |
| `getEstablishmentBySlug()` | Récupération par slug | ✅ |
| `activateEstablishment()` | Activation/désactivation | ✅ |
| `getEstablishmentSettings()` | Configuration | ✅ |

### **NotificationService.ts - Communication**
| Méthode | Fonction | Status |
|---------|----------|--------|
| `createNotification()` | Création notification | ✅ |
| `getUserNotifications()` | Récupération utilisateur | ✅ |
| `markAsRead()` | Marquer comme lu | ✅ |
| `sendBulkNotifications()` | Notifications masse | ✅ |
| `getNotificationsByType()` | Filtrage par type | ✅ |

---

## 🛠️ INTERFACE STORAGE (138+ MÉTHODES)

### **Establishments Operations**
- `getEstablishment()`, `getEstablishmentBySlug()`, `createEstablishment()`, `getAllEstablishments()`

### **User Operations** 
- `getUser()`, `getUserByUsername()`, `getUserByEmail()`, `createUser()`, `updateUser()`, `deleteUser()`
- `updateUserLastLogin()`, `getUsersByEstablishment()`, `getAllUsers()`, `upsertUser()`

### **Course Operations**
- `getCourse()`, `getCoursesByEstablishment()`, `getCoursesByCategory()`, `createCourse()`, `updateCourse()`
- `deleteCourse()`, `approveCourse()`

### **Assessment Operations**
- `getAssessment()`, `createAssessment()`, `updateAssessment()`, `deleteAssessment()`
- `getAssessmentsByEstablishment()`, `getAssessmentsByModule()`

### **User Course Enrollment**
- `getUserCourse()`, `createUserCourseEnrollment()`, `updateUserCourseProgress()`
- `getUserCoursesByUser()`, `getUserCoursesByCourse()`

### **Theme & Customization**
- `getActiveTheme()`, `getThemesByEstablishment()`, `createTheme()`, `updateTheme()`, `activateTheme()`
- `getCustomizableContents()`, `getCustomizableContentByKey()`, `createCustomizableContent()`

### **Study Groups & Collaboration**
- `getStudyGroup()`, `createStudyGroup()`, `updateStudyGroup()`, `deleteStudyGroup()`
- `getStudyGroupsByEstablishment()`, `getStudyGroupsByUser()`, `joinStudyGroup()`, `leaveStudyGroup()`

---

## 🔗 API ROUTES (154+ ENDPOINTS)

### **Authentication Routes**
```typescript
app.get('/api/auth/user')           // Current user
app.post('/api/auth/logout')        // Déconnexion
app.post('/api/auth/login')         // Connexion locale
app.post('/api/auth/register')      // Inscription
```

### **Establishments Routes**
```typescript
app.get('/api/establishments')               // Liste publique
app.get('/api/establishments/slug/:slug')   // Par slug
app.get('/api/establishment-content/:slug/:pageType') // Contenu
```

### **Courses Routes**
```typescript
app.get('/api/courses/:id')                    // Détail cours
app.post('/api/courses')                       // Créer cours
app.get('/api/users/:userId/courses')          // Cours utilisateur
app.post('/api/users/:userId/courses')         // Inscription
app.patch('/api/users/:userId/courses/:courseId/progress') // Progression
```

### **Admin Routes**
```typescript
app.get('/api/admin/themes')                   // Thèmes
app.post('/api/admin/themes')                  // Créer thème
app.post('/api/admin/themes/:themeId/activate') // Activer
app.get('/api/admin/customizable-contents')    // Contenus
app.patch('/api/admin/customizable-contents/:contentId') // Modifier
app.get('/api/admin/menu-items')               // Menu items
```

### **Study Groups Routes + WebSocket**
```typescript
app.get('/api/study-groups')                   // Liste groupes
app.post('/api/study-groups')                  // Créer groupe
app.post('/api/study-groups/:id/join')         // Rejoindre
app.post('/api/study-groups/:id/leave')        // Quitter
app.get('/api/study-groups/:id/messages')      // Messages
app.post('/api/study-groups/:id/messages')     // Envoyer message
app.put('/api/study-groups/:id/messages/:messageId') // Modifier
app.delete('/api/study-groups/:id/messages/:messageId') // Supprimer

// WebSocket pour temps réel
WebSocket server sur même port pour chat study groups
```

### **Assessment Routes**
```typescript
app.get('/api/assessments')                    // Liste évaluations
app.post('/api/assessments')                   // Créer évaluation
app.get('/api/assessments/:id')                // Détail évaluation
app.put('/api/assessments/:id')                // Modifier évaluation
app.post('/api/assessments/:id/attempt')       // Tentative
app.put('/api/assessment-attempts/:id/submit') // Soumettre
```

### **Export & System Routes**
```typescript
app.post('/api/export/create')                 // Créer job export
app.get('/api/export-jobs')                    // Liste jobs
app.get('/api/export-jobs/:id/download')       // Télécharger
app.get('/api/system/help')                    // Aide système
app.get('/api/system/versions')                // Versions système
```

---

## 🗃️ BASE DE DONNÉES (25+ TABLES)

### **Core Tables Multi-Tenant**
| Table | Fonction | Relations |
|-------|----------|-----------|
| `establishments` | Établissements | → users, courses |
| `users` | Utilisateurs | ← establishments |
| `sessions` | Sessions auth | Index expire |

### **Learning Management Tables**
| Table | Fonction | Relations |
|-------|----------|-----------|
| `courses` | Cours et formations | ← establishments |
| `user_courses` | Inscriptions | ← users, courses |
| `course_modules` | Modules de cours | ← courses |
| `user_module_progress` | Progression | ← users, modules |
| `assessments` | Évaluations | ← courses |
| `assessment_attempts` | Tentatives | ← assessments, users |
| `certificates` | Certifications | ← users, courses |

### **Collaboration Tables**
| Table | Fonction | Relations |
|-------|----------|-----------|
| `studyGroups` | Groupes d'étude | ← establishments |
| `studyGroupMembers` | Membres groupes | ← studyGroups, users |
| `studyGroupMessages` | Messages chat | ← studyGroups, users |
| `whiteboards` | Tableaux blancs | ← studyGroups |

### **Customization Tables**
| Table | Fonction | Relations |
|-------|----------|-----------|
| `themes` | Thèmes visuels | ← establishments |
| `customizable_contents` | Contenu WYSIWYG | ← establishments |
| `menu_items` | Menus dynamiques | ← establishments |
| `establishment_branding` | Branding avancé | ← establishments |

---

## ⚙️ MIDDLEWARE & SÉCURITÉ

### **Middleware Auth (auth.ts)**
```typescript
requireAuth()            // Authentification requise
requireSuperAdmin()      // Super admin requis  
requireAdmin()           // Admin requis
requireEstablishmentAccess() // Accès établissement
```

### **Session Management**
```typescript
express-session avec:
- secret: SESSION_SECRET
- cookie: secure, httpOnly, maxAge 24h
- store: memory (dev) / redis (prod)
- rolling: true (extend session)
```

### **RBAC System**
```typescript
Hiérarchie des rôles:
- super_admin (niveau 5)
- admin (niveau 4)  
- manager (niveau 3)
- formateur (niveau 2)
- apprenant (niveau 1)
```

---

## 📡 WEBSOCKET & TEMPS RÉEL

### **WebSocket Server**
```typescript
WebSocketServer intégré dans Express
- Chat temps réel study groups
- Notifications push
- Collaboration whiteboard
- Status utilisateurs online
```

### **Message Types**
```typescript
messageTypeEnum:
- "text", "file", "image", "link"  
- "poll", "whiteboard"
```

---

## 🚨 PROBLÈMES CRITIQUES DÉTECTÉS

### ❌ **Storage.ts - 71 Erreurs LSP**
**Nouvelles erreurs après consolidation :**
- Types manquants ou mal importés
- Méthodes avec signatures incorrectes  
- Références à tables inexistantes
- Interface IStorage incomplète

### ⚠️ **Incohérences Nomenclature**
- Tables BDD : `snake_case` (user_courses)
- Interface storage : `camelCase` (getUserCourses)
- Types TypeScript : Mélange conventions

### 🔧 **Architecture Legacy**
- `establishment-service.ts` - Service dupliqué
- `replitAuth.ts` - Auth legacy non utilisé
- `init-database.ts` - Init manuelle BDD

---

## 📊 MÉTRIQUES BACKEND

### **Code Base**
- **15 fichiers** TypeScript total
- **138+ méthodes** dans storage interface
- **154+ routes** API REST + WebSocket
- **4 services** métier spécialisés
- **25+ tables** PostgreSQL avec relations

### **API Coverage**
- **Auth** : 4 endpoints (login, logout, register, user)
- **Establishments** : 3 endpoints publics
- **Courses** : 15+ endpoints CRUD + inscription
- **Admin** : 20+ endpoints gestion
- **Study Groups** : 10+ endpoints + WebSocket
- **Assessments** : 8+ endpoints évaluation
- **Export** : 5+ endpoints archivage

### **Performance**
- **Express** : Serveur HTTP performant
- **PostgreSQL** : Base relationnelle optimisée
- **Drizzle ORM** : ORM type-safe moderne
- **WebSocket** : Communication temps réel
- **Sessions** : Gestion état utilisateur

---

## ✅ POINTS FORTS

### **Architecture Robuste**
- Multi-tenant complet et fonctionnel
- Services métier bien structurés  
- API REST complète (154+ endpoints)
- WebSocket temps réel intégré
- RBAC granulaire et sécurisé

### **Stack Moderne** 
- TypeScript pour type safety
- Drizzle ORM avec migrations
- Express.js performant
- bcryptjs pour sécurité
- Zod pour validation

### **Fonctionnalités LMS**
- Gestion cours complète
- Système évaluation avancé
- Collaboration temps réel
- Export/archivage données
- Personnalisation multi-tenant

---

## ❌ DÉFIS CRITIQUES

### **Erreurs Techniques**
- 71 erreurs LSP dans storage.ts
- Types incohérents après consolidation
- Interface storage incomplète
- Nomenclature mixte (snake_case/camelCase)

### **Code Legacy**  
- Services dupliqués (establishment-service.ts)
- Auth Replit non utilisé (replitAuth.ts)
- Init manuelle BDD (init-database.ts)

### **Architecture**
- Interface storage trop monolithique
- Pas d'organisation par domaines
- Configuration éparpillée
- Manque tests unitaires

---

## 🎯 PRIORITÉS CORRECTIVES

### **URGENT - Storage Interface**
1. Corriger 71 erreurs LSP storage.ts
2. Unifier nomenclature snake_case
3. Compléter types manquants
4. Valider méthodes CRUD

### **IMPORTANT - Nettoyage**
1. Supprimer fichiers legacy
2. Consolider services dupliqués
3. Optimiser interface storage
4. Ajouter tests unitaires

### **OPTIMISATION - Architecture**
1. Organiser par domaines métier
2. Séparer couches (routes/services/data)
3. Améliorer error handling
4. Documentation API

---

*Inventaire généré le 07/08/2025 - Architecture SERVER/ consolidée*
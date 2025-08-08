<?php
/**
 * StacGateLMS - PHP Migration
 * Point d'entrée principal de l'application
 * 
 * Système de routage simple et configuration globale
 */

// Configuration des erreurs pour développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrage de la session
session_start();

// Configuration des chemins
define('ROOT_PATH', __DIR__);
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('API_PATH', ROOT_PATH . '/api');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Chargement de la configuration
require_once CONFIG_PATH . '/config.php';
require_once CONFIG_PATH . '/database.php';

// Chargement des classes core
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Router.php';
require_once CORE_PATH . '/Auth.php';
require_once CORE_PATH . '/Validator.php';
require_once CORE_PATH . '/Utils.php';

// Chargement des services
require_once CORE_PATH . '/services/AuthService.php';
require_once CORE_PATH . '/services/EstablishmentService.php';
require_once CORE_PATH . '/services/CourseService.php';
require_once CORE_PATH . '/services/UserService.php';
require_once CORE_PATH . '/services/AnalyticsService.php';
require_once CORE_PATH . '/services/AssessmentService.php';
require_once CORE_PATH . '/services/StudyGroupService.php';
require_once CORE_PATH . '/services/ExportService.php';
require_once CORE_PATH . '/services/HelpService.php';
require_once CORE_PATH . '/services/SystemService.php';
require_once CORE_PATH . '/services/NotificationService.php';

// Initialisation du routeur
$router = new Router();

// Routes publiques
$router->get('/', 'pages/home.php');
$router->get('/portal', 'pages/portal.php');
$router->get('/establishment/{slug}', 'pages/establishment.php');
$router->get('/login', 'pages/login.php');
$router->post('/api/auth/login', 'api/auth/login.php');
$router->post('/api/auth/register', 'api/auth/register.php');
$router->get('/api/establishments', 'api/establishments/index.php');

// Routes authentifiées
$router->get('/dashboard', 'pages/dashboard.php', true);
$router->get('/courses', 'pages/courses.php', true);
$router->get('/admin', 'pages/admin.php', true);
$router->get('/super-admin', 'pages/super-admin.php', true);
$router->get('/user-management', 'pages/user-management.php', true);
$router->get('/analytics', 'pages/analytics.php', true);
$router->get('/assessments', 'pages/assessments.php', true);
$router->get('/study-groups', 'pages/study-groups.php', true);
$router->get('/help-center', 'pages/help-center.php', true);
$router->get('/wysiwyg-editor', 'pages/wysiwyg-editor.php', true);
$router->get('/archive-export', 'pages/archive-export.php', true);
$router->get('/system-updates', 'pages/system-updates.php', true);
$router->get('/user-manual', 'pages/user-manual.php', true);

// Routes API authentifiées
$router->get('/api/auth/user', 'api/auth/user.php', true);
$router->post('/api/auth/logout', 'api/auth/logout.php', true);
$router->get('/api/courses', 'api/courses/index.php', true);
$router->post('/api/courses', 'api/courses/create.php', true);
$router->get('/api/courses/{id}', 'api/courses/show.php', true);
$router->put('/api/courses/{id}', 'api/courses/update.php', true);
$router->delete('/api/courses/{id}', 'api/courses/delete.php', true);
$router->post('/api/courses/{id}/enroll', 'api/courses/enroll.php', true);

// Routes utilisateurs
$router->get('/api/users', 'api/users/index.php', true);
$router->post('/api/users', 'api/users/create.php', true);
$router->get('/api/users/{id}', 'api/users/show.php', true);
$router->put('/api/users/{id}', 'api/users/update.php', true);
$router->delete('/api/users/{id}', 'api/users/delete.php', true);

// Routes analytics
$router->get('/api/analytics/overview', 'api/analytics/overview.php', true);
$router->get('/api/analytics/courses', 'api/analytics/courses.php', true);
$router->get('/api/analytics/users', 'api/analytics/users.php', true);
$router->get('/api/analytics/enrollments', 'api/analytics/enrollments.php', true);

// Routes assessments
$router->get('/api/assessments', 'api/assessments/index.php', true);
$router->post('/api/assessments', 'api/assessments/create.php', true);
$router->get('/api/assessments/{id}', 'api/assessments/show.php', true);
$router->post('/api/assessments/{id}/attempt', 'api/assessments/attempt.php', true);

// Routes study groups
$router->get('/api/study-groups', 'api/study-groups/index.php', true);
$router->post('/api/study-groups', 'api/study-groups/create.php', true);
$router->post('/api/study-groups/{id}/join', 'api/study-groups/join.php', true);
$router->get('/api/study-groups/{id}/messages', 'api/study-groups/messages.php', true);
$router->post('/api/study-groups/{id}/messages', 'api/study-groups/send-message.php', true);

// Routes exports
$router->get('/api/exports', 'api/exports/index.php', true);
$router->post('/api/exports', 'api/exports/create.php', true);
$router->get('/api/exports/{id}/download', 'api/exports/download.php', true);

// Routes help
$router->get('/api/help/contents', 'api/help/index.php', true);
$router->post('/api/help/contents', 'api/help/create.php', true);
$router->get('/api/help/search', 'api/help/search.php', true);

// Routes system
$router->get('/api/system/info', 'api/system/info.php', true);
$router->get('/api/system/health', 'api/system/health.php', true);
$router->post('/api/system/maintenance', 'api/system/maintenance.php', true);

// Routes establishments admin
$router->post('/api/establishments', 'api/establishments/create.php', true);
$router->put('/api/establishments/{id}', 'api/establishments/update.php', true);
$router->get('/api/establishments/{id}/themes', 'api/establishments/themes.php', true);
$router->post('/api/establishments/{id}/themes', 'api/establishments/create-theme.php', true);

// WebSocket simulation via Long Polling
$router->get('/api/collaboration/poll', 'api/collaboration/poll.php', true);
$router->post('/api/collaboration/send', 'api/collaboration/send.php', true);
$router->get('/api/collaboration/stats', 'api/collaboration/stats.php', true);

// Page 404
$router->get('/404', 'pages/not-found.php');

// Traitement de la requête
try {
    $router->handleRequest();
} catch (Exception $e) {
    error_log("Router error: " . $e->getMessage());
    header("Location: /404");
    exit;
}
?>
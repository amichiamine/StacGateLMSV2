<?php
/**
 * Page tableau de bord
 * Correspond à client/src/pages/dashboard.tsx
 */

// Vérifier l'authentification
Auth::requireAuth();

$pageTitle = "Tableau de bord - StacGateLMS";
$pageDescription = "Votre espace personnel d'apprentissage avec vue d'ensemble de vos cours, progression et activités.";

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

// Initialiser les services
$courseService = new CourseService();
$analyticsService = new AnalyticsService();
$userService = new AuthService();

// Obtenir les données selon le rôle
$userCourses = [];
$enrollmentStats = [];
$recentActivities = [];
$systemStats = [];

try {
    if (Auth::hasRole('apprenant')) {
        // Données apprenant
        $userCoursesData = $courseService->getUserCourses($currentUser['id'], 1, 10);
        $userCourses = $userCoursesData['data'];
        
    } elseif (Auth::hasRole('formateur')) {
        // Données formateur - cours qu'il enseigne
        $instructorCourses = $courseService->getCoursesByEstablishment($establishmentId, 1, 10, ['instructor_id' => $currentUser['id']]);
        $userCourses = $instructorCourses['data'];
        
    } elseif (Auth::hasRole('manager') || Auth::hasRole('admin')) {
        // Données gestionnaire/admin - analytics de l'établissement
        $enrollmentStats = $analyticsService->getEnrollmentStats($establishmentId);
        $recentActivities = $analyticsService->getUserActivities($establishmentId, 10);
        
    } elseif (Auth::hasRole('super_admin')) {
        // Données super admin - analytics globales
        $systemStats = $analyticsService->getOverview();
        $recentActivities = $analyticsService->getUserActivities(null, 15);
    }
    
    // Analytics communes pour tous les rôles
    $overview = $analyticsService->getOverview($establishmentId);
    $popularCourses = $analyticsService->getPopularCourses($establishmentId, 5);
    
} catch (Exception $e) {
    Utils::log("Dashboard data loading error: " . $e->getMessage(), 'ERROR');
    $overview = ['users' => ['total' => 0], 'courses' => ['total' => 0], 'enrollments' => ['total' => 0]];
    $popularCourses = [];
}

require_once ROOT_PATH . '/includes/header.php';
?>

<div style="padding: 2rem 0; margin-top: 80px;">
    <div class="container">
        <!-- En-tête de bienvenue -->
        <div class="glassmorphism p-8 mb-8 text-center animate-fade-in">
            <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">
                Bonjour, <?= htmlspecialchars($currentUser['first_name']) ?> !
            </h1>
            <p style="font-size: 1.25rem; opacity: 0.8; margin-bottom: 1.5rem;">
                Bienvenue dans votre espace d'apprentissage
                <?php if ($currentEstablishment): ?>
                    chez <strong><?= htmlspecialchars($currentEstablishment['name']) ?></strong>
                <?php endif; ?>
            </p>
            
            <!-- Badge de rôle -->
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background: rgba(var(--color-primary), 0.1); border-radius: 9999px;">
                <div style="width: 0.5rem; height: 0.5rem; background: rgb(var(--color-primary)); border-radius: 50%;"></div>
                <span style="font-weight: 500; color: rgb(var(--color-primary));">
                    <?= ucfirst(str_replace('_', ' ', $currentUser['role'])) ?>
                </span>
            </div>
        </div>
        
        <!-- Métriques rapides -->
        <div class="grid grid-4 mb-8">
            <div class="glass-card p-6 text-center animate-fade-in" style="animation-delay: 0.1s;">
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.5rem;">
                    <?= $overview['users']['total'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8;">
                    <?php if (Auth::hasRole('super_admin')): ?>
                        Utilisateurs total
                    <?php else: ?>
                        Apprenants
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="glass-card p-6 text-center animate-fade-in" style="animation-delay: 0.2s;">
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-secondary)); margin-bottom: 0.5rem;">
                    <?= $overview['courses']['total'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8;">Cours disponibles</div>
            </div>
            
            <div class="glass-card p-6 text-center animate-fade-in" style="animation-delay: 0.3s;">
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-accent)); margin-bottom: 0.5rem;">
                    <?= $overview['enrollments']['total'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8;">Inscriptions</div>
            </div>
            
            <div class="glass-card p-6 text-center animate-fade-in" style="animation-delay: 0.4s;">
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.5rem;">
                    <?= $overview['users']['active'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8;">Actifs ce mois</div>
            </div>
        </div>
        
        <!-- Contenu spécifique selon le rôle -->
        <div class="grid grid-2">
            <!-- Colonne gauche -->
            <div>
                <?php if (Auth::hasRole('apprenant')): ?>
                    <!-- Mes cours (apprenant) -->
                    <div class="glass-card p-6 mb-6 animate-fade-in" style="animation-delay: 0.5s;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h2 style="font-size: 1.5rem; font-weight: 600;">Mes cours</h2>
                            <a href="/courses" class="glass-button" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                Voir tous
                            </a>
                        </div>
                        
                        <?php if (!empty($userCourses)): ?>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <?php foreach (array_slice($userCourses, 0, 5) as $enrollment): ?>
                                    <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(255, 255, 255, 0.05); border-radius: var(--border-radius);">
                                        <div style="flex: 1;">
                                            <h3 style="font-weight: 600; margin-bottom: 0.5rem;">
                                                <?= htmlspecialchars($enrollment['title']) ?>
                                            </h3>
                                            <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.875rem; opacity: 0.7;">
                                                <span>Progression: <?= number_format($enrollment['progress'], 1) ?>%</span>
                                                <span>•</span>
                                                <span><?= htmlspecialchars($enrollment['category']) ?></span>
                                            </div>
                                        </div>
                                        
                                        <div style="width: 100px;">
                                            <div style="background: rgba(255, 255, 255, 0.1); height: 8px; border-radius: 4px; overflow: hidden;">
                                                <div style="background: rgb(var(--color-primary)); height: 100%; width: <?= $enrollment['progress'] ?>%; transition: width 0.3s ease;"></div>
                                            </div>
                                        </div>
                                        
                                        <a href="/courses/<?= $enrollment['id'] ?>" class="glass-button-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem; text-decoration: none;">
                                            Continuer
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 2rem; opacity: 0.7;">
                                <svg style="width: 3rem; height: 3rem; margin-bottom: 1rem; opacity: 0.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                                <p>Vous n'êtes inscrit à aucun cours pour le moment.</p>
                                <a href="/courses" class="glass-button" style="margin-top: 1rem;">
                                    Découvrir les cours
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                <?php elseif (Auth::hasRole('formateur')): ?>
                    <!-- Mes cours (formateur) -->
                    <div class="glass-card p-6 mb-6 animate-fade-in" style="animation-delay: 0.5s;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h2 style="font-size: 1.5rem; font-weight: 600;">Mes cours enseignés</h2>
                            <a href="/courses" class="glass-button" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                Gérer
                            </a>
                        </div>
                        
                        <?php if (!empty($userCourses)): ?>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <?php foreach (array_slice($userCourses, 0, 5) as $course): ?>
                                    <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(255, 255, 255, 0.05); border-radius: var(--border-radius);">
                                        <div style="flex: 1;">
                                            <h3 style="font-weight: 600; margin-bottom: 0.5rem;">
                                                <?= htmlspecialchars($course['title']) ?>
                                            </h3>
                                            <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.875rem; opacity: 0.7;">
                                                <span><?= $course['enrollment_count'] ?> inscrits</span>
                                                <span>•</span>
                                                <span>Note: <?= number_format($course['rating'], 1) ?>/5</span>
                                            </div>
                                        </div>
                                        
                                        <span class="badge <?= $course['is_active'] ? 'badge-success' : 'badge-warning' ?>">
                                            <?= $course['is_active'] ? 'Actif' : 'Inactif' ?>
                                        </span>
                                        
                                        <a href="/courses/<?= $course['id'] ?>" class="glass-button-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem; text-decoration: none;">
                                            Voir
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 2rem; opacity: 0.7;">
                                <p>Aucun cours créé pour le moment.</p>
                                <a href="/courses" class="glass-button" style="margin-top: 1rem;">
                                    Créer un cours
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                <?php else: ?>
                    <!-- Analytics pour managers/admins -->
                    <div class="glass-card p-6 mb-6 animate-fade-in" style="animation-delay: 0.5s;">
                        <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                            Statistiques d'activité
                        </h2>
                        
                        <div class="grid grid-2" style="gap: 1rem;">
                            <div style="text-align: center; padding: 1rem; background: rgba(var(--color-primary), 0.1); border-radius: var(--border-radius);">
                                <div style="font-size: 1.5rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.5rem;">
                                    <?= $overview['users']['activity_rate'] ?? 0 ?>%
                                </div>
                                <div style="font-size: 0.875rem; opacity: 0.8;">Taux d'activité</div>
                            </div>
                            
                            <div style="text-align: center; padding: 1rem; background: rgba(var(--color-secondary), 0.1); border-radius: var(--border-radius);">
                                <div style="font-size: 1.5rem; font-weight: 700; color: rgb(var(--color-secondary)); margin-bottom: 0.5rem;">
                                    <?= $overview['courses']['activation_rate'] ?? 0 ?>%
                                </div>
                                <div style="font-size: 0.875rem; opacity: 0.8;">Cours actifs</div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 1.5rem;">
                            <a href="/analytics" class="glass-button" style="width: 100%;">
                                Voir l'analytics complète
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Actions rapides -->
                <div class="glass-card p-6 animate-fade-in" style="animation-delay: 0.7s;">
                    <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">Actions rapides</h2>
                    
                    <div class="grid grid-2" style="gap: 1rem;">
                        <?php if (Auth::hasRole('apprenant')): ?>
                            <a href="/courses" class="glass-button-secondary" style="padding: 1rem; text-align: center; text-decoration: none; display: block;">
                                <svg style="width: 1.5rem; height: 1.5rem; margin-bottom: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                                <div>Parcourir les cours</div>
                            </a>
                            
                            <a href="/study-groups" class="glass-button-secondary" style="padding: 1rem; text-align: center; text-decoration: none; display: block;">
                                <svg style="width: 1.5rem; height: 1.5rem; margin-bottom: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <div>Groupes d'étude</div>
                            </a>
                            
                        <?php elseif (Auth::hasRole('formateur')): ?>
                            <a href="/courses" class="glass-button-secondary" style="padding: 1rem; text-align: center; text-decoration: none; display: block;">
                                <svg style="width: 1.5rem; height: 1.5rem; margin-bottom: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <div>Créer un cours</div>
                            </a>
                            
                            <a href="/assessments" class="glass-button-secondary" style="padding: 1rem; text-align: center; text-decoration: none; display: block;">
                                <svg style="width: 1.5rem; height: 1.5rem; margin-bottom: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>Évaluations</div>
                            </a>
                            
                        <?php else: ?>
                            <a href="/user-management" class="glass-button-secondary" style="padding: 1rem; text-align: center; text-decoration: none; display: block;">
                                <svg style="width: 1.5rem; height: 1.5rem; margin-bottom: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                                <div>Gestion utilisateurs</div>
                            </a>
                            
                            <a href="/analytics" class="glass-button-secondary" style="padding: 1rem; text-align: center; text-decoration: none; display: block;">
                                <svg style="width: 1.5rem; height: 1.5rem; margin-bottom: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <div>Analytics</div>
                            </a>
                        <?php endif; ?>
                        
                        <a href="/help-center" class="glass-button-secondary" style="padding: 1rem; text-align: center; text-decoration: none; display: block;">
                            <svg style="width: 1.5rem; height: 1.5rem; margin-bottom: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>Centre d'aide</div>
                        </a>
                        
                        <button onclick="refreshDashboard()" class="glass-button-secondary" style="padding: 1rem; text-align: center; border: none; cursor: pointer;">
                            <svg style="width: 1.5rem; height: 1.5rem; margin-bottom: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <div>Actualiser</div>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Colonne droite -->
            <div>
                <!-- Cours populaires -->
                <?php if (!empty($popularCourses)): ?>
                    <div class="glass-card p-6 mb-6 animate-fade-in" style="animation-delay: 0.6s;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h2 style="font-size: 1.5rem; font-weight: 600;">Cours populaires</h2>
                            <a href="/courses" style="color: rgb(var(--color-primary)); text-decoration: none; font-size: 0.875rem;">
                                Voir plus →
                            </a>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($popularCourses as $course): ?>
                                <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(255, 255, 255, 0.05); border-radius: var(--border-radius);">
                                    <div style="width: 60px; height: 60px; background: var(--gradient-primary); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center;">
                                        <svg style="width: 1.5rem; height: 1.5rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                    </div>
                                    
                                    <div style="flex: 1;">
                                        <h4 style="font-weight: 600; margin-bottom: 0.25rem; font-size: 0.875rem;">
                                            <?= htmlspecialchars(Utils::truncate($course['title'], 40)) ?>
                                        </h4>
                                        <div style="font-size: 0.75rem; opacity: 0.7;">
                                            <?= $course['enrollment_count'] ?> inscrits • Note: <?= number_format($course['rating'], 1) ?>
                                        </div>
                                    </div>
                                    
                                    <span class="badge"><?= htmlspecialchars($course['category']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Activités récentes -->
                <?php if (!empty($recentActivities)): ?>
                    <div class="glass-card p-6 animate-fade-in" style="animation-delay: 0.8s;">
                        <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">Activités récentes</h2>
                        
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach (array_slice($recentActivities, 0, 8) as $activity): ?>
                                <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem; border-radius: var(--border-radius);">
                                    <div style="width: 8px; height: 8px; background: rgb(var(--color-primary)); border-radius: 50%; flex-shrink: 0;"></div>
                                    
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">
                                            <?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?>
                                        </div>
                                        <div style="font-size: 0.75rem; opacity: 0.7;">
                                            <?= htmlspecialchars($activity['activity_description']) ?>
                                        </div>
                                    </div>
                                    
                                    <div style="font-size: 0.75rem; opacity: 0.6; white-space: nowrap;">
                                        <?= Utils::timeAgo($activity['activity_date']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction pour actualiser le dashboard
async function refreshDashboard() {
    window.location.reload();
}

// Auto-refresh des données toutes les 5 minutes
setInterval(() => {
    // Ici on pourrait faire des requêtes AJAX pour mettre à jour les données
    // sans recharger toute la page
}, 300000);

// Animation d'entrée progressive
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.glass-card, .glassmorphism');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
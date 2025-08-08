<?php
/**
 * Page analytics détaillées
 */

// Vérifier l'authentification et les permissions
Auth::requireAuth();

if (!Auth::hasRole('manager')) {
    http_response_code(403);
    Utils::redirectWithMessage('/dashboard', 'Accès non autorisé', 'error');
    exit;
}

$pageTitle = "Analytics - StacGateLMS";
$pageDescription = "Tableau de bord analytique avec statistiques détaillées et métriques de performance.";

$currentUser = Auth::user();
$establishmentId = Auth::hasRole('super_admin') ? null : $currentUser['establishment_id'];

// Initialiser les services
$analyticsService = new AnalyticsService();

// Paramètres de période
$period = $_GET['period'] ?? '30'; // 7, 30, 90 jours
$exportFormat = $_GET['export'] ?? null;

// Obtenir les données analytics
try {
    $overview = $analyticsService->getOverview($establishmentId);
    $popularCourses = $analyticsService->getPopularCourses($establishmentId, 10);
    $enrollmentStats = $analyticsService->getEnrollmentStats($establishmentId, $period);
    $categoryDistribution = $analyticsService->getCategoryDistribution($establishmentId);
    $progressStats = $analyticsService->getProgressStats($establishmentId);
    $instructorPerformance = $analyticsService->getInstructorPerformance($establishmentId);
    $realTimeMetrics = $analyticsService->getRealTimeMetrics($establishmentId);
    
    // Export si demandé
    if ($exportFormat) {
        $exportData = [
            'overview' => $overview,
            'popular_courses' => $popularCourses,
            'enrollment_stats' => $enrollmentStats,
            'category_distribution' => $categoryDistribution,
            'progress_stats' => $progressStats
        ];
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="analytics-' . date('Y-m-d') . '.json"');
        echo json_encode($exportData, JSON_PRETTY_PRINT);
        exit;
    }
    
} catch (Exception $e) {
    Utils::log("Analytics page error: " . $e->getMessage(), 'ERROR');
    $overview = $popularCourses = $enrollmentStats = [];
    $categoryDistribution = $progressStats = $instructorPerformance = $realTimeMetrics = [];
}

require_once ROOT_PATH . '/includes/header.php';
?>

<div style="padding: 2rem 0; margin-top: 80px;">
    <div class="container">
        <!-- En-tête -->
        <div class="glassmorphism p-6 mb-8">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                        Analytics & Insights
                    </h1>
                    <p style="opacity: 0.8;">
                        <?php if ($establishmentId): ?>
                            Analytics pour <?= htmlspecialchars($currentUser['establishment_name']) ?>
                        <?php else: ?>
                            Analytics globales - Tous les établissements
                        <?php endif; ?>
                    </p>
                </div>
                
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <!-- Sélecteur de période -->
                    <select onchange="changePeriod(this.value)" class="glass-input" style="padding: 0.5rem 1rem;">
                        <option value="7" <?= $period === '7' ? 'selected' : '' ?>>7 derniers jours</option>
                        <option value="30" <?= $period === '30' ? 'selected' : '' ?>>30 derniers jours</option>
                        <option value="90" <?= $period === '90' ? 'selected' : '' ?>>90 derniers jours</option>
                    </select>
                    
                    <!-- Bouton export -->
                    <button onclick="exportData()" class="glass-button" style="padding: 0.5rem 1rem;">
                        📊 Exporter
                    </button>
                    
                    <!-- Actualiser -->
                    <button onclick="refreshData()" class="glass-button glass-button-secondary" style="padding: 0.5rem 1rem;">
                        🔄
                    </button>
                </div>
            </div>
        </div>

        <!-- Métriques temps réel -->
        <div class="glassmorphism p-6 mb-8">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem; color: rgb(var(--color-primary));">
                🔴 Temps réel
            </h2>
            
            <div class="grid grid-4">
                <div class="text-center">
                    <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.25rem;">
                        <?= $realTimeMetrics['active_users_now'] ?? 0 ?>
                    </div>
                    <div style="opacity: 0.8; font-size: 0.9rem;">Utilisateurs actifs</div>
                    <div style="font-size: 0.7rem; opacity: 0.6;">maintenant</div>
                </div>
                
                <div class="text-center">
                    <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-secondary)); margin-bottom: 0.25rem;">
                        <?= $realTimeMetrics['active_courses_now'] ?? 0 ?>
                    </div>
                    <div style="opacity: 0.8; font-size: 0.9rem;">Cours en cours</div>
                    <div style="font-size: 0.7rem; opacity: 0.6;">actuellement</div>
                </div>
                
                <div class="text-center">
                    <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-accent)); margin-bottom: 0.25rem;">
                        <?= $realTimeMetrics['sessions_today'] ?? 0 ?>
                    </div>
                    <div style="opacity: 0.8; font-size: 0.9rem;">Sessions</div>
                    <div style="font-size: 0.7rem; opacity: 0.6;">aujourd'hui</div>
                </div>
                
                <div class="text-center">
                    <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.25rem;">
                        <?= intval($realTimeMetrics['completion_rate_today'] ?? 0) ?>%
                    </div>
                    <div style="opacity: 0.8; font-size: 0.9rem;">Taux completion</div>
                    <div style="font-size: 0.7rem; opacity: 0.6;">aujourd'hui</div>
                </div>
            </div>
        </div>

        <!-- Vue d'ensemble -->
        <div class="grid grid-4 mb-8">
            <div class="glass-card p-6 text-center animate-fade-in">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">👥</div>
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.5rem;">
                    <?= Utils::formatNumber($overview['users']['total'] ?? 0) ?>
                </div>
                <div style="opacity: 0.8;">Utilisateurs total</div>
                <div style="font-size: 0.8rem; opacity: 0.6; margin-top: 0.5rem;">
                    +<?= $overview['users']['this_month'] ?? 0 ?> ce mois
                    <span style="color: rgb(var(--color-primary));">(+<?= intval($overview['users']['growth_rate'] ?? 0) ?>%)</span>
                </div>
            </div>
            
            <div class="glass-card p-6 text-center animate-fade-in" style="animation-delay: 0.1s;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">📚</div>
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-secondary)); margin-bottom: 0.5rem;">
                    <?= Utils::formatNumber($overview['courses']['total'] ?? 0) ?>
                </div>
                <div style="opacity: 0.8;">Cours créés</div>
                <div style="font-size: 0.8rem; opacity: 0.6; margin-top: 0.5rem;">
                    <?= $overview['courses']['active'] ?? 0 ?> actifs
                    <span style="color: rgb(var(--color-secondary));">(<?= intval($overview['courses']['active_rate'] ?? 0) ?>%)</span>
                </div>
            </div>
            
            <div class="glass-card p-6 text-center animate-fade-in" style="animation-delay: 0.2s;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">✅</div>
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-accent)); margin-bottom: 0.5rem;">
                    <?= Utils::formatNumber($overview['enrollments']['total'] ?? 0) ?>
                </div>
                <div style="opacity: 0.8;">Inscriptions</div>
                <div style="font-size: 0.8rem; opacity: 0.6; margin-top: 0.5rem;">
                    +<?= $overview['enrollments']['this_week'] ?? 0 ?> cette semaine
                </div>
            </div>
            
            <div class="glass-card p-6 text-center animate-fade-in" style="animation-delay: 0.3s;">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">⭐</div>
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.5rem;">
                    <?= intval($overview['satisfaction_rate'] ?? 0) ?>%
                </div>
                <div style="opacity: 0.8;">Satisfaction</div>
                <div style="font-size: 0.8rem; opacity: 0.6; margin-top: 0.5rem;">
                    Moyenne des évaluations
                </div>
            </div>
        </div>

        <!-- Grille principale -->
        <div class="grid grid-2 mb-8" style="gap: 2rem;">
            <!-- Cours populaires -->
            <div class="glassmorphism p-6">
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                    📈 Cours les plus populaires
                </h2>
                
                <div style="space-y: 1rem;">
                    <?php foreach (array_slice($popularCourses, 0, 6) as $index => $course): ?>
                        <div style="display: flex; align-items: center; padding: 1rem; background: rgba(var(--color-primary), 0.05); border-radius: 0.5rem; margin-bottom: 0.5rem;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: rgb(var(--color-primary)); margin-right: 1rem; min-width: 30px;">
                                #<?= $index + 1 ?>
                            </div>
                            
                            <div style="flex: 1;">
                                <h4 style="font-weight: 600; margin-bottom: 0.25rem;">
                                    <?= htmlspecialchars($course['title']) ?>
                                </h4>
                                <div style="display: flex; gap: 1rem; font-size: 0.8rem; opacity: 0.8;">
                                    <span>👥 <?= $course['enrolled_count'] ?? 0 ?> inscrits</span>
                                    <span>⭐ <?= number_format($course['average_rating'] ?? 0, 1) ?></span>
                                    <span>✅ <?= intval($course['completion_rate'] ?? 0) ?>% terminé</span>
                                </div>
                            </div>
                            
                            <div style="text-align: right; font-size: 0.8rem; opacity: 0.6;">
                                <?= $course['views'] ?? 0 ?> vues
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($popularCourses)): ?>
                        <div style="text-align: center; opacity: 0.6; padding: 2rem;">
                            Aucune donnée disponible
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Répartition par catégories -->
            <div class="glassmorphism p-6">
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                    📊 Répartition par catégories
                </h2>
                
                <div style="space-y: 1rem;">
                    <?php foreach ($categoryDistribution as $category): ?>
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: 500;"><?= htmlspecialchars($category['name']) ?></span>
                                <span style="opacity: 0.8;">
                                    <?= $category['course_count'] ?> cours (<?= intval($category['percentage']) ?>%)
                                </span>
                            </div>
                            
                            <div style="background: rgba(255, 255, 255, 0.1); height: 8px; border-radius: 4px; overflow: hidden;">
                                <div style="background: rgb(var(--color-primary)); height: 100%; width: <?= intval($category['percentage']) ?>%; transition: width 0.8s ease; background: linear-gradient(90deg, rgb(var(--color-primary)), rgb(var(--color-secondary)));"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($categoryDistribution)): ?>
                        <div style="text-align: center; opacity: 0.6; padding: 2rem;">
                            Aucune donnée de catégorie
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Performance des instructeurs -->
        <?php if (!empty($instructorPerformance)): ?>
            <div class="glassmorphism p-6 mb-8">
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                    👨‍🏫 Performance des instructeurs
                </h2>
                
                <div class="grid grid-3">
                    <?php foreach (array_slice($instructorPerformance, 0, 6) as $instructor): ?>
                        <div class="glass-card p-4">
                            <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                                <?php if ($instructor['avatar']): ?>
                                    <img src="<?= htmlspecialchars($instructor['avatar']) ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 1rem; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--gradient-primary); margin-right: 1rem; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                        <?= strtoupper(substr($instructor['first_name'], 0, 1) . substr($instructor['last_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="flex: 1;">
                                    <h4 style="font-weight: 600; font-size: 0.9rem; margin-bottom: 0.25rem;">
                                        <?= htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']) ?>
                                    </h4>
                                    <div style="font-size: 0.7rem; opacity: 0.8;">
                                        <?= $instructor['course_count'] ?> cours
                                    </div>
                                </div>
                            </div>
                            
                            <div style="font-size: 0.8rem; margin-bottom: 0.5rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span>Satisfaction</span>
                                    <span style="color: rgb(var(--color-primary));"><?= number_format($instructor['average_rating'] ?? 0, 1) ?>⭐</span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                    <span>Inscrits totaux</span>
                                    <span><?= $instructor['total_students'] ?? 0 ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Taux completion</span>
                                    <span style="color: rgb(var(--color-secondary));"><?= intval($instructor['completion_rate'] ?? 0) ?>%</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistiques de progression -->
        <div class="glassmorphism p-6">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                📈 Évolution des inscriptions (<?= $period ?> derniers jours)
            </h2>
            
            <?php if (!empty($enrollmentStats)): ?>
                <div style="height: 200px; display: flex; align-items: end; justify-content: space-between; padding: 1rem 0; border-bottom: 2px solid rgba(255,255,255,0.1);">
                    <?php 
                    $maxValue = max(array_column($enrollmentStats, 'count'));
                    foreach ($enrollmentStats as $stat): 
                        $height = $maxValue > 0 ? ($stat['count'] / $maxValue) * 180 : 0;
                    ?>
                        <div style="display: flex; flex-direction: column; align-items: center; flex: 1;">
                            <div style="background: var(--gradient-primary); width: 20px; height: <?= $height ?>px; border-radius: 2px; transition: height 0.8s ease;" title="<?= $stat['count'] ?> inscriptions le <?= $stat['date'] ?>"></div>
                            <div style="font-size: 0.7rem; opacity: 0.6; margin-top: 0.5rem; writing-mode: vertical-lr; transform: rotate(180deg);">
                                <?= date('d/m', strtotime($stat['date'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 1rem; opacity: 0.8; font-size: 0.9rem;">
                    Total : <?= array_sum(array_column($enrollmentStats, 'count')) ?> inscriptions sur <?= $period ?> jours
                </div>
            <?php else: ?>
                <div style="text-align: center; opacity: 0.6; padding: 3rem;">
                    Aucune donnée d'inscription pour cette période
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function changePeriod(period) {
    const url = new URL(window.location);
    url.searchParams.set('period', period);
    window.location.href = url.toString();
}

function exportData() {
    const url = new URL(window.location);
    url.searchParams.set('export', 'json');
    window.open(url.toString());
}

function refreshData() {
    window.location.reload();
}

// Auto-refresh toutes les 5 minutes
setInterval(() => {
    // Refresh seulement les métriques temps réel via AJAX
    refreshRealTimeMetrics();
}, 300000);

async function refreshRealTimeMetrics() {
    try {
        const response = await apiRequest('/api/analytics/realtime');
        if (response.success) {
            // Mettre à jour les métriques temps réel
            document.querySelector('[data-metric="active-users"]').textContent = response.data.active_users_now;
            document.querySelector('[data-metric="active-courses"]').textContent = response.data.active_courses_now;
            document.querySelector('[data-metric="sessions-today"]').textContent = response.data.sessions_today;
            document.querySelector('[data-metric="completion-rate"]').textContent = Math.round(response.data.completion_rate_today) + '%';
        }
    } catch (error) {
        console.log('Erreur refresh temps réel:', error);
    }
}
</script>

<style>
/* Animations pour les graphiques */
@keyframes growBar {
    from { height: 0; }
    to { height: var(--target-height); }
}

.animate-bar {
    animation: growBar 0.8s ease-out forwards;
}

/* Responsive */
@media (max-width: 768px) {
    .grid-4, .grid-3, .grid-2 {
        grid-template-columns: 1fr;
    }
    
    .glassmorphism h1 {
        font-size: 2rem !important;
    }
    
    .glass-card h2 {
        font-size: 1.25rem !important;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
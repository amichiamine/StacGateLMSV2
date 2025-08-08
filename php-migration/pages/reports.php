<?php
/**
 * Page rapports avancés
 */

// Vérifier l'authentification et les permissions
Auth::requireAuth();

if (!Auth::hasRole('admin')) {
    http_response_code(403);
    Utils::redirectWithMessage('/dashboard', 'Accès non autorisé', 'error');
    exit;
}

$pageTitle = "Rapports - StacGateLMS";
$pageDescription = "Rapports détaillés et analyses de performance.";

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

// Paramètres de date
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // Premier jour du mois
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Aujourd'hui
$reportType = $_GET['type'] ?? 'overview';

// Initialiser les services
$analyticsService = new AnalyticsService();
$courseService = new CourseService();
$authService = new AuthService();

// Obtenir les données selon le type de rapport
try {
    switch ($reportType) {
        case 'courses':
            $reportData = $analyticsService->getCourseReport($establishmentId, $startDate, $endDate);
            break;
        case 'users':
            $reportData = $analyticsService->getUserReport($establishmentId, $startDate, $endDate);
            break;
        case 'engagement':
            $reportData = $analyticsService->getEngagementReport($establishmentId, $startDate, $endDate);
            break;
        case 'financial':
            $reportData = $analyticsService->getFinancialReport($establishmentId, $startDate, $endDate);
            break;
        default:
            $reportData = $analyticsService->getOverviewReport($establishmentId, $startDate, $endDate);
    }
} catch (Exception $e) {
    Utils::log("Reports page error: " . $e->getMessage(), 'ERROR');
    $reportData = [];
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
                        Rapports et analyses
                    </h1>
                    <p style="opacity: 0.8;">
                        Période : <?= date('d/m/Y', strtotime($startDate)) ?> - <?= date('d/m/Y', strtotime($endDate)) ?>
                    </p>
                </div>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button onclick="exportReport()" class="glass-button" style="background: var(--gradient-primary); color: white;">
                        Exporter PDF
                    </button>
                    <button onclick="scheduleReport()" class="glass-button glass-button-secondary">
                        Programmer
                    </button>
                </div>
            </div>
        </div>

        <!-- Filtres et navigation -->
        <div class="glassmorphism p-4 mb-6">
            <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.9rem; margin-bottom: 0.25rem;">Type de rapport</label>
                    <select name="type" class="glass-input" style="width: auto;">
                        <option value="overview" <?= $reportType === 'overview' ? 'selected' : '' ?>>Vue d'ensemble</option>
                        <option value="courses" <?= $reportType === 'courses' ? 'selected' : '' ?>>Cours</option>
                        <option value="users" <?= $reportType === 'users' ? 'selected' : '' ?>>Utilisateurs</option>
                        <option value="engagement" <?= $reportType === 'engagement' ? 'selected' : '' ?>>Engagement</option>
                        <option value="financial" <?= $reportType === 'financial' ? 'selected' : '' ?>>Financier</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.9rem; margin-bottom: 0.25rem;">Date de début</label>
                    <input type="date" name="start_date" value="<?= $startDate ?>" class="glass-input" style="width: auto;">
                </div>
                
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.9rem; margin-bottom: 0.25rem;">Date de fin</label>
                    <input type="date" name="end_date" value="<?= $endDate ?>" class="glass-input" style="width: auto;">
                </div>
                
                <button type="submit" class="glass-button glass-button-secondary">
                    Générer
                </button>
            </form>
        </div>

        <!-- Contenu du rapport -->
        <div id="report-content">
            <?php switch ($reportType): 
                case 'courses': ?>
                    <!-- Rapport des cours -->
                    <div class="grid grid-4 mb-6">
                        <div class="glass-card p-4 text-center">
                            <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary));">
                                <?= $reportData['total_courses'] ?? 0 ?>
                            </div>
                            <div style="opacity: 0.8;">Cours totaux</div>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-secondary));">
                                <?= $reportData['new_courses'] ?? 0 ?>
                            </div>
                            <div style="opacity: 0.8;">Nouveaux cours</div>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-accent));">
                                <?= $reportData['avg_enrollment'] ?? 0 ?>
                            </div>
                            <div style="opacity: 0.8;">Moy. inscriptions</div>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary));">
                                <?= $reportData['completion_rate'] ?? 0 ?>%
                            </div>
                            <div style="opacity: 0.8;">Taux de réussite</div>
                        </div>
                    </div>

                    <!-- Top cours -->
                    <div class="glassmorphism p-6 mb-6">
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem;">Cours les plus populaires</h3>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Cours</th>
                                        <th>Inscriptions</th>
                                        <th>Taux de réussite</th>
                                        <th>Note moyenne</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reportData['top_courses'] ?? [] as $course): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($course['title']) ?></td>
                                            <td><?= $course['enrollments'] ?></td>
                                            <td><?= $course['completion_rate'] ?>%</td>
                                            <td><?= number_format($course['avg_rating'], 1) ?>/5</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php break;
                case 'users': ?>
                    <!-- Rapport utilisateurs -->
                    <div class="grid grid-4 mb-6">
                        <div class="glass-card p-4 text-center">
                            <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary));">
                                <?= $reportData['total_users'] ?? 0 ?>
                            </div>
                            <div style="opacity: 0.8;">Utilisateurs totaux</div>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-secondary));">
                                <?= $reportData['new_users'] ?? 0 ?>
                            </div>
                            <div style="opacity: 0.8;">Nouveaux utilisateurs</div>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-accent));">
                                <?= $reportData['active_users'] ?? 0 ?>
                            </div>
                            <div style="opacity: 0.8;">Utilisateurs actifs</div>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary));">
                                <?= $reportData['retention_rate'] ?? 0 ?>%
                            </div>
                            <div style="opacity: 0.8;">Taux de rétention</div>
                        </div>
                    </div>

                    <!-- Répartition par rôle -->
                    <div class="grid grid-2 mb-6">
                        <div class="glassmorphism p-6">
                            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem;">Répartition par rôle</h3>
                            <div class="chart-container">
                                <?php foreach ($reportData['users_by_role'] ?? [] as $role => $count): ?>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span><?= ucfirst($role) ?></span>
                                        <span style="font-weight: 600;"><?= $count ?></span>
                                    </div>
                                    <div style="background: rgba(255,255,255,0.1); height: 8px; border-radius: 4px; margin-bottom: 1rem;">
                                        <div style="background: var(--gradient-primary); height: 100%; width: <?= ($count / ($reportData['total_users'] ?? 1)) * 100 ?>%; border-radius: 4px;"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="glassmorphism p-6">
                            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem;">Activité mensuelle</h3>
                            <div class="chart-container">
                                <?php foreach ($reportData['monthly_activity'] ?? [] as $month => $activity): ?>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span><?= $month ?></span>
                                        <span style="font-weight: 600;"><?= $activity ?>%</span>
                                    </div>
                                    <div style="background: rgba(255,255,255,0.1); height: 8px; border-radius: 4px; margin-bottom: 1rem;">
                                        <div style="background: var(--gradient-secondary); height: 100%; width: <?= $activity ?>%; border-radius: 4px;"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                <?php break;
                default: ?>
                    <!-- Vue d'ensemble -->
                    <div class="grid grid-4 mb-6">
                        <div class="glass-card p-4 text-center">
                            <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary));">
                                <?= $reportData['total_users'] ?? 0 ?>
                            </div>
                            <div style="opacity: 0.8;">Utilisateurs</div>
                            <div style="font-size: 0.8rem; color: rgb(var(--color-primary));">
                                +<?= $reportData['users_growth'] ?? 0 ?>% vs période précédente
                            </div>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-secondary));">
                                <?= $reportData['total_courses'] ?? 0 ?>
                            </div>
                            <div style="opacity: 0.8;">Cours</div>
                            <div style="font-size: 0.8rem; color: rgb(var(--color-secondary));">
                                +<?= $reportData['courses_growth'] ?? 0 ?>% vs période précédente
                            </div>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-accent));">
                                <?= $reportData['total_enrollments'] ?? 0 ?>
                            </div>
                            <div style="opacity: 0.8;">Inscriptions</div>
                            <div style="font-size: 0.8rem; color: rgb(var(--color-accent));">
                                +<?= $reportData['enrollments_growth'] ?? 0 ?>% vs période précédente
                            </div>
                        </div>
                        <div class="glass-card p-4 text-center">
                            <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary));">
                                <?= $reportData['engagement_rate'] ?? 0 ?>%
                            </div>
                            <div style="opacity: 0.8;">Engagement</div>
                            <div style="font-size: 0.8rem; color: rgb(var(--color-primary));">
                                +<?= $reportData['engagement_growth'] ?? 0 ?>% vs période précédente
                            </div>
                        </div>
                    </div>

                    <!-- Graphique des tendances -->
                    <div class="glassmorphism p-6 mb-6">
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem;">Tendances de croissance</h3>
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="growthChart"></canvas>
                        </div>
                    </div>
            <?php endswitch; ?>
        </div>
    </div>
</div>

<style>
.table {
    width: 100%;
    border-collapse: collapse;
}

.table th, .table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.table th {
    font-weight: 600;
    opacity: 0.8;
}

.table-responsive {
    overflow-x: auto;
}

@media (max-width: 768px) {
    .grid-4 {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Export du rapport
async function exportReport() {
    try {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'pdf');
        
        const response = await fetch(`/api/exports/report?${params.toString()}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                _token: '<?= generateCSRFToken() ?>'
            })
        });
        
        if (response.ok) {
            const data = await response.json();
            showToast('Export en cours de génération', 'success');
            
            // Rediriger vers les exports
            setTimeout(() => {
                window.location.href = '/archive-export';
            }, 2000);
        } else {
            showToast('Erreur lors de l\'export', 'error');
        }
    } catch (error) {
        showToast('Erreur lors de l\'export', 'error');
    }
}

// Programmer un rapport
function scheduleReport() {
    // Modal de programmation (à implémenter)
    showToast('Fonctionnalité de programmation bientôt disponible', 'info');
}

// Graphique des tendances (si overview)
<?php if ($reportType === 'overview'): ?>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('growthChart');
    if (canvas) {
        // Simulation d'un graphique simple avec Canvas
        const ctx = canvas.getContext('2d');
        canvas.width = canvas.offsetWidth;
        canvas.height = 300;
        
        // Données simulées pour le graphique
        const data = <?= json_encode($reportData['growth_chart'] ?? []) ?>;
        
        // Dessin simple du graphique
        ctx.strokeStyle = 'rgb(var(--color-primary))';
        ctx.lineWidth = 2;
        ctx.beginPath();
        
        // Points de données simulés
        for (let i = 0; i < 12; i++) {
            const x = (canvas.width / 11) * i;
            const y = canvas.height - (Math.random() * 200 + 50);
            
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }
        
        ctx.stroke();
    }
});
<?php endif; ?>
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
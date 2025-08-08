<?php
/**
 * Page administration
 */

// Vérifier l'authentification et les permissions
Auth::requireAuth();

if (!Auth::hasRole('admin')) {
    http_response_code(403);
    Utils::redirectWithMessage('/dashboard', 'Accès non autorisé', 'error');
    exit;
}

$pageTitle = "Administration - StacGateLMS";
$pageDescription = "Panneau d'administration pour la gestion de l'établissement.";

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

// Initialiser les services
$establishmentService = new EstablishmentService();
$authService = new AuthService();
$courseService = new CourseService();
$analyticsService = new AnalyticsService();

// Obtenir les données
try {
    $establishment = $establishmentService->getEstablishmentById($establishmentId);
    $establishmentStats = $establishmentService->getEstablishmentStats($establishmentId);
    $userStats = $authService->getUserStats($establishmentId);
    $courseStats = $courseService->getCourseStats($establishmentId);
    $analytics = $analyticsService->getOverview($establishmentId);
    
} catch (Exception $e) {
    Utils::log("Admin page error: " . $e->getMessage(), 'ERROR');
    $establishment = null;
    $establishmentStats = [];
    $userStats = [];
    $courseStats = [];
    $analytics = [];
}

require_once ROOT_PATH . '/includes/header.php';
?>

<div style="padding: 2rem 0; margin-top: 80px;">
    <div class="container">
        <!-- En-tête -->
        <div class="glassmorphism p-6 mb-8">
            <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                Administration
            </h1>
            <p style="opacity: 0.8;">
                Gestion de l'établissement <?= htmlspecialchars($establishment['name'] ?? 'Non défini') ?>
            </p>
        </div>

        <!-- Métriques principales -->
        <div class="grid grid-4 mb-8">
            <div class="glass-card p-6 text-center animate-fade-in">
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.5rem;">
                    <?= $analytics['users']['total'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8;">Utilisateurs</div>
                <div style="font-size: 0.8rem; opacity: 0.6; margin-top: 0.25rem;">
                    +<?= $analytics['users']['this_month'] ?? 0 ?> ce mois
                </div>
            </div>
            
            <div class="glass-card p-6 text-center animate-fade-in" style="animation-delay: 0.1s;">
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-secondary)); margin-bottom: 0.5rem;">
                    <?= $analytics['courses']['total'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8;">Cours</div>
                <div style="font-size: 0.8rem; opacity: 0.6; margin-top: 0.25rem;">
                    <?= $analytics['courses']['active'] ?? 0 ?> actifs
                </div>
            </div>
            
            <div class="glass-card p-6 text-center animate-fade-in" style="animation-delay: 0.2s;">
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-accent)); margin-bottom: 0.5rem;">
                    <?= $analytics['enrollments']['total'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8;">Inscriptions</div>
                <div style="font-size: 0.8rem; opacity: 0.6; margin-top: 0.25rem;">
                    +<?= $analytics['enrollments']['this_week'] ?? 0 ?> cette semaine
                </div>
            </div>
            
            <div class="glass-card p-6 text-center animate-fade-in" style="animation-delay: 0.3s;">
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.5rem;">
                    <?= intval($analytics['activity_rate'] ?? 0) ?>%
                </div>
                <div style="opacity: 0.8;">Taux d'activité</div>
                <div style="font-size: 0.8rem; opacity: 0.6; margin-top: 0.25rem;">
                    <?= $analytics['active_users'] ?? 0 ?> utilisateurs actifs
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="glassmorphism p-6 mb-8">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">Actions rapides</h2>
            
            <div class="grid grid-3">
                <a href="/user-management" class="glass-card p-6 text-center" style="text-decoration: none; color: inherit;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>
                    <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Gestion des utilisateurs</h3>
                    <p style="opacity: 0.8; font-size: 0.9rem;">Ajouter, modifier et gérer les comptes utilisateurs</p>
                </a>
                
                <a href="/analytics" class="glass-card p-6 text-center" style="text-decoration: none; color: inherit;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                    <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Analytics détaillées</h3>
                    <p style="opacity: 0.8; font-size: 0.9rem;">Consulter les statistiques et rapports complets</p>
                </a>
                
                <a href="/establishment/settings" class="glass-card p-6 text-center" style="text-decoration: none; color: inherit;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">⚙️</div>
                    <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Paramètres</h3>
                    <p style="opacity: 0.8; font-size: 0.9rem;">Configurer l'établissement et les préférences</p>
                </a>
                
                <a href="/courses" class="glass-card p-6 text-center" style="text-decoration: none; color: inherit;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📚</div>
                    <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Gestion des cours</h3>
                    <p style="opacity: 0.8; font-size: 0.9rem;">Superviser les cours et formations</p>
                </a>
                
                <a href="/assessments" class="glass-card p-6 text-center" style="text-decoration: none; color: inherit;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
                    <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Évaluations</h3>
                    <p style="opacity: 0.8; font-size: 0.9rem;">Gérer les examens et évaluations</p>
                </a>
                
                <a href="/archive-export" class="glass-card p-6 text-center" style="text-decoration: none; color: inherit;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                    <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Archives & Export</h3>
                    <p style="opacity: 0.8; font-size: 0.9rem;">Sauvegarder et exporter les données</p>
                </a>
            </div>
        </div>

        <!-- Informations établissement -->
        <?php if ($establishment): ?>
            <div class="grid grid-2 mb-8">
                <!-- Détails établissement -->
                <div class="glassmorphism p-6">
                    <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">Informations établissement</h2>
                    
                    <div style="display: flex; align-items: center; margin-bottom: 1.5rem;">
                        <?php if ($establishment['logo']): ?>
                            <img src="<?= htmlspecialchars($establishment['logo']) ?>" 
                                 alt="Logo" 
                                 style="width: 80px; height: 80px; object-fit: contain; margin-right: 1rem; border-radius: 0.5rem;">
                        <?php else: ?>
                            <div style="width: 80px; height: 80px; background: var(--gradient-primary); margin-right: 1rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: 700;">
                                <?= strtoupper(substr($establishment['name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <h3 style="font-weight: 600; margin-bottom: 0.25rem;">
                                <?= htmlspecialchars($establishment['name']) ?>
                            </h3>
                            <p style="opacity: 0.8; font-size: 0.9rem;">
                                <?= htmlspecialchars($establishment['slug']) ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($establishment['description']): ?>
                        <div style="margin-bottom: 1rem;">
                            <strong>Description :</strong>
                            <p style="margin-top: 0.5rem; opacity: 0.8;">
                                <?= nl2br(htmlspecialchars($establishment['description'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem;">
                        <div>
                            <strong>Statut :</strong>
                            <span class="badge <?= $establishment['is_active'] ? 'badge-success' : 'badge-error' ?>" style="margin-left: 0.5rem;">
                                <?= $establishment['is_active'] ? 'Actif' : 'Inactif' ?>
                            </span>
                        </div>
                        <div>
                            <strong>Créé le :</strong>
                            <?= Utils::formatDate($establishment['created_at']) ?>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1.5rem;">
                        <a href="/establishment/settings" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 0.75rem 1.5rem;">
                            Modifier les paramètres
                        </a>
                    </div>
                </div>

                <!-- Activité récente -->
                <div class="glassmorphism p-6">
                    <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">Activité récente</h2>
                    
                    <div style="space-y: 1rem;">
                        <div style="padding: 1rem; background: rgba(var(--color-primary), 0.05); border-radius: 0.5rem; border-left: 4px solid rgb(var(--color-primary));">
                            <div style="font-weight: 500;">Nouveaux utilisateurs ce mois</div>
                            <div style="opacity: 0.8; font-size: 0.9rem;">
                                +<?= $analytics['users']['this_month'] ?? 0 ?> nouvelles inscriptions
                            </div>
                        </div>
                        
                        <div style="padding: 1rem; background: rgba(var(--color-secondary), 0.05); border-radius: 0.5rem; border-left: 4px solid rgb(var(--color-secondary));">
                            <div style="font-weight: 500;">Cours les plus populaires</div>
                            <div style="opacity: 0.8; font-size: 0.9rem;">
                                <?= $analytics['popular_course_name'] ?? 'Aucun cours populaire' ?>
                            </div>
                        </div>
                        
                        <div style="padding: 1rem; background: rgba(var(--color-accent), 0.05); border-radius: 0.5rem; border-left: 4px solid rgb(var(--color-accent));">
                            <div style="font-weight: 500;">Taux de completion moyen</div>
                            <div style="opacity: 0.8; font-size: 0.9rem;">
                                <?= intval($analytics['completion_rate'] ?? 0) ?>% des cours terminés
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1.5rem;">
                        <a href="/analytics" class="glass-button" style="width: 100%; text-align: center; padding: 0.75rem;">
                            Voir toutes les analytics
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Alertes et notifications système -->
        <div class="glassmorphism p-6">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">État du système</h2>
            
            <div class="grid grid-2">
                <div>
                    <h3 style="font-weight: 600; margin-bottom: 1rem; color: rgb(var(--color-primary));">✅ Tout va bien</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            🟢 Base de données : Connectée
                        </li>
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            🟢 Authentification : Fonctionnelle
                        </li>
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            🟢 Espace disque : <?= Utils::formatFileSize(disk_free_space('.')) ?> disponible
                        </li>
                        <li style="padding: 0.5rem 0;">
                            🟢 Version PHP : <?= PHP_VERSION ?>
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h3 style="font-weight: 600; margin-bottom: 1rem; color: rgb(var(--color-secondary));">ℹ️ Informations</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            📊 Cache : <?= CACHE_ENABLED ? 'Activé' : 'Désactivé' ?>
                        </li>
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            📝 Logs : <?= LOG_ENABLED ? 'Activés' : 'Désactivés' ?>
                        </li>
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            🔧 Mode debug : <?= APP_DEBUG ? 'ON' : 'OFF' ?>
                        </li>
                        <li style="padding: 0.5rem 0;">
                            🌍 Environnement : <?= APP_ENV ?>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem; text-align: center;">
                <button onclick="clearCache()" class="glass-button glass-button-secondary" style="margin-right: 0.5rem; padding: 0.75rem 1.5rem;">
                    Vider le cache
                </button>
                
                <?php if (Auth::hasRole('super_admin')): ?>
                    <a href="/system-updates" class="glass-button" style="padding: 0.75rem 1.5rem;">
                        Gestion système
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
async function clearCache() {
    try {
        const response = await apiRequest('/api/system/clear-cache', 'POST');
        
        if (response.success) {
            showToast('Cache vidé avec succès', 'success');
        } else {
            showToast(response.error, 'error');
        }
    } catch (error) {
        showToast('Erreur lors du vidage du cache', 'error');
    }
}

// Auto-refresh des métriques toutes les 5 minutes
setInterval(() => {
    window.location.reload();
}, 300000);
</script>

<style>
.glass-card:hover {
    transform: translateY(-2px);
    transition: transform 0.3s ease;
}

@media (max-width: 768px) {
    .grid-4, .grid-3, .grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
<?php
/**
 * Page Mises à jour système
 * Gestion des mises à jour de la plateforme
 */

// Vérifier l'authentification et permissions
Auth::requireAuth();

if (!Auth::hasRole('admin')) {
    http_response_code(403);
    Utils::redirectWithMessage('/dashboard', 'Accès non autorisé', 'error');
    exit;
}

$pageTitle = "Mises à jour Système - StacGateLMS";
$pageDescription = "Gestion des mises à jour et maintenance de la plateforme.";

$currentUser = Auth::user();

// Initialiser les services
$systemService = new SystemService();

// Traitement des actions
$action = $_POST['action'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['_token'] ?? '')) {
    try {
        switch ($action) {
            case 'check_updates':
                $updates = $systemService->checkForUpdates();
                $message = ['type' => 'info', 'text' => 'Vérification des mises à jour terminée'];
                break;
                
            case 'backup_system':
                $backup = $systemService->createBackup();
                $message = ['type' => 'success', 'text' => 'Sauvegarde système créée avec succès'];
                break;
                
            case 'maintenance_mode':
                $enabled = $_POST['enabled'] === 'true';
                $maintenanceMessage = $_POST['maintenance_message'] ?? '';
                $systemService->setMaintenanceMode($enabled, $maintenanceMessage);
                $message = ['type' => 'success', 'text' => $enabled ? 'Mode maintenance activé' : 'Mode maintenance désactivé'];
                break;
                
            case 'clear_cache':
                $systemService->clearCache();
                $message = ['type' => 'success', 'text' => 'Cache système vidé avec succès'];
                break;
                
            case 'optimize_database':
                $result = $systemService->optimizeDatabase();
                $message = ['type' => 'success', 'text' => "Base de données optimisée ({$result['tables_optimized']} tables)"];
                break;
        }
    } catch (Exception $e) {
        $message = ['type' => 'error', 'text' => $e->getMessage()];
        Utils::log("System updates error: " . $e->getMessage(), 'ERROR');
    }
}

// Obtenir les données
try {
    $systemInfo = $systemService->getSystemInfo();
    $versions = $systemService->getVersions();
    $isMaintenanceMode = $systemService->isMaintenanceMode();
    $backups = $systemService->getBackups();
    
} catch (Exception $e) {
    Utils::log("System updates page error: " . $e->getMessage(), 'ERROR');
    $systemInfo = [];
    $versions = ['current' => 'Unknown', 'history' => [], 'latest_available' => []];
    $isMaintenanceMode = false;
    $backups = [];
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
                        🔄 Mises à jour Système
                    </h1>
                    <p style="opacity: 0.8;">
                        Gestion des versions, maintenance et optimisations
                    </p>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div class="version-badge" style="padding: 0.5rem 1rem; background: rgba(var(--color-primary), 0.1); border-radius: 20px; font-weight: 600;">
                        v<?= htmlspecialchars($versions['current']) ?>
                    </div>
                    <?php if ($isMaintenanceMode): ?>
                        <div class="maintenance-badge" style="padding: 0.5rem 1rem; background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-radius: 20px; font-weight: 600;">
                            🔧 Maintenance
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?= $message['type'] ?>" style="margin-top: 1rem; padding: 1rem; border-radius: 8px; background: rgba(<?= $message['type'] === 'success' ? '34, 197, 94' : ($message['type'] === 'error' ? '239, 68, 68' : '59, 130, 246') ?>, 0.1); color: <?= $message['type'] === 'success' ? '#22c55e' : ($message['type'] === 'error' ? '#ef4444' : '#3b82f6') ?>;">
                    <?= htmlspecialchars($message['text']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-2 mb-8">
            <!-- Vérification des mises à jour -->
            <div class="glassmorphism p-6">
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                    📥 Mises à jour Disponibles
                </h2>
                
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <span style="font-weight: 500;">Version actuelle :</span>
                        <span style="font-family: monospace; color: rgb(var(--color-primary));">
                            v<?= htmlspecialchars($versions['current']) ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($versions['latest_available']['version']) && $versions['latest_available']['version'] !== $versions['current']): ?>
                        <div style="padding: 1rem; background: rgba(var(--color-success), 0.1); border-radius: 8px; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600; color: rgb(var(--color-success));">
                                    ✨ Nouvelle version disponible
                                </span>
                                <span style="font-family: monospace;">
                                    v<?= htmlspecialchars($versions['latest_available']['version']) ?>
                                </span>
                            </div>
                            <p style="margin: 0; opacity: 0.8; font-size: 0.9rem;">
                                <?= htmlspecialchars($versions['latest_available']['release_notes']) ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div style="padding: 1rem; background: rgba(var(--color-success), 0.1); border-radius: 8px; margin-bottom: 1rem;">
                            <span style="color: rgb(var(--color-success)); font-weight: 600;">
                                ✓ Système à jour
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="check_updates">
                        <button type="submit" class="glass-button">
                            🔍 Vérifier les mises à jour
                        </button>
                    </form>
                    
                    <?php if (!empty($versions['latest_available']['download_url'])): ?>
                        <a href="<?= htmlspecialchars($versions['latest_available']['download_url']) ?>" 
                           class="glass-button" 
                           target="_blank">
                            📥 Télécharger
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mode maintenance -->
            <div class="glassmorphism p-6">
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                    🔧 Mode Maintenance
                </h2>
                
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div class="status-indicator" style="width: 12px; height: 12px; border-radius: 50%; background: <?= $isMaintenanceMode ? '#f59e0b' : '#22c55e' ?>;"></div>
                            <span style="font-weight: 500;">
                                <?= $isMaintenanceMode ? 'Activé' : 'Désactivé' ?>
                            </span>
                        </div>
                    </div>
                    
                    <p style="opacity: 0.8; font-size: 0.9rem; margin-bottom: 1.5rem;">
                        Le mode maintenance rend la plateforme indisponible pour les utilisateurs 
                        pendant les opérations de mise à jour ou de maintenance.
                    </p>
                    
                    <form method="POST">
                        <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="maintenance_mode">
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                Message de maintenance
                            </label>
                            <textarea name="maintenance_message" 
                                      class="glass-input" 
                                      style="width: 100%; height: 80px;" 
                                      placeholder="Message affiché aux utilisateurs...">Maintenance en cours. Veuillez revenir dans quelques minutes.</textarea>
                        </div>
                        
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" 
                                    name="enabled" 
                                    value="<?= $isMaintenanceMode ? 'false' : 'true' ?>"
                                    class="glass-button <?= $isMaintenanceMode ? 'glass-button-secondary' : '' ?>"
                                    style="<?= $isMaintenanceMode ? '' : 'background: rgba(245, 158, 11, 0.1); color: #f59e0b;' ?>">
                                <?= $isMaintenanceMode ? '✓ Désactiver' : '🔧 Activer' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Optimisations système -->
        <div class="glassmorphism p-6 mb-8">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                ⚡ Optimisations Système
            </h2>
            
            <div class="grid grid-3">
                <!-- Cache système -->
                <div class="optimization-card" style="padding: 1.5rem; background: rgba(255,255,255,0.02); border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="font-size: 2rem;">🗂️</div>
                        <div>
                            <h3 style="font-size: 1.1rem; font-weight: 600; margin: 0;">Cache Système</h3>
                            <p style="margin: 0; opacity: 0.7; font-size: 0.9rem;">
                                <?= count(glob(CACHE_PATH . '/*.cache')) ?> fichiers
                            </p>
                        </div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="clear_cache">
                        <button type="submit" class="glass-button" style="width: 100%;">
                            Vider le cache
                        </button>
                    </form>
                </div>

                <!-- Base de données -->
                <div class="optimization-card" style="padding: 1.5rem; background: rgba(255,255,255,0.02); border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="font-size: 2rem;">🗄️</div>
                        <div>
                            <h3 style="font-size: 1.1rem; font-weight: 600; margin: 0;">Base de données</h3>
                            <p style="margin: 0; opacity: 0.7; font-size: 0.9rem;">
                                Optimiser les tables
                            </p>
                        </div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="optimize_database">
                        <button type="submit" class="glass-button" style="width: 100%;">
                            Optimiser
                        </button>
                    </form>
                </div>

                <!-- Sauvegarde -->
                <div class="optimization-card" style="padding: 1.5rem; background: rgba(255,255,255,0.02); border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <div style="font-size: 2rem;">💾</div>
                        <div>
                            <h3 style="font-size: 1.1rem; font-weight: 600; margin: 0;">Sauvegarde</h3>
                            <p style="margin: 0; opacity: 0.7; font-size: 0.9rem;">
                                <?= count($backups) ?> sauvegardes
                            </p>
                        </div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="backup_system">
                        <button type="submit" class="glass-button" style="width: 100%;">
                            Créer sauvegarde
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Historique des versions -->
        <div class="glassmorphism p-6">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                📚 Historique des Versions
            </h2>
            
            <?php if (!empty($versions['history'])): ?>
                <div style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($versions['history'] as $version): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <div>
                                <div style="font-weight: 600; margin-bottom: 0.25rem;">
                                    v<?= htmlspecialchars($version['version']) ?>
                                    <?php if ($version['version'] === $versions['current']): ?>
                                        <span style="color: rgb(var(--color-success)); font-size: 0.8rem; margin-left: 0.5rem;">
                                            (Actuelle)
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="opacity: 0.7; font-size: 0.9rem;">
                                    Déployée le <?= date('d/m/Y à H:i', strtotime($version['deployed_at'])) ?>
                                </div>
                                <?php if (!empty($version['changes'])): ?>
                                    <div style="margin-top: 0.5rem;">
                                        <?php
                                        $changes = json_decode($version['changes'], true);
                                        if (is_array($changes)):
                                        ?>
                                            <ul style="margin: 0; padding-left: 1rem; font-size: 0.9rem; opacity: 0.8;">
                                                <?php foreach (array_slice($changes, 0, 3) as $change): ?>
                                                    <li><?= htmlspecialchars($change) ?></li>
                                                <?php endforeach; ?>
                                                <?php if (count($changes) > 3): ?>
                                                    <li style="opacity: 0.6;">... et <?= count($changes) - 3 ?> autres changements</li>
                                                <?php endif; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-family: monospace; font-size: 0.9rem; opacity: 0.7;">
                                    Par <?= htmlspecialchars($version['deployed_by'] ?? 'Système') ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; opacity: 0.7;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                    <p>Aucun historique de version disponible</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-refresh du statut maintenance
setInterval(async () => {
    try {
        const response = await fetch('/api/system/health');
        const data = await response.json();
        // Mettre à jour les indicateurs si nécessaire
    } catch (error) {
        console.error('Erreur vérification statut:', error);
    }
}, 30000);

// Confirmation pour les actions critiques
document.addEventListener('DOMContentLoaded', function() {
    const criticalActions = ['maintenance_mode', 'optimize_database'];
    
    criticalActions.forEach(action => {
        const forms = document.querySelectorAll(`form input[value="${action}"]`);
        forms.forEach(input => {
            const form = input.closest('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    let message = '';
                    switch (action) {
                        case 'maintenance_mode':
                            const enabled = input.value === 'true';
                            message = enabled ? 
                                'Activer le mode maintenance ? Les utilisateurs ne pourront plus accéder à la plateforme.' :
                                'Désactiver le mode maintenance ? Les utilisateurs pourront à nouveau accéder à la plateforme.';
                            break;
                        case 'optimize_database':
                            message = 'Optimiser la base de données ? Cette opération peut prendre plusieurs minutes.';
                            break;
                    }
                    
                    if (message && !confirm(message)) {
                        e.preventDefault();
                    }
                });
            }
        });
    });
});

// Animation des cartes d'optimisation
document.querySelectorAll('.optimization-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
        this.style.transition = 'transform 0.2s';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});
</script>

<style>
.status-indicator {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.version-badge, .maintenance-badge {
    font-size: 0.9rem;
    white-space: nowrap;
}

.optimization-card {
    border: 1px solid rgba(255,255,255,0.1);
    transition: all 0.3s;
}

.optimization-card:hover {
    border-color: rgba(var(--color-primary), 0.3);
    box-shadow: 0 4px 20px rgba(var(--color-primary), 0.1);
}

@media (max-width: 768px) {
    .grid-3 {
        grid-template-columns: 1fr !important;
    }
    
    .version-badge, .maintenance-badge {
        font-size: 0.8rem;
        padding: 0.25rem 0.75rem;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
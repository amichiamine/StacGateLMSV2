<?php
/**
 * Page monitoring système
 * Dashboard admin avancé pour surveillance système
 */

// Vérifier l'authentification et les permissions
Auth::requireAuth();

if (!Auth::hasRole('admin')) {
    http_response_code(403);
    Utils::redirectWithMessage('/dashboard', 'Accès non autorisé', 'error');
    exit;
}

$pageTitle = "Monitoring Système - StacGateLMS";
$pageDescription = "Surveillance et monitoring système en temps réel.";

$currentUser = Auth::user();

// Initialiser les services
$systemService = new SystemService();

// Obtenir les données système
try {
    $systemInfo = $systemService->getSystemInfo();
    $healthCheck = $systemService->healthCheck();
    $performanceMetrics = $systemService->getPerformanceMetrics();
    $recentActivity = $systemService->getRecentActivity(24);
    
} catch (Exception $e) {
    Utils::log("System monitoring page error: " . $e->getMessage(), 'ERROR');
    $systemInfo = [];
    $healthCheck = [];
    $performanceMetrics = [];
    $recentActivity = [];
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
                        🖥️ Monitoring Système
                    </h1>
                    <p style="opacity: 0.8;">
                        Surveillance système en temps réel - <?= htmlspecialchars($systemInfo['app']['environment'] ?? 'Unknown') ?>
                    </p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button onclick="refreshData()" class="glass-button">
                        🔄 Actualiser
                    </button>
                    <button onclick="downloadReport()" class="glass-button glass-button-secondary">
                        📥 Rapport
                    </button>
                </div>
            </div>
        </div>

        <!-- Health Status -->
        <div class="glassmorphism p-6 mb-8">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                🏥 État de Santé Système
            </h2>
            <div class="grid grid-4" id="health-checks">
                <!-- Sera rempli par JavaScript -->
            </div>
        </div>

        <!-- Métriques Performance -->
        <div class="grid grid-2 mb-8">
            <!-- Utilisation Ressources -->
            <div class="glassmorphism p-6">
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">
                    📊 Ressources Système
                </h3>
                <div style="space-y: 1rem;">
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Mémoire PHP</span>
                            <span id="memory-usage">--</span>
                        </div>
                        <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px;">
                            <div id="memory-bar" style="height: 100%; background: linear-gradient(90deg, #10B981, #F59E0B, #EF4444); border-radius: 4px; width: 0%;"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Espace Disque</span>
                            <span id="disk-usage">--</span>
                        </div>
                        <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px;">
                            <div id="disk-bar" style="height: 100%; background: linear-gradient(90deg, #10B981, #F59E0B, #EF4444); border-radius: 4px; width: 0%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques Base de Données -->
            <div class="glassmorphism p-6">
                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">
                    🗄️ Base de Données
                </h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="text-center">
                        <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary));" id="db-connections">
                            --
                        </div>
                        <div style="opacity: 0.8; font-size: 0.9rem;">Connexions</div>
                    </div>
                    <div class="text-center">
                        <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-secondary));" id="db-queries">
                            --
                        </div>
                        <div style="opacity: 0.8; font-size: 0.9rem;">Requêtes/min</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activité Récente -->
        <div class="glassmorphism p-6 mb-8">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                📈 Activité Récente (24h)
            </h2>
            <div class="grid grid-4">
                <div class="text-center">
                    <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary));" id="recent-logins">
                        <?= $recentActivity['logins'] ?? 0 ?>
                    </div>
                    <div style="opacity: 0.8;">Connexions</div>
                </div>
                <div class="text-center">
                    <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-secondary));" id="new-users">
                        <?= $recentActivity['new_users'] ?? 0 ?>
                    </div>
                    <div style="opacity: 0.8;">Nouveaux utilisateurs</div>
                </div>
                <div class="text-center">
                    <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-accent));" id="api-requests">
                        <?= $recentActivity['api_requests'] ?? 0 ?>
                    </div>
                    <div style="opacity: 0.8;">Requêtes API</div>
                </div>
                <div class="text-center">
                    <div style="font-size: 2rem; font-weight: 700; color: #EF4444;" id="errors-count">
                        <?= $recentActivity['errors'] ?? 0 ?>
                    </div>
                    <div style="opacity: 0.8;">Erreurs</div>
                </div>
            </div>
        </div>

        <!-- Informations Système -->
        <div class="glassmorphism p-6">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                ℹ️ Informations Système
            </h2>
            <div class="grid grid-2">
                <div>
                    <h4 style="font-weight: 600; margin-bottom: 0.5rem;">Application</h4>
                    <ul style="line-height: 1.6; opacity: 0.8;">
                        <li>Version: <?= htmlspecialchars($systemInfo['app']['version'] ?? 'Unknown') ?></li>
                        <li>Environnement: <?= htmlspecialchars($systemInfo['app']['environment'] ?? 'Unknown') ?></li>
                        <li>Debug: <?= ($systemInfo['app']['debug'] ?? false) ? 'Activé' : 'Désactivé' ?></li>
                    </ul>
                </div>
                <div>
                    <h4 style="font-weight: 600; margin-bottom: 0.5rem;">Serveur</h4>
                    <ul style="line-height: 1.6; opacity: 0.8;">
                        <li>PHP: <?= htmlspecialchars($systemInfo['server']['php_version'] ?? 'Unknown') ?></li>
                        <li>SAPI: <?= htmlspecialchars($systemInfo['server']['php_sapi'] ?? 'Unknown') ?></li>
                        <li>Serveur: <?= htmlspecialchars($systemInfo['server']['server_software'] ?? 'Unknown') ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Monitoring temps réel
let monitoringInterval;

function startMonitoring() {
    monitoringInterval = setInterval(refreshData, 30000); // Actualiser toutes les 30 secondes
}

function stopMonitoring() {
    if (monitoringInterval) {
        clearInterval(monitoringInterval);
    }
}

async function refreshData() {
    try {
        // Health checks
        const healthResponse = await fetch('/api/system/health');
        const healthData = await healthResponse.json();
        updateHealthChecks(healthData);
        
        // Stats système
        const statsResponse = await fetch('/api/system/stats');
        const statsData = await statsResponse.json();
        updateSystemStats(statsData);
        
    } catch (error) {
        console.error('Erreur actualisation monitoring:', error);
    }
}

function updateHealthChecks(data) {
    const container = document.getElementById('health-checks');
    if (!data.checks) return;
    
    container.innerHTML = '';
    
    Object.entries(data.checks).forEach(([name, check]) => {
        const statusColor = check.status === 'healthy' ? '#10B981' : 
                           check.status === 'warning' ? '#F59E0B' : '#EF4444';
        const statusIcon = check.status === 'healthy' ? '✅' : 
                          check.status === 'warning' ? '⚠️' : '❌';
        
        const checkElement = document.createElement('div');
        checkElement.className = 'glass-card p-4 text-center';
        checkElement.innerHTML = `
            <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">${statusIcon}</div>
            <div style="font-weight: 600; margin-bottom: 0.25rem; text-transform: capitalize;">${name.replace('_', ' ')}</div>
            <div style="font-size: 0.8rem; opacity: 0.8;">${check.message}</div>
        `;
        container.appendChild(checkElement);
    });
}

function updateSystemStats(data) {
    if (data.resource_usage) {
        // Mémoire
        const memoryPercentage = data.resource_usage.memory_percentage || 0;
        document.getElementById('memory-usage').textContent = `${memoryPercentage}%`;
        document.getElementById('memory-bar').style.width = `${memoryPercentage}%`;
        
        // Mise à jour des autres métriques si disponibles
        if (data.activity) {
            document.getElementById('recent-logins').textContent = data.activity.recent_logins || 0;
            document.getElementById('new-users').textContent = data.activity.new_users || 0;
            document.getElementById('api-requests').textContent = data.activity.api_requests || 0;
            document.getElementById('errors-count').textContent = data.activity.errors || 0;
        }
    }
}

function downloadReport() {
    const reportData = {
        timestamp: new Date().toISOString(),
        type: 'system_monitoring',
        format: 'pdf'
    };
    
    // Créer un lien de téléchargement
    const link = document.createElement('a');
    link.href = '/api/exports/reports?' + new URLSearchParams(reportData);
    link.download = `system-report-${new Date().toISOString().split('T')[0]}.pdf`;
    link.click();
}

// Démarrer le monitoring au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    refreshData();
    startMonitoring();
});

// Arrêter le monitoring quand on quitte la page
window.addEventListener('beforeunload', stopMonitoring);
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
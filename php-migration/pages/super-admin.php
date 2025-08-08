<?php
/**
 * Page Super Admin
 * Interface super administrateur pour gestion globale
 */

// Vérifier l'authentification et permissions
Auth::requireAuth();

if (!Auth::hasRole('super_admin')) {
    http_response_code(403);
    Utils::redirectWithMessage('/dashboard', 'Accès non autorisé', 'error');
    exit;
}

$pageTitle = "Super Administration - StacGateLMS";
$pageDescription = "Interface de super administration pour la gestion globale de la plateforme.";

$currentUser = Auth::user();

// Initialiser les services
$establishmentService = new EstablishmentService();
$authService = new AuthService();
$systemService = new SystemService();
$analyticsService = new AnalyticsService();

// Traitement des actions
$action = $_POST['action'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['_token'] ?? '')) {
    try {
        switch ($action) {
            case 'create_establishment':
                $establishmentData = [
                    'name' => $_POST['name'],
                    'slug' => $_POST['slug'],
                    'description' => $_POST['description'],
                    'category' => $_POST['category'],
                    'contact_email' => $_POST['contact_email'],
                    'website' => $_POST['website'] ?? null,
                    'is_active' => true
                ];
                
                $newEstablishment = $establishmentService->createEstablishment($establishmentData);
                $message = ['type' => 'success', 'text' => 'Établissement créé avec succès'];
                break;
                
            case 'toggle_establishment':
                $establishmentId = $_POST['establishment_id'];
                $newStatus = $_POST['status'] === 'active';
                
                $establishmentService->updateEstablishment($establishmentId, ['is_active' => $newStatus]);
                $message = ['type' => 'success', 'text' => 'Statut mis à jour avec succès'];
                break;
                
            case 'create_global_admin':
                $adminData = [
                    'first_name' => $_POST['first_name'],
                    'last_name' => $_POST['last_name'],
                    'email' => $_POST['email'],
                    'password' => $_POST['password'],
                    'role' => 'admin',
                    'establishment_id' => $_POST['establishment_id']
                ];
                
                $newAdmin = $authService->createUser($adminData);
                $message = ['type' => 'success', 'text' => 'Administrateur créé avec succès'];
                break;
                
            case 'maintenance_mode_global':
                $enabled = $_POST['enabled'] === 'true';
                $maintenanceMessage = $_POST['maintenance_message'] ?? '';
                $systemService->setMaintenanceMode($enabled, $maintenanceMessage);
                $message = ['type' => 'success', 'text' => $enabled ? 'Mode maintenance global activé' : 'Mode maintenance global désactivé'];
                break;
        }
    } catch (Exception $e) {
        $message = ['type' => 'error', 'text' => $e->getMessage()];
        Utils::log("Super admin error: " . $e->getMessage(), 'ERROR');
    }
}

// Obtenir les données
try {
    $establishments = $establishmentService->getAllEstablishments();
    $globalStats = $analyticsService->getGlobalStats();
    $systemHealth = $systemService->healthCheck();
    $recentActivity = $systemService->getRecentActivity(24);
    
} catch (Exception $e) {
    Utils::log("Super admin page error: " . $e->getMessage(), 'ERROR');
    $establishments = [];
    $globalStats = [];
    $systemHealth = [];
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
                        👑 Super Administration
                    </h1>
                    <p style="opacity: 0.8;">
                        Gestion globale de la plateforme multi-établissements
                    </p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <a href="/system-monitoring" class="glass-button">
                        🖥️ Monitoring
                    </a>
                    <a href="/system-updates" class="glass-button glass-button-secondary">
                        🔄 Mises à jour
                    </a>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?= $message['type'] ?>" style="margin-top: 1rem; padding: 1rem; border-radius: 8px; background: rgba(<?= $message['type'] === 'success' ? '34, 197, 94' : '239, 68, 68' ?>, 0.1); color: <?= $message['type'] === 'success' ? '#22c55e' : '#ef4444' ?>;">
                    <?= htmlspecialchars($message['text']) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Métriques globales -->
        <div class="grid grid-4 mb-8">
            <div class="glassmorphism p-4 text-center">
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-primary));">
                    <?= count($establishments) ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Établissements</div>
            </div>
            <div class="glassmorphism p-4 text-center">
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-secondary));">
                    <?= $globalStats['total_users'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Utilisateurs</div>
            </div>
            <div class="glassmorphism p-4 text-center">
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-accent));">
                    <?= $globalStats['total_courses'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Cours</div>
            </div>
            <div class="glassmorphism p-4 text-center">
                <div style="font-size: 2.5rem; font-weight: 700; color: rgb(var(--color-success));">
                    <?= $globalStats['total_enrollments'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Inscriptions</div>
            </div>
        </div>

        <div class="grid grid-2 mb-8">
            <!-- Santé système -->
            <div class="glassmorphism p-6">
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                    🏥 Santé du Système
                </h2>
                
                <?php if (!empty($systemHealth['checks'])): ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <?php foreach ($systemHealth['checks'] as $checkName => $check): ?>
                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(255,255,255,0.02); border-radius: 6px;">
                                <div style="font-size: 1.2rem;">
                                    <?= $check['status'] === 'healthy' ? '✅' : ($check['status'] === 'warning' ? '⚠️' : '❌') ?>
                                </div>
                                <div>
                                    <div style="font-weight: 500; text-transform: capitalize;">
                                        <?= str_replace('_', ' ', $checkName) ?>
                                    </div>
                                    <div style="font-size: 0.8rem; opacity: 0.7;">
                                        <?= htmlspecialchars($check['message']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; opacity: 0.7;">
                        <div style="font-size: 2rem; margin-bottom: 1rem;">🔧</div>
                        <p>Vérification du système en cours...</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Activité récente -->
            <div class="glassmorphism p-6">
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                    📈 Activité Récente (24h)
                </h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div style="text-align: center; padding: 1rem; background: rgba(var(--color-primary), 0.05); border-radius: 8px;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: rgb(var(--color-primary));">
                            <?= $recentActivity['logins'] ?? 0 ?>
                        </div>
                        <div style="opacity: 0.8; font-size: 0.9rem;">Connexions</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: rgba(var(--color-secondary), 0.05); border-radius: 8px;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: rgb(var(--color-secondary));">
                            <?= $recentActivity['new_users'] ?? 0 ?>
                        </div>
                        <div style="opacity: 0.8; font-size: 0.9rem;">Nouveaux utilisateurs</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: rgba(var(--color-accent), 0.05); border-radius: 8px;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: rgb(var(--color-accent));">
                            <?= $recentActivity['api_requests'] ?? 0 ?>
                        </div>
                        <div style="opacity: 0.8; font-size: 0.9rem;">Requêtes API</div>
                    </div>
                    <div style="text-align: center; padding: 1rem; background: rgba(239, 68, 68, 0.05); border-radius: 8px;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: #ef4444;">
                            <?= $recentActivity['errors'] ?? 0 ?>
                        </div>
                        <div style="opacity: 0.8; font-size: 0.9rem;">Erreurs</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gestion des établissements -->
        <div class="glassmorphism p-6 mb-8">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.5rem; font-weight: 600; margin: 0;">
                    🏛️ Établissements
                </h2>
                <button onclick="openCreateEstablishmentModal()" class="glass-button">
                    ➕ Nouvel établissement
                </button>
            </div>
            
            <?php if (!empty($establishments)): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <th style="padding: 1rem; text-align: left; font-weight: 600;">Nom</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 600;">Slug</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 600;">Catégorie</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 600;">Utilisateurs</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 600;">Statut</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($establishments as $establishment): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td style="padding: 1rem;">
                                        <div style="font-weight: 600;"><?= htmlspecialchars($establishment['name']) ?></div>
                                        <div style="font-size: 0.8rem; opacity: 0.7;"><?= htmlspecialchars($establishment['contact_email']) ?></div>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <code style="background: rgba(255,255,255,0.1); padding: 0.25rem 0.5rem; border-radius: 4px;">
                                            <?= htmlspecialchars($establishment['slug']) ?>
                                        </code>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <span class="badge" style="background: rgba(var(--color-info), 0.1); color: rgb(var(--color-info)); padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem;">
                                            <?= ucfirst($establishment['category']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <?= $establishment['user_count'] ?? 0 ?>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <span class="badge <?= $establishment['is_active'] ? 'badge-success' : 'badge-error' ?>" style="padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem;">
                                            <?= $establishment['is_active'] ? 'Actif' : 'Inactif' ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="/establishment/<?= htmlspecialchars($establishment['slug']) ?>" 
                                               class="glass-button glass-button-secondary" 
                                               style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"
                                               target="_blank">
                                                👁️
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                                                <input type="hidden" name="action" value="toggle_establishment">
                                                <input type="hidden" name="establishment_id" value="<?= $establishment['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $establishment['is_active'] ? 'inactive' : 'active' ?>">
                                                <button type="submit" 
                                                        class="glass-button glass-button-secondary" 
                                                        style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                                    <?= $establishment['is_active'] ? '🔒' : '🔓' ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; opacity: 0.7;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🏛️</div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 1rem;">Aucun établissement</h3>
                    <p>Créez votre premier établissement pour commencer.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Actions administratives rapides -->
        <div class="grid grid-3">
            <div class="glassmorphism p-6">
                <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 1rem;">🔧 Maintenance</h3>
                <p style="opacity: 0.8; margin-bottom: 1.5rem; font-size: 0.9rem;">
                    Activer le mode maintenance global pour tous les établissements.
                </p>
                <form method="POST">
                    <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="maintenance_mode_global">
                    <input type="hidden" name="enabled" value="true">
                    <button type="submit" class="glass-button" style="width: 100%;" 
                            onclick="return confirm('Activer le mode maintenance global ?')">
                        Activer maintenance
                    </button>
                </form>
            </div>
            
            <div class="glassmorphism p-6">
                <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 1rem;">📊 Analytics</h3>
                <p style="opacity: 0.8; margin-bottom: 1.5rem; font-size: 0.9rem;">
                    Accéder aux analytics globaux de la plateforme.
                </p>
                <a href="/analytics?global=true" class="glass-button" style="width: 100%; display: block; text-align: center; text-decoration: none;">
                    Voir analytics
                </a>
            </div>
            
            <div class="glassmorphism p-6">
                <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 1rem;">👥 Utilisateurs</h3>
                <p style="opacity: 0.8; margin-bottom: 1.5rem; font-size: 0.9rem;">
                    Gestion globale des utilisateurs et permissions.
                </p>
                <a href="/user-management?global=true" class="glass-button" style="width: 100%; display: block; text-align: center; text-decoration: none;">
                    Gérer utilisateurs
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal création établissement -->
<div id="create-establishment-modal" class="modal" style="display: none;">
    <div class="modal-content glassmorphism" style="max-width: 600px;">
        <h3 style="margin-bottom: 1.5rem;">Créer un nouvel établissement</h3>
        
        <form method="POST">
            <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="create_establishment">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Nom</label>
                    <input type="text" name="name" required class="glass-input" style="width: 100%;" placeholder="Nom de l'établissement">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Slug</label>
                    <input type="text" name="slug" required class="glass-input" style="width: 100%;" placeholder="slug-etablissement">
                </div>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Description</label>
                <textarea name="description" class="glass-input" style="width: 100%; height: 80px;" placeholder="Description de l'établissement"></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Catégorie</label>
                    <select name="category" required class="glass-input" style="width: 100%;">
                        <option value="">Sélectionner...</option>
                        <option value="universite">Université</option>
                        <option value="ecole">École</option>
                        <option value="formation">Formation professionnelle</option>
                        <option value="entreprise">Entreprise</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Email de contact</label>
                    <input type="email" name="contact_email" required class="glass-input" style="width: 100%;" placeholder="contact@etablissement.fr">
                </div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Site web (optionnel)</label>
                <input type="url" name="website" class="glass-input" style="width: 100%;" placeholder="https://www.etablissement.fr">
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeCreateEstablishmentModal()" class="glass-button glass-button-secondary">
                    Annuler
                </button>
                <button type="submit" class="glass-button">
                    Créer établissement
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateEstablishmentModal() {
    document.getElementById('create-establishment-modal').style.display = 'flex';
}

function closeCreateEstablishmentModal() {
    document.getElementById('create-establishment-modal').style.display = 'none';
}

// Génération automatique du slug
document.querySelector('input[name="name"]')?.addEventListener('input', function() {
    const slug = this.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    
    const slugInput = document.querySelector('input[name="slug"]');
    if (slugInput) {
        slugInput.value = slug;
    }
});

// Fermer modal en cliquant à l'extérieur
document.getElementById('create-establishment-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateEstablishmentModal();
    }
});

// Auto-refresh des métriques toutes les 30 secondes
setInterval(async () => {
    try {
        const response = await fetch('/api/system/health');
        const data = await response.json();
        // Mettre à jour les indicateurs de santé si nécessaire
    } catch (error) {
        console.error('Erreur auto-refresh:', error);
    }
}, 30000);
</script>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    max-width: 90vw;
    max-height: 90vh;
    overflow-y: auto;
    padding: 2rem;
    border-radius: 12px;
}

.badge-success {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
}

.badge-error {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

@media (max-width: 768px) {
    .grid-4 {
        grid-template-columns: 1fr 1fr !important;
    }
    
    .grid-3 {
        grid-template-columns: 1fr !important;
    }
    
    table {
        font-size: 0.9rem;
    }
    
    table th, table td {
        padding: 0.5rem !important;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
<?php
/**
 * Page paramètres système
 */

// Vérifier l'authentification et les permissions
Auth::requireAuth();

if (!Auth::hasRole('admin')) {
    http_response_code(403);
    Utils::redirectWithMessage('/dashboard', 'Accès non autorisé', 'error');
    exit;
}

$pageTitle = "Paramètres - StacGateLMS";
$pageDescription = "Configuration et paramètres de l'établissement.";

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

// Initialiser les services
$establishmentService = new EstablishmentService();
$systemService = new SystemService();

// Obtenir les données
try {
    $establishment = $establishmentService->getEstablishmentById($establishmentId);
    $themes = $establishmentService->getEstablishmentThemes($establishmentId);
    $systemInfo = $systemService->getSystemInfo();
    
} catch (Exception $e) {
    Utils::log("Settings page error: " . $e->getMessage(), 'ERROR');
    $establishment = null;
    $themes = [];
    $systemInfo = [];
}

require_once ROOT_PATH . '/includes/header.php';
?>

<div style="padding: 2rem 0; margin-top: 80px;">
    <div class="container">
        <!-- En-tête -->
        <div class="glassmorphism p-6 mb-8">
            <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                Paramètres système
            </h1>
            <p style="opacity: 0.8;">
                Configuration et personnalisation de <?= htmlspecialchars($establishment['name'] ?? 'votre établissement') ?>
            </p>
        </div>

        <div class="grid grid-2" style="gap: 2rem; align-items: start;">
            <!-- Paramètres établissement -->
            <div class="glassmorphism p-6">
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    Établissement
                </h2>

                <form id="establishment-form" class="form">
                    <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label>Nom de l'établissement</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($establishment['name'] ?? '') ?>" 
                               class="glass-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Slug URL</label>
                        <input type="text" name="slug" value="<?= htmlspecialchars($establishment['slug'] ?? '') ?>" 
                               class="glass-input" required>
                        <small style="opacity: 0.7;">Utilisé dans l'URL : example.com/<?= htmlspecialchars($establishment['slug'] ?? 'slug') ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="glass-input" rows="3"><?= htmlspecialchars($establishment['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Logo URL</label>
                        <input type="url" name="logo" value="<?= htmlspecialchars($establishment['logo'] ?? '') ?>" 
                               class="glass-input">
                    </div>
                    
                    <div class="form-group">
                        <label>Domaine personnalisé</label>
                        <input type="text" name="domain" value="<?= htmlspecialchars($establishment['domain'] ?? '') ?>" 
                               class="glass-input" placeholder="exemple.com">
                    </div>
                    
                    <button type="submit" class="glass-button" style="background: var(--gradient-primary); color: white;">
                        Sauvegarder
                    </button>
                </form>
            </div>

            <!-- Thèmes et apparence -->
            <div class="glassmorphism p-6">
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313-12.454z"/>
                    </svg>
                    Thème et apparence
                </h2>

                <form id="theme-form" class="form">
                    <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label>Couleur primaire</label>
                        <input type="color" name="primary_color" value="#8B5CF6" class="glass-input">
                    </div>
                    
                    <div class="form-group">
                        <label>Couleur secondaire</label>
                        <input type="color" name="secondary_color" value="#A78BFA" class="glass-input">
                    </div>
                    
                    <div class="form-group">
                        <label>Couleur d'accent</label>
                        <input type="color" name="accent_color" value="#C4B5FD" class="glass-input">
                    </div>
                    
                    <div class="form-group">
                        <label>Police principale</label>
                        <select name="font_family" class="glass-input">
                            <option value="Inter">Inter</option>
                            <option value="Roboto">Roboto</option>
                            <option value="Open Sans">Open Sans</option>
                            <option value="Poppins">Poppins</option>
                            <option value="Montserrat">Montserrat</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="glass-button" style="background: var(--gradient-secondary); color: white;">
                        Appliquer le thème
                    </button>
                </form>
            </div>
        </div>

        <!-- Paramètres système -->
        <div class="glassmorphism p-6 mt-8">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 1l9 5v6c0 5.55-3.84 10.74-9 12c-5.16-1.26-9-6.45-9-12V6l9-5z"/>
                </svg>
                Paramètres système
            </h2>

            <div class="grid grid-3" style="gap: 1.5rem;">
                <!-- Informations système -->
                <div class="glass-card p-4">
                    <h3 style="font-weight: 600; margin-bottom: 1rem;">Informations</h3>
                    <div style="font-size: 0.9rem; line-height: 1.6;">
                        <div><strong>Version PHP:</strong> <?= $systemInfo['php_version'] ?? 'N/A' ?></div>
                        <div><strong>Base de données:</strong> <?= $systemInfo['db_type'] ?? 'N/A' ?></div>
                        <div><strong>Espace disque:</strong> <?= $systemInfo['disk_usage'] ?? 'N/A' ?></div>
                        <div><strong>Mémoire:</strong> <?= $systemInfo['memory_usage'] ?? 'N/A' ?></div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="glass-card p-4">
                    <h3 style="font-weight: 600; margin-bottom: 1rem;">Actions</h3>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <button onclick="clearCache()" class="glass-button glass-button-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                            Vider le cache
                        </button>
                        <button onclick="optimizeDatabase()" class="glass-button glass-button-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                            Optimiser la DB
                        </button>
                        <button onclick="generateBackup()" class="glass-button glass-button-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                            Créer sauvegarde
                        </button>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="glass-card p-4">
                    <h3 style="font-weight: 600; margin-bottom: 1rem;">Activité</h3>
                    <div style="font-size: 0.9rem; line-height: 1.6;">
                        <div><strong>Connexions aujourd'hui:</strong> <?= $systemInfo['connections_today'] ?? '0' ?></div>
                        <div><strong>Requêtes API:</strong> <?= $systemInfo['api_requests'] ?? '0' ?></div>
                        <div><strong>Erreurs 24h:</strong> <?= $systemInfo['errors_24h'] ?? '0' ?></div>
                        <div><strong>Uptime:</strong> <?= $systemInfo['uptime'] ?? 'N/A' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Gestion formulaire établissement
document.getElementById('establishment-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await apiRequest('/api/establishments/<?= $establishmentId ?>', 'PUT', data);
        
        if (response.error) {
            showToast(response.error, 'error');
        } else {
            showToast('Établissement mis à jour avec succès', 'success');
        }
    } catch (error) {
        showToast('Erreur lors de la mise à jour', 'error');
    }
});

// Gestion formulaire thème
document.getElementById('theme-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await apiRequest('/api/establishments/<?= $establishmentId ?>/themes', 'POST', data);
        
        if (response.error) {
            showToast(response.error, 'error');
        } else {
            showToast('Thème appliqué avec succès', 'success');
            // Recharger la page pour appliquer le nouveau thème
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        showToast('Erreur lors de l\'application du thème', 'error');
    }
});

// Actions système
async function clearCache() {
    try {
        const response = await apiRequest('/api/system/clear-cache', 'POST', {
            _token: '<?= generateCSRFToken() ?>'
        });
        
        if (response.error) {
            showToast(response.error, 'error');
        } else {
            showToast('Cache vidé avec succès', 'success');
        }
    } catch (error) {
        showToast('Erreur lors du vidage du cache', 'error');
    }
}

async function optimizeDatabase() {
    try {
        const response = await apiRequest('/api/system/optimize-db', 'POST', {
            _token: '<?= generateCSRFToken() ?>'
        });
        
        if (response.error) {
            showToast(response.error, 'error');
        } else {
            showToast('Base de données optimisée', 'success');
        }
    } catch (error) {
        showToast('Erreur lors de l\'optimisation', 'error');
    }
}

async function generateBackup() {
    try {
        const response = await apiRequest('/api/exports', 'POST', {
            _token: '<?= generateCSRFToken() ?>',
            type: 'backup',
            format: 'zip',
            tables: ['all']
        });
        
        if (response.error) {
            showToast(response.error, 'error');
        } else {
            showToast('Sauvegarde créée avec succès', 'success');
        }
    } catch (error) {
        showToast('Erreur lors de la création de la sauvegarde', 'error');
    }
}
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
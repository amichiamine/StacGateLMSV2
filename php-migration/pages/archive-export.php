<?php
/**
 * Page archives et exports
 */

// Vérifier l'authentification et les permissions
Auth::requireAuth();

if (!Auth::hasRole('manager')) {
    http_response_code(403);
    Utils::redirectWithMessage('/dashboard', 'Accès non autorisé', 'error');
    exit;
}

$pageTitle = "Archives & Export - StacGateLMS";
$pageDescription = "Sauvegardez et exportez vos données d'établissement.";

$currentUser = Auth::user();
$establishmentId = Auth::hasRole('super_admin') ? null : $currentUser['establishment_id'];

// Initialiser les services
$exportService = new ExportService();

// Paramètres
$page = intval($_GET['page'] ?? 1);
$perPage = 15;
$status = $_GET['status'] ?? '';

// Traitement des actions
$action = $_POST['action'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['_token'] ?? '')) {
    try {
        switch ($action) {
            case 'create_export':
                $exportData = [
                    'type' => $_POST['export_type'],
                    'format' => $_POST['format'],
                    'establishment_id' => $establishmentId,
                    'filters' => json_encode($_POST['filters'] ?? []),
                    'created_by' => $currentUser['id']
                ];
                
                $export = $exportService->createExportJob($exportData);
                $message = ['type' => 'success', 'text' => 'Export créé avec succès. Traitement en cours...'];
                break;
                
            case 'delete_export':
                $exportId = intval($_POST['export_id']);
                $exportService->deleteExportJob($exportId);
                $message = ['type' => 'success', 'text' => 'Export supprimé avec succès'];
                break;
        }
    } catch (Exception $e) {
        $message = ['type' => 'error', 'text' => $e->getMessage()];
        Utils::log("Archive export error: " . $e->getMessage(), 'ERROR');
    }
}

// Obtenir les données
try {
    $exportsData = $exportService->getExportJobs($establishmentId, $page, $perPage);
    $exports = $exportsData['data'];
    $meta = $exportsData['meta'];
    
} catch (Exception $e) {
    Utils::log("Archive export page error: " . $e->getMessage(), 'ERROR');
    $exports = [];
    $meta = ['total' => 0, 'current_page' => 1, 'total_pages' => 1];
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
                        Archives & Export
                    </h1>
                    <p style="opacity: 0.8;">
                        Sauvegardez et exportez vos données d'établissement
                    </p>
                </div>
                
                <button onclick="openExportModal()" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 0.75rem 1.5rem;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" style="margin-right: 0.5rem;">
                        <path d="M12 4v16m8-8H4"/>
                    </svg>
                    Nouvel export
                </button>
            </div>
        </div>

        <!-- Message -->
        <?php if ($message): ?>
            <div class="glassmorphism p-4 mb-6" style="border-left: 4px solid <?= $message['type'] === 'success' ? 'rgb(var(--color-primary))' : '#ef4444' ?>;">
                <div style="color: <?= $message['type'] === 'success' ? 'rgb(var(--color-primary))' : '#ef4444' ?>; font-weight: 500;">
                    <?= htmlspecialchars($message['text']) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Actions rapides -->
        <div class="grid grid-4 mb-8">
            <div class="glass-card p-6 text-center export-quick-action" onclick="quickExport('users', 'csv')">
                <div style="font-size: 3rem; margin-bottom: 1rem; color: rgb(var(--color-primary));">👥</div>
                <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Utilisateurs</h3>
                <p style="opacity: 0.8; font-size: 0.9rem; margin-bottom: 1rem;">
                    Exporter tous les utilisateurs en CSV
                </p>
                <div class="glass-button glass-button-secondary" style="width: 100%; padding: 0.5rem;">
                    Export rapide
                </div>
            </div>
            
            <div class="glass-card p-6 text-center export-quick-action" onclick="quickExport('courses', 'csv')">
                <div style="font-size: 3rem; margin-bottom: 1rem; color: rgb(var(--color-secondary));">📚</div>
                <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Cours</h3>
                <p style="opacity: 0.8; font-size: 0.9rem; margin-bottom: 1rem;">
                    Exporter tous les cours en CSV
                </p>
                <div class="glass-button glass-button-secondary" style="width: 100%; padding: 0.5rem;">
                    Export rapide
                </div>
            </div>
            
            <div class="glass-card p-6 text-center export-quick-action" onclick="quickExport('analytics', 'json')">
                <div style="font-size: 3rem; margin-bottom: 1rem; color: rgb(var(--color-accent));">📊</div>
                <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Analytics</h3>
                <p style="opacity: 0.8; font-size: 0.9rem; margin-bottom: 1rem;">
                    Exporter les statistiques en JSON
                </p>
                <div class="glass-button glass-button-secondary" style="width: 100%; padding: 0.5rem;">
                    Export rapide
                </div>
            </div>
            
            <div class="glass-card p-6 text-center export-quick-action" onclick="quickExport('full_backup', 'zip')">
                <div style="font-size: 3rem; margin-bottom: 1rem; color: rgb(var(--color-primary));">💾</div>
                <h3 style="font-weight: 600; margin-bottom: 0.5rem;">Backup complet</h3>
                <p style="opacity: 0.8; font-size: 0.9rem; margin-bottom: 1rem;">
                    Sauvegarde complète en ZIP
                </p>
                <div class="glass-button glass-button-secondary" style="width: 100%; padding: 0.5rem;">
                    Backup complet
                </div>
            </div>
        </div>

        <!-- Liste des exports -->
        <div class="glassmorphism">
            <div style="padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
                <h2 style="font-size: 1.5rem; font-weight: 600;">Historique des exports</h2>
            </div>
            
            <?php if (!empty($exports)): ?>
                <!-- En-têtes du tableau -->
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 150px; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); font-weight: 600; background: rgba(var(--color-primary), 0.05);">
                    <div>Nom & Type</div>
                    <div>Format</div>
                    <div>Statut</div>
                    <div>Taille</div>
                    <div>Date</div>
                    <div>Actions</div>
                </div>
                
                <!-- Lignes exports -->
                <?php foreach ($exports as $export): ?>
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 150px; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); align-items: center;">
                        <!-- Nom & Type -->
                        <div>
                            <div style="font-weight: 600; margin-bottom: 0.25rem;">
                                <?= htmlspecialchars($export['name'] ?? 'Export sans nom') ?>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="badge" style="background: rgba(var(--color-primary), 0.1); color: rgb(var(--color-primary)); font-size: 0.7rem;">
                                    <?= strtoupper($export['type']) ?>
                                </span>
                                <?php if ($export['filters']): ?>
                                    <span style="font-size: 0.7rem; opacity: 0.6;">avec filtres</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Format -->
                        <div>
                            <span class="badge badge-secondary" style="font-size: 0.8rem;">
                                <?= strtoupper($export['format']) ?>
                            </span>
                        </div>
                        
                        <!-- Statut -->
                        <div>
                            <?php
                            $statusClass = 'badge-warning';
                            $statusIcon = '⏳';
                            if ($export['status'] === 'completed') {
                                $statusClass = 'badge-success';
                                $statusIcon = '✅';
                            } elseif ($export['status'] === 'failed') {
                                $statusClass = 'badge-error'; 
                                $statusIcon = '❌';
                            }
                            ?>
                            <span class="badge <?= $statusClass ?>" style="font-size: 0.8rem;">
                                <?= $statusIcon ?> <?= ucfirst($export['status']) ?>
                            </span>
                        </div>
                        
                        <!-- Taille -->
                        <div style="font-size: 0.9rem;">
                            <?php if ($export['file_size']): ?>
                                <?= Utils::formatFileSize($export['file_size']) ?>
                            <?php else: ?>
                                <span style="opacity: 0.6;">-</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Date -->
                        <div style="font-size: 0.9rem; opacity: 0.8;">
                            <?= Utils::formatDate($export['created_at'], 'd/m/Y H:i') ?>
                        </div>
                        
                        <!-- Actions -->
                        <div style="display: flex; gap: 0.25rem;">
                            <?php if ($export['status'] === 'completed'): ?>
                                <a href="/api/exports/<?= $export['id'] ?>/download" 
                                   class="glass-button glass-button-secondary" 
                                   style="padding: 0.5rem; font-size: 0.8rem;" 
                                   title="Télécharger">
                                    💾
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($export['status'] === 'processing'): ?>
                                <button onclick="refreshExportStatus(<?= $export['id'] ?>)" 
                                        class="glass-button glass-button-secondary" 
                                        style="padding: 0.5rem; font-size: 0.8rem;" 
                                        title="Actualiser">
                                    🔄
                                </button>
                            <?php endif; ?>
                            
                            <button onclick="deleteExport(<?= $export['id'] ?>)" 
                                    class="glass-button" 
                                    style="padding: 0.5rem; font-size: 0.8rem; background: rgba(239, 68, 68, 0.1); color: #ef4444;" 
                                    title="Supprimer">
                                🗑️
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- État vide -->
                <div style="padding: 4rem 2rem; text-center;">
                    <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;">📦</div>
                    <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">
                        Aucun export
                    </h3>
                    <p style="opacity: 0.8; margin-bottom: 2rem;">
                        Créez votre premier export pour sauvegarder vos données.
                    </p>
                    <button onclick="openExportModal()" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 1rem 2rem;">
                        Créer un export
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($meta['total_pages'] > 1): ?>
            <div style="display: flex; justify-content: center; margin-top: 2rem; gap: 0.5rem;">
                <?php for ($i = 1; $i <= $meta['total_pages']; $i++): ?>
                    <a href="?page=<?= $i ?>" 
                       class="glass-button <?= $i == $meta['current_page'] ? 'active' : '' ?>" 
                       style="padding: 0.5rem 1rem; <?= $i == $meta['current_page'] ? 'background: var(--gradient-primary); color: white;' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal création d'export -->
<div id="exportModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
    <div class="glassmorphism" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; font-weight: 600;">Créer un export</h2>
                <button onclick="closeExportModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; opacity: 0.7;">&times;</button>
            </div>
            
            <form id="exportForm" method="POST">
                <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="create_export">
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Type d'export *</label>
                    <select name="export_type" id="exportType" required class="glass-input" style="width: 100%; padding: 0.75rem 1rem;">
                        <option value="">Sélectionner un type</option>
                        <option value="users">Utilisateurs</option>
                        <option value="courses">Cours</option>
                        <option value="analytics">Analytics</option>
                        <option value="assessments">Évaluations</option>
                        <option value="study_groups">Groupes d'étude</option>
                        <option value="full_backup">Backup complet</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Format *</label>
                    <select name="format" id="exportFormat" required class="glass-input" style="width: 100%; padding: 0.75rem 1rem;">
                        <option value="">Sélectionner un format</option>
                        <option value="csv">CSV</option>
                        <option value="json">JSON</option>
                        <option value="xml">XML</option>
                        <option value="pdf">PDF (rapport)</option>
                        <option value="zip">ZIP (backup)</option>
                    </select>
                </div>
                
                <!-- Filtres dynamiques -->
                <div id="filtersSection" style="margin-bottom: 1.5rem; display: none;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Filtres (optionnel)</label>
                    <div id="filtersContainer" style="padding: 1rem; background: rgba(var(--color-primary), 0.05); border-radius: 0.5rem;">
                        <!-- Les filtres seront ajoutés dynamiquement selon le type -->
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeExportModal()" class="glass-button glass-button-secondary" style="padding: 0.75rem 1.5rem;">
                        Annuler
                    </button>
                    <button type="submit" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 0.75rem 1.5rem;">
                        Créer l'export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openExportModal() {
    document.getElementById('exportModal').style.display = 'flex';
}

function closeExportModal() {
    document.getElementById('exportModal').style.display = 'none';
    document.getElementById('exportForm').reset();
    document.getElementById('filtersSection').style.display = 'none';
}

function quickExport(type, format) {
    document.getElementById('exportType').value = type;
    document.getElementById('exportFormat').value = format;
    updateFilters();
    openExportModal();
}

function updateFilters() {
    const type = document.getElementById('exportType').value;
    const filtersSection = document.getElementById('filtersSection');
    const filtersContainer = document.getElementById('filtersContainer');
    
    if (!type) {
        filtersSection.style.display = 'none';
        return;
    }
    
    filtersSection.style.display = 'block';
    filtersContainer.innerHTML = '';
    
    // Filtres selon le type
    switch (type) {
        case 'users':
            filtersContainer.innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.9rem; margin-bottom: 0.25rem;">Rôle</label>
                        <select name="filters[role]" class="glass-input" style="width: 100%; padding: 0.5rem;">
                            <option value="">Tous les rôles</option>
                            <option value="apprenant">Apprenant</option>
                            <option value="formateur">Formateur</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.9rem; margin-bottom: 0.25rem;">Statut</label>
                        <select name="filters[status]" class="glass-input" style="width: 100%; padding: 0.5rem;">
                            <option value="">Tous</option>
                            <option value="active">Actifs</option>
                            <option value="inactive">Inactifs</option>
                        </select>
                    </div>
                </div>
            `;
            break;
            
        case 'courses':
            filtersContainer.innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.9rem; margin-bottom: 0.25rem;">Catégorie</label>
                        <select name="filters[category]" class="glass-input" style="width: 100%; padding: 0.5rem;">
                            <option value="">Toutes</option>
                            <option value="informatique">Informatique</option>
                            <option value="mathematiques">Mathématiques</option>
                            <option value="langues">Langues</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.9rem; margin-bottom: 0.25rem;">Statut</label>
                        <select name="filters[status]" class="glass-input" style="width: 100%; padding: 0.5rem;">
                            <option value="">Tous</option>
                            <option value="active">Actifs</option>
                            <option value="draft">Brouillons</option>
                        </select>
                    </div>
                </div>
            `;
            break;
            
        case 'analytics':
            filtersContainer.innerHTML = `
                <div>
                    <label style="display: block; font-size: 0.9rem; margin-bottom: 0.25rem;">Période</label>
                    <select name="filters[period]" class="glass-input" style="width: 100%; padding: 0.5rem;">
                        <option value="30">30 derniers jours</option>
                        <option value="90">90 derniers jours</option>
                        <option value="365">1 an</option>
                        <option value="all">Toute la période</option>
                    </select>
                </div>
            `;
            break;
    }
}

function deleteExport(exportId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet export ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="delete_export">
            <input type="hidden" name="export_id" value="${exportId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

async function refreshExportStatus(exportId) {
    try {
        const response = await apiRequest(`/api/exports/${exportId}`);
        if (response.success) {
            window.location.reload();
        }
    } catch (error) {
        console.error('Erreur refresh status:', error);
    }
}

// Event listeners
document.getElementById('exportType').addEventListener('change', updateFilters);

document.getElementById('exportModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeExportModal();
    }
});

// Animation des actions rapides
document.querySelectorAll('.export-quick-action').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-3px)';
        this.style.transition = 'transform 0.3s ease';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});
</script>

<style>
.export-quick-action {
    cursor: pointer;
    transition: all 0.3s ease;
}

.export-quick-action:hover {
    transform: translateY(-3px);
}

@media (max-width: 768px) {
    .grid-4 {
        grid-template-columns: 1fr 1fr;
    }
    
    .glassmorphism > div[style*="grid-template-columns"] {
        display: block !important;
        padding: 1rem !important;
    }
    
    .glassmorphism > div[style*="grid-template-columns"] > div {
        margin-bottom: 0.5rem;
    }
    
    .glassmorphism > div[style*="grid-template-columns"]:first-child {
        display: none !important;
    }
}

@media (max-width: 480px) {
    .grid-4 {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
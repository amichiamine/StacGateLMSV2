<?php
/**
 * Page évaluations
 */

// Vérifier l'authentification et les permissions
Auth::requireAuth();

if (!Auth::hasRole('formateur')) {
    http_response_code(403);
    Utils::redirectWithMessage('/dashboard', 'Accès non autorisé', 'error');
    exit;
}

$pageTitle = "Évaluations - StacGateLMS";
$pageDescription = "Créez et gérez vos évaluations, examens et quiz.";

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

// Initialiser les services
$assessmentService = new AssessmentService();

// Paramètres
$page = intval($_GET['page'] ?? 1);
$perPage = 12;
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$filters = [];
if ($search) $filters['search'] = $search;
if ($status) $filters['status'] = $status;

// Si c'est un formateur (non manager), montrer seulement ses évaluations
if (!Auth::hasRole('manager')) {
    $filters['created_by'] = $currentUser['id'];
}

// Obtenir les données
try {
    $assessmentsData = $assessmentService->getAssessmentsByEstablishment($establishmentId, $page, $perPage, $filters);
    $assessments = $assessmentsData['data'];
    $meta = $assessmentsData['meta'];
    
    // Statistiques générales
    $stats = $assessmentService->getGeneralStats($establishmentId);
    
} catch (Exception $e) {
    Utils::log("Assessments page error: " . $e->getMessage(), 'ERROR');
    $assessments = [];
    $meta = ['total' => 0, 'current_page' => 1, 'total_pages' => 1];
    $stats = [];
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
                        Évaluations & Examens
                    </h1>
                    <p style="opacity: 0.8;">
                        Créez et gérez vos évaluations - Total : <?= $meta['total'] ?> évaluations
                    </p>
                </div>
                
                <button onclick="createAssessment()" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 0.75rem 1.5rem;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" style="margin-right: 0.5rem;">
                        <path d="M12 4v16m8-8H4"/>
                    </svg>
                    Nouvelle évaluation
                </button>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-4 mb-8">
            <div class="glass-card p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.5rem;">
                    <?= $stats['total_assessments'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Total évaluations</div>
            </div>
            
            <div class="glass-card p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-secondary)); margin-bottom: 0.5rem;">
                    <?= $stats['active_assessments'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Évaluations actives</div>
            </div>
            
            <div class="glass-card p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-accent)); margin-bottom: 0.5rem;">
                    <?= $stats['total_attempts'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Tentatives totales</div>
            </div>
            
            <div class="glass-card p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.5rem;">
                    <?= intval($stats['average_score'] ?? 0) ?>%
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Score moyen</div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="glassmorphism p-4 mb-6">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Rechercher une évaluation..." 
                           class="glass-input" style="width: 100%; padding: 0.75rem 1rem;">
                </div>
                
                <select name="status" class="glass-input" style="padding: 0.75rem 1rem;">
                    <option value="">Tous les statuts</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                    <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archivée</option>
                </select>
                
                <button type="submit" class="glass-button" style="padding: 0.75rem 1.5rem;">
                    Filtrer
                </button>
            </form>
        </div>

        <!-- Grille des évaluations -->
        <?php if (!empty($assessments)): ?>
            <div class="grid grid-3 mb-8">
                <?php foreach ($assessments as $assessment): ?>
                    <div class="glass-card p-6 assessment-card">
                        <!-- En-tête -->
                        <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 1rem;">
                            <div style="flex: 1;">
                                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">
                                    <?= htmlspecialchars($assessment['title']) ?>
                                </h3>
                                
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <span class="badge" style="background: rgba(var(--color-primary), 0.1); color: rgb(var(--color-primary)); font-size: 0.75rem;">
                                        <?= htmlspecialchars($assessment['type'] ?? 'Quiz') ?>
                                    </span>
                                    
                                    <span class="badge <?= $assessment['status'] === 'active' ? 'badge-success' : ($assessment['status'] === 'draft' ? 'badge-warning' : 'badge-error') ?>">
                                        <?= ucfirst($assessment['status']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div style="opacity: 0.7; font-size: 0.8rem; text-align: right;">
                                <?= Utils::timeAgo($assessment['created_at']) ?>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <?php if ($assessment['description']): ?>
                            <p style="opacity: 0.8; font-size: 0.9rem; margin-bottom: 1rem;">
                                <?= Utils::truncate(htmlspecialchars($assessment['description']), 120) ?>
                            </p>
                        <?php endif; ?>
                        
                        <!-- Métadonnées -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; font-size: 0.8rem;">
                            <div>
                                <div style="font-weight: 600; color: rgb(var(--color-primary));">
                                    <?= $assessment['questions_count'] ?? 0 ?>
                                </div>
                                <div style="opacity: 0.7;">Questions</div>
                            </div>
                            
                            <div>
                                <div style="font-weight: 600; color: rgb(var(--color-secondary));">
                                    <?= $assessment['duration'] ?? 0 ?>min
                                </div>
                                <div style="opacity: 0.7;">Durée</div>
                            </div>
                            
                            <div>
                                <div style="font-weight: 600; color: rgb(var(--color-accent));">
                                    <?= $assessment['attempts_count'] ?? 0 ?>
                                </div>
                                <div style="opacity: 0.7;">Tentatives</div>
                            </div>
                            
                            <div>
                                <div style="font-weight: 600; color: rgb(var(--color-primary));">
                                    <?= intval($assessment['average_score'] ?? 0) ?>%
                                </div>
                                <div style="opacity: 0.7;">Score moyen</div>
                            </div>
                        </div>
                        
                        <!-- Barre de progression des tentatives -->
                        <?php if ($assessment['max_attempts'] > 0): ?>
                            <div style="margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem; font-size: 0.8rem;">
                                    <span>Tentatives utilisées</span>
                                    <span><?= $assessment['attempts_count'] ?>/<?= $assessment['max_attempts'] ?></span>
                                </div>
                                <div style="background: rgba(255, 255, 255, 0.1); height: 6px; border-radius: 3px; overflow: hidden;">
                                    <div style="background: rgb(var(--color-accent)); height: 100%; width: <?= min(100, ($assessment['attempts_count'] / $assessment['max_attempts']) * 100) ?>%; transition: width 0.3s ease;"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Actions -->
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="/assessments/<?= $assessment['id'] ?>" class="glass-button" style="flex: 1; text-align: center; padding: 0.75rem;">
                                Voir détails
                            </a>
                            
                            <?php if ($assessment['created_by'] == $currentUser['id'] || Auth::hasRole('admin')): ?>
                                <button onclick="editAssessment(<?= $assessment['id'] ?>)" class="glass-button glass-button-secondary" style="padding: 0.75rem;">
                                    ✏️
                                </button>
                                
                                <button onclick="duplicateAssessment(<?= $assessment['id'] ?>)" class="glass-button glass-button-secondary" style="padding: 0.75rem;" title="Dupliquer">
                                    📄
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- État vide -->
            <div class="glassmorphism p-8 text-center">
                <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;">📝</div>
                <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">
                    Aucune évaluation trouvée
                </h3>
                <p style="opacity: 0.8; margin-bottom: 2rem;">
                    <?php if ($search || $status): ?>
                        Aucune évaluation ne correspond à vos critères de recherche.
                    <?php else: ?>
                        Créez votre première évaluation pour commencer à tester vos apprenants.
                    <?php endif; ?>
                </p>
                
                <?php if ($search || $status): ?>
                    <a href="/assessments" class="glass-button glass-button-secondary" style="margin-right: 1rem; padding: 1rem 2rem;">
                        Voir toutes les évaluations
                    </a>
                <?php endif; ?>
                
                <button onclick="createAssessment()" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 1rem 2rem;">
                    Créer une évaluation
                </button>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($meta['total_pages'] > 1): ?>
            <div style="display: flex; justify-content: center; margin-top: 2rem; gap: 0.5rem;">
                <?php for ($i = 1; $i <= $meta['total_pages']; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" 
                       class="glass-button <?= $i == $meta['current_page'] ? 'active' : '' ?>" 
                       style="padding: 0.5rem 1rem; <?= $i == $meta['current_page'] ? 'background: var(--gradient-primary); color: white;' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal création rapide -->
<div id="quickCreateModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
    <div class="glassmorphism" style="width: 90%; max-width: 600px;">
        <div style="padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="font-size: 1.5rem; font-weight: 600;">Création rapide d'évaluation</h2>
                <button onclick="closeQuickCreate()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; opacity: 0.7;">&times;</button>
            </div>
            
            <form id="quickCreateForm" onsubmit="submitQuickCreate(event)">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Titre de l'évaluation *</label>
                    <input type="text" id="assessmentTitle" required class="glass-input" style="width: 100%; padding: 0.75rem 1rem;" placeholder="Ex: Quiz Chapitre 1 - Introduction">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Description</label>
                    <textarea id="assessmentDescription" class="glass-input" style="width: 100%; padding: 0.75rem 1rem; height: 80px; resize: vertical;" placeholder="Description optionnelle de l'évaluation"></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Type</label>
                        <select id="assessmentType" class="glass-input" style="width: 100%; padding: 0.75rem 1rem;">
                            <option value="quiz">Quiz</option>
                            <option value="exam">Examen</option>
                            <option value="test">Test</option>
                            <option value="survey">Sondage</option>
                        </select>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Durée (minutes)</label>
                        <input type="number" id="assessmentDuration" class="glass-input" style="width: 100%; padding: 0.75rem 1rem;" value="30" min="1" max="300">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Tentatives max</label>
                        <input type="number" id="maxAttempts" class="glass-input" style="width: 100%; padding: 0.75rem 1rem;" value="3" min="1" max="10">
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Note minimale (%)</label>
                        <input type="number" id="passingScore" class="glass-input" style="width: 100%; padding: 0.75rem 1rem;" value="60" min="0" max="100">
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeQuickCreate()" class="glass-button glass-button-secondary" style="padding: 0.75rem 1.5rem;">
                        Annuler
                    </button>
                    <button type="submit" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 0.75rem 1.5rem;">
                        Créer & Continuer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function createAssessment() {
    document.getElementById('quickCreateModal').style.display = 'flex';
    document.getElementById('assessmentTitle').focus();
}

function closeQuickCreate() {
    document.getElementById('quickCreateModal').style.display = 'none';
    document.getElementById('quickCreateForm').reset();
}

async function submitQuickCreate(event) {
    event.preventDefault();
    
    const formData = {
        title: document.getElementById('assessmentTitle').value,
        description: document.getElementById('assessmentDescription').value,
        type: document.getElementById('assessmentType').value,
        duration: parseInt(document.getElementById('assessmentDuration').value),
        max_attempts: parseInt(document.getElementById('maxAttempts').value),
        passing_score: parseInt(document.getElementById('passingScore').value),
        status: 'draft'
    };
    
    try {
        const response = await apiRequest('/api/assessments', 'POST', formData);
        
        if (response.success) {
            showToast('Évaluation créée avec succès', 'success');
            closeQuickCreate();
            // Rediriger vers l'éditeur d'évaluation
            window.location.href = `/assessments/${response.assessment.id}/edit`;
        } else {
            showToast(response.error || 'Erreur lors de la création', 'error');
        }
    } catch (error) {
        console.error('Erreur création évaluation:', error);
        showToast('Erreur lors de la création de l\'évaluation', 'error');
    }
}

async function editAssessment(assessmentId) {
    window.location.href = `/assessments/${assessmentId}/edit`;
}

async function duplicateAssessment(assessmentId) {
    if (confirm('Dupliquer cette évaluation ?')) {
        try {
            const response = await apiRequest(`/api/assessments/${assessmentId}/duplicate`, 'POST');
            
            if (response.success) {
                showToast('Évaluation dupliquée avec succès', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast(response.error || 'Erreur lors de la duplication', 'error');
            }
        } catch (error) {
            showToast('Erreur lors de la duplication', 'error');
        }
    }
}

// Animation des cartes
document.querySelectorAll('.assessment-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-3px)';
        this.style.transition = 'transform 0.3s ease';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});

// Fermer le modal en cliquant en dehors
document.getElementById('quickCreateModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeQuickCreate();
    }
});
</script>

<style>
.assessment-card {
    transition: all 0.3s ease;
}

.assessment-card:hover {
    transform: translateY(-3px);
}

@media (max-width: 768px) {
    .grid-3, .grid-4 {
        grid-template-columns: 1fr;
    }
    
    #quickCreateModal > div {
        width: 95% !important;
        margin: 1rem;
    }
    
    #quickCreateModal form > div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
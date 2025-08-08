<?php
/**
 * Page groupes d'étude
 */

// Vérifier l'authentification
Auth::requireAuth();

$pageTitle = "Groupes d'étude - StacGateLMS";
$pageDescription = "Rejoignez ou créez des groupes d'étude pour apprendre ensemble.";

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

// Initialiser les services
$studyGroupService = new StudyGroupService();

// Paramètres
$page = intval($_GET['page'] ?? 1);
$perPage = 12;
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all'; // all, my_groups, moderated

$filters = [];
if ($search) $filters['search'] = $search;

// Appliquer le filtre
if ($filter === 'my_groups') {
    $filters['member_id'] = $currentUser['id'];
} elseif ($filter === 'moderated' && Auth::hasRole('formateur')) {
    $filters['moderator_id'] = $currentUser['id'];
}

// Obtenir les données
try {
    $groupsData = $studyGroupService->getStudyGroupsByEstablishment($establishmentId, $page, $perPage, $filters);
    $groups = $groupsData['data'];
    $meta = $groupsData['meta'];
    
    // Mes groupes si on n'est pas déjà en train de les filtrer
    if ($filter !== 'my_groups') {
        $myGroupsData = $studyGroupService->getUserGroups($currentUser['id'], 1, 6);
        $myGroups = $myGroupsData['data'];
    } else {
        $myGroups = [];
    }
    
    // Statistiques
    $stats = $studyGroupService->getStudyGroupStats($establishmentId);
    
} catch (Exception $e) {
    Utils::log("Study groups page error: " . $e->getMessage(), 'ERROR');
    $groups = $myGroups = [];
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
                        Groupes d'étude
                    </h1>
                    <p style="opacity: 0.8;">
                        Apprenez en collaboration avec d'autres étudiants
                    </p>
                </div>
                
                <button onclick="createGroup()" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 0.75rem 1.5rem;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" style="margin-right: 0.5rem;">
                        <path d="M12 4v16m8-8H4"/>
                    </svg>
                    Créer un groupe
                </button>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-4 mb-8">
            <div class="glass-card p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.5rem;">
                    <?= $stats['total_groups'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Groupes actifs</div>
            </div>
            
            <div class="glass-card p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-secondary)); margin-bottom: 0.5rem;">
                    <?= $stats['total_members'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Membres total</div>
            </div>
            
            <div class="glass-card p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-accent)); margin-bottom: 0.5rem;">
                    <?= count($myGroups) ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Mes groupes</div>
            </div>
            
            <div class="glass-card p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.5rem;">
                    <?= $stats['messages_today'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Messages aujourd'hui</div>
            </div>
        </div>

        <!-- Mes groupes (si pas déjà filtré) -->
        <?php if ($filter !== 'my_groups' && !empty($myGroups)): ?>
            <div class="glassmorphism p-6 mb-8">
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">Mes groupes</h2>
                
                <div class="grid grid-3">
                    <?php foreach ($myGroups as $group): ?>
                        <div class="glass-card p-4 group-card" onclick="viewGroup(<?= $group['id'] ?>)">
                            <div style="display: flex; align-items: center; margin-bottom: 1rem;">
                                <?php if ($group['avatar']): ?>
                                    <img src="<?= htmlspecialchars($group['avatar']) ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 1rem; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--gradient-primary); margin-right: 1rem; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                        <?= strtoupper(substr($group['name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="flex: 1;">
                                    <h4 style="font-weight: 600; margin-bottom: 0.25rem;">
                                        <?= htmlspecialchars($group['name']) ?>
                                    </h4>
                                    <div style="font-size: 0.8rem; opacity: 0.7;">
                                        <?= $group['member_count'] ?> membres
                                    </div>
                                </div>
                                
                                <?php if ($group['unread_count'] > 0): ?>
                                    <div class="badge badge-error" style="font-size: 0.7rem;">
                                        <?= $group['unread_count'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="font-size: 0.8rem; opacity: 0.8;">
                                Dernier message : <?= Utils::timeAgo($group['last_message_at'] ?? $group['created_at']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="?filter=my_groups" class="glass-button glass-button-secondary">
                        Voir tous mes groupes
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filtres et recherche -->
        <div class="glassmorphism p-4 mb-6">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Rechercher un groupe..." 
                           class="glass-input" style="width: 100%; padding: 0.75rem 1rem;">
                </div>
                
                <select name="filter" class="glass-input" style="padding: 0.75rem 1rem;">
                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Tous les groupes</option>
                    <option value="my_groups" <?= $filter === 'my_groups' ? 'selected' : '' ?>>Mes groupes</option>
                    <?php if (Auth::hasRole('formateur')): ?>
                        <option value="moderated" <?= $filter === 'moderated' ? 'selected' : '' ?>>Que je modère</option>
                    <?php endif; ?>
                </select>
                
                <button type="submit" class="glass-button" style="padding: 0.75rem 1.5rem;">
                    Filtrer
                </button>
                
                <a href="/study-groups" class="glass-button glass-button-secondary" style="padding: 0.75rem 1rem;">
                    Réinitialiser
                </a>
            </form>
        </div>

        <!-- Grille des groupes -->
        <?php if (!empty($groups)): ?>
            <div class="grid grid-3 mb-8">
                <?php foreach ($groups as $group): ?>
                    <div class="glass-card p-6 group-card">
                        <!-- En-tête du groupe -->
                        <div style="display: flex; align-items: start; margin-bottom: 1rem;">
                            <?php if ($group['avatar']): ?>
                                <img src="<?= htmlspecialchars($group['avatar']) ?>" alt="Avatar" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 1rem; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--gradient-primary); margin-right: 1rem; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.25rem;">
                                    <?= strtoupper(substr($group['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div style="flex: 1;">
                                <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">
                                    <?= htmlspecialchars($group['name']) ?>
                                </h3>
                                
                                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; opacity: 0.7;">
                                    <span>👥 <?= $group['member_count'] ?> membres</span>
                                    
                                    <?php if ($group['is_private']): ?>
                                        <span class="badge" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                                            🔒 Privé
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-success">
                                            🌐 Public
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <?php if ($group['description']): ?>
                            <p style="opacity: 0.8; font-size: 0.9rem; margin-bottom: 1rem;">
                                <?= Utils::truncate(htmlspecialchars($group['description']), 120) ?>
                            </p>
                        <?php endif; ?>
                        
                        <!-- Cours associé -->
                        <?php if ($group['course_name']): ?>
                            <div style="background: rgba(var(--color-primary), 0.05); padding: 0.5rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.8rem;">
                                📚 <?= htmlspecialchars($group['course_name']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Modérateur -->
                        <div style="display: flex; align-items: center; margin-bottom: 1rem; font-size: 0.8rem;">
                            <span style="opacity: 0.7; margin-right: 0.5rem;">Modéré par:</span>
                            <span style="font-weight: 500;">
                                <?= htmlspecialchars($group['moderator_name']) ?>
                            </span>
                        </div>
                        
                        <!-- Statistiques d'activité -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; font-size: 0.8rem;">
                            <div style="text-align: center;">
                                <div style="font-weight: 600; color: rgb(var(--color-secondary));">
                                    <?= $group['message_count'] ?? 0 ?>
                                </div>
                                <div style="opacity: 0.7;">Messages</div>
                            </div>
                            
                            <div style="text-align: center;">
                                <div style="font-weight: 600; color: rgb(var(--color-accent));">
                                    <?= Utils::timeAgo($group['last_activity'] ?? $group['created_at']) ?>
                                </div>
                                <div style="opacity: 0.7;">Dernière activité</div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div style="display: flex; gap: 0.5rem;">
                            <?php if ($group['is_member']): ?>
                                <button onclick="viewGroup(<?= $group['id'] ?>)" class="glass-button" style="flex: 1; background: var(--gradient-primary); color: white; padding: 0.75rem;">
                                    💬 Accéder
                                    <?php if ($group['unread_count'] > 0): ?>
                                        <span class="badge badge-error" style="margin-left: 0.5rem; font-size: 0.7rem;">
                                            <?= $group['unread_count'] ?>
                                        </span>
                                    <?php endif; ?>
                                </button>
                                
                                <button onclick="leaveGroup(<?= $group['id'] ?>, '<?= htmlspecialchars($group['name']) ?>')" class="glass-button glass-button-secondary" style="padding: 0.75rem;" title="Quitter">
                                    🚪
                                </button>
                            <?php else: ?>
                                <?php if ($group['is_private']): ?>
                                    <button onclick="requestJoin(<?= $group['id'] ?>)" class="glass-button" style="flex: 1; padding: 0.75rem; background: rgba(var(--color-secondary), 0.1); color: rgb(var(--color-secondary));">
                                        🔑 Demander l'accès
                                    </button>
                                <?php else: ?>
                                    <button onclick="joinGroup(<?= $group['id'] ?>)" class="glass-button" style="flex: 1; background: var(--gradient-secondary); color: white; padding: 0.75rem;">
                                        ➕ Rejoindre
                                    </button>
                                <?php endif; ?>
                                
                                <button onclick="viewGroupPreview(<?= $group['id'] ?>)" class="glass-button glass-button-secondary" style="padding: 0.75rem;" title="Aperçu">
                                    👀
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- État vide -->
            <div class="glassmorphism p-8 text-center">
                <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;">👥</div>
                <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">
                    <?php if ($search || $filter !== 'all'): ?>
                        Aucun groupe trouvé
                    <?php else: ?>
                        Aucun groupe d'étude
                    <?php endif; ?>
                </h3>
                <p style="opacity: 0.8; margin-bottom: 2rem;">
                    <?php if ($search || $filter !== 'all'): ?>
                        Aucun groupe ne correspond à vos critères de recherche.
                    <?php else: ?>
                        Créez le premier groupe d'étude de votre établissement.
                    <?php endif; ?>
                </p>
                
                <?php if ($search || $filter !== 'all'): ?>
                    <a href="/study-groups" class="glass-button glass-button-secondary" style="margin-right: 1rem; padding: 1rem 2rem;">
                        Voir tous les groupes
                    </a>
                <?php endif; ?>
                
                <button onclick="createGroup()" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 1rem 2rem;">
                    Créer un groupe
                </button>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($meta['total_pages'] > 1): ?>
            <div style="display: flex; justify-content: center; margin-top: 2rem; gap: 0.5rem;">
                <?php for ($i = 1; $i <= $meta['total_pages']; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>" 
                       class="glass-button <?= $i == $meta['current_page'] ? 'active' : '' ?>" 
                       style="padding: 0.5rem 1rem; <?= $i == $meta['current_page'] ? 'background: var(--gradient-primary); color: white;' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function createGroup() {
    window.location.href = '/study-groups/create';
}

function viewGroup(groupId) {
    window.location.href = `/study-groups/${groupId}`;
}

function viewGroupPreview(groupId) {
    window.location.href = `/study-groups/${groupId}/preview`;
}

async function joinGroup(groupId) {
    try {
        const response = await apiRequest(`/api/study-groups/${groupId}/join`, 'POST');
        
        if (response.success) {
            showToast('Vous avez rejoint le groupe avec succès', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(response.error || 'Erreur lors de l\'adhésion', 'error');
        }
    } catch (error) {
        showToast('Erreur lors de l\'adhésion au groupe', 'error');
    }
}

async function leaveGroup(groupId, groupName) {
    if (confirm(`Êtes-vous sûr de vouloir quitter le groupe "${groupName}" ?`)) {
        try {
            const response = await apiRequest(`/api/study-groups/${groupId}/leave`, 'POST');
            
            if (response.success) {
                showToast('Vous avez quitté le groupe', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast(response.error || 'Erreur lors de la sortie du groupe', 'error');
            }
        } catch (error) {
            showToast('Erreur lors de la sortie du groupe', 'error');
        }
    }
}

async function requestJoin(groupId) {
    try {
        const response = await apiRequest(`/api/study-groups/${groupId}/request-join`, 'POST');
        
        if (response.success) {
            showToast('Demande d\'accès envoyée', 'success');
        } else {
            showToast(response.error || 'Erreur lors de la demande', 'error');
        }
    } catch (error) {
        showToast('Erreur lors de la demande d\'accès', 'error');
    }
}

// Animation des cartes
document.querySelectorAll('.group-card').forEach(card => {
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
.group-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.group-card:hover {
    transform: translateY(-3px);
}

@media (max-width: 768px) {
    .grid-3, .grid-4 {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
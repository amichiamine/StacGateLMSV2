<?php
/**
 * Page gestion des utilisateurs
 */

// Vérifier l'authentification et les permissions
Auth::requireAuth();

if (!Auth::hasRole('manager')) {
    http_response_code(403);
    Utils::redirectWithMessage('/dashboard', 'Accès non autorisé', 'error');
    exit;
}

$pageTitle = "Gestion des utilisateurs - StacGateLMS";
$pageDescription = "Administration des comptes utilisateurs, rôles et permissions.";

$currentUser = Auth::user();
$establishmentId = Auth::hasRole('super_admin') ? null : $currentUser['establishment_id'];

// Initialiser les services
$authService = new AuthService();

// Paramètres
$page = intval($_GET['page'] ?? 1);
$perPage = 15;
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

// Traitement des actions
$action = $_POST['action'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['_token'] ?? '')) {
    try {
        switch ($action) {
            case 'create_user':
                $userData = [
                    'first_name' => $_POST['first_name'],
                    'last_name' => $_POST['last_name'],
                    'email' => $_POST['email'],
                    'password' => $_POST['password'],
                    'role' => $_POST['role'],
                    'establishment_id' => $establishmentId ?: $_POST['establishment_id']
                ];
                
                $newUser = $authService->createUser($userData);
                $message = ['type' => 'success', 'text' => 'Utilisateur créé avec succès'];
                break;
                
            case 'update_user':
                $userId = intval($_POST['user_id']);
                $updateData = [
                    'first_name' => $_POST['first_name'],
                    'last_name' => $_POST['last_name'],
                    'email' => $_POST['email'],
                    'role' => $_POST['role']
                ];
                
                if (!empty($_POST['password'])) {
                    $updateData['password'] = Auth::hashPassword($_POST['password']);
                }
                
                $authService->updateUser($userId, $updateData);
                $message = ['type' => 'success', 'text' => 'Utilisateur mis à jour avec succès'];
                break;
                
            case 'toggle_status':
                $userId = intval($_POST['user_id']);
                $newStatus = $_POST['status'] === 'active';
                
                $authService->updateUser($userId, ['is_active' => $newStatus]);
                $message = ['type' => 'success', 'text' => 'Statut mis à jour avec succès'];
                break;
                
            case 'delete_user':
                $userId = intval($_POST['user_id']);
                $authService->deleteUser($userId);
                $message = ['type' => 'success', 'text' => 'Utilisateur supprimé avec succès'];
                break;
        }
    } catch (Exception $e) {
        $message = ['type' => 'error', 'text' => $e->getMessage()];
        Utils::log("User management error: " . $e->getMessage(), 'ERROR');
    }
}

// Obtenir les données
try {
    if (Auth::hasRole('super_admin')) {
        $usersData = $authService->getAllUsers($page, $perPage, $search, $role);
    } else {
        $usersData = $authService->getUsersByEstablishment($establishmentId, $page, $perPage, $search);
    }
    
    $users = $usersData['data'];
    $meta = $usersData['meta'];
    
    // Statistiques utilisateurs
    $userStats = $authService->getUserStats($establishmentId);
    
} catch (Exception $e) {
    Utils::log("User management page error: " . $e->getMessage(), 'ERROR');
    $users = [];
    $meta = ['total' => 0, 'current_page' => 1, 'total_pages' => 1];
    $userStats = [];
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
                        Gestion des utilisateurs
                    </h1>
                    <p style="opacity: 0.8;">
                        Administration des comptes et permissions
                        <?php if ($establishmentId): ?>
                            - <?= htmlspecialchars($currentUser['establishment_name']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <button onclick="openCreateModal()" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 0.75rem 1.5rem;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" style="margin-right: 0.5rem;">
                        <path d="M12 4v16m8-8H4"/>
                    </svg>
                    Nouvel utilisateur
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

        <!-- Statistiques utilisateurs -->
        <div class="grid grid-4 mb-8">
            <div class="glass-card p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.5rem;">
                    <?= $userStats['total'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Total utilisateurs</div>
            </div>
            
            <div class="glass-card p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-secondary)); margin-bottom: 0.5rem;">
                    <?= $userStats['active'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Utilisateurs actifs</div>
            </div>
            
            <div class="glass-card p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-accent)); margin-bottom: 0.5rem;">
                    <?= $userStats['this_month'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Nouveaux ce mois</div>
            </div>
            
            <div class="glass-card p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary)); margin-bottom: 0.5rem;">
                    <?= $userStats['instructors'] ?? 0 ?>
                </div>
                <div style="opacity: 0.8; font-size: 0.9rem;">Formateurs</div>
            </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="glassmorphism p-4 mb-6">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Rechercher un utilisateur..." 
                           class="glass-input" style="width: 100%; padding: 0.75rem 1rem;">
                </div>
                
                <select name="role" class="glass-input" style="padding: 0.75rem 1rem;">
                    <option value="">Tous les rôles</option>
                    <option value="apprenant" <?= $role === 'apprenant' ? 'selected' : '' ?>>Apprenant</option>
                    <option value="formateur" <?= $role === 'formateur' ? 'selected' : '' ?>>Formateur</option>
                    <option value="manager" <?= $role === 'manager' ? 'selected' : '' ?>>Manager</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <?php if (Auth::hasRole('super_admin')): ?>
                        <option value="super_admin" <?= $role === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                    <?php endif; ?>
                </select>
                
                <select name="status" class="glass-input" style="padding: 0.75rem 1rem;">
                    <option value="">Tous les statuts</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Actif</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactif</option>
                </select>
                
                <button type="submit" class="glass-button" style="padding: 0.75rem 1.5rem;">
                    Filtrer
                </button>
                
                <a href="/user-management" class="glass-button glass-button-secondary" style="padding: 0.75rem 1rem;">
                    Réinitialiser
                </a>
            </form>
        </div>

        <!-- Liste des utilisateurs -->
        <div class="glassmorphism">
            <?php if (!empty($users)): ?>
                <!-- En-têtes du tableau -->
                <div style="display: grid; grid-template-columns: 1fr 2fr 1.5fr 1fr 1fr 150px; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); font-weight: 600; background: rgba(var(--color-primary), 0.05);">
                    <div>Avatar</div>
                    <div>Nom complet</div>
                    <div>Email</div>
                    <div>Rôle</div>
                    <div>Statut</div>
                    <div>Actions</div>
                </div>
                
                <!-- Lignes utilisateurs -->
                <?php foreach ($users as $user): ?>
                    <div style="display: grid; grid-template-columns: 1fr 2fr 1.5fr 1fr 1fr 150px; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); align-items: center;" data-user-id="<?= $user['id'] ?>">
                        <!-- Avatar -->
                        <div style="display: flex; align-items: center;">
                            <?php if ($user['avatar']): ?>
                                <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--gradient-primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.9rem;">
                                    <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Nom complet -->
                        <div>
                            <div style="font-weight: 600; margin-bottom: 0.25rem;">
                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                            </div>
                            <div style="font-size: 0.8rem; opacity: 0.7;">
                                @<?= htmlspecialchars($user['username'] ?? 'N/A') ?>
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div style="font-size: 0.9rem;">
                            <?= htmlspecialchars($user['email']) ?>
                        </div>
                        
                        <!-- Rôle -->
                        <div>
                            <span class="badge" style="
                                background: <?= $user['role'] === 'super_admin' ? 'rgba(220, 38, 127, 0.1)' : 
                                                ($user['role'] === 'admin' ? 'rgba(239, 68, 68, 0.1)' : 
                                                ($user['role'] === 'manager' ? 'rgba(245, 158, 11, 0.1)' : 
                                                ($user['role'] === 'formateur' ? 'rgba(34, 197, 94, 0.1)' : 'rgba(99, 102, 241, 0.1)'))) ?>;
                                color: <?= $user['role'] === 'super_admin' ? '#dc2677' : 
                                            ($user['role'] === 'admin' ? '#ef4444' : 
                                            ($user['role'] === 'manager' ? '#f59e0b' : 
                                            ($user['role'] === 'formateur' ? '#22c55e' : '#6366f1'))) ?>;
                                font-size: 0.75rem;
                                font-weight: 500;
                            ">
                                <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                            </span>
                        </div>
                        
                        <!-- Statut -->
                        <div>
                            <span class="badge <?= $user['is_active'] ? 'badge-success' : 'badge-error' ?>" style="font-size: 0.75rem;">
                                <?= $user['is_active'] ? 'Actif' : 'Inactif' ?>
                            </span>
                        </div>
                        
                        <!-- Actions -->
                        <div style="display: flex; gap: 0.25rem;">
                            <button onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)" 
                                    class="glass-button glass-button-secondary" 
                                    style="padding: 0.5rem; font-size: 0.8rem;" 
                                    title="Modifier">
                                ✏️
                            </button>
                            
                            <?php if ($user['id'] != $currentUser['id']): ?>
                                <button onclick="toggleUserStatus(<?= $user['id'] ?>, '<?= $user['is_active'] ? 'inactive' : 'active' ?>')" 
                                        class="glass-button glass-button-secondary" 
                                        style="padding: 0.5rem; font-size: 0.8rem;" 
                                        title="<?= $user['is_active'] ? 'Désactiver' : 'Activer' ?>">
                                    <?= $user['is_active'] ? '🔒' : '🔓' ?>
                                </button>
                                
                                <?php if (Auth::hasRole('admin')): ?>
                                    <button onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>')" 
                                            class="glass-button" 
                                            style="padding: 0.5rem; font-size: 0.8rem; background: rgba(239, 68, 68, 0.1); color: #ef4444;" 
                                            title="Supprimer">
                                        🗑️
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- État vide -->
                <div style="padding: 4rem 2rem; text-center;">
                    <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;">👥</div>
                    <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">
                        Aucun utilisateur trouvé
                    </h3>
                    <p style="opacity: 0.8; margin-bottom: 2rem;">
                        <?php if ($search || $role || $status): ?>
                            Aucun utilisateur ne correspond à vos critères de recherche.
                        <?php else: ?>
                            Commencez par créer votre premier utilisateur.
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($search || $role || $status): ?>
                        <a href="/user-management" class="glass-button glass-button-secondary" style="margin-right: 1rem; padding: 1rem 2rem;">
                            Voir tous les utilisateurs
                        </a>
                    <?php endif; ?>
                    
                    <button onclick="openCreateModal()" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 1rem 2rem;">
                        Créer un utilisateur
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($meta['total_pages'] > 1): ?>
            <div style="display: flex; justify-content: center; margin-top: 2rem; gap: 0.5rem;">
                <?php for ($i = 1; $i <= $meta['total_pages']; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role) ?>&status=<?= urlencode($status) ?>" 
                       class="glass-button <?= $i == $meta['current_page'] ? 'active' : '' ?>" 
                       style="padding: 0.5rem 1rem; <?= $i == $meta['current_page'] ? 'background: var(--gradient-primary); color: white;' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal création/édition -->
<div id="userModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
    <div class="glassmorphism" style="width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 id="modalTitle" style="font-size: 1.5rem; font-weight: 600;">Nouvel utilisateur</h2>
                <button onclick="closeUserModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; opacity: 0.7;">&times;</button>
            </div>
            
            <form id="userForm" method="POST">
                <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" id="formAction" name="action" value="create_user">
                <input type="hidden" id="userId" name="user_id">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label class="form-label">Prénom *</label>
                        <input type="text" id="firstName" name="first_name" required class="glass-input" style="width: 100%; padding: 0.75rem 1rem; margin-top: 0.25rem;">
                    </div>
                    <div>
                        <label class="form-label">Nom *</label>
                        <input type="text" id="lastName" name="last_name" required class="glass-input" style="width: 100%; padding: 0.75rem 1rem; margin-top: 0.25rem;">
                    </div>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Email *</label>
                    <input type="email" id="email" name="email" required class="glass-input" style="width: 100%; padding: 0.75rem 1rem; margin-top: 0.25rem;">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Mot de passe <span id="passwordRequired">*</span></label>
                    <input type="password" id="password" name="password" class="glass-input" style="width: 100%; padding: 0.75rem 1rem; margin-top: 0.25rem;">
                    <div style="font-size: 0.8rem; opacity: 0.7; margin-top: 0.25rem;" id="passwordHelp">
                        Laissez vide pour conserver le mot de passe actuel
                    </div>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Rôle *</label>
                    <select id="role" name="role" required class="glass-input" style="width: 100%; padding: 0.75rem 1rem; margin-top: 0.25rem;">
                        <option value="">Sélectionner un rôle</option>
                        <option value="apprenant">Apprenant</option>
                        <option value="formateur">Formateur</option>
                        <?php if (Auth::hasRole('admin')): ?>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        <?php endif; ?>
                        <?php if (Auth::hasRole('super_admin')): ?>
                            <option value="super_admin">Super Admin</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <?php if (Auth::hasRole('super_admin')): ?>
                    <div style="margin-bottom: 1rem;" id="establishmentField">
                        <label class="form-label">Établissement *</label>
                        <select id="establishment" name="establishment_id" class="glass-input" style="width: 100%; padding: 0.75rem 1rem; margin-top: 0.25rem;">
                            <option value="">Sélectionner un établissement</option>
                            <!-- Les options seront chargées via JavaScript -->
                        </select>
                    </div>
                <?php endif; ?>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeUserModal()" class="glass-button glass-button-secondary" style="padding: 0.75rem 1.5rem;">
                        Annuler
                    </button>
                    <button type="submit" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 0.75rem 1.5rem;">
                        <span id="submitText">Créer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let editingUser = null;

function openCreateModal() {
    editingUser = null;
    document.getElementById('modalTitle').textContent = 'Nouvel utilisateur';
    document.getElementById('formAction').value = 'create_user';
    document.getElementById('submitText').textContent = 'Créer';
    document.getElementById('passwordRequired').style.display = 'inline';
    document.getElementById('password').required = true;
    document.getElementById('passwordHelp').style.display = 'none';
    document.getElementById('userForm').reset();
    document.getElementById('userModal').style.display = 'flex';
    
    <?php if (Auth::hasRole('super_admin')): ?>
        loadEstablishments();
    <?php endif; ?>
}

function editUser(user) {
    editingUser = user;
    document.getElementById('modalTitle').textContent = 'Modifier l\'utilisateur';
    document.getElementById('formAction').value = 'update_user';
    document.getElementById('userId').value = user.id;
    document.getElementById('submitText').textContent = 'Modifier';
    document.getElementById('passwordRequired').style.display = 'none';
    document.getElementById('password').required = false;
    document.getElementById('passwordHelp').style.display = 'block';
    
    // Pré-remplir les champs
    document.getElementById('firstName').value = user.first_name;
    document.getElementById('lastName').value = user.last_name;
    document.getElementById('email').value = user.email;
    document.getElementById('role').value = user.role;
    
    <?php if (Auth::hasRole('super_admin')): ?>
        loadEstablishments(user.establishment_id);
    <?php endif; ?>
    
    document.getElementById('userModal').style.display = 'flex';
}

function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
    editingUser = null;
}

async function toggleUserStatus(userId, newStatus) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="user_id" value="${userId}">
        <input type="hidden" name="status" value="${newStatus}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function deleteUser(userId, userName) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${userName}" ?\n\nCette action est irréversible.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

<?php if (Auth::hasRole('super_admin')): ?>
async function loadEstablishments(selectedId = null) {
    try {
        const response = await apiRequest('/api/establishments');
        const select = document.getElementById('establishment');
        select.innerHTML = '<option value="">Sélectionner un établissement</option>';
        
        if (response.success) {
            response.data.forEach(est => {
                const option = document.createElement('option');
                option.value = est.id;
                option.textContent = est.name;
                if (selectedId && est.id == selectedId) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Erreur chargement établissements:', error);
    }
}
<?php endif; ?>

// Fermer le modal en cliquant en dehors
document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeUserModal();
    }
});
</script>

<style>
.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

@media (max-width: 768px) {
    .glassmorphism > div[style*="grid-template-columns"] {
        display: block !important;
        padding: 1rem !important;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .glassmorphism > div[style*="grid-template-columns"] > div {
        margin-bottom: 0.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .glassmorphism > div[style*="grid-template-columns"] > div:first-child {
        justify-content: flex-start;
    }
    
    .glassmorphism > div[style*="grid-template-columns"]:first-child {
        display: none !important;
    }
    
    #userModal > div {
        width: 95% !important;
        margin: 1rem;
    }
    
    #userModal form > div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
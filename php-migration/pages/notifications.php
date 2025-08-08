<?php
/**
 * Page centre de notifications
 */

// Vérifier l'authentification
Auth::requireAuth();

$pageTitle = "Notifications - StacGateLMS";
$pageDescription = "Centre de notifications et alertes.";

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

// Initialiser les services
$notificationService = new NotificationService();

// Obtenir les notifications
try {
    $page = intval($_GET['page'] ?? 1);
    $filter = $_GET['filter'] ?? 'all'; // all, unread, read, important
    
    $filters = [];
    if ($filter === 'unread') $filters['read'] = false;
    if ($filter === 'read') $filters['read'] = true;
    if ($filter === 'important') $filters['important'] = true;
    
    $notifications = $notificationService->getUserNotifications($currentUser['id'], $page, 20, $filters);
    $unreadCount = $notificationService->getUnreadCount($currentUser['id']);
    
} catch (Exception $e) {
    Utils::log("Notifications page error: " . $e->getMessage(), 'ERROR');
    $notifications = ['data' => [], 'pagination' => []];
    $unreadCount = 0;
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
                        Notifications
                    </h1>
                    <p style="opacity: 0.8;">
                        <?= $unreadCount ?> notification<?= $unreadCount > 1 ? 's' : '' ?> non lue<?= $unreadCount > 1 ? 's' : '' ?>
                    </p>
                </div>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button onclick="markAllAsRead()" class="glass-button glass-button-secondary">
                        Tout marquer comme lu
                    </button>
                    <button onclick="clearNotifications()" class="glass-button glass-button-secondary">
                        Vider les notifications
                    </button>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="glassmorphism p-4 mb-6">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                    Toutes
                </a>
                <a href="?filter=unread" class="filter-btn <?= $filter === 'unread' ? 'active' : '' ?>">
                    Non lues (<?= $unreadCount ?>)
                </a>
                <a href="?filter=read" class="filter-btn <?= $filter === 'read' ? 'active' : '' ?>">
                    Lues
                </a>
                <a href="?filter=important" class="filter-btn <?= $filter === 'important' ? 'active' : '' ?>">
                    Importantes
                </a>
            </div>
        </div>

        <!-- Liste des notifications -->
        <div class="notifications-list">
            <?php if (empty($notifications['data'])): ?>
                <div class="glassmorphism p-8 text-center">
                    <svg width="64" height="64" fill="currentColor" viewBox="0 0 24 24" style="opacity: 0.5; margin-bottom: 1rem;">
                        <path d="M12 2A10 10 0 0 0 2 12a10 10 0 0 0 10 10a10 10 0 0 0 10-10A10 10 0 0 0 12 2z"/>
                    </svg>
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">Aucune notification</h3>
                    <p style="opacity: 0.7;">Vous n'avez aucune notification pour le moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications['data'] as $notification): ?>
                    <div class="glass-card notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>" 
                         data-id="<?= $notification['id'] ?>" 
                         style="margin-bottom: 1rem; padding: 1.5rem; cursor: pointer;">
                        
                        <div style="display: flex; gap: 1rem;">
                            <!-- Icône de type -->
                            <div class="notification-icon" style="flex-shrink: 0;">
                                <?php 
                                $iconColor = 'rgb(var(--color-primary))';
                                switch ($notification['type']) {
                                    case 'course_enrollment':
                                        $icon = '<path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>';
                                        break;
                                    case 'assignment_due':
                                        $icon = '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>';
                                        $iconColor = 'rgb(var(--color-accent))';
                                        break;
                                    case 'message':
                                        $icon = '<path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>';
                                        break;
                                    case 'system':
                                        $icon = '<path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>';
                                        $iconColor = 'rgb(var(--color-secondary))';
                                        break;
                                    default:
                                        $icon = '<path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
                                }
                                ?>
                                <svg width="24" height="24" fill="none" stroke="<?= $iconColor ?>" viewBox="0 0 24 24" stroke-width="2">
                                    <?= $icon ?>
                                </svg>
                            </div>
                            
                            <!-- Contenu -->
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                    <h3 style="font-weight: 600; margin: 0;">
                                        <?= htmlspecialchars($notification['title']) ?>
                                    </h3>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                                        <?php if ($notification['is_important']): ?>
                                            <svg width="16" height="16" fill="rgb(var(--color-accent))" viewBox="0 0 24 24">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                            </svg>
                                        <?php endif; ?>
                                        <small style="opacity: 0.7; white-space: nowrap;">
                                            <?= Utils::timeAgo($notification['created_at']) ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <p style="margin: 0; opacity: 0.8; line-height: 1.5;">
                                    <?= htmlspecialchars($notification['message']) ?>
                                </p>
                                
                                <?php if ($notification['action_url']): ?>
                                    <div style="margin-top: 1rem;">
                                        <a href="<?= htmlspecialchars($notification['action_url']) ?>" 
                                           class="glass-button glass-button-secondary" 
                                           style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                            <?= htmlspecialchars($notification['action_text'] ?? 'Voir') ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Indicateur non lu -->
                            <?php if (!$notification['is_read']): ?>
                                <div class="unread-indicator" style="width: 8px; height: 8px; background: rgb(var(--color-primary)); border-radius: 50%; flex-shrink: 0;"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($notifications['pagination']['total_pages'] > 1): ?>
            <div class="glassmorphism p-4 mt-6">
                <div style="display: flex; justify-content: center; gap: 1rem;">
                    <?php if ($notifications['pagination']['has_prev']): ?>
                        <a href="?page=<?= $page - 1 ?>&filter=<?= $filter ?>" class="glass-button glass-button-secondary">
                            Précédent
                        </a>
                    <?php endif; ?>
                    
                    <span style="display: flex; align-items: center; padding: 0 1rem;">
                        Page <?= $notifications['pagination']['current_page'] ?> sur <?= $notifications['pagination']['total_pages'] ?>
                    </span>
                    
                    <?php if ($notifications['pagination']['has_next']): ?>
                        <a href="?page=<?= $page + 1 ?>&filter=<?= $filter ?>" class="glass-button glass-button-secondary">
                            Suivant
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.filter-btn {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    text-decoration: none;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.filter-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.filter-btn.active {
    background: var(--gradient-primary);
    color: white;
    border-color: transparent;
}

.notification-item.unread {
    border-left: 4px solid rgb(var(--color-primary));
    background: rgba(var(--color-primary), 0.05);
}

.notification-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}
</style>

<script>
// Marquer une notification comme lue au clic
document.querySelectorAll('.notification-item').forEach(item => {
    item.addEventListener('click', function() {
        const notificationId = this.dataset.id;
        if (this.classList.contains('unread')) {
            markAsRead(notificationId);
        }
    });
});

// Marquer comme lue
async function markAsRead(notificationId) {
    try {
        await apiRequest(`/api/notifications/${notificationId}/read`, 'POST', {
            _token: '<?= generateCSRFToken() ?>'
        });
        
        const item = document.querySelector(`[data-id="${notificationId}"]`);
        if (item) {
            item.classList.remove('unread');
            const indicator = item.querySelector('.unread-indicator');
            if (indicator) indicator.remove();
        }
    } catch (error) {
        console.error('Erreur lors du marquage de la notification');
    }
}

// Marquer toutes comme lues
async function markAllAsRead() {
    try {
        const response = await apiRequest('/api/notifications/mark-all-read', 'POST', {
            _token: '<?= generateCSRFToken() ?>'
        });
        
        if (response.error) {
            showToast(response.error, 'error');
        } else {
            showToast('Toutes les notifications ont été marquées comme lues', 'success');
            location.reload();
        }
    } catch (error) {
        showToast('Erreur lors du marquage des notifications', 'error');
    }
}

// Vider les notifications
async function clearNotifications() {
    if (!confirm('Êtes-vous sûr de vouloir supprimer toutes les notifications ?')) {
        return;
    }
    
    try {
        const response = await apiRequest('/api/notifications/clear', 'DELETE', {
            _token: '<?= generateCSRFToken() ?>'
        });
        
        if (response.error) {
            showToast(response.error, 'error');
        } else {
            showToast('Notifications supprimées avec succès', 'success');
            location.reload();
        }
    } catch (error) {
        showToast('Erreur lors de la suppression des notifications', 'error');
    }
}
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
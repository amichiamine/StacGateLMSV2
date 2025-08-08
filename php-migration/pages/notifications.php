<?php
/**
 * Page Notifications
 * Centre de notifications et messages
 */

// Vérifier l'authentification
Auth::requireAuth();

$pageTitle = "Notifications - StacGateLMS";
$pageDescription = "Centre de notifications et messages de la plateforme.";

$currentUser = Auth::user();

// Initialiser les services
$notificationService = new NotificationService();

// Paramètres
$page = intval($_GET['page'] ?? 1);
$perPage = 20;
$filter = $_GET['filter'] ?? 'all'; // all, unread, read, system, user
$category = $_GET['category'] ?? '';

// Traitement des actions
$action = $_POST['action'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['_token'] ?? '')) {
    try {
        switch ($action) {
            case 'mark_read':
                $notificationId = intval($_POST['notification_id']);
                $notificationService->markAsRead($notificationId, $currentUser['id']);
                $message = ['type' => 'success', 'text' => 'Notification marquée comme lue'];
                break;
                
            case 'mark_unread':
                $notificationId = intval($_POST['notification_id']);
                $notificationService->markAsUnread($notificationId, $currentUser['id']);
                $message = ['type' => 'success', 'text' => 'Notification marquée comme non lue'];
                break;
                
            case 'mark_all_read':
                $notificationService->markAllAsRead($currentUser['id']);
                $message = ['type' => 'success', 'text' => 'Toutes les notifications marquées comme lues'];
                break;
                
            case 'delete_notification':
                $notificationId = intval($_POST['notification_id']);
                $notificationService->deleteNotification($notificationId, $currentUser['id']);
                $message = ['type' => 'success', 'text' => 'Notification supprimée'];
                break;
                
            case 'delete_all_read':
                $notificationService->deleteAllRead($currentUser['id']);
                $message = ['type' => 'success', 'text' => 'Toutes les notifications lues supprimées'];
                break;
        }
    } catch (Exception $e) {
        $message = ['type' => 'error', 'text' => $e->getMessage()];
        Utils::log("Notifications error: " . $e->getMessage(), 'ERROR');
    }
}

// Obtenir les données
try {
    $notificationsData = $notificationService->getUserNotifications($currentUser['id'], $page, $perPage, $filter, $category);
    $notifications = $notificationsData['data'];
    $meta = $notificationsData['meta'];
    
    // Statistiques
    $stats = $notificationService->getNotificationStats($currentUser['id']);
    
} catch (Exception $e) {
    Utils::log("Notifications page error: " . $e->getMessage(), 'ERROR');
    $notifications = [];
    $meta = ['total' => 0, 'current_page' => 1, 'total_pages' => 1];
    $stats = ['total' => 0, 'unread' => 0, 'today' => 0];
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
                        🔔 Notifications
                    </h1>
                    <p style="opacity: 0.8;">
                        Centre de messages et notifications
                    </p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <?php if ($stats['unread'] > 0): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="mark_all_read">
                            <button type="submit" class="glass-button">
                                ✓ Tout marquer lu
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="delete_all_read">
                        <button type="submit" class="glass-button glass-button-secondary" 
                                onclick="return confirm('Supprimer toutes les notifications lues ?')">
                            🗑️ Vider lues
                        </button>
                    </form>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?= $message['type'] ?>" style="margin-top: 1rem; padding: 1rem; border-radius: 8px; background: rgba(<?= $message['type'] === 'success' ? '34, 197, 94' : '239, 68, 68' ?>, 0.1); color: <?= $message['type'] === 'success' ? '#22c55e' : '#ef4444' ?>;">
                    <?= htmlspecialchars($message['text']) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-3 mb-8">
            <div class="glassmorphism p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary));">
                    <?= $stats['total'] ?>
                </div>
                <div style="opacity: 0.8;">Total</div>
            </div>
            <div class="glassmorphism p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-warning));">
                    <?= $stats['unread'] ?>
                </div>
                <div style="opacity: 0.8;">Non lues</div>
            </div>
            <div class="glassmorphism p-4 text-center">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-info));">
                    <?= $stats['today'] ?>
                </div>
                <div style="opacity: 0.8;">Aujourd'hui</div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="glassmorphism p-4 mb-6">
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <div style="display: flex; gap: 0.5rem;">
                    <a href="?filter=all" 
                       class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>"
                       style="padding: 0.5rem 1rem; border-radius: 20px; text-decoration: none; transition: all 0.3s; <?= $filter === 'all' ? 'background: rgba(var(--color-primary), 0.1); color: rgb(var(--color-primary));' : 'background: rgba(255,255,255,0.05);' ?>">
                        Toutes (<?= $stats['total'] ?>)
                    </a>
                    <a href="?filter=unread" 
                       class="filter-btn <?= $filter === 'unread' ? 'active' : '' ?>"
                       style="padding: 0.5rem 1rem; border-radius: 20px; text-decoration: none; transition: all 0.3s; <?= $filter === 'unread' ? 'background: rgba(var(--color-warning), 0.1); color: rgb(var(--color-warning));' : 'background: rgba(255,255,255,0.05);' ?>">
                        Non lues (<?= $stats['unread'] ?>)
                    </a>
                    <a href="?filter=read" 
                       class="filter-btn <?= $filter === 'read' ? 'active' : '' ?>"
                       style="padding: 0.5rem 1rem; border-radius: 20px; text-decoration: none; transition: all 0.3s; <?= $filter === 'read' ? 'background: rgba(var(--color-success), 0.1); color: rgb(var(--color-success));' : 'background: rgba(255,255,255,0.05);' ?>">
                        Lues
                    </a>
                </div>
                
                <div style="margin-left: auto;">
                    <form method="GET" style="display: flex; gap: 1rem;">
                        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                        <select name="category" onchange="this.form.submit()" class="glass-input" style="padding: 0.5rem 1rem;">
                            <option value="">Toutes catégories</option>
                            <option value="system" <?= $category === 'system' ? 'selected' : '' ?>>Système</option>
                            <option value="course" <?= $category === 'course' ? 'selected' : '' ?>>Cours</option>
                            <option value="assessment" <?= $category === 'assessment' ? 'selected' : '' ?>>Évaluations</option>
                            <option value="social" <?= $category === 'social' ? 'selected' : '' ?>>Social</option>
                            <option value="achievement" <?= $category === 'achievement' ? 'selected' : '' ?>>Succès</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <!-- Liste des notifications -->
        <div class="glassmorphism">
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?= !$notification['is_read'] ? 'unread' : 'read' ?>" 
                         data-notification-id="<?= $notification['id'] ?>"
                         style="display: flex; align-items: flex-start; gap: 1rem; padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); <?= !$notification['is_read'] ? 'background: rgba(var(--color-primary), 0.02);' : '' ?>">
                        
                        <!-- Icône de statut -->
                        <div class="notification-status" style="margin-top: 0.25rem;">
                            <?php if (!$notification['is_read']): ?>
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: rgb(var(--color-primary));"></div>
                            <?php else: ?>
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.2);"></div>
                            <?php endif; ?>
                        </div>

                        <!-- Icône de catégorie -->
                        <div class="notification-icon" style="font-size: 1.5rem; margin-top: 0.25rem;">
                            <?php
                            $icons = [
                                'system' => '⚙️',
                                'course' => '📚',
                                'assessment' => '📝',
                                'social' => '👥',
                                'achievement' => '🏆',
                                'message' => '💬',
                                'warning' => '⚠️',
                                'info' => 'ℹ️'
                            ];
                            echo $icons[$notification['category']] ?? '🔔';
                            ?>
                        </div>

                        <!-- Contenu -->
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <h3 style="font-size: 1.1rem; font-weight: 600; margin: 0; <?= !$notification['is_read'] ? 'color: rgb(var(--color-primary));' : '' ?>">
                                    <?= htmlspecialchars($notification['title']) ?>
                                </h3>
                                <div style="display: flex; gap: 0.5rem; margin-left: 1rem;">
                                    <span style="font-size: 0.8rem; opacity: 0.7; white-space: nowrap;">
                                        <?= Utils::timeAgo($notification['created_at']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <p style="margin: 0 0 1rem 0; opacity: 0.8; line-height: 1.5;">
                                <?= htmlspecialchars($notification['message']) ?>
                            </p>
                            
                            <!-- Métadonnées -->
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    <span class="notification-category" style="font-size: 0.8rem; padding: 0.25rem 0.75rem; background: rgba(var(--color-<?= $notification['category'] === 'system' ? 'info' : ($notification['category'] === 'warning' ? 'warning' : 'primary') ?>), 0.1); color: rgb(var(--color-<?= $notification['category'] === 'system' ? 'info' : ($notification['category'] === 'warning' ? 'warning' : 'primary') ?>)); border-radius: 12px;">
                                        <?= ucfirst($notification['category']) ?>
                                    </span>
                                    
                                    <?php if (!empty($notification['action_url'])): ?>
                                        <a href="<?= htmlspecialchars($notification['action_url']) ?>" 
                                           class="glass-button glass-button-secondary" 
                                           style="padding: 0.25rem 0.75rem; font-size: 0.8rem;">
                                            Voir
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Actions -->
                                <div style="display: flex; gap: 0.5rem;">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                        <input type="hidden" name="action" value="<?= $notification['is_read'] ? 'mark_unread' : 'mark_read' ?>">
                                        <button type="submit" 
                                                class="action-btn" 
                                                style="background: none; border: none; color: inherit; opacity: 0.6; cursor: pointer; padding: 0.25rem;" 
                                                title="<?= $notification['is_read'] ? 'Marquer comme non lu' : 'Marquer comme lu' ?>">
                                            <?= $notification['is_read'] ? '👁️' : '✓' ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                        <input type="hidden" name="action" value="delete_notification">
                                        <button type="submit" 
                                                class="action-btn" 
                                                style="background: none; border: none; color: inherit; opacity: 0.6; cursor: pointer; padding: 0.25rem;" 
                                                title="Supprimer"
                                                onclick="return confirm('Supprimer cette notification ?')">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <?php if ($meta['total_pages'] > 1): ?>
                    <div style="padding: 2rem; text-align: center;">
                        <?php for ($i = 1; $i <= $meta['total_pages']; $i++): ?>
                            <a href="?page=<?= $i ?>&filter=<?= htmlspecialchars($filter) ?>&category=<?= htmlspecialchars($category) ?>" 
                               class="pagination-btn <?= $i === $meta['current_page'] ? 'active' : '' ?>"
                               style="display: inline-block; padding: 0.5rem 1rem; margin: 0 0.25rem; border-radius: 6px; text-decoration: none; transition: all 0.3s; <?= $i === $meta['current_page'] ? 'background: rgba(var(--color-primary), 0.1); color: rgb(var(--color-primary));' : 'background: rgba(255,255,255,0.05);' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- État vide -->
                <div style="padding: 4rem 2rem; text-align: center;">
                    <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;">
                        <?= $filter === 'unread' ? '📭' : '📫' ?>
                    </div>
                    <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">
                        <?php if ($filter === 'unread'): ?>
                            Aucune notification non lue
                        <?php elseif ($filter === 'read'): ?>
                            Aucune notification lue
                        <?php else: ?>
                            Aucune notification
                        <?php endif; ?>
                    </h3>
                    <p style="opacity: 0.8;">
                        <?php if ($filter === 'unread'): ?>
                            Toutes vos notifications sont à jour !
                        <?php else: ?>
                            Vous recevrez ici vos notifications et messages.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Marquer comme lu au clic
document.querySelectorAll('.notification-item.unread').forEach(item => {
    item.addEventListener('click', function(e) {
        // Ne pas déclencher si on clique sur un bouton d'action
        if (e.target.closest('.action-btn') || e.target.closest('form') || e.target.closest('a')) {
            return;
        }
        
        const notificationId = this.dataset.notificationId;
        markAsRead(notificationId);
    });
});

async function markAsRead(notificationId) {
    try {
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        formData.append('action', 'mark_read');
        formData.append('notification_id', notificationId);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            // Mettre à jour visuellement
            const item = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (item) {
                item.classList.remove('unread');
                item.classList.add('read');
                item.style.background = '';
                
                // Mettre à jour l'indicateur de statut
                const statusDot = item.querySelector('.notification-status div');
                if (statusDot) {
                    statusDot.style.background = 'rgba(255,255,255,0.2)';
                }
            }
            
            // Mettre à jour le compteur dans la navigation si présent
            updateNotificationCounter();
        }
    } catch (error) {
        console.error('Erreur marquage lecture:', error);
    }
}

function updateNotificationCounter() {
    // Mettre à jour le compteur de notifications non lues
    const unreadItems = document.querySelectorAll('.notification-item.unread');
    const counter = document.querySelector('.notification-counter');
    if (counter) {
        const count = unreadItems.length;
        counter.textContent = count;
        counter.style.display = count > 0 ? 'block' : 'none';
    }
}

// Animation hover pour les notifications
document.querySelectorAll('.notification-item').forEach(item => {
    item.addEventListener('mouseenter', function() {
        this.style.transform = 'translateX(4px)';
        this.style.transition = 'transform 0.2s';
    });
    
    item.addEventListener('mouseleave', function() {
        this.style.transform = 'translateX(0)';
    });
});

// Auto-refresh des notifications (toutes les minutes)
setInterval(async () => {
    try {
        const response = await fetch('/api/notifications/count');
        const data = await response.json();
        
        if (data.unread > document.querySelectorAll('.notification-item.unread').length) {
            // Il y a de nouvelles notifications, recharger la page
            window.location.reload();
        }
    } catch (error) {
        console.error('Erreur vérification notifications:', error);
    }
}, 60000);
</script>

<style>
.filter-btn:hover {
    background: rgba(var(--color-primary), 0.05) !important;
}

.action-btn:hover {
    opacity: 1 !important;
    transform: scale(1.1);
}

.notification-item {
    cursor: pointer;
    transition: all 0.3s;
}

.notification-item:hover {
    background: rgba(255,255,255,0.02) !important;
}

.notification-item.unread:hover {
    background: rgba(var(--color-primary), 0.05) !important;
}

.pagination-btn:hover {
    background: rgba(var(--color-primary), 0.05) !important;
}

@media (max-width: 768px) {
    .notification-item {
        padding: 1rem !important;
    }
    
    .notification-item > div:first-child {
        display: none; /* Masquer l'indicateur de statut sur mobile */
    }
    
    .grid-3 {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
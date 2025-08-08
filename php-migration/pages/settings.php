<?php
/**
 * Page Paramètres
 * Configuration personnelle et préférences utilisateur
 */

// Vérifier l'authentification
Auth::requireAuth();

$pageTitle = "Paramètres - StacGateLMS";
$pageDescription = "Configuration personnelle et préférences de votre compte.";

$currentUser = Auth::user();

// Traitement des actions
$action = $_POST['action'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['_token'] ?? '')) {
    try {
        $authService = new AuthService();
        
        switch ($action) {
            case 'update_profile':
                $updateData = [
                    'first_name' => $_POST['first_name'],
                    'last_name' => $_POST['last_name'],
                    'email' => $_POST['email'],
                    'bio' => $_POST['bio'] ?? null,
                    'timezone' => $_POST['timezone'] ?? 'Europe/Paris',
                    'language' => $_POST['language'] ?? 'fr'
                ];
                
                $authService->updateUser($currentUser['id'], $updateData);
                $message = ['type' => 'success', 'text' => 'Profil mis à jour avec succès'];
                
                // Recharger les données utilisateur
                $currentUser = Auth::user();
                break;
                
            case 'change_password':
                $currentPassword = $_POST['current_password'];
                $newPassword = $_POST['new_password'];
                $confirmPassword = $_POST['confirm_password'];
                
                if ($newPassword !== $confirmPassword) {
                    throw new Exception('Les mots de passe ne correspondent pas');
                }
                
                if (!Auth::verifyPassword($currentPassword, $currentUser['password'])) {
                    throw new Exception('Mot de passe actuel incorrect');
                }
                
                $authService->updateUser($currentUser['id'], [
                    'password' => Auth::hashPassword($newPassword)
                ]);
                
                $message = ['type' => 'success', 'text' => 'Mot de passe modifié avec succès'];
                break;
                
            case 'update_preferences':
                $preferences = [
                    'theme' => $_POST['theme'] ?? 'auto',
                    'notifications_email' => isset($_POST['notifications_email']),
                    'notifications_push' => isset($_POST['notifications_push']),
                    'notifications_courses' => isset($_POST['notifications_courses']),
                    'notifications_assessments' => isset($_POST['notifications_assessments']),
                    'notifications_social' => isset($_POST['notifications_social']),
                    'privacy_show_profile' => isset($_POST['privacy_show_profile']),
                    'privacy_show_activity' => isset($_POST['privacy_show_activity']),
                    'privacy_allow_messages' => isset($_POST['privacy_allow_messages'])
                ];
                
                $authService->updateUser($currentUser['id'], [
                    'preferences' => json_encode($preferences)
                ]);
                
                $message = ['type' => 'success', 'text' => 'Préférences mises à jour avec succès'];
                break;
                
            case 'export_data':
                // Générer un export des données utilisateur
                $exportService = new ExportService();
                $exportData = $exportService->exportUserData($currentUser['id']);
                
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="mes-donnees-stacgate-' . date('Y-m-d') . '.json"');
                echo json_encode($exportData, JSON_PRETTY_PRINT);
                exit;
                
            case 'delete_account':
                $confirmDelete = $_POST['confirm_delete'] ?? '';
                if ($confirmDelete !== 'SUPPRIMER') {
                    throw new Exception('Veuillez saisir "SUPPRIMER" pour confirmer');
                }
                
                // Anonymiser le compte au lieu de le supprimer
                $authService->anonymizeUser($currentUser['id']);
                Auth::logout();
                Utils::redirectWithMessage('/portal', 'Compte supprimé avec succès', 'success');
                exit;
        }
    } catch (Exception $e) {
        $message = ['type' => 'error', 'text' => $e->getMessage()];
        Utils::log("Settings error: " . $e->getMessage(), 'ERROR');
    }
}

// Récupérer les préférences actuelles
$preferences = json_decode($currentUser['preferences'] ?? '{}', true);
$defaultPreferences = [
    'theme' => 'auto',
    'notifications_email' => true,
    'notifications_push' => true,
    'notifications_courses' => true,
    'notifications_assessments' => true,
    'notifications_social' => false,
    'privacy_show_profile' => true,
    'privacy_show_activity' => true,
    'privacy_allow_messages' => true
];
$preferences = array_merge($defaultPreferences, $preferences);

require_once ROOT_PATH . '/includes/header.php';
?>

<div style="padding: 2rem 0; margin-top: 80px;">
    <div class="container">
        <!-- En-tête -->
        <div class="glassmorphism p-6 mb-8">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                        ⚙️ Paramètres
                    </h1>
                    <p style="opacity: 0.8;">
                        Configuration de votre compte et préférences
                    </p>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="text-align: right;">
                        <div style="font-weight: 600;"><?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></div>
                        <div style="opacity: 0.7; font-size: 0.9rem;"><?= htmlspecialchars($currentUser['email']) ?></div>
                    </div>
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--gradient-primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.2rem;">
                        <?= strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)) ?>
                    </div>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="message <?= $message['type'] ?>" style="margin-top: 1rem; padding: 1rem; border-radius: 8px; background: rgba(<?= $message['type'] === 'success' ? '34, 197, 94' : '239, 68, 68' ?>, 0.1); color: <?= $message['type'] === 'success' ? '#22c55e' : '#ef4444' ?>;">
                    <?= htmlspecialchars($message['text']) ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 250px 1fr; gap: 2rem;">
            <!-- Navigation sections -->
            <div class="glassmorphism p-4">
                <nav style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <a href="#profile" class="settings-nav-link active" onclick="showSection('profile', this)">
                        👤 Profil
                    </a>
                    <a href="#security" class="settings-nav-link" onclick="showSection('security', this)">
                        🔒 Sécurité
                    </a>
                    <a href="#notifications" class="settings-nav-link" onclick="showSection('notifications', this)">
                        🔔 Notifications
                    </a>
                    <a href="#privacy" class="settings-nav-link" onclick="showSection('privacy', this)">
                        🛡️ Confidentialité
                    </a>
                    <a href="#data" class="settings-nav-link" onclick="showSection('data', this)">
                        📊 Mes données
                    </a>
                </nav>
            </div>

            <!-- Contenu des sections -->
            <div class="settings-content">
                <!-- Section Profil -->
                <div id="profile-section" class="settings-section active">
                    <div class="glassmorphism p-6">
                        <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                            👤 Informations du profil
                        </h2>
                        
                        <form method="POST">
                            <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Prénom</label>
                                    <input type="text" name="first_name" value="<?= htmlspecialchars($currentUser['first_name']) ?>" 
                                           required class="glass-input" style="width: 100%;">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Nom</label>
                                    <input type="text" name="last_name" value="<?= htmlspecialchars($currentUser['last_name']) ?>" 
                                           required class="glass-input" style="width: 100%;">
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($currentUser['email']) ?>" 
                                       required class="glass-input" style="width: 100%;">
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Bio</label>
                                <textarea name="bio" class="glass-input" style="width: 100%; height: 80px;" 
                                          placeholder="Parlez-vous en quelques mots..."><?= htmlspecialchars($currentUser['bio'] ?? '') ?></textarea>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Fuseau horaire</label>
                                    <select name="timezone" class="glass-input" style="width: 100%;">
                                        <option value="Europe/Paris" <?= ($currentUser['timezone'] ?? 'Europe/Paris') === 'Europe/Paris' ? 'selected' : '' ?>>Europe/Paris</option>
                                        <option value="Europe/London" <?= ($currentUser['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' ?>>Europe/London</option>
                                        <option value="America/New_York" <?= ($currentUser['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>America/New_York</option>
                                        <option value="Asia/Tokyo" <?= ($currentUser['timezone'] ?? '') === 'Asia/Tokyo' ? 'selected' : '' ?>>Asia/Tokyo</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Langue</label>
                                    <select name="language" class="glass-input" style="width: 100%;">
                                        <option value="fr" <?= ($currentUser['language'] ?? 'fr') === 'fr' ? 'selected' : '' ?>>Français</option>
                                        <option value="en" <?= ($currentUser['language'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                                        <option value="es" <?= ($currentUser['language'] ?? '') === 'es' ? 'selected' : '' ?>>Español</option>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" class="glass-button">
                                💾 Enregistrer les modifications
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Section Sécurité -->
                <div id="security-section" class="settings-section" style="display: none;">
                    <div class="glassmorphism p-6">
                        <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                            🔒 Sécurité du compte
                        </h2>
                        
                        <form method="POST">
                            <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Mot de passe actuel</label>
                                <input type="password" name="current_password" required class="glass-input" style="width: 100%;">
                            </div>
                            
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Nouveau mot de passe</label>
                                <input type="password" name="new_password" required class="glass-input" style="width: 100%;" minlength="8">
                            </div>
                            
                            <div style="margin-bottom: 1.5rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Confirmer le nouveau mot de passe</label>
                                <input type="password" name="confirm_password" required class="glass-input" style="width: 100%;" minlength="8">
                            </div>
                            
                            <button type="submit" class="glass-button">
                                🔑 Changer le mot de passe
                            </button>
                        </form>
                        
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1);">
                            <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 1rem;">Sessions actives</h3>
                            <div style="padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 8px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 500;">Session actuelle</div>
                                        <div style="opacity: 0.7; font-size: 0.9rem;">
                                            Dernière activité : <?= date('d/m/Y à H:i') ?>
                                        </div>
                                    </div>
                                    <span style="color: rgb(var(--color-success)); font-size: 0.9rem;">●</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Notifications -->
                <div id="notifications-section" class="settings-section" style="display: none;">
                    <div class="glassmorphism p-6">
                        <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                            🔔 Préférences de notifications
                        </h2>
                        
                        <form method="POST">
                            <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="update_preferences">
                            
                            <div style="margin-bottom: 2rem;">
                                <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Canaux de notification</h3>
                                
                                <div style="display: flex; flex-direction: column; gap: 1rem;">
                                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                                        <input type="checkbox" name="notifications_email" 
                                               <?= $preferences['notifications_email'] ? 'checked' : '' ?>
                                               style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 500;">Notifications par email</div>
                                            <div style="opacity: 0.7; font-size: 0.9rem;">Recevoir des notifications importantes par email</div>
                                        </div>
                                    </label>
                                    
                                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                                        <input type="checkbox" name="notifications_push" 
                                               <?= $preferences['notifications_push'] ? 'checked' : '' ?>
                                               style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 500;">Notifications push</div>
                                            <div style="opacity: 0.7; font-size: 0.9rem;">Recevoir des notifications dans le navigateur</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 2rem;">
                                <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Types de notifications</h3>
                                
                                <div style="display: flex; flex-direction: column; gap: 1rem;">
                                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                                        <input type="checkbox" name="notifications_courses" 
                                               <?= $preferences['notifications_courses'] ? 'checked' : '' ?>
                                               style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 500;">Cours et formations</div>
                                            <div style="opacity: 0.7; font-size: 0.9rem;">Nouveaux cours, mises à jour, échéances</div>
                                        </div>
                                    </label>
                                    
                                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                                        <input type="checkbox" name="notifications_assessments" 
                                               <?= $preferences['notifications_assessments'] ? 'checked' : '' ?>
                                               style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 500;">Évaluations</div>
                                            <div style="opacity: 0.7; font-size: 0.9rem;">Nouvelles évaluations, résultats, rappels</div>
                                        </div>
                                    </label>
                                    
                                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                                        <input type="checkbox" name="notifications_social" 
                                               <?= $preferences['notifications_social'] ? 'checked' : '' ?>
                                               style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 500;">Activités sociales</div>
                                            <div style="opacity: 0.7; font-size: 0.9rem;">Messages, mentions, groupes d'étude</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="glass-button">
                                💾 Enregistrer les préférences
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Section Confidentialité -->
                <div id="privacy-section" class="settings-section" style="display: none;">
                    <div class="glassmorphism p-6">
                        <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                            🛡️ Confidentialité
                        </h2>
                        
                        <form method="POST">
                            <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="update_preferences">
                            
                            <div style="margin-bottom: 2rem;">
                                <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Visibilité du profil</h3>
                                
                                <div style="display: flex; flex-direction: column; gap: 1rem;">
                                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                                        <input type="checkbox" name="privacy_show_profile" 
                                               <?= $preferences['privacy_show_profile'] ? 'checked' : '' ?>
                                               style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 500;">Profil public</div>
                                            <div style="opacity: 0.7; font-size: 0.9rem;">Permettre aux autres utilisateurs de voir votre profil</div>
                                        </div>
                                    </label>
                                    
                                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                                        <input type="checkbox" name="privacy_show_activity" 
                                               <?= $preferences['privacy_show_activity'] ? 'checked' : '' ?>
                                               style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 500;">Activité visible</div>
                                            <div style="opacity: 0.7; font-size: 0.9rem;">Afficher votre activité récente aux autres</div>
                                        </div>
                                    </label>
                                    
                                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
                                        <input type="checkbox" name="privacy_allow_messages" 
                                               <?= $preferences['privacy_allow_messages'] ? 'checked' : '' ?>
                                               style="width: 18px; height: 18px;">
                                        <div>
                                            <div style="font-weight: 500;">Messages privés</div>
                                            <div style="opacity: 0.7; font-size: 0.9rem;">Autoriser les autres à vous envoyer des messages</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="glass-button">
                                💾 Enregistrer les préférences
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Section Mes données -->
                <div id="data-section" class="settings-section" style="display: none;">
                    <div class="glassmorphism p-6">
                        <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                            📊 Gestion de mes données
                        </h2>
                        
                        <div style="margin-bottom: 2rem;">
                            <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">Export des données</h3>
                            <p style="opacity: 0.8; margin-bottom: 1rem; line-height: 1.5;">
                                Téléchargez une copie de toutes vos données personnelles, 
                                cours suivis, évaluations et activités sur la plateforme.
                            </p>
                            
                            <form method="POST">
                                <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="export_data">
                                <button type="submit" class="glass-button">
                                    📥 Télécharger mes données
                                </button>
                            </form>
                        </div>
                        
                        <div style="padding: 1.5rem; background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 8px;">
                            <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem; color: #ef4444;">
                                ⚠️ Zone de danger
                            </h3>
                            
                            <p style="opacity: 0.8; margin-bottom: 1rem; line-height: 1.5;">
                                La suppression de votre compte est <strong>irréversible</strong>. 
                                Toutes vos données personnelles seront anonymisées.
                            </p>
                            
                            <details style="margin-bottom: 1rem;">
                                <summary style="cursor: pointer; font-weight: 500; margin-bottom: 0.5rem;">
                                    Que se passe-t-il lors de la suppression ?
                                </summary>
                                <ul style="margin-left: 1rem; opacity: 0.8; line-height: 1.5;">
                                    <li>Vos informations personnelles sont anonymisées</li>
                                    <li>Vos cours et évaluations restent accessibles de manière anonyme</li>
                                    <li>Votre adresse email devient inutilisable sur la plateforme</li>
                                    <li>Cette action ne peut pas être annulée</li>
                                </ul>
                            </details>
                            
                            <form method="POST" onsubmit="return confirmAccountDeletion()">
                                <input type="hidden" name="_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="delete_account">
                                
                                <div style="margin-bottom: 1rem;">
                                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                                        Tapez "SUPPRIMER" pour confirmer
                                    </label>
                                    <input type="text" name="confirm_delete" class="glass-input" 
                                           style="width: 200px;" placeholder="SUPPRIMER">
                                </div>
                                
                                <button type="submit" class="glass-button" 
                                        style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">
                                    🗑️ Supprimer mon compte
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showSection(sectionName, clickedLink) {
    // Masquer toutes les sections
    document.querySelectorAll('.settings-section').forEach(section => {
        section.style.display = 'none';
    });
    
    // Retirer la classe active de tous les liens
    document.querySelectorAll('.settings-nav-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // Afficher la section demandée
    document.getElementById(sectionName + '-section').style.display = 'block';
    
    // Activer le lien cliqué
    clickedLink.classList.add('active');
    
    // Mettre à jour l'URL
    window.history.replaceState({}, '', '#' + sectionName);
}

function confirmAccountDeletion() {
    return confirm(
        'Êtes-vous absolument sûr de vouloir supprimer votre compte ?\n\n' +
        'Cette action est IRRÉVERSIBLE et toutes vos données personnelles seront anonymisées.\n\n' +
        'Cliquez sur OK pour continuer ou Annuler pour abandonner.'
    );
}

// Gérer les liens de navigation avec ancres
document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash.slice(1);
    if (hash && document.getElementById(hash + '-section')) {
        const link = document.querySelector(`[onclick="showSection('${hash}', this)"]`);
        if (link) {
            showSection(hash, link);
        }
    }
});

// Validation des mots de passe
document.querySelector('input[name="confirm_password"]')?.addEventListener('input', function() {
    const newPassword = document.querySelector('input[name="new_password"]').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity('Les mots de passe ne correspondent pas');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<style>
.settings-nav-link {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    display: block;
    transition: all 0.3s;
    border: 1px solid transparent;
}

.settings-nav-link:hover {
    background: rgba(var(--color-primary), 0.05);
    transform: translateX(4px);
}

.settings-nav-link.active {
    background: rgba(var(--color-primary), 0.1);
    color: rgb(var(--color-primary));
    border-color: rgba(var(--color-primary), 0.3);
}

.settings-section {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
    .container > div:first-child {
        grid-template-columns: 1fr !important;
    }
    
    .glassmorphism:first-child {
        margin-bottom: 1rem;
    }
    
    .settings-nav-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
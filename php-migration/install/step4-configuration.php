<?php
/**
 * Étape 4 : Configuration de l'application
 */

// Récupération de la configuration de base de données
$dbConfig = $_SESSION['db_config'] ?? null;
if (!$dbConfig) {
    header('Location: ?action=database&step=3');
    exit;
}

// Traitement du formulaire
if ($_POST && isset($_POST['app_name'])) {
    $appConfig = [
        'app_name' => $_POST['app_name'],
        'app_url' => $_POST['app_url'],
        'admin_email' => $_POST['admin_email'],
        'admin_password' => $_POST['admin_password'],
        'timezone' => $_POST['timezone'],
        'language' => $_POST['language'],
        'demo_data' => isset($_POST['demo_data'])
    ];
    
    $_SESSION['app_config'] = $appConfig;
    
    // Validation simple
    if (strlen($appConfig['admin_password']) < 6) {
        $error = "Le mot de passe administrateur doit contenir au moins 6 caractères.";
    } elseif (!filter_var($appConfig['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email administrateur n'est pas valide.";
    } else {
        // Redirection vers la finalisation
        header('Location: ?action=finalize&step=5');
        exit;
    }
}

// Configuration par défaut
$defaultConfig = $_SESSION['app_config'] ?? [
    'app_name' => 'StacGateLMS',
    'app_url' => 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['REQUEST_URI']),
    'admin_email' => 'admin@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
    'admin_password' => '',
    'timezone' => 'Europe/Paris',
    'language' => 'fr',
    'demo_data' => true
];

// Nettoyage de l'URL
$defaultConfig['app_url'] = rtrim(str_replace('/install.php', '', $defaultConfig['app_url']), '/');

$timezones = [
    'Europe/Paris' => 'Paris (UTC+1)',
    'Europe/London' => 'Londres (UTC+0)',
    'Europe/Berlin' => 'Berlin (UTC+1)',
    'Europe/Madrid' => 'Madrid (UTC+1)',
    'Europe/Rome' => 'Rome (UTC+1)',
    'America/New_York' => 'New York (UTC-5)',
    'America/Los_Angeles' => 'Los Angeles (UTC-8)',
    'America/Montreal' => 'Montréal (UTC-5)',
    'Africa/Casablanca' => 'Casablanca (UTC+1)',
    'Africa/Algiers' => 'Alger (UTC+1)',
    'Asia/Tokyo' => 'Tokyo (UTC+9)',
    'Australia/Sydney' => 'Sydney (UTC+11)'
];
?>

<h2>⚙️ Configuration de l'application</h2>

<div style="margin: 20px 0;">
    <p style="font-size: 16px; line-height: 1.6; color: #4b5563;">
        Configurez les paramètres de base de votre plateforme d'apprentissage.
        Ces informations peuvent être modifiées ultérieurement dans l'administration.
    </p>
</div>

<?php if (isset($error)): ?>
    <div class="alert error">
        <strong>❌ Erreur :</strong><br>
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form method="post" id="config-form">
    <div class="two-column">
        <div>
            <h3>🏢 Informations générales</h3>
            
            <div class="form-group">
                <label for="app_name">Nom de l'application *</label>
                <input type="text" name="app_name" id="app_name" 
                       value="<?= htmlspecialchars($defaultConfig['app_name']) ?>" 
                       placeholder="StacGateLMS" required>
                <small style="color: #6b7280; display: block; margin-top: 5px;">
                    Ce nom apparaîtra dans l'interface utilisateur
                </small>
            </div>

            <div class="form-group">
                <label for="app_url">URL de l'application *</label>
                <input type="url" name="app_url" id="app_url" 
                       value="<?= htmlspecialchars($defaultConfig['app_url']) ?>" 
                       placeholder="https://mon-domaine.com" required>
                <small style="color: #6b7280; display: block; margin-top: 5px;">
                    URL complète d'accès à votre application
                </small>
            </div>

            <div class="form-group">
                <label for="timezone">Fuseau horaire</label>
                <select name="timezone" id="timezone">
                    <?php foreach ($timezones as $tz => $label): ?>
                        <option value="<?= $tz ?>" <?= $defaultConfig['timezone'] === $tz ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="language">Langue par défaut</label>
                <select name="language" id="language">
                    <option value="fr" <?= $defaultConfig['language'] === 'fr' ? 'selected' : '' ?>>
                        Français
                    </option>
                    <option value="en" <?= $defaultConfig['language'] === 'en' ? 'selected' : '' ?>>
                        English
                    </option>
                </select>
            </div>
        </div>

        <div>
            <h3>👤 Compte administrateur</h3>
            
            <div class="form-group">
                <label for="admin_email">Email administrateur *</label>
                <input type="email" name="admin_email" id="admin_email" 
                       value="<?= htmlspecialchars($defaultConfig['admin_email']) ?>" 
                       placeholder="admin@mondomaine.com" required>
                <small style="color: #6b7280; display: block; margin-top: 5px;">
                    Utilisé pour la connexion au compte super-administrateur
                </small>
            </div>

            <div class="form-group">
                <label for="admin_password">Mot de passe administrateur *</label>
                <input type="password" name="admin_password" id="admin_password" 
                       value="<?= htmlspecialchars($defaultConfig['admin_password']) ?>" 
                       placeholder="Minimum 6 caractères" required minlength="6">
                <small style="color: #6b7280; display: block; margin-top: 5px;">
                    Choisissez un mot de passe sécurisé (minimum 6 caractères)
                </small>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="demo_data" value="1" 
                           <?= $defaultConfig['demo_data'] ? 'checked' : '' ?>
                           style="margin-right: 8px;">
                    Installer les données de démonstration
                </label>
                <small style="color: #6b7280; display: block; margin-top: 5px;">
                    Inclut des établissements, cours et utilisateurs d'exemple
                </small>
            </div>
        </div>
    </div>

    <div style="margin-top: 30px; padding: 20px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
        <h4>📋 Résumé de la configuration</h4>
        <div class="two-column" style="margin-top: 15px;">
            <div>
                <p><strong>Base de données :</strong> <?= ucfirst($dbConfig['type']) ?></p>
                <?php if ($dbConfig['type'] !== 'sqlite'): ?>
                    <p><strong>Serveur :</strong> <?= htmlspecialchars($dbConfig['host']) ?></p>
                    <p><strong>Base :</strong> <?= htmlspecialchars($dbConfig['name']) ?></p>
                <?php else: ?>
                    <p><strong>Fichier :</strong> database.sqlite</p>
                <?php endif; ?>
            </div>
            <div>
                <p><strong>Application :</strong> <span id="summary-name"><?= htmlspecialchars($defaultConfig['app_name']) ?></span></p>
                <p><strong>URL :</strong> <span id="summary-url"><?= htmlspecialchars($defaultConfig['app_url']) ?></span></p>
                <p><strong>Admin :</strong> <span id="summary-email"><?= htmlspecialchars($defaultConfig['admin_email']) ?></span></p>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="?action=database&step=3" class="btn secondary">← Retour</a>
        
        <button type="submit" class="btn" style="margin-left: 10px;">
            Lancer l'installation 🚀
        </button>
    </div>
</form>

<div style="margin-top: 30px; padding: 20px; background: #fef3c7; border-radius: 10px; border-left: 4px solid #f59e0b;">
    <h4>⚠️ Informations importantes</h4>
    <ul style="margin: 15px 0; padding-left: 20px; line-height: 1.8;">
        <li><strong>Sécurité :</strong> Choisissez un mot de passe administrateur robuste</li>
        <li><strong>Email :</strong> Utilisez une adresse email valide pour les notifications</li>
        <li><strong>URL :</strong> L'URL doit être accessible depuis l'extérieur pour un usage en production</li>
        <li><strong>Données de démo :</strong> Utiles pour découvrir la plateforme, supprimables ensuite</li>
    </ul>
</div>

<script>
// Mise à jour du résumé en temps réel
document.getElementById('app_name').addEventListener('input', function() {
    document.getElementById('summary-name').textContent = this.value || 'StacGateLMS';
});

document.getElementById('app_url').addEventListener('input', function() {
    document.getElementById('summary-url').textContent = this.value || 'Non définie';
});

document.getElementById('admin_email').addEventListener('input', function() {
    document.getElementById('summary-email').textContent = this.value || 'Non défini';
});

// Validation du formulaire
document.getElementById('config-form').addEventListener('submit', function(e) {
    const password = document.getElementById('admin_password').value;
    const email = document.getElementById('admin_email').value;
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Le mot de passe doit contenir au moins 6 caractères.');
        return;
    }
    
    if (!email.includes('@')) {
        e.preventDefault();
        alert('Veuillez saisir une adresse email valide.');
        return;
    }
    
    // Confirmation avant installation
    if (!confirm('Êtes-vous prêt à lancer l\'installation ? Cette opération va créer les tables de base de données et configurer l\'application.')) {
        e.preventDefault();
    }
});

// Génération automatique d'URL
document.addEventListener('DOMContentLoaded', function() {
    const urlField = document.getElementById('app_url');
    if (urlField.value === '') {
        urlField.value = window.location.origin + window.location.pathname.replace('/install.php', '');
    }
});
</script>
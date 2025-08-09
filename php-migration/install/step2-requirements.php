<?php
/**
 * Étape 2 : Vérification des prérequis système
 */

// Vérifications système
$checks = [
    'php_version' => [
        'name' => 'Version PHP',
        'required' => '8.1.0',
        'current' => PHP_VERSION,
        'status' => version_compare(PHP_VERSION, '8.1.0', '>=') ? 'ok' : 'error',
        'description' => 'PHP 8.1.0 ou plus récent requis'
    ],
    'pdo' => [
        'name' => 'Extension PDO',
        'required' => 'Activée',
        'current' => extension_loaded('pdo') ? 'Activée' : 'Manquante',
        'status' => extension_loaded('pdo') ? 'ok' : 'error',
        'description' => 'Nécessaire pour la base de données'
    ],
    'pdo_mysql' => [
        'name' => 'PDO MySQL',
        'required' => 'Recommandée',
        'current' => extension_loaded('pdo_mysql') ? 'Activée' : 'Manquante',
        'status' => extension_loaded('pdo_mysql') ? 'ok' : 'warning',
        'description' => 'Support MySQL/MariaDB'
    ],
    'pdo_pgsql' => [
        'name' => 'PDO PostgreSQL',
        'required' => 'Recommandée',
        'current' => extension_loaded('pdo_pgsql') ? 'Activée' : 'Manquante',
        'status' => extension_loaded('pdo_pgsql') ? 'ok' : 'warning',
        'description' => 'Support PostgreSQL'
    ],
    'pdo_sqlite' => [
        'name' => 'PDO SQLite',
        'required' => 'Recommandée',
        'current' => extension_loaded('pdo_sqlite') ? 'Activée' : 'Manquante',
        'status' => extension_loaded('pdo_sqlite') ? 'ok' : 'warning',
        'description' => 'Base de données intégrée'
    ],
    'mbstring' => [
        'name' => 'Extension Mbstring',
        'required' => 'Activée',
        'current' => extension_loaded('mbstring') ? 'Activée' : 'Manquante',
        'status' => extension_loaded('mbstring') ? 'ok' : 'error',
        'description' => 'Gestion des caractères UTF-8'
    ],
    'json' => [
        'name' => 'Extension JSON',
        'required' => 'Activée',
        'current' => extension_loaded('json') ? 'Activée' : 'Manquante',
        'status' => extension_loaded('json') ? 'ok' : 'error',
        'description' => 'Traitement des données JSON'
    ],
    'session' => [
        'name' => 'Support Sessions',
        'required' => 'Activée',
        'current' => extension_loaded('session') ? 'Activée' : 'Manquante',
        'status' => extension_loaded('session') ? 'ok' : 'error',
        'description' => 'Gestion des sessions utilisateur'
    ],
    'openssl' => [
        'name' => 'Extension OpenSSL',
        'required' => 'Recommandée',
        'current' => extension_loaded('openssl') ? 'Activée' : 'Manquante',
        'status' => extension_loaded('openssl') ? 'ok' : 'warning',
        'description' => 'Chiffrement et sécurité'
    ]
];

// Vérifications des permissions de dossiers
$directories = [
    'cache' => ROOT_PATH . '/cache',
    'logs' => ROOT_PATH . '/logs',
    'uploads' => ROOT_PATH . '/uploads',
    'config' => ROOT_PATH . '/config'
];

foreach ($directories as $name => $path) {
    if (!file_exists($path)) {
        @mkdir($path, 0755, true);
    }
    
    $writable = is_writable($path);
    $checks["dir_$name"] = [
        'name' => "Dossier $name/",
        'required' => 'Accessible en écriture',
        'current' => $writable ? 'Accessible' : 'Protégé',
        'status' => $writable ? 'ok' : 'error',
        'description' => "Permissions d'écriture dans $path"
    ];
}

// Comptage des statuts
$totalChecks = count($checks);
$passedChecks = count(array_filter($checks, fn($check) => $check['status'] === 'ok'));
$errorChecks = count(array_filter($checks, fn($check) => $check['status'] === 'error'));
$warningChecks = count(array_filter($checks, fn($check) => $check['status'] === 'warning'));

$canContinue = $errorChecks === 0;
?>

<h2>🔍 Vérification des prérequis système</h2>

<div style="margin: 20px 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h3>Résultats de la vérification</h3>
            <p style="color: #6b7280;">
                <?= $passedChecks ?>/<?= $totalChecks ?> vérifications réussies
            </p>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 2em;">
                <?php if ($canContinue): ?>
                    <span style="color: #10b981;">✅</span>
                <?php else: ?>
                    <span style="color: #ef4444;">❌</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($canContinue): ?>
        <div class="alert success">
            <strong>🎉 Excellent !</strong> Votre serveur est compatible avec StacGateLMS. 
            Vous pouvez continuer l'installation.
        </div>
    <?php else: ?>
        <div class="alert error">
            <strong>⚠️ Problèmes détectés !</strong> 
            Veuillez corriger les erreurs ci-dessous avant de continuer.
            <?php if ($errorChecks > 0): ?>
                <br><small>Erreurs critiques : <?= $errorChecks ?></small>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($warningChecks > 0): ?>
        <div class="alert warning">
            <strong>ℹ️ Avertissements :</strong> 
            <?= $warningChecks ?> fonctionnalités optionnelles ne sont pas disponibles. 
            L'installation peut continuer mais certaines fonctionnalités seront limitées.
        </div>
    <?php endif; ?>
</div>

<div class="content" style="padding: 0;">
    <h3>📋 Détail des vérifications</h3>
    <ul class="check-list">
        <?php foreach ($checks as $key => $check): ?>
            <li>
                <div class="check-item">
                    <div>
                        <strong><?= htmlspecialchars($check['name']) ?></strong>
                        <br>
                        <small style="color: #6b7280;"><?= htmlspecialchars($check['description']) ?></small>
                    </div>
                    <div style="text-align: right;">
                        <span class="status <?= $check['status'] ?>">
                            <?= htmlspecialchars($check['current']) ?>
                        </span>
                        <br>
                        <small style="color: #9ca3af;">
                            Requis: <?= htmlspecialchars($check['required']) ?>
                        </small>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<div style="text-align: center; margin-top: 30px;">
    <a href="?action=welcome&step=1" class="btn secondary">← Retour</a>
    
    <?php if ($canContinue): ?>
        <a href="?action=database&step=3" class="btn" style="margin-left: 10px;">
            Continuer vers la base de données →
        </a>
    <?php else: ?>
        <button class="btn" onclick="window.location.reload()" style="margin-left: 10px; background: #f59e0b;">
            🔄 Revérifier
        </button>
    <?php endif; ?>
</div>

<?php if (!$canContinue): ?>
<div style="margin-top: 30px; padding: 20px; background: #fef2f2; border-radius: 10px; border-left: 4px solid #ef4444;">
    <h4>🛠️ Comment corriger les erreurs :</h4>
    <ul style="margin: 15px 0; padding-left: 20px; line-height: 1.8;">
        <?php if (!extension_loaded('pdo')): ?>
            <li><strong>Extension PDO :</strong> Activez l'extension PDO dans votre php.ini</li>
        <?php endif; ?>
        <?php if (!extension_loaded('mbstring')): ?>
            <li><strong>Extension Mbstring :</strong> Installez php-mbstring sur votre serveur</li>
        <?php endif; ?>
        <?php if (!extension_loaded('json')): ?>
            <li><strong>Extension JSON :</strong> Activez l'extension JSON dans votre php.ini</li>
        <?php endif; ?>
        <?php foreach ($directories as $name => $path): ?>
            <?php if (!is_writable($path)): ?>
                <li><strong>Dossier <?= $name ?> :</strong> 
                    Exécutez <code>chmod 755 <?= htmlspecialchars($path) ?></code>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<script>
// Auto-refresh toutes les 5 secondes si il y a des erreurs
<?php if (!$canContinue): ?>
setTimeout(() => {
    window.location.reload();
}, 5000);
<?php endif; ?>
</script>
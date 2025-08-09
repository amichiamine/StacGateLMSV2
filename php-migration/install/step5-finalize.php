<?php
/**
 * Étape 5 : Finalisation et installation
 */

// Récupération des configurations
$dbConfig = $_SESSION['db_config'] ?? null;
$appConfig = $_SESSION['app_config'] ?? null;

if (!$dbConfig || !$appConfig) {
    header('Location: ?action=welcome&step=1');
    exit;
}

// Variables pour le suivi de l'installation
$steps = [
    'database' => ['name' => 'Connexion à la base de données', 'status' => 'pending'],
    'tables' => ['name' => 'Création des tables', 'status' => 'pending'],
    'config' => ['name' => 'Génération de la configuration', 'status' => 'pending'],
    'admin' => ['name' => 'Création du compte administrateur', 'status' => 'pending'],
    'demo' => ['name' => 'Installation des données de démonstration', 'status' => 'pending'],
    'security' => ['name' => 'Configuration de la sécurité', 'status' => 'pending'],
    'cleanup' => ['name' => 'Finalisation', 'status' => 'pending']
];

$currentStep = $_GET['install_step'] ?? 'start';
$isInstalling = $currentStep !== 'start';

if ($_POST && isset($_POST['start_install'])) {
    header('Location: ?action=finalize&step=5&install_step=database');
    exit;
}

// Traitement de l'installation étape par étape
if ($isInstalling) {
    $result = performInstallationStep($currentStep, $dbConfig, $appConfig);
    
    if ($result['success']) {
        $steps[$currentStep]['status'] = 'completed';
        
        // Déterminer la prochaine étape
        $stepKeys = array_keys($steps);
        $currentIndex = array_search($currentStep, $stepKeys);
        
        if ($currentIndex !== false && $currentIndex < count($stepKeys) - 1) {
            $nextStep = $stepKeys[$currentIndex + 1];
            // Auto-redirection vers la prochaine étape
            echo "<script>setTimeout(() => window.location.href = '?action=finalize&step=5&install_step=$nextStep', 1500);</script>";
        } else {
            // Installation terminée
            echo "<script>setTimeout(() => window.location.href = '?action=complete', 2000);</script>";
        }
        
        $steps[$currentStep]['message'] = $result['message'];
    } else {
        $steps[$currentStep]['status'] = 'error';
        $steps[$currentStep]['message'] = $result['message'];
    }
    
    // Marquer les étapes précédentes comme complétées
    $stepKeys = array_keys($steps);
    $currentIndex = array_search($currentStep, $stepKeys);
    for ($i = 0; $i < $currentIndex; $i++) {
        if ($steps[$stepKeys[$i]]['status'] === 'pending') {
            $steps[$stepKeys[$i]]['status'] = 'completed';
        }
    }
}

function performInstallationStep($step, $dbConfig, $appConfig) {
    try {
        switch ($step) {
            case 'database':
                return setupDatabase($dbConfig);
                
            case 'tables':
                return createTables($dbConfig);
                
            case 'config':
                return generateConfiguration($dbConfig, $appConfig);
                
            case 'admin':
                return createAdminUser($dbConfig, $appConfig);
                
            case 'demo':
                if ($appConfig['demo_data']) {
                    return installDemoData($dbConfig);
                } else {
                    return ['success' => true, 'message' => 'Données de démonstration ignorées'];
                }
                
            case 'security':
                return configureSecurity($appConfig);
                
            case 'cleanup':
                return finalizeInstallation();
                
            default:
                return ['success' => false, 'message' => 'Étape inconnue'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function setupDatabase($config) {
    // Code de connexion à la base
    require_once __DIR__ . '/database-installer.php';
    return connectToDatabase($config);
}

function createTables($config) {
    require_once __DIR__ . '/database-installer.php';
    return createDatabaseTables($config);
}

function generateConfiguration($dbConfig, $appConfig) {
    $configContent = generateConfigFile($dbConfig, $appConfig);
    if (file_put_contents(dirname(__DIR__) . '/config/install-config.php', $configContent)) {
        return ['success' => true, 'message' => 'Configuration générée avec succès'];
    } else {
        return ['success' => false, 'message' => 'Impossible d\'écrire le fichier de configuration'];
    }
}

function createAdminUser($dbConfig, $appConfig) {
    require_once __DIR__ . '/database-installer.php';
    return createSuperAdmin($dbConfig, $appConfig);
}

function installDemoData($dbConfig) {
    require_once __DIR__ . '/database-installer.php';
    return seedDemoData($dbConfig);
}

function configureSecurity($appConfig) {
    // Génération des clés de sécurité
    $sessionSecret = bin2hex(random_bytes(32));
    $csrfSecret = bin2hex(random_bytes(16));
    
    $securityConfig = "<?php\n";
    $securityConfig .= "// Configuration de sécurité générée automatiquement\n";
    $securityConfig .= "define('SESSION_SECRET', '$sessionSecret');\n";
    $securityConfig .= "define('CSRF_SECRET', '$csrfSecret');\n";
    $securityConfig .= "define('SECURITY_SALT', '" . bin2hex(random_bytes(16)) . "');\n";
    
    if (file_put_contents(dirname(__DIR__) . '/config/security.php', $securityConfig)) {
        return ['success' => true, 'message' => 'Clés de sécurité générées'];
    } else {
        return ['success' => false, 'message' => 'Impossible de générer les clés de sécurité'];
    }
}

function finalizeInstallation() {
    // Création du fichier de verrouillage d'installation
    $lockFile = dirname(__DIR__) . '/.installed';
    $lockContent = "Installation terminée le " . date('Y-m-d H:i:s') . "\n";
    $lockContent .= "Version: " . INSTALL_VERSION . "\n";
    
    if (file_put_contents($lockFile, $lockContent)) {
        return ['success' => true, 'message' => 'Installation finalisée avec succès'];
    } else {
        return ['success' => false, 'message' => 'Impossible de finaliser l\'installation'];
    }
}

function generateConfigFile($dbConfig, $appConfig) {
    $config = "<?php\n";
    $config .= "/**\n * Configuration générée automatiquement par l'installateur\n */\n\n";
    
    // Configuration de l'application
    $config .= "define('APP_NAME', " . var_export($appConfig['app_name'], true) . ");\n";
    $config .= "define('APP_URL', " . var_export($appConfig['app_url'], true) . ");\n";
    $config .= "define('APP_ENV', 'production');\n";
    $config .= "define('APP_TIMEZONE', " . var_export($appConfig['timezone'], true) . ");\n";
    $config .= "define('APP_LANGUAGE', " . var_export($appConfig['language'], true) . ");\n\n";
    
    // Configuration de la base de données
    $config .= "// Configuration base de données\n";
    $config .= "define('DB_CONFIG', [\n";
    foreach ($dbConfig as $key => $value) {
        $config .= "    " . var_export($key, true) . " => " . var_export($value, true) . ",\n";
    }
    $config .= "]);\n\n";
    
    return $config;
}
?>

<h2>🚀 Installation en cours</h2>

<div style="margin: 20px 0;">
    <p style="font-size: 16px; line-height: 1.6; color: #4b5563;">
        <?php if (!$isInstalling): ?>
            Tout est prêt pour l'installation ! Cliquez sur le bouton ci-dessous pour commencer.
        <?php else: ?>
            Installation en cours... Veuillez patienter pendant que nous configurons votre plateforme.
        <?php endif; ?>
    </p>
</div>

<div style="margin: 30px 0;">
    <h3>📋 Étapes d'installation</h3>
    <ul class="check-list">
        <?php foreach ($steps as $key => $step): ?>
            <li>
                <div class="check-item">
                    <div>
                        <strong><?= htmlspecialchars($step['name']) ?></strong>
                        <?php if (isset($step['message'])): ?>
                            <br><small style="color: #6b7280;"><?= htmlspecialchars($step['message']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($step['status'] === 'completed'): ?>
                            <span class="status ok">✅ Terminé</span>
                        <?php elseif ($step['status'] === 'error'): ?>
                            <span class="status error">❌ Erreur</span>
                        <?php elseif ($currentStep === $key): ?>
                            <span class="status warning">⏳ En cours...</span>
                        <?php else: ?>
                            <span class="status">⏸️ En attente</span>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php if (!$isInstalling): ?>
    <div style="margin-top: 30px; padding: 20px; background: #f0f9ff; border-radius: 10px; border-left: 4px solid #0ea5e9;">
        <h4>📊 Récapitulatif de l'installation</h4>
        <div class="two-column" style="margin-top: 15px;">
            <div>
                <p><strong>Application :</strong> <?= htmlspecialchars($appConfig['app_name']) ?></p>
                <p><strong>URL :</strong> <?= htmlspecialchars($appConfig['app_url']) ?></p>
                <p><strong>Fuseau horaire :</strong> <?= htmlspecialchars($appConfig['timezone']) ?></p>
                <p><strong>Langue :</strong> <?= htmlspecialchars($appConfig['language']) ?></p>
            </div>
            <div>
                <p><strong>Base de données :</strong> <?= ucfirst($dbConfig['type']) ?></p>
                <?php if ($dbConfig['type'] !== 'sqlite'): ?>
                    <p><strong>Serveur :</strong> <?= htmlspecialchars($dbConfig['host']) ?></p>
                    <p><strong>Base :</strong> <?= htmlspecialchars($dbConfig['name']) ?></p>
                <?php endif; ?>
                <p><strong>Admin :</strong> <?= htmlspecialchars($appConfig['admin_email']) ?></p>
                <p><strong>Données de démo :</strong> <?= $appConfig['demo_data'] ? 'Oui' : 'Non' ?></p>
            </div>
        </div>
    </div>

    <form method="post">
        <div style="text-align: center; margin-top: 30px;">
            <a href="?action=configuration&step=4" class="btn secondary">← Modifier la configuration</a>
            
            <button type="submit" name="start_install" class="btn" style="margin-left: 10px;">
                🚀 Lancer l'installation
            </button>
        </div>
    </form>

<?php else: ?>
    <?php 
    $completedSteps = count(array_filter($steps, fn($s) => $s['status'] === 'completed'));
    $totalSteps = count($steps);
    $progress = ($completedSteps / $totalSteps) * 100;
    ?>
    
    <div class="progress-bar" style="margin: 30px 0;">
        <div class="progress-fill" style="width: <?= $progress ?>%"></div>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <p style="font-size: 18px; font-weight: bold; color: #8B5CF6;">
            <?= $completedSteps ?>/<?= $totalSteps ?> étapes terminées (<?= round($progress) ?>%)
        </p>
    </div>

    <?php if ($currentStep === 'cleanup' && $steps['cleanup']['status'] === 'completed'): ?>
        <div class="alert success" style="margin-top: 30px;">
            <strong>🎉 Installation terminée avec succès !</strong><br>
            Redirection vers la page de finalisation...
        </div>
    <?php endif; ?>

    <?php if (isset($steps[$currentStep]) && $steps[$currentStep]['status'] === 'error'): ?>
        <div class="alert error" style="margin-top: 30px;">
            <strong>❌ Erreur lors de l'installation :</strong><br>
            <?= htmlspecialchars($steps[$currentStep]['message']) ?>
            
            <div style="margin-top: 15px;">
                <a href="?action=welcome&step=1" class="btn secondary">← Recommencer</a>
                <button onclick="window.location.reload()" class="btn" style="margin-left: 10px; background: #f59e0b;">
                    🔄 Réessayer
                </button>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php if ($isInstalling && !isset($steps[$currentStep]['status']) || $steps[$currentStep]['status'] !== 'error'): ?>
<script>
// Animation de l'étape en cours
setInterval(() => {
    const currentElement = document.querySelector('.status.warning');
    if (currentElement) {
        currentElement.style.opacity = currentElement.style.opacity === '0.5' ? '1' : '0.5';
    }
}, 500);

// Mise à jour automatique toutes les 2 secondes
setTimeout(() => {
    if (!window.location.search.includes('install_step=cleanup')) {
        window.location.reload();
    }
}, 2000);
</script>
<?php endif; ?>
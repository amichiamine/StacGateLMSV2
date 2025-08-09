<?php
/**
 * Étape 3 : Configuration de la base de données
 */

// Traitement du formulaire
if ($_POST && isset($_POST['db_type'])) {
    $dbConfig = [
        'type' => $_POST['db_type'],
        'host' => $_POST['db_host'] ?? 'localhost',
        'port' => $_POST['db_port'] ?? '',
        'name' => $_POST['db_name'],
        'username' => $_POST['db_username'] ?? '',
        'password' => $_POST['db_password'] ?? '',
        'charset' => $_POST['db_charset'] ?? 'utf8mb4'
    ];
    
    // Sauvegarder la configuration en session
    $_SESSION['db_config'] = $dbConfig;
    
    // Test de connexion
    try {
        $testResult = testDatabaseConnection($dbConfig);
        if ($testResult['success']) {
            // Redirection vers l'étape suivante
            header('Location: ?action=configuration&step=4');
            exit;
        } else {
            $error = $testResult['message'];
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Configuration par défaut
$defaultConfig = $_SESSION['db_config'] ?? [
    'type' => 'sqlite',
    'host' => 'localhost',
    'port' => '',
    'name' => '',
    'username' => '',
    'password' => '',
    'charset' => 'utf8mb4'
];

function testDatabaseConnection($config) {
    try {
        switch ($config['type']) {
            case 'sqlite':
                $dsn = "sqlite:" . ROOT_PATH . "/database.sqlite";
                $pdo = new PDO($dsn);
                break;
                
            case 'mysql':
                $port = $config['port'] ?: '3306';
                $dsn = "mysql:host={$config['host']};port={$port};charset={$config['charset']}";
                $pdo = new PDO($dsn, $config['username'], $config['password']);
                
                // Créer la base si elle n'existe pas
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['name']}` CHARACTER SET {$config['charset']} COLLATE {$config['charset']}_unicode_ci");
                $pdo->exec("USE `{$config['name']}`");
                break;
                
            case 'postgresql':
                $port = $config['port'] ?: '5432';
                $dsn = "pgsql:host={$config['host']};port={$port}";
                $pdo = new PDO($dsn, $config['username'], $config['password']);
                
                // Vérifier si la base existe, la créer si nécessaire
                $stmt = $pdo->prepare("SELECT 1 FROM pg_database WHERE datname = ?");
                $stmt->execute([$config['name']]);
                if (!$stmt->fetch()) {
                    $pdo->exec("CREATE DATABASE \"{$config['name']}\"");
                }
                
                // Reconnexion à la nouvelle base
                $dsn = "pgsql:host={$config['host']};port={$port};dbname={$config['name']}";
                $pdo = new PDO($dsn, $config['username'], $config['password']);
                break;
        }
        
        // Test simple
        $pdo->exec("SELECT 1");
        
        return ['success' => true, 'message' => 'Connexion réussie'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>

<h2>🗄️ Configuration de la base de données</h2>

<div style="margin: 20px 0;">
    <p style="font-size: 16px; line-height: 1.6; color: #4b5563;">
        StacGateLMS supporte plusieurs types de bases de données. 
        Choisissez celle qui correspond à votre environnement.
    </p>
</div>

<?php if (isset($error)): ?>
    <div class="alert error">
        <strong>❌ Erreur de connexion :</strong><br>
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form method="post" id="db-form">
    <div class="form-group">
        <label for="db_type">Type de base de données *</label>
        <select name="db_type" id="db_type" required onchange="toggleDatabaseFields()">
            <option value="sqlite" <?= $defaultConfig['type'] === 'sqlite' ? 'selected' : '' ?>>
                SQLite (Recommandé pour débuter)
            </option>
            <option value="mysql" <?= $defaultConfig['type'] === 'mysql' ? 'selected' : '' ?>>
                MySQL / MariaDB
            </option>
            <option value="postgresql" <?= $defaultConfig['type'] === 'postgresql' ? 'selected' : '' ?>>
                PostgreSQL
            </option>
        </select>
        <small style="color: #6b7280; display: block; margin-top: 5px;">
            SQLite ne nécessite aucune configuration supplémentaire
        </small>
    </div>

    <div id="advanced-fields" style="display: <?= $defaultConfig['type'] === 'sqlite' ? 'none' : 'block' ?>;">
        <div class="two-column">
            <div class="form-group">
                <label for="db_host">Serveur *</label>
                <input type="text" name="db_host" id="db_host" 
                       value="<?= htmlspecialchars($defaultConfig['host']) ?>" 
                       placeholder="localhost" required>
            </div>
            <div class="form-group">
                <label for="db_port">Port</label>
                <input type="text" name="db_port" id="db_port" 
                       value="<?= htmlspecialchars($defaultConfig['port']) ?>" 
                       placeholder="Auto">
                <small style="color: #6b7280; display: block; margin-top: 5px;">
                    Laissez vide pour le port par défaut
                </small>
            </div>
        </div>

        <div class="form-group">
            <label for="db_name">Nom de la base de données *</label>
            <input type="text" name="db_name" id="db_name" 
                   value="<?= htmlspecialchars($defaultConfig['name']) ?>" 
                   placeholder="stacgatelms" required>
            <small style="color: #6b7280; display: block; margin-top: 5px;">
                La base sera créée automatiquement si elle n'existe pas
            </small>
        </div>

        <div class="two-column">
            <div class="form-group">
                <label for="db_username">Utilisateur *</label>
                <input type="text" name="db_username" id="db_username" 
                       value="<?= htmlspecialchars($defaultConfig['username']) ?>" 
                       placeholder="root" required>
            </div>
            <div class="form-group">
                <label for="db_password">Mot de passe</label>
                <input type="password" name="db_password" id="db_password" 
                       value="<?= htmlspecialchars($defaultConfig['password']) ?>" 
                       placeholder="Laissez vide si aucun">
            </div>
        </div>

        <div class="form-group" id="charset-field" style="display: <?= $defaultConfig['type'] === 'mysql' ? 'block' : 'none' ?>;">
            <label for="db_charset">Jeu de caractères</label>
            <select name="db_charset" id="db_charset">
                <option value="utf8mb4" <?= $defaultConfig['charset'] === 'utf8mb4' ? 'selected' : '' ?>>
                    utf8mb4 (Recommandé)
                </option>
                <option value="utf8" <?= $defaultConfig['charset'] === 'utf8' ? 'selected' : '' ?>>
                    utf8
                </option>
            </select>
        </div>
    </div>

    <div id="db-result"></div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="?action=requirements&step=2" class="btn secondary">← Retour</a>
        
        <button type="button" onclick="testDatabaseConnection()" class="btn" 
                style="margin-left: 10px; background: #f59e0b;">
            🔍 Tester la connexion
        </button>
        
        <button type="submit" class="btn" style="margin-left: 10px;">
            Continuer →
        </button>
    </div>
</form>

<div style="margin-top: 30px; padding: 20px; background: #f0f9ff; border-radius: 10px; border-left: 4px solid #0ea5e9;">
    <h4>💡 Conseils pour la base de données</h4>
    <div class="two-column" style="margin-top: 15px;">
        <div>
            <h5>🟢 SQLite (Recommandé pour débuter)</h5>
            <ul style="margin: 10px 0; padding-left: 20px; line-height: 1.6;">
                <li>Aucune configuration requise</li>
                <li>Parfait pour les tests et petites installations</li>
                <li>Base de données dans un fichier</li>
                <li>Pas de serveur à configurer</li>
            </ul>
        </div>
        <div>
            <h5>🔵 MySQL/PostgreSQL (Production)</h5>
            <ul style="margin: 10px 0; padding-left: 20px; line-height: 1.6;">
                <li>Performances optimales</li>
                <li>Adapté aux grandes installations</li>
                <li>Support concurrent multiple</li>
                <li>Sauvegardes professionnelles</li>
            </ul>
        </div>
    </div>
</div>

<script>
function toggleDatabaseFields() {
    const dbType = document.getElementById('db_type').value;
    const advancedFields = document.getElementById('advanced-fields');
    const charsetField = document.getElementById('charset-field');
    const portField = document.getElementById('db_port');
    
    if (dbType === 'sqlite') {
        advancedFields.style.display = 'none';
        // Supprimer l'attribut required des champs cachés
        advancedFields.querySelectorAll('input[required]').forEach(input => {
            input.removeAttribute('required');
        });
    } else {
        advancedFields.style.display = 'block';
        // Remettre l'attribut required
        document.getElementById('db_host').setAttribute('required', '');
        document.getElementById('db_name').setAttribute('required', '');
        document.getElementById('db_username').setAttribute('required', '');
        
        // Placeholder du port selon le type
        if (dbType === 'mysql') {
            portField.placeholder = '3306 (défaut)';
            charsetField.style.display = 'block';
        } else if (dbType === 'postgresql') {
            portField.placeholder = '5432 (défaut)';
            charsetField.style.display = 'none';
        }
    }
    
    // Effacer les résultats précédents
    document.getElementById('db-result').innerHTML = '';
}

async function testDatabaseConnection() {
    const btn = event.target;
    const originalText = btn.textContent;
    btn.textContent = 'Test en cours...';
    btn.disabled = true;

    const formData = new FormData(document.getElementById('db-form'));
    
    try {
        const response = await fetch('install/test-db.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('db-result').innerHTML = 
                '<div class="alert success"><strong>✅ Succès !</strong> ' + result.message + 
                '<br><small>La base de données est prête à être utilisée.</small></div>';
        } else {
            document.getElementById('db-result').innerHTML = 
                '<div class="alert error"><strong>❌ Erreur :</strong> ' + result.message + '</div>';
        }
    } catch (error) {
        document.getElementById('db-result').innerHTML = 
            '<div class="alert error"><strong>❌ Erreur de test :</strong> ' + error.message + '</div>';
    }

    btn.textContent = originalText;
    btn.disabled = false;
}

// Initialiser l'affichage au chargement
document.addEventListener('DOMContentLoaded', toggleDatabaseFields);
</script>
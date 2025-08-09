<?php
/**
 * Script de vérification des prérequis
 */

echo "🔍 Vérification des prérequis StacGateLMS\n";
echo str_repeat("=", 50) . "\n";

$errors = [];
$warnings = [];

// Vérification de la version PHP
echo "PHP Version: " . PHP_VERSION . " ";
if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    echo "✅\n";
} else {
    echo "❌\n";
    $errors[] = "PHP 8.1.0 ou plus récent requis (version actuelle: " . PHP_VERSION . ")";
}

// Vérification des extensions PHP requises
$requiredExtensions = [
    'pdo' => 'Base de données',
    'mbstring' => 'Gestion UTF-8',
    'json' => 'Données JSON',
    'session' => 'Sessions utilisateur',
    'filter' => 'Validation données',
    'curl' => 'Requêtes HTTP'
];

$recommendedExtensions = [
    'pdo_mysql' => 'Support MySQL',
    'pdo_pgsql' => 'Support PostgreSQL',
    'pdo_sqlite' => 'Support SQLite',
    'openssl' => 'Sécurité/Chiffrement',
    'fileinfo' => 'Détection type fichier',
    'gd' => 'Manipulation images'
];

echo "\n📦 Extensions PHP requises:\n";
foreach ($requiredExtensions as $ext => $desc) {
    echo sprintf("  %-12s (%s): ", $ext, $desc);
    if (extension_loaded($ext)) {
        echo "✅\n";
    } else {
        echo "❌\n";
        $errors[] = "Extension $ext manquante - $desc";
    }
}

echo "\n📦 Extensions PHP recommandées:\n";
foreach ($recommendedExtensions as $ext => $desc) {
    echo sprintf("  %-12s (%s): ", $ext, $desc);
    if (extension_loaded($ext)) {
        echo "✅\n";
    } else {
        echo "⚠️\n";
        $warnings[] = "Extension $ext recommandée - $desc";
    }
}

// Vérification des permissions de dossiers
echo "\n📁 Permissions des dossiers:\n";
$directories = [
    'cache' => 'Cache application',
    'logs' => 'Fichiers de logs',
    'uploads' => 'Fichiers uploadés',
    'config' => 'Configuration'
];

foreach ($directories as $dir => $desc) {
    $path = dirname(__DIR__) . "/$dir";
    echo sprintf("  %-12s (%s): ", $dir, $desc);
    
    if (!file_exists($path)) {
        if (@mkdir($path, 0755, true)) {
            echo "✅ (créé)\n";
        } else {
            echo "❌\n";
            $errors[] = "Impossible de créer le dossier $path";
            continue;
        }
    }
    
    if (is_writable($path)) {
        echo "✅\n";
    } else {
        echo "❌\n";
        $errors[] = "Dossier $path non accessible en écriture";
    }
}

// Vérification de la configuration
echo "\n⚙️ Configuration:\n";
$configFile = dirname(__DIR__) . '/.env';
echo "  Fichier .env: ";
if (file_exists($configFile)) {
    echo "✅\n";
} else {
    echo "⚠️\n";
    $warnings[] = "Fichier .env manquant (sera créé automatiquement)";
}

// Vérification de la base de données (si configurée)
if (file_exists($configFile)) {
    $envVars = parse_ini_file($configFile);
    if ($envVars && isset($envVars['DB_TYPE'])) {
        echo "  Base de données (" . $envVars['DB_TYPE'] . "): ";
        try {
            $testResult = testDatabaseConnection($envVars);
            if ($testResult) {
                echo "✅\n";
            } else {
                echo "⚠️\n";
                $warnings[] = "Configuration base de données à vérifier";
            }
        } catch (Exception $e) {
            echo "⚠️\n";
            $warnings[] = "Erreur de connexion DB: " . $e->getMessage();
        }
    }
}

// Résumé
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 RÉSUMÉ DE LA VÉRIFICATION\n";
echo str_repeat("=", 50) . "\n";

if (empty($errors)) {
    echo "✅ Tous les prérequis critiques sont satisfaits!\n";
} else {
    echo "❌ " . count($errors) . " erreur(s) critique(s) détectée(s):\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
}

if (!empty($warnings)) {
    echo "⚠️  " . count($warnings) . " avertissement(s):\n";
    foreach ($warnings as $warning) {
        echo "   - $warning\n";
    }
}

if (empty($errors)) {
    echo "\n🚀 Votre système est prêt pour StacGateLMS!\n";
    echo "💡 Prochaines étapes:\n";
    echo "   1. Configurez .env si nécessaire\n";
    echo "   2. Lancez l'installation web: http://localhost/install.php\n";
    echo "   3. Ou utilisez: php -S localhost:8000 install.php\n";
    exit(0);
} else {
    echo "\n🛠️  Veuillez corriger les erreurs avant de continuer.\n";
    exit(1);
}

function testDatabaseConnection($config) {
    $dbType = $config['DB_TYPE'] ?? 'sqlite';
    
    try {
        switch ($dbType) {
            case 'sqlite':
                $dsn = "sqlite:" . dirname(__DIR__) . "/database.sqlite";
                $pdo = new PDO($dsn);
                break;
                
            case 'mysql':
                $host = $config['DB_HOST'] ?? 'localhost';
                $port = $config['DB_PORT'] ?? '3306';
                $dbname = $config['DB_NAME'] ?? '';
                $charset = $config['DB_CHARSET'] ?? 'utf8mb4';
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
                $pdo = new PDO($dsn, $config['DB_USERNAME'] ?? '', $config['DB_PASSWORD'] ?? '');
                break;
                
            case 'postgresql':
                $host = $config['DB_HOST'] ?? 'localhost';
                $port = $config['DB_PORT'] ?? '5432';
                $dbname = $config['DB_NAME'] ?? '';
                $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
                $pdo = new PDO($dsn, $config['DB_USERNAME'] ?? '', $config['DB_PASSWORD'] ?? '');
                break;
                
            default:
                return false;
        }
        
        $pdo->exec("SELECT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>
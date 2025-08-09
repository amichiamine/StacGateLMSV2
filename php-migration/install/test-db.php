<?php
/**
 * Script de test de connexion à la base de données
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

define('ROOT_PATH', dirname(__DIR__));

try {
    $config = [
        'type' => $_POST['db_type'] ?? 'sqlite',
        'host' => $_POST['db_host'] ?? 'localhost',
        'port' => $_POST['db_port'] ?? '',
        'name' => $_POST['db_name'] ?? '',
        'username' => $_POST['db_username'] ?? '',
        'password' => $_POST['db_password'] ?? '',
        'charset' => $_POST['db_charset'] ?? 'utf8mb4'
    ];

    $result = testConnection($config);
    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur inattendue: ' . $e->getMessage()
    ]);
}

function testConnection($config) {
    try {
        switch ($config['type']) {
            case 'sqlite':
                $dbPath = ROOT_PATH . "/database.sqlite";
                $dsn = "sqlite:$dbPath";
                $pdo = new PDO($dsn);
                
                // Test d'écriture
                $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (id INTEGER PRIMARY KEY, test TEXT)");
                $pdo->exec("INSERT INTO test_table (test) VALUES ('test')");
                $pdo->exec("DROP TABLE test_table");
                
                return [
                    'success' => true, 
                    'message' => "Connexion SQLite réussie ($dbPath)"
                ];
                
            case 'mysql':
                $port = $config['port'] ?: '3306';
                $dsn = "mysql:host={$config['host']};port={$port};charset={$config['charset']}";
                
                // Test de connexion au serveur
                $pdo = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5
                ]);
                
                // Vérifier/créer la base de données
                $dbName = $config['name'];
                $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
                $stmt->execute([$dbName]);
                
                if (!$stmt->fetch()) {
                    $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET {$config['charset']} COLLATE {$config['charset']}_unicode_ci");
                    $created = true;
                } else {
                    $created = false;
                }
                
                // Connexion à la base spécifique
                $dsn = "mysql:host={$config['host']};port={$port};dbname={$config['name']};charset={$config['charset']}";
                $pdo = new PDO($dsn, $config['username'], $config['password']);
                
                // Test d'écriture
                $pdo->exec("CREATE TEMPORARY TABLE test_table (id INT AUTO_INCREMENT PRIMARY KEY, test VARCHAR(255))");
                
                $message = "Connexion MySQL réussie sur {$config['host']}:{$port}";
                if ($created) {
                    $message .= " (base '$dbName' créée)";
                }
                
                return ['success' => true, 'message' => $message];
                
            case 'postgresql':
                $port = $config['port'] ?: '5432';
                $dsn = "pgsql:host={$config['host']};port={$port}";
                
                // Test de connexion au serveur
                $pdo = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5
                ]);
                
                // Vérifier/créer la base de données
                $dbName = $config['name'];
                $stmt = $pdo->prepare("SELECT 1 FROM pg_database WHERE datname = ?");
                $stmt->execute([$dbName]);
                
                if (!$stmt->fetch()) {
                    $pdo->exec("CREATE DATABASE \"$dbName\"");
                    $created = true;
                } else {
                    $created = false;
                }
                
                // Connexion à la base spécifique
                $dsn = "pgsql:host={$config['host']};port={$port};dbname={$config['name']}";
                $pdo = new PDO($dsn, $config['username'], $config['password']);
                
                // Test d'écriture
                $pdo->exec("CREATE TEMP TABLE test_table (id SERIAL PRIMARY KEY, test VARCHAR(255))");
                
                $message = "Connexion PostgreSQL réussie sur {$config['host']}:{$port}";
                if ($created) {
                    $message .= " (base '$dbName' créée)";
                }
                
                return ['success' => true, 'message' => $message];
                
            default:
                return [
                    'success' => false, 
                    'message' => 'Type de base de données non supporté: ' . $config['type']
                ];
        }
        
    } catch (PDOException $e) {
        return [
            'success' => false, 
            'message' => "Erreur PDO: " . $e->getMessage()
        ];
    } catch (Exception $e) {
        return [
            'success' => false, 
            'message' => "Erreur: " . $e->getMessage()
        ];
    }
}
?>
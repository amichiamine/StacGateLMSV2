<?php
/**
 * Configuration simple de la base de données SQLite
 * Version simplifiée pour compatibilité maximale
 */

// Configuration base de données SQLite simple
$db_config = [
    'type' => 'sqlite',
    'name' => (defined('ROOT_PATH') ? ROOT_PATH : __DIR__ . '/..') . '/database.sqlite',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
];

// SQL pour la création des tables (version simplifiée SQLite)
$create_tables_sql = [
    'establishments' => "
        CREATE TABLE IF NOT EXISTS establishments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            logo VARCHAR(500),
            domain VARCHAR(255),
            is_active INTEGER DEFAULT 1,
            settings TEXT DEFAULT '{}',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
    
    'users' => "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            establishment_id INTEGER NOT NULL,
            email VARCHAR(255) NOT NULL,
            username VARCHAR(100),
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'apprenant',
            avatar VARCHAR(500),
            is_active INTEGER DEFAULT 1,
            last_login_at DATETIME NULL,
            email_verified_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
    
    'courses' => "
        CREATE TABLE IF NOT EXISTS courses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            establishment_id INTEGER NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            short_description VARCHAR(500),
            category VARCHAR(100) DEFAULT 'web',
            type VARCHAR(50) DEFAULT 'cours',
            price DECIMAL(10,2) DEFAULT 0.00,
            is_free INTEGER DEFAULT 1,
            duration INTEGER DEFAULT 60,
            level VARCHAR(20) DEFAULT 'debutant',
            language VARCHAR(10) DEFAULT 'fr',
            tags TEXT,
            image_url VARCHAR(500),
            thumbnail_url VARCHAR(500),
            video_trailer_url VARCHAR(500),
            instructor_id INTEGER,
            is_public INTEGER DEFAULT 1,
            is_active INTEGER DEFAULT 1,
            rating DECIMAL(3,2) DEFAULT 0.00,
            enrollment_count INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
];

// Fonction d'initialisation simple
function initializeDatabase() {
    global $db_config, $create_tables_sql;
    
    try {
        $pdo = new PDO("sqlite:{$db_config['name']}", '', '', $db_config['options']);
        
        // Création des tables
        foreach ($create_tables_sql as $table_name => $sql) {
            $pdo->exec($sql);
        }
        
        // Données de test
        seedTestData($pdo);
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Erreur de connexion à la base de données: " . $e->getMessage());
    }
}

function seedTestData($pdo) {
    // Vérifier si des données existent déjà
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM establishments");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        // Données de test pour establishments
        $pdo->exec("INSERT INTO establishments (name, slug, description, domain, is_active) VALUES 
            ('StacGate Academy', 'stacgate-academy', 'École de formation professionnelle', 'stacgate.academy', 1),
            ('TechPro Institute', 'techpro-institute', 'Institut de formation technique', 'techpro.institute', 1)");
        
        // Données de test pour users
        $pdo->exec("INSERT INTO users (establishment_id, email, first_name, last_name, password, role, is_active) VALUES 
            (1, 'admin@stacgate.fr', 'Admin', 'Principal', '" . password_hash('admin123', PASSWORD_BCRYPT) . "', 'admin', 1),
            (1, 'formateur@stacgate.fr', 'Jean', 'Formateur', '" . password_hash('formateur123', PASSWORD_BCRYPT) . "', 'formateur', 1),
            (1, 'apprenant@stacgate.fr', 'Marie', 'Apprenante', '" . password_hash('apprenant123', PASSWORD_BCRYPT) . "', 'apprenant', 1)");
        
        // Données de test pour courses
        $pdo->exec("INSERT INTO courses (establishment_id, title, description, category, is_free, duration, level, instructor_id, is_active) VALUES 
            (1, 'Introduction au Développement Web', 'Apprenez les bases du développement web', 'web', 1, 120, 'debutant', 2, 1),
            (1, 'React Avancé', 'Maîtrisez React avec les hooks et patterns avancés', 'frontend', 0, 180, 'avance', 2, 1)");
    }
}

// Export de la configuration
define('DB_CONFIG', $db_config);
?>
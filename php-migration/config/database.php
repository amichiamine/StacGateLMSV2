<?php
/**
 * Configuration de la base de données
 * Support MySQL et PostgreSQL via PDO
 */

// Configuration base de données depuis variables d'environnement
$db_config = [
    'type' => env('DB_TYPE', 'mysql'), // mysql ou postgresql
    'host' => env('DB_HOST', 'localhost'),
    'port' => env('DB_PORT', '3306'),
    'name' => env('DB_NAME', 'stacgatelms'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false,
    ]
];

// DSN selon le type de base de données
function getDSN($config) {
    switch ($config['type']) {
        case 'postgresql':
        case 'pgsql':
            return "pgsql:host={$config['host']};port={$config['port']};dbname={$config['name']};";
            
        case 'mysql':
        default:
            return "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset={$config['charset']};";
    }
}

// Configuration des requêtes SQL selon le type de DB
define('DB_TYPE', $db_config['type']);
define('IS_POSTGRESQL', in_array(DB_TYPE, ['postgresql', 'pgsql']));
define('IS_MYSQL', DB_TYPE === 'mysql');

// Configuration pour les différences SQL entre MySQL et PostgreSQL
define('SQL_AUTO_INCREMENT', IS_POSTGRESQL ? 'SERIAL PRIMARY KEY' : 'INT AUTO_INCREMENT PRIMARY KEY');
define('SQL_TEXT_TYPE', IS_POSTGRESQL ? 'TEXT' : 'TEXT');
define('SQL_JSON_TYPE', IS_POSTGRESQL ? 'JSONB' : 'JSON');
define('SQL_TIMESTAMP_TYPE', IS_POSTGRESQL ? 'TIMESTAMP' : 'TIMESTAMP');
define('SQL_BOOLEAN_TYPE', IS_POSTGRESQL ? 'BOOLEAN' : 'TINYINT(1)');

// SQL pour la création des tables
$create_tables_sql = [
    'establishments' => "
        CREATE TABLE IF NOT EXISTS establishments (
            id " . (IS_POSTGRESQL ? 'SERIAL PRIMARY KEY' : 'INT AUTO_INCREMENT PRIMARY KEY') . ",
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            logo VARCHAR(500),
            domain VARCHAR(255),
            is_active " . SQL_BOOLEAN_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'TRUE' : '1') . ",
            settings " . SQL_JSON_TYPE . " DEFAULT " . (IS_POSTGRESQL ? "'{}'" : "'{}'") . ",
            created_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
            updated_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP') . "
        )",
    
    'users' => "
        CREATE TABLE IF NOT EXISTS users (
            id " . (IS_POSTGRESQL ? 'SERIAL PRIMARY KEY' : 'INT AUTO_INCREMENT PRIMARY KEY') . ",
            establishment_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            username VARCHAR(100),
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('super_admin', 'admin', 'manager', 'formateur', 'apprenant') DEFAULT 'apprenant',
            avatar VARCHAR(500),
            is_active " . SQL_BOOLEAN_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'TRUE' : '1') . ",
            last_login_at " . SQL_TIMESTAMP_TYPE . " NULL,
            email_verified_at " . SQL_TIMESTAMP_TYPE . " NULL,
            created_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
            updated_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP') . ",
            FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE,
            UNIQUE KEY unique_email_establishment (email, establishment_id)
        )",
    
    'themes' => "
        CREATE TABLE IF NOT EXISTS themes (
            id " . (IS_POSTGRESQL ? 'SERIAL PRIMARY KEY' : 'INT AUTO_INCREMENT PRIMARY KEY') . ",
            establishment_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            is_active " . SQL_BOOLEAN_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'FALSE' : '0') . ",
            primary_color VARCHAR(7) DEFAULT '#8B5CF6',
            secondary_color VARCHAR(7) DEFAULT '#A78BFA',
            accent_color VARCHAR(7) DEFAULT '#C4B5FD',
            background_color VARCHAR(7) DEFAULT '#FFFFFF',
            text_color VARCHAR(7) DEFAULT '#1F2937',
            font_family VARCHAR(100) DEFAULT 'Inter',
            font_size VARCHAR(10) DEFAULT '16px',
            created_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
            updated_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP') . ",
            FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE
        )",
    
    'courses' => "
        CREATE TABLE IF NOT EXISTS courses (
            id " . (IS_POSTGRESQL ? 'SERIAL PRIMARY KEY' : 'INT AUTO_INCREMENT PRIMARY KEY') . ",
            establishment_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            short_description VARCHAR(500),
            category VARCHAR(100) DEFAULT 'web',
            type VARCHAR(50) DEFAULT 'cours',
            price DECIMAL(10,2) DEFAULT 0.00,
            is_free " . SQL_BOOLEAN_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'TRUE' : '1') . ",
            duration INT DEFAULT 60,
            level ENUM('debutant', 'intermediaire', 'avance') DEFAULT 'debutant',
            language VARCHAR(10) DEFAULT 'fr',
            tags TEXT,
            image_url VARCHAR(500),
            thumbnail_url VARCHAR(500),
            video_trailer_url VARCHAR(500),
            instructor_id INT,
            is_public " . SQL_BOOLEAN_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'TRUE' : '1') . ",
            is_active " . SQL_BOOLEAN_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'TRUE' : '1') . ",
            rating DECIMAL(3,2) DEFAULT 0.00,
            enrollment_count INT DEFAULT 0,
            created_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
            updated_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP') . ",
            FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE,
            FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL
        )",
    
    'user_courses' => "
        CREATE TABLE IF NOT EXISTS user_courses (
            id " . (IS_POSTGRESQL ? 'SERIAL PRIMARY KEY' : 'INT AUTO_INCREMENT PRIMARY KEY') . ",
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            enrolled_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
            completed_at " . SQL_TIMESTAMP_TYPE . " NULL,
            progress DECIMAL(5,2) DEFAULT 0.00,
            last_accessed_at " . SQL_TIMESTAMP_TYPE . " NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_course (user_id, course_id)
        )",
    
    'assessments' => "
        CREATE TABLE IF NOT EXISTS assessments (
            id " . (IS_POSTGRESQL ? 'SERIAL PRIMARY KEY' : 'INT AUTO_INCREMENT PRIMARY KEY') . ",
            establishment_id INT NOT NULL,
            course_id INT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            type ENUM('quiz', 'exam', 'assignment') DEFAULT 'quiz',
            questions " . SQL_JSON_TYPE . " DEFAULT " . (IS_POSTGRESQL ? "'[]'" : "'[]'") . ",
            time_limit INT DEFAULT 60,
            max_attempts INT DEFAULT 3,
            passing_score DECIMAL(5,2) DEFAULT 60.00,
            is_active " . SQL_BOOLEAN_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'TRUE' : '1') . ",
            created_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
            updated_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP') . ",
            FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        )",
    
    'study_groups' => "
        CREATE TABLE IF NOT EXISTS study_groups (
            id " . (IS_POSTGRESQL ? 'SERIAL PRIMARY KEY' : 'INT AUTO_INCREMENT PRIMARY KEY') . ",
            establishment_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            course_id INT,
            creator_id INT NOT NULL,
            max_members INT DEFAULT 20,
            is_public " . SQL_BOOLEAN_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'TRUE' : '1') . ",
            is_active " . SQL_BOOLEAN_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'TRUE' : '1') . ",
            created_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
            updated_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP') . ",
            FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
            FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
        )",
    
    'collaboration_rooms' => "
        CREATE TABLE IF NOT EXISTS collaboration_rooms (
            id " . (IS_POSTGRESQL ? 'SERIAL PRIMARY KEY' : 'INT AUTO_INCREMENT PRIMARY KEY') . ",
            room_id VARCHAR(100) UNIQUE NOT NULL,
            type ENUM('course', 'studygroup', 'whiteboard', 'assessment') NOT NULL,
            resource_id INT NOT NULL,
            establishment_id INT NOT NULL,
            participants " . SQL_JSON_TYPE . " DEFAULT " . (IS_POSTGRESQL ? "'[]'" : "'[]'") . ",
            last_activity " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
            created_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
            FOREIGN KEY (establishment_id) REFERENCES establishments(id) ON DELETE CASCADE
        )",
    
    'collaboration_messages' => "
        CREATE TABLE IF NOT EXISTS collaboration_messages (
            id " . (IS_POSTGRESQL ? 'SERIAL PRIMARY KEY' : 'INT AUTO_INCREMENT PRIMARY KEY') . ",
            room_id VARCHAR(100) NOT NULL,
            user_id INT NOT NULL,
            type ENUM('chat', 'cursor', 'text_change', 'whiteboard_draw', 'typing', 'join', 'leave') NOT NULL,
            data " . SQL_JSON_TYPE . " DEFAULT " . (IS_POSTGRESQL ? "'{}'" : "'{}'") . ",
            created_at " . SQL_TIMESTAMP_TYPE . " DEFAULT " . (IS_POSTGRESQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )"
];

// Fonction d'initialisation de la base de données
function initializeDatabase() {
    global $db_config, $create_tables_sql;
    
    try {
        $dsn = getDSN($db_config);
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $db_config['options']);
        
        // Création des tables
        foreach ($create_tables_sql as $table_name => $sql) {
            $pdo->exec($sql);
        }
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Erreur de connexion à la base de données. Vérifiez la configuration.");
    }
}

// Export de la configuration pour utilisation dans Database.php
define('DB_CONFIG', $db_config);
?>
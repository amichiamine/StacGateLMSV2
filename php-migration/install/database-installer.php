<?php
/**
 * Fonctions d'installation de la base de données
 */

function connectToDatabase($config) {
    try {
        switch ($config['type']) {
            case 'sqlite':
                $dsn = "sqlite:" . dirname(__DIR__) . "/database.sqlite";
                $pdo = new PDO($dsn);
                break;
                
            case 'mysql':
                $port = $config['port'] ?: '3306';
                $dsn = "mysql:host={$config['host']};port={$port};dbname={$config['name']};charset={$config['charset']}";
                $pdo = new PDO($dsn, $config['username'], $config['password']);
                break;
                
            case 'postgresql':
                $port = $config['port'] ?: '5432';
                $dsn = "pgsql:host={$config['host']};port={$port};dbname={$config['name']}";
                $pdo = new PDO($dsn, $config['username'], $config['password']);
                break;
        }
        
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return ['success' => true, 'message' => 'Connexion à la base de données établie'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur de connexion: ' . $e->getMessage()];
    }
}

function createDatabaseTables($config) {
    try {
        $pdo = getDatabaseConnection($config);
        
        // Configuration des types SQL selon la base
        $isPostgreSQL = $config['type'] === 'postgresql';
        $isMySQL = $config['type'] === 'mysql';
        $isSQLite = $config['type'] === 'sqlite';
        
        $autoIncrement = $isSQLite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 
                        ($isPostgreSQL ? 'SERIAL PRIMARY KEY' : 'INT AUTO_INCREMENT PRIMARY KEY');
        $textType = 'TEXT';
        $jsonType = $isSQLite ? 'TEXT' : ($isPostgreSQL ? 'JSONB' : 'JSON');
        $timestampType = $isSQLite ? 'DATETIME' : ($isPostgreSQL ? 'TIMESTAMP' : 'TIMESTAMP');
        $booleanType = $isSQLite ? 'INTEGER' : ($isPostgreSQL ? 'BOOLEAN' : 'TINYINT(1)');
        
        // Tables principales
        $tables = [
            'establishments' => "
                CREATE TABLE IF NOT EXISTS establishments (
                    id $autoIncrement,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(100) UNIQUE NOT NULL,
                    description TEXT,
                    logo VARCHAR(500),
                    domain VARCHAR(255),
                    is_active $booleanType DEFAULT " . ($isPostgreSQL ? 'TRUE' : '1') . ",
                    settings $jsonType DEFAULT " . ($isPostgreSQL ? "'{}'" : "'{}'") . ",
                    created_at $timestampType DEFAULT " . ($isPostgreSQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
                    updated_at $timestampType DEFAULT " . ($isPostgreSQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . "
                )",
            
            'users' => "
                CREATE TABLE IF NOT EXISTS users (
                    id $autoIncrement,
                    establishment_id INT NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    username VARCHAR(100),
                    first_name VARCHAR(100) NOT NULL,
                    last_name VARCHAR(100) NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    role VARCHAR(20) DEFAULT 'apprenant',
                    avatar VARCHAR(500),
                    is_active $booleanType DEFAULT " . ($isPostgreSQL ? 'TRUE' : '1') . ",
                    last_login_at $timestampType NULL,
                    email_verified_at $timestampType NULL,
                    created_at $timestampType DEFAULT " . ($isPostgreSQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
                    updated_at $timestampType DEFAULT " . ($isPostgreSQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . "
                )",
            
            'courses' => "
                CREATE TABLE IF NOT EXISTS courses (
                    id $autoIncrement,
                    establishment_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    short_description VARCHAR(500),
                    category VARCHAR(100) DEFAULT 'web',
                    type VARCHAR(50) DEFAULT 'cours',
                    price DECIMAL(10,2) DEFAULT 0.00,
                    is_free $booleanType DEFAULT " . ($isPostgreSQL ? 'TRUE' : '1') . ",
                    duration INT DEFAULT 60,
                    level VARCHAR(20) DEFAULT 'debutant',
                    language VARCHAR(10) DEFAULT 'fr',
                    tags TEXT,
                    image_url VARCHAR(500),
                    thumbnail_url VARCHAR(500),
                    video_trailer_url VARCHAR(500),
                    instructor_id INT,
                    is_public $booleanType DEFAULT " . ($isPostgreSQL ? 'TRUE' : '1') . ",
                    is_active $booleanType DEFAULT " . ($isPostgreSQL ? 'TRUE' : '1') . ",
                    rating DECIMAL(3,2) DEFAULT 0.00,
                    enrollment_count INT DEFAULT 0,
                    created_at $timestampType DEFAULT " . ($isPostgreSQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
                    updated_at $timestampType DEFAULT " . ($isPostgreSQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . "
                )",
            
            'enrollments' => "
                CREATE TABLE IF NOT EXISTS enrollments (
                    id $autoIncrement,
                    user_id INT NOT NULL,
                    course_id INT NOT NULL,
                    status VARCHAR(20) DEFAULT 'active',
                    progress INT DEFAULT 0,
                    completed_at $timestampType NULL,
                    created_at $timestampType DEFAULT " . ($isPostgreSQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . "
                )",
            
            'assessments' => "
                CREATE TABLE IF NOT EXISTS assessments (
                    id $autoIncrement,
                    establishment_id INT NOT NULL,
                    course_id INT,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    type VARCHAR(50) DEFAULT 'quiz',
                    questions $jsonType DEFAULT " . ($isPostgreSQL ? "'[]'" : "'[]'") . ",
                    settings $jsonType DEFAULT " . ($isPostgreSQL ? "'{}'" : "'{}'") . ",
                    is_active $booleanType DEFAULT " . ($isPostgreSQL ? 'TRUE' : '1') . ",
                    created_at $timestampType DEFAULT " . ($isPostgreSQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
                    updated_at $timestampType DEFAULT " . ($isPostgreSQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . "
                )",
            
            'study_groups' => "
                CREATE TABLE IF NOT EXISTS study_groups (
                    id $autoIncrement,
                    establishment_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    max_members INT DEFAULT 10,
                    is_public $booleanType DEFAULT " . ($isPostgreSQL ? 'TRUE' : '1') . ",
                    created_by INT NOT NULL,
                    created_at $timestampType DEFAULT " . ($isPostgreSQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . "
                )",
            
            'group_members' => "
                CREATE TABLE IF NOT EXISTS group_members (
                    id $autoIncrement,
                    group_id INT NOT NULL,
                    user_id INT NOT NULL,
                    role VARCHAR(20) DEFAULT 'member',
                    joined_at $timestampType DEFAULT " . ($isPostgreSQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . "
                )",
            
            'notifications' => "
                CREATE TABLE IF NOT EXISTS notifications (
                    id $autoIncrement,
                    user_id INT NOT NULL,
                    establishment_id INT NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT,
                    type VARCHAR(50) DEFAULT 'info',
                    is_read $booleanType DEFAULT " . ($isPostgreSQL ? 'FALSE' : '0') . ",
                    data $jsonType DEFAULT " . ($isPostgreSQL ? "'{}'" : "'{}'") . ",
                    created_at $timestampType DEFAULT " . ($isPostgreSQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . "
                )",
            
            'themes' => "
                CREATE TABLE IF NOT EXISTS themes (
                    id $autoIncrement,
                    establishment_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    is_active $booleanType DEFAULT " . ($isPostgreSQL ? 'FALSE' : '0') . ",
                    primary_color VARCHAR(7) DEFAULT '#8B5CF6',
                    secondary_color VARCHAR(7) DEFAULT '#A78BFA',
                    accent_color VARCHAR(7) DEFAULT '#C4B5FD',
                    background_color VARCHAR(7) DEFAULT '#FFFFFF',
                    text_color VARCHAR(7) DEFAULT '#1F2937',
                    font_family VARCHAR(100) DEFAULT 'Inter',
                    font_size VARCHAR(10) DEFAULT '16px',
                    created_at $timestampType DEFAULT " . ($isPostgreSQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . ",
                    updated_at $timestampType DEFAULT " . ($isPostgreSQL ? 'CURRENT_TIMESTAMP' : 'CURRENT_TIMESTAMP') . "
                )"
        ];
        
        $createdTables = 0;
        foreach ($tables as $tableName => $sql) {
            $pdo->exec($sql);
            $createdTables++;
        }
        
        return ['success' => true, 'message' => "$createdTables tables créées avec succès"];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur lors de la création des tables: ' . $e->getMessage()];
    }
}

function createSuperAdmin($config, $appConfig) {
    try {
        $pdo = getDatabaseConnection($config);
        
        // Vérifier si un super admin existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return ['success' => true, 'message' => 'Compte super administrateur déjà existant'];
        }
        
        // Créer l'établissement principal si nécessaire
        $stmt = $pdo->prepare("SELECT id FROM establishments WHERE slug = 'main' LIMIT 1");
        $stmt->execute();
        $establishment = $stmt->fetch();
        
        if (!$establishment) {
            $stmt = $pdo->prepare("
                INSERT INTO establishments (name, slug, description, domain, is_active) 
                VALUES (?, 'main', 'Établissement principal', ?, 1)
            ");
            $stmt->execute([
                $appConfig['app_name'],
                parse_url($appConfig['app_url'], PHP_URL_HOST) ?: 'localhost'
            ]);
            $establishmentId = $pdo->lastInsertId();
        } else {
            $establishmentId = $establishment['id'];
        }
        
        // Créer le compte super administrateur
        $hashedPassword = password_hash($appConfig['admin_password'], PASSWORD_ARGON2ID);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (establishment_id, email, first_name, last_name, password, role, is_active, email_verified_at) 
            VALUES (?, ?, 'Super', 'Administrateur', ?, 'super_admin', 1, ?)
        ");
        
        $stmt->execute([
            $establishmentId,
            $appConfig['admin_email'],
            $hashedPassword,
            date('Y-m-d H:i:s')
        ]);
        
        return ['success' => true, 'message' => 'Compte super administrateur créé'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur lors de la création du super admin: ' . $e->getMessage()];
    }
}

function seedDemoData($config) {
    try {
        $pdo = getDatabaseConnection($config);
        
        // Vérifier si des données de démo existent déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM establishments WHERE slug != 'main'");
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return ['success' => true, 'message' => 'Données de démonstration déjà présentes'];
        }
        
        // Créer des établissements de démonstration
        $establishments = [
            ['name' => 'StacGate Academy', 'slug' => 'stacgate-academy', 'description' => 'École de formation professionnelle', 'domain' => 'stacgate.academy'],
            ['name' => 'TechPro Institute', 'slug' => 'techpro-institute', 'description' => 'Institut de formation technique', 'domain' => 'techpro.institute'],
            ['name' => 'Digital Learning Center', 'slug' => 'digital-learning', 'description' => 'Centre de formation numérique', 'domain' => 'digital-learning.edu']
        ];
        
        $establishmentIds = [];
        foreach ($establishments as $est) {
            $stmt = $pdo->prepare("
                INSERT INTO establishments (name, slug, description, domain, is_active) 
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$est['name'], $est['slug'], $est['description'], $est['domain']]);
            $establishmentIds[] = $pdo->lastInsertId();
        }
        
        // Créer des utilisateurs de démonstration
        $demoUsers = [
            ['email' => 'admin@stacgate.academy', 'first_name' => 'Jean', 'last_name' => 'Administrateur', 'role' => 'admin'],
            ['email' => 'formateur1@stacgate.academy', 'first_name' => 'Marie', 'last_name' => 'Formatrice', 'role' => 'formateur'],
            ['email' => 'formateur2@stacgate.academy', 'first_name' => 'Paul', 'last_name' => 'Formateur', 'role' => 'formateur'],
            ['email' => 'apprenant1@stacgate.academy', 'first_name' => 'Sophie', 'last_name' => 'Apprenante', 'role' => 'apprenant'],
            ['email' => 'apprenant2@stacgate.academy', 'first_name' => 'Lucas', 'last_name' => 'Apprenant', 'role' => 'apprenant']
        ];
        
        $userIds = [];
        foreach ($demoUsers as $user) {
            $stmt = $pdo->prepare("
                INSERT INTO users (establishment_id, email, first_name, last_name, password, role, is_active, email_verified_at) 
                VALUES (?, ?, ?, ?, ?, ?, 1, ?)
            ");
            $stmt->execute([
                $establishmentIds[0], // Premier établissement
                $user['email'],
                $user['first_name'],
                $user['last_name'],
                password_hash('demo123', PASSWORD_ARGON2ID),
                $user['role'],
                date('Y-m-d H:i:s')
            ]);
            $userIds[] = $pdo->lastInsertId();
        }
        
        // Créer des cours de démonstration
        $demoCourses = [
            [
                'title' => 'Introduction au Développement Web',
                'description' => 'Apprenez les bases du développement web avec HTML, CSS et JavaScript',
                'category' => 'web',
                'level' => 'debutant',
                'duration' => 120,
                'instructor_id' => $userIds[1] // Premier formateur
            ],
            [
                'title' => 'React pour Débutants',
                'description' => 'Maîtrisez React et créez des applications web modernes',
                'category' => 'frontend',
                'level' => 'intermediaire',
                'duration' => 180,
                'instructor_id' => $userIds[1]
            ],
            [
                'title' => 'PHP et MySQL',
                'description' => 'Développement backend avec PHP et gestion de base de données',
                'category' => 'backend',
                'level' => 'intermediaire',
                'duration' => 200,
                'instructor_id' => $userIds[2] // Second formateur
            ]
        ];
        
        foreach ($demoCourses as $course) {
            $stmt = $pdo->prepare("
                INSERT INTO courses (establishment_id, title, description, category, level, duration, instructor_id, is_active, is_free) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1)
            ");
            $stmt->execute([
                $establishmentIds[0],
                $course['title'],
                $course['description'],
                $course['category'],
                $course['level'],
                $course['duration'],
                $course['instructor_id']
            ]);
        }
        
        return ['success' => true, 'message' => 'Données de démonstration installées (3 établissements, 5 utilisateurs, 3 cours)'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur lors de l\'installation des données de démo: ' . $e->getMessage()];
    }
}

function getDatabaseConnection($config) {
    switch ($config['type']) {
        case 'sqlite':
            $dsn = "sqlite:" . dirname(__DIR__) . "/database.sqlite";
            return new PDO($dsn, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
        case 'mysql':
            $port = $config['port'] ?: '3306';
            $dsn = "mysql:host={$config['host']};port={$port};dbname={$config['name']};charset={$config['charset']}";
            return new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
        case 'postgresql':
            $port = $config['port'] ?: '5432';
            $dsn = "pgsql:host={$config['host']};port={$port};dbname={$config['name']}";
            return new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
}
?>
<?php
/**
 * Gestionnaire de base de données
 * Abstraction PDO avec support MySQL/PostgreSQL
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $config;
    
    private function __construct() {
        $this->config = DB_CONFIG;
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = $this->getDSN();
            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $this->config['options']);
            $this->initializeTables();
        } catch (PDOException $e) {
            Utils::log("Database connection failed: " . $e->getMessage(), 'ERROR');
            throw new Exception("Erreur de connexion à la base de données");
        }
    }
    
    private function getDSN() {
        switch ($this->config['type']) {
            case 'postgresql':
            case 'pgsql':
                return "pgsql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['name']};";
            case 'mysql':
            default:
                return "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['name']};charset={$this->config['charset']};";
        }
    }
    
    private function initializeTables() {
        global $create_tables_sql;
        
        foreach ($create_tables_sql as $table_name => $sql) {
            try {
                $this->pdo->exec($sql);
            } catch (PDOException $e) {
                Utils::log("Table creation failed for $table_name: " . $e->getMessage(), 'ERROR');
            }
        }
        
        $this->seedDefaultData();
    }
    
    private function seedDefaultData() {
        // Vérifier si des données existent déjà
        $stmt = $this->query("SELECT COUNT(*) as count FROM establishments");
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            $this->seedEstablishments();
            $this->seedUsers();
            $this->seedCourses();
        }
    }
    
    private function seedEstablishments() {
        $establishments = [
            [
                'id' => 1,
                'name' => 'StacGate Academy',
                'slug' => 'stacgate-academy',
                'description' => 'École de formation professionnelle en technologie',
                'logo' => '/logos/stacgate.png',
                'domain' => 'stacgate.academy',
                'is_active' => 1,
                'settings' => json_encode(['theme' => 'violet', 'language' => 'fr'])
            ],
            [
                'id' => 2,
                'name' => 'TechPro Institute',
                'slug' => 'techpro-institute',
                'description' => 'Institut de formation technique avancée',
                'logo' => '/logos/techpro.png',
                'domain' => 'techpro.institute',
                'is_active' => 1,
                'settings' => json_encode(['theme' => 'blue', 'language' => 'fr'])
            ]
        ];
        
        foreach ($establishments as $est) {
            $this->insert('establishments', $est);
        }
    }
    
    private function seedUsers() {
        $users = [
            [
                'establishment_id' => 1,
                'email' => 'admin@stacgate.fr',
                'first_name' => 'Administrateur',
                'last_name' => 'Principal',
                'password' => password_hash('admin123', PASSWORD_ARGON2ID),
                'role' => 'admin',
                'is_active' => 1
            ],
            [
                'establishment_id' => 1,
                'email' => 'formateur@stacgate.fr',
                'first_name' => 'Jean',
                'last_name' => 'Formateur',
                'password' => password_hash('formateur123', PASSWORD_ARGON2ID),
                'role' => 'formateur',
                'is_active' => 1
            ],
            [
                'establishment_id' => 1,
                'email' => 'apprenant@stacgate.fr',
                'first_name' => 'Marie',
                'last_name' => 'Apprenante',
                'password' => password_hash('apprenant123', PASSWORD_ARGON2ID),
                'role' => 'apprenant',
                'is_active' => 1
            ]
        ];
        
        foreach ($users as $user) {
            $this->insert('users', $user);
        }
    }
    
    private function seedCourses() {
        $courses = [
            [
                'establishment_id' => 1,
                'title' => 'Introduction au Développement Web',
                'description' => 'Apprenez les bases du développement web avec HTML, CSS et JavaScript',
                'short_description' => 'Formation complète aux technologies web fondamentales',
                'category' => 'web',
                'type' => 'cours',
                'price' => 0.00,
                'is_free' => 1,
                'duration' => 120,
                'level' => 'debutant',
                'instructor_id' => 2,
                'is_public' => 1,
                'is_active' => 1,
                'tags' => 'html,css,javascript,web'
            ],
            [
                'establishment_id' => 1,
                'title' => 'React Avancé',
                'description' => 'Maîtrisez React avec les hooks, le state management et les patterns avancés',
                'short_description' => 'Formation React pour développeurs expérimentés',
                'category' => 'frontend',
                'type' => 'cours',
                'price' => 99.00,
                'is_free' => 0,
                'duration' => 180,
                'level' => 'avance',
                'instructor_id' => 2,
                'is_public' => 1,
                'is_active' => 1,
                'tags' => 'react,javascript,frontend,hooks'
            ]
        ];
        
        foreach ($courses as $course) {
            $this->insert('courses', $course);
        }
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            Utils::log("Query failed: $sql - " . $e->getMessage(), 'ERROR');
            throw new Exception("Erreur lors de l'exécution de la requête");
        }
    }
    
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            Utils::log("Insert failed: $sql - " . $e->getMessage(), 'ERROR');
            throw new Exception("Erreur lors de l'insertion");
        }
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "$key = :$key";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE $table SET $setClause WHERE $where";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_merge($data, $whereParams));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            Utils::log("Update failed: $sql - " . $e->getMessage(), 'ERROR');
            throw new Exception("Erreur lors de la mise à jour");
        }
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            Utils::log("Delete failed: $sql - " . $e->getMessage(), 'ERROR');
            throw new Exception("Erreur lors de la suppression");
        }
    }
    
    public function select($table, $columns = '*', $where = '', $params = [], $orderBy = '', $limit = '') {
        $sql = "SELECT $columns FROM $table";
        
        if ($where) {
            $sql .= " WHERE $where";
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            Utils::log("Select failed: $sql - " . $e->getMessage(), 'ERROR');
            throw new Exception("Erreur lors de la sélection");
        }
    }
    
    public function selectOne($table, $columns = '*', $where = '', $params = []) {
        $results = $this->select($table, $columns, $where, $params, '', '1');
        return empty($results) ? null : $results[0];
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollBack();
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    public function getPDO() {
        return $this->pdo;
    }
}
?>
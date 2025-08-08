<?php
/**
 * Classe Database - Gestionnaire de base de données avec support MySQL/PostgreSQL
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
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
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
    
    public function getPDO() {
        return $this->pdo;
    }
    
    /**
     * Exécuter une requête SELECT
     */
    public function select($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database select error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Erreur lors de l'exécution de la requête");
        }
    }
    
    /**
     * Exécuter une requête SELECT et retourner une seule ligne
     */
    public function selectOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database selectOne error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Erreur lors de l'exécution de la requête");
        }
    }
    
    /**
     * Insérer des données
     */
    public function insert($table, $data) {
        try {
            $columns = implode(',', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database insert error: " . $e->getMessage() . " Table: " . $table);
            throw new Exception("Erreur lors de l'insertion des données");
        }
    }
    
    /**
     * Mettre à jour des données
     */
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $set = [];
            foreach (array_keys($data) as $key) {
                $set[] = "{$key} = :{$key}";
            }
            $setClause = implode(', ', $set);
            
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_merge($data, $whereParams));
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database update error: " . $e->getMessage() . " Table: " . $table);
            throw new Exception("Erreur lors de la mise à jour des données");
        }
    }
    
    /**
     * Supprimer des données
     */
    public function delete($table, $where, $whereParams = []) {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($whereParams);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database delete error: " . $e->getMessage() . " Table: " . $table);
            throw new Exception("Erreur lors de la suppression des données");
        }
    }
    
    /**
     * Pagination des résultats
     */
    public function paginate($sql, $params = [], $page = 1, $perPage = 20) {
        try {
            // Compter le total
            $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as count_query";
            $stmt = $this->pdo->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];
            
            // Calculer les métadonnées de pagination
            $totalPages = ceil($total / $perPage);
            $offset = ($page - 1) * $perPage;
            
            // Requête paginée
            $paginatedSql = $sql . " LIMIT {$perPage} OFFSET {$offset}";
            $stmt = $this->pdo->prepare($paginatedSql);
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            return [
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ];
        } catch (PDOException $e) {
            error_log("Database paginate error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Erreur lors de la pagination");
        }
    }
    
    /**
     * Insérer avec timestamps automatiques
     */
    public function insertWithTimestamps($table, $data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->insert($table, $data);
    }
    
    /**
     * Mettre à jour avec timestamp automatique
     */
    public function updateWithTimestamp($table, $data, $where, $whereParams = []) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($table, $data, $where, $whereParams);
    }
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
            $stmt = $this->pdo->prepare($sql);
            
            // Combiner les paramètres de données et de condition WHERE
            $allParams = array_merge($data, $whereParams);
            $stmt->execute($allParams);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database update error: " . $e->getMessage() . " Table: " . $table);
            throw new Exception("Erreur lors de la mise à jour des données");
        }
    }
    
    /**
     * Supprimer des données
     */
    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database delete error: " . $e->getMessage() . " Table: " . $table);
            throw new Exception("Erreur lors de la suppression des données");
        }
    }
    
    /**
     * Exécuter une requête custom
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database execute error: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Erreur lors de l'exécution de la requête");
        }
    }
    
    /**
     * Démarrer une transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Valider une transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Annuler une transaction
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * Vérifier si on est dans une transaction
     */
    public function inTransaction() {
        return $this->pdo->inTransaction();
    }
    
    /**
     * Compter les lignes d'une table
     */
    public function count($table, $where = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
        $result = $this->selectOne($sql, $params);
        return (int) $result['count'];
    }
    
    /**
     * Vérifier si un enregistrement existe
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
    
    /**
     * Pagination des résultats
     */
    public function paginate($sql, $params = [], $page = 1, $perPage = 10) {
        // Compter le total
        $countSql = preg_replace('/^SELECT.*?FROM/i', 'SELECT COUNT(*) as total FROM', $sql);
        $total = $this->selectOne($countSql, $params)['total'];
        
        // Calculer offset
        $offset = ($page - 1) * $perPage;
        
        // Ajouter LIMIT et OFFSET selon le type de DB
        if (IS_POSTGRESQL) {
            $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        } else {
            $sql .= " LIMIT {$offset}, {$perPage}";
        }
        
        $data = $this->select($sql, $params);
        
        return [
            'data' => $data,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
            'has_next' => $page < ceil($total / $perPage),
            'has_prev' => $page > 1
        ];
    }
    
    /**
     * Recherche full-text simple
     */
    public function search($table, $columns, $term, $where = '1=1', $params = []) {
        $searchConditions = [];
        
        foreach ($columns as $column) {
            if (IS_POSTGRESQL) {
                $searchConditions[] = "{$column} ILIKE :search_term";
            } else {
                $searchConditions[] = "{$column} LIKE :search_term";
            }
        }
        
        $searchClause = '(' . implode(' OR ', $searchConditions) . ')';
        $sql = "SELECT * FROM {$table} WHERE {$where} AND {$searchClause}";
        
        $params['search_term'] = '%' . $term . '%';
        
        return $this->select($sql, $params);
    }
    
    /**
     * Mise à jour des timestamps automatiquement
     */
    private function addTimestamps($data, $isUpdate = false) {
        if (IS_POSTGRESQL) {
            if (!$isUpdate) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            $data['updated_at'] = date('Y-m-d H:i:s');
        } else {
            // MySQL gère automatiquement avec DEFAULT CURRENT_TIMESTAMP
            if (!$isUpdate) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
        }
        
        return $data;
    }
    
    /**
     * Insert avec timestamps automatiques
     */
    public function insertWithTimestamps($table, $data) {
        $data = $this->addTimestamps($data, false);
        return $this->insert($table, $data);
    }
    
    /**
     * Update avec timestamps automatiques
     */
    public function updateWithTimestamps($table, $data, $where, $whereParams = []) {
        $data = $this->addTimestamps($data, true);
        return $this->update($table, $data, $where, $whereParams);
    }
}
?>
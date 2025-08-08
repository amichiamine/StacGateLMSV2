<?php
/**
 * Service de gestion du centre d'aide
 */

class HelpService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtenir les articles d'aide par établissement
     */
    public function getHelpContentsByEstablishment($establishmentId, $role = null, $page = 1, $perPage = 20, $filters = []) {
        $whereClause = "hc.establishment_id = :establishment_id AND hc.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
        $params = ['establishment_id' => $establishmentId];
        
        if ($role) {
            $whereClause .= " AND (hc.target_roles LIKE :role OR hc.target_roles = 'all')";
            $params['role'] = "%{$role}%";
        }
        
        if (!empty($filters['category'])) {
            $whereClause .= " AND hc.category = :category";
            $params['category'] = $filters['category'];
        }
        
        if (!empty($filters['language'])) {
            $whereClause .= " AND hc.language = :language";
            $params['language'] = $filters['language'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (hc.title LIKE :search OR hc.content LIKE :search OR hc.tags LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }
        
        $sql = "SELECT hc.*, u.first_name as author_first_name, u.last_name as author_last_name
                FROM help_contents hc
                LEFT JOIN users u ON hc.author_id = u.id
                WHERE {$whereClause}
                ORDER BY hc.sort_order ASC, hc.created_at DESC";
        
        return $this->db->paginate($sql, $params, $page, $perPage);
    }
    
    /**
     * Obtenir un article d'aide par ID
     */
    public function getHelpContentById($id) {
        return $this->db->selectOne(
            "SELECT hc.*, u.first_name as author_first_name, u.last_name as author_last_name
             FROM help_contents hc
             LEFT JOIN users u ON hc.author_id = u.id
             WHERE hc.id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Créer un nouvel article d'aide
     */
    public function createHelpContent($data) {
        try {
            $validator = Validator::make($data, [
                'establishment_id' => 'required|integer',
                'title' => 'required|max:255',
                'content' => 'required',
                'category' => 'required|max:100',
                'target_roles' => 'required',
                'language' => 'max:10',
                'author_id' => 'required|integer'
            ]);
            
            if (!$validator->validate()) {
                throw new ValidationException($validator->getErrors());
            }
            
            $validatedData = $validator->getValidatedData();
            
            // Valeurs par défaut
            $validatedData['language'] = $validatedData['language'] ?? 'fr';
            $validatedData['is_active'] = $validatedData['is_active'] ?? true;
            $validatedData['sort_order'] = $validatedData['sort_order'] ?? 0;
            $validatedData['view_count'] = 0;
            
            // Traiter les tags
            if (isset($data['tags'])) {
                if (is_array($data['tags'])) {
                    $validatedData['tags'] = implode(',', $data['tags']);
                } else {
                    $validatedData['tags'] = $data['tags'];
                }
            }
            
            // Traiter les rôles cibles
            if (is_array($validatedData['target_roles'])) {
                $validatedData['target_roles'] = implode(',', $validatedData['target_roles']);
            }
            
            $contentId = $this->db->insertWithTimestamps('help_contents', $validatedData);
            
            return $this->getHelpContentById($contentId);
            
        } catch (Exception $e) {
            Utils::log("Help content creation error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Mettre à jour un article d'aide
     */
    public function updateHelpContent($id, $data) {
        try {
            unset($data['id'], $data['created_at'], $data['view_count']);
            
            // Traiter les tags
            if (isset($data['tags']) && is_array($data['tags'])) {
                $data['tags'] = implode(',', $data['tags']);
            }
            
            // Traiter les rôles cibles
            if (isset($data['target_roles']) && is_array($data['target_roles'])) {
                $data['target_roles'] = implode(',', $data['target_roles']);
            }
            
            $this->db->updateWithTimestamps('help_contents', $data, 'id = :id', ['id' => $id]);
            
            return $this->getHelpContentById($id);
            
        } catch (Exception $e) {
            Utils::log("Help content update error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Supprimer un article d'aide
     */
    public function deleteHelpContent($id) {
        try {
            return $this->db->delete('help_contents', 'id = :id', ['id' => $id]);
            
        } catch (Exception $e) {
            Utils::log("Help content deletion error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Rechercher dans les articles d'aide
     */
    public function searchHelpContent($establishmentId, $searchTerm, $role = null, $page = 1, $perPage = 20) {
        $whereClause = "hc.establishment_id = :establishment_id AND hc.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1') . 
                      " AND (hc.title LIKE :search OR hc.content LIKE :search OR hc.tags LIKE :search)";
        $params = [
            'establishment_id' => $establishmentId,
            'search' => "%{$searchTerm}%"
        ];
        
        if ($role) {
            $whereClause .= " AND (hc.target_roles LIKE :role OR hc.target_roles = 'all')";
            $params['role'] = "%{$role}%";
        }
        
        $sql = "SELECT hc.*, u.first_name as author_first_name, u.last_name as author_last_name,
                       (CASE 
                        WHEN hc.title LIKE :search_exact THEN 3
                        WHEN hc.title LIKE :search THEN 2
                        WHEN hc.content LIKE :search THEN 1
                        ELSE 0
                       END) as relevance_score
                FROM help_contents hc
                LEFT JOIN users u ON hc.author_id = u.id
                WHERE {$whereClause}
                ORDER BY relevance_score DESC, hc.view_count DESC, hc.created_at DESC";
        
        $params['search_exact'] = "%{$searchTerm}%";
        
        return $this->db->paginate($sql, $params, $page, $perPage);
    }
    
    /**
     * Incrémenter le compteur de vues
     */
    public function incrementViewCount($id) {
        try {
            $this->db->execute(
                "UPDATE help_contents SET view_count = view_count + 1 WHERE id = :id",
                ['id' => $id]
            );
            
            return true;
            
        } catch (Exception $e) {
            Utils::log("Help content view increment error: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Obtenir les catégories disponibles
     */
    public function getCategories($establishmentId) {
        return $this->db->select(
            "SELECT DISTINCT category, COUNT(*) as article_count
             FROM help_contents
             WHERE establishment_id = :establishment_id AND is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1') . "
             GROUP BY category
             ORDER BY category ASC",
            ['establishment_id' => $establishmentId]
        );
    }
    
    /**
     * Obtenir les articles les plus populaires
     */
    public function getPopularContent($establishmentId, $limit = 10, $role = null) {
        $whereClause = "hc.establishment_id = :establishment_id AND hc.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
        $params = ['establishment_id' => $establishmentId];
        
        if ($role) {
            $whereClause .= " AND (hc.target_roles LIKE :role OR hc.target_roles = 'all')";
            $params['role'] = "%{$role}%";
        }
        
        return $this->db->select(
            "SELECT hc.id, hc.title, hc.category, hc.view_count, hc.created_at
             FROM help_contents hc
             WHERE {$whereClause}
             ORDER BY hc.view_count DESC, hc.created_at DESC
             LIMIT {$limit}",
            $params
        );
    }
    
    /**
     * Obtenir les articles récents
     */
    public function getRecentContent($establishmentId, $limit = 10, $role = null) {
        $whereClause = "hc.establishment_id = :establishment_id AND hc.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
        $params = ['establishment_id' => $establishmentId];
        
        if ($role) {
            $whereClause .= " AND (hc.target_roles LIKE :role OR hc.target_roles = 'all')";
            $params['role'] = "%{$role}%";
        }
        
        return $this->db->select(
            "SELECT hc.id, hc.title, hc.category, hc.view_count, hc.created_at,
                    u.first_name as author_first_name, u.last_name as author_last_name
             FROM help_contents hc
             LEFT JOIN users u ON hc.author_id = u.id
             WHERE {$whereClause}
             ORDER BY hc.created_at DESC
             LIMIT {$limit}",
            $params
        );
    }
    
    /**
     * Obtenir les articles par catégorie
     */
    public function getContentByCategory($establishmentId, $category, $role = null, $page = 1, $perPage = 20) {
        $whereClause = "hc.establishment_id = :establishment_id AND hc.category = :category AND hc.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
        $params = [
            'establishment_id' => $establishmentId,
            'category' => $category
        ];
        
        if ($role) {
            $whereClause .= " AND (hc.target_roles LIKE :role OR hc.target_roles = 'all')";
            $params['role'] = "%{$role}%";
        }
        
        $sql = "SELECT hc.*, u.first_name as author_first_name, u.last_name as author_last_name
                FROM help_contents hc
                LEFT JOIN users u ON hc.author_id = u.id
                WHERE {$whereClause}
                ORDER BY hc.sort_order ASC, hc.created_at DESC";
        
        return $this->db->paginate($sql, $params, $page, $perPage);
    }
    
    /**
     * Obtenir les FAQ (articles avec category='faq')
     */
    public function getFAQ($establishmentId, $role = null, $limit = 20) {
        $whereClause = "hc.establishment_id = :establishment_id AND hc.category = 'faq' AND hc.is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1');
        $params = ['establishment_id' => $establishmentId];
        
        if ($role) {
            $whereClause .= " AND (hc.target_roles LIKE :role OR hc.target_roles = 'all')";
            $params['role'] = "%{$role}%";
        }
        
        return $this->db->select(
            "SELECT hc.id, hc.title, hc.content, hc.view_count, hc.sort_order
             FROM help_contents hc
             WHERE {$whereClause}
             ORDER BY hc.sort_order ASC, hc.view_count DESC
             LIMIT {$limit}",
            $params
        );
    }
    
    /**
     * Réorganiser l'ordre des articles
     */
    public function reorderContent($establishmentId, $categoryOrders) {
        try {
            $this->db->beginTransaction();
            
            foreach ($categoryOrders as $category => $articleIds) {
                foreach ($articleIds as $order => $articleId) {
                    $this->db->update(
                        'help_contents',
                        ['sort_order' => $order],
                        'id = :id AND establishment_id = :establishment_id AND category = :category',
                        [
                            'id' => $articleId,
                            'establishment_id' => $establishmentId,
                            'category' => $category
                        ]
                    );
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            Utils::log("Help content reorder error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir les statistiques du centre d'aide
     */
    public function getHelpStats($establishmentId) {
        $stats = $this->db->selectOne(
            "SELECT COUNT(*) as total_articles,
                    COUNT(CASE WHEN is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1') . " THEN 1 END) as active_articles,
                    SUM(view_count) as total_views,
                    AVG(view_count) as average_views,
                    COUNT(DISTINCT category) as categories_count
             FROM help_contents
             WHERE establishment_id = :establishment_id",
            ['establishment_id' => $establishmentId]
        );
        
        // Articles par catégorie
        $categoryStats = $this->db->select(
            "SELECT category, 
                    COUNT(*) as article_count,
                    SUM(view_count) as total_views
             FROM help_contents
             WHERE establishment_id = :establishment_id
             GROUP BY category
             ORDER BY article_count DESC",
            ['establishment_id' => $establishmentId]
        );
        
        // Recherches populaires (si on avait un log des recherches)
        $popularSearches = []; // À implémenter si nécessaire
        
        return [
            'total_articles' => (int) $stats['total_articles'],
            'active_articles' => (int) $stats['active_articles'],
            'total_views' => (int) $stats['total_views'],
            'average_views' => round((float) $stats['average_views'], 2),
            'categories_count' => (int) $stats['categories_count'],
            'category_distribution' => $categoryStats,
            'popular_searches' => $popularSearches
        ];
    }
    
    /**
     * Dupliquer un article vers un autre établissement
     */
    public function duplicateToEstablishment($contentId, $targetEstablishmentId, $authorId) {
        try {
            $originalContent = $this->getHelpContentById($contentId);
            if (!$originalContent) {
                throw new Exception("Article introuvable");
            }
            
            // Créer une copie
            $duplicateData = $originalContent;
            unset($duplicateData['id'], $duplicateData['created_at'], $duplicateData['updated_at']);
            unset($duplicateData['author_first_name'], $duplicateData['author_last_name']);
            
            $duplicateData['establishment_id'] = $targetEstablishmentId;
            $duplicateData['author_id'] = $authorId;
            $duplicateData['title'] = '[Copie] ' . $duplicateData['title'];
            
            return $this->createHelpContent($duplicateData);
            
        } catch (Exception $e) {
            Utils::log("Help content duplication error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
}
?>
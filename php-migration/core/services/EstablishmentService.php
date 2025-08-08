<?php
/**
 * Service de gestion des établissements
 */

class EstablishmentService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtenir tous les établissements
     */
    public function getAllEstablishments($activeOnly = true) {
        $whereClause = $activeOnly ? "is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1') : "1 = 1";
        
        return $this->db->select(
            "SELECT * FROM establishments WHERE {$whereClause} ORDER BY name ASC"
        );
    }
    
    /**
     * Obtenir un établissement par ID
     */
    public function getEstablishmentById($id) {
        return $this->db->selectOne(
            "SELECT * FROM establishments WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Obtenir un établissement par slug
     */
    public function getEstablishmentBySlug($slug) {
        return $this->db->selectOne(
            "SELECT * FROM establishments WHERE slug = :slug AND is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1'),
            ['slug' => $slug]
        );
    }
    
    /**
     * Créer un nouvel établissement
     */
    public function createEstablishment($data) {
        try {
            // Valider les données
            $validator = Validator::make($data, [
                'name' => 'required|max:255',
                'slug' => 'required|max:100|unique:establishments,slug',
                'description' => 'max:1000',
                'logo' => 'url',
                'domain' => 'max:255'
            ]);
            
            if (!$validator->validate()) {
                throw new ValidationException($validator->getErrors());
            }
            
            $validatedData = $validator->getValidatedData();
            
            // Générer le slug si pas fourni
            if (empty($validatedData['slug'])) {
                $validatedData['slug'] = $this->generateUniqueSlug($validatedData['name']);
            }
            
            // Paramètres par défaut
            $validatedData['is_active'] = $validatedData['is_active'] ?? true;
            $validatedData['settings'] = json_encode($validatedData['settings'] ?? []);
            
            // Insérer l'établissement
            $establishmentId = $this->db->insertWithTimestamps('establishments', $validatedData);
            
            // Créer le thème par défaut
            $this->createDefaultTheme($establishmentId);
            
            return $this->getEstablishmentById($establishmentId);
            
        } catch (Exception $e) {
            Utils::log("Establishment creation error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Mettre à jour un établissement
     */
    public function updateEstablishment($id, $data) {
        try {
            // Enlever les champs non modifiables
            unset($data['id'], $data['created_at']);
            
            // Valider le slug si fourni
            if (isset($data['slug'])) {
                $validator = Validator::make(['slug' => $data['slug']], [
                    'slug' => "unique:establishments,slug,{$id}"
                ]);
                
                if (!$validator->validate()) {
                    throw new ValidationException($validator->getErrors());
                }
            }
            
            // Encoder les settings en JSON si fournis
            if (isset($data['settings']) && is_array($data['settings'])) {
                $data['settings'] = json_encode($data['settings']);
            }
            
            $this->db->updateWithTimestamps('establishments', $data, 'id = :id', ['id' => $id]);
            
            return $this->getEstablishmentById($id);
            
        } catch (Exception $e) {
            Utils::log("Establishment update error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Supprimer un établissement
     */
    public function deleteEstablishment($id) {
        try {
            // Vérifier qu'il y a des utilisateurs
            $userCount = $this->db->count('users', 'establishment_id = :id', ['id' => $id]);
            if ($userCount > 0) {
                throw new Exception("Impossible de supprimer un établissement avec des utilisateurs");
            }
            
            return $this->db->delete('establishments', 'id = :id', ['id' => $id]);
            
        } catch (Exception $e) {
            Utils::log("Establishment deletion error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir les thèmes d'un établissement
     */
    public function getThemes($establishmentId) {
        return $this->db->select(
            "SELECT * FROM themes WHERE establishment_id = :establishment_id ORDER BY is_active DESC, name ASC",
            ['establishment_id' => $establishmentId]
        );
    }
    
    /**
     * Obtenir le thème actif d'un établissement
     */
    public function getActiveTheme($establishmentId) {
        return $this->db->selectOne(
            "SELECT * FROM themes WHERE establishment_id = :establishment_id AND is_active = " . (IS_POSTGRESQL ? 'TRUE' : '1'),
            ['establishment_id' => $establishmentId]
        );
    }
    
    /**
     * Créer un nouveau thème
     */
    public function createTheme($establishmentId, $themeData) {
        try {
            $validator = Validator::make($themeData, [
                'name' => 'required|max:255',
                'primary_color' => 'regex:/^#[0-9A-Fa-f]{6}$/',
                'secondary_color' => 'regex:/^#[0-9A-Fa-f]{6}$/',
                'accent_color' => 'regex:/^#[0-9A-Fa-f]{6}$/',
                'background_color' => 'regex:/^#[0-9A-Fa-f]{6}$/',
                'text_color' => 'regex:/^#[0-9A-Fa-f]{6}$/',
                'font_family' => 'max:100',
                'font_size' => 'max:10'
            ]);
            
            if (!$validator->validate()) {
                throw new ValidationException($validator->getErrors());
            }
            
            $validatedData = $validator->getValidatedData();
            $validatedData['establishment_id'] = $establishmentId;
            $validatedData['is_active'] = false; // Nouveau thème non actif par défaut
            
            $themeId = $this->db->insertWithTimestamps('themes', $validatedData);
            
            return $this->db->selectOne("SELECT * FROM themes WHERE id = :id", ['id' => $themeId]);
            
        } catch (Exception $e) {
            Utils::log("Theme creation error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Activer un thème
     */
    public function activateTheme($themeId, $establishmentId) {
        try {
            $this->db->beginTransaction();
            
            // Désactiver tous les thèmes de l'établissement
            $this->db->update(
                'themes',
                ['is_active' => IS_POSTGRESQL ? false : 0],
                'establishment_id = :establishment_id',
                ['establishment_id' => $establishmentId]
            );
            
            // Activer le thème sélectionné
            $this->db->update(
                'themes',
                ['is_active' => IS_POSTGRESQL ? true : 1],
                'id = :id AND establishment_id = :establishment_id',
                ['id' => $themeId, 'establishment_id' => $establishmentId]
            );
            
            $this->db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            Utils::log("Theme activation error: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtenir les contenus personnalisables d'un établissement
     */
    public function getCustomizableContents($establishmentId) {
        // Note: Cette table sera créée dans la prochaine étape
        return [];
    }
    
    /**
     * Obtenir les éléments de menu d'un établissement
     */
    public function getMenuItems($establishmentId) {
        // Note: Cette table sera créée dans la prochaine étape
        return [];
    }
    
    /**
     * Obtenir les statistiques d'un établissement
     */
    public function getEstablishmentStats($establishmentId) {
        // Nombre d'utilisateurs
        $userCount = $this->db->count('users', 'establishment_id = :id', ['id' => $establishmentId]);
        
        // Nombre de cours
        $courseCount = $this->db->count('courses', 'establishment_id = :id', ['id' => $establishmentId]);
        
        // Nombre d'inscriptions
        $enrollmentCount = $this->db->selectOne(
            "SELECT COUNT(uc.id) as count 
             FROM user_courses uc 
             JOIN courses c ON uc.course_id = c.id 
             WHERE c.establishment_id = :id",
            ['id' => $establishmentId]
        )['count'] ?? 0;
        
        // Utilisateurs actifs ce mois
        $activeUsers = $this->db->count(
            'users',
            'establishment_id = :id AND last_login_at > :date',
            [
                'id' => $establishmentId,
                'date' => date('Y-m-d H:i:s', strtotime('-30 days'))
            ]
        );
        
        return [
            'users' => $userCount,
            'courses' => $courseCount,
            'enrollments' => $enrollmentCount,
            'active_users' => $activeUsers
        ];
    }
    
    /**
     * Générer un slug unique
     */
    private function generateUniqueSlug($name) {
        $baseSlug = Utils::generateSlug($name);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->db->exists('establishments', 'slug = :slug', ['slug' => $slug])) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Créer le thème par défaut pour un nouvel établissement
     */
    private function createDefaultTheme($establishmentId) {
        $defaultTheme = [
            'establishment_id' => $establishmentId,
            'name' => 'Thème par défaut',
            'is_active' => true,
            'primary_color' => DEFAULT_THEME_COLORS['primary'],
            'secondary_color' => DEFAULT_THEME_COLORS['secondary'],
            'accent_color' => DEFAULT_THEME_COLORS['accent'],
            'background_color' => DEFAULT_THEME_COLORS['background'],
            'text_color' => DEFAULT_THEME_COLORS['text'],
            'font_family' => 'Inter',
            'font_size' => '16px'
        ];
        
        $this->db->insertWithTimestamps('themes', $defaultTheme);
    }
}
?>
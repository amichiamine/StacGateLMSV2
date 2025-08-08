<?php

class ThemeService {
    private $database;
    private $defaultThemes = [
        'glassmorphism-blue' => [
            'name' => 'Glassmorphism Bleu',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#8B5CF6',
            'accent_color' => '#06B6D4',
            'background_gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'glass_opacity' => '0.1',
            'blur_strength' => '10px',
            'border_radius' => '16px'
        ],
        'glassmorphism-purple' => [
            'name' => 'Glassmorphism Violet',
            'primary_color' => '#8B5CF6',
            'secondary_color' => '#EC4899',
            'accent_color' => '#F59E0B',
            'background_gradient' => 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
            'glass_opacity' => '0.15',
            'blur_strength' => '12px',
            'border_radius' => '20px'
        ],
        'glassmorphism-green' => [
            'name' => 'Glassmorphism Vert',
            'primary_color' => '#10B981',
            'secondary_color' => '#059669',
            'accent_color' => '#F59E0B',
            'background_gradient' => 'linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%)',
            'glass_opacity' => '0.12',
            'blur_strength' => '8px',
            'border_radius' => '14px'
        ]
    ];

    public function __construct($database) {
        $this->database = $database;
    }

    /**
     * Obtenir le thème actif d'un établissement
     */
    public function getActiveTheme($establishmentId) {
        $theme = $this->database->findOne('establishment_themes', [
            'establishment_id' => $establishmentId,
            'is_active' => true
        ]);

        if (!$theme) {
            // Retourner le thème par défaut
            return $this->getDefaultTheme('glassmorphism-blue');
        }

        return $theme;
    }

    /**
     * Obtenir un thème par défaut
     */
    public function getDefaultTheme($themeKey = 'glassmorphism-blue') {
        $defaultTheme = $this->defaultThemes[$themeKey] ?? $this->defaultThemes['glassmorphism-blue'];
        
        return array_merge($defaultTheme, [
            'id' => 'default_' . $themeKey,
            'establishment_id' => null,
            'is_default' => true,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Créer un thème personnalisé
     */
    public function createCustomTheme($establishmentId, $themeData, $createdBy) {
        $themeId = uniqid('theme_');
        
        // Désactiver les thèmes existants
        $this->database->update('establishment_themes', [
            'is_active' => false
        ], ['establishment_id' => $establishmentId]);

        $theme = [
            'id' => $themeId,
            'establishment_id' => $establishmentId,
            'name' => $themeData['name'],
            'primary_color' => $themeData['primary_color'],
            'secondary_color' => $themeData['secondary_color'],
            'accent_color' => $themeData['accent_color'],
            'background_gradient' => $themeData['background_gradient'],
            'glass_opacity' => $themeData['glass_opacity'] ?? '0.1',
            'blur_strength' => $themeData['blur_strength'] ?? '10px',
            'border_radius' => $themeData['border_radius'] ?? '16px',
            'custom_css' => $themeData['custom_css'] ?? '',
            'is_active' => true,
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->database->insert('establishment_themes', $theme);
        
        // Générer le fichier CSS
        $this->generateThemeCSS($theme);
        
        return $theme;
    }

    /**
     * Mettre à jour un thème
     */
    public function updateTheme($themeId, $themeData) {
        $theme = $this->database->findOne('establishment_themes', ['id' => $themeId]);
        
        if (!$theme) {
            throw new Exception('Thème non trouvé');
        }

        $updatedTheme = array_merge($theme, $themeData, [
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $this->database->update('establishment_themes', $updatedTheme, ['id' => $themeId]);
        
        // Régénérer le CSS
        $this->generateThemeCSS($updatedTheme);
        
        return $updatedTheme;
    }

    /**
     * Activer un thème
     */
    public function activateTheme($themeId, $establishmentId) {
        // Désactiver tous les thèmes de l'établissement
        $this->database->update('establishment_themes', [
            'is_active' => false
        ], ['establishment_id' => $establishmentId]);

        // Activer le thème sélectionné
        $result = $this->database->update('establishment_themes', [
            'is_active' => true
        ], ['id' => $themeId, 'establishment_id' => $establishmentId]);

        if ($result) {
            $theme = $this->database->findOne('establishment_themes', ['id' => $themeId]);
            $this->generateThemeCSS($theme);
        }

        return $result;
    }

    /**
     * Obtenir tous les thèmes disponibles pour un établissement
     */
    public function getAvailableThemes($establishmentId) {
        $customThemes = $this->database->findAll('establishment_themes', [
            'establishment_id' => $establishmentId
        ], [
            'order_by' => 'created_at DESC'
        ]);

        $defaultThemes = [];
        foreach ($this->defaultThemes as $key => $theme) {
            $defaultThemes[] = $this->getDefaultTheme($key);
        }

        return [
            'custom' => $customThemes,
            'default' => $defaultThemes
        ];
    }

    /**
     * Dupliquer un thème
     */
    public function duplicateTheme($themeId, $newName, $establishmentId, $createdBy) {
        $originalTheme = $this->database->findOne('establishment_themes', ['id' => $themeId]);
        
        if (!$originalTheme) {
            throw new Exception('Thème original non trouvé');
        }

        $newThemeId = uniqid('theme_');
        $duplicatedTheme = $originalTheme;
        unset($duplicatedTheme['id']);
        
        $duplicatedTheme = array_merge($duplicatedTheme, [
            'id' => $newThemeId,
            'name' => $newName,
            'establishment_id' => $establishmentId,
            'is_active' => false,
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->database->insert('establishment_themes', $duplicatedTheme);
        
        return $duplicatedTheme;
    }

    /**
     * Générer le fichier CSS pour un thème
     */
    public function generateThemeCSS($theme) {
        $establishmentId = $theme['establishment_id'] ?? 'default';
        $cssContent = $this->buildThemeCSS($theme);
        
        $cssDir = "assets/css/themes/";
        if (!is_dir($cssDir)) {
            mkdir($cssDir, 0755, true);
        }
        
        $cssFile = $cssDir . "theme-{$establishmentId}.css";
        file_put_contents($cssFile, $cssContent);
        
        return $cssFile;
    }

    /**
     * Construire le contenu CSS du thème
     */
    private function buildThemeCSS($theme) {
        $css = "/* Thème généré: {$theme['name']} */\n\n";
        
        // Variables CSS
        $css .= ":root {\n";
        $css .= "  --primary-color: {$theme['primary_color']};\n";
        $css .= "  --secondary-color: {$theme['secondary_color']};\n";
        $css .= "  --accent-color: {$theme['accent_color']};\n";
        $css .= "  --glass-opacity: {$theme['glass_opacity']};\n";
        $css .= "  --blur-strength: {$theme['blur_strength']};\n";
        $css .= "  --border-radius: {$theme['border_radius']};\n";
        $css .= "}\n\n";

        // Arrière-plan principal
        $css .= "body {\n";
        $css .= "  background: {$theme['background_gradient']};\n";
        $css .= "  background-attachment: fixed;\n";
        $css .= "}\n\n";

        // Effet glass morphism
        $css .= ".glass-card {\n";
        $css .= "  background: rgba(255, 255, 255, var(--glass-opacity));\n";
        $css .= "  backdrop-filter: blur(var(--blur-strength));\n";
        $css .= "  border-radius: var(--border-radius);\n";
        $css .= "  border: 1px solid rgba(255, 255, 255, 0.2);\n";
        $css .= "  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);\n";
        $css .= "}\n\n";

        // Boutons primaires
        $css .= ".btn-primary {\n";
        $css .= "  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));\n";
        $css .= "  border: none;\n";
        $css .= "  border-radius: var(--border-radius);\n";
        $css .= "  color: white;\n";
        $css .= "  padding: 12px 24px;\n";
        $css .= "  font-weight: 600;\n";
        $css .= "  transition: all 0.3s ease;\n";
        $css .= "}\n\n";

        $css .= ".btn-primary:hover {\n";
        $css .= "  transform: translateY(-2px);\n";
        $css .= "  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);\n";
        $css .= "}\n\n";

        // Navigation
        $css .= ".navbar {\n";
        $css .= "  background: rgba(255, 255, 255, 0.1);\n";
        $css .= "  backdrop-filter: blur(20px);\n";
        $css .= "  border-bottom: 1px solid rgba(255, 255, 255, 0.1);\n";
        $css .= "}\n\n";

        // Cartes de contenu
        $css .= ".content-card {\n";
        $css .= "  background: rgba(255, 255, 255, var(--glass-opacity));\n";
        $css .= "  backdrop-filter: blur(var(--blur-strength));\n";
        $css .= "  border-radius: var(--border-radius);\n";
        $css .= "  border: 1px solid rgba(255, 255, 255, 0.2);\n";
        $css .= "  transition: all 0.3s ease;\n";
        $css .= "}\n\n";

        $css .= ".content-card:hover {\n";
        $css .= "  transform: translateY(-5px);\n";
        $css .= "  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);\n";
        $css .= "}\n\n";

        // CSS personnalisé
        if (!empty($theme['custom_css'])) {
            $css .= "\n/* CSS personnalisé */\n";
            $css .= $theme['custom_css'];
        }

        return $css;
    }

    /**
     * Exporter un thème
     */
    public function exportTheme($themeId) {
        $theme = $this->database->findOne('establishment_themes', ['id' => $themeId]);
        
        if (!$theme) {
            throw new Exception('Thème non trouvé');
        }

        // Supprimer les champs spécifiques à l'établissement
        unset($theme['id'], $theme['establishment_id'], $theme['is_active'], $theme['created_at'], $theme['updated_at']);
        
        return [
            'version' => '1.0',
            'exported_at' => date('Y-m-d H:i:s'),
            'theme' => $theme
        ];
    }

    /**
     * Importer un thème
     */
    public function importTheme($themeData, $establishmentId, $userId) {
        if (!isset($themeData['theme'])) {
            throw new Exception('Format de thème invalide');
        }

        $theme = $themeData['theme'];
        $theme['name'] = $theme['name'] . ' (Importé)';
        
        return $this->createCustomTheme($establishmentId, $theme, $userId);
    }

    /**
     * Obtenir l'aperçu d'un thème
     */
    public function getThemePreview($themeData) {
        $previewHTML = '
        <div class="theme-preview" style="background: ' . $themeData['background_gradient'] . '; padding: 20px; border-radius: 12px;">
            <div style="background: rgba(255, 255, 255, ' . $themeData['glass_opacity'] . '); 
                        backdrop-filter: blur(' . $themeData['blur_strength'] . '); 
                        border-radius: ' . $themeData['border_radius'] . '; 
                        padding: 16px; 
                        border: 1px solid rgba(255, 255, 255, 0.2);">
                <h3 style="color: ' . $themeData['primary_color'] . '; margin: 0 0 10px 0;">Aperçu du thème</h3>
                <button style="background: linear-gradient(135deg, ' . $themeData['primary_color'] . ', ' . $themeData['secondary_color'] . '); 
                               border: none; 
                               border-radius: ' . $themeData['border_radius'] . '; 
                               color: white; 
                               padding: 8px 16px;">
                    Bouton exemple
                </button>
                <p style="color: #666; margin: 10px 0 0 0;">Exemple de texte avec le thème</p>
            </div>
        </div>';

        return $previewHTML;
    }
}
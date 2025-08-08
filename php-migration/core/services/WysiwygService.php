<?php

class WysiwygService {
    private $database;
    private $allowedTags = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'p', 'div', 'span', 'br', 'hr',
        'strong', 'b', 'em', 'i', 'u', 'strike',
        'ul', 'ol', 'li',
        'a', 'img', 'video', 'audio',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'blockquote', 'code', 'pre'
    ];

    public function __construct($database) {
        $this->database = $database;
    }

    /**
     * Nettoyer et valider le contenu HTML
     */
    public function sanitizeContent($html) {
        // Supprimer les balises script et style
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);
        
        // Nettoyer les attributs dangereux
        $html = preg_replace('/on\w+="[^"]*"/i', '', $html);
        $html = preg_replace('/javascript:/i', '', $html);
        
        // Valider les balises autorisées
        $allowedTagsString = implode('|', $this->allowedTags);
        $html = preg_replace('/<(?!\/?(' . $allowedTagsString . ')\b)[^>]*>/i', '', $html);
        
        return trim($html);
    }

    /**
     * Créer un composant réutilisable
     */
    public function createComponent($name, $content, $properties, $establishmentId, $createdBy) {
        $componentId = uniqid('comp_');
        
        $component = [
            'id' => $componentId,
            'name' => $name,
            'content' => $this->sanitizeContent($content),
            'properties' => json_encode($properties),
            'establishment_id' => $establishmentId,
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s'),
            'is_active' => true
        ];

        $this->database->insert('content_components', $component);
        
        return $component;
    }

    /**
     * Obtenir tous les composants disponibles
     */
    public function getComponents($establishmentId) {
        return $this->database->findAll('content_components', [
            'establishment_id' => $establishmentId,
            'is_active' => true
        ], [
            'order_by' => 'name ASC'
        ]);
    }

    /**
     * Mettre à jour un composant
     */
    public function updateComponent($componentId, $content, $properties) {
        return $this->database->update('content_components', [
            'content' => $this->sanitizeContent($content),
            'properties' => json_encode($properties),
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $componentId]);
    }

    /**
     * Traiter les médias uploadés
     */
    public function handleMediaUpload($file, $establishmentId, $userId) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'audio/mp3'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Type de fichier non autorisé');
        }

        if ($file['size'] > $maxSize) {
            throw new Exception('Fichier trop volumineux (max 10MB)');
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('media_') . '.' . $extension;
        $uploadPath = "uploads/{$establishmentId}/media/";
        
        // Créer le dossier si nécessaire
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $fullPath = $uploadPath . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            // Enregistrer en base
            $mediaId = uniqid('media_');
            $media = [
                'id' => $mediaId,
                'filename' => $filename,
                'original_name' => $file['name'],
                'file_path' => $fullPath,
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'establishment_id' => $establishmentId,
                'uploaded_by' => $userId,
                'uploaded_at' => date('Y-m-d H:i:s')
            ];

            $this->database->insert('media_files', $media);
            
            return [
                'id' => $mediaId,
                'url' => '/' . $fullPath,
                'name' => $file['name'],
                'type' => $file['type'],
                'size' => $file['size']
            ];
        }

        throw new Exception('Erreur lors de l\'upload du fichier');
    }

    /**
     * Obtenir la galerie de médias
     */
    public function getMediaGallery($establishmentId, $type = null) {
        $conditions = ['establishment_id' => $establishmentId];
        
        if ($type) {
            $conditions['mime_type LIKE'] = $type . '%';
        }

        return $this->database->findAll('media_files', $conditions, [
            'order_by' => 'uploaded_at DESC'
        ]);
    }

    /**
     * Générer un aperçu du contenu
     */
    public function generatePreview($content, $template = 'default') {
        $templates = [
            'default' => '<div class="preview-container">{content}</div>',
            'card' => '<div class="card glass-card"><div class="card-body">{content}</div></div>',
            'article' => '<article class="content-article">{content}</article>'
        ];

        $templateHtml = $templates[$template] ?? $templates['default'];
        $processedContent = $this->processContentBlocks($content);
        
        return str_replace('{content}', $processedContent, $templateHtml);
    }

    /**
     * Traiter les blocs de contenu spéciaux
     */
    private function processContentBlocks($content) {
        // Traiter les composants réutilisables
        $content = preg_replace_callback(
            '/\[component:(\w+)\]/',
            [$this, 'renderComponent'],
            $content
        );

        // Traiter les galeries d'images
        $content = preg_replace_callback(
            '/\[gallery:(\w+)\]/',
            [$this, 'renderGallery'],
            $content
        );

        // Traiter les vidéos
        $content = preg_replace_callback(
            '/\[video:([^\]]+)\]/',
            [$this, 'renderVideo'],
            $content
        );

        return $content;
    }

    /**
     * Rendre un composant réutilisable
     */
    private function renderComponent($matches) {
        $componentId = $matches[1];
        $component = $this->database->findOne('content_components', ['id' => $componentId]);
        
        if ($component) {
            return $component['content'];
        }
        
        return '<div class="component-not-found">Composant non trouvé: ' . $componentId . '</div>';
    }

    /**
     * Rendre une galerie d'images
     */
    private function renderGallery($matches) {
        $galleryId = $matches[1];
        $images = $this->database->findAll('media_files', [
            'id LIKE' => $galleryId . '%',
            'mime_type LIKE' => 'image%'
        ]);

        $html = '<div class="image-gallery">';
        foreach ($images as $image) {
            $html .= '<div class="gallery-item">';
            $html .= '<img src="/' . $image['file_path'] . '" alt="' . htmlspecialchars($image['original_name']) . '" loading="lazy">';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Rendre une vidéo
     */
    private function renderVideo($matches) {
        $videoUrl = $matches[1];
        
        // Vérifier si c'est une URL YouTube ou Vimeo
        if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false) {
            $videoId = $this->extractYouTubeId($videoUrl);
            return '<div class="video-wrapper"><iframe src="https://www.youtube.com/embed/' . $videoId . '" frameborder="0" allowfullscreen></iframe></div>';
        }
        
        if (strpos($videoUrl, 'vimeo.com') !== false) {
            $videoId = $this->extractVimeoId($videoUrl);
            return '<div class="video-wrapper"><iframe src="https://player.vimeo.com/video/' . $videoId . '" frameborder="0" allowfullscreen></iframe></div>';
        }

        // Vidéo locale
        return '<video controls class="responsive-video"><source src="' . $videoUrl . '" type="video/mp4">Votre navigateur ne supporte pas la vidéo.</video>';
    }

    /**
     * Extraire l'ID YouTube d'une URL
     */
    private function extractYouTubeId($url) {
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Extraire l'ID Vimeo d'une URL
     */
    private function extractVimeoId($url) {
        preg_match('/vimeo\.com\/(\d+)/', $url, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Sauvegarder une version du contenu
     */
    public function saveContentVersion($contentId, $content, $userId, $versionNote = '') {
        $versionId = uniqid('ver_');
        
        $version = [
            'id' => $versionId,
            'content_id' => $contentId,
            'content' => $this->sanitizeContent($content),
            'version_note' => $versionNote,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->database->insert('content_versions', $version);
        
        // Garder seulement les 10 dernières versions
        $versions = $this->database->findAll('content_versions', [
            'content_id' => $contentId
        ], [
            'order_by' => 'created_at DESC'
        ]);

        if (count($versions) > 10) {
            $versionsToDelete = array_slice($versions, 10);
            foreach ($versionsToDelete as $oldVersion) {
                $this->database->delete('content_versions', ['id' => $oldVersion['id']]);
            }
        }
        
        return $version;
    }

    /**
     * Obtenir l'historique des versions
     */
    public function getContentVersions($contentId) {
        return $this->database->findAll('content_versions', [
            'content_id' => $contentId
        ], [
            'order_by' => 'created_at DESC'
        ]);
    }

    /**
     * Restaurer une version spécifique
     */
    public function restoreVersion($versionId, $userId) {
        $version = $this->database->findOne('content_versions', ['id' => $versionId]);
        
        if (!$version) {
            throw new Exception('Version non trouvée');
        }

        // Sauvegarder la version actuelle avant restauration
        $currentContent = $this->database->findOne('course_content', ['id' => $version['content_id']]);
        if ($currentContent) {
            $this->saveContentVersion(
                $version['content_id'], 
                $currentContent['content'], 
                $userId, 
                'Sauvegarde avant restauration'
            );
        }

        // Restaurer la version
        return $this->database->update('course_content', [
            'content' => $version['content'],
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userId
        ], ['id' => $version['content_id']]);
    }
}
<?php
/**
 * Page Éditeur WYSIWYG
 * Éditeur visuel avancé pour création de contenu
 */

// Vérifier l'authentification et permissions
Auth::requireAuth();

if (!Auth::hasRole('formateur')) {
    http_response_code(403);
    Utils::redirectWithMessage('/dashboard', 'Accès non autorisé', 'error');
    exit;
}

$pageTitle = "Éditeur WYSIWYG - StacGateLMS";
$pageDescription = "Éditeur visuel avancé pour créer du contenu pédagogique.";

$currentUser = Auth::user();

// Paramètres d'édition
$contentId = $_GET['content_id'] ?? null;
$courseId = $_GET['course_id'] ?? null;
$contentType = $_GET['type'] ?? 'course';

// Charger le contenu existant si ID fourni
$existingContent = null;
if ($contentId) {
    try {
        $wysiwygService = new WysiwygService();
        $existingContent = $wysiwygService->getContent($contentId);
    } catch (Exception $e) {
        Utils::log("WYSIWYG load content error: " . $e->getMessage(), 'ERROR');
    }
}

require_once ROOT_PATH . '/includes/header.php';
?>

<div style="padding: 2rem 0; margin-top: 80px;">
    <div class="container-fluid" style="max-width: 100%; padding: 0 1rem;">
        <!-- En-tête avec actions -->
        <div class="glassmorphism p-4 mb-4">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">
                        ✏️ Éditeur de Contenu
                    </h1>
                    <p style="opacity: 0.8;">
                        <?= $existingContent ? 'Modifier le contenu' : 'Créer du nouveau contenu' ?>
                    </p>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div style="display: flex; gap: 0.5rem;">
                        <button onclick="saveContent()" class="glass-button" id="save-btn">
                            💾 Enregistrer
                        </button>
                        <button onclick="previewContent()" class="glass-button glass-button-secondary">
                            👁️ Aperçu
                        </button>
                        <button onclick="publishContent()" class="glass-button" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                            🚀 Publier
                        </button>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button onclick="toggleFullscreen()" class="glass-button glass-button-secondary" title="Plein écran">
                            ⛶
                        </button>
                        <a href="<?= $courseId ? "/courses?id=$courseId" : '/courses' ?>" class="glass-button glass-button-secondary">
                            ← Retour
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barre d'outils -->
        <div class="glassmorphism p-3 mb-4">
            <div class="editor-toolbar" style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                <!-- Format texte -->
                <div class="toolbar-group" style="display: flex; gap: 0.25rem; padding-right: 1rem; border-right: 1px solid rgba(255,255,255,0.1);">
                    <button onclick="formatText('bold')" class="toolbar-btn" title="Gras">
                        <strong>B</strong>
                    </button>
                    <button onclick="formatText('italic')" class="toolbar-btn" title="Italique">
                        <em>I</em>
                    </button>
                    <button onclick="formatText('underline')" class="toolbar-btn" title="Souligné">
                        <u>U</u>
                    </button>
                    <button onclick="formatText('strikethrough')" class="toolbar-btn" title="Barré">
                        <s>S</s>
                    </button>
                </div>

                <!-- Titres -->
                <div class="toolbar-group" style="display: flex; gap: 0.25rem; padding-right: 1rem; border-right: 1px solid rgba(255,255,255,0.1);">
                    <select onchange="formatHeading(this.value)" class="toolbar-select">
                        <option value="">Style</option>
                        <option value="h1">Titre 1</option>
                        <option value="h2">Titre 2</option>
                        <option value="h3">Titre 3</option>
                        <option value="p">Paragraphe</option>
                    </select>
                </div>

                <!-- Listes -->
                <div class="toolbar-group" style="display: flex; gap: 0.25rem; padding-right: 1rem; border-right: 1px solid rgba(255,255,255,0.1);">
                    <button onclick="formatText('insertOrderedList')" class="toolbar-btn" title="Liste numérotée">
                        1.
                    </button>
                    <button onclick="formatText('insertUnorderedList')" class="toolbar-btn" title="Liste à puces">
                        •
                    </button>
                </div>

                <!-- Alignement -->
                <div class="toolbar-group" style="display: flex; gap: 0.25rem; padding-right: 1rem; border-right: 1px solid rgba(255,255,255,0.1);">
                    <button onclick="formatText('justifyLeft')" class="toolbar-btn" title="Aligner à gauche">
                        ⬅
                    </button>
                    <button onclick="formatText('justifyCenter')" class="toolbar-btn" title="Centrer">
                        ↔
                    </button>
                    <button onclick="formatText('justifyRight')" class="toolbar-btn" title="Aligner à droite">
                        ➡
                    </button>
                </div>

                <!-- Insertion -->
                <div class="toolbar-group" style="display: flex; gap: 0.25rem; padding-right: 1rem; border-right: 1px solid rgba(255,255,255,0.1);">
                    <button onclick="insertLink()" class="toolbar-btn" title="Insérer lien">
                        🔗
                    </button>
                    <button onclick="insertImage()" class="toolbar-btn" title="Insérer image">
                        🖼️
                    </button>
                    <button onclick="insertTable()" class="toolbar-btn" title="Insérer tableau">
                        📊
                    </button>
                    <button onclick="insertVideo()" class="toolbar-btn" title="Insérer vidéo">
                        🎥
                    </button>
                </div>

                <!-- Composants pédagogiques -->
                <div class="toolbar-group" style="display: flex; gap: 0.25rem;">
                    <button onclick="insertQuizComponent()" class="toolbar-btn" title="Quiz interactif">
                        ❓
                    </button>
                    <button onclick="insertInfoBox()" class="toolbar-btn" title="Boîte d'information">
                        ℹ️
                    </button>
                    <button onclick="insertCodeBlock()" class="toolbar-btn" title="Bloc de code">
                        💻
                    </button>
                </div>
            </div>
        </div>

        <!-- Zone d'édition -->
        <div class="glassmorphism" style="height: calc(100vh - 300px); display: flex;">
            <!-- Panneau propriétés -->
            <div id="properties-panel" style="width: 250px; border-right: 1px solid rgba(255,255,255,0.1); padding: 1rem; overflow-y: auto;">
                <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">Propriétés</h3>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Titre du contenu</label>
                    <input type="text" id="content-title" value="<?= htmlspecialchars($existingContent['title'] ?? '') ?>" 
                           class="glass-input" style="width: 100%;" placeholder="Titre...">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Type de contenu</label>
                    <select id="content-type" class="glass-input" style="width: 100%;">
                        <option value="course" <?= $contentType === 'course' ? 'selected' : '' ?>>Cours</option>
                        <option value="lesson" <?= $contentType === 'lesson' ? 'selected' : '' ?>>Leçon</option>
                        <option value="exercise" <?= $contentType === 'exercise' ? 'selected' : '' ?>>Exercice</option>
                        <option value="resource" <?= $contentType === 'resource' ? 'selected' : '' ?>>Ressource</option>
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Tags</label>
                    <input type="text" id="content-tags" value="<?= htmlspecialchars($existingContent['tags'] ?? '') ?>" 
                           class="glass-input" style="width: 100%;" placeholder="tag1, tag2...">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Statut</label>
                    <select id="content-status" class="glass-input" style="width: 100%;">
                        <option value="draft" <?= ($existingContent['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                        <option value="review" <?= ($existingContent['status'] ?? '') === 'review' ? 'selected' : '' ?>>En révision</option>
                        <option value="published" <?= ($existingContent['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publié</option>
                    </select>
                </div>

                <!-- Historique des versions -->
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Versions</label>
                    <div id="version-history" style="max-height: 150px; overflow-y: auto;">
                        <!-- Sera rempli par JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Éditeur principal -->
            <div style="flex: 1; display: flex; flex-direction: column;">
                <div id="editor-container" style="flex: 1; padding: 1rem;">
                    <div id="wysiwyg-editor" 
                         contenteditable="true" 
                         style="height: 100%; overflow-y: auto; padding: 1rem; border: none; outline: none; line-height: 1.6;">
                        <?= $existingContent['content'] ?? '<p>Commencez à taper votre contenu ici...</p>' ?>
                    </div>
                </div>

                <!-- Barre de statut -->
                <div style="padding: 0.5rem 1rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: between; align-items: center; font-size: 0.9rem; opacity: 0.7;">
                    <div>
                        <span id="word-count">0 mots</span> • 
                        <span id="char-count">0 caractères</span>
                    </div>
                    <div id="save-status">
                        <span id="save-indicator">✓ Sauvegardé</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modales -->
<!-- Modal insertion lien -->
<div id="link-modal" class="modal" style="display: none;">
    <div class="modal-content glassmorphism">
        <h3>Insérer un lien</h3>
        <div style="margin: 1rem 0;">
            <input type="text" id="link-text" placeholder="Texte du lien" class="glass-input" style="width: 100%; margin-bottom: 1rem;">
            <input type="url" id="link-url" placeholder="https://..." class="glass-input" style="width: 100%;">
        </div>
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button onclick="closeLinkModal()" class="glass-button glass-button-secondary">Annuler</button>
            <button onclick="confirmInsertLink()" class="glass-button">Insérer</button>
        </div>
    </div>
</div>

<!-- Modal insertion image -->
<div id="image-modal" class="modal" style="display: none;">
    <div class="modal-content glassmorphism">
        <h3>Insérer une image</h3>
        <div style="margin: 1rem 0;">
            <div style="border: 2px dashed rgba(255,255,255,0.3); border-radius: 8px; padding: 2rem; text-align: center; margin-bottom: 1rem;">
                <input type="file" id="image-upload" accept="image/*" style="display: none;" onchange="handleImageUpload(event)">
                <button onclick="document.getElementById('image-upload').click()" class="glass-button">
                    📎 Choisir une image
                </button>
                <p style="margin-top: 1rem; opacity: 0.7;">ou</p>
                <input type="url" id="image-url" placeholder="URL de l'image" class="glass-input" style="width: 100%; margin-top: 1rem;">
            </div>
            <input type="text" id="image-alt" placeholder="Texte alternatif" class="glass-input" style="width: 100%;">
        </div>
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button onclick="closeImageModal()" class="glass-button glass-button-secondary">Annuler</button>
            <button onclick="confirmInsertImage()" class="glass-button">Insérer</button>
        </div>
    </div>
</div>

<script>
// Variables globales
let currentContentId = <?= json_encode($contentId) ?>;
let hasUnsavedChanges = false;
let autoSaveInterval;
let editor = document.getElementById('wysiwyg-editor');

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeEditor();
    setupAutoSave();
    updateWordCount();
});

function initializeEditor() {
    // Écouter les changements dans l'éditeur
    editor.addEventListener('input', function() {
        hasUnsavedChanges = true;
        updateSaveStatus('Non sauvegardé');
        updateWordCount();
    });

    // Gestion des raccourcis clavier
    editor.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch (e.key) {
                case 's':
                    e.preventDefault();
                    saveContent();
                    break;
                case 'b':
                    e.preventDefault();
                    formatText('bold');
                    break;
                case 'i':
                    e.preventDefault();
                    formatText('italic');
                    break;
                case 'u':
                    e.preventDefault();
                    formatText('underline');
                    break;
            }
        }
    });
}

function setupAutoSave() {
    // Sauvegarde automatique toutes les 30 secondes
    autoSaveInterval = setInterval(() => {
        if (hasUnsavedChanges) {
            saveContent(true); // Sauvegarde silencieuse
        }
    }, 30000);
}

function formatText(command, value = null) {
    document.execCommand(command, false, value);
    editor.focus();
}

function formatHeading(tag) {
    if (tag) {
        document.execCommand('formatBlock', false, tag);
    }
    editor.focus();
}

function insertLink() {
    const selection = window.getSelection();
    const selectedText = selection.toString();
    
    document.getElementById('link-text').value = selectedText;
    document.getElementById('link-modal').style.display = 'flex';
}

function closeLinkModal() {
    document.getElementById('link-modal').style.display = 'none';
}

function confirmInsertLink() {
    const text = document.getElementById('link-text').value;
    const url = document.getElementById('link-url').value;
    
    if (url) {
        const link = `<a href="${url}" target="_blank">${text || url}</a>`;
        document.execCommand('insertHTML', false, link);
    }
    
    closeLinkModal();
    editor.focus();
}

function insertImage() {
    document.getElementById('image-modal').style.display = 'flex';
}

function closeImageModal() {
    document.getElementById('image-modal').style.display = 'none';
}

function handleImageUpload(event) {
    const file = event.target.files[0];
    if (file) {
        const formData = new FormData();
        formData.append('image', file);
        
        fetch('/api/upload/image', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('image-url').value = data.url;
            }
        })
        .catch(error => {
            console.error('Erreur upload:', error);
            alert('Erreur lors de l\'upload de l\'image');
        });
    }
}

function confirmInsertImage() {
    const url = document.getElementById('image-url').value;
    const alt = document.getElementById('image-alt').value;
    
    if (url) {
        const img = `<img src="${url}" alt="${alt}" style="max-width: 100%; height: auto;">`;
        document.execCommand('insertHTML', false, img);
    }
    
    closeImageModal();
    editor.focus();
}

function insertTable() {
    const table = `
        <table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
            <tr>
                <th style="border: 1px solid rgba(255,255,255,0.3); padding: 8px;">En-tête 1</th>
                <th style="border: 1px solid rgba(255,255,255,0.3); padding: 8px;">En-tête 2</th>
                <th style="border: 1px solid rgba(255,255,255,0.3); padding: 8px;">En-tête 3</th>
            </tr>
            <tr>
                <td style="border: 1px solid rgba(255,255,255,0.3); padding: 8px;">Cellule 1</td>
                <td style="border: 1px solid rgba(255,255,255,0.3); padding: 8px;">Cellule 2</td>
                <td style="border: 1px solid rgba(255,255,255,0.3); padding: 8px;">Cellule 3</td>
            </tr>
        </table>
    `;
    document.execCommand('insertHTML', false, table);
    editor.focus();
}

function insertVideo() {
    const url = prompt('URL de la vidéo (YouTube, Vimeo, etc.)');
    if (url) {
        const video = `<div style="margin: 1rem 0; text-align: center;"><iframe src="${url}" width="560" height="315" frameborder="0" allowfullscreen></iframe></div>`;
        document.execCommand('insertHTML', false, video);
    }
    editor.focus();
}

function insertQuizComponent() {
    const quiz = `
        <div class="quiz-component" style="border: 2px solid rgba(var(--color-primary), 0.3); border-radius: 8px; padding: 1rem; margin: 1rem 0; background: rgba(var(--color-primary), 0.05);">
            <h4>❓ Quiz interactif</h4>
            <p><strong>Question :</strong> Votre question ici</p>
            <div>
                <label><input type="radio" name="quiz1"> Option 1</label><br>
                <label><input type="radio" name="quiz1"> Option 2</label><br>
                <label><input type="radio" name="quiz1"> Option 3</label>
            </div>
            <button onclick="checkQuizAnswer()" class="glass-button" style="margin-top: 1rem;">Vérifier</button>
        </div>
    `;
    document.execCommand('insertHTML', false, quiz);
    editor.focus();
}

function insertInfoBox() {
    const infoBox = `
        <div class="info-box" style="border-left: 4px solid rgb(var(--color-info)); background: rgba(var(--color-info), 0.1); padding: 1rem; margin: 1rem 0; border-radius: 0 8px 8px 0;">
            <h5 style="margin: 0 0 0.5rem 0; color: rgb(var(--color-info));">ℹ️ Information importante</h5>
            <p style="margin: 0;">Votre contenu informatif ici...</p>
        </div>
    `;
    document.execCommand('insertHTML', false, infoBox);
    editor.focus();
}

function insertCodeBlock() {
    const codeBlock = `
        <div class="code-block" style="background: rgba(0,0,0,0.8); border-radius: 8px; padding: 1rem; margin: 1rem 0; font-family: monospace; color: #e0e0e0;">
            <div style="color: #888; font-size: 0.9rem; margin-bottom: 0.5rem;">Code</div>
            <pre style="margin: 0; white-space: pre-wrap;">// Votre code ici
console.log('Hello World!');</pre>
        </div>
    `;
    document.execCommand('insertHTML', false, codeBlock);
    editor.focus();
}

function updateWordCount() {
    const text = editor.textContent || editor.innerText || '';
    const words = text.trim().split(/\s+/).filter(word => word.length > 0).length;
    const chars = text.length;
    
    document.getElementById('word-count').textContent = `${words} mots`;
    document.getElementById('char-count').textContent = `${chars} caractères`;
}

function updateSaveStatus(status) {
    document.getElementById('save-indicator').textContent = status;
}

async function saveContent(silent = false) {
    const contentData = {
        title: document.getElementById('content-title').value,
        content: editor.innerHTML,
        type: document.getElementById('content-type').value,
        tags: document.getElementById('content-tags').value,
        status: document.getElementById('content-status').value,
        course_id: <?= json_encode($courseId) ?>
    };

    try {
        const url = currentContentId ? `/api/wysiwyg/content/${currentContentId}` : '/api/wysiwyg/content';
        const method = currentContentId ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(contentData)
        });

        const result = await response.json();

        if (result.success) {
            hasUnsavedChanges = false;
            updateSaveStatus('✓ Sauvegardé');
            
            if (!currentContentId && result.data.id) {
                currentContentId = result.data.id;
                // Mettre à jour l'URL sans recharger la page
                window.history.replaceState({}, '', `?content_id=${currentContentId}&course_id=${courseId || ''}&type=${contentData.type}`);
            }
            
            if (!silent) {
                showNotification('Contenu sauvegardé avec succès', 'success');
            }
        } else {
            throw new Error(result.error || 'Erreur de sauvegarde');
        }
    } catch (error) {
        console.error('Erreur sauvegarde:', error);
        updateSaveStatus('❌ Erreur');
        if (!silent) {
            showNotification('Erreur lors de la sauvegarde', 'error');
        }
    }
}

function previewContent() {
    const previewWindow = window.open('', '_blank');
    const content = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Aperçu - ${document.getElementById('content-title').value}</title>
            <style>
                body { font-family: system-ui, sans-serif; max-width: 800px; margin: 0 auto; padding: 2rem; }
                h1, h2, h3, h4, h5, h6 { color: #333; }
                img { max-width: 100%; height: auto; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <h1>${document.getElementById('content-title').value || 'Aperçu du contenu'}</h1>
            ${editor.innerHTML}
        </body>
        </html>
    `;
    previewWindow.document.write(content);
    previewWindow.document.close();
}

async function publishContent() {
    if (hasUnsavedChanges) {
        await saveContent();
    }
    
    document.getElementById('content-status').value = 'published';
    await saveContent();
    showNotification('Contenu publié avec succès', 'success');
}

function toggleFullscreen() {
    const container = document.querySelector('.container-fluid');
    if (container.classList.contains('fullscreen')) {
        container.classList.remove('fullscreen');
        document.body.style.overflow = '';
    } else {
        container.classList.add('fullscreen');
        document.body.style.overflow = 'hidden';
    }
}

function showNotification(message, type = 'info') {
    // Créer notification temporaire
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        background: ${type === 'success' ? 'rgba(34, 197, 94, 0.9)' : 'rgba(239, 68, 68, 0.9)'};
        color: white;
        z-index: 1000;
        transform: translateX(100%);
        transition: transform 0.3s;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Nettoyage à la fermeture
window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = 'Vous avez des modifications non sauvegardées. Voulez-vous vraiment quitter ?';
    }
    
    if (autoSaveInterval) {
        clearInterval(autoSaveInterval);
    }
});
</script>

<style>
.toolbar-btn {
    padding: 0.5rem;
    background: rgba(255,255,255,0.1);
    border: none;
    border-radius: 4px;
    color: inherit;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 32px;
    text-align: center;
}

.toolbar-btn:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-1px);
}

.toolbar-select {
    padding: 0.5rem;
    background: rgba(255,255,255,0.1);
    border: none;
    border-radius: 4px;
    color: inherit;
    cursor: pointer;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    max-width: 500px;
    width: 90%;
    padding: 2rem;
    border-radius: 12px;
}

.fullscreen {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    max-width: 100% !important;
    height: 100vh !important;
    z-index: 100;
    padding: 1rem !important;
}

.fullscreen .glassmorphism:first-child {
    margin-top: 0 !important;
}

#wysiwyg-editor {
    font-size: 16px;
    line-height: 1.6;
}

#wysiwyg-editor h1 { font-size: 2em; margin: 1em 0 0.5em 0; }
#wysiwyg-editor h2 { font-size: 1.7em; margin: 1em 0 0.5em 0; }
#wysiwyg-editor h3 { font-size: 1.4em; margin: 1em 0 0.5em 0; }
#wysiwyg-editor p { margin: 1em 0; }
#wysiwyg-editor ul, #wysiwyg-editor ol { margin: 1em 0; padding-left: 2em; }
#wysiwyg-editor blockquote { margin: 1em 0; padding-left: 1em; border-left: 3px solid rgba(var(--color-primary), 0.5); }

@media (max-width: 768px) {
    #properties-panel {
        width: 200px;
    }
    
    .toolbar-group {
        flex-wrap: wrap;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
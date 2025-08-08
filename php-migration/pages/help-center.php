<?php
/**
 * Page centre d'aide
 */

// Vérifier l'authentification
Auth::requireAuth();

$pageTitle = "Centre d'aide - StacGateLMS";
$pageDescription = "Documentation, FAQ et support pour utiliser la plateforme.";

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

// Initialiser les services
$helpService = new HelpService();

// Paramètres
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$page = intval($_GET['page'] ?? 1);
$perPage = 10;

$filters = [];
if ($search) $filters['search'] = $search;
if ($category) $filters['category'] = $category;

// Obtenir les données
try {
    if ($search) {
        // Recherche
        $contentsData = $helpService->searchHelpContent($establishmentId, $search, $currentUser['role'], $page, $perPage);
    } else {
        // Liste normale
        $contentsData = $helpService->getHelpContentsByEstablishment($establishmentId, $currentUser['role'], $page, $perPage, $filters);
    }
    
    $contents = $contentsData['data'];
    $meta = $contentsData['meta'];
    
    // Catégories disponibles
    $categories = $helpService->getCategories($establishmentId);
    
    // Contenu populaire
    $popularContents = $helpService->getPopularContent($establishmentId, 5, $currentUser['role']);
    
    // Contenu récent
    $recentContents = $helpService->getRecentContent($establishmentId, 5, $currentUser['role']);
    
    // FAQ
    $faqContents = $helpService->getFAQ($establishmentId, $currentUser['role'], 8);
    
    // Statistiques
    $stats = $helpService->getHelpStats($establishmentId);
    
} catch (Exception $e) {
    Utils::log("Help center page error: " . $e->getMessage(), 'ERROR');
    $contents = $popularContents = $recentContents = $faqContents = [];
    $categories = [];
    $meta = ['total' => 0, 'current_page' => 1, 'total_pages' => 1];
    $stats = [];
}

require_once ROOT_PATH . '/includes/header.php';
?>

<div style="padding: 2rem 0; margin-top: 80px;">
    <div class="container">
        <!-- En-tête -->
        <div class="glassmorphism p-6 mb-8 text-center">
            <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">
                Centre d'aide
            </h1>
            <p style="opacity: 0.8; font-size: 1.1rem; margin-bottom: 2rem;">
                Trouvez rapidement les réponses à vos questions
            </p>
            
            <!-- Barre de recherche principale -->
            <form method="GET" style="max-width: 600px; margin: 0 auto; position: relative;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Rechercher dans l'aide..." 
                       class="glass-input" 
                       style="width: 100%; padding: 1rem 3rem 1rem 1.5rem; font-size: 1.1rem; border-radius: 50px;">
                <button type="submit" 
                        style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: var(--gradient-primary); border: none; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: white; cursor: pointer;">
                    🔍
                </button>
            </form>
        </div>

        <?php if ($search): ?>
            <!-- Résultats de recherche -->
            <div class="glassmorphism p-6 mb-8">
                <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">
                    Résultats de recherche pour "<?= htmlspecialchars($search) ?>"
                </h2>
                <p style="opacity: 0.8; margin-bottom: 1.5rem;">
                    <?= $meta['total'] ?> résultat<?= $meta['total'] > 1 ? 's' : '' ?> trouvé<?= $meta['total'] > 1 ? 's' : '' ?>
                </p>
                
                <?php if (!empty($contents)): ?>
                    <div style="space-y: 1rem;">
                        <?php foreach ($contents as $content): ?>
                            <div style="padding: 1.5rem; background: rgba(var(--color-primary), 0.05); border-radius: 0.5rem; margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                    <h3 style="font-size: 1.1rem; font-weight: 600;">
                                        <a href="/help-center/<?= $content['id'] ?>" style="text-decoration: none; color: inherit;">
                                            <?= htmlspecialchars($content['title']) ?>
                                        </a>
                                    </h3>
                                    <span class="badge" style="background: rgba(var(--color-secondary), 0.1); color: rgb(var(--color-secondary)); font-size: 0.7rem;">
                                        <?= htmlspecialchars($content['category']) ?>
                                    </span>
                                </div>
                                
                                <p style="opacity: 0.8; margin-bottom: 0.5rem;">
                                    <?= Utils::truncate(strip_tags($content['content']), 200) ?>
                                </p>
                                
                                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; opacity: 0.6;">
                                    <span>👀 <?= $content['views'] ?? 0 ?> vues</span>
                                    <span>Mis à jour <?= Utils::timeAgo($content['updated_at']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem;">
                        <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;">🔍</div>
                        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">
                            Aucun résultat trouvé
                        </h3>
                        <p style="opacity: 0.8; margin-bottom: 2rem;">
                            Essayez avec d'autres mots-clés ou consultez les catégories ci-dessous.
                        </p>
                        <a href="/help-center" class="glass-button">
                            Voir toute l'aide
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Vue d'ensemble -->
            <div class="grid grid-2 mb-8" style="gap: 2rem;">
                <!-- Contenu populaire -->
                <div class="glassmorphism p-6">
                    <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center;">
                        🔥 Articles populaires
                    </h2>
                    
                    <?php if (!empty($popularContents)): ?>
                        <div style="space-y: 1rem;">
                            <?php foreach ($popularContents as $content): ?>
                                <div style="padding: 1rem; background: rgba(var(--color-primary), 0.03); border-radius: 0.5rem; margin-bottom: 1rem;">
                                    <h4 style="font-weight: 600; margin-bottom: 0.5rem;">
                                        <a href="/help-center/<?= $content['id'] ?>" style="text-decoration: none; color: inherit;">
                                            <?= htmlspecialchars($content['title']) ?>
                                        </a>
                                    </h4>
                                    <p style="font-size: 0.9rem; opacity: 0.8; margin-bottom: 0.5rem;">
                                        <?= Utils::truncate(strip_tags($content['content']), 80) ?>
                                    </p>
                                    <div style="font-size: 0.7rem; opacity: 0.6;">
                                        👀 <?= $content['views'] ?> vues
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="opacity: 0.6; text-align: center; padding: 2rem;">
                            Aucun contenu populaire pour le moment
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Articles récents -->
                <div class="glassmorphism p-6">
                    <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center;">
                        🆕 Articles récents
                    </h2>
                    
                    <?php if (!empty($recentContents)): ?>
                        <div style="space-y: 1rem;">
                            <?php foreach ($recentContents as $content): ?>
                                <div style="padding: 1rem; background: rgba(var(--color-secondary), 0.03); border-radius: 0.5rem; margin-bottom: 1rem;">
                                    <h4 style="font-weight: 600; margin-bottom: 0.5rem;">
                                        <a href="/help-center/<?= $content['id'] ?>" style="text-decoration: none; color: inherit;">
                                            <?= htmlspecialchars($content['title']) ?>
                                        </a>
                                    </h4>
                                    <p style="font-size: 0.9rem; opacity: 0.8; margin-bottom: 0.5rem;">
                                        <?= Utils::truncate(strip_tags($content['content']), 80) ?>
                                    </p>
                                    <div style="font-size: 0.7rem; opacity: 0.6;">
                                        📅 <?= Utils::timeAgo($content['created_at']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="opacity: 0.6; text-align: center; padding: 2rem;">
                            Aucun contenu récent
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Catégories -->
            <?php if (!empty($categories)): ?>
                <div class="glassmorphism p-6 mb-8">
                    <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                        📚 Parcourir par catégorie
                    </h2>
                    
                    <div class="grid grid-3">
                        <?php foreach ($categories as $cat): ?>
                            <a href="?category=<?= urlencode($cat['name']) ?>" 
                               class="glass-card p-4 text-center" 
                               style="text-decoration: none; color: inherit; transition: transform 0.3s ease;">
                                
                                <div style="font-size: 3rem; margin-bottom: 1rem;">
                                    <?= $cat['icon'] ?? '📄' ?>
                                </div>
                                
                                <h3 style="font-weight: 600; margin-bottom: 0.5rem;">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </h3>
                                
                                <p style="font-size: 0.9rem; opacity: 0.8; margin-bottom: 1rem;">
                                    <?= htmlspecialchars($cat['description'] ?? '') ?>
                                </p>
                                
                                <div style="font-size: 0.8rem; opacity: 0.6;">
                                    <?= $cat['content_count'] ?> article<?= $cat['content_count'] > 1 ? 's' : '' ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- FAQ -->
            <?php if (!empty($faqContents)): ?>
                <div class="glassmorphism p-6 mb-8">
                    <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1.5rem;">
                        ❓ Questions fréquentes
                    </h2>
                    
                    <div class="faq-container">
                        <?php foreach ($faqContents as $index => $faq): ?>
                            <div class="faq-item" style="border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 1rem;">
                                <button onclick="toggleFaq(<?= $index ?>)" 
                                        class="faq-question" 
                                        style="width: 100%; text-align: left; padding: 1rem 0; background: none; border: none; color: inherit; font-size: 1rem; font-weight: 500; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                                    <span><?= htmlspecialchars($faq['title']) ?></span>
                                    <span class="faq-icon" data-index="<?= $index ?>">▼</span>
                                </button>
                                
                                <div id="faq-<?= $index ?>" class="faq-answer" style="display: none; padding: 0 0 1rem 0; opacity: 0.8;">
                                    <?= nl2br(htmlspecialchars(strip_tags($faq['content']))) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Contact support -->
        <div class="glassmorphism p-6 text-center">
            <h2 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">
                Vous ne trouvez pas ce que vous cherchez ?
            </h2>
            <p style="opacity: 0.8; margin-bottom: 2rem;">
                Notre équipe support est là pour vous aider
            </p>
            
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="mailto:support@stacgatelms.com" 
                   class="glass-button" 
                   style="background: var(--gradient-primary); color: white; padding: 1rem 2rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    📧 Contacter le support
                </a>
                
                <a href="/user-manual" class="glass-button glass-button-secondary" style="padding: 1rem 2rem;">
                    📖 Manuel utilisateur
                </a>
                
                <?php if (Auth::hasRole('admin')): ?>
                    <a href="/help-center/admin" class="glass-button glass-button-secondary" style="padding: 1rem 2rem;">
                        ⚙️ Gérer l'aide
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($meta['total_pages'] > 1): ?>
            <div style="display: flex; justify-content: center; margin-top: 2rem; gap: 0.5rem;">
                <?php for ($i = 1; $i <= $meta['total_pages']; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>" 
                       class="glass-button <?= $i == $meta['current_page'] ? 'active' : '' ?>" 
                       style="padding: 0.5rem 1rem; <?= $i == $meta['current_page'] ? 'background: var(--gradient-primary); color: white;' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleFaq(index) {
    const answer = document.getElementById(`faq-${index}`);
    const icon = document.querySelector(`[data-index="${index}"]`);
    
    if (answer.style.display === 'none' || answer.style.display === '') {
        answer.style.display = 'block';
        icon.textContent = '▲';
    } else {
        answer.style.display = 'none';
        icon.textContent = '▼';
    }
}

// Animation des cartes catégories
document.querySelectorAll('.glass-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-3px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

// Améliorer la recherche avec suggestions
document.querySelector('input[name="search"]').addEventListener('input', function() {
    // Ici on pourrait ajouter des suggestions de recherche en temps réel
    // via une requête AJAX vers /api/help/suggestions
});
</script>

<style>
.faq-item {
    transition: all 0.3s ease;
}

.faq-question:hover {
    opacity: 0.8;
}

.faq-answer {
    line-height: 1.6;
}

.faq-icon {
    transition: transform 0.3s ease;
}

@media (max-width: 768px) {
    .grid-3, .grid-2 {
        grid-template-columns: 1fr;
    }
    
    .glassmorphism h1 {
        font-size: 2rem !important;
    }
    
    .glass-button {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
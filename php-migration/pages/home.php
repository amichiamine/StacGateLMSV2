<?php
/**
 * Page d'accueil publique
 * Correspond à client/src/pages/home.tsx
 */

$pageTitle = "StacGateLMS - Plateforme E-learning Moderne";
$pageDescription = "Découvrez notre plateforme e-learning innovante avec architecture multi-tenant, interface glassmorphism et outils pédagogiques avancés.";

require_once ROOT_PATH . '/includes/header.php';

// Obtenir quelques statistiques publiques
$establishmentService = new EstablishmentService();
$courseService = new CourseService();

$totalEstablishments = count($establishmentService->getAllEstablishments());
$popularCourses = $courseService->getPopularCourses(null, 6);
?>

<!-- Section Hero -->
<section class="hero-section">
    <div class="hero-content">
        <h1 class="hero-title animate-fade-in">
            Plateforme E-learning
            <br>
            <span style="background: var(--gradient-secondary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                Nouvelle Génération
            </span>
        </h1>
        
        <p class="hero-subtitle animate-fade-in" style="animation-delay: 0.2s;">
            Découvrez StacGateLMS, la solution complète pour l'apprentissage en ligne avec une interface moderne glassmorphism, 
            un système multi-tenant avancé et des outils pédagogiques innovants.
        </p>
        
        <div class="hero-buttons animate-fade-in" style="animation-delay: 0.4s;">
            <a href="/portal" class="glass-button">
                Découvrir les établissements
            </a>
            <a href="/login" class="glass-button glass-button-secondary">
                Se connecter
            </a>
        </div>
        
        <!-- Statistiques -->
        <div class="grid grid-3 mt-8" style="max-width: 600px; margin: 3rem auto 0;">
            <div class="glassmorphism p-6 text-center animate-fade-in" style="animation-delay: 0.6s;">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary));">
                    <?= $totalEstablishments ?>+
                </div>
                <div style="opacity: 0.8;">Établissements</div>
            </div>
            
            <div class="glassmorphism p-6 text-center animate-fade-in" style="animation-delay: 0.8s;">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary));">
                    <?= count($popularCourses) ?>+
                </div>
                <div style="opacity: 0.8;">Cours disponibles</div>
            </div>
            
            <div class="glassmorphism p-6 text-center animate-fade-in" style="animation-delay: 1s;">
                <div style="font-size: 2rem; font-weight: 700; color: rgb(var(--color-primary));">
                    24/7
                </div>
                <div style="opacity: 0.8;">Support</div>
            </div>
        </div>
    </div>
</section>

<!-- Section Fonctionnalités -->
<section style="padding: 4rem 0; background: rgba(255, 255, 255, 0.05);">
    <div class="container">
        <div class="text-center mb-8">
            <h2 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">
                Fonctionnalités avancées
            </h2>
            <p style="font-size: 1.25rem; opacity: 0.8; max-width: 600px; margin: 0 auto;">
                Tous les outils nécessaires pour créer, gérer et suivre vos formations en ligne de manière efficace.
            </p>
        </div>
        
        <div class="grid grid-3">
            <!-- Multi-tenant -->
            <div class="glass-card p-6 text-center">
                <div style="width: 4rem; height: 4rem; margin: 0 auto 1.5rem; background: var(--gradient-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 2rem; height: 2rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">Multi-tenant</h3>
                <p style="opacity: 0.8; line-height: 1.6;">
                    Architecture multi-établissements avec isolation complète des données, thèmes personnalisés et gestion indépendante.
                </p>
            </div>
            
            <!-- Interface Glassmorphism -->
            <div class="glass-card p-6 text-center">
                <div style="width: 4rem; height: 4rem; margin: 0 auto 1.5rem; background: var(--gradient-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 2rem; height: 2rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">Design Moderne</h3>
                <p style="opacity: 0.8; line-height: 1.6;">
                    Interface glassmorphism élégante et intuitive avec effets de transparence, animations fluides et design responsive.
                </p>
            </div>
            
            <!-- Analytics Avancés -->
            <div class="glass-card p-6 text-center">
                <div style="width: 4rem; height: 4rem; margin: 0 auto 1.5rem; background: var(--gradient-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 2rem; height: 2rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">Analytics Temps Réel</h3>
                <p style="opacity: 0.8; line-height: 1.6;">
                    Tableaux de bord complets avec métriques détaillées, suivi de progression et rapports personnalisables.
                </p>
            </div>
            
            <!-- Évaluations -->
            <div class="glass-card p-6 text-center">
                <div style="width: 4rem; height: 4rem; margin: 0 auto 1.5rem; background: var(--gradient-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 2rem; height: 2rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">Évaluations Avancées</h3>
                <p style="opacity: 0.8; line-height: 1.6;">
                    Système complet d'évaluations avec quiz interactifs, examens chronométrés et correction automatique.
                </p>
            </div>
            
            <!-- Collaboration -->
            <div class="glass-card p-6 text-center">
                <div style="width: 4rem; height: 4rem; margin: 0 auto 1.5rem; background: var(--gradient-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 2rem; height: 2rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">Collaboration</h3>
                <p style="opacity: 0.8; line-height: 1.6;">
                    Groupes d'étude, chat temps réel, tableau blanc collaboratif et partage de ressources entre apprenants.
                </p>
            </div>
            
            <!-- WYSIWYG -->
            <div class="glass-card p-6 text-center">
                <div style="width: 4rem; height: 4rem; margin: 0 auto 1.5rem; background: var(--gradient-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 2rem; height: 2rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">Éditeur WYSIWYG</h3>
                <p style="opacity: 0.8; line-height: 1.6;">
                    Créateur de contenu visuel intuitif avec composants réutilisables et personnalisation complète des pages.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Section Cours Populaires -->
<?php if (!empty($popularCourses)): ?>
<section style="padding: 4rem 0;">
    <div class="container">
        <div class="text-center mb-8">
            <h2 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">
                Cours Populaires
            </h2>
            <p style="font-size: 1.25rem; opacity: 0.8; max-width: 600px; margin: 0 auto;">
                Découvrez les formations les plus appréciées par notre communauté d'apprenants.
            </p>
        </div>
        
        <div class="grid grid-3">
            <?php foreach ($popularCourses as $course): ?>
                <div class="glass-card p-6">
                    <?php if ($course['thumbnail_url']): ?>
                        <img src="<?= htmlspecialchars($course['thumbnail_url']) ?>" 
                             alt="<?= htmlspecialchars($course['title']) ?>"
                             style="width: 100%; height: 200px; object-fit: cover; border-radius: var(--border-radius); margin-bottom: 1.5rem;">
                    <?php else: ?>
                        <div style="width: 100%; height: 200px; background: var(--gradient-primary); border-radius: var(--border-radius); margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: center;">
                            <svg style="width: 3rem; height: 3rem; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                    <?php endif; ?>
                    
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                        <span class="badge"><?= htmlspecialchars($course['category']) ?></span>
                        <span class="badge badge-success"><?= htmlspecialchars($course['level']) ?></span>
                    </div>
                    
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">
                        <?= htmlspecialchars($course['title']) ?>
                    </h3>
                    
                    <p style="opacity: 0.8; margin-bottom: 1.5rem; line-height: 1.6;">
                        <?= htmlspecialchars(Utils::truncate($course['short_description'] ?: $course['description'], 120)) ?>
                    </p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg style="width: 1rem; height: 1rem; color: #fbbf24;" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <span style="font-weight: 500;"><?= number_format($course['rating'], 1) ?></span>
                        </div>
                        
                        <div style="font-size: 0.875rem; opacity: 0.7;">
                            <?= $course['enrollment_count'] ?> inscrits
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-weight: 600; color: rgb(var(--color-primary));">
                            <?php if ($course['is_free']): ?>
                                Gratuit
                            <?php else: ?>
                                <?= number_format($course['price'], 2) ?>€
                            <?php endif; ?>
                        </div>
                        
                        <a href="/login" class="glass-button" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                            Voir le cours
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8">
            <a href="/portal" class="glass-button">
                Voir tous les cours
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Section CTA Final -->
<section style="padding: 4rem 0; background: rgba(255, 255, 255, 0.05);">
    <div class="container text-center">
        <h2 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem;">
            Prêt à commencer votre apprentissage ?
        </h2>
        <p style="font-size: 1.25rem; opacity: 0.8; max-width: 600px; margin: 0 auto 2rem;">
            Rejoignez des milliers d'apprenants qui font confiance à StacGateLMS pour développer leurs compétences.
        </p>
        
        <div class="hero-buttons">
            <a href="/portal" class="glass-button" style="font-size: 1.125rem; padding: 1rem 2rem;">
                Commencer maintenant
            </a>
            <a href="/help-center" class="glass-button glass-button-secondary" style="font-size: 1.125rem; padding: 1rem 2rem;">
                En savoir plus
            </a>
        </div>
    </div>
</section>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
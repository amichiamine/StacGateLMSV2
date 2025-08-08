<?php
/**
 * Page portail - Sélection d'établissement
 */

$pageTitle = "Choisir votre établissement - StacGateLMS";
$pageDescription = "Sélectionnez votre établissement d'enseignement pour accéder à votre espace d'apprentissage.";

// Obtenir la liste des établissements actifs
$establishmentService = new EstablishmentService();
$establishments = $establishmentService->getAllEstablishments(true);

require_once ROOT_PATH . '/includes/header.php';
?>

<div style="padding: 2rem 0; margin-top: 80px; min-height: calc(100vh - 160px);">
    <div class="container">
        <!-- En-tête -->
        <div class="text-center mb-8">
            <h1 style="font-size: 3rem; font-weight: 700; margin-bottom: 1rem;">
                Choisissez votre établissement
            </h1>
            <p style="font-size: 1.25rem; opacity: 0.8; max-width: 600px; margin: 0 auto;">
                Sélectionnez votre établissement d'enseignement pour accéder à votre espace d'apprentissage personnalisé.
            </p>
        </div>

        <!-- Grille des établissements -->
        <div class="grid grid-3 mb-8">
            <?php foreach ($establishments as $establishment): ?>
                <div class="glass-card p-6 text-center cursor-pointer establishment-card" 
                     onclick="selectEstablishment(<?= $establishment['id'] ?>, '<?= htmlspecialchars($establishment['slug']) ?>')">
                    
                    <!-- Logo -->
                    <?php if ($establishment['logo']): ?>
                        <img src="<?= htmlspecialchars($establishment['logo']) ?>" 
                             alt="<?= htmlspecialchars($establishment['name']) ?>"
                             style="height: 80px; width: 80px; object-fit: contain; margin: 0 auto 1rem; border-radius: 0.5rem;">
                    <?php else: ?>
                        <div style="height: 80px; width: 80px; background: var(--gradient-primary); margin: 0 auto 1rem; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 2rem;">
                            <?= strtoupper(substr($establishment['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Nom -->
                    <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">
                        <?= htmlspecialchars($establishment['name']) ?>
                    </h3>
                    
                    <!-- Description -->
                    <?php if ($establishment['description']): ?>
                        <p style="opacity: 0.8; font-size: 0.9rem; margin-bottom: 1rem;">
                            <?= Utils::truncate(htmlspecialchars($establishment['description']), 100) ?>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Statistiques -->
                    <div style="display: flex; justify-content: space-around; margin-bottom: 1rem; font-size: 0.8rem; opacity: 0.7;">
                        <div>
                            <div style="font-weight: 600; color: rgb(var(--color-primary));">
                                <?= $establishment['total_users'] ?? 0 ?>
                            </div>
                            <div>Étudiants</div>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: rgb(var(--color-secondary));">
                                <?= $establishment['total_courses'] ?? 0 ?>
                            </div>
                            <div>Cours</div>
                        </div>
                    </div>
                    
                    <!-- Bouton -->
                    <div class="glass-button" style="background: var(--gradient-primary); color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 500;">
                        Accéder
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Informations supplémentaires -->
        <div class="glassmorphism p-6 text-center">
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem;">
                Vous ne trouvez pas votre établissement ?
            </h3>
            <p style="opacity: 0.8; margin-bottom: 1.5rem;">
                Contactez votre administration ou demandez l'ajout de votre établissement.
            </p>
            
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="mailto:support@stacgatelms.com" class="glass-button" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Contacter le support
                </a>
                
                <a href="/help-center" class="glass-button glass-button-secondary">
                    Centre d'aide
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function selectEstablishment(id, slug) {
    // Rediriger vers la page de connexion avec l'établissement présélectionné
    window.location.href = `/login?establishment=${id}`;
}

// Animation des cartes
document.querySelectorAll('.establishment-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
        this.style.transition = 'transform 0.3s ease';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});
</script>

<style>
.establishment-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.establishment-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px -10px rgba(0, 0, 0, 0.2);
}

@media (max-width: 768px) {
    .grid-3 {
        grid-template-columns: 1fr;
    }
    
    h1 {
        font-size: 2rem !important;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
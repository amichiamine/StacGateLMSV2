<?php
/**
 * Footer commun pour toutes les pages
 */
?>

<!-- Footer -->
<footer class="glassmorphism" style="margin-top: 4rem; padding: 3rem 0; border-radius: 0;">
    <div class="container">
        <div class="grid grid-4" style="gap: 2rem;">
            <!-- Logo et description -->
            <div>
                <div class="nav-logo" style="margin-bottom: 1rem; font-size: 1.25rem;">
                    StacGateLMS
                </div>
                <p style="opacity: 0.8; line-height: 1.6;">
                    Plateforme e-learning moderne avec architecture multi-tenant et interface glassmorphism.
                </p>
                <div style="margin-top: 1rem;">
                    <small style="opacity: 0.6;">
                        Version <?= APP_VERSION ?> • <?= APP_ENV ?>
                    </small>
                </div>
            </div>
            
            <!-- Liens rapides -->
            <div>
                <h4 style="margin-bottom: 1rem; font-weight: 600;">Navigation</h4>
                <ul style="list-style: none; line-height: 2;">
                    <?php if (Auth::check()): ?>
                        <li><a href="/dashboard" style="color: inherit; text-decoration: none; opacity: 0.8; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">Tableau de bord</a></li>
                        <li><a href="/courses" style="color: inherit; text-decoration: none; opacity: 0.8; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">Cours</a></li>
                        <li><a href="/help-center" style="color: inherit; text-decoration: none; opacity: 0.8; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">Centre d'aide</a></li>
                    <?php else: ?>
                        <li><a href="/" style="color: inherit; text-decoration: none; opacity: 0.8; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">Accueil</a></li>
                        <li><a href="/portal" style="color: inherit; text-decoration: none; opacity: 0.8; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">Établissements</a></li>
                        <li><a href="/login" style="color: inherit; text-decoration: none; opacity: 0.8; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">Connexion</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Support -->
            <div>
                <h4 style="margin-bottom: 1rem; font-weight: 600;">Support</h4>
                <ul style="list-style: none; line-height: 2;">
                    <li><a href="/help-center" style="color: inherit; text-decoration: none; opacity: 0.8; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">Centre d'aide</a></li>
                    <li><a href="/user-manual" style="color: inherit; text-decoration: none; opacity: 0.8; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">Manuel utilisateur</a></li>
                    <?php if (Auth::hasRole('admin')): ?>
                        <li><a href="/system-updates" style="color: inherit; text-decoration: none; opacity: 0.8; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">Mises à jour</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Informations système -->
            <div>
                <h4 style="margin-bottom: 1rem; font-weight: 600;">Système</h4>
                <ul style="list-style: none; line-height: 2; font-size: 0.875rem;">
                    <li style="opacity: 0.7;">PHP <?= PHP_VERSION ?></li>
                    <li style="opacity: 0.7;"><?= IS_POSTGRESQL ? 'PostgreSQL' : 'MySQL' ?></li>
                    <?php if ($currentEstablishment): ?>
                        <li style="opacity: 0.7;"><?= htmlspecialchars($currentEstablishment['name']) ?></li>
                    <?php endif; ?>
                    <li style="opacity: 0.7;">
                        <span id="currentTime"></span>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Séparateur -->
        <hr style="border: none; height: 1px; background: var(--glass-border); margin: 2rem 0;">
        
        <!-- Copyright et liens légaux -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="opacity: 0.7;">
                © <?= date('Y') ?> StacGateLMS. Tous droits réservés.
            </div>
            
            <div style="display: flex; gap: 2rem; font-size: 0.875rem;">
                <a href="#" style="color: inherit; text-decoration: none; opacity: 0.7; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">
                    Politique de confidentialité
                </a>
                <a href="#" style="color: inherit; text-decoration: none; opacity: 0.7; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">
                    Conditions d'utilisation
                </a>
                <a href="#" style="color: inherit; text-decoration: none; opacity: 0.7; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.7'">
                    Contact
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- Scripts de fin de page -->
<script>
    // Horloge temps réel
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        const timeElement = document.getElementById('currentTime');
        if (timeElement) {
            timeElement.textContent = timeString;
        }
    }
    
    // Mettre à jour l'heure toutes les secondes
    setInterval(updateTime, 1000);
    updateTime(); // Appel initial
    
    // Smooth scroll pour les ancres
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Animation des éléments à l'apparition
    function animateOnScroll() {
        const elements = document.querySelectorAll('.glass-card, .glassmorphism');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        elements.forEach(el => observer.observe(el));
    }
    
    // Initialiser les animations quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', animateOnScroll);
    } else {
        animateOnScroll();
    }
    
    // Gestionnaire global d'erreurs pour les requêtes AJAX
    window.addEventListener('unhandledrejection', function(event) {
        console.error('Unhandled promise rejection:', event.reason);
        
        // Afficher une notification d'erreur à l'utilisateur
        if (window.showToast) {
            window.showToast('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    });
    
    // Confirmation pour les actions destructrices
    document.addEventListener('click', function(event) {
        const target = event.target;
        
        // Boutons de suppression
        if (target.classList.contains('btn-delete') || target.dataset.confirm) {
            const message = target.dataset.confirm || 'Êtes-vous sûr de vouloir supprimer cet élément ?';
            if (!confirm(message)) {
                event.preventDefault();
                return false;
            }
        }
        
        // Formulaires de suppression
        if (target.type === 'submit' && target.form && target.form.method.toLowerCase() === 'post') {
            const action = target.form.action;
            if (action.includes('/delete') || target.classList.contains('btn-delete')) {
                const message = target.dataset.confirm || 'Êtes-vous sûr de vouloir effectuer cette action ?';
                if (!confirm(message)) {
                    event.preventDefault();
                    return false;
                }
            }
        }
    });
    
    // Auto-resize pour les textareas
    document.querySelectorAll('textarea').forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
    
    // Validation côté client pour les formulaires
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(event) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#ef4444';
                    
                    // Remettre la bordure normale après un délai
                    setTimeout(() => {
                        field.style.borderColor = '';
                    }, 3000);
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                event.preventDefault();
                if (window.showToast) {
                    window.showToast('Veuillez remplir tous les champs obligatoires.', 'error');
                }
            }
        });
    });
</script>

<!-- CSS responsive pour le footer -->
<style>
@media (max-width: 768px) {
    footer .grid-4 {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    footer .grid-4 > div:first-child {
        text-align: center;
    }
    
    footer > div > div:last-child {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    footer > div > div:last-child > div:last-child {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

</body>
</html>
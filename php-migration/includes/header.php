<?php
/**
 * Header commun pour toutes les pages
 */

// Obtenir l'utilisateur connecté
$currentUser = Auth::user();
$isAuthenticated = Auth::check();

// Obtenir l'établissement actuel
$currentEstablishment = null;
if ($currentUser && $currentUser['establishment_id']) {
    $establishmentService = new EstablishmentService();
    $currentEstablishment = $establishmentService->getEstablishmentById($currentUser['establishment_id']);
    
    // Obtenir le thème actif
    $activeTheme = $establishmentService->getActiveTheme($currentUser['establishment_id']);
}

// Configuration du thème
$themeColors = DEFAULT_THEME_COLORS;
if (isset($activeTheme) && $activeTheme) {
    $themeColors = [
        'primary' => $activeTheme['primary_color'],
        'secondary' => $activeTheme['secondary_color'],
        'accent' => $activeTheme['accent_color'],
        'background' => $activeTheme['background_color'],
        'text' => $activeTheme['text_color']
    ];
}

// Générer le token CSRF
$csrfToken = generateCSRFToken();

// Message flash
$flashMessage = Utils::getFlashMessage();

// Page title par défaut
$pageTitle = $pageTitle ?? 'StacGateLMS - Plateforme E-learning';
?>
<!DOCTYPE html>
<html lang="fr" class="<?= isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === 'true' ? 'dark' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $pageDescription ?? 'Plateforme e-learning moderne avec système multi-tenant et interface glassmorphism' ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/glassmorphism.css">
    
    <!-- Variables CSS dynamiques pour le thème -->
    <style>
        :root {
            --color-primary: <?= implode(' ', sscanf($themeColors['primary'], "#%02x%02x%02x")) ?>;
            --color-secondary: <?= implode(' ', sscanf($themeColors['secondary'], "#%02x%02x%02x")) ?>;
            --color-accent: <?= implode(' ', sscanf($themeColors['accent'], "#%02x%02x%02x")) ?>;
            --color-background: <?= implode(' ', sscanf($themeColors['background'], "#%02x%02x%02x")) ?>;
            --color-text: <?= implode(' ', sscanf($themeColors['text'], "#%02x%02x%02x")) ?>;
        }
    </style>
    
    <!-- Meta tags supplémentaires selon la page -->
    <?php if (isset($additionalMeta)): ?>
        <?= $additionalMeta ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar glass-nav">
        <div class="nav-container">
            <!-- Logo -->
            <a href="<?= $isAuthenticated ? '/dashboard' : '/' ?>" class="nav-logo">
                <?php if ($currentEstablishment && $currentEstablishment['logo']): ?>
                    <img src="<?= htmlspecialchars($currentEstablishment['logo']) ?>" alt="<?= htmlspecialchars($currentEstablishment['name']) ?>" style="height: 2rem; display: inline-block;">
                <?php else: ?>
                    StacGateLMS
                <?php endif; ?>
            </a>
            
            <!-- Menu desktop -->
            <ul class="nav-menu">
                <?php if (!$isAuthenticated): ?>
                    <!-- Menu public -->
                    <li><a href="/" class="nav-link">Accueil</a></li>
                    <li><a href="/portal" class="nav-link">Établissements</a></li>
                    <li><a href="/login" class="nav-link">Connexion</a></li>
                <?php else: ?>
                    <!-- Menu authentifié -->
                    <li><a href="/dashboard" class="nav-link">Tableau de bord</a></li>
                    <li><a href="/courses" class="nav-link">Cours</a></li>
                    
                    <?php if (Auth::hasRole('formateur')): ?>
                        <li><a href="/assessments" class="nav-link">Évaluations</a></li>
                        <li><a href="/study-groups" class="nav-link">Groupes</a></li>
                    <?php endif; ?>
                    
                    <?php if (Auth::hasRole('manager')): ?>
                        <li><a href="/analytics" class="nav-link">Analytics</a></li>
                        <li><a href="/user-management" class="nav-link">Utilisateurs</a></li>
                    <?php endif; ?>
                    
                    <?php if (Auth::hasRole('admin')): ?>
                        <li><a href="/admin" class="nav-link">Administration</a></li>
                    <?php endif; ?>
                    
                    <?php if (Auth::hasRole('super_admin')): ?>
                        <li><a href="/super-admin" class="nav-link">Super Admin</a></li>
                        <li><a href="/system-updates" class="nav-link">Système</a></li>
                    <?php endif; ?>
                    
                    <!-- Menu utilisateur -->
                    <li class="user-menu" style="position: relative;">
                        <button class="nav-link" onclick="toggleUserMenu()" style="background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                            <?php if ($currentUser['avatar']): ?>
                                <img src="<?= htmlspecialchars($currentUser['avatar']) ?>" alt="Avatar" style="width: 2rem; height: 2rem; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 2rem; height: 2rem; border-radius: 50%; background: var(--gradient-primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                    <?= strtoupper(substr($currentUser['first_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($currentUser['first_name']) ?></span>
                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <div id="userDropdown" class="glassmorphism" style="position: absolute; top: 100%; right: 0; width: 200px; padding: 1rem; display: none; z-index: 1000;">
                            <div style="margin-bottom: 0.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--glass-border);">
                                <div style="font-weight: 600;"><?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></div>
                                <div style="font-size: 0.875rem; opacity: 0.7;"><?= htmlspecialchars($currentUser['role']) ?></div>
                                <?php if ($currentEstablishment): ?>
                                    <div style="font-size: 0.75rem; opacity: 0.6;"><?= htmlspecialchars($currentEstablishment['name']) ?></div>
                                <?php endif; ?>
                            </div>
                            <a href="/help-center" style="display: block; padding: 0.5rem 0; color: inherit; text-decoration: none;">Centre d'aide</a>
                            <button onclick="toggleTheme()" style="display: block; width: 100%; text-align: left; background: none; border: none; padding: 0.5rem 0; color: inherit; cursor: pointer;">
                                <span id="themeToggleText">Mode sombre</span>
                            </button>
                            <form action="/api/auth/logout" method="POST" style="margin: 0;">
                                <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                                <button type="submit" style="display: block; width: 100%; text-align: left; background: none; border: none; padding: 0.5rem 0; color: #ef4444; cursor: pointer;">
                                    Déconnexion
                                </button>
                            </form>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>
            
            <!-- Toggle menu mobile -->
            <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>
    
    <!-- Menu mobile -->
    <div id="mobileMenu" class="mobile-menu">
        <div style="position: absolute; top: 2rem; right: 2rem; cursor: pointer; color: white; font-size: 2rem;" onclick="toggleMobileMenu()">×</div>
        
        <?php if (!$isAuthenticated): ?>
            <a href="/" class="nav-link">Accueil</a>
            <a href="/portal" class="nav-link">Établissements</a>
            <a href="/login" class="nav-link">Connexion</a>
        <?php else: ?>
            <a href="/dashboard" class="nav-link">Tableau de bord</a>
            <a href="/courses" class="nav-link">Cours</a>
            
            <?php if (Auth::hasRole('formateur')): ?>
                <a href="/assessments" class="nav-link">Évaluations</a>
                <a href="/study-groups" class="nav-link">Groupes</a>
            <?php endif; ?>
            
            <?php if (Auth::hasRole('manager')): ?>
                <a href="/analytics" class="nav-link">Analytics</a>
                <a href="/user-management" class="nav-link">Utilisateurs</a>
            <?php endif; ?>
            
            <?php if (Auth::hasRole('admin')): ?>
                <a href="/admin" class="nav-link">Administration</a>
            <?php endif; ?>
            
            <?php if (Auth::hasRole('super_admin')): ?>
                <a href="/super-admin" class="nav-link">Super Admin</a>
                <a href="/system-updates" class="nav-link">Système</a>
            <?php endif; ?>
            
            <a href="/help-center" class="nav-link">Centre d'aide</a>
            
            <form action="/api/auth/logout" method="POST" style="margin: 0;">
                <input type="hidden" name="_token" value="<?= $csrfToken ?>">
                <button type="submit" class="nav-link" style="background: none; border: none; color: #ef4444; font-size: 1.5rem;">
                    Déconnexion
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <!-- Message flash -->
    <?php if ($flashMessage): ?>
        <div id="flashMessage" class="glassmorphism" style="position: fixed; top: 100px; right: 2rem; padding: 1rem 1.5rem; z-index: 1001; max-width: 400px; <?= $flashMessage['type'] === 'error' ? 'border-left: 4px solid #ef4444;' : 'border-left: 4px solid #10b981;' ?>">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span><?= htmlspecialchars($flashMessage['text']) ?></span>
                <button onclick="document.getElementById('flashMessage').remove()" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 1.25rem; margin-left: 1rem;">×</button>
            </div>
        </div>
        
        <script>
        // Auto-hide flash message after 5 seconds
        setTimeout(() => {
            const flashEl = document.getElementById('flashMessage');
            if (flashEl) {
                flashEl.style.opacity = '0';
                flashEl.style.transform = 'translateX(100%)';
                setTimeout(() => flashEl.remove(), 300);
            }
        }, 5000);
        </script>
    <?php endif; ?>
    
    <!-- JavaScript commun -->
    <script>
        // Variables globales
        window.APP_CONFIG = {
            baseUrl: '<?= BASE_URL ?>',
            csrfToken: '<?= $csrfToken ?>',
            user: <?= $currentUser ? json_encode([
                'id' => $currentUser['id'],
                'name' => $currentUser['first_name'] . ' ' . $currentUser['last_name'],
                'role' => $currentUser['role'],
                'establishment_id' => $currentUser['establishment_id']
            ]) : 'null' ?>,
            establishment: <?= $currentEstablishment ? json_encode([
                'id' => $currentEstablishment['id'],
                'name' => $currentEstablishment['name'],
                'slug' => $currentEstablishment['slug']
            ]) : 'null' ?>
        };
        
        // Toggle menu mobile
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('active');
        }
        
        // Toggle menu utilisateur
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'none' || !dropdown.style.display ? 'block' : 'none';
        }
        
        // Fermer les menus quand on clique ailleurs
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const dropdown = document.getElementById('userDropdown');
            
            if (userMenu && !userMenu.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });
        
        // Toggle thème dark/light
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            const newTheme = !isDark;
            
            html.classList.toggle('dark', newTheme);
            
            // Sauvegarder dans un cookie
            document.cookie = `dark_mode=${newTheme}; path=/; max-age=${365 * 24 * 60 * 60}`;
            
            // Mettre à jour le texte du bouton
            const toggleText = document.getElementById('themeToggleText');
            if (toggleText) {
                toggleText.textContent = newTheme ? 'Mode clair' : 'Mode sombre';
            }
        }
        
        // Initialiser le texte du toggle thème
        document.addEventListener('DOMContentLoaded', function() {
            const toggleText = document.getElementById('themeToggleText');
            if (toggleText) {
                const isDark = document.documentElement.classList.contains('dark');
                toggleText.textContent = isDark ? 'Mode clair' : 'Mode sombre';
            }
        });
        
        // Utilitaire pour les requêtes AJAX
        window.apiRequest = async function(url, options = {}) {
            const config = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                },
                ...options
            };
            
            // Ajouter le token CSRF pour les requêtes POST/PUT/DELETE
            if (['POST', 'PUT', 'DELETE'].includes(config.method)) {
                if (config.body && typeof config.body === 'object') {
                    config.body._token = window.APP_CONFIG.csrfToken;
                    config.body = JSON.stringify(config.body);
                } else if (config.body instanceof FormData) {
                    config.body.append('_token', window.APP_CONFIG.csrfToken);
                }
            }
            
            try {
                const response = await fetch(url, config);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return await response.json();
                } else {
                    return await response.text();
                }
            } catch (error) {
                console.error('API Request failed:', error);
                throw error;
            }
        };
        
        // Notification toast simple
        window.showToast = function(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = 'glassmorphism animate-fade-in';
            toast.style.cssText = `
                position: fixed;
                top: 120px;
                right: 2rem;
                padding: 1rem 1.5rem;
                z-index: 1001;
                max-width: 400px;
                border-left: 4px solid ${type === 'error' ? '#ef4444' : '#10b981'};
            `;
            
            toast.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 1.25rem; margin-left: 1rem;">×</button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        };
    </script>
    
    <!-- CSS et JS supplémentaires selon la page -->
    <?php if (isset($additionalCSS)): ?>
        <?= $additionalCSS ?>
    <?php endif; ?>
    
    <?php if (isset($additionalJS)): ?>
        <?= $additionalJS ?>
    <?php endif; ?>
</body>
<?php // Le body sera fermé dans footer.php ?>
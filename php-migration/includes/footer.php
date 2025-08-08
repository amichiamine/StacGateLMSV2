    </main>
    
    <!-- Footer -->
    <footer style="margin-top: 4rem; padding: 2rem 0; border-top: 1px solid rgba(255,255,255,0.1);">
        <div class="container">
            <div class="glassmorphism p-6">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                    <div>
                        <h3 style="font-size: 1.2rem; font-weight: 600; margin-bottom: 1rem;">
                            🎓 StacGateLMS
                        </h3>
                        <p style="opacity: 0.8; line-height: 1.6;">
                            Plateforme e-learning moderne pour une formation adaptée aux besoins de chaque établissement.
                        </p>
                    </div>
                    
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 1rem;">Navigation</h4>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <a href="/portal" style="color: rgba(255,255,255,0.8); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.8)'">
                                🏛️ Portail
                            </a>
                            <a href="/help-center" style="color: rgba(255,255,255,0.8); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.8)'">
                                ❓ Centre d'aide
                            </a>
                            <?php if (Auth::isAuthenticated()): ?>
                                <a href="/dashboard" style="color: rgba(255,255,255,0.8); text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.8)'">
                                    📊 Dashboard
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 1rem;">Fonctionnalités</h4>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <span style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">📚 Gestion des cours</span>
                            <span style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">📝 Évaluations</span>
                            <span style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">👥 Groupes d'étude</span>
                            <span style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">📊 Analytics</span>
                        </div>
                    </div>
                    
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 1rem;">Informations</h4>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem; color: rgba(255,255,255,0.8);">
                            <div>Version: <?= APP_VERSION ?></div>
                            <div>Environnement: <?= APP_ENV ?></div>
                            <div>© <?= date('Y') ?> StacGateLMS</div>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); opacity: 0.7;">
                    <p style="font-size: 0.9rem;">
                        Propulsé par la technologie moderne • 
                        Interface responsive • 
                        Sécurité enterprise
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts JavaScript -->
    <script>
        // Utilitaires globaux
        window.StacGate = {
            // Affichage de notifications
            notify: function(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `flash-message ${type}`;
                notification.textContent = message;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.animation = 'slideIn 0.3s ease reverse';
                    setTimeout(() => notification.remove(), 300);
                }, 5000);
            },
            
            // Requêtes AJAX simples
            request: async function(url, options = {}) {
                const defaultOptions = {
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                };
                
                const response = await fetch(url, {...defaultOptions, ...options});
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return await response.json();
                } else {
                    return await response.text();
                }
            },
            
            // Confirmation avec style
            confirm: function(message, callback) {
                if (confirm(message)) {
                    callback();
                }
            },
            
            // Formatage des dates
            formatDate: function(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },
            
            // Auto-refresh périodique
            autoRefresh: function(selector, url, interval = 30000) {
                setInterval(async () => {
                    try {
                        const data = await this.request(url);
                        const element = document.querySelector(selector);
                        if (element && data) {
                            element.innerHTML = data;
                        }
                    } catch (error) {
                        console.warn('Auto-refresh failed:', error);
                    }
                }, interval);
            }
        };
        
        // Amélioration de l'UX
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus sur le premier input visible
            const firstInput = document.querySelector('input:not([type="hidden"]):not([readonly])');
            if (firstInput) {
                firstInput.focus();
            }
            
            // Amélioration des formulaires
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = submitBtn.innerHTML + ' <span class="loading"></span>';
                        
                        // Re-enable après 10 secondes en cas de problème
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = submitBtn.innerHTML.replace(' <span class="loading"></span>', '');
                        }, 10000);
                    }
                });
            });
            
            // Raccourcis clavier globaux
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + K pour recherche rapide
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    const searchInput = document.querySelector('input[type="search"], input[placeholder*="recherch"], input[placeholder*="Recherch"]');
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                }
                
                // Échap pour fermer les modales
                if (e.key === 'Escape') {
                    const modals = document.querySelectorAll('.modal[style*="display: flex"], .modal[style*="display: block"]');
                    modals.forEach(modal => {
                        modal.style.display = 'none';
                    });
                }
            });
        });
        
        // Gestion des erreurs JavaScript
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
            // En mode debug, afficher l'erreur
            <?php if (APP_DEBUG): ?>
                StacGate.notify('Erreur JavaScript: ' + e.message, 'error');
            <?php endif; ?>
        });
        
        // Performance monitoring simple
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log(`Page loaded in ${Math.round(loadTime)}ms`);
            
            // Rapport de performance en mode debug
            <?php if (APP_DEBUG): ?>
                if (loadTime > 3000) {
                    console.warn('Page load time is slow:', loadTime + 'ms');
                }
            <?php endif; ?>
        });
    </script>
    
    <!-- Analytics et tracking -->
    <?php if (APP_ENV === 'production'): ?>
        <!-- Google Analytics ou autre solution de tracking -->
        <script>
            // Analytics code here in production
        </script>
    <?php endif; ?>
</body>
</html>
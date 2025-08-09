<?php
/**
 * Script PHP de configuration environnement pour StacGateLMS React/Node.js
 * Génère le fichier .env automatiquement
 */

class EnvironmentSetup {
    
    private $config = [];
    
    public function __construct() {
        session_start();
        $this->config = $_SESSION['env_config'] ?? [];
    }
    
    public function run() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        } else {
            $this->showForm();
        }
    }
    
    private function handlePost() {
        $step = $_POST['step'] ?? 1;
        
        switch ($step) {
            case 1:
                $this->handleDatabaseConfig();
                break;
            case 2:
                $this->handleAppConfig();
                break;
            case 3:
                $this->generateEnvFile();
                break;
        }
    }
    
    private function handleDatabaseConfig() {
        $this->config['db_type'] = $_POST['db_type'] ?? 'docker';
        
        switch ($this->config['db_type']) {
            case 'local':
                $this->config['db_host'] = $_POST['db_host'] ?? 'localhost';
                $this->config['db_port'] = $_POST['db_port'] ?? '5432';
                $this->config['db_name'] = $_POST['db_name'] ?? 'stacgatelms';
                $this->config['db_user'] = $_POST['db_user'] ?? 'postgres';
                $this->config['db_pass'] = $_POST['db_pass'] ?? '';
                break;
            case 'cloud':
                $this->config['db_url'] = $_POST['db_url'] ?? '';
                break;
            default:
                $this->config['db_url'] = 'postgresql://stacgate:stacgate123@localhost:5433/stacgatelms';
        }
        
        $_SESSION['env_config'] = $this->config;
        $this->showForm(2);
    }
    
    private function handleAppConfig() {
        $this->config['app_name'] = $_POST['app_name'] ?? 'StacGateLMS';
        $this->config['app_url'] = $_POST['app_url'] ?? 'http://localhost:5000';
        $this->config['admin_email'] = $_POST['admin_email'] ?? '';
        $this->config['admin_password'] = $_POST['admin_password'] ?? '';
        $this->config['install_demo'] = isset($_POST['install_demo']);
        
        $_SESSION['env_config'] = $this->config;
        $this->showForm(3);
    }
    
    private function generateEnvFile() {
        try {
            $envContent = $this->buildEnvContent();
            $envPath = '../.env';
            
            if (file_put_contents($envPath, $envContent)) {
                $this->showSuccess();
            } else {
                throw new Exception('Impossible d\'écrire le fichier .env');
            }
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
    }
    
    private function buildEnvContent() {
        $dbUrl = $this->getDatabaseUrl();
        
        $content = "# Configuration StacGateLMS React/Node.js\n";
        $content .= "# Générée automatiquement le " . date('Y-m-d H:i:s') . "\n\n";
        
        $content .= "# Environnement\n";
        $content .= "NODE_ENV=development\n";
        $content .= "PORT=5000\n";
        $content .= "VITE_PORT=3000\n\n";
        
        $content .= "# Base de données\n";
        $content .= "DATABASE_URL=\"{$dbUrl}\"\n\n";
        
        $content .= "# Application\n";
        $content .= "APP_NAME=\"{$this->config['app_name']}\"\n";
        $content .= "APP_URL=\"{$this->config['app_url']}\"\n\n";
        
        $content .= "# Sécurité\n";
        $content .= "JWT_SECRET=\"" . $this->generateSecret() . "\"\n";
        $content .= "SESSION_SECRET=\"" . $this->generateSecret() . "\"\n";
        $content .= "CSRF_SECRET=\"" . $this->generateSecret(16) . "\"\n\n";
        
        $content .= "# Administrateur\n";
        $content .= "ADMIN_EMAIL=\"{$this->config['admin_email']}\"\n";
        $content .= "ADMIN_PASSWORD=\"{$this->config['admin_password']}\"\n\n";
        
        $content .= "# Fonctionnalités\n";
        $content .= "INSTALL_DEMO_DATA=\"" . ($this->config['install_demo'] ? 'true' : 'false') . "\"\n\n";
        
        $content .= "# Frontend (Vite)\n";
        $content .= "VITE_API_URL=http://localhost:5000\n";
        $content .= "VITE_APP_NAME=\"{$this->config['app_name']}\"\n\n";
        
        $content .= "# Upload et stockage\n";
        $content .= "UPLOAD_MAX_SIZE=10485760\n";
        $content .= "UPLOAD_ALLOWED_TYPES=image/jpeg,image/png,image/gif,application/pdf\n\n";
        
        $content .= "# Logs et debug\n";
        $content .= "LOG_LEVEL=info\n";
        $content .= "DEBUG_MODE=false\n\n";
        
        $content .= "# Rate limiting\n";
        $content .= "RATE_LIMIT_WINDOW_MS=900000\n";
        $content .= "RATE_LIMIT_MAX_REQUESTS=100\n\n";
        
        $content .= "# Cache\n";
        $content .= "CACHE_TTL=3600\n";
        $content .= "REDIS_URL=\n\n";
        
        $content .= "# Email (optionnel)\n";
        $content .= "SMTP_HOST=\n";
        $content .= "SMTP_PORT=587\n";
        $content .= "SMTP_USER=\n";
        $content .= "SMTP_PASS=\n";
        $content .= "SMTP_FROM=noreply@" . parse_url($this->config['app_url'], PHP_URL_HOST) . "\n\n";
        
        $content .= "# Analytics (optionnel)\n";
        $content .= "GOOGLE_ANALYTICS_ID=\n";
        $content .= "MATOMO_URL=\n";
        $content .= "MATOMO_SITE_ID=\n";
        
        return $content;
    }
    
    private function getDatabaseUrl() {
        switch ($this->config['db_type']) {
            case 'local':
                return "postgresql://{$this->config['db_user']}:{$this->config['db_pass']}@{$this->config['db_host']}:{$this->config['db_port']}/{$this->config['db_name']}";
            case 'cloud':
                return $this->config['db_url'];
            default:
                return 'postgresql://stacgate:stacgate123@localhost:5433/stacgatelms';
        }
    }
    
    private function generateSecret($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    private function showForm($step = 1) {
        switch ($step) {
            case 1:
                $this->renderDatabaseForm();
                break;
            case 2:
                $this->renderAppForm();
                break;
            case 3:
                $this->renderConfirmation();
                break;
        }
    }
    
    private function renderDatabaseForm() {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Configuration Base de Données</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .option { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 6px; border: 2px solid transparent; cursor: pointer; }
                .option:hover { border-color: #667eea; }
                .option input[type="radio"] { margin-right: 10px; }
                .form-group { margin: 15px 0; }
                .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
                .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
                .btn { background: #667eea; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; }
                .btn:hover { background: #5a6fd8; }
                .config-section { display: none; margin-top: 15px; padding: 15px; background: #e3f2fd; border-radius: 6px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🗄️ Configuration Base de Données</h1>
                    <p>StacGateLMS React/Node.js</p>
                </div>
                
                <div class="content">
                    <h2>Choisissez votre configuration PostgreSQL</h2>
                    
                    <form method="post">
                        <input type="hidden" name="step" value="1">
                        
                        <div class="option">
                            <label>
                                <input type="radio" name="db_type" value="docker" checked>
                                <strong>🐳 PostgreSQL Docker</strong>
                                <p>Configuration automatique avec Docker Compose (recommandé)</p>
                            </label>
                        </div>
                        
                        <div class="option">
                            <label>
                                <input type="radio" name="db_type" value="local">
                                <strong>🖥️ PostgreSQL Local</strong>
                                <p>Utilise une installation PostgreSQL existante</p>
                            </label>
                        </div>
                        
                        <div class="option">
                            <label>
                                <input type="radio" name="db_type" value="cloud">
                                <strong>☁️ Service Cloud</strong>
                                <p>Neon, Supabase, Railway ou autre fournisseur</p>
                            </label>
                        </div>
                        
                        <div id="localConfig" class="config-section">
                            <h3>Configuration PostgreSQL Local</h3>
                            <div class="form-group">
                                <label>Hôte :</label>
                                <input type="text" name="db_host" value="localhost">
                            </div>
                            <div class="form-group">
                                <label>Port :</label>
                                <input type="text" name="db_port" value="5432">
                            </div>
                            <div class="form-group">
                                <label>Base de données :</label>
                                <input type="text" name="db_name" value="stacgatelms">
                            </div>
                            <div class="form-group">
                                <label>Utilisateur :</label>
                                <input type="text" name="db_user" value="postgres">
                            </div>
                            <div class="form-group">
                                <label>Mot de passe :</label>
                                <input type="password" name="db_pass">
                            </div>
                        </div>
                        
                        <div id="cloudConfig" class="config-section">
                            <h3>Configuration Cloud</h3>
                            <div class="form-group">
                                <label>URL de connexion :</label>
                                <input type="text" name="db_url" placeholder="postgresql://user:pass@host:5432/database">
                            </div>
                            <p><small>Exemples d'URLs :</small></p>
                            <ul style="font-size: 12px;">
                                <li>Neon: postgresql://user:pass@host.neon.tech/dbname</li>
                                <li>Supabase: postgresql://user:pass@host.pooler.supabase.com:5432/postgres</li>
                            </ul>
                        </div>
                        
                        <button type="submit" class="btn">Continuer</button>
                    </form>
                </div>
            </div>
            
            <script>
                document.querySelectorAll('input[name="db_type"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        document.querySelectorAll('.config-section').forEach(section => {
                            section.style.display = 'none';
                        });
                        
                        if (this.value === 'local') {
                            document.getElementById('localConfig').style.display = 'block';
                        } else if (this.value === 'cloud') {
                            document.getElementById('cloudConfig').style.display = 'block';
                        }
                    });
                });
            </script>
        </body>
        </html>
        <?php
    }
    
    private function renderAppForm() {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Configuration Application</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .form-group { margin: 20px 0; }
                .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
                .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
                .form-group input[type="checkbox"] { width: auto; }
                .btn { background: #667eea; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; }
                .btn:hover { background: #5a6fd8; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>⚙️ Configuration Application</h1>
                    <p>Paramètres généraux</p>
                </div>
                
                <div class="content">
                    <form method="post">
                        <input type="hidden" name="step" value="2">
                        
                        <div class="form-group">
                            <label>Nom de l'application :</label>
                            <input type="text" name="app_name" value="<?= htmlspecialchars($this->config['app_name'] ?? 'StacGateLMS') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>URL de l'application :</label>
                            <input type="url" name="app_url" value="<?= htmlspecialchars($this->config['app_url'] ?? 'http://localhost:5000') ?>" required>
                        </div>
                        
                        <h3>Compte Super Administrateur</h3>
                        
                        <div class="form-group">
                            <label>Email administrateur :</label>
                            <input type="email" name="admin_email" value="<?= htmlspecialchars($this->config['admin_email'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Mot de passe :</label>
                            <input type="password" name="admin_password" value="<?= htmlspecialchars($this->config['admin_password'] ?? '') ?>" minlength="6" required>
                            <small>Minimum 6 caractères</small>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="install_demo" <?= ($this->config['install_demo'] ?? true) ? 'checked' : '' ?>>
                                Installer les données de démonstration
                            </label>
                        </div>
                        
                        <button type="submit" class="btn">Générer Configuration</button>
                    </form>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function renderConfirmation() {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Confirmation Configuration</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .summary { background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0; }
                .btn { background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
                .btn:hover { background: #218838; }
                .btn-secondary { background: #6c757d; margin-left: 10px; }
                .btn-secondary:hover { background: #5a6268; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📋 Confirmation Configuration</h1>
                    <p>Vérifiez les paramètres avant génération</p>
                </div>
                
                <div class="content">
                    <div class="summary">
                        <h3>Résumé de la configuration</h3>
                        <ul>
                            <li><strong>Application :</strong> <?= htmlspecialchars($this->config['app_name']) ?></li>
                            <li><strong>URL :</strong> <?= htmlspecialchars($this->config['app_url']) ?></li>
                            <li><strong>Admin :</strong> <?= htmlspecialchars($this->config['admin_email']) ?></li>
                            <li><strong>Base de données :</strong> <?= ucfirst($this->config['db_type']) ?> PostgreSQL</li>
                            <li><strong>Données démo :</strong> <?= $this->config['install_demo'] ? 'Oui' : 'Non' ?></li>
                        </ul>
                    </div>
                    
                    <div style="text-align: center;">
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="step" value="3">
                            <button type="submit" class="btn">Générer .env</button>
                        </form>
                        <a href="?" class="btn btn-secondary">Recommencer</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function showSuccess() {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Configuration Terminée</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .success { background: #d4edda; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #28a745; }
                .btn { background: #667eea; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
                .btn:hover { background: #5a6fd8; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>✅ Configuration Terminée</h1>
                    <p>Fichier .env généré avec succès</p>
                </div>
                
                <div class="content">
                    <div class="success">
                        <h3>Fichier .env créé</h3>
                        <p>La configuration a été sauvegardée dans le fichier .env à la racine du projet.</p>
                    </div>
                    
                    <h3>🚀 Prochaines étapes :</h3>
                    <ol>
                        <li>Installez Node.js 18+ si pas encore fait</li>
                        <li>Exécutez <code>npm install</code> pour installer les dépendances</li>
                        <li>Lancez <code>npm run db:push</code> pour créer les tables</li>
                        <li>Créez le compte admin avec <code>node scripts/seed-admin.js</code></li>
                        <li>Démarrez l'application avec <code>npm run dev</code></li>
                    </ol>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="install-wizard.php" class="btn">Assistant d'installation complet</a>
                    </div>
                    
                    <div style="background: #e3f2fd; padding: 15px; border-radius: 6px; font-size: 12px;">
                        <strong>Informations de connexion :</strong><br>
                        Email: <?= htmlspecialchars($this->config['admin_email']) ?><br>
                        Mot de passe: <?= htmlspecialchars($this->config['admin_password']) ?><br>
                        Application: <?= htmlspecialchars($this->config['app_url']) ?>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        
        // Nettoyer la session
        unset($_SESSION['env_config']);
    }
    
    private function showError($message) {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erreur Configuration</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #dc3545, #c82333); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .error { background: #f8d7da; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #dc3545; }
                .btn { background: #667eea; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
                .btn:hover { background: #5a6fd8; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>❌ Erreur de Configuration</h1>
                </div>
                
                <div class="content">
                    <div class="error">
                        <h3>Échec de la génération</h3>
                        <p><?= htmlspecialchars($message) ?></p>
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="?" class="btn">Réessayer</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}

// Lancement du configurateur
$setup = new EnvironmentSetup();
$setup->run();
?>
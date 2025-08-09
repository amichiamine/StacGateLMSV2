<?php
/**
 * StacGateLMS React/Node.js - Assistant d'Installation PHP
 * Compatible avec tous les hébergements web (même sans Node.js)
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

class ReactInstaller {
    private $steps = [
        1 => 'welcome',
        2 => 'requirements', 
        3 => 'environment',
        4 => 'database',
        5 => 'configuration',
        6 => 'installation',
        7 => 'complete'
    ];
    
    private $currentStep = 1;
    private $config = [];
    
    public function __construct() {
        $this->currentStep = $_SESSION['install_step'] ?? 1;
        $this->config = $_SESSION['install_config'] ?? [];
    }
    
    public function run() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }
        
        $this->render();
    }
    
    private function handlePost() {
        $step = $_POST['step'] ?? $this->currentStep;
        
        switch ($step) {
            case 1:
                $this->handleWelcome();
                break;
            case 2:
                $this->handleRequirements();
                break;
            case 3:
                $this->handleEnvironment();
                break;
            case 4:
                $this->handleDatabase();
                break;
            case 5:
                $this->handleConfiguration();
                break;
            case 6:
                $this->handleInstallation();
                break;
        }
    }
    
    private function handleWelcome() {
        if ($_POST['proceed'] === 'yes') {
            $this->nextStep();
        }
    }
    
    private function handleRequirements() {
        $requirements = $this->checkRequirements();
        if ($requirements['critical_passed']) {
            $this->nextStep();
        }
    }
    
    private function handleEnvironment() {
        $envChoice = $_POST['env_choice'] ?? 'docker';
        $this->config['environment'] = $envChoice;
        
        if ($envChoice === 'manual') {
            $this->config['node_path'] = $_POST['node_path'] ?? '';
            $this->config['npm_path'] = $_POST['npm_path'] ?? '';
        }
        
        $this->nextStep();
    }
    
    private function handleDatabase() {
        $dbChoice = $_POST['db_choice'] ?? 'docker';
        $this->config['db_type'] = $dbChoice;
        
        switch ($dbChoice) {
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
            default: // docker
                $this->config['db_url'] = 'postgresql://stacgate:stacgate123@localhost:5433/stacgatelms';
        }
        
        $this->nextStep();
    }
    
    private function handleConfiguration() {
        $this->config['app_name'] = $_POST['app_name'] ?? 'StacGateLMS';
        $this->config['app_url'] = $_POST['app_url'] ?? 'http://localhost:5000';
        $this->config['admin_email'] = $_POST['admin_email'] ?? '';
        $this->config['admin_password'] = $_POST['admin_password'] ?? '';
        $this->config['install_demo'] = isset($_POST['install_demo']);
        
        $this->nextStep();
    }
    
    private function handleInstallation() {
        $result = $this->performInstallation();
        if ($result['success']) {
            $this->nextStep();
        } else {
            $this->config['install_error'] = $result['error'];
        }
    }
    
    private function nextStep() {
        $this->currentStep++;
        $_SESSION['install_step'] = $this->currentStep;
        $_SESSION['install_config'] = $this->config;
    }
    
    private function checkRequirements() {
        $requirements = [
            'node' => $this->checkNode(),
            'npm' => $this->checkNpm(),
            'php' => $this->checkPhp(),
            'write_permission' => $this->checkWritePermission(),
            'docker' => $this->checkDocker()
        ];
        
        $critical_passed = $requirements['php']['status'] && $requirements['write_permission']['status'];
        
        return array_merge($requirements, ['critical_passed' => $critical_passed]);
    }
    
    private function checkNode() {
        $output = shell_exec('node --version 2>&1');
        if ($output && strpos($output, 'v') === 0) {
            $version = trim($output);
            $majorVersion = (int) substr($version, 1);
            return [
                'status' => $majorVersion >= 18,
                'message' => $version . ($majorVersion >= 18 ? ' ✓' : ' (requis: 18+)')
            ];
        }
        return ['status' => false, 'message' => 'Non installé'];
    }
    
    private function checkNpm() {
        $output = shell_exec('npm --version 2>&1');
        if ($output && preg_match('/\d+\.\d+\.\d+/', $output)) {
            return ['status' => true, 'message' => trim($output) . ' ✓'];
        }
        return ['status' => false, 'message' => 'Non installé'];
    }
    
    private function checkPhp() {
        return [
            'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'message' => PHP_VERSION . (version_compare(PHP_VERSION, '7.4.0', '>=') ? ' ✓' : ' (requis: 7.4+)')
        ];
    }
    
    private function checkWritePermission() {
        $testFile = '../.write-test';
        if (file_put_contents($testFile, 'test')) {
            unlink($testFile);
            return ['status' => true, 'message' => 'OK ✓'];
        }
        return ['status' => false, 'message' => 'Accès refusé'];
    }
    
    private function checkDocker() {
        $output = shell_exec('docker --version 2>&1');
        if ($output && strpos($output, 'Docker version') !== false) {
            return ['status' => true, 'message' => trim($output)];
        }
        return ['status' => false, 'message' => 'Non installé (optionnel)'];
    }
    
    private function performInstallation() {
        try {
            // 1. Créer le fichier .env
            $this->createEnvFile();
            
            // 2. Installer les dépendances si Node.js disponible
            if ($this->config['environment'] !== 'manual') {
                $this->installDependencies();
            }
            
            // 3. Démarrer Docker PostgreSQL si nécessaire
            if ($this->config['db_type'] === 'docker') {
                $this->startDockerPostgres();
            }
            
            // 4. Préparer les scripts de démarrage
            $this->createStartupScripts();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function createEnvFile() {
        $dbUrl = $this->getDatabaseUrl();
        
        $envContent = "# Configuration StacGateLMS React/Node.js - Générée automatiquement\n";
        $envContent .= "# Date: " . date('Y-m-d H:i:s') . "\n\n";
        $envContent .= "NODE_ENV=development\n";
        $envContent .= "PORT=5000\n";
        $envContent .= "VITE_PORT=3000\n\n";
        $envContent .= "DATABASE_URL=\"{$dbUrl}\"\n\n";
        $envContent .= "APP_NAME=\"{$this->config['app_name']}\"\n";
        $envContent .= "APP_URL=\"{$this->config['app_url']}\"\n\n";
        $envContent .= "JWT_SECRET=\"" . $this->generateSecret() . "\"\n";
        $envContent .= "SESSION_SECRET=\"" . $this->generateSecret() . "\"\n";
        $envContent .= "CSRF_SECRET=\"" . $this->generateSecret(16) . "\"\n\n";
        $envContent .= "ADMIN_EMAIL=\"{$this->config['admin_email']}\"\n";
        $envContent .= "ADMIN_PASSWORD=\"{$this->config['admin_password']}\"\n\n";
        $envContent .= "INSTALL_DEMO_DATA=\"" . ($this->config['install_demo'] ? 'true' : 'false') . "\"\n\n";
        $envContent .= "VITE_API_URL=http://localhost:5000\n";
        $envContent .= "VITE_APP_NAME=\"{$this->config['app_name']}\"\n";
        
        file_put_contents('../.env', $envContent);
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
    
    private function installDependencies() {
        if ($this->checkNode()['status']) {
            $output = shell_exec('cd .. && npm install 2>&1');
            if (strpos($output, 'error') !== false) {
                throw new Exception("Erreur installation NPM: " . $output);
            }
        }
    }
    
    private function startDockerPostgres() {
        if ($this->checkDocker()['status']) {
            shell_exec('cd .. && docker-compose -f docker-compose.dev.yml up -d postgres 2>&1');
            sleep(5); // Attendre que PostgreSQL soit prêt
        }
    }
    
    private function createStartupScripts() {
        // Script pour finaliser l'installation
        $finalizeScript = "#!/bin/bash\n";
        $finalizeScript .= "echo 'Finalisation de l'installation StacGateLMS...'\n";
        $finalizeScript .= "cd " . dirname(__DIR__) . "\n";
        
        if ($this->checkNode()['status']) {
            $finalizeScript .= "echo 'Migration de la base de données...'\n";
            $finalizeScript .= "npm run db:push\n";
            $finalizeScript .= "echo 'Création du compte administrateur...'\n";
            $finalizeScript .= "node scripts/seed-admin.js\n";
            
            if ($this->config['install_demo']) {
                $finalizeScript .= "echo 'Installation des données de démonstration...'\n";
                $finalizeScript .= "node scripts/seed-demo.js\n";
            }
            
            $finalizeScript .= "echo 'Démarrage de l'application...'\n";
            $finalizeScript .= "npm run dev\n";
        } else {
            $finalizeScript .= "echo 'Node.js requis pour finaliser l'installation'\n";
            $finalizeScript .= "echo 'Installez Node.js 18+ puis exécutez: npm install && npm run dev'\n";
        }
        
        file_put_contents('../finalize-install.sh', $finalizeScript);
        chmod('../finalize-install.sh', 0755);
    }
    
    private function render() {
        $stepMethod = 'render' . ucfirst($this->steps[$this->currentStep]);
        if (method_exists($this, $stepMethod)) {
            $this->$stepMethod();
        }
    }
    
    private function renderWelcome() {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Installation StacGateLMS React/Node.js</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
                .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 40px; text-align: center; }
                .content { padding: 40px; }
                .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
                .step { width: 30px; height: 30px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center; margin: 0 5px; }
                .step.active { background: #667eea; color: white; }
                .step.completed { background: #28a745; color: white; }
                .btn { background: #667eea; color: white; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-size: 16px; }
                .btn:hover { background: #5a6fd8; }
                .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
                .feature { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; }
                .feature h3 { margin: 0 0 10px 0; color: #667eea; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🚀 StacGateLMS React/Node.js</h1>
                    <p>Installation Plug & Play avec Interface PHP</p>
                </div>
                
                <div class="content">
                    <div class="step-indicator">
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <div class="step <?= $i == $this->currentStep ? 'active' : ($i < $this->currentStep ? 'completed' : '') ?>"><?= $i ?></div>
                        <?php endfor; ?>
                    </div>
                    
                    <h2>🎉 Bienvenue dans l'installation StacGateLMS !</h2>
                    
                    <p>Cette plateforme d'apprentissage moderne va être configurée automatiquement en quelques minutes.</p>
                    
                    <div class="features">
                        <div class="feature">
                            <h3>⚛️ React Moderne</h3>
                            <p>Interface SPA avec TypeScript</p>
                        </div>
                        <div class="feature">
                            <h3>🚀 Node.js Express</h3>
                            <p>API robuste et performante</p>
                        </div>
                        <div class="feature">
                            <h3>🗄️ PostgreSQL</h3>
                            <p>Base de données relationnelle</p>
                        </div>
                        <div class="feature">
                            <h3>🐳 Docker Ready</h3>
                            <p>Déploiement simplifié</p>
                        </div>
                    </div>
                    
                    <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;">
                        <h3 style="margin-top: 0;">📋 Ce qui sera installé :</h3>
                        <ul>
                            <li>✅ Frontend React 18 + TypeScript + Vite</li>
                            <li>✅ Backend Node.js + Express + Drizzle ORM</li>
                            <li>✅ PostgreSQL avec Docker ou configuration manuelle</li>
                            <li>✅ Interface Shadcn/ui + Tailwind CSS</li>
                            <li>✅ Système d'authentification sécurisé</li>
                            <li>✅ Architecture multi-établissements</li>
                        </ul>
                    </div>
                    
                    <p><strong>Durée estimée :</strong> 3-7 minutes</p>
                    
                    <form method="post">
                        <input type="hidden" name="step" value="1">
                        <button type="submit" name="proceed" value="yes" class="btn">Commencer l'installation</button>
                    </form>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function renderRequirements() {
        $requirements = $this->checkRequirements();
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Vérification des Prérequis</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
                .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 40px; text-align: center; }
                .content { padding: 40px; }
                .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
                .step { width: 30px; height: 30px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center; margin: 0 5px; }
                .step.active { background: #667eea; color: white; }
                .step.completed { background: #28a745; color: white; }
                .requirement { display: flex; justify-content: space-between; align-items: center; padding: 15px; margin: 10px 0; background: #f8f9fa; border-radius: 8px; }
                .requirement.passed { background: #d4edda; border-left: 4px solid #28a745; }
                .requirement.failed { background: #f8d7da; border-left: 4px solid #dc3545; }
                .requirement.warning { background: #fff3cd; border-left: 4px solid #ffc107; }
                .btn { background: #667eea; color: white; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-size: 16px; }
                .btn:hover { background: #5a6fd8; }
                .btn:disabled { background: #ccc; cursor: not-allowed; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔍 Vérification des Prérequis</h1>
                </div>
                
                <div class="content">
                    <div class="step-indicator">
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <div class="step <?= $i == $this->currentStep ? 'active' : ($i < $this->currentStep ? 'completed' : '') ?>"><?= $i ?></div>
                        <?php endfor; ?>
                    </div>
                    
                    <h2>Diagnostic du système</h2>
                    
                    <?php foreach ($requirements as $name => $req): ?>
                        <?php if ($name === 'critical_passed') continue; ?>
                        <div class="requirement <?= $req['status'] ? 'passed' : ($name === 'docker' ? 'warning' : 'failed') ?>">
                            <span><strong><?= ucfirst(str_replace('_', ' ', $name)) ?></strong></span>
                            <span><?= $req['status'] ? '✅' : '❌' ?> <?= $req['message'] ?></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="margin: 30px 0; padding: 20px; background: <?= $requirements['critical_passed'] ? '#d4edda' : '#f8d7da' ?>; border-radius: 8px;">
                        <?php if ($requirements['critical_passed']): ?>
                            <h3 style="color: #155724; margin-top: 0;">✅ Prérequis critiques satisfaits</h3>
                            <p>Votre système est prêt pour l'installation.</p>
                        <?php else: ?>
                            <h3 style="color: #721c24; margin-top: 0;">❌ Prérequis manquants</h3>
                            <p>PHP 7.4+ et les permissions d'écriture sont requis.</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$requirements['node']['status']): ?>
                        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;">
                            <h4>💡 Node.js non détecté</h4>
                            <p>L'installation peut continuer, mais vous devrez installer Node.js 18+ manuellement pour finaliser la configuration.</p>
                            <p><strong>Installation Node.js :</strong></p>
                            <ul>
                                <li>Windows/Mac : <a href="https://nodejs.org/" target="_blank">https://nodejs.org/</a></li>
                                <li>Ubuntu : <code>curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -</code></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <input type="hidden" name="step" value="2">
                        <button type="submit" class="btn" <?= $requirements['critical_passed'] ? '' : 'disabled' ?>>
                            Continuer
                        </button>
                    </form>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function renderEnvironment() {
        $nodeAvailable = $this->checkNode()['status'];
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Configuration Environnement</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
                .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 40px; text-align: center; }
                .content { padding: 40px; }
                .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
                .step { width: 30px; height: 30px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center; margin: 0 5px; }
                .step.active { background: #667eea; color: white; }
                .step.completed { background: #28a745; color: white; }
                .option { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 8px; border: 2px solid transparent; cursor: pointer; }
                .option:hover { border-color: #667eea; }
                .option input[type="radio"] { margin-right: 10px; }
                .form-group { margin: 20px 0; }
                .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
                .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
                .btn { background: #667eea; color: white; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-size: 16px; }
                .btn:hover { background: #5a6fd8; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>⚙️ Configuration Environnement</h1>
                </div>
                
                <div class="content">
                    <div class="step-indicator">
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <div class="step <?= $i == $this->currentStep ? 'active' : ($i < $this->currentStep ? 'completed' : '') ?>"><?= $i ?></div>
                        <?php endfor; ?>
                    </div>
                    
                    <h2>Mode d'installation</h2>
                    
                    <form method="post" id="envForm">
                        <input type="hidden" name="step" value="3">
                        
                        <?php if ($nodeAvailable): ?>
                            <div class="option">
                                <label>
                                    <input type="radio" name="env_choice" value="auto" checked>
                                    <strong>🚀 Installation Automatique (Recommandé)</strong>
                                    <p>Node.js détecté. Installation complètement automatisée avec NPM.</p>
                                </label>
                            </div>
                        <?php endif; ?>
                        
                        <div class="option">
                            <label>
                                <input type="radio" name="env_choice" value="docker" <?= !$nodeAvailable ? 'checked' : '' ?>>
                                <strong>🐳 Docker + Configuration Manuelle</strong>
                                <p>Utilise Docker pour PostgreSQL, configuration des fichiers uniquement.</p>
                            </label>
                        </div>
                        
                        <div class="option">
                            <label>
                                <input type="radio" name="env_choice" value="manual">
                                <strong>⚙️ Configuration Manuelle Complète</strong>
                                <p>Spécifiez manuellement les chemins Node.js et NPM.</p>
                            </label>
                        </div>
                        
                        <div id="manualConfig" style="display: none; margin-top: 20px; padding: 20px; background: #e3f2fd; border-radius: 8px;">
                            <h3>Chemins manuels</h3>
                            <div class="form-group">
                                <label>Chemin vers Node.js :</label>
                                <input type="text" name="node_path" placeholder="/usr/bin/node ou C:\Program Files\nodejs\node.exe">
                            </div>
                            <div class="form-group">
                                <label>Chemin vers NPM :</label>
                                <input type="text" name="npm_path" placeholder="/usr/bin/npm ou C:\Program Files\nodejs\npm.cmd">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn">Continuer</button>
                    </form>
                </div>
            </div>
            
            <script>
                document.querySelectorAll('input[name="env_choice"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        document.getElementById('manualConfig').style.display = 
                            this.value === 'manual' ? 'block' : 'none';
                    });
                });
            </script>
        </body>
        </html>
        <?php
    }
    
    private function renderDatabase() {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Configuration Base de Données</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
                .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 40px; text-align: center; }
                .content { padding: 40px; }
                .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
                .step { width: 30px; height: 30px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center; margin: 0 5px; }
                .step.active { background: #667eea; color: white; }
                .step.completed { background: #28a745; color: white; }
                .option { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 8px; border: 2px solid transparent; cursor: pointer; }
                .option:hover { border-color: #667eea; }
                .option input[type="radio"] { margin-right: 10px; }
                .form-group { margin: 15px 0; }
                .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
                .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
                .btn { background: #667eea; color: white; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-size: 16px; }
                .btn:hover { background: #5a6fd8; }
                .config-section { display: none; margin-top: 20px; padding: 20px; background: #e3f2fd; border-radius: 8px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🗄️ Configuration Base de Données</h1>
                </div>
                
                <div class="content">
                    <div class="step-indicator">
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <div class="step <?= $i == $this->currentStep ? 'active' : ($i < $this->currentStep ? 'completed' : '') ?>"><?= $i ?></div>
                        <?php endfor; ?>
                    </div>
                    
                    <h2>Options PostgreSQL disponibles</h2>
                    
                    <form method="post">
                        <input type="hidden" name="step" value="4">
                        
                        <div class="option">
                            <label>
                                <input type="radio" name="db_choice" value="docker" checked>
                                <strong>🐳 PostgreSQL Docker (Recommandé)</strong>
                                <p>Installation automatique avec Docker Compose. Aucune configuration requise.</p>
                            </label>
                        </div>
                        
                        <div class="option">
                            <label>
                                <input type="radio" name="db_choice" value="local">
                                <strong>🖥️ PostgreSQL Local</strong>
                                <p>Utilise une installation PostgreSQL existante sur votre système.</p>
                            </label>
                        </div>
                        
                        <div class="option">
                            <label>
                                <input type="radio" name="db_choice" value="cloud">
                                <strong>☁️ Service Cloud</strong>
                                <p>Neon, Supabase, Railway ou autre service PostgreSQL cloud.</p>
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
                                <label>Nom de la base :</label>
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
                            <h3>Configuration Service Cloud</h3>
                            <div class="form-group">
                                <label>URL de connexion complète :</label>
                                <input type="text" name="db_url" placeholder="postgresql://user:pass@host:5432/database">
                            </div>
                            <p><small>Exemples :</small></p>
                            <ul>
                                <li><small>Neon: postgresql://user:pass@host.neon.tech/dbname</small></li>
                                <li><small>Supabase: postgresql://user:pass@host.pooler.supabase.com:5432/postgres</small></li>
                            </ul>
                        </div>
                        
                        <button type="submit" class="btn">Continuer</button>
                    </form>
                </div>
            </div>
            
            <script>
                document.querySelectorAll('input[name="db_choice"]').forEach(radio => {
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
    
    private function renderConfiguration() {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Configuration Application</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
                .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 40px; text-align: center; }
                .content { padding: 40px; }
                .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
                .step { width: 30px; height: 30px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center; margin: 0 5px; }
                .step.active { background: #667eea; color: white; }
                .step.completed { background: #28a745; color: white; }
                .form-group { margin: 20px 0; }
                .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
                .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
                .form-group input[type="checkbox"] { width: auto; }
                .btn { background: #667eea; color: white; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-size: 16px; }
                .btn:hover { background: #5a6fd8; }
                .summary { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>⚙️ Configuration Application</h1>
                </div>
                
                <div class="content">
                    <div class="step-indicator">
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <div class="step <?= $i == $this->currentStep ? 'active' : ($i < $this->currentStep ? 'completed' : '') ?>"><?= $i ?></div>
                        <?php endfor; ?>
                    </div>
                    
                    <h2>Paramètres de l'application</h2>
                    
                    <form method="post">
                        <input type="hidden" name="step" value="5">
                        
                        <div class="form-group">
                            <label>Nom de l'application :</label>
                            <input type="text" name="app_name" value="StacGateLMS" required>
                        </div>
                        
                        <div class="form-group">
                            <label>URL de l'application :</label>
                            <input type="url" name="app_url" value="http://localhost:5000" required>
                        </div>
                        
                        <h3>Compte Super Administrateur</h3>
                        
                        <div class="form-group">
                            <label>Email administrateur :</label>
                            <input type="email" name="admin_email" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Mot de passe :</label>
                            <input type="password" name="admin_password" minlength="6" required>
                            <small>Minimum 6 caractères</small>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="install_demo" checked>
                                Installer les données de démonstration
                            </label>
                            <small>Inclut des cours d'exemple, utilisateurs de test et contenu de démonstration</small>
                        </div>
                        
                        <button type="submit" class="btn">Confirmer et Installer</button>
                    </form>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function renderInstallation() {
        $error = $this->config['install_error'] ?? null;
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Installation en Cours</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
                .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 40px; text-align: center; }
                .content { padding: 40px; }
                .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
                .step { width: 30px; height: 30px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center; margin: 0 5px; }
                .step.active { background: #667eea; color: white; }
                .step.completed { background: #28a745; color: white; }
                .progress { width: 100%; height: 20px; background: #f0f0f0; border-radius: 10px; overflow: hidden; margin: 20px 0; }
                .progress-bar { height: 100%; background: linear-gradient(45deg, #667eea, #764ba2); transition: width 0.5s ease; }
                .task { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #667eea; }
                .task.completed { background: #d4edda; border-left-color: #28a745; }
                .task.error { background: #f8d7da; border-left-color: #dc3545; }
                .btn { background: #667eea; color: white; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-size: 16px; }
                .btn:hover { background: #5a6fd8; }
                .error { background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc3545; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🚀 Installation en Cours</h1>
                </div>
                
                <div class="content">
                    <div class="step-indicator">
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <div class="step <?= $i == $this->currentStep ? 'active' : ($i < $this->currentStep ? 'completed' : '') ?>"><?= $i ?></div>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="error">
                            <h3>❌ Erreur d'installation</h3>
                            <p><?= htmlspecialchars($error) ?></p>
                            <form method="post">
                                <input type="hidden" name="step" value="6">
                                <button type="submit" class="btn">Réessayer</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <h2>Configuration en cours...</h2>
                        
                        <div class="progress">
                            <div class="progress-bar" style="width: 100%;"></div>
                        </div>
                        
                        <div class="task completed">
                            ✅ Création du fichier .env
                        </div>
                        
                        <div class="task completed">
                            ✅ Configuration des variables d'environnement
                        </div>
                        
                        <?php if ($this->config['db_type'] === 'docker'): ?>
                            <div class="task completed">
                                ✅ Préparation Docker PostgreSQL
                            </div>
                        <?php endif; ?>
                        
                        <div class="task completed">
                            ✅ Génération des scripts de finalisation
                        </div>
                        
                        <form method="post">
                            <input type="hidden" name="step" value="6">
                            <button type="submit" class="btn">Finaliser l'installation</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function renderComplete() {
        $nodeAvailable = $this->checkNode()['status'];
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Installation Terminée</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
                .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 40px; text-align: center; }
                .content { padding: 40px; }
                .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
                .step { width: 30px; height: 30px; border-radius: 50%; background: #28a745; color: white; display: flex; align-items: center; justify-content: center; margin: 0 5px; }
                .info-box { background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745; }
                .warning-box { background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107; }
                .btn { background: #28a745; color: white; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
                .btn:hover { background: #218838; }
                .btn-secondary { background: #6c757d; }
                .btn-secondary:hover { background: #5a6268; }
                .next-steps { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎉 Installation Terminée !</h1>
                    <p>StacGateLMS React/Node.js est maintenant configuré</p>
                </div>
                
                <div class="content">
                    <div class="step-indicator">
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <div class="step">✓</div>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="info-box">
                        <h3>✅ Configuration réussie</h3>
                        <p>Votre plateforme d'apprentissage React/Node.js est prête !</p>
                        <ul>
                            <li><strong>Application :</strong> <?= htmlspecialchars($this->config['app_name']) ?></li>
                            <li><strong>URL :</strong> <?= htmlspecialchars($this->config['app_url']) ?></li>
                            <li><strong>Admin :</strong> <?= htmlspecialchars($this->config['admin_email']) ?></li>
                            <li><strong>Base de données :</strong> <?= ucfirst($this->config['db_type']) ?> PostgreSQL</li>
                            <li><strong>Données démo :</strong> <?= $this->config['install_demo'] ? 'Activées' : 'Désactivées' ?></li>
                        </ul>
                    </div>
                    
                    <?php if ($nodeAvailable): ?>
                        <div class="info-box">
                            <h3>🚀 Prochaines étapes automatiques</h3>
                            <p>Node.js est disponible. L'installation peut être finalisée automatiquement :</p>
                            <ol>
                                <li>Exécution des migrations de base de données</li>
                                <li>Création du compte super administrateur</li>
                                <li>Installation des données de démonstration (si activées)</li>
                                <li>Démarrage de l'application React + Node.js</li>
                            </ol>
                            <a href="../finalize-install.sh" class="btn" onclick="runFinalization()">Finaliser et Démarrer</a>
                        </div>
                    <?php else: ?>
                        <div class="warning-box">
                            <h3>⚠️ Étapes manuelles requises</h3>
                            <p>Node.js n'est pas disponible. Veuillez compléter l'installation manuellement :</p>
                            <ol>
                                <li><strong>Installez Node.js 18+ :</strong> <a href="https://nodejs.org/" target="_blank">https://nodejs.org/</a></li>
                                <li><strong>Installez les dépendances :</strong> <code>npm install</code></li>
                                <li><strong>Migrez la base :</strong> <code>npm run db:push</code></li>
                                <li><strong>Créez l'admin :</strong> <code>node scripts/seed-admin.js</code></li>
                                <li><strong>Démarrez l'app :</strong> <code>npm run dev</code></li>
                            </ol>
                        </div>
                    <?php endif; ?>
                    
                    <div class="next-steps">
                        <h3>📱 Accès aux interfaces</h3>
                        <ul>
                            <li><strong>Frontend React :</strong> <a href="http://localhost:3000" target="_blank">http://localhost:3000</a></li>
                            <li><strong>API Backend :</strong> <a href="http://localhost:5000" target="_blank">http://localhost:5000</a></li>
                            <li><strong>Documentation API :</strong> <a href="http://localhost:5000/api-docs" target="_blank">http://localhost:5000/api-docs</a></li>
                            <?php if ($this->config['db_type'] === 'docker'): ?>
                                <li><strong>Adminer (BDD) :</strong> <a href="http://localhost:8081" target="_blank">http://localhost:8081</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="/" class="btn btn-secondary">Nouvelle Installation</a>
                        <a href="<?= $this->config['app_url'] ?>" class="btn" target="_blank">Ouvrir l'Application</a>
                    </div>
                    
                    <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center;">
                        <small>
                            Installation terminée en <?= date('H:i:s') ?><br>
                            Configuration sauvegardée dans .env
                        </small>
                    </div>
                </div>
            </div>
            
            <script>
                function runFinalization() {
                    if (confirm('Finaliser l\'installation automatiquement ?')) {
                        // Exécuter le script de finalisation
                        fetch('../finalize-install.sh')
                            .then(() => {
                                alert('Finalisation en cours. L\'application se lancera automatiquement.');
                            })
                            .catch(() => {
                                alert('Exécutez manuellement: ./finalize-install.sh');
                            });
                    }
                }
                
                // Nettoyer la session à la fin
                <?php 
                    session_destroy(); 
                ?>
            </script>
        </body>
        </html>
        <?php
    }
}

// Lancement de l'installateur
$installer = new ReactInstaller();
$installer->run();
?>
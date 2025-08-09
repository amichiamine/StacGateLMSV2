<?php
/**
 * Script PHP de déploiement automatique pour StacGateLMS React/Node.js
 * Gère le déploiement sur différents environnements
 */

class ReactDeployer {
    
    private $environments = [
        'vercel' => 'Vercel (Frontend + Serverless)',
        'railway' => 'Railway (Full-Stack)',
        'render' => 'Render (Full-Stack)',
        'vps' => 'Serveur VPS/Dédié',
        'docker' => 'Docker (Production)'
    ];
    
    private $config = [];
    
    public function __construct() {
        session_start();
        $this->config = $_SESSION['deploy_config'] ?? [];
    }
    
    public function run() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        } else {
            $this->showEnvironmentChoice();
        }
    }
    
    private function handlePost() {
        $step = $_POST['step'] ?? 1;
        
        switch ($step) {
            case 1:
                $this->handleEnvironmentChoice();
                break;
            case 2:
                $this->handleConfiguration();
                break;
            case 3:
                $this->performDeployment();
                break;
        }
    }
    
    private function handleEnvironmentChoice() {
        $this->config['environment'] = $_POST['environment'] ?? 'vercel';
        $_SESSION['deploy_config'] = $this->config;
        $this->showConfiguration();
    }
    
    private function handleConfiguration() {
        $this->config['domain'] = $_POST['domain'] ?? '';
        $this->config['database_url'] = $_POST['database_url'] ?? '';
        $this->config['jwt_secret'] = $_POST['jwt_secret'] ?? $this->generateSecret();
        $this->config['session_secret'] = $_POST['session_secret'] ?? $this->generateSecret();
        $this->config['app_name'] = $_POST['app_name'] ?? 'StacGateLMS';
        
        $_SESSION['deploy_config'] = $this->config;
        $this->showDeploymentSummary();
    }
    
    private function performDeployment() {
        $environment = $this->config['environment'];
        
        try {
            switch ($environment) {
                case 'vercel':
                    $result = $this->deployToVercel();
                    break;
                case 'railway':
                    $result = $this->deployToRailway();
                    break;
                case 'render':
                    $result = $this->deployToRender();
                    break;
                case 'vps':
                    $result = $this->deployToVPS();
                    break;
                case 'docker':
                    $result = $this->deployDocker();
                    break;
                default:
                    throw new Exception('Environnement non supporté');
            }
            
            $this->showSuccess($result);
            
        } catch (Exception $e) {
            $this->showError($e->getMessage());
        }
    }
    
    private function deployToVercel() {
        // Créer vercel.json
        $vercelConfig = [
            'version' => 2,
            'builds' => [
                [
                    'src' => 'package.json',
                    'use' => '@vercel/node'
                ]
            ],
            'routes' => [
                [
                    'src' => '/api/(.*)',
                    'dest' => '/server/index.js'
                ],
                [
                    'src' => '/(.*)',
                    'dest' => '/client/dist/$1'
                ]
            ],
            'env' => [
                'NODE_ENV' => 'production',
                'DATABASE_URL' => '@database_url',
                'JWT_SECRET' => '@jwt_secret',
                'SESSION_SECRET' => '@session_secret',
                'APP_NAME' => $this->config['app_name']
            ]
        ];
        
        file_put_contents('../vercel.json', json_encode($vercelConfig, JSON_PRETTY_PRINT));
        
        // Créer script de build
        $buildScript = "#!/bin/bash\n";
        $buildScript .= "npm install\n";
        $buildScript .= "npm run build\n";
        
        file_put_contents('../build.sh', $buildScript);
        chmod('../build.sh', 0755);
        
        return [
            'type' => 'vercel',
            'files' => ['vercel.json', 'build.sh'],
            'next_steps' => [
                'Connectez votre repository GitHub à Vercel',
                'Configurez les variables d\'environnement',
                'Déployez automatiquement'
            ]
        ];
    }
    
    private function deployToRailway() {
        // Créer railway.toml
        $railwayConfig = "[build]\n";
        $railwayConfig .= "builder = \"nixpacks\"\n\n";
        $railwayConfig .= "[deploy]\n";
        $railwayConfig .= "startCommand = \"npm run start:production\"\n\n";
        $railwayConfig .= "[env]\n";
        $railwayConfig .= "NODE_ENV = \"production\"\n";
        
        file_put_contents('../railway.toml', $railwayConfig);
        
        // Script de déploiement Railway
        $deployScript = "#!/bin/bash\n";
        $deployScript .= "echo 'Déploiement Railway...'\n";
        $deployScript .= "railway login\n";
        $deployScript .= "railway init\n";
        $deployScript .= "railway add postgresql\n";
        $deployScript .= "railway deploy\n";
        
        file_put_contents('../deploy-railway.sh', $deployScript);
        chmod('../deploy-railway.sh', 0755);
        
        return [
            'type' => 'railway',
            'files' => ['railway.toml', 'deploy-railway.sh'],
            'next_steps' => [
                'Installez Railway CLI',
                'Exécutez ./deploy-railway.sh',
                'Configurez les variables d\'environnement'
            ]
        ];
    }
    
    private function deployToRender() {
        // Créer render.yaml
        $renderConfig = [
            'services' => [
                [
                    'type' => 'web',
                    'name' => 'stacgatelms',
                    'env' => 'node',
                    'buildCommand' => 'npm install && npm run build',
                    'startCommand' => 'npm run start:production',
                    'envVars' => [
                        [
                            'key' => 'NODE_ENV',
                            'value' => 'production'
                        ],
                        [
                            'key' => 'DATABASE_URL',
                            'fromDatabase' => [
                                'name' => 'stacgate-db',
                                'property' => 'connectionString'
                            ]
                        ]
                    ]
                ]
            ],
            'databases' => [
                [
                    'name' => 'stacgate-db',
                    'databaseName' => 'stacgatelms',
                    'user' => 'stacgate'
                ]
            ]
        ];
        
        file_put_contents('../render.yaml', "# render.yaml\n" . yaml_emit($renderConfig));
        
        return [
            'type' => 'render',
            'files' => ['render.yaml'],
            'next_steps' => [
                'Connectez votre repository à Render',
                'Le déploiement se fera automatiquement',
                'Configurez le domaine personnalisé'
            ]
        ];
    }
    
    private function deployToVPS() {
        // Script d'installation VPS
        $vpsScript = "#!/bin/bash\n\n";
        $vpsScript .= "# Installation StacGateLMS sur VPS\n";
        $vpsScript .= "echo 'Installation StacGateLMS sur VPS...'\n\n";
        
        $vpsScript .= "# Mise à jour système\n";
        $vpsScript .= "sudo apt update && sudo apt upgrade -y\n\n";
        
        $vpsScript .= "# Installation Node.js 18\n";
        $vpsScript .= "curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -\n";
        $vpsScript .= "sudo apt-get install -y nodejs\n\n";
        
        $vpsScript .= "# Installation PostgreSQL\n";
        $vpsScript .= "sudo apt install -y postgresql postgresql-contrib\n";
        $vpsScript .= "sudo systemctl start postgresql\n";
        $vpsScript .= "sudo systemctl enable postgresql\n\n";
        
        $vpsScript .= "# Configuration PostgreSQL\n";
        $vpsScript .= "sudo -u postgres createuser --interactive stacgate\n";
        $vpsScript .= "sudo -u postgres createdb stacgatelms\n\n";
        
        $vpsScript .= "# Installation de l'application\n";
        $vpsScript .= "git clone [REPOSITORY_URL] /var/www/stacgatelms\n";
        $vpsScript .= "cd /var/www/stacgatelms\n";
        $vpsScript .= "npm install\n";
        $vpsScript .= "npm run build\n\n";
        
        $vpsScript .= "# Installation PM2\n";
        $vpsScript .= "sudo npm install -g pm2\n";
        $vpsScript .= "pm2 start npm --name 'stacgate' -- run start:production\n";
        $vpsScript .= "pm2 startup\n";
        $vpsScript .= "pm2 save\n\n";
        
        $vpsScript .= "# Configuration Nginx\n";
        $vpsScript .= "sudo apt install -y nginx\n";
        $vpsScript .= "# Créer configuration Nginx...\n";
        
        file_put_contents('../install-vps.sh', $vpsScript);
        chmod('../install-vps.sh', 0755);
        
        // Configuration Nginx
        $nginxConfig = "server {\n";
        $nginxConfig .= "    listen 80;\n";
        $nginxConfig .= "    server_name {$this->config['domain']};\n\n";
        $nginxConfig .= "    location / {\n";
        $nginxConfig .= "        proxy_pass http://localhost:5000;\n";
        $nginxConfig .= "        proxy_http_version 1.1;\n";
        $nginxConfig .= "        proxy_set_header Upgrade \$http_upgrade;\n";
        $nginxConfig .= "        proxy_set_header Connection 'upgrade';\n";
        $nginxConfig .= "        proxy_set_header Host \$host;\n";
        $nginxConfig .= "        proxy_cache_bypass \$http_upgrade;\n";
        $nginxConfig .= "    }\n";
        $nginxConfig .= "}\n";
        
        file_put_contents('../nginx.conf', $nginxConfig);
        
        return [
            'type' => 'vps',
            'files' => ['install-vps.sh', 'nginx.conf'],
            'next_steps' => [
                'Uploadez les fichiers sur votre VPS',
                'Exécutez ./install-vps.sh',
                'Configurez SSL avec Certbot'
            ]
        ];
    }
    
    private function deployDocker() {
        // Dockerfile production
        $dockerfile = "FROM node:18-alpine\n\n";
        $dockerfile .= "WORKDIR /app\n\n";
        $dockerfile .= "# Copier package.json\n";
        $dockerfile .= "COPY package*.json ./\n";
        $dockerfile .= "RUN npm ci --only=production\n\n";
        $dockerfile .= "# Copier le code source\n";
        $dockerfile .= "COPY . .\n\n";
        $dockerfile .= "# Build l'application\n";
        $dockerfile .= "RUN npm run build\n\n";
        $dockerfile .= "# Exposer le port\n";
        $dockerfile .= "EXPOSE 5000\n\n";
        $dockerfile .= "# Démarrer l'application\n";
        $dockerfile .= "CMD [\"npm\", \"run\", \"start:production\"]\n";
        
        file_put_contents('../Dockerfile.prod', $dockerfile);
        
        // Docker Compose production
        $dockerCompose = "version: '3.8'\n\n";
        $dockerCompose .= "services:\n";
        $dockerCompose .= "  app:\n";
        $dockerCompose .= "    build:\n";
        $dockerCompose .= "      context: .\n";
        $dockerCompose .= "      dockerfile: Dockerfile.prod\n";
        $dockerCompose .= "    ports:\n";
        $dockerCompose .= "      - \"5000:5000\"\n";
        $dockerCompose .= "    environment:\n";
        $dockerCompose .= "      - NODE_ENV=production\n";
        $dockerCompose .= "      - DATABASE_URL=\${DATABASE_URL}\n";
        $dockerCompose .= "      - JWT_SECRET=\${JWT_SECRET}\n";
        $dockerCompose .= "      - SESSION_SECRET=\${SESSION_SECRET}\n";
        $dockerCompose .= "    depends_on:\n";
        $dockerCompose .= "      - postgres\n";
        $dockerCompose .= "    restart: unless-stopped\n\n";
        
        $dockerCompose .= "  postgres:\n";
        $dockerCompose .= "    image: postgres:15-alpine\n";
        $dockerCompose .= "    environment:\n";
        $dockerCompose .= "      - POSTGRES_DB=stacgatelms\n";
        $dockerCompose .= "      - POSTGRES_USER=stacgate\n";
        $dockerCompose .= "      - POSTGRES_PASSWORD=stacgate123\n";
        $dockerCompose .= "    volumes:\n";
        $dockerCompose .= "      - postgres_data:/var/lib/postgresql/data\n";
        $dockerCompose .= "    restart: unless-stopped\n\n";
        
        $dockerCompose .= "  nginx:\n";
        $dockerCompose .= "    image: nginx:alpine\n";
        $dockerCompose .= "    ports:\n";
        $dockerCompose .= "      - \"80:80\"\n";
        $dockerCompose .= "      - \"443:443\"\n";
        $dockerCompose .= "    volumes:\n";
        $dockerCompose .= "      - ./nginx.conf:/etc/nginx/conf.d/default.conf\n";
        $dockerCompose .= "    depends_on:\n";
        $dockerCompose .= "      - app\n";
        $dockerCompose .= "    restart: unless-stopped\n\n";
        
        $dockerCompose .= "volumes:\n";
        $dockerCompose .= "  postgres_data:\n";
        
        file_put_contents('../docker-compose.prod.yml', $dockerCompose);
        
        // Script de déploiement Docker
        $deployScript = "#!/bin/bash\n\n";
        $deployScript .= "echo 'Déploiement Docker Production...'\n\n";
        $deployScript .= "# Arrêt des containers existants\n";
        $deployScript .= "docker-compose -f docker-compose.prod.yml down\n\n";
        $deployScript .= "# Build et démarrage\n";
        $deployScript .= "docker-compose -f docker-compose.prod.yml up --build -d\n\n";
        $deployScript .= "# Attendre que PostgreSQL soit prêt\n";
        $deployScript .= "sleep 10\n\n";
        $deployScript .= "# Migration de la base\n";
        $deployScript .= "docker-compose -f docker-compose.prod.yml exec app npm run db:push\n\n";
        $deployScript .= "echo 'Déploiement terminé !'\n";
        $deployScript .= "echo 'Application accessible sur http://localhost'\n";
        
        file_put_contents('../deploy-docker.sh', $deployScript);
        chmod('../deploy-docker.sh', 0755);
        
        return [
            'type' => 'docker',
            'files' => ['Dockerfile.prod', 'docker-compose.prod.yml', 'deploy-docker.sh'],
            'next_steps' => [
                'Exécutez ./deploy-docker.sh',
                'Configurez les variables d\'environnement',
                'Accédez à http://localhost'
            ]
        ];
    }
    
    private function generateSecret($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    private function showEnvironmentChoice() {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Déploiement StacGateLMS React</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .environment { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 6px; border: 2px solid transparent; cursor: pointer; }
                .environment:hover { border-color: #667eea; }
                .environment input[type="radio"] { margin-right: 10px; }
                .btn { background: #667eea; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; }
                .btn:hover { background: #5a6fd8; }
                .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
                .feature { text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🚀 Déploiement StacGateLMS React</h1>
                    <p>Choisissez votre environnement de déploiement</p>
                </div>
                
                <div class="content">
                    <form method="post">
                        <input type="hidden" name="step" value="1">
                        
                        <?php foreach ($this->environments as $key => $name): ?>
                            <div class="environment">
                                <label>
                                    <input type="radio" name="environment" value="<?= $key ?>" <?= $key === 'vercel' ? 'checked' : '' ?>>
                                    <strong><?= $name ?></strong>
                                    <p><?= $this->getEnvironmentDescription($key) ?></p>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="text-align: center; margin: 30px 0;">
                            <button type="submit" class="btn">Continuer</button>
                        </div>
                    </form>
                    
                    <div class="features">
                        <div class="feature">
                            <h3>⚡ Rapide</h3>
                            <p>Déploiement automatisé</p>
                        </div>
                        <div class="feature">
                            <h3>🔒 Sécurisé</h3>
                            <p>HTTPS automatique</p>
                        </div>
                        <div class="feature">
                            <h3>📊 Scalable</h3>
                            <p>Montée en charge</p>
                        </div>
                        <div class="feature">
                            <h3>🌍 Global</h3>
                            <p>CDN intégré</p>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function getEnvironmentDescription($env) {
        $descriptions = [
            'vercel' => 'Déploiement ultra-rapide avec CDN global. Idéal pour le frontend.',
            'railway' => 'Plateforme tout-en-un avec base de données incluse.',
            'render' => 'Alternative gratuite avec auto-scaling et SSL.',
            'vps' => 'Contrôle total sur serveur dédié ou VPS.',
            'docker' => 'Conteneurisation pour portabilité maximale.'
        ];
        
        return $descriptions[$env] ?? '';
    }
    
    private function showConfiguration() {
        $env = $this->config['environment'];
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Configuration Déploiement</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .form-group { margin: 20px 0; }
                .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
                .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
                .btn { background: #667eea; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; }
                .btn:hover { background: #5a6fd8; }
                .info { background: #e3f2fd; padding: 15px; border-radius: 6px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>⚙️ Configuration</h1>
                    <p>Déploiement <?= $this->environments[$env] ?></p>
                </div>
                
                <div class="content">
                    <form method="post">
                        <input type="hidden" name="step" value="2">
                        
                        <div class="form-group">
                            <label>Nom de l'application :</label>
                            <input type="text" name="app_name" value="StacGateLMS" required>
                        </div>
                        
                        <?php if (in_array($env, ['vercel', 'railway', 'render', 'vps'])): ?>
                            <div class="form-group">
                                <label>Domaine (optionnel) :</label>
                                <input type="text" name="domain" placeholder="mon-lms.com">
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>URL Base de données PostgreSQL :</label>
                            <input type="text" name="database_url" placeholder="postgresql://user:pass@host:5432/db" required>
                        </div>
                        
                        <div class="form-group">
                            <label>JWT Secret (généré automatiquement) :</label>
                            <input type="text" name="jwt_secret" value="<?= $this->generateSecret() ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Session Secret (généré automatiquement) :</label>
                            <input type="text" name="session_secret" value="<?= $this->generateSecret() ?>" required>
                        </div>
                        
                        <div class="info">
                            <h3>Information <?= $this->environments[$env] ?></h3>
                            <p><?= $this->getDeploymentInfo($env) ?></p>
                        </div>
                        
                        <button type="submit" class="btn">Générer Configuration</button>
                    </form>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function getDeploymentInfo($env) {
        $info = [
            'vercel' => 'Vercel déploiera automatiquement depuis votre repository GitHub. Le frontend sera servi via CDN global.',
            'railway' => 'Railway gérera automatiquement le build et le déploiement. Une base PostgreSQL sera créée.',
            'render' => 'Render construira et déploiera automatiquement avec SSL gratuit et auto-scaling.',
            'vps' => 'Scripts d\'installation automatique pour Ubuntu/Debian. Nginx sera configuré automatiquement.',
            'docker' => 'Conteneurs Docker avec PostgreSQL, Nginx et SSL. Déploiement en un clic.'
        ];
        
        return $info[$env] ?? '';
    }
    
    private function showDeploymentSummary() {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Résumé Déploiement</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .summary { background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0; }
                .btn { background: #28a745; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; }
                .btn:hover { background: #218838; }
                .btn-secondary { background: #6c757d; margin-left: 10px; }
                .btn-secondary:hover { background: #5a6268; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📋 Résumé du Déploiement</h1>
                    <p>Vérifiez avant génération</p>
                </div>
                
                <div class="content">
                    <div class="summary">
                        <h3>Configuration</h3>
                        <ul>
                            <li><strong>Environnement :</strong> <?= $this->environments[$this->config['environment']] ?></li>
                            <li><strong>Application :</strong> <?= htmlspecialchars($this->config['app_name']) ?></li>
                            <?php if ($this->config['domain']): ?>
                                <li><strong>Domaine :</strong> <?= htmlspecialchars($this->config['domain']) ?></li>
                            <?php endif; ?>
                            <li><strong>Base de données :</strong> Configurée</li>
                            <li><strong>Sécurité :</strong> Secrets générés</li>
                        </ul>
                    </div>
                    
                    <div style="text-align: center;">
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="step" value="3">
                            <button type="submit" class="btn">Générer Déploiement</button>
                        </form>
                        <a href="?" class="btn btn-secondary">Recommencer</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    private function showSuccess($result) {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Déploiement Généré</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 700px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
                .header { background: linear-gradient(45deg, #28a745, #20c997); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .success { background: #d4edda; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #28a745; }
                .files { background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 15px 0; }
                .btn { background: #667eea; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
                .btn:hover { background: #5a6fd8; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>✅ Déploiement Généré</h1>
                    <p>Configuration <?= $this->environments[$this->config['environment']] ?></p>
                </div>
                
                <div class="content">
                    <div class="success">
                        <h3>Fichiers créés avec succès</h3>
                        <p>Tous les fichiers de configuration ont été générés.</p>
                    </div>
                    
                    <div class="files">
                        <h3>📁 Fichiers générés :</h3>
                        <ul>
                            <?php foreach ($result['files'] as $file): ?>
                                <li><code><?= htmlspecialchars($file) ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <h3>🚀 Prochaines étapes :</h3>
                    <ol>
                        <?php foreach ($result['next_steps'] as $step): ?>
                            <li><?= htmlspecialchars($step) ?></li>
                        <?php endforeach; ?>
                    </ol>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="?" class="btn">Nouveau Déploiement</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        
        // Nettoyer la session
        unset($_SESSION['deploy_config']);
    }
    
    private function showError($message) {
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erreur Déploiement</title>
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
                    <h1>❌ Erreur Déploiement</h1>
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

// Lancement du déployeur
$deployer = new ReactDeployer();
$deployer->run();
?>
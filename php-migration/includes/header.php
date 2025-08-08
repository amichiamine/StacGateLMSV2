<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'StacGateLMS') ?></title>
    
    <?php if (isset($pageDescription)): ?>
        <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <?php endif; ?>
    
    <!-- SEO et Partage Social -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle ?? 'StacGateLMS') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription ?? 'Plateforme e-learning moderne') ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= BASE_URL ?>/assets/images/og-image.jpg">
    
    <!-- Styles Glassmorphism -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --color-primary: 139, 92, 246;
            --color-secondary: 167, 139, 250;
            --color-accent: 196, 181, 253;
            --color-success: 34, 197, 94;
            --color-error: 239, 68, 68;
            --color-warning: 245, 158, 11;
            --color-info: 59, 130, 246;
            
            --gradient-primary: linear-gradient(135deg, rgb(139, 92, 246), rgb(167, 139, 250));
            --gradient-secondary: linear-gradient(135deg, rgb(167, 139, 250), rgb(196, 181, 253));
            
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .glassmorphism {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            box-shadow: var(--glass-shadow);
        }
        
        .glass-button {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            font-weight: 500;
            cursor: pointer;
        }
        
        .glass-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .glass-button-secondary {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .glass-input {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .glass-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .glass-input:focus {
            outline: none;
            border-color: rgb(var(--color-primary));
            box-shadow: 0 0 0 2px rgba(var(--color-primary), 0.2);
        }
        
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 0;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
        }
        
        .nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .nav a:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .grid {
            display: grid;
            gap: 2rem;
        }
        
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }
        
        .p-4 { padding: 1.5rem; }
        .p-6 { padding: 2rem; }
        .p-8 { padding: 2.5rem; }
        
        .mb-4 { margin-bottom: 1.5rem; }
        .mb-6 { margin-bottom: 2rem; }
        .mb-8 { margin-bottom: 2.5rem; }
        
        .text-center { text-align: center; }
        
        .hidden { display: none; }
        
        @media (max-width: 768px) {
            .grid-2, .grid-3, .grid-4 {
                grid-template-columns: 1fr;
            }
            
            .nav {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            body {
                padding-top: 120px;
            }
        }
        
        .flash-message {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 2000;
            animation: slideIn 0.3s ease;
        }
        
        .flash-message.success {
            background: rgba(34, 197, 94, 0.9);
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        
        .flash-message.error {
            background: rgba(239, 68, 68, 0.9);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .flash-message.warning {
            background: rgba(245, 158, 11, 0.9);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .flash-message.info {
            background: rgba(59, 130, 246, 0.9);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/assets/images/favicon.ico">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="/" class="logo">
                    🎓 StacGateLMS
                </a>
                
                <nav class="nav">
                    <?php if (Auth::isAuthenticated()): ?>
                        <?php $currentUser = Auth::user(); ?>
                        <a href="/dashboard">📊 Dashboard</a>
                        
                        <?php if (Auth::hasRole('formateur')): ?>
                            <a href="/courses">📚 Cours</a>
                        <?php endif; ?>
                        
                        <?php if (Auth::hasRole('admin')): ?>
                            <a href="/admin">⚙️ Admin</a>
                        <?php endif; ?>
                        
                        <?php if (Auth::hasRole('super_admin')): ?>
                            <a href="/super-admin">👑 Super Admin</a>
                        <?php endif; ?>
                        
                        <a href="/help-center">❓ Aide</a>
                        <a href="/settings">⚙️ Paramètres</a>
                        <a href="/logout" class="glass-button glass-button-secondary">
                            🚪 Déconnexion
                        </a>
                    <?php else: ?>
                        <a href="/portal">🏛️ Portail</a>
                        <a href="/help-center">❓ Aide</a>
                        <a href="/login" class="glass-button">
                            🔐 Connexion
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
    
    <!-- Flash Messages -->
    <?php $flashMessage = Utils::getFlashMessage(); ?>
    <?php if ($flashMessage): ?>
        <div class="flash-message <?= htmlspecialchars($flashMessage['type']) ?>" id="flashMessage">
            <?= htmlspecialchars($flashMessage['text']) ?>
        </div>
        
        <script>
            setTimeout(() => {
                const flash = document.getElementById('flashMessage');
                if (flash) {
                    flash.style.animation = 'slideIn 0.3s ease reverse';
                    setTimeout(() => flash.remove(), 300);
                }
            }, 5000);
        </script>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main>
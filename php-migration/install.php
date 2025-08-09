<?php
/**
 * StacGateLMS - Installateur Web Automatique
 * Interface d'installation plug & play pour tous environnements
 */

// Configuration pour l'installation
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

define('INSTALL_VERSION', '1.0.0');
define('MIN_PHP_VERSION', '8.1.0');
define('ROOT_PATH', __DIR__);

// Action en cours (étape de l'installation)
$action = $_GET['action'] ?? 'welcome';
$step = (int)($_GET['step'] ?? 1);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation StacGateLMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .installer {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            max-width: 800px;
            width: 90%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 2.5em;
            font-weight: bold;
            background: linear-gradient(135deg, #8B5CF6, #A78BFA);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 0 20px;
        }
        .step {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            font-weight: bold;
            position: relative;
        }
        .step.active {
            background: #8B5CF6;
            color: white;
        }
        .step.completed {
            background: #10b981;
            color: white;
        }
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 120px;
            height: 2px;
            background: #e5e7eb;
            transform: translateY(-50%);
        }
        .step.completed:not(:last-child)::after {
            background: #10b981;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .btn {
            background: #8B5CF6;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #7C3AED;
            transform: translateY(-2px);
        }
        .btn.secondary {
            background: #6b7280;
        }
        .btn.secondary:hover {
            background: #4b5563;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #374151;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #8B5CF6;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .alert.warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .check-list {
            list-style: none;
            padding: 0;
        }
        .check-list li {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .check-list li:last-child {
            border-bottom: none;
        }
        .check-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status.ok {
            background: #d1fae5;
            color: #065f46;
        }
        .status.error {
            background: #fee2e2;
            color: #991b1b;
        }
        .status.warning {
            background: #fef3c7;
            color: #92400e;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #8B5CF6, #A78BFA);
            transition: width 0.3s ease;
        }
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .two-column {
                grid-template-columns: 1fr;
            }
            .steps {
                flex-wrap: wrap;
                gap: 10px;
            }
            .step:not(:last-child)::after {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="header">
            <div class="logo">StacGateLMS</div>
            <p>Assistant d'installation automatique</p>
        </div>

        <div class="steps">
            <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">1</div>
            <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">2</div>
            <div class="step <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">3</div>
            <div class="step <?= $step >= 4 ? ($step > 4 ? 'completed' : 'active') : '' ?>">4</div>
            <div class="step <?= $step >= 5 ? 'active' : '' ?>">5</div>
        </div>

        <div class="progress-bar">
            <div class="progress-fill" style="width: <?= ($step / 5) * 100 ?>%"></div>
        </div>

        <div class="content">
            <?php
            switch ($action) {
                case 'welcome':
                default:
                    include_once 'install/step1-welcome.php';
                    break;
                case 'requirements':
                    include_once 'install/step2-requirements.php';
                    break;
                case 'database':
                    include_once 'install/step3-database.php';
                    break;
                case 'configuration':
                    include_once 'install/step4-configuration.php';
                    break;
                case 'finalize':
                    include_once 'install/step5-finalize.php';
                    break;
                case 'complete':
                    include_once 'install/complete.php';
                    break;
            }
            ?>
        </div>
    </div>

    <script>
        // Auto-refresh pour les étapes avec vérifications
        if (window.location.search.includes('action=requirements')) {
            setTimeout(() => {
                window.location.reload();
            }, 3000);
        }

        // Animation des étapes
        document.addEventListener('DOMContentLoaded', function() {
            const steps = document.querySelectorAll('.step');
            steps.forEach((step, index) => {
                setTimeout(() => {
                    step.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        step.style.transform = 'scale(1)';
                    }, 200);
                }, index * 100);
            });
        });

        // Validation des formulaires
        function validateForm(formId) {
            const form = document.getElementById(formId);
            const inputs = form.querySelectorAll('input[required], select[required]');
            let valid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.style.borderColor = '#ef4444';
                    valid = false;
                } else {
                    input.style.borderColor = '#10b981';
                }
            });

            return valid;
        }

        // Test de connexion base de données
        async function testDatabaseConnection() {
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = 'Test en cours...';
            btn.disabled = true;

            try {
                const formData = new FormData(document.getElementById('db-form'));
                const response = await fetch('install/test-db.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('db-result').innerHTML = 
                        '<div class="alert success">✅ Connexion réussie ! Base de données prête.</div>';
                } else {
                    document.getElementById('db-result').innerHTML = 
                        '<div class="alert error">❌ Erreur: ' + result.message + '</div>';
                }
            } catch (error) {
                document.getElementById('db-result').innerHTML = 
                    '<div class="alert error">❌ Erreur de connexion: ' + error.message + '</div>';
            }

            btn.textContent = originalText;
            btn.disabled = false;
        }
    </script>
</body>
</html>
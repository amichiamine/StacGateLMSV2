<?php
/**
 * Script PHP de vérification des prérequis pour StacGateLMS React/Node.js
 * Compatible avec tous les hébergements web
 */

class RequirementsChecker {
    
    public function checkAll() {
        $results = [
            'php' => $this->checkPhp(),
            'node' => $this->checkNode(),
            'npm' => $this->checkNpm(),
            'git' => $this->checkGit(),
            'docker' => $this->checkDocker(),
            'postgresql' => $this->checkPostgreSQL(),
            'write_permission' => $this->checkWritePermission(),
            'extensions' => $this->checkPhpExtensions(),
            'memory' => $this->checkMemory()
        ];
        
        return $results;
    }
    
    private function checkPhp() {
        $version = PHP_VERSION;
        $required = version_compare($version, '7.4.0', '>=');
        
        return [
            'name' => 'PHP',
            'status' => $required,
            'current' => $version,
            'required' => '7.4+',
            'message' => $required ? "✅ PHP $version" : "❌ PHP $version (requis: 7.4+)",
            'critical' => true
        ];
    }
    
    private function checkNode() {
        $output = $this->execCommand('node --version');
        
        if ($output && strpos($output, 'v') === 0) {
            $version = trim($output);
            $majorVersion = (int) substr($version, 1);
            $required = $majorVersion >= 18;
            
            return [
                'name' => 'Node.js',
                'status' => $required,
                'current' => $version,
                'required' => '18+',
                'message' => $required ? "✅ Node.js $version" : "⚠️ Node.js $version (requis: 18+)",
                'critical' => false
            ];
        }
        
        return [
            'name' => 'Node.js',
            'status' => false,
            'current' => 'Non installé',
            'required' => '18+',
            'message' => '⚠️ Node.js non détecté (optionnel pour cette installation)',
            'critical' => false
        ];
    }
    
    private function checkNpm() {
        $output = $this->execCommand('npm --version');
        
        if ($output && preg_match('/\d+\.\d+\.\d+/', $output)) {
            $version = trim($output);
            return [
                'name' => 'NPM',
                'status' => true,
                'current' => $version,
                'required' => 'Dernière',
                'message' => "✅ NPM $version",
                'critical' => false
            ];
        }
        
        return [
            'name' => 'NPM',
            'status' => false,
            'current' => 'Non installé',
            'required' => 'Dernière',
            'message' => '⚠️ NPM non détecté (installé avec Node.js)',
            'critical' => false
        ];
    }
    
    private function checkGit() {
        $output = $this->execCommand('git --version');
        
        if ($output && strpos($output, 'git version') !== false) {
            return [
                'name' => 'Git',
                'status' => true,
                'current' => trim($output),
                'required' => 'Optionnel',
                'message' => '✅ Git disponible',
                'critical' => false
            ];
        }
        
        return [
            'name' => 'Git',
            'status' => false,
            'current' => 'Non installé',
            'required' => 'Optionnel',
            'message' => '⚠️ Git non disponible (optionnel)',
            'critical' => false
        ];
    }
    
    private function checkDocker() {
        $output = $this->execCommand('docker --version');
        
        if ($output && strpos($output, 'Docker version') !== false) {
            return [
                'name' => 'Docker',
                'status' => true,
                'current' => trim($output),
                'required' => 'Optionnel',
                'message' => '✅ Docker disponible',
                'critical' => false
            ];
        }
        
        return [
            'name' => 'Docker',
            'status' => false,
            'current' => 'Non installé',
            'required' => 'Optionnel',
            'message' => '⚠️ Docker non disponible (optionnel pour PostgreSQL)',
            'critical' => false
        ];
    }
    
    private function checkPostgreSQL() {
        $output = $this->execCommand('psql --version');
        
        if ($output && strpos($output, 'psql') !== false) {
            return [
                'name' => 'PostgreSQL',
                'status' => true,
                'current' => trim($output),
                'required' => 'Optionnel',
                'message' => '✅ PostgreSQL disponible',
                'critical' => false
            ];
        }
        
        return [
            'name' => 'PostgreSQL',
            'status' => false,
            'current' => 'Non installé',
            'required' => 'Optionnel',
            'message' => '⚠️ PostgreSQL non disponible (Docker ou cloud possibles)',
            'critical' => false
        ];
    }
    
    private function checkWritePermission() {
        $testFile = '../.write-test-' . uniqid();
        
        if (@file_put_contents($testFile, 'test')) {
            @unlink($testFile);
            return [
                'name' => 'Permissions d\'écriture',
                'status' => true,
                'current' => 'OK',
                'required' => 'Lecture/Écriture',
                'message' => '✅ Permissions d\'écriture OK',
                'critical' => true
            ];
        }
        
        return [
            'name' => 'Permissions d\'écriture',
            'status' => false,
            'current' => 'Refusées',
            'required' => 'Lecture/Écriture',
            'message' => '❌ Permissions d\'écriture refusées',
            'critical' => true
        ];
    }
    
    private function checkPhpExtensions() {
        $required = ['json', 'curl', 'openssl', 'mbstring'];
        $missing = [];
        
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        
        if (empty($missing)) {
            return [
                'name' => 'Extensions PHP',
                'status' => true,
                'current' => 'Toutes présentes',
                'required' => implode(', ', $required),
                'message' => '✅ Extensions PHP OK',
                'critical' => false
            ];
        }
        
        return [
            'name' => 'Extensions PHP',
            'status' => false,
            'current' => 'Manquantes: ' . implode(', ', $missing),
            'required' => implode(', ', $required),
            'message' => '⚠️ Extensions manquantes: ' . implode(', ', $missing),
            'critical' => false
        ];
    }
    
    private function checkMemory() {
        $memory = ini_get('memory_limit');
        $memoryBytes = $this->convertToBytes($memory);
        $required = 128 * 1024 * 1024; // 128MB
        
        return [
            'name' => 'Mémoire PHP',
            'status' => $memoryBytes >= $required,
            'current' => $memory,
            'required' => '128M+',
            'message' => $memoryBytes >= $required ? "✅ Mémoire $memory" : "⚠️ Mémoire $memory (recommandé: 128M+)",
            'critical' => false
        ];
    }
    
    private function convertToBytes($value) {
        $value = trim($value);
        $last = strtolower($value[strlen($value)-1]);
        $value = (int) $value;
        
        switch ($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }
    
    private function execCommand($command) {
        if (!function_exists('shell_exec')) {
            return false;
        }
        
        return @shell_exec($command . ' 2>&1');
    }
    
    public function generateReport() {
        $results = $this->checkAll();
        $criticalPassed = true;
        $warnings = 0;
        
        foreach ($results as $result) {
            if ($result['critical'] && !$result['status']) {
                $criticalPassed = false;
            }
            if (!$result['critical'] && !$result['status']) {
                $warnings++;
            }
        }
        
        return [
            'results' => $results,
            'critical_passed' => $criticalPassed,
            'warnings' => $warnings,
            'total_checks' => count($results)
        ];
    }
}

// Si appelé directement en ligne de commande
if (php_sapi_name() === 'cli') {
    $checker = new RequirementsChecker();
    $report = $checker->generateReport();
    
    echo "🔍 Vérification des prérequis StacGateLMS React/Node.js\n";
    echo str_repeat('=', 60) . "\n\n";
    
    foreach ($report['results'] as $result) {
        echo $result['message'] . "\n";
    }
    
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "📊 RÉSUMÉ\n";
    echo str_repeat('=', 60) . "\n";
    
    if ($report['critical_passed']) {
        echo "✅ Prérequis critiques satisfaits\n";
    } else {
        echo "❌ Prérequis critiques manquants\n";
    }
    
    if ($report['warnings'] > 0) {
        echo "⚠️ {$report['warnings']} avertissement(s)\n";
    }
    
    echo "\n";
    if ($report['critical_passed']) {
        echo "🚀 Installation possible avec l'assistant PHP\n";
        echo "💡 Lancez: php scripts/install-wizard.php\n";
    } else {
        echo "🛠️ Corrigez les erreurs critiques avant de continuer\n";
    }
    
    exit($report['critical_passed'] ? 0 : 1);
}

// Si appelé via web, retourner JSON
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    $checker = new RequirementsChecker();
    echo json_encode($checker->generateReport());
    exit;
}

// Interface web simple
$checker = new RequirementsChecker();
$report = $checker->generateReport();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Prérequis - StacGateLMS</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; }
        .requirement { display: flex; justify-content: space-between; align-items: center; padding: 15px; margin: 10px 0; background: #f8f9fa; border-radius: 6px; }
        .requirement.passed { background: #d4edda; border-left: 4px solid #28a745; }
        .requirement.failed { background: #f8d7da; border-left: 4px solid #dc3545; }
        .requirement.warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .summary { background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .btn { background: #667eea; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #5a6fd8; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Vérification des Prérequis</h1>
            <p>StacGateLMS React/Node.js</p>
        </div>
        
        <div class="content">
            <h2>Diagnostic du système</h2>
            
            <?php foreach ($report['results'] as $result): ?>
                <div class="requirement <?= $result['status'] ? 'passed' : ($result['critical'] ? 'failed' : 'warning') ?>">
                    <div>
                        <strong><?= $result['name'] ?></strong>
                        <br><small><?= $result['current'] ?> / Requis: <?= $result['required'] ?></small>
                    </div>
                    <div><?= $result['message'] ?></div>
                </div>
            <?php endforeach; ?>
            
            <div class="summary">
                <h3><?= $report['critical_passed'] ? '✅ Système compatible' : '❌ Configuration incomplète' ?></h3>
                <p>
                    <?= $report['critical_passed'] 
                        ? 'Votre système satisfait tous les prérequis critiques pour l\'installation.' 
                        : 'Certains prérequis critiques ne sont pas satisfaits.' 
                    ?>
                </p>
                <?php if ($report['warnings'] > 0): ?>
                    <p><strong>Avertissements :</strong> <?= $report['warnings'] ?> composant(s) optionnel(s) manquant(s)</p>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <?php if ($report['critical_passed']): ?>
                    <a href="install-wizard.php" class="btn">Démarrer l'Installation</a>
                <?php else: ?>
                    <button class="btn" disabled>Installation Impossible</button>
                <?php endif; ?>
                <a href="?refresh=1" class="btn" style="background: #6c757d; margin-left: 10px;">Actualiser</a>
            </div>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; font-size: 12px; color: #6c757d;">
                Vérification effectuée le <?= date('d/m/Y à H:i:s') ?><br>
                PHP <?= PHP_VERSION ?> • <?= php_uname('s') ?> <?= php_uname('r') ?>
            </div>
        </div>
    </div>
</body>
</html>
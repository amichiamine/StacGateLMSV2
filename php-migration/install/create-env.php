<?php
/**
 * Script de création du fichier .env
 */

define('ROOT_PATH', dirname(__DIR__));

// Vérifier si le fichier .env existe déjà
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    echo "Le fichier .env existe déjà.\n";
    exit(0);
}

// Contenu par défaut du fichier .env
$envContent = "# Configuration StacGateLMS
# Généré automatiquement le " . date('Y-m-d H:i:s') . "

# Configuration de l'application
APP_NAME=\"StacGateLMS\"
APP_ENV=development
APP_URL=http://localhost:8000
APP_TIMEZONE=Europe/Paris
APP_LANGUAGE=fr

# Configuration de la base de données
DB_TYPE=sqlite
DB_HOST=localhost
DB_PORT=
DB_NAME=database.sqlite
DB_USERNAME=
DB_PASSWORD=
DB_CHARSET=utf8mb4

# Configuration de sécurité
SESSION_SECRET=" . bin2hex(random_bytes(32)) . "
CSRF_SECRET=" . bin2hex(random_bytes(16)) . "
SECURITY_SALT=" . bin2hex(random_bytes(16)) . "

# Configuration email
MAIL_HOST=localhost
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM=noreply@stacgatelms.local

# Configuration cache et logs
CACHE_ENABLED=true
CACHE_LIFETIME=3600
LOG_ENABLED=true
LOG_LEVEL=DEBUG

# Configuration collaboration
COLLABORATION_ENABLED=true
POLL_INTERVAL=2
MAX_ROOM_PARTICIPANTS=50
";

// Créer le fichier .env
if (file_put_contents($envFile, $envContent)) {
    echo "✅ Fichier .env créé avec succès.\n";
    echo "📝 Vous pouvez maintenant modifier les paramètres dans .env\n";
} else {
    echo "❌ Erreur : Impossible de créer le fichier .env\n";
    exit(1);
}

// Créer également un fichier d'exemple
$envExampleFile = ROOT_PATH . '/.env.example';
$envExampleContent = str_replace([
    bin2hex(random_bytes(32)),
    bin2hex(random_bytes(16)),
    bin2hex(random_bytes(16))
], [
    'your-session-secret-here',
    'your-csrf-secret-here', 
    'your-security-salt-here'
], $envContent);

if (file_put_contents($envExampleFile, $envExampleContent)) {
    echo "✅ Fichier .env.example créé avec succès.\n";
}
?>
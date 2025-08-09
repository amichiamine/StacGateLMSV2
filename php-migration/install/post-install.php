<?php
/**
 * Script post-installation pour Composer
 */

echo "🔧 Configuration post-installation StacGateLMS\n";
echo str_repeat("=", 50) . "\n";

// Créer le fichier .env s'il n'existe pas
$envFile = dirname(__DIR__) . '/.env';
if (!file_exists($envFile)) {
    echo "📝 Création du fichier .env...\n";
    include __DIR__ . '/create-env.php';
}

// Créer les dossiers nécessaires
$directories = ['cache', 'logs', 'uploads'];
foreach ($directories as $dir) {
    $path = dirname(__DIR__) . "/$dir";
    if (!file_exists($path)) {
        echo "📁 Création du dossier $dir/...\n";
        mkdir($path, 0755, true);
    }
}

// Créer le fichier .htaccess pour Apache
$htaccessFile = dirname(__DIR__) . '/.htaccess';
if (!file_exists($htaccessFile)) {
    echo "🔒 Création du fichier .htaccess...\n";
    $htaccessContent = "# StacGateLMS - Configuration Apache
RewriteEngine On

# Redirection vers index.php pour toutes les routes
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Sécurité - Bloquer l'accès aux fichiers sensibles
<Files \".env\">
    Order allow,deny
    Deny from all
</Files>

<Files \"composer.json\">
    Order allow,deny
    Deny from all
</Files>

<Files \"composer.lock\">
    Order allow,deny
    Deny from all
</Files>

# Protection des dossiers
<Directory \"install\">
    <RequireAll>
        Require ip 127.0.0.1
        Require ip ::1
    </RequireAll>
</Directory>

# Headers de sécurité
<IfModule mod_headers.c>
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection \"1; mode=block\"
    Header always set Referrer-Policy \"strict-origin-when-cross-origin\"
</IfModule>

# Compression
<IfModule mod_deflate.c>
    SetOutputFilter DEFLATE
    SetEnvIfNoCase Request_URI \\.(?:gif|jpe?g|png)$ no-gzip dont-vary
    SetEnvIfNoCase Request_URI \\.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
</IfModule>
";
    file_put_contents($htaccessFile, $htaccessContent);
}

echo "✅ Configuration post-installation terminée!\n";
echo "\n💡 Prochaines étapes:\n";
echo "   1. Configurez votre base de données dans .env\n";
echo "   2. Lancez l'installation web: http://localhost/install.php\n";
echo "   3. Ou démarrez un serveur de développement: php -S localhost:8000\n";
?>
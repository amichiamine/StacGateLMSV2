#!/usr/bin/env node

/**
 * Script de configuration de l'environnement StacGateLMS
 */

import { writeFileSync, existsSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { createInterface } from 'readline';
import { randomBytes } from 'crypto';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const ROOT_DIR = join(__dirname, '..');

const rl = createInterface({
    input: process.stdin,
    output: process.stdout
});

function question(prompt) {
    return new Promise((resolve) => {
        rl.question(prompt, resolve);
    });
}

function generateSecretKey(length = 32) {
    return randomBytes(length).toString('hex');
}

async function setupEnvironment() {
    console.log('🔧 Configuration de l\'environnement StacGateLMS');
    console.log('=' .repeat(50));
    console.log();

    const envFile = join(ROOT_DIR, '.env');
    
    if (existsSync(envFile)) {
        const overwrite = await question('Un fichier .env existe déjà. Voulez-vous le remplacer ? (o/N): ');
        if (overwrite.toLowerCase() !== 'o' && overwrite.toLowerCase() !== 'oui') {
            console.log('Configuration annulée.');
            rl.close();
            return;
        }
    }

    console.log('Configuration de la base de données :');
    console.log('1. PostgreSQL local');
    console.log('2. PostgreSQL Docker');
    console.log('3. Service cloud (Neon, Supabase, etc.)');
    console.log();

    const dbChoice = await question('Choisissez une option (1-3): ');
    let databaseUrl = '';

    switch (dbChoice) {
        case '1':
            const host = await question('Hôte (localhost): ') || 'localhost';
            const port = await question('Port (5432): ') || '5432';
            const dbname = await question('Nom de la base (stacgatelms): ') || 'stacgatelms';
            const username = await question('Utilisateur (postgres): ') || 'postgres';
            const password = await question('Mot de passe: ');
            databaseUrl = `postgresql://${username}:${password}@${host}:${port}/${dbname}`;
            break;
        case '2':
            databaseUrl = 'postgresql://stacgate:stacgate123@localhost:5433/stacgatelms';
            console.log('✅ Configuration Docker préparée');
            break;
        case '3':
            databaseUrl = await question('URL de connexion complète: ');
            break;
        default:
            console.log('Option non valide, utilisation de la configuration Docker par défaut.');
            databaseUrl = 'postgresql://stacgate:stacgate123@localhost:5433/stacgatelms';
    }

    console.log();
    console.log('Configuration de l\'application :');
    const appName = await question('Nom de l\'application (StacGateLMS): ') || 'StacGateLMS';
    const appUrl = await question('URL de l\'application (http://localhost:5000): ') || 'http://localhost:5000';

    console.log();
    console.log('Configuration du super administrateur :');
    const adminEmail = await question('Email administrateur: ');
    let adminPassword = await question('Mot de passe (min. 6 caractères): ');
    
    while (adminPassword.length < 6) {
        console.log('❌ Le mot de passe doit contenir au moins 6 caractères.');
        adminPassword = await question('Mot de passe (min. 6 caractères): ');
    }

    console.log();
    const installDemo = await question('Installer les données de démonstration ? (O/n): ');
    const demoData = installDemo.toLowerCase() !== 'n' && installDemo.toLowerCase() !== 'non';

    // Génération du fichier .env
    const envContent = `# Configuration StacGateLMS - Générée automatiquement
# Date: ${new Date().toISOString()}

# Environnement
NODE_ENV=development
PORT=5000
VITE_PORT=3000

# Base de données
DATABASE_URL="${databaseUrl}"

# Application
APP_NAME="${appName}"
APP_URL="${appUrl}"

# Sécurité
JWT_SECRET="${generateSecretKey()}"
SESSION_SECRET="${generateSecretKey()}"
CSRF_SECRET="${generateSecretKey(16)}"

# Administrateur
ADMIN_EMAIL="${adminEmail}"
ADMIN_PASSWORD="${adminPassword}"

# Fonctionnalités
INSTALL_DEMO_DATA="${demoData}"

# Frontend (Vite)
VITE_API_URL=http://localhost:5000
VITE_APP_NAME="${appName}"

# Upload et stockage
UPLOAD_MAX_SIZE=10485760
UPLOAD_ALLOWED_TYPES=image/jpeg,image/png,image/gif,application/pdf

# Logs et debug
LOG_LEVEL=info
DEBUG_MODE=false

# Rate limiting
RATE_LIMIT_WINDOW_MS=900000
RATE_LIMIT_MAX_REQUESTS=100

# Cache
CACHE_TTL=3600
REDIS_URL=

# Email (optionnel)
SMTP_HOST=
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
SMTP_FROM=noreply@${appUrl.replace('http://', '').replace('https://', '')}

# Analytics (optionnel)
GOOGLE_ANALYTICS_ID=
MATOMO_URL=
MATOMO_SITE_ID=
`;

    try {
        writeFileSync(envFile, envContent);
        console.log();
        console.log('✅ Fichier .env créé avec succès !');
        console.log();
        console.log('Résumé de la configuration :');
        console.log(`  📱 Application: ${appName}`);
        console.log(`  🌐 URL: ${appUrl}`);
        console.log(`  📧 Admin: ${adminEmail}`);
        console.log(`  🗄️ Base de données: ${dbChoice === '2' ? 'Docker PostgreSQL' : 'PostgreSQL configurée'}`);
        console.log(`  📚 Données de démo: ${demoData ? 'Oui' : 'Non'}`);
        console.log();
        console.log('🚀 Prochaines étapes :');
        console.log('  1. npm run db:push (pour créer les tables)');
        console.log('  2. npm run seed:admin (pour créer le compte admin)');
        console.log('  3. npm run dev (pour démarrer l\'application)');
        console.log();
    } catch (error) {
        console.error('❌ Erreur lors de la création du fichier .env:', error.message);
        process.exit(1);
    }

    rl.close();
}

setupEnvironment().catch(console.error);
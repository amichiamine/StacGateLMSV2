#!/usr/bin/env node

/**
 * Script de vérification des prérequis pour StacGateLMS React/Node.js
 */

import { execSync } from 'child_process';
import { existsSync, writeFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const ROOT_DIR = join(__dirname, '..');

// Configuration des couleurs pour la console
const colors = {
    reset: '\x1b[0m',
    bright: '\x1b[1m',
    red: '\x1b[31m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    magenta: '\x1b[35m',
    cyan: '\x1b[36m'
};

function log(message, color = 'reset') {
    console.log(`${colors[color]}${message}${colors.reset}`);
}

function checkRequirement(name, checkFn) {
    try {
        const result = checkFn();
        if (result.status) {
            log(`  ✅ ${name}: ${result.message}`, 'green');
        } else {
            log(`  ⚠️ ${name}: ${result.message}`, 'yellow');
        }
        return result.status;
    } catch (error) {
        log(`  ❌ ${name}: ${error.message}`, 'red');
        return false;
    }
}

function checkNodeVersion() {
    try {
        const version = execSync('node --version', { encoding: 'utf8' }).trim();
        const majorVersion = parseInt(version.slice(1).split('.')[0]);
        if (majorVersion >= 18) {
            return { status: true, message: `${version} ✓` };
        } else {
            return { status: false, message: `${version} (requis: 18+)` };
        }
    } catch (error) {
        throw new Error('Non installé');
    }
}

function checkNpm() {
    try {
        const version = execSync('npm --version', { encoding: 'utf8' }).trim();
        return { status: true, message: `${version} ✓` };
    } catch (error) {
        throw new Error('Non installé');
    }
}

function checkGit() {
    try {
        const version = execSync('git --version', { encoding: 'utf8' }).trim();
        return { status: true, message: version };
    } catch (error) {
        return { status: false, message: 'Non installé (optionnel)' };
    }
}

function checkPostgreSQL() {
    try {
        const version = execSync('psql --version', { encoding: 'utf8' }).trim();
        return { status: true, message: version };
    } catch (error) {
        return { status: false, message: 'Non installé (optionnel)' };
    }
}

function checkDocker() {
    try {
        const version = execSync('docker --version', { encoding: 'utf8' }).trim();
        return { status: true, message: version };
    } catch (error) {
        return { status: false, message: 'Non installé (optionnel)' };
    }
}

function checkWritePermissions() {
    try {
        const testFile = join(ROOT_DIR, '.write-test');
        writeFileSync(testFile, 'test');
        execSync(`rm -f "${testFile}"`);
        return { status: true, message: 'OK ✓' };
    } catch (error) {
        throw new Error('Accès refusé');
    }
}

function checkDependencies() {
    const nodeModules = join(ROOT_DIR, 'node_modules');
    if (existsSync(nodeModules)) {
        return { status: true, message: 'Installées ✓' };
    } else {
        return { status: false, message: 'À installer (npm install)' };
    }
}

function checkEnvironmentFile() {
    const envFile = join(ROOT_DIR, '.env');
    if (existsSync(envFile)) {
        return { status: true, message: 'Présent ✓' };
    } else {
        return { status: false, message: 'À créer' };
    }
}

// Fonction principale
function main() {
    log('🔍 Vérification des prérequis StacGateLMS React/Node.js', 'cyan');
    log('=' .repeat(60), 'blue');
    console.log();

    const requirements = [
        { name: 'Node.js 18+', check: checkNodeVersion, critical: true },
        { name: 'NPM', check: checkNpm, critical: true },
        { name: 'Git', check: checkGit, critical: false },
        { name: 'PostgreSQL', check: checkPostgreSQL, critical: false },
        { name: 'Docker', check: checkDocker, critical: false },
        { name: 'Permissions d\'écriture', check: checkWritePermissions, critical: true },
        { name: 'Dépendances NPM', check: checkDependencies, critical: false },
        { name: 'Fichier .env', check: checkEnvironmentFile, critical: false }
    ];

    let criticalErrors = 0;
    let warnings = 0;

    for (const req of requirements) {
        const result = checkRequirement(req.name, req.check);
        if (!result) {
            if (req.critical) {
                criticalErrors++;
            } else {
                warnings++;
            }
        }
    }

    console.log();
    log('=' .repeat(60), 'blue');
    log('📊 RÉSUMÉ DE LA VÉRIFICATION', 'cyan');
    log('=' .repeat(60), 'blue');

    if (criticalErrors === 0) {
        log('✅ Tous les prérequis critiques sont satisfaits !', 'green');
    } else {
        log(`❌ ${criticalErrors} erreur(s) critique(s) détectée(s)`, 'red');
        console.log();
        log('Erreurs à corriger :', 'yellow');
        if (!existsSync(join(ROOT_DIR, 'node_modules'))) {
            log('  - Installez les dépendances: npm install', 'white');
        }
    }

    if (warnings > 0) {
        log(`⚠️ ${warnings} avertissement(s)`, 'yellow');
    }

    console.log();
    if (criticalErrors === 0) {
        log('🚀 Votre système est prêt pour StacGateLMS !', 'green');
        console.log();
        log('💡 Prochaines étapes :', 'magenta');
        log('   1. Lancez l\'assistant d\'installation: npm run install:express', 'white');
        log('   2. Ou utilisez les scripts de démarrage:', 'white');
        log('      - Windows: start-express.bat', 'white');
        log('      - Linux/Mac: ./start-express.sh', 'white');
        console.log();
        process.exit(0);
    } else {
        log('🛠️ Veuillez corriger les erreurs avant de continuer.', 'red');
        console.log();
        log('Guides d\'installation :', 'cyan');
        log('  Node.js 18+: https://nodejs.org/', 'white');
        log('  PostgreSQL: https://www.postgresql.org/download/', 'white');
        log('  Docker: https://docs.docker.com/get-docker/', 'white');
        console.log();
        process.exit(1);
    }
}

// Exécution
main();
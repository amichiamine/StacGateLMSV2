#!/usr/bin/env node

/**
 * Script de réinitialisation de la base de données
 * Supprime et recrée toutes les tables
 */

import { execSync } from 'child_process';
import { createInterface } from 'readline';
import { config } from 'dotenv';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const ROOT_DIR = join(__dirname, '..');

config();

const rl = createInterface({
    input: process.stdin,
    output: process.stdout
});

function question(prompt) {
    return new Promise((resolve) => {
        rl.question(prompt, resolve);
    });
}

async function reinitDatabase() {
    console.log('🗄️ Réinitialisation de la base de données StacGateLMS');
    console.log('=' .repeat(50));
    console.log();
    
    console.log('⚠️ ATTENTION: Cette opération va supprimer TOUTES les données !');
    console.log('Cette action est irréversible.');
    console.log();
    
    const confirm1 = await question('Êtes-vous sûr de vouloir continuer ? (tapez "OUI" en majuscules): ');
    if (confirm1 !== 'OUI') {
        console.log('Opération annulée.');
        rl.close();
        return;
    }
    
    const confirm2 = await question('Dernière confirmation. Tapez "SUPPRIMER" pour confirmer: ');
    if (confirm2 !== 'SUPPRIMER') {
        console.log('Opération annulée.');
        rl.close();
        return;
    }

    rl.close();

    try {
        console.log();
        console.log('🔄 Suppression des tables existantes...');
        
        // Drizzle push avec --drop pour supprimer et recréer
        execSync('npx drizzle-kit push --force', {
            stdio: 'inherit',
            cwd: ROOT_DIR
        });
        
        console.log('✅ Tables supprimées et recréées');
        
        console.log();
        console.log('👤 Recréation du compte super administrateur...');
        
        // Recréer le compte admin
        execSync('node scripts/seed-admin.js', {
            stdio: 'inherit',
            cwd: ROOT_DIR
        });
        
        // Demander s'il faut réinstaller les données de démo
        const rl2 = createInterface({
            input: process.stdin,
            output: process.stdout
        });
        
        const installDemo = await new Promise((resolve) => {
            rl2.question('Voulez-vous réinstaller les données de démonstration ? (O/n): ', resolve);
        });
        
        rl2.close();
        
        if (installDemo.toLowerCase() !== 'n' && installDemo.toLowerCase() !== 'non') {
            console.log();
            console.log('📚 Réinstallation des données de démonstration...');
            
            // Mettre temporairement INSTALL_DEMO_DATA à true
            process.env.INSTALL_DEMO_DATA = 'true';
            
            execSync('node scripts/seed-demo.js', {
                stdio: 'inherit',
                cwd: ROOT_DIR,
                env: { ...process.env, INSTALL_DEMO_DATA: 'true' }
            });
        }
        
        console.log();
        console.log('🎉 Réinitialisation terminée avec succès !');
        console.log();
        console.log('🚀 Votre base de données est maintenant propre et prête à l\'emploi.');
        console.log('Vous pouvez démarrer votre application avec: npm run dev');
        console.log();
        
    } catch (error) {
        console.error('❌ Erreur lors de la réinitialisation:', error.message);
        console.error();
        console.error('💡 Vérifications possibles :');
        console.error('  - La base de données est-elle accessible ?');
        console.error('  - Le fichier .env est-il correctement configuré ?');
        console.error('  - Les migrations Drizzle sont-elles à jour ?');
        process.exit(1);
    }
}

// Fonction utilitaire pour demander confirmation
function askConfirmation(prompt) {
    return new Promise((resolve) => {
        const rl = createInterface({
            input: process.stdin,
            output: process.stdout
        });
        
        rl.question(prompt, (answer) => {
            rl.close();
            resolve(answer);
        });
    });
}

reinitDatabase().catch(console.error);
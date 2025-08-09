#!/usr/bin/env node

/**
 * Script de création du compte super administrateur
 */

import { drizzle } from 'drizzle-orm/node-postgres';
import { eq } from 'drizzle-orm';
import pg from 'pg';
import bcrypt from 'bcryptjs';
import { users, establishments } from '../shared/schema.js';
import { config } from 'dotenv';

config();

const { Pool } = pg;

async function createAdminUser() {
    console.log('👤 Création du compte super administrateur...');
    console.log('=' .repeat(50));

    const adminEmail = process.env.ADMIN_EMAIL;
    const adminPassword = process.env.ADMIN_PASSWORD;

    if (!adminEmail || !adminPassword) {
        console.error('❌ Variables ADMIN_EMAIL et ADMIN_PASSWORD requises dans .env');
        process.exit(1);
    }

    if (!process.env.DATABASE_URL) {
        console.error('❌ Variable DATABASE_URL requise dans .env');
        process.exit(1);
    }

    let client;
    try {
        // Connexion à la base de données
        const pool = new Pool({
            connectionString: process.env.DATABASE_URL,
        });
        
        client = await pool.connect();
        const db = drizzle(client);

        console.log('🔍 Vérification de l\'établissement principal...');
        
        // Vérifier si l'établissement principal existe
        let mainEstablishment = await db
            .select()
            .from(establishments)
            .where(eq(establishments.slug, 'main'))
            .limit(1);

        if (mainEstablishment.length === 0) {
            console.log('🏢 Création de l\'établissement principal...');
            const [newEstablishment] = await db
                .insert(establishments)
                .values({
                    name: process.env.APP_NAME || 'StacGateLMS',
                    slug: 'main',
                    description: 'Établissement principal',
                    domain: new URL(process.env.APP_URL || 'http://localhost:5000').hostname,
                    isActive: true,
                    settings: {
                        theme: 'default',
                        language: 'fr',
                        timezone: 'Europe/Paris'
                    }
                })
                .returning();
            
            mainEstablishment = [newEstablishment];
            console.log('✅ Établissement principal créé');
        } else {
            console.log('✅ Établissement principal trouvé');
        }

        // Vérifier si un super admin existe déjà
        console.log('🔍 Vérification du compte super administrateur...');
        const existingAdmin = await db
            .select()
            .from(users)
            .where(eq(users.email, adminEmail))
            .limit(1);

        if (existingAdmin.length > 0) {
            console.log('⚠️ Un compte avec cet email existe déjà');
            
            if (existingAdmin[0].role === 'super_admin') {
                console.log('✅ Le compte est déjà super administrateur');
                return;
            } else {
                console.log('🔄 Mise à jour du rôle vers super administrateur...');
                await db
                    .update(users)
                    .set({ 
                        role: 'super_admin',
                        updatedAt: new Date()
                    })
                    .where(eq(users.email, adminEmail));
                
                console.log('✅ Rôle mis à jour vers super administrateur');
                return;
            }
        }

        // Créer le compte super administrateur
        console.log('👤 Création du compte super administrateur...');
        const hashedPassword = await bcrypt.hash(adminPassword, 12);

        const [newAdmin] = await db
            .insert(users)
            .values({
                establishmentId: mainEstablishment[0].id,
                email: adminEmail,
                username: adminEmail.split('@')[0],
                firstName: 'Super',
                lastName: 'Administrateur',
                password: hashedPassword,
                role: 'super_admin',
                isActive: true,
                emailVerifiedAt: new Date()
            })
            .returning();

        console.log('✅ Compte super administrateur créé avec succès !');
        console.log();
        console.log('📋 Informations de connexion :');
        console.log(`  📧 Email: ${adminEmail}`);
        console.log(`  🔑 Mot de passe: ${adminPassword}`);
        console.log(`  👑 Rôle: Super Administrateur`);
        console.log(`  🏢 Établissement: ${mainEstablishment[0].name}`);
        console.log();
        console.log('🌐 Vous pouvez maintenant vous connecter sur votre application');

    } catch (error) {
        console.error('❌ Erreur lors de la création du compte admin:', error.message);
        console.error('Détails:', error);
        process.exit(1);
    } finally {
        if (client) {
            client.release();
        }
    }
}

createAdminUser();
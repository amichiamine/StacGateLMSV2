#!/usr/bin/env node

/**
 * Script d'installation des données de démonstration
 */

import { drizzle } from 'drizzle-orm/node-postgres';
import { eq } from 'drizzle-orm';
import pg from 'pg';
import bcrypt from 'bcryptjs';
import { users, establishments, courses, enrollments, assessments, studyGroups, notifications } from '../shared/schema.js';
import { config } from 'dotenv';

config();

const { Pool } = pg;

async function seedDemoData() {
    console.log('📚 Installation des données de démonstration...');
    console.log('=' .repeat(50));

    if (process.env.INSTALL_DEMO_DATA !== 'true') {
        console.log('ℹ️ Données de démonstration désactivées dans la configuration');
        return;
    }

    if (!process.env.DATABASE_URL) {
        console.error('❌ Variable DATABASE_URL requise dans .env');
        process.exit(1);
    }

    let client;
    try {
        const pool = new Pool({
            connectionString: process.env.DATABASE_URL,
        });
        
        client = await pool.connect();
        const db = drizzle(client);

        // Vérifier si des données de démo existent déjà
        const existingEstablishments = await db
            .select()
            .from(establishments)
            .where(eq(establishments.slug, 'demo-academy'));

        if (existingEstablishments.length > 0) {
            console.log('⚠️ Données de démonstration déjà présentes');
            return;
        }

        console.log('🏢 Création des établissements de démonstration...');
        
        // Créer des établissements de démonstration
        const demoEstablishments = [
            {
                name: 'StacGate Academy',
                slug: 'demo-academy',
                description: 'École de formation professionnelle en développement web',
                domain: 'stacgate.academy',
                isActive: true,
                settings: {
                    theme: 'purple',
                    language: 'fr',
                    timezone: 'Europe/Paris',
                    allowRegistration: true
                }
            },
            {
                name: 'TechPro Institute',
                slug: 'techpro-institute',
                description: 'Institut spécialisé en technologies avancées',
                domain: 'techpro.institute',
                isActive: true,
                settings: {
                    theme: 'blue',
                    language: 'fr',
                    timezone: 'Europe/Paris',
                    allowRegistration: false
                }
            },
            {
                name: 'Digital Learning Center',
                slug: 'digital-learning',
                description: 'Centre de formation continue en numérique',
                domain: 'digital-learning.edu',
                isActive: true,
                settings: {
                    theme: 'green',
                    language: 'fr',
                    timezone: 'Europe/Paris',
                    allowRegistration: true
                }
            }
        ];

        const createdEstablishments = [];
        for (const est of demoEstablishments) {
            const [created] = await db.insert(establishments).values(est).returning();
            createdEstablishments.push(created);
        }

        console.log(`✅ ${createdEstablishments.length} établissements créés`);

        console.log('👥 Création des utilisateurs de démonstration...');
        
        // Créer des utilisateurs de démonstration
        const demoUsers = [
            {
                establishmentId: createdEstablishments[0].id,
                email: 'admin@stacgate.academy',
                username: 'admin_stacgate',
                firstName: 'Jean',
                lastName: 'Administrateur',
                role: 'admin',
                password: 'demo123'
            },
            {
                establishmentId: createdEstablishments[0].id,
                email: 'marie.formateur@stacgate.academy',
                username: 'marie_formateur',
                firstName: 'Marie',
                lastName: 'Formatrice',
                role: 'formateur',
                password: 'demo123'
            },
            {
                establishmentId: createdEstablishments[0].id,
                email: 'paul.formateur@stacgate.academy',
                username: 'paul_formateur',
                firstName: 'Paul',
                lastName: 'Instructeur',
                role: 'formateur',
                password: 'demo123'
            },
            {
                establishmentId: createdEstablishments[0].id,
                email: 'sophie.apprenant@stacgate.academy',
                username: 'sophie_apprenant',
                firstName: 'Sophie',
                lastName: 'Étudiante',
                role: 'apprenant',
                password: 'demo123'
            },
            {
                establishmentId: createdEstablishments[0].id,
                email: 'lucas.apprenant@stacgate.academy',
                username: 'lucas_apprenant',
                firstName: 'Lucas',
                lastName: 'Étudiant',
                role: 'apprenant',
                password: 'demo123'
            },
            {
                establishmentId: createdEstablishments[1].id,
                email: 'admin@techpro.institute',
                username: 'admin_techpro',
                firstName: 'Claire',
                lastName: 'Directrice',
                role: 'admin',
                password: 'demo123'
            }
        ];

        const createdUsers = [];
        for (const user of demoUsers) {
            const hashedPassword = await bcrypt.hash(user.password, 12);
            const [created] = await db.insert(users).values({
                ...user,
                password: hashedPassword,
                isActive: true,
                emailVerifiedAt: new Date()
            }).returning();
            createdUsers.push(created);
        }

        console.log(`✅ ${createdUsers.length} utilisateurs créés`);

        console.log('📚 Création des cours de démonstration...');
        
        // Créer des cours de démonstration
        const demoCourses = [
            {
                establishmentId: createdEstablishments[0].id,
                title: 'Introduction au Développement Web',
                description: 'Apprenez les bases du développement web avec HTML, CSS et JavaScript. Ce cours couvre les fondamentaux nécessaires pour créer vos premières pages web interactives.',
                shortDescription: 'Les bases du développement web moderne',
                category: 'web',
                type: 'cours',
                price: '0.00',
                isFree: true,
                duration: 120,
                level: 'debutant',
                language: 'fr',
                tags: ['HTML', 'CSS', 'JavaScript', 'Web'],
                instructorId: createdUsers[1].id, // Marie Formatrice
                isPublic: true,
                isActive: true,
                rating: '4.50',
                enrollmentCount: 25
            },
            {
                establishmentId: createdEstablishments[0].id,
                title: 'React pour Débutants',
                description: 'Maîtrisez React et créez des applications web modernes. Découvrez les composants, le state management, les hooks et les meilleures pratiques.',
                shortDescription: 'Création d\'applications React modernes',
                category: 'frontend',
                type: 'cours',
                price: '99.00',
                isFree: false,
                duration: 180,
                level: 'intermediaire',
                language: 'fr',
                tags: ['React', 'JavaScript', 'Frontend', 'Hooks'],
                instructorId: createdUsers[1].id, // Marie Formatrice
                isPublic: true,
                isActive: true,
                rating: '4.80',
                enrollmentCount: 15
            },
            {
                establishmentId: createdEstablishments[0].id,
                title: 'PHP et Base de Données',
                description: 'Développement backend avec PHP et gestion des bases de données MySQL. Apprenez à créer des APIs et à gérer les données efficacement.',
                shortDescription: 'Backend PHP et bases de données',
                category: 'backend',
                type: 'cours',
                price: '149.00',
                isFree: false,
                duration: 200,
                level: 'intermediaire',
                language: 'fr',
                tags: ['PHP', 'MySQL', 'Backend', 'API'],
                instructorId: createdUsers[2].id, // Paul Instructeur
                isPublic: true,
                isActive: true,
                rating: '4.60',
                enrollmentCount: 12
            },
            {
                establishmentId: createdEstablishments[0].id,
                title: 'Design System et UI/UX',
                description: 'Créez des interfaces utilisateur cohérentes et attractives. Apprenez les principes du design, Figma, et l\'implémentation de design systems.',
                shortDescription: 'Design d\'interfaces modernes',
                category: 'design',
                type: 'workshop',
                price: '75.00',
                isFree: false,
                duration: 90,
                level: 'tous',
                language: 'fr',
                tags: ['UI/UX', 'Design', 'Figma', 'Design System'],
                instructorId: createdUsers[1].id, // Marie Formatrice
                isPublic: true,
                isActive: true,
                rating: '4.70',
                enrollmentCount: 8
            }
        ];

        const createdCourses = [];
        for (const course of demoCourses) {
            const [created] = await db.insert(courses).values(course).returning();
            createdCourses.push(created);
        }

        console.log(`✅ ${createdCourses.length} cours créés`);

        console.log('📝 Création des inscriptions...');
        
        // Créer des inscriptions
        const enrollmentData = [
            { userId: createdUsers[3].id, courseId: createdCourses[0].id, progress: 75, status: 'active' },
            { userId: createdUsers[3].id, courseId: createdCourses[1].id, progress: 30, status: 'active' },
            { userId: createdUsers[4].id, courseId: createdCourses[0].id, progress: 100, status: 'completed', completedAt: new Date() },
            { userId: createdUsers[4].id, courseId: createdCourses[2].id, progress: 45, status: 'active' },
            { userId: createdUsers[4].id, courseId: createdCourses[3].id, progress: 10, status: 'active' }
        ];

        for (const enrollment of enrollmentData) {
            await db.insert(enrollments).values(enrollment);
        }

        console.log(`✅ ${enrollmentData.length} inscriptions créées`);

        console.log('📊 Création des évaluations...');
        
        // Créer des évaluations
        const assessmentData = [
            {
                establishmentId: createdEstablishments[0].id,
                courseId: createdCourses[0].id,
                title: 'Quiz HTML/CSS',
                description: 'Évaluation des connaissances de base en HTML et CSS',
                type: 'quiz',
                questions: [
                    {
                        question: 'Quelle balise HTML est utilisée pour créer un lien ?',
                        type: 'multiple',
                        options: ['<link>', '<a>', '<href>', '<url>'],
                        correct: 1
                    },
                    {
                        question: 'Comment appliquer une couleur de fond rouge en CSS ?',
                        type: 'multiple',
                        options: ['color: red;', 'background-color: red;', 'bg-color: red;', 'background: red;'],
                        correct: 1
                    }
                ],
                settings: {
                    timeLimit: 30,
                    attempts: 3,
                    passingScore: 70
                },
                isActive: true
            },
            {
                establishmentId: createdEstablishments[0].id,
                courseId: createdCourses[1].id,
                title: 'Exercice React Components',
                description: 'Création d\'un composant React fonctionnel',
                type: 'assignment',
                questions: [
                    {
                        question: 'Créez un composant React qui affiche une liste d\'éléments passés en props',
                        type: 'code',
                        language: 'javascript',
                        template: 'function ItemList(props) {\n  // Votre code ici\n}'
                    }
                ],
                settings: {
                    timeLimit: 60,
                    attempts: 2,
                    passingScore: 80
                },
                isActive: true
            }
        ];

        for (const assessment of assessmentData) {
            await db.insert(assessments).values(assessment);
        }

        console.log(`✅ ${assessmentData.length} évaluations créées`);

        console.log('👥 Création des groupes d\'étude...');
        
        // Créer des groupes d'étude
        const studyGroupData = [
            {
                establishmentId: createdEstablishments[0].id,
                name: 'Groupe Web Débutants',
                description: 'Entraide pour les débutants en développement web',
                maxMembers: 15,
                isPublic: true,
                createdBy: createdUsers[1].id // Marie Formatrice
            },
            {
                establishmentId: createdEstablishments[0].id,
                name: 'React Avancé',
                description: 'Discussions et projets autour de React',
                maxMembers: 10,
                isPublic: true,
                createdBy: createdUsers[2].id // Paul Instructeur
            }
        ];

        for (const group of studyGroupData) {
            await db.insert(studyGroups).values(group);
        }

        console.log(`✅ ${studyGroupData.length} groupes d'étude créés`);

        console.log('🔔 Création des notifications...');
        
        // Créer des notifications de démonstration
        const notificationData = [
            {
                userId: createdUsers[3].id, // Sophie
                establishmentId: createdEstablishments[0].id,
                title: 'Bienvenue sur StacGate Academy !',
                message: 'Découvrez nos cours et commencez votre apprentissage dès aujourd\'hui.',
                type: 'welcome',
                isRead: false,
                data: {}
            },
            {
                userId: createdUsers[4].id, // Lucas
                establishmentId: createdEstablishments[0].id,
                title: 'Félicitations pour votre premier cours terminé !',
                message: 'Vous avez terminé "Introduction au Développement Web" avec succès.',
                type: 'achievement',
                isRead: false,
                data: { courseId: createdCourses[0].id }
            }
        ];

        for (const notification of notificationData) {
            await db.insert(notifications).values(notification);
        }

        console.log(`✅ ${notificationData.length} notifications créées`);

        console.log();
        console.log('🎉 Données de démonstration installées avec succès !');
        console.log();
        console.log('📊 Résumé :');
        console.log(`  🏢 ${createdEstablishments.length} établissements`);
        console.log(`  👥 ${createdUsers.length} utilisateurs`);
        console.log(`  📚 ${createdCourses.length} cours`);
        console.log(`  📝 ${enrollmentData.length} inscriptions`);
        console.log(`  📊 ${assessmentData.length} évaluations`);
        console.log(`  👥 ${studyGroupData.length} groupes d'étude`);
        console.log(`  🔔 ${notificationData.length} notifications`);
        console.log();
        console.log('👤 Comptes de test disponibles :');
        console.log('  📧 admin@stacgate.academy / demo123 (Administrateur)');
        console.log('  📧 marie.formateur@stacgate.academy / demo123 (Formateur)');
        console.log('  📧 sophie.apprenant@stacgate.academy / demo123 (Apprenant)');
        console.log();

    } catch (error) {
        console.error('❌ Erreur lors de l\'installation des données de démo:', error.message);
        console.error('Détails:', error);
        process.exit(1);
    } finally {
        if (client) {
            client.release();
        }
    }
}

seedDemoData();
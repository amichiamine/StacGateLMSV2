import bcrypt from 'bcryptjs';
import { drizzle } from 'drizzle-orm/postgres-js';
import postgres from 'postgres';

// Script de réinitialisation de la base de données avec des données cohérentes
async function createTestData() {
  
  const sql = postgres(process.env.DATABASE_URL);
  const db = drizzle(sql);

  console.log('🔄 Réinitialisation de la base de données...');

  try {
    // Hachage des mots de passe
    const hashedPassword = await bcrypt.hash('admin123', 12);
    const hashedPassword2 = await bcrypt.hash('password123', 12);
    
    // 1. Créer les établissements
    console.log('📚 Création des établissements...');
    
    const establishment1 = {
      id: 'est-001-main',
      name: 'StacGate Academy',
      slug: 'stacgate-academy',
      description: 'École de formation professionnelle en technologie',
      logo: '/logos/stacgate.png',
      domain: 'stacgate.academy',
      isActive: true
    };
    
    const establishment2 = {
      id: 'est-002-tech',
      name: 'TechPro Institute',
      slug: 'techpro-institute', 
      description: 'Institut de formation technique avancée',
      logo: '/logos/techpro.png',
      domain: 'techpro.institute',
      isActive: true
    };

    await db.execute(`
      INSERT INTO establishments (id, name, slug, description, logo, domain, is_active)
      VALUES 
        ('${establishment1.id}', '${establishment1.name}', '${establishment1.slug}', '${establishment1.description}', '${establishment1.logo}', '${establishment1.domain}', ${establishment1.isActive}),
        ('${establishment2.id}', '${establishment2.name}', '${establishment2.slug}', '${establishment2.description}', '${establishment2.logo}', '${establishment2.domain}', ${establishment2.isActive})
    `);

    // 2. Créer les utilisateurs avec mots de passe hashés
    console.log('👥 Création des utilisateurs...');

    await db.execute(`
      INSERT INTO users (id, establishment_id, email, username, password, first_name, last_name, role, is_active)
      VALUES 
        ('user-001-super', '${establishment1.id}', 'superadmin@stacgate.com', 'superadmin', '${hashedPassword}', 'Super', 'Admin', 'super_admin', true),
        ('user-002-admin1', '${establishment1.id}', 'admin@stacgate.com', 'admin', '${hashedPassword}', 'Admin', 'StacGate', 'admin', true),
        ('user-003-admin2', '${establishment2.id}', 'admin@techpro.com', 'admin.techpro', '${hashedPassword}', 'Admin', 'TechPro', 'admin', true),
        ('user-004-manager', '${establishment1.id}', 'manager@stacgate.com', 'manager', '${hashedPassword2}', 'Manager', 'Test', 'manager', true),
        ('user-005-formateur', '${establishment1.id}', 'formateur@stacgate.com', 'formateur', '${hashedPassword2}', 'Formateur', 'Test', 'formateur', true),
        ('user-006-apprenant', '${establishment1.id}', 'apprenant@stacgate.com', 'apprenant', '${hashedPassword2}', 'Apprenant', 'Test', 'apprenant', true)
    `);

    // 3. Créer un thème par défaut
    console.log('🎨 Création des thèmes...');
    
    await db.execute(`
      INSERT INTO themes (id, establishment_id, name, is_active, primary_color, secondary_color, accent_color)
      VALUES 
        ('theme-001', '${establishment1.id}', 'Thème StacGate', true, '#6366f1', '#06b6d4', '#10b981'),
        ('theme-002', '${establishment2.id}', 'Thème TechPro', true, '#7c3aed', '#059669', '#dc2626')
    `);

    // 4. Créer quelques cours exemples
    console.log('📖 Création des cours...');
    
    await db.execute(`
      INSERT INTO courses (id, establishment_id, title, description, type, is_active, instructor_id)
      VALUES 
        ('course-001', '${establishment1.id}', 'Introduction au Développement Web', 'Cours complet pour débuter en développement web', 'asynchrone', true, 'user-005-formateur'),
        ('course-002', '${establishment1.id}', 'JavaScript Avancé', 'Perfectionnement en JavaScript moderne', 'synchrone', true, 'user-005-formateur'),
        ('course-003', '${establishment2.id}', 'React & TypeScript', 'Développement d\'applications React avec TypeScript', 'asynchrone', true, 'user-003-admin2')
    `);

    console.log('✅ Base de données réinitialisée avec succès !');
    console.log('\n🔑 Identifiants de connexion :');
    console.log('═══════════════════════════════════════════');
    console.log('📧 SUPER ADMIN :');
    console.log('   Email: superadmin@stacgate.com');
    console.log('   Mot de passe: admin123');
    console.log('');
    console.log('📧 ADMIN STACGATE :');
    console.log('   Email: admin@stacgate.com');
    console.log('   Mot de passe: admin123');
    console.log('');
    console.log('📧 ADMIN TECHPRO :');
    console.log('   Email: admin@techpro.com');
    console.log('   Mot de passe: admin123');
    console.log('');
    console.log('📧 MANAGER :');
    console.log('   Email: manager@stacgate.com');
    console.log('   Mot de passe: password123');
    console.log('');
    console.log('📧 FORMATEUR :');
    console.log('   Email: formateur@stacgate.com');
    console.log('   Mot de passe: password123');
    console.log('');
    console.log('📧 APPRENANT :');
    console.log('   Email: apprenant@stacgate.com');
    console.log('   Mot de passe: password123');
    console.log('═══════════════════════════════════════════');

  } catch (error) {
    console.error('❌ Erreur lors de la réinitialisation:', error);
  } finally {
    await sql.end();
  }
}

createTestData();
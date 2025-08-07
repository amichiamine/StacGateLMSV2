import { establishmentService } from './establishment-service';
import { databaseManager } from './database-manager';

async function initializeDatabase() {
  console.log('🚀 Initialisation de la base de données multi-établissements...');
  
  try {
    // Créer des établissements par défaut
    const establishments = [
      {
        name: 'École Polytechnique de Paris',
        slug: 'polytechnique-paris',
        description: 'Grande école d\'ingénieurs française de premier plan',
        logo: '/images/polytechnique-logo.png',
        domain: 'polytechnique.edu'
      },
      {
        name: 'Université de la Sorbonne',
        slug: 'sorbonne',
        description: 'Université pluridisciplinaire de renommée internationale',
        logo: '/images/sorbonne-logo.png',
        domain: 'sorbonne.fr'
      },
      {
        name: 'École de Commerce de Lyon',
        slug: 'emlyon',
        description: 'École de commerce et de management',
        logo: '/images/emlyon-logo.png',
        domain: 'emlyon.com'
      }
    ];

    const createdEstablishments = [];
    
    for (const establishmentData of establishments) {
      try {
        console.log(`📚 Création de l'établissement: ${establishmentData.name}`);
        const establishment = await establishmentService.createEstablishment(establishmentData);
        createdEstablishments.push(establishment);
        console.log(`✅ Établissement créé avec ID: ${establishment.id}`);
      } catch (error) {
        console.error(`❌ Erreur lors de la création de ${establishmentData.name}:`, error);
      }
    }

    console.log(`\n📊 Résumé de l'initialisation:`);
    console.log(`✅ ${createdEstablishments.length} établissements créés`);
    
    // Afficher les statistiques pour chaque établissement
    for (const establishment of createdEstablishments) {
      try {
        const stats = await establishmentService.getEstablishmentStats(establishment.id);
        console.log(`📈 ${establishment.name}: ${stats.users} utilisateurs, ${stats.courses} cours, ${stats.themes} thèmes`);
      } catch (error) {
        console.error(`❌ Impossible de récupérer les stats pour ${establishment.name}`);
      }
    }

    console.log('\n🎉 Initialisation terminée avec succès !');
    console.log('\n📝 Informations de connexion Super Admin:');
    console.log('   Email: superadmin@stacgatelms.com');
    console.log('   Mot de passe: admin123');
    console.log('\n🌐 Accès Super Admin: http://localhost:5000/super-admin');
    
  } catch (error) {
    console.error('❌ Erreur lors de l\'initialisation:', error);
  } finally {
    // Fermer les connexions
    await databaseManager.closeAllConnections();
    process.exit(0);
  }
}

// Exécuter l'initialisation si le script est appelé directement
if (import.meta.url === `file://${process.argv[1]}`) {
  initializeDatabase();
}

export { initializeDatabase };
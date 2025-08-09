<?php
/**
 * Page de finalisation de l'installation
 */

// Récupération des configurations
$appConfig = $_SESSION['app_config'] ?? ['app_name' => 'StacGateLMS', 'admin_email' => '', 'app_url' => ''];

// Nettoyage de la session
unset($_SESSION['db_config']);
unset($_SESSION['app_config']);
?>

<div style="text-align: center; margin: 40px 0;">
    <div style="font-size: 4em; margin-bottom: 20px;">🎉</div>
    <h2 style="color: #10b981; margin-bottom: 10px;">Installation terminée avec succès !</h2>
    <p style="font-size: 18px; color: #6b7280;">
        Votre plateforme <?= htmlspecialchars($appConfig['app_name']) ?> est maintenant prête à l'emploi.
    </p>
</div>

<div class="two-column" style="margin: 40px 0;">
    <div style="padding: 30px; background: #f0f9ff; border-radius: 15px; border-left: 4px solid #0ea5e9;">
        <h3>🔐 Informations de connexion</h3>
        <div style="margin: 20px 0; font-family: monospace; background: white; padding: 15px; border-radius: 8px; border: 1px solid #e5e7eb;">
            <p><strong>Email :</strong> <?= htmlspecialchars($appConfig['admin_email']) ?></p>
            <p><strong>Mot de passe :</strong> Le mot de passe que vous avez défini</p>
            <p><strong>Rôle :</strong> Super Administrateur</p>
        </div>
        <small style="color: #6b7280;">
            Utilisez ces informations pour vous connecter à votre plateforme.
        </small>
    </div>

    <div style="padding: 30px; background: #f0fdf4; border-radius: 15px; border-left: 4px solid #10b981;">
        <h3>🚀 Prochaines étapes</h3>
        <ol style="margin: 20px 0; padding-left: 20px; line-height: 1.8;">
            <li>Connectez-vous avec votre compte administrateur</li>
            <li>Explorez l'interface de super administration</li>
            <li>Configurez vos établissements</li>
            <li>Créez vos premiers cours</li>
            <li>Invitez vos utilisateurs</li>
        </ol>
    </div>
</div>

<div style="margin: 40px 0; padding: 30px; background: #fef3c7; border-radius: 15px; border-left: 4px solid #f59e0b;">
    <h3>⚡ Fonctionnalités disponibles</h3>
    <div class="two-column" style="margin-top: 20px;">
        <div>
            <h4>🎓 Formation</h4>
            <ul style="list-style: none; padding: 0; margin: 10px 0;">
                <li style="padding: 5px 0;">✅ Gestion des cours</li>
                <li style="padding: 5px 0;">✅ Évaluations et quiz</li>
                <li style="padding: 5px 0;">✅ Groupes d'étude</li>
                <li style="padding: 5px 0;">✅ Suivi des progressions</li>
            </ul>
        </div>
        <div>
            <h4>🛠️ Administration</h4>
            <ul style="list-style: none; padding: 0; margin: 10px 0;">
                <li style="padding: 5px 0;">✅ Multi-établissements</li>
                <li style="padding: 5px 0;">✅ Gestion des utilisateurs</li>
                <li style="padding: 5px 0;">✅ Analytics avancées</li>
                <li style="padding: 5px 0;">✅ Personnalisation thèmes</li>
            </ul>
        </div>
    </div>
</div>

<div style="text-align: center; margin: 40px 0;">
    <a href="../" class="btn" style="font-size: 18px; padding: 15px 40px; margin-right: 15px;">
        🏠 Accéder à votre plateforme
    </a>
    
    <a href="../login" class="btn secondary" style="font-size: 18px; padding: 15px 40px;">
        🔑 Page de connexion
    </a>
</div>

<div style="margin: 40px 0; padding: 25px; background: #f8fafc; border-radius: 15px; border: 1px solid #e2e8f0;">
    <h3>📚 Ressources utiles</h3>
    <div class="two-column" style="margin-top: 20px;">
        <div>
            <h4>Documentation</h4>
            <ul style="list-style: none; padding: 0; margin: 15px 0; line-height: 1.8;">
                <li>📖 <a href="../manual" style="color: #8B5CF6; text-decoration: none;">Manuel utilisateur</a></li>
                <li>🔧 <a href="../help-center" style="color: #8B5CF6; text-decoration: none;">Centre d'aide</a></li>
                <li>⚙️ <a href="../super-admin" style="color: #8B5CF6; text-decoration: none;">Panel super admin</a></li>
            </ul>
        </div>
        <div>
            <h4>Support</h4>
            <ul style="list-style: none; padding: 0; margin: 15px 0; line-height: 1.8;">
                <li>🎯 Interface intuitive et moderne</li>
                <li>🔒 Sécurité enterprise-grade</li>
                <li>📱 Responsive design</li>
                <li>🌍 Multi-tenant natif</li>
            </ul>
        </div>
    </div>
</div>

<div style="margin: 40px 0; padding: 25px; background: #fef2f2; border-radius: 15px; border-left: 4px solid #ef4444;">
    <h3>🛡️ Sécurité importante</h3>
    <div style="margin: 20px 0;">
        <p style="margin-bottom: 15px; line-height: 1.6;">
            <strong>⚠️ Supprimez le dossier d'installation :</strong>
        </p>
        <div style="background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 8px; font-family: monospace; margin: 15px 0;">
            rm -rf install/<br>
            rm install.php
        </div>
        <p style="color: #6b7280; font-size: 14px; margin-top: 10px;">
            Ces fichiers d'installation ne doivent pas rester accessibles en production pour des raisons de sécurité.
        </p>
    </div>
</div>

<div style="text-align: center; margin: 50px 0; padding: 30px; border-top: 2px solid #e5e7eb;">
    <h3 style="color: #8B5CF6; margin-bottom: 15px;">Merci d'avoir choisi StacGateLMS !</h3>
    <p style="color: #6b7280; font-size: 16px; line-height: 1.6;">
        Votre plateforme d'apprentissage moderne est maintenant opérationnelle.<br>
        Nous vous souhaitons une excellente expérience avec votre nouveau LMS.
    </p>
    
    <div style="margin-top: 30px;">
        <a href="../" class="btn" style="background: linear-gradient(135deg, #8B5CF6, #A78BFA); font-size: 20px; padding: 18px 50px;">
            🚀 Commencer maintenant
        </a>
    </div>
</div>

<script>
// Effet de confettis (optionnel)
function createConfetti() {
    const colors = ['#8B5CF6', '#A78BFA', '#C4B5FD', '#10b981', '#f59e0b'];
    
    for (let i = 0; i < 50; i++) {
        const confetti = document.createElement('div');
        confetti.style.position = 'fixed';
        confetti.style.width = '10px';
        confetti.style.height = '10px';
        confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.left = Math.random() * 100 + 'vw';
        confetti.style.top = '-10px';
        confetti.style.opacity = Math.random();
        confetti.style.zIndex = '1000';
        confetti.style.borderRadius = '50%';
        
        document.body.appendChild(confetti);
        
        const fallDuration = Math.random() * 3 + 2;
        confetti.animate([
            { transform: 'translateY(0) rotate(0deg)' },
            { transform: `translateY(100vh) rotate(${Math.random() * 360}deg)` }
        ], {
            duration: fallDuration * 1000,
            easing: 'linear'
        });
        
        setTimeout(() => {
            confetti.remove();
        }, fallDuration * 1000);
    }
}

// Lancer les confettis au chargement
document.addEventListener('DOMContentLoaded', createConfetti);
</script>
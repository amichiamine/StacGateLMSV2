<?php
/**
 * Page Manuel utilisateur
 * Guide d'utilisation interactif de la plateforme
 */

// Vérifier l'authentification
Auth::requireAuth();

$pageTitle = "Manuel Utilisateur - StacGateLMS";
$pageDescription = "Guide d'utilisation complet de la plateforme e-learning.";

$currentUser = Auth::user();

// Sections du manuel selon le rôle
$sections = [
    'apprenant' => [
        'getting-started' => 'Premiers pas',
        'courses' => 'Suivre des cours',
        'assessments' => 'Passer des évaluations',
        'study-groups' => 'Rejoindre des groupes',
        'profile' => 'Gérer son profil'
    ],
    'formateur' => [
        'getting-started' => 'Premiers pas',
        'course-creation' => 'Créer des cours',
        'content-management' => 'Gérer le contenu',
        'student-management' => 'Suivre les apprenants',
        'assessments' => 'Créer des évaluations',
        'analytics' => 'Analyser les performances'
    ],
    'manager' => [
        'getting-started' => 'Premiers pas',
        'user-management' => 'Gérer les utilisateurs',
        'course-management' => 'Superviser les cours',
        'reports' => 'Générer des rapports',
        'settings' => 'Configurer la plateforme'
    ],
    'admin' => [
        'getting-started' => 'Administration',
        'system-management' => 'Gestion système',
        'user-management' => 'Gestion utilisateurs',
        'establishment-management' => 'Gestion établissements',
        'security' => 'Sécurité et permissions',
        'maintenance' => 'Maintenance système'
    ]
];

$userSections = $sections[$currentUser['role']] ?? $sections['apprenant'];
$currentSection = $_GET['section'] ?? 'getting-started';

require_once ROOT_PATH . '/includes/header.php';
?>

<div style="padding: 2rem 0; margin-top: 80px;">
    <div class="container">
        <!-- En-tête -->
        <div class="glassmorphism p-6 mb-8">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                        📖 Manuel Utilisateur
                    </h1>
                    <p style="opacity: 0.8;">
                        Guide d'utilisation personnalisé pour les <?= ucfirst(str_replace('_', ' ', $currentUser['role'])) ?>s
                    </p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button onclick="printManual()" class="glass-button">
                        🖨️ Imprimer
                    </button>
                    <button onclick="exportPDF()" class="glass-button glass-button-secondary">
                        📄 Export PDF
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-layout" style="grid-template-columns: 250px 1fr; gap: 2rem;">
            <!-- Navigation sections -->
            <div class="glassmorphism p-4">
                <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem;">
                    Sections
                </h3>
                <nav style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($userSections as $sectionId => $sectionTitle): ?>
                        <a href="?section=<?= $sectionId ?>" 
                           class="nav-link <?= $currentSection === $sectionId ? 'active' : '' ?>"
                           style="padding: 0.75rem 1rem; border-radius: 8px; text-decoration: none; transition: all 0.3s; <?= $currentSection === $sectionId ? 'background: rgba(var(--color-primary), 0.1); color: rgb(var(--color-primary));' : '' ?>">
                            <?= htmlspecialchars($sectionTitle) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <!-- Contenu principal -->
            <div class="glassmorphism p-6">
                <div id="manual-content">
                    <?php
                    $contentFile = ROOT_PATH . "/php-migration/manual-content/{$currentUser['role']}/{$currentSection}.php";
                    if (file_exists($contentFile)) {
                        include $contentFile;
                    } else {
                        // Contenu par défaut
                        include ROOT_PATH . "/php-migration/manual-content/default.php";
                    }
                    ?>
                </div>

                <!-- Navigation bas de page -->
                <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between;">
                    <?php
                    $sectionKeys = array_keys($userSections);
                    $currentIndex = array_search($currentSection, $sectionKeys);
                    $prevSection = $currentIndex > 0 ? $sectionKeys[$currentIndex - 1] : null;
                    $nextSection = $currentIndex < count($sectionKeys) - 1 ? $sectionKeys[$currentIndex + 1] : null;
                    ?>
                    
                    <?php if ($prevSection): ?>
                        <a href="?section=<?= $prevSection ?>" class="glass-button glass-button-secondary">
                            ← <?= htmlspecialchars($userSections[$prevSection]) ?>
                        </a>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                    
                    <?php if ($nextSection): ?>
                        <a href="?section=<?= $nextSection ?>" class="glass-button">
                            <?= htmlspecialchars($userSections[$nextSection]) ?> →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printManual() {
    window.print();
}

async function exportPDF() {
    try {
        const response = await fetch('/api/exports/reports', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                report_type: 'manual',
                format: 'pdf',
                section: '<?= $currentSection ?>',
                role: '<?= $currentUser['role'] ?>'
            })
        });
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `manuel-utilisateur-${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } else {
            throw new Error('Erreur lors de la génération du PDF');
        }
    } catch (error) {
        console.error('Erreur export PDF:', error);
        alert('Erreur lors de l\'export PDF');
    }
}

// Navigation clavier
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey || e.metaKey) {
        switch (e.key) {
            case 'p':
                e.preventDefault();
                printManual();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                <?php if ($prevSection): ?>
                    window.location.href = '?section=<?= $prevSection ?>';
                <?php endif; ?>
                break;
            case 'ArrowRight':
                e.preventDefault();
                <?php if ($nextSection): ?>
                    window.location.href = '?section=<?= $nextSection ?>';
                <?php endif; ?>
                break;
        }
    }
});
</script>

<style>
.nav-link:hover {
    background: rgba(var(--color-primary), 0.05) !important;
    transform: translateX(4px);
}

@media print {
    .glassmorphism {
        background: white !important;
        backdrop-filter: none !important;
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
    
    .nav-link {
        color: #333 !important;
    }
}

@media (max-width: 768px) {
    .grid-layout {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
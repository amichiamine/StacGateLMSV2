<?php
/**
 * Page gestion des cours
 */

// Vérifier l'authentification
Auth::requireAuth();

$pageTitle = "Mes cours - StacGateLMS";
$pageDescription = "Gérez et suivez vos cours, votre progression et vos inscriptions.";

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

// Initialiser les services
$courseService = new CourseService();

// Paramètres de pagination et filtres
$page = intval($_GET['page'] ?? 1);
$perPage = 12;
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

$filters = [];
if ($search) $filters['search'] = $search;
if ($category) $filters['category'] = $category;
if ($status) $filters['status'] = $status;

// Obtenir les données selon le rôle
$userCourses = [];
$allCourses = [];

try {
    if (Auth::hasRole('apprenant')) {
        // Cours de l'apprenant
        $userCoursesData = $courseService->getUserCourses($currentUser['id'], $page, $perPage);
        $userCourses = $userCoursesData['data'];
        $meta = $userCoursesData['meta'];
        
        // Cours disponibles pour inscription
        $availableCoursesData = $courseService->getCoursesByEstablishment($establishmentId, 1, 8, ['not_enrolled' => $currentUser['id']]);
        $allCourses = $availableCoursesData['data'];
        
    } elseif (Auth::hasRole('formateur') && !Auth::hasRole('manager')) {
        // Cours enseignés par le formateur
        $filters['instructor_id'] = $currentUser['id'];
        $coursesData = $courseService->getCoursesByEstablishment($establishmentId, $page, $perPage, $filters);
        $userCourses = $coursesData['data'];
        $meta = $coursesData['meta'];
        
    } else {
        // Manager+ : tous les cours de l'établissement
        $coursesData = $courseService->getCoursesByEstablishment($establishmentId, $page, $perPage, $filters);
        $userCourses = $coursesData['data'];
        $meta = $coursesData['meta'];
    }
    
} catch (Exception $e) {
    Utils::log("Courses page error: " . $e->getMessage(), 'ERROR');
    $userCourses = [];
    $allCourses = [];
    $meta = ['total' => 0, 'current_page' => 1, 'total_pages' => 1];
}

require_once ROOT_PATH . '/includes/header.php';
?>

<div style="padding: 2rem 0; margin-top: 80px;">
    <div class="container">
        <!-- En-tête -->
        <div class="glassmorphism p-6 mb-8">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                        <?php if (Auth::hasRole('apprenant')): ?>
                            Mes cours
                        <?php elseif (Auth::hasRole('formateur') && !Auth::hasRole('manager')): ?>
                            Mes cours enseignés
                        <?php else: ?>
                            Gestion des cours
                        <?php endif; ?>
                    </h1>
                    <p style="opacity: 0.8;">
                        Total : <?= $meta['total'] ?> cours
                    </p>
                </div>
                
                <?php if (Auth::hasRole('formateur')): ?>
                    <a href="/courses/create" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 0.75rem 1.5rem;">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" style="margin-right: 0.5rem;">
                            <path d="M12 4v16m8-8H4"/>
                        </svg>
                        Créer un cours
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filtres -->
        <div class="glassmorphism p-4 mb-6">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Rechercher un cours..." 
                           class="glass-input" style="width: 100%; padding: 0.75rem 1rem;">
                </div>
                
                <select name="category" class="glass-input" style="padding: 0.75rem 1rem;">
                    <option value="">Toutes les catégories</option>
                    <option value="informatique" <?= $category === 'informatique' ? 'selected' : '' ?>>Informatique</option>
                    <option value="mathematiques" <?= $category === 'mathematiques' ? 'selected' : '' ?>>Mathématiques</option>
                    <option value="langues" <?= $category === 'langues' ? 'selected' : '' ?>>Langues</option>
                    <option value="sciences" <?= $category === 'sciences' ? 'selected' : '' ?>>Sciences</option>
                </select>
                
                <?php if (!Auth::hasRole('apprenant')): ?>
                    <select name="status" class="glass-input" style="padding: 0.75rem 1rem;">
                        <option value="">Tous les statuts</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Actif</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archivé</option>
                    </select>
                <?php endif; ?>
                
                <button type="submit" class="glass-button" style="padding: 0.75rem 1.5rem;">
                    Filtrer
                </button>
            </form>
        </div>

        <!-- Grille des cours -->
        <?php if (!empty($userCourses)): ?>
            <div class="grid grid-3 mb-8">
                <?php foreach ($userCourses as $course): ?>
                    <div class="glass-card p-6 course-card">
                        <!-- Image du cours -->
                        <div style="height: 150px; background: var(--gradient-primary); border-radius: 0.5rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                            <?php if ($course['thumbnail']): ?>
                                <img src="<?= htmlspecialchars($course['thumbnail']) ?>" alt="<?= htmlspecialchars($course['title']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 0.5rem;">
                            <?php else: ?>
                                📚
                            <?php endif; ?>
                        </div>
                        
                        <!-- Titre et catégorie -->
                        <div style="margin-bottom: 1rem;">
                            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">
                                <?= htmlspecialchars($course['title']) ?>
                            </h3>
                            
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <span class="badge" style="background: rgba(var(--color-primary), 0.1); color: rgb(var(--color-primary)); font-size: 0.75rem;">
                                    <?= htmlspecialchars($course['category'] ?? 'Général') ?>
                                </span>
                                
                                <?php if (isset($course['status'])): ?>
                                    <span class="badge <?= $course['status'] === 'active' ? 'badge-success' : ($course['status'] === 'draft' ? 'badge-warning' : 'badge-error') ?>">
                                        <?= ucfirst($course['status']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($course['description']): ?>
                                <p style="opacity: 0.8; font-size: 0.9rem;">
                                    <?= Utils::truncate(htmlspecialchars($course['description']), 100) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Progression (pour les apprenants) -->
                        <?php if (Auth::hasRole('apprenant') && isset($course['progress'])): ?>
                            <div style="margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem; font-size: 0.8rem;">
                                    <span>Progression</span>
                                    <span><?= intval($course['progress']) ?>%</span>
                                </div>
                                <div style="background: rgba(255, 255, 255, 0.1); height: 6px; border-radius: 3px; overflow: hidden;">
                                    <div style="background: rgb(var(--color-primary)); height: 100%; width: <?= intval($course['progress']) ?>%; transition: width 0.3s ease;"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Statistiques -->
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.8rem; opacity: 0.7;">
                            <div>
                                <div style="font-weight: 600;"><?= $course['enrolled_count'] ?? 0 ?></div>
                                <div>Inscrits</div>
                            </div>
                            <div>
                                <div style="font-weight: 600;"><?= $course['lessons_count'] ?? 0 ?></div>
                                <div>Leçons</div>
                            </div>
                            <div>
                                <div style="font-weight: 600;"><?= $course['duration'] ?? 0 ?>h</div>
                                <div>Durée</div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div style="display: flex; gap: 0.5rem;">
                            <?php if (Auth::hasRole('apprenant')): ?>
                                <a href="/courses/<?= $course['id'] ?>/learn" class="glass-button" style="flex: 1; text-align: center; background: var(--gradient-primary); color: white; padding: 0.75rem;">
                                    <?= isset($course['progress']) && $course['progress'] > 0 ? 'Continuer' : 'Commencer' ?>
                                </a>
                            <?php else: ?>
                                <a href="/courses/<?= $course['id'] ?>" class="glass-button" style="flex: 1; text-align: center; padding: 0.75rem;">
                                    Voir
                                </a>
                                <?php if (Auth::hasRole('formateur') && ($course['instructor_id'] == $currentUser['id'] || Auth::hasRole('admin'))): ?>
                                    <a href="/courses/<?= $course['id'] ?>/edit" class="glass-button glass-button-secondary" style="padding: 0.75rem;">
                                        ✏️
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- État vide -->
            <div class="glassmorphism p-8 text-center">
                <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;">📚</div>
                <h3 style="font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;">
                    <?php if (Auth::hasRole('apprenant')): ?>
                        Aucun cours inscrit
                    <?php else: ?>
                        Aucun cours trouvé
                    <?php endif; ?>
                </h3>
                <p style="opacity: 0.8; margin-bottom: 2rem;">
                    <?php if (Auth::hasRole('apprenant')): ?>
                        Explorez les cours disponibles et commencez votre apprentissage.
                    <?php elseif (Auth::hasRole('formateur')): ?>
                        Créez votre premier cours pour commencer à enseigner.
                    <?php else: ?>
                        Aucun cours ne correspond à vos critères de recherche.
                    <?php endif; ?>
                </p>
                
                <?php if (Auth::hasRole('apprenant')): ?>
                    <a href="#available-courses" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 1rem 2rem;">
                        Parcourir les cours
                    </a>
                <?php elseif (Auth::hasRole('formateur')): ?>
                    <a href="/courses/create" class="glass-button" style="background: var(--gradient-primary); color: white; padding: 1rem 2rem;">
                        Créer un cours
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Cours disponibles (pour les apprenants) -->
        <?php if (Auth::hasRole('apprenant') && !empty($allCourses)): ?>
            <div id="available-courses">
                <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: 2rem;">Cours disponibles</h2>
                
                <div class="grid grid-3">
                    <?php foreach ($allCourses as $course): ?>
                        <div class="glass-card p-6 course-card">
                            <!-- Image du cours -->
                            <div style="height: 150px; background: var(--gradient-secondary); border-radius: 0.5rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                <?php if ($course['thumbnail']): ?>
                                    <img src="<?= htmlspecialchars($course['thumbnail']) ?>" alt="<?= htmlspecialchars($course['title']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 0.5rem;">
                                <?php else: ?>
                                    📖
                                <?php endif; ?>
                            </div>
                            
                            <!-- Contenu -->
                            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">
                                <?= htmlspecialchars($course['title']) ?>
                            </h3>
                            
                            <?php if ($course['description']): ?>
                                <p style="opacity: 0.8; font-size: 0.9rem; margin-bottom: 1rem;">
                                    <?= Utils::truncate(htmlspecialchars($course['description']), 100) ?>
                                </p>
                            <?php endif; ?>
                            
                            <!-- Action -->
                            <button onclick="enrollInCourse(<?= $course['id'] ?>)" class="glass-button" style="width: 100%; background: var(--gradient-secondary); color: white; padding: 0.75rem;">
                                S'inscrire
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($meta['total_pages'] > 1): ?>
            <div style="display: flex; justify-content: center; margin-top: 2rem; gap: 0.5rem;">
                <?php for ($i = 1; $i <= $meta['total_pages']; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>" 
                       class="glass-button <?= $i == $meta['current_page'] ? 'active' : '' ?>" 
                       style="padding: 0.5rem 1rem; <?= $i == $meta['current_page'] ? 'background: var(--gradient-primary); color: white;' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
async function enrollInCourse(courseId) {
    try {
        const response = await apiRequest('/api/courses/enroll', 'POST', {
            course_id: courseId,
            action: 'enroll'
        });
        
        if (response.success) {
            showToast(response.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(response.error, 'error');
        }
    } catch (error) {
        showToast('Erreur lors de l\'inscription', 'error');
    }
}

// Animation des cartes
document.querySelectorAll('.course-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-3px)';
        this.style.transition = 'transform 0.3s ease';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});
</script>

<style>
.course-card {
    transition: all 0.3s ease;
}

.course-card:hover {
    transform: translateY(-3px);
}

@media (max-width: 768px) {
    .grid-3 {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
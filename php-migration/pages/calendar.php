<?php
/**
 * Page calendrier et planification
 */

// Vérifier l'authentification
Auth::requireAuth();

$pageTitle = "Calendrier - StacGateLMS";
$pageDescription = "Planification et gestion des événements.";

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

// Paramètres de vue
$currentMonth = $_GET['month'] ?? date('Y-m');
$view = $_GET['view'] ?? 'month'; // month, week, day

// Calculer les dates
$monthStart = $currentMonth . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));
$prevMonth = date('Y-m', strtotime($monthStart . ' -1 month'));
$nextMonth = date('Y-m', strtotime($monthStart . ' +1 month'));

// Initialiser les services
$courseService = new CourseService();
$assessmentService = new AssessmentService();

// Obtenir les événements du mois
try {
    $events = [];
    
    // Cours avec dates de début
    $courses = $courseService->getCoursesByDateRange($establishmentId, $monthStart, $monthEnd);
    foreach ($courses as $course) {
        $events[] = [
            'id' => 'course_' . $course['id'],
            'title' => $course['title'],
            'type' => 'course',
            'date' => $course['start_date'],
            'time' => $course['start_time'] ?? '09:00',
            'duration' => $course['duration'] ?? 60,
            'instructor' => $course['instructor_name'],
            'color' => 'rgb(var(--color-primary))'
        ];
    }
    
    // Évaluations avec dates d'échéance
    $assessments = $assessmentService->getAssessmentsByDateRange($establishmentId, $monthStart, $monthEnd);
    foreach ($assessments as $assessment) {
        $events[] = [
            'id' => 'assessment_' . $assessment['id'],
            'title' => $assessment['title'],
            'type' => 'assessment',
            'date' => $assessment['due_date'],
            'time' => $assessment['due_time'] ?? '23:59',
            'duration' => $assessment['time_limit'] ?? 60,
            'course' => $assessment['course_title'],
            'color' => 'rgb(var(--color-accent))'
        ];
    }
    
    // Événements personnalisés
    $customEvents = $courseService->getCustomEvents($establishmentId, $monthStart, $monthEnd);
    foreach ($customEvents as $event) {
        $events[] = [
            'id' => 'event_' . $event['id'],
            'title' => $event['title'],
            'type' => 'event',
            'date' => $event['event_date'],
            'time' => $event['event_time'],
            'duration' => $event['duration'] ?? 60,
            'description' => $event['description'],
            'color' => 'rgb(var(--color-secondary))'
        ];
    }
    
    // Organiser les événements par date
    $eventsByDate = [];
    foreach ($events as $event) {
        $date = $event['date'];
        if (!isset($eventsByDate[$date])) {
            $eventsByDate[$date] = [];
        }
        $eventsByDate[$date][] = $event;
    }
    
} catch (Exception $e) {
    Utils::log("Calendar page error: " . $e->getMessage(), 'ERROR');
    $events = [];
    $eventsByDate = [];
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
                        Calendrier
                    </h1>
                    <p style="opacity: 0.8;">
                        <?= strftime('%B %Y', strtotime($monthStart)) ?>
                    </p>
                </div>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <?php if (Auth::hasRole('formateur')): ?>
                        <button onclick="createEvent()" class="glass-button" style="background: var(--gradient-primary); color: white;">
                            Nouvel événement
                        </button>
                    <?php endif; ?>
                    <button onclick="exportCalendar()" class="glass-button glass-button-secondary">
                        Exporter (.ics)
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation et vues -->
        <div class="glassmorphism p-4 mb-6">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <!-- Navigation mois -->
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <a href="?month=<?= $prevMonth ?>&view=<?= $view ?>" class="glass-button glass-button-secondary">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                    </a>
                    
                    <h2 style="font-size: 1.25rem; font-weight: 600; margin: 0;">
                        <?= strftime('%B %Y', strtotime($monthStart)) ?>
                    </h2>
                    
                    <a href="?month=<?= $nextMonth ?>&view=<?= $view ?>" class="glass-button glass-button-secondary">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>
                    </a>
                    
                    <a href="?month=<?= date('Y-m') ?>&view=<?= $view ?>" class="glass-button glass-button-secondary">
                        Aujourd'hui
                    </a>
                </div>
                
                <!-- Sélecteur de vue -->
                <div style="display: flex; gap: 0.5rem;">
                    <a href="?month=<?= $currentMonth ?>&view=month" 
                       class="view-btn <?= $view === 'month' ? 'active' : '' ?>">Mois</a>
                    <a href="?month=<?= $currentMonth ?>&view=week" 
                       class="view-btn <?= $view === 'week' ? 'active' : '' ?>">Semaine</a>
                    <a href="?month=<?= $currentMonth ?>&view=day" 
                       class="view-btn <?= $view === 'day' ? 'active' : '' ?>">Jour</a>
                </div>
            </div>
        </div>

        <!-- Vue calendrier -->
        <?php if ($view === 'month'): ?>
            <!-- Vue mensuelle -->
            <div class="glassmorphism p-6">
                <div class="calendar-month">
                    <!-- En-têtes des jours -->
                    <div class="calendar-header">
                        <div class="calendar-day-header">Lun</div>
                        <div class="calendar-day-header">Mar</div>
                        <div class="calendar-day-header">Mer</div>
                        <div class="calendar-day-header">Jeu</div>
                        <div class="calendar-day-header">Ven</div>
                        <div class="calendar-day-header">Sam</div>
                        <div class="calendar-day-header">Dim</div>
                    </div>
                    
                    <!-- Grille des jours -->
                    <div class="calendar-grid">
                        <?php
                        $firstDay = date('Y-m-01', strtotime($monthStart));
                        $lastDay = date('Y-m-t', strtotime($monthStart));
                        $startWeek = date('Y-m-d', strtotime('monday this week', strtotime($firstDay)));
                        $endWeek = date('Y-m-d', strtotime('sunday this week', strtotime($lastDay)));
                        
                        $currentDate = $startWeek;
                        while ($currentDate <= $endWeek):
                            $isCurrentMonth = date('Y-m', strtotime($currentDate)) === $currentMonth;
                            $isToday = $currentDate === date('Y-m-d');
                            $dayEvents = $eventsByDate[$currentDate] ?? [];
                        ?>
                            <div class="calendar-day <?= $isCurrentMonth ? 'current-month' : 'other-month' ?> <?= $isToday ? 'today' : '' ?>"
                                 data-date="<?= $currentDate ?>">
                                <div class="day-number"><?= date('j', strtotime($currentDate)) ?></div>
                                
                                <?php foreach (array_slice($dayEvents, 0, 3) as $event): ?>
                                    <div class="calendar-event" 
                                         style="background: <?= $event['color'] ?>; opacity: 0.8;"
                                         onclick="showEventDetails('<?= $event['id'] ?>')">
                                        <div style="font-size: 0.75rem; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= htmlspecialchars($event['title']) ?>
                                        </div>
                                        <div style="font-size: 0.7rem; opacity: 0.8;">
                                            <?= date('H:i', strtotime($event['time'])) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (count($dayEvents) > 3): ?>
                                    <div class="more-events" onclick="showDayEvents('<?= $currentDate ?>')">
                                        +<?= count($dayEvents) - 3 ?> plus
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php
                            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
                        endwhile;
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Légende -->
        <div class="glassmorphism p-4 mt-6">
            <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 1rem;">Légende</h3>
            <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 16px; height: 16px; background: rgb(var(--color-primary)); border-radius: 2px;"></div>
                    <span style="font-size: 0.9rem;">Cours</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 16px; height: 16px; background: rgb(var(--color-accent)); border-radius: 2px;"></div>
                    <span style="font-size: 0.9rem;">Évaluations</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <div style="width: 16px; height: 16px; background: rgb(var(--color-secondary)); border-radius: 2px;"></div>
                    <span style="font-size: 0.9rem;">Événements</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal détails événement -->
<div id="event-modal" class="modal" style="display: none;">
    <div class="modal-content glassmorphism">
        <div class="modal-header">
            <h3 id="event-title"></h3>
            <button onclick="closeModal('event-modal')" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="event-details"></div>
        </div>
        <div class="modal-footer">
            <button onclick="closeModal('event-modal')" class="glass-button glass-button-secondary">
                Fermer
            </button>
        </div>
    </div>
</div>

<style>
.calendar-month {
    width: 100%;
}

.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    margin-bottom: 1px;
}

.calendar-day-header {
    padding: 1rem;
    text-align: center;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.25rem;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
}

.calendar-day {
    min-height: 120px;
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.calendar-day:hover {
    background: rgba(255, 255, 255, 0.1);
}

.calendar-day.today {
    background: rgba(var(--color-primary), 0.2);
    border: 2px solid rgb(var(--color-primary));
}

.calendar-day.other-month {
    opacity: 0.3;
}

.day-number {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.calendar-event {
    margin-bottom: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: opacity 0.3s ease;
}

.calendar-event:hover {
    opacity: 1 !important;
}

.more-events {
    font-size: 0.75rem;
    color: rgb(var(--color-accent));
    cursor: pointer;
    margin-top: 0.25rem;
}

.view-btn {
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    text-decoration: none;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.view-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.view-btn.active {
    background: var(--gradient-primary);
    color: white;
    border-color: transparent;
}

@media (max-width: 768px) {
    .calendar-day {
        min-height: 80px;
    }
    
    .calendar-day-header, .day-number {
        font-size: 0.9rem;
    }
    
    .calendar-event {
        font-size: 0.7rem;
    }
}
</style>

<script>
// Détails d'événement
function showEventDetails(eventId) {
    // Récupérer les détails depuis l'API
    fetch(`/api/calendar/events/${eventId}`)
        .then(response => response.json())
        .then(event => {
            document.getElementById('event-title').textContent = event.title;
            document.getElementById('event-details').innerHTML = `
                <p><strong>Type:</strong> ${event.type}</p>
                <p><strong>Date:</strong> ${event.date}</p>
                <p><strong>Heure:</strong> ${event.time}</p>
                <p><strong>Durée:</strong> ${event.duration} min</p>
                ${event.description ? `<p><strong>Description:</strong> ${event.description}</p>` : ''}
            `;
            openModal('event-modal');
        })
        .catch(error => {
            showToast('Erreur lors du chargement de l\'événement', 'error');
        });
}

// Créer un événement
function createEvent() {
    // Rediriger vers le formulaire de création ou ouvrir une modal
    window.location.href = '/courses?action=create';
}

// Export calendrier
function exportCalendar() {
    window.location.href = `/api/calendar/export?month=<?= $currentMonth ?>&format=ics`;
}

// Afficher les événements d'un jour
function showDayEvents(date) {
    window.location.href = `?view=day&date=${date}`;
}
</script>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
<?php
$pageTitle = 'Campus Events & Workshops';

require_once __DIR__ . '/config/database.php';

// Get published future events from the database
$sql = "
    SELECT
        e.id,
        e.title,
        e.description,
        e.category,
        e.venue,
        e.event_date,
        e.registration_deadline,
        e.capacity,
        (
            SELECT COUNT(*)
            FROM registrations r
            WHERE r.event_id = e.id
              AND r.status = 'registered'
        ) AS registered_count
    FROM events e
    WHERE e.status = 'published'
      AND e.event_date >= NOW()
    ORDER BY e.event_date ASC
    LIMIT 6
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$events = $stmt->fetchAll();

// Total published upcoming events count
$totalUpcomingCount = count($events);

require_once __DIR__ . '/includes/header.php';

function homeEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<main>
    <!-- Welcome Introduction -->
    <section class="py-5 border-bottom bg-white">
        <div class="container py-lg-3">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="cc-eyebrow">College Event Hub</span>

                    <h1 class="h1 fw-bold mt-1 mb-3" style="color: var(--cc-text-main);">
                        College events, workshops, and activities in one place
                    </h1>

                    <p class="text-secondary mb-4" style="max-width: 560px; font-size: 1.05rem; line-height: 1.6;">
                        CampusConnect centralizes departmental workshops, seminars, competitions, and student activities. Anyone can browse upcoming events and check real-time seat availability.
                    </p>

                    <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                        <a href="/campusconnect/events.php" class="btn cc-btn-primary cc-btn-lg">
                            <span>Browse Events</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                        </a>

                        <?php if ($isLoggedIn): ?>
                            <a href="/campusconnect/dashboard.php" class="btn cc-btn-outline cc-btn-lg">
                                Student Dashboard
                            </a>
                        <?php else: ?>
                            <a href="/campusconnect/register.php" class="btn cc-btn-outline cc-btn-lg">
                                Create Account
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex flex-wrap gap-3 pt-3 text-muted small border-top" style="border-color: var(--cc-border) !important;">
                        <span>&bull; Public browsing with no login required</span>
                        <span>&bull; Verified capacity limits</span>
                        <span>&bull; Student attendance tracking</span>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="cc-card p-4 p-md-4">
                        <h2 class="h5 fw-bold mb-2">Platform Overview</h2>
                        <p class="text-secondary small mb-3">
                            Event details and seat availability update automatically from the college database.
                        </p>

                        <div class="p-3 rounded-2 mb-3" style="background: var(--cc-surface-muted); border: 1px solid var(--cc-border);">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-muted fw-semibold">Upcoming Published Events</span>
                                <span class="fw-bold text-dark"><?= $totalUpcomingCount ?> available</span>
                            </div>
                            <div class="small text-muted">
                                Real-time slots synchronized with campus coordinators.
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="/campusconnect/events.php" class="btn cc-btn-outline w-100 btn-sm">
                                Search by Category or Venue
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Upcoming Events Section -->
    <section class="container py-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
            <div>
                <span class="cc-eyebrow">Schedule</span>
                <h2 class="h3 fw-bold mb-0">Upcoming Events</h2>
                <p class="text-secondary small mb-0">Activities scheduled across academic departments and clubs.</p>
            </div>

            <a href="/campusconnect/events.php" class="btn cc-btn-outline cc-btn-sm">
                <span>View all events</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>

        <?php if (empty($events)): ?>
            <div class="cc-empty-state">
                <div class="cc-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <h3 class="h5 fw-bold">No upcoming events published yet</h3>
                <p class="text-secondary small mx-auto" style="max-width: 420px;">
                    Department coordinators will publish new workshops and activities soon. Check back shortly.
                </p>
                <a href="/campusconnect/events.php" class="btn cc-btn-outline btn-sm mt-2">
                    Browse All Records
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($events as $event): ?>
                    <?php
                    $eventTimestamp = strtotime($event['event_date']);
                    $capacity = (int) $event['capacity'];
                    $registered = (int) $event['registered_count'];
                    $remaining = max(0, $capacity - $registered);
                    $percentFilled = $capacity > 0 ? min(100, round(($registered / $capacity) * 100)) : 100;

                    $statusBadge = 'cc-badge-success';
                    $statusText = $remaining . ' seats left';

                    if ($remaining === 0) {
                        $statusBadge = 'cc-badge-danger';
                        $statusText = 'Full capacity';
                    } elseif ($remaining <= 5) {
                        $statusBadge = 'cc-badge-warning';
                        $statusText = 'Only ' . $remaining . ' left';
                    }
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <article class="cc-card cc-card-interactive h-100 d-flex flex-column p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="cc-badge cc-badge-category">
                                    <?= homeEscape($event['category']) ?>
                                </span>
                                <span class="cc-badge <?= $statusBadge ?>">
                                    <?= $statusText ?>
                                </span>
                            </div>

                            <h3 class="h5 fw-bold mb-2">
                                <a href="/campusconnect/event-details.php?id=<?= (int) $event['id'] ?>" class="text-reset text-decoration-none">
                                    <?= homeEscape($event['title']) ?>
                                </a>
                            </h3>

                            <p class="text-secondary small mb-4 flex-grow-1" style="line-height: 1.6;">
                                <?= homeEscape(mb_strimwidth($event['description'], 0, 120, '...')) ?>
                            </p>

                            <div class="pt-3 border-top mt-auto" style="border-color: var(--cc-border) !important;">
                                <div class="d-flex align-items-center gap-2 text-secondary small mb-2">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <span>
                                        <?= $eventTimestamp !== false ? date('D, M j, Y, g:i A', $eventTimestamp) : 'Date to be announced' ?>
                                    </span>
                                </div>

                                <div class="d-flex align-items-center gap-2 text-secondary small mb-3">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    <span class="text-truncate"><?= homeEscape($event['venue']) ?></span>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Seats filled</span>
                                        <span class="fw-semibold text-dark"><?= $registered ?> / <?= $capacity ?></span>
                                    </div>
                                    <div class="cc-progress-track">
                                        <div class="cc-progress-fill" style="width: <?= $percentFilled ?>%;"></div>
                                    </div>
                                </div>

                                <a href="/campusconnect/event-details.php?id=<?= (int) $event['id'] ?>" class="btn cc-btn-outline w-100 btn-sm">
                                    View Details
                                </a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Categories Section -->
    <section id="categories" class="py-5 bg-white border-top border-bottom">
        <div class="container py-2">
            <div class="mb-4">
                <span class="cc-eyebrow">Explore</span>
                <h2 class="h3 fw-bold mb-1">Browse by Category</h2>
                <p class="text-secondary small mb-0">Filter campus activities based on topics of interest.</p>
            </div>

            <div class="row g-3">
                <?php
                $categoriesList = [
                    [
                        'name' => 'Technology',
                        'desc' => 'Hackathons, coding workshops, and software talks.'
                    ],
                    [
                        'name' => 'Workshop',
                        'desc' => 'Practical hands-on training sessions with instructors.'
                    ],
                    [
                        'name' => 'Cultural',
                        'desc' => 'Music, performing arts, exhibitions, and college festivals.'
                    ],
                    [
                        'name' => 'Sports',
                        'desc' => 'Tournaments, athletics meets, and recreational matches.'
                    ],
                    [
                        'name' => 'Education',
                        'desc' => 'Academic lectures, career preparation, and research talks.'
                    ],
                    [
                        'name' => 'Other',
                        'desc' => 'Club orientations, volunteer activities, and informal sessions.'
                    ],
                ];
                ?>

                <?php foreach ($categoriesList as $cat): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="cc-card p-3 h-100 d-flex flex-column justify-content-between">
                            <div>
                                <h3 class="h6 fw-bold mb-1"><?= homeEscape($cat['name']) ?></h3>
                                <p class="text-secondary small mb-3">
                                    <?= homeEscape($cat['desc']) ?>
                                </p>
                            </div>
                            <div>
                                <a href="/campusconnect/events.php?category=<?= urlencode($cat['name']) ?>" class="small fw-semibold text-primary">
                                    Browse <?= homeEscape($cat['name']) ?> &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- How the Platform Works -->
    <section id="about" class="container py-5">
        <div class="mb-4">
            <span class="cc-eyebrow">Workflow</span>
            <h2 class="h3 fw-bold mb-1">How Event Registration Works</h2>
            <p class="text-secondary small mb-0">Three steps from finding an event to participating on campus.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="cc-card p-4 h-100">
                    <span class="cc-badge cc-badge-category mb-3">Step 1</span>
                    <h3 class="h5 fw-bold mb-2">Browse the Directory</h3>
                    <p class="text-secondary small mb-0">
                        Visitors can view scheduled events, check venue locations, and see remaining seats without logging in.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="cc-card p-4 h-100">
                    <span class="cc-badge cc-badge-category mb-3">Step 2</span>
                    <h3 class="h5 fw-bold mb-2">Reserve a Seat</h3>
                    <p class="text-secondary small mb-0">
                        Log in with your student account to register. Seats are reserved immediately, protecting against overbooking.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="cc-card p-4 h-100">
                    <span class="cc-badge cc-badge-category mb-3">Step 3</span>
                    <h3 class="h5 fw-bold mb-2">Attend and Check In</h3>
                    <p class="text-secondary small mb-0">
                        View your schedule in your student dashboard. On event day, organizers verify attendance at the venue door.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-5 bg-white border-top">
        <div class="container py-2">
            <div class="mb-4">
                <span class="cc-eyebrow">Help</span>
                <h2 class="h3 fw-bold mb-1">Frequently Asked Questions</h2>
                <p class="text-secondary small mb-0">Information about registrations, cancellations, and venue capacities.</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">

                        <div class="accordion-item cc-card mb-2" style="border: 1px solid var(--cc-border);">
                            <h3 class="accordion-header" id="faq1Heading">
                                <button class="accordion-button fw-semibold py-3 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq1Collapse" aria-expanded="true" aria-controls="faq1Collapse" style="background: transparent; color: var(--cc-text-main); box-shadow: none;">
                                    Do I need an account to view events?
                                </button>
                            </h3>
                            <div id="faq1Collapse" class="accordion-collapse collapse show" aria-labelledby="faq1Heading" data-bs-parent="#faqAccordion">
                                <div class="accordion-body px-3 pb-3 pt-0 text-secondary small">
                                    No. Anyone can browse the homepage, view the event directory, check categories, search by keywords, and review venue capacities without signing in.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item cc-card mb-2" style="border: 1px solid var(--cc-border);">
                            <h3 class="accordion-header" id="faq2Heading">
                                <button class="accordion-button collapsed fw-semibold py-3 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq2Collapse" aria-expanded="false" aria-controls="faq2Collapse" style="background: transparent; color: var(--cc-text-main); box-shadow: none;">
                                    When do I need to log in?
                                </button>
                            </h3>
                            <div id="faq2Collapse" class="accordion-collapse collapse" aria-labelledby="faq2Heading" data-bs-parent="#faqAccordion">
                                <div class="accordion-body px-3 pb-3 pt-0 text-secondary small">
                                    A student account is required only when you want to reserve a seat for an event, view your registered bookings in the dashboard, or cancel a registration.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item cc-card mb-2" style="border: 1px solid var(--cc-border);">
                            <h3 class="accordion-header" id="faq3Heading">
                                <button class="accordion-button collapsed fw-semibold py-3 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq3Collapse" aria-expanded="false" aria-controls="faq3Collapse" style="background: transparent; color: var(--cc-text-main); box-shadow: none;">
                                    Can I cancel my registration if my schedule changes?
                                </button>
                            </h3>
                            <div id="faq3Collapse" class="accordion-collapse collapse" aria-labelledby="faq3Heading" data-bs-parent="#faqAccordion">
                                <div class="accordion-body px-3 pb-3 pt-0 text-secondary small">
                                    Yes. You can cancel your registration from the My Registrations page as long as the event has not yet taken place. Cancelling immediately frees your seat for other students.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item cc-card" style="border: 1px solid var(--cc-border);">
                            <h3 class="accordion-header" id="faq4Heading">
                                <button class="accordion-button collapsed fw-semibold py-3 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq4Collapse" aria-expanded="false" aria-controls="faq4Collapse" style="background: transparent; color: var(--cc-text-main); box-shadow: none;">
                                    How are capacity limits managed?
                                </button>
                            </h3>
                            <div id="faq4Collapse" class="accordion-collapse collapse" aria-labelledby="faq4Heading" data-bs-parent="#faqAccordion">
                                <div class="accordion-body px-3 pb-3 pt-0 text-secondary small">
                                    Event capacity is enforced directly by the database using transactions and row locking. Once maximum capacity is reached, registration closes automatically.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

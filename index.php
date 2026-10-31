<?php
$pageTitle = 'Discover Campus Events — SKFGI Mankundu';

require_once __DIR__ . '/config/database.php';

// Get total count of all upcoming published events in database
$countStmt = $pdo->query("
    SELECT COUNT(*)
    FROM events
    WHERE status = 'published'
      AND event_date >= NOW()
");
$totalUpcomingCount = (int) $countStmt->fetchColumn();

// Get published upcoming events (up to 6 for the homepage spotlight)
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

require_once __DIR__ . '/includes/header.php';

function homeEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<main>

    <!-- Hero Section with SKFGI Institutional Context -->
    <section class="py-5" style="background: linear-gradient(180deg, #f6f1ff 0%, #faf9fd 100%); border-bottom: 1px solid var(--cc-border);">
        <div class="container py-lg-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="cc-eyebrow">SKFGI MANKUNDU &bull; STUDENT FULL-STACK MVP</span>

                    <h1 class="display-4 fw-bold mt-2 mb-3" style="font-family: 'Outfit', sans-serif; letter-spacing: -0.03em; color: var(--cc-text-main);">
                        Campus life, technical workshops, and activities <span style="color: var(--cc-primary);">at SKFGI.</span>
                    </h1>

                    <p class="lead text-secondary mb-4" style="max-width: 620px; font-size: 1.15rem; line-height: 1.7;">
                        A student-developed event management platform for Supreme Knowledge Foundation Group of Institutions (SKFGI), Mankundu, Hooghly. Explore computing hackathons, engineering exhibitions, and campus activities with live MySQL seat reservations.
                    </p>

                    <!-- Fast Search Bar -->
                    <form action="/campusconnect/events.php" method="get" class="mb-4" style="max-width: 580px;">
                        <div class="input-group p-1 bg-white rounded-3 shadow-sm" style="border: 1px solid var(--cc-border-strong);">
                            <span class="input-group-text bg-transparent border-0 text-muted ps-3 pe-2">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            </span>
                            <input
                                type="search"
                                name="search"
                                class="form-control border-0 shadow-none ps-1"
                                placeholder="Search SKFGI workshops, hackathons, or venues..."
                                aria-label="Search campus events"
                                maxlength="100"
                            >
                            <button type="submit" class="btn cc-btn-primary px-3 py-2">
                                <span>Find Events</span>
                            </button>
                        </div>
                    </form>

                    <!-- Quick Category Shortcuts -->
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="small text-muted fw-semibold me-1">Browse by topic:</span>
                        <a href="/campusconnect/events.php?category=Technology" class="cc-badge cc-badge-neutral text-decoration-none">Technology</a>
                        <a href="/campusconnect/events.php?category=Workshop" class="cc-badge cc-badge-neutral text-decoration-none">Workshops</a>
                        <a href="/campusconnect/events.php?category=Education" class="cc-badge cc-badge-neutral text-decoration-none">BCA & Projects</a>
                        <a href="/campusconnect/events.php?category=Cultural" class="cc-badge cc-badge-neutral text-decoration-none">Cultural</a>
                        <a href="/campusconnect/events.php" class="small text-primary fw-bold ms-2">All events &rarr;</a>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="cc-card p-4 p-md-5" style="border: 1px solid var(--cc-primary-border); background: #ffffff; box-shadow: var(--cc-shadow-lg);">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="cc-badge cc-badge-category">Academic Project Demo</span>
                            <span class="small text-muted fw-semibold">MAKAUT Affiliated</span>
                        </div>

                        <h2 class="h4 fw-bold mb-2" style="color: var(--cc-text-main);">
                            Supreme Knowledge Foundation Group of Institutions
                        </h2>

                        <div class="text-muted small mb-3">
                            1 Khan Road, Mankundu, Chandannagar, Hooghly, West Bengal
                        </div>

                        <p class="text-secondary small mb-4" style="line-height: 1.7;">
                            CampusConnect is developed as a student project to demonstrate practical Core PHP, MySQL, PDO transactions, seat reservation locking, and door attendance tracking for college activities.
                        </p>

                        <div class="p-3 rounded-3 mb-4" style="background: var(--cc-primary-subtle); border: 1px solid var(--cc-primary-border);">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark small">Upcoming Events in Database</span>
                                <span class="fw-bold text-primary"><?= $totalUpcomingCount ?> published</span>
                            </div>
                            <div class="small text-muted" style="line-height: 1.5;">
                                Real-time student registrations tracked in MySQL.
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="/campusconnect/events.php" class="btn cc-btn-primary">
                                Explore SKFGI Events &rarr;
                            </a>
                            <?php if (!$isLoggedIn): ?>
                                <a href="/campusconnect/login.php" class="btn cc-btn-outline">
                                    Sign In / Try Demo Accounts
                                </a>
                            <?php else: ?>
                                <a href="/campusconnect/dashboard.php" class="btn cc-btn-outline">
                                    Open Student Dashboard
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Upcoming Events Section (Eventbrite-style Date Badges & Real Metrics) -->
    <section class="container py-5 my-2">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <span class="cc-eyebrow">SCHEDULED SESSIONS</span>
                <h2 class="h2 fw-bold mb-1">Upcoming Campus Events</h2>
                <p class="text-secondary mb-0">Browse workshops, student exhibitions, and seminars scheduled across SKFGI departments.</p>
            </div>

            <a href="/campusconnect/events.php" class="btn cc-btn-outline">
                <span>View all <?= $totalUpcomingCount ?> events</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>

        <?php if (empty($events)): ?>
            <div class="cc-empty-state">
                <div class="cc-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <h3 class="h4 fw-bold">No upcoming events published right now</h3>
                <p class="text-secondary mx-auto" style="max-width: 440px;">
                    Department coordinators will publish new workshops and events soon. Check back shortly or browse past activities.
                </p>
                <a href="/campusconnect/events.php" class="btn cc-btn-outline mt-2">
                    Refresh Event Directory
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($events as $event): ?>
                    <?php
                    $eventTimestamp = strtotime($event['event_date']);
                    $deadlineTimestamp = strtotime($event['registration_deadline']);
                    $capacity = (int) $event['capacity'];
                    $registered = (int) $event['registered_count'];
                    $remaining = max(0, $capacity - $registered);
                    $percentFilled = $capacity > 0 ? min(100, round(($registered / $capacity) * 100)) : 100;
                    $isDeadlinePassed = ($deadlineTimestamp !== false && time() >= $deadlineTimestamp);

                    // Real Status Badge logic
                    if ($isDeadlinePassed) {
                        $statusBadge = 'cc-badge-danger';
                        $statusText = 'Registration closed';
                    } elseif ($remaining === 0) {
                        $statusBadge = 'cc-badge-danger';
                        $statusText = 'Full capacity';
                    } elseif ($remaining <= 5) {
                        $statusBadge = 'cc-badge-warning';
                        $statusText = 'Only ' . $remaining . ' left';
                    } else {
                        $statusBadge = 'cc-badge-success';
                        $statusText = $remaining . ' seats open';
                    }
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <article class="cc-card cc-card-interactive h-100 d-flex flex-column p-4">

                            <!-- Eventbrite-style Header: Date Badge Block + Category & Status -->
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div class="cc-date-badge" aria-label="Event date">
                                    <span class="cc-date-badge-month"><?= $eventTimestamp !== false ? date('M', $eventTimestamp) : 'TBC' ?></span>
                                    <span class="cc-date-badge-day"><?= $eventTimestamp !== false ? date('d', $eventTimestamp) : '--' ?></span>
                                </div>

                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-1">
                                        <span class="cc-badge cc-badge-category">
                                            <?= homeEscape($event['category']) ?>
                                        </span>
                                        <span class="cc-badge <?= $statusBadge ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </div>
                                    <div class="small text-muted">
                                        <?= $eventTimestamp !== false ? date('l &bull; g:i A', $eventTimestamp) : 'Schedule pending' ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Title -->
                            <h3 class="h5 fw-bold mb-2" style="line-height: 1.35;">
                                <a href="/campusconnect/event-details.php?id=<?= (int) $event['id'] ?>" class="text-reset">
                                    <?= homeEscape($event['title']) ?>
                                </a>
                            </h3>

                            <!-- Short Description -->
                            <p class="text-secondary small mb-4 flex-grow-1" style="line-height: 1.6;">
                                <?= homeEscape(mb_strimwidth($event['description'], 0, 120, '...')) ?>
                            </p>

                            <!-- Metadata & Capacity Meter -->
                            <div class="pt-3 border-top mt-auto" style="border-color: var(--cc-border) !important;">
                                <div class="d-flex align-items-center gap-2 text-secondary small mb-3">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    <span class="text-truncate"><?= homeEscape($event['venue']) ?></span>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Seat reservation</span>
                                        <span class="fw-semibold text-dark"><?= $registered ?> / <?= $capacity ?></span>
                                    </div>
                                    <div class="cc-progress-track">
                                        <div class="cc-progress-fill" style="width: <?= $percentFilled ?>%;"></div>
                                    </div>
                                </div>

                                <a href="/campusconnect/event-details.php?id=<?= (int) $event['id'] ?>" class="btn cc-btn-outline w-100">
                                    View Event Details &rarr;
                                </a>
                            </div>

                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Event Categories Discovery Section -->
    <section id="categories" class="py-5" style="background-color: var(--cc-surface); border-top: 1px solid var(--cc-border); border-bottom: 1px solid var(--cc-border);">
        <div class="container py-3">
            <div class="text-center max-w-600 mx-auto mb-5">
                <span class="cc-eyebrow">CURATED PATHWAYS</span>
                <h2 class="h2 fw-bold">Explore By Academic Interest</h2>
                <p class="text-secondary mb-0">Browse college activities designed to build technical skills, career readiness, and creative participation.</p>
            </div>

            <div class="row g-4">
                <?php
                $categoriesList = [
                    [
                        'name' => 'Technology',
                        'desc' => 'Coding challenges, problem-solving workshops, hardware prototypes, and robotics demos.',
                        'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>'
                    ],
                    [
                        'name' => 'Workshop',
                        'desc' => 'Hands-on practical development sessions, resume clinics, and startup idea discussions.',
                        'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>'
                    ],
                    [
                        'name' => 'Education',
                        'desc' => 'BCA and engineering project showcases, capstone exhibitions, and technical seminars.',
                        'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>'
                    ],
                    [
                        'name' => 'Cultural',
                        'desc' => 'Musical performances, drama, acoustic recitals, and campus creative activities.',
                        'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"></path><circle cx="6" cy="18" r="3"></circle><circle cx="18" cy="16" r="3"></circle></svg>'
                    ],
                    [
                        'name' => 'Sports',
                        'desc' => 'Inter-departmental leagues, indoor games, athletics meets, and friendly tournaments.',
                        'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path><path d="M2 12h20"></path></svg>'
                    ],
                    [
                        'name' => 'Other',
                        'desc' => 'Inter-departmental quiz contests, club orientations, and informal campus gatherings.',
                        'icon' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>'
                    ],
                ];
                ?>

                <?php foreach ($categoriesList as $cat): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="cc-card p-4 h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="p-2 rounded-3 text-primary" style="background: var(--cc-primary-subtle); display: inline-grid; place-items: center;">
                                        <?= $cat['icon'] ?>
                                    </div>
                                    <h3 class="h5 fw-bold mb-0"><?= homeEscape($cat['name']) ?></h3>
                                </div>
                                <p class="text-secondary small mb-3" style="line-height: 1.6;">
                                    <?= homeEscape($cat['desc']) ?>
                                </p>
                            </div>
                            <div>
                                <a href="/campusconnect/events.php?category=<?= urlencode($cat['name']) ?>" class="small fw-bold text-primary">
                                    Browse <?= homeEscape($cat['name']) ?> events &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- How CampusConnect Works -->
    <section id="how-it-works" class="container py-5 my-3">
        <div class="text-center max-w-600 mx-auto mb-5">
            <span class="cc-eyebrow">SIMPLE WORKFLOW</span>
            <h2 class="h2 fw-bold">How CampusConnect Works</h2>
            <p class="text-secondary mb-0">From discovering open sessions to door check-in at SKFGI.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="cc-card p-4 h-100">
                    <span class="cc-badge cc-badge-category mb-3" style="font-size: 0.85rem; width: 32px; height: 32px; justify-content: center; padding: 0;">1</span>
                    <h3 class="h5 fw-bold mb-2">Explore Freely</h3>
                    <p class="text-secondary small mb-0" style="line-height: 1.7;">
                        Public-first discovery lets anyone browse campus events, check venue details, schedules, and remaining seat counts without needing an account.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="cc-card p-4 h-100">
                    <span class="cc-badge cc-badge-category mb-3" style="font-size: 0.85rem; width: 32px; height: 32px; justify-content: center; padding: 0;">2</span>
                    <h3 class="h5 fw-bold mb-2">Reserve Your Seat</h3>
                    <p class="text-secondary small mb-0" style="line-height: 1.7;">
                        Sign in with your student credentials to confirm your registration. Database locks guarantee capacity limits are respected without overbooking.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="cc-card p-4 h-100">
                    <span class="cc-badge cc-badge-category mb-3" style="font-size: 0.85rem; width: 32px; height: 32px; justify-content: center; padding: 0;">3</span>
                    <h3 class="h5 fw-bold mb-2">Attend & Check In</h3>
                    <p class="text-secondary small mb-0" style="line-height: 1.7;">
                        Present your student ID at the venue door for check-in. If your schedule changes, cancel your seat with one click to release it for other students.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Student Benefits Section -->
    <section class="py-5" style="background-color: var(--cc-surface-muted);">
        <div class="container py-3">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="cc-eyebrow">BUILT FOR SKFGI STUDENTS</span>
                    <h2 class="h2 fw-bold mb-3">Take the guesswork out of campus participation.</h2>
                    <p class="text-secondary mb-4" style="line-height: 1.7;">
                        University life moves fast. Notices pinned on departmental bulletin boards or buried in group chats get missed. CampusConnect centralizes activities in a reliable, searchable hub.
                    </p>

                    <div class="d-grid gap-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 rounded-2 text-primary" style="background: var(--cc-primary-subtle);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            </div>
                            <div>
                                <h4 class="h6 fw-bold mb-1">Accurate Capacity Tracking</h4>
                                <p class="small text-secondary mb-0">Never show up to a full seminar hall. Real-time database limits guarantee seats for registered attendees.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 rounded-2 text-primary" style="background: var(--cc-primary-subtle);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            </div>
                            <div>
                                <h4 class="h6 fw-bold mb-1">Flexible Schedule Management</h4>
                                <p class="small text-secondary mb-0">Need to cancel due to lab exams? Cancellation releases your seat back to other students automatically.</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 rounded-2 text-primary" style="background: var(--cc-primary-subtle);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            </div>
                            <div>
                                <h4 class="h6 fw-bold mb-1">Official Administration Record</h4>
                                <p class="small text-secondary mb-0">Facilitates event check-in and provides verified attendee lists for certificates and department reports.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="cc-card p-4 p-md-5" style="background: #ffffff;">
                        <h3 class="h4 fw-bold mb-4">Quick Student Checklist</h3>

                        <div class="p-3 mb-3 rounded-3" style="border: 1px solid var(--cc-border);">
                            <span class="badge text-bg-light mb-1">Step 1</span>
                            <div class="fw-bold text-dark">Register with your college email</div>
                            <div class="small text-muted">Create a secure student account in under 30 seconds.</div>
                        </div>

                        <div class="p-3 mb-3 rounded-3" style="border: 1px solid var(--cc-border);">
                            <span class="badge text-bg-light mb-1">Step 2</span>
                            <div class="fw-bold text-dark">Reserve an event slot</div>
                            <div class="small text-muted">Find workshops matching your academic or club interests.</div>
                        </div>

                        <div class="p-3 mb-4 rounded-3" style="border: 1px solid var(--cc-border);">
                            <span class="badge text-bg-light mb-1">Step 3</span>
                            <div class="fw-bold text-dark">View in My Registrations</div>
                            <div class="small text-muted">Everything organized with dates, locations, and status.</div>
                        </div>

                        <?php if (!$isLoggedIn): ?>
                            <a href="/campusconnect/register.php" class="btn cc-btn-primary w-100">
                                Get Started Free
                            </a>
                        <?php else: ?>
                            <a href="/campusconnect/my-registrations.php" class="btn cc-btn-primary w-100">
                                View Your Registrations
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Compact FAQ Section -->
    <section id="faq" class="container py-5 my-3">
        <div class="text-center max-w-600 mx-auto mb-5">
            <span class="cc-eyebrow">COMMON QUESTIONS</span>
            <h2 class="h2 fw-bold">Frequently Asked Questions</h2>
            <p class="text-secondary mb-0">Everything you need to know about registering and participating.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-8">
                <div class="accordion cc-accordion" id="faqAccordion">

                    <div class="accordion-item cc-card mb-3" style="overflow: hidden;">
                        <h3 class="accordion-header" id="faq1Heading">
                            <button class="accordion-button fw-bold py-3 px-4" type="button" data-bs-toggle="collapse" data-bs-target="#faq1Collapse" aria-expanded="true" aria-controls="faq1Collapse" style="background: transparent; color: var(--cc-text-main); box-shadow: none;">
                                Who can register for events on CampusConnect?
                            </button>
                        </h3>
                        <div id="faq1Collapse" class="accordion-collapse collapse show" aria-labelledby="faq1Heading" data-bs-parent="#faqAccordion">
                            <div class="accordion-body px-4 pb-4 pt-1 text-secondary" style="line-height: 1.7;">
                                Any enrolled student with a registered CampusConnect account can register for open college events. Registration is free and managed directly by college event organizers.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item cc-card mb-3" style="overflow: hidden;">
                        <h3 class="accordion-header" id="faq2Heading">
                            <button class="accordion-button collapsed fw-bold py-3 px-4" type="button" data-bs-toggle="collapse" data-bs-target="#faq2Collapse" aria-expanded="false" aria-controls="faq2Collapse" style="background: transparent; color: var(--cc-text-main); box-shadow: none;">
                                Can I cancel my registration if I cannot attend?
                            </button>
                        </h3>
                        <div id="faq2Collapse" class="accordion-collapse collapse" aria-labelledby="faq2Heading" data-bs-parent="#faqAccordion">
                            <div class="accordion-body px-4 pb-4 pt-1 text-secondary" style="line-height: 1.7;">
                                Yes. You can cancel your registration from the <strong>My Registrations</strong> page as long as the event has not already taken place. When you cancel, the seat is immediately freed up for other students.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item cc-card mb-3" style="overflow: hidden;">
                        <h3 class="accordion-header" id="faq3Heading">
                            <button class="accordion-button collapsed fw-bold py-3 px-4" type="button" data-bs-toggle="collapse" data-bs-target="#faq3Collapse" aria-expanded="false" aria-controls="faq3Collapse" style="background: transparent; color: var(--cc-text-main); box-shadow: none;">
                                What happens when an event reaches maximum capacity?
                            </button>
                        </h3>
                        <div id="faq3Collapse" class="accordion-collapse collapse" aria-labelledby="faq3Heading" data-bs-parent="#faqAccordion">
                            <div class="accordion-body px-4 pb-4 pt-1 text-secondary" style="line-height: 1.7;">
                                When capacity is reached, registration automatically closes to prevent overbooking venue space. If another registered student cancels, the slot automatically becomes available again.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item cc-card" style="overflow: hidden;">
                        <h3 class="accordion-header" id="faq4Heading">
                            <button class="accordion-button collapsed fw-bold py-3 px-4" type="button" data-bs-toggle="collapse" data-bs-target="#faq4Collapse" aria-expanded="false" aria-controls="faq4Collapse" style="background: transparent; color: var(--cc-text-main); box-shadow: none;">
                                Is CampusConnect an official SKFGI announcement portal?
                            </button>
                        </h3>
                        <div id="faq4Collapse" class="accordion-collapse collapse" aria-labelledby="faq4Heading" data-bs-parent="#faqAccordion">
                            <div class="accordion-body px-4 pb-4 pt-1 text-secondary" style="line-height: 1.7;">
                                CampusConnect is developed as an academic web development demonstration project by Ankit Pal. It showcases full-stack web engineering, database design, and real-time registration handling for Supreme Knowledge Foundation Group of Institutions (SKFGI), Mankundu, Hooghly.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

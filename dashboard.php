<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Student Dashboard';
$userId = (int) $_SESSION['user']['id'];

// Fetch latest account data from database
$userStmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = :id LIMIT 1');
$userStmt->execute(['id' => $userId]);
$user = $userStmt->fetch();

if (!$user || $user['role'] !== 'student') {
    unset($_SESSION['user']);
    header('Location: /campusconnect/login.php');
    exit;
}

// 1. Total Active registrations
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM registrations r
    INNER JOIN events e ON e.id = r.event_id
    WHERE r.user_id = :user_id
      AND r.status = 'registered'
");
$countStmt->execute(['user_id' => $userId]);
$totalActiveRegistrations = (int) $countStmt->fetchColumn();

// 2. Upcoming events count (future date & active registration)
$upcomingCountStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM registrations r
    INNER JOIN events e ON e.id = r.event_id
    WHERE r.user_id = :user_id
      AND r.status = 'registered'
      AND e.status = 'published'
      AND e.event_date >= NOW()
");
$upcomingCountStmt->execute(['user_id' => $userId]);
$totalUpcomingEvents = (int) $upcomingCountStmt->fetchColumn();

// 3. Past or cancelled registrations count
$pastCountStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM registrations r
    INNER JOIN events e ON e.id = r.event_id
    WHERE r.user_id = :user_id
      AND (r.status = 'cancelled' OR e.event_date < NOW())
");
$pastCountStmt->execute(['user_id' => $userId]);
$totalPastOrCancelled = (int) $pastCountStmt->fetchColumn();

// 4. Fetch the student's upcoming registered events list
$eventsStmt = $pdo->prepare("
    SELECT
        e.id,
        e.title,
        e.category,
        e.venue,
        e.event_date,
        e.capacity,
        r.id AS registration_id,
        r.registered_at,
        r.status AS registration_status
    FROM registrations r
    INNER JOIN events e ON e.id = r.event_id
    WHERE r.user_id = :user_id
      AND r.status = 'registered'
      AND e.status = 'published'
      AND e.event_date >= NOW()
    ORDER BY e.event_date ASC
    LIMIT 6
");
$eventsStmt->execute(['user_id' => $userId]);
$upcomingRegisteredEvents = $eventsStmt->fetchAll();

function dashboardEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="container py-5">

    <!-- Welcome Banner -->
    <section class="cc-card p-4 p-md-5 mb-5" style="background: linear-gradient(135deg, #f6f1ff 0%, #ffffff 85%); border-color: var(--cc-primary-border);">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="cc-eyebrow">STUDENT PORTAL</span>
                <h1 class="display-6 fw-bold mt-1 mb-2" style="font-family: 'Outfit', sans-serif;">
                    Welcome back, <?= dashboardEscape($user['name']) ?>!
                </h1>
                <p class="text-secondary mb-4" style="max-width: 580px; font-size: 1.05rem;">
                    Track your reserved seats, view event schedules, and discover upcoming college workshops and cultural activities.
                </p>

                <div class="d-flex flex-wrap gap-2">
                    <a href="/campusconnect/events.php" class="btn cc-btn-primary">
                        <span>Discover New Events</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </a>
                    <a href="/campusconnect/my-registrations.php" class="btn cc-btn-outline">
                        Manage All Registrations
                    </a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="p-3 rounded-3 bg-white border" style="border-color: var(--cc-border) !important;">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="cc-brand-mark" style="width: 38px; height: 38px; font-size: 1rem;">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold text-dark"><?= dashboardEscape($user['name']) ?></div>
                            <div class="small text-muted"><?= dashboardEscape($user['email']) ?></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 mt-2 border-top small" style="border-color: var(--cc-border) !important;">
                        <span class="text-muted">Account Role:</span>
                        <span class="cc-badge cc-badge-category">Verified Student</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Real Database Statistics Cards -->
    <section class="row g-4 mb-5" aria-label="Student overview statistics">
        <div class="col-sm-6 col-lg-4">
            <article class="cc-stat-card h-100">
                <span class="cc-stat-label">Upcoming Events</span>
                <p class="cc-stat-value text-primary"><?= $totalUpcomingEvents ?></p>
                <p class="cc-stat-desc">Published events scheduled ahead</p>
            </article>
        </div>

        <div class="col-sm-6 col-lg-4">
            <article class="cc-stat-card h-100">
                <span class="cc-stat-label">Active Registrations</span>
                <p class="cc-stat-value"><?= $totalActiveRegistrations ?></p>
                <p class="cc-stat-desc">Confirmed seats currently held</p>
            </article>
        </div>

        <div class="col-sm-6 col-lg-4">
            <article class="cc-stat-card h-100">
                <span class="cc-stat-label">Past & Completed</span>
                <p class="cc-stat-value text-muted"><?= $totalPastOrCancelled ?></p>
                <p class="cc-stat-desc">Historical records in system</p>
            </article>
        </div>
    </section>

    <!-- Upcoming Registered Events Section -->
    <section aria-labelledby="upcoming-events-heading">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h2 id="upcoming-events-heading" class="h4 fw-bold mb-1">
                    Your Upcoming Schedule
                </h2>
                <p class="text-secondary small mb-0">
                    Confirmed seats for events coming up on campus.
                </p>
            </div>

            <a href="/campusconnect/my-registrations.php" class="btn cc-btn-outline cc-btn-sm">
                View Full Registration History &rarr;
            </a>
        </div>

        <?php if (empty($upcomingRegisteredEvents)): ?>
            <div class="cc-empty-state">
                <div class="cc-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <h3 class="h5 fw-bold">You haven't registered for any upcoming events yet</h3>
                <p class="text-secondary mx-auto" style="max-width: 420px; font-size: 0.95rem;">
                    Discover departmental seminars, tech hackathons, and student activities currently open for registration.
                </p>
                <a href="/campusconnect/events.php" class="btn cc-btn-primary mt-2">
                    Explore Campus Events
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($upcomingRegisteredEvents as $regEvent): ?>
                    <?php
                    $eventTimestamp = strtotime($regEvent['event_date']);
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <article class="cc-card cc-card-interactive h-100 d-flex flex-column p-4">

                            <!-- Eventbrite-style Date Badge + Status Header -->
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <div class="cc-date-badge" aria-label="Event date">
                                    <span class="cc-date-badge-month"><?= $eventTimestamp !== false ? date('M', $eventTimestamp) : 'TBC' ?></span>
                                    <span class="cc-date-badge-day"><?= $eventTimestamp !== false ? date('d', $eventTimestamp) : '--' ?></span>
                                </div>

                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-1">
                                        <span class="cc-badge cc-badge-category">
                                            <?= dashboardEscape($regEvent['category']) ?>
                                        </span>
                                        <span class="cc-badge cc-badge-success">
                                            Confirmed
                                        </span>
                                    </div>
                                    <div class="small text-muted">
                                        <?= $eventTimestamp !== false ? date('l &bull; g:i A', $eventTimestamp) : 'Schedule pending' ?>
                                    </div>
                                </div>
                            </div>

                            <h3 class="h5 fw-bold mb-2">
                                <a href="/campusconnect/event-details.php?id=<?= (int) $regEvent['id'] ?>" class="text-reset">
                                    <?= dashboardEscape($regEvent['title']) ?>
                                </a>
                            </h3>

                            <div class="d-flex align-items-center gap-2 text-secondary small mb-3">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <span class="text-truncate"><?= dashboardEscape($regEvent['venue']) ?></span>
                            </div>

                            <div class="d-flex gap-2 mt-auto pt-3 border-top" style="border-color: var(--cc-border) !important;">
                                <a href="/campusconnect/event-details.php?id=<?= (int) $regEvent['id'] ?>" class="btn cc-btn-outline cc-btn-sm flex-grow-1">
                                    View Details
                                </a>
                                <form action="/campusconnect/cancel-registration.php" method="post" class="m-0" onsubmit="return confirm('Cancel this event registration? Your slot will be released.');">
                                    <input type="hidden" name="registration_id" value="<?= (int) $regEvent['registration_id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-outline-danger cc-btn-sm" title="Cancel your seat registration">
                                        Cancel
                                    </button>
                                </form>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

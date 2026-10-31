<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';

$pageTitle = 'My Registrations';
$userId = (int) $_SESSION['user']['id'];

// Fetch all registrations with event details
$stmt = $pdo->prepare("
    SELECT
        r.id AS registration_id,
        r.status AS registration_status,
        r.registered_at,
        e.id AS event_id,
        e.title,
        e.category,
        e.venue,
        e.event_date,
        e.status AS event_status
    FROM registrations r
    INNER JOIN events e ON e.id = r.event_id
    WHERE r.user_id = :user_id
    ORDER BY e.event_date DESC
");

$stmt->execute(['user_id' => $userId]);
$allRegistrations = $stmt->fetchAll();

// Separate into Upcoming Active vs Past / Cancelled
$upcomingRegistrations = [];
$pastOrCancelled = [];

foreach ($allRegistrations as $item) {
    $eventTime = strtotime($item['event_date']);
    $isFuture = ($eventTime !== false && $eventTime >= time());
    $isActive = ($item['registration_status'] === 'registered' && $item['event_status'] === 'published');

    if ($isFuture && $isActive) {
        $upcomingRegistrations[] = $item;
    } else {
        $pastOrCancelled[] = $item;
    }
}

function myRegistrationEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="container py-5">

    <!-- Flash message from session -->
    <?php if (!empty($_SESSION['registration_message'])): ?>
        <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="status">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
            <div><?= myRegistrationEscape($_SESSION['registration_message']) ?></div>
        </div>
        <?php unset($_SESSION['registration_message']); ?>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="status">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <div><strong>Registration confirmed!</strong> Your seat has been reserved successfully in the database.</div>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-5">
        <div>
            <span class="cc-eyebrow">STUDENT PORTAL</span>
            <h1 class="display-6 fw-bold mt-1 mb-1" style="font-family: 'Outfit', sans-serif;">My Event Registrations</h1>
            <p class="text-secondary mb-0">
                Track your active campus registrations and review past participation history.
            </p>
        </div>

        <a href="/campusconnect/events.php" class="btn cc-btn-primary">
            <span>Explore More Events</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </a>
    </div>

    <?php if (empty($allRegistrations)): ?>
        <section class="cc-empty-state my-4">
            <div class="cc-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <h2 class="h4 fw-bold">No Registrations Found</h2>
            <p class="text-secondary mx-auto" style="max-width: 440px;">
                You haven't reserved a seat for any campus events yet. Explore upcoming seminars, hackathons, and workshops now.
            </p>
            <div class="mt-3">
                <a href="/campusconnect/events.php" class="btn cc-btn-primary">
                    Browse Upcoming Events
                </a>
            </div>
        </section>
    <?php else: ?>

        <!-- Section 1: Upcoming Active Registrations -->
        <section class="mb-5" aria-labelledby="upcoming-active-heading">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2 id="upcoming-active-heading" class="h4 fw-bold mb-0">
                    Active & Upcoming (<?= count($upcomingRegistrations) ?>)
                </h2>
                <span class="small text-muted">Confirmed seats for scheduled events</span>
            </div>

            <?php if (empty($upcomingRegistrations)): ?>
                <div class="cc-card p-4 text-center text-secondary small">
                    You have no active upcoming registrations right now.
                    <a href="/campusconnect/events.php" class="fw-bold ms-1">Find an event to attend &rarr;</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($upcomingRegistrations as $registration): ?>
                        <?php
                        $eventTimestamp = strtotime($registration['event_date']);
                        $registeredTimestamp = strtotime($registration['registered_at']);
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
                                                <?= myRegistrationEscape($registration['category']) ?>
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
                                    <a href="/campusconnect/event-details.php?id=<?= (int) $registration['event_id'] ?>" class="text-reset">
                                        <?= myRegistrationEscape($registration['title']) ?>
                                    </a>
                                </h3>

                                <div class="d-flex align-items-center gap-2 text-secondary small mb-2">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    <span class="text-truncate"><?= myRegistrationEscape($registration['venue']) ?></span>
                                </div>

                                <div class="small text-muted mb-3">
                                    Registered on <?= $registeredTimestamp !== false ? date('d M Y', $registeredTimestamp) : 'Recently' ?>
                                </div>

                                <div class="d-flex gap-2 mt-auto pt-3 border-top" style="border-color: var(--cc-border) !important;">
                                    <a href="/campusconnect/event-details.php?id=<?= (int) $registration['event_id'] ?>" class="btn cc-btn-outline cc-btn-sm flex-grow-1">
                                        View Event
                                    </a>

                                    <form
                                        action="/campusconnect/cancel-registration.php"
                                        method="post"
                                        class="m-0"
                                        onsubmit="return confirm('Are you sure you want to cancel your seat for <?= myRegistrationEscape(addslashes($registration['title'])) ?>? Your reservation will be released to others.');"
                                    >
                                        <input type="hidden" name="registration_id" value="<?= (int) $registration['registration_id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-outline-danger cc-btn-sm" title="Cancel this registration">
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

        <!-- Section 2: Past & Cancelled Registrations -->
        <?php if (!empty($pastOrCancelled)): ?>
            <section aria-labelledby="history-heading">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 id="history-heading" class="h5 fw-bold mb-0">
                        Past & Cancelled Activity (<?= count($pastOrCancelled) ?>)
                    </h2>
                </div>

                <div class="cc-table-container">
                    <div class="table-responsive">
                        <table class="table cc-table">
                            <thead>
                                <tr>
                                    <th scope="col">Event Title</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Event Date</th>
                                    <th scope="col">Venue</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pastOrCancelled as $past): ?>
                                    <?php
                                    $pTimestamp = strtotime($past['event_date']);
                                    if ($past['registration_status'] === 'cancelled') {
                                        $pBadge = 'cc-badge-danger';
                                        $pStatus = 'Registration Cancelled';
                                    } elseif ($past['event_status'] === 'cancelled') {
                                        $pBadge = 'cc-badge-danger';
                                        $pStatus = 'Event Cancelled by Admin';
                                    } else {
                                        $pBadge = 'cc-badge-neutral';
                                        $pStatus = 'Event Concluded';
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong class="text-dark d-block">
                                                <?= myRegistrationEscape($past['title']) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <span class="cc-badge cc-badge-category">
                                                <?= myRegistrationEscape($past['category']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="small">
                                                <?= $pTimestamp !== false ? date('d M Y, g:i A', $pTimestamp) : 'TBC' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="small text-secondary">
                                                <?= myRegistrationEscape($past['venue']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="cc-badge <?= $pBadge ?>">
                                                <?= $pStatus ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="/campusconnect/event-details.php?id=<?= (int) $past['event_id'] ?>" class="btn cc-btn-outline cc-btn-sm">
                                                Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        <?php endif; ?>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
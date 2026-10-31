<?php
$pageTitle = 'Event Details';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

function detailEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Validate event ID
$eventId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$event = null;
$userRegistration = null;

$isLoggedIn = isLoggedIn();
$currentUserRole = $_SESSION['user']['role'] ?? '';
$currentUserId = (int) ($_SESSION['user']['id'] ?? 0);

if ($eventId && $eventId > 0) {
    // If admin is logged in, allow viewing any status; otherwise only published events
    if ($isLoggedIn && $currentUserRole === 'admin') {
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
                e.status,
                e.created_at,
                (
                    SELECT COUNT(*)
                    FROM registrations r
                    WHERE r.event_id = e.id
                      AND r.status = 'registered'
                ) AS registered_count
            FROM events e
            WHERE e.id = :id
            LIMIT 1
        ";
    } else {
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
                e.status,
                e.created_at,
                (
                    SELECT COUNT(*)
                    FROM registrations r
                    WHERE r.event_id = e.id
                      AND r.status = 'registered'
                ) AS registered_count
            FROM events e
            WHERE e.id = :id
              AND e.status = 'published'
            LIMIT 1
        ";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $eventId]);
    $event = $stmt->fetch();

    // Check if the current student has an existing registration for this event
    if ($event && $isLoggedIn && $currentUserRole === 'student') {
        $regStmt = $pdo->prepare("
            SELECT id, status, registered_at
            FROM registrations
            WHERE user_id = :user_id
              AND event_id = :event_id
            LIMIT 1
        ");
        $regStmt->execute([
            'user_id' => $currentUserId,
            'event_id' => $eventId
        ]);
        $userRegistration = $regStmt->fetch();
    }
}

if ($event) {
    $pageTitle = $event['title'];
} else {
    http_response_code(404);
}

// Check for flash registration error in session
$registrationError = $_SESSION['registration_error'] ?? null;
unset($_SESSION['registration_error']);

require_once __DIR__ . '/includes/header.php';
?>

<main class="container py-5">

    <?php if (!$event): ?>
        <section class="cc-empty-state my-5">
            <div class="cc-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <h1 class="h4 fw-bold">Event Not Found</h1>
            <p class="text-secondary small mx-auto" style="max-width: 440px;">
                The event you requested might have been cancelled, unpublished, or the link may be outdated.
            </p>
            <div class="mt-3">
                <a href="/campusconnect/events.php" class="btn cc-btn-primary btn-sm">
                    Back to Event Directory
                </a>
            </div>
        </section>
    <?php else: ?>

        <?php
        $eventTimestamp = strtotime($event['event_date']);
        $deadlineTimestamp = strtotime($event['registration_deadline']);
        $capacity = (int) $event['capacity'];
        $registered = (int) $event['registered_count'];
        $remaining = max(0, $capacity - $registered);
        $percentFilled = $capacity > 0 ? min(100, round(($registered / $capacity) * 100)) : 100;

        $isPastEvent = ($eventTimestamp !== false && time() >= $eventTimestamp);
        $isDeadlinePassed = ($deadlineTimestamp !== false && time() >= $deadlineTimestamp);
        $isFull = ($remaining <= 0);
        $isCancelled = ($event['status'] === 'cancelled');
        $isDraft = ($event['status'] === 'draft');

        $canRegister = (!$isPastEvent && !$isDeadlinePassed && !$isFull && !$isCancelled && !$isDraft);
        $isAlreadyRegistered = ($userRegistration && $userRegistration['status'] === 'registered');
        ?>

        <!-- Breadcrumb Navigation -->
        <nav aria-label="Breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="/campusconnect/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="/campusconnect/events.php">Events</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= detailEscape($event['title']) ?></li>
            </ol>
        </nav>

        <!-- Flash Registration Error -->
        <?php if ($registrationError): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <div>
                    <strong>Registration notice:</strong> <?= detailEscape($registrationError) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Admin Notice / Quick Management Bar (Shown only to administrators) -->
        <?php if ($isLoggedIn && $currentUserRole === 'admin'): ?>
            <div class="cc-card p-3 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3 bg-white" style="border-color: var(--cc-primary-border);">
                <div class="d-flex align-items-center gap-2">
                    <span class="cc-badge cc-badge-warning">Administrator Preview</span>
                    <span class="small text-muted">Status: <strong><?= ucfirst($event['status']) ?></strong>, Total Registered: <strong><?= $registered ?></strong></span>
                </div>
                <div class="d-flex gap-2">
                    <a href="/campusconnect/admin/edit-event.php?id=<?= (int) $event['id'] ?>" class="btn cc-btn-primary cc-btn-sm">
                        Edit Event
                    </a>
                    <a href="/campusconnect/admin/attendees.php?event_id=<?= (int) $event['id'] ?>" class="btn cc-btn-outline cc-btn-sm">
                        Attendees (<?= $registered ?>)
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Event Overview Banner -->
        <section class="cc-card p-4 p-md-4 mb-4 bg-white">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <span class="cc-badge cc-badge-category">
                    <?= detailEscape($event['category']) ?>
                </span>

                <?php if ($isCancelled): ?>
                    <span class="cc-badge cc-badge-danger">Event Cancelled</span>
                <?php elseif ($isPastEvent): ?>
                    <span class="cc-badge cc-badge-neutral">Completed Event</span>
                <?php elseif ($isAlreadyRegistered): ?>
                    <span class="cc-badge cc-badge-success">Registered</span>
                <?php elseif ($isFull): ?>
                    <span class="cc-badge cc-badge-danger">Full Capacity</span>
                <?php elseif ($isDeadlinePassed): ?>
                    <span class="cc-badge cc-badge-warning">Registration Closed</span>
                <?php else: ?>
                    <span class="cc-badge cc-badge-success"><?= $remaining ?> seats open</span>
                <?php endif; ?>
            </div>

            <h1 class="h2 fw-bold mb-3" style="color: var(--cc-text-main);">
                <?= detailEscape($event['title']) ?>
            </h1>

            <div class="d-flex flex-wrap gap-4 text-secondary small">
                <div class="d-flex align-items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    <span><?= $eventTimestamp !== false ? date('l, F j, Y, g:i A', $eventTimestamp) : 'Date to be confirmed' ?></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    <span><?= detailEscape($event['venue']) ?></span>
                </div>
            </div>
        </section>

        <!-- Main Content & Registration Aside -->
        <div class="row g-4">

            <!-- Left Details Column -->
            <div class="col-lg-8">
                <article class="cc-card p-4 p-md-4 mb-4">
                    <h2 class="h5 fw-bold mb-3">About This Event</h2>
                    <div class="text-secondary" style="line-height: 1.75; font-size: 0.95rem;">
                        <?= nl2br(detailEscape($event['description'])) ?>
                    </div>

                    <div class="pt-4 mt-4 border-top" style="border-color: var(--cc-border) !important;">
                        <h3 class="h6 fw-bold mb-2">Attendee Information</h3>
                        <ul class="text-secondary small d-grid gap-2 mb-0 ps-3" style="line-height: 1.6;">
                            <li>Please arrive at <?= detailEscape($event['venue']) ?> at least 10 minutes before the scheduled start time.</li>
                            <li>Carry your student ID card for verification during check-in.</li>
                            <li>If you are unable to attend, please cancel your registration from your dashboard to free the seat for another student.</li>
                        </ul>
                    </div>
                </article>
            </div>

            <!-- Right Registration Card -->
            <div class="col-lg-4">
                <aside class="cc-card p-4 sticky-top" style="top: 85px;">
                    <h2 class="h6 fw-bold mb-3">Registration & Capacity</h2>

                    <!-- Live Capacity Meter -->
                    <div class="p-3 rounded-2 mb-3" style="background: var(--cc-surface-muted); border: 1px solid var(--cc-border);">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted fw-semibold">Seats Available</span>
                            <span class="fw-bold text-dark"><?= $remaining ?> of <?= $capacity ?></span>
                        </div>
                        <div class="cc-progress-track my-2">
                            <div class="cc-progress-fill" style="width: <?= $percentFilled ?>%;"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span><?= $registered ?> registered</span>
                            <span><?= $percentFilled ?>% filled</span>
                        </div>
                    </div>

                    <!-- Meta Details -->
                    <div class="d-grid gap-2 mb-4 pb-3 border-bottom" style="border-color: var(--cc-border) !important;">
                        <div>
                            <span class="text-muted small d-block">Schedule</span>
                            <span class="fw-semibold text-dark small">
                                <?= $eventTimestamp !== false ? date('M j, Y, g:i A', $eventTimestamp) : 'To be confirmed' ?>
                            </span>
                        </div>

                        <div>
                            <span class="text-muted small d-block">Venue</span>
                            <span class="fw-semibold text-dark small"><?= detailEscape($event['venue']) ?></span>
                        </div>

                        <div>
                            <span class="text-muted small d-block">Registration Deadline</span>
                            <span class="fw-semibold text-dark small">
                                <?= $deadlineTimestamp !== false ? date('M j, Y, g:i A', $deadlineTimestamp) : 'Not specified' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Action States -->
                    <?php if ($isAlreadyRegistered): ?>
                        <div class="p-3 rounded-2 mb-3" style="background: var(--cc-success-subtle); border: 1px solid var(--cc-success-border);">
                            <div class="d-flex align-items-center gap-2 text-success fw-bold small mb-1">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <span>You are registered</span>
                            </div>
                            <p class="small text-secondary mb-0">
                                Your seat is reserved. You can view your confirmation in My Registrations.
                            </p>
                        </div>

                        <a href="/campusconnect/my-registrations.php" class="btn cc-btn-primary w-100 mb-2 btn-sm">
                            Go to My Registrations
                        </a>

                        <?php if (!$isPastEvent && !$isCancelled): ?>
                            <form action="/campusconnect/cancel-registration.php" method="post" onsubmit="return confirm('Cancel your seat registration for this event?');">
                                <input type="hidden" name="registration_id" value="<?= (int) $userRegistration['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
                                    Cancel Registration
                                </button>
                            </form>
                        <?php endif; ?>

                    <?php elseif ($isLoggedIn && $currentUserRole === 'student'): ?>
                        <?php if ($canRegister): ?>
                            <form action="/campusconnect/register-event.php" method="post">
                                <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn cc-btn-primary w-100 py-2">
                                    Register for Event
                                </button>
                            </form>
                            <p class="small text-muted text-center mt-2 mb-0">
                                Free registration, instant confirmation
                            </p>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100 btn-sm" disabled>
                                <?php
                                if ($isCancelled) echo 'Event Cancelled';
                                elseif ($isPastEvent) echo 'Event Concluded';
                                elseif ($isFull) echo 'Event Full (Capacity Reached)';
                                elseif ($isDeadlinePassed) echo 'Registration Closed';
                                else echo 'Registration Unavailable';
                                ?>
                            </button>
                        <?php endif; ?>

                    <?php elseif (!$isLoggedIn): ?>
                        <div class="p-3 rounded-2 mb-3" style="background: var(--cc-surface-muted); border: 1px solid var(--cc-border);">
                            <div class="fw-semibold text-dark small mb-1">Student Account Required</div>
                            <div class="small text-muted">
                                Sign in with your student credentials to reserve a seat.
                            </div>
                        </div>

                        <a href="/campusconnect/login.php" class="btn cc-btn-primary w-100 mb-2 btn-sm">
                            Sign In to Register
                        </a>
                        <a href="/campusconnect/register.php" class="btn cc-btn-outline w-100 btn-sm">
                            Create Student Account
                        </a>

                    <?php else: ?>
                        <div class="p-3 rounded-2 mb-3" style="background: var(--cc-surface-muted); border: 1px solid var(--cc-border);">
                            <div class="fw-semibold text-dark small">Administrator Mode</div>
                            <div class="small text-muted">
                                Event registration is disabled on admin accounts.
                            </div>
                        </div>
                        <a href="/campusconnect/admin/events.php" class="btn cc-btn-outline w-100 btn-sm">
                            Manage All Events
                        </a>
                    <?php endif; ?>

                </aside>
            </div>

        </div>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

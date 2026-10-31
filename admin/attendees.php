<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

// Verify administrator role against the database
$roleStmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
$roleStmt->execute(['id' => (int) $_SESSION['user']['id']]);

if ($roleStmt->fetchColumn() !== 'admin') {
    http_response_code(403);
    exit('403 Forbidden: Administrator access required.');
}

$pageTitle = 'Event Attendees';

$eventId = filter_var($_GET['event_id'] ?? null, FILTER_VALIDATE_INT);

$eventsStmt = $pdo->query('
    SELECT id, title, event_date, status, capacity
    FROM events
    ORDER BY event_date DESC
');
$events = $eventsStmt->fetchAll();

$attendees = [];
$selectedEvent = null;
$error = '';
$totalRegisteredCount = 0;
$totalCancelledCount = 0;

if ($eventId !== null && $eventId !== false) {
    if ($eventId < 1) {
        $error = 'Invalid event ID provided.';
    } else {
        $selectedStmt = $pdo->prepare('
            SELECT id, title, event_date, status, venue, capacity
            FROM events
            WHERE id = :id
            LIMIT 1
        ');
        $selectedStmt->execute(['id' => $eventId]);
        $selectedEvent = $selectedStmt->fetch();

        if (!$selectedEvent) {
            $error = 'The requested event could not be found.';
        } else {
            $attendeeStmt = $pdo->prepare("
                SELECT
                    u.id AS student_id,
                    u.name,
                    u.email,
                    r.id AS registration_id,
                    r.registered_at,
                    r.status AS registration_status
                FROM registrations r
                INNER JOIN users u ON u.id = r.user_id
                WHERE r.event_id = :event_id
                ORDER BY r.registered_at DESC
            ");
            $attendeeStmt->execute(['event_id' => $eventId]);
            $attendees = $attendeeStmt->fetchAll();

            foreach ($attendees as $att) {
                if ($att['registration_status'] === 'registered') {
                    $totalRegisteredCount++;
                } else {
                    $totalCancelledCount++;
                }
            }
        }
    }
}

function attendeeEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="cc-admin-nav">
    <div class="container">
        <ul class="cc-admin-nav-list">
            <li>
                <a href="/campusconnect/admin/index.php" class="cc-admin-nav-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="/campusconnect/admin/events.php" class="cc-admin-nav-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Manage Events
                </a>
            </li>
            <li>
                <a href="/campusconnect/admin/event-form.php" class="cc-admin-nav-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Create Event
                </a>
            </li>
            <li>
                <a href="/campusconnect/admin/attendees.php" class="cc-admin-nav-link active">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Attendees
                </a>
            </li>
            <li>
                <a href="/campusconnect/admin/reports.php" class="cc-admin-nav-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    Reports
                </a>
            </li>
        </ul>
    </div>
</div>

<main class="container pb-5">

    <div class="mb-4">
        <span class="cc-eyebrow">ATTENDANCE & ROLLS</span>
        <h1 class="display-6 fw-bold mt-1 mb-1" style="font-family: 'Outfit', sans-serif;">Event Attendee Directory</h1>
        <p class="text-secondary mb-0">
            Select an event to view confirmed student registrations and cancellation records.
        </p>
    </div>

    <!-- Event Selector Form -->
    <section class="cc-card p-4 mb-4" aria-label="Select event to inspect attendees">
        <form method="get" action="/campusconnect/admin/attendees.php">
            <div class="row g-3 align-items-end">
                <div class="col-lg-8 col-md-7">
                    <label for="event_id" class="form-label">
                        Select Campus Event
                    </label>
                    <select id="event_id" name="event_id" class="form-select" required>
                        <option value="">-- Choose an event from catalog --</option>
                        <?php foreach ($events as $item): ?>
                            <option
                                value="<?= (int) $item['id'] ?>"
                                <?= $eventId === (int) $item['id'] ? 'selected' : '' ?>
                            >
                                <?= attendeeEscape($item['title']) ?> (<?= date('d M Y', strtotime($item['event_date'])) ?> &bull; <?= ucfirst($item['status']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-4 col-md-5 d-flex gap-2">
                    <button type="submit" class="btn cc-btn-primary flex-grow-1">
                        View Attendees
                    </button>
                    <?php if ($selectedEvent): ?>
                        <a href="/campusconnect/admin/attendees.php" class="btn cc-btn-outline">
                            Clear
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </section>

    <?php if ($error !== ''): ?>
        <div class="alert alert-warning mb-4" role="alert">
            <?= attendeeEscape($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($selectedEvent): ?>
        <!-- Event Overview Banner -->
        <section class="cc-card p-4 mb-4" style="background: var(--cc-surface-muted);">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <span class="cc-badge cc-badge-category mb-2">
                        <?= ucfirst($selectedEvent['status']) ?>
                    </span>
                    <h2 class="h4 fw-bold mb-1"><?= attendeeEscape($selectedEvent['title']) ?></h2>
                    <div class="text-secondary small">
                        <span><strong>Venue:</strong> <?= attendeeEscape($selectedEvent['venue']) ?></span> &bull;
                        <span><strong>Date:</strong> <?= date('d M Y, g:i A', strtotime($selectedEvent['event_date'])) ?></span> &bull;
                        <span><strong>Capacity:</strong> <?= (int)$selectedEvent['capacity'] ?></span>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div class="text-center p-2 px-3 bg-white rounded-3 border" style="border-color: var(--cc-border) !important;">
                        <span class="d-block small text-muted">Confirmed</span>
                        <strong class="h4 text-success mb-0"><?= $totalRegisteredCount ?></strong>
                    </div>
                    <div class="text-center p-2 px-3 bg-white rounded-3 border" style="border-color: var(--cc-border) !important;">
                        <span class="d-block small text-muted">Cancelled</span>
                        <strong class="h4 text-muted mb-0"><?= $totalCancelledCount ?></strong>
                    </div>
                </div>
            </div>
        </section>

        <!-- Attendees Table -->
        <section aria-labelledby="attendees-list-heading">
            <h3 id="attendees-list-heading" class="h5 fw-bold mb-3">
                Registered Students (<?= count($attendees) ?> Total Records)
            </h3>

            <?php if (empty($attendees)): ?>
                <div class="cc-empty-state">
                    <div class="cc-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <h4 class="h5 fw-bold">No registered attendees yet</h4>
                    <p class="text-secondary small mx-auto" style="max-width: 420px;">
                        No students have registered for this event so far. Make sure the event is published so students can discover it.
                    </p>
                </div>
            <?php else: ?>
                <div class="cc-table-container">
                    <div class="table-responsive">
                        <table class="table cc-table">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Student Name</th>
                                    <th scope="col">Email Address</th>
                                    <th scope="col">Registration Date</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $index = 1; foreach ($attendees as $att): ?>
                                    <?php
                                    $isReg = ($att['registration_status'] === 'registered');
                                    $regDate = strtotime($att['registered_at']);
                                    ?>
                                    <tr>
                                        <td class="text-muted small"><?= $index++ ?></td>
                                        <td>
                                            <strong class="text-dark"><?= attendeeEscape($att['name']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="text-secondary small font-monospace">
                                                <?= attendeeEscape($att['email']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="small">
                                                <?= $regDate !== false ? date('d M Y, g:i A', $regDate) : 'Unknown' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="cc-badge <?= $isReg ? 'cc-badge-success' : 'cc-badge-danger' ?>">
                                                <?= $isReg ? 'Confirmed' : 'Cancelled' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </section>

    <?php else: ?>
        <section class="cc-empty-state">
            <div class="cc-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            </div>
            <h3 class="h5 fw-bold">Please Select an Event Above</h3>
            <p class="text-secondary small mx-auto" style="max-width: 420px;">
                Choose any college event from the dropdown selector to view its confirmed attendees and check-in roster.
            </p>
        </section>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

$roleStmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
$roleStmt->execute(['id' => (int) $_SESSION['user']['id']]);

if ($roleStmt->fetchColumn() !== 'admin') {
    http_response_code(403);
    exit('403 Forbidden: Administrator access required.');
}

$pageTitle = 'Edit Event';

function editEventEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$eventId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$eventId || $eventId < 1) {
    http_response_code(400);
    exit('Invalid event ID.');
}

$stmt = $pdo->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $eventId]);
$event = $stmt->fetch();

if (!$event) {
    http_response_code(404);
    exit('Event not found.');
}

// Check active registration count
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM registrations
    WHERE event_id = :id
      AND status = 'registered'
");
$countStmt->execute(['id' => $eventId]);
$activeRegistrations = (int) $countStmt->fetchColumn();

$categories = [
    'Technology',
    'Workshop',
    'Cultural',
    'Sports',
    'Education',
    'Other'
];

$statuses = ['draft', 'published', 'cancelled'];
$errors = [];

$title = $event['title'];
$description = $event['description'];
$category = $event['category'];
$venue = $event['venue'];
$eventDate = date('Y-m-d\TH:i', strtotime($event['event_date']));
$deadline = date('Y-m-d\TH:i', strtotime($event['registration_deadline']));
$capacity = (string) $event['capacity'];
$status = $event['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken(is_string($token) ? $token : null)) {
        $errors[] = 'Invalid security session. Please refresh and try again.';
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $category = (string) ($_POST['category'] ?? '');
    $venue = trim((string) ($_POST['venue'] ?? ''));
    $eventDate = (string) ($_POST['event_date'] ?? '');
    $deadline = (string) ($_POST['registration_deadline'] ?? '');
    $capacity = (string) ($_POST['capacity'] ?? '');
    $status = (string) ($_POST['status'] ?? '');

    $eventTimestamp = strtotime($eventDate);
    $deadlineTimestamp = strtotime($deadline);

    if ($title === '' || mb_strlen($title) > 200) {
        $errors[] = 'Please enter an event title (up to 200 characters).';
    }

    if ($description === '') {
        $errors[] = 'Please enter an event description.';
    }

    if (!in_array($category, $categories, true)) {
        $errors[] = 'Please select a valid event category.';
    }

    if ($venue === '' || mb_strlen($venue) > 200) {
        $errors[] = 'Please enter a venue (up to 200 characters).';
    }

    if ($eventTimestamp === false || $eventDate === '') {
        $errors[] = 'Please enter a valid event date and time.';
    }

    if ($deadlineTimestamp === false || $deadline === '') {
        $errors[] = 'Please enter a valid registration deadline.';
    } elseif ($eventTimestamp !== false && $deadlineTimestamp >= $eventTimestamp) {
        $errors[] = 'The registration deadline must be set prior to the event date.';
    }

    $capacityNumber = filter_var(
        $capacity,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 100000]]
    );

    if ($capacityNumber === false) {
        $errors[] = 'Seat capacity must be an integer between 1 and 100,000.';
    }

    if (!in_array($status, $statuses, true)) {
        $errors[] = 'Please select a valid event status.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Lock the event while checking existing registrations
            $lockStmt = $pdo->prepare('SELECT capacity FROM events WHERE id = :id FOR UPDATE');
            $lockStmt->execute(['id' => $eventId]);
            $lockedCapacity = $lockStmt->fetchColumn();

            if ($lockedCapacity === false) {
                throw new RuntimeException('Event no longer exists.');
            }

            $currentCountStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM registrations
                WHERE event_id = :id
                  AND status = 'registered'
            ");
            $currentCountStmt->execute(['id' => $eventId]);
            $currentActive = (int) $currentCountStmt->fetchColumn();

            if ($capacityNumber < $currentActive) {
                throw new RuntimeException(
                    "Capacity cannot be reduced to $capacityNumber because there are already $currentActive confirmed student registrations."
                );
            }

            $updateStmt = $pdo->prepare('
                UPDATE events
                SET title = :title,
                    description = :description,
                    category = :category,
                    venue = :venue,
                    event_date = :event_date,
                    registration_deadline = :deadline,
                    capacity = :capacity,
                    status = :status
                WHERE id = :id
            ');

            $updateStmt->execute([
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'venue' => $venue,
                'event_date' => date('Y-m-d H:i:s', $eventTimestamp),
                'deadline' => date('Y-m-d H:i:s', $deadlineTimestamp),
                'capacity' => $capacityNumber,
                'status' => $status,
                'id' => $eventId,
            ]);

            $pdo->commit();

            header('Location: /campusconnect/admin/events.php?updated=1');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $e->getMessage();
        }
    }
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
                <a href="/campusconnect/admin/events.php" class="cc-admin-nav-link active">
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
                <a href="/campusconnect/admin/attendees.php?event_id=<?= (int) $eventId ?>" class="cc-admin-nav-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Attendees (<?= $activeRegistrations ?>)
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

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <span class="cc-eyebrow">EVENT SETTINGS</span>
            <h1 class="display-6 fw-bold mt-1 mb-1" style="font-family: 'Outfit', sans-serif;">Edit Event #<?= (int) $eventId ?></h1>
            <p class="text-secondary mb-0">
                Updating details for <strong><?= editEventEscape($event['title']) ?></strong>.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="/campusconnect/event-details.php?id=<?= (int) $eventId ?>" class="btn cc-btn-outline" target="_blank">
                View Public Page &nearr;
            </a>
            <a href="/campusconnect/admin/attendees.php?event_id=<?= (int) $eventId ?>" class="btn cc-btn-outline">
                View Attendees (<?= $activeRegistrations ?>)
            </a>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger mb-4" role="alert">
            <div class="fw-bold mb-1">Please address the following issues:</div>
            <ul class="mb-0 ps-3 small">
                <?php foreach ($errors as $error): ?>
                    <li><?= editEventEscape($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="cc-card p-4 p-md-5">
                <form method="post" action="/campusconnect/admin/edit-event.php?id=<?= (int) $eventId ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= editEventEscape(csrfToken()) ?>">

                    <div class="mb-3">
                        <label for="title" class="form-label">
                            Event Title <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            class="form-control"
                            value="<?= editEventEscape($title) ?>"
                            maxlength="200"
                            required
                        >
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="category" class="form-label">
                                Category <span class="text-danger">*</span>
                            </label>
                            <select id="category" name="category" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= editEventEscape($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                        <?= editEventEscape($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="venue" class="form-label">
                                Venue <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                id="venue"
                                name="venue"
                                class="form-control"
                                value="<?= editEventEscape($venue) ?>"
                                maxlength="200"
                                required
                            >
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">
                            Event Description <span class="text-danger">*</span>
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            class="form-control"
                            rows="5"
                            required
                        ><?= editEventEscape($description) ?></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="event_date" class="form-label">
                                Event Date & Time <span class="text-danger">*</span>
                            </label>
                            <input
                                type="datetime-local"
                                id="event_date"
                                name="event_date"
                                class="form-control"
                                value="<?= editEventEscape($eventDate) ?>"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label for="registration_deadline" class="form-label">
                                Registration Deadline <span class="text-danger">*</span>
                            </label>
                            <input
                                type="datetime-local"
                                id="registration_deadline"
                                name="registration_deadline"
                                class="form-control"
                                value="<?= editEventEscape($deadline) ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="capacity" class="form-label">
                                Seat Capacity <span class="text-danger">*</span>
                            </label>
                            <input
                                type="number"
                                id="capacity"
                                name="capacity"
                                class="form-control"
                                value="<?= editEventEscape($capacity) ?>"
                                min="<?= max(1, $activeRegistrations) ?>"
                                max="100000"
                                required
                            >
                            <div class="form-text small text-muted">
                                Minimum allowed is <strong><?= $activeRegistrations ?></strong> (current active registrations).
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">
                                Event Status <span class="text-danger">*</span>
                            </label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published (Live on site)</option>
                                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled (Registrations suspended)</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2 pt-3 border-top" style="border-color: var(--cc-border) !important;">
                        <button type="submit" class="btn cc-btn-primary">
                            Save Changes
                        </button>
                        <a href="/campusconnect/admin/events.php" class="btn cc-btn-outline">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <aside class="cc-card p-4">
                <h2 class="h5 fw-bold mb-3">Registration Status</h2>

                <div class="p-3 rounded-3 mb-3" style="background: var(--cc-surface-muted); border: 1px solid var(--cc-border);">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">Active Registrations</span>
                        <span class="fw-bold text-dark"><?= $activeRegistrations ?> confirmed</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">Current Capacity</span>
                        <span class="fw-bold text-dark"><?= $capacity ?> seats</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="small text-muted">Seats Remaining</span>
                        <span class="fw-bold text-primary"><?= max(0, (int)$capacity - $activeRegistrations) ?></span>
                    </div>
                </div>

                <div class="small text-muted mb-4" style="line-height: 1.6;">
                    The system prevents reducing capacity below existing confirmed registrations to protect student reservations.
                </div>

                <a href="/campusconnect/admin/attendees.php?event_id=<?= (int) $eventId ?>" class="btn cc-btn-outline w-100 btn-sm">
                    Review Full Attendee Roll &rarr;
                </a>
            </aside>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

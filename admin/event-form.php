<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

// Verify the current user is an administrator
$roleStmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
$roleStmt->execute(['id' => (int) $_SESSION['user']['id']]);
$currentRole = $roleStmt->fetchColumn();

if ($currentRole !== 'admin') {
    http_response_code(403);
    exit('403 Forbidden: Administrator access required.');
}

$pageTitle = 'Create Event';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

$title = '';
$description = '';
$category = 'Technology';
$venue = '';
$eventDate = '';
$deadline = '';
$capacity = '50';
$status = 'published';

$allowedCategories = [
    'Technology',
    'Workshop',
    'Cultural',
    'Sports',
    'Education',
    'Other'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $category = (string) ($_POST['category'] ?? '');
    $venue = trim((string) ($_POST['venue'] ?? ''));
    $eventDate = (string) ($_POST['event_date'] ?? '');
    $deadline = (string) ($_POST['registration_deadline'] ?? '');
    $capacity = (string) ($_POST['capacity'] ?? '');
    $status = (string) ($_POST['status'] ?? 'draft');
    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken(is_string($token) ? $token : null)) {
        $errors[] = 'Invalid security session. Please refresh and try again.';
    }

    if ($title === '' || mb_strlen($title) > 200) {
        $errors[] = 'Please enter an event title (up to 200 characters).';
    }

    if ($description === '') {
        $errors[] = 'Please enter an event description outlining the agenda and details.';
    }

    if (!in_array($category, $allowedCategories, true)) {
        $errors[] = 'Please select a valid event category.';
    }

    if ($venue === '' || mb_strlen($venue) > 200) {
        $errors[] = 'Please enter a venue location (e.g. Auditorium A, Room 204).';
    }

    $eventTimestamp = strtotime($eventDate);
    $deadlineTimestamp = strtotime($deadline);

    if ($eventDate === '' || $eventTimestamp === false || $eventTimestamp <= time()) {
        $errors[] = 'Please select a future event date and time.';
    }

    if ($deadline === '' || $deadlineTimestamp === false || $deadlineTimestamp <= time()) {
        $errors[] = 'Please select a future registration deadline.';
    } elseif ($eventTimestamp !== false && $deadlineTimestamp >= $eventTimestamp) {
        $errors[] = 'The registration deadline must be set prior to the event start time.';
    }

    $capacityNumber = filter_var(
        $capacity,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 100000]]
    );

    if ($capacityNumber === false) {
        $errors[] = 'Seat capacity must be a positive number between 1 and 100,000.';
    }

    if (!in_array($status, ['draft', 'published'], true)) {
        $errors[] = 'Please choose a valid publication status (draft or published).';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO events
                    (title, description, category, venue,
                     event_date, registration_deadline,
                     capacity, status, created_by)
                 VALUES
                    (:title, :description, :category, :venue,
                     :event_date, :deadline,
                     :capacity, :status, :created_by)'
            );

            $stmt->execute([
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'venue' => $venue,
                'event_date' => date('Y-m-d H:i:s', $eventTimestamp),
                'deadline' => date('Y-m-d H:i:s', $deadlineTimestamp),
                'capacity' => $capacityNumber,
                'status' => $status,
                'created_by' => (int) $_SESSION['user']['id'],
            ]);

            header('Location: /campusconnect/admin/events.php?created=1');
            exit;

        } catch (PDOException $e) {
            error_log('CampusConnect create event failed: ' . $e->getMessage());
            $errors[] = 'A database error occurred while creating the event. Please verify your input and try again.';
        }
    }
}

function formEscape($value): string
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
                <a href="/campusconnect/admin/event-form.php" class="cc-admin-nav-link active">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Create Event
                </a>
            </li>
            <li>
                <a href="/campusconnect/admin/attendees.php" class="cc-admin-nav-link">
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
        <span class="cc-eyebrow">EVENT CREATION</span>
        <h1 class="display-6 fw-bold mt-1 mb-1" style="font-family: 'Outfit', sans-serif;">Create New Campus Event</h1>
        <p class="text-secondary mb-0">
            Publish a workshop, seminar, or competition with verified capacity limits and registration deadlines.
        </p>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger mb-4" role="alert">
            <div class="fw-bold mb-1">Please review the following errors:</div>
            <ul class="mb-0 ps-3 small">
                <?php foreach ($errors as $error): ?>
                    <li><?= formEscape($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="cc-card p-4 p-md-5">
                <form method="post" action="/campusconnect/admin/event-form.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= formEscape($_SESSION['csrf_token']) ?>">

                    <div class="mb-3">
                        <label for="title" class="form-label">
                            Event Title <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            class="form-control"
                            placeholder="e.g. Annual Autonomous Robotics Hackathon"
                            value="<?= formEscape($title) ?>"
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
                                <?php foreach ($allowedCategories as $cat): ?>
                                    <option value="<?= formEscape($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                        <?= formEscape($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="venue" class="form-label">
                                Venue / Room <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                id="venue"
                                name="venue"
                                class="form-control"
                                placeholder="e.g. Science Complex Auditorium 101"
                                value="<?= formEscape($venue) ?>"
                                maxlength="200"
                                required
                            >
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">
                            Detailed Description <span class="text-danger">*</span>
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            class="form-control"
                            rows="5"
                            placeholder="Provide schedule details, prerequisites, speaker information, or student guidelines..."
                            required
                        ><?= formEscape($description) ?></textarea>
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
                                value="<?= formEscape($eventDate) ?>"
                                required
                            >
                            <div class="form-text small text-muted">When the event takes place.</div>
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
                                value="<?= formEscape($deadline) ?>"
                                required
                            >
                            <div class="form-text small text-muted">Must be set before the event starts.</div>
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
                                value="<?= formEscape($capacity) ?>"
                                min="1"
                                max="100000"
                                required
                            >
                            <div class="form-text small text-muted">Maximum seats available in venue.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">
                                Publication Status <span class="text-danger">*</span>
                            </label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published (Live on site)</option>
                                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft (Saved privately)</option>
                            </select>
                            <div class="form-text small text-muted">Only published events can be registered for.</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 pt-3 border-top" style="border-color: var(--cc-border) !important;">
                        <button type="submit" class="btn cc-btn-primary">
                            Save & Publish Event
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
                <h2 class="h5 fw-bold mb-3">Publishing Checklist</h2>
                <ul class="text-secondary small d-grid gap-2 mb-4 ps-3" style="line-height: 1.7;">
                    <li>Ensure venue permits match the entered capacity limit.</li>
                    <li>Set deadlines at least 2 hours before the session for attendee roll generation.</li>
                    <li>Clearly detail software requirements or equipment students need to bring.</li>
                </ul>

                <div class="p-3 rounded-3" style="background: var(--cc-primary-subtle); border: 1px solid var(--cc-primary-border);">
                    <div class="fw-bold small text-primary mb-1">Database Integrity Note</div>
                    <div class="small text-muted" style="line-height: 1.6;">
                        Once published, registrations cannot exceed this capacity. If reduced later, capacity cannot be lower than existing confirmed registrations.
                    </div>
                </div>
            </aside>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

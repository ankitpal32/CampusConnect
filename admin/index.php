<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Require a logged-in administrator
requireAdmin();

// Recheck administrator role in database
$adminStmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1');
$adminStmt->execute(['id' => (int) $_SESSION['user']['id']]);
$admin = $adminStmt->fetch();

if (!$admin || $admin['role'] !== 'admin') {
    $_SESSION = [];
    session_destroy();
    header('Location: /campusconnect/login.php');
    exit;
}

$pageTitle = 'Admin Dashboard';

// Real database platform statistics
$totalEvents = (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$publishedEvents = (int) $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'published'")->fetchColumn();
$totalStudents = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalRegistrations = (int) $pdo->query("SELECT COUNT(*) FROM registrations WHERE status = 'registered'")->fetchColumn();

// Recent events query with attendee count
$recentStmt = $pdo->query("
    SELECT
        e.id,
        e.title,
        e.category,
        e.event_date,
        e.capacity,
        e.status,
        (
            SELECT COUNT(*)
            FROM registrations r
            WHERE r.event_id = e.id
              AND r.status = 'registered'
        ) AS registration_count
    FROM events e
    ORDER BY e.created_at DESC
    LIMIT 6
");
$recentEvents = $recentStmt->fetchAll();

function adminEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="cc-admin-nav">
    <div class="container">
        <ul class="cc-admin-nav-list">
            <li>
                <a href="/campusconnect/admin/index.php" class="cc-admin-nav-link active">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="/campusconnect/admin/events.php" class="cc-admin-nav-link">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    Manage Events
                </a>
            </li>
            <li>
                <a href="/campusconnect/admin/event-form.php" class="cc-admin-nav-link">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Create Event
                </a>
            </li>
            <li>
                <a href="/campusconnect/admin/attendees.php" class="cc-admin-nav-link">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Attendees
                </a>
            </li>
            <li>
                <a href="/campusconnect/admin/reports.php" class="cc-admin-nav-link">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    Reports
                </a>
            </li>
        </ul>
    </div>
</div>

<main class="container pb-5">

    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <span class="cc-eyebrow">Admin Center</span>
            <h1 class="h3 fw-bold mt-1 mb-1">Platform Overview</h1>
            <p class="text-secondary small mb-0">
                Signed in as <?= adminEscape($admin['name']) ?> (<?= adminEscape($admin['email']) ?>).
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="/campusconnect/admin/event-form.php" class="btn cc-btn-primary btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>Create Event</span>
            </a>
            <a href="/campusconnect/events.php" class="btn cc-btn-outline btn-sm" title="View public website directory">
                View Public Site &rarr;
            </a>
        </div>
    </div>

    <!-- Metrics Cards -->
    <section class="row g-3 mb-4" aria-label="CampusConnect platform metrics">
        <div class="col-sm-6 col-xl-3">
            <article class="cc-stat-card h-100">
                <span class="cc-stat-label">Total Events</span>
                <p class="cc-stat-value"><?= $totalEvents ?></p>
                <p class="cc-stat-desc">All database records</p>
            </article>
        </div>

        <div class="col-sm-6 col-xl-3">
            <article class="cc-stat-card h-100">
                <span class="cc-stat-label">Published</span>
                <p class="cc-stat-value text-primary"><?= $publishedEvents ?></p>
                <p class="cc-stat-desc">Live on public calendar</p>
            </article>
        </div>

        <div class="col-sm-6 col-xl-3">
            <article class="cc-stat-card h-100">
                <span class="cc-stat-label">Student Accounts</span>
                <p class="cc-stat-value"><?= $totalStudents ?></p>
                <p class="cc-stat-desc">Registered student profiles</p>
            </article>
        </div>

        <div class="col-sm-6 col-xl-3">
            <article class="cc-stat-card h-100">
                <span class="cc-stat-label">Active Bookings</span>
                <p class="cc-stat-value text-success"><?= $totalRegistrations ?></p>
                <p class="cc-stat-desc">Confirmed reservations</p>
            </article>
        </div>
    </section>

    <!-- Shortcuts -->
    <section class="cc-card p-3 p-md-4 mb-4" aria-label="Administrative shortcuts">
        <h2 class="h6 fw-bold mb-3">Management Shortcuts</h2>
        <div class="row g-2">
            <div class="col-sm-6 col-md-3">
                <a href="/campusconnect/admin/event-form.php" class="p-3 rounded-2 border d-block text-reset text-decoration-none bg-white" style="border-color: var(--cc-border) !important;">
                    <div class="fw-semibold text-primary small mb-1">+ New Event</div>
                    <div class="small text-muted" style="font-size: 0.8rem;">Publish a workshop or talk</div>
                </a>
            </div>
            <div class="col-sm-6 col-md-3">
                <a href="/campusconnect/admin/events.php" class="p-3 rounded-2 border d-block text-reset text-decoration-none bg-white" style="border-color: var(--cc-border) !important;">
                    <div class="fw-semibold text-dark small mb-1">Catalog</div>
                    <div class="small text-muted" style="font-size: 0.8rem;">Edit schedules and status</div>
                </a>
            </div>
            <div class="col-sm-6 col-md-3">
                <a href="/campusconnect/admin/attendees.php" class="p-3 rounded-2 border d-block text-reset text-decoration-none bg-white" style="border-color: var(--cc-border) !important;">
                    <div class="fw-semibold text-dark small mb-1">Attendees</div>
                    <div class="small text-muted" style="font-size: 0.8rem;">Mark attendance and check in</div>
                </a>
            </div>
            <div class="col-sm-6 col-md-3">
                <a href="/campusconnect/admin/reports.php" class="p-3 rounded-2 border d-block text-reset text-decoration-none bg-white" style="border-color: var(--cc-border) !important;">
                    <div class="fw-semibold text-dark small mb-1">Reports</div>
                    <div class="small text-muted" style="font-size: 0.8rem;">Capacity and turnout stats</div>
                </a>
            </div>
        </div>
    </section>

    <!-- Recent Events Management Table -->
    <section aria-labelledby="recent-events-heading">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 id="recent-events-heading" class="h6 fw-bold mb-0">
                Recently Created Events
            </h2>
            <a href="/campusconnect/admin/events.php" class="small fw-semibold text-primary">
                View all <?= $totalEvents ?> events &rarr;
            </a>
        </div>

        <?php if (empty($recentEvents)): ?>
            <div class="cc-empty-state">
                <div class="cc-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <h3 class="h6 fw-bold">No events created yet</h3>
                <p class="text-secondary small mx-auto" style="max-width: 400px;">
                    Get started by creating the first event on CampusConnect.
                </p>
                <a href="/campusconnect/admin/event-form.php" class="btn cc-btn-primary btn-sm mt-2">
                    Create First Event
                </a>
            </div>
        <?php else: ?>
            <div class="cc-table-container">
                <div class="table-responsive">
                    <table class="table cc-table">
                        <thead>
                            <tr>
                                <th scope="col">Event Details</th>
                                <th scope="col">Category</th>
                                <th scope="col">Scheduled Date</th>
                                <th scope="col">Registrations</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentEvents as $ev): ?>
                                <?php
                                $evDate = strtotime($ev['event_date']);
                                $cap = (int) $ev['capacity'];
                                $reg = (int) $ev['registration_count'];

                                $statusBadge = 'cc-badge-category';
                                if ($ev['status'] === 'published') {
                                    $statusBadge = 'cc-badge-success';
                                } elseif ($ev['status'] === 'cancelled') {
                                    $statusBadge = 'cc-badge-danger';
                                } elseif ($ev['status'] === 'draft') {
                                    $statusBadge = 'cc-badge-neutral';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block">
                                            <?= adminEscape($ev['title']) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="cc-badge cc-badge-category">
                                            <?= adminEscape($ev['category']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small">
                                            <?= $evDate !== false ? date('M j, Y, g:i A', $evDate) : 'TBC' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold small">
                                            <?= $reg ?> / <?= $cap ?>
                                        </span>
                                        <span class="text-muted small">
                                            (<?= $cap > 0 ? round(($reg / $cap) * 100) : 0 ?>%)
                                        </span>
                                    </td>
                                    <td>
                                        <span class="cc-badge <?= $statusBadge ?>">
                                            <?= ucfirst($ev['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <a href="/campusconnect/event-details.php?id=<?= (int) $ev['id'] ?>" class="btn cc-btn-outline cc-btn-sm" title="Preview event details">
                                                View
                                            </a>
                                            <a href="/campusconnect/admin/edit-event.php?id=<?= (int) $ev['id'] ?>" class="btn cc-btn-outline cc-btn-sm" title="Edit event settings">
                                                Edit
                                            </a>
                                            <a href="/campusconnect/admin/attendees.php?event_id=<?= (int) $ev['id'] ?>" class="btn cc-btn-outline cc-btn-sm" title="Review registered attendees">
                                                Attendees
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </section>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

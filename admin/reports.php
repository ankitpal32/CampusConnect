<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

// Verify administrator role using the database
$roleStmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
$roleStmt->execute(['id' => (int) $_SESSION['user']['id']]);

if ($roleStmt->fetchColumn() !== 'admin') {
    http_response_code(403);
    exit('403 Forbidden: Administrator access required.');
}

$pageTitle = 'Reports & Analytics';

// Platform-wide statistics from real database counts
$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM events) AS total_events,
        (SELECT COUNT(*) FROM events WHERE status = 'published') AS published_events,
        (SELECT COUNT(*) FROM users WHERE role = 'student') AS total_students,
        (SELECT COUNT(*) FROM registrations WHERE status = 'registered') AS active_registrations
")->fetch(PDO::FETCH_ASSOC);

// Per-event performance statistics
$reportStmt = $pdo->query("
    SELECT
        e.id,
        e.title,
        e.category,
        e.event_date,
        e.capacity,
        e.status,
        COUNT(r.id) AS registered_count
    FROM events e
    LEFT JOIN registrations r
        ON r.event_id = e.id
        AND r.status = 'registered'
    GROUP BY
        e.id, e.title, e.category, e.event_date,
        e.capacity, e.status
    ORDER BY e.event_date DESC
");

$eventReports = $reportStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate remaining seats for upcoming published events
$availableSeats = 0;
$totalFutureCapacity = 0;
$totalFutureRegistrations = 0;
$categoryCounts = [];

foreach ($eventReports as $report) {
    $cat = $report['category'] ?? 'Other';
    $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;

    if ($report['status'] === 'published' && strtotime($report['event_date']) > time()) {
        $cap = (int) $report['capacity'];
        $reg = (int) $report['registered_count'];
        $availableSeats += max(0, $cap - $reg);
        $totalFutureCapacity += $cap;
        $totalFutureRegistrations += $reg;
    }
}

$overallOccupancyRate = $totalFutureCapacity > 0
    ? round(($totalFutureRegistrations / $totalFutureCapacity) * 100, 1)
    : 0;

function reportEscape($value): string
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
                <a href="/campusconnect/admin/attendees.php" class="cc-admin-nav-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Attendees
                </a>
            </li>
            <li>
                <a href="/campusconnect/admin/reports.php" class="cc-admin-nav-link active">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    Reports
                </a>
            </li>
        </ul>
    </div>
</div>

<main class="container pb-5">

    <div class="mb-4">
        <span class="cc-eyebrow">METRICS & CAPACITY</span>
        <h1 class="display-6 fw-bold mt-1 mb-1" style="font-family: 'Outfit', sans-serif;">Platform Analytics & Reports</h1>
        <p class="text-secondary mb-0">
            Real-time occupancy calculations, seat allocation totals, and event performance.
        </p>
    </div>

    <!-- Key Metrics Cards -->
    <section class="row g-4 mb-5" aria-label="Key database performance statistics">
        <div class="col-sm-6 col-lg-3">
            <article class="cc-stat-card h-100">
                <span class="cc-stat-label">Total Events</span>
                <p class="cc-stat-value"><?= (int) $stats['total_events'] ?></p>
                <p class="cc-stat-desc">
                    <?= (int) $stats['published_events'] ?> currently published
                </p>
            </article>
        </div>

        <div class="col-sm-6 col-lg-3">
            <article class="cc-stat-card h-100">
                <span class="cc-stat-label">Student Community</span>
                <p class="cc-stat-value text-primary"><?= (int) $stats['total_students'] ?></p>
                <p class="cc-stat-desc">Verified registered students</p>
            </article>
        </div>

        <div class="col-sm-6 col-lg-3">
            <article class="cc-stat-card h-100">
                <span class="cc-stat-label">Active Bookings</span>
                <p class="cc-stat-value text-success"><?= (int) $stats['active_registrations'] ?></p>
                <p class="cc-stat-desc">Confirmed seats held</p>
            </article>
        </div>

        <div class="col-sm-6 col-lg-3">
            <article class="cc-stat-card h-100">
                <span class="cc-stat-label">Open Future Seats</span>
                <p class="cc-stat-value text-dark"><?= number_format($availableSeats) ?></p>
                <p class="cc-stat-desc">Remaining slots across events</p>
            </article>
        </div>
    </section>

    <!-- Capacity Summary Banner -->
    <section class="cc-card p-4 mb-5" style="background: var(--cc-surface-muted);">
        <div class="row align-items-center g-4">
            <div class="col-md-7">
                <h2 class="h5 fw-bold mb-1">Upcoming Event Capacity Utilization</h2>
                <p class="text-secondary small mb-3">
                    Calculated from active registrations vs total venue capacities of all upcoming published events.
                </p>
                <div class="cc-progress-track" style="height: 10px;">
                    <div class="cc-progress-fill" style="width: <?= min(100, $overallOccupancyRate) ?>%;"></div>
                </div>
            </div>
            <div class="col-md-5 d-flex justify-content-md-end gap-3">
                <div class="bg-white p-3 rounded-3 border text-center" style="border-color: var(--cc-border) !important; min-width: 130px;">
                    <span class="small text-muted d-block">Overall Fill Rate</span>
                    <strong class="h3 text-primary mb-0"><?= $overallOccupancyRate ?>%</strong>
                </div>
                <div class="bg-white p-3 rounded-3 border text-center" style="border-color: var(--cc-border) !important; min-width: 130px;">
                    <span class="small text-muted d-block">Seats Booked</span>
                    <strong class="h3 text-dark mb-0"><?= $totalFutureRegistrations ?> / <?= $totalFutureCapacity ?></strong>
                </div>
            </div>
        </div>
    </section>

    <!-- Event Performance Table -->
    <section aria-labelledby="performance-heading">
        <h2 id="performance-heading" class="h4 fw-bold mb-3">
            Individual Event Performance Breakdown
        </h2>

        <?php if (empty($eventReports)): ?>
            <div class="cc-empty-state">
                <p class="text-secondary mb-0">No event records available for performance analysis yet.</p>
            </div>
        <?php else: ?>
            <div class="cc-table-container">
                <div class="table-responsive">
                    <table class="table cc-table">
                        <thead>
                            <tr>
                                <th scope="col">Event Title</th>
                                <th scope="col">Category</th>
                                <th scope="col">Event Date</th>
                                <th scope="col">Capacity</th>
                                <th scope="col">Registered</th>
                                <th scope="col" style="min-width: 160px;">Occupancy</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventReports as $event): ?>
                                <?php
                                $cap = (int) $event['capacity'];
                                $reg = (int) $event['registered_count'];
                                $occupancy = $cap > 0 ? min(100, round(($reg / $cap) * 100)) : 0;
                                $eventDate = strtotime($event['event_date']);

                                $statusBadge = 'cc-badge-category';
                                if ($event['status'] === 'published') {
                                    $statusBadge = 'cc-badge-success';
                                } elseif ($event['status'] === 'cancelled') {
                                    $statusBadge = 'cc-badge-danger';
                                } elseif ($event['status'] === 'draft') {
                                    $statusBadge = 'cc-badge-neutral';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block">
                                            <?= reportEscape($event['title']) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="cc-badge cc-badge-category">
                                            <?= reportEscape($event['category']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small text-nowrap">
                                            <?= $eventDate !== false ? date('d M Y', $eventDate) : 'TBC' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small fw-semibold"><?= $cap ?></span>
                                    </td>
                                    <td>
                                        <span class="small fw-bold text-dark"><?= $reg ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="cc-progress-track flex-grow-1" style="height: 6px;">
                                                <div class="cc-progress-fill" style="width: <?= $occupancy ?>%;"></div>
                                            </div>
                                            <span class="small fw-bold" style="min-width: 38px; text-align: right;">
                                                <?= $occupancy ?>%
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="cc-badge <?= $statusBadge ?>">
                                            <?= ucfirst($event['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="/campusconnect/admin/attendees.php?event_id=<?= (int) $event['id'] ?>" class="btn cc-btn-outline cc-btn-sm" title="View attendee roll">
                                            Attendees
                                        </a>
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

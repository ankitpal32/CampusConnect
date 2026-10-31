<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

// Verify administrator role against database
$roleStmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
$roleStmt->execute(['id' => (int) $_SESSION['user']['id']]);

if ($roleStmt->fetchColumn() !== 'admin') {
    http_response_code(403);
    exit('403 Forbidden: Administrator access required.');
}

$pageTitle = 'Manage Events';

$stmt = $pdo->query("
    SELECT
        e.id,
        e.title,
        e.category,
        e.venue,
        e.event_date,
        e.capacity,
        e.status,
        (
            SELECT COUNT(*)
            FROM registrations r
            WHERE r.event_id = e.id
              AND r.status = 'registered'
        ) AS attendee_count
    FROM events e
    ORDER BY e.created_at DESC
");

$events = $stmt->fetchAll();

function manageEventEscape($value): string
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

    <!-- Flash Feedback -->
    <?php if (isset($_GET['created'])): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="status">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <div>Event published successfully and added to the campus catalog.</div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="status">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <div>Event changes saved successfully in the database.</div>
        </div>
    <?php endif; ?>

    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-5">
        <div>
            <span class="cc-eyebrow">ADMINISTRATION</span>
            <h1 class="display-6 fw-bold mt-1 mb-1" style="font-family: 'Outfit', sans-serif;">Manage College Events</h1>
            <p class="text-secondary mb-0">
                View, edit, or adjust publication status for all campus event records.
            </p>
        </div>

        <a href="/campusconnect/admin/event-form.php" class="btn cc-btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>+ Create New Event</span>
        </a>
    </div>

    <!-- Events List Panel -->
    <?php if (empty($events)): ?>
        <section class="cc-empty-state my-4">
            <div class="cc-empty-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <h2 class="h5 fw-bold">No Events in Platform</h2>
            <p class="text-secondary small mx-auto" style="max-width: 400px;">
                Your college event catalog is currently empty. Create your first workshop, hackathon, or seminar.
            </p>
            <a href="/campusconnect/admin/event-form.php" class="btn cc-btn-primary mt-2">
                Create First Event
            </a>
        </section>
    <?php else: ?>
        <section class="cc-table-container">
            <div class="table-responsive">
                <table class="table cc-table">
                    <thead>
                        <tr>
                            <th scope="col">Event Title</th>
                            <th scope="col">Category</th>
                            <th scope="col">Scheduled Date</th>
                            <th scope="col">Venue</th>
                            <th scope="col">Attendees / Capacity</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <?php
                            $eventTime = strtotime($event['event_date']);
                            $cap = (int) $event['capacity'];
                            $att = (int) $event['attendee_count'];

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
                                        <?= manageEventEscape($event['title']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="cc-badge cc-badge-category">
                                        <?= manageEventEscape($event['category']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="small text-nowrap">
                                        <?= $eventTime !== false ? date('d M Y, g:i A', $eventTime) : 'TBC' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="small text-secondary">
                                        <?= manageEventEscape($event['venue']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold small"><?= $att ?></span>
                                    <span class="text-muted small">/ <?= $cap ?></span>
                                    <div class="cc-progress-track mt-1" style="max-width: 90px; height: 4px;">
                                        <div class="cc-progress-fill" style="width: <?= $cap > 0 ? min(100, round(($att / $cap) * 100)) : 0 ?>%;"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="cc-badge <?= $statusBadge ?>">
                                        <?= ucfirst($event['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="/campusconnect/event-details.php?id=<?= (int) $event['id'] ?>" class="btn cc-btn-outline cc-btn-sm" title="Preview event on website">
                                            View
                                        </a>
                                        <a href="/campusconnect/admin/edit-event.php?id=<?= (int) $event['id'] ?>" class="btn cc-btn-outline cc-btn-sm" title="Edit details">
                                            Edit
                                        </a>
                                        <a href="/campusconnect/admin/attendees.php?event_id=<?= (int) $event['id'] ?>" class="btn cc-btn-outline cc-btn-sm" title="View attendees list">
                                            Attendees
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
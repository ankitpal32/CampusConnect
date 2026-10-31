<?php
$pageTitle = 'Explore Events';

require_once __DIR__ . '/config/database.php';

// Read filters
$search = trim((string) ($_GET['search'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? 'date_asc'));

// Query categories currently in the database for published events
$categoryStmt = $pdo->query("
    SELECT DISTINCT category
    FROM events
    WHERE status = 'published'
      AND event_date >= NOW()
    ORDER BY category ASC
");
$dbCategories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// Standard categories merged with database categories to ensure comprehensive filtering
$defaultCategories = ['Technology', 'Workshop', 'Cultural', 'Sports', 'Education', 'Other'];
$allCategories = array_values(array_unique(array_merge($defaultCategories, $dbCategories)));

if ($category !== '' && !in_array($category, $allCategories, true)) {
    $category = '';
}

// Build query for published upcoming events
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
";

$params = [];

if ($search !== '') {
    $sql .= " AND (e.title LIKE :title_search
                   OR e.venue LIKE :venue_search
                   OR e.description LIKE :desc_search)";

    $searchTerm = '%' . $search . '%';
    $params['title_search'] = $searchTerm;
    $params['venue_search'] = $searchTerm;
    $params['desc_search'] = $searchTerm;
}

if ($category !== '') {
    $sql .= " AND e.category = :category";
    $params['category'] = $category;
}

// Sort options
switch ($sort) {
    case 'date_desc':
        $sql .= " ORDER BY e.event_date DESC";
        break;
    case 'title_asc':
        $sql .= " ORDER BY e.title ASC";
        break;
    case 'capacity_desc':
        $sql .= " ORDER BY e.capacity DESC";
        break;
    case 'date_asc':
    default:
        $sql .= " ORDER BY e.event_date ASC";
        $sort = 'date_asc';
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

function eventEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$hasActiveFilters = ($search !== '' || $category !== '' || $sort !== 'date_asc');

require_once __DIR__ . '/includes/header.php';
?>

<main class="container py-5">

    <!-- Page Header -->
    <div class="mb-4">
        <span class="cc-eyebrow">CAMPUS EVENT DIRECTORY &bull; SKFGI MANKUNDU</span>
        <h1 class="display-5 fw-bold mt-1 mb-2">Explore Campus Events</h1>
        <p class="text-secondary" style="max-width: 680px; font-size: 1.05rem;">
            Discover technical workshops, student exhibitions, resume seminars, and inter-departmental competitions at SKFGI. Filter by category or search by topic and venue.
        </p>
    </div>

    <!-- Filter Card -->
    <section class="cc-card p-4 mb-4" aria-label="Search and filter events">
        <form method="get" action="/campusconnect/events.php" class="row g-3 align-items-end">

            <div class="col-lg-5 col-md-6">
                <label for="search" class="form-label">
                    Search keywords or venue
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: var(--cc-radius-md) 0 0 var(--cc-radius-md); border-color: var(--cc-border-strong);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input
                        type="search"
                        class="form-control border-start-0"
                        id="search"
                        name="search"
                        placeholder="e.g. BCA, Coding, Workshop, Lab 3, Auditorium..."
                        value="<?= eventEscape($search) ?>"
                        maxlength="100"
                        style="border-radius: 0 var(--cc-radius-md) var(--cc-radius-md) 0;"
                    >
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <label for="category" class="form-label">
                    Category
                </label>
                <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= eventEscape($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                            <?= eventEscape($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-2 col-md-6">
                <label for="sort" class="form-label">
                    Sort by
                </label>
                <select class="form-select" id="sort" name="sort">
                    <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Date (Soonest)</option>
                    <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Date (Latest)</option>
                    <option value="title_asc" <?= $sort === 'title_asc' ? 'selected' : '' ?>>Title (A-Z)</option>
                    <option value="capacity_desc" <?= $sort === 'capacity_desc' ? 'selected' : '' ?>>Highest Capacity</option>
                </select>
            </div>

            <div class="col-lg-2 col-md-6 d-flex gap-2">
                <button type="submit" class="btn cc-btn-primary flex-grow-1">
                    Apply Filter
                </button>

                <?php if ($hasActiveFilters): ?>
                    <a href="/campusconnect/events.php" class="btn cc-btn-outline" title="Reset all filters">
                        Reset
                    </a>
                <?php endif; ?>
            </div>

        </form>

        <!-- Active Filter Chips -->
        <?php if ($hasActiveFilters): ?>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-3 border-top" style="border-color: var(--cc-border) !important;">
                <span class="small text-muted fw-semibold">Active filters:</span>

                <?php if ($search !== ''): ?>
                    <span class="cc-badge cc-badge-neutral">
                        Keyword: "<?= eventEscape($search) ?>"
                    </span>
                <?php endif; ?>

                <?php if ($category !== ''): ?>
                    <span class="cc-badge cc-badge-category">
                        Category: <?= eventEscape($category) ?>
                    </span>
                <?php endif; ?>

                <?php if ($sort !== 'date_asc'): ?>
                    <span class="cc-badge cc-badge-neutral">
                        Sorted
                    </span>
                <?php endif; ?>

                <a href="/campusconnect/events.php" class="small text-primary fw-bold ms-2">
                    Clear all filters
                </a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Results Section -->
    <section aria-labelledby="results-heading">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 id="results-heading" class="h4 fw-bold mb-0">
                <?= count($events) ?> <?= count($events) === 1 ? 'Event' : 'Events' ?> Available
            </h2>
            <span class="text-secondary small">
                Updated live from MySQL database
            </span>
        </div>

        <?php if (empty($events)): ?>
            <div class="cc-empty-state">
                <div class="cc-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </div>
                <h3 class="h4 fw-bold">No matching campus events found</h3>
                <p class="text-secondary mx-auto" style="max-width: 420px;">
                    We couldn't find any published upcoming events matching your selected criteria. Try modifying your search term or category.
                </p>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <a href="/campusconnect/events.php" class="btn cc-btn-primary">
                        Show All Events
                    </a>
                </div>
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

                    if ($isDeadlinePassed) {
                        $badgeClass = 'cc-badge-danger';
                        $badgeText = 'Deadline Passed';
                    } elseif ($remaining === 0) {
                        $badgeClass = 'cc-badge-danger';
                        $badgeText = 'Full capacity';
                    } elseif ($remaining <= 5) {
                        $badgeClass = 'cc-badge-warning';
                        $badgeText = 'Only ' . $remaining . ' Seats Left';
                    } else {
                        $badgeClass = 'cc-badge-success';
                        $badgeText = $remaining . ' Seats Available';
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
                                            <?= eventEscape($event['category']) ?>
                                        </span>
                                        <span class="cc-badge <?= $badgeClass ?>">
                                            <?= $badgeText ?>
                                        </span>
                                    </div>
                                    <div class="small text-muted">
                                        <?= $eventTimestamp !== false ? date('l &bull; g:i A', $eventTimestamp) : 'Schedule pending' ?>
                                    </div>
                                </div>
                            </div>

                            <h3 class="h5 fw-bold mb-2" style="line-height: 1.35;">
                                <a href="/campusconnect/event-details.php?id=<?= (int) $event['id'] ?>" class="text-reset">
                                    <?= eventEscape($event['title']) ?>
                                </a>
                            </h3>

                            <p class="text-secondary small mb-4 flex-grow-1" style="line-height: 1.6;">
                                <?= eventEscape(mb_strimwidth($event['description'], 0, 130, '...')) ?>
                            </p>

                            <div class="pt-3 border-top mt-auto" style="border-color: var(--cc-border) !important;">
                                <div class="d-flex align-items-center gap-2 text-secondary small mb-3">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    <span class="text-truncate"><?= eventEscape($event['venue']) ?></span>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Capacity fill</span>
                                        <span class="fw-semibold text-dark"><?= $registered ?> / <?= $capacity ?></span>
                                    </div>
                                    <div class="cc-progress-track">
                                        <div class="cc-progress-fill" style="width: <?= $percentFilled ?>%;"></div>
                                    </div>
                                </div>

                                <a href="/campusconnect/event-details.php?id=<?= (int) $event['id'] ?>" class="btn cc-btn-outline w-100">
                                    View Details &bull; Register &rarr;
                                </a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

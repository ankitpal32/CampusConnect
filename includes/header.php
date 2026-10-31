<?php
// Start a session only if one has not already started.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';

$user = $_SESSION['user'] ?? null;
$isLoggedIn = isLoggedIn();
$isAdmin = $isLoggedIn && (($user['role'] ?? '') === 'admin');

$userName = $isLoggedIn
    ? (string) ($user['name'] ?? 'User')
    : '';

$userEmail = $isLoggedIn
    ? (string) ($user['email'] ?? '')
    : '';

$userRole = $isLoggedIn
    ? (string) ($user['role'] ?? 'student')
    : '';

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF'] ?? ''));
$isAdminSection = ($currentDir === 'admin');

$pageTitle = $pageTitle ?? 'CampusConnect';

$escapedTitle = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
$escapedUserName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');

function ccNavActive(bool $isActive): string
{
    return $isActive ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="CampusConnect is a college event management platform. Discover workshops, seminars, and student activities with real-time seat availability.">

    <title><?= $escapedTitle ?> | CampusConnect</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Project Stylesheet -->
    <link href="/campusconnect/assets/css/style.css" rel="stylesheet">

    <!-- Bootstrap JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</head>

<body>

<header>
    <nav class="navbar navbar-expand-lg cc-navbar sticky-top" aria-label="Main navigation">
        <div class="container">

            <!-- Brand Logo -->
            <a class="cc-brand" href="/campusconnect/index.php" aria-label="CampusConnect home">
                <span class="cc-brand-mark" aria-hidden="true">C</span>
                <span>CampusConnect</span>
            </a>

            <!-- Mobile Navigation Toggle -->
            <button
                class="navbar-toggler border-0 p-2"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#ccNavbar"
                aria-controls="ccNavbar"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="ccNavbar">

                <ul class="navbar-nav mx-auto my-2 my-lg-0">
                    <li class="nav-item">
                        <a
                            class="nav-link <?= ccNavActive(!$isAdminSection && $currentPage === 'index.php') ?>"
                            href="/campusconnect/index.php"
                            <?= (!$isAdminSection && $currentPage === 'index.php') ? 'aria-current="page"' : '' ?>
                        >
                            Home
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link <?= ccNavActive(!$isAdminSection && in_array($currentPage, ['events.php', 'event-details.php'])) ?>"
                            href="/campusconnect/events.php"
                            <?= (!$isAdminSection && $currentPage === 'events.php') ? 'aria-current="page"' : '' ?>
                        >
                            Events
                        </a>
                    </li>

                    <?php if ($isLoggedIn && $isAdmin): ?>
                        <li class="nav-item">
                            <a
                                class="nav-link <?= ccNavActive($isAdminSection) ?>"
                                href="/campusconnect/admin/index.php"
                                <?= $isAdminSection ? 'aria-current="page"' : '' ?>
                            >
                                Admin Center
                            </a>
                        </li>
                    <?php elseif ($isLoggedIn): ?>
                        <li class="nav-item">
                            <a
                                class="nav-link <?= ccNavActive($currentPage === 'dashboard.php') ?>"
                                href="/campusconnect/dashboard.php"
                                <?= $currentPage === 'dashboard.php' ? 'aria-current="page"' : '' ?>
                            >
                                Dashboard
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                class="nav-link <?= ccNavActive($currentPage === 'my-registrations.php') ?>"
                                href="/campusconnect/my-registrations.php"
                                <?= $currentPage === 'my-registrations.php' ? 'aria-current="page"' : '' ?>
                            >
                                My Registrations
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <!-- Session-aware account actions -->
                <div class="navbar-nav cc-navbar-actions align-items-lg-center gap-2">

                    <?php if ($isLoggedIn): ?>
                        <div class="d-flex align-items-center gap-2">
                            <span class="cc-user-pill">
                                <span class="cc-user-avatar" aria-hidden="true">
                                    <?= strtoupper(substr($userName, 0, 1)) ?>
                                </span>
                                <span><?= $escapedUserName ?></span>
                                <span class="cc-badge <?= $isAdmin ? 'cc-badge-warning' : 'cc-badge-category' ?>" style="font-size: 0.7rem; padding: 2px 6px;">
                                    <?= $isAdmin ? 'Admin' : 'Student' ?>
                                </span>
                            </span>

                            <form action="/campusconnect/logout.php" method="post" class="m-0">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>"
                                >
                                <button type="submit" class="btn cc-btn-outline cc-btn-sm" title="Sign out of CampusConnect">
                                    Sign out
                                </button>
                            </form>
                        </div>

                    <?php else: ?>
                        <a class="btn cc-btn-outline cc-btn-sm" href="/campusconnect/login.php">
                            Sign in
                        </a>

                        <a class="btn cc-btn-primary cc-btn-sm" href="/campusconnect/register.php">
                            Create account
                        </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </nav>
</header>

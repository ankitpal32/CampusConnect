<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (($_SESSION['user']['role'] ?? '') === 'admin') {
        header('Location: /campusconnect/admin/index.php');
    } else {
        header('Location: /campusconnect/dashboard.php');
    }
    exit;
}

$pageTitle = 'Sign In';
$errors = [];
$email = '';

// Check if current host is local or demo environment
$hostName = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? '';
$isDemoEnvironment = in_array($hostName, ['localhost', '127.0.0.1', '::1'], true)
    || str_starts_with($hostName, 'localhost:')
    || (getenv('APP_ENV') === 'development')
    || (getenv('CAMPUSCONNECT_DEMO') === '1');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $token = (string) ($_POST['csrf_token'] ?? '');

    if (!verifyCsrfToken($token)) {
        $errors[] = 'Invalid security session. Please refresh and try again.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '' || strlen($password) > 72) {
        $errors[] = 'Please enter your account password.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'SELECT id, name, email, password_hash, role
             FROM users
             WHERE email = :email
             LIMIT 1'
        );

        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Generate a fresh session ID upon login
            if (!session_regenerate_id(true)) {
                $errors[] = 'Unable to establish a secure session. Please try again.';
            } else {
                // Rotate CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                $_SESSION['user'] = [
                    'id' => (int) $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ];

                if ($user['role'] === 'admin') {
                    header('Location: /campusconnect/admin/index.php');
                } else {
                    header('Location: /campusconnect/dashboard.php');
                }
                exit;
            }
        } else {
            $errors[] = 'Invalid email address or password. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';

function loginEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<main class="container py-5">
    <div class="row justify-content-center align-items-start g-4">

        <!-- Sign In Card -->
        <div class="<?= $isDemoEnvironment ? 'col-lg-6 col-xl-5' : 'col-md-8 col-lg-5 col-xl-4' ?>">
            <div class="cc-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="cc-brand-mark mx-auto mb-3" style="width: 48px; height: 48px; font-size: 1.4rem;">C</span>
                    <h1 class="h3 fw-bold mb-1">Welcome Back</h1>
                    <p class="text-secondary small">
                        Sign in to manage your campus registrations and discover upcoming events.
                    </p>
                </div>

                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="status">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Account created successfully. You can now log in below.</span>
                    </div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="alert alert-danger mb-4" role="alert">
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $error): ?>
                                <li><?= loginEscape($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="/campusconnect/login.php" novalidate id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?= loginEscape($_SESSION['csrf_token']) ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Email address
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="name@college.edu"
                            value="<?= loginEscape($email) ?>"
                            maxlength="150"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label mb-0">
                                Password
                            </label>
                            <button
                                type="button"
                                class="btn btn-link p-0 text-decoration-none small"
                                style="color: var(--cc-primary); font-size: 0.8rem;"
                                id="togglePasswordBtn"
                                aria-label="Toggle password visibility"
                            >
                                Show password
                            </button>
                        </div>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn cc-btn-primary w-100 py-2">
                        Sign In
                    </button>
                </form>

                <div class="pt-4 mt-4 text-center border-top" style="border-color: var(--cc-border) !important;">
                    <p class="text-secondary small mb-0">
                        Don't have a student account?
                        <a href="/campusconnect/register.php" class="fw-bold">
                            Join CampusConnect
                        </a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Demo Accounts Section (Shown only in Local/Development Environment) -->
        <?php if ($isDemoEnvironment): ?>
            <div class="col-lg-6 col-xl-5">
                <aside class="cc-card p-4 p-md-5" style="background: var(--cc-surface); border: 1px solid var(--cc-primary-border); box-shadow: var(--cc-shadow-md);">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="cc-eyebrow mb-0">REVIEWER ENVIRONMENT</span>
                        <span class="cc-badge cc-badge-category" style="font-size: 0.72rem;">Demo Sandbox</span>
                    </div>

                    <h2 class="h4 fw-bold mb-2">Explore Demo Accounts</h2>
                    <p class="text-secondary small mb-4" style="line-height: 1.6;">
                        Evaluating CampusConnect? Use these preconfigured demonstration accounts to test both student and administrator workflows without manual registration.
                    </p>

                    <!-- Account 1: Demo Administrator -->
                    <div class="p-3 rounded-3 mb-3" style="background: var(--cc-surface-muted); border: 1px solid var(--cc-border);">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-dark">Campus Coordinator</strong>
                                    <span class="cc-badge cc-badge-warning" style="font-size: 0.7rem; padding: 2px 7px;">Admin Role</span>
                                </div>
                                <div class="small text-muted font-monospace mt-1">demo.admin@campusconnect.edu</div>
                            </div>

                            <button
                                type="button"
                                class="btn cc-btn-outline cc-btn-sm"
                                onclick="fillCredentials('demo.admin@campusconnect.edu', 'AdminDemo@2026')"
                                title="Auto-fill credentials in sign in form"
                            >
                                Use Account
                            </button>
                        </div>

                        <div class="small text-secondary pt-2 border-top" style="border-color: var(--cc-border) !important; line-height: 1.5;">
                            <strong>Capabilities:</strong> Full CRUD event management, safe deletion, attendee check-in at the door, CSV exports, and platform reports.
                        </div>
                    </div>

                    <!-- Account 2: Demo Student -->
                    <div class="p-3 rounded-3 mb-4" style="background: var(--cc-surface-muted); border: 1px solid var(--cc-border);">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-dark">Alex Rivera</strong>
                                    <span class="cc-badge cc-badge-category" style="font-size: 0.7rem; padding: 2px 7px;">Student Role</span>
                                </div>
                                <div class="small text-muted font-monospace mt-1">demo.student@campusconnect.edu</div>
                            </div>

                            <button
                                type="button"
                                class="btn cc-btn-outline cc-btn-sm"
                                onclick="fillCredentials('demo.student@campusconnect.edu', 'StudentDemo@2026')"
                                title="Auto-fill credentials in sign in form"
                            >
                                Use Account
                            </button>
                        </div>

                        <div class="small text-secondary pt-2 border-top" style="border-color: var(--cc-border) !important; line-height: 1.5;">
                            <strong>Capabilities:</strong> Public event browsing, 1-click atomic seat registration, schedule tracking on Student Dashboard, and IDOR-safe cancellation.
                        </div>
                    </div>

                    <div class="p-2 px-3 rounded-2 text-muted small" style="background: var(--cc-primary-subtle); border: 1px solid var(--cc-primary-border);">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary me-1"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <span>Clicking <strong>Use Account</strong> loads credentials into the form. Password hashing and CSRF validation are enforced normally on submit.</span>
                    </div>
                </aside>
            </div>
        <?php endif; ?>

    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const pwdInput = document.getElementById('password');

    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = pwdInput.getAttribute('type') === 'password';
            pwdInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleBtn.textContent = isPassword ? 'Hide password' : 'Show password';
        });
    }
});

function fillCredentials(email, password) {
    const emailInput = document.getElementById('email');
    const pwdInput = document.getElementById('password');

    if (emailInput && pwdInput) {
        emailInput.value = email;
        pwdInput.value = password;
        pwdInput.setAttribute('type', 'password');

        const toggleBtn = document.getElementById('togglePasswordBtn');
        if (toggleBtn) {
            toggleBtn.textContent = 'Show password';
        }

        // Visual subtle pulse on the sign in button to indicate readiness
        const submitBtn = document.querySelector('#loginForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.focus();
        }
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

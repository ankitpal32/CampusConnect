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
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5 col-xl-4">

            <div class="cc-card p-4 p-md-5 my-md-4">
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

                <form method="post" action="/campusconnect/login.php" novalidate>
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
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

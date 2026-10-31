<?php
$pageTitle = 'Create Student Account';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /campusconnect/dashboard.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $token = (string) ($_POST['csrf_token'] ?? '');

    if (!verifyCsrfToken($token)) {
        $errors[] = 'Invalid security session. Please refresh and try again.';
    }

    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Please enter your full name (up to 100 characters).';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Please enter a valid college or personal email address.';
    }

    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Your password must be between 8 and 72 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'The entered passwords do not match.';
    }

    if (!$errors) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);

        if ($check->fetch()) {
            $errors[] = 'An account with this email address already exists. Please log in.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $insert = $pdo->prepare(
                    'INSERT INTO users (name, email, password_hash, role)
                     VALUES (:name, :email, :password_hash, :role)'
                );

                $insert->execute([
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => $passwordHash,
                    'role' => 'student',
                ]);

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header('Location: /campusconnect/login.php?registered=1');
                exit;
            } catch (PDOException $e) {
                error_log('CampusConnect registration failed: ' . $e->getMessage());
                $errors[] = 'Unable to create your account right now. Please try again.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';

function registerEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-6 col-xl-5">

            <div class="cc-card p-4 p-md-5 my-md-4">
                <div class="text-center mb-4">
                    <span class="cc-brand-mark mx-auto mb-3" style="width: 48px; height: 48px; font-size: 1.4rem;">C</span>
                    <h1 class="h3 fw-bold mb-1">Create Student Account</h1>
                    <p class="text-secondary small">
                        Join CampusConnect to register for university events, track attendance, and explore opportunities.
                    </p>
                </div>

                <?php if ($errors): ?>
                    <div class="alert alert-danger mb-4" role="alert">
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $error): ?>
                                <li><?= registerEscape($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="/campusconnect/register.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= registerEscape($_SESSION['csrf_token']) ?>">

                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Full name
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control"
                            placeholder="e.g. Maya Chen"
                            maxlength="100"
                            autocomplete="name"
                            value="<?= registerEscape($name) ?>"
                            required
                        >
                        <div class="form-text small text-muted">
                            As you want it to appear on registration lists and certificates.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Email address
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="student@university.edu"
                            maxlength="150"
                            autocomplete="email"
                            value="<?= registerEscape($email) ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label mb-0">
                                Password
                            </label>
                            <button
                                type="button"
                                class="btn btn-link p-0 text-decoration-none small"
                                style="color: var(--cc-primary); font-size: 0.8rem;"
                                id="toggleRegisterPasswordBtn"
                            >
                                Show password
                            </button>
                        </div>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="At least 8 characters"
                            minlength="8"
                            maxlength="72"
                            autocomplete="new-password"
                            required
                        >
                        <div class="form-text small text-muted">
                            Minimum 8 characters. Keep it secure and unique.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">
                            Confirm password
                        </label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            placeholder="Re-enter your password"
                            minlength="8"
                            maxlength="72"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn cc-btn-primary w-100 py-2">
                        Create Account & Get Started
                    </button>
                </form>

                <div class="pt-4 mt-4 text-center border-top" style="border-color: var(--cc-border) !important;">
                    <p class="text-secondary small mb-0">
                        Already have an account?
                        <a href="/campusconnect/login.php" class="fw-bold">
                            Sign in here
                        </a>
                    </p>
                </div>
            </div>

        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggleRegisterPasswordBtn');
    const pwd = document.getElementById('password');
    const confirmPwd = document.getElementById('confirm_password');

    if (toggleBtn && pwd && confirmPwd) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = pwd.getAttribute('type') === 'password';
            pwd.setAttribute('type', isPassword ? 'text' : 'password');
            confirmPwd.setAttribute('type', isPassword ? 'text' : 'password');
            toggleBtn.textContent = isPassword ? 'Hide password' : 'Show password';
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

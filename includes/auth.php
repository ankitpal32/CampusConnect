
<?php

// Start a session if one is not already active.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Check whether a user is logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user']['id'])
        && is_array($_SESSION['user']);
}

/**
 * Require an authenticated user.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /campusconnect/login.php');
        exit;
    }
}

/**
 * Require an authenticated administrator.
 */
function requireAdmin(): void
{
    requireLogin();

    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('403 Forbidden: Administrator access required.');
    }
}

/**
 * Generate or retrieve the session CSRF token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Validate a submitted CSRF token.
 */
function verifyCsrfToken(?string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

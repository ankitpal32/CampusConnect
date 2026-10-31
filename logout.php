
<?php

require_once __DIR__ . '/includes/auth.php';

// Logout must use POST, not a normal GET link.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed. Use the logout form.');
}

$token = $_POST['csrf_token'] ?? null;

if (!verifyCsrfToken(is_string($token) ? $token : null)) {
    http_response_code(403);
    exit('Invalid security token. Refresh the page and try again.');
}

// Remove authenticated session data.
$_SESSION = [];

// Expire the session cookie using its current configuration.
if (ini_get('session.use_cookies')) {
    $cookieParams = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $cookieParams['path'],
            'domain' => $cookieParams['domain'],
            'secure' => $cookieParams['secure'],
            'httponly' => $cookieParams['httponly'],
            'samesite' => $cookieParams['samesite'] ?? 'Lax',
        ]
    );
}

session_destroy();

header('Location: /campusconnect/index.php?logged_out=1');
exit;

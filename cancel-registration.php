
<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if (($_SESSION['user']['role'] ?? '') !== 'student') {
    http_response_code(403);
    exit('Only students can cancel their registrations.');
}

$token = $_POST['csrf_token'] ?? null;

if (
    !is_string($token) ||
    !verifyCsrfToken($token)
) {
    http_response_code(403);
    exit('Invalid security token. Refresh and try again.');
}

$registrationId = filter_input(
    INPUT_POST,
    'registration_id',
    FILTER_VALIDATE_INT
);

if (!$registrationId || $registrationId < 1) {
    http_response_code(400);
    exit('Invalid registration ID.');
}

$userId = (int) $_SESSION['user']['id'];

try {
    // Only the logged-in student's registration can be changed.
    $stmt = $pdo->prepare(
        "UPDATE registrations
         SET status = 'cancelled'
         WHERE id = :registration_id
           AND user_id = :user_id
           AND status = 'registered'
           AND EXISTS (
               SELECT 1
               FROM events e
               WHERE e.id = registrations.event_id
                 AND e.status = 'published'
                 AND e.event_date > NOW()
           )"
    );

    $stmt->execute([
        'registration_id' => $registrationId,
        'user_id' => $userId,
    ]);

    if ($stmt->rowCount() === 1) {
        $_SESSION['registration_message'] =
            'Your registration was cancelled successfully.';
    } else {
        $_SESSION['registration_message'] =
            'The registration could not be cancelled. It may already be cancelled, belong to another account, or relate to an event that is no longer eligible for cancellation.';
    }

    header('Location: /campusconnect/my-registrations.php');
    exit;

} catch (Throwable $e) {
    error_log('CampusConnect cancellation failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Cancellation could not be completed. Please try again.');
}

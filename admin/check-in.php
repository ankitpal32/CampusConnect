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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

$token = $_POST['csrf_token'] ?? null;
if (!is_string($token) || !verifyCsrfToken($token)) {
    http_response_code(403);
    exit('Invalid security token. Please refresh the page and try again.');
}

$registrationId = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$action = trim((string) ($_POST['action'] ?? 'checkin'));

if (!$registrationId || $registrationId < 1 || !$eventId || $eventId < 1) {
    http_response_code(400);
    exit('Invalid registration or event ID.');
}

try {
    // Verify the registration exists and belongs to the given event
    $verifyStmt = $pdo->prepare("
        SELECT r.id, r.status, u.name
        FROM registrations r
        INNER JOIN users u ON u.id = r.user_id
        WHERE r.id = :reg_id AND r.event_id = :event_id
        LIMIT 1
    ");
    $verifyStmt->execute([
        'reg_id' => $registrationId,
        'event_id' => $eventId,
    ]);
    $reg = $verifyStmt->fetch();

    if (!$reg) {
        throw new RuntimeException('Registration record not found for this event.');
    }

    if ($action === 'checkin') {
        if ($reg['status'] !== 'registered') {
            throw new RuntimeException('Cannot check in a cancelled registration.');
        }

        $checkInStmt = $pdo->prepare("
            INSERT INTO attendance (registration_id, checked_in_at)
            VALUES (:reg_id, NOW())
            ON DUPLICATE KEY UPDATE checked_in_at = NOW()
        ");
        $checkInStmt->execute(['reg_id' => $registrationId]);

        $_SESSION['attendee_message'] = 'Checked in student ' . $reg['name'] . ' successfully.';
    } elseif ($action === 'undo') {
        $undoStmt = $pdo->prepare("DELETE FROM attendance WHERE registration_id = :reg_id");
        $undoStmt->execute(['reg_id' => $registrationId]);

        $_SESSION['attendee_message'] = 'Check-in removed for student ' . $reg['name'] . '.';
    } else {
        throw new RuntimeException('Unknown attendance action.');
    }

    header('Location: /campusconnect/admin/attendees.php?event_id=' . $eventId);
    exit;

} catch (RuntimeException $e) {
    $_SESSION['attendee_error'] = $e->getMessage();
    header('Location: /campusconnect/admin/attendees.php?event_id=' . $eventId);
    exit;
} catch (Throwable $e) {
    error_log('CampusConnect attendance check-in error: ' . $e->getMessage());
    $_SESSION['attendee_error'] = 'An error occurred while updating attendance. Please try again.';
    header('Location: /campusconnect/admin/attendees.php?event_id=' . $eventId);
    exit;
}


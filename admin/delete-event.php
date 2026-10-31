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

$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
if (!$eventId || $eventId < 1) {
    http_response_code(400);
    exit('Invalid event ID.');
}

try {
    $pdo->beginTransaction();

    // Lock event row
    $lockStmt = $pdo->prepare('SELECT id, title, status FROM events WHERE id = :id FOR UPDATE');
    $lockStmt->execute(['id' => $eventId]);
    $event = $lockStmt->fetch();

    if (!$event) {
        throw new RuntimeException('Event not found or has already been removed.');
    }

    // Check count of all registrations (both registered and cancelled)
    $regCountStmt = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE event_id = :id');
    $regCountStmt->execute(['id' => $eventId]);
    $totalRegistrations = (int) $regCountStmt->fetchColumn();

    if ($totalRegistrations === 0) {
        // Safe hard delete: No registrations exist, safe to permanently delete from MySQL
        $deleteStmt = $pdo->prepare('DELETE FROM events WHERE id = :id');
        $deleteStmt->execute(['id' => $eventId]);

        $pdo->commit();
        $_SESSION['admin_message'] = 'Event "' . $event['title'] . '" was permanently deleted.';
    } else {
        // Safe archive/cancellation: Registrations exist; preserve audit integrity
        $cancelStmt = $pdo->prepare("UPDATE events SET status = 'cancelled' WHERE id = :id");
        $cancelStmt->execute(['id' => $eventId]);

        $pdo->commit();
        $_SESSION['admin_message'] = 'Event "' . $event['title'] . '" has ' . $totalRegistrations . ' associated student registration(s). To protect student records, the event was marked as Cancelled rather than deleted.';
    }

    header('Location: /campusconnect/admin/events.php');
    exit;

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['admin_error'] = $e->getMessage();
    header('Location: /campusconnect/admin/events.php');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('CampusConnect event deletion error: ' . $e->getMessage());
    $_SESSION['admin_error'] = 'An unexpected error occurred while deleting the event. Please try again.';
    header('Location: /campusconnect/admin/events.php');
    exit;
}

<?php

require_once __DIR__ . '/includes/auth.php';
requireLogin();

require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

$token = $_POST['csrf_token'] ?? null;

if (!verifyCsrfToken(is_string($token) ? $token : null)) {
    http_response_code(403);
    exit('Invalid security token. Refresh the event page and try again.');
}

$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

if (!$eventId || $eventId < 1) {
    http_response_code(400);
    exit('Invalid event ID.');
}

$userId = (int) $_SESSION['user']['id'];

try {
    $pdo->beginTransaction();

    // Lock the event row while checking capacity.
    $eventStmt = $pdo->prepare(
        "SELECT id, capacity, registration_deadline, event_date
         FROM events
         WHERE id = :id
           AND status = 'published'
         FOR UPDATE"
    );

    $eventStmt->execute(['id' => $eventId]);
    $event = $eventStmt->fetch();

    if (!$event) {
        throw new RuntimeException(
            'This event is unavailable for registration.'
        );
    }

    if (
        strtotime($event['registration_deadline']) <= time()
        || strtotime($event['event_date']) <= time()
    ) {
        throw new RuntimeException(
            'Registration for this event has closed.'
        );
    }

    // Check whether this student already has a registration.
    $existingStmt = $pdo->prepare(
        'SELECT id, status
         FROM registrations
         WHERE user_id = :user_id
           AND event_id = :event_id
         LIMIT 1'
    );

    $existingStmt->execute([
        'user_id' => $userId,
        'event_id' => $eventId,
    ]);

    $existing = $existingStmt->fetch();

    if ($existing && $existing['status'] === 'registered') {
        throw new RuntimeException(
            'You are already registered for this event.'
        );
    }

    // Count active registrations while the event row is locked.
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM registrations
         WHERE event_id = :event_id
           AND status = 'registered'"
    );

    $countStmt->execute(['event_id' => $eventId]);
    $registeredCount = (int) $countStmt->fetchColumn();

    if ($registeredCount >= (int) $event['capacity']) {
        throw new RuntimeException(
            'Sorry, this event is full.'
        );
    }

    if ($existing) {
        // Reuse the cancelled registration record.
        $restoreStmt = $pdo->prepare(
            "UPDATE registrations
             SET status = 'registered',
                 registered_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND status = 'cancelled'"
        );

        $restoreStmt->execute(['id' => $existing['id']]);
    } else {
        $insertStmt = $pdo->prepare(
            "INSERT INTO registrations (user_id, event_id, status)
             VALUES (:user_id, :event_id, 'registered')"
        );

        $insertStmt->execute([
            'user_id' => $userId,
            'event_id' => $eventId,
        ]);
    }

    $pdo->commit();

    header(
        'Location: /campusconnect/my-registrations.php?registered=1'
    );
    exit;

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['registration_error'] = $e->getMessage();

    header(
        'Location: /campusconnect/event-details.php?id='
        . $eventId
        . '&registration=failed'
    );
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('CampusConnect registration error: ' . $e->getMessage());

    http_response_code(500);
    exit('Registration could not be completed. Please try again.');
}

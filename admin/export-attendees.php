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

$eventId = filter_var($_GET['event_id'] ?? null, FILTER_VALIDATE_INT);
if (!$eventId || $eventId < 1) {
    http_response_code(400);
    exit('Invalid event ID.');
}

// Fetch event details
$eventStmt = $pdo->prepare('SELECT id, title, event_date, venue FROM events WHERE id = :id LIMIT 1');
$eventStmt->execute(['id' => $eventId]);
$event = $eventStmt->fetch();

if (!$event) {
    http_response_code(404);
    exit('Event not found.');
}

// Fetch attendees with attendance info
$stmt = $pdo->prepare("
    SELECT
        u.name,
        u.email,
        r.registered_at,
        r.status AS registration_status,
        a.checked_in_at
    FROM registrations r
    INNER JOIN users u ON u.id = r.user_id
    LEFT JOIN attendance a ON a.registration_id = r.id
    WHERE r.event_id = :event_id
    ORDER BY r.registered_at ASC
");
$stmt->execute(['event_id' => $eventId]);
$attendees = $stmt->fetchAll();

// Clean filename slug
$cleanTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($event['title']));
$filename = 'attendees_' . $eventId . '_' . substr($cleanTitle, 0, 30) . '_' . date('Ymd') . '.csv';

// Send CSV download headers
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// CSV Header
fputcsv($output, [
    'Student Name',
    'Email Address',
    'Registration Date',
    'Booking Status',
    'Attendance Status',
    'Checked-In Timestamp'
]);

// Write attendee rows
foreach ($attendees as $row) {
    $isReg = ($row['registration_status'] === 'registered');
    $isCheckedIn = !empty($row['checked_in_at']);

    $bookingStatus = $isReg ? 'Confirmed' : 'Cancelled';
    $attendanceStatus = $isCheckedIn ? 'Checked In (Present)' : ($isReg ? 'Not Checked In' : 'N/A');
    $checkInTime = $isCheckedIn ? date('Y-m-d H:i:s', strtotime($row['checked_in_at'])) : '';

    fputcsv($output, [
        $row['name'],
        $row['email'],
        date('Y-m-d H:i:s', strtotime($row['registered_at'])),
        $bookingStatus,
        $attendanceStatus,
        $checkInTime
    ]);
}

fclose($output);
exit;


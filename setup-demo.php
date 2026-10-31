<?php

require_once __DIR__ . '/config/database.php';

// Safe execution check: local CLI or local web environment only
$isCli = (PHP_SAPI === 'cli');
$isLocalWeb = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost'], true);

if (!$isCli && !$isLocalWeb) {
    http_response_code(403);
    exit('Demo setup is restricted to local/development environments.');
}

header('Content-Type: text/plain; charset=utf-8');
echo "=== CampusConnect Demo Environment Setup ===\n\n";

try {
    $pdo->beginTransaction();

    // 1. Setup Demo Admin
    $adminEmail = 'demo.admin@campusconnect.edu';
    $adminPassword = 'AdminDemo@2026';
    $adminName = 'Campus Coordinator (Demo Admin)';

    $checkAdmin = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $checkAdmin->execute(['email' => $adminEmail]);
    $adminId = $checkAdmin->fetchColumn();

    if ($adminId) {
        echo "[INFO] Demo Admin already exists (ID: $adminId).\n";
    } else {
        $insertAdmin = $pdo->prepare("
            INSERT INTO users (name, email, password_hash, role)
            VALUES (:name, :email, :password_hash, 'admin')
        ");
        $insertAdmin->execute([
            'name' => $adminName,
            'email' => $adminEmail,
            'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
        ]);
        $adminId = (int) $pdo->lastInsertId();
        echo "[CREATED] Demo Admin account created (ID: $adminId, Email: $adminEmail)\n";
    }

    // 2. Setup Demo Student
    $studentEmail = 'demo.student@campusconnect.edu';
    $studentPassword = 'StudentDemo@2026';
    $studentName = 'Alex Rivera (Demo Student)';

    $checkStudent = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $checkStudent->execute(['email' => $studentEmail]);
    $studentId = $checkStudent->fetchColumn();

    if ($studentId) {
        echo "[INFO] Demo Student already exists (ID: $studentId).\n";
    } else {
        $insertStudent = $pdo->prepare("
            INSERT INTO users (name, email, password_hash, role)
            VALUES (:name, :email, :password_hash, 'student')
        ");
        $insertStudent->execute([
            'name' => $studentName,
            'email' => $studentEmail,
            'password_hash' => password_hash($studentPassword, PASSWORD_DEFAULT),
        ]);
        $studentId = (int) $pdo->lastInsertId();
        echo "[CREATED] Demo Student account created (ID: $studentId, Email: $studentEmail)\n";
    }

    // 3. Setup Realistic Demo Events
    $demoEvents = [
        [
            'title' => 'Annual Autonomous Robotics Hackathon',
            'description' => "A 24-hour collegiate robotics design competition. Teams will construct autonomous navigation robots to solve complex obstacle courses. Hardware kits and sensor modules provided at check-in.\n\nOpen to all engineering and computer science students. Lunch and refreshments included.",
            'category' => 'Technology',
            'venue' => 'Robotics & Automation Lab (Room 302)',
            'event_date' => date('Y-m-d 09:00:00', strtotime('+12 days')),
            'registration_deadline' => date('Y-m-d 23:59:00', strtotime('+10 days')),
            'capacity' => 40,
            'status' => 'published',
        ],
        [
            'title' => 'Full-Stack Web Development Bootcamp',
            'description' => "Hands-on intensive workshop focusing on modern server-side development, relational database modeling, and application security.\n\nPrerequisites: Basic HTML, CSS, and familiarity with programming logic. Bring a laptop with local development server installed.",
            'category' => 'Workshop',
            'venue' => 'Main Computing Center (Hall B)',
            'event_date' => date('Y-m-d 14:00:00', strtotime('+18 days')),
            'registration_deadline' => date('Y-m-d 18:00:00', strtotime('+16 days')),
            'capacity' => 35,
            'status' => 'published',
        ],
        [
            'title' => 'Inter-Department Basketball Championship',
            'description' => "The annual campus tournament showcasing teams from Engineering, Arts, Business, and Science faculties.\n\nCome cheer for your department in the quarter-final matchups. Student ID required for admission.",
            'category' => 'Sports',
            'venue' => 'University Indoor Sports Complex',
            'event_date' => date('Y-m-d 17:00:00', strtotime('+24 days')),
            'registration_deadline' => date('Y-m-d 12:00:00', strtotime('+23 days')),
            'capacity' => 80,
            'status' => 'published',
        ],
        [
            'title' => 'Spring Music & Performing Arts Gala',
            'description' => "An evening celebrating student acoustic bands, classical vocalists, and dramatic stage performances organized by the Campus Cultural Committee.",
            'category' => 'Cultural',
            'venue' => 'University Grand Auditorium',
            'event_date' => date('Y-m-d 18:30:00', strtotime('+30 days')),
            'registration_deadline' => date('Y-m-d 15:00:00', strtotime('+29 days')),
            'capacity' => 120,
            'status' => 'published',
        ],
    ];

    $createdEventIds = [];
    foreach ($demoEvents as $ev) {
        $checkEv = $pdo->prepare('SELECT id FROM events WHERE title = :title LIMIT 1');
        $checkEv->execute(['title' => $ev['title']]);
        $existingEvId = $checkEv->fetchColumn();

        if ($existingEvId) {
            echo "[INFO] Event already exists: \"{$ev['title']}\" (ID: $existingEvId)\n";
            $createdEventIds[] = (int) $existingEvId;
        } else {
            $insertEv = $pdo->prepare("
                INSERT INTO events (title, description, category, venue, event_date, registration_deadline, capacity, status, created_by)
                VALUES (:title, :description, :category, :venue, :event_date, :registration_deadline, :capacity, :status, :created_by)
            ");
            $insertEv->execute([
                'title' => $ev['title'],
                'description' => $ev['description'],
                'category' => $ev['category'],
                'venue' => $ev['venue'],
                'event_date' => $ev['event_date'],
                'registration_deadline' => $ev['registration_deadline'],
                'capacity' => $ev['capacity'],
                'status' => $ev['status'],
                'created_by' => $adminId,
            ]);
            $newId = (int) $pdo->lastInsertId();
            echo "[CREATED] Event added: \"{$ev['title']}\" (ID: $newId)\n";
            $createdEventIds[] = $newId;
        }
    }

    // 4. Register Demo Student for first two events so the student experience is populated
    if (!empty($createdEventIds) && $studentId) {
        foreach (array_slice($createdEventIds, 0, 2) as $regEvId) {
            $checkReg = $pdo->prepare('SELECT id, status FROM registrations WHERE user_id = :uid AND event_id = :eid LIMIT 1');
            $checkReg->execute(['uid' => $studentId, 'eid' => $regEvId]);
            $existingReg = $checkReg->fetch();

            if ($existingReg) {
                if ($existingReg['status'] !== 'registered') {
                    $pdo->prepare("UPDATE registrations SET status = 'registered' WHERE id = :id")->execute(['id' => $existingReg['id']]);
                    echo "[UPDATED] Restored active registration for Student ID $studentId in Event ID $regEvId\n";
                } else {
                    echo "[INFO] Registration already active for Student ID $studentId in Event ID $regEvId\n";
                }
            } else {
                $insertReg = $pdo->prepare("
                    INSERT INTO registrations (user_id, event_id, status)
                    VALUES (:uid, :eid, 'registered')
                ");
                $insertReg->execute(['uid' => $studentId, 'eid' => $regEvId]);
                echo "[CREATED] Registered Student ID $studentId for Event ID $regEvId\n";
            }
        }
    }

    $pdo->commit();

    echo "\n=== Setup Completed Successfully ===\n";
    echo "Demo Admin Credentials:\n";
    echo "  Email:    $adminEmail\n";
    echo "  Password: $adminPassword\n\n";
    echo "Demo Student Credentials:\n";
    echo "  Email:    $studentEmail\n";
    echo "  Password: $studentPassword\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERROR] Setup failed: " . $e->getMessage() . "\n";
    exit(1);
}

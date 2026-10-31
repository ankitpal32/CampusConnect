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
echo "=== CampusConnect (SKFGI Mankundu) Demo Seed Setup ===\n\n";

try {
    $pdo->beginTransaction();

    // 1. Setup Demo Admin
    $adminEmail = 'demo.admin@campusconnect.edu';
    $adminPassword = 'AdminDemo@2026';
    $adminName = 'SKFGI Coordinator (Demo Admin)';

    $checkAdmin = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $checkAdmin->execute(['email' => $adminEmail]);
    $adminId = $checkAdmin->fetchColumn();

    if ($adminId) {
        echo "[INFO] Demo Admin account active (ID: $adminId, $adminEmail)\n";
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
        echo "[CREATED] Demo Admin account created (ID: $adminId, $adminEmail)\n";
    }

    // 2. Setup Demo Student
    $studentEmail = 'demo.student@campusconnect.edu';
    $studentPassword = 'StudentDemo@2026';
    $studentName = 'Demo Student (SKFGI)';

    $checkStudent = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $checkStudent->execute(['email' => $studentEmail]);
    $studentId = $checkStudent->fetchColumn();

    if ($studentId) {
        echo "[INFO] Demo Student account active (ID: $studentId, $studentEmail)\n";
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
        echo "[CREATED] Demo Student account created (ID: $studentId, $studentEmail)\n";
    }

    // 3. Setup the 8 SKFGI-Contextual Demo Events
    $skfgiEvents = [
        [
            'title' => '[DEMO] Student Coding and Problem-Solving Workshop',
            'description' => "A practical hands-on problem-solving session designed for computer science and engineering students. Topics include core data structures, algorithmic efficiency, and live code challenges.\n\nDemonstration notice: This is sample data created for testing CampusConnect functionality at Supreme Knowledge Foundation Group of Institutions (SKFGI).",
            'category' => 'Technology',
            'venue' => 'SKFGI Main Computing Lab (Demo Venue)',
            'event_date' => date('Y-m-d 10:00:00', strtotime('+7 days')),
            'registration_deadline' => date('Y-m-d 18:00:00', strtotime('+5 days')),
            'capacity' => 40,
            'status' => 'published',
        ],
        [
            'title' => '[DEMO] BCA and Computing Student Project Showcase',
            'description' => "An interactive peer exhibition where final-year and pre-final year BCA and computing students demonstrate capstone software projects, web applications, and database architectures.\n\nDemonstration notice: Sample student showcase event for SKFGI CampusConnect MVP evaluation.",
            'category' => 'Education',
            'venue' => 'SKFGI Seminar Hall A (Demo Venue)',
            'event_date' => date('Y-m-d 11:30:00', strtotime('+12 days')),
            'registration_deadline' => date('Y-m-d 17:00:00', strtotime('+10 days')),
            'capacity' => 50,
            'status' => 'published',
        ],
        [
            'title' => '[DEMO] Engineering Innovation and Project Exhibition',
            'description' => "Inter-disciplinary hardware and software showcase featuring automated micro-controllers, IoT prototypes, and mechanical engineering capstone demonstrations.\n\nDemonstration notice: Sample collegiate exhibition event at SKFGI Mankundu campus.",
            'category' => 'Technology',
            'venue' => 'SKFGI Central Workshop Complex (Demo Venue)',
            'event_date' => date('Y-m-d 10:30:00', strtotime('+16 days')),
            'registration_deadline' => date('Y-m-d 16:00:00', strtotime('+14 days')),
            'capacity' => 60,
            'status' => 'published',
        ],
        [
            'title' => '[DEMO] Career Development and Resume Workshop',
            'description' => "Practical guidance on resume building, technical interview preparation, and off-campus opportunity navigation organized for pre-final and final year students affiliated with MAKAUT curriculum.\n\nDemonstration notice: Sample career development event for testing CampusConnect.",
            'category' => 'Workshop',
            'venue' => 'SKFGI Placement & Training Cell (Demo Venue)',
            'event_date' => date('Y-m-d 14:00:00', strtotime('+20 days')),
            'registration_deadline' => date('Y-m-d 12:00:00', strtotime('+18 days')),
            'capacity' => 45,
            'status' => 'published',
        ],
        [
            'title' => '[DEMO] Student Entrepreneurship and Startup Ideas',
            'description' => "Interactive discussion round on conceptualizing campus tech startups, MVP prototyping, and pitching innovative solutions for regional challenges in Hooghly and West Bengal.\n\nDemonstration notice: Sample student society session for demonstration purposes.",
            'category' => 'Workshop',
            'venue' => 'SKFGI Conference Room (Demo Venue)',
            'event_date' => date('Y-m-d 15:00:00', strtotime('+24 days')),
            'registration_deadline' => date('Y-m-d 12:00:00', strtotime('+22 days')),
            'capacity' => 35,
            'status' => 'published',
        ],
        [
            'title' => '[DEMO] Web Development and Digital Skills Workshop',
            'description' => "A guided tutorial session exploring Core PHP, relational MySQL database management, responsive Bootstrap layouts, and secure PDO authentication for college web development projects.\n\nDemonstration notice: Sample technical session for demonstration purposes at SKFGI.",
            'category' => 'Technology',
            'venue' => 'SKFGI Lab 2, Mankundu Campus (Demo Venue)',
            'event_date' => date('Y-m-d 10:00:00', strtotime('+28 days')),
            'registration_deadline' => date('Y-m-d 18:00:00', strtotime('+26 days')),
            'capacity' => 30,
            'status' => 'published',
        ],
        [
            'title' => '[DEMO] Campus Cultural and Creative Activities',
            'description' => "An open platform for musical performances, acoustic jams, poetry recitals, and creative arts organized by college student clubs.\n\nDemonstration notice: Sample cultural activity for evaluating event registrations at SKFGI.",
            'category' => 'Cultural',
            'venue' => 'SKFGI College Auditorium (Demo Venue)',
            'event_date' => date('Y-m-d 16:30:00', strtotime('+34 days')),
            'registration_deadline' => date('Y-m-d 12:00:00', strtotime('+32 days')),
            'capacity' => 100,
            'status' => 'published',
        ],
        [
            'title' => '[DEMO] Inter-Departmental Quiz Competition',
            'description' => "Multi-round quiz contest covering general science, Indian technological history, computing milestones, and current events. Teams will represent their respective academic departments.\n\nDemonstration notice: Sample quiz event for CampusConnect project demonstration.",
            'category' => 'Other',
            'venue' => 'SKFGI Multi-Purpose Hall (Demo Venue)',
            'event_date' => date('Y-m-d 13:30:00', strtotime('+38 days')),
            'registration_deadline' => date('Y-m-d 18:00:00', strtotime('+36 days')),
            'capacity' => 50,
            'status' => 'published',
        ],
    ];

    $seededEventMap = [];
    foreach ($skfgiEvents as $ev) {
        $checkEv = $pdo->prepare('SELECT id FROM events WHERE title = :title LIMIT 1');
        $checkEv->execute(['title' => $ev['title']]);
        $existingId = $checkEv->fetchColumn();

        if ($existingId) {
            // Update dates, venue, and descriptions so future dates stay fresh on repeated seed runs
            $updateEv = $pdo->prepare("
                UPDATE events
                SET description = :description,
                    category = :category,
                    venue = :venue,
                    event_date = :event_date,
                    registration_deadline = :registration_deadline,
                    capacity = :capacity,
                    status = :status
                WHERE id = :id
            ");
            $updateEv->execute([
                'description' => $ev['description'],
                'category' => $ev['category'],
                'venue' => $ev['venue'],
                'event_date' => $ev['event_date'],
                'registration_deadline' => $ev['registration_deadline'],
                'capacity' => $ev['capacity'],
                'status' => $ev['status'],
                'id' => $existingId,
            ]);
            $seededEventMap[$ev['title']] = (int) $existingId;
            echo "[UPDATED] Synced event: \"{$ev['title']}\" (ID: $existingId)\n";
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
            $seededEventMap[$ev['title']] = $newId;
            echo "[CREATED] Seeded event: \"{$ev['title']}\" (ID: $newId)\n";
        }
    }

    // 4. Seed Registrations for Demo Student
    // Event 1, Event 2, Event 4: Active registrations
    // Event 8: Cancelled registration (so student has realistic historical activity)
    $studentRegMap = [
        '[DEMO] Student Coding and Problem-Solving Workshop' => 'registered',
        '[DEMO] BCA and Computing Student Project Showcase' => 'registered',
        '[DEMO] Career Development and Resume Workshop' => 'registered',
        '[DEMO] Inter-Departmental Quiz Competition' => 'cancelled',
    ];

    $firstActiveRegId = null;

    foreach ($studentRegMap as $eventTitle => $targetStatus) {
        if (!isset($seededEventMap[$eventTitle])) {
            continue;
        }
        $eventId = $seededEventMap[$eventTitle];

        $checkReg = $pdo->prepare('SELECT id, status FROM registrations WHERE user_id = :uid AND event_id = :eid LIMIT 1');
        $checkReg->execute(['uid' => $studentId, 'eid' => $eventId]);
        $existingReg = $checkReg->fetch();

        if ($existingReg) {
            $regId = (int) $existingReg['id'];
            if ($existingReg['status'] !== $targetStatus) {
                $pdo->prepare('UPDATE registrations SET status = :status WHERE id = :id')->execute([
                    'status' => $targetStatus,
                    'id' => $regId,
                ]);
                echo "[UPDATED] Registration ID $regId set to '$targetStatus' for Event ID $eventId\n";
            } else {
                echo "[INFO] Registration already set to '$targetStatus' for Event ID $eventId\n";
            }
        } else {
            $insertReg = $pdo->prepare("
                INSERT INTO registrations (user_id, event_id, status)
                VALUES (:uid, :eid, :status)
            ");
            $insertReg->execute([
                'uid' => $studentId,
                'eid' => $eventId,
                'status' => $targetStatus,
            ]);
            $regId = (int) $pdo->lastInsertId();
            echo "[CREATED] Registration ID $regId created with status '$targetStatus' for Event ID $eventId\n";
        }

        if ($targetStatus === 'registered' && $firstActiveRegId === null) {
            $firstActiveRegId = $regId;
        }
    }

    // 5. Seed Attendance Check-in for Demo Student's First Event
    if ($firstActiveRegId) {
        $checkAtt = $pdo->prepare('SELECT id FROM attendance WHERE registration_id = :reg_id LIMIT 1');
        $checkAtt->execute(['reg_id' => $firstActiveRegId]);
        if (!$checkAtt->fetch()) {
            $insertAtt = $pdo->prepare("
                INSERT INTO attendance (registration_id, checked_in_at)
                VALUES (:reg_id, NOW())
            ");
            $insertAtt->execute(['reg_id' => $firstActiveRegId]);
            echo "[CREATED] Attendance check-in recorded for Registration ID $firstActiveRegId\n";
        } else {
            echo "[INFO] Attendance already recorded for Registration ID $firstActiveRegId\n";
        }
    }

    $pdo->commit();

    echo "\n=== Seed Process Finished Successfully ===\n";
    echo "SKFGI Mankundu events count: " . count($skfgiEvents) . "\n";
    echo "Demo Student ID: $studentId (Active Bookings + 1 Cancelled Record)\n";
    echo "Demo Admin ID:   $adminId (Owner of SKFGI demo events)\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERROR] Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}

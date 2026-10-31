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
echo "=== CampusConnect (SKFGI Mankundu) 6 Demo Events Seeder ===\n\n";

try {
    $pdo->beginTransaction();

    // 1. Setup Demo Admin Account
    $adminEmail = 'demo.admin@campusconnect.edu';
    $adminPassword = 'AdminDemo@2026';
    $adminName = 'SKFGI Coordinator (Demo Admin)';

    $checkAdmin = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $checkAdmin->execute(['email' => $adminEmail]);
    $adminId = $checkAdmin->fetchColumn();

    if ($adminId) {
        $adminId = (int) $adminId;
        echo "[INFO] Demo Admin active (ID: $adminId, $adminEmail)\n";
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
        echo "[CREATED] Demo Admin created (ID: $adminId, $adminEmail)\n";
    }

    // 2. Setup Demo Student Account
    $studentEmail = 'demo.student@campusconnect.edu';
    $studentPassword = 'StudentDemo@2026';
    $studentName = 'Alex Rivera (Demo Student)';

    $checkStudent = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $checkStudent->execute(['email' => $studentEmail]);
    $studentId = $checkStudent->fetchColumn();

    if ($studentId) {
        $studentId = (int) $studentId;
        echo "[INFO] Demo Student active (ID: $studentId, $studentEmail)\n";
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
        echo "[CREATED] Demo Student created (ID: $studentId, $studentEmail)\n";
    }

    // 3. Define the Exactly 6 SKFGI-Related Demo Events
    $targetEvents = [
        [
            'title' => 'Web Development Hands-on Workshop',
            'description' => "A practical laboratory session for engineering and computer application students focusing on full-stack web fundamentals. Students work through building dynamic applications using Core PHP, relational MySQL database integration, PDO security practices, and responsive Bootstrap interfaces. Participants should bring their college laptops with local server environments pre-installed.\n\nDemonstration notice: This event is simulated demo data created for demonstrating the CampusConnect college platform at Supreme Knowledge Foundation Group of Institutions (SKFGI), Mankundu. It is not an official college announcement.",
            'category' => 'Technology',
            'venue' => 'Computing Lab 2, SKFGI Mankundu Campus (Demo Venue)',
            'event_date' => date('Y-m-d 10:30:00', strtotime('+5 days')),
            'registration_deadline' => date('Y-m-d 18:00:00', strtotime('+3 days')),
            'capacity' => 35,
            'status' => 'published',
        ],
        [
            'title' => 'Introduction to AI & Data Science',
            'description' => "An introductory technical seminar covering foundational concepts in machine learning, data analysis with Python, and emerging applications of artificial intelligence in engineering disciplines. The session explores practical workflows, basic model training, and ethical computing considerations for undergraduate students.\n\nDemonstration notice: This event is simulated demo data created for demonstrating the CampusConnect college platform at Supreme Knowledge Foundation Group of Institutions (SKFGI), Mankundu. It is not an official college announcement.",
            'category' => 'Education',
            'venue' => 'Sir Visvesvaraya Seminar Hall, SKFGI (Demo Venue)',
            'event_date' => date('Y-m-d 11:00:00', strtotime('+10 days')),
            'registration_deadline' => date('Y-m-d 17:00:00', strtotime('+8 days')),
            'capacity' => 60,
            'status' => 'published',
        ],
        [
            'title' => 'Student Project Showcase',
            'description' => "An interactive peer exhibition where BCA, CSE, and engineering students present their semester mini-projects, capstone software architectures, and hardware prototypes. Open to all campus students to explore ongoing academic projects, exchange peer feedback, and discuss technical approaches.\n\nDemonstration notice: This event is simulated demo data created for demonstrating the CampusConnect college platform at Supreme Knowledge Foundation Group of Institutions (SKFGI), Mankundu. It is not an official college announcement.",
            'category' => 'Education',
            'venue' => 'Central Exhibition Hall, SKFGI Campus (Demo Venue)',
            'event_date' => date('Y-m-d 13:00:00', strtotime('+15 days')),
            'registration_deadline' => date('Y-m-d 16:00:00', strtotime('+12 days')),
            'capacity' => 50,
            'status' => 'published',
        ],
        [
            'title' => 'Coding & Problem-Solving Challenge',
            'description' => "A timed algorithmic problem-solving challenge for campus programming enthusiasts. Students tackle structured coding questions evaluating data structure selection, recursion, dynamic programming, and computational time complexity. Results and logic explanations are reviewed in an open discussion session following the contest.\n\nDemonstration notice: This event is simulated demo data created for demonstrating the CampusConnect college platform at Supreme Knowledge Foundation Group of Institutions (SKFGI), Mankundu. It is not an official college announcement.",
            'category' => 'Technology',
            'venue' => 'Software Development Lab 1, SKFGI (Demo Venue)',
            'event_date' => date('Y-m-d 14:00:00', strtotime('+19 days')),
            'registration_deadline' => date('Y-m-d 12:00:00', strtotime('+17 days')),
            'capacity' => 40,
            'status' => 'published',
        ],
        [
            'title' => 'Career Readiness & Resume Workshop',
            'description' => "A practical guidance session focused on technical resume formatting, industry portfolio preparation, GitHub project presentation, and common technical interview workflows for graduating students. Students will analyze real resume examples and practice answering technical behavioral questions.\n\nDemonstration notice: This event is simulated demo data created for demonstrating the CampusConnect college platform at Supreme Knowledge Foundation Group of Institutions (SKFGI), Mankundu. It is not an official college announcement.",
            'category' => 'Workshop',
            'venue' => 'Training & Placement Seminar Room, SKFGI (Demo Venue)',
            'event_date' => date('Y-m-d 14:30:00', strtotime('+24 days')),
            'registration_deadline' => date('Y-m-d 18:00:00', strtotime('+22 days')),
            'capacity' => 45,
            'status' => 'published',
        ],
        [
            'title' => 'Inter-Departmental Cultural Meetup',
            'description' => "A collaborative cultural and creative arts gathering bringing together students from all academic departments at SKFGI. Features acoustic music performances, poetry recitals, campus photography displays, and open mic segments organized by student cultural club members.\n\nDemonstration notice: This event is simulated demo data created for demonstrating the CampusConnect college platform at Supreme Knowledge Foundation Group of Institutions (SKFGI), Mankundu. It is not an official college announcement.",
            'category' => 'Cultural',
            'venue' => 'Main Campus Auditorium, SKFGI (Demo Venue)',
            'event_date' => date('Y-m-d 16:00:00', strtotime('+30 days')),
            'registration_deadline' => date('Y-m-d 12:00:00', strtotime('+28 days')),
            'capacity' => 100,
            'status' => 'published',
        ],
    ];

    $targetTitles = array_column($targetEvents, 'title');

    // 4. Safe Cleanup of Obsolete Demo Events
    // Only target events created by the demo admin ($adminId) or matching previous demo titles
    // NEVER touch events created by real users (e.g. created_by != $adminId)
    $allEventsStmt = $pdo->prepare('SELECT id, title, created_by FROM events WHERE created_by = :admin_id');
    $allEventsStmt->execute(['admin_id' => $adminId]);
    $seededEvents = $allEventsStmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\n--- Cleaning up obsolete demo events outside the 6 target events ---\n";
    foreach ($seededEvents as $sEvent) {
        if (!in_array($sEvent['title'], $targetTitles, true)) {
            $evId = (int) $sEvent['id'];
            $evTitle = $sEvent['title'];

            // Safely delete attendance check-ins associated with this demo event
            $delAttendance = $pdo->prepare("
                DELETE a FROM attendance a
                INNER JOIN registrations r ON r.id = a.registration_id
                WHERE r.event_id = :event_id
            ");
            $delAttendance->execute(['event_id' => $evId]);

            // Safely delete registrations associated with this demo event
            $delReg = $pdo->prepare('DELETE FROM registrations WHERE event_id = :event_id');
            $delReg->execute(['event_id' => $evId]);

            // Delete the demo event
            $delEv = $pdo->prepare('DELETE FROM events WHERE id = :event_id AND created_by = :admin_id');
            $delEv->execute(['event_id' => $evId, 'admin_id' => $adminId]);

            echo "[REMOVED] Obsolete demo event ID $evId: \"$evTitle\"\n";
        }
    }

    // Ensure real user events (created_by != $adminId) remain untouched and safe from deletion.
    // If they were practice items, set status to 'draft' so they don't pollute the 6 published demo events.
    $protectStmt = $pdo->prepare("
        UPDATE events
        SET status = 'draft'
        WHERE created_by != :admin_id
          AND status = 'published'
    ");
    $protectStmt->execute(['admin_id' => $adminId]);
    if ($protectStmt->rowCount() > 0) {
        echo "[PRESERVED] Set " . $protectStmt->rowCount() . " personal/test event(s) to 'draft' (records preserved, excluded from published catalog)\n";
    }

    // 5. Seed / Sync the 6 Target SKFGI Demo Events
    echo "\n--- Seeding/updating the 6 SKFGI demo events ---\n";
    $seededEventMap = [];

    foreach ($targetEvents as $ev) {
        $checkEv = $pdo->prepare('SELECT id FROM events WHERE title = :title LIMIT 1');
        $checkEv->execute(['title' => $ev['title']]);
        $existingId = $checkEv->fetchColumn();

        if ($existingId) {
            $existingId = (int) $existingId;
            $updateEv = $pdo->prepare("
                UPDATE events
                SET description = :description,
                    category = :category,
                    venue = :venue,
                    event_date = :event_date,
                    registration_deadline = :registration_deadline,
                    capacity = :capacity,
                    status = :status,
                    created_by = :created_by
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
                'created_by' => $adminId,
                'id' => $existingId,
            ]);
            $seededEventMap[$ev['title']] = $existingId;
            echo "[UPDATED] Synced demo event: \"{$ev['title']}\" (ID: $existingId)\n";
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
            echo "[CREATED] Inserted demo event: \"{$ev['title']}\" (ID: $newId)\n";
        }
    }

    // 6. Seed Demo Student Registrations
    // Event 1, Event 3, Event 5: Active confirmed registrations
    // Event 6: Cancelled registration (demonstrating student schedule history & cancellation flow)
    echo "\n--- Seeding Demo Student registrations ---\n";
    $studentRegMap = [
        'Web Development Hands-on Workshop' => 'registered',
        'Student Project Showcase' => 'registered',
        'Career Readiness & Resume Workshop' => 'registered',
        'Inter-Departmental Cultural Meetup' => 'cancelled',
    ];

    $webDevRegId = null;

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
                echo "[UPDATED] Demo Student registration ID $regId updated to '$targetStatus' for \"$eventTitle\"\n";
            } else {
                echo "[INFO] Demo Student registration ID $regId already '$targetStatus' for \"$eventTitle\"\n";
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
            echo "[CREATED] Demo Student registration ID $regId created with '$targetStatus' for \"$eventTitle\"\n";
        }

        if ($eventTitle === 'Web Development Hands-on Workshop') {
            $webDevRegId = $regId;
        }
    }

    // 7. Seed Door Attendance Check-in for Web Development Workshop
    if ($webDevRegId) {
        $checkAtt = $pdo->prepare('SELECT id FROM attendance WHERE registration_id = :reg_id LIMIT 1');
        $checkAtt->execute(['reg_id' => $webDevRegId]);
        if (!$checkAtt->fetch()) {
            $insertAtt = $pdo->prepare("
                INSERT INTO attendance (registration_id, checked_in_at)
                VALUES (:reg_id, NOW())
            ");
            $insertAtt->execute(['reg_id' => $webDevRegId]);
            echo "[CREATED] Attendance check-in recorded for Registration ID $webDevRegId\n";
        } else {
            echo "[INFO] Attendance check-in already recorded for Registration ID $webDevRegId\n";
        }
    }

    $pdo->commit();

    echo "\n=== Seeding Finished Successfully ===\n";
    echo "SKFGI Demo Events count: " . count($targetEvents) . "\n";
    echo "Demo Admin ID: $adminId ($adminEmail)\n";
    echo "Demo Student ID: $studentId ($studentEmail)\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERROR] Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}

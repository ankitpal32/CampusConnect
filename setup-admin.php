
<?php
require_once __DIR__ . '/config/database.php';

$email = 'YOUR_REGISTERED_EMAIL@example.com';

$stmt = $pdo->prepare(
    "UPDATE users
     SET role = 'admin'
     WHERE email = :email
       AND role = 'student'"
);

$stmt->execute(['email' => $email]);

if ($stmt->rowCount() === 1) {
    echo 'Your account is now an administrator.';
} else {
    echo 'No account was changed. Check the email and current role in phpMyAdmin.';
}

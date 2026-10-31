<?php

require_once __DIR__ . '/includes/auth.php';

echo 'Auth helper loaded successfully.<br>';

echo isLoggedIn()
    ? 'You are logged in.'
    : 'You are not logged in.';

echo '<br>';

echo 'CSRF token generated: '
    . (csrfToken() !== '' ? 'Yes' : 'No');

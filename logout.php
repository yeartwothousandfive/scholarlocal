<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$user = current_user();
if ($user) {
    log_audit((int) $user['id'], 'USER_LOGOUT', 'user', (int) $user['id'], 'Signed out');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

header('Location: ' . url('index.php'));
exit;

<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 8,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    $clean = ltrim($path, '/');
    return $clean === '' ? ($base ?: '/') : "{$base}/{$clean}";
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $cached = false;
    static $user = null;
    if ($cached) {
        return $user;
    }

    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id, full_name, student_id, program, email, role, account_status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $row = $stmt->fetch();

    if (!$row || $row['account_status'] !== 'active') {
        unset($_SESSION['user_id'], $_SESSION['user_role']);
        $cached = true;
        $user = null;
        return null;
    }

    $cached = true;
    $user = $row;
    return $user;
}

function is_admin(?array $user = null): bool
{
    $u = $user ?? current_user();
    return $u !== null && in_array($u['role'], ['admin', 'super_admin'], true);
}

function is_super_admin(?array $user = null): bool
{
    $u = $user ?? current_user();
    return $u !== null && $u['role'] === 'super_admin';
}

function require_login(string $redirectTo = 'login.php'): array
{
    $user = current_user();
    if (!$user) {
        header('Location: ' . url($redirectTo));
        exit;
    }
    return $user;
}

function require_admin(): array
{
    $user = current_user();
    if (!$user || !is_admin($user)) {
        header('Location: ' . url('admin/login.php'));
        exit;
    }
    return $user;
}

function is_valid_pup_email(string $email): bool
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    foreach (ALLOWED_EMAIL_DOMAINS as $domain) {
        if (str_ends_with($email, $domain)) {
            return true;
        }
    }
    return false;
}

function log_audit(int $userId, string $action, string $targetType = '', ?int $targetId = null, string $details = ''): void
{
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, target_type, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt->execute([$userId, $action, $targetType, $targetId, $details, $ip]);
    } catch (Throwable $ex) {
        // Non-fatal audit logging
    }
}

/**
 * Verifies a password and automatically upgrades seeded placeholder hashes to BCRYPT on first login.
 */
function verify_user_password(PDO $pdo, array $userRow, string $plainPassword): bool
{
    $stored = (string) ($userRow['password_hash'] ?? '');
    if (str_starts_with($stored, 'SEED:')) {
        $expected = substr($stored, 5);
        if (!hash_equals($expected, $plainPassword)) {
            return false;
        }
        $newHash = password_hash($plainPassword, PASSWORD_BCRYPT);
        $upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $upd->execute([$newHash, (int) $userRow['id']]);
        return true;
    }
    return password_verify($plainPassword, $stored);
}

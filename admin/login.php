<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

$user = current_user();
if ($user && is_admin($user)) {
    header('Location: ' . url('admin/index.php'));
    exit;
}

$pdo = get_db();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $message = 'Session expired. Please refresh and try again.';
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $found = $stmt->fetch();

        if (!$found || !verify_user_password($pdo, $found, $password)) {
            $message = 'Invalid administrator credentials.';
        } elseif (!in_array($found['role'], ['admin', 'super_admin'], true)) {
            $message = 'This account does not have repository administrator privileges.';
        } elseif ($found['account_status'] !== 'active') {
            $message = 'This administrator account is suspended.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $found['id'];
            $_SESSION['user_role'] = $found['role'];
            log_audit((int) $found['id'], 'ADMIN_LOGIN', 'user', (int) $found['id'], 'Signed in via staff portal');
            header('Location: ' . url('admin/index.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal · <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<main class="auth-main">
    <div class="watermark">
        <img class="seal seal-large" src="<?= e(url('assets/images/scholarlocal-logo.png')) ?>" alt="" aria-hidden="true">
    </div>
    <section class="auth-card">
        <a class="auth-brand" href="<?= e(url('index.php')) ?>">
            <img class="seal" src="<?= e(url('assets/images/scholarlocal-logo.png')) ?>" alt="ScholarLocal logo" width="48" height="48">
            <span>
                <strong>ScholarLocal</strong>
                <small>Repository Staff Portal</small>
            </span>
        </a>

        <div class="auth-heading">
            <p class="eyebrow maroon">ROLE-BASED ACCESS CONTROL</p>
            <h1>Administrator sign in</h1>
            <p>Authorized PUP ITECH repository staff and super administrators only.</p>
        </div>

        <form method="post" action="<?= e(url('admin/login.php')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>
                <span>Staff email</span>
                <div class="field">
                    <input name="email" type="email" autocomplete="email" placeholder="repo.admin@pup.edu.ph" required>
                </div>
            </label>
            <label>
                <span>Password</span>
                <div class="field">
                    <input name="password" type="password" autocomplete="current-password" placeholder="Enter password" required>
                </div>
            </label>

            <?php if ($message !== ''): ?>
                <p class="form-message" role="alert"><?= e($message) ?></p>
            <?php endif; ?>

            <button class="auth-submit" type="submit">Sign in to Admin Dashboard</button>
        </form>

        <div class="auth-links">
            <a href="<?= e(url('login.php')) ?>">Student &amp; Faculty login</a>
            <span>&middot;</span>
            <a href="<?= e(url('index.php')) ?>">Return to repository</a>
        </div>
    </section>
</main>
</body>
</html>

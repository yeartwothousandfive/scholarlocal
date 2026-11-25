<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$pdo = get_db();
$user = current_user();
$mode = ($_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';
$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $message = 'Session expired. Please refresh and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? 'login');

        if ($action === 'register') {
            $mode = 'register';
            $fullName = trim((string) ($_POST['full_name'] ?? ''));
            $studentId = strtoupper(trim((string) ($_POST['student_id'] ?? '')));
            $program = (string) ($_POST['program'] ?? '');
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $consent = !empty($_POST['privacy_consent']);

            if (mb_strlen($fullName) < 3 || mb_strlen($studentId) < 5) {
                $message = 'Enter your full name and valid PUP student ID.';
            } elseif (!array_key_exists($program, ITECH_PROGRAMS)) {
                $message = 'Select your diploma program.';
            } elseif (!is_valid_pup_email($email)) {
                $message = 'Use a valid PUP institutional email address (@iskolarngbayan.pup.edu.ph or @pup.edu.ph).';
            } elseif (strlen($password) < 8) {
                $message = 'Use a password with at least eight characters.';
            } elseif (!$consent) {
                $message = 'Please accept the Data Privacy Notice to create an account.';
            } else {
                $role = str_ends_with($email, '@pup.edu.ph') ? 'faculty' : 'student';
                $hash = password_hash($password, PASSWORD_BCRYPT);
                try {
                    $stmt = $pdo->prepare(
                        'INSERT INTO users (full_name, student_id, program, email, password_hash, role, account_status) VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$fullName, $studentId, $program, $email, $hash, $role, 'active']);
                    $newId = (int) $pdo->lastInsertId();
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $newId;
                    $_SESSION['user_role'] = $role;
                    log_audit($newId, 'USER_REGISTERED', 'user', $newId, "Registered {$role} account");
                    header('Location: ' . url('index.php'));
                    exit;
                } catch (PDOException $ex) {
                    $message = 'An account with that email or student ID already exists.';
                }
            }
        } else {
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');

            if ($email === '' || $password === '') {
                $message = 'Enter your institutional email and password.';
            } else {
                $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $found = $stmt->fetch();

                if (!$found || !verify_user_password($pdo, $found, $password)) {
                    $message = 'The email or password is incorrect.';
                } elseif ($found['account_status'] !== 'active') {
                    $message = 'This account is ' . e($found['account_status']) . '. Contact the repository office.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $found['id'];
                    $_SESSION['user_role'] = $found['role'];
                    log_audit((int) $found['id'], 'USER_LOGIN', 'user', (int) $found['id'], 'Signed in via student/faculty portal');
                    header('Location: ' . url('index.php'));
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $mode === 'register' ? 'Register' : 'Sign in' ?> · <?= e(APP_NAME) ?></title>
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
                <small>PUP Research Repository</small>
            </span>
        </a>

        <div class="auth-heading">
            <p class="eyebrow maroon">SECURE INSTITUTIONAL ACCESS</p>
            <h1><?= $user ? 'Account active' : ($mode === 'login' ? 'Welcome back' : 'Create your account') ?></h1>
            <p>
                <?= $user
                    ? 'Signed in as ' . e($user['full_name']) . '.'
                    : ($mode === 'login'
                        ? 'Sign in to view full PDFs and track download requests.'
                        : 'Register using your PUP institutional credentials.') ?>
            </p>
        </div>

        <?php if ($user): ?>
            <div class="signed-in-card">
                <div>
                    <strong><?= e($user['full_name']) ?></strong>
                    <span><?= e($user['student_id']) ?> &middot; <?= e($user['program']) ?> (<?= e(ucfirst($user['role'])) ?>)</span>
                    <small><?= e($user['email']) ?></small>
                </div>
                <?php if (is_admin($user)): ?>
                    <a class="auth-submit" href="<?= e(url('admin/index.php')) ?>">Open Admin Dashboard</a>
                <?php endif; ?>
                <a class="auth-submit" href="<?= e(url('logout.php')) ?>">Sign out</a>
            </div>
        <?php else: ?>
            <form method="post" action="<?= e(url('login.php?mode=' . $mode)) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="<?= e($mode) ?>">

                <?php if ($mode === 'register'): ?>
                    <label>
                        <span>Full name</span>
                        <div class="field">
                            <input name="full_name" autocomplete="name" placeholder="Juan M. Dela Cruz" required>
                        </div>
                    </label>
                    <label>
                        <span>Student / Faculty ID</span>
                        <div class="field">
                            <input name="student_id" placeholder="2024-00000-MN-0" required>
                        </div>
                    </label>
                    <label>
                        <span>Diploma program</span>
                        <div class="field">
                            <select name="program" required>
                                <option value="" disabled selected>Select your program</option>
                                <?php foreach (ITECH_PROGRAMS as $code => $label): ?>
                                    <option value="<?= e($code) ?>"><?= e($code) ?> — <?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </label>
                <?php endif; ?>

                <label>
                    <span>Institutional email</span>
                    <div class="field">
                        <input name="email" type="email" autocomplete="email" placeholder="name@iskolarngbayan.pup.edu.ph" required>
                    </div>
                </label>

                <label>
                    <span>Password</span>
                    <div class="field">
                        <input name="password" type="password" minlength="8" autocomplete="<?= $mode === 'login' ? 'current-password' : 'new-password' ?>" placeholder="At least 8 characters" required>
                    </div>
                </label>

                <div class="privacy-notice">
                    <div class="privacy-notice-heading">
                        <strong>Data Privacy Notice</strong>
                    </div>
                    <p>
                        In accordance with the Data Privacy Act of 2012 (Republic Act No. 10173), your
                        information is processed only for account verification, repository access, and
                        institutional research services.
                    </p>
                    <?php if ($mode === 'register'): ?>
                        <label class="privacy-consent">
                            <input type="checkbox" name="privacy_consent" value="1" required>
                            <span>I consent to this processing and will use repository documents for legitimate academic purposes.</span>
                        </label>
                    <?php endif; ?>
                </div>

                <?php if ($message !== ''): ?>
                    <p class="form-message <?= e($messageType) ?>" role="alert"><?= e($message) ?></p>
                <?php endif; ?>

                <button class="auth-submit" type="submit">
                    <?= $mode === 'login' ? 'Sign in to ScholarLocal' : 'Create account' ?>
                </button>
            </form>

            <div class="auth-links">
                <?php if ($mode === 'login'): ?>
                    <a href="<?= e(url('login.php?mode=register')) ?>">Register a new account</a>
                <?php else: ?>
                    <a href="<?= e(url('login.php?mode=login')) ?>">Return to sign in</a>
                <?php endif; ?>
                <span>&middot;</span>
                <a href="<?= e(url('admin/login.php')) ?>">Repository staff portal</a>
            </div>
        <?php endif; ?>

        <p class="security-note">Student sessions and repository staff access are kept separate.</p>
    </section>
</main>
</body>
</html>

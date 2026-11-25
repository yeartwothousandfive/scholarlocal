<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME . ' · ' . APP_SUBTITLE;
$activeNav = $activeNav ?? '';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="app-shell">
    <header class="site-header">
        <div class="topline"></div>
        <div class="nav-shell">
            <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="ScholarLocal home">
                <img class="seal" src="<?= e(url('assets/images/scholarlocal-logo.png')) ?>" alt="ScholarLocal research repository logo" width="48" height="48">
                <span class="brand-copy">
                    <strong>ScholarLocal</strong>
                    <small>PUP Institute of Technology</small>
                </span>
            </a>
            <nav aria-label="Primary navigation">
                <a class="nav-link <?= $activeNav === 'home' ? 'active' : '' ?>" href="<?= e(url('index.php')) ?>">Home</a>
                <a class="nav-link <?= $activeNav === 'browse' ? 'active' : '' ?>" href="<?= e(url('browse.php')) ?>">Browse All Studies</a>
                <a class="nav-link <?= $activeNav === 'about' ? 'active' : '' ?>" href="<?= e(url('about.php')) ?>">About</a>
                <?php if ($user && is_admin($user)): ?>
                    <a class="nav-link <?= $activeNav === 'admin' ? 'active' : '' ?>" href="<?= e(url('admin/index.php')) ?>">Admin Panel</a>
                <?php endif; ?>
                <?php if ($user): ?>
                    <a class="login-nav" href="<?= e(url('login.php')) ?>">
                        <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                        <?= e(explode(' ', $user['full_name'])[0]) ?>
                    </a>
                <?php else: ?>
                    <a class="login-nav" href="<?= e(url('login.php')) ?>">
                        <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                        Login
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

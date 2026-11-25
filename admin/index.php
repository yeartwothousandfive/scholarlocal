<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

$admin = require_admin();
$isSuper = is_super_admin($admin);
$pdo = get_db();

$section = (string) ($_GET['section'] ?? 'overview');
$allowedSections = $isSuper
    ? ['overview', 'requests', 'repository', 'users', 'settings']
    : ['overview', 'requests', 'repository'];
if (!in_array($section, $allowedSections, true)) {
    $section = 'overview';
}

$notice = '';
if (isset($_GET['notice'])) {
    $map = [
        'updated' => 'Download request status updated.',
        'saved'   => 'Research study saved and indexed for TF-IDF search.',
        'invalid' => 'Please complete all required fields.',
        'user_updated' => 'User account status updated.',
    ];
    $notice = $map[(string) $_GET['notice']] ?? '';
}

// Handle Super Admin user status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isSuper && ($_POST['action'] ?? '') === 'toggle_user') {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $newStatus = (string) ($_POST['account_status'] ?? 'active');
        if ($targetUserId > 0 && $targetUserId !== (int) $admin['id'] && in_array($newStatus, ['active', 'suspended'], true)) {
            $pdo->prepare('UPDATE users SET account_status = ? WHERE id = ?')->execute([$newStatus, $targetUserId]);
            log_audit((int) $admin['id'], 'USER_STATUS_' . strtoupper($newStatus), 'user', $targetUserId);
            header('Location: ' . url('admin/index.php?section=users&notice=user_updated'));
            exit;
        }
    }
}

$totalStudies = (int) $pdo->query('SELECT COUNT(*) FROM studies')->fetchColumn();
$availablePdfs = (int) $pdo->query("SELECT COUNT(*) FROM studies WHERE file_status = 'available'")->fetchColumn();
$pendingRequests = (int) $pdo->query("SELECT COUNT(*) FROM download_requests WHERE status = 'pending'")->fetchColumn();
$approvedRequests = (int) $pdo->query("SELECT COUNT(*) FROM download_requests WHERE status = 'approved'")->fetchColumn();
$deniedRequests = (int) $pdo->query("SELECT COUNT(*) FROM download_requests WHERE status = 'denied'")->fetchColumn();
$activeUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE account_status = 'active'")->fetchColumn();

$studies = $pdo->query('SELECT * FROM studies ORDER BY id DESC')->fetchAll();
$requests = $pdo->query(
    'SELECT dr.*, u.full_name, u.student_id, u.program FROM download_requests dr JOIN users u ON u.id = dr.user_id ORDER BY dr.id DESC'
)->fetchAll();
$usersList = $isSuper ? $pdo->query('SELECT id, full_name, student_id, program, email, role, account_status, created_at FROM users ORDER BY id DESC')->fetchAll() : [];
$settings = $isSuper ? $pdo->query('SELECT * FROM system_settings ORDER BY setting_key ASC')->fetchAll() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration · <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<main class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?= e(url('index.php')) ?>">
            <img class="seal" src="<?= e(url('assets/images/scholarlocal-logo.png')) ?>" alt="ScholarLocal logo" width="48" height="48">
            <span>
                <strong>ScholarLocal</strong>
                <small>Repository Administration</small>
            </span>
        </a>
        <div class="access-badge">
            <?= $isSuper ? 'SUPER ADMINISTRATOR' : 'CONTENT ADMINISTRATOR' ?>
        </div>
        <nav class="admin-menu" aria-label="Administration">
            <a class="<?= $section === 'overview' ? 'active' : '' ?>" href="<?= e(url('admin/index.php?section=overview')) ?>">Overview</a>
            <a class="<?= $section === 'requests' ? 'active' : '' ?>" href="<?= e(url('admin/index.php?section=requests')) ?>">
                Download requests
                <?php if ($pendingRequests > 0): ?>
                    <span class="menu-count"><?= $pendingRequests ?></span>
                <?php endif; ?>
            </a>
            <a class="<?= $section === 'repository' ? 'active' : '' ?>" href="<?= e(url('admin/index.php?section=repository')) ?>">Repository &amp; Upload</a>
            <?php if ($isSuper): ?>
                <a class="<?= $section === 'users' ? 'active' : '' ?>" href="<?= e(url('admin/index.php?section=users')) ?>">User management</a>
                <a class="<?= $section === 'settings' ? 'active' : '' ?>" href="<?= e(url('admin/index.php?section=settings')) ?>">System settings</a>
            <?php endif; ?>
        </nav>
        <a class="admin-signout" href="<?= e(url('logout.php')) ?>">Sign out</a>
    </aside>

    <section class="admin-content">
        <header class="admin-topbar">
            <div>
                <p class="eyebrow maroon"><?= $isSuper ? 'SYSTEM GOVERNANCE' : 'REPOSITORY MANAGEMENT' ?></p>
                <h1><?= e(ucfirst($section)) ?></h1>
            </div>
            <div class="admin-profile">
                <span>
                    <strong><?= e($admin['full_name']) ?></strong>
                    <small><?= $isSuper ? 'Super Admin' : 'Content Admin' ?></small>
                </span>
                <div><?= e(strtoupper(substr($admin['full_name'], 0, 2))) ?></div>
            </div>
        </header>

        <?php if ($notice !== ''): ?>
            <div class="admin-notice"><?= e($notice) ?></div>
        <?php endif; ?>

        <?php if ($section === 'overview'): ?>
            <div class="admin-welcome">
                <div>
                    <span><?= $isSuper ? 'Institute-wide access' : "Today's workspace" ?></span>
                    <h2><?= $isSuper ? 'Manage access with confidence.' : 'Keep PUP ITECH scholarship moving forward.' ?></h2>
                    <p>Review download requests, publish approved studies, and maintain the TF-IDF repository index.</p>
                </div>
                <a href="<?= e(url('admin/index.php?section=repository')) ?>">Add new study</a>
            </div>

            <div class="admin-stats">
                <article>
                    <span>Published studies</span>
                    <strong><?= $totalStudies ?></strong>
                    <small><?= $availablePdfs ?> with PDFs</small>
                </article>
                <article>
                    <span>Download requests</span>
                    <strong><?= $pendingRequests ?></strong>
                    <small>Awaiting review</small>
                </article>
                <article>
                    <span>Approved downloads</span>
                    <strong><?= $approvedRequests ?></strong>
                    <small>Granted access</small>
                </article>
                <article>
                    <span>Active accounts</span>
                    <strong><?= $activeUsers ?></strong>
                    <small>Verified PUP users</small>
                </article>
            </div>
        <?php endif; ?>

        <?php if ($section === 'overview' || $section === 'requests'): ?>
            <section class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow maroon">CONTROLLED PDF ACCESS</p>
                        <h2>Download requests</h2>
                    </div>
                </div>
                <div class="detail-summary">
                    <article><strong><?= $pendingRequests ?></strong><span>Awaiting review</span></article>
                    <article><strong><?= $approvedRequests ?></strong><span>Approved requests</span></article>
                    <article><strong><?= $deniedRequests ?></strong><span>Denied requests</span></article>
                </div>
                <div class="admin-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Requested document</th>
                                <th>Requester</th>
                                <th>Purpose</th>
                                <th>Requested</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr><td class="empty-table" colspan="6">No PDF download requests have been submitted yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($requests as $req): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($req['study_title']) ?></strong>
                                            <small><?= e($req['program']) ?></small>
                                        </td>
                                        <td>
                                            <strong><?= e($req['full_name']) ?></strong>
                                            <small><?= e($req['student_id']) ?></small>
                                        </td>
                                        <td><?= e($req['purpose']) ?></td>
                                        <td><?= e(date('M j, Y g:i A', strtotime((string) $req['created_at']))) ?></td>
                                        <td><span class="status <?= e($req['status']) ?>"><?= e($req['status']) ?></span></td>
                                        <td>
                                            <form class="request-actions" method="post" action="<?= e(url('api/download-request.php')) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="review">
                                                <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                                                <?php if ($req['status'] === 'pending'): ?>
                                                    <button class="approve-action" type="submit" name="status" value="approved">Approve</button>
                                                    <button class="deny-action" type="submit" name="status" value="denied">Deny</button>
                                                <?php else: ?>
                                                    <button type="submit" name="status" value="pending">Reopen</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($section === 'repository'): ?>
            <section class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow maroon">AUTOMATED TF-IDF INDEXING</p>
                        <h2>Publish new research study</h2>
                    </div>
                </div>
                <form class="admin-form-grid" method="post" action="<?= e(url('api/study-save.php')) ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <label class="full">
                        Study Title
                        <input name="title" type="text" required placeholder="Full approved capstone or thesis title">
                    </label>
                    <label>
                        Authors
                        <input name="authors" type="text" required placeholder="Surname, First M., ...">
                    </label>
                    <label>
                        Diploma Program
                        <select name="program" required>
                            <?php foreach (ITECH_PROGRAMS as $code => $label): ?>
                                <option value="<?= e($code) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Publication Year
                        <input name="year" type="number" min="1904" max="2100" value="<?= date('Y') ?>" required>
                    </label>
                    <label>
                        Keywords (comma-separated)
                        <input name="keywords" type="text" placeholder="TF-IDF, IoT, Machine Learning">
                    </label>
                    <label class="full">
                        Abstract
                        <textarea name="abstract" required placeholder="Enter the study abstract for TF-IDF indexing and preview..."></textarea>
                    </label>
                    <label class="full">
                        Full-Text PDF (optional, max 20 MB — text is automatically extracted for search)
                        <input name="pdf_file" type="file" accept="application/pdf">
                    </label>
                    <div class="full">
                        <button class="save-settings" type="submit">Publish &amp; Index Study</button>
                    </div>
                </form>
            </section>

            <section class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow maroon">PUBLISHED COLLECTION</p>
                        <h2>Repository records (<?= count($studies) ?>)</h2>
                    </div>
                </div>
                <div class="admin-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Repository record</th>
                                <th>Program</th>
                                <th>Year</th>
                                <th>File</th>
                                <th>Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($studies as $s): ?>
                                <tr>
                                    <td>
                                        <strong><a href="<?= e(url('study.php?id=' . (int) $s['id'])) ?>"><?= e($s['title']) ?></a></strong>
                                        <small><?= e($s['authors']) ?></small>
                                    </td>
                                    <td><?= e($s['program']) ?></td>
                                    <td><?= (int) $s['year'] ?></td>
                                    <td><span class="status <?= e($s['file_status']) ?>"><?= e($s['file_status']) ?></span></td>
                                    <td><?= (int) $s['view_count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($isSuper && $section === 'users'): ?>
            <section class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow maroon">ACCOUNT DIRECTORY</p>
                        <h2>Institute user &amp; staff accounts</h2>
                    </div>
                </div>
                <div class="admin-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Account ID</th>
                                <th>Program</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usersList as $u): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($u['full_name']) ?></strong>
                                        <small><?= e($u['email']) ?></small>
                                    </td>
                                    <td><?= e($u['student_id']) ?></td>
                                    <td><?= e($u['program']) ?></td>
                                    <td><?= e(ucfirst(str_replace('_', ' ', $u['role']))) ?></td>
                                    <td><span class="status <?= e($u['account_status']) ?>"><?= e($u['account_status']) ?></span></td>
                                    <td>
                                        <?php if ((int) $u['id'] !== (int) $admin['id']): ?>
                                            <form method="post" action="<?= e(url('admin/index.php?section=users')) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="toggle_user">
                                                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                                <input type="hidden" name="account_status" value="<?= $u['account_status'] === 'active' ? 'suspended' : 'active' ?>">
                                                <button type="submit"><?= $u['account_status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                                            </form>
                                        <?php else: ?>
                                            <span>Current</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($isSuper && $section === 'settings'): ?>
            <section class="admin-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow maroon">REPOSITORY CONFIGURATION</p>
                        <h2>System settings</h2>
                    </div>
                </div>
                <div class="settings-list">
                    <?php foreach ($settings as $set): ?>
                        <div class="setting-row">
                            <div>
                                <strong><?= e(ucwords(str_replace('_', ' ', $set['setting_key']))) ?></strong>
                                <small><?= e($set['description']) ?></small>
                            </div>
                            <span class="status <?= e($set['setting_value']) ?>"><?= e($set['setting_value']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </section>
</main>
</body>
</html>

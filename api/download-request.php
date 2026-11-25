<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid CSRF token.');
}

$pdo = get_db();
$user = require_login();
$action = (string) ($_POST['action'] ?? 'create');

if ($action === 'create') {
    $studyId = (int) ($_POST['study_id'] ?? 0);
    $purpose = trim((string) ($_POST['purpose'] ?? ''));

    if ($studyId <= 0 || mb_strlen($purpose) < 10) {
        header('Location: ' . url('study.php?id=' . $studyId . '&req=error'));
        exit;
    }

    $studyStmt = $pdo->prepare("SELECT id, title, file_status FROM studies WHERE id = ? AND review_status = 'approved' LIMIT 1");
    $studyStmt->execute([$studyId]);
    $study = $studyStmt->fetch();

    if (!$study || $study['file_status'] !== 'available') {
        header('Location: ' . url('study.php?id=' . $studyId . '&req=error'));
        exit;
    }

    // Prevent duplicate pending requests
    $dupStmt = $pdo->prepare("SELECT id FROM download_requests WHERE user_id = ? AND study_id = ? AND status = 'pending' LIMIT 1");
    $dupStmt->execute([(int) $user['id'], $studyId]);
    if ($dupStmt->fetch()) {
        header('Location: ' . url('study.php?id=' . $studyId . '&req=exists'));
        exit;
    }

    $ins = $pdo->prepare(
        'INSERT INTO download_requests (user_id, study_id, study_title, purpose, academic_use_accepted, status) VALUES (?, ?, ?, ?, 1, ?)'
    );
    $ins->execute([(int) $user['id'], $studyId, $study['title'], mb_substr($purpose, 0, 500), 'pending']);
    $reqId = (int) $pdo->lastInsertId();

    log_audit((int) $user['id'], 'DOWNLOAD_REQUEST_CREATED', 'download_request', $reqId, "Requested PDF for study #{$studyId}");
    header('Location: ' . url('study.php?id=' . $studyId . '&req=submitted'));
    exit;
}

if ($action === 'review') {
    if (!is_admin($user)) {
        http_response_code(403);
        exit('Administrator access required.');
    }

    $requestId = (int) ($_POST['request_id'] ?? 0);
    $newStatus = (string) ($_POST['status'] ?? '');
    $reviewNote = trim((string) ($_POST['review_note'] ?? ''));

    if ($requestId <= 0 || !in_array($newStatus, ['pending', 'approved', 'denied'], true)) {
        header('Location: ' . url('admin/index.php?section=requests&notice=invalid'));
        exit;
    }

    $upd = $pdo->prepare('UPDATE download_requests SET status = ?, review_note = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?');
    $upd->execute([$newStatus, $reviewNote, (int) $user['id'], $requestId]);

    log_audit((int) $user['id'], 'DOWNLOAD_REQUEST_' . strtoupper($newStatus), 'download_request', $requestId, $reviewNote);
    header('Location: ' . url('admin/index.php?section=requests&notice=updated'));
    exit;
}

http_response_code(400);
exit('Unknown action.');

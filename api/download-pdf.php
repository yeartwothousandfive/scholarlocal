<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';

$user = current_user();
if (!$user) {
    http_response_code(401);
    exit('Authentication required to download repository PDFs.');
}

$studyId = (int) ($_GET['study_id'] ?? 0);
$pdo = get_db();

$stmt = $pdo->prepare("SELECT * FROM studies WHERE id = ? AND review_status = 'approved' LIMIT 1");
$stmt->execute([$studyId]);
$study = $stmt->fetch();

if (!$study || empty($study['pdf_filename']) || $study['file_status'] !== 'available') {
    http_response_code(404);
    exit('The requested PDF is not available.');
}

// RBAC check: Admin/Super Admin can download directly; Students/Faculty require an approved download request
if (!is_admin($user)) {
    $reqStmt = $pdo->prepare("SELECT id FROM download_requests WHERE user_id = ? AND study_id = ? AND status = 'approved' ORDER BY id DESC LIMIT 1");
    $reqStmt->execute([(int) $user['id'], $studyId]);
    if (!$reqStmt->fetch()) {
        http_response_code(403);
        exit('An approved download request is required to download this PDF.');
    }
}

$safeFile = basename((string) $study['pdf_filename']);
$filePath = UPLOAD_DIR . '/' . $safeFile;

if (!is_file($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    exit('PDF file not found on server storage.');
}

log_audit((int) $user['id'], 'PDF_DOWNLOADED', 'study', $studyId, "Downloaded {$safeFile}");

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $safeFile . '"');
header('Content-Length: ' . (string) filesize($filePath));
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

readfile($filePath);
exit;

<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/pdf_parser.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$admin = require_admin();
if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid CSRF token.');
}

$pdo = get_db();
$title = trim((string) ($_POST['title'] ?? ''));
$authors = trim((string) ($_POST['authors'] ?? ''));
$year = (int) ($_POST['year'] ?? date('Y'));
$program = (string) ($_POST['program'] ?? 'DIT');
$abstract = trim((string) ($_POST['abstract'] ?? ''));
$keywords = trim((string) ($_POST['keywords'] ?? ''));
$reviewStatus = (string) ($_POST['review_status'] ?? 'approved');

if ($title === '' || $authors === '' || $abstract === '' || !array_key_exists($program, ITECH_PROGRAMS)) {
    header('Location: ' . url('admin/index.php?section=repository&notice=invalid'));
    exit;
}

if (!in_array($reviewStatus, ['approved', 'pending', 'revision'], true)) {
    $reviewStatus = 'approved';
}

$slugBase = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title) ?? 'study', '-'));
$slug = substr($slugBase, 0, 120) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

$pdfFilename = null;
$fileStatus = 'metadata-only';
$extractedText = '';

if (!empty($_FILES['pdf_file']['tmp_name']) && is_uploaded_file($_FILES['pdf_file']['tmp_name'])) {
    $fileSize = (int) ($_FILES['pdf_file']['size'] ?? 0);
    $mime = mime_content_type($_FILES['pdf_file']['tmp_name']) ?: '';

    if ($fileSize > 0 && $fileSize <= MAX_PDF_SIZE_BYTES && str_contains($mime, 'pdf')) {
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }
        $pdfFilename = $slug . '.pdf';
        $dest = UPLOAD_DIR . '/' . $pdfFilename;
        if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $dest)) {
            $fileStatus = 'available';
            $extractedText = extract_pdf_text($dest);
        }
    }
}

$stmt = $pdo->prepare(
    'INSERT INTO studies (slug, title, authors, year, department, program, abstract, keywords, extracted_text, pdf_filename, file_status, review_status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $slug,
    $title,
    $authors,
    max(1904, min(2100, $year)),
    'PUP Institute of Technology',
    $program,
    $abstract,
    $keywords,
    $extractedText,
    $pdfFilename,
    $fileStatus,
    $reviewStatus,
    (int) $admin['id'],
]);

$newId = (int) $pdo->lastInsertId();
log_audit((int) $admin['id'], 'STUDY_CREATED', 'study', $newId, "Published {$title}");

header('Location: ' . url('admin/index.php?section=repository&notice=saved'));
exit;

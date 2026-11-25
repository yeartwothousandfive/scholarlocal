<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/tfidf.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($query) < 2) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Enter at least two characters to search.', 'results' => []]);
    exit;
}

$pdo = get_db();
$studies = $pdo->query("SELECT id, slug, title, authors, year, program, abstract, keywords, extracted_text FROM studies WHERE review_status = 'approved'")->fetchAll();
$ranked = tfidf_rank_studies($studies, $query);

echo json_encode([
    'ok'      => true,
    'query'   => $query,
    'count'   => count($ranked),
    'results' => $ranked,
]);

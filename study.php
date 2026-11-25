<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/tfidf.php';

$pdo = get_db();
$user = current_user();

$studyId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM studies WHERE id = ? AND review_status = 'approved' LIMIT 1");
$stmt->execute([$studyId]);
$study = $stmt->fetch();

if (!$study) {
    $study = $pdo->query("SELECT * FROM studies WHERE review_status = 'approved' ORDER BY id ASC LIMIT 1")->fetch();
}

if (!$study) {
    http_response_code(404);
    exit('No studies available in the repository.');
}

// Increment view counter
$pdo->prepare('UPDATE studies SET view_count = view_count + 1 WHERE id = ?')->execute([(int) $study['id']]);

// Check current user's latest download request for this study
$downloadRequest = null;
if ($user) {
    $reqStmt = $pdo->prepare('SELECT * FROM download_requests WHERE user_id = ? AND study_id = ? ORDER BY id DESC LIMIT 1');
    $reqStmt->execute([(int) $user['id'], (int) $study['id']]);
    $downloadRequest = $reqStmt->fetch() ?: null;
}

// Related studies via TF-IDF Cosine Similarity
$allStudies = $pdo->query("SELECT * FROM studies WHERE review_status = 'approved'")->fetchAll();
$relatedMatches = tfidf_related_studies($allStudies, (int) $study['id'], 3);

$keywords = array_values(array_filter(array_map('trim', explode(',', (string) $study['keywords']))));
$reqStatus = $downloadRequest['status'] ?? null; // pending | approved | denied
$hasFile = ($study['file_status'] === 'available' && !empty($study['pdf_filename']));

$flash = '';
if (isset($_GET['req'])) {
    if ($_GET['req'] === 'submitted') {
        $flash = 'Your PDF download request has been submitted for administrator review.';
    } elseif ($_GET['req'] === 'exists') {
        $flash = 'You already have a pending download request for this study.';
    } elseif ($_GET['req'] === 'error') {
        $flash = 'Please provide an academic purpose (at least 10 characters) to submit a request.';
    }
}

$pageTitle = $study['title'] . ' · ' . APP_NAME;
$activeNav = 'browse';
require __DIR__ . '/includes/header.php';
?>
<main class="details-main">
    <div class="content-wrap">
        <p class="breadcrumb details-breadcrumb">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <span>/</span>
            <a href="<?= e(url('browse.php')) ?>">Search results</a>
            <span>/</span>
            Document details
        </p>

        <section class="document-intro">
            <div class="document-type">UNDERGRADUATE CAPSTONE &middot; <?= (int) $study['year'] ?></div>
            <h1><?= e($study['title']) ?></h1>
            <p class="authors">By <?= e($study['authors']) ?></p>
            <?php if (!empty($keywords)): ?>
                <div class="keyword-row">
                    <?php foreach ($keywords as $kw): ?>
                        <span><?= e($kw) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="document-layout">
            <section class="viewer-column">
                <div class="viewer-heading">
                    <div>
                        <p class="eyebrow maroon">FULL DOCUMENT</p>
                        <h2>Read the study</h2>
                    </div>

                    <?php if (!$hasFile): ?>
                        <button class="outline-button" disabled>Metadata only</button>
                    <?php elseif (!$user): ?>
                        <a class="outline-button" href="<?= e(url('login.php')) ?>">
                            <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                            Sign in to request PDF
                        </a>
                    <?php elseif ($reqStatus === 'approved' || is_admin($user)): ?>
                        <a class="outline-button download-request-button approved" href="<?= e(url('api/download-pdf.php?study_id=' . (int) $study['id'])) ?>">
                            <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12m-5-5 5 5 5-5M5 21h14"/></svg>
                            Download approved PDF
                        </a>
                    <?php elseif ($reqStatus === 'pending'): ?>
                        <button class="outline-button download-request-button pending" disabled>
                            Request pending
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($flash !== ''): ?>
                    <div class="download-request-message">
                        <div><strong><?= e($flash) ?></strong></div>
                    </div>
                <?php endif; ?>

                <?php if ($reqStatus): ?>
                    <div class="download-request-message <?= e($reqStatus) ?>">
                        <div>
                            <strong>
                                <?php if ($reqStatus === 'pending'): ?>
                                    Your download request is awaiting admin review.
                                <?php elseif ($reqStatus === 'approved'): ?>
                                    Your request was approved. The PDF is ready to download.
                                <?php else: ?>
                                    Your previous request was not approved.
                                <?php endif; ?>
                            </strong>
                            <span>
                                <?php if ($reqStatus === 'pending'): ?>
                                    You can continue reading the study preview while the repository office reviews it.
                                <?php elseif ($reqStatus === 'approved'): ?>
                                    Select “Download approved PDF” above to download the protected file.
                                <?php else: ?>
                                    <?= !empty($downloadRequest['review_note']) ? 'Note: ' . e($downloadRequest['review_note']) : 'You may submit a new request below for administrator review.' ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($hasFile && $user && !is_admin($user) && ($reqStatus === null || $reqStatus === 'denied')): ?>
                    <form class="request-purpose-box" method="post" action="<?= e(url('api/download-request.php')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="study_id" value="<?= (int) $study['id'] ?>">
                        <label for="purposeInput">Request Full PDF Download (Academic Purpose)</label>
                        <textarea id="purposeInput" name="purpose" minlength="10" maxlength="500" required placeholder="Briefly state your academic purpose (e.g., Review of Related Literature for DIT Capstone Project)..."></textarea>
                        <button class="outline-button" type="submit">Submit download request</button>
                    </form>
                <?php endif; ?>

                <div class="pdf-viewer" id="protectedPdfViewer">
                    <div class="viewer-toolbar">
                        <span><?= e($study['pdf_filename'] ?: ($study['slug'] . '.pdf')) ?></span>
                        <div class="viewer-controls">
                            <button type="button" id="btnPrevPage" aria-label="Previous page">&lsaquo;</button>
                            <span id="pdfPageLabel">1 / 86</span>
                            <button type="button" id="btnNextPage" aria-label="Next page">&rsaquo;</button>
                            <i></i>
                            <button type="button" id="btnZoomOut" aria-label="Zoom out">&minus;</button>
                            <span id="pdfZoomLabel">100%</span>
                            <button type="button" id="btnZoomIn" aria-label="Zoom in">+</button>
                        </div>
                    </div>

                    <div class="pdf-canvas">
                        <?php if ($user): ?>
                            <div class="pdf-watermark-overlay" aria-hidden="true">
                                <span><?= e($user['student_id']) ?> &middot; <?= e($user['email']) ?> &middot; PUP ITECH</span>
                            </div>
                        <?php endif; ?>

                        <article class="pdf-page" id="pdfPagePreview">
                            <div class="mini-seal">
                                <img class="seal" src="<?= e(url('assets/images/scholarlocal-logo.png')) ?>" alt="ScholarLocal seal" width="48" height="48">
                            </div>
                            <p>POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</p>
                            <small>INSTITUTE OF TECHNOLOGY</small>
                            <h3><?= e($study['title']) ?></h3>
                            <div class="pdf-rule"></div>
                            <p class="pdf-by">A Research Study Presented by</p>
                            <strong><?= e($study['authors']) ?></strong>
                            <p class="pdf-year">Manila, Philippines &middot; <?= (int) $study['year'] ?></p>
                            <span class="page-number" id="pdfPageFooter">1</span>
                        </article>
                    </div>

                    <?php if (!$user): ?>
                        <div class="pdf-locked-banner">
                            <span>Full-text PDF viewing and downloads require a verified PUP ITECH session.</span>
                            <a href="<?= e(url('login.php')) ?>">Sign in &rarr;</a>
                        </div>
                    <?php endif; ?>
                </div>

                <section class="metadata-section">
                    <h2>About this study</h2>
                    <dl>
                        <div>
                            <dt>Diploma program</dt>
                            <dd><?= e(ITECH_PROGRAMS[$study['program']] ?? $study['program']) ?></dd>
                        </div>
                        <div>
                            <dt>Publication year</dt>
                            <dd><?= (int) $study['year'] ?></dd>
                        </div>
                        <div>
                            <dt>Document type</dt>
                            <dd>Diploma Capstone / Thesis</dd>
                        </div>
                    </dl>
                    <h3>Abstract</h3>
                    <p><?= e($study['abstract']) ?></p>
                </section>
            </section>

            <aside class="related-sidebar">
                <p class="eyebrow maroon">YOU MAY ALSO LIKE</p>
                <h2>Related local studies</h2>
                <?php if (empty($relatedMatches)): ?>
                    <p style="color:var(--ink-500);font-size:14px;">No similar studies indexed yet.</p>
                <?php else: ?>
                    <?php foreach ($relatedMatches as $match): ?>
                        <?php $rel = $match['study']; ?>
                        <a class="related-item" href="<?= e(url('study.php?id=' . (int) $rel['id'])) ?>">
                            <span><?= (int) round($match['score'] * 100) ?>% similar &middot; <?= (int) $rel['year'] ?> &middot; <?= e($rel['program']) ?></span>
                            <strong><?= e($rel['title']) ?></strong>
                            <small>View study &rsaquo;</small>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

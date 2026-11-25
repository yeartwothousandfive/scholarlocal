<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/tfidf.php';

$pdo = get_db();

$query = trim((string) ($_GET['q'] ?? ''));
$authorFilter = trim((string) ($_GET['author'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'relevance');

// Support both single select (?year=2025 or ?program=DIT) and checkbox arrays (?years[]=... & ?programs[]=...)
$selectedYears = [];
if (!empty($_GET['year']) && is_string($_GET['year'])) {
    $selectedYears[] = (string) $_GET['year'];
}
if (!empty($_GET['years']) && is_array($_GET['years'])) {
    foreach ($_GET['years'] as $y) {
        $selectedYears[] = (string) $y;
    }
}
$selectedYears = array_values(array_unique(array_filter($selectedYears)));

$selectedPrograms = [];
if (!empty($_GET['program']) && is_string($_GET['program'])) {
    $selectedPrograms[] = (string) $_GET['program'];
}
if (!empty($_GET['programs']) && is_array($_GET['programs'])) {
    foreach ($_GET['programs'] as $p) {
        $selectedPrograms[] = (string) $p;
    }
}
$selectedPrograms = array_values(array_unique(array_filter($selectedPrograms)));

$allStudies = $pdo->query("SELECT * FROM studies WHERE review_status = 'approved' ORDER BY year DESC, id DESC")->fetchAll();

// Apply metadata filters
$filtered = array_values(array_filter($allStudies, static function (array $s) use ($selectedYears, $selectedPrograms, $authorFilter): bool {
    if (!empty($selectedYears)) {
        $matchYear = false;
        foreach ($selectedYears as $y) {
            if ($y === '2021-earlier' && (int) $s['year'] <= 2021) {
                $matchYear = true;
            } elseif ((string) $s['year'] === $y) {
                $matchYear = true;
            }
        }
        if (!$matchYear) {
            return false;
        }
    }
    if (!empty($selectedPrograms) && !in_array($s['program'], $selectedPrograms, true)) {
        return false;
    }
    if ($authorFilter !== '' && stripos((string) $s['authors'], $authorFilter) === false) {
        return false;
    }
    return true;
}));

// Apply TF-IDF + Cosine Similarity ranking if query is provided
$scoreById = [];
if ($query !== '') {
    $ranked = tfidf_rank_studies($filtered, $query);
    $byId = [];
    foreach ($filtered as $s) {
        $byId[(string) $s['id']] = $s;
    }
    $visibleStudies = [];
    foreach ($ranked as $match) {
        $idKey = (string) $match['id'];
        if (isset($byId[$idKey])) {
            $scoreById[$idKey] = $match['score'];
            $visibleStudies[] = $byId[$idKey];
        }
    }
    if ($sort === 'newest') {
        usort($visibleStudies, static fn(array $a, array $b): int => (int) $b['year'] <=> (int) $a['year']);
    }
} else {
    $visibleStudies = $filtered;
    if ($sort === 'newest') {
        usort($visibleStudies, static fn(array $a, array $b): int => (int) $b['year'] <=> (int) $a['year']);
    }
}

$pageTitle = ($query !== '' ? "Results for \"$query\" · " : 'Browse Studies · ') . APP_NAME;
$activeNav = 'browse';
require __DIR__ . '/includes/header.php';
?>
<main class="page-main">
    <section class="page-banner">
        <div class="content-wrap">
            <p class="eyebrow">PUP ITECH REPOSITORY</p>
            <h1>Search the collection</h1>
            <form class="compact-search" action="<?= e(url('browse.php')) ?>" method="get">
                <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input name="q" type="search" value="<?= e($query) ?>" placeholder="Search by title, author, keyword, or abstract">
                <button type="submit">Search</button>
            </form>
        </div>
    </section>

    <div class="content-wrap results-layout">
        <aside class="filter-sidebar">
            <form id="browseFilterForm" action="<?= e(url('browse.php')) ?>" method="get">
                <?php if ($query !== ''): ?>
                    <input type="hidden" name="q" value="<?= e($query) ?>">
                <?php endif; ?>
                <?php if ($authorFilter !== ''): ?>
                    <input type="hidden" name="author" value="<?= e($authorFilter) ?>">
                <?php endif; ?>
                <input type="hidden" name="sort" value="<?= e($sort) ?>">

                <div class="filter-title">
                    <strong>Filter results</strong>
                    <a href="<?= e(url('browse.php')) ?>">Clear all</a>
                </div>

                <fieldset>
                    <legend>Publication year</legend>
                    <?php foreach (['2026' => '2026', '2025' => '2025', '2024' => '2024', '2023' => '2023', '2022' => '2022', '2021-earlier' => '2021 & earlier'] as $val => $label): ?>
                        <label>
                            <input type="checkbox" name="years[]" value="<?= e($val) ?>" <?= in_array($val, $selectedYears, true) ? 'checked' : '' ?>>
                            <span><?= e($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <fieldset>
                    <legend>Diploma program</legend>
                    <?php foreach (ITECH_PROGRAMS as $code => $label): ?>
                        <label>
                            <input type="checkbox" name="programs[]" value="<?= e($code) ?>" <?= in_array($code, $selectedPrograms, true) ? 'checked' : '' ?>>
                            <span><?= e($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
            </form>
        </aside>

        <section class="results-list">
            <div class="results-summary">
                <div>
                    <p class="breadcrumb"><a href="<?= e(url('index.php')) ?>">Home</a> <span>/</span> Search results</p>
                    <h2><?= $query !== '' ? 'Results for “' . e($query) . '”' : 'All research studies' ?></h2>
                    <span>
                        <?= count($visibleStudies) ?> <?= count($visibleStudies) === 1 ? 'study' : 'studies' ?>
                        <?= $query !== '' ? 'ranked by TF-IDF cosine similarity' : 'in the approved archive' ?>
                    </span>
                </div>
                <form class="sort-select" action="<?= e(url('browse.php')) ?>" method="get">
                    <?php if ($query !== ''): ?><input type="hidden" name="q" value="<?= e($query) ?>"><?php endif; ?>
                    <?php foreach ($selectedYears as $y): ?><input type="hidden" name="years[]" value="<?= e($y) ?>"><?php endforeach; ?>
                    <?php foreach ($selectedPrograms as $p): ?><input type="hidden" name="programs[]" value="<?= e($p) ?>"><?php endforeach; ?>
                    <label>
                        Sort by
                        <select name="sort" onchange="this.form.submit()">
                            <option value="relevance" <?= $sort === 'relevance' ? 'selected' : '' ?>>Most relevant</option>
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
                        </select>
                    </label>
                </form>
            </div>

            <?php if (empty($visibleStudies)): ?>
                <div class="search-empty">
                    <svg aria-hidden="true" width="27" height="27" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <strong>No matching studies found</strong>
                    <span>Try broader keywords, fewer filters, or a different research topic.</span>
                </div>
            <?php else: ?>
                <?php foreach ($visibleStudies as $study): ?>
                    <?php $idKey = (string) $study['id']; ?>
                    <article class="result-card">
                        <div class="result-meta">
                            <span><?= e(ITECH_PROGRAMS[$study['program']] ?? $study['program']) ?></span>
                            <span class="year-badge"><?= (int) $study['year'] ?></span>
                            <?php if (isset($scoreById[$idKey])): ?>
                                <span class="similarity-badge"><?= (int) round($scoreById[$idKey] * 100) ?>% relevance</span>
                            <?php endif; ?>
                        </div>
                        <h3><?= e($study['title']) ?></h3>
                        <p class="authors">By <?= e($study['authors']) ?></p>
                        <p class="abstract"><?= e($study['abstract']) ?></p>
                        <div class="card-footer">
                            <span><?= e($study['department']) ?></span>
                            <a class="btn-primary" href="<?= e(url('study.php?id=' . (int) $study['id'])) ?>">
                                View full document
                                <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

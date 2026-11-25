<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$pdo = get_db();
$totalStudies = (int) $pdo->query("SELECT COUNT(*) FROM studies WHERE review_status = 'approved'")->fetchColumn();
$years = $pdo->query("SELECT DISTINCT year FROM studies WHERE review_status = 'approved' ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'ScholarLocal · PUP Institute of Technology Research Repository';
$activeNav = 'home';
require __DIR__ . '/includes/header.php';
?>
<main>
    <section class="hero">
        <div class="hero-inner">
            <p class="eyebrow">PUP ITECH RESEARCH REPOSITORY</p>
            <h1>
                Knowledge built by our community,<br>
                accessible to all.
            </h1>
            <p class="hero-copy">
                Discover applied research, technical projects, and diploma studies produced by the
                students and faculty of the PUP Institute of Technology.
            </p>
            <form class="search-panel" action="<?= e(url('browse.php')) ?>" method="get">
                <div class="main-search">
                    <svg aria-hidden="true" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <input name="q" type="search" placeholder="Search by title, author, keyword, or topic" aria-label="Search studies">
                    <button type="submit">Search repository</button>
                </div>
                <div class="advanced-row">
                    <span>Refine your search</span>
                    <label>
                        <span class="sr-only">Publication year</span>
                        <select name="year">
                            <option value="">All years</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?= (int) $y ?>"><?= (int) $y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span class="sr-only">Diploma program</span>
                        <select name="program">
                            <option value="">All diploma programs</option>
                            <?php foreach (ITECH_PROGRAMS as $code => $label): ?>
                                <option value="<?= e($code) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span class="sr-only">Author name</span>
                        <input name="author" type="text" placeholder="Author name">
                    </label>
                </div>
            </form>
            <div class="quick-links">
                <span>Popular topics:</span>
                <?php foreach (['Machine Learning', 'IoT Systems', 'Renewable Energy', 'Railway Safety'] as $topic): ?>
                    <a href="<?= e(url('browse.php?q=' . urlencode($topic))) ?>"><?= e($topic) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="collection-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow maroon">EXPLORE THE ARCHIVE</p>
                <h2>Technical research built for real-world impact</h2>
            </div>
            <p>
                A focused collection of applied studies and technical innovations, preserved for the
                next generation of PUP ITECH researchers.
            </p>
        </div>
        <div class="stats-grid">
            <article>
                <svg aria-hidden="true" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M20 5.5A2.5 2.5 0 0 0 17.5 3H13v16h4.5a2.5 2.5 0 0 1 2.5 2.5z"/></svg>
                <strong><?= $totalStudies ?></strong>
                <span>Approved ITECH studies</span>
            </article>
            <article>
                <svg aria-hidden="true" width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h8l4 4v16H6z"/><path d="M14 2v5h5M9 13h6M9 17h6"/></svg>
                <strong><?= count(ITECH_PROGRAMS) ?></strong>
                <span>Diploma programs</span>
            </article>
            <article>
                <span class="stat-year">1904</span>
                <strong>120+ years</strong>
                <span>of PUP academic excellence</span>
            </article>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

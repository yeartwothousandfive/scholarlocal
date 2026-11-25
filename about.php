<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'About · ' . APP_NAME;
$activeNav = 'about';
require __DIR__ . '/includes/header.php';
?>
<main class="about-main">
    <div class="content-wrap about-layout">
        <div>
            <p class="eyebrow maroon">ABOUT THE REPOSITORY</p>
            <h1>
                Preserving innovation.<br>
                Advancing technology.
            </h1>
        </div>
        <div class="about-copy">
            <p>
                ScholarLocal is the research repository of the PUP Institute of Technology. It makes
                applied studies, technical projects, and innovations from its eight diploma programs
                more visible, discoverable, and useful to students, faculty, and communities.
            </p>
            <p>
                Powered by TF-IDF vectorization, Cosine Similarity ranking, and Role-Based Access
                Control (RBAC), the platform supports responsible digital preservation and strengthens
                the Institute's commitment to accessible, industry-relevant technical education.
            </p>
        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

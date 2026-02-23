<?php
session_start();
$pageTitle = 'Responsabilité';
$baseUrl = '';
require_once __DIR__ . '/inc/db.php';
$organisation = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Responsabilité — ' . $organisation->name;
}
require_once __DIR__ . '/inc/page-static.php';
?>
    <section class="py-5 page-content">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Responsabilité</h1>
            <div class="content-prose">
                <p>Politiques et rapports sur nos engagements éthiques, diversité et impact environnemental.</p>
                <p>Notre responsabilité s'exerce vis-à-vis des personnes que nous accompagnons, de nos équipes et de l'environnement.</p>
                <h2 class="h5 mt-4 mb-2">Éthique et intégrité</h2>
                <p>Des politiques et dispositifs encadrent nos pratiques pour garantir l'intégrité de nos actions.</p>
                <h2 class="h5 mt-4 mb-2">Diversité et inclusion</h2>
                <p>Nous œuvrons pour un environnement inclusif et une représentation équitable au sein de l'organisation.</p>
                <h2 class="h5 mt-4 mb-2">Environnement</h2>
                <p>Nous nous efforçons de réduire l'impact environnemental de nos activités et de nos déplacements.</p>
            </div>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>

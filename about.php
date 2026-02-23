<?php
session_start();
$pageTitle = 'Qui nous sommes';
$baseUrl = '';
require_once __DIR__ . '/inc/db.php';
$organisation = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Qui nous sommes — ' . $organisation->name;
}
require_once __DIR__ . '/inc/page-static.php';
?>
    <section class="py-5 page-content">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Qui nous sommes</h1>
            <div class="content-prose">
                <p>Découvrez notre mission, notre charte et nos principes.</p>
                <p>Nous sommes une organisation indépendante qui place les personnes au cœur de son action. Notre identité repose sur des valeurs partagées et un cadre commun qui guide nos interventions sur le terrain.</p>
                <h2 class="h5 mt-4 mb-2">Notre mission</h2>
                <p>Agir pour et avec les populations que nous accompagnons, dans le respect de leur dignité et de leurs droits.</p>
                <h2 class="h5 mt-4 mb-2">Nos principes</h2>
                <ul>
                    <li>Indépendance et impartialité</li>
                    <li>Neutralité et accès aux populations</li>
                    <li>Redevabilité et transparence</li>
                </ul>
            </div>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>

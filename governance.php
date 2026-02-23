<?php
session_start();
$pageTitle = 'Notre gouvernance';
$baseUrl = '';
require_once __DIR__ . '/inc/db.php';
$organisation = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Notre gouvernance — ' . $organisation->name;
}
require_once __DIR__ . '/inc/page-static.php';
?>
    <section class="py-5 page-content">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Notre gouvernance</h1>
            <div class="content-prose">
                <p>Notre gouvernance et ce que signifie être une association. Guide de nos instances et de nos bureaux.</p>
                <p>La gouvernance de l'organisation assure la cohérence des décisions, le respect des mandats et la redevabilité envers les bénéficiaires et les partenaires.</p>
                <h2 class="h5 mt-4 mb-2">Instances</h2>
                <p>Les instances dirigeantes définissent les orientations stratégiques et contrôlent la mise en œuvre des activités.</p>
                <h2 class="h5 mt-4 mb-2">Bureaux</h2>
                <p>Nos bureaux dans le monde assurent la coordination opérationnelle et le lien avec les terrains d'intervention.</p>
            </div>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>

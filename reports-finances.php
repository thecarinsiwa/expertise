<?php
session_start();
$pageTitle = 'Rapports et finances';
$baseUrl = '';
require_once __DIR__ . '/inc/db.php';
$organisation = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Rapports et finances — ' . $organisation->name;
}
require_once __DIR__ . '/inc/page-static.php';
?>
    <section class="py-5 page-content">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Rapports et finances</h1>
            <div class="content-prose">
                <p>Rapports annuels d'activité et financiers, origine des fonds et utilisation.</p>
                <p>Nous nous engageons à rendre compte de notre activité et de l'usage des ressources qui nous sont confiées.</p>
                <h2 class="h5 mt-4 mb-2">Rapports d'activité</h2>
                <p>Les rapports annuels présentent les réalisations, les enseignements et les perspectives de l'organisation.</p>
                <h2 class="h5 mt-4 mb-2">Transparence financière</h2>
                <p>L'origine des fonds et leur répartition sont détaillées dans nos documents financiers publics.</p>
            </div>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>

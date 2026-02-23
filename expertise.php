<?php
session_start();
$pageTitle = 'Expertise';
$baseUrl = '';
require_once __DIR__ . '/inc/db.php';
$organisation = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Expertise — ' . $organisation->name;
}
require_once __DIR__ . '/inc/page-static.php';
?>
    <section class="py-5 page-content">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Expertise</h1>
            <div class="content-prose">
                <p>Métiers, compétences et ressources mobilisées pour nos missions et projets.</p>
                <p>Notre organisation s’appuie sur des expertises techniques et sectorielles qui permettent d’adapter nos réponses aux contextes et aux besoins identifiés.</p>
                <h2 class="h5 mt-4 mb-2">Domaines d’intervention</h2>
                <p>Santé, logistique, eau et assainissement, sécurité alimentaire, construction, formation… selon les mandats et les terrains.</p>
                <h2 class="h5 mt-4 mb-2">Ressources</h2>
                <p>Équipes pluridisciplinaires, outils et partenariats pour déployer une expertise de qualité.</p>
            </div>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>

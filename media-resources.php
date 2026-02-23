<?php
session_start();
$pageTitle = 'Médias & ressources';
$baseUrl = '';
require_once __DIR__ . '/inc/db.php';
$organisation = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Médias & ressources — ' . $organisation->name;
}
require_once __DIR__ . '/inc/page-static.php';
?>
    <section class="py-5 page-content">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Médias & ressources</h1>
            <div class="content-prose">
                <p>Documents, publications et ressources utiles.</p>
                <p>Cette section rassemble les supports de communication, rapports et documents mis à disposition du public.</p>
                <div class="mt-4">
                    <a href="index.php#actualites" class="btn btn-view-all me-2">Actualités</a>
                    <a href="reports-finances.php" class="btn btn-view-all">Rapports et finances</a>
                </div>
            </div>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>

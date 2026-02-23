<?php
session_start();
$pageTitle = 'Comment nous travaillons';
$baseUrl = '';
require_once __DIR__ . '/inc/db.php';
$organisation = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Comment nous travaillons — ' . $organisation->name;
}
require_once __DIR__ . '/inc/page-static.php';
?>
    <section class="py-5 page-content">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Comment nous travaillons</h1>
            <div class="content-prose">
                <p>Ce qui déclenche une intervention et comment la logistique permet à nos équipes de réagir rapidement.</p>
                <p>Notre mode d'action repose sur une analyse des besoins, une réponse adaptée aux contextes locaux et une logistique capable de déployer des moyens humains et matériels dans des délais courts.</p>
                <h2 class="h5 mt-4 mb-2">Déclenchement d'une intervention</h2>
                <p>Chaque intervention est décidée sur la base d'une évaluation des besoins et des capacités locales, en coordination avec les acteurs concernés.</p>
                <h2 class="h5 mt-4 mb-2">Logistique et réactivité</h2>
                <p>Une chaîne logistique structurée nous permet de mobiliser des équipes et du matériel pour répondre aux urgences et aux projets de long terme.</p>
            </div>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>

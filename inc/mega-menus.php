<?php
if (!isset($baseUrl)) $baseUrl = '';

$latestProjects = [];
// Récupère les 2 derniers projets pour le sous-menu "Nos projets ASBL".
if (isset($pdo) && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.name
            FROM project p
            WHERE p.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
            ORDER BY p.start_date DESC, p.updated_at DESC
            LIMIT 2
        ");
        $stmt->execute();
        $latestProjects = $stmt->fetchAll(PDO::FETCH_OBJ);
    } catch (Throwable $e) {
        $latestProjects = [];
    }
}
?>

<!-- Mega-menu : À propos -->
<div class="mega-menu" id="mega-about" aria-hidden="true">
    <div class="mega-red-line"></div>
    <div class="container">
        <div class="mega-header">
            <h2 class="mega-title">À propos</h2>
            <button type="button" class="btn-close-mega" aria-label="Fermer" data-close-mega><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="mega-grid">
            <div class="mega-col">
                <h3>Qui nous sommes</h3>
                <p>Découvrez notre mission, notre charte et nos principes.</p>
                <a href="<?= isset($baseUrl) ? $baseUrl : '' ?>about.php" class="mega-link">En savoir plus</a>
            </div>
            <div class="mega-col">
                <h3>Notre équipe</h3>
                <p>Les membres de notre organisation.</p>
                <a href="<?= $baseUrl ?>teams.php" class="mega-link">Voir l'équipe</a>
            </div>
            <div class="mega-col">
                <h3>Comment nous travaillons</h3>
                <p>Ce qui déclenche une intervention et comment la logistique permet à nos équipes de réagir rapidement.</p>
                <a href="<?= $baseUrl ?>how-we-work.php" class="mega-link">En savoir plus</a>
            </div>
            <div class="mega-col">
                <h3>Notre gouvernance</h3>
                <p>Notre gouvernance et ce que signifie être une association.</p>
                <a href="<?= $baseUrl ?>governance.php" class="mega-link">En savoir plus</a>
            </div>
            <div class="mega-col">
                <h3>Rapports et finances</h3>
                <p>Rapports annuels d'activité et financiers, origine des fonds et utilisation.</p>
                <a href="<?= $baseUrl ?>reports-finances.php" class="mega-link">En savoir plus</a>
            </div>
            <div class="mega-col">
                <h3>Responsabilité</h3>
                <p>Politiques et rapports sur nos engagements éthiques, diversité et impact environnemental.</p>
                <a href="<?= $baseUrl ?>responsibility.php" class="mega-link">En savoir plus</a>
            </div>
            <div class="mega-col">
                <h3>Médias &amp; ressources</h3>
                <p>Documents, publications et ressources disponibles.</p>
                <a href="<?= $baseUrl ?>media-resources.php" class="mega-link">En savoir plus</a>
            </div>
            <div class="mega-col">
                <h3>Nous contacter</h3>
                <p>Coordonnées de nos bureaux dans le monde.</p>
                <a href="<?= $baseUrl ?>contact.php" class="mega-link">En savoir plus</a>
            </div>
        </div>
    </div>
</div>

<!-- Mega-menu : Ce que nous faisons -->
<div class="mega-menu" id="mega-what" aria-hidden="true">
    <div class="mega-red-line"></div>
    <div class="container">
        <div class="mega-header">
            <h2 class="mega-title">Ce que nous faisons</h2>
            <button type="button" class="btn-close-mega" aria-label="Fermer" data-close-mega><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="mega-grid">
            <div class="mega-col">
                <h3>Missions</h3>
                <p>Nos missions sur le terrain : objectifs, rapports et résultats.</p>
                <a href="<?= $baseUrl ?>index.php#missions" class="mega-link">En savoir plus</a>
            </div>
            <div class="mega-col">
                <h3>Actualités</h3>
                <p>Annonces et dernières actualités de l'organisation.</p>
                <a href="<?= $baseUrl ?>index.php#actualites" class="mega-link">En savoir plus</a>
            </div>
            <div class="mega-col">
                <h3>Projets</h3>
                <p>Portfolios et programmes, suivi des projets et livrables.</p>
                <a href="<?= $baseUrl ?>projects.php" class="mega-link">En savoir plus</a>
            </div>
            <div class="mega-col">
                <h3>Expertise</h3>
                <p>Métiers, compétences et ressources mobilisées.</p>
                <a href="<?= $baseUrl ?>expertise.php" class="mega-link">En savoir plus</a>
            </div>
        </div>
    </div>
</div>

<!-- Mega-menu : Nos projets ASBL -->
<div class="mega-menu" id="mega-where" aria-hidden="true">
    <div class="mega-red-line"></div>
    <div class="container">
        <div class="mega-header">
            <h2 class="mega-title">Nos projets ASBL</h2>
            <button type="button" class="btn-close-mega" aria-label="Fermer" data-close-mega><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="mega-grid">
            <div class="mega-col">
                <h3>Derniers projets</h3>
                <p>Les deux derniers projets ASBL.</p>
                <?php if (!empty($latestProjects)): ?>
                    <?php foreach ($latestProjects as $p): ?>
                        <a href="<?= $baseUrl ?>project.php?id=<?= (int) $p->id ?>" class="mega-link d-block">
                            <?= htmlspecialchars($p->name) ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="mb-0">Aucun projet récent pour le moment.</p>
                <?php endif; ?>
                <a href="<?= $baseUrl ?>projects.php" class="mega-link d-block">Voir tous les projets</a>
            </div>
            <div class="mega-col">
                <h3>Accéder au catalogue</h3>
                <p>Recherchez et filtrez tous nos projets.</p>
                <a href="<?= $baseUrl ?>projects.php" class="mega-link">En savoir plus</a>
            </div>
        </div>
    </div>
</div>

<?php
session_start();
$pageTitle = 'Où nous travaillons';
$baseUrl = '';
require_once __DIR__ . '/inc/db.php';
$organisation = null;
$locations = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = 'Où nous travaillons — ' . $organisation->name;
    }
    $stmt = $pdo->query("
        SELECT DISTINCT m.location
        FROM mission m
        WHERE m.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
          AND m.location IS NOT NULL AND m.location != ''
        ORDER BY m.location
    ");
    if ($stmt) {
        $rows = $stmt->fetchAll();
        $locations = array_map(function ($r) { return $r->location; }, $rows);
    }
}
require_once __DIR__ . '/inc/page-static.php';
?>
    <section class="py-5 page-content">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Où nous travaillons</h1>
            <div class="content-prose">
                <p>Visualisez nos interventions et accédez à nos missions par lieu.</p>
                <h2 class="h5 mt-4 mb-2">Par lieu</h2>
                <?php if (count($locations) > 0): ?>
                    <p class="text-muted mb-2">Nos missions sont présentes dans les lieux suivants. Cliquez sur un lieu pour voir les missions associées.</p>
                    <ul class="list-unstyled row g-2">
                        <?php foreach ($locations as $loc): ?>
                            <li class="col-md-6 col-lg-4"><a href="missions.php?location=<?= urlencode($loc) ?>"><?= htmlspecialchars($loc) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">Les lieux d'intervention seront listés ici à partir des missions enregistrées.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>

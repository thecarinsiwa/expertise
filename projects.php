<?php
session_start();
$pageTitle = 'Projets';
$baseUrl = '';
$projects = [];
require_once __DIR__ . '/inc/db.php';
$organisation = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = 'Projets — ' . $organisation->name;
    }
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.code, p.description, p.start_date, p.end_date, p.status
        FROM project p
        WHERE p.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
        ORDER BY p.start_date DESC, p.updated_at DESC
    ");
    if ($stmt) $projects = $stmt->fetchAll();
}
require_once __DIR__ . '/inc/asset_url.php';
require_once __DIR__ . '/inc/page-static.php';
?>
    <section class="py-5 page-content">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Projets</h1>
            <p class="lead text-muted mb-4">Portfolios et programmes, suivi des projets et livrables.</p>
            <?php if (count($projects) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($projects as $p): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-mission h-100">
                                <div class="card-body">
                                    <h2 class="card-title h5"><?= htmlspecialchars($p->name) ?></h2>
                                    <?php if (!empty($p->code)): ?>
                                        <p class="card-meta mb-1"><?= htmlspecialchars($p->code) ?></p>
                                    <?php endif; ?>
                                    <p class="card-meta mb-0">
                                        <?php if ($p->start_date): ?><?= date('d/m/Y', strtotime($p->start_date)) ?><?php endif; ?>
                                        <?php if ($p->end_date): ?> — <?= date('d/m/Y', strtotime($p->end_date)) ?><?php endif; ?>
                                        <?php if (!empty($p->status)): ?> · <?= htmlspecialchars($p->status) ?><?php endif; ?>
                                    </p>
                                    <?php if (!empty($p->description)): ?>
                                        <p class="mt-2 small text-muted"><?= htmlspecialchars(mb_substr(strip_tags($p->description), 0, 120)) ?><?= mb_strlen(strip_tags($p->description)) > 120 ? '…' : '' ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">Aucun projet pour le moment.</p>
            <?php endif; ?>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>

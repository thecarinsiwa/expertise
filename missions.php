<?php
session_start();
$pageTitle = 'Missions';
$organisation = null;
$missions = [];
$totalMissions = 0;
$baseUrl = '';
$perPage = 12;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $perPage;
$locationFilter = isset($_GET['location']) ? trim($_GET['location']) : '';

require_once __DIR__ . '/inc/db.php';

try {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = $row->name . ' — Missions';
    }

    $whereOrg = "m.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)";
    $whereLocation = $locationFilter !== '' ? " AND m.location = ?" : "";
    $paramsCount = $locationFilter !== '' ? [$locationFilter] : [];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mission m WHERE $whereOrg$whereLocation");
    if ($paramsCount) $stmt->execute($paramsCount); else $stmt->execute();
    $totalMissions = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT m.id, m.title, m.location, m.start_date, m.updated_at
        FROM mission m
        WHERE $whereOrg$whereLocation
        ORDER BY m.updated_at DESC, m.start_date DESC
        LIMIT ? OFFSET ?
    ");
    if ($locationFilter !== '') {
        $stmt->bindValue(1, $locationFilter, PDO::PARAM_STR);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    } else {
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    $missions = $stmt->fetchAll();
} catch (PDOException $e) {
    $pdo = null;
}
$totalPages = $totalMissions > 0 ? (int) ceil($totalMissions / $perPage) : 1;

require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/header.php';
?>

    <section class="py-5">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Retour à l'accueil</a>
            </nav>

            <h1 class="mb-4">Missions<?= $locationFilter !== '' ? ' — ' . htmlspecialchars($locationFilter) : '' ?></h1>
            <?php if ($locationFilter !== ''): ?>
                <p class="text-muted mb-3"><a href="missions.php" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Toutes les missions</a></p>
            <?php endif; ?>

            <?php if (count($missions) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($missions as $m): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-mission h-100">
                                <div class="card-body">
                                    <h2 class="card-title h5"><a href="mission.php?id=<?= (int) $m->id ?>"><?= htmlspecialchars($m->title) ?></a></h2>
                                    <p class="card-meta mb-1"><?= htmlspecialchars($m->location ?: '—') ?></p>
                                    <p class="card-meta mb-0"><?= $m->updated_at ? date('d M Y', strtotime($m->updated_at)) : ($m->start_date ? date('d M Y', strtotime($m->start_date)) : '') ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Pagination des missions" class="mt-4 d-flex justify-content-center">
                        <ul class="pagination mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item"><a class="page-link" href="missions.php?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a></li>
                            <?php endif; ?>
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="missions.php?page=<?= $i ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item"><a class="page-link" href="missions.php?page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted">Aucune mission pour le moment.</p>
            <?php endif; ?>
        </div>
    </section>

<?php require __DIR__ . '/inc/footer.php'; ?>

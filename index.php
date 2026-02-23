<?php
session_start();
$pageTitle = 'Expertise';
$organisation = null;
$featuredMission = null;
$featuredAnnouncement = null;
$tagline = 'An international, independent medical humanitarian organisation';
$baseUrl = '';

$dbConfig = [
    'host'   => 'localhost',
    'dbname' => 'expertise',
    'user'   => 'root',
    'pass'   => '',
    'charset'=> 'utf8mb4',
];

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['dbname'], $dbConfig['charset']);
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    ]);

    $stmt = $pdo->query("SELECT name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = $row->name;
        if (!empty($row->description)) $tagline = $row->description;
    }

    $stmt = $pdo->query("
        SELECT m.id, m.title, m.description, m.location, m.start_date, m.updated_at
        FROM mission m
        WHERE m.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
        ORDER BY m.start_date DESC, m.updated_at DESC
        LIMIT 1
    ");
    if ($stmt && $row = $stmt->fetch()) $featuredMission = $row;

    if (!$featuredMission) {
        $stmt = $pdo->query("
            SELECT a.id, a.title, a.content AS description, a.published_at AS updated_at
            FROM announcement a
            WHERE a.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
            ORDER BY a.published_at DESC, a.created_at DESC
            LIMIT 1
        ");
        if ($stmt && $row = $stmt->fetch()) $featuredAnnouncement = $row;
    }

    $recentMissions = [];
    $stmt = $pdo->query("
        SELECT m.id, m.title, m.location, m.start_date, m.updated_at
        FROM mission m
        WHERE m.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
        ORDER BY m.updated_at DESC, m.start_date DESC
        LIMIT 6
    ");
    if ($stmt) $recentMissions = $stmt->fetchAll();

    $recentAnnouncements = [];
    $stmt = $pdo->query("
        SELECT a.id, a.title, a.published_at, a.created_at
        FROM announcement a
        WHERE a.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
        ORDER BY COALESCE(a.published_at, a.created_at) DESC, a.created_at DESC
        LIMIT 6
    ");
    if ($stmt) $recentAnnouncements = $stmt->fetchAll();
} catch (PDOException $e) {
    $pdo = null;
}
if (!isset($recentMissions)) $recentMissions = [];
if (!isset($recentAnnouncements)) $recentAnnouncements = [];

require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/header.php';
?>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-end">
                <div class="col-lg-7">
                    <?php if ($featuredMission): ?>
                        <span class="badge-location d-block mb-2"><?= htmlspecialchars($featuredMission->location ?: 'Mission') ?></span>
                        <h1 class="mb-3"><?= htmlspecialchars($featuredMission->title) ?></h1>
                        <p class="meta mb-2">Mise à jour projet · <?= $featuredMission->updated_at ? date('d M Y', strtotime($featuredMission->updated_at)) : ($featuredMission->start_date ? date('d M Y', strtotime($featuredMission->start_date)) : '') ?></p>
                        <?php if (!empty($featuredMission->description)): ?>
                            <p class="lead mb-4"><?= htmlspecialchars(mb_substr(strip_tags($featuredMission->description), 0, 220)) ?><?= mb_strlen(strip_tags($featuredMission->description)) > 220 ? '…' : '' ?></p>
                        <?php endif; ?>
                        <a href="#" class="btn btn-read-more">Lire la suite</a>
                    <?php elseif ($featuredAnnouncement): ?>
                        <span class="badge-location d-block mb-2">Annonce</span>
                        <h1 class="mb-3"><?= htmlspecialchars($featuredAnnouncement->title) ?></h1>
                        <p class="meta mb-2"><?= $featuredAnnouncement->updated_at ? date('d M Y', strtotime($featuredAnnouncement->updated_at)) : '' ?></p>
                        <?php if (!empty($featuredAnnouncement->description)): ?>
                            <p class="lead mb-4"><?= htmlspecialchars(mb_substr(strip_tags($featuredAnnouncement->description), 0, 220)) ?><?= mb_strlen(strip_tags($featuredAnnouncement->description)) > 220 ? '…' : '' ?></p>
                        <?php endif; ?>
                        <a href="#" class="btn btn-read-more">Lire la suite</a>
                    <?php else: ?>
                        <span class="badge-location d-block mb-2">Actualité</span>
                        <h1 class="mb-3">Bienvenue sur Expertise</h1>
                        <p class="meta mb-2">Mise à jour · <?= date('d M Y') ?></p>
                        <p class="lead mb-4">Plateforme de gestion des missions et des projets. Connectez la base de données pour afficher les missions et annonces à la une.</p>
                        <a href="#" class="btn btn-read-more">Lire la suite</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Partager -->
    <div class="container py-4">
        <div class="row align-items-center">
            <div class="col-md-4">
                <p class="share-label mb-2">Partager</p>
                <a href="#" class="share-icon" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                <a href="#" class="share-icon" aria-label="X"><i class="bi bi-twitter-x"></i></a>
                <a href="#" class="share-icon" aria-label="Email"><i class="bi bi-envelope"></i></a>
                <a href="#" class="share-icon" aria-label="Imprimer"><i class="bi bi-printer"></i></a>
                <a href="#" class="copy-link ms-1"><i class="bi bi-link-45deg"></i> Copier le lien</a>
            </div>
        </div>
    </div>

    <!-- Dernières missions -->
    <section class="container py-5" id="missions">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <h2 class="section-heading mb-0">Dernières missions</h2>
            <a href="admin/missions.php" class="btn-view-all">Voir tout</a>
        </div>
        <?php if (count($recentMissions) > 0): ?>
            <div class="row g-4">
                <?php foreach (array_slice($recentMissions, 0, 6) as $m): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-mission h-100">
                            <div class="card-body">
                                <h3 class="card-title"><a href="#"><?= htmlspecialchars($m->title) ?></a></h3>
                                <p class="card-meta mb-1"><?= htmlspecialchars($m->location ?: '—') ?></p>
                                <p class="card-meta mb-0"><?= $m->updated_at ? date('d M Y', strtotime($m->updated_at)) : ($m->start_date ? date('d M Y', strtotime($m->start_date)) : '') ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">Aucune mission pour le moment.</p>
        <?php endif; ?>
    </section>

    <!-- Actualités -->
    <section class="container py-5 border-top" id="actualites">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pt-4">
            <h2 class="section-heading mb-0">Actualités</h2>
            <a href="admin/announcements.php" class="btn-view-all">Voir tout</a>
        </div>
        <?php if (count($recentAnnouncements) > 0): ?>
            <div class="row g-4">
                <?php foreach (array_slice($recentAnnouncements, 0, 6) as $a): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-announcement h-100">
                            <div class="card-body">
                                <h3 class="card-title"><a href="#"><?= htmlspecialchars($a->title) ?></a></h3>
                                <p class="card-meta mb-0"><?= $a->published_at ? date('d M Y', strtotime($a->published_at)) : ($a->created_at ? date('d M Y', strtotime($a->created_at)) : '') ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">Aucune actualité pour le moment.</p>
        <?php endif; ?>
    </section>

<?php require __DIR__ . '/inc/footer.php'; ?>

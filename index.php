<?php
session_start();
$pageTitle = 'Expertise';
$organisation = null;
$featuredMission = null;
$featuredAnnouncement = null;
$tagline = 'An international, independent medical humanitarian organisation';
// Base URL du site (sous-dossier éventuel, ex. /expertise/)
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
// URL absolue de la page pour partage
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$pathPrefix = trim($baseUrl, '/');
$currentPageUrl = $protocol . '://' . $host . '/' . ($pathPrefix ? $pathPrefix . '/' : '') . 'index.php';

require_once __DIR__ . '/inc/db.php';

try {
    $stmt = $pdo->query("SELECT name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = $row->name;
        if (!empty($row->description)) $tagline = $row->description;
    }

    $stmt = $pdo->query("
        SELECT m.id, m.title, m.description, m.cover_image, m.location, m.start_date, m.updated_at
        FROM mission m
        WHERE m.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
        ORDER BY m.start_date DESC, m.updated_at DESC
        LIMIT 1
    ");
    if ($stmt && $row = $stmt->fetch()) $featuredMission = $row;

    if (!$featuredMission) {
        $stmt = $pdo->query("
            SELECT a.id, a.title, a.content AS description, a.cover_image, a.published_at AS updated_at
            FROM announcement a
            WHERE a.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
              AND (a.published_at IS NULL OR a.published_at <= NOW())
              AND (a.expires_at IS NULL OR a.expires_at > NOW())
            ORDER BY COALESCE(a.published_at, a.created_at) DESC, a.created_at DESC
            LIMIT 1
        ");
        if ($stmt && $row = $stmt->fetch()) $featuredAnnouncement = $row;
    }

    $heroAnnouncements = [];
    $stmt = $pdo->query("
        SELECT a.id, a.title, a.content AS description, a.cover_image, a.published_at, a.created_at
        FROM announcement a
        WHERE a.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
          AND (a.published_at IS NULL OR a.published_at <= NOW())
          AND (a.expires_at IS NULL OR a.expires_at > NOW())
        ORDER BY COALESCE(a.published_at, a.created_at) DESC, a.created_at DESC
        LIMIT 3
    ");
    if ($stmt) $heroAnnouncements = $stmt->fetchAll();

    $recentMissions = [];
    $stmt = $pdo->query("
        SELECT m.id, m.title, m.cover_image, m.location, m.start_date, m.updated_at
        FROM mission m
        WHERE m.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
        ORDER BY m.updated_at DESC, m.start_date DESC
        LIMIT 6
    ");
    if ($stmt) $recentMissions = $stmt->fetchAll();

    $recentAnnouncements = [];
    $stmt = $pdo->query("
        SELECT a.id, a.title, a.cover_image, a.published_at, a.created_at
        FROM announcement a
        WHERE a.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
          AND (a.published_at IS NULL OR a.published_at <= NOW())
          AND (a.expires_at IS NULL OR a.expires_at > NOW())
        ORDER BY COALESCE(a.published_at, a.created_at) DESC, a.created_at DESC
        LIMIT 6
    ");
    if ($stmt) $recentAnnouncements = $stmt->fetchAll();

    $statsMissions = 0;
    $statsLocations = 0;
    $statsAnnouncements = 0;
    $recentLocations = [];
    $orgId = (int) $pdo->query("SELECT id FROM organisation WHERE is_active = 1 LIMIT 1")->fetchColumn();
    if ($orgId) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM mission WHERE organisation_id = $orgId");
        if ($stmt) $statsMissions = (int) $stmt->fetchColumn();
        $stmt = $pdo->query("SELECT COUNT(DISTINCT location) FROM mission WHERE organisation_id = $orgId AND location IS NOT NULL AND TRIM(location) != ''");
        if ($stmt) $statsLocations = (int) $stmt->fetchColumn();
        $stmt = $pdo->query("SELECT COUNT(*) FROM announcement WHERE organisation_id = $orgId AND (published_at IS NULL OR published_at <= NOW()) AND (expires_at IS NULL OR expires_at > NOW())");
        if ($stmt) $statsAnnouncements = (int) $stmt->fetchColumn();
        $stmt = $pdo->query("SELECT DISTINCT location FROM mission WHERE organisation_id = $orgId AND location IS NOT NULL AND TRIM(location) != '' ORDER BY location LIMIT 8");
        if ($stmt) $recentLocations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    $pdo = null;
}
if (!isset($recentMissions)) $recentMissions = [];
if (!isset($recentAnnouncements)) $recentAnnouncements = [];
if (!isset($statsMissions)) $statsMissions = 0;
if (!isset($statsLocations)) $statsLocations = 0;
if (!isset($statsAnnouncements)) $statsAnnouncements = 0;
if (!isset($recentLocations)) $recentLocations = [];
if (!isset($heroAnnouncements)) $heroAnnouncements = [];

// Photos depuis la BDD (hero + cartes missions)
require_once __DIR__ . '/inc/asset_url.php';
$heroCoverUrl = '';
if ($featuredMission && !empty($featuredMission->cover_image)) {
    $heroCoverUrl = client_asset_url($baseUrl, $featuredMission->cover_image);
} elseif ($featuredAnnouncement && !empty($featuredAnnouncement->cover_image)) {
    $heroCoverUrl = client_asset_url($baseUrl, $featuredAnnouncement->cover_image);
}

require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/header.php';
?>

    <!-- Hero : carousel des 3 dernières annonces -->
    <section class="hero hero-carousel <?= count($heroAnnouncements) > 0 ? 'hero-has-cover' : 'hero-fallback' ?>">
        <?php if (count($heroAnnouncements) > 0): ?>
        <div id="heroAnnouncementsCarousel" class="carousel slide carousel-fade h-100" data-bs-ride="carousel" data-bs-interval="6000">
            <div class="carousel-inner h-100">
                <?php foreach ($heroAnnouncements as $idx => $ann): 
                    $slideCover = !empty($ann->cover_image) ? client_asset_url($baseUrl, $ann->cover_image) : '';
                    $slideDesc = '';
                    if (!empty($ann->description)) {
                        $slideDesc = html_entity_decode(strip_tags($ann->description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $slideDesc = preg_replace('/^(Description\s+générale\s*:?\s*|Description\s*:?\s*)/iu', '', $slideDesc);
                        $slideDesc = trim(preg_replace('/\s+/u', ' ', $slideDesc));
                        $slideDesc = mb_substr($slideDesc, 0, 220) . (mb_strlen($slideDesc) > 220 ? '…' : '');
                    }
                    $slideDate = $ann->published_at ? date('d M Y', strtotime($ann->published_at)) : ($ann->created_at ? date('d M Y', strtotime($ann->created_at)) : '');
                ?>
                <div class="carousel-item h-100 <?= $idx === 0 ? 'active' : '' ?>">
                    <?php if ($slideCover): ?>
                    <div class="hero-bg" style="background-image: url('<?= htmlspecialchars($slideCover) ?>');"></div>
                    <?php else: ?>
                    <div class="hero-bg hero-bg-default"></div>
                    <?php endif; ?>
                    <div class="container h-100 d-flex align-items-end">
                        <div class="row w-100 align-items-end">
                            <div class="col-lg-7">
                                <span class="badge-location d-block mb-2">Annonce</span>
                                <h1 class="mb-3"><?= htmlspecialchars($ann->title) ?></h1>
                                <p class="meta mb-2"><?= $slideDate ?></p>
                                <?php if ($slideDesc !== ''): ?>
                                <p class="lead mb-4"><?= htmlspecialchars($slideDesc) ?></p>
                                <?php endif; ?>
                                <a href="<?= $baseUrl ?>announcement.php?id=<?= (int) $ann->id ?>" class="btn btn-read-more">Lire la suite</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($heroAnnouncements) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroAnnouncementsCarousel" data-bs-slide="prev" aria-label="Précédent">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroAnnouncementsCarousel" data-bs-slide="next" aria-label="Suivant">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
            </button>
            <div class="carousel-indicators">
                <?php foreach ($heroAnnouncements as $idx => $ann): ?>
                <button type="button" data-bs-target="#heroAnnouncementsCarousel" data-bs-slide-to="<?= $idx ?>" <?= $idx === 0 ? 'class="active" aria-current="true"' : '' ?> aria-label="Annonce <?= $idx + 1 ?>"></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="hero-bg hero-bg-default"></div>
        <div class="hero-carousel-overlay"></div>
        <div class="container">
            <div class="row align-items-end">
                <div class="col-lg-7">
                    <span class="badge-location d-block mb-2"><?= $organisation ? htmlspecialchars($organisation->name) : 'Actualité' ?></span>
                    <h1 class="mb-3"><?= $organisation ? htmlspecialchars($organisation->name) : 'Bienvenue sur Expertise' ?></h1>
                    <p class="meta mb-2"><?= date('d M Y') ?></p>
                    <p class="lead mb-4"><?= $organisation && !empty($organisation->description) ? htmlspecialchars(mb_substr($organisation->description, 0, 280)) . (mb_strlen($organisation->description) > 280 ? '…' : '') : 'Plateforme de gestion des missions et des projets. Découvrez nos actions et actualités.' ?></p>
                    <a href="<?= $baseUrl ?>about.php" class="btn btn-read-more">En savoir plus</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- Partager (URL et titre dynamiques) -->
    <div class="container py-4" data-share-url="<?= htmlspecialchars($currentPageUrl) ?>" data-share-title="<?= htmlspecialchars($pageTitle) ?>">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="share-label mb-2">Partager</p>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($currentPageUrl) ?>" class="share-icon" aria-label="Partager sur Facebook" target="_blank" rel="noopener noreferrer"><i class="bi bi-facebook"></i></a>
                <a href="https://twitter.com/intent/tweet?url=<?= urlencode($currentPageUrl) ?>&text=<?= urlencode($pageTitle) ?>" class="share-icon" aria-label="Partager sur X" target="_blank" rel="noopener noreferrer"><i class="bi bi-twitter-x"></i></a>
                <a href="mailto:?subject=<?= urlencode($pageTitle) ?>&body=<?= urlencode($currentPageUrl) ?>" class="share-icon" aria-label="Partager par email"><i class="bi bi-envelope"></i></a>
                <a href="#" class="share-icon share-print" aria-label="Imprimer" title="Imprimer"><i class="bi bi-printer"></i></a>
                <a href="#" class="copy-link ms-1" data-copy-url="<?= htmlspecialchars($currentPageUrl) ?>" title="Copier l’adresse de la page"><i class="bi bi-link-45deg"></i> <span class="copy-link-text">Copier le lien</span></a>
            </div>
        </div>
    </div>

    <!-- À propos -->
    <section class="index-about py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h2 class="section-heading">Qui nous sommes</h2>
                    <p class="index-about-lead">
                        <?= $organisation && !empty($organisation->description) ? nl2br(htmlspecialchars(mb_substr($organisation->description, 0, 380))) . (mb_strlen($organisation->description) > 380 ? '…' : '') : 'Nous sommes une organisation humanitaire médicale internationale et indépendante. Découvrez notre mission, notre gouvernance et nos principes d\'action.' ?>
                    </p>
                    <a href="<?= $baseUrl ?>about.php" class="btn-view-all">En savoir plus</a>
                </div>
                <div class="col-lg-5 d-none d-lg-block text-end">
                    <span class="index-about-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                </div>
            </div>
        </div>
    </section>

    <!-- Chiffres clés -->
    <section class="index-stats py-5">
        <div class="container">
            <h2 class="section-heading text-center mb-4">Notre activité en quelques chiffres</h2>
            <div class="row g-4 justify-content-center">
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="index-stat-card">
                        <span class="index-stat-number"><?= $statsMissions ?></span>
                        <span class="index-stat-label">Missions</span>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="index-stat-card">
                        <span class="index-stat-number"><?= $statsLocations ?></span>
                        <span class="index-stat-label">Lieux d'intervention</span>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="index-stat-card">
                        <span class="index-stat-number"><?= $statsAnnouncements ?></span>
                        <span class="index-stat-label">Actualités publiées</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Dernières missions -->
    <section class="container py-5" id="missions">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <h2 class="section-heading mb-0">Dernières missions</h2>
            <a href="<?= $baseUrl ?>missions.php" class="btn-view-all">Voir tout</a>
        </div>
        <?php if (count($recentMissions) > 0): ?>
            <div class="row g-4">
                <?php foreach (array_slice($recentMissions, 0, 6) as $m):
                    $cardCoverUrl = !empty($m->cover_image) ? client_asset_url($baseUrl, $m->cover_image) : '';
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-mission h-100">
                            <?php if ($cardCoverUrl): ?>
                            <div class="card-mission-img" style="background-image: url('<?= htmlspecialchars($cardCoverUrl) ?>');"></div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h3 class="card-title"><a href="<?= $baseUrl ?>mission.php?id=<?= (int) $m->id ?>"><?= htmlspecialchars($m->title) ?></a></h3>
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
            <a href="<?= $baseUrl ?>news.php" class="btn-view-all">Voir tout</a>
        </div>
        <?php if (count($recentAnnouncements) > 0): ?>
            <div class="row g-4">
                <?php foreach (array_slice($recentAnnouncements, 0, 6) as $a):
                    $announceCardCover = !empty($a->cover_image) ? client_asset_url($baseUrl, $a->cover_image) : '';
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-announcement h-100">
                            <?php if ($announceCardCover): ?>
                            <div class="card-mission-img" style="background-image: url('<?= htmlspecialchars($announceCardCover) ?>');"></div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h3 class="card-title"><a href="<?= $baseUrl ?>announcement.php?id=<?= (int) $a->id ?>"><?= htmlspecialchars($a->title) ?></a></h3>
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

    <!-- Où nous travaillons -->
    <section class="index-where py-5">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h2 class="section-heading mb-0">Où nous travaillons</h2>
                <a href="<?= $baseUrl ?>where-we-work.php" class="btn-view-all">Voir la carte et les lieux</a>
            </div>
            <?php if (count($recentLocations) > 0): ?>
                <p class="text-muted mb-3">Parmi nos lieux d'intervention :</p>
                <ul class="index-where-list">
                    <?php foreach ($recentLocations as $loc): ?>
                        <li><a href="<?= $baseUrl ?>missions.php?location=<?= urlencode($loc) ?>"><?= htmlspecialchars($loc) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted mb-0">Découvrez nos zones d'intervention et les missions par pays ou région.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Appel à l'action -->
    <section class="index-cta py-5">
        <div class="container text-center">
            <h2 class="index-cta-title">Agir avec nous</h2>
            <p class="index-cta-lead mb-4">Vous souhaitez nous contacter, rejoindre nos équipes ou en savoir plus sur nos actions ?</p>
            <a href="<?= $baseUrl ?>contact.php" class="btn btn-cta-primary">Nous contacter</a>
        </div>
    </section>

<?php require __DIR__ . '/inc/footer.php'; ?>

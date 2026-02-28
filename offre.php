<?php
session_start();
$pageTitle = 'Offre';
$organisation = null;
$offer = null;
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';

require_once __DIR__ . '/inc/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = $row->name;
    }

    $stmt = $pdo->prepare("
        SELECT o.id, o.title, o.reference, o.description, o.cover_image, o.published_at, o.deadline_at, o.updated_at,
               o.mission_id, o.project_id, m.title AS mission_title, p.name AS project_name
        FROM offer o
        LEFT JOIN mission m ON o.mission_id = m.id
        LEFT JOIN project p ON o.project_id = p.id
        WHERE o.id = ? AND o.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1) AND o.status = 'published'
    ");
    $stmt->execute([$id]);
    $offer = $stmt->fetch();

    if (!$offer) {
        header('Location: index.php');
        exit;
    }

    $pageTitle = $offer->title . ' — ' . ($organisation ? $organisation->name : 'Expertise');
} catch (PDOException $e) {
    $pdo = null;
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/inc/asset_url.php';
require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/header.php';
$hasCover = !empty($offer->cover_image);
$coverUrl = $hasCover ? client_asset_url($baseUrl, $offer->cover_image) : '';

$clientLoggedIn = !empty($_SESSION['client_logged_in']);
$clientAlreadyApplied = false;
if ($clientLoggedIn && $pdo && $offer) {
    $cid = (int) ($_SESSION['client_id'] ?? 0);
    if ($cid > 0) {
        $stmt = $pdo->prepare("SELECT id FROM offer_application WHERE offer_id = ? AND user_id = ?");
        $stmt->execute([$offer->id, $cid]);
        $clientAlreadyApplied = $stmt->fetch() !== false;
    }
}
$applyUrl = $baseUrl . 'client/apply.php?offer_id=' . (int) $offer->id;
$loginUrl = $baseUrl . 'client/login.php?redirect=' . urlencode($baseUrl . 'offre.php?id=' . (int) $offer->id);
?>

    <main class="mission-detail">
        <?php if ($hasCover): ?>
        <header class="mission-detail-hero">
            <div class="mission-detail-hero-bg" style="background-image: url('<?= htmlspecialchars($coverUrl) ?>');"></div>
            <div class="mission-detail-hero-overlay"></div>
            <div class="container mission-detail-hero-content">
                <nav aria-label="Fil d'Ariane" class="mission-detail-breadcrumb">
                    <a href="<?= $baseUrl ?>index.php"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
                    <span class="mx-2">/</span>
                    <a href="<?= $baseUrl ?>offres.php">Nos offres</a>
                </nav>
                <span class="mission-detail-badge"><?= htmlspecialchars($offer->mission_id ? ($offer->mission_title ?? 'Mission') : ($offer->project_id ? ($offer->project_name ?? 'Projet') : 'Offre')) ?></span>
                <h1 class="mission-detail-title"><?= htmlspecialchars($offer->title) ?></h1>
                <?php if ($offer->reference): ?>
                    <p class="mission-detail-ref">Réf. <?= htmlspecialchars($offer->reference) ?></p>
                <?php endif; ?>
                <p class="mission-detail-dates">
                    <?php if ($offer->deadline_at): ?>Date limite : <?= date('d/m/Y', strtotime($offer->deadline_at)) ?><?php endif; ?>
                    <?php if ($offer->updated_at): ?> <span class="mission-detail-updated">· Mise à jour <?= date('d M Y', strtotime($offer->updated_at)) ?></span><?php endif; ?>
                </p>
            </div>
        </header>
        <?php else: ?>
        <div class="mission-detail-no-hero">
            <div class="container">
                <nav aria-label="Fil d'Ariane" class="mission-detail-breadcrumb">
                    <a href="<?= $baseUrl ?>index.php"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
                    <span class="mx-2">/</span>
                    <a href="<?= $baseUrl ?>offres.php">Nos offres</a>
                </nav>
                <span class="mission-detail-badge mission-detail-badge--dark"><?= htmlspecialchars($offer->mission_id ? ($offer->mission_title ?? 'Mission') : ($offer->project_id ? ($offer->project_name ?? 'Projet') : 'Offre')) ?></span>
                <h1 class="mission-detail-title mission-detail-title--dark"><?= htmlspecialchars($offer->title) ?></h1>
                <?php if ($offer->reference): ?>
                    <p class="mission-detail-ref mission-detail-ref--dark">Réf. <?= htmlspecialchars($offer->reference) ?></p>
                <?php endif; ?>
                <p class="mission-detail-dates mission-detail-dates--dark">
                    <?php if ($offer->deadline_at): ?>Date limite : <?= date('d/m/Y', strtotime($offer->deadline_at)) ?><?php endif; ?>
                    <?php if ($offer->updated_at): ?> <span class="mission-detail-updated">· Mise à jour <?= date('d M Y', strtotime($offer->updated_at)) ?></span><?php endif; ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <div class="mission-detail-body">
            <div class="container">
                <?php if ($hasCover): ?>
                <div class="mission-detail-cover-wrap mb-4">
                    <img src="<?= htmlspecialchars($coverUrl) ?>" alt="<?= htmlspecialchars($offer->title) ?>" class="mission-detail-cover-img img-fluid rounded">
                </div>
                <?php endif; ?>
                <div class="mission-detail-grid">
                    <article class="mission-detail-main">
                        <?php if (!empty($offer->description)): ?>
                            <div class="mission-detail-description">
                                <?= client_rewrite_uploads_in_html($baseUrl, $offer->description) ?>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4">
                            <?php if ($clientLoggedIn): ?>
                                <?php if ($clientAlreadyApplied): ?>
                                    <p class="text-success mb-2"><i class="bi bi-check-circle me-2"></i>Vous avez déjà postulé à cette offre.</p>
                                    <a href="<?= htmlspecialchars($applyUrl) ?>" class="btn btn-read-more"><i class="bi bi-pencil-square me-2"></i>Voir ou modifier ma candidature</a>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($applyUrl) ?>" class="btn btn-read-more"><i class="bi bi-send me-2"></i>Postuler à cette offre</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($loginUrl) ?>" class="btn btn-read-more"><i class="bi bi-box-arrow-in-right me-2"></i>Se connecter pour postuler</a>
                            <?php endif; ?>
                        </div>
                    </article>

                    <aside class="mission-detail-sidebar">
                        <?php if ($offer->mission_id): ?>
                            <div class="mission-detail-block">
                                <h2 class="mission-detail-block-title"><i class="bi bi-geo-alt"></i> Mission liée</h2>
                                <p class="mb-0"><a href="<?= $baseUrl ?>mission.php?id=<?= (int) $offer->mission_id ?>"><?= htmlspecialchars($offer->mission_title ?? 'Voir la mission') ?></a></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($offer->project_id): ?>
                            <div class="mission-detail-block">
                                <h2 class="mission-detail-block-title"><i class="bi bi-folder"></i> Projet lié</h2>
                                <p class="mb-0"><?= htmlspecialchars($offer->project_name ?? '') ?></p>
                            </div>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>
        </div>
    </main>

<?php require __DIR__ . '/inc/footer.php'; ?>

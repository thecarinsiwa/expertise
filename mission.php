<?php
session_start();
$pageTitle = 'Mission';
$organisation = null;
$mission = null;
$baseUrl = '';

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
        SELECT m.id, m.title, m.reference, m.description, m.cover_image, m.start_date, m.end_date, m.location, m.updated_at,
               mt.name AS type_name, ms.name AS status_name
        FROM mission m
        LEFT JOIN mission_type mt ON m.mission_type_id = mt.id
        LEFT JOIN mission_status ms ON m.mission_status_id = ms.id
        WHERE m.id = ? AND m.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
    ");
    $stmt->execute([$id]);
    $mission = $stmt->fetch();

    if (!$mission) {
        header('Location: index.php');
        exit;
    }

    $pageTitle = $mission->title . ' — ' . ($organisation ? $organisation->name : 'Expertise');

    $objectives = [];
    $stmt = $pdo->prepare("SELECT title, description, is_achieved FROM objective WHERE mission_id = ? ORDER BY sequence ASC, id ASC");
    $stmt->execute([$id]);
    $objectives = $stmt->fetchAll();

    $bailleurs = [];
    $stmt = $pdo->prepare("
        SELECT b.id, b.name, b.logo FROM bailleur b
        INNER JOIN mission_bailleur mb ON mb.bailleur_id = b.id
        WHERE mb.mission_id = ?
    ");
    $stmt->execute([$id]);
    $bailleurs = $stmt->fetchAll();
} catch (PDOException $e) {
    $pdo = null;
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/inc/asset_url.php';
require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/header.php';
$hasCover = !empty($mission->cover_image);
$coverUrl = $hasCover ? client_asset_url($baseUrl, $mission->cover_image) : '';
?>

    <main class="mission-detail">
        <?php if ($hasCover): ?>
        <header class="mission-detail-hero">
            <div class="mission-detail-hero-bg" style="background-image: url('<?= htmlspecialchars($coverUrl) ?>');"></div>
            <div class="mission-detail-hero-overlay"></div>
            <div class="container mission-detail-hero-content">
                <nav aria-label="Fil d'Ariane" class="mission-detail-breadcrumb">
                    <a href="index.php"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
                </nav>
                <span class="mission-detail-badge"><?= htmlspecialchars($mission->location ?: 'Mission') ?></span>
                <?php if ($mission->type_name || $mission->status_name): ?>
                    <p class="mission-detail-meta-top">
                        <?php if ($mission->type_name): ?><span><?= htmlspecialchars($mission->type_name) ?></span><?php endif; ?>
                        <?php if ($mission->type_name && $mission->status_name): ?> · <?php endif; ?>
                        <?php if ($mission->status_name): ?><span><?= htmlspecialchars($mission->status_name) ?></span><?php endif; ?>
                    </p>
                <?php endif; ?>
                <h1 class="mission-detail-title"><?= htmlspecialchars($mission->title) ?></h1>
                <?php if ($mission->reference): ?>
                    <p class="mission-detail-ref">Réf. <?= htmlspecialchars($mission->reference) ?></p>
                <?php endif; ?>
                <p class="mission-detail-dates">
                    <?php if ($mission->start_date): ?>Du <?= date('d/m/Y', strtotime($mission->start_date)) ?><?php endif; ?>
                    <?php if ($mission->end_date): ?> au <?= date('d/m/Y', strtotime($mission->end_date)) ?><?php endif; ?>
                    <?php if ($mission->updated_at): ?> <span class="mission-detail-updated">· Mise à jour <?= date('d M Y', strtotime($mission->updated_at)) ?></span><?php endif; ?>
                </p>
            </div>
        </header>
        <?php else: ?>
        <div class="mission-detail-no-hero">
            <div class="container">
                <nav aria-label="Fil d'Ariane" class="mission-detail-breadcrumb">
                    <a href="index.php"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
                </nav>
                <span class="mission-detail-badge mission-detail-badge--dark"><?= htmlspecialchars($mission->location ?: 'Mission') ?></span>
                <?php if ($mission->type_name || $mission->status_name): ?>
                    <p class="mission-detail-meta-top mission-detail-meta-top--dark">
                        <?php if ($mission->type_name): ?><span><?= htmlspecialchars($mission->type_name) ?></span><?php endif; ?>
                        <?php if ($mission->type_name && $mission->status_name): ?> · <?php endif; ?>
                        <?php if ($mission->status_name): ?><span><?= htmlspecialchars($mission->status_name) ?></span><?php endif; ?>
                    </p>
                <?php endif; ?>
                <h1 class="mission-detail-title mission-detail-title--dark"><?= htmlspecialchars($mission->title) ?></h1>
                <?php if ($mission->reference): ?>
                    <p class="mission-detail-ref mission-detail-ref--dark">Réf. <?= htmlspecialchars($mission->reference) ?></p>
                <?php endif; ?>
                <p class="mission-detail-dates mission-detail-dates--dark">
                    <?php if ($mission->start_date): ?>Du <?= date('d/m/Y', strtotime($mission->start_date)) ?><?php endif; ?>
                    <?php if ($mission->end_date): ?> au <?= date('d/m/Y', strtotime($mission->end_date)) ?><?php endif; ?>
                    <?php if ($mission->updated_at): ?> <span class="mission-detail-updated">· Mise à jour <?= date('d M Y', strtotime($mission->updated_at)) ?></span><?php endif; ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <div class="mission-detail-body">
            <div class="container">
                <div class="mission-detail-grid">
                    <article class="mission-detail-main">
                        <?php if (!empty($mission->description)): ?>
                            <div class="mission-detail-description">
                                <?= client_rewrite_uploads_in_html($baseUrl, $mission->description) ?>
                            </div>
                        <?php endif; ?>
                    </article>

                    <aside class="mission-detail-sidebar">
                        <?php if (count($objectives) > 0): ?>
                            <div class="mission-detail-block">
                                <h2 class="mission-detail-block-title"><i class="bi bi-bullseye"></i> Objectifs</h2>
                                <ul class="mission-detail-objectives">
                                    <?php foreach ($objectives as $obj): ?>
                                        <li class="mission-detail-objective <?= $obj->is_achieved ? 'mission-detail-objective--done' : '' ?>">
                                            <?php if ($obj->is_achieved): ?><i class="bi bi-check-circle-fill"></i><?php endif; ?>
                                            <div>
                                                <strong><?= htmlspecialchars($obj->title) ?></strong>
                                                <?php if (!empty($obj->description)): ?>
                                                    <p class="mission-detail-objective-desc"><?= htmlspecialchars($obj->description) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (count($bailleurs) > 0): ?>
                            <div class="mission-detail-block">
                                <h2 class="mission-detail-block-title"><i class="bi bi-building"></i> Bailleurs</h2>
                                <div class="mission-detail-bailleurs">
                                    <?php foreach ($bailleurs as $b): ?>
                                        <div class="mission-detail-bailleur">
                                            <?php if (!empty($b->logo)): ?>
                                                <img src="<?= htmlspecialchars(client_asset_url($baseUrl, $b->logo)) ?>" alt="<?= htmlspecialchars($b->name) ?>" class="mission-detail-bailleur-logo">
                                            <?php endif; ?>
                                            <span class="mission-detail-bailleur-name"><?= htmlspecialchars($b->name) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>
        </div>
    </main>

<?php require __DIR__ . '/inc/footer.php'; ?>

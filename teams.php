<?php
/**
 * Équipe – Liste et fiche détail d'un membre du personnel (public)
 * teams.php = liste ; teams.php?id=123 = détail du membre (staff id)
 */
session_start();
$pageTitle = 'Notre équipe';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$organisation = null;
$person = null;
$teamList = [];

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/asset_url.php';

$staffId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;

    if ($staffId > 0) {
        $stmt = $pdo->prepare("
            SELECT s.id, s.user_id, s.employee_number, s.phone_extension, s.work_email, s.hire_date, s.end_date,
                   s.employment_type, s.department, s.job_title, s.notes, s.photo, s.is_active,
                   u.first_name, u.last_name, u.email, u.phone
            FROM staff s
            JOIN user u ON u.id = s.user_id AND u.is_active = 1
            WHERE s.id = ? AND s.is_active = 1
              AND s.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
        ");
        $stmt->execute([$staffId]);
        $person = $stmt->fetch(PDO::FETCH_OBJ);
        if ($person) {
            $pageTitle = trim($person->first_name . ' ' . $person->last_name) . ' — Notre équipe';
            if ($organisation) $pageTitle .= ' — ' . $organisation->name;
        }
    } else {
        $stmt = $pdo->query("
            SELECT s.id, s.job_title, s.department, s.photo,
                   u.first_name, u.last_name
            FROM staff s
            JOIN user u ON u.id = s.user_id AND u.is_active = 1
            WHERE s.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
              AND s.is_active = 1
            ORDER BY u.last_name ASC, u.first_name ASC
        ");
        if ($stmt) $teamList = $stmt->fetchAll(PDO::FETCH_OBJ);
        if ($organisation) $pageTitle = 'Notre équipe — ' . $organisation->name;
    }
}

require __DIR__ . '/inc/head.php';
require __DIR__ . '/inc/header.php';

$employmentLabels = [
    'full_time' => 'Temps plein',
    'part_time' => 'Temps partiel',
    'contract' => 'Contrat',
    'intern' => 'Stage',
    'freelance' => 'Freelance',
];
?>

    <main class="mission-detail teams-page">
        <?php if ($person): ?>
        <?php
        $photoUrl = !empty($person->photo) ? client_asset_url($baseUrl, $person->photo) : '';
        $personName = trim($person->first_name . ' ' . $person->last_name);
        ?>
        <?php if ($photoUrl): ?>
        <header class="mission-detail-hero mission-detail-hero--team">
            <div class="mission-detail-hero-bg" style="background-image: url('<?= htmlspecialchars($photoUrl) ?>');"></div>
            <div class="mission-detail-hero-overlay"></div>
            <div class="container mission-detail-hero-content">
                <nav aria-label="Fil d'Ariane" class="mission-detail-breadcrumb">
                    <a href="<?= $baseUrl ?>index.php"><i class="bi bi-arrow-left"></i> Accueil</a>
                    <span class="mx-1">/</span>
                    <a href="<?= $baseUrl ?>teams.php">Notre équipe</a>
                    <span class="mx-1">/</span>
                    <span><?= htmlspecialchars($personName) ?></span>
                </nav>
                <span class="mission-detail-badge">Équipe</span>
                <?php if ($person->job_title || $person->department): ?>
                <p class="mission-detail-meta-top">
                    <?php if ($person->job_title): ?><span><?= htmlspecialchars($person->job_title) ?></span><?php endif; ?>
                    <?php if ($person->job_title && $person->department): ?> · <?php endif; ?>
                    <?php if ($person->department): ?><span><?= htmlspecialchars($person->department) ?></span><?php endif; ?>
                </p>
                <?php endif; ?>
                <h1 class="mission-detail-title"><?= htmlspecialchars($personName) ?></h1>
            </div>
        </header>
        <?php else: ?>
        <div class="mission-detail-no-hero">
            <div class="container">
                <nav aria-label="Fil d'Ariane" class="mission-detail-breadcrumb">
                    <a href="<?= $baseUrl ?>index.php"><i class="bi bi-arrow-left"></i> Accueil</a>
                    <span class="mx-1">/</span>
                    <a href="<?= $baseUrl ?>teams.php">Notre équipe</a>
                    <span class="mx-1">/</span>
                    <span><?= htmlspecialchars($personName) ?></span>
                </nav>
                <span class="mission-detail-badge mission-detail-badge--dark">Équipe</span>
                <?php if ($person->job_title || $person->department): ?>
                <p class="mission-detail-meta-top mission-detail-meta-top--dark">
                    <?php if ($person->job_title): ?><span><?= htmlspecialchars($person->job_title) ?></span><?php endif; ?>
                    <?php if ($person->job_title && $person->department): ?> · <?php endif; ?>
                    <?php if ($person->department): ?><span><?= htmlspecialchars($person->department) ?></span><?php endif; ?>
                </p>
                <?php endif; ?>
                <h1 class="mission-detail-title mission-detail-title--dark"><?= htmlspecialchars($personName) ?></h1>
            </div>
        </div>
        <?php endif; ?>

        <div class="mission-detail-body">
            <div class="container">
                <div class="mission-detail-grid teams-detail-grid">
                    <article class="mission-detail-main">
                        <div class="teams-detail-profile">
                            <?php if ($photoUrl): ?>
                            <div class="teams-detail-avatar" style="background-image: url('<?= htmlspecialchars($photoUrl) ?>');"></div>
                            <?php else: ?>
                            <div class="teams-detail-avatar teams-detail-avatar-placeholder"><i class="bi bi-person"></i></div>
                            <?php endif; ?>
                            <div class="teams-detail-profile-head">
                                <h2 class="teams-detail-name"><?= htmlspecialchars($personName) ?></h2>
                                <?php if ($person->job_title || $person->department): ?>
                                <p class="teams-detail-role">
                                    <?php if ($person->job_title): ?><?= htmlspecialchars($person->job_title) ?><?php endif; ?>
                                    <?php if ($person->job_title && $person->department): ?> · <?php endif; ?>
                                    <?php if ($person->department): ?><?= htmlspecialchars($person->department) ?><?php endif; ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <section class="teams-detail-about">
                            <h3 class="teams-detail-section-title">À propos</h3>
                            <?php if (!empty($person->notes)): ?>
                            <div class="mission-detail-description">
                                <?= nl2br(htmlspecialchars($person->notes)) ?>
                            </div>
                            <?php else: ?>
                            <p class="teams-detail-intro"><?= htmlspecialchars($personName) ?> fait partie de notre équipe<?= $organisation ? ' au sein de ' . htmlspecialchars($organisation->name) : '' ?>.</p>
                            <?php endif; ?>
                        </section>
                    </article>
                    <aside class="mission-detail-sidebar teams-detail-sidebar">
                        <div class="mission-detail-block teams-detail-card">
                            <h2 class="mission-detail-block-title"><i class="bi bi-person-badge"></i> Fiche</h2>
                            <dl class="project-detail-meta teams-detail-meta mb-0">
                                <?php if (!empty($person->job_title)): ?>
                                <div class="teams-detail-meta-row">
                                    <dt>Poste</dt>
                                    <dd><?= htmlspecialchars($person->job_title) ?></dd>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($person->department)): ?>
                                <div class="teams-detail-meta-row">
                                    <dt>Département / Service</dt>
                                    <dd><?= htmlspecialchars($person->department) ?></dd>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($person->employment_type)): ?>
                                <div class="teams-detail-meta-row">
                                    <dt>Type de contrat</dt>
                                    <dd><?= htmlspecialchars($employmentLabels[$person->employment_type] ?? $person->employment_type) ?></dd>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($person->hire_date)): ?>
                                <div class="teams-detail-meta-row">
                                    <dt>Entrée</dt>
                                    <dd><?= date('d/m/Y', strtotime($person->hire_date)) ?></dd>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($person->employee_number)): ?>
                                <div class="teams-detail-meta-row">
                                    <dt>Matricule</dt>
                                    <dd><?= htmlspecialchars($person->employee_number) ?></dd>
                                </div>
                                <?php endif; ?>
                            </dl>
                        </div>
                        <?php if (!empty($person->work_email) || !empty($person->email) || !empty($person->phone_extension) || !empty($person->phone)): ?>
                        <div class="mission-detail-block teams-detail-card teams-detail-contact-card">
                            <h2 class="mission-detail-block-title"><i class="bi bi-envelope"></i> Contact</h2>
                            <ul class="list-unstyled mb-0 teams-detail-contact-list">
                                <?php if (!empty($person->work_email)): ?>
                                <li><a href="mailto:<?= htmlspecialchars($person->work_email) ?>" class="teams-detail-contact-link"><i class="bi bi-envelope"></i><?= htmlspecialchars($person->work_email) ?></a></li>
                                <?php elseif (!empty($person->email)): ?>
                                <li><a href="mailto:<?= htmlspecialchars($person->email) ?>" class="teams-detail-contact-link"><i class="bi bi-envelope"></i><?= htmlspecialchars($person->email) ?></a></li>
                                <?php endif; ?>
                                <?php if (!empty($person->phone_extension)): ?>
                                <li class="teams-detail-contact-item"><i class="bi bi-telephone"></i>Poste <?= htmlspecialchars($person->phone_extension) ?></li>
                                <?php elseif (!empty($person->phone)): ?>
                                <li><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $person->phone)) ?>" class="teams-detail-contact-link"><i class="bi bi-telephone"></i><?= htmlspecialchars($person->phone) ?></a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        <div class="mission-detail-block">
                            <a href="<?= $baseUrl ?>teams.php" class="btn btn-read-more w-100"><i class="bi bi-people me-2"></i>Toute l'équipe</a>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Liste de l'équipe -->
        <div class="mission-detail-no-hero">
            <div class="container">
                <nav aria-label="Fil d'Ariane" class="mission-detail-breadcrumb">
                    <a href="<?= $baseUrl ?>index.php"><i class="bi bi-arrow-left"></i> Accueil</a>
                    <span class="mx-1">/</span>
                    <span>Notre équipe</span>
                </nav>
                <span class="mission-detail-badge mission-detail-badge--dark">Équipe</span>
                <h1 class="mission-detail-title mission-detail-title--dark">Notre équipe</h1>
                <p class="mission-detail-ref mission-detail-ref--dark">Découvrez les membres de notre organisation.</p>
            </div>
        </div>
        <div class="mission-detail-body">
            <div class="container py-4">
                <?php if (count($teamList) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($teamList as $s):
                        $staffPhotoUrl = !empty($s->photo) ? client_asset_url($baseUrl, $s->photo) : '';
                        $staffName = trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));
                    ?>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <a href="<?= $baseUrl ?>teams.php?id=<?= (int) $s->id ?>" class="card card-mission card-staff-link h-100 text-decoration-none">
                            <?php if ($staffPhotoUrl): ?>
                            <div class="card-mission-img card-staff-list-img" style="background-image: url('<?= htmlspecialchars($staffPhotoUrl) ?>');"></div>
                            <?php else: ?>
                            <div class="card-mission-img card-staff-list-img card-staff-img-placeholder d-flex align-items-center justify-content-center"><i class="bi bi-person text-muted" style="font-size: 3rem;"></i></div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h3 class="card-title h6 mb-1 text-dark"><?= htmlspecialchars($staffName) ?></h3>
                                <?php if (!empty($s->job_title)): ?>
                                <p class="card-meta small mb-0 text-muted"><?= htmlspecialchars($s->job_title) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($s->department)): ?>
                                <p class="card-meta small mb-0 text-muted"><?= htmlspecialchars($s->department) ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">Aucun membre de l'équipe à afficher pour le moment.</p>
                <a href="<?= $baseUrl ?>about.php" class="btn btn-read-more">Qui nous sommes</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

<?php require __DIR__ . '/inc/footer.php'; ?>

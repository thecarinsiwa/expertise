<?php
session_start();
$pageTitle = 'Qui nous sommes';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$organisation = null;
$organisationTypes = [];
$departments = [];
$entities = [];

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/asset_url.php';

if ($pdo) {
    $stmt = $pdo->query("
        SELECT id, name, code, description, address, phone, email, website,
               postal_code, city, country, rccm, nif, sector, logo, cover_image,
               facebook_url, linkedin_url, twitter_url, instagram_url, youtube_url
        FROM organisation WHERE is_active = 1 LIMIT 1
    ");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Qui nous sommes — ' . $organisation->name;

    if ($organisation) {
        $orgId = (int) $organisation->id;
        $stmt = $pdo->prepare("SELECT type_code FROM organisation_organisation_type WHERE organisation_id = ?");
        $stmt->execute([$orgId]);
        $organisationTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $orgId = $organisation ? (int) $organisation->id : 0;
    if ($orgId) {
        $stmt = $pdo->prepare("
            SELECT d.id, d.name, d.code, d.description, d.photo,
                   d.head_user_id, u.first_name AS head_first_name, u.last_name AS head_last_name
            FROM department d
            LEFT JOIN user u ON u.id = d.head_user_id AND u.is_active = 1
            WHERE d.organisation_id = ? AND d.is_active = 1
            ORDER BY d.name
        ");
        $stmt->execute([$orgId]);
        $deptRows = $stmt->fetchAll(PDO::FETCH_OBJ);
        $deptIds = array_map(function ($d) { return (int) $d->id; }, $deptRows);

        if (!empty($deptIds)) {
            $placeholders = implode(',', array_fill(0, count($deptIds), '?'));
            $stmt = $pdo->prepare("SELECT id, department_id, name, code, description, photo FROM service WHERE department_id IN ($placeholders) AND is_active = 1 ORDER BY name");
            $stmt->execute($deptIds);
            $servicesByDept = [];
            while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                $did = (int) $row->department_id;
                if (!isset($servicesByDept[$did])) $servicesByDept[$did] = [];
                $servicesByDept[$did][] = $row;
            }
            $serviceIds = [];
            foreach ($servicesByDept as $list) foreach ($list as $s) { $serviceIds[] = (int) $s->id; }
            $unitsByService = [];
            if (!empty($serviceIds)) {
                $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));
                $stmt = $pdo->prepare("SELECT id, service_id, name, code, description, photo FROM unit WHERE service_id IN ($placeholders) AND is_active = 1 ORDER BY name");
                $stmt->execute($serviceIds);
                while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
                    $sid = (int) $row->service_id;
                    if (!isset($unitsByService[$sid])) $unitsByService[$sid] = [];
                    $unitsByService[$sid][] = $row;
                }
            }
            foreach ($deptRows as $dept) {
                $dept->services = $servicesByDept[(int) $dept->id] ?? [];
                foreach ($dept->services as $svc) {
                    $svc->units = $unitsByService[(int) $svc->id] ?? [];
                }
            }
            $departments = $deptRows;
        }

        $stmt = $pdo->prepare("SELECT id, name, code, entity_type, parent_entity_id, description FROM organisational_entity WHERE organisation_id = ? AND is_active = 1 ORDER BY entity_type, name");
        $stmt->execute([$orgId]);
        $entities = $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}

require_once __DIR__ . '/inc/page-static.php';

$orgLogoUrl = '';
if ($organisation && !empty($organisation->logo)) {
    $orgLogoUrl = client_asset_url($baseUrl, $organisation->logo);
}
$orgCoverUrl = '';
if ($organisation && !empty($organisation->cover_image)) {
    $orgCoverUrl = client_asset_url($baseUrl, $organisation->cover_image);
}
$typeLabels = [
    'ngo' => 'ONG',
    'association' => 'Association',
    'foundation' => 'Fondation',
    'company' => 'Entreprise',
    'public' => 'Organisation publique',
    'other' => 'Autre',
];
?>
    <main class="mission-detail about-detail">
        <?php if ($organisation && $orgCoverUrl): ?>
        <header class="mission-detail-hero">
            <div class="mission-detail-hero-bg" style="background-image: url('<?= htmlspecialchars($orgCoverUrl) ?>');"></div>
            <div class="mission-detail-hero-overlay"></div>
            <div class="container mission-detail-hero-content">
                <nav aria-label="Fil d'Ariane" class="mission-detail-breadcrumb">
                    <a href="<?= $baseUrl ?>index.php"><i class="bi bi-arrow-left"></i> Accueil</a>
                    <span class="mx-1">/</span>
                    <span>Qui nous sommes</span>
                </nav>
                <span class="mission-detail-badge">Organisation</span>
                <?php if (!empty($organisation->sector)): ?>
                <p class="mission-detail-meta-top"><span><?= htmlspecialchars($organisation->sector) ?></span></p>
                <?php endif; ?>
                <h1 class="mission-detail-title"><?= htmlspecialchars($organisation->name) ?></h1>
                <?php if (!empty($organisation->code) || !empty($organisationTypes)): ?>
                <p class="mission-detail-ref">
                    <?php if (!empty($organisation->code)): ?><?= htmlspecialchars($organisation->code) ?><?php endif; ?>
                    <?php if (!empty($organisation->code) && !empty($organisationTypes)): ?> · <?php endif; ?>
                    <?php if (!empty($organisationTypes)): ?>
                        <?php
                        $labels = array_map(function ($t) use ($typeLabels) { return $typeLabels[$t] ?? ucfirst($t); }, $organisationTypes);
                        echo htmlspecialchars(implode(', ', $labels));
                        ?>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
            </div>
        </header>
        <?php else: ?>
        <div class="mission-detail-no-hero">
            <div class="container">
                <nav aria-label="Fil d'Ariane" class="mission-detail-breadcrumb">
                    <a href="<?= $baseUrl ?>index.php"><i class="bi bi-arrow-left"></i> Accueil</a>
                    <span class="mx-1">/</span>
                    <span>Qui nous sommes</span>
                </nav>
                <span class="mission-detail-badge mission-detail-badge--dark">Organisation</span>
                <?php if ($organisation && !empty($organisation->sector)): ?>
                <p class="mission-detail-meta-top mission-detail-meta-top--dark"><span><?= htmlspecialchars($organisation->sector) ?></span></p>
                <?php endif; ?>
                <h1 class="mission-detail-title mission-detail-title--dark"><?= $organisation ? htmlspecialchars($organisation->name) : 'Qui nous sommes' ?></h1>
                <?php if ($organisation && (!empty($organisation->code) || !empty($organisationTypes))): ?>
                <p class="mission-detail-ref mission-detail-ref--dark">
                    <?php if (!empty($organisation->code)): ?><?= htmlspecialchars($organisation->code) ?><?php endif; ?>
                    <?php if (!empty($organisation->code) && !empty($organisationTypes)): ?> · <?php endif; ?>
                    <?php if (!empty($organisationTypes)): ?>
                        <?php $labels = array_map(function ($t) use ($typeLabels) { return $typeLabels[$t] ?? ucfirst($t); }, $organisationTypes); echo htmlspecialchars(implode(', ', $labels)); ?>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="mission-detail-body">
            <div class="container">
                <div class="mission-detail-grid">
                    <article class="mission-detail-main">
                        <?php if ($organisation): ?>
                        <?php if ($orgLogoUrl): ?>
                        <div class="about-detail-logo mb-4">
                            <img src="<?= htmlspecialchars($orgLogoUrl) ?>" alt="Logo de <?= htmlspecialchars($organisation->name) ?>" class="about-detail-logo-img" width="100" height="100" loading="lazy">
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($organisation->description)): ?>
                        <div class="mission-detail-description about-org-description-html">
                            <?= client_rewrite_uploads_in_html($baseUrl, $organisation->description) ?>
                        </div>
                        <?php else: ?>
                        <div class="mission-detail-description">
                            <p>Découvrez notre mission, notre charte et nos principes. Nous sommes une organisation indépendante qui place les personnes au cœur de son action.</p>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($departments)): ?>
                        <div class="mission-detail-block mt-4 pt-4 border-top">
                            <h2 class="mission-detail-block-title"><i class="bi bi-diagram-3"></i> Notre structure</h2>
                            <p class="text-muted small mb-4">Organisation en départements, services et unités.</p>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($departments as $dept):
                                    $deptPhotoUrl = !empty($dept->photo) ? client_asset_url($baseUrl, $dept->photo) : '';
                                    $headName = (!empty($dept->head_first_name) || !empty($dept->head_last_name)) ? trim(($dept->head_first_name ?? '') . ' ' . ($dept->head_last_name ?? '')) : null;
                                ?>
                                <li class="about-structure-item mb-4 pb-4 border-bottom border-light">
                                    <div class="d-flex align-items-start gap-3">
                                        <?php if ($deptPhotoUrl): ?>
                                        <img src="<?= htmlspecialchars($deptPhotoUrl) ?>" alt="" class="about-dept-thumb rounded" width="56" height="56" loading="lazy">
                                        <?php else: ?>
                                        <span class="about-dept-icon text-primary"><i class="bi bi-building"></i></span>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <strong class="d-block"><?= htmlspecialchars($dept->name) ?><?php if (!empty($dept->code)): ?> <span class="text-muted fw-normal small">(<?= htmlspecialchars($dept->code) ?>)</span><?php endif; ?></strong>
                                            <?php if ($headName): ?>
                                            <span class="text-muted small d-block mt-1"><i class="bi bi-person me-1"></i> Responsable : <?= htmlspecialchars($headName) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($dept->description)): ?>
                                            <div class="about-structure-description small text-muted mt-1 mb-2"><?= client_rewrite_uploads_in_html($baseUrl, $dept->description) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($dept->services)): ?>
                                            <ul class="list-unstyled small mb-0 mt-2">
                                                <?php foreach ($dept->services as $svc): ?>
                                                <li class="mb-1">
                                                    <i class="bi bi-chevron-right text-muted me-1"></i>
                                                    <strong><?= htmlspecialchars($svc->name) ?></strong><?php if (!empty($svc->code)): ?> <span class="text-muted">(<?= htmlspecialchars($svc->code) ?>)</span><?php endif; ?>
                                                    <?php if (!empty($svc->units)): ?>
                                                    <ul class="list-unstyled ms-3 mt-1 mb-0">
                                                        <?php foreach ($svc->units as $unit): ?>
                                                        <li><i class="bi bi-arrow-return-right text-muted me-1"></i><?= htmlspecialchars($unit->name) ?><?php if (!empty($unit->code)): ?> <span class="text-muted">(<?= htmlspecialchars($unit->code) ?>)</span><?php endif; ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                    <?php endif; ?>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($entities) && empty($departments)): ?>
                        <div class="mission-detail-block mt-4 pt-4 border-top">
                            <h2 class="mission-detail-block-title"><i class="bi bi-diagram-3"></i> Entités organisationnelles</h2>
                            <?php $byType = ['division' => 'Divisions', 'department' => 'Départements', 'service' => 'Services', 'unit' => 'Unités']; ?>
                            <?php foreach ($byType as $type => $label):
                                $list = array_filter($entities, function ($e) use ($type) { return $e->entity_type === $type; });
                                if (empty($list)) continue;
                            ?>
                            <h3 class="h6 text-muted mt-3 mb-2"><?= $label ?></h3>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($list as $e): ?>
                                <li class="d-flex align-items-start gap-2 py-2 border-bottom border-light">
                                    <i class="bi bi-chevron-right text-primary small mt-1"></i>
                                    <div>
                                        <strong><?= htmlspecialchars($e->name) ?><?php if (!empty($e->code)): ?> <span class="text-muted fw-normal small">(<?= htmlspecialchars($e->code) ?>)</span><?php endif; ?></strong>
                                        <?php if (!empty($e->description)): ?>
                                        <p class="text-muted small mb-0 mt-1"><?= nl2br(htmlspecialchars($e->description)) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (empty($departments) && empty($entities) && $organisation): ?>
                        <div class="mission-detail-description mt-4 pt-4 border-top">
                            <h2 class="h5">Notre mission</h2>
                            <p>Agir pour et avec les populations que nous accompagnons, dans le respect de leur dignité et de leurs droits.</p>
                            <h2 class="h5 mt-4">Nos principes</h2>
                            <ul>
                                <li>Indépendance et impartialité</li>
                                <li>Neutralité et accès aux populations</li>
                                <li>Redevabilité et transparence</li>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <?php else: ?>
                        <div class="mission-detail-description">
                            <p>Découvrez notre mission, notre charte et nos principes.</p>
                            <p>Nous sommes une organisation indépendante qui place les personnes au cœur de son action.</p>
                            <h2 class="h5 mt-4">Notre mission</h2>
                            <p>Agir pour et avec les populations que nous accompagnons, dans le respect de leur dignité et de leurs droits.</p>
                            <h2 class="h5 mt-4">Nos principes</h2>
                            <ul>
                                <li>Indépendance et impartialité</li>
                                <li>Neutralité et accès aux populations</li>
                                <li>Redevabilité et transparence</li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </article>

                    <aside class="mission-detail-sidebar">
                        <?php if ($organisation): ?>
                        <div class="mission-detail-block">
                            <h2 class="mission-detail-block-title"><i class="bi bi-info-circle"></i> Informations</h2>
                            <dl class="project-detail-meta mb-0">
                                <?php if (!empty($organisation->code)): ?>
                                <dt>Code</dt>
                                <dd><?= htmlspecialchars($organisation->code) ?></dd>
                                <?php endif; ?>
                                <?php if (!empty($organisationTypes)): ?>
                                <dt>Type(s)</dt>
                                <dd><?= htmlspecialchars(implode(', ', array_map(function ($t) use ($typeLabels) { return $typeLabels[$t] ?? $t; }, $organisationTypes))) ?></dd>
                                <?php endif; ?>
                                <?php if (!empty($organisation->sector)): ?>
                                <dt>Secteur</dt>
                                <dd><?= htmlspecialchars($organisation->sector) ?></dd>
                                <?php endif; ?>
                            </dl>
                        </div>

                        <div class="mission-detail-block">
                            <h2 class="mission-detail-block-title"><i class="bi bi-geo-alt"></i> Coordonnées</h2>
                            <ul class="list-unstyled mb-0 about-sidebar-contact">
                                <?php
                                $addrParts = array_filter([
                                    $organisation->address ?? '',
                                    trim(($organisation->postal_code ?? '') . ' ' . ($organisation->city ?? '')),
                                    $organisation->country ?? '',
                                ]);
                                $fullAddress = implode(', ', $addrParts);
                                if ($fullAddress !== ''):
                                ?>
                                <li class="mb-2"><i class="bi bi-geo-alt text-muted me-2"></i><?= nl2br(htmlspecialchars($fullAddress)) ?></li>
                                <?php elseif (!empty($organisation->address)): ?>
                                <li class="mb-2"><i class="bi bi-geo-alt text-muted me-2"></i><?= nl2br(htmlspecialchars($organisation->address)) ?></li>
                                <?php endif; ?>
                                <?php if (!empty($organisation->phone)): ?>
                                <li class="mb-2"><i class="bi bi-telephone text-muted me-2"></i><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $organisation->phone)) ?>"><?= htmlspecialchars($organisation->phone) ?></a></li>
                                <?php endif; ?>
                                <?php if (!empty($organisation->email)): ?>
                                <li class="mb-2"><i class="bi bi-envelope text-muted me-2"></i><a href="mailto:<?= htmlspecialchars($organisation->email) ?>"><?= htmlspecialchars($organisation->email) ?></a></li>
                                <?php endif; ?>
                                <?php if (!empty($organisation->website)): ?>
                                <li class="mb-2"><i class="bi bi-globe text-muted me-2"></i><a href="<?= htmlspecialchars($organisation->website) ?>" target="_blank" rel="noopener"><?= htmlspecialchars(parse_url($organisation->website, PHP_URL_HOST) ?: $organisation->website) ?></a></li>
                                <?php endif; ?>
                                <?php if (!empty($organisation->rccm) || !empty($organisation->nif)): ?>
                                <li class="pt-2 mt-2 border-top border-light small">
                                    <?php if (!empty($organisation->rccm)): ?><strong>RCCM</strong> <?= htmlspecialchars($organisation->rccm) ?><br><?php endif; ?>
                                    <?php if (!empty($organisation->nif)): ?><strong>NIF</strong> <?= htmlspecialchars($organisation->nif) ?><?php endif; ?>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <?php
                        $hasSocial = !empty($organisation->facebook_url) || !empty($organisation->linkedin_url) || !empty($organisation->twitter_url) || !empty($organisation->instagram_url) || !empty($organisation->youtube_url);
                        if ($hasSocial):
                        ?>
                        <div class="mission-detail-block">
                            <h2 class="mission-detail-block-title"><i class="bi bi-share"></i> Suivez-nous</h2>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if (!empty($organisation->facebook_url)): ?><a href="<?= htmlspecialchars($organisation->facebook_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary" aria-label="Facebook"><i class="bi bi-facebook"></i></a><?php endif; ?>
                                <?php if (!empty($organisation->linkedin_url)): ?><a href="<?= htmlspecialchars($organisation->linkedin_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a><?php endif; ?>
                                <?php if (!empty($organisation->twitter_url)): ?><a href="<?= htmlspecialchars($organisation->twitter_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary" aria-label="X"><i class="bi bi-twitter-x"></i></a><?php endif; ?>
                                <?php if (!empty($organisation->instagram_url)): ?><a href="<?= htmlspecialchars($organisation->instagram_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary" aria-label="Instagram"><i class="bi bi-instagram"></i></a><?php endif; ?>
                                <?php if (!empty($organisation->youtube_url)): ?><a href="<?= htmlspecialchars($organisation->youtube_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary" aria-label="YouTube"><i class="bi bi-youtube"></i></a><?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>

                        <div class="mission-detail-block">
                            <h2 class="mission-detail-block-title"><i class="bi bi-link-45deg"></i> En savoir plus</h2>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><a href="<?= $baseUrl ?>contact.php" class="text-decoration-none">Nous contacter</a></li>
                                <li class="mb-2"><a href="<?= $baseUrl ?>projects.php" class="text-decoration-none">Nos projets</a></li>
                                <li class="mb-2"><a href="<?= $baseUrl ?>missions.php" class="text-decoration-none">Nos missions</a></li>
                                <li><a href="<?= $baseUrl ?>how-we-work.php" class="text-decoration-none">Comment nous travaillons</a></li>
                            </ul>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </main>
<?php require __DIR__ . '/inc/footer.php'; ?>

<?php
session_start();
$pageTitle = 'Qui nous sommes';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$organisation = null;
$departments = [];
$entities = [];

require_once __DIR__ . '/inc/db.php';

if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, code, description, address, phone, email, website FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Qui nous sommes — ' . $organisation->name;

    $orgId = $organisation ? (int) $organisation->id : 0;
    if ($orgId) {
        $stmt = $pdo->prepare("SELECT id, name, code, description FROM department WHERE organisation_id = ? AND is_active = 1 ORDER BY name");
        $stmt->execute([$orgId]);
        $deptRows = $stmt->fetchAll(PDO::FETCH_OBJ);
        $deptIds = array_map(function ($d) { return (int) $d->id; }, $deptRows);

        if (!empty($deptIds)) {
            $placeholders = implode(',', array_fill(0, count($deptIds), '?'));
            $stmt = $pdo->prepare("SELECT id, department_id, name, code, description FROM service WHERE department_id IN ($placeholders) AND is_active = 1 ORDER BY name");
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
                $stmt = $pdo->prepare("SELECT id, service_id, name, code, description FROM unit WHERE service_id IN ($placeholders) AND is_active = 1 ORDER BY name");
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
?>
    <section class="py-5 page-content about-page">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Qui nous sommes</h1>

            <?php if ($organisation): ?>
            <div class="about-intro content-prose mb-5">
                <?php if (!empty($organisation->description)): ?>
                <div class="about-org-description mb-4">
                    <?= nl2br(htmlspecialchars($organisation->description)) ?>
                </div>
                <?php else: ?>
                <p>Découvrez notre mission, notre charte et nos principes. Nous sommes une organisation indépendante qui place les personnes au cœur de son action.</p>
                <?php endif; ?>

                <div class="about-org-contact card border-0 shadow-sm mt-4 p-4 about-contact-card">
                    <h2 class="h6 text-uppercase text-muted mb-3"><?= htmlspecialchars($organisation->name) ?><?php if (!empty($organisation->code)): ?> <span class="text-muted fw-normal">(<?= htmlspecialchars($organisation->code) ?>)</span><?php endif; ?></h2>
                    <div class="row g-3">
                        <?php if (!empty($organisation->address)): ?>
                        <div class="col-12"><p class="mb-0"><i class="bi bi-geo-alt me-2 text-muted"></i><?= nl2br(htmlspecialchars($organisation->address)) ?></p></div>
                        <?php endif; ?>
                        <?php if (!empty($organisation->phone)): ?>
                        <div class="col-12 col-md-6"><p class="mb-0"><i class="bi bi-telephone me-2 text-muted"></i><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $organisation->phone)) ?>"><?= htmlspecialchars($organisation->phone) ?></a></p></div>
                        <?php endif; ?>
                        <?php if (!empty($organisation->email)): ?>
                        <div class="col-12 col-md-6"><p class="mb-0"><i class="bi bi-envelope me-2 text-muted"></i><a href="mailto:<?= htmlspecialchars($organisation->email) ?>"><?= htmlspecialchars($organisation->email) ?></a></p></div>
                        <?php endif; ?>
                        <?php if (!empty($organisation->website)): ?>
                        <div class="col-12"><p class="mb-0"><i class="bi bi-globe me-2 text-muted"></i><a href="<?= htmlspecialchars($organisation->website) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($organisation->website) ?></a></p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($departments)): ?>
            <section class="about-structure mt-5" aria-labelledby="about-structure-heading">
                <h2 id="about-structure-heading" class="section-heading mb-4">Notre structure</h2>
                <p class="text-muted mb-4">Organisation en départements, services et unités.</p>
                <div class="about-structure-list">
                    <?php foreach ($departments as $dept): ?>
                    <div class="about-structure-block card border-0 shadow-sm mb-3 overflow-hidden">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start gap-2">
                                <span class="about-structure-icon text-primary"><i class="bi bi-building"></i></span>
                                <div class="flex-grow-1">
                                    <h3 class="h5 mb-1"><?= htmlspecialchars($dept->name) ?><?php if (!empty($dept->code)): ?> <span class="text-muted fw-normal small">(<?= htmlspecialchars($dept->code) ?>)</span><?php endif; ?></h3>
                                    <?php if (!empty($dept->description)): ?>
                                    <p class="text-muted small mb-3"><?= nl2br(htmlspecialchars($dept->description)) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($dept->services)): ?>
                                    <ul class="about-services-list list-unstyled mb-0">
                                        <?php foreach ($dept->services as $svc): ?>
                                        <li class="about-service-item mb-3">
                                            <span class="about-service-name d-inline-flex align-items-center gap-2"><i class="bi bi-diagram-3 text-muted small"></i><strong><?= htmlspecialchars($svc->name) ?><?php if (!empty($svc->code)): ?> <span class="text-muted fw-normal small">(<?= htmlspecialchars($svc->code) ?>)</span><?php endif; ?></strong></span>
                                            <?php if (!empty($svc->description)): ?>
                                            <p class="about-service-desc text-muted small ms-4 mb-2"><?= nl2br(htmlspecialchars($svc->description)) ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($svc->units)): ?>
                                            <ul class="about-units-list list-unstyled ms-4 mb-0">
                                                <?php foreach ($svc->units as $unit): ?>
                                                <li class="about-unit-item small d-flex align-items-center gap-2 py-1">
                                                    <i class="bi bi-arrow-return-right text-muted"></i>
                                                    <?= htmlspecialchars($unit->name) ?><?php if (!empty($unit->code)): ?> <span class="text-muted">(<?= htmlspecialchars($unit->code) ?>)</span><?php endif; ?>
                                                    <?php if (!empty($unit->description)): ?>
                                                    <span class="text-muted">— <?= htmlspecialchars(mb_substr($unit->description, 0, 120)) ?><?= mb_strlen($unit->description) > 120 ? '…' : '' ?></span>
                                                    <?php endif; ?>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php endif; ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if (!empty($entities) && empty($departments)): ?>
            <section class="about-entities mt-5" aria-labelledby="about-entities-heading">
                <h2 id="about-entities-heading" class="section-heading mb-4">Entités organisationnelles</h2>
                <?php
                $byType = ['division' => 'Divisions', 'department' => 'Départements', 'service' => 'Services', 'unit' => 'Unités'];
                foreach ($byType as $type => $label):
                    $list = array_filter($entities, function ($e) use ($type) { return $e->entity_type === $type; });
                    if (empty($list)) continue;
                ?>
                <div class="mb-4">
                    <h3 class="h6 text-uppercase text-muted mb-2"><?= $label ?></h3>
                    <ul class="about-entities-list list-unstyled">
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
                </div>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>

            <?php if (empty($departments) && empty($entities)): ?>
            <div class="content-prose mt-4">
                <h2 class="h5 mt-4 mb-2">Notre mission</h2>
                <p>Agir pour et avec les populations que nous accompagnons, dans le respect de leur dignité et de leurs droits.</p>
                <h2 class="h5 mt-4 mb-2">Nos principes</h2>
                <ul>
                    <li>Indépendance et impartialité</li>
                    <li>Neutralité et accès aux populations</li>
                    <li>Redevabilité et transparence</li>
                </ul>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="content-prose">
                <p>Découvrez notre mission, notre charte et nos principes.</p>
                <p>Nous sommes une organisation indépendante qui place les personnes au cœur de son action. Notre identité repose sur des valeurs partagées et un cadre commun qui guide nos interventions sur le terrain.</p>
                <h2 class="h5 mt-4 mb-2">Notre mission</h2>
                <p>Agir pour et avec les populations que nous accompagnons, dans le respect de leur dignité et de leurs droits.</p>
                <h2 class="h5 mt-4 mb-2">Nos principes</h2>
                <ul>
                    <li>Indépendance et impartialité</li>
                    <li>Neutralité et accès aux populations</li>
                    <li>Redevabilité et transparence</li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>

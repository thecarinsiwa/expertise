<?php
/**
 * Contenu de la page Responsabilité – Introduction et engagements
 */
$pageTitle = 'Responsabilité – Contenu';
$currentNav = 'responsibility';
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.documents.view');
require __DIR__ . '/inc/db.php';

// Force UTF-8 for MySQL session (ensures correct encoding when reading/writing)
if ($pdo) {
    $pdo->exec("SET NAMES 'utf8mb4'");
}

$error = '';
$success = '';
$organisations = [];
$pageContent = null;
$commitments = [];
$organisation_id = isset($_GET['organisation_id']) ? $_GET['organisation_id'] : '';
if ($organisation_id !== '' && $organisation_id !== null) {
    $organisation_id = (int) $organisation_id;
} else {
    $organisation_id = null; // default / global content
}

// Available Bootstrap Icons for commitments
$iconOptions = ['bi-shield-check', 'bi-people', 'bi-globe2', 'bi-heart', 'bi-award', 'bi-file-earmark-text', 'bi-lightning', 'bi-tree', 'bi-recycle', 'bi-hand-thumbs-up', 'bi-briefcase', 'bi-building'];

if ($pdo) {
    $organisations = $pdo->query("SELECT id, name FROM organisation WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_OBJ);

    // ---------- POST: Save intro ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_intro'])) {
        if (!has_permission('admin.documents.modify')) {
            $error = 'Droits insuffisants pour modifier.';
        } else {
            $intro1 = trim($_POST['intro_block1'] ?? '');
            $intro2 = trim($_POST['intro_block2'] ?? '');
            $org_id = isset($_POST['organisation_id']) && $_POST['organisation_id'] !== '' ? (int) $_POST['organisation_id'] : null;
            try {
                $stmt = $pdo->prepare("SELECT id FROM responsibility_page WHERE organisation_id <=> ? LIMIT 1");
                $stmt->execute([$org_id]);
                $existing = $stmt->fetch(PDO::FETCH_OBJ);
                if ($existing) {
                    $pdo->prepare("UPDATE responsibility_page SET intro_block1 = ?, intro_block2 = ? WHERE id = ?")
                        ->execute([$intro1, $intro2, $existing->id]);
                    $success = 'Introduction mise à jour.';
                } else {
                    $pdo->prepare("INSERT INTO responsibility_page (organisation_id, intro_block1, intro_block2) VALUES (?, ?, ?)")
                        ->execute([$org_id, $intro1, $intro2]);
                    $success = 'Introduction enregistrée.';
                }
                $organisation_id = $org_id;
            } catch (PDOException $e) {
                $error = 'Erreur : ' . $e->getMessage();
            }
        }
    }

    // ---------- POST: Save commitment ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_commitment'])) {
        if (!has_permission('admin.documents.modify')) {
            $error = 'Droits insuffisants pour modifier.';
        } else {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;
            $icon = trim($_POST['icon'] ?? '') ?: null;
            $sort_order = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
            $commitment_org_id = isset($_POST['commitment_organisation_id']) && $_POST['commitment_organisation_id'] !== '' ? (int) $_POST['commitment_organisation_id'] : null;
            $commitment_id = isset($_POST['commitment_id']) ? (int) $_POST['commitment_id'] : 0;
            if ($title === '') {
                $error = 'Le titre de l\'engagement est obligatoire.';
            } else {
                try {
                    if ($commitment_id > 0) {
                        $pdo->prepare("UPDATE responsibility_commitment SET organisation_id = ?, title = ?, description = ?, icon = ?, sort_order = ? WHERE id = ?")
                            ->execute([$commitment_org_id, $title, $description, $icon, $sort_order, $commitment_id]);
                        $success = 'Engagement mis à jour.';
                    } else {
                        $pdo->prepare("INSERT INTO responsibility_commitment (organisation_id, title, description, icon, sort_order) VALUES (?, ?, ?, ?, ?)")
                            ->execute([$commitment_org_id, $title, $description, $icon, $sort_order]);
                        $success = 'Engagement ajouté.';
                    }
                    $organisation_id = $commitment_org_id;
                } catch (PDOException $e) {
                    $error = 'Erreur : ' . $e->getMessage();
                }
            }
        }
    }

    // ---------- POST: Delete commitment ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_commitment'])) {
        if (!has_permission('admin.documents.modify')) {
            $error = 'Droits insuffisants pour supprimer.';
        } else {
            $delId = (int) $_POST['delete_commitment'];
            try {
                $pdo->prepare("DELETE FROM responsibility_commitment WHERE id = ?")->execute([$delId]);
                $success = 'Engagement supprimé.';
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer.';
            }
        }
    }

    // ---------- POST: Reset default content (fix UTF-8 encoding) ----------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_default_content']) && has_permission('admin.documents.modify')) {
        try {
            $defaultIntro1 = "Politiques et rapports sur nos engagements éthiques, diversité et impact environnemental.";
            $defaultIntro2 = "Notre responsabilité s'exerce vis-à-vis des personnes que nous accompagnons, de nos équipes et de l'environnement.";
            $stmt = $pdo->prepare("SELECT id FROM responsibility_page WHERE organisation_id IS NULL LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_OBJ);
            if ($row) {
                $pdo->prepare("UPDATE responsibility_page SET intro_block1 = ?, intro_block2 = ? WHERE id = ?")
                    ->execute([$defaultIntro1, $defaultIntro2, $row->id]);
            } else {
                $pdo->prepare("INSERT INTO responsibility_page (organisation_id, intro_block1, intro_block2) VALUES (NULL, ?, ?)")
                    ->execute([$defaultIntro1, $defaultIntro2]);
            }
            $defaultCommitments = [
                ['Éthique et intégrité', 'Des politiques et dispositifs encadrent nos pratiques pour garantir l\'intégrité de nos actions.', 'bi-shield-check', 1],
                ['Diversité et inclusion', 'Nous œuvrons pour un environnement inclusif et une représentation équitable au sein de l\'organisation.', 'bi-people', 2],
                ['Environnement', 'Nous nous efforçons de réduire l\'impact environnemental de nos activités et de nos déplacements.', 'bi-globe2', 3],
            ];
            $pdo->prepare("DELETE FROM responsibility_commitment WHERE organisation_id IS NULL")->execute();
            $ins = $pdo->prepare("INSERT INTO responsibility_commitment (organisation_id, title, description, icon, sort_order) VALUES (NULL, ?, ?, ?, ?)");
            foreach ($defaultCommitments as $dc) {
                $ins->execute($dc);
            }
            $success = 'Contenu par défaut réinitialisé avec l\'encodage UTF-8 correct.';
        } catch (PDOException $e) {
            $error = 'Erreur : ' . $e->getMessage();
        }
    }

    // ---------- Load content for selected organisation ----------
    try {
        $stmt = $pdo->prepare("SELECT id, organisation_id, intro_block1, intro_block2 FROM responsibility_page WHERE organisation_id <=> ? LIMIT 1");
        $stmt->execute([$organisation_id]);
        $pageContent = $stmt->fetch(PDO::FETCH_OBJ);

        $stmt = $pdo->prepare("SELECT id, organisation_id, title, description, icon, sort_order FROM responsibility_commitment WHERE organisation_id <=> ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$organisation_id]);
        $commitments = $stmt->fetchAll(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        $pageContent = null;
        $commitments = [];
    }
}

require __DIR__ . '/inc/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item active">Responsabilité</li>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>Contenu – Page Responsabilité</h1>
            <p class="text-muted mb-0">Introduction et engagements affichés sur la page publique Responsabilité.</p>
        </div>
        <a href="<?= htmlspecialchars(dirname($_SERVER['SCRIPT_NAME']) . '/../responsibility.php') ?>" class="btn btn-admin-outline" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i> Voir la page</a>
    </div>
</header>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="admin-card admin-section-card mb-4">
    <form method="GET" class="mb-0">
        <label class="form-label fw-bold">Organisation</label>
        <div class="d-flex gap-2 align-items-center">
            <select name="organisation_id" class="form-select" style="max-width: 320px;" onchange="this.form.submit()">
                <option value="">Contenu par défaut (global)</option>
                <?php foreach ($organisations as $o): ?>
                    <option value="<?= (int) $o->id ?>" <?= $organisation_id === (int) $o->id ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <p class="form-text small mt-1 mb-0">Choisir une organisation pour éditer son contenu spécifique, ou « Contenu par défaut » pour le site sans organisation active.</p>
    </form>
</div>

<div class="admin-card admin-section-card mb-4">
    <h5 class="card-title mb-3"><i class="bi bi-text-paragraph me-2"></i>Introduction (texte sous le titre « Responsabilité »)</h5>
    <?php if (has_permission('admin.documents.modify')): ?>
    <form method="POST" accept-charset="UTF-8">
        <input type="hidden" name="save_intro" value="1">
        <input type="hidden" name="organisation_id" value="<?= $organisation_id !== null ? (int) $organisation_id : '' ?>">
        <div class="mb-3">
            <label class="form-label">Premier paragraphe</label>
            <textarea name="intro_block1" class="form-control" rows="2" placeholder="Politiques et rapports sur nos engagements éthiques, diversité et impact environnemental."><?= htmlspecialchars($pageContent->intro_block1 ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Deuxième paragraphe</label>
            <textarea name="intro_block2" class="form-control" rows="2" placeholder="Notre responsabilité s'exerce vis-à-vis des personnes que nous accompagnons, de nos équipes et de l'environnement."><?= htmlspecialchars($pageContent->intro_block2 ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-admin-primary">Enregistrer l'introduction</button>
    </form>
    <?php if ($organisation_id === null): ?>
    <form method="POST" class="mt-3" accept-charset="UTF-8" onsubmit="return confirm('Réinitialiser l\'introduction et les 3 engagements par défaut (texte correct en UTF-8) ? Les engagements actuels du contenu global seront remplacés.');">
        <input type="hidden" name="reset_default_content" value="1">
        <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser le contenu par défaut (UTF-8)</button>
    </form>
    <p class="form-text small mt-1">Utilisez ce bouton si les accents s'affichent en « ?? ». Les textes par défaut seront réécrits correctement.</p>
    <?php endif; ?>
    <?php else: ?>
    <p class="text-muted mb-0"><?= nl2br(htmlspecialchars(($pageContent->intro_block1 ?? '') . "\n" . ($pageContent->intro_block2 ?? ''))) ?: 'Aucun contenu. Droits de modification requis pour éditer.' ?></p>
    <?php endif; ?>
</div>

<div class="admin-card admin-section-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="card-title mb-0"><i class="bi bi-card-list me-2"></i>Nos engagements</h5>
        <?php if (has_permission('admin.documents.modify')): ?>
        <button type="button" class="btn btn-admin-primary btn-sm" data-bs-toggle="modal" data-bs-target="#commitmentModal" onclick="resetCommitmentForm()"><i class="bi bi-plus me-1"></i> Nouvel engagement</button>
        <?php endif; ?>
    </div>
    <?php if (empty($commitments)): ?>
        <p class="text-muted mb-0">Aucun engagement pour cette organisation. Utilisez « Contenu par défaut » pour gérer les engagements globaux.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table table table-hover">
                <thead>
                    <tr>
                        <th>Ordre</th>
                        <th>Icône</th>
                        <th>Titre</th>
                        <th>Description</th>
                        <?php if (has_permission('admin.documents.modify')): ?><th class="text-end">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commitments as $c): ?>
                        <tr>
                            <td><?= (int) $c->sort_order ?></td>
                            <td><i class="bi <?= htmlspecialchars($c->icon ?? 'bi-file-earmark') ?> text-primary"></i></td>
                            <td><?= htmlspecialchars($c->title) ?></td>
                            <td><?= htmlspecialchars(mb_substr($c->description ?? '', 0, 80)) ?><?= mb_strlen($c->description ?? '') > 80 ? '…' : '' ?></td>
                            <?php if (has_permission('admin.documents.modify')): ?>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick='editCommitment(<?= json_encode($c) ?>)'><i class="bi bi-pencil"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet engagement ?');">
                                    <input type="hidden" name="delete_commitment" value="<?= (int) $c->id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (has_permission('admin.documents.modify')): ?>
<div class="modal fade" id="commitmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" accept-charset="UTF-8">
                <input type="hidden" name="save_commitment" value="1">
                <input type="hidden" name="commitment_id" id="commitment_id" value="0">
                <input type="hidden" name="commitment_organisation_id" value="<?= $organisation_id !== null ? (int) $organisation_id : '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="commitmentModalTitle">Nouvel engagement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Titre *</label>
                        <input type="text" name="title" id="commitment_title" class="form-control" required placeholder="Ex: Éthique et intégrité">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" id="commitment_description" class="form-control" rows="3" placeholder="Courte description de l'engagement."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Icône (Bootstrap Icons)</label>
                        <select name="icon" id="commitment_icon" class="form-select">
                            <option value="">— Aucune (défaut) —</option>
                            <?php foreach ($iconOptions as $ico): ?>
                                <option value="<?= htmlspecialchars($ico) ?>"><?= htmlspecialchars($ico) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Classe Bootstrap Icons, ex. bi-shield-check</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ordre d'affichage</label>
                        <input type="number" name="sort_order" id="commitment_sort_order" class="form-control" value="0" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-admin-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function resetCommitmentForm() {
    document.getElementById('commitment_id').value = 0;
    document.getElementById('commitment_title').value = '';
    document.getElementById('commitment_description').value = '';
    document.getElementById('commitment_icon').value = '';
    document.getElementById('commitment_sort_order').value = '0';
    document.getElementById('commitmentModalTitle').textContent = 'Nouvel engagement';
}
function editCommitment(c) {
    document.getElementById('commitment_id').value = c.id;
    document.getElementById('commitment_title').value = c.title || '';
    document.getElementById('commitment_description').value = c.description || '';
    document.getElementById('commitment_icon').value = c.icon || '';
    document.getElementById('commitment_sort_order').value = c.sort_order ?? 0;
    document.getElementById('commitmentModalTitle').textContent = 'Modifier l\'engagement';
    new bootstrap.Modal(document.getElementById('commitmentModal')).show();
}
</script>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Tableau de bord</a>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>

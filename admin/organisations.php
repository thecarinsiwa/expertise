<?php
$pageTitle = 'Organisations – Administration';
$currentNav = 'organisations';
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.organisations.view');
require __DIR__ . '/inc/db.php';

$list = [];
$detail = null;
$stats = ['total' => 0, 'active' => 0];
$error = '';
$success = '';
$organisationTypes = [
    'company' => 'Société commerciale',
    'association' => 'Association',
    'ngo' => 'ONG',
    'public' => 'Administration / Public',
    'other' => 'Autre',
];

if ($pdo) {
    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_id'])) {
            require_permission('admin.organisations.delete');
            $delId = (int) $_POST['delete_id'];
            try {
                $pdo->prepare("DELETE FROM organisation WHERE id = ?")->execute([$delId]);
                header('Location: organisations.php?msg=deleted');
                exit;
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer : des enregistrements dépendent de cette organisation.';
            }
        }
        if (isset($_POST['save_organisation'])) {
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '') ?: null;
            $description = trim($_POST['description'] ?? '') ?: null;
            $address = trim($_POST['address'] ?? '') ?: null;
            $phone = trim($_POST['phone'] ?? '') ?: null;
            $email = trim($_POST['email'] ?? '') ?: null;
            $website = trim($_POST['website'] ?? '') ?: null;
            $postal_code = trim($_POST['postal_code'] ?? '') ?: null;
            $city = trim($_POST['city'] ?? '') ?: null;
            $country = trim($_POST['country'] ?? '') ?: null;
            $rccm = trim($_POST['rccm'] ?? '') ?: null;
            $nif = trim($_POST['nif'] ?? '') ?: null;
            $organisation_types = isset($_POST['organisation_types']) && is_array($_POST['organisation_types'])
                ? array_intersect($_POST['organisation_types'], array_keys($organisationTypes)) : [];
            $sector = trim($_POST['sector'] ?? '') ?: null;
            $notes = trim($_POST['notes'] ?? '') ?: null;
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            $facebook_url = trim($_POST['facebook_url'] ?? '') ?: null;
            $linkedin_url = trim($_POST['linkedin_url'] ?? '') ?: null;
            $twitter_url = trim($_POST['twitter_url'] ?? '') ?: null;
            $instagram_url = trim($_POST['instagram_url'] ?? '') ?: null;
            $youtube_url = trim($_POST['youtube_url'] ?? '') ?: null;

            $logo = null;
            $cover_image = null;
            if ($id > 0) {
                $cur = $pdo->prepare("SELECT logo, cover_image FROM organisation WHERE id = ?");
                $cur->execute([$id]);
                $row = $cur->fetch();
                if ($row) {
                    if (!empty($row->logo)) $logo = $row->logo;
                    if (!empty($row->cover_image)) $cover_image = $row->cover_image;
                }
            }
            if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $target_dir = __DIR__ . '/../uploads/organisations/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $file_name = 'logo_' . ($id ?: 'new') . '_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_dir . $file_name)) {
                        $logo = 'uploads/organisations/' . $file_name;
                    }
                }
            }
            if (!empty($_FILES['cover_image']['name']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $target_dir = __DIR__ . '/../uploads/organisations/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $file_name = 'cover_' . ($id ?: 'new') . '_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_dir . $file_name)) {
                        $cover_image = 'uploads/organisations/' . $file_name;
                    }
                }
            }

            if ($name === '') {
                $error = 'Le nom est obligatoire.';
            } else {
                if ($id > 0) {
                    require_permission('admin.organisations.modify');
                    $stmt = $pdo->prepare("UPDATE organisation SET name = ?, code = ?, description = ?, address = ?, phone = ?, email = ?, website = ?, postal_code = ?, city = ?, country = ?, rccm = ?, nif = ?, sector = ?, notes = ?, logo = ?, cover_image = ?, facebook_url = ?, linkedin_url = ?, twitter_url = ?, instagram_url = ?, youtube_url = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$name, $code, $description, $address, $phone, $email, $website, $postal_code, $city, $country, $rccm, $nif, $sector, $notes, $logo, $cover_image, $facebook_url, $linkedin_url, $twitter_url, $instagram_url, $youtube_url, $is_active, $id]);
                    $pdo->prepare("DELETE FROM organisation_organisation_type WHERE organisation_id = ?")->execute([$id]);
                    $stmtTypes = $pdo->prepare("INSERT INTO organisation_organisation_type (organisation_id, type_code) VALUES (?, ?)");
                    foreach ($organisation_types as $tc) {
                        $stmtTypes->execute([$id, $tc]);
                    }
                    $success = 'Organisation mise à jour.';
                    $action = 'view';
                } else {
                    require_permission('admin.organisations.add');
                    $stmt = $pdo->prepare("INSERT INTO organisation (name, code, description, address, phone, email, website, postal_code, city, country, rccm, nif, sector, notes, logo, cover_image, facebook_url, linkedin_url, twitter_url, instagram_url, youtube_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $code, $description, $address, $phone, $email, $website, $postal_code, $city, $country, $rccm, $nif, $sector, $notes, $logo, $cover_image, $facebook_url, $linkedin_url, $twitter_url, $instagram_url, $youtube_url, $is_active]);
                    $newId = (int) $pdo->lastInsertId();
                    $stmtTypes = $pdo->prepare("INSERT INTO organisation_organisation_type (organisation_id, type_code) VALUES (?, ?)");
                    foreach ($organisation_types as $tc) {
                        $stmtTypes->execute([$newId, $tc]);
                    }
                    header('Location: organisations.php?id=' . $newId . '&msg=created');
                    exit;
                }
            }
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM organisation WHERE id = ?");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();
        if ($detail) {
            $detail->organisation_types = [];
            $stmtTypes = $pdo->prepare("SELECT type_code FROM organisation_organisation_type WHERE organisation_id = ?");
            $stmtTypes->execute([$id]);
            while ($row = $stmtTypes->fetch()) {
                $detail->organisation_types[] = $row->type_code;
            }
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM department WHERE organisation_id = ?");
            $stmtCount->execute([$id]);
            $detail->count_departments = (int) $stmtCount->fetchColumn();
            $stmtStaff = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE organisation_id = ?");
            $stmtStaff->execute([$id]);
            $detail->count_staff = (int) $stmtStaff->fetchColumn();
            $stmtUser = $pdo->prepare("SELECT COUNT(*) FROM user WHERE organisation_id = ?");
            $stmtUser->execute([$id]);
            $detail->count_users = (int) $stmtUser->fetchColumn();
        }
    }

    if (!$detail || $action === 'list') {
        $stats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM organisation")->fetchColumn();
        $stats['active'] = (int) $pdo->query("SELECT COUNT(*) FROM organisation WHERE is_active = 1")->fetchColumn();
        $stmt = $pdo->query("SELECT id, name, code, logo, is_active, created_at FROM organisation ORDER BY name");
        if ($stmt) $list = $stmt->fetchAll();
    }
}
require __DIR__ . '/inc/header.php';

$isForm = ($action === 'add') || ($action === 'edit' && $detail);
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="organisations.php" class="text-decoration-none">Structure</a></li>
        <li class="breadcrumb-item"><a href="organisations.php" class="text-decoration-none">Organisations</a></li>
        <?php if ($action === 'add'): ?>
            <li class="breadcrumb-item active">Nouvelle</li>
        <?php elseif ($action === 'edit' && $detail): ?>
            <li class="breadcrumb-item active">Modifier</li>
        <?php elseif ($detail): ?>
            <li class="breadcrumb-item active"><?= htmlspecialchars($detail->name) ?></li>
        <?php endif; ?>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>
                <?php
                if ($action === 'add') echo 'Nouvelle organisation';
                elseif ($action === 'edit' && $detail) echo 'Modifier l\'organisation';
                elseif ($detail) echo htmlspecialchars($detail->name);
                else echo 'Organisations';
                ?>
            </h1>
            <p class="text-muted mb-0">
                <?php
                if ($detail && !$isForm) echo 'Fiche organisation';
                elseif ($action === 'add') echo 'Créer une nouvelle organisation.';
                else echo 'Gestion des organisations.';
                ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail && $action !== 'edit'): ?>
                <?php if (has_permission('admin.organisations.modify')): ?><a href="organisations.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a><?php endif; ?>
                <a href="units.php?organisation_id=<?= (int) $detail->id ?>" class="btn btn-admin-outline"><i class="bi bi-diagram-3 me-1"></i> Unités & Services</a>
                <a href="organisations.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php elseif ($isForm): ?>
                <a href="organisations.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Annuler</a>
            <?php else: ?>
                <?php if (has_permission('admin.organisations.add')): ?><a href="organisations.php?action=add" class="btn btn-admin-primary"><i class="bi bi-building-add me-1"></i> Nouvelle organisation</a><?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">L'organisation a été supprimée.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Organisation créée.</div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!$detail && !$isForm): ?>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Total</div>
                <div class="h3 mb-0 fw-bold"><?= $stats['total'] ?></div>
                <div class="mt-2"><span class="badge bg-secondary-subtle text-secondary border">Organisations</span></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Actives</div>
                <div class="h3 mb-0 fw-bold text-success"><?= $stats['active'] ?></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($isForm && (($action === 'add' && has_permission('admin.organisations.add')) || ($action === 'edit' && has_permission('admin.organisations.modify')))): ?>
    <div class="admin-card admin-section-card">
        <form method="POST" action="<?= $id ? 'organisations.php?action=edit&id=' . $id : 'organisations.php?action=add' ?>" enctype="multipart/form-data">
            <input type="hidden" name="save_organisation" value="1">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-bold">Nom *</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($detail->name ?? '') ?>" required placeholder="Nom de l'organisation">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Code</label>
                    <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($detail->code ?? '') ?>" placeholder="ex: ORG-01">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" id="organisation_description" class="form-control" rows="6" placeholder="Description de l'organisation (texte enrichi)"></textarea>
                    <script type="application/json" id="organisation_description_data"><?= json_encode(isset($detail->description) ? $detail->description : '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Adresse</label>
                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($detail->address ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Téléphone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($detail->phone ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($detail->email ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Site web</label>
                    <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($detail->website ?? '') ?>" placeholder="https://">
                </div>
                <div class="col-12"><h6 class="text-muted mb-2">Coordonnées</h6></div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Code postal</label>
                    <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($detail->postal_code ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Ville</label>
                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($detail->city ?? '') ?>" placeholder="ex: Kinshasa, Lubumbashi, Goma">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Pays</label>
                    <input type="text" name="country" class="form-control" value="<?= htmlspecialchars(isset($detail) ? ($detail->country ?? 'République Démocratique du Congo') : 'République Démocratique du Congo') ?>" placeholder="République Démocratique du Congo">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">RCCM</label>
                    <input type="text" name="rccm" class="form-control" value="<?= htmlspecialchars($detail->rccm ?? '') ?>" placeholder="Registre de Commerce et de Crédit Mobilier">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">NIF</label>
                    <input type="text" name="nif" class="form-control" value="<?= htmlspecialchars($detail->nif ?? '') ?>" placeholder="Numéro d'Identification Fiscal (DGI)">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Type(s) d'organisation</label>
                    <div class="d-flex flex-wrap gap-3">
                        <?php
                        $selectedTypes = isset($detail) && isset($detail->organisation_types) ? $detail->organisation_types : [];
                        foreach ($organisationTypes as $k => $v):
                        ?>
                            <div class="form-check">
                                <input type="checkbox" name="organisation_types[]" id="org_type_<?= $k ?>" class="form-check-input" value="<?= htmlspecialchars($k) ?>" <?= in_array($k, $selectedTypes) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="org_type_<?= $k ?>"><?= $v ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Secteur d'activité</label>
                    <input type="text" name="sector" class="form-control" value="<?= htmlspecialchars($detail->sector ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Notes internes</label>
                    <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($detail->notes ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Logo</label>
                    <?php if (!empty($detail->logo)): ?>
                        <div class="mb-2">
                            <img src="../<?= htmlspecialchars($detail->logo) ?>" alt="Logo" class="rounded border" style="height: 60px; max-width: 120px; object-fit: contain;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Photo de couverture</label>
                    <p class="text-muted small mb-1">Image d'en-tête affichée sur la page « Qui nous sommes » (bannière).</p>
                    <?php if (!empty($detail->cover_image)): ?>
                        <div class="mb-2">
                            <img src="../<?= htmlspecialchars($detail->cover_image) ?>" alt="Couverture" class="rounded border" style="max-height: 120px; max-width: 100%; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="cover_image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                </div>
                <div class="col-12"><h6 class="text-muted mb-2">Réseaux sociaux</h6></div>
                <div class="col-md-6">
                    <label class="form-label">Facebook</label>
                    <input type="url" name="facebook_url" class="form-control" value="<?= htmlspecialchars(isset($detail) ? ($detail->facebook_url ?? '') : '') ?>" placeholder="https://facebook.com/...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">LinkedIn</label>
                    <input type="url" name="linkedin_url" class="form-control" value="<?= htmlspecialchars(isset($detail) ? ($detail->linkedin_url ?? '') : '') ?>" placeholder="https://linkedin.com/company/...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Twitter / X</label>
                    <input type="url" name="twitter_url" class="form-control" value="<?= htmlspecialchars(isset($detail) ? ($detail->twitter_url ?? '') : '') ?>" placeholder="https://twitter.com/...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Instagram</label>
                    <input type="url" name="instagram_url" class="form-control" value="<?= htmlspecialchars(isset($detail) ? ($detail->instagram_url ?? '') : '') ?>" placeholder="https://instagram.com/...">
                </div>
                <div class="col-md-6">
                    <label class="form-label">YouTube</label>
                    <input type="url" name="youtube_url" class="form-control" value="<?= htmlspecialchars(isset($detail) ? ($detail->youtube_url ?? '') : '') ?>" placeholder="https://youtube.com/...">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="is_active_org" class="form-check-input" value="1" <?= ($detail && $detail->is_active) || !$detail ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active_org">Organisation active</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-admin-primary"><i class="bi bi-check-lg me-1"></i> Enregistrer</button>
                    <a href="organisations.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
    <style>.organisation-description-editor h1,.organisation-description-editor h2,.organisation-description-editor h3,.organisation-description-editor h4{ margin: 1rem 0 0.5rem; font-size: 1rem; font-weight: 600; } .organisation-description-editor p{ margin: 0.5rem 0; } .organisation-description-editor ul,.organisation-description-editor ol{ margin: 0.5rem 0; padding-left: 1.5rem; } .organisation-description-editor img{ max-width: 100%; height: auto; }</style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined' && $.fn.summernote) {
            $('#organisation_description').summernote({
                placeholder: 'Description de l\'organisation (texte enrichi)...',
                tabsize: 2,
                height: 220,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                callbacks: {
                    onImageUpload: function(files) {
                        for (var i = 0; i < files.length; i++) {
                            var data = new FormData();
                            data.append('file', files[i]);
                            $.ajax({
                                url: 'upload_organisation_image.php',
                                data: data,
                                processData: false,
                                contentType: false,
                                type: 'POST',
                                success: function(res) {
                                    if (res && res.url) $('#organisation_description').summernote('insertImage', res.url);
                                },
                                error: function(xhr) {
                                    try {
                                        var r = (xhr.responseJSON || {});
                                        alert(r.error || 'Erreur lors de l\'upload de l\'image.');
                                    } catch (e) { alert('Erreur lors de l\'upload de l\'image.'); }
                                }
                            });
                        }
                    }
                }
            });
            var dataEl = document.getElementById('organisation_description_data');
            if (dataEl) {
                try {
                    var content = dataEl.textContent ? JSON.parse(dataEl.textContent) : '';
                    if (content) $('#organisation_description').summernote('code', content);
                } catch (e) {}
            }
        }
    });
    </script>
<?php endif; ?>

<?php if ($detail && !$isForm): ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-building"></i> Informations</h5>
        <div class="d-flex flex-wrap gap-4 align-items-start mb-4">
            <?php if (!empty($detail->logo)): ?>
                <img src="../<?= htmlspecialchars($detail->logo) ?>" alt="Logo" class="rounded border flex-shrink-0" style="width: 80px; height: 80px; object-fit: contain;">
            <?php endif; ?>
            <?php if (!empty($detail->cover_image)): ?>
                <img src="../<?= htmlspecialchars($detail->cover_image) ?>" alt="Photo de couverture" class="rounded border flex-shrink-0" style="max-width: 280px; max-height: 120px; object-fit: cover;">
            <?php endif; ?>
            <?php $na = '-'; ?>
            <table class="admin-table mb-0 flex-grow-1">
            <tr><th style="width:180px;">Nom</th><td><?= htmlspecialchars($detail->name) ?></td></tr>
            <tr><th>Code</th><td><?= htmlspecialchars($detail->code ?? '—') ?></td></tr>
            <tr><th>Description</th><td><div class="organisation-description-view"><?php
                $allowedDescTags = '<p><br><strong><b><em><i><u><a><ul><ol><li><h2><h3><h4><img><table><thead><tbody><tr><td><th><span><div>';
                echo (isset($detail->description) && $detail->description !== '') ? strip_tags($detail->description, $allowedDescTags) : $na;
            ?></div></td></tr>
            <tr><th>Adresse</th><td><?= nl2br(htmlspecialchars($detail->address ?? $na)) ?></td></tr>
            <tr><th>Code postal</th><td><?= htmlspecialchars($detail->postal_code ?? $na) ?></td></tr>
            <tr><th>Ville</th><td><?= htmlspecialchars($detail->city ?? $na) ?></td></tr>
            <tr><th>Pays</th><td><?= htmlspecialchars($detail->country ?? $na) ?></td></tr>
            <tr><th>Téléphone</th><td><?= htmlspecialchars($detail->phone ?? $na) ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($detail->email ?? $na) ?></td></tr>
            <tr><th>Site web</th><td><?= $detail->website ? '<a href="' . htmlspecialchars($detail->website) . '" target="_blank" rel="noopener">' . htmlspecialchars($detail->website) . '</a>' : $na ?></td></tr>
            <tr><th>RCCM</th><td><?= htmlspecialchars($detail->rccm ?? $na) ?></td></tr>
            <tr><th>NIF</th><td><?= htmlspecialchars($detail->nif ?? $na) ?></td></tr>
            <tr><th>Type(s) d'organisation</th><td>
                <?php
                $detailTypes = $detail->organisation_types ?? [];
                if (!empty($detailTypes)) {
                    echo implode(', ', array_map(function ($code) use ($organisationTypes) { return $organisationTypes[$code] ?? $code; }, $detailTypes));
                } else {
                    echo $na;
                }
                ?>
            </td></tr>
            <tr><th>Secteur d'activité</th><td><?= htmlspecialchars($detail->sector ?? '—') ?></td></tr>
            <tr><th>Statut</th><td><?= $detail->is_active ? '<span class="badge bg-success-subtle text-success border">Active</span>' : '<span class="badge bg-secondary-subtle text-secondary border">Inactive</span>' ?></td></tr>
            <?php
            $socialUrls = [
                'Facebook' => $detail->facebook_url ?? null,
                'LinkedIn' => $detail->linkedin_url ?? null,
                'Twitter / X' => $detail->twitter_url ?? null,
                'Instagram' => $detail->instagram_url ?? null,
                'YouTube' => $detail->youtube_url ?? null,
            ];
            $hasSocial = array_filter($socialUrls);
            if (!empty($hasSocial)): ?>
            <tr><th>Réseaux sociaux</th><td>
                <?php foreach ($socialUrls as $label => $url): if (empty($url)) continue; ?>
                    <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener noreferrer" class="me-3"><?= htmlspecialchars($label) ?></a>
                <?php endforeach; ?>
            </td></tr>
            <?php endif; ?>
            <?php if (!empty($detail->notes)): ?>
            <tr><th>Notes</th><td><span class="text-muted"><?= nl2br(htmlspecialchars($detail->notes)) ?></span></td></tr>
            <?php endif; ?>
        </table>
        </div>
        <h5 class="card-title"><i class="bi bi-diagram-3"></i> Synthèse</h5>
        <div class="row g-2 mb-4">
            <div class="col-auto"><span class="badge bg-primary"><?= (int)($detail->count_departments ?? 0) ?> département(s)</span></div>
            <div class="col-auto"><span class="badge bg-info"><?= (int)($detail->count_staff ?? 0) ?> personnel</span></div>
            <div class="col-auto"><span class="badge bg-secondary"><?= (int)($detail->count_users ?? 0) ?> utilisateur(s)</span></div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <?php if (has_permission('admin.organisations.modify')): ?><a href="organisations.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a><?php endif; ?>
            <a href="units.php?organisation_id=<?= (int) $detail->id ?>" class="btn btn-admin-outline"><i class="bi bi-diagram-3 me-1"></i> Unités & Services</a>
            <a href="organisations.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php if (has_permission('admin.organisations.delete')): ?><button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteOrgModal"><i class="bi bi-trash me-1"></i> Supprimer</button><?php endif; ?>
        </div>
    </div>
    <?php if (has_permission('admin.organisations.delete')): ?>
    <div class="modal fade" id="deleteOrgModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Supprimer cette organisation ?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    L'organisation <strong><?= htmlspecialchars($detail->name) ?></strong> et toutes les données liées (départements, services, unités) pourront être supprimées. Cette action est irréversible.
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="delete_id" value="<?= (int) $detail->id ?>">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php elseif (count($list) > 0): ?>
    <div class="admin-card admin-section-card">
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="orgTable">
                <thead>
                    <tr>
                        <th>Organisation</th>
                        <th>Code</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $o): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!empty($o->logo)): ?>
                                        <img src="../<?= htmlspecialchars($o->logo) ?>" alt="" class="rounded flex-shrink-0" style="width: 32px; height: 32px; object-fit: contain;">
                                    <?php endif; ?>
                                    <a href="organisations.php?id=<?= (int) $o->id ?>"><?= htmlspecialchars($o->name) ?></a>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($o->code ?? '—') ?></td>
                            <td><?= $o->is_active ? '<span class="badge bg-success-subtle text-success border">Active</span>' : '<span class="badge bg-secondary-subtle text-secondary border">Inactive</span>' ?></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item" href="organisations.php?id=<?= (int) $o->id ?>"><i class="bi bi-eye me-2"></i> Voir</a></li>
                                        <?php if (has_permission('admin.organisations.modify')): ?><li><a class="dropdown-item" href="organisations.php?action=edit&id=<?= (int) $o->id ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li><?php endif; ?>
                                        <li><a class="dropdown-item" href="units.php?organisation_id=<?= (int) $o->id ?>"><i class="bi bi-diagram-3 me-2"></i> Unités & Services</a></li>
                                        <?php if (has_permission('admin.organisations.delete')): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" onsubmit="return confirm('Supprimer cette organisation ?');">
                                                <input type="hidden" name="delete_id" value="<?= (int) $o->id ?>">
                                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i> Supprimer</button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<style>
    .breadcrumb { font-size: 0.85rem; }
    .admin-table th { background: #f8f9fa; padding: 1rem 0.5rem; }
    .admin-table td { padding: 1rem 0.5rem; }
</style>
<?php if (!empty($list)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('orgTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#orgTable').DataTable({
            language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" },
            order: [[0, "asc"]],
            pageLength: 10,
            dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>'
        });
    }
});
</script>
<?php endif; ?>

<footer class="admin-main-footer">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Tableau de bord</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>

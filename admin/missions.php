<?php
/**
 * Gestion des Missions - CRUD Complet
 */
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$pageTitle = 'Missions – Administration';
$currentNav = 'missions';

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$error = '';
$success = '';

// --- REQUÊTES POUR LES关联 TABLES ---
$missionTypes = $pdo->query("SELECT id, name FROM mission_type ORDER BY name ASC")->fetchAll();
$missionStatuses = $pdo->query("SELECT id, name, code FROM mission_status ORDER BY sort_order ASC")->fetchAll();
$allStaff = $pdo->query("
    SELECT s.id, u.id as user_id, u.first_name, u.last_name, p.title as job_title
    FROM staff s 
    JOIN user u ON s.user_id = u.id 
    LEFT JOIN assignment a ON s.id = a.staff_id AND a.is_primary = 1
    LEFT JOIN position p ON a.position_id = p.id
    ORDER BY u.last_name ASC
")->fetchAll();

$allUsers = $pdo->query("SELECT id, first_name, last_name, email FROM user ORDER BY last_name ASC")->fetchAll();

// --- TRAITEMENT DES ACTIONS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    // SUPPRESSION
    if (isset($_POST['delete_id'])) {
        $delId = (int) $_POST['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM mission WHERE id = ?");
        if ($stmt->execute([$delId])) {
            header('Location: missions.php?msg=deleted');
            exit;
        }
    }

    // ENREGISTREMENT (AJOUT / MODIFICATION)
    if (isset($_POST['save_mission'])) {
        $title = trim($_POST['title'] ?? '');
        $reference = trim($_POST['reference'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $mission_type_id = !empty($_POST['mission_type_id']) ? (int) $_POST['mission_type_id'] : null;
        $mission_status_id = !empty($_POST['mission_status_id']) ? (int) $_POST['mission_status_id'] : null;
        $organisation_id = 1;

        // Données liées
        $objectives = $_POST['objectives'] ?? [];
        $staff_ids = $_POST['staff_ids'] ?? [];
        $step_titles = $_POST['step_titles'] ?? [];
        $step_contents = $_POST['step_contents'] ?? [];
        $existing_step_images = $_POST['existing_step_images'] ?? [];

        if (empty($title)) {
            $error = "Le titre est obligatoire.";
        } else {
            try {
                $pdo->beginTransaction();

                // Gestion photo de couverture
                $cover_image = $detail->cover_image ?? null;
                if (!empty($_FILES['cover_image']['name'])) {
                    $target_dir = "../uploads/missions/";
                    if (!is_dir($target_dir))
                        mkdir($target_dir, 0777, true);
                    $file_ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
                    $file_name = "cover_" . time() . "_" . uniqid() . "." . $file_ext;
                    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_dir . $file_name)) {
                        $cover_image = "uploads/missions/" . $file_name;
                    }
                }

                if ($id > 0) {
                    // Update
                    $stmt = $pdo->prepare("
                        UPDATE mission SET 
                        title = ?, reference = ?, description = ?, 
                        cover_image = ?, location = ?, start_date = ?, end_date = ?, 
                        mission_type_id = ?, mission_status_id = ?,
                        updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $reference, $description, $cover_image, $location, $start_date, $end_date, $mission_type_id, $mission_status_id, $id]);
                    $missionId = $id;
                } else {
                    // Insert
                    $stmt = $pdo->prepare("
                        INSERT INTO mission (organisation_id, title, reference, description, cover_image, location, start_date, end_date, mission_type_id, mission_status_id, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$organisation_id, $title, $reference, $description, $cover_image, $location, $start_date, $end_date, $mission_type_id, $mission_status_id]);
                    $missionId = $pdo->lastInsertId();
                }

                // --- GESTION DES OBJECTIFS ---
                $pdo->prepare("DELETE FROM objective WHERE mission_id = ?")->execute([$missionId]);
                if (!empty($objectives)) {
                    $stmtObj = $pdo->prepare("INSERT INTO objective (mission_id, title, is_achieved) VALUES (?, ?, 0)");
                    foreach ($objectives as $objTitle) {
                        if (trim($objTitle))
                            $stmtObj->execute([$missionId, trim($objTitle)]);
                    }
                }

                // --- GESTION DU PERSONNEL ---
                $pdo->prepare("DELETE FROM mission_assignment WHERE mission_id = ?")->execute([$missionId]);
                if (!empty($staff_ids)) {
                    $stmtStaff = $pdo->prepare("INSERT INTO mission_assignment (mission_id, user_id, start_date, end_date) VALUES (?, ?, ?, ?)");
                    foreach ($staff_ids as $uId) {
                        $stmtStaff->execute([$missionId, $uId, $start_date, $end_date]);
                    }
                }

                // --- GESTION DES ÉTAPES (STEPS) ---
                $pdo->prepare("DELETE FROM mission_plan WHERE mission_id = ?")->execute([$missionId]);
                $stmtStep = $pdo->prepare("INSERT INTO mission_plan (mission_id, title, content, image_url, sequence) VALUES (?, ?, ?, ?, ?)");

                foreach ($step_titles as $index => $stepTitle) {
                    if (empty(trim($stepTitle)))
                        continue;

                    $stepContent = $step_contents[$index] ?? '';
                    $stepImage = $existing_step_images[$index] ?? null;

                    // Gestion photo de l'étape
                    if (!empty($_FILES['step_images']['name'][$index])) {
                        $target_dir = "../uploads/missions/steps/";
                        if (!is_dir($target_dir))
                            mkdir($target_dir, 0777, true);
                        $file_ext = strtolower(pathinfo($_FILES['step_images']['name'][$index], PATHINFO_EXTENSION));
                        $file_name = "step_" . $missionId . "_" . $index . "_" . time() . "." . $file_ext;
                        if (move_uploaded_file($_FILES['step_images']['tmp_name'][$index], $target_dir . $file_name)) {
                            $stepImage = "uploads/missions/steps/" . $file_name;
                        }
                    }

                    $stmtStep->execute([$missionId, trim($stepTitle), $stepContent, $stepImage, $index]);
                }

                $pdo->commit();

                if ($id > 0) {
                    $success = "Mission mise à jour avec succès.";
                    $action = 'view';
                } else {
                    header('Location: missions.php?id=' . $missionId . '&msg=created');
                    exit;
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Erreur : " . $e->getMessage();
            }
        }

        // GESTION DES ORDRES DE MISSION (AJOUT/MODIF)
        if (isset($_POST['save_order'])) {
            $m_id = (int) $_POST['mission_id'];
            $num = trim($_POST['order_number'] ?? '');
            $date = $_POST['issue_date'] ?: null;
            $auth_by = (int) $_POST['authorised_by_user_id'] ?: null;
            $notes = trim($_POST['notes'] ?? '');
            $status = $_POST['status'] ?? 'draft';

            $stmt = $pdo->prepare("SELECT id FROM mission_order WHERE mission_id = ?");
            $stmt->execute([$m_id]);
            $exists = $stmt->fetch();

            if ($exists) {
                $stmt = $pdo->prepare("UPDATE mission_order SET order_number = ?, issue_date = ?, authorised_by_user_id = ?, notes = ?, status = ? WHERE mission_id = ?");
                $stmt->execute([$num, $date, $auth_by, $notes, $status, $m_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO mission_order (mission_id, order_number, issue_date, authorised_by_user_id, notes, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$m_id, $num, $date, $auth_by, $notes, $status]);
            }
            $success = "Ordre de mission enregistré.";
        }

        // GESTION DES RAPPORTS (AJOUT/MODIF)
        if (isset($_POST['save_report'])) {
            $repId = (int) $_POST['report_id'];
            $m_id = (int) $_POST['mission_id'];
            $title = trim($_POST['report_title'] ?? '');
            $summary = trim($_POST['summary'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $date = $_POST['report_date'] ?: date('Y-m-d');
            $author = (int) $_POST['author_user_id'];
            $status = $_POST['status'] ?? 'draft';

            if ($repId > 0) {
                $stmt = $pdo->prepare("UPDATE mission_report SET title = ?, summary = ?, content = ?, report_date = ?, author_user_id = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $summary, $content, $date, $author, $status, $repId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO mission_report (mission_id, author_user_id, title, summary, content, report_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$m_id, $author, $title, $summary, $content, $date, $status]);
            }
            $success = "Rapport enregistré.";
        }

        // SUPPRESSION RAPPORT
        if (isset($_POST['delete_report_id'])) {
            $pdo->prepare("DELETE FROM mission_report WHERE id = ?")->execute([(int) $_POST['delete_report_id']]);
            $success = "Rapport supprimé.";
        }

        // GESTION DES FRAIS (AJOUT/MODIF)
        if (isset($_POST['save_expense'])) {
            $expId = (int) $_POST['expense_id'];
            $m_id = (int) $_POST['mission_id'];
            $cat = trim($_POST['category'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $amount = (float) $_POST['amount'];
            $currency = $_POST['currency'] ?? 'XOF';
            $date = $_POST['expense_date'] ?: date('Y-m-d');
            $beneficiary = (int) $_POST['user_id'];
            $status = $_POST['status'] ?? 'pending';

            if ($expId > 0) {
                $stmt = $pdo->prepare("UPDATE mission_expense SET category = ?, description = ?, amount = ?, currency = ?, expense_date = ?, user_id = ?, status = ? WHERE id = ?");
                $stmt->execute([$cat, $desc, $amount, $currency, $date, $beneficiary, $status, $expId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO mission_expense (mission_id, user_id, category, description, amount, currency, expense_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$m_id, $beneficiary, $cat, $desc, $amount, $currency, $date, $status]);
            }
            $success = "Dépense enregistrée.";
        }

        // SUPPRESSION FRAIS
        if (isset($_POST['delete_expense_id'])) {
            $pdo->prepare("DELETE FROM mission_expense WHERE id = ?")->execute([(int) $_POST['delete_expense_id']]);
            $success = "Dépense supprimée.";
        }
    }
}

// --- CHARGEMENT DES DONNÉES ---
$missions = [];
$detail = null;
$search = trim($_GET['s'] ?? '');

if ($pdo) {
    if ($id > 0) {
        $stmt = $pdo->prepare("
            SELECT m.*, mt.name as type_name, ms.name as status_name, ms.code as status_code 
            FROM mission m 
            LEFT JOIN mission_type mt ON m.mission_type_id = mt.id 
            LEFT JOIN mission_status ms ON m.mission_status_id = ms.id 
            WHERE m.id = ?
        ");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();

        if ($detail) {
            // Objectifs

            $stmtObj = $pdo->prepare("SELECT * FROM objective WHERE mission_id = ? ORDER BY id ASC");
            $stmtObj->execute([$id]);
            $detail->objectives = $stmtObj->fetchAll();

            // Personnel assigné
            $stmtStaff = $pdo->prepare("
                SELECT u.id as user_id, u.first_name, u.last_name, p.title as job_title
                FROM user u
                JOIN mission_assignment ma ON u.id = ma.user_id 
                LEFT JOIN staff s ON u.id = s.user_id
                LEFT JOIN assignment a ON s.id = a.staff_id AND a.is_primary = 1
                LEFT JOIN position p ON a.position_id = p.id
                WHERE ma.mission_id = ?
            ");
            $stmtStaff->execute([$id]);
            $detail->staff = $stmtStaff->fetchAll();
            $detail->staff_ids = array_column($detail->staff, 'user_id');

            // Étapes de la mission
            $stmtSteps = $pdo->prepare("SELECT * FROM mission_plan WHERE mission_id = ? ORDER BY sequence ASC, id ASC");
            $stmtSteps->execute([$id]);
            $detail->steps = $stmtSteps->fetchAll();

            // Ordres de mission
            $stmtOrder = $pdo->prepare("SELECT * FROM mission_order WHERE mission_id = ?");
            $stmtOrder->execute([$id]);
            $detail->order = $stmtOrder->fetch();

            // Rapports
            $stmtReports = $pdo->prepare("SELECT * FROM mission_report WHERE mission_id = ? ORDER BY report_date DESC");
            $stmtReports->execute([$id]);
            $detail->reports = $stmtReports->fetchAll();

            // Frais
            $stmtExpenses = $pdo->prepare("SELECT * FROM mission_expense WHERE mission_id = ? ORDER BY id DESC");
            $stmtExpenses->execute([$id]);
            $detail->expenses = $stmtExpenses->fetchAll();
            $detail->expenses_total = array_sum(array_column($detail->expenses, 'amount'));

            if ($action === 'list')
                $action = 'view';
        }
    }

    if ($action === 'list') {
        $sql = "
            SELECT m.*, mt.name as type_name, ms.name as status_name, ms.code as status_code 
            FROM mission m 
            LEFT JOIN mission_type mt ON m.mission_type_id = mt.id 
            LEFT JOIN mission_status ms ON m.mission_status_id = ms.id 
        ";
        $params = [];
        if ($search) {
            $sql .= " WHERE m.title LIKE ? OR m.location LIKE ? OR m.reference LIKE ?";
            $params = ["%$search%", "%$search%", "%$search%"];
        }
        $sql .= " ORDER BY m.updated_at DESC, m.start_date DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $missions = $stmt->fetchAll();

        // Récupération des stats par statut
        $dashStats = [
            'total' => (int) $pdo->query("SELECT COUNT(*) FROM mission")->fetchColumn(),
            'planned' => (int) $pdo->query("SELECT COUNT(*) FROM mission m JOIN mission_status ms ON m.mission_status_id = ms.id WHERE ms.code = 'planned'")->fetchColumn(),
            'in_progress' => (int) $pdo->query("SELECT COUNT(*) FROM mission m JOIN mission_status ms ON m.mission_status_id = ms.id WHERE ms.code = 'in_progress'")->fetchColumn(),
            'completed' => (int) $pdo->query("SELECT COUNT(*) FROM mission m JOIN mission_status ms ON m.mission_status_id = ms.id WHERE ms.code = 'completed'")->fetchColumn(),
        ];
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted')
        $success = "Mission supprimée avec succès.";
    if ($_GET['msg'] === 'created')
        $success = "Mission créée avec succès.";
}

require __DIR__ . '/inc/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="missions.php" class="text-decoration-none">Missions</a></li>
        <?php if ($action === 'add'): ?>
            <li class="breadcrumb-item active">Nouvelle</li><?php endif; ?>
        <?php if ($action === 'edit'): ?>
            <li class="breadcrumb-item active">Modifier</li><?php endif; ?>
        <?php if ($action === 'view'): ?>
            <li class="breadcrumb-item active">Détail</li><?php endif; ?>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>
                <?php
                if ($action === 'add')
                    echo 'Nouvelle Mission';
                elseif ($action === 'edit')
                    echo 'Modifier Mission';
                elseif ($action === 'view')
                    echo 'Détail Mission';
                else
                    echo 'Missions';
                ?>
            </h1>
            <p>
                <?php
                if ($detail && ($action === 'view' || $action === 'edit'))
                    echo htmlspecialchars($detail->title);
                else
                    echo 'Gestion des interventions sur le terrain.';
                ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($action === 'list' || $action === 'view'): ?>
                <a href="missions.php?action=add" class="btn btn-admin-primary">
                    <i class="bi bi-plus-lg me-1"></i> Créer une mission
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if ($action === 'list'): ?>
    <!-- Raccourcis de gestion -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="admin-card p-3 d-flex flex-wrap gap-2 align-items-center bg-light border shadow-sm">
                <span class="text-muted small fw-bold text-uppercase me-2"><i class="bi bi-sliders me-1"></i> Configuration
                    :</span>
                <a href="mission_types.php" class="btn btn-sm btn-admin-outline"><i class="bi bi-tag me-1"></i> Gérer les
                    Types de Mission</a>
            </div>
        </div>
    </div>
    <!-- CARDS STATISTIQUES MISSIONS -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Total Missions</div>
                <div class="h3 mb-0 fw-bold"><?= $dashStats['total'] ?></div>
                <div class="mt-2"><span class="badge bg-secondary-subtle text-secondary border">Global</span></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Planifiées</div>
                <div class="h3 mb-0 fw-bold text-warning"><?= $dashStats['planned'] ?></div>
                <div class="mt-2"><span class="badge bg-warning-subtle text-warning border">Pipeline</span></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">En cours</div>
                <div class="h3 mb-0 fw-bold text-info"><?= $dashStats['in_progress'] ?></div>
                <div class="mt-2"><span class="badge bg-info-subtle text-info border">Actif</span></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Terminées</div>
                <div class="h3 mb-0 fw-bold text-success"><?= $dashStats['completed'] ?></div>
                <div class="mt-2"><span class="badge bg-success-subtle text-success border">Clôturées</span></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- FORMULAIRE (AJOUT / EDITION) ENRICHI -->
    <div class="admin-card admin-section-card p-0 overflow-hidden">
        <form method="POST" action="missions.php?action=<?= $action ?><?= $id ? '&id=' . $id : '' ?>" id="missionForm"
            enctype="multipart/form-data">
            <div class="admin-header-tabs px-4 pt-3 bg-light border-bottom">
                <ul class="nav nav-tabs border-0" id="missionTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button">1.
                            Général</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-objectives" type="button">2.
                            Objectifs</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-team" type="button">3. Équipe &
                            RH</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-steps" type="button">4. Planning
                            & Étapes</button>
                    </li>
                    <?php if ($id > 0): ?>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-orders" type="button">5.
                                Ordres</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-reports" type="button">6.
                                Rapports</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-expenses" type="button">7.
                                Frais</button>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="tab-content p-4">
                <!-- TAB 1: GENERAL -->
                <div class="tab-pane fade show active" id="tab-general">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Titre de la mission *</label>
                            <input type="text" name="title" class="form-control"
                                value="<?= htmlspecialchars($detail->title ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Référence</label>
                            <input type="text" name="reference" class="form-control"
                                value="<?= htmlspecialchars($detail->reference ?? '') ?>" placeholder="ex: M-2024-001">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Photo de couverture</label>
                            <?php if (!empty($detail->cover_image)): ?>
                                <div class="mb-2">
                                    <img src="../<?= $detail->cover_image ?>" alt="Couverture" class="rounded border"
                                        style="height: 100px; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="cover_image" class="form-control" accept="image/*">
                            <div class="form-text text-muted small">Format recommandé : 1200x400px (JPG, PNG).</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Type de mission</label>
                            <select name="mission_type_id" class="form-select">
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($missionTypes as $type): ?>
                                    <option value="<?= $type->id ?>" <?= (($detail->mission_type_id ?? '') == $type->id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Statut initial</label>
                            <select name="mission_status_id" class="form-select">
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($missionStatuses as $status): ?>
                                    <option value="<?= $status->id ?>" <?= (($detail->mission_status_id ?? '') == $status->id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($status->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Lieu / Destination</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" name="location" class="form-control"
                                    value="<?= htmlspecialchars($detail->location ?? '') ?>"
                                    placeholder="ex: Kinshasa, RDC">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date de début</label>
                            <input type="date" name="start_date" class="form-control"
                                value="<?= $detail->start_date ?? '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date de fin prévue</label>
                            <input type="date" name="end_date" class="form-control" value="<?= $detail->end_date ?? '' ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description globale</label>
                            <textarea name="description" id="mission_description" class="form-control"
                                rows="8"><?= htmlspecialchars($detail->description ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: OBJECTIVES -->
                <div class="tab-pane fade" id="tab-objectives">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h6 mb-0 fw-bold">Définition des objectifs spécifiques</h3>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addObjectiveBtn">
                            <i class="bi bi-plus-circle me-1"></i> Ajouter un objectif
                        </button>
                    </div>
                    <div id="objectivesContainer" class="d-flex flex-column gap-2">
                        <?php if (!empty($detail->objectives)): ?>
                            <?php foreach ($detail->objectives as $obj): ?>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-dot"></i></span>
                                    <input type="text" name="objectives[]" class="form-control"
                                        value="<?= htmlspecialchars($obj->title) ?>">
                                    <button type="button" class="btn btn-outline-danger remove-line"><i
                                            class="bi bi-x"></i></button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-dot"></i></span>
                                <input type="text" name="objectives[]" class="form-control"
                                    placeholder="Objectif de la mission...">
                                <button type="button" class="btn btn-outline-danger remove-line"><i
                                        class="bi bi-x"></i></button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded small text-muted">
                        <i class="bi bi-info-circle me-1"></i> Ces objectifs permettront de suivre l'avancée réelle de la
                        mission sur le terrain.
                    </div>
                </div>

                <!-- TAB 3: TEAM -->
                <div class="tab-pane fade" id="tab-team">
                    <h3 class="h6 mb-3 fw-bold">Personnel affecté à la mission</h3>
                    <div class="row g-3 overflow-auto" style="max-height: 400px;">
                        <?php foreach ($allStaff as $staff): ?>
                            <div class="col-md-6">
                                <div
                                    class="form-check admin-staff-card p-3 border rounded h-100 d-flex align-items-center gap-3">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="staff_ids[]"
                                        value="<?= $staff->user_id ?>" id="staff_<?= $staff->user_id ?>"
                                        <?= (in_array($staff->user_id, $detail->staff_ids ?? [])) ? 'checked' : '' ?>>
                                    <label class="form-check-label flex-grow-1" for="staff_<?= $staff->user_id ?>">
                                        <div class="fw-bold">
                                            <?= htmlspecialchars($staff->last_name . ' ' . $staff->first_name) ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars($staff->job_title ?: 'Collaborateur') ?>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- TAB 4: STEPS -->
                <div class="tab-pane fade" id="tab-steps">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h6 fw-bold mb-0">Déroulement / Étapes de la mission</h3>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addStepBtn">
                            <i class="bi bi-plus-circle me-1"></i> Ajouter une étape
                        </button>
                    </div>

                    <div id="stepsContainer">
                        <?php if (!empty($detail->steps)): ?>
                            <?php foreach ($detail->steps as $index => $step): ?>
                                <div class="admin-step-item p-3 border rounded mb-3 bg-white shadow-sm">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Titre de l'étape</label>
                                            <input type="text" name="step_titles[]" class="form-control form-control-sm"
                                                value="<?= htmlspecialchars($step->title) ?>" placeholder="Titre...">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Photo de l'étape</label>
                                            <?php if (!empty($step->image_url)): ?>
                                                <div class="mb-1">
                                                    <img src="../<?= $step->image_url ?>" class="rounded" style="height: 40px;">
                                                    <input type="hidden" name="existing_step_images[]" value="<?= $step->image_url ?>">
                                                </div>
                                            <?php else: ?>
                                                <input type="hidden" name="existing_step_images[]" value="">
                                            <?php endif; ?>
                                            <input type="file" name="step_images[]" class="form-control form-control-sm"
                                                accept="image/*">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-bold">Description de l'étape</label>
                                            <textarea name="step_contents[]" class="form-control form-control-sm" rows="3"
                                                placeholder="Détails de cette étape..."><?= htmlspecialchars($step->content) ?></textarea>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-sm btn-link text-danger remove-step p-0 mt-2"><i
                                                class="bi bi-trash me-1"></i> Supprimer cette étape</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="admin-step-item p-3 border rounded mb-3 bg-white shadow-sm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Titre de l'étape</label>
                                        <input type="text" name="step_titles[]" class="form-control form-control-sm"
                                            placeholder="Titre de l'étape...">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Photo de l'étape</label>
                                        <input type="file" name="step_images[]" class="form-control form-control-sm"
                                            accept="image/*">
                                        <input type="hidden" name="existing_step_images[]" value="">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold">Description de l'étape</label>
                                        <textarea name="step_contents[]" class="form-control form-control-sm" rows="3"
                                            placeholder="Détails de cette étape..."></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($id > 0): ?>
                    <!-- TAB 5: ORDERS -->
                    <div class="tab-pane fade" id="tab-orders">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="h6 mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2"></i> Ordre de mission</h3>
                            <button type="button" class="btn btn-sm btn-admin-primary" data-bs-toggle="modal"
                                data-bs-target="#orderModal">
                                <?= $detail->order ? 'Modifier' : 'Générer' ?> l'ordre
                            </button>
                        </div>
                        <?php if ($detail->order): ?>
                            <div class="admin-card p-3 border shadow-none bg-light">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <div class="small text-muted mb-1">Numéro d'ordre</div>
                                        <div class="fw-bold"><?= htmlspecialchars($detail->order->order_number) ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="small text-muted mb-1">Date d'émission</div>
                                        <div class="fw-bold"><?= date('d/m/Y', strtotime($detail->order->issue_date)) ?></div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span
                                            class="badge bg-<?= $detail->order->status === 'signed' ? 'success' : 'warning' ?>-subtle text-<?= $detail->order->status === 'signed' ? 'success' : 'warning' ?> border px-3">
                                            <?= ucfirst($detail->order->status) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 bg-light rounded border border-dashed">
                                <p class="text-muted small mb-0">Aucun ordre de mission généré pour le moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- TAB 6: REPORTS -->
                    <div class="tab-pane fade" id="tab-reports">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="h6 mb-0 fw-bold"><i class="bi bi-journal-text me-2"></i> Rapports de mission</h3>
                            <button type="button" class="btn btn-sm btn-admin-primary" onclick="openReportModal(0)">
                                <i class="bi bi-plus-lg me-1"></i> Nouveau rapport
                            </button>
                        </div>
                        <?php if (!empty($detail->reports)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="small text-muted">
                                        <tr>
                                            <th>Date</th>
                                            <th>Résumé</th>
                                            <th>Auteur</th>
                                            <th>Statut</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detail->reports as $rep): ?>
                                            <tr>
                                                <td class="small"><?= date('d/m/Y', strtotime($rep->report_date)) ?></td>
                                                <td class="small text-truncate" style="max-width: 200px;">
                                                    <strong><?= htmlspecialchars($rep->title) ?></strong><br>
                                                    <span class="text-muted"><?= htmlspecialchars($rep->summary) ?></span>
                                                </td>
                                                <td class="small">
                                                    <?php
                                                    $author = null;
                                                    foreach ($allUsers as $u)
                                                        if ($u->id == $rep->author_user_id)
                                                            $author = $u;
                                                    echo $author ? htmlspecialchars($author->first_name . ' ' . $author->last_name) : '—';
                                                    ?>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?= $rep->status === 'final' ? 'success' : 'info' ?>-subtle text-<?= $rep->status === 'final' ? 'success' : 'info' ?> x-small border">
                                                        <?= ucfirst($rep->status) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-link text-primary p-0 me-2"
                                                        onclick="openReportModal(<?= $rep->id ?>, '<?= addslashes($rep->title) ?>', '<?= addslashes($rep->summary) ?>', '<?= addslashes(str_replace(["\r", "\n"], ['\r', '\n'], $rep->content)) ?>', '<?= $rep->report_date ?>', <?= $rep->author_user_id ?>, '<?= $rep->status ?>')">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-link text-danger p-0"
                                                        onclick="deleteReport(<?= $rep->id ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 bg-light rounded border border-dashed">
                                <p class="text-muted small mb-0">En attente des premiers comptes-rendus.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- TAB 7: EXPENSES -->
                    <div class="tab-pane fade" id="tab-expenses">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="h6 mb-0 fw-bold"><i class="bi bi-cash-stack me-2"></i> Frais de mission</h3>
                            <button type="button" class="btn btn-sm btn-admin-primary" onclick="openExpenseModal(0)">
                                <i class="bi bi-plus-lg me-1"></i> Nouvelle dépense
                            </button>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="admin-card p-3 bg-light text-center border shadow-none">
                                    <div class="small text-muted mb-1">Budget consommé</div>
                                    <div class="h5 fw-bold mb-0 text-success">
                                        <?= number_format($detail->expenses_total, 0, ',', ' ') ?> <small>XOF</small></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="admin-card p-3 bg-light text-center border shadow-none">
                                    <div class="small text-muted mb-1">Nombre de justificatifs</div>
                                    <div class="h5 fw-bold mb-0"><?= count($detail->expenses) ?></div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($detail->expenses)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="small text-muted">
                                        <tr>
                                            <th>Date</th>
                                            <th>Catégorie</th>
                                            <th>Description</th>
                                            <th>Montant</th>
                                            <th>Statut</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detail->expenses as $exp): ?>
                                            <tr>
                                                <td class="small"><?= date('d/m/Y', strtotime($exp->expense_date)) ?></td>
                                                <td><span
                                                        class="badge bg-light text-muted border small"><?= htmlspecialchars($exp->category) ?></span>
                                                </td>
                                                <td class="small"><?= htmlspecialchars($exp->description) ?></td>
                                                <td class="fw-bold small"><?= number_format($exp->amount, 0, ',', ' ') ?>
                                                    <?= $exp->currency ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?= $exp->status === 'approved' ? 'success' : ($exp->status === 'rejected' ? 'danger' : 'warning') ?>-subtle text-<?= $exp->status === 'approved' ? 'success' : ($exp->status === 'rejected' ? 'danger' : 'warning') ?> x-small border">
                                                        <?= ucfirst($exp->status) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-link text-primary p-0 me-2"
                                                        onclick="openExpenseModal(<?= $exp->id ?>, '<?= $exp->category ?>', '<?= addslashes($exp->description) ?>', <?= $exp->amount ?>, '<?= $exp->currency ?>', '<?= $exp->expense_date ?>', <?= $exp->user_id ?>, '<?= $exp->status ?>')">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-link text-danger p-0"
                                                        onclick="deleteExpense(<?= $exp->id ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 bg-light rounded border border-dashed">
                                <p class="text-muted small mb-0">Aucune dépense enregistrée.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="admin-footer-actions border-top p-4 d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-admin-outline me-2 prev-tab"
                        style="display:none">Précédent</button>
                    <button type="button" class="btn btn-admin-primary next-tab">Suivant</button>
                </div>
                <div>
                    <a href="missions.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-admin-outline me-2">Annuler</a>
                    <button type="submit" name="save_mission" class="btn btn-success px-4" id="submitMission"
                        style="display:none">
                        <i class="bi bi-cloud-upload me-1"></i> Finaliser & Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>

<?php elseif ($action === 'view' && $detail): ?>
    <!-- VUE DÉTAILLÉE AVEC ONGLETS -->
    <?php if (!empty($detail->cover_image)): ?>
        <div class="mission-cover-container mb-4 rounded overflow-hidden shadow-sm border"
            style="height: 250px; background: #eee;">
            <img src="../<?= $detail->cover_image ?>" class="w-100 h-100" style="object-fit: cover;">
        </div>
    <?php endif; ?>

    <div class="admin-card admin-section-card p-0 overflow-hidden">
        <div class="admin-header-tabs px-4 pt-3 bg-light border-bottom">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 mb-0 fw-bold text-admin-sidebar"><?= htmlspecialchars($detail->title) ?></h2>
                <div class="d-flex gap-2">
                    <a href="missions.php?action=edit&id=<?= $detail->id ?>" class="btn btn-sm btn-admin-outline">
                        <i class="bi bi-pencil me-1"></i> Modifier
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                        data-bs-target="#deleteModal">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <ul class="nav nav-tabs border-0" id="viewMissionTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#view-tab-info"
                        type="button">Aperçu</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#view-tab-team"
                        type="button">Équipe</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#view-tab-steps"
                        type="button">Planning</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#view-tab-orders"
                        type="button">Ordres</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#view-tab-reports"
                        type="button">Rapports</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#view-tab-expenses"
                        type="button">Frais</button>
                </li>
            </ul>
        </div>

        <div class="tab-content p-4">
            <!-- VIEW TAB: INFO -->
            <div class="tab-pane fade show active" id="view-tab-info">

                <div class="row g-4">
                    <div class="col-md-7">
                        <h3 class="h6 fw-bold border-bottom pb-2 mb-3"><i class="bi bi-list-check me-1"></i> Objectifs &
                            Résultats
                        </h3>
                        <?php if (!empty($detail->objectives)): ?>
                            <ul class="list-group list-group-flush mb-4">
                                <?php foreach ($detail->objectives as $obj): ?>
                                    <li class="list-group-item d-flex align-items-center bg-transparent px-0">
                                        <i
                                            class="bi <?= $obj->is_achieved ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' ?> me-3"></i>
                                        <span
                                            class="<?= $obj->is_achieved ? 'text-decoration-line-through text-muted' : '' ?>"><?= htmlspecialchars($obj->title) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted small">Aucun objectif spécifique défini.</p>
                        <?php endif; ?>

                        <h3 class="h6 fw-bold border-bottom pb-2 mb-3"><i class="bi bi-justify-left me-1"></i> Description
                        </h3>
                        <div class="text-muted small lh-lg mb-4">
                            <?= $detail->description ?: 'Aucune description.' ?>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded border mb-4">
                            <h3 class="h6 fw-bold border-bottom pb-2 mb-3">Informations Clés</h3>
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <td class="text-muted small" style="width: 100px;">Type</td>
                                    <td><span
                                            class="text-primary fw-medium small"><?= htmlspecialchars($detail->type_name ?: 'Non défini') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Statut</td>
                                    <td>
                                        <?php
                                        $badgeClass = 'bg-secondary-subtle text-secondary';
                                        if ($detail->status_code === 'in_progress')
                                            $badgeClass = 'bg-info-subtle text-info';
                                        if ($detail->status_code === 'completed')
                                            $badgeClass = 'bg-success-subtle text-success';
                                        if ($detail->status_code === 'cancelled')
                                            $badgeClass = 'bg-danger-subtle text-danger';
                                        if ($detail->status_code === 'planned')
                                            $badgeClass = 'bg-warning-subtle text-warning';
                                        ?>
                                        <span
                                            class="badge <?= $badgeClass ?> border px-2"><?= htmlspecialchars($detail->status_name ?: 'Inconnu') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Lieu</td>
                                    <td><i class="bi bi-geo-alt text-danger me-1 small"></i> <span
                                            class="small"><?= htmlspecialchars($detail->location ?: 'Non définie') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Début</td>
                                    <td class="small fw-bold">
                                        <?= $detail->start_date ? date('d/m/Y', strtotime($detail->start_date)) : '?' ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Fin prévue</td>
                                    <td class="small fw-bold">
                                        <?= $detail->end_date ? date('d/m/Y', strtotime($detail->end_date)) : '?' ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Référence</td>
                                    <td class="small text-uppercase"><?= htmlspecialchars($detail->reference ?: '—') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW TAB: TEAM -->
            <div class="tab-pane fade" id="view-tab-team">
                <h3 class="h6 fw-bold border-bottom pb-2 mb-3"><i class="bi bi-people me-1"></i> Équipe assignée</h3>
                <?php if (!empty($detail->staff)): ?>
                    <div class="row g-3">
                        <?php foreach ($detail->staff as $s): ?>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2 p-2 border rounded bg-white">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:32px; height:32px; font-size:12px;">
                                        <?= strtoupper(substr($s->first_name, 0, 1) . substr($s->last_name, 0, 1)) ?>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="fw-bold small text-nowrap">
                                            <?= htmlspecialchars($s->first_name . ' ' . $s->last_name) ?>
                                        </div>
                                        <div class="text-muted x-small text-truncate">
                                            <?= htmlspecialchars($s->job_title ?: 'Personnel') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small">Aucun membre assigné.</p>
                <?php endif; ?>
            </div>

            <!-- VIEW TAB: PLANNING -->
            <div class="tab-pane fade" id="view-tab-steps">
                <h3 class="h6 fw-bold border-bottom pb-2 mb-3"><i class="bi bi-signpost-split me-1"></i> Étapes du
                    projet
                </h3>
                <?php if (!empty($detail->steps)): ?>
                    <div class="row g-4">
                        <?php foreach ($detail->steps as $sIdx => $step): ?>
                            <div class="col-12">
                                <div class="d-flex gap-3 p-3 border rounded bg-white shadow-sm">
                                    <div class="step-number-circle bg-admin-primary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                                        style="width: 32px; height: 32px; border-radius: 50%;">
                                        <?= $sIdx + 1 ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h4 class="h6 fw-bold mb-1"><?= htmlspecialchars($step->title) ?></h4>
                                        <p class="text-muted small mb-2"><?= nl2br(htmlspecialchars($step->content)) ?></p>
                                        <?php if (!empty($step->image_url)): ?>
                                            <div class="mt-2">
                                                <img src="../<?= $step->image_url ?>" class="rounded border"
                                                    style="max-height: 200px; max-width: 100%;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small">Aucun planning défini.</p>
                <?php endif; ?>
            </div>

            <!-- VIEW TAB: ORDERS -->
            <div class="tab-pane fade" id="view-tab-orders">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-file-earmark-text me-2"></i> Ordres de mission</h3>
                    <a href="mission_orders.php?mission_id=<?= $detail->id ?>" class="btn btn-sm btn-admin-primary">Accéder
                        à la gestion</a>
                </div>
                <!-- Debug: Tab rendered -->
                <?php if ($detail->order): ?>
                    <div class="admin-card p-3 border shadow-none bg-light">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <div class="small text-muted mb-1">Numéro d'ordre</div>
                                <div class="fw-bold"><?= htmlspecialchars($detail->order->order_number) ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-muted mb-1">Date d'émission</div>
                                <div class="fw-bold"><?= date('d/m/Y', strtotime($detail->order->issue_date)) ?></div>
                            </div>
                            <div class="col-md-4 text-end">
                                <span
                                    class="badge bg-<?= $detail->order->status === 'signed' ? 'success' : 'warning' ?>-subtle text-<?= $detail->order->status === 'signed' ? 'success' : 'warning' ?> border px-3">
                                    <?= ucfirst($detail->order->status) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 bg-light rounded border border-dashed">
                        <p class="text-muted small mb-0">Aucun ordre de mission généré.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- VIEW TAB: REPORTS -->
            <div class="tab-pane fade" id="view-tab-reports">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-journal-text me-2"></i> Rapports de mission</h3>
                    <a href="mission_reports.php?mission_id=<?= $detail->id ?>" class="btn btn-sm btn-admin-primary">Voir
                        tous les rapports</a>
                </div>
                <?php if (!empty($detail->reports)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="small text-muted">
                                <tr>
                                    <th>Date</th>
                                    <th>Résumé</th>
                                    <th class="text-end">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail->reports as $rep): ?>
                                    <tr>
                                        <td class="small"><?= date('d/m/Y', strtotime($rep->report_date)) ?></td>
                                        <td class="small"><?= htmlspecialchars($rep->summary) ?></td>
                                        <td class="text-end">
                                            <span
                                                class="badge bg-<?= $rep->status === 'final' ? 'success' : 'info' ?>-subtle text-<?= $rep->status === 'final' ? 'success' : 'info' ?> x-small border">
                                                <?= ucfirst($rep->status) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted small text-center py-4">Aucun rapport soumis.</p>
                <?php endif; ?>
            </div>

            <!-- VIEW TAB: EXPENSES -->
            <div class="tab-pane fade" id="view-tab-expenses">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="h6 mb-0 fw-bold"><i class="bi bi-cash-stack me-2"></i> Frais de mission</h3>
                    <a href="mission_expenses.php?mission_id=<?= $detail->id ?>"
                        class="btn btn-sm btn-admin-primary">Détails financiers</a>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="admin-card p-4 bg-light text-center border-0 shadow-none">
                            <div class="small text-muted mb-1">Total consommé</div>
                            <div class="h4 fw-bold mb-0 text-primary">
                                <?= number_format($detail->expenses_total, 0, ',', ' ') ?> <small
                                    class="text-muted">XOF</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="admin-card p-4 bg-light text-center border-0 shadow-none">
                            <div class="small text-muted mb-1">Justificatifs</div>
                            <div class="h4 fw-bold mb-0"><?= count($detail->expenses) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 pt-3">
        <a href="missions.php" class="btn btn-sm btn-admin-outline"><i class="bi bi-arrow-left"></i> Retour à la
            liste</a>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    Êtes-vous sûr de vouloir supprimer la mission
                    <strong><?= htmlspecialchars($detail->title) ?></strong> ?
                    Cette action est irréversible.
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST">
                        <input type="hidden" name="delete_id" value="<?= $detail->id ?>">
                        <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- LISTE DES MISSIONS -->
    <div class="admin-card admin-section-card">
        <?php if (count($missions) > 0): ?>
            <div class="table-responsive">
                <table class="admin-table table table-hover" id="missionsTable">
                    <thead>
                        <tr>
                            <th>Mission</th>
                            <th>Type / Statut</th>
                            <th>Lieu</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($missions as $m): ?>
                            <tr>
                                <td>
                                    <h6 class="mb-0"><a href="missions.php?id=<?= $m->id ?>"><?= htmlspecialchars($m->title) ?></a>
                                    </h6>
                                    <div class="d-flex gap-2 align-items-center mt-1">
                                        <span
                                            class="text-muted x-small"><?= htmlspecialchars($m->reference ?: 'Sans réf.') ?></span>
                                        <span class="opacity-25 text-muted small">|</span>
                                        <span class="text-muted x-small"><i class="bi bi-calendar-event me-1"></i>
                                            <?= $m->start_date ? date('d/m/y', strtotime($m->start_date)) : '?' ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-medium text-primary mb-1">
                                        <?= htmlspecialchars($m->type_name ?: '—') ?>
                                    </div>
                                    <?php
                                    $badgeClass = 'bg-secondary-subtle text-secondary';
                                    if ($m->status_code === 'in_progress')
                                        $badgeClass = 'bg-info-subtle text-info';
                                    if ($m->status_code === 'completed')
                                        $badgeClass = 'bg-success-subtle text-success';
                                    if ($m->status_code === 'cancelled')
                                        $badgeClass = 'bg-danger-subtle text-danger';
                                    if ($m->status_code === 'planned')
                                        $badgeClass = 'bg-warning-subtle text-warning';
                                    ?>
                                    <span
                                        class="badge <?= $badgeClass ?> border x-small"><?= htmlspecialchars($m->status_name ?: '—') ?></span>
                                </td>
                                <td><i class="bi bi-geo-alt text-danger x-small me-1"></i>
                                    <?= htmlspecialchars($m->location ?: '—') ?></td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                            <li><a class="dropdown-item" href="missions.php?id=<?= $m->id ?>"><i
                                                        class="bi bi-eye me-2"></i> Voir</a></li>
                                            <li><a class="dropdown-item" href="missions.php?action=edit&id=<?= $m->id ?>"><i
                                                        class="bi bi-pencil me-2"></i> Modifier</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <h6 class="dropdown-header small text-uppercase">Gestion Spécifique</h6>
                                            </li>
                                            <li><a class="dropdown-item" href="mission_orders.php?mission_id=<?= $m->id ?>"><i
                                                        class="bi bi-file-earmark-text me-2"></i> Ordres de mission</a></li>
                                            <li><a class="dropdown-item" href="mission_plans.php?mission_id=<?= $m->id ?>"><i
                                                        class="bi bi-calendar-event me-2"></i> Plannings & Étapes</a></li>
                                            <li><a class="dropdown-item" href="mission_reports.php?mission_id=<?= $m->id ?>"><i
                                                        class="bi bi-journal-text me-2"></i> Rapports de mission</a></li>
                                            <li><a class="dropdown-item" href="mission_expenses.php?mission_id=<?= $m->id ?>"><i
                                                        class="bi bi-cash-stack me-2"></i> Frais de mission</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <form method="POST" onsubmit="return confirm('Supprimer cette mission ?');">
                                                    <input type="hidden" name="delete_id" value="<?= $m->id ?>">
                                                    <button type="submit" class="dropdown-item text-danger"><i
                                                            class="bi bi-trash me-2"></i> Supprimer</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="admin-empty py-5">
                <i class="bi bi-geo-alt d-block mb-3" style="font-size: 3rem;"></i>
                <h5>Aucune mission enregistrée</h5>
                <p class="text-muted mb-4">Commencez par ajouter votre première mission sur le terrain.</p>
                <a href="missions.php?action=add" class="btn btn-admin-primary">
                    <i class="bi bi-plus-lg me-1"></i> Créer ma première mission
                </a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                Êtes-vous sûr de vouloir supprimer la mission <strong>
                    <?= htmlspecialchars($detail->title ?? '') ?></strong> ?
                Cette action est irréversible.
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST">
                    <input type="hidden" name="delete_id" value="<?= $detail->id ?? 0 ?>">
                    <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .breadcrumb {
        font-size: 0.85rem;
        margin-bottom: 0;
    }

    .breadcrumb-item a {
        color: var(--admin-muted);
    }

    .breadcrumb-item.active {
        color: var(--admin-accent);
        font-weight: 600;
    }

    .x-small {
        font-size: 0.75rem;
    }

    .btn-outline-danger:hover {
        color: #fff;
    }

    .admin-table th {
        background: #f8f9fa;
        padding: 1rem 0.5rem;
    }

    .admin-table td {
        padding: 1rem 0.5rem;
    }

    /* Style DataTables */
    .dataTables_filter input {
        border-radius: 8px;
        border: 1.5px solid #dde1e7;
        padding: 0.4rem 0.8rem;
        margin-left: 0.5rem;
        outline: none;
    }

    .dataTables_filter input:focus {
        border-color: var(--admin-sidebar);
    }

    .dataTables_wrapper .pagination .page-item.active .page-link {
        background-color: var(--admin-sidebar);
        border-color: var(--admin-sidebar);
        color: #fff;
    }

    .dataTables_wrapper .pagination .page-link {
        color: var(--admin-sidebar);
        border-radius: 6px;
        margin: 0 2px;
    }

    .dataTables_info {
        font-size: 0.85rem;
        color: var(--admin-muted);
    }

    /* Form Stepper & Team */
    .admin-staff-card {
        cursor: pointer;
        transition: all 0.2s;
        border-width: 2px !important;
    }

    .admin-staff-card:hover {
        border-color: var(--admin-sidebar) !important;
        background-color: #f8f9fa;
    }

    .admin-staff-card has(:checked) {
        border-color: var(--admin-accent) !important;
        background-color: #fffbef;
    }

    .nav-tabs .nav-link {
        color: var(--admin-muted);
        font-weight: 500;
        padding: 1rem 1.5rem;
    }

    .nav-tabs .nav-link.active {
        background: transparent !important;
        border-bottom: 3px solid var(--admin-accent) !important;
        color: var(--admin-sidebar) !important;
    }
</style>

<footer class="admin-main-footer">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>
            Tableau
            de bord</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>

<!-- Modal Ordre de Mission -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Gérer l'ordre de mission</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="mission_id" value="<?= $id ?>">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Numéro d'ordre</label>
                    <input type="text" name="order_number" id="order_number" class="form-control"
                        value="<?= $detail->order->order_number ?? 'OM-' . date('Y') . '-' . sprintf('%04d', $id) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Date d'émission</label>
                    <input type="date" name="issue_date" id="order_issue_date" class="form-control"
                        value="<?= $detail->order->issue_date ?? date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Signataire autorisé</label>
                    <select name="authorised_by_user_id" id="order_auth_by" class="form-select">
                        <option value="">Sélectionner...</option>
                        <?php foreach ($allUsers as $u): ?>
                            <option value="<?= $u->id ?>" <?= ($detail->order->authorised_by_user_id ?? '') == $u->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u->first_name . ' ' . $u->last_name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Notes / Instructions</label>
                    <textarea name="notes" id="order_notes" class="form-control"
                        rows="3"><?= htmlspecialchars($detail->order->notes ?? '') ?></textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-bold">Statut</label>
                    <select name="status" id="order_status" class="form-select">
                        <option value="draft" <?= ($detail->order->status ?? '') == 'draft' ? 'selected' : '' ?>>Brouillon
                        </option>
                        <option value="sent" <?= ($detail->order->status ?? '') == 'sent' ? 'selected' : '' ?>>Envoyé
                        </option>
                        <option value="signed" <?= ($detail->order->status ?? '') == 'signed' ? 'selected' : '' ?>>Signé
                        </option>
                        <option value="cancelled" <?= ($detail->order->status ?? '') == 'cancelled' ? 'selected' : '' ?>>
                            Annulé</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" name="save_order" class="btn btn-admin-primary">Enregistrer l'ordre</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Rapport -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="reportModalTitle">Ajouter un rapport</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="mission_id" value="<?= $id ?>">
                <input type="hidden" name="report_id" id="report_id" value="0">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Titre du rapport</label>
                        <input type="text" name="report_title" id="report_title" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Date</label>
                        <input type="date" name="report_date" id="report_date" class="form-control"
                            value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Résumé court</label>
                        <textarea name="summary" id="report_summary" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Contenu détaillé</label>
                        <textarea name="content" id="report_content" class="form-control" rows="5"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Auteur</label>
                        <select name="author_user_id" id="report_author" class="form-select" required>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?= $u->id ?>"><?= htmlspecialchars($u->first_name . ' ' . $u->last_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Statut</label>
                        <select name="status" id="report_status" class="form-select">
                            <option value="draft">Brouillon</option>
                            <option value="submitted">Soumis</option>
                            <option value="final">Finalisé</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" name="save_report" class="btn btn-admin-primary">Enregistrer le rapport</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Frais -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="expenseModalTitle">Ajouter une dépense</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="mission_id" value="<?= $id ?>">
                <input type="hidden" name="expense_id" id="expense_id" value="0">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Catégorie</label>
                        <select name="category" id="expense_category" class="form-select">
                            <option value="Transport">Transport</option>
                            <option value="Hébergement">Hébergement</option>
                            <option value="Restauration">Restauration</option>
                            <option value="Matériel">Matériel</option>
                            <option value="Communication">Communication</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Description</label>
                        <input type="text" name="description" id="expense_description" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Montant</label>
                        <div class="input-group">
                            <input type="number" name="amount" id="expense_amount" class="form-control" step="0.01"
                                required>
                            <span class="input-group-text">XOF</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Date</label>
                        <input type="date" name="expense_date" id="expense_date" class="form-control"
                            value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Bénéficiaire</label>
                        <select name="user_id" id="expense_user" class="form-select" required>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?= $u->id ?>"><?= htmlspecialchars($u->first_name . ' ' . $u->last_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Statut</label>
                        <select name="status" id="expense_status" class="form-select">
                            <option value="pending">En attente</option>
                            <option value="submitted">Soumis</option>
                            <option value="approved">Approuvé</option>
                            <option value="reimbursed">Remboursé</option>
                            <option value="rejected">Rejeté</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" name="save_expense" class="btn btn-admin-primary">Enregistrer la dépense</button>
            </div>
        </form>
    </div>
</div>

<!-- Forms cachés pour suppression -->
<form id="deleteReportForm" method="POST" style="display:none;">
    <input type="hidden" name="delete_report_id" id="delete_report_id_input">
</form>
<form id="deleteExpenseForm" method="POST" style="display:none;">
    <input type="hidden" name="delete_expense_id" id="delete_expense_id_input">
</form>

<?php require __DIR__ . '/inc/footer.php'; ?>

<script>
    $(document).ready(function () {
        // Initialisation DataTable
        if ($('#missionsTable').length) {
            $('#missionsTable').DataTable({
                "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" },
                "order": [[0, "desc"]],
                "pageLength": 10,
                "dom": '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>',
            });
        }

        // Initialisation Summernote (Gratuit & Open Source)
        $('#mission_description').summernote({
            placeholder: 'Décrivez la mission ici...',
            tabsize: 2,
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });

        // Gestion des Onglets (Stepper)
        const tabs = ['tab-general', 'tab-objectives', 'tab-team', 'tab-steps', 'tab-orders', 'tab-reports', 'tab-expenses'];
        let currentTabIdx = 0;

        function updateStepButtons() {
            $('.prev-tab').toggle(currentTabIdx > 0);
            if (currentTabIdx === tabs.length - 1) {
                $('.next-tab').hide();
                $('#submitMission').show();
            } else {
                $('.next-tab').show();
                $('#submitMission').hide();
            }
        }

        $('.next-tab').click(function () {

            if (currentTabIdx < tabs.length - 1) {
                currentTabIdx++;
                const nextTabId = tabs[currentTabIdx];
                const tabEl = document.querySelector(`button[data-bs-target="#${nextTabId}"]`);
                bootstrap.Tab.getInstance(tabEl)?.show() || new bootstrap.Tab(tabEl).show();
                updateStepButtons();
            }
        });

        $('.prev-tab').click(function () {
            if (currentTabIdx > 0) {
                currentTabIdx--;
                const prevTabId = tabs[currentTabIdx];
                const tabEl = document.querySelector(`button[data-bs-target="#${prevTabId}"]`);
                bootstrap.Tab.getInstance(tabEl)?.show() || new bootstrap.Tab(tabEl).show();
                updateStepButtons();
            }
        });

        // Écouteur sur les changements d'onglets manuels (clic direct)
        $('#missionTabs button').on('shown.bs.tab', function (e) {
            const targetId = $(e.target).data('bs-target').replace('#', '');
            currentTabIdx = tabs.indexOf(targetId);
            updateStepButtons();
        });

        // Ajout dynamique d'étapes
        $('#addStepBtn').click(function () {
            const html = `
                <div class="admin-step-item p-3 border rounded mb-3 bg-white shadow-sm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Titre de l'étape</label>
                            <input type="text" name="step_titles[]" class="form-control form-control-sm" placeholder="Titre de l'étape...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Photo de l'étape</label>
                            <input type="file" name="step_images[]" class="form-control form-control-sm" accept="image/*">
                            <input type="hidden" name="existing_step_images[]" value="">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description de l'étape</label>
                            <textarea name="step_contents[]" class="form-control form-control-sm" rows="3" placeholder="Détails de cette étape..."></textarea>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-sm btn-link text-danger remove-step p-0 mt-2"><i class="bi bi-trash me-1"></i> Supprimer cette étape</button>
                    </div>
                </div>
            `;
            $('#stepsContainer').append(html);
        });

        $(document).on('click', '.remove-step', function () {
            $(this).closest('.admin-step-item').remove();
        });

        // Ajout dynamique d'objectifs
        $('#addObjectiveBtn').click(function () {
            const html = `
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-dot"></i></span>
                <input type="text" name="objectives[]" class="form-control" placeholder="Nouvel objectif...">
                <button type="button" class="btn btn-outline-danger remove-line"><i class="bi bi-x"></i></button>
            </div>
        `;
            $('#objectivesContainer').append(html);
        });

        $(document).on('click', '.remove-line', function () {
            if ($('#objectivesContainer .input-group').length > 1) {
                $(this).closest('.input-group').remove();
            } else {
                $(this).closest('.input-group').find('input').val('');
            }
        });

        // Style visuel pour les cartes de personnel
        $('.admin-staff-card input:checked').closest('.admin-staff-card').css({
            'border-color': 'var(--admin-accent)',
            'background-color': '#fffbef'
        });

        $('.admin-staff-card input').change(function () {
            if ($(this).is(':checked')) {
                $(this).closest('.admin-staff-card').css({
                    'border-color': 'var(--admin-accent)',
                    'background-color': '#fffbef'
                });
            } else {
                $(this).closest('.admin-staff-card').css({
                    'border-color': '',
                    'background-color': ''
                });
            }
        });
    });
</script>
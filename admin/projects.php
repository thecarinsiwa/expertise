<?php
$pageTitle = 'Projets & Tâches – Administration';
$currentNav = 'projects';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$list = [];
$detail = null;
$stats = ['total' => 0, 'in_progress' => 0, 'draft' => 0];
$error = '';
$success = '';
$projectPhases = [];
$projectTasks = [];
$organisations = [];
$programmes = [];
$users = [];
$bailleurs = [];

$statusLabels = [
    'draft' => 'Brouillon',
    'planned' => 'Planifié',
    'in_progress' => 'En cours',
    'on_hold' => 'En pause',
    'completed' => 'Terminé',
    'cancelled' => 'Annulé',
];
$priorityLabels = ['low' => 'Basse', 'medium' => 'Moyenne', 'high' => 'Haute', 'critical' => 'Critique'];
$taskStatusLabels = ['todo' => 'À faire', 'in_progress' => 'En cours', 'review' => 'En revue', 'done' => 'Fait', 'cancelled' => 'Annulé'];
$subTaskStatusLabels = ['todo' => 'À faire', 'in_progress' => 'En cours', 'done' => 'Fait', 'cancelled' => 'Annulé'];

if ($pdo) {
    $action = $_GET['action'] ?? 'list';
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    // Charger listes pour formulaires
    $organisations = $pdo->query("SELECT id, name FROM organisation ORDER BY name")->fetchAll();
    try {
        $programmes = $pdo->query("SELECT id, name, portfolio_id FROM programme ORDER BY name")->fetchAll();
    } catch (PDOException $e) {
        $programmes = [];
    }
    $users = $pdo->query("SELECT id, first_name, last_name, email FROM user WHERE is_active = 1 ORDER BY last_name, first_name")->fetchAll();
    try {
        $bailleurs = $pdo->query("SELECT id, name, code FROM bailleur WHERE is_active = 1 ORDER BY name")->fetchAll();
    } catch (PDOException $e) {
        $bailleurs = [];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Suppression projet
        if (isset($_POST['delete_project_id'])) {
            $delId = (int) $_POST['delete_project_id'];
            try {
                $pdo->prepare("DELETE FROM project WHERE id = ?")->execute([$delId]);
                header('Location: projects.php?msg=deleted');
                exit;
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer le projet.';
            }
        }
        // Enregistrement projet
        if (isset($_POST['save_project'])) {
            $organisation_id = (int) ($_POST['organisation_id'] ?? 0);
            $programme_id = trim($_POST['programme_id'] ?? '') !== '' ? (int) $_POST['programme_id'] : null;
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '') ?: null;
            $description = trim($_POST['description'] ?? '') ?: null;
            $start_date = trim($_POST['start_date'] ?? '') ?: null;
            $end_date = trim($_POST['end_date'] ?? '') ?: null;
            $status = $_POST['status'] ?? 'draft';
            $priority = $_POST['priority'] ?? 'medium';
            $manager_user_id = trim($_POST['manager_user_id'] ?? '') !== '' ? (int) $_POST['manager_user_id'] : null;

            $cover_image = ($id > 0 && !empty($detail->cover_image)) ? $detail->cover_image : null;
            if (!empty($_FILES['cover_image']['name'])) {
                $target_dir = __DIR__ . '/../uploads/projects/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $file_ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
                $file_name = 'cover_' . time() . '_' . uniqid() . '.' . $file_ext;
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_dir . $file_name)) {
                    $cover_image = 'uploads/projects/' . $file_name;
                }
            }

            if ($name === '' || $organisation_id <= 0) {
                $error = 'Le nom et l\'organisation sont obligatoires.';
            } else {
                $bailleur_ids = isset($_POST['bailleur_ids']) && is_array($_POST['bailleur_ids']) ? array_map('intval', array_filter($_POST['bailleur_ids'])) : [];
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE project SET organisation_id = ?, programme_id = ?, name = ?, code = ?, description = ?, cover_image = ?, start_date = ?, end_date = ?, status = ?, priority = ?, manager_user_id = ? WHERE id = ?");
                    $stmt->execute([$organisation_id, $programme_id, $name, $code, $description, $cover_image, $start_date, $end_date, $status, $priority, $manager_user_id, $id]);
                    $success = 'Projet mis à jour.';
                    $action = 'view';
                    $pdo->prepare("DELETE FROM project_bailleur WHERE project_id = ?")->execute([$id]);
                    $insBailleur = $pdo->prepare("INSERT INTO project_bailleur (project_id, bailleur_id) VALUES (?, ?)");
                    foreach ($bailleur_ids as $bid) { if ($bid > 0) $insBailleur->execute([$id, $bid]); }
                    $stmt = $pdo->prepare("SELECT p.*, o.name AS organisation_name FROM project p LEFT JOIN organisation o ON p.organisation_id = o.id WHERE p.id = ?");
                    $stmt->execute([$id]);
                    $detail = $stmt->fetch();
                    if ($detail) {
                        $detail->manager_name = null;
                        if (!empty($detail->manager_user_id)) {
                            $m = $pdo->prepare("SELECT first_name, last_name FROM user WHERE id = ?");
                            $m->execute([$detail->manager_user_id]);
                            $u = $m->fetch();
                            $detail->manager_name = $u ? trim($u->first_name . ' ' . $u->last_name) : null;
                        }
                        $stmtPhases = $pdo->prepare("SELECT * FROM project_phase WHERE project_id = ? ORDER BY sequence ASC, id ASC");
                        $stmtPhases->execute([(int) $detail->id]);
                        $projectPhases = $stmtPhases->fetchAll(PDO::FETCH_OBJ);
                        try {
                            $pbStmt = $pdo->prepare("SELECT bailleur_id FROM project_bailleur WHERE project_id = ?");
                            $pbStmt->execute([(int) $detail->id]);
                            $detail->bailleur_ids = array_column($pbStmt->fetchAll(PDO::FETCH_ASSOC), 'bailleur_id');
                        } catch (PDOException $e) { $detail->bailleur_ids = []; }
                        try {
                            $pbStmt = $pdo->prepare("SELECT b.id, b.name FROM project_bailleur pb JOIN bailleur b ON pb.bailleur_id = b.id WHERE pb.project_id = ? ORDER BY b.name");
                            $pbStmt->execute([(int) $detail->id]);
                            $detail->bailleurs = $pbStmt->fetchAll(PDO::FETCH_OBJ);
                        } catch (PDOException $e) { $detail->bailleurs = []; }
                        $projectTasks = $pdo->prepare("SELECT t.*, u.first_name AS assignee_first, u.last_name AS assignee_last FROM task t LEFT JOIN user u ON t.assigned_user_id = u.id WHERE t.project_id = ? AND t.parent_task_id IS NULL ORDER BY t.sequence ASC, t.id ASC");
                        $projectTasks->execute([$id]);
                        $projectTasks = $projectTasks->fetchAll();
                        $stStmt = $pdo->prepare("SELECT st.*, u.first_name AS assignee_first, u.last_name AS assignee_last FROM sub_task st LEFT JOIN user u ON st.assigned_user_id = u.id WHERE st.task_id = ? ORDER BY st.sequence ASC, st.id ASC");
                        foreach ($projectTasks as $t) {
                            $stStmt->execute([$t->id]);
                            $t->sub_tasks = $stStmt->fetchAll();
                        }
                    }
                } else {
                    $stmt = $pdo->prepare("INSERT INTO project (organisation_id, programme_id, name, code, description, cover_image, start_date, end_date, status, priority, manager_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$organisation_id, $programme_id, $name, $code, $description, $cover_image, $start_date, $end_date, $status, $priority, $manager_user_id]);
                    $newId = (int) $pdo->lastInsertId();
                    $bailleur_ids = isset($_POST['bailleur_ids']) && is_array($_POST['bailleur_ids']) ? array_map('intval', array_filter($_POST['bailleur_ids'])) : [];
                    $insBailleur = $pdo->prepare("INSERT INTO project_bailleur (project_id, bailleur_id) VALUES (?, ?)");
                    foreach ($bailleur_ids as $bid) { if ($bid > 0) $insBailleur->execute([$newId, $bid]); }
                    header('Location: projects.php?id=' . $newId . '&msg=created');
                    exit;
                }
            }
        }
        // Ajout phase
        if (isset($_POST['add_phase']) && isset($_POST['project_id'])) {
            $pid = (int) $_POST['project_id'];
            $phaseName = trim($_POST['phase_name'] ?? '');
            $phaseSeq = (int) ($_POST['phase_sequence'] ?? 0);
            $phaseDesc = trim($_POST['phase_description'] ?? '') ?: null;
            $phaseImage = null;
            if (!empty($_FILES['phase_image']['name'])) {
                $target_dir = __DIR__ . '/../uploads/projects/phases/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $file_ext = strtolower(pathinfo($_FILES['phase_image']['name'], PATHINFO_EXTENSION));
                $file_name = 'phase_' . $pid . '_' . time() . '_' . uniqid() . '.' . $file_ext;
                if (move_uploaded_file($_FILES['phase_image']['tmp_name'], $target_dir . $file_name)) {
                    $phaseImage = 'uploads/projects/phases/' . $file_name;
                }
            }
            if ($phaseName !== '') {
                $pdo->prepare("INSERT INTO project_phase (project_id, name, sequence, description, image_url) VALUES (?, ?, ?, ?, ?)")->execute([$pid, $phaseName, $phaseSeq, $phaseDesc, $phaseImage]);
                $redirectEdit = isset($_POST['from_edit']) ? 'action=edit&' : '';
                header('Location: projects.php?' . $redirectEdit . 'id=' . $pid . '&msg=phase_added');
                exit;
            }
        }
        // Suppression phase
        if (isset($_POST['delete_phase_id'])) {
            $phaseId = (int) $_POST['delete_phase_id'];
            $pid = (int) ($_POST['project_id'] ?? 0);
            $pdo->prepare("DELETE FROM project_phase WHERE id = ?")->execute([$phaseId]);
            $redirectEdit = isset($_POST['from_edit']) ? 'action=edit&' : '';
            header('Location: projects.php?' . $redirectEdit . 'id=' . $pid . '&msg=phase_deleted');
            exit;
        }
        // Modification phase
        if (isset($_POST['update_phase']) && isset($_POST['phase_id']) && isset($_POST['project_id'])) {
            $phaseId = (int) $_POST['phase_id'];
            $pid = (int) $_POST['project_id'];
            $phaseName = trim($_POST['phase_name'] ?? '');
            $phaseSeq = (int) ($_POST['phase_sequence'] ?? 0);
            $phaseDesc = trim($_POST['phase_description'] ?? '') ?: null;
            $phaseImage = trim($_POST['existing_phase_image'] ?? '') ?: null;
            if (!empty($_FILES['phase_image']['name'])) {
                $target_dir = __DIR__ . '/../uploads/projects/phases/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $file_ext = strtolower(pathinfo($_FILES['phase_image']['name'], PATHINFO_EXTENSION));
                $file_name = 'phase_' . $pid . '_' . time() . '_' . uniqid() . '.' . $file_ext;
                if (move_uploaded_file($_FILES['phase_image']['tmp_name'], $target_dir . $file_name)) {
                    $phaseImage = 'uploads/projects/phases/' . $file_name;
                }
            }
            if ($phaseName !== '') {
                $pdo->prepare("UPDATE project_phase SET name = ?, sequence = ?, description = ?, image_url = ? WHERE id = ?")->execute([$phaseName, $phaseSeq, $phaseDesc, $phaseImage, $phaseId]);
                $redirectEdit = isset($_POST['from_edit']) ? 'action=edit&' : '';
                header('Location: projects.php?' . $redirectEdit . 'id=' . $pid . '&msg=phase_updated');
                exit;
            }
        }
        // Ajout tâche
        if (isset($_POST['add_task']) && isset($_POST['project_id'])) {
            $pid = (int) $_POST['project_id'];
            $title = trim($_POST['task_title'] ?? '');
            $phase_id = trim($_POST['task_phase_id'] ?? '') !== '' ? (int) $_POST['task_phase_id'] : null;
            $priority = $_POST['task_priority'] ?? 'medium';
            $status = $_POST['task_status'] ?? 'todo';
            $due_date = trim($_POST['task_due_date'] ?? '') ?: null;
            $assigned_user_id = trim($_POST['task_assigned_user_id'] ?? '') !== '' ? (int) $_POST['task_assigned_user_id'] : null;
            if ($title !== '') {
                $pdo->prepare("INSERT INTO task (project_id, project_phase_id, title, status, priority, due_date, assigned_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$pid, $phase_id, $title, $status, $priority, $due_date, $assigned_user_id]);
                $redirectEdit = isset($_POST['from_edit']) ? 'action=edit&' : '';
                header('Location: projects.php?' . $redirectEdit . 'id=' . $pid . '&msg=task_added');
                exit;
            }
        }
        // Suppression tâche
        if (isset($_POST['delete_task_id'])) {
            $pid = (int) ($_POST['project_id'] ?? 0);
            $pdo->prepare("DELETE FROM task WHERE id = ?")->execute([(int) $_POST['delete_task_id']]);
            $redirectEdit = isset($_POST['from_edit']) ? 'action=edit&' : '';
            header('Location: projects.php?' . $redirectEdit . 'id=' . $pid . '&msg=task_deleted');
            exit;
        }
        // Modification tâche
        if (isset($_POST['update_task']) && isset($_POST['task_id']) && isset($_POST['project_id'])) {
            $tid = (int) $_POST['task_id'];
            $pid = (int) $_POST['project_id'];
            $title = trim($_POST['task_title'] ?? '');
            $phase_id = trim($_POST['task_phase_id'] ?? '') !== '' ? (int) $_POST['task_phase_id'] : null;
            $priority = $_POST['task_priority'] ?? 'medium';
            $status = $_POST['task_status'] ?? 'todo';
            $due_date = trim($_POST['task_due_date'] ?? '') ?: null;
            $assigned_user_id = trim($_POST['task_assigned_user_id'] ?? '') !== '' ? (int) $_POST['task_assigned_user_id'] : null;
            if ($title !== '') {
                $pdo->prepare("UPDATE task SET title = ?, project_phase_id = ?, status = ?, priority = ?, due_date = ?, assigned_user_id = ? WHERE id = ?")
                    ->execute([$title, $phase_id, $status, $priority, $due_date, $assigned_user_id, $tid]);
                $redirectEdit = isset($_POST['from_edit']) ? 'action=edit&' : '';
                header('Location: projects.php?' . $redirectEdit . 'id=' . $pid . '&msg=task_updated');
                exit;
            }
        }
        // Ajout sous-tâche
        if (isset($_POST['add_sub_task']) && isset($_POST['task_id'])) {
            $tid = (int) $_POST['task_id'];
            $title = trim($_POST['sub_task_title'] ?? '');
            $status = $_POST['sub_task_status'] ?? 'todo';
            if ($title !== '') {
                $pdo->prepare("INSERT INTO sub_task (task_id, title, status) VALUES (?, ?, ?)")->execute([$tid, $title, $status]);
                $taskRow = $pdo->prepare("SELECT project_id FROM task WHERE id = ?");
                $taskRow->execute([$tid]);
                $pid = (int) $taskRow->fetch()->project_id;
                $redirectEdit = isset($_POST['from_edit']) ? 'action=edit&' : '';
                header('Location: projects.php?' . $redirectEdit . 'id=' . $pid . '&msg=subtask_added');
                exit;
            }
        }
        // Suppression sous-tâche
        if (isset($_POST['delete_sub_task_id'])) {
            $pid = (int) ($_POST['project_id'] ?? 0);
            $pdo->prepare("DELETE FROM sub_task WHERE id = ?")->execute([(int) $_POST['delete_sub_task_id']]);
            $redirectEdit = isset($_POST['from_edit']) ? 'action=edit&' : '';
            header('Location: projects.php?' . $redirectEdit . 'id=' . $pid . '&msg=subtask_deleted');
            exit;
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT p.*, o.name AS organisation_name FROM project p LEFT JOIN organisation o ON p.organisation_id = o.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();
        if ($detail) {
            $detail->manager_name = null;
            if (!empty($detail->manager_user_id)) {
                $m = $pdo->prepare("SELECT first_name, last_name FROM user WHERE id = ?");
                $m->execute([$detail->manager_user_id]);
                $u = $m->fetch();
                $detail->manager_name = $u ? trim($u->first_name . ' ' . $u->last_name) : null;
            }
            // Toujours recharger les phases pour ce projet
            try {
                $stmtPhases = $pdo->prepare("SELECT * FROM project_phase WHERE project_id = ? ORDER BY sequence ASC, id ASC");
                $stmtPhases->execute([(int) $detail->id]);
                $projectPhases = $stmtPhases->fetchAll(PDO::FETCH_OBJ);
            } catch (PDOException $e) {
                $projectPhases = [];
            }
            try {
                $pbStmt = $pdo->prepare("SELECT b.id, b.name FROM project_bailleur pb JOIN bailleur b ON pb.bailleur_id = b.id WHERE pb.project_id = ? ORDER BY b.name");
                $pbStmt->execute([(int) $detail->id]);
                $detail->bailleurs = $pbStmt->fetchAll(PDO::FETCH_OBJ);
                $detail->bailleur_ids = array_column($detail->bailleurs, 'id');
            } catch (PDOException $e) {
                $detail->bailleurs = [];
                $detail->bailleur_ids = [];
            }
            $projectTasks = $pdo->prepare("SELECT t.*, u.first_name AS assignee_first, u.last_name AS assignee_last FROM task t LEFT JOIN user u ON t.assigned_user_id = u.id WHERE t.project_id = ? AND t.parent_task_id IS NULL ORDER BY t.sequence ASC, t.id ASC");
            $projectTasks->execute([$id]);
            $projectTasks = $projectTasks->fetchAll();
            $stStmt = $pdo->prepare("SELECT st.*, u.first_name AS assignee_first, u.last_name AS assignee_last FROM sub_task st LEFT JOIN user u ON st.assigned_user_id = u.id WHERE st.task_id = ? ORDER BY st.sequence ASC, st.id ASC");
            foreach ($projectTasks as $t) {
                $stStmt->execute([$t->id]);
                $t->sub_tasks = $stStmt->fetchAll();
            }
        }
    }

    if (!$detail || $action === 'list') {
        $stats['total'] = (int) $pdo->query("SELECT COUNT(*) FROM project")->fetchColumn();
        $stats['in_progress'] = (int) $pdo->query("SELECT COUNT(*) FROM project WHERE status = 'in_progress'")->fetchColumn();
        $stats['draft'] = (int) $pdo->query("SELECT COUNT(*) FROM project WHERE status = 'draft'")->fetchColumn();
        $stmt = $pdo->query("SELECT p.id, p.name, p.code, p.status, p.priority, p.start_date, p.end_date, p.created_at, o.name AS organisation_name FROM project p LEFT JOIN organisation o ON p.organisation_id = o.id ORDER BY p.created_at DESC");
        if ($stmt) $list = $stmt->fetchAll();
    }
}
require __DIR__ . '/inc/header.php';

$isForm = ($action === 'add') || ($action === 'edit' && $detail);
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="projects.php" class="text-decoration-none">Projets & Tâches</a></li>
        <?php if ($action === 'add'): ?>
            <li class="breadcrumb-item active">Nouveau projet</li>
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
                if ($action === 'add') echo 'Nouveau projet';
                elseif ($action === 'edit' && $detail) echo 'Modifier le projet';
                elseif ($detail) echo htmlspecialchars($detail->name);
                else echo 'Projets & Tâches';
                ?>
            </h1>
            <p class="text-muted mb-0">
                <?php
                if ($detail && !$isForm) echo 'Fiche projet, phases et tâches';
                elseif ($action === 'add') echo 'Créer un nouveau projet.';
                else echo 'Gestion des projets et des tâches.';
                ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($detail && $action !== 'edit'): ?>
                <a href="projects.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
                <a href="projects.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <?php elseif ($isForm): ?>
                <a href="programmes.php" class="btn btn-admin-outline" target="_blank"><i class="bi bi-folder2 me-1"></i> Configuration des programmes</a>
                <a href="projects.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Annuler</a>
            <?php else: ?>
                <a href="programmes.php" class="btn btn-admin-outline"><i class="bi bi-folder2 me-1"></i> Configuration des programmes</a>
                <a href="projects.php?action=add" class="btn btn-admin-primary"><i class="bi bi-kanban me-1"></i> Nouveau projet</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Le projet a été supprimé.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Projet créé.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'phase_added'): ?>
    <div class="alert alert-success">Phase ajoutée.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'phase_deleted'): ?>
    <div class="alert alert-success">Phase supprimée.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'task_added'): ?>
    <div class="alert alert-success">Tâche ajoutée.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'task_deleted'): ?>
    <div class="alert alert-success">Tâche supprimée.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'subtask_added'): ?>
    <div class="alert alert-success">Sous-tâche ajoutée.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'subtask_deleted'): ?>
    <div class="alert alert-success">Sous-tâche supprimée.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'task_updated'): ?>
    <div class="alert alert-success">Tâche mise à jour.</div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'phase_updated'): ?>
    <div class="alert alert-success">Phase mise à jour.</div>
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
                <div class="mt-2"><span class="badge bg-secondary">Projets</span></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">En cours</div>
                <div class="h3 mb-0 fw-bold text-primary"><?= $stats['in_progress'] ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="admin-card text-center p-3 h-100">
                <div class="text-muted small text-uppercase mb-1 fw-bold">Brouillons</div>
                <div class="h3 mb-0 fw-bold text-muted"><?= $stats['draft'] ?></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($isForm): ?>
    <!-- Formulaire type Missions : onglets + stepper (Phases & Tâches en édition) -->
    <div class="admin-card admin-section-card p-0 overflow-hidden">
        <form method="POST" action="<?= $id ? 'projects.php?action=edit&id=' . $id : 'projects.php?action=add' ?>" id="projectForm" enctype="multipart/form-data">
            <input type="hidden" name="save_project" value="1">
        </form>
        <div class="admin-header-tabs px-4 pt-3 bg-light border-bottom">
            <ul class="nav nav-tabs border-0" id="projectTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button">1. Général</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-planning" type="button">2. Planning & Responsable</button>
                </li>
                <?php if ($id > 0): ?>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-phases" type="button">3. Phases</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tasks" type="button">4. Tâches</button>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="tab-content p-4">
            <!-- TAB 1: GÉNÉRAL -->
            <div class="tab-pane fade show active" id="tab-general">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-bold">Nom du projet *</label>
                        <input form="projectForm" type="text" name="name" class="form-control" value="<?= htmlspecialchars($detail->name ?? '') ?>" required placeholder="Nom du projet">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Code</label>
                        <input form="projectForm" type="text" name="code" class="form-control" value="<?= htmlspecialchars($detail->code ?? '') ?>" placeholder="ex: PRJ-001">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Organisation *</label>
                        <select form="projectForm" name="organisation_id" class="form-select" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($organisations as $o): ?>
                                <option value="<?= (int) $o->id ?>" <?= ($detail && $detail->organisation_id == $o->id) ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Programme</label>
                        <select form="projectForm" name="programme_id" class="form-select">
                            <option value="">— Aucun —</option>
                            <?php foreach ($programmes as $pr): ?>
                                <option value="<?= (int) $pr->id ?>" <?= ($detail && $detail->programme_id == $pr->id) ? 'selected' : '' ?>><?= htmlspecialchars($pr->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted"><a href="programmes.php" target="_blank">Configuration des programmes</a></small>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Bailleurs de fonds</label>
                        <select form="projectForm" name="bailleur_ids[]" class="form-select" multiple size="4">
                            <?php foreach ($bailleurs as $b): ?>
                                <option value="<?= (int) $b->id ?>" <?= ($detail && in_array((int)$b->id, $detail->bailleur_ids ?? [])) ? 'selected' : '' ?>><?= htmlspecialchars($b->name) ?><?= !empty($b->code) ? ' (' . htmlspecialchars($b->code) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs bailleurs. <a href="bailleurs.php?action=add">Créer un bailleur</a></small>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Description</label>
                        <textarea form="projectForm" name="description" id="project_description" class="form-control" rows="6" placeholder="Description du projet..."><?= htmlspecialchars($detail->description ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Photo de couverture</label>
                        <?php if (!empty($detail->cover_image)): ?>
                            <div class="mb-2">
                                <img src="../<?= htmlspecialchars($detail->cover_image) ?>" alt="Couverture" class="rounded border" style="height: 100px; object-fit: cover;">
                            </div>
                        <?php endif; ?>
                        <input form="projectForm" type="file" name="cover_image" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>

            <!-- TAB 2: PLANNING & RESPONSABLE -->
            <div class="tab-pane fade" id="tab-planning">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Date de début</label>
                        <input form="projectForm" type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($detail->start_date ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Date de fin</label>
                        <input form="projectForm" type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($detail->end_date ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Statut</label>
                        <select form="projectForm" name="status" class="form-select">
                            <?php foreach ($statusLabels as $k => $v): ?>
                                <option value="<?= $k ?>" <?= ($detail && $detail->status === $k) ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Priorité</label>
                        <select form="projectForm" name="priority" class="form-select">
                            <?php foreach ($priorityLabels as $k => $v): ?>
                                <option value="<?= $k ?>" <?= ($detail && $detail->priority === $k) ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Chef de projet</label>
                        <select form="projectForm" name="manager_user_id" class="form-select">
                            <option value="">— Non assigné —</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int) $u->id ?>" <?= ($detail && $detail->manager_user_id == $u->id) ? 'selected' : '' ?>><?= htmlspecialchars($u->first_name . ' ' . $u->last_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <?php if ($id > 0): ?>
            <!-- TAB 3: PHASES (uniquement en édition) -->
            <div class="tab-pane fade" id="tab-phases">
                <h5 class="h6 fw-bold mb-3"><i class="bi bi-list-ol"></i> Phases <span class="badge bg-secondary ms-2"><?= count($projectPhases) ?></span></h5>
                <form method="POST" class="mb-3" enctype="multipart/form-data">
                    <input type="hidden" name="project_id" value="<?= (int) $detail->id ?>">
                    <input type="hidden" name="add_phase" value="1">
                    <input type="hidden" name="from_edit" value="1">
                    <div class="row g-2 align-items-end mb-2">
                        <div class="col-md-5"><input type="text" name="phase_name" class="form-control form-control-sm" placeholder="Nom de la phase" required></div>
                        <div class="col-md-2"><input type="number" name="phase_sequence" class="form-control form-control-sm" value="0" placeholder="Ordre"></div>
                        <div class="col-md-3"><input type="file" name="phase_image" class="form-control form-control-sm" accept="image/*" placeholder="Photo"></div>
                        <div class="col-auto"><button type="submit" class="btn btn-sm btn-admin-primary">Ajouter une phase</button></div>
                    </div>
                    <div class="row g-2">
                        <div class="col-12"><textarea name="phase_description" class="form-control form-control-sm" rows="2" placeholder="Description de la phase (optionnel)"></textarea></div>
                    </div>
                </form>
                <?php if (count($projectPhases) > 0): ?>
                    <ul class="list-group">
                        <?php foreach ($projectPhases as $ph): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= htmlspecialchars($ph->name) ?> <small class="text-muted">(ordre <?= (int) $ph->sequence ?>)</small></span>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-phase"
                                        data-phase-id="<?= (int) $ph->id ?>"
                                        data-project-id="<?= (int) $detail->id ?>"
                                        data-from-edit="1"
                                        data-name="<?= htmlspecialchars($ph->name, ENT_QUOTES, 'UTF-8') ?>"
                                        data-sequence="<?= (int) $ph->sequence ?>"
                                        data-description="<?= htmlspecialchars($ph->description ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-image-url="<?= htmlspecialchars($ph->image_url ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi bi-pencil"></i> Modifier
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette phase ?');">
                                        <input type="hidden" name="project_id" value="<?= (int) $detail->id ?>">
                                        <input type="hidden" name="delete_phase_id" value="<?= (int) $ph->id ?>">
                                        <input type="hidden" name="from_edit" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="admin-empty mb-0 py-3"><i class="bi bi-inbox"></i> Aucune phase. Ajoutez-en une ci-dessus.</p>
                <?php endif; ?>
            </div>

            <!-- TAB 4: TÂCHES (uniquement en édition) -->
            <div class="tab-pane fade" id="tab-tasks">
                <h5 class="h6 fw-bold mb-3"><i class="bi bi-check2-square"></i> Tâches</h5>
                <form method="POST" class="mb-4 p-3 bg-light rounded row g-2 align-items-end">
                    <input type="hidden" name="project_id" value="<?= (int) $detail->id ?>">
                    <input type="hidden" name="add_task" value="1">
                    <input type="hidden" name="from_edit" value="1">
                    <div class="col-12"><input type="text" name="task_title" class="form-control" placeholder="Titre de la tâche" required></div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Phase</label>
                        <select name="task_phase_id" class="form-select form-select-sm">
                            <option value="">— Aucune —</option>
                            <?php foreach ($projectPhases as $ph): ?>
                                <option value="<?= (int) $ph->id ?>"><?= htmlspecialchars($ph->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Statut</label>
                        <select name="task_status" class="form-select form-select-sm">
                            <?php foreach ($taskStatusLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Priorité</label>
                        <select name="task_priority" class="form-select form-select-sm">
                            <?php foreach ($priorityLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Échéance</label>
                        <input type="date" name="task_due_date" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Assigné à</label>
                        <select name="task_assigned_user_id" class="form-select form-select-sm">
                            <option value="">— Non assigné —</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int) $u->id ?>"><?= htmlspecialchars($u->first_name . ' ' . $u->last_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12"><button type="submit" class="btn btn-admin-primary btn-sm">Ajouter une tâche</button></div>
                </form>

                <?php if (count($projectTasks) > 0): ?>
                    <div class="table-responsive">
                        <table class="admin-table table">
                            <thead>
                                <tr>
                                    <th>Tâche</th>
                                    <th>Statut</th>
                                    <th>Priorité</th>
                                    <th>Échéance</th>
                                    <th>Assigné à</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projectTasks as $t): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($t->title) ?></strong>
                                            <?php if (!empty($t->sub_tasks)): ?>
                                                <ul class="small text-muted mb-0 mt-1 ps-3">
                                                    <?php foreach ($t->sub_tasks as $st): ?>
                                                        <li class="d-flex justify-content-between align-items-center">
                                                            <?= htmlspecialchars($st->title) ?>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette sous-tâche ?');">
                                                                <input type="hidden" name="project_id" value="<?= (int) $detail->id ?>">
                                                                <input type="hidden" name="delete_sub_task_id" value="<?= (int) $st->id ?>">
                                                                <input type="hidden" name="from_edit" value="1">
                                                                <button type="submit" class="btn btn-link btn-sm text-danger p-0">Supprimer</button>
                                                            </form>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                                <form method="POST" class="mt-2 d-flex gap-2 align-items-center">
                                                    <input type="hidden" name="task_id" value="<?= (int) $t->id ?>">
                                                    <input type="hidden" name="project_id" value="<?= (int) $detail->id ?>">
                                                    <input type="hidden" name="add_sub_task" value="1">
                                                    <input type="hidden" name="from_edit" value="1">
                                                    <input type="text" name="sub_task_title" class="form-control form-control-sm" style="max-width:200px" placeholder="Nouvelle sous-tâche">
                                                    <select name="sub_task_status" class="form-select form-select-sm" style="max-width:120px">
                                                        <?php foreach ($subTaskStatusLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Ajouter</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="mt-2 d-flex gap-2 align-items-center">
                                                    <input type="hidden" name="task_id" value="<?= (int) $t->id ?>">
                                                    <input type="hidden" name="project_id" value="<?= (int) $detail->id ?>">
                                                    <input type="hidden" name="add_sub_task" value="1">
                                                    <input type="hidden" name="from_edit" value="1">
                                                    <input type="text" name="sub_task_title" class="form-control form-control-sm" style="max-width:200px" placeholder="Ajouter une sous-tâche">
                                                    <select name="sub_task_status" class="form-select form-select-sm" style="max-width:120px">
                                                        <?php foreach ($subTaskStatusLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Ajouter sous-tâche</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-secondary"><?= $taskStatusLabels[$t->status] ?? $t->status ?></span></td>
                                        <td><?= $priorityLabels[$t->priority] ?? $t->priority ?></td>
                                        <td><?= $t->due_date ? date('d/m/Y', strtotime($t->due_date)) : '—' ?></td>
                                        <td><?= $t->assignee_first ? htmlspecialchars(trim($t->assignee_first . ' ' . $t->assignee_last)) : '—' ?></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-task"
                                                data-task-id="<?= (int) $t->id ?>"
                                                data-project-id="<?= (int) $detail->id ?>"
                                                data-from-edit="1"
                                                data-title="<?= htmlspecialchars($t->title, ENT_QUOTES, 'UTF-8') ?>"
                                                data-phase-id="<?= (int)($t->project_phase_id ?? 0) ?>"
                                                data-status="<?= htmlspecialchars($t->status ?? 'todo') ?>"
                                                data-priority="<?= htmlspecialchars($t->priority ?? 'medium') ?>"
                                                data-due-date="<?= htmlspecialchars($t->due_date ?? '') ?>"
                                                data-assigned-id="<?= (int)($t->assigned_user_id ?? 0) ?>">
                                                <i class="bi bi-pencil"></i> Modifier
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette tâche et ses sous-tâches ?');">
                                                <input type="hidden" name="project_id" value="<?= (int) $detail->id ?>">
                                                <input type="hidden" name="delete_task_id" value="<?= (int) $t->id ?>">
                                                <input type="hidden" name="from_edit" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="admin-empty"><i class="bi bi-check2-square"></i> Aucune tâche. Ajoutez-en une avec le formulaire ci-dessus.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="admin-footer-actions border-top p-4 d-flex justify-content-between align-items-center">
            <div>
                <button type="button" class="btn btn-admin-outline me-2 prev-tab" style="display:none">Précédent</button>
                <button type="button" class="btn btn-admin-primary next-tab">Suivant</button>
            </div>
            <div>
                <a href="projects.php<?= $id ? '?id=' . $id : '' ?>" class="btn btn-admin-outline me-2">Annuler</a>
                <button form="projectForm" type="submit" class="btn btn-success px-4" id="submitProject" style="display:none">
                    <i class="bi bi-cloud-upload me-1"></i> Finaliser & Enregistrer
                </button>
            </div>
        </div>
    </div>
    <?php if ($id > 0): ?>
    <div class="modal fade modal-edit-task" id="editTaskModalForm" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil me-1"></i> Modifier la tâche</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editTaskFormInForm">
                    <input type="hidden" name="update_task" value="1">
                    <input type="hidden" name="task_id" id="editTaskForm_task_id">
                    <input type="hidden" name="project_id" id="editTaskForm_project_id">
                    <input type="hidden" name="from_edit" value="1">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Titre *</label>
                            <input type="text" name="task_title" id="editTaskForm_title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Phase</label>
                            <select name="task_phase_id" id="editTaskForm_phase_id" class="form-select">
                                <option value="">— Aucune —</option>
                                <?php foreach ($projectPhases as $ph): ?>
                                    <option value="<?= (int) $ph->id ?>"><?= htmlspecialchars($ph->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label fw-bold">Statut</label>
                                <select name="task_status" id="editTaskForm_status" class="form-select">
                                    <?php foreach ($taskStatusLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Priorité</label>
                                <select name="task_priority" id="editTaskForm_priority" class="form-select">
                                    <?php foreach ($priorityLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 mt-2">
                            <label class="form-label fw-bold">Échéance</label>
                            <input type="date" name="task_due_date" id="editTaskForm_due_date" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Assigné à</label>
                            <select name="task_assigned_user_id" id="editTaskForm_assigned_id" class="form-select">
                                <option value="">— Non assigné —</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= (int) $u->id ?>"><?= htmlspecialchars($u->first_name . ' ' . $u->last_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-admin-primary"><i class="bi bi-check-lg me-1"></i> Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Modifier phase (page édition) -->
    <div class="modal fade modal-edit-phase" id="editPhaseModalForm" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil me-1"></i> Modifier la phase</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editPhaseFormInForm" enctype="multipart/form-data">
                    <input type="hidden" name="update_phase" value="1">
                    <input type="hidden" name="phase_id" id="editPhaseForm_phase_id">
                    <input type="hidden" name="project_id" id="editPhaseForm_project_id">
                    <input type="hidden" name="from_edit" value="1">
                    <input type="hidden" name="existing_phase_image" id="editPhaseForm_existing_image">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom de la phase *</label>
                            <input type="text" name="phase_name" id="editPhaseForm_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Ordre</label>
                            <input type="number" name="phase_sequence" id="editPhaseForm_sequence" class="form-control" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="phase_description" id="editPhaseForm_description" class="form-control" rows="3" placeholder="Description de l'étape (optionnel)"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Photo de l'étape</label>
                            <div id="editPhaseForm_image_preview" class="mb-2"></div>
                            <input type="file" name="phase_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-admin-primary"><i class="bi bi-check-lg me-1"></i> Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($detail && !$isForm): ?>
    <?php if (!empty($detail->cover_image)): ?>
    <div class="mb-4 rounded overflow-hidden border shadow-sm" style="max-height: 280px;">
        <img src="../<?= htmlspecialchars($detail->cover_image) ?>" alt="Couverture" class="w-100 h-100" style="object-fit: cover;">
    </div>
    <?php endif; ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-kanban"></i> Informations projet</h5>
        <table class="admin-table mb-0">
            <tr><th style="width:180px;">Organisation</th><td><?= htmlspecialchars($detail->organisation_name ?? '—') ?></td></tr>
            <tr><th>Code</th><td><?= htmlspecialchars($detail->code ?? '—') ?></td></tr>
            <tr><th>Bailleurs de fonds</th><td><?= !empty($detail->bailleurs) ? implode(', ', array_map(function($b) { return htmlspecialchars($b->name); }, $detail->bailleurs)) : '—' ?></td></tr>
            <tr><th>Description</th><td class="project-description"><?= !empty($detail->description) ? $detail->description : '—' ?></td></tr>
            <tr><th>Dates</th><td><?= $detail->start_date ? date('d/m/Y', strtotime($detail->start_date)) : '—' ?> — <?= $detail->end_date ? date('d/m/Y', strtotime($detail->end_date)) : '—' ?></td></tr>
            <tr><th>Statut</th><td><span class="badge bg-secondary"><?= $statusLabels[$detail->status] ?? $detail->status ?></span></td></tr>
            <tr><th>Priorité</th><td><?= $priorityLabels[$detail->priority] ?? $detail->priority ?></td></tr>
            <tr><th>Chef de projet</th><td><?= htmlspecialchars($detail->manager_name ?? '—') ?></td></tr>
        </table>
        <div class="mt-4 d-flex gap-2">
            <a href="projects.php?action=edit&id=<?= (int) $detail->id ?>" class="btn btn-admin-primary"><i class="bi bi-pencil me-1"></i> Modifier</a>
            <a href="projects.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Liste</a>
            <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteProjectModal"><i class="bi bi-trash me-1"></i> Supprimer</button>
        </div>
    </div>

    <!-- Phases -->
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-list-ol"></i> Phases <span class="badge bg-secondary ms-2"><?= count($projectPhases) ?></span></h5>
        <form method="POST" class="mb-3" enctype="multipart/form-data">
            <input type="hidden" name="project_id" value="<?= (int) $detail->id ?>">
            <input type="hidden" name="add_phase" value="1">
            <div class="row g-2 align-items-end mb-2">
                <div class="col-md-5"><input type="text" name="phase_name" class="form-control form-control-sm" placeholder="Nom de la phase" required></div>
                <div class="col-md-2"><input type="number" name="phase_sequence" class="form-control form-control-sm" value="0" placeholder="Ordre"></div>
                <div class="col-md-3"><input type="file" name="phase_image" class="form-control form-control-sm" accept="image/*"></div>
                <div class="col-auto"><button type="submit" class="btn btn-sm btn-admin-primary">Ajouter une phase</button></div>
            </div>
            <div class="row g-2"><div class="col-12"><textarea name="phase_description" class="form-control form-control-sm" rows="2" placeholder="Description de la phase (optionnel)"></textarea></div></div>
        </form>
        <?php if (count($projectPhases) > 0): ?>
            <ul class="list-group">
                <?php foreach ($projectPhases as $ph): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div class="flex-grow-1">
                                <span class="fw-bold"><?= htmlspecialchars($ph->name) ?></span> <small class="text-muted">(ordre <?= (int) $ph->sequence ?>)</small>
                                <?php if (!empty($ph->description)): ?>
                                    <div class="mt-2 text-muted small project-description"><?= nl2br(htmlspecialchars($ph->description)) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($ph->image_url)): ?>
                                    <div class="mt-2"><img src="../<?= htmlspecialchars($ph->image_url) ?>" alt="" class="rounded border" style="max-height: 120px; object-fit: cover;"></div>
                                <?php endif; ?>
                            </div>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-phase"
                                data-phase-id="<?= (int) $ph->id ?>"
                                data-project-id="<?= (int) $detail->id ?>"
                                data-name="<?= htmlspecialchars($ph->name, ENT_QUOTES, 'UTF-8') ?>"
                                data-sequence="<?= (int) $ph->sequence ?>"
                                data-description="<?= htmlspecialchars($ph->description ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-image-url="<?= htmlspecialchars($ph->image_url ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-pencil"></i> Modifier
                            </button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette phase ?');">
                                <input type="hidden" name="project_id" value="<?= (int) $detail->id ?>">
                                <input type="hidden" name="delete_phase_id" value="<?= (int) $ph->id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                            </form>
                        </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="admin-empty mb-0 py-3"><i class="bi bi-inbox"></i> Aucune phase. Ajoutez-en une ci-dessus.</p>
        <?php endif; ?>
    </div>

    <!-- Tâches -->
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-check2-square"></i> Tâches</h5>
        <form method="POST" class="mb-4 p-3 bg-light rounded row g-2 align-items-end">
            <input type="hidden" name="project_id" value="<?= (int) $detail->id ?>">
            <input type="hidden" name="add_task" value="1">
            <div class="col-12"><input type="text" name="task_title" class="form-control" placeholder="Titre de la tâche" required></div>
            <div class="col-md-3">
                <label class="form-label small mb-0">Phase</label>
                <select name="task_phase_id" class="form-select form-select-sm">
                    <option value="">— Aucune —</option>
                    <?php foreach ($projectPhases as $ph): ?>
                        <option value="<?= (int) $ph->id ?>"><?= htmlspecialchars($ph->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Statut</label>
                <select name="task_status" class="form-select form-select-sm">
                    <?php foreach ($taskStatusLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Priorité</label>
                <select name="task_priority" class="form-select form-select-sm">
                    <?php foreach ($priorityLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Échéance</label>
                <input type="date" name="task_due_date" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-0">Assigné à</label>
                <select name="task_assigned_user_id" class="form-select form-select-sm">
                    <option value="">— Non assigné —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int) $u->id ?>"><?= htmlspecialchars($u->first_name . ' ' . $u->last_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12"><button type="submit" class="btn btn-admin-primary btn-sm">Ajouter une tâche</button></div>
        </form>

        <?php if (count($projectTasks) > 0): ?>
            <div class="table-responsive">
                <table class="admin-table table">
                    <thead>
                        <tr>
                            <th>Tâche</th>
                            <th>Statut</th>
                            <th>Priorité</th>
                            <th>Échéance</th>
                            <th>Assigné à</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projectTasks as $t): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($t->title) ?></strong>
                                    <?php if (!empty($t->sub_tasks)): ?>
                                        <ul class="small text-muted mb-0 mt-1 ps-3">
                                            <?php foreach ($t->sub_tasks as $st): ?>
                                                <li class="d-flex justify-content-between align-items-center">
                                                    <?= htmlspecialchars($st->title) ?>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette sous-tâche ?');">
                                                        <input type="hidden" name="project_id" value="<?= (int) $detail->id ?>">
                                                        <input type="hidden" name="delete_sub_task_id" value="<?= (int) $st->id ?>">
                                                        <button type="submit" class="btn btn-link btn-sm text-danger p-0">Supprimer</button>
                                                    </form>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <form method="POST" class="mt-2 d-flex gap-2 align-items-center">
                                            <input type="hidden" name="task_id" value="<?= (int) $t->id ?>">
                                            <input type="hidden" name="project_id" value="<?= (int) $detail->id ?>">
                                            <input type="hidden" name="add_sub_task" value="1">
                                            <input type="text" name="sub_task_title" class="form-control form-control-sm" style="max-width:200px" placeholder="Nouvelle sous-tâche">
                                            <select name="sub_task_status" class="form-select form-select-sm" style="max-width:120px">
                                                <?php foreach ($subTaskStatusLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Ajouter</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" class="mt-2 d-flex gap-2 align-items-center">
                                            <input type="hidden" name="task_id" value="<?= (int) $t->id ?>">
                                            <input type="hidden" name="project_id" value="<?= (int) $detail->id ?>">
                                            <input type="hidden" name="add_sub_task" value="1">
                                            <input type="text" name="sub_task_title" class="form-control form-control-sm" style="max-width:200px" placeholder="Ajouter une sous-tâche">
                                            <select name="sub_task_status" class="form-select form-select-sm" style="max-width:120px">
                                                <?php foreach ($subTaskStatusLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Ajouter sous-tâche</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= $taskStatusLabels[$t->status] ?? $t->status ?></span></td>
                                <td><?= $priorityLabels[$t->priority] ?? $t->priority ?></td>
                                <td><?= $t->due_date ? date('d/m/Y', strtotime($t->due_date)) : '—' ?></td>
                                <td><?= $t->assignee_first ? htmlspecialchars(trim($t->assignee_first . ' ' . $t->assignee_last)) : '—' ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit-task"
                                        data-task-id="<?= (int) $t->id ?>"
                                        data-project-id="<?= (int) $detail->id ?>"
                                        data-title="<?= htmlspecialchars($t->title, ENT_QUOTES, 'UTF-8') ?>"
                                        data-phase-id="<?= (int)($t->project_phase_id ?? 0) ?>"
                                        data-status="<?= htmlspecialchars($t->status ?? 'todo') ?>"
                                        data-priority="<?= htmlspecialchars($t->priority ?? 'medium') ?>"
                                        data-due-date="<?= htmlspecialchars($t->due_date ?? '') ?>"
                                        data-assigned-id="<?= (int)($t->assigned_user_id ?? 0) ?>">
                                        <i class="bi bi-pencil"></i> Modifier
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette tâche et ses sous-tâches ?');">
                                        <input type="hidden" name="project_id" value="<?= (int) $detail->id ?>">
                                        <input type="hidden" name="delete_task_id" value="<?= (int) $t->id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="admin-empty"><i class="bi bi-check2-square"></i> Aucune tâche. Ajoutez-en une avec le formulaire ci-dessus.</p>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="deleteProjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Supprimer ce projet ?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    Le projet <strong><?= htmlspecialchars($detail->name) ?></strong> et toutes les phases, tâches et sous-tâches associées seront supprimés. Cette action est irréversible.
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="delete_project_id" value="<?= (int) $detail->id ?>">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Modifier tâche (page fiche projet) -->
    <div class="modal fade modal-edit-task" id="editTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil me-1"></i> Modifier la tâche</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editTaskForm">
                    <input type="hidden" name="update_task" value="1">
                    <input type="hidden" name="task_id" id="editTask_task_id">
                    <input type="hidden" name="project_id" id="editTask_project_id">
                    <input type="hidden" name="from_edit" id="editTask_from_edit" value="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Titre *</label>
                            <input type="text" name="task_title" id="editTask_title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Phase</label>
                            <select name="task_phase_id" id="editTask_phase_id" class="form-select">
                                <option value="">— Aucune —</option>
                                <?php foreach ($projectPhases as $ph): ?>
                                    <option value="<?= (int) $ph->id ?>"><?= htmlspecialchars($ph->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label fw-bold">Statut</label>
                                <select name="task_status" id="editTask_status" class="form-select">
                                    <?php foreach ($taskStatusLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Priorité</label>
                                <select name="task_priority" id="editTask_priority" class="form-select">
                                    <?php foreach ($priorityLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 mt-2">
                            <label class="form-label fw-bold">Échéance</label>
                            <input type="date" name="task_due_date" id="editTask_due_date" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Assigné à</label>
                            <select name="task_assigned_user_id" id="editTask_assigned_id" class="form-select">
                                <option value="">— Non assigné —</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= (int) $u->id ?>"><?= htmlspecialchars($u->first_name . ' ' . $u->last_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-admin-primary"><i class="bi bi-check-lg me-1"></i> Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Modifier phase (fiche projet) -->
    <div class="modal fade modal-edit-phase" id="editPhaseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil me-1"></i> Modifier la phase</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editPhaseForm" enctype="multipart/form-data">
                    <input type="hidden" name="update_phase" value="1">
                    <input type="hidden" name="phase_id" id="editPhase_phase_id">
                    <input type="hidden" name="project_id" id="editPhase_project_id">
                    <input type="hidden" name="from_edit" id="editPhase_from_edit" value="">
                    <input type="hidden" name="existing_phase_image" id="editPhase_existing_image">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nom de la phase *</label>
                            <input type="text" name="phase_name" id="editPhase_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Ordre</label>
                            <input type="number" name="phase_sequence" id="editPhase_sequence" class="form-control" value="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="phase_description" id="editPhase_description" class="form-control" rows="3" placeholder="Description de l'étape (optionnel)"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Photo de l'étape</label>
                            <div id="editPhase_image_preview" class="mb-2"></div>
                            <input type="file" name="phase_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-admin-primary"><i class="bi bi-check-lg me-1"></i> Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php elseif (count($list) > 0): ?>
    <div class="admin-card admin-section-card">
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="projectsTable">
                <thead>
                    <tr>
                        <th>Projet</th>
                        <th>Organisation</th>
                        <th>Statut</th>
                        <th>Priorité</th>
                        <th>Dates</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $p): ?>
                        <tr>
                            <td>
                                <a href="projects.php?id=<?= (int) $p->id ?>"><?= htmlspecialchars($p->name) ?></a>
                                <?php if (!empty($p->code)): ?><br><small class="text-muted"><?= htmlspecialchars($p->code) ?></small><?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p->organisation_name ?? '—') ?></td>
                            <td><span class="badge bg-secondary"><?= $statusLabels[$p->status] ?? $p->status ?></span></td>
                            <td><?= $priorityLabels[$p->priority] ?? $p->priority ?></td>
                            <td><?= $p->start_date ? date('d/m/Y', strtotime($p->start_date)) : '—' ?> — <?= $p->end_date ? date('d/m/Y', strtotime($p->end_date)) : '—' ?></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><a class="dropdown-item" href="projects.php?id=<?= (int) $p->id ?>"><i class="bi bi-eye me-2"></i> Voir</a></li>
                                        <li><a class="dropdown-item" href="projects.php?action=edit&id=<?= (int) $p->id ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" onsubmit="return confirm('Supprimer ce projet et toutes ses données ?');">
                                                <input type="hidden" name="delete_project_id" value="<?= (int) $p->id ?>">
                                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i> Supprimer</button>
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
    </div>

<?php endif; ?>

<style>
    .breadcrumb { font-size: 0.85rem; }
    .admin-table th { background: #f8f9fa; padding: 1rem 0.5rem; }
    .admin-table td { padding: 1rem 0.5rem; }
    .project-description { vertical-align: top; }
    .project-description h1, .project-description h2, .project-description h3, .project-description h4 { margin: 1rem 0 0.5rem; font-size: 1rem; font-weight: 600; }
    .project-description p { margin: 0.5rem 0; }
    .project-description ul, .project-description ol { margin: 0.5rem 0; padding-left: 1.5rem; }
    .project-description img { max-width: 100%; height: auto; }
</style>
<?php if (!empty($list)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('projectsTable') && typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#projectsTable').DataTable({
            language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json" },
            order: [[0, "asc"]],
            pageLength: 10,
            dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>'
        });
    }
});
</script>
<?php endif; ?>

<?php if ($isForm): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.summernote) {
        $('#project_description').summernote({
            placeholder: 'Décrivez le projet ici...',
            tabsize: 2,
            height: 220,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    }

    const tabs = <?= $id > 0 ? "['tab-general', 'tab-planning', 'tab-phases', 'tab-tasks']" : "['tab-general', 'tab-planning']" ?>;
    let currentTabIdx = 0;

    function updateStepButtons() {
        document.querySelector('.prev-tab').style.display = currentTabIdx > 0 ? '' : 'none';
        if (currentTabIdx === tabs.length - 1) {
            document.querySelector('.next-tab').style.display = 'none';
            document.getElementById('submitProject').style.display = '';
        } else {
            document.querySelector('.next-tab').style.display = '';
            document.getElementById('submitProject').style.display = 'none';
        }
    }

    document.querySelector('.next-tab').addEventListener('click', function() {
        if (currentTabIdx < tabs.length - 1) {
            currentTabIdx++;
            const tabEl = document.querySelector('button[data-bs-target="#' + tabs[currentTabIdx] + '"]');
            if (tabEl) {
                bootstrap.Tab.getOrCreateInstance(tabEl).show();
            }
            updateStepButtons();
        }
    });

    document.querySelector('.prev-tab').addEventListener('click', function() {
        if (currentTabIdx > 0) {
            currentTabIdx--;
            const tabEl = document.querySelector('button[data-bs-target="#' + tabs[currentTabIdx] + '"]');
            if (tabEl) {
                bootstrap.Tab.getOrCreateInstance(tabEl).show();
            }
            updateStepButtons();
        }
    });

    document.querySelectorAll('#projectTabs button').forEach(function(btn) {
        btn.addEventListener('shown.bs.tab', function(e) {
            const targetId = e.target.getAttribute('data-bs-target').replace('#', '');
            currentTabIdx = tabs.indexOf(targetId);
            if (currentTabIdx < 0) currentTabIdx = 0;
            updateStepButtons();
        });
    });

    updateStepButtons();
});
</script>
<?php endif; ?>

<?php if ($detail): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-edit-task');
        if (!btn) return;
        e.preventDefault();
        var modal = document.getElementById('editTaskModal') || document.getElementById('editTaskModalForm');
        if (!modal) return;
        var form = modal.querySelector('form');
        if (!form) return;
        form.querySelector('[name="task_id"]').value = btn.getAttribute('data-task-id') || '';
        form.querySelector('[name="project_id"]').value = btn.getAttribute('data-project-id') || '';
        var fromEdit = btn.getAttribute('data-from-edit');
        var fromEditEl = form.querySelector('[name="from_edit"]');
        if (fromEditEl) fromEditEl.value = fromEdit === '1' ? '1' : '';
        form.querySelector('[name="task_title"]').value = btn.getAttribute('data-title') || '';
        var phaseId = btn.getAttribute('data-phase-id');
        form.querySelector('[name="task_phase_id"]').value = (phaseId && phaseId !== '0') ? phaseId : '';
        form.querySelector('[name="task_status"]').value = btn.getAttribute('data-status') || 'todo';
        form.querySelector('[name="task_priority"]').value = btn.getAttribute('data-priority') || 'medium';
        form.querySelector('[name="task_due_date"]').value = btn.getAttribute('data-due-date') || '';
        var assignedId = btn.getAttribute('data-assigned-id');
        form.querySelector('[name="task_assigned_user_id"]').value = (assignedId && assignedId !== '0') ? assignedId : '';
        var modalInst = bootstrap.Modal.getOrCreateInstance(modal);
        modalInst.show();
    });

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-edit-phase');
        if (!btn) return;
        e.preventDefault();
        var modal = document.getElementById('editPhaseModal') || document.getElementById('editPhaseModalForm');
        if (!modal) return;
        var form = modal.querySelector('form');
        if (!form) return;
        form.querySelector('[name="phase_id"]').value = btn.getAttribute('data-phase-id') || '';
        form.querySelector('[name="project_id"]').value = btn.getAttribute('data-project-id') || '';
        var fromEdit = btn.getAttribute('data-from-edit');
        var fromEditEl = form.querySelector('[name="from_edit"]');
        if (fromEditEl) fromEditEl.value = fromEdit === '1' ? '1' : '';
        form.querySelector('[name="phase_name"]').value = btn.getAttribute('data-name') || '';
        form.querySelector('[name="phase_sequence"]').value = btn.getAttribute('data-sequence') || '0';
        var descEl = form.querySelector('[name="phase_description"]');
        if (descEl) descEl.value = btn.getAttribute('data-description') || '';
        var existingImgEl = form.querySelector('[name="existing_phase_image"]');
        var imgUrl = btn.getAttribute('data-image-url') || '';
        if (existingImgEl) existingImgEl.value = imgUrl;
        var preview = modal.querySelector('[id$="_image_preview"]');
        if (preview) {
            var safeUrl = (imgUrl || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
            preview.innerHTML = imgUrl ? '<img src="../' + safeUrl + '" alt="" class="rounded border" style="height:60px;object-fit:cover;">' : '';
        }
        var phaseModalInst = bootstrap.Modal.getOrCreateInstance(modal);
        phaseModalInst.show();
    });
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

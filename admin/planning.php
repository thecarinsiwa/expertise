<?php
/**
 * Planning & KPI – Calendriers, Événements, Plannings, Indicateurs de performance
 */
$pageTitle = 'Planning & KPI – Gestion';
$currentNav = 'planning';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$error = '';
$success = '';
$tab = $_GET['tab'] ?? 'calendars'; // calendars | events | schedules | kpi
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$organisations = [];
$calendars = [];
$users = [];
$list = [];
$detail = null;

if ($pdo) {
    $organisations = $pdo->query("SELECT id, name FROM organisation WHERE is_active = 1 ORDER BY name")->fetchAll();
    $users = $pdo->query("SELECT id, first_name, last_name, email FROM user WHERE is_active = 1 ORDER BY last_name, first_name")->fetchAll();
    $default_org_id = $organisations[0]->id ?? null;
    $created_by = $_SESSION['admin_id'] ?? null;

    // ---------- Calendriers ----------
    if ($tab === 'calendars') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_calendar'])) {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;
            $colour = trim($_POST['colour'] ?? '') ?: null;
            $is_public = isset($_POST['is_public']) ? 1 : 0;
            $org_id = !empty($_POST['organisation_id']) ? (int) $_POST['organisation_id'] : $default_org_id;
            $user_id = !empty($_POST['user_id']) ? (int) $_POST['user_id'] : null;
            $cal_id = isset($_POST['calendar_id']) ? (int) $_POST['calendar_id'] : 0;

            if ($name === '') {
                $error = 'Le nom du calendrier est obligatoire.';
            } else {
                try {
                    if ($cal_id > 0) {
                        $pdo->prepare("UPDATE calendar SET organisation_id = ?, user_id = ?, name = ?, description = ?, colour = ?, is_public = ? WHERE id = ?")
                            ->execute([$org_id, $user_id, $name, $description, $colour, $is_public, $cal_id]);
                        $success = 'Calendrier mis à jour.';
                    } else {
                        $pdo->prepare("INSERT INTO calendar (organisation_id, user_id, name, description, colour, is_public) VALUES (?, ?, ?, ?, ?, ?)")
                            ->execute([$org_id, $user_id, $name, $description, $colour, $is_public]);
                        $success = 'Calendrier créé.';
                    }
                } catch (PDOException $e) {
                    $error = $e->getMessage();
                }
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_calendar'])) {
            try {
                $pdo->prepare("DELETE FROM calendar WHERE id = ?")->execute([(int) $_POST['delete_calendar']]);
                $success = 'Calendrier supprimé.';
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer (événements liés).';
            }
        }

        $calendars = $pdo->query("SELECT cal.id, cal.name, cal.colour, cal.is_public, o.name AS org_name, CONCAT(u.first_name, ' ', u.last_name) AS user_name FROM calendar cal LEFT JOIN organisation o ON o.id = cal.organisation_id LEFT JOIN user u ON u.id = cal.user_id ORDER BY cal.name")->fetchAll();
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM calendar WHERE id = ?");
            $stmt->execute([$id]);
            $detail = $stmt->fetch();
        }
    }

    // ---------- Événements ----------
    if ($tab === 'events') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_event'])) {
            $calendar_id = (int) ($_POST['calendar_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;
            $start_at = trim($_POST['start_at'] ?? '');
            $end_at = trim($_POST['end_at'] ?? '');
            $location = trim($_POST['location'] ?? '') ?: null;
            $is_all_day = isset($_POST['is_all_day']) ? 1 : 0;
            $recurrence_rule = trim($_POST['recurrence_rule'] ?? '') ?: null;
            $ev_id = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;

            if ($calendar_id < 1 || $title === '' || $start_at === '' || $end_at === '') {
                $error = 'Calendrier, titre, date de début et date de fin requis.';
            } else {
                try {
                    if ($ev_id > 0) {
                        $pdo->prepare("UPDATE event SET calendar_id = ?, title = ?, description = ?, start_at = ?, end_at = ?, location = ?, is_all_day = ?, recurrence_rule = ?, created_by_user_id = ? WHERE id = ?")
                            ->execute([$calendar_id, $title, $description, $start_at, $end_at, $location, $is_all_day, $recurrence_rule, $created_by, $ev_id]);
                        $success = 'Événement mis à jour.';
                    } else {
                        $pdo->prepare("INSERT INTO event (calendar_id, title, description, start_at, end_at, location, is_all_day, recurrence_rule, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                            ->execute([$calendar_id, $title, $description, $start_at, $end_at, $location, $is_all_day, $recurrence_rule, $created_by]);
                        $success = 'Événement créé.';
                    }
                } catch (PDOException $e) {
                    $error = $e->getMessage();
                }
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
            try {
                $pdo->prepare("DELETE FROM event WHERE id = ?")->execute([(int) $_POST['delete_event']]);
                $success = 'Événement supprimé.';
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer l\'événement.';
            }
        }

        $calendars = $pdo->query("SELECT id, name FROM calendar ORDER BY name")->fetchAll();
        $sql = "SELECT e.id, e.title, e.start_at, e.end_at, e.is_all_day, c.name AS calendar_name FROM event e JOIN calendar c ON c.id = e.calendar_id WHERE 1=1";
        $params = [];
        if (!empty($_GET['calendar_id'])) {
            $sql .= " AND e.calendar_id = ?";
            $params[] = (int) $_GET['calendar_id'];
        }
        $sql .= " ORDER BY e.start_at DESC";
        $stmt = $params ? $pdo->prepare($sql) : $pdo->query($sql);
        if ($params) $stmt->execute($params);
        $list = $stmt->fetchAll();
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT e.*, c.name AS calendar_name FROM event e JOIN calendar c ON c.id = e.calendar_id WHERE e.id = ?");
            $stmt->execute([$id]);
            $detail = $stmt->fetch();
        }
    }

    // ---------- Plannings ----------
    if ($tab === 'schedules') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_schedule'])) {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '') ?: null;
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            $schedule_type = trim($_POST['schedule_type'] ?? '') ?: null;
            $org_id = !empty($_POST['organisation_id']) ? (int) $_POST['organisation_id'] : $default_org_id;
            $sched_id = isset($_POST['schedule_id']) ? (int) $_POST['schedule_id'] : 0;

            if ($name === '') {
                $error = 'Le nom du planning est obligatoire.';
            } else {
                try {
                    if ($sched_id > 0) {
                        $pdo->prepare("UPDATE schedule SET organisation_id = ?, name = ?, description = ?, start_date = ?, end_date = ?, schedule_type = ?, created_by_user_id = ? WHERE id = ?")
                            ->execute([$org_id, $name, $description, $start_date, $end_date, $schedule_type, $created_by, $sched_id]);
                        $success = 'Planning mis à jour.';
                    } else {
                        $pdo->prepare("INSERT INTO schedule (organisation_id, name, description, start_date, end_date, schedule_type, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)")
                            ->execute([$org_id, $name, $description, $start_date, $end_date, $schedule_type, $created_by]);
                        $success = 'Planning créé.';
                    }
                } catch (PDOException $e) {
                    $error = $e->getMessage();
                }
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedule'])) {
            try {
                $pdo->prepare("DELETE FROM schedule WHERE id = ?")->execute([(int) $_POST['delete_schedule']]);
                $success = 'Planning supprimé.';
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer.';
            }
        }

        $list = $pdo->query("SELECT s.*, o.name AS org_name FROM schedule s LEFT JOIN organisation o ON o.id = s.organisation_id ORDER BY s.start_date DESC, s.name")->fetchAll();
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM schedule WHERE id = ?");
            $stmt->execute([$id]);
            $detail = $stmt->fetch();
        }
    }

    // ---------- Indicateurs KPI ----------
    if ($tab === 'kpi') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_kpi'])) {
            $name = trim($_POST['name'] ?? '');
            $code = trim($_POST['code'] ?? '') ?: null;
            $description = trim($_POST['description'] ?? '') ?: null;
            $indicator_type = $_POST['indicator_type'] ?? 'numeric';
            $unit = trim($_POST['unit'] ?? '') ?: null;
            $target_value = trim($_POST['target_value'] ?? '') !== '' ? (float) $_POST['target_value'] : null;
            $current_value = trim($_POST['current_value'] ?? '') !== '' ? (float) $_POST['current_value'] : null;
            $period_type = trim($_POST['period_type'] ?? '') ?: null;
            $period_start = !empty($_POST['period_start']) ? $_POST['period_start'] : null;
            $period_end = !empty($_POST['period_end']) ? $_POST['period_end'] : null;
            $org_id = !empty($_POST['organisation_id']) ? (int) $_POST['organisation_id'] : $default_org_id;
            $kpi_id = isset($_POST['kpi_id']) ? (int) $_POST['kpi_id'] : 0;

            if ($name === '') {
                $error = 'Le nom de l\'indicateur est obligatoire.';
            } else {
                try {
                    if ($kpi_id > 0) {
                        $pdo->prepare("UPDATE performance_indicator SET organisation_id = ?, name = ?, code = ?, description = ?, indicator_type = ?, unit = ?, target_value = ?, current_value = ?, period_type = ?, period_start = ?, period_end = ? WHERE id = ?")
                            ->execute([$org_id, $name, $code, $description, $indicator_type, $unit, $target_value, $current_value, $period_type, $period_start, $period_end, $kpi_id]);
                        $success = 'Indicateur mis à jour.';
                    } else {
                        $pdo->prepare("INSERT INTO performance_indicator (organisation_id, name, code, description, indicator_type, unit, target_value, current_value, period_type, period_start, period_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                            ->execute([$org_id, $name, $code, $description, $indicator_type, $unit, $target_value, $current_value, $period_type, $period_start, $period_end]);
                        $success = 'Indicateur créé.';
                    }
                } catch (PDOException $e) {
                    $error = $e->getMessage();
                }
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_kpi'])) {
            try {
                $pdo->prepare("DELETE FROM performance_indicator WHERE id = ?")->execute([(int) $_POST['delete_kpi']]);
                $success = 'Indicateur supprimé.';
            } catch (PDOException $e) {
                $error = 'Impossible de supprimer.';
            }
        }

        $list = $pdo->query("SELECT p.*, o.name AS org_name FROM performance_indicator p LEFT JOIN organisation o ON o.id = p.organisation_id ORDER BY p.name")->fetchAll();
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM performance_indicator WHERE id = ?");
            $stmt->execute([$id]);
            $detail = $stmt->fetch();
        }
    }
}

require __DIR__ . '/inc/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="planning.php" class="text-decoration-none">Gestion</a></li>
        <li class="breadcrumb-item active">Planning & KPI</li>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>Planning & KPI</h1>
            <p class="text-muted mb-0">Calendriers, événements, plannings et indicateurs de performance.</p>
        </div>
    </div>
</header>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Raccourci configuration (comme page Missions / Documents) -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="admin-card p-3 d-flex flex-wrap gap-2 align-items-center bg-light border shadow-sm">
            <span class="text-muted small fw-bold text-uppercase me-2"><i class="bi bi-sliders me-1"></i> Configuration :</span>
            <a href="planning.php?tab=calendars" class="btn btn-sm btn-admin-outline"><i class="bi bi-calendar3 me-1"></i> Gérer les Calendriers</a>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link <?= $tab === 'calendars' ? 'active' : '' ?>" href="planning.php?tab=calendars">Calendriers</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'events' ? 'active' : '' ?>" href="planning.php?tab=events">Événements</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'schedules' ? 'active' : '' ?>" href="planning.php?tab=schedules">Plannings</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'kpi' ? 'active' : '' ?>" href="planning.php?tab=kpi">Indicateurs KPI</a></li>
</ul>

<?php if ($tab === 'calendars'): ?>
    <div class="admin-card admin-section-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted"><?= count($calendars) ?> calendrier(s)</span>
            <a href="planning.php?tab=calendars&action=add" class="btn btn-admin-primary btn-sm"><i class="bi bi-plus me-1"></i> Nouveau calendrier</a>
        </div>
        <?php if ($action === 'add' || ($action === 'edit' && $detail)): ?>
            <form method="POST" class="mb-4">
                <input type="hidden" name="save_calendar" value="1">
                <input type="hidden" name="calendar_id" value="<?= (int) ($detail->id ?? 0) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nom *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($detail->name ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Organisation</label>
                        <select name="organisation_id" class="form-select">
                            <?php foreach ($organisations as $o): ?>
                                <option value="<?= (int) $o->id ?>" <?= ($detail && (int)$detail->organisation_id === (int)$o->id) ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Propriétaire (utilisateur)</label>
                        <select name="user_id" class="form-select">
                            <option value="">— Aucun —</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int) $u->id ?>" <?= ($detail && (int)$detail->user_id === (int)$u->id) ? 'selected' : '' ?>><?= htmlspecialchars($u->first_name . ' ' . $u->last_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Couleur</label>
                        <input type="text" name="colour" class="form-control" value="<?= htmlspecialchars($detail->colour ?? '') ?>" placeholder="#3b82f6">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($detail->description ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="is_public" class="form-check-input" value="1" <?= ($detail && $detail->is_public) ? 'checked' : '' ?>>
                            <label class="form-check-label">Calendrier public</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-admin-primary">Enregistrer</button>
                        <a href="planning.php?tab=calendars" class="btn btn-secondary">Annuler</a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="calendarsTable">
                <thead><tr><th>Nom</th><th>Organisation</th><th>Propriétaire</th><th>Couleur</th><th>Public</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($calendars as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c->name) ?></td>
                            <td><?= htmlspecialchars($c->org_name ?? '—') ?></td>
                            <td><?= htmlspecialchars($c->user_name ?? '—') ?></td>
                            <td><?= $c->colour ? '<span class="badge" style="background-color:'.htmlspecialchars($c->colour).'">'.$c->colour.'</span>' : '—' ?></td>
                            <td><?= $c->is_public ? 'Oui' : 'Non' ?></td>
                            <td class="text-end">
                                <a href="planning.php?tab=calendars&action=edit&id=<?= (int) $c->id ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce calendrier et ses événements ?');">
                                    <input type="hidden" name="delete_calendar" value="<?= (int) $c->id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($calendars)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun calendrier. Créez-en un.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($tab === 'events'): ?>
    <div class="admin-card admin-section-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <form method="GET" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="tab" value="events">
                <label class="form-label mb-0 small">Calendrier</label>
                <select name="calendar_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <option value="">Tous</option>
                    <?php foreach ($calendars as $cal): ?>
                        <option value="<?= (int) $cal->id ?>" <?= (!empty($_GET['calendar_id']) && (int)$_GET['calendar_id'] === (int)$cal->id) ? 'selected' : '' ?>><?= htmlspecialchars($cal->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="planning.php?tab=events&action=add" class="btn btn-admin-primary btn-sm"><i class="bi bi-plus me-1"></i> Nouvel événement</a>
        </div>
        <?php if ($action === 'add' || ($action === 'edit' && $detail)): ?>
            <form method="POST" class="mb-4">
                <input type="hidden" name="save_event" value="1">
                <input type="hidden" name="event_id" value="<?= (int) ($detail->id ?? 0) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Calendrier *</label>
                        <select name="calendar_id" class="form-select" required>
                            <?php foreach ($calendars as $cal): ?>
                                <option value="<?= (int) $cal->id ?>" <?= ($detail && (int)$detail->calendar_id === (int)$cal->id) ? 'selected' : '' ?>><?= htmlspecialchars($cal->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Titre *</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($detail->title ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Début *</label>
                        <input type="datetime-local" name="start_at" class="form-control" value="<?= $detail ? date('Y-m-d\TH:i', strtotime($detail->start_at)) : '' ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Fin *</label>
                        <input type="datetime-local" name="end_at" class="form-control" value="<?= $detail ? date('Y-m-d\TH:i', strtotime($detail->end_at)) : '' ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($detail->description ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Lieu</label>
                        <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($detail->location ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="is_all_day" class="form-check-input" value="1" <?= ($detail && $detail->is_all_day) ? 'checked' : '' ?>>
                            <label class="form-check-label">Journée entière</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Règle de récurrence</label>
                        <input type="text" name="recurrence_rule" class="form-control" value="<?= htmlspecialchars($detail->recurrence_rule ?? '') ?>" placeholder="Ex: FREQ=WEEKLY;BYDAY=MO,WE">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-admin-primary">Enregistrer</button>
                        <a href="planning.php?tab=events" class="btn btn-secondary">Annuler</a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="eventsTable">
                <thead><tr><th>Titre</th><th>Calendrier</th><th>Début</th><th>Fin</th><th>Journée entière</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($list as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e->title) ?></td>
                            <td><?= htmlspecialchars($e->calendar_name) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($e->start_at)) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($e->end_at)) ?></td>
                            <td><?= $e->is_all_day ? 'Oui' : 'Non' ?></td>
                            <td class="text-end">
                                <a href="planning.php?tab=events&action=edit&id=<?= (int) $e->id ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet événement ?');">
                                    <input type="hidden" name="delete_event" value="<?= (int) $e->id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun événement.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($tab === 'schedules'): ?>
    <div class="admin-card admin-section-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted"><?= count($list) ?> planning(s)</span>
            <a href="planning.php?tab=schedules&action=add" class="btn btn-admin-primary btn-sm"><i class="bi bi-plus me-1"></i> Nouveau planning</a>
        </div>
        <?php if ($action === 'add' || ($action === 'edit' && $detail)): ?>
            <form method="POST" class="mb-4">
                <input type="hidden" name="save_schedule" value="1">
                <input type="hidden" name="schedule_id" value="<?= (int) ($detail->id ?? 0) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nom *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($detail->name ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Organisation</label>
                        <select name="organisation_id" class="form-select">
                            <?php foreach ($organisations as $o): ?>
                                <option value="<?= (int) $o->id ?>" <?= ($detail && (int)$detail->organisation_id === (int)$o->id) ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Type</label>
                        <input type="text" name="schedule_type" class="form-control" value="<?= htmlspecialchars($detail->schedule_type ?? '') ?>" placeholder="Ex: Hebdomadaire">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Début</label>
                        <input type="date" name="start_date" class="form-control" value="<?= $detail && $detail->start_date ? $detail->start_date : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Fin</label>
                        <input type="date" name="end_date" class="form-control" value="<?= $detail && $detail->end_date ? $detail->end_date : '' ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($detail->description ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-admin-primary">Enregistrer</button>
                        <a href="planning.php?tab=schedules" class="btn btn-secondary">Annuler</a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="schedulesTable">
                <thead><tr><th>Nom</th><th>Organisation</th><th>Type</th><th>Début</th><th>Fin</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($list as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s->name) ?></td>
                            <td><?= htmlspecialchars($s->org_name ?? '—') ?></td>
                            <td><?= htmlspecialchars($s->schedule_type ?? '—') ?></td>
                            <td><?= $s->start_date ? date('d/m/Y', strtotime($s->start_date)) : '—' ?></td>
                            <td><?= $s->end_date ? date('d/m/Y', strtotime($s->end_date)) : '—' ?></td>
                            <td class="text-end">
                                <a href="planning.php?tab=schedules&action=edit&id=<?= (int) $s->id ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce planning ?');">
                                    <input type="hidden" name="delete_schedule" value="<?= (int) $s->id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun planning.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($tab === 'kpi'): ?>
    <div class="admin-card admin-section-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted"><?= count($list) ?> indicateur(s)</span>
            <a href="planning.php?tab=kpi&action=add" class="btn btn-admin-primary btn-sm"><i class="bi bi-plus me-1"></i> Nouvel indicateur</a>
        </div>
        <?php if ($action === 'add' || ($action === 'edit' && $detail)): ?>
            <form method="POST" class="mb-4">
                <input type="hidden" name="save_kpi" value="1">
                <input type="hidden" name="kpi_id" value="<?= (int) ($detail->id ?? 0) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nom *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($detail->name ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Code</label>
                        <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($detail->code ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Organisation</label>
                        <select name="organisation_id" class="form-select">
                            <?php foreach ($organisations as $o): ?>
                                <option value="<?= (int) $o->id ?>" <?= ($detail && (int)$detail->organisation_id === (int)$o->id) ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Type</label>
                        <select name="indicator_type" class="form-select">
                            <?php foreach (['numeric' => 'Numérique', 'percentage' => 'Pourcentage', 'boolean' => 'Oui/Non', 'rating' => 'Note'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($detail && $detail->indicator_type === $v) ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Unité</label>
                        <input type="text" name="unit" class="form-control" value="<?= htmlspecialchars($detail->unit ?? '') ?>" placeholder="Ex: %">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Valeur cible</label>
                        <input type="number" step="any" name="target_value" class="form-control" value="<?= $detail && $detail->target_value !== null ? (float)$detail->target_value : '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Valeur actuelle</label>
                        <input type="number" step="any" name="current_value" class="form-control" value="<?= $detail && $detail->current_value !== null ? (float)$detail->current_value : '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Période</label>
                        <select name="period_type" class="form-select">
                            <option value="">—</option>
                            <?php foreach (['daily' => 'Quotidien', 'weekly' => 'Hebdomadaire', 'monthly' => 'Mensuel', 'quarterly' => 'Trimestriel', 'yearly' => 'Annuel'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($detail && $detail->period_type === $v) ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Début période</label>
                        <input type="date" name="period_start" class="form-control" value="<?= $detail && $detail->period_start ? $detail->period_start : '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Fin période</label>
                        <input type="date" name="period_end" class="form-control" value="<?= $detail && $detail->period_end ? $detail->period_end : '' ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($detail->description ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-admin-primary">Enregistrer</button>
                        <a href="planning.php?tab=kpi" class="btn btn-secondary">Annuler</a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="admin-table table table-hover" id="kpiTable">
                <thead><tr><th>Nom</th><th>Code</th><th>Type</th><th>Valeur / Cible</th><th>Période</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($list as $k): ?>
                        <tr>
                            <td><?= htmlspecialchars($k->name) ?></td>
                            <td><?= htmlspecialchars($k->code ?? '—') ?></td>
                            <td><?= htmlspecialchars($k->indicator_type ?? '—') ?></td>
                            <td><?= $k->current_value !== null ? $k->current_value : '—' ?> <?= $k->target_value !== null ? ' / ' . $k->target_value : '' ?> <?= $k->unit ? $k->unit : '' ?></td>
                            <td><?= htmlspecialchars($k->period_type ?? '—') ?></td>
                            <td class="text-end">
                                <a href="planning.php?tab=kpi&action=edit&id=<?= (int) $k->id ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet indicateur ?');">
                                    <input type="hidden" name="delete_kpi" value="<?= (int) $k->id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($list)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun indicateur.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Tableau de bord</a>
        <a href="documents.php" class="text-muted text-decoration-none small"><i class="bi bi-file-earmark-text me-1"></i> Documents</a>
        <span class="small text-muted">&copy; <?= date('Y') ?> Expertise</span>
    </div>
</footer>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ === 'undefined' || !$.fn.DataTable) return;
    var opts = {
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json' },
        pageLength: 10,
        dom: '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>'
    };
    if ($('#calendarsTable').length) $('#calendarsTable').DataTable(Object.assign({}, opts, { order: [[0, 'asc']] }));
    if ($('#eventsTable').length) $('#eventsTable').DataTable(Object.assign({}, opts, { order: [[2, 'desc']] }));
    if ($('#schedulesTable').length) $('#schedulesTable').DataTable(Object.assign({}, opts, { order: [[3, 'desc']] }));
    if ($('#kpiTable').length) $('#kpiTable').DataTable(Object.assign({}, opts, { order: [[0, 'asc']] }));
});
</script>
<?php require __DIR__ . '/inc/footer.php'; ?>

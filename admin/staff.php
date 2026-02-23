<?php
$pageTitle = 'Personnel – Administration';
$currentNav = 'staff';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$staffList = [];
$detail = null;
$employmentTypes = [
    'full_time' => 'Temps plein',
    'part_time' => 'Temps partiel',
    'contract' => 'Contrat',
    'intern' => 'Stagiaire',
    'freelance' => 'Freelance',
];

if ($pdo) {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
    if ($userId > 0) {
        $stmt = $pdo->prepare("SELECT id FROM staff WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if ($row) {
            header('Location: staff.php?id=' . (int) $row->id);
            exit;
        }
    }
    if ($id > 0) {
        $stmt = $pdo->prepare("
            SELECT s.id, s.user_id, s.organisation_id, s.employee_number, s.hire_date, s.employment_type, s.is_active, s.created_at,
                   u.first_name, u.last_name, u.email, u.phone, u.is_active AS user_active, u.last_login_at,
                   o.name AS organisation_name
            FROM staff s
            JOIN user u ON s.user_id = u.id
            LEFT JOIN organisation o ON s.organisation_id = o.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();
        if ($detail) {
            $stmtAssign = $pdo->prepare("
                SELECT a.id, a.start_date, a.end_date, a.is_primary, p.title AS position_title, u.name AS unit_name
                FROM assignment a
                LEFT JOIN position p ON a.position_id = p.id
                LEFT JOIN unit u ON a.unit_id = u.id
                WHERE a.staff_id = ?
                ORDER BY a.is_primary DESC, a.start_date DESC
            ");
            $stmtAssign->execute([$id]);
            $detail->assignments = $stmtAssign->fetchAll();
            $stmtRoles = $pdo->prepare("
                SELECT r.id, r.name, r.code FROM role r
                JOIN user_role ur ON r.id = ur.role_id WHERE ur.user_id = ?
            ");
            $stmtRoles->execute([$detail->user_id]);
            $detail->roles = $stmtRoles->fetchAll();
        }
    }
    if (!$detail) {
        $stmt = $pdo->query("
            SELECT s.id, s.employee_number, s.hire_date, s.employment_type, s.is_active,
                   u.first_name, u.last_name, u.email,
                   o.name AS organisation_name,
                   (SELECT p.title FROM assignment a JOIN position p ON a.position_id = p.id WHERE a.staff_id = s.id AND a.is_primary = 1 LIMIT 1) AS primary_position
            FROM staff s
            JOIN user u ON s.user_id = u.id
            LEFT JOIN organisation o ON s.organisation_id = o.id
            ORDER BY u.last_name ASC, u.first_name ASC
        ");
        if ($stmt) {
            $staffList = $stmt->fetchAll();
        }
    }
}
require __DIR__ . '/inc/header.php';
?>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1><?= $detail ? 'Fiche personnel' : 'Personnel' ?></h1>
            <p><?= $detail ? htmlspecialchars($detail->first_name . ' ' . $detail->last_name) : 'Liste du personnel (RH).' ?></p>
        </div>
        <?php if ($detail): ?>
            <a href="staff.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Retour à la liste</a>
        <?php else: ?>
            <a href="register.php" class="btn btn-admin-primary"><i class="bi bi-person-plus me-1"></i> Nouvel utilisateur</a>
        <?php endif; ?>
    </div>
</header>

<?php if ($detail): ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-person-vcard"></i> Identité & compte</h5>
        <table class="admin-table mb-4">
            <tr>
                <th style="width:180px;">Nom / Prénom</th>
                <td><?= htmlspecialchars($detail->last_name . ' ' . $detail->first_name) ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?= htmlspecialchars($detail->email) ?></td>
            </tr>
            <tr>
                <th>Téléphone</th>
                <td><?= htmlspecialchars($detail->phone ?? '—') ?></td>
            </tr>
            <tr>
                <th>Organisation</th>
                <td><?= htmlspecialchars($detail->organisation_name ?? '—') ?></td>
            </tr>
            <tr>
                <th>Dernière connexion</th>
                <td><?= $detail->last_login_at ? date('d/m/Y H:i', strtotime($detail->last_login_at)) : '—' ?></td>
            </tr>
        </table>

        <h5 class="card-title"><i class="bi bi-briefcase"></i> RH</h5>
        <table class="admin-table mb-4">
            <tr>
                <th style="width:180px;">N° employé</th>
                <td><?= htmlspecialchars($detail->employee_number ?? '—') ?></td>
            </tr>
            <tr>
                <th>Type de contrat</th>
                <td><?= $employmentTypes[$detail->employment_type] ?? $detail->employment_type ?></td>
            </tr>
            <tr>
                <th>Date d'embauche</th>
                <td><?= $detail->hire_date ? date('d/m/Y', strtotime($detail->hire_date)) : '—' ?></td>
            </tr>
            <tr>
                <th>Statut</th>
                <td>
                    <?php if ($detail->is_active): ?><span class="badge bg-success">Actif</span><?php else: ?><span class="badge bg-secondary">Inactif</span><?php endif; ?>
                </td>
            </tr>
        </table>

        <?php if (!empty($detail->roles)): ?>
            <h5 class="card-title"><i class="bi bi-shield-lock"></i> Rôles</h5>
            <p class="mb-2">
                <?php foreach ($detail->roles as $r): ?>
                    <span class="badge bg-primary me-1"><?= htmlspecialchars($r->name) ?></span>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($detail->assignments)): ?>
            <h5 class="card-title mt-3"><i class="bi bi-diagram-3"></i> Affectations / Postes</h5>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Poste</th>
                        <th>Unité</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Principal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detail->assignments as $a): ?>
                        <tr>
                            <td><?= htmlspecialchars($a->position_title ?? '—') ?></td>
                            <td><?= htmlspecialchars($a->unit_name ?? '—') ?></td>
                            <td><?= $a->start_date ? date('d/m/Y', strtotime($a->start_date)) : '—' ?></td>
                            <td><?= $a->end_date ? date('d/m/Y', strtotime($a->end_date)) : '—' ?></td>
                            <td><?= $a->is_primary ? 'Oui' : 'Non' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="mt-3">
            <a href="staff.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Retour à la liste</a>
        </div>
    </div>
<?php elseif (count($staffList) > 0): ?>
    <div class="admin-card admin-section-card">
        <table class="admin-table table-hover">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>N° employé</th>
                    <th>Type</th>
                    <th>Poste principal</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staffList as $s): ?>
                    <tr>
                        <td><a href="staff.php?id=<?= (int) $s->id ?>"><?= htmlspecialchars($s->last_name . ' ' . $s->first_name) ?></a></td>
                        <td class="text-muted"><?= htmlspecialchars($s->email) ?></td>
                        <td><?= htmlspecialchars($s->employee_number ?? '—') ?></td>
                        <td><?= $employmentTypes[$s->employment_type] ?? $s->employment_type ?></td>
                        <td><?= htmlspecialchars($s->primary_position ?? '—') ?></td>
                        <td><?= $s->is_active ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?></td>
                        <td><a href="staff.php?id=<?= (int) $s->id ?>" class="btn btn-sm btn-light border"><i class="bi bi-eye"></i></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <div class="admin-empty">
            <i class="bi bi-people d-block"></i>
            Aucun personnel enregistré.
            <p class="mt-2 mb-0"><a href="register.php" class="btn btn-admin-primary btn-sm">Créer un utilisateur</a></p>
        </div>
    </div>
<?php endif; ?>

<footer class="admin-main-footer">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="index.php" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Tableau de bord</a>
        <span><?= date('Y') ?></span>
    </div>
</footer>

<?php require __DIR__ . '/inc/footer.php'; ?>

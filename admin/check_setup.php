<?php
/**
 * Vérification de la configuration Admin (CRUD) – Base de données et RBAC.
 * Aide au diagnostic quand le CRUD ne fonctionne pas (session, permissions, schéma).
 */
$pageTitle = 'Vérification configuration – Administration';
$currentNav = 'dashboard';
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.dashboard');
require __DIR__ . '/inc/db.php';

$checks = [];
$ok = true;

// 1. Connexion DB
if (!$pdo) {
    $checks[] = ['label' => 'Connexion base de données', 'status' => 'error', 'message' => 'Impossible de se connecter (vérifier config/database.php et .env).'];
    $ok = false;
} else {
    $checks[] = ['label' => 'Connexion base de données', 'status' => 'ok', 'message' => 'Connexion PDO active.'];

    $requiredTables = ['organisation', 'organisation_organisation_type', 'user', 'role', 'user_role', 'permission', 'role_permission'];
    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
            if ($stmt->rowCount() === 0) {
                $checks[] = ['label' => "Table « $table »", 'status' => 'error', 'message' => 'Table absente. Exécutez database/schema.sql.'];
                $ok = false;
            } else {
                $checks[] = ['label' => "Table « $table »", 'status' => 'ok', 'message' => 'Présente.'];
            }
        } catch (PDOException $e) {
            $checks[] = ['label' => "Table « $table »", 'status' => 'error', 'message' => $e->getMessage()];
            $ok = false;
        }
    }

    // Permissions pour rôles système (1 = SuperAdmin, 2 = Administrateur)
    try {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM role_permission WHERE role_id IN (1, 2)")->fetchColumn();
        if ($count === 0) {
            $checks[] = ['label' => 'Permissions RBAC (rôles 1 et 2)', 'status' => 'error', 'message' => 'Aucune permission liée aux rôles admin. Exécutez les INSERT role_permission du schema.sql.'];
            $ok = false;
        } else {
            $checks[] = ['label' => 'Permissions RBAC (rôles 1 et 2)', 'status' => 'ok', 'message' => "$count liaison(s) role_permission."];
        }
    } catch (PDOException $e) {
        $checks[] = ['label' => 'Permissions RBAC', 'status' => 'error', 'message' => $e->getMessage()];
        $ok = false;
    }

    // Au moins un utilisateur avec rôle admin ou superadmin
    try {
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT ur.user_id) AS n
            FROM user_role ur
            JOIN role r ON r.id = ur.role_id AND r.is_system = 1 AND r.code IN ('admin', 'superadmin')
        ");
        $adminCount = (int) $stmt->fetchColumn();
        if ($adminCount === 0) {
            $checks[] = ['label' => 'Utilisateurs admin', 'status' => 'error', 'message' => 'Aucun utilisateur avec rôle admin/superadmin. Ajoutez une entrée dans user_role (role_id 1 ou 2) pour un user actif.'];
            $ok = false;
        } else {
            $checks[] = ['label' => 'Utilisateurs admin', 'status' => 'ok', 'message' => "$adminCount utilisateur(s) avec rôle admin ou superadmin."];
        }
    } catch (PDOException $e) {
        $checks[] = ['label' => 'Utilisateurs admin', 'status' => 'error', 'message' => $e->getMessage()];
        $ok = false;
    }

    // Session actuelle
    $checks[] = [
        'label' => 'Session actuelle',
        'status' => !empty($_SESSION['admin_logged_in']) ? 'ok' : 'error',
        'message' => !empty($_SESSION['admin_logged_in'])
            ? 'Connecté (id=' . ($_SESSION['admin_id'] ?? '?') . ', rôle=' . ($_SESSION['admin_role'] ?? '?') . ', permissions=' . count($_SESSION['admin_permissions'] ?? []) . ')'
            : 'Non connecté.',
    ];
    if (empty($_SESSION['admin_logged_in'])) {
        $ok = false;
    }
}

require __DIR__ . '/inc/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item active">Vérification configuration</li>
    </ol>
</nav>

<header class="admin-header">
    <h1>Vérification configuration Admin</h1>
    <p class="text-muted mb-0">Diagnostic rapide pour le CRUD (base de données, RBAC, session).</p>
</header>

<div class="admin-card admin-section-card">
    <table class="admin-table table">
        <thead>
            <tr>
                <th>Vérification</th>
                <th>Statut</th>
                <th>Détail</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($checks as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['label']) ?></td>
                    <td>
                        <?php if ($c['status'] === 'ok'): ?>
                            <span class="badge bg-success">OK</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Erreur</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted"><?= htmlspecialchars($c['message']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($ok): ?>
        <div class="alert alert-success mb-0 mt-3">Tous les contrôles sont passés. Si le CRUD ne fonctionne toujours pas, vérifiez les erreurs PHP (display_errors / log) et la console navigateur.</div>
    <?php else: ?>
        <div class="alert alert-warning mb-0 mt-3">Corrigez les points en erreur ci-dessus (schéma SQL, user_role pour un compte admin, configuration .env).</div>
    <?php endif; ?>
</div>

<p class="mt-3"><a href="index.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Retour au tableau de bord</a></p>

<?php require __DIR__ . '/inc/footer.php'; ?>

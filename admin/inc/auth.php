<?php
/**
 * Gardien d'authentification admin (RBAC).
 * À inclure EN PREMIER dans chaque page protégée.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_logged_in'])) {
    // Mémoriser l'URL demandée pour rediriger après connexion
    $redirect = $_SERVER['REQUEST_URI'] ?? '';
    header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/login.php'
        . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
    exit;
}

/**
 * Retourne la liste des codes de permission de l'utilisateur (via ses rôles, RBAC).
 * Lit depuis la session si présente, sinon charge depuis la DB et met en cache.
 * @return string[]
 */
function get_admin_permissions() {
    if (!empty($_SESSION['admin_permissions']) && is_array($_SESSION['admin_permissions'])) {
        return $_SESSION['admin_permissions'];
    }
    $userId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : 0;
    if ($userId <= 0) {
        return [];
    }
    require_once __DIR__ . '/db.php';
    $perms = [];
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo']) {
        $stmt = $GLOBALS['pdo']->prepare("
            SELECT DISTINCT p.code
            FROM permission p
            JOIN role_permission rp ON p.id = rp.permission_id
            JOIN user_role ur ON ur.role_id = rp.role_id
            WHERE ur.user_id = ?
        ");
        $stmt->execute([$userId]);
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $perms[] = $row->code;
        }
        $_SESSION['admin_permissions'] = $perms;
    }
    return $perms;
}

/**
 * Indique si l'utilisateur a la permission donnée (via ses rôles ou rôle superadmin).
 * @param string $code Code de la permission (ex. admin.organisations.view)
 * @return bool
 */
function has_permission($code) {
    if (!empty($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin') {
        return true;
    }
    return in_array($code, get_admin_permissions(), true);
}

/**
 * Exige la permission ; redirige vers 403 en cas d'absence.
 * @param string $code Code de la permission
 */
function require_permission($code) {
    if (has_permission($code)) {
        return;
    }
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . $base . '/403.php');
    exit;
}

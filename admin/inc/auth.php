<?php
/**
 * Gardien d'authentification admin.
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

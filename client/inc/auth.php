<?php
/**
 * Gardien d'authentification client.
 * À inclure en premier dans chaque page protégée de l'espace client.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['client_logged_in'])) {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $loginUrl = $base . '/login.php';
    $redirect = $_SERVER['REQUEST_URI'] ?? '';
    header('Location: ' . $loginUrl . ($redirect ? '?redirect=' . urlencode($redirect) : ''));
    exit;
}

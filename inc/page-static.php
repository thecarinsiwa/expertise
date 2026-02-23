<?php
/**
 * Démarre une page client à contenu statique (navbar).
 * À appeler après avoir défini $pageTitle et optionnellement $pageHeading.
 * Charge org, head, header. La page doit ensuite afficher le contenu puis require footer.
 */
if (!isset($organisation)) {
    $organisation = null;
    if (isset($pdo) && $pdo) {
        $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
        if ($row = $stmt->fetch()) $organisation = $row;
    }
}
if (!isset($baseUrl)) $baseUrl = '';
require __DIR__ . '/head.php';
require __DIR__ . '/header.php';

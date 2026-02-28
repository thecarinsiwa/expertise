<?php
/**
 * Tableau de bord – Espace client Expertise RDC (même structure que le site principal)
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';

$pageTitle = 'Mon espace – Expertise RDC';
$clientName = !empty($_SESSION['client_name']) && trim($_SESSION['client_name']) !== ''
    ? trim($_SESSION['client_name'])
    : ($_SESSION['client_email'] ?? '');

// Base URL vers la racine du site (depuis client/ → ../)
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : '../';

// Organisation pour le header/footer (comme le site principal)
$organisation = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
    }
}

require __DIR__ . '/../inc/head.php';
require __DIR__ . '/../inc/header.php';
?>

    <section class="py-5">
        <div class="container">
            <h1 class="section-heading mb-4"><i class="bi bi-person-circle me-2"></i>Mon espace</h1>
            <p class="lead mb-2">Bienvenue, <?= htmlspecialchars($clientName) ?>.</p>
            <p class="text-muted mb-4">Vous êtes connecté à votre compte client. Utilisez les liens ci-dessous pour naviguer.</p>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="btn btn-read-more">
                    <i class="bi bi-house me-1"></i> Accueil
                </a>
                <a href="<?= htmlspecialchars($baseUrl) ?>missions.php" class="btn btn-outline-secondary">
                    <i class="bi bi-geo-alt me-1"></i> Nos missions
                </a>
                <a href="<?= htmlspecialchars($baseUrl) ?>news.php" class="btn btn-outline-secondary">
                    <i class="bi bi-newspaper me-1"></i> Actualités
                </a>
                <a href="<?= htmlspecialchars($baseUrl) ?>contact.php" class="btn btn-outline-secondary">
                    <i class="bi bi-envelope me-1"></i> Nous contacter
                </a>
                <a href="<?= htmlspecialchars($baseUrl) ?>client/logout.php" class="btn btn-outline-danger ms-auto">
                    <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
                </a>
            </div>
        </div>
    </section>

<?php require __DIR__ . '/../inc/footer.php'; ?>

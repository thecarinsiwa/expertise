<?php
/**
 * Accès refusé (403) – RBAC
 */
require_once __DIR__ . '/inc/auth.php';
$pageTitle = 'Accès refusé – Administration';
require_once __DIR__ . '/inc/header.php';
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item active">Accès refusé</li>
    </ol>
</nav>
<div class="admin-card admin-section-card text-center py-5">
    <i class="bi bi-shield-x text-danger" style="font-size: 4rem;"></i>
    <h1 class="h3 mt-3">Accès refusé</h1>
    <p class="text-muted mb-4">Vous n'avez pas la permission d'accéder à cette page.</p>
    <a href="index.php" class="btn btn-admin-primary"><i class="bi bi-speedometer2 me-1"></i> Retour au tableau de bord</a>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>

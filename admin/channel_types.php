<?php
/**
 * Types de canaux (référentiel) – aligné sur la logique mission_types
 * Les types sont définis dans le schéma (ENUM). Cette page les affiche et documente.
 */
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$pageTitle = 'Types de canal – Administration';
$currentNav = 'channels';

$types = [
    ['code' => 'public',   'name' => 'Public',   'description' => 'Visible par tous les utilisateurs de l\'organisation.'],
    ['code' => 'private',  'name' => 'Privé',    'description' => 'Accès sur invitation ou attribution explicite (membres du canal).'],
    ['code' => 'direct',   'name' => 'Direct',   'description' => 'Conversation entre deux personnes (messagerie directe).'],
    ['code' => 'announcement', 'name' => 'Annonce', 'description' => 'Canal dédié aux annonces (lecture large, écriture restreinte).'],
];

require __DIR__ . '/inc/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="announcements.php" class="text-decoration-none">Communication</a></li>
        <li class="breadcrumb-item"><a href="channels.php" class="text-decoration-none">Canaux</a></li>
        <li class="breadcrumb-item active">Types de canal</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 fw-bold">Types de canal</h1>
        <p class="text-muted small">Configurez et consultez les types de canaux disponibles pour la communication.</p>
    </div>
    <a href="channels.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Retour aux canaux</a>
</div>

<div class="admin-card p-0 overflow-hidden">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="ps-4 py-3" style="width: 120px;">Code</th>
                <th class="py-3" style="width: 140px;">Libellé</th>
                <th class="pe-4 py-3">Description</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($types as $t): ?>
                <tr>
                    <td class="ps-4">
                        <span class="badge bg-secondary"><?= htmlspecialchars($t['code']) ?></span>
                    </td>
                    <td class="fw-medium"><?= htmlspecialchars($t['name']) ?></td>
                    <td class="pe-4 text-muted small"><?= htmlspecialchars($t['description']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="mt-4">
    <a href="channels.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Retour aux canaux</a>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>

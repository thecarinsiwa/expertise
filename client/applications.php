<?php
/**
 * Mes candidatures – Espace client
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';

$pageTitle = 'Mes candidatures – Mon espace';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : '../';

$applications = [];
$clientId = (int) ($_SESSION['client_id'] ?? 0);

$statusLabels = [
    'pending' => 'En attente',
    'reviewed' => 'Examinée',
    'accepted' => 'Acceptée',
    'rejected' => 'Refusée',
];
$statusClass = [
    'pending' => 'warning',
    'reviewed' => 'info',
    'accepted' => 'success',
    'rejected' => 'danger',
];

if ($clientId > 0 && $pdo) {
    $stmt = $pdo->prepare("
        SELECT a.id, a.offer_id, a.message, a.cv_path, a.status, a.created_at,
               o.title AS offer_title, o.reference AS offer_reference
        FROM offer_application a
        JOIN offer o ON o.id = a.offer_id
        WHERE a.user_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$clientId]);
    $applications = $stmt->fetchAll(PDO::FETCH_OBJ);
}

$organisation = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
}

require __DIR__ . '/../inc/head.php';
require __DIR__ . '/../inc/header.php';
?>

    <section class="client-dashboard py-5">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="client-breadcrumb mb-4">
                <a href="<?= htmlspecialchars($baseUrl) ?>client/"><i class="bi bi-arrow-left me-1"></i> Mon espace</a>
            </nav>

            <h1 class="client-page-title"><i class="bi bi-file-earmark-text me-2"></i>Mes candidatures</h1>
            <p class="client-page-desc">Liste des offres auxquelles vous avez postulé.</p>

            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] === 'submitted'): ?>
                    <div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle-fill me-2"></i> Votre candidature a bien été envoyée.</div>
                <?php elseif ($_GET['msg'] === 'updated'): ?>
                    <div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle-fill me-2"></i> Votre candidature a été mise à jour.</div>
                <?php elseif ($_GET['msg'] === 'already_applied'): ?>
                    <div class="alert alert-info d-flex align-items-center"><i class="bi bi-info-circle-fill me-2"></i> Vous avez déjà postulé à cette offre.</div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (count($applications) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($applications as $app):
                        $statusCl = $statusClass[$app->status] ?? 'secondary';
                    ?>
                    <div class="col-12">
                        <div class="client-application-card">
                            <div class="row align-items-center">
                                <div class="col-lg-8">
                                    <h3 class="client-application-title">
                                        <a href="<?= htmlspecialchars($baseUrl) ?>offre.php?id=<?= (int) $app->offer_id ?>"><?= htmlspecialchars($app->offer_title) ?></a>
                                    </h3>
                                    <?php if (!empty($app->offer_reference)): ?>
                                        <p class="client-application-ref mb-1">Réf. <?= htmlspecialchars($app->offer_reference) ?></p>
                                    <?php endif; ?>
                                    <p class="client-application-date mb-0">Candidature du <?= date('d/m/Y à H:i', strtotime($app->created_at)) ?></p>
                                    <?php if (!empty($app->cv_path)): ?>
                                        <p class="client-application-cv mt-2 mb-0">
                                            <a href="<?= htmlspecialchars($baseUrl . $app->cv_path) ?>" target="_blank" class="client-cv-link"><i class="bi bi-file-earmark-pdf me-1"></i>CV joint</a>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-lg-4 mt-3 mt-lg-0 text-lg-end">
                                    <span class="badge client-status-badge client-status-<?= $statusCl ?>"><?= $statusLabels[$app->status] ?? $app->status ?></span>
                                    <a href="<?= htmlspecialchars($baseUrl) ?>client/apply.php?offer_id=<?= (int) $app->offer_id ?>" class="btn btn-client-edit mt-2">Modifier</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="client-empty-state">
                    <span class="client-empty-icon"><i class="bi bi-file-earmark-plus"></i></span>
                    <h3 class="client-empty-title">Aucune candidature</h3>
                    <p class="client-empty-text">Vous n'avez pas encore postulé à une offre. Parcourez nos offres et déposez votre candidature.</p>
                    <a href="<?= htmlspecialchars($baseUrl) ?>offres.php" class="btn btn-read-more"><i class="bi bi-briefcase me-2"></i>Voir nos offres</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

<?php require __DIR__ . '/../inc/footer.php'; ?>

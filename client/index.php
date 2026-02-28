<?php
/**
 * Tableau de bord – Espace client Expertise
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';

$pageTitle = 'Mon espace – Expertise';
$clientName = !empty($_SESSION['client_name']) && trim($_SESSION['client_name']) !== ''
    ? trim($_SESSION['client_name'])
    : ($_SESSION['client_email'] ?? '');

$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : '../';

$organisation = null;
$applicationsCount = 0;
$recentApplications = [];

if ($pdo) {
    $stmt = $pdo->query("SELECT name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;

    $clientId = (int) ($_SESSION['client_id'] ?? 0);
    if ($clientId > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM offer_application WHERE user_id = ?");
        $stmt->execute([$clientId]);
        $applicationsCount = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT a.id, a.offer_id, a.status, a.created_at, o.title AS offer_title, o.reference AS offer_reference
            FROM offer_application a
            JOIN offer o ON o.id = a.offer_id
            WHERE a.user_id = ?
            ORDER BY a.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$clientId]);
        $recentApplications = $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}

require __DIR__ . '/../inc/head.php';
require __DIR__ . '/../inc/header.php';
?>

    <section class="client-dashboard py-5">
        <div class="container">
            <!-- Bandeau bienvenue -->
            <div class="client-welcome-card">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <p class="client-welcome-label">Espace client</p>
                        <h1 class="client-welcome-title">Bienvenue, <?= htmlspecialchars($clientName) ?></h1>
                        <p class="client-welcome-text">Accédez à nos offres, suivez vos candidatures et restez informé.</p>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <a href="<?= htmlspecialchars($baseUrl) ?>client/logout.php" class="btn btn-client-logout">
                            <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                        </a>
                    </div>
                </div>
            </div>

            <!-- Indicateurs rapides -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="client-stat-card">
                        <span class="client-stat-number"><?= $applicationsCount ?></span>
                        <span class="client-stat-label">candidature<?= $applicationsCount !== 1 ? 's' : '' ?></span>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <h2 class="client-section-title">Accès rapides</h2>
            <div class="row g-4 mb-5">
                <div class="col-sm-6 col-lg-4">
                    <a href="<?= htmlspecialchars($baseUrl) ?>offres.php" class="client-action-card">
                        <span class="client-action-icon"><i class="bi bi-briefcase"></i></span>
                        <h3 class="client-action-title">Nos offres</h3>
                        <p class="client-action-desc">Consulter les offres et postuler</p>
                        <span class="client-action-link">Voir les offres <i class="bi bi-arrow-right"></i></span>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <a href="applications.php" class="client-action-card">
                        <span class="client-action-icon"><i class="bi bi-file-earmark-text"></i></span>
                        <h3 class="client-action-title">Mes candidatures</h3>
                        <p class="client-action-desc">Suivre vos candidatures envoyées</p>
                        <span class="client-action-link">Voir mes candidatures <i class="bi bi-arrow-right"></i></span>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <a href="profile.php" class="client-action-card">
                        <span class="client-action-icon"><i class="bi bi-person-badge"></i></span>
                        <h3 class="client-action-title">Mon profil / Mon CV</h3>
                        <p class="client-action-desc">Compléter et réutiliser votre CV pour les offres</p>
                        <span class="client-action-link">Mon profil <i class="bi bi-arrow-right"></i></span>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="client-action-card">
                        <span class="client-action-icon"><i class="bi bi-house"></i></span>
                        <h3 class="client-action-title">Site principal</h3>
                        <p class="client-action-desc">Retour à l'accueil du site</p>
                        <span class="client-action-link">Accueil <i class="bi bi-arrow-right"></i></span>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <a href="<?= htmlspecialchars($baseUrl) ?>missions.php" class="client-action-card">
                        <span class="client-action-icon"><i class="bi bi-geo-alt"></i></span>
                        <h3 class="client-action-title">Nos missions</h3>
                        <p class="client-action-desc">Découvrir nos missions</p>
                        <span class="client-action-link">Voir les missions <i class="bi bi-arrow-right"></i></span>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <a href="<?= htmlspecialchars($baseUrl) ?>news.php" class="client-action-card">
                        <span class="client-action-icon"><i class="bi bi-newspaper"></i></span>
                        <h3 class="client-action-title">Actualités</h3>
                        <p class="client-action-desc">Lire les dernières actualités</p>
                        <span class="client-action-link">Voir les actualités <i class="bi bi-arrow-right"></i></span>
                    </a>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <a href="<?= htmlspecialchars($baseUrl) ?>contact.php" class="client-action-card">
                        <span class="client-action-icon"><i class="bi bi-envelope"></i></span>
                        <h3 class="client-action-title">Nous contacter</h3>
                        <p class="client-action-desc">Une question ? Écrivez-nous</p>
                        <span class="client-action-link">Contact <i class="bi bi-arrow-right"></i></span>
                    </a>
                </div>
            </div>

            <!-- Dernières candidatures -->
            <?php if (count($recentApplications) > 0): ?>
            <h2 class="client-section-title">Vos dernières candidatures</h2>
            <div class="client-recent-list">
                <?php
                $statusLabels = ['pending' => 'En attente', 'reviewed' => 'Examinée', 'accepted' => 'Acceptée', 'rejected' => 'Refusée'];
                $statusClass = ['pending' => 'warning', 'reviewed' => 'info', 'accepted' => 'success', 'rejected' => 'danger'];
                foreach ($recentApplications as $app):
                    $statusCl = $statusClass[$app->status] ?? 'secondary';
                ?>
                <div class="client-recent-item">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <a href="<?= htmlspecialchars($baseUrl) ?>offre.php?id=<?= (int) $app->offer_id ?>" class="client-recent-title"><?= htmlspecialchars($app->offer_title) ?></a>
                            <?php if (!empty($app->offer_reference)): ?><span class="client-recent-ref">Réf. <?= htmlspecialchars($app->offer_reference) ?></span><?php endif; ?>
                            <p class="client-recent-date mb-0"><?= date('d/m/Y à H:i', strtotime($app->created_at)) ?></p>
                        </div>
                        <span class="badge bg-<?= $statusCl ?>"><?= $statusLabels[$app->status] ?? $app->status ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="mt-3 mb-0">
                <a href="applications.php" class="btn btn-read-more btn-sm"><i class="bi bi-list-ul me-1"></i>Toutes mes candidatures</a>
            </p>
            <?php endif; ?>
        </div>
    </section>

<?php require __DIR__ . '/../inc/footer.php'; ?>

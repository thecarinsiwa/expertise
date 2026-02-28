<?php
/**
 * Profil candidat – Vue admin (lecture seule)
 * Accessible depuis la fiche d'une offre pour un candidat ayant au moins une candidature.
 */
$pageTitle = 'Profil candidat – Administration';
$currentNav = 'offers';
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.offers.view');
require __DIR__ . '/inc/db.php';

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$user = null;
$profile = null;
$applications = [];
$error = '';

if ($userId <= 0) {
    $error = 'Candidat non spécifié.';
} elseif ($pdo) {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, created_at FROM user WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);
    if (!$user) {
        $error = 'Utilisateur introuvable.';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM offer_application WHERE user_id = ?");
        $stmt->execute([$userId]);
        $hasApplication = (int) $stmt->fetchColumn() > 0;
        if (!$hasApplication) {
            $error = 'Cet utilisateur n\'a aucune candidature.';
            $user = null;
        }
    }

    if ($user) {
        $stmt = $pdo->prepare("SELECT id, user_id, bio, job_title, skills, cv_path FROM profile WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_OBJ);
        if ($profile) {
            try {
                $ext = $pdo->prepare("SELECT experience, education FROM profile WHERE user_id = ?");
                $ext->execute([$userId]);
                $row = $ext->fetch(PDO::FETCH_OBJ);
                if ($row) {
                    $profile->experience = $row->experience ?? null;
                    $profile->education = $row->education ?? null;
                }
            } catch (PDOException $e) {
                $profile->experience = null;
                $profile->education = null;
            }
        }

        $stmt = $pdo->prepare("
            SELECT a.id, a.offer_id, a.status, a.created_at, o.title AS offer_title, o.reference AS offer_reference
            FROM offer_application a
            JOIN offer o ON o.id = a.offer_id
            WHERE a.user_id = ?
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$userId]);
        $applications = $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}

$applicationStatusLabels = [
    'pending' => 'En attente',
    'reviewed' => 'Examinée',
    'accepted' => 'Acceptée',
    'rejected' => 'Refusée',
];

require __DIR__ . '/inc/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item"><a href="offers.php" class="text-decoration-none">Nos offres</a></li>
        <li class="breadcrumb-item active">Profil candidat</li>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1><i class="bi bi-person-badge me-2"></i>Profil candidat</h1>
            <p class="text-muted mb-0">Vue en lecture seule du profil et CV du candidat.</p>
        </div>
        <a href="offers.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Retour aux offres</a>
    </div>
</header>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php elseif ($user): ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-person"></i> Identité</h5>
        <table class="admin-table mb-0">
            <tr><th style="width:180px;">Nom</th><td><?= htmlspecialchars(trim($user->first_name . ' ' . $user->last_name)) ?></td></tr>
            <tr><th>Prénom</th><td><?= htmlspecialchars($user->first_name) ?></td></tr>
            <tr><th>Nom</th><td><?= htmlspecialchars($user->last_name) ?></td></tr>
            <tr><th>E-mail</th><td><a href="mailto:<?= htmlspecialchars($user->email) ?>"><?= htmlspecialchars($user->email) ?></a></td></tr>
            <tr><th>Téléphone</th><td><?= $user->phone ? htmlspecialchars($user->phone) : '—' ?></td></tr>
            <tr><th>Compte créé le</th><td><?= date('d/m/Y à H:i', strtotime($user->created_at)) ?></td></tr>
        </table>
    </div>

    <?php if ($profile): ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-file-earmark-text"></i> CV / Profil</h5>
        <?php if (!empty($profile->bio)): ?>
            <p class="mb-3"><strong>Résumé</strong><br><?= nl2br(htmlspecialchars($profile->bio)) ?></p>
        <?php endif; ?>
        <?php if (!empty($profile->job_title)): ?>
            <p class="mb-3"><strong>Poste visé</strong><br><?= htmlspecialchars($profile->job_title) ?></p>
        <?php endif; ?>
        <?php if (!empty($profile->cv_path)): ?>
            <p class="mb-3">
                <a href="../<?= htmlspecialchars($profile->cv_path) ?>" target="_blank" class="btn btn-admin-primary btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>Télécharger le CV</a>
            </p>
        <?php endif; ?>

        <?php
        $skills_arr = [];
        if (!empty($profile->skills)) {
            $dec = json_decode($profile->skills, true);
            if (is_array($dec)) $skills_arr = $dec;
        }
        if (count($skills_arr) > 0):
        ?>
            <p class="mb-1"><strong>Compétences</strong></p>
            <ul class="mb-3">
                <?php foreach ($skills_arr as $s): ?>
                    <li><?= htmlspecialchars($s) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php
        $experiences = [];
        if (!empty($profile->experience)) {
            $dec = json_decode($profile->experience, true);
            if (is_array($dec)) $experiences = $dec;
        }
        if (count($experiences) > 0):
        ?>
            <p class="mb-1"><strong>Expériences professionnelles</strong></p>
            <ul class="list-unstyled mb-3">
                <?php foreach ($experiences as $exp): ?>
                    <li class="mb-2 pb-2 border-bottom">
                        <strong><?= htmlspecialchars($exp['titre'] ?? '') ?></strong>
                        <?php if (!empty($exp['entreprise'])): ?> — <?= htmlspecialchars($exp['entreprise']) ?><?php endif; ?>
                        <?php if (!empty($exp['periode'])): ?><br><small class="text-muted"><?= htmlspecialchars($exp['periode']) ?></small><?php endif; ?>
                        <?php if (!empty($exp['description'])): ?><br><?= nl2br(htmlspecialchars($exp['description'])) ?><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php
        $educations = [];
        if (!empty($profile->education)) {
            $dec = json_decode($profile->education, true);
            if (is_array($dec)) $educations = $dec;
        }
        if (count($educations) > 0):
        ?>
            <p class="mb-1"><strong>Formation</strong></p>
            <ul class="list-unstyled mb-0">
                <?php foreach ($educations as $edu): ?>
                    <li class="mb-2">
                        <strong><?= htmlspecialchars($edu['diplome'] ?? '') ?></strong>
                        <?php if (!empty($edu['etablissement'])): ?> — <?= htmlspecialchars($edu['etablissement']) ?><?php endif; ?>
                        <?php if (!empty($edu['periode'])): ?><br><small class="text-muted"><?= htmlspecialchars($edu['periode']) ?></small><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (empty($profile->bio) && empty($profile->job_title) && empty($profile->cv_path) && empty($profile->skills) && empty($profile->experience) && empty($profile->education)): ?>
            <p class="text-muted mb-0">Le candidat n'a pas encore complété son profil.</p>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="admin-card admin-section-card mb-4">
        <p class="text-muted mb-0">Le candidat n'a pas encore créé de profil.</p>
    </div>
    <?php endif; ?>

    <?php if (count($applications) > 0): ?>
    <div class="admin-card admin-section-card mb-4">
        <h5 class="card-title"><i class="bi bi-briefcase"></i> Candidatures (<?= count($applications) ?>)</h5>
        <div class="table-responsive">
            <table class="admin-table table table-hover">
                <thead>
                    <tr>
                        <th>Offre</th>
                        <th>Référence</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><a href="offers.php?id=<?= (int) $app->offer_id ?>"><?= htmlspecialchars($app->offer_title) ?></a></td>
                            <td><?= htmlspecialchars($app->offer_reference ?? '—') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($app->created_at)) ?></td>
                            <td><span class="badge bg-secondary"><?= $applicationStatusLabels[$app->status] ?? $app->status ?></span></td>
                            <td><a href="offers.php?id=<?= (int) $app->offer_id ?>" class="btn btn-sm btn-admin-outline">Voir l'offre</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<footer class="admin-main-footer mt-4">
    <a href="offers.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Nos offres</a>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>

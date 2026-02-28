<?php
/**
 * Candidater à une offre – Espace client
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';

$pageTitle = 'Postuler – Mon espace';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : '../';

$offer = null;
$error = '';
$alreadyApplied = false;
$existingApplication = null;
$offerId = isset($_GET['offer_id']) ? (int) $_GET['offer_id'] : (isset($_POST['offer_id']) ? (int) $_POST['offer_id'] : 0);
$clientId = (int) ($_SESSION['client_id'] ?? 0);

if ($offerId <= 0 || $clientId <= 0) {
    header('Location: index.php');
    exit;
}

if ($pdo) {
    $stmt = $pdo->prepare("
        SELECT o.id, o.title, o.reference
        FROM offer o
        WHERE o.id = ? AND o.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1) AND o.status = 'published'
    ");
    $stmt->execute([$offerId]);
    $offer = $stmt->fetch();

    if ($offer) {
        $stmt = $pdo->prepare("SELECT id, message, cv_path, status, created_at FROM offer_application WHERE offer_id = ? AND user_id = ?");
        $stmt->execute([$offerId, $clientId]);
        $existingApplication = $stmt->fetch();
        $alreadyApplied = $existingApplication !== false;
    }
}

if (!$offer) {
    header('Location: ' . $baseUrl . 'offres.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $message = trim($_POST['message'] ?? '');
    $cv_path = $existingApplication && !empty($existingApplication->cv_path) ? $existingApplication->cv_path : null;

    if (!empty($_FILES['cv']['name'])) {
        $allowed = ['pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $error = 'Format de fichier non accepté. Utilisez PDF, DOC ou DOCX (max. 5 Mo).';
        } elseif ($_FILES['cv']['size'] > 5 * 1024 * 1024) {
            $error = 'Fichier trop volumineux (max. 5 Mo).';
        } else {
            $target_dir = __DIR__ . '/../uploads/offers/cv/';
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $file_name = 'cv_' . $offerId . '_' . $clientId . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['cv']['tmp_name'], $target_dir . $file_name)) {
                $cv_path = 'uploads/offers/cv/' . $file_name;
            } else {
                $error = 'Impossible d\'enregistrer le fichier.';
            }
        }
    }

    if ($error === '') {
        try {
            if ($alreadyApplied) {
                $pdo->prepare("UPDATE offer_application SET message = ?, cv_path = ?, updated_at = NOW() WHERE offer_id = ? AND user_id = ?")
                    ->execute([$message ?: null, $cv_path, $offerId, $clientId]);
                header('Location: applications.php?msg=updated');
                exit;
            }
            $pdo->prepare("
                INSERT INTO offer_application (offer_id, user_id, message, cv_path, status)
                VALUES (?, ?, ?, ?, 'pending')
            ")->execute([$offerId, $clientId, $message ?: null, $cv_path]);
            header('Location: applications.php?msg=submitted');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Vous avez déjà postulé à cette offre.';
            } else {
                $error = 'Erreur lors de l\'envoi. Veuillez réessayer.';
            }
        }
    }
}

$organisation = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
}

require __DIR__ . '/../inc/head.php';
require __DIR__ . '/../inc/header.php';
?>

    <section class="py-5">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="<?= htmlspecialchars($baseUrl) ?>client/" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Mon espace</a>
                <span class="mx-2">/</span>
                <a href="<?= htmlspecialchars($baseUrl) ?>offres.php" class="text-muted text-decoration-none small">Nos offres</a>
                <span class="mx-2">/</span>
                <a href="<?= htmlspecialchars($baseUrl) ?>offre.php?id=<?= (int) $offer->id ?>" class="text-muted text-decoration-none small"><?= htmlspecialchars($offer->title) ?></a>
            </nav>

            <h1 class="section-heading mb-4"><i class="bi bi-send me-2"></i><?= $alreadyApplied ? 'Modifier ma candidature' : 'Postuler à l\'offre' ?></h1>
            <p class="lead mb-2"><?= htmlspecialchars($offer->title) ?></p>
            <?php if ($offer->reference): ?><p class="text-muted mb-4">Réf. <?= htmlspecialchars($offer->reference) ?></p><?php else: ?><p class="mb-4">&nbsp;</p><?php endif; ?>
<?php if ($alreadyApplied && $existingApplication): ?>
                <?php
                $statusLabels = ['pending' => 'En attente', 'reviewed' => 'Examinée', 'accepted' => 'Acceptée', 'rejected' => 'Refusée'];
                $statusLib = $statusLabels[$existingApplication->status] ?? $existingApplication->status;
                ?>
                <p class="text-muted small mb-4">Candidature du <?= date('d/m/Y à H:i', strtotime($existingApplication->created_at)) ?> · Statut : <?= htmlspecialchars($statusLib) ?></p>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <form method="POST" action="" enctype="multipart/form-data">
                                <input type="hidden" name="offer_id" value="<?= (int) $offerId ?>">
                                <div class="mb-4">
                                    <label for="message" class="form-label fw-bold">Message (optionnel)</label>
                                    <textarea name="message" id="message" class="form-control" rows="5" placeholder="Présentez-vous ou précisez votre motivation…"><?= htmlspecialchars($_POST['message'] ?? ($existingApplication->message ?? '')) ?></textarea>
                                </div>
                                <div class="mb-4">
                                    <label for="cv" class="form-label fw-bold">CV (optionnel)</label>
                                    <?php if ($existingApplication && !empty($existingApplication->cv_path)): ?>
                                        <p class="small text-muted mb-2">CV actuel : <a href="<?= htmlspecialchars($baseUrl . $existingApplication->cv_path) ?>" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i>Télécharger</a>. Envoyez un nouveau fichier pour le remplacer.</p>
                                    <?php endif; ?>
                                    <input type="file" name="cv" id="cv" class="form-control" accept=".pdf,.doc,.docx">
                                    <div class="form-text">PDF, DOC ou DOCX — max. 5 Mo</div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-read-more"><i class="bi bi-check-lg me-2"></i><?= $alreadyApplied ? 'Enregistrer les modifications' : 'Envoyer ma candidature' ?></button>
                                    <a href="<?= htmlspecialchars($baseUrl) ?>offre.php?id=<?= (int) $offer->id ?>" class="btn btn-outline-secondary">Annuler</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php require __DIR__ . '/../inc/footer.php'; ?>

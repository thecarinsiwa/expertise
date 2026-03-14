<?php
/**
 * Contenu de la page Rapports et finances
 */
$pageTitle = 'Rapports et finances – Contenu';
$currentNav = 'reports_finances';
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.documents.view');
require __DIR__ . '/inc/db.php';

if ($pdo) {
    $pdo->exec("SET NAMES 'utf8mb4'");
}

$error = '';
$success = '';
$organisations = [];
$pageContent = null;
$organisation_id = isset($_GET['organisation_id']) ? $_GET['organisation_id'] : '';
if ($organisation_id !== '' && $organisation_id !== null) {
    $organisation_id = (int) $organisation_id;
} else {
    $organisation_id = null;
}

if ($pdo) {
    $organisations = $pdo->query("SELECT id, name FROM organisation WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_OBJ);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_content'])) {
        if (!has_permission('admin.documents.modify')) {
            $error = 'Droits insuffisants pour modifier.';
        } else {
            $intro1 = trim($_POST['intro_block1'] ?? '');
            $intro2 = trim($_POST['intro_block2'] ?? '');
            $actTitle = trim($_POST['section_activity_title'] ?? '') ?: null;
            $actText = trim($_POST['section_activity_text'] ?? '') ?: null;
            $finTitle = trim($_POST['section_finance_title'] ?? '') ?: null;
            $finText = trim($_POST['section_finance_text'] ?? '') ?: null;
            $org_id = isset($_POST['organisation_id']) && $_POST['organisation_id'] !== '' ? (int) $_POST['organisation_id'] : null;
            try {
                $stmt = $pdo->prepare("SELECT id FROM reports_finances_page WHERE organisation_id <=> ? LIMIT 1");
                $stmt->execute([$org_id]);
                $existing = $stmt->fetch(PDO::FETCH_OBJ);
                if ($existing) {
                    $pdo->prepare("UPDATE reports_finances_page SET intro_block1 = ?, intro_block2 = ?, section_activity_title = ?, section_activity_text = ?, section_finance_title = ?, section_finance_text = ? WHERE id = ?")
                        ->execute([$intro1, $intro2, $actTitle, $actText, $finTitle, $finText, $existing->id]);
                    $success = 'Contenu enregistré.';
                } else {
                    $pdo->prepare("INSERT INTO reports_finances_page (organisation_id, intro_block1, intro_block2, section_activity_title, section_activity_text, section_finance_title, section_finance_text) VALUES (?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$org_id, $intro1, $intro2, $actTitle, $actText, $finTitle, $finText]);
                    $success = 'Contenu enregistré.';
                }
                $organisation_id = $org_id;
            } catch (PDOException $e) {
                $error = 'Erreur : ' . $e->getMessage();
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_default']) && has_permission('admin.documents.modify')) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM reports_finances_page WHERE organisation_id IS NULL LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_OBJ);
            $intro1 = "Rapports annuels d'activité et financiers, origine des fonds et utilisation.";
            $intro2 = "Nous nous engageons à rendre compte de notre activité et de l'usage des ressources qui nous sont confiées.";
            $actTitle = "Rapports d'activité";
            $actText = "Les rapports annuels présentent les réalisations, les enseignements et les perspectives de l'organisation.";
            $finTitle = "Transparence financière";
            $finText = "L'origine des fonds et leur répartition sont détaillées dans nos documents financiers publics.";
            if ($row) {
                $pdo->prepare("UPDATE reports_finances_page SET intro_block1 = ?, intro_block2 = ?, section_activity_title = ?, section_activity_text = ?, section_finance_title = ?, section_finance_text = ? WHERE id = ?")
                    ->execute([$intro1, $intro2, $actTitle, $actText, $finTitle, $finText, $row->id]);
            } else {
                $pdo->prepare("INSERT INTO reports_finances_page (organisation_id, intro_block1, intro_block2, section_activity_title, section_activity_text, section_finance_title, section_finance_text) VALUES (NULL, ?, ?, ?, ?, ?, ?)")
                    ->execute([$intro1, $intro2, $actTitle, $actText, $finTitle, $finText]);
            }
            $success = 'Contenu par défaut réinitialisé.';
        } catch (PDOException $e) {
            $error = 'Erreur : ' . $e->getMessage();
        }
    }

    try {
        $stmt = $pdo->prepare("SELECT id, organisation_id, intro_block1, intro_block2, section_activity_title, section_activity_text, section_finance_title, section_finance_text FROM reports_finances_page WHERE organisation_id <=> ? LIMIT 1");
        $stmt->execute([$organisation_id]);
        $pageContent = $stmt->fetch(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        $pageContent = null;
    }
}

require __DIR__ . '/inc/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Tableau de bord</a></li>
        <li class="breadcrumb-item active">Rapports et finances</li>
    </ol>
</nav>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1>Contenu – Page Rapports et finances</h1>
            <p class="text-muted mb-0">Textes affichés sur la page publique Rapports et finances. Les documents sont gérés via les catégories de documents (mots-clés : rapport, financier, activité, etc.).</p>
        </div>
        <a href="<?= htmlspecialchars(dirname($_SERVER['SCRIPT_NAME']) . '/../reports-finances.php') ?>" class="btn btn-admin-outline" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i> Voir la page</a>
    </div>
</header>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="admin-card admin-section-card mb-4">
    <form method="GET" class="mb-0">
        <label class="form-label fw-bold">Organisation</label>
        <select name="organisation_id" class="form-select" style="max-width: 320px;" onchange="this.form.submit()">
            <option value="">Contenu par défaut (global)</option>
            <?php foreach ($organisations as $o): ?>
                <option value="<?= (int) $o->id ?>" <?= $organisation_id === (int) $o->id ? 'selected' : '' ?>><?= htmlspecialchars($o->name) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="admin-card admin-section-card">
    <h5 class="card-title mb-3"><i class="bi bi-text-paragraph me-2"></i>Textes de la page</h5>
    <?php if (has_permission('admin.documents.modify')): ?>
    <form method="POST" accept-charset="UTF-8">
        <input type="hidden" name="save_content" value="1">
        <input type="hidden" name="organisation_id" value="<?= $organisation_id !== null ? (int) $organisation_id : '' ?>">
        <div class="mb-3">
            <label class="form-label">Introduction – Premier paragraphe</label>
            <textarea name="intro_block1" class="form-control" rows="2"><?= htmlspecialchars($pageContent->intro_block1 ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Introduction – Deuxième paragraphe</label>
            <textarea name="intro_block2" class="form-control" rows="2"><?= htmlspecialchars($pageContent->intro_block2 ?? '') ?></textarea>
        </div>
        <hr class="my-4">
        <div class="mb-3">
            <label class="form-label">Titre de la section « Rapports d'activité »</label>
            <input type="text" name="section_activity_title" class="form-control" value="<?= htmlspecialchars($pageContent->section_activity_title ?? '') ?>" placeholder="Rapports d'activité">
        </div>
        <div class="mb-3">
            <label class="form-label">Texte de la section Rapports d'activité</label>
            <textarea name="section_activity_text" class="form-control" rows="3"><?= htmlspecialchars($pageContent->section_activity_text ?? '') ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Titre de la section « Transparence financière »</label>
            <input type="text" name="section_finance_title" class="form-control" value="<?= htmlspecialchars($pageContent->section_finance_title ?? '') ?>" placeholder="Transparence financière">
        </div>
        <div class="mb-3">
            <label class="form-label">Texte de la section Transparence financière</label>
            <textarea name="section_finance_text" class="form-control" rows="3"><?= htmlspecialchars($pageContent->section_finance_text ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-admin-primary">Enregistrer</button>
    </form>
    <?php if ($organisation_id === null): ?>
    <form method="POST" class="mt-3" accept-charset="UTF-8" onsubmit="return confirm('Réinitialiser le contenu par défaut ?');">
        <input type="hidden" name="reset_default" value="1">
        <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser le contenu par défaut</button>
    </form>
    <?php endif; ?>
    <?php else: ?>
    <p class="text-muted mb-0">Droits de modification requis pour éditer. Contenu actuel : intro + 2 sections (Rapports d'activité, Transparence financière).</p>
    <?php endif; ?>
</div>

<footer class="admin-main-footer mt-4">
    <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Tableau de bord</a>
</footer>
<?php require __DIR__ . '/inc/footer.php'; ?>

<?php
$pageTitle = 'Annonces – Administration';
$currentNav = 'announcements';
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';
$announcements = [];
$detail = null;
if ($pdo) {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT id, title, content, is_pinned, published_at, expires_at, created_at FROM announcement WHERE id = ?");
        $stmt->execute([$id]);
        $detail = $stmt->fetch();
    }
    if (!$detail) {
        $stmt = $pdo->query("
            SELECT id, title, published_at, created_at
            FROM announcement
            ORDER BY COALESCE(published_at, created_at) DESC, created_at DESC
        ");
        if ($stmt)
            $announcements = $stmt->fetchAll();
    }
}
require __DIR__ . '/inc/header.php';
?>

<header class="admin-header">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1><?= $detail ? 'Détail annonce' : 'Annonces' ?></h1>
            <p><?= $detail ? htmlspecialchars($detail->title) : 'Liste des annonces publiées.' ?></p>
        </div>
        <?php if (!$detail): ?>
            <a href="#" class="btn btn-admin-primary"><i class="bi bi-plus-lg me-1"></i> Nouvelle annonce</a>
        <?php endif; ?>
    </div>
</header>

<?php if ($detail): ?>
    <div class="admin-card admin-section-card mb-4">
        <table class="admin-table">
            <tr>
                <th style="width:180px;">Titre</th>
                <td><?= htmlspecialchars($detail->title) ?></td>
            </tr>
            <tr>
                <th>Épinglée</th>
                <td><?= $detail->is_pinned ? 'Oui' : 'Non' ?></td>
            </tr>
            <tr>
                <th>Publiée le</th>
                <td><?= $detail->published_at ? date('d/m/Y H:i', strtotime($detail->published_at)) : '—' ?></td>
            </tr>
            <tr>
                <th>Expire le</th>
                <td><?= $detail->expires_at ? date('d/m/Y', strtotime($detail->expires_at)) : '—' ?></td>
            </tr>
            <tr>
                <th>Créée le</th>
                <td><?= $detail->created_at ? date('d/m/Y H:i', strtotime($detail->created_at)) : '—' ?></td>
            </tr>
        </table>
        <?php if (!empty($detail->content)): ?>
            <div class="mt-3 pt-3 border-top">
                <strong>Contenu</strong>
                <div class="mt-1 text-muted"><?= nl2br(htmlspecialchars($detail->content)) ?></div>
            </div>
        <?php endif; ?>
        <div class="mt-3">
            <a href="announcements.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Retour à la
                liste</a>
        </div>
    </div>
<?php elseif (count($announcements) > 0): ?>
    <div class="admin-card admin-section-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Publiée le</th>
                    <th>Créée le</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($announcements as $a): ?>
                    <tr>
                        <td><a href="announcements.php?id=<?= (int) $a->id ?>"><?= htmlspecialchars($a->title) ?></a></td>
                        <td class="text-muted"><?= $a->published_at ? date('d/m/Y H:i', strtotime($a->published_at)) : '—' ?>
                        </td>
                        <td class="text-muted"><?= $a->created_at ? date('d/m/Y H:i', strtotime($a->created_at)) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="admin-card admin-section-card">
        <div class="admin-empty">
            <i class="bi bi-megaphone d-block"></i>
            Aucune annonce pour le moment.
            <p class="mt-2 mb-0"><a href="#" class="btn btn-admin-primary btn-sm">Créer une annonce</a></p>
        </div>
    </div>
<?php endif; ?>

<footer class="admin-main-footer">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="index.php" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Tableau de
            bord</a>
        <span><?= date('Y') ?></span>
    </div>
</footer>

<?php require __DIR__ . '/inc/footer.php'; ?>
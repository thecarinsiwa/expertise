<?php
session_start();
$pageTitle = 'Nous contacter';
$baseUrl = '';
require_once __DIR__ . '/inc/db.php';
$organisation = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, description, address, phone, email, website FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Nous contacter — ' . $organisation->name;
}
require_once __DIR__ . '/inc/page-static.php';
?>
    <section class="py-5 page-content">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Nous contacter</h1>
            <div class="content-prose">
                <p>Coordonnées de nos bureaux. N'hésitez pas à nous écrire pour toute question.</p>
                <?php if ($organisation && (isset($organisation->address) || isset($organisation->phone) || isset($organisation->email) || isset($organisation->website))): ?>
                <div class="card border-0 shadow-sm mt-4 p-4" style="max-width: 480px;">
                    <h2 class="h6 text-uppercase text-muted mb-3"><?= htmlspecialchars($organisation->name) ?></h2>
                    <?php if (!empty($organisation->address)): ?>
                        <p class="mb-2"><i class="bi bi-geo-alt me-2 text-muted"></i><?= nl2br(htmlspecialchars($organisation->address)) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($organisation->phone)): ?>
                        <p class="mb-2"><i class="bi bi-telephone me-2 text-muted"></i><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $organisation->phone)) ?>"><?= htmlspecialchars($organisation->phone) ?></a></p>
                    <?php endif; ?>
                    <?php if (!empty($organisation->email)): ?>
                        <p class="mb-2"><i class="bi bi-envelope me-2 text-muted"></i><a href="mailto:<?= htmlspecialchars($organisation->email) ?>"><?= htmlspecialchars($organisation->email) ?></a></p>
                    <?php endif; ?>
                    <?php if (!empty($organisation->website)): ?>
                        <p class="mb-0"><i class="bi bi-globe me-2 text-muted"></i><a href="<?= htmlspecialchars($organisation->website) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($organisation->website) ?></a></p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">Les coordonnées de l'organisation seront affichées ici une fois renseignées dans l'administration.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>

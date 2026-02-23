<?php
session_start();
$pageTitle = 'Mentions légales';
$baseUrl = '';
require_once __DIR__ . '/inc/db.php';
$organisation = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT id, name, description, address, email, website FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Mentions légales — ' . $organisation->name;
}
require_once __DIR__ . '/inc/page-static.php';
?>
    <section class="py-5 page-content">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Mentions légales</h1>
            <div class="content-prose">
                <p>Éditeur du site : <?= $organisation ? htmlspecialchars($organisation->name) : 'Expertise' ?>.</p>
                <?php if ($organisation && (!empty($organisation->address) || !empty($organisation->email))): ?>
                <p>
                    <?php if (!empty($organisation->address)): ?>Adresse : <?= nl2br(htmlspecialchars($organisation->address)) ?>. <?php endif; ?>
                    <?php if (!empty($organisation->email)): ?>Contact : <a href="mailto:<?= htmlspecialchars($organisation->email) ?>"><?= htmlspecialchars($organisation->email) ?></a>.<?php endif; ?>
                </p>
                <?php endif; ?>
                <h2 class="h5 mt-4 mb-2">Hébergement</h2>
                <p>Ce site est hébergé selon la configuration de votre hébergeur. Les mentions d'hébergement peuvent être complétées ici.</p>
                <h2 class="h5 mt-4 mb-2">Propriété intellectuelle</h2>
                <p>L'ensemble du contenu de ce site (textes, images, logos) est protégé par le droit d'auteur et ne peut être reproduit sans autorisation.</p>
                <h2 class="h5 mt-4 mb-2">Données personnelles</h2>
                <p>Les données collectées via ce site sont traitées conformément à la réglementation en vigueur. Pour toute question, contactez-nous.</p>
            </div>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>

<?php
session_start();
$pageTitle = 'Notre gouvernance';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/') . '/';
$organisation = null;
$pageContent = null;

require_once __DIR__ . '/inc/db.php';

if ($pdo) {
    $pdo->exec("SET NAMES 'utf8mb4'");
    $stmt = $pdo->query("SELECT id, name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
    if ($organisation) $pageTitle = 'Notre gouvernance — ' . $organisation->name;

    $orgId = $organisation ? (int) $organisation->id : null;
    try {
        $stmt = $pdo->prepare("SELECT id, intro_block1, intro_block2, section_instances_title, section_instances_text, section_bureaux_title, section_bureaux_text FROM governance_page WHERE organisation_id = ? OR organisation_id IS NULL ORDER BY organisation_id IS NULL ASC LIMIT 1");
        $stmt->execute([$orgId]);
        $pageContent = $stmt->fetch(PDO::FETCH_OBJ);
    } catch (PDOException $e) {
        $pageContent = null;
    }
}

$intro1 = $pageContent ? ($pageContent->intro_block1 ?? 'Notre gouvernance et ce que signifie être une association. Guide de nos instances et de nos bureaux.') : 'Notre gouvernance et ce que signifie être une association. Guide de nos instances et de nos bureaux.';
$intro2 = $pageContent ? ($pageContent->intro_block2 ?? 'La gouvernance de l\'organisation assure la cohérence des décisions, le respect des mandats et la redevabilité envers les bénéficiaires et les partenaires.') : 'La gouvernance de l\'organisation assure la cohérence des décisions, le respect des mandats et la redevabilité envers les bénéficiaires et les partenaires.';
$instancesTitle = $pageContent ? ($pageContent->section_instances_title ?? 'Instances') : 'Instances';
$instancesText = $pageContent ? ($pageContent->section_instances_text ?? 'Les instances dirigeantes définissent les orientations stratégiques et contrôlent la mise en œuvre des activités.') : 'Les instances dirigeantes définissent les orientations stratégiques et contrôlent la mise en œuvre des activités.';
$bureauxTitle = $pageContent ? ($pageContent->section_bureaux_title ?? 'Bureaux') : 'Bureaux';
$bureauxText = $pageContent ? ($pageContent->section_bureaux_text ?? 'Nos bureaux dans le monde assurent la coordination opérationnelle et le lien avec les terrains d\'intervention.') : 'Nos bureaux dans le monde assurent la coordination opérationnelle et le lien avec les terrains d\'intervention.';

require_once __DIR__ . '/inc/page-static.php';
?>
    <section class="py-5 page-content">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Accueil</a>
            </nav>
            <h1 class="section-heading mb-4">Notre gouvernance</h1>
            <div class="content-prose">
                <?php if ($intro1 !== ''): ?><p><?= nl2br(htmlspecialchars($intro1)) ?></p><?php endif; ?>
                <?php if ($intro2 !== ''): ?><p><?= nl2br(htmlspecialchars($intro2)) ?></p><?php endif; ?>
                <?php if ($instancesTitle !== '' || $instancesText !== ''): ?>
                    <h2 class="h5 mt-4 mb-2"><?= htmlspecialchars($instancesTitle) ?></h2>
                    <?php if ($instancesText !== ''): ?><p><?= nl2br(htmlspecialchars($instancesText)) ?></p><?php endif; ?>
                <?php endif; ?>
                <?php if ($bureauxTitle !== '' || $bureauxText !== ''): ?>
                    <h2 class="h5 mt-4 mb-2"><?= htmlspecialchars($bureauxTitle) ?></h2>
                    <?php if ($bureauxText !== ''): ?><p><?= nl2br(htmlspecialchars($bureauxText)) ?></p><?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php require __DIR__ . '/inc/footer.php'; ?>

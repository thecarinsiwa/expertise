<?php
if (!isset($organisation)) $organisation = null;
if (!isset($baseUrl)) $baseUrl = '';
?>
    <footer class="site-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <p class="footer-title"><?= $organisation ? htmlspecialchars($organisation->name) : 'Expertise' ?></p>
                    <p class="mb-0 small">Plateforme de gestion des missions et des projets.</p>
                </div>
                <div class="col-md-2">
                    <p class="footer-title">Liens</p>
                    <ul class="footer-links">
                        <li><a href="#">Qui nous sommes</a></li>
                        <li><a href="#">Notre travail</a></li>
                        <li><a href="#">Où nous travaillons</a></li>
                        <li><a href="#">Nous contacter</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <p class="footer-title">Back-office</p>
                    <ul class="footer-links">
                        <li><a href="<?= $baseUrl ?>admin/">Administration</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>&copy; <?= date('Y') ?> <?= $organisation ? htmlspecialchars($organisation->name) : 'Expertise' ?></span>
                <a href="#">Mentions légales</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $baseUrl ?>inc/scripts.js"></script>
</body>
</html>

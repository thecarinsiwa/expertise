<?php
if (!isset($baseUrl)) $baseUrl = '';
if (!isset($organisation)) $organisation = null;
?>
<body>
    <header class="site-header">
        <?php require __DIR__ . '/navbar.php'; ?>
    </header>

    <?php require __DIR__ . '/mega-menus.php'; ?>

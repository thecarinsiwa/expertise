<?php
/**
 * Page d'accueil – Style MSF (Médecins Sans Frontières)
 * Données issues de database/schema.sql : organisation, mission, announcement
 */
$pageTitle = 'Expertise';
$organisation = null;
$featuredMission = null;
$featuredAnnouncement = null;
$tagline = 'An international, independent medical humanitarian organisation';

$dbConfig = [
    'host'   => 'localhost',
    'dbname' => 'expertise',
    'user'   => 'root',
    'pass'   => '',
    'charset'=> 'utf8mb4',
];

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['dbname'], $dbConfig['charset']);
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    ]);

    // Organisation (nom, description pour le slogan)
    $stmt = $pdo->query("SELECT name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) {
        $organisation = $row;
        $pageTitle = $row->name;
        if (!empty($row->description)) {
            $tagline = $row->description;
        }
    }

    // Mission mise en avant (hero) – dernière ou première par date
    $stmt = $pdo->query("
        SELECT m.id, m.title, m.description, m.location, m.start_date, m.updated_at
        FROM mission m
        WHERE m.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
        ORDER BY m.start_date DESC, m.updated_at DESC
        LIMIT 1
    ");
    if ($stmt && $row = $stmt->fetch()) {
        $featuredMission = $row;
    }

    // Sinon, annonce mise en avant
    if (!$featuredMission) {
        $stmt = $pdo->query("
            SELECT a.id, a.title, a.content AS description, a.published_at AS updated_at
            FROM announcement a
            WHERE a.organisation_id = (SELECT id FROM organisation WHERE is_active = 1 LIMIT 1)
            ORDER BY a.published_at DESC, a.created_at DESC
            LIMIT 1
        ");
        if ($stmt && $row = $stmt->fetch()) {
            $featuredAnnouncement = $row;
        }
    }
} catch (PDOException $e) {
    // Pas de base ou schéma non chargé : affichage avec contenu par défaut
    $pdo = null;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --msf-red: #c31e3a;
            --msf-dark: #1a1a1a;
            --msf-dark-nav: #2d2d2d;
        }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; padding-top: 120px; }
        .site-header { position: fixed; top: 0; left: 0; right: 0; width: 100%; z-index: 1030; }
        .top-bar { background: var(--msf-dark); color: #fff; font-size: 0.85rem; }
        .top-bar a { color: #fff; text-decoration: none; }
        .navbar-main { background: #fff !important; box-shadow: 0 1px 0 rgba(0,0,0,0.08); }
        .navbar-main .navbar-brand { color: #000; font-weight: 700; text-transform: uppercase; letter-spacing: 0.02em; }
        .navbar-main .nav-link { color: #000 !important; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 0.03em; }
        .navbar-main .nav-link:hover { color: #000 !important; opacity: 0.85; }
        .navbar-main .nav-link .bi-chevron-down { font-size: 0.6rem; color: var(--msf-red); vertical-align: 0.15em; }
        .navbar-main .nav-link.active { outline: 1px solid #fff; outline-offset: 4px; }
        .navbar-main .navbar-toggler { border-color: #333; }
        .navbar-main .navbar-toggler-icon { filter: invert(1); }
        .nav-red-line { height: 3px; background: var(--msf-red); width: 100%; }
        .btn-donate { background: var(--msf-red); color: #fff; border: none; border-radius: 4px; padding: 0.5rem 1.25rem; text-transform: uppercase; font-size: 0.9rem; font-weight: 600; }
        .btn-donate:hover { background: #a01930; color: #fff; }
        /* Mega-menu overlay */
        .mega-menu { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(26,26,26,0.98); z-index: 1050; overflow-y: auto; padding-top: 0; }
        .mega-menu.show { display: block; }
        .mega-menu .mega-red-line { height: 3px; background: var(--msf-red); width: 100%; }
        .mega-menu .mega-header { display: flex; justify-content: space-between; align-items: flex-start; padding: 2rem 0 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .mega-menu .mega-title { font-size: 2.5rem; font-weight: 700; color: #fff; margin: 0; text-transform: uppercase; letter-spacing: 0.02em; }
        .mega-menu .btn-close-mega { background: none; border: none; color: #fff; font-size: 1.5rem; padding: 0.25rem; line-height: 1; cursor: pointer; opacity: 0.9; }
        .mega-menu .btn-close-mega:hover { opacity: 1; color: #fff; }
        .mega-menu .mega-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 2rem; padding: 2rem 0 3rem; }
        .mega-menu .mega-col h3 { font-size: 0.8rem; font-weight: 700; color: var(--msf-red); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.75rem; }
        .mega-menu .mega-col p { color: rgba(255,255,255,0.9); font-size: 0.95rem; line-height: 1.5; margin-bottom: 0.75rem; }
        .mega-menu .mega-col .mega-link { color: var(--msf-red); text-transform: uppercase; font-size: 0.85rem; font-weight: 600; text-decoration: none; border-bottom: 1px solid var(--msf-red); }
        .mega-menu .mega-col .mega-link:hover { color: #fff; border-bottom-color: #fff; }
        .hero { position: relative; min-height: 70vh; background: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.35)), url('https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=1600') center/cover no-repeat; }
        .hero .container { position: relative; z-index: 2; min-height: 70vh; display: flex; flex-direction: column; justify-content: flex-end; padding-bottom: 3rem; }
        .hero .badge-location { color: rgba(255,255,255,0.85); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .hero h1 { color: #fff; font-weight: 700; font-size: clamp(1.75rem, 4vw, 2.75rem); text-shadow: 0 1px 3px rgba(0,0,0,0.5); }
        .hero .meta { color: rgba(255,255,255,0.9); font-size: 0.9rem; }
        .hero .lead { color: #fff; max-width: 480px; text-shadow: 0 1px 2px rgba(0,0,0,0.4); }
        .btn-read-more { background: var(--msf-red); color: #fff; border: none; border-radius: 4px; padding: 0.6rem 1.5rem; }
        .btn-read-more:hover { background: #a01930; color: #fff; }
        .share-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: #333; }
        .share-icon { width: 40px; height: 40px; border: 1px solid var(--msf-red); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: var(--msf-red); text-decoration: none; margin-right: 0.5rem; }
        .share-icon:hover { background: var(--msf-red); color: #fff; }
        .copy-link { color: var(--msf-red); text-decoration: none; font-size: 0.9rem; }
        .tagline { font-size: clamp(1.1rem, 2.5vw, 1.5rem); font-weight: 700; color: #1a1a1a; }
    </style>
</head>
<body>
    <header class="site-header">
        <!-- Barre supérieure -->
        <div class="top-bar py-2">
            <div class="container">
                <div class="d-flex justify-content-end gap-3">
                    <a href="#">Travailler avec nous</a>
                    <a href="#">Sites</a>
                    <span>Français <i class="bi bi-chevron-down small"></i></span>
                </div>
    </div>
            </div>
        </div>

        <!-- Navigation principale (fond blanc, texte noir) -->
        <nav class="navbar navbar-expand-lg navbar-main navbar-light">
            <div class="container">
                <a class="navbar-brand" href="#"><?= $organisation ? htmlspecialchars($organisation->name) : 'EXPERTISE' ?></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link nav-mega-trigger" href="#" data-mega="mega-about">À propos <i class="bi bi-chevron-down"></i></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link nav-mega-trigger" href="#" data-mega="mega-what">Ce que nous faisons <i class="bi bi-chevron-down"></i></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link nav-mega-trigger" href="#" data-mega="mega-where">Où nous travaillons <i class="bi bi-chevron-down"></i></a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="#">Ressources</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Dernières actualités</a></li>
                    </ul>
                    <div class="d-flex align-items-center gap-2">
                        <a class="nav-link" href="#" aria-label="Recherche"><i class="bi bi-search"></i></a>
                        <a class="btn btn-donate" href="#">Donner</a>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Ligne rouge sous la nav -->
        <div class="nav-red-line" id="navRedLine"></div>
    </header>

    <!-- Mega-menu : À propos -->
    <div class="mega-menu" id="mega-about" aria-hidden="true">
        <div class="mega-red-line"></div>
        <div class="container">
            <div class="mega-header">
                <h2 class="mega-title">À propos</h2>
                <button type="button" class="btn-close-mega" aria-label="Fermer" data-close-mega><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="mega-grid">
                <div class="mega-col">
                    <h3>Qui nous sommes</h3>
                    <p>Découvrez notre mission, notre charte et nos principes, et qui nous sommes.</p>
                    <a href="#" class="mega-link">En savoir plus</a>
                </div>
                <div class="mega-col">
                    <h3>Comment nous travaillons</h3>
                    <p>Ce qui déclenche une intervention et comment la logistique permet à nos équipes de réagir rapidement.</p>
                    <a href="#" class="mega-link">En savoir plus</a>
                </div>
                <div class="mega-col">
                    <h3>Notre gouvernance</h3>
                    <p>Notre gouvernance et ce que signifie être une association. Guide visuel de nos bureaux dans le monde.</p>
                    <a href="#" class="mega-link">En savoir plus</a>
                </div>
                <div class="mega-col">
                    <h3>Rapports et finances</h3>
                    <p>Rapports annuels d'activité et financiers, origine des fonds et utilisation.</p>
                    <a href="#" class="mega-link">En savoir plus</a>
                </div>
                <div class="mega-col">
                    <h3>Responsabilité</h3>
                    <p>Politiques et rapports sur nos engagements éthiques, diversité et impact environnemental.</p>
                    <a href="#" class="mega-link">En savoir plus</a>
                </div>
                <div class="mega-col">
                    <h3>Nous contacter</h3>
                    <p>Coordonnées de nos bureaux dans le monde.</p>
                    <a href="#" class="mega-link">En savoir plus</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mega-menu : Ce que nous faisons -->
    <div class="mega-menu" id="mega-what" aria-hidden="true">
        <div class="mega-red-line"></div>
        <div class="container">
            <div class="mega-header">
                <h2 class="mega-title">Ce que nous faisons</h2>
                <button type="button" class="btn-close-mega" aria-label="Fermer" data-close-mega><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="mega-grid">
                <div class="mega-col">
                    <h3>Missions</h3>
                    <p>Nos missions sur le terrain : objectifs, rapports et résultats.</p>
                    <a href="#" class="mega-link">En savoir plus</a>
                </div>
                <div class="mega-col">
                    <h3>Projets</h3>
                    <p>Portfolios et programmes, suivi des projets et livrables.</p>
                    <a href="#" class="mega-link">En savoir plus</a>
                </div>
                <div class="mega-col">
                    <h3>Expertise</h3>
                    <p>Métiers, compétences et ressources mobilisées.</p>
                    <a href="#" class="mega-link">En savoir plus</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mega-menu : Où nous travaillons -->
    <div class="mega-menu" id="mega-where" aria-hidden="true">
        <div class="mega-red-line"></div>
        <div class="container">
            <div class="mega-header">
                <h2 class="mega-title">Où nous travaillons</h2>
                <button type="button" class="btn-close-mega" aria-label="Fermer" data-close-mega><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="mega-grid">
                <div class="mega-col">
                    <h3>Carte</h3>
                    <p>Visualisez nos interventions et bureaux sur la carte.</p>
                    <a href="#" class="mega-link">En savoir plus</a>
                </div>
                <div class="mega-col">
                    <h3>Par pays</h3>
                    <p>Accès par pays et par région.</p>
                    <a href="#" class="mega-link">En savoir plus</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-end">
                <div class="col-lg-7">
                    <?php if ($featuredMission): ?>
                        <span class="badge-location d-block mb-2"><?= htmlspecialchars($featuredMission->location ?: 'Mission') ?></span>
                        <h1 class="mb-3"><?= htmlspecialchars($featuredMission->title) ?></h1>
                        <p class="meta mb-2">Mise à jour projet · <?= $featuredMission->updated_at ? date('d M Y', strtotime($featuredMission->updated_at)) : ($featuredMission->start_date ? date('d M Y', strtotime($featuredMission->start_date)) : '') ?></p>
                        <?php if (!empty($featuredMission->description)): ?>
                            <p class="lead mb-4"><?= htmlspecialchars(mb_substr(strip_tags($featuredMission->description), 0, 220)) ?><?= mb_strlen(strip_tags($featuredMission->description)) > 220 ? '…' : '' ?></p>
                        <?php endif; ?>
                        <a href="#" class="btn btn-read-more">Lire la suite</a>
                    <?php elseif ($featuredAnnouncement): ?>
                        <span class="badge-location d-block mb-2">Annonce</span>
                        <h1 class="mb-3"><?= htmlspecialchars($featuredAnnouncement->title) ?></h1>
                        <p class="meta mb-2"><?= $featuredAnnouncement->updated_at ? date('d M Y', strtotime($featuredAnnouncement->updated_at)) : '' ?></p>
                        <?php if (!empty($featuredAnnouncement->description)): ?>
                            <p class="lead mb-4"><?= htmlspecialchars(mb_substr(strip_tags($featuredAnnouncement->description), 0, 220)) ?><?= mb_strlen(strip_tags($featuredAnnouncement->description)) > 220 ? '…' : '' ?></p>
                        <?php endif; ?>
                        <a href="#" class="btn btn-read-more">Lire la suite</a>
                    <?php else: ?>
                        <span class="badge-location d-block mb-2">Actualité</span>
                        <h1 class="mb-3">Bienvenue sur Expertise</h1>
                        <p class="meta mb-2">Mise à jour · <?= date('d M Y') ?></p>
                        <p class="lead mb-4">Plateforme de gestion des missions et des projets. Connectez la base de données pour afficher les missions et annonces à la une.</p>
                        <a href="#" class="btn btn-read-more">Lire la suite</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Partager + Slogan -->
    <div class="container py-4">
        <div class="row align-items-center">
            <div class="col-md-4">
                <p class="share-label mb-2">Partager</p>
                <a href="#" class="share-icon" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                <a href="#" class="share-icon" aria-label="X"><i class="bi bi-twitter-x"></i></a>
                <a href="#" class="share-icon" aria-label="Email"><i class="bi bi-envelope"></i></a>
                <a href="#" class="share-icon" aria-label="Imprimer"><i class="bi bi-printer"></i></a>
                <a href="#" class="copy-link ms-1"><i class="bi bi-link-45deg"></i> Copier le lien</a>
            </div>
            <div class="col-md-8 text-md-end mt-3 mt-md-0">
                <p class="tagline mb-0"><?= htmlspecialchars($tagline) ?></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function () {
        var triggers = document.querySelectorAll('.nav-mega-trigger');
        var megas = document.querySelectorAll('.mega-menu');
        var closeBtns = document.querySelectorAll('[data-close-mega]');
        var body = document.body;

        function closeAll() {
            megas.forEach(function (m) {
                m.classList.remove('show');
                m.setAttribute('aria-hidden', 'true');
            });
            triggers.forEach(function (t) { t.classList.remove('active'); });
            body.style.overflow = '';
        }

        function openMega(id) {
            closeAll();
            var el = document.getElementById(id);
            if (el) {
                el.classList.add('show');
                el.setAttribute('aria-hidden', 'false');
                body.style.overflow = 'hidden';
            }
            var t = document.querySelector('.nav-mega-trigger[data-mega="' + id + '"]');
            if (t) t.classList.add('active');
        }

        triggers.forEach(function (t) {
            t.addEventListener('click', function (e) {
                e.preventDefault();
                var id = t.getAttribute('data-mega');
                if (document.getElementById(id).classList.contains('show')) {
                    closeAll();
                } else {
                    openMega(id);
                }
            });
        });

        closeBtns.forEach(function (btn) {
            btn.addEventListener('click', closeAll);
        });

        document.addEventListener('click', function (e) {
            if (e.target.closest('.nav-mega-trigger')) return;
            if (e.target.closest('.mega-menu')) return;
            closeAll();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAll();
        });
    })();
    </script>
</body>
</html>

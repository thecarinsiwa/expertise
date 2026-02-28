<?php
if (!isset($pageTitle))
    $pageTitle = 'Administration – Expertise';
$currentNav = isset($currentNav) ? $currentNav : 'dashboard';
require_once __DIR__ . '/auth.php';
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        :root {
            --admin-bg: #f0f2f5;
            --admin-sidebar: #1D1C3E;
            --admin-sidebar-hover: #2d2d4e;
            --admin-accent: #FFC107;
            --admin-accent-hover: #FFB300;
            --admin-card: #fff;
            --admin-text: #1a1a1a;
            --admin-muted: #6c757d;
            --admin-font: "Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif
        }

        * {
            font-family: var(--admin-font)
        }

        body {
            background: var(--admin-bg);
            min-height: 100vh;
            margin: 0
        }

        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            background: var(--admin-sidebar);
            color: #fff;
            z-index: 1030;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 24px rgba(0, 0, 0, .08)
        }

        .admin-sidebar-brand {
            padding: 1.5rem;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: .02em;
            border-bottom: 1px solid rgba(255, 255, 255, .08)
        }

        .admin-sidebar-brand a {
            color: #fff;
            text-decoration: none
        }

        .admin-sidebar-nav {
            flex: 1;
            padding: 1rem 0;
            overflow-y: auto
        }

        .admin-sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem 1.5rem;
            color: rgba(255, 255, 255, .85);
            text-decoration: none;
            font-size: .9rem;
            font-weight: 500;
            transition: background .2s, color .2s
        }

        .admin-sidebar-nav .nav-link:hover {
            background: var(--admin-sidebar-hover);
            color: #fff
        }

        .admin-sidebar-nav .nav-link.active {
            background: rgba(255, 193, 7, .2);
            color: #fff;
            border-left: 3px solid var(--admin-accent)
        }

        .admin-sidebar-nav .nav-link i {
            font-size: 1.15rem;
            opacity: .9
        }

        .admin-sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, .08);
            display: flex;
            flex-direction: column;
            gap: .25rem
        }

        .admin-sidebar-footer .sidebar-user {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .78rem;
            color: rgba(255, 255, 255, .45);
            padding: .25rem 0 .5rem;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            margin-bottom: .25rem;
            overflow: hidden
        }

        .admin-sidebar-footer .sidebar-user span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap
        }

        .admin-sidebar-footer a {
            color: rgba(255, 255, 255, .7);
            text-decoration: none;
            font-size: .85rem;
            display: flex;
            align-items: center;
            gap: .5rem
        }

        .admin-sidebar-footer a:hover {
            color: #fff
        }

        .admin-sidebar-label {
            padding: 1.25rem 1.5rem .5rem;
            font-size: .65rem;
            font-weight: 700;
            color: rgba(255, 255, 255, .35);
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .admin-main {
            margin-left: 260px;
            min-height: 100vh;
            padding: 2rem
        }

        .admin-header {
            margin-bottom: 2rem
        }

        .admin-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--admin-text);
            margin: 0
        }

        .admin-header p {
            color: var(--admin-muted);
            margin: .25rem 0 0;
            font-size: .95rem
        }

        .admin-card {
            background: var(--admin-card);
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
            transition: box-shadow .2s
        }

        .admin-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08)
        }

        .admin-table {
            width: 100%;
            font-size: .9rem
        }

        .admin-table th {
            font-weight: 600;
            color: var(--admin-muted);
            text-transform: uppercase;
            letter-spacing: .03em;
            font-size: .75rem;
            padding: .75rem 0;
            border-bottom: 1px solid #eee
        }

        .admin-table td {
            padding: .75rem 0;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle
        }

        .admin-table tr:last-child td {
            border-bottom: none
        }

        .admin-table a {
            color: var(--admin-text);
            text-decoration: none;
            font-weight: 500
        }

        .admin-table a:hover {
            color: var(--admin-accent)
        }

        .admin-section-card {
            padding: 1.5rem
        }

        .admin-section-card .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--admin-text);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .5rem
        }

        .admin-empty {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--admin-muted);
            font-size: .9rem
        }

        .admin-empty i {
            font-size: 2rem;
            margin-bottom: .5rem;
            opacity: .5
        }

        .admin-main-footer {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e0e0;
            color: var(--admin-muted);
            font-size: .85rem
        }

        .btn-admin-primary {
            background: var(--admin-accent);
            color: var(--admin-sidebar);
            border: none;
            font-weight: 600;
            padding: .5rem 1rem;
            border-radius: 8px;
            font-size: .9rem
        }

        .btn-admin-primary:hover {
            background: var(--admin-accent-hover);
            color: var(--admin-sidebar)
        }

        .btn-admin-outline {
            background: 0 0;
            color: var(--admin-sidebar);
            border: 2px solid var(--admin-sidebar);
            font-weight: 600;
            padding: .5rem 1rem;
            border-radius: 8px;
            font-size: .9rem
        }

        .btn-admin-outline:hover {
            background: var(--admin-sidebar);
            color: #fff
        }

        @media (max-width:991.98px) {
            .admin-sidebar {
                width: 72px
            }

            .admin-sidebar-brand span:not(.bi) {
                display: none
            }

            .admin-sidebar-nav .nav-link span {
                display: none
            }

            .admin-sidebar-nav .nav-link {
                justify-content: center;
                padding: 1rem
            }

            .admin-sidebar-footer span {
                display: none
            }

            .admin-main {
                margin-left: 72px;
                padding: 1rem
            }
        }
    </style>
</head>

<body>
    <aside class="admin-sidebar">
        <div class="admin-sidebar-brand">
            <a href="../">
                <i class="bi bi-grid-1x2-fill me-2"></i><span>Expertise</span>
            </a>
        </div>
        <nav class="admin-sidebar-nav">
            <?php if (has_permission('admin.dashboard')): ?>
            <a href="index.php" class="nav-link<?= $currentNav === 'dashboard' ? ' active' : '' ?>">
                <i class="bi bi-speedometer2"></i><span>Tableau de bord</span>
            </a>
            <?php endif; ?>

            <div class="admin-sidebar-label">Structure</div>
            <?php if (has_permission('admin.organisations.view')): ?>
            <a href="organisations.php" class="nav-link<?= $currentNav === 'organisations' ? ' active' : '' ?>">
                <i class="bi bi-building"></i><span>Organisations</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.units.view')): ?>
            <a href="units.php" class="nav-link<?= $currentNav === 'units' ? ' active' : '' ?>">
                <i class="bi bi-diagram-3"></i><span>Unités & Services</span>
            </a>
            <?php endif; ?>

            <div class="admin-sidebar-label">RH & Sécurité</div>
            <?php if (has_permission('admin.users.view')): ?>
            <a href="user.php" class="nav-link<?= $currentNav === 'users' ? ' active' : '' ?>">
                <i class="bi bi-person-badge"></i><span>Utilisateurs</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.staff.view')): ?>
            <a href="staff.php" class="nav-link<?= $currentNav === 'staff' ? ' active' : '' ?>">
                <i class="bi bi-people"></i><span>Personnel</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.roles.view')): ?>
            <a href="roles.php" class="nav-link<?= $currentNav === 'roles' ? ' active' : '' ?>">
                <i class="bi bi-shield-lock"></i><span>Rôles & Accès</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.security.view')): ?>
            <a href="security.php" class="nav-link<?= $currentNav === 'security' ? ' active' : '' ?>">
                <i class="bi bi-shield-check"></i><span>Sécurité</span>
            </a>
            <?php endif; ?>

            <div class="admin-sidebar-label">Opérations</div>
            <?php if (has_permission('admin.projects.view')): ?>
            <a href="projects.php" class="nav-link<?= $currentNav === 'projects' ? ' active' : '' ?>">
                <i class="bi bi-kanban"></i><span>Projets & Tâches</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.programmes.view')): ?>
            <a href="programmes.php" class="nav-link<?= $currentNav === 'programmes' ? ' active' : '' ?>">
                <i class="bi bi-folder2"></i><span>Programmes</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.portfolios.view')): ?>
            <a href="portfolios.php" class="nav-link<?= $currentNav === 'portfolios' ? ' active' : '' ?>">
                <i class="bi bi-folder"></i><span>Portfolios</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.bailleurs.view')): ?>
            <a href="bailleurs.php" class="nav-link<?= $currentNav === 'bailleurs' ? ' active' : '' ?>">
                <i class="bi bi-bank"></i><span>Bailleurs de fonds</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.missions.view')): ?>
            <a href="missions.php" class="nav-link<?= $currentNav === 'missions' ? ' active' : '' ?>">
                <i class="bi bi-geo-alt"></i><span>Missions</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.offers.view')): ?>
            <a href="offers.php" class="nav-link<?= $currentNav === 'offers' ? ' active' : '' ?>">
                <i class="bi bi-briefcase"></i><span>Nos offres</span>
            </a>
            <?php endif; ?>

        
            <div class="admin-sidebar-label">Communication</div>
            <?php if (has_permission('admin.announcements.view')): ?>
            <a href="announcements.php" class="nav-link<?= $currentNav === 'announcements' ? ' active' : '' ?>">
                <i class="bi bi-megaphone"></i><span>Annonces</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.channels.view')): ?>
            <a href="channels.php" class="nav-link<?= $currentNav === 'channels' ? ' active' : '' ?>">
                <i class="bi bi-chat-dots"></i><span>Canaux</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.conversations.view')): ?>
            <a href="conversations.php" class="nav-link<?= $currentNav === 'conversations' ? ' active' : '' ?>">
                <i class="bi bi-chat-text"></i><span>Conversations</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.notifications.view')): ?>
            <a href="notifications.php" class="nav-link<?= $currentNav === 'notifications' ? ' active' : '' ?>">
                <i class="bi bi-bell"></i><span>Notifications</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.comments.view')): ?>
            <a href="comments.php" class="nav-link<?= $currentNav === 'comments' ? ' active' : '' ?>">
                <i class="bi bi-chat-quote"></i><span>Commentaires</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.attachments.view')): ?>
            <a href="attachments.php" class="nav-link<?= $currentNav === 'attachments' ? ' active' : '' ?>">
                <i class="bi bi-paperclip"></i><span>Pièces jointes</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.communication_history.view')): ?>
            <a href="communication_history.php" class="nav-link<?= $currentNav === 'communication_history' ? ' active' : '' ?>">
                <i class="bi bi-clock-history"></i><span>Historique</span>
            </a>
            <?php endif; ?>

            <div class="admin-sidebar-label">Gestion</div>
            <?php if (has_permission('admin.documents.view')): ?>
            <a href="documents.php" class="nav-link<?= $currentNav === 'documents' ? ' active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i><span>Documents</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.documents.view')): ?>
            <a href="responsibility.php" class="nav-link<?= $currentNav === 'responsibility' ? ' active' : '' ?>">
                <i class="bi bi-card-text"></i><span>Responsabilité</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.documents.view')): ?>
            <a href="reports_finances.php" class="nav-link<?= $currentNav === 'reports_finances' ? ' active' : '' ?>">
                <i class="bi bi-graph-up-arrow"></i><span>Rapports et finances</span>
            </a>
            <?php endif; ?>
            <?php if (has_permission('admin.planning.view')): ?>
            <a href="planning.php" class="nav-link<?= $currentNav === 'planning' ? ' active' : '' ?>">
                <i class="bi bi-calendar3"></i><span>Planning & KPI</span>
            </a>
            <?php endif; ?>

            <div class="admin-sidebar-label">Système</div>
            <?php if (has_permission('admin.dashboard')): ?>
            <a href="#" class="nav-link<?= $currentNav === 'settings' ? ' active' : '' ?>">
                <i class="bi bi-gear"></i><span>Paramètres</span>
            </a>
            <?php endif; ?>
        </nav>
        <div class="admin-sidebar-footer">
            <?php if (!empty($_SESSION['admin_email'])): ?>
                <div class="sidebar-user">
                    <i class="bi bi-person-circle"></i>
                    <span><?= htmlspecialchars($_SESSION['admin_email']) ?></span>
                </div>
            <?php endif; ?>
            <a href="../" class="mt-1"><i class="bi bi-box-arrow-left"></i><span>Retour au site</span></a>
            <a href="logout.php" class="mt-1" style="color:rgba(224,60,49,.85)" title="Se déconnecter">
                <i class="bi bi-power"></i><span>Déconnexion</span>
            </a>
        </div>
    </aside>

    <main class="admin-main">

</body>

</html>
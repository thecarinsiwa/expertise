<?php
/**
 * Page de connexion – Administration Expertise
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Déjà connecté → tableau de bord
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

/* ──────────────────────────────────────────────
   Connexion DB (config centralisée, voir config/database.php)
────────────────────────────────────────────── */
require_once __DIR__ . '/inc/db.php';

/* ──────────────────────────────────────────────
   Traitement du formulaire
────────────────────────────────────────────── */
$error = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!$pdo) {
        $error = 'Impossible de contacter la base de données.';
    } else {
        // Cherche l'utilisateur et son rôle via la table user_roles
        $stmt = $pdo->prepare("
            SELECT u.id, u.email, u.password_hash, r.code AS role
            FROM `user` u
            LEFT JOIN `user_role` ur ON ur.user_id = u.id
            LEFT JOIN `role`      r  ON r.id = ur.role_id AND r.is_system = 1
            WHERE u.email = ? AND u.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user->password_hash)) {
            if (!in_array($user->role ?? '', ['admin', 'superadmin'], true)) {
                $error = 'Accès réservé aux administrateurs.';
            } else {
                // Régénérer l'ID de session pour éviter la fixation
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user->id;
                $_SESSION['admin_email'] = $user->email;
                $_SESSION['admin_role'] = $user->role;

                // Redirection sécurisée (même domaine uniquement)
                $safe = filter_var($redirect, FILTER_VALIDATE_URL) === false
                    ? $redirect
                    : 'index.php';
                header('Location: ' . $safe);
                exit;
            }
        } else {
            // Délai anti-brute-force minimal
            usleep(300_000);
            $error = 'Adresse e-mail ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion – Administration Expertise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --c-bg: #f0f2f5;
            --c-card: #ffffff;
            --c-dark: #1D1C3E;
            --c-accent: #FFC107;
            --c-accent-h: #FFB300;
            --c-muted: #6c757d;
            --c-font: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: var(--c-font);
            background: var(--c-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            margin: 0;
        }

        /* ── Carte centrale ── */
        .login-card {
            background: var(--c-card);
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, .10);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            animation: fadeUp .35s ease both;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Bandeau supérieur ── */
        .login-top {
            background: var(--c-dark);
            padding: 2.25rem 2rem 1.75rem;
            text-align: center;
            color: #fff;
        }

        .login-top .brand-icon {
            width: 56px;
            height: 56px;
            background: rgba(224, 60, 49, .18);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: var(--c-accent);
            margin-bottom: 1rem;
        }

        .login-top h1 {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0 0 .25rem;
            letter-spacing: .02em;
        }

        .login-top p {
            font-size: .85rem;
            color: rgba(255, 255, 255, .65);
            margin: 0;
        }

        /* ── Corps formulaire ── */
        .login-body {
            padding: 2rem;
        }

        .form-label {
            font-size: .82rem;
            font-weight: 600;
            color: var(--c-dark);
            margin-bottom: .4rem;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .input-group-icon {
            position: relative;
        }

        .input-group-icon i {
            position: absolute;
            left: .875rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--c-muted);
            font-size: 1rem;
            pointer-events: none;
            z-index: 5;
        }

        .input-group-icon input {
            padding-left: 2.5rem;
        }

        .input-group-icon .toggle-pwd {
            position: absolute;
            right: .875rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--c-muted);
            z-index: 5;
            background: none;
            border: none;
            padding: 0;
            font-size: 1rem;
            transition: color .2s;
        }

        .input-group-icon .toggle-pwd:hover {
            color: var(--c-dark);
        }

        .form-control {
            border-radius: 10px;
            border: 1.5px solid #dde1e7;
            font-size: .93rem;
            padding: .65rem 1rem;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus {
            border-color: var(--c-dark);
            box-shadow: 0 0 0 3px rgba(29, 28, 62, .10);
        }

        .form-control.is-invalid {
            border-color: var(--c-accent);
            box-shadow: none;
        }

        /* ── Alerte erreur ── */
        .login-alert {
            display: flex;
            align-items: center;
            gap: .6rem;
            background: rgba(224, 60, 49, .08);
            border: 1.5px solid rgba(224, 60, 49, .25);
            border-radius: 10px;
            color: #b02a21;
            font-size: .88rem;
            font-weight: 500;
            padding: .75rem 1rem;
            margin-bottom: 1.25rem;
            animation: shake .35s ease;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .login-alert i {
            font-size: 1.05rem;
            flex-shrink: 0;
        }

        /* ── Bouton ── */
        .btn-login {
            background: var(--c-accent);
            color: var(--c-dark);
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: .95rem;
            padding: .75rem 1.5rem;
            width: 100%;
            transition: background .2s, transform .15s, box-shadow .2s;
            letter-spacing: .02em;
        }

        .btn-login:hover {
            background: var(--c-accent-h);
            color: var(--c-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(255, 193, 7, .30);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* ── Pied de carte ── */
        .login-footer {
            padding: 1.25rem 2rem;
            border-top: 1px solid #f0f0f0;
            text-align: center;
            font-size: .82rem;
            color: var(--c-muted);
        }

        .login-footer a {
            color: var(--c-dark);
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer a:hover {
            color: var(--c-accent);
        }

        /* ── Pied de page global ── */
        .page-footer {
            margin-top: 1.75rem;
            font-size: .78rem;
            color: var(--c-muted);
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="login-card">

        <!-- Bandeau -->
        <div class="login-top">
            <div class="brand-icon"><i class="bi bi-shield-lock-fill"></i></div>
            <h1>Administration</h1>
            <p>Connectez-vous pour accéder au back-office</p>
        </div>

        <!-- Formulaire -->
        <div class="login-body">

            <?php if ($error): ?>
                <div class="login-alert" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php elseif (isset($_GET['logout'])): ?>
                <div class="login-alert" role="alert"
                    style="background:rgba(25,135,84,.08);border-color:rgba(25,135,84,.25);color:#146c43;">
                    <i class="bi bi-check-circle-fill"></i>
                    Vous avez été déconnecté avec succès.
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate id="loginForm">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                <!-- E-mail -->
                <div class="mb-3">
                    <label for="email" class="form-label">Adresse e-mail</label>
                    <div class="input-group-icon">
                        <i class="bi bi-envelope"></i>
                        <input type="email" id="email" name="email"
                            class="form-control<?= $error ? ' is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="admin@exemple.fr"
                            autocomplete="email" required autofocus>
                    </div>
                </div>

                <!-- Mot de passe -->
                <div class="mb-4">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="input-group-icon">
                        <i class="bi bi-lock"></i>
                        <input type="password" id="password" name="password"
                            class="form-control<?= $error ? ' is-invalid' : '' ?>" placeholder="••••••••"
                            autocomplete="current-password" required>
                        <button type="button" class="toggle-pwd" id="togglePwd"
                            aria-label="Afficher/masquer le mot de passe">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="submitBtn">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                </button>
            </form>
        </div>

        <!-- Pied -->
        <div class="login-footer">
            <a href="forgot-password.php"><i class="bi bi-key me-1"></i>Mot de passe oublié ?</a>
            <span class="mx-2" style="color:#dee2e6;">|</span>
            <a href="register.php"><i class="bi bi-person-plus me-1"></i>Créer un compte</a>
            <span class="mx-2" style="color:#dee2e6;">|</span>
            <a href="../" class="d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left"></i> Retour au site
            </a>
        </div>
    </div>

    <p class="page-footer">&copy;
        <?= date('Y') ?> Expertise &middot; Administration
    </p>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle visibilité mot de passe
        const toggleBtn = document.getElementById('togglePwd');
        const pwdInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        toggleBtn.addEventListener('click', () => {
            const isText = pwdInput.type === 'text';
            pwdInput.type = isText ? 'password' : 'text';
            eyeIcon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
        });

        // Désactiver le bouton pendant la soumission (anti double-clic)
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Connexion…';
        });
    </script>
</body>

</html>
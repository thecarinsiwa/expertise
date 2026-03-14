<?php
/**
 * Mot de passe oublié – Administration Expertise
 * Génère un token de réinitialisation stocké en DB et simulé par affichage (sans SMTP).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Déjà connecté → tableau de bord
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

/* ── DB (config centralisée, voir config/database.php) ── */
require_once __DIR__ . '/inc/db.php';
if ($pdo) {
    // Assure l'existence de la table password_reset_tokens
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
            `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id`    INT UNSIGNED NOT NULL,
            `token`      VARCHAR(64)  NOT NULL,
            `used`       TINYINT(1)   NOT NULL DEFAULT 0,
            `expires_at` DATETIME     NOT NULL,
            `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_prt_token` (`token`),
            KEY `idx_prt_user` (`user_id`),
            CONSTRAINT `fk_prt_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

/* ── Étapes : 1=saisie email | 2=saisie nouveau mdp | 3=succès ── */
$step = 1;
$error = '';
$success = '';
$token = '';   // token affiché (dev) ou passé via URL

/* ─────────────────────────────────────────────────
   ÉTAPE 2 : token présent en GET → formulaire reset
───────────────────────────────────────────────── */
if (isset($_GET['token']) && !isset($_POST['email'])) {
    $rawToken = trim($_GET['token']);
    $validToken = null;

    if ($pdo && $rawToken !== '') {
        $chk = $pdo->prepare("
            SELECT prt.id, prt.user_id, prt.token
            FROM `password_reset_tokens` prt
            WHERE prt.token = ?
              AND prt.used = 0
              AND prt.expires_at > NOW()
            LIMIT 1
        ");
        $chk->execute([$rawToken]);
        $validToken = $chk->fetch();
    }

    if (!$validToken) {
        $error = 'Ce lien de réinitialisation est invalide ou a expiré.';
    } else {
        $step = 2;
        $token = $rawToken;
    }
}

/* ─────────────────────────────────────────────────
   POST ÉTAPE 1 : envoi e-mail
───────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Veuillez saisir votre adresse e-mail.';
    } elseif (!$pdo) {
        $error = 'Impossible de contacter la base de données.';
    } else {
        // Toujours afficher le même message (sécurité anti-enumération)
        $success = 'Si cette adresse est enregistrée, un lien de réinitialisation a été généré.';

        $stmt = $pdo->prepare("SELECT id FROM `user` WHERE email = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Supprimer les anciens tokens non utilisés pour ce user
            $pdo->prepare("DELETE FROM `password_reset_tokens` WHERE user_id = ? AND used = 0")->execute([$user->id]);

            // Générer un token sécurisé
            $rawToken = bin2hex(random_bytes(32)); // 64 chars hex
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1h

            $ins = $pdo->prepare("INSERT INTO `password_reset_tokens` (user_id, token, expires_at) VALUES (?, ?, ?)");
            $ins->execute([$user->id, $rawToken, $expires]);

            /*
             * En production, envoyer le lien par e-mail ici.
             * Pour le développement, on l'affiche directement.
             */
            $resetLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST']
                . dirname($_SERVER['SCRIPT_NAME']) . '/forgot-password.php?token=' . $rawToken;

            $success .= '<br><small class="d-block mt-2 text-muted"><strong>Mode dev</strong> – Lien de réinitialisation :'
                . ' <a href="' . htmlspecialchars($resetLink) . '" style="word-break:break-all">' . htmlspecialchars($resetLink) . '</a></small>';
        }
        $step = 3;
    }
}

/* ─────────────────────────────────────────────────
   POST ÉTAPE 2 : sauvegarder le nouveau mot de passe
───────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $rawToken = trim($_POST['token']);
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if ($password === '' || $confirm === '') {
        $error = 'Veuillez remplir tous les champs.';
        $step = 2;
        $token = $rawToken;
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
        $step = 2;
        $token = $rawToken;
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Le mot de passe doit contenir au moins une majuscule et un chiffre.';
        $step = 2;
        $token = $rawToken;
    } elseif ($password !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
        $step = 2;
        $token = $rawToken;
    } elseif (!$pdo) {
        $error = 'Impossible de contacter la base de données.';
        $step = 2;
        $token = $rawToken;
    } else {
        $chk = $pdo->prepare("
            SELECT id, user_id FROM `password_reset_tokens`
            WHERE token = ? AND used = 0 AND expires_at > NOW()
            LIMIT 1
        ");
        $chk->execute([$rawToken]);
        $tokenRow = $chk->fetch();

        if (!$tokenRow) {
            $error = 'Lien expiré ou déjà utilisé. Recommencez.';
            $step = 1;
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE `user` SET password_hash = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$hash, $tokenRow->user_id]);
            $pdo->prepare("UPDATE `password_reset_tokens` SET used = 1 WHERE id = ?")
                ->execute([$tokenRow->id]);

            $success = 'Mot de passe mis à jour avec succès !';
            $step = 3;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié – Administration Expertise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --c-bg: #f0f2f5;
            --c-card: #fff;
            --c-dark: #1D1C3E;
            --c-accent: #E03C31;
            --c-accent-h: #c23128;
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

        .auth-card {
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

        .auth-top {
            background: var(--c-dark);
            padding: 2.25rem 2rem 1.75rem;
            text-align: center;
            color: #fff;
        }

        .auth-top .brand-icon {
            width: 56px;
            height: 56px;
            background: rgba(224, 60, 49, .18);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: #E03C31;
            margin-bottom: 1rem;
        }

        .auth-top h1 {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0 0 .25rem;
        }

        .auth-top p {
            font-size: .83rem;
            color: rgba(255, 255, 255, .6);
            margin: 0;
        }

        /* Stepper */
        .stepper {
            display: flex;
            justify-content: center;
            gap: 0;
            margin-top: 1.25rem;
        }

        .step-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .25);
            margin: 0 4px;
            transition: background .3s, transform .3s;
        }

        .step-dot.active {
            background: #E03C31;
            transform: scale(1.3);
        }

        .step-dot.done {
            background: rgba(255, 255, 255, .6);
        }

        .auth-body {
            padding: 2rem;
        }

        .form-label {
            font-size: .8rem;
            font-weight: 600;
            color: var(--c-dark);
            margin-bottom: .35rem;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .input-group-icon {
            position: relative;
        }

        .input-group-icon>i.icon-left {
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

        .toggle-pwd {
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

        .toggle-pwd:hover {
            color: var(--c-dark);
        }

        .form-control {
            border-radius: 10px;
            border: 1.5px solid #dde1e7;
            font-size: .90rem;
            padding: .65rem 1rem;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus {
            border-color: var(--c-dark);
            box-shadow: 0 0 0 3px rgba(29, 28, 62, .09);
        }

        .form-control.is-invalid {
            border-color: var(--c-accent);
            box-shadow: none;
        }

        /* Force MDP */
        .pwd-strength {
            display: flex;
            gap: 4px;
            margin-top: 6px;
        }

        .pwd-strength span {
            height: 4px;
            flex: 1;
            border-radius: 2px;
            background: #e0e0e0;
            transition: background .3s;
        }

        .pwd-strength.s1 span:nth-child(1) {
            background: #E03C31;
        }

        .pwd-strength.s2 span:nth-child(-n+2) {
            background: #fd7e14;
        }

        .pwd-strength.s3 span:nth-child(-n+3) {
            background: #ffc107;
        }

        .pwd-strength.s4 span {
            background: #198754;
        }

        .pwd-hint {
            font-size: .75rem;
            color: var(--c-muted);
            margin-top: 4px;
        }

        /* Alertes */
        .auth-alert {
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            border-radius: 10px;
            font-size: .87rem;
            font-weight: 500;
            padding: .75rem 1rem;
            margin-bottom: 1.25rem;
        }

        .auth-alert.danger {
            background: rgba(224, 60, 49, .08);
            border: 1.5px solid rgba(224, 60, 49, .25);
            color: #b02a21;
            animation: shake .35s ease;
        }

        .auth-alert.success {
            background: rgba(25, 135, 84, .08);
            border: 1.5px solid rgba(25, 135, 84, .25);
            color: #146c43;
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

        .auth-alert i {
            font-size: 1.05rem;
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* Bouton */
        .btn-auth {
            background: var(--c-accent);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: .95rem;
            padding: .75rem 1.5rem;
            width: 100%;
            transition: background .2s, transform .15s, box-shadow .2s;
            letter-spacing: .02em;
        }

        .btn-auth:hover {
            background: var(--c-accent-h);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(224, 60, 49, .30);
        }

        .btn-auth:active {
            transform: translateY(0);
        }

        /* Success state */
        .success-icon {
            width: 72px;
            height: 72px;
            background: rgba(25, 135, 84, .1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #198754;
            margin: 0 auto 1.25rem;
        }

        .auth-footer {
            padding: 1.25rem 2rem;
            border-top: 1px solid #f0f0f0;
            text-align: center;
            font-size: .82rem;
            color: var(--c-muted);
        }

        .auth-footer a {
            color: var(--c-dark);
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            color: var(--c-accent);
        }

        .page-footer {
            margin-top: 1.75rem;
            font-size: .78rem;
            color: var(--c-muted);
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="auth-card">

        <!-- En-tête -->
        <div class="auth-top">
            <div class="brand-icon">
                <?php if ($step === 3 && $success): ?>
                    <i class="bi bi-check-lg"></i>
                <?php elseif ($step === 2): ?>
                    <i class="bi bi-key-fill"></i>
                <?php else: ?>
                    <i class="bi bi-envelope-open-fill"></i>
                <?php endif; ?>
            </div>
            <h1>
                <?php if ($step === 3 && $success): ?>
                    Terminé !
                <?php elseif ($step === 2): ?>
                    Nouveau mot de passe
                <?php else: ?>
                    Mot de passe oublié ?
                <?php endif; ?>
            </h1>
            <p>
                <?php if ($step === 3 && $success && strpos($success, 'mis à jour') !== false): ?>
                    Votre mot de passe a été réinitialisé
                <?php elseif ($step === 2): ?>
                    Choisissez un nouveau mot de passe sécurisé
                <?php else: ?>
                    Saisissez votre e-mail pour recevoir un lien
                <?php endif; ?>
            </p>
            <!-- Stepper -->
            <div class="stepper">
                <div class="step-dot <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>"></div>
                <div class="step-dot <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>"></div>
                <div class="step-dot <?= $step >= 3 ? 'active' : '' ?>"></div>
            </div>
        </div>

        <!-- Corps -->
        <div class="auth-body">

            <?php if ($error): ?>
                <div class="auth-alert danger" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>
                        <?= htmlspecialchars($error) ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <!-- ─── Étape 1 : Saisir l'e-mail ─── -->
                <p style="font-size:.88rem;color:var(--c-muted);margin-bottom:1.25rem;">
                    Entrez l'adresse e-mail associée à votre compte administrateur.
                    Nous générerons un lien de réinitialisation valable <strong>1 heure</strong>.
                </p>
                <form method="POST" action="" novalidate id="forgotForm">
                    <div class="mb-4">
                        <label for="email" class="form-label">Adresse e-mail</label>
                        <div class="input-group-icon">
                            <i class="bi bi-envelope icon-left"></i>
                            <input type="email" id="email" name="email"
                                class="form-control<?= $error ? ' is-invalid' : '' ?>"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="admin@exemple.fr"
                                autocomplete="email" required autofocus>
                        </div>
                    </div>
                    <button type="submit" class="btn-auth" id="submitBtn">
                        <i class="bi bi-send me-2"></i>Envoyer le lien
                    </button>
                </form>

            <?php elseif ($step === 2): ?>
                <!-- ─── Étape 2 : Nouveau mot de passe ─── -->
                <form method="POST" action="" novalidate id="resetForm">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="mb-3">
                        <label for="password" class="form-label">Nouveau mot de passe</label>
                        <div class="input-group-icon">
                            <i class="bi bi-lock icon-left"></i>
                            <input type="password" id="password" name="password"
                                class="form-control<?= $error ? ' is-invalid' : '' ?>"
                                placeholder="Min. 8 car., 1 maj., 1 chiffre" autocomplete="new-password" required autofocus>
                            <button type="button" class="toggle-pwd" id="togglePwd1" aria-label="Afficher/masquer">
                                <i class="bi bi-eye" id="eye1"></i>
                            </button>
                        </div>
                        <div class="pwd-strength" id="pwdStrength">
                            <span></span><span></span><span></span><span></span>
                        </div>
                        <div class="pwd-hint" id="pwdHint">8 caractères minimum, 1 majuscule, 1 chiffre</div>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirm" class="form-label">Confirmer</label>
                        <div class="input-group-icon">
                            <i class="bi bi-lock-fill icon-left"></i>
                            <input type="password" id="password_confirm" name="password_confirm"
                                class="form-control<?= $error ? ' is-invalid' : '' ?>" placeholder="Répétez le mot de passe"
                                autocomplete="new-password" required>
                            <button type="button" class="toggle-pwd" id="togglePwd2" aria-label="Afficher/masquer">
                                <i class="bi bi-eye" id="eye2"></i>
                            </button>
                        </div>
                        <div class="pwd-hint" id="matchHint" style="display:none;color:var(--c-accent)">
                            <i class="bi bi-x-circle me-1"></i>Les mots de passe ne correspondent pas.
                        </div>
                    </div>

                    <button type="submit" class="btn-auth" id="submitBtn">
                        <i class="bi bi-check2-circle me-2"></i>Réinitialiser le mot de passe
                    </button>
                </form>

            <?php else: ?>
                <!-- ─── Étape 3 : Confirmation ─── -->
                <div class="text-center py-2">
                    <div class="success-icon">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <div class="auth-alert success" role="alert" style="text-align:left;">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>
                            <?= $success ?>
                        </span>
                    </div>
                    <a href="login.php" class="btn-auth d-inline-flex align-items-center justify-content-center gap-2"
                        style="text-decoration:none;">
                        <i class="bi bi-box-arrow-in-right"></i>Retour à la connexion
                    </a>
                </div>
            <?php endif; ?>

        </div>

        <!-- Pied -->
        <div class="auth-footer">
            <a href="login.php"><i class="bi bi-arrow-left me-1"></i>Retour à la connexion</a>
            <span class="mx-2">·</span>
            <a href="../"><i class="bi bi-house me-1"></i>Site public</a>
        </div>
    </div>

    <p class="page-footer">&copy;
        <?= date('Y') ?> Expertise &middot; Administration
    </p>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* Toggle password */
        function makeToggle(btnId, inputId, iconId) {
            const btn = document.getElementById(btnId);
            if (!btn) return;
            btn.addEventListener('click', () => {
                const inp = document.getElementById(inputId);
                const ico = document.getElementById(iconId);
                const isText = inp.type === 'text';
                inp.type = isText ? 'password' : 'text';
                ico.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        }
        makeToggle('togglePwd1', 'password', 'eye1');
        makeToggle('togglePwd2', 'password_confirm', 'eye2');

        /* Force du mot de passe */
        const pwdInput = document.getElementById('password');
        if (pwdInput) {
            const pwdStrength = document.getElementById('pwdStrength');
            const pwdHint = document.getElementById('pwdHint');
            const labels = ['', 'Trop faible', 'Faible', 'Moyen', 'Fort'];
            const colors = ['', '#E03C31', '#fd7e14', '#ffc107', '#198754'];

            function getStrength(p) {
                let s = 0;
                if (p.length >= 8) s++;
                if (/[A-Z]/.test(p)) s++;
                if (/[0-9]/.test(p)) s++;
                if (/[^A-Za-z0-9]/.test(p)) s++;
                return s;
            }
            pwdInput.addEventListener('input', () => {
                const s = getStrength(pwdInput.value);
                pwdStrength.className = 'pwd-strength' + (s ? ' s' + s : '');
                pwdHint.textContent = s ? labels[s] : '8 caractères minimum, 1 majuscule, 1 chiffre';
                pwdHint.style.color = s ? colors[s] : '';
            });

            const confirmInput = document.getElementById('password_confirm');
            const matchHint = document.getElementById('matchHint');
            confirmInput?.addEventListener('input', () => {
                if (!confirmInput.value) { matchHint.style.display = 'none'; return; }
                matchHint.style.display = confirmInput.value !== pwdInput.value ? 'block' : 'none';
            });
        }

        /* Anti double-clic */
        document.getElementById('forgotForm')?.addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi en cours…';
        });
        document.getElementById('resetForm')?.addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mise à jour…';
        });
    </script>
</body>

</html>
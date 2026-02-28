<?php
/**
 * Page d'inscription – Espace client Expertise
 * Inscription publique : création d'un compte avec le statut (rôle) client.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/inc/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse e-mail invalide.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Le mot de passe doit contenir au moins une majuscule et un chiffre.';
    } elseif ($password !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (!$pdo) {
        $error = 'Impossible de contacter la base de données.';
    } else {
        $check = $pdo->prepare("SELECT id FROM `user` WHERE email = ? LIMIT 1");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'Cette adresse e-mail est déjà utilisée.';
        } else {
            $stmtRole = $pdo->prepare("SELECT id FROM `role` WHERE `code` = 'client' AND `is_system` = 1 LIMIT 1");
            $stmtRole->execute();
            $roleRow = $stmtRole->fetch();
            $clientRoleId = $roleRow ? (int) $roleRow->id : null;

            if (!$clientRoleId) {
                $error = 'Le rôle client n\'est pas configuré. Veuillez contacter l\'administrateur.';
            } else {
                try {
                    $pdo->beginTransaction();

                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $ins = $pdo->prepare("
                        INSERT INTO `user` (email, password_hash, first_name, last_name, phone, is_active, email_verified_at)
                        VALUES (?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $ins->execute([$email, $hash, $firstName, $lastName, $phone ?: null]);
                    $userId = (int) $pdo->lastInsertId();

                    $ur = $pdo->prepare("INSERT INTO `user_role` (user_id, role_id) VALUES (?, ?)");
                    $ur->execute([$userId, $clientRoleId]);

                    $pdo->commit();
                    $success = 'Compte client <strong>' . htmlspecialchars($firstName . ' ' . $lastName) . '</strong> créé avec succès. Vous pouvez vous connecter.';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = 'Erreur lors de la création du compte : ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte – Expertise</title>
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
            --c-accent: #FFC107;
            --c-accent-h: #FFB300;
            --c-muted: #6c757d;
            --c-font: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: var(--c-font);
            background: var(--c-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            margin: 0;
        }
        .auth-card {
            background: var(--c-card);
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, .10);
            width: 100%;
            max-width: 520px;
            overflow: hidden;
            animation: fadeUp .35s ease both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .auth-top {
            background: var(--c-dark);
            padding: 2rem 2rem 1.75rem;
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
        .auth-top h1 { font-size: 1.3rem; font-weight: 700; margin: 0 0 .2rem; }
        .auth-top p { font-size: .83rem; color: rgba(255, 255, 255, .6); margin: 0; }
        .auth-body { padding: 2rem; }
        .form-label {
            font-size: .8rem;
            font-weight: 600;
            color: var(--c-dark);
            margin-bottom: .35rem;
            letter-spacing: .03em;
            text-transform: uppercase;
        }
        .form-label .req { color: var(--c-accent); margin-left: 2px; }
        .input-group-icon { position: relative; }
        .input-group-icon > i.icon-left {
            position: absolute;
            left: .875rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--c-muted);
            font-size: 1rem;
            pointer-events: none;
            z-index: 5;
        }
        .input-group-icon input { padding-left: 2.5rem; }
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
        .toggle-pwd:hover { color: var(--c-dark); }
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
        .form-control.is-invalid { border-color: var(--c-accent); box-shadow: none; }
        .pwd-strength { display: flex; gap: 4px; margin-top: 6px; }
        .pwd-strength span {
            height: 4px;
            flex: 1;
            border-radius: 2px;
            background: #e0e0e0;
            transition: background .3s;
        }
        .pwd-strength.s1 span:nth-child(1) { background: #E03C31; }
        .pwd-strength.s2 span:nth-child(-n+2) { background: #fd7e14; }
        .pwd-strength.s3 span:nth-child(-n+3) { background: #ffc107; }
        .pwd-strength.s4 span { background: #198754; }
        .pwd-hint { font-size: .75rem; color: var(--c-muted); margin-top: 4px; }
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
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .auth-alert i { font-size: 1.05rem; flex-shrink: 0; margin-top: 1px; }
        .btn-auth {
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
        .btn-auth:hover {
            background: var(--c-accent-h);
            color: var(--c-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(255, 193, 7, .30);
        }
        .btn-auth:active { transform: translateY(0); }
        .form-section-title {
            font-size: .7rem;
            font-weight: 700;
            color: var(--c-muted);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin: 1.25rem 0 .75rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .form-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #ebebeb;
        }
        .auth-footer {
            padding: 1.25rem 2rem;
            border-top: 1px solid #f0f0f0;
            text-align: center;
            font-size: .82rem;
            color: var(--c-muted);
        }
        .auth-footer a { color: var(--c-dark); text-decoration: none; font-weight: 600; }
        .auth-footer a:hover { color: var(--c-accent); }
        .page-footer { margin-top: 1.75rem; font-size: .78rem; color: var(--c-muted); text-align: center; }
    </style>
</head>

<body>

    <div class="auth-card">
        <div class="auth-top">
            <div class="brand-icon"><i class="bi bi-person-plus-fill"></i></div>
            <h1>Créer un compte client</h1>
            <p>Inscription à l'espace client Expertise</p>
        </div>

        <div class="auth-body">
            <?php if ($error): ?>
                <div class="auth-alert danger" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php elseif ($success): ?>
                <div class="auth-alert success" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?= $success ?> <a href="login.php">Se connecter</a></span>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
                <form method="POST" action="" novalidate id="registerForm">
                    <div class="form-section-title">Identité</div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="first_name" class="form-label">Prénom <span class="req">*</span></label>
                            <div class="input-group-icon">
                                <i class="bi bi-person icon-left"></i>
                                <input type="text" id="first_name" name="first_name"
                                    class="form-control<?= $error ? ' is-invalid' : '' ?>"
                                    value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" placeholder="Jean"
                                    autocomplete="given-name" required autofocus>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="last_name" class="form-label">Nom <span class="req">*</span></label>
                            <div class="input-group-icon">
                                <i class="bi bi-person icon-left"></i>
                                <input type="text" id="last_name" name="last_name"
                                    class="form-control<?= $error ? ' is-invalid' : '' ?>"
                                    value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" placeholder="Dupont"
                                    autocomplete="family-name" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-section-title">Contact</div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail <span class="req">*</span></label>
                        <div class="input-group-icon">
                            <i class="bi bi-envelope icon-left"></i>
                            <input type="email" id="email" name="email"
                                class="form-control<?= $error ? ' is-invalid' : '' ?>"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="vous@exemple.fr"
                                autocomplete="email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Téléphone <span style="color:var(--c-muted);font-weight:400">(optionnel)</span></label>
                        <div class="input-group-icon">
                            <i class="bi bi-telephone icon-left"></i>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+33 6 00 00 00 00"
                                autocomplete="tel">
                        </div>
                    </div>

                    <div class="form-section-title">Sécurité</div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe <span class="req">*</span></label>
                        <div class="input-group-icon">
                            <i class="bi bi-lock icon-left"></i>
                            <input type="password" id="password" name="password"
                                class="form-control<?= $error ? ' is-invalid' : '' ?>"
                                placeholder="Min. 8 car., 1 maj., 1 chiffre" autocomplete="new-password" required>
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
                        <label for="password_confirm" class="form-label">Confirmer le mot de passe <span class="req">*</span></label>
                        <div class="input-group-icon">
                            <i class="bi bi-lock-fill icon-left"></i>
                            <input type="password" id="password_confirm" name="password_confirm"
                                class="form-control<?= $error ? ' is-invalid' : '' ?>"
                                placeholder="Répétez le mot de passe" autocomplete="new-password" required>
                            <button type="button" class="toggle-pwd" id="togglePwd2" aria-label="Afficher/masquer">
                                <i class="bi bi-eye" id="eye2"></i>
                            </button>
                        </div>
                        <div class="pwd-hint" id="matchHint" style="display:none;color:var(--c-accent)">
                            <i class="bi bi-x-circle me-1"></i>Les mots de passe ne correspondent pas.
                        </div>
                    </div>

                    <button type="submit" class="btn-auth" id="submitBtn">
                        <i class="bi bi-person-check-fill me-2"></i>Créer mon compte client
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="auth-footer">
            Déjà un compte ?
            <a href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i>Se connecter</a>
            <span class="mx-2">·</span>
            <a href="../"><i class="bi bi-arrow-left me-1"></i>Retour au site</a>
        </div>
    </div>

    <p class="page-footer">&copy; <?= date('Y') ?> Expertise &middot; Espace client</p>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function makeToggle(btnId, inputId, iconId) {
            const btn = document.getElementById(btnId);
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!btn) return;
            btn.addEventListener('click', () => {
                const isText = input.type === 'text';
                input.type = isText ? 'password' : 'text';
                icon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
            });
        }
        makeToggle('togglePwd1', 'password', 'eye1');
        makeToggle('togglePwd2', 'password_confirm', 'eye2');

        const pwdInput = document.getElementById('password');
        const pwdStrength = document.getElementById('pwdStrength');
        const pwdHint = document.getElementById('pwdHint');
        function getStrength(pwd) {
            let s = 0;
            if (pwd.length >= 8) s++;
            if (/[A-Z]/.test(pwd)) s++;
            if (/[0-9]/.test(pwd)) s++;
            if (/[^A-Za-z0-9]/.test(pwd)) s++;
            return s;
        }
        const labels = ['', 'Trop faible', 'Faible', 'Moyen', 'Fort'];
        const colors = ['', '#E03C31', '#fd7e14', '#ffc107', '#198754'];
        pwdInput.addEventListener('input', () => {
            const s = getStrength(pwdInput.value);
            pwdStrength.className = 'pwd-strength' + (s ? ' s' + s : '');
            pwdHint.textContent = s ? labels[s] : '8 caractères minimum, 1 majuscule, 1 chiffre';
            pwdHint.style.color = s ? colors[s] : '';
        });

        const confirmInput = document.getElementById('password_confirm');
        const matchHint = document.getElementById('matchHint');
        confirmInput.addEventListener('input', () => {
            if (confirmInput.value === '') { matchHint.style.display = 'none'; return; }
            matchHint.style.display = confirmInput.value !== pwdInput.value ? 'block' : 'none';
        });

        document.getElementById('registerForm')?.addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Création en cours…';
        });
    </script>
</body>

</html>

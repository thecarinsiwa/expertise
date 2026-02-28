<?php
/**
 * Mon profil / Mon CV – Espace client
 * Profil complet réutilisable pour les candidatures.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';

$pageTitle = 'Mon profil – Mon espace';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : '../';

$clientId = (int) ($_SESSION['client_id'] ?? 0);
if ($clientId <= 0) {
    header('Location: index.php');
    exit;
}

$user = null;
$profile = null;
$error = '';
$success = '';

if ($pdo) {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone FROM user WHERE id = ?");
    $stmt->execute([$clientId]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);
    if (!$user) {
        header('Location: index.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, user_id, bio, job_title, skills FROM profile WHERE user_id = ?");
    $stmt->execute([$clientId]);
    $profile = $stmt->fetch(PDO::FETCH_OBJ);
    if ($profile) {
        try {
            $ext = $pdo->prepare("SELECT cv_path, experience, education FROM profile WHERE user_id = ?");
            $ext->execute([$clientId]);
            $row = $ext->fetch(PDO::FETCH_OBJ);
            if ($row) {
                $profile->cv_path = $row->cv_path ?? null;
                $profile->experience = $row->experience ?? null;
                $profile->education = $row->education ?? null;
            }
        } catch (PDOException $e) {
            $profile->cv_path = null;
            $profile->experience = null;
            $profile->education = null;
        }
    }
    if (!$profile && $pdo) {
        $profile = (object)[
            'id' => null,
            'user_id' => $clientId,
            'bio' => '',
            'job_title' => '',
            'skills' => null,
            'cv_path' => null,
            'experience' => null,
            'education' => null,
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo && $user) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '') ?: null;
    $bio = trim($_POST['bio'] ?? '') ?: null;
    $job_title = trim($_POST['job_title'] ?? '') ?: null;

    $skills_raw = trim($_POST['skills'] ?? '');
    $skills_arr = array_filter(array_map('trim', explode("\n", $skills_raw)));
    $skills_json = count($skills_arr) > 0 ? json_encode(array_values($skills_arr)) : null;

    $experience = [];
    if (!empty($_POST['experience']) && is_array($_POST['experience'])) {
        foreach ($_POST['experience'] as $exp) {
            $titre = trim($exp['titre'] ?? '');
            $entreprise = trim($exp['entreprise'] ?? '');
            $periode = trim($exp['periode'] ?? '');
            $description = trim($exp['description'] ?? '');
            if ($titre !== '' || $entreprise !== '' || $periode !== '' || $description !== '') {
                $experience[] = ['titre' => $titre, 'entreprise' => $entreprise, 'periode' => $periode, 'description' => $description];
            }
        }
    }
    $experience_json = count($experience) > 0 ? json_encode($experience) : null;

    $education = [];
    if (!empty($_POST['education']) && is_array($_POST['education'])) {
        foreach ($_POST['education'] as $edu) {
            $diplome = trim($edu['diplome'] ?? '');
            $etablissement = trim($edu['etablissement'] ?? '');
            $periode = trim($edu['periode'] ?? '');
            if ($diplome !== '' || $etablissement !== '' || $periode !== '') {
                $education[] = ['diplome' => $diplome, 'etablissement' => $etablissement, 'periode' => $periode];
            }
        }
    }
    $education_json = count($education) > 0 ? json_encode($education) : null;

    $cv_path = $profile && !empty($profile->cv_path) ? $profile->cv_path : null;
    if (!empty($_FILES['cv']['name'])) {
        $allowed = ['pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $error = 'Format de fichier non accepté. Utilisez PDF, DOC ou DOCX (max. 5 Mo).';
        } elseif ($_FILES['cv']['size'] > 5 * 1024 * 1024) {
            $error = 'Fichier trop volumineux (max. 5 Mo).';
        } else {
            $target_dir = __DIR__ . '/../uploads/offers/cv/';
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $file_name = 'profile_' . $clientId . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['cv']['tmp_name'], $target_dir . $file_name)) {
                $cv_path = 'uploads/offers/cv/' . $file_name;
            } else {
                $error = 'Impossible d\'enregistrer le fichier CV.';
            }
        }
    }

    if ($error === '' && $first_name !== '' && $last_name !== '') {
        try {
            $pdo->prepare("UPDATE user SET first_name = ?, last_name = ?, phone = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$first_name, $last_name, $phone, $clientId]);

            $has_profile_columns = true;
            try {
                $pdo->query("SELECT experience, education FROM profile LIMIT 1");
            } catch (PDOException $e) {
                $has_profile_columns = false;
            }

            if ($profile && $profile->id) {
                if ($has_profile_columns) {
                    $pdo->prepare("UPDATE profile SET bio = ?, job_title = ?, skills = ?, cv_path = ?, experience = ?, education = ?, updated_at = NOW() WHERE user_id = ?")
                        ->execute([$bio, $job_title, $skills_json, $cv_path, $experience_json, $education_json, $clientId]);
                } else {
                    $pdo->prepare("UPDATE profile SET bio = ?, job_title = ?, skills = ?, updated_at = NOW() WHERE user_id = ?")
                        ->execute([$bio, $job_title, $skills_json, $clientId]);
                }
            } else {
                if ($has_profile_columns) {
                    $pdo->prepare("INSERT INTO profile (user_id, bio, job_title, skills, cv_path, experience, education) VALUES (?, ?, ?, ?, ?, ?, ?)")
                        ->execute([$clientId, $bio, $job_title, $skills_json, $cv_path, $experience_json, $education_json]);
                } else {
                    $pdo->prepare("INSERT INTO profile (user_id, bio, job_title, skills) VALUES (?, ?, ?, ?)")
                        ->execute([$clientId, $bio, $job_title, $skills_json]);
                }
            }

            $_SESSION['client_name'] = trim($first_name . ' ' . $last_name);
            $success = 'Profil enregistré.';
            $user->first_name = $first_name;
            $user->last_name = $last_name;
            $user->phone = $phone;
            $profile = $profile ?: (object)['id' => null, 'user_id' => $clientId, 'bio' => $bio, 'job_title' => $job_title, 'skills' => $skills_json, 'cv_path' => $cv_path, 'experience' => $experience_json, 'education' => $education_json];
            $profile->bio = $bio;
            $profile->job_title = $job_title;
            $profile->skills = $skills_json;
            $profile->cv_path = $cv_path;
            $profile->experience = $experience_json;
            $profile->education = $education_json;
        } catch (PDOException $e) {
            $error = 'Erreur lors de l\'enregistrement. Vérifiez que la migration database/add_profile_cv_fields.sql a été exécutée.';
        }
    } elseif ($error === '' && ($first_name === '' || $last_name === '')) {
        $error = 'Le prénom et le nom sont obligatoires.';
    }
}

$organisation = null;
if ($pdo) {
    $stmt = $pdo->query("SELECT name, description FROM organisation WHERE is_active = 1 LIMIT 1");
    if ($row = $stmt->fetch()) $organisation = $row;
}

$experiences_data = [];
$educations_data = [];
if ($profile) {
    if (!empty($profile->experience)) {
        $dec = json_decode($profile->experience, true);
        if (is_array($dec)) $experiences_data = $dec;
    }
    if (!empty($profile->education)) {
        $dec = json_decode($profile->education, true);
        if (is_array($dec)) $educations_data = $dec;
    }
}
if (empty($experiences_data)) $experiences_data = [['titre' => '', 'entreprise' => '', 'periode' => '', 'description' => '']];
if (empty($educations_data)) $educations_data = [['diplome' => '', 'etablissement' => '', 'periode' => '']];

$skills_text = '';
if ($profile && !empty($profile->skills)) {
    $arr = json_decode($profile->skills, true);
    if (is_array($arr)) $skills_text = implode("\n", $arr);
}

require __DIR__ . '/../inc/head.php';
require __DIR__ . '/../inc/header.php';
?>

    <section class="client-dashboard py-5">
        <div class="container">
            <nav aria-label="Fil d'Ariane" class="mb-4">
                <a href="<?= htmlspecialchars($baseUrl) ?>client/" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Mon espace</a>
            </nav>

            <h1 class="section-heading mb-4"><i class="bi bi-person-badge me-2"></i>Mon profil / Mon CV</h1>
            <p class="lead text-muted mb-4">Complétez votre profil une fois et réutilisez-le pour toutes vos candidatures.</p>

            <?php if ($success): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" class="mb-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-person me-2"></i>Identité</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Prénom *</label>
                                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user->first_name ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nom *</label>
                                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user->last_name ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">E-mail</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user->email ?? '') ?>" readonly disabled>
                                <small class="text-muted">Non modifiable</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Téléphone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user->phone ?? '') ?>" placeholder="+33 6 12 34 56 78">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>CV</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Résumé / Présentation</label>
                            <textarea name="bio" class="form-control" rows="4" placeholder="Quelques lignes pour vous présenter…"><?= htmlspecialchars($profile->bio ?? '') ?></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Intitulé du poste visé</label>
                            <input type="text" name="job_title" class="form-control" value="<?= htmlspecialchars($profile->job_title ?? '') ?>" placeholder="Ex. Développeur web, Chef de projet…">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Compétences</label>
                            <textarea name="skills" class="form-control" rows="3" placeholder="Une compétence par ligne"><?= htmlspecialchars($skills_text) ?></textarea>
                            <small class="text-muted">Saisissez une compétence par ligne.</small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">CV (fichier PDF/DOC)</label>
                            <?php if (!empty($profile->cv_path)): ?>
                                <p class="small text-muted mb-2">CV actuel : <a href="<?= htmlspecialchars($baseUrl . $profile->cv_path) ?>" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i>Télécharger</a>. Envoyez un nouveau fichier pour le remplacer.</p>
                            <?php endif; ?>
                            <input type="file" name="cv" class="form-control" accept=".pdf,.doc,.docx">
                            <small class="text-muted">PDF, DOC ou DOCX — max. 5 Mo. Ce CV sera utilisé par défaut pour vos candidatures.</small>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-briefcase me-2"></i>Expériences professionnelles</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addExperience"><i class="bi bi-plus"></i> Ajouter</button>
                    </div>
                    <div class="card-body" id="experienceList">
                        <?php foreach ($experiences_data as $i => $exp): ?>
                        <div class="experience-block border rounded p-3 mb-3">
                            <div class="row g-2">
                                <div class="col-md-6"><input type="text" name="experience[<?= $i ?>][titre]" class="form-control form-control-sm" placeholder="Titre du poste" value="<?= htmlspecialchars($exp['titre'] ?? '') ?>"></div>
                                <div class="col-md-6"><input type="text" name="experience[<?= $i ?>][entreprise]" class="form-control form-control-sm" placeholder="Entreprise" value="<?= htmlspecialchars($exp['entreprise'] ?? '') ?>"></div>
                                <div class="col-12"><input type="text" name="experience[<?= $i ?>][periode]" class="form-control form-control-sm" placeholder="Période (ex. 2020 – 2023)" value="<?= htmlspecialchars($exp['periode'] ?? '') ?>"></div>
                                <div class="col-12"><textarea name="experience[<?= $i ?>][description]" class="form-control form-control-sm" rows="2" placeholder="Description"><?= htmlspecialchars($exp['description'] ?? '') ?></textarea></div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-experience"><i class="bi bi-trash"></i></button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-mortarboard me-2"></i>Formation</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addEducation"><i class="bi bi-plus"></i> Ajouter</button>
                    </div>
                    <div class="card-body" id="educationList">
                        <?php foreach ($educations_data as $i => $edu): ?>
                        <div class="education-block border rounded p-3 mb-3">
                            <div class="row g-2">
                                <div class="col-md-6"><input type="text" name="education[<?= $i ?>][diplome]" class="form-control form-control-sm" placeholder="Diplôme" value="<?= htmlspecialchars($edu['diplome'] ?? '') ?>"></div>
                                <div class="col-md-6"><input type="text" name="education[<?= $i ?>][etablissement]" class="form-control form-control-sm" placeholder="Établissement" value="<?= htmlspecialchars($edu['etablissement'] ?? '') ?>"></div>
                                <div class="col-12"><input type="text" name="education[<?= $i ?>][periode]" class="form-control form-control-sm" placeholder="Période" value="<?= htmlspecialchars($edu['periode'] ?? '') ?>"></div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-education"><i class="bi bi-trash"></i></button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-read-more"><i class="bi bi-check-lg me-2"></i>Enregistrer mon profil</button>
                    <a href="<?= htmlspecialchars($baseUrl) ?>client/" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </section>

    <script>
    (function() {
        var expIndex = <?= count($experiences_data) ?>;
        var eduIndex = <?= count($educations_data) ?>;

        document.getElementById('addExperience').addEventListener('click', function() {
            var tpl = '<div class="experience-block border rounded p-3 mb-3">' +
                '<div class="row g-2"><div class="col-md-6"><input type="text" name="experience[' + expIndex + '][titre]" class="form-control form-control-sm" placeholder="Titre du poste"></div>' +
                '<div class="col-md-6"><input type="text" name="experience[' + expIndex + '][entreprise]" class="form-control form-control-sm" placeholder="Entreprise"></div>' +
                '<div class="col-12"><input type="text" name="experience[' + expIndex + '][periode]" class="form-control form-control-sm" placeholder="Période"></div>' +
                '<div class="col-12"><textarea name="experience[' + expIndex + '][description]" class="form-control form-control-sm" rows="2" placeholder="Description"></textarea></div></div>' +
                '<button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-experience"><i class="bi bi-trash"></i></button></div>';
            document.getElementById('experienceList').insertAdjacentHTML('beforeend', tpl);
            expIndex++;
        });
        document.getElementById('experienceList').addEventListener('click', function(e) {
            if (e.target.closest('.remove-experience')) e.target.closest('.experience-block').remove();
        });

        document.getElementById('addEducation').addEventListener('click', function() {
            var tpl = '<div class="education-block border rounded p-3 mb-3">' +
                '<div class="row g-2"><div class="col-md-6"><input type="text" name="education[' + eduIndex + '][diplome]" class="form-control form-control-sm" placeholder="Diplôme"></div>' +
                '<div class="col-md-6"><input type="text" name="education[' + eduIndex + '][etablissement]" class="form-control form-control-sm" placeholder="Établissement"></div>' +
                '<div class="col-12"><input type="text" name="education[' + eduIndex + '][periode]" class="form-control form-control-sm" placeholder="Période"></div></div>' +
                '<button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-education"><i class="bi bi-trash"></i></button></div>';
            document.getElementById('educationList').insertAdjacentHTML('beforeend', tpl);
            eduIndex++;
        });
        document.getElementById('educationList').addEventListener('click', function(e) {
            if (e.target.closest('.remove-education')) e.target.closest('.education-block').remove();
        });
    })();
    </script>

<?php require __DIR__ . '/../inc/footer.php'; ?>

<?php
/**
 * Gestion des Types de Missions
 */
require_once __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/db.php';

$pageTitle = 'Types de Mission – Administration';
$currentNav = 'missions';

$error = '';
$success = '';

// --- GESTION DES ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    if (isset($_POST['save_type'])) {
        $name = trim($_POST['name'] ?? '');
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (empty($name)) {
            $error = "Le nom est obligatoire.";
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE mission_type SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);
                $success = "Type mis à jour.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO mission_type (name) VALUES (?)");
                $stmt->execute([$name]);
                $success = "Type créé.";
            }
        }
    }

    if (isset($_POST['delete_id'])) {
        $delId = (int) $_POST['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM mission_type WHERE id = ?");
        try {
            $stmt->execute([$delId]);
            $success = "Type supprimé.";
        } catch (Exception $e) {
            $error = "Impossible de supprimer ce type (il est probablement utilisé par des missions).";
        }
    }
}

// --- RÉCUPÉRATION DES DONNÉES ---
$types = $pdo->query("SELECT * FROM mission_type ORDER BY name ASC")->fetchAll();

require __DIR__ . '/inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 fw-bold">Types de Mission</h1>
        <p class="text-muted small">Configurez les catégories de missions.</p>
    </div>
    <button class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#typeModal" onclick="resetForm()">
        <i class="bi bi-plus-lg me-1"></i> Ajouter un type
    </button>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger small">
        <?= $error ?>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success small">
        <?= $success ?>
    </div>
<?php endif; ?>

<div class="admin-card p-0 overflow-hidden">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="ps-4 py-3" style="width: 80px;">ID</th>
                <th class="py-3">Nom du Type</th>
                <th class="pe-4 py-3 text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($types as $t): ?>
                <tr>
                    <td class="ps-4 text-muted small">
                        <?= $t->id ?>
                    </td>
                    <td class="fw-medium">
                        <?= htmlspecialchars($t->name) ?>
                    </td>
                    <td class="pe-4 text-end">
                        <button class="btn btn-sm btn-outline-admin-sidebar me-1"
                            onclick="editType(<?= $t->id ?>, '<?= addslashes($t->name) ?>')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce type ?');">
                            <input type="hidden" name="delete_id" value="<?= $t->id ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($types)): ?>
                <tr>
                    <td colspan="3" class="text-center py-4 text-muted small">Aucun type défini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-4">
    <a href="missions.php" class="btn btn-admin-outline"><i class="bi bi-arrow-left me-1"></i> Retour aux missions</a>
</div>

<!-- Modal Type -->
<div class="modal fade" id="typeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalTitle">Nouveau Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="type_id" value="0">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nom du type *</label>
                        <input type="text" name="name" id="type_name" class="form-control shadow-none" required
                            placeholder="ex: Expertise technique">
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-admin-outline" data-bs-toggle="modal">Annuler</button>
                    <button type="submit" name="save_type" class="btn btn-admin-primary px-4">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function resetForm() {
        document.getElementById('type_id').value = 0;
        document.getElementById('type_name').value = '';
        document.getElementById('modalTitle').innerText = 'Nouveau Type';
    }

    function editType(id, name) {
        document.getElementById('type_id').value = id;
        document.getElementById('type_name').value = name;
        document.getElementById('modalTitle').innerText = 'Modifier le Type';
        var myModal = new bootstrap.Modal(document.getElementById('typeModal'));
        myModal.show();
    }
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
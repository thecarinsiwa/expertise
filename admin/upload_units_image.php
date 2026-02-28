<?php
/**
 * Upload d'images pour le contenu WYSIWYG (Summernote) des Départements, Services et Unités.
 * Retourne l'URL relative pour insertion dans l'éditeur (depuis l'admin : ../uploads/units/editor/...).
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/inc/auth.php';
require_permission('admin.units.modify');

$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$file = $_FILES['file'] ?? $_FILES['image'] ?? null;
if (!$file || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Aucun fichier ou erreur d\'upload']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    echo json_encode(['error' => 'Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.']);
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!in_array($mime, $allowed, true)) {
    echo json_encode(['error' => 'Type MIME non autorisé']);
    exit;
}

$targetDir = __DIR__ . '/../uploads/units/editor/';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$fileName = 'editor_' . date('Ymd_His') . '_' . substr(uniqid(), -6) . '.' . $ext;
$targetPath = $targetDir . $fileName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode(['error' => 'Impossible d\'enregistrer le fichier']);
    exit;
}

echo json_encode(['url' => '../uploads/units/editor/' . $fileName]);

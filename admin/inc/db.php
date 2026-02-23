<?php
/**
 * Connexion DB partagée – administration
 * Charge la config depuis config/database.php (dynamique : env ou défauts).
 */
if (!isset($dbConfig)) {
    require_once __DIR__ . '/../../config/database.php';
}
$pdo = null;
try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $dbConfig['host'],
        $dbConfig['dbname'],
        $dbConfig['charset']
    );
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    ]);
} catch (PDOException $e) {
    $pdo = null;
}

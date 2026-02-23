<?php
/**
 * Connexion DB partagée admin
 */
$dbConfig = [
    'host'   => 'localhost',
    'dbname' => 'expertise',
    'user'   => 'root',
    'pass'   => '',
    'charset'=> 'utf8mb4',
];
$pdo = null;
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['dbname'], $dbConfig['charset']);
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    ]);
} catch (PDOException $e) {
    $pdo = null;
}

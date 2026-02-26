<?php
/**
 * Run RBAC permissions migration (001)
 * Usage: php run_rbac_migration.php
 */
require __DIR__ . '/config/database.php';
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $dbConfig['host'], $dbConfig['dbname'], $dbConfig['charset']);
$pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$sql = file_get_contents(__DIR__ . '/database/migrations/001_rbac_permissions.sql');
$pdo->exec($sql);
echo "Migration 001_rbac_permissions applied.\n";

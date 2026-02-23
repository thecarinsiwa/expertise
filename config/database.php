<?php
/**
 * Configuration base de données – chargée dynamiquement (variables d'environnement ou valeurs par défaut).
 * Utilisée par la partie client (inc/db.php) et l'administration (admin/inc/db.php).
 *
 * Variables d'environnement optionnelles : DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET
 */
$dbConfig = [
    'host'    => getenv('DB_HOST')    ?: 'localhost',
    'dbname'  => getenv('DB_NAME')    ?: 'expertise',
    'user'    => getenv('DB_USER')    ?: 'root',
    'pass'    => getenv('DB_PASS')    ?: '',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
];

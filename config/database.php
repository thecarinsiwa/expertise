<?php
/**
 * Configuration base de données – chargée depuis .env ou variables d'environnement.
 * Utilisée par la partie client (inc/db.php) et l'administration (admin/inc/db.php).
 *
 * Créez un fichier .env à la racine du projet (copier .env.example) avec :
 * DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET, DB_PORT
 */
require_once __DIR__ . '/load_env.php';

$dbConfig = [
    'host'    => getenv('DB_HOST')    ?: 'localhost',
    'dbname'  => getenv('DB_NAME')    ?: 'expertise',
    'user'    => getenv('DB_USER')    ?: 'root',
    'pass'    => getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'Baraka@1234',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
];

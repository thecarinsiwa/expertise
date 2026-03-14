<?php
/**
 * Charge les variables du fichier .env dans l'environnement.
 * Le fichier .env doit se trouver à la racine du projet.
 * Les variables déjà définies ne sont pas écrasées.
 */
$envFile = dirname(__DIR__) . '/.env';
if (!is_file($envFile) || !is_readable($envFile)) {
    return;
}
$lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!is_array($lines)) {
    return;
}
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, '#') === 0) {
        continue;
    }
    if (strpos($line, '=') === false) {
        continue;
    }
    list($key, $value) = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    if ($key === '') {
        continue;
    }
    if (preg_match('/^(["\'])(.*)\\1$/', $value, $m)) {
        $value = str_replace(['\\' . $m[1], '\\n', '\\r'], [$m[1], "\n", "\r"], $m[2]);
    }
    if (!getenv($key)) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

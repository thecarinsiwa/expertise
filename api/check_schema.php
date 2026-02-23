<?php
require_once __DIR__ . '/../admin/inc/db.php';
$stmt = $pdo->query("SHOW TABLES");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

$tables = ['mission_order', 'mission_report', 'mission_expense'];
foreach ($tables as $t) {
    try {
        echo "\nTable: $t\n";
        $stmt = $pdo->query("DESCRIBE $t");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo "Error describe $t: " . $e->getMessage() . "\n";
    }
}

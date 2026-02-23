<?php
require_once __DIR__ . '/../admin/inc/db.php';
$stmt = $pdo->query("SELECT mission_id, summary FROM mission_report");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt = $pdo->query("SELECT mission_id, amount FROM mission_expense");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

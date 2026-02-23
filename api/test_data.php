<?php
require_once __DIR__ . '/../admin/inc/db.php';

try {
    // Get all mission IDs
    $missions = $pdo->query("SELECT id FROM mission")->fetchAll(PDO::FETCH_ASSOC);

    // Get a valid user ID
    $user_id = $pdo->query("SELECT id FROM user LIMIT 1")->fetchColumn();
    if (!$user_id) {
        die("Aucun utilisateur dans la base. Veuillez d'abord vous inscrire.");
    }

    foreach ($missions as $m) {
        $mission_id = $m['id'];

        // Insert a Test Order if not exists
        $stmt = $pdo->prepare("SELECT id FROM mission_order WHERE mission_id = ?");
        $stmt->execute([$mission_id]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO mission_order (mission_id, order_number, issue_date, status) VALUES (?, ?, ?, ?)")
                ->execute([$mission_id, 'OM-2026-' . str_pad($mission_id, 4, '0', STR_PAD_LEFT), date('Y-m-d'), 'draft']);
        }

        // Insert a Test Report if not exists
        $stmt = $pdo->prepare("SELECT id FROM mission_report WHERE mission_id = ?");
        $stmt->execute([$mission_id]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO mission_report (mission_id, author_user_id, report_date, summary, content, status) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$mission_id, $user_id, date('Y-m-d'), 'Premier rapport de terrain.', 'Tout se déroule normalement pour le moment.', 'draft']);
        }

        // Insert a Test Expense if not exists
        $stmt = $pdo->prepare("SELECT id FROM mission_expense WHERE mission_id = ?");
        $stmt->execute([$mission_id]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO mission_expense (mission_id, user_id, description, amount, expense_date, category) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$mission_id, $user_id, 'Frais de transport initiaux', 12500, date('Y-m-d'), 'Transport']);
        }

        // Insert a Test Planning Step if not exists
        $stmt = $pdo->prepare("SELECT id FROM mission_plan WHERE mission_id = ?");
        $stmt->execute([$mission_id]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO mission_plan (mission_id, title, content, sequence) VALUES (?, ?, ?, ?)")
                ->execute([$mission_id, 'Phase d\'initialisation', 'Mise en place des ressources et contact avec les partenaires locaux.', 1]);
            $pdo->prepare("INSERT INTO mission_plan (mission_id, title, content, sequence) VALUES (?, ?, ?, ?)")
                ->execute([$mission_id, 'Exécution terrain', 'Réalisation des enquêtes et collecte de données.', 2]);
        }
    }

    echo "Données de test injectées sur toutes les missions avec succès.";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}

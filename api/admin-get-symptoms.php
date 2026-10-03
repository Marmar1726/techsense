<?php
// ============================================================
// TechSense API — Admin Get Symptoms for Problem CRUD
// ============================================================
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
$selected_ids = isset($_GET['selected_ids']) ? explode(',', $_GET['selected_ids']) : [];

try {
    $stmt = $pdo->prepare("SELECT id, symptom_name FROM symptoms WHERE category_id = ? ORDER BY symptom_name ASC");
    $stmt->execute([$category_id]);
    $symptoms = $stmt->fetchAll();

    foreach ($symptoms as &$sym) {
        $sym['selected'] = in_array($sym['id'], $selected_ids);
    }

    echo json_encode(['symptoms' => $symptoms]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load symptoms']);
}

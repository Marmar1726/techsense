<?php
// ============================================================
// TechSense API — Get Symptoms by Category
// ============================================================
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;

if ($category_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid category ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, symptom_name FROM symptoms WHERE category_id = ? AND status = 'active' ORDER BY symptom_name ASC");
    $stmt->execute([$category_id]);
    $symptoms = $stmt->fetchAll();

    echo json_encode(['symptoms' => $symptoms]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch symptoms']);
}

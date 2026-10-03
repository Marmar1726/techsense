<?php
// ============================================================
// TechSense API — Search Knowledge Base
// ============================================================
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$q = isset($_GET['q']) ? trim(strip_tags($_GET['q'])) : '';

if (strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

try {
    $search = "%{$q}%";
    $stmt = $pdo->prepare("SELECT p.id, p.problem_name, p.difficulty, c.category_name 
                           FROM problems p 
                           JOIN categories c ON p.category_id = c.id 
                           WHERE p.status = 'active' 
                             AND (p.problem_name LIKE ? OR p.description LIKE ?) 
                           ORDER BY p.problem_name ASC 
                           LIMIT 10");
    $stmt->execute([$search, $search]);
    $results = $stmt->fetchAll();

    echo json_encode(['results' => $results]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search failed']);
}

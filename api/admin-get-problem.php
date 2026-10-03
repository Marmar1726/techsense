<?php
// ============================================================
// TechSense API — Admin Fetch Full Problem Data for Edit Modal
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

$problem_id = isset($_GET['problem_id']) ? (int)$_GET['problem_id'] : 0;

if ($problem_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid problem ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM problems WHERE id = ?");
    $stmt->execute([$problem_id]);
    $problem = $stmt->fetch();

    if (!$problem) {
        http_response_code(404);
        echo json_encode(['error' => 'Problem not found']);
        exit;
    }

    // Symptoms
    $symStmt = $pdo->prepare("SELECT symptom_id FROM problem_symptoms WHERE problem_id = ?");
    $symStmt->execute([$problem_id]);
    $symptom_ids = $symStmt->fetchAll(PDO::FETCH_COLUMN);

    // Causes
    $cStmt = $pdo->prepare("SELECT cause_description FROM causes WHERE problem_id = ? ORDER BY order_num ASC");
    $cStmt->execute([$problem_id]);
    $causes = $cStmt->fetchAll(PDO::FETCH_COLUMN);

    // Steps
    $stStmt = $pdo->prepare("SELECT step_number, instruction FROM troubleshooting_steps WHERE problem_id = ? ORDER BY step_number ASC");
    $stStmt->execute([$problem_id]);
    $steps = $stStmt->fetchAll();

    // Solution
    $solStmt = $pdo->prepare("SELECT solution_description FROM solutions WHERE problem_id = ? LIMIT 1");
    $solStmt->execute([$problem_id]);
    $solution = $solStmt->fetchColumn() ?: '';

    echo json_encode([
        'problem'     => $problem,
        'symptom_ids' => $symptom_ids,
        'causes'      => $causes,
        'steps'       => $steps,
        'solution'    => $solution,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

<?php
// ============================================================
// TechSense API — Update or Save Session Status
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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$session_id = isset($input['session_id']) ? (int)$input['session_id'] : 0;
$result     = isset($input['result']) && in_array($input['result'], ['resolved', 'unresolved', 'in_progress']) ? $input['result'] : 'in_progress';
$userId     = (int)$_SESSION['user_id'];

if ($session_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Session ID required']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE troubleshooting_sessions SET result = ?, status = 'closed' WHERE id = ? AND user_id = ?");
    $stmt->execute([$result, $session_id, $userId]);

    echo json_encode(['success' => true, 'result' => $result]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update session']);
}

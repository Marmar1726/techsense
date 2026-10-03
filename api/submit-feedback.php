<?php
// ============================================================
// TechSense API — Submit Troubleshooting Feedback
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
$rating     = isset($input['rating']) && in_array($input['rating'], ['helpful', 'partial', 'not_helpful']) ? $input['rating'] : 'helpful';
$comments   = isset($input['comments']) ? sanitize($input['comments']) : '';
$userId     = (int)$_SESSION['user_id'];

if ($session_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid Session ID required']);
    exit;
}

try {
    // Check if session exists and belongs to user
    $chk = $pdo->prepare("SELECT id FROM troubleshooting_sessions WHERE id = ? AND user_id = ?");
    $chk->execute([$session_id, $userId]);
    if (!$chk->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Session not found or access denied']);
        exit;
    }

    // Check duplicate feedback
    $dup = $pdo->prepare("SELECT id FROM feedback WHERE session_id = ?");
    $dup->execute([$session_id]);
    if ($dup->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Feedback already submitted']);
        exit;
    }

    $ins = $pdo->prepare("INSERT INTO feedback (session_id, user_id, rating, comments) VALUES (?, ?, ?, ?)");
    $ins->execute([$session_id, $userId, $rating, $comments]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save feedback']);
}

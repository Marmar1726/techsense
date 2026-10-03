<?php
// ============================================================
// TechSense API — Problem Analysis & Recommendation Engine
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

// Support both JSON payload and POST form
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$category_id = isset($input['category_id']) ? (int)$input['category_id'] : 0;
$symptom_ids = isset($input['symptom_ids']) && is_array($input['symptom_ids']) ? array_map('intval', $input['symptom_ids']) : [];
$description = isset($input['description']) ? trim(strip_tags($input['description'])) : '';

if ($category_id <= 0 || empty($symptom_ids)) {
    http_response_code(400);
    echo json_encode(['error' => 'Category and at least one symptom are required']);
    exit;
}

try {
    // 1. Fetch all active problems in this category
    $probStmt = $pdo->prepare("SELECT p.*, c.category_name 
                               FROM problems p 
                               JOIN categories c ON p.category_id = c.id 
                               WHERE p.category_id = ? AND p.status = 'active'");
    $probStmt->execute([$category_id]);
    $problems = $probStmt->fetchAll();

    if (empty($problems)) {
        echo json_encode(['matches' => []]);
        exit;
    }

    // Clean description keywords
    $descKeywords = [];
    if (!empty($description)) {
        $words = preg_split('/[\s,\.\?!;:]+/', strtolower($description));
        foreach ($words as $w) {
            if (strlen($w) >= 4) {
                $descKeywords[] = $w;
            }
        }
    }

    $ranked = [];

    foreach ($problems as $problem) {
        $problemId = (int)$problem['id'];

        // Get problem's symptoms
        $psStmt = $pdo->prepare("SELECT symptom_id FROM problem_symptoms WHERE problem_id = ?");
        $psStmt->execute([$problemId]);
        $probSymptoms = $psStmt->fetchAll(PDO::FETCH_COLUMN);

        $totalProblemSymptoms = count($probSymptoms);
        $matchedSymptomsCount = count(array_intersect($symptom_ids, $probSymptoms));

        // Base match score: percentage of problem's symptoms that match
        $baseScore = 0;
        if ($totalProblemSymptoms > 0) {
            $baseScore = ($matchedSymptomsCount / $totalProblemSymptoms) * 85;
        }

        // Additional weighting if user's selected symptoms strongly overlap
        if (!empty($symptom_ids)) {
            $coverage = ($matchedSymptomsCount / count($symptom_ids)) * 15;
            $baseScore += $coverage;
        }

        // Keyword relevance bonus (+10% if description matches title or problem description)
        $keywordBonus = 0;
        $searchCorpus = strtolower($problem['problem_name'] . ' ' . $problem['description']);
        foreach ($descKeywords as $kw) {
            if (strpos($searchCorpus, $kw) !== false) {
                $keywordBonus = 10;
                break;
            }
        }

        $finalScore = min(100, round($baseScore + $keywordBonus));

        // Fetch Causes
        $causesStmt = $pdo->prepare("SELECT cause_description FROM causes WHERE problem_id = ? ORDER BY order_num ASC");
        $causesStmt->execute([$problemId]);
        $causes = $causesStmt->fetchAll(PDO::FETCH_COLUMN);

        // Fetch Steps
        $stepsStmt = $pdo->prepare("SELECT step_number, instruction FROM troubleshooting_steps WHERE problem_id = ? ORDER BY step_number ASC");
        $stepsStmt->execute([$problemId]);
        $steps = $stepsStmt->fetchAll();

        // Fetch Solution
        $solStmt = $pdo->prepare("SELECT solution_description FROM solutions WHERE problem_id = ? LIMIT 1");
        $solStmt->execute([$problemId]);
        $solution = $solStmt->fetchColumn() ?: 'Please review the troubleshooting steps or contact a certified technician.';

        $ranked[] = [
            'problem_id'      => $problemId,
            'problem_name'    => $problem['problem_name'],
            'category_name'   => $problem['category_name'],
            'description'     => $problem['description'],
            'difficulty'      => $problem['difficulty'],
            'estimated_time'  => $problem['estimated_time'],
            'warning'         => $problem['warning'],
            'matched_symptoms'=> $matchedSymptomsCount,
            'score'           => $finalScore,
            'causes'          => $causes,
            'steps'           => $steps,
            'solution'        => $solution,
        ];
    }

    // Sort by score descending, then matched_symptoms descending
    usort($ranked, function($a, $b) {
        if ($a['score'] === $b['score']) {
            return $b['matched_symptoms'] <=> $a['matched_symptoms'];
        }
        return $b['score'] <=> $a['score'];
    });

    // Return top 3 recommendations
    $topResults = array_slice($ranked, 0, 3);

    echo json_encode(['matches' => $topResults]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Analysis engine error: ' . $e->getMessage()]);
}

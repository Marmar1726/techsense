<?php
// ============================================================
// TechSense — Troubleshooting Analysis Results
// ============================================================
$pageTitle = 'Analysis Results';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/user/troubleshoot.php');
}

$token       = $_POST['csrf_token'] ?? '';
$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$description = isset($_POST['description']) ? trim(strip_tags($_POST['description'])) : '';
$symptoms    = isset($_POST['symptoms']) && is_array($_POST['symptoms']) ? array_map('intval', $_POST['symptoms']) : [];

if (!validateCsrf($token)) {
    setFlash('danger', 'Security validation expired. Please re-run your troubleshooting query.');
    redirect(BASE_URL . '/user/troubleshoot.php');
}

if ($category_id <= 0 || empty($symptoms)) {
    setFlash('warning', 'Please select a category and at least one symptom.');
    redirect(BASE_URL . '/user/troubleshoot.php');
}

// Fetch category details
$catStmt = $pdo->prepare("SELECT category_name FROM categories WHERE id = ?");
$catStmt->execute([$category_id]);
$categoryName = $catStmt->fetchColumn() ?: 'General IT';

// Run Rule-Based Recommendation Algorithm
$rankedMatches = [];
$sessionId = 0;

try {
    $probStmt = $pdo->prepare("SELECT p.*, c.category_name 
                               FROM problems p 
                               JOIN categories c ON p.category_id = c.id 
                               WHERE p.category_id = ? AND p.status = 'active'");
    $probStmt->execute([$category_id]);
    $problems = $probStmt->fetchAll();

    $descWords = [];
    if (!empty($description)) {
        $words = preg_split('/[\s,\.\?!;:]+/', strtolower($description));
        foreach ($words as $w) {
            if (strlen($w) >= 4) $descWords[] = $w;
        }
    }

    foreach ($problems as $prob) {
        $probId = (int)$prob['id'];

        $psStmt = $pdo->prepare("SELECT symptom_id FROM problem_symptoms WHERE problem_id = ?");
        $psStmt->execute([$probId]);
        $storedSymptoms = $psStmt->fetchAll(PDO::FETCH_COLUMN);

        $totalProbSyms = count($storedSymptoms);
        $matchedSymsCount = count(array_intersect($symptoms, $storedSymptoms));

        $score = 0;
        if ($totalProbSyms > 0) {
            $score = ($matchedSymsCount / $totalProbSyms) * 80;
        }
        if (!empty($symptoms)) {
            $score += ($matchedSymsCount / count($symptoms)) * 15;
        }

        // Keyword bonus
        $searchCorpus = strtolower($prob['problem_name'] . ' ' . $prob['description']);
        foreach ($descWords as $kw) {
            if (strpos($searchCorpus, $kw) !== false) {
                $score += 10;
                break;
            }
        }
        $finalScore = min(100, max(15, round($score)));

        // Causes
        $cStmt = $pdo->prepare("SELECT cause_description FROM causes WHERE problem_id = ? ORDER BY order_num ASC");
        $cStmt->execute([$probId]);
        $causes = $cStmt->fetchAll(PDO::FETCH_COLUMN);

        // Steps
        $sStmt = $pdo->prepare("SELECT step_number, instruction FROM troubleshooting_steps WHERE problem_id = ? ORDER BY step_number ASC");
        $sStmt->execute([$probId]);
        $steps = $sStmt->fetchAll();

        // Solution
        $solStmt = $pdo->prepare("SELECT solution_description FROM solutions WHERE problem_id = ? LIMIT 1");
        $solStmt->execute([$probId]);
        $solution = $solStmt->fetchColumn() ?: 'Review physical connections and follow recommended safety diagnostics.';

        $rankedMatches[] = [
            'problem_id'       => $probId,
            'problem_name'     => $prob['problem_name'],
            'description'      => $prob['description'],
            'difficulty'       => $prob['difficulty'],
            'estimated_time'   => $prob['estimated_time'],
            'warning'          => $prob['warning'],
            'score'            => $finalScore,
            'matched_symptoms' => $matchedSymsCount,
            'causes'           => $causes,
            'steps'            => $steps,
            'solution'         => $solution,
        ];
    }

    usort($rankedMatches, function($a, $b) {
        if ($a['score'] === $b['score']) {
            return $b['matched_symptoms'] <=> $a['matched_symptoms'];
        }
        return $b['score'] <=> $a['score'];
    });

    $topMatches = array_slice($rankedMatches, 0, 3);

    // Record session in database
    $primaryProblemId = !empty($topMatches) ? $topMatches[0]['problem_id'] : null;
    $primaryScore     = !empty($topMatches) ? $topMatches[0]['score'] : 0;
    $symptomString    = implode(',', $symptoms);

    $insSess = $pdo->prepare("INSERT INTO troubleshooting_sessions 
        (user_id, problem_id, category_id, user_description, selected_symptoms, match_score, result, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'in_progress', 'active')");
    $insSess->execute([
        $userId,
        $primaryProblemId,
        $category_id,
        $description,
        $symptomString,
        $primaryScore
    ]);
    $sessionId = (int)$pdo->lastInsertId();

    // Create a notification for the user
    $notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $notif->execute([
        $userId,
        'Troubleshooting Session Logged',
        "Analysis complete for category: {$categoryName}. Diagnostic confidence: {$primaryScore}%."
    ]);

} catch (Exception $e) {
    $topMatches = [];
    $error = $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/user-sidebar.php'; ?>

  <main class="ts-content">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1">Diagnostic Analysis Results</h1>
        <div class="ts-breadcrumb">
          <a href="<?= BASE_URL ?>/user/troubleshoot.php">Troubleshooting Wizard</a> &rsaquo;
          <span>Results &amp; Recommendation</span>
        </div>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/user/troubleshoot.php" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i> New Diagnosis
        </a>
      </div>
    </div>

    <!-- Overview Bar -->
    <div class="bg-white p-3 rounded-ts border mb-4 shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="ts-logo-icon bg-navy text-ts-accent">
          <i class="bi bi-cpu-fill"></i>
        </div>
        <div>
          <div class="fw-bold text-navy"><?= e($categoryName) ?></div>
          <div class="text-muted small">Reported: &ldquo;<?= e(substr($description, 0, 80)) ?><?= strlen($description) > 80 ? '...' : '' ?>&rdquo;</div>
        </div>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <span class="badge bg-light text-secondary border px-3 py-2">
          <i class="bi bi-check2-all text-success me-1"></i> <?= count($symptoms) ?> Symptoms Cross-Referenced
        </span>
        <span class="badge bg-primary-subtle text-primary border px-3 py-2">
          Session #<?= $sessionId ?>
        </span>
      </div>
    </div>

    <?php if (!empty($topMatches)): ?>
      <div class="mb-3">
        <h5 class="fw-bold text-navy mb-3">Ranked Troubleshooting Pathways</h5>
      </div>

      <div class="row g-4 mb-4">
        <?php foreach ($topMatches as $index => $match): ?>
          <?php 
            $isTop = ($index === 0); 
            $scoreColor = $match['score'] >= 80 ? 'high' : ($match['score'] >= 60 ? 'mid' : 'low');
          ?>
          <div class="col-12">
            <div class="ts-match-card <?= $isTop ? 'top-match shadow' : 'shadow-sm' ?>">
              <div class="row g-4">
                
                <!-- Left: Score Ring & Quick Meta -->
                <div class="col-md-3 text-center border-end-md pb-3 pb-md-0 d-flex flex-column align-items-center justify-content-center">
                  <div class="ts-score-ring <?= $scoreColor ?> mb-2">
                    <span class="ts-score-pct"><?= $match['score'] ?>%</span>
                  </div>
                  <div class="fw-bold text-navy small mb-1">Diagnostic Match</div>
                  <div class="text-muted small mb-2"><?= $match['matched_symptoms'] ?> symptoms matched</div>
                  <div>
                    <?= difficultyBadge($match['difficulty']) ?>
                    <span class="badge bg-light text-dark border ms-1"><i class="bi bi-clock me-1"></i><?= e($match['estimated_time']) ?></span>
                  </div>
                </div>

                <!-- Right: Detailed Causes, Solution, & Steps Preview -->
                <div class="col-md-9">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                      <h4 class="fw-bold text-navy mb-1"><?= e($match['problem_name']) ?></h4>
                      <p class="text-muted small mb-2"><?= e($match['description']) ?></p>
                    </div>
                  </div>

                  <!-- Warning Alert -->
                  <?php if (!empty($match['warning'])): ?>
                    <div class="alert alert-warning py-2 px-3 small d-flex align-items-center gap-2 mb-3 rounded-ts" role="alert">
                      <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0"></i>
                      <div><strong>Safety Notice:</strong> <?= e($match['warning']) ?></div>
                    </div>
                  <?php endif; ?>

                  <!-- Possible Causes List -->
                  <div class="mb-3">
                    <div class="fw-semibold text-navy small mb-2"><i class="bi bi-question-circle-fill text-primary me-1"></i> Possible Causes:</div>
                    <ul class="list-unstyled row g-2 mb-0 small text-secondary">
                      <?php foreach ($match['causes'] as $cause): ?>
                        <li class="col-sm-6 d-flex align-items-start gap-2">
                          <i class="bi bi-dot fs-5 text-primary lh-1"></i>
                          <span><?= e($cause) ?></span>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>

                  <!-- Suggested Solution Preview -->
                  <div class="p-3 bg-light rounded-ts border mb-3">
                    <div class="fw-semibold text-navy small mb-1"><i class="bi bi-wrench-adjustable text-success me-1"></i> Recommended Solution:</div>
                    <div class="text-muted small"><?= e($match['solution']) ?></div>
                  </div>

                  <!-- Action Buttons -->
                  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between pt-2 border-top">
                    <span class="text-muted small">
                      <i class="bi bi-list-ol me-1"></i> <?= count($match['steps']) ?> Chronological Steps Available
                    </span>
                    <a href="<?= BASE_URL ?>/user/guide.php?session_id=<?= $sessionId ?>&problem_id=<?= $match['problem_id'] ?>" class="btn btn-ts-primary px-4">
                      <i class="bi bi-play-circle-fill me-1"></i> Follow Step-by-Step Guide &rarr;
                    </a>
                  </div>

                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <div class="ts-card text-center p-5">
        <i class="bi bi-search fs-1 text-muted d-block mb-3"></i>
        <h4 class="fw-bold text-navy">No Direct Rule Matches Found</h4>
        <p class="text-muted max-w-600 mx-auto mb-4">
          The combination of symptoms reported does not match any current troubleshooting profiles. You may want to check related categories or browse the full knowledge base.
        </p>
        <div>
          <a href="<?= BASE_URL ?>/user/troubleshoot.php" class="btn btn-outline-secondary me-2">Try Different Symptoms</a>
          <a href="<?= BASE_URL ?>/user/knowledge-base.php" class="btn btn-ts-primary">Browse Knowledge Base</a>
        </div>
      </div>
    <?php endif; ?>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
// ============================================================
// TechSense — Session History Detail View
// ============================================================
$pageTitle = 'Session Detail';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$userId = (int)$_SESSION['user_id'];
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

if ($sessionId <= 0) {
    setFlash('warning', 'Session ID required.');
    redirect(BASE_URL . '/user/history.php');
}

// Fetch session data ensuring user ownership
$stmt = $pdo->prepare("SELECT s.*, p.problem_name, p.difficulty, p.estimated_time, p.warning, c.category_name 
                       FROM troubleshooting_sessions s 
                       LEFT JOIN problems p ON s.problem_id = p.id 
                       LEFT JOIN categories c ON s.category_id = c.id 
                       WHERE s.id = ? AND s.user_id = ?");
$stmt->execute([$sessionId, $userId]);
$session = $stmt->fetch();

if (!$session) {
    setFlash('danger', 'Troubleshooting session not found.');
    redirect(BASE_URL . '/user/history.php');
}

// Fetch symptoms names
$symptomNames = [];
if (!empty($session['selected_symptoms'])) {
    $symIds = array_map('intval', explode(',', $session['selected_symptoms']));
    if (!empty($symIds)) {
        $inQuery = implode(',', array_fill(0, count($symIds), '?'));
        $sStmt = $pdo->prepare("SELECT symptom_name FROM symptoms WHERE id IN ($inQuery)");
        $sStmt->execute($symIds);
        $symptomNames = $sStmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Fetch problem causes, steps, and solution
$causes = [];
$steps = [];
$solution = '';

if (!empty($session['problem_id'])) {
    $cStmt = $pdo->prepare("SELECT cause_description FROM causes WHERE problem_id = ? ORDER BY order_num ASC");
    $cStmt->execute([$session['problem_id']]);
    $causes = $cStmt->fetchAll(PDO::FETCH_COLUMN);

    $stStmt = $pdo->prepare("SELECT step_number, instruction FROM troubleshooting_steps WHERE problem_id = ? ORDER BY step_number ASC");
    $stStmt->execute([$session['problem_id']]);
    $steps = $stStmt->fetchAll();

    $solStmt = $pdo->prepare("SELECT solution_description FROM solutions WHERE problem_id = ? LIMIT 1");
    $solStmt->execute([$session['problem_id']]);
    $solution = $solStmt->fetchColumn() ?: '';
}

// Fetch existing feedback
$fbStmt = $pdo->prepare("SELECT * FROM feedback WHERE session_id = ?");
$fbStmt->execute([$sessionId]);
$feedback = $fbStmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/user-sidebar.php'; ?>

  <main class="ts-content">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1">Diagnostic Report #<?= $session['id'] ?></h1>
        <div class="ts-breadcrumb">
          <a href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a> &rsaquo;
          <a href="<?= BASE_URL ?>/user/history.php">History</a> &rsaquo;
          <span>Session Details</span>
        </div>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/user/history.php" class="btn btn-outline-secondary btn-sm">
          &larr; Back to History
        </a>
      </div>
    </div>

    <div class="row g-4">
      
      <!-- Left Column: Session Meta & Symptoms -->
      <div class="col-lg-4">
        
        <div class="ts-card mb-4">
          <div class="ts-card-header">
            <i class="bi bi-info-circle text-primary"></i>
            <span>Session Metadata</span>
          </div>
          <div class="ts-card-body">
            <div class="mb-3">
              <div class="text-muted small">Date &amp; Time</div>
              <div class="fw-semibold text-navy"><?= formatDate($session['created_at']) ?></div>
            </div>
            <div class="mb-3">
              <div class="text-muted small">Problem Category</div>
              <div class="fw-semibold text-navy"><?= e($session['category_name'] ?: 'General') ?></div>
            </div>
            <div class="mb-3">
              <div class="text-muted small">Diagnostic Confidence</div>
              <span class="badge bg-info-subtle text-info fw-bold fs-6"><?= round($session['match_score']) ?>% Match</span>
            </div>
            <div class="mb-3">
              <div class="text-muted small">Resolution Outcome</div>
              <div><?= resultBadge($session['result']) ?></div>
            </div>
            <div>
              <div class="text-muted small">Session Status</div>
              <span class="badge bg-secondary"><?= ucfirst($session['status']) ?></span>
            </div>
          </div>
        </div>

        <!-- Symptoms Card -->
        <div class="ts-card mb-4">
          <div class="ts-card-header">
            <i class="bi bi-check2-square text-primary"></i>
            <span>Reported Symptoms</span>
          </div>
          <div class="ts-card-body">
            <?php if (!empty($symptomNames)): ?>
              <ul class="list-unstyled mb-0">
                <?php foreach ($symptomNames as $sName): ?>
                  <li class="d-flex align-items-center gap-2 mb-2 small text-navy">
                    <i class="bi bi-check-circle-fill text-ts-accent"></i>
                    <span><?= e($sName) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="text-muted small mb-0">No specific symptom chips linked.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- User Feedback Card -->
        <div class="ts-card">
          <div class="ts-card-header">
            <i class="bi bi-star-fill text-warning"></i>
            <span>User Feedback</span>
          </div>
          <div class="ts-card-body">
            <?php if ($feedback): ?>
              <div class="mb-2">
                <span class="badge <?= $feedback['rating'] === 'helpful' ? 'bg-success' : ($feedback['rating'] === 'partial' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                  Rating: <?= ucfirst($feedback['rating']) ?>
                </span>
              </div>
              <?php if (!empty($feedback['comments'])): ?>
                <div class="small text-muted bg-light p-2 rounded border">
                  &ldquo;<?= e($feedback['comments']) ?>&rdquo;
                </div>
              <?php endif; ?>
            <?php else: ?>
              <p class="text-muted small mb-3">No feedback submitted for this session yet.</p>
              <a href="<?= BASE_URL ?>/user/feedback.php?session_id=<?= $sessionId ?>" class="btn btn-sm btn-outline-primary w-100">
                Rate this Recommendation
              </a>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- Right Column: Diagnosed Problem, Steps, Solution -->
      <div class="col-lg-8">
        
        <!-- Diagnosed Problem Card -->
        <div class="ts-card mb-4">
          <div class="ts-card-header bg-navy text-white d-flex justify-content-between align-items-center">
            <div class="fw-bold">
              <i class="bi bi-shield-check text-ts-accent me-1"></i>
              Diagnosed: <?= e($session['problem_name'] ?: 'Custom Problem') ?>
            </div>
            <?php if (!empty($session['difficulty'])): ?>
              <?= difficultyBadge($session['difficulty']) ?>
            <?php endif; ?>
          </div>
          <div class="ts-card-body">
            
            <?php if (!empty($session['user_description'])): ?>
              <div class="p-3 bg-light rounded-ts border mb-3">
                <div class="text-muted small fw-semibold">User Description:</div>
                <div class="text-navy">&ldquo;<?= e($session['user_description']) ?>&rdquo;</div>
              </div>
            <?php endif; ?>

            <?php if (!empty($session['warning'])): ?>
              <div class="alert alert-warning small d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0"></i>
                <div><strong>Warning:</strong> <?= e($session['warning']) ?></div>
              </div>
            <?php endif; ?>

            <!-- Causes -->
            <?php if (!empty($causes)): ?>
              <div class="mb-4">
                <h6 class="fw-bold text-navy mb-2"><i class="bi bi-exclamation-circle text-primary me-1"></i> Root Causes Identified:</h6>
                <ul class="list-group list-group-flush border-top">
                  <?php foreach ($causes as $c): ?>
                    <li class="list-group-item px-0 py-2 small text-secondary d-flex align-items-center gap-2">
                      <i class="bi bi-arrow-right text-muted"></i> <?= e($c) ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <!-- Recommended Solution -->
            <?php if (!empty($solution)): ?>
              <div class="p-3 rounded-ts border bg-success-subtle border-success-subtle mb-4">
                <div class="fw-bold text-success small mb-1"><i class="bi bi-check-circle-fill me-1"></i> Suggested Resolution:</div>
                <div class="text-navy small"><?= e($solution) ?></div>
              </div>
            <?php endif; ?>

            <!-- Troubleshooting Steps -->
            <?php if (!empty($steps)): ?>
              <h6 class="fw-bold text-navy mb-3"><i class="bi bi-list-ol text-primary me-1"></i> Prescribed Troubleshooting Steps:</h6>
              <div class="d-flex flex-column gap-2 mb-3">
                <?php foreach ($steps as $st): ?>
                  <div class="p-3 bg-light rounded-ts border d-flex gap-3 align-items-start">
                    <span class="badge bg-primary rounded-circle px-2 py-1"><?= $st['step_number'] ?></span>
                    <span class="text-navy small"><?= e($st['instruction']) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="d-flex justify-content-end pt-3 border-top">
              <?php if (!empty($session['problem_id'])): ?>
                <a href="<?= BASE_URL ?>/user/guide.php?session_id=<?= $sessionId ?>&problem_id=<?= $session['problem_id'] ?>" class="btn btn-ts-primary">
                  <i class="bi bi-play-circle me-1"></i> Rerun Step-by-Step Guide
                </a>
              <?php endif; ?>
            </div>

          </div>
        </div>

      </div>

    </div>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
// ============================================================
// TechSense — Troubleshooting Step-by-Step Guide
// ============================================================
$pageTitle = 'Step-by-Step Guide';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$userId    = (int)$_SESSION['user_id'];
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$problemId = isset($_GET['problem_id']) ? (int)$_GET['problem_id'] : 0;

if ($sessionId <= 0 || $problemId <= 0) {
    setFlash('warning', 'Invalid session or problem selection.');
    redirect(BASE_URL . '/user/dashboard.php');
}

// Verify session ownership
$sessStmt = $pdo->prepare("SELECT * FROM troubleshooting_sessions WHERE id = ? AND user_id = ?");
$sessStmt->execute([$sessionId, $userId]);
$session = $sessStmt->fetch();

if (!$session) {
    setFlash('danger', 'Troubleshooting session not found.');
    redirect(BASE_URL . '/user/dashboard.php');
}

// Fetch problem details & ordered steps
$probStmt = $pdo->prepare("SELECT p.*, c.category_name 
                           FROM problems p 
                           JOIN categories c ON p.category_id = c.id 
                           WHERE p.id = ?");
$probStmt->execute([$problemId]);
$problem = $probStmt->fetch();

if (!$problem) {
    setFlash('danger', 'Problem guide not found.');
    redirect(BASE_URL . '/user/dashboard.php');
}

$stepsStmt = $pdo->prepare("SELECT step_number, instruction FROM troubleshooting_steps WHERE problem_id = ? ORDER BY step_number ASC");
$stepsStmt->execute([$problemId]);
$steps = $stepsStmt->fetchAll();

// Update session with this problem_id if it differed
if ($session['problem_id'] != $problemId) {
    $upd = $pdo->prepare("UPDATE troubleshooting_sessions SET problem_id = ? WHERE id = ?");
    $upd->execute([$problemId, $sessionId]);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/user-sidebar.php'; ?>

  <main class="ts-content">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1"><?= e($problem['problem_name']) ?></h1>
        <div class="ts-breadcrumb">
          <a href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a> &rsaquo;
          <a href="<?= BASE_URL ?>/user/troubleshoot.php">Troubleshoot</a> &rsaquo;
          <span>Step-by-Step Guide</span>
        </div>
      </div>
      <div>
        <span class="badge bg-light text-secondary border px-3 py-2">
          Category: <?= e($problem['category_name']) ?>
        </span>
      </div>
    </div>

    <!-- Safety Warning If Applicable -->
    <?php if (!empty($problem['warning'])): ?>
      <div class="alert alert-warning py-3 px-4 d-flex align-items-center gap-3 rounded-ts shadow-sm mb-4">
        <i class="bi bi-shield-exclamation fs-3 text-warning flex-shrink-0"></i>
        <div>
          <strong class="d-block text-navy">Important Safety Warning:</strong>
          <span class="small text-muted"><?= e($problem['warning']) ?></span>
        </div>
      </div>
    <?php endif; ?>

    <!-- The Interactive Step Controller Card -->
    <div class="ts-card p-4 max-w-800 mx-auto shadow" id="ts-step-guide" 
         data-steps='<?= json_encode($steps) ?>' 
         data-session-id='<?= $sessionId ?>'>

      <!-- Active Guide Panel -->
      <div id="guidePanel">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill" id="stepCounter">
            Step 1 of <?= count($steps) ?>
          </span>
          <!-- Step Indicator Dots -->
          <div class="d-flex gap-2" id="stepDots"></div>
        </div>

        <h4 class="fw-bold text-navy mb-3" id="stepTitle">Step 1</h4>

        <div class="ts-step-instruction mb-4">
          <p class="mb-0 text-navy fw-medium" id="stepInstruction">
            <?= !empty($steps) ? e($steps[0]['instruction']) : 'No specific steps recorded for this issue.' ?>
          </p>
        </div>

        <div class="alert alert-light border small text-muted mb-4 d-flex align-items-center gap-2">
          <i class="bi bi-info-circle text-primary fs-5"></i>
          <span>Perform this step carefully on your machine. If the malfunction disappears, mark as resolved!</span>
        </div>

        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
          <button type="button" class="btn btn-success px-4 py-2 fw-semibold" id="btnFixed">
            <i class="bi bi-check2-circle me-1"></i> Problem Fixed!
          </button>
          <button type="button" class="btn btn-ts-primary px-4 py-2" id="btnContinue">
            Continue to Next Step &rarr;
          </button>
        </div>
      </div>

      <!-- Final Evaluation Panel (shown after last step) -->
      <div id="finalPanel" class="d-none text-center py-4">
        <div class="p-3 mb-3 d-inline-block rounded-circle bg-primary-subtle text-primary">
          <i class="bi bi-clipboard-check fs-1"></i>
        </div>
        <h3 class="fw-bold text-navy mb-2">Troubleshooting Steps Completed</h3>
        <p class="text-muted max-w-600 mx-auto mb-4">
          You have executed all recommended diagnostic steps for &ldquo;<?= e($problem['problem_name']) ?>&rdquo;. Was your problem resolved?
        </p>

        <div class="d-flex justify-content-center gap-3">
          <button type="button" class="btn btn-success px-4 py-2 btn-final-resolved fw-semibold" data-result="resolved">
            <i class="bi bi-check-circle-fill me-1"></i> Yes, Problem Resolved
          </button>
          <button type="button" class="btn btn-danger px-4 py-2 btn-final-resolved fw-semibold" data-result="unresolved">
            <i class="bi bi-x-circle-fill me-1"></i> No, Problem Still Exists
          </button>
        </div>
      </div>

    </div>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

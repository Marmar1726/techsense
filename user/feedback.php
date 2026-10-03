<?php
// ============================================================
// TechSense — Troubleshooting Recommendation Feedback
// ============================================================
$pageTitle = 'Session Feedback';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$userId    = (int)$_SESSION['user_id'];
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

if ($sessionId <= 0) {
    setFlash('warning', 'Session ID required.');
    redirect(BASE_URL . '/user/history.php');
}

// Verify ownership
$sStmt = $pdo->prepare("SELECT s.*, p.problem_name, c.category_name 
                        FROM troubleshooting_sessions s 
                        LEFT JOIN problems p ON s.problem_id = p.id 
                        LEFT JOIN categories c ON s.category_id = c.id 
                        WHERE s.id = ? AND s.user_id = ?");
$sStmt->execute([$sessionId, $userId]);
$session = $sStmt->fetch();

if (!$session) {
    setFlash('danger', 'Session not found or access denied.');
    redirect(BASE_URL . '/user/history.php');
}

// Check if feedback already submitted
$fbStmt = $pdo->prepare("SELECT * FROM feedback WHERE session_id = ?");
$fbStmt->execute([$sessionId]);
$existingFeedback = $fbStmt->fetch();

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existingFeedback) {
    $token    = $_POST['csrf_token'] ?? '';
    $rating   = $_POST['rating'] ?? 'helpful';
    $comments = sanitize($_POST['comments'] ?? '');

    if (!validateCsrf($token)) {
        setFlash('danger', 'Security validation failed.');
    } elseif (!in_array($rating, ['helpful', 'partial', 'not_helpful'])) {
        setFlash('warning', 'Please select a valid feedback rating.');
    } else {
        $ins = $pdo->prepare("INSERT INTO feedback (session_id, user_id, rating, comments) VALUES (?, ?, ?, ?)");
        $ins->execute([$sessionId, $userId, $rating, $comments]);

        // If user says helpful and session was in_progress, mark resolved
        if ($rating === 'helpful' && $session['result'] === 'in_progress') {
            $upd = $pdo->prepare("UPDATE troubleshooting_sessions SET result = 'resolved' WHERE id = ?");
            $upd->execute([$sessionId]);
        }

        setFlash('success', 'Thank you! Your feedback helps refine our IT troubleshooting recommendations.');
        redirect(BASE_URL . '/user/history-detail.php?session_id=' . $sessionId);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/user-sidebar.php'; ?>

  <main class="ts-content">

    <div class="ts-page-header">
      <h1>Troubleshooting Feedback</h1>
      <div class="ts-breadcrumb">
        <a href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a> &rsaquo;
        <a href="<?= BASE_URL ?>/user/history.php">History</a> &rsaquo;
        <span>Submit Feedback</span>
      </div>
    </div>

    <div class="ts-card max-w-700 mx-auto p-4 shadow">
      
      <!-- Session Summary Header -->
      <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-ts border mb-4">
        <div class="ts-logo-icon bg-navy text-ts-accent">
          <i class="bi bi-chat-quote-fill"></i>
        </div>
        <div>
          <div class="fw-bold text-navy"><?= e($session['problem_name'] ?: 'Diagnosed Issue') ?></div>
          <div class="text-muted small">Category: <?= e($session['category_name']) ?> &bull; Session #<?= $session['id'] ?></div>
        </div>
      </div>

      <?php if ($existingFeedback): ?>
        <div class="alert alert-success text-center py-4">
          <i class="bi bi-check-circle-fill fs-1 text-success d-block mb-2"></i>
          <h5 class="fw-bold">Feedback Already Recorded</h5>
          <p class="mb-3 text-muted small">You evaluated this session as: <strong><?= ucfirst($existingFeedback['rating']) ?></strong></p>
          <?php if (!empty($existingFeedback['comments'])): ?>
            <div class="p-3 bg-white rounded border max-w-500 mx-auto text-muted small text-start mb-3">
              &ldquo;<?= e($existingFeedback['comments']) ?>&rdquo;
            </div>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>/user/history-detail.php?session_id=<?= $sessionId ?>" class="btn btn-outline-success btn-sm">
            View Session Details &rarr;
          </a>
        </div>
      <?php else: ?>

        <form method="POST" action="<?= BASE_URL ?>/user/feedback.php?session_id=<?= $sessionId ?>">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <div class="mb-4">
            <label class="form-label fw-bold text-navy mb-3">
              Was this troubleshooting recommendation helpful?
            </label>
            
            <div class="row g-3">
              <div class="col-sm-4">
                <input type="radio" class="btn-check" name="rating" id="ratingYes" value="helpful" checked>
                <label class="btn btn-outline-success w-100 p-3 text-center d-block rounded-ts h-100" for="ratingYes">
                  <i class="bi bi-hand-thumbs-up-fill fs-2 d-block mb-1"></i>
                  <div class="fw-bold">Yes, Helpful</div>
                  <div class="small opacity-75">Solved my issue</div>
                </label>
              </div>

              <div class="col-sm-4">
                <input type="radio" class="btn-check" name="rating" id="ratingPart" value="partial">
                <label class="btn btn-outline-warning w-100 p-3 text-center d-block rounded-ts h-100" for="ratingPart">
                  <i class="bi bi-dash-circle-fill fs-2 d-block mb-1"></i>
                  <div class="fw-bold">Partially</div>
                  <div class="small opacity-75">Helpful context</div>
                </label>
              </div>

              <div class="col-sm-4">
                <input type="radio" class="btn-check" name="rating" id="ratingNo" value="not_helpful">
                <label class="btn btn-outline-danger w-100 p-3 text-center d-block rounded-ts h-100" for="ratingNo">
                  <i class="bi bi-hand-thumbs-down-fill fs-2 d-block mb-1"></i>
                  <div class="fw-bold">No</div>
                  <div class="small opacity-75">Did not resolve</div>
                </label>
              </div>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold text-navy small">Additional Comments or Observations</label>
            <textarea name="comments" class="form-control ts-form-control" rows="4" placeholder="Let us know what worked, what step failed, or if any hardware part was replaced..."></textarea>
          </div>

          <div class="d-flex justify-content-between align-items-center pt-3 border-top">
            <a href="<?= BASE_URL ?>/user/history.php" class="btn btn-outline-secondary">Skip Feedback</a>
            <button type="submit" class="btn btn-ts-primary px-4">
              <i class="bi bi-send-fill me-1"></i> Submit Evaluation
            </button>
          </div>
        </form>

      <?php endif; ?>

    </div>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

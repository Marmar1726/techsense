<?php
// ============================================================
// TechSense — Admin Feedback Management
// ============================================================
$pageTitle = 'Feedback Management';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$ratingFilter = isset($_GET['rating']) ? sanitize($_GET['rating']) : '';
$page         = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage      = 12;

// KPIs
$totFb      = (int)$pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$helpfulCnt = (int)$pdo->query("SELECT COUNT(*) FROM feedback WHERE rating = 'helpful'")->fetchColumn();
$partCnt    = (int)$pdo->query("SELECT COUNT(*) FROM feedback WHERE rating = 'partial'")->fetchColumn();
$notCnt     = (int)$pdo->query("SELECT COUNT(*) FROM feedback WHERE rating = 'not_helpful'")->fetchColumn();

$helpfulPct = $totFb > 0 ? round(($helpfulCnt / $totFb) * 100) : 0;
$partPct    = $totFb > 0 ? round(($partCnt / $totFb) * 100) : 0;
$notPct     = $totFb > 0 ? round(($notCnt / $totFb) * 100) : 0;

$where = ["1=1"];
$params = [];

if (!empty($ratingFilter) && in_array($ratingFilter, ['helpful', 'partial', 'not_helpful'])) {
    $where[] = "f.rating = ?";
    $params[] = $ratingFilter;
}

$whereSql = implode(' AND ', $where);

$countSql = "SELECT COUNT(*) FROM feedback f WHERE $whereSql";
$dataSql  = "SELECT f.*, u.full_name, u.username, u.email, p.problem_name, c.category_name, s.match_score 
             FROM feedback f 
             JOIN users u ON f.user_id = u.id 
             JOIN troubleshooting_sessions s ON f.session_id = s.id 
             LEFT JOIN problems p ON s.problem_id = p.id 
             LEFT JOIN categories c ON s.category_id = c.id 
             WHERE $whereSql 
             ORDER BY f.created_at DESC";

$pagination = paginate($pdo, $countSql, $dataSql, $params, $page, $perPage);
$feedbacks = $pagination['data'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="ts-content">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1">User Feedback &amp; Quality Reviews</h1>
        <div class="ts-breadcrumb">
          <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a> &rsaquo;
          <span>Recommendations Evaluation</span>
        </div>
      </div>
      <div>
        <span class="badge bg-light text-secondary border px-3 py-2">
          <?= $totFb ?> Total User Evaluations
        </span>
      </div>
    </div>

    <!-- Feedback KPI Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="ts-stat-card p-3 d-flex justify-content-between align-items-center">
          <div>
            <div class="ts-stat-value text-success"><?= $helpfulCnt ?> <span class="fs-6 text-muted">(<?= $helpfulPct ?>%)</span></div>
            <div class="ts-stat-label">Helpful / Problem Solved</div>
          </div>
          <div class="ts-stat-icon bg-success-subtle text-success">
            <i class="bi bi-hand-thumbs-up-fill"></i>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="ts-stat-card p-3 d-flex justify-content-between align-items-center">
          <div>
            <div class="ts-stat-value text-warning"><?= $partCnt ?> <span class="fs-6 text-muted">(<?= $partPct ?>%)</span></div>
            <div class="ts-stat-label">Partially Helpful</div>
          </div>
          <div class="ts-stat-icon bg-warning-subtle text-warning">
            <i class="bi bi-dash-circle-fill"></i>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="ts-stat-card p-3 d-flex justify-content-between align-items-center">
          <div>
            <div class="ts-stat-value text-danger"><?= $notCnt ?> <span class="fs-6 text-muted">(<?= $notPct ?>%)</span></div>
            <div class="ts-stat-label">Not Helpful / Unresolved</div>
          </div>
          <div class="ts-stat-icon bg-danger-subtle text-danger">
            <i class="bi bi-hand-thumbs-down-fill"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Rating Filter -->
    <div class="ts-card p-3 mb-4 shadow-sm">
      <form method="GET" action="<?= BASE_URL ?>/admin/feedback.php" class="row g-2 align-items-center">
        <div class="col-sm-6 col-md-4">
          <select name="rating" class="form-select ts-form-control">
            <option value="">All Ratings</option>
            <option value="helpful" <?= $ratingFilter === 'helpful' ? 'selected' : '' ?>>Helpful</option>
            <option value="partial" <?= $ratingFilter === 'partial' ? 'selected' : '' ?>>Partially Helpful</option>
            <option value="not_helpful" <?= $ratingFilter === 'not_helpful' ? 'selected' : '' ?>>Not Helpful</option>
          </select>
        </div>
        <div class="col-sm-3 col-md-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
        </div>
        <?php if (!empty($ratingFilter)): ?>
          <div class="col-auto">
            <a href="<?= BASE_URL ?>/admin/feedback.php" class="btn btn-outline-secondary">Reset</a>
          </div>
        <?php endif; ?>
      </form>
    </div>

    <!-- Feedback Table -->
    <div class="ts-card">
      <div class="ts-card-body p-0">
        <div class="table-responsive">
          <table class="ts-table mb-0">
            <thead>
              <tr>
                <th>User</th>
                <th>Diagnosed Problem</th>
                <th>Rating</th>
                <th>User Comments</th>
                <th>Confidence</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($feedbacks)): ?>
                <?php foreach ($feedbacks as $fb): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold text-navy"><?= e($fb['full_name']) ?></div>
                      <div class="text-muted small">@<?= e($fb['username']) ?></div>
                    </td>
                    <td>
                      <div class="fw-semibold text-navy"><?= e($fb['problem_name'] ?: 'Custom Problem') ?></div>
                      <span class="badge bg-light text-dark border"><?= e($fb['category_name']) ?></span>
                    </td>
                    <td>
                      <span class="badge <?= $fb['rating'] === 'helpful' ? 'bg-success' : ($fb['rating'] === 'partial' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                        <?= ucfirst($fb['rating']) ?>
                      </span>
                    </td>
                    <td class="small" style="max-width: 320px;">
                      <?php if (!empty($fb['comments'])): ?>
                        &ldquo;<?= e($fb['comments']) ?>&rdquo;
                      <?php else: ?>
                        <span class="text-muted">No additional comments entered.</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge bg-info-subtle text-info fw-bold"><?= round($fb['match_score']) ?>%</span>
                    </td>
                    <td class="text-muted small"><?= formatDate($fb['created_at'], 'M d, Y') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" class="text-center py-5 text-muted">No feedback records found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
          <div class="p-3 border-top d-flex justify-content-center">
            <?= renderPagination($pagination['page'], $pagination['total_pages'], BASE_URL . '/admin/feedback.php?rating=' . urlencode($ratingFilter)) ?>
          </div>
        <?php endif; ?>

      </div>
    </div>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

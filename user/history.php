<?php
// ============================================================
// TechSense — User Troubleshooting History
// ============================================================
$pageTitle = 'My Troubleshooting History';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$userId = (int)$_SESSION['user_id'];

// Handle Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (validateCsrf($_POST['csrf_token'] ?? '')) {
        $delId = (int)$_POST['delete_id'];
        $del = $pdo->prepare("DELETE FROM troubleshooting_sessions WHERE id = ? AND user_id = ?");
        $del->execute([$delId, $userId]);
        setFlash('success', 'Session record removed successfully.');
    }
    redirect(BASE_URL . '/user/history.php');
}

// Filters
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$resultFilter   = isset($_GET['result']) ? sanitize($_GET['result']) : '';
$page           = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage        = 10;

// Query builder
$whereClauses = ["s.user_id = ?"];
$params = [$userId];

if ($categoryFilter > 0) {
    $whereClauses[] = "s.category_id = ?";
    $params[] = $categoryFilter;
}
if (!empty($resultFilter) && in_array($resultFilter, ['resolved', 'unresolved', 'in_progress'])) {
    $whereClauses[] = "s.result = ?";
    $params[] = $resultFilter;
}

$whereSql = implode(' AND ', $whereClauses);

$countSql = "SELECT COUNT(*) FROM troubleshooting_sessions s WHERE $whereSql";
$dataSql  = "SELECT s.*, p.problem_name, c.category_name, sol.solution_description 
             FROM troubleshooting_sessions s 
             LEFT JOIN problems p ON s.problem_id = p.id 
             LEFT JOIN categories c ON s.category_id = c.id 
             LEFT JOIN solutions sol ON p.id = sol.problem_id 
             WHERE $whereSql 
             ORDER BY s.created_at DESC";

$pagination = paginate($pdo, $countSql, $dataSql, $params, $page, $perPage);
$sessions = $pagination['data'];

// Fetch categories for filter dropdown
$categories = $pdo->query("SELECT id, category_name FROM categories WHERE status = 'active'")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/user-sidebar.php'; ?>

  <main class="ts-content">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1">Troubleshooting History</h1>
        <div class="ts-breadcrumb">
          <a href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a> &rsaquo;
          <span>Previous Diagnostic Sessions</span>
        </div>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/user/troubleshoot.php" class="btn btn-ts-primary px-3 shadow-sm">
          <i class="bi bi-play-circle-fill me-1"></i> New Diagnosis
        </a>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="ts-card p-3 mb-4">
      <form method="GET" action="<?= BASE_URL ?>/user/history.php" class="row g-2 align-items-center">
        <div class="col-sm-5 col-md-4">
          <select name="category" class="form-select ts-form-control">
            <option value="0">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['category_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-4 col-md-3">
          <select name="result" class="form-select ts-form-control">
            <option value="">All Outcomes</option>
            <option value="resolved" <?= $resultFilter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
            <option value="unresolved" <?= $resultFilter === 'unresolved' ? 'selected' : '' ?>>Unresolved</option>
            <option value="in_progress" <?= $resultFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
          </select>
        </div>
        <div class="col-sm-3 col-md-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
        </div>
        <?php if ($categoryFilter > 0 || !empty($resultFilter)): ?>
          <div class="col-auto">
            <a href="<?= BASE_URL ?>/user/history.php" class="btn btn-outline-secondary">Reset</a>
          </div>
        <?php endif; ?>
      </form>
    </div>

    <!-- Sessions Table -->
    <div class="ts-card">
      <div class="ts-card-body p-0">
        <div class="table-responsive">
          <table class="ts-table mb-0">
            <thead>
              <tr>
                <th>Date &amp; Time</th>
                <th>Diagnosed Problem</th>
                <th>Category</th>
                <th>Match Score</th>
                <th>Outcome</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($sessions)): ?>
                <?php foreach ($sessions as $s): ?>
                  <tr>
                    <td class="text-muted small">
                      <div class="fw-semibold text-navy"><?= formatDate($s['created_at'], 'M d, Y') ?></div>
                      <div style="font-size: 0.75rem;"><?= formatDate($s['created_at'], 'h:i A') ?></div>
                    </td>
                    <td>
                      <div class="fw-semibold text-navy"><?= e($s['problem_name'] ?: 'Custom Diagnostic') ?></div>
                      <div class="text-muted text-truncate small" style="max-width: 250px;">
                        <?= e($s['user_description'] ?: 'No description provided') ?>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border"><?= e($s['category_name'] ?: 'General') ?></span>
                    </td>
                    <td>
                      <span class="badge bg-info-subtle text-info fw-bold"><?= round($s['match_score']) ?>%</span>
                    </td>
                    <td><?= resultBadge($s['result']) ?></td>
                    <td>
                      <span class="badge <?= $s['status'] === 'closed' ? 'bg-secondary' : 'bg-primary' ?>">
                        <?= ucfirst($s['status']) ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="d-inline-flex gap-1">
                        <a href="<?= BASE_URL ?>/user/history-detail.php?session_id=<?= $s['id'] ?>" class="btn btn-sm btn-light border" title="Full Report">
                          <i class="bi bi-eye"></i> Details
                        </a>
                        <form method="POST" action="<?= BASE_URL ?>/user/history.php" onsubmit="return confirm('Delete this troubleshooting history record?');" style="display:inline;">
                          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                          <input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                    No troubleshooting history matched your query.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
          <div class="p-3 border-top d-flex justify-content-center">
            <?= renderPagination($pagination['page'], $pagination['total_pages'], BASE_URL . '/user/history.php?category=' . $categoryFilter . '&result=' . urlencode($resultFilter)) ?>
          </div>
        <?php endif; ?>

      </div>
    </div>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

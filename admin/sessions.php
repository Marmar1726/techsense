<?php
// ============================================================
// TechSense — Admin Troubleshooting Sessions Log
// ============================================================
$pageTitle = 'Session History';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$resultFilter   = isset($_GET['result']) ? sanitize($_GET['result']) : '';
$q              = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$page           = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage        = 12;

$where = ["1=1"];
$params = [];

if ($categoryFilter > 0) {
    $where[] = "s.category_id = ?";
    $params[] = $categoryFilter;
}
if (!empty($resultFilter) && in_array($resultFilter, ['resolved', 'unresolved', 'in_progress'])) {
    $where[] = "s.result = ?";
    $params[] = $resultFilter;
}
if (!empty($q)) {
    $where[] = "(u.full_name LIKE ? OR u.username LIKE ? OR p.problem_name LIKE ?)";
    $params = array_merge($params, ["%$q%", "%$q%", "%$q%"]);
}

$whereSql = implode(' AND ', $where);

$countSql = "SELECT COUNT(*) FROM troubleshooting_sessions s 
             JOIN users u ON s.user_id = u.id 
             LEFT JOIN problems p ON s.problem_id = p.id 
             WHERE $whereSql";

$dataSql  = "SELECT s.*, u.full_name, u.username, u.email, p.problem_name, c.category_name, f.rating as fb_rating 
             FROM troubleshooting_sessions s 
             JOIN users u ON s.user_id = u.id 
             LEFT JOIN problems p ON s.problem_id = p.id 
             LEFT JOIN categories c ON s.category_id = c.id 
             LEFT JOIN feedback f ON s.id = f.session_id 
             WHERE $whereSql 
             ORDER BY s.created_at DESC";

$pagination = paginate($pdo, $countSql, $dataSql, $params, $page, $perPage);
$sessions = $pagination['data'];

$categories = $pdo->query("SELECT id, category_name FROM categories WHERE status = 'active' ORDER BY category_name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="ts-content">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1">User Troubleshooting Sessions</h1>
        <div class="ts-breadcrumb">
          <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a> &rsaquo;
          <span>System Diagnostic Logs</span>
        </div>
      </div>
      <div>
        <span class="badge bg-light text-secondary border px-3 py-2">
          <?= $pagination['total'] ?> Total Sessions Monitored
        </span>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="ts-card p-3 mb-4 shadow-sm">
      <form method="GET" action="<?= BASE_URL ?>/admin/sessions.php" class="row g-2 align-items-center">
        <div class="col-md-5">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" name="q" class="form-control ts-form-control border-start-0" placeholder="Search user, username, or problem..." value="<?= e($q) ?>">
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <select name="category" class="form-select ts-form-control">
            <option value="0">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['category_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-6 col-md-2">
          <select name="result" class="form-select ts-form-control">
            <option value="">All Outcomes</option>
            <option value="resolved" <?= $resultFilter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
            <option value="unresolved" <?= $resultFilter === 'unresolved' ? 'selected' : '' ?>>Unresolved</option>
            <option value="in_progress" <?= $resultFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
        </div>
      </form>
    </div>

    <!-- Sessions Table -->
    <div class="ts-card">
      <div class="ts-card-body p-0">
        <div class="table-responsive">
          <table class="ts-table mb-0">
            <thead>
              <tr>
                <th>Session ID</th>
                <th>User</th>
                <th>Diagnosed Problem</th>
                <th>Category</th>
                <th>Match</th>
                <th>Outcome</th>
                <th>Feedback</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($sessions)): ?>
                <?php foreach ($sessions as $s): ?>
                  <tr>
                    <td class="text-muted small">#<?= $s['id'] ?></td>
                    <td>
                      <div class="fw-semibold text-navy"><?= e($s['full_name']) ?></div>
                      <div class="text-muted small">@<?= e($s['username']) ?></div>
                    </td>
                    <td>
                      <div class="fw-semibold text-navy"><?= e($s['problem_name'] ?: 'Custom Problem') ?></div>
                      <div class="text-muted small text-truncate" style="max-width: 200px;">&ldquo;<?= e($s['user_description']) ?>&rdquo;</div>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border"><?= e($s['category_name'] ?: 'General') ?></span>
                    </td>
                    <td>
                      <span class="badge bg-info-subtle text-info fw-bold"><?= round($s['match_score']) ?>%</span>
                    </td>
                    <td><?= resultBadge($s['result']) ?></td>
                    <td>
                      <?php if ($s['fb_rating']): ?>
                        <span class="badge <?= $s['fb_rating'] === 'helpful' ? 'bg-success' : ($s['fb_rating'] === 'partial' ? 'bg-warning text-dark' : 'bg-danger') ?>">
                          <?= ucfirst($s['fb_rating']) ?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted small">None</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= formatDate($s['created_at'], 'M d, Y h:i A') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">No diagnostic sessions logged.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
          <div class="p-3 border-top d-flex justify-content-center">
            <?= renderPagination($pagination['page'], $pagination['total_pages'], BASE_URL . '/admin/sessions.php?category=' . $categoryFilter . '&result=' . urlencode($resultFilter) . '&q=' . urlencode($q)) ?>
          </div>
        <?php endif; ?>

      </div>
    </div>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

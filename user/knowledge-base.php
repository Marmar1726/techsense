<?php
// ============================================================
// TechSense — Searchable Troubleshooting Knowledge Base
// ============================================================
$pageTitle = 'Knowledge Base';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$q          = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$category_id= isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$difficulty = isset($_GET['difficulty']) ? sanitize($_GET['difficulty']) : '';
$problem_id = isset($_GET['problem_id']) ? (int)$_GET['problem_id'] : 0;
$page       = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage    = 9;

// If viewing a specific problem
$singleProblem = null;
if ($problem_id > 0) {
    $spStmt = $pdo->prepare("SELECT p.*, c.category_name, sol.solution_description 
                             FROM problems p 
                             JOIN categories c ON p.category_id = c.id 
                             LEFT JOIN solutions sol ON p.id = sol.problem_id 
                             WHERE p.id = ? AND p.status = 'active'");
    $spStmt->execute([$problem_id]);
    $singleProblem = $spStmt->fetch();

    if ($singleProblem) {
        $cStmt = $pdo->prepare("SELECT cause_description FROM causes WHERE problem_id = ? ORDER BY order_num ASC");
        $cStmt->execute([$problem_id]);
        $singleProblem['causes'] = $cStmt->fetchAll(PDO::FETCH_COLUMN);

        $stStmt = $pdo->prepare("SELECT step_number, instruction FROM troubleshooting_steps WHERE problem_id = ? ORDER BY step_number ASC");
        $stStmt->execute([$problem_id]);
        $singleProblem['steps'] = $stStmt->fetchAll();
    }
}

// Build list query
$where = ["p.status = 'active'"];
$params = [];

if (!empty($q)) {
    $where[] = "(p.problem_name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($category_id > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $category_id;
}
if (!empty($difficulty) && in_array($difficulty, ['Beginner', 'Intermediate', 'Advanced'])) {
    $where[] = "p.difficulty = ?";
    $params[] = $difficulty;
}

$whereSql = implode(' AND ', $where);

$countSql = "SELECT COUNT(*) FROM problems p WHERE $whereSql";
$dataSql  = "SELECT p.*, c.category_name 
             FROM problems p 
             JOIN categories c ON p.category_id = c.id 
             WHERE $whereSql 
             ORDER BY p.problem_name ASC";

$pagination = paginate($pdo, $countSql, $dataSql, $params, $page, $perPage);
$problems = $pagination['data'];

// Categories for filter
$categories = $pdo->query("SELECT id, category_name FROM categories WHERE status = 'active' ORDER BY category_name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/user-sidebar.php'; ?>

  <main class="ts-content">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1">Troubleshooting Knowledge Base</h1>
        <div class="ts-breadcrumb">
          <a href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a> &rsaquo;
          <span>Knowledge Base Search</span>
        </div>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/user/troubleshoot.php" class="btn btn-ts-primary btn-sm">
          <i class="bi bi-cpu-fill me-1"></i> Run Interactive Diagnostic
        </a>
      </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="ts-card p-3 mb-4 shadow-sm">
      <form method="GET" action="<?= BASE_URL ?>/user/knowledge-base.php">
        <div class="row g-2 align-items-center">
          <div class="col-md-5">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
              <input type="text" name="q" id="liveSearch" class="form-control ts-form-control border-start-0" placeholder="e.g. WiFi not connecting, Blue screen, Overheating..." value="<?= e($q) ?>">
            </div>
            <div id="searchResults" class="position-absolute bg-white shadow rounded border mt-1 z-3" style="max-width: 400px;"></div>
          </div>
          <div class="col-sm-6 col-md-3">
            <select name="category_id" class="form-select ts-form-control">
              <option value="0">All Categories</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                  <?= e($cat['category_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-6 col-md-2">
            <select name="difficulty" class="form-select ts-form-control">
              <option value="">Any Difficulty</option>
              <option value="Beginner" <?= $difficulty === 'Beginner' ? 'selected' : '' ?>>Beginner</option>
              <option value="Intermediate" <?= $difficulty === 'Intermediate' ? 'selected' : '' ?>>Intermediate</option>
              <option value="Advanced" <?= $difficulty === 'Advanced' ? 'selected' : '' ?>>Advanced</option>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Search</button>
          </div>
        </div>
      </form>
    </div>

    <!-- If Single Problem Detail is Active -->
    <?php if ($singleProblem): ?>
      <div class="ts-card mb-4 border-primary shadow">
        <div class="ts-card-header bg-navy text-white d-flex justify-content-between align-items-center">
          <div class="fw-bold">
            <i class="bi bi-book-half text-ts-accent me-2"></i>
            <?= e($singleProblem['problem_name']) ?>
          </div>
          <a href="<?= BASE_URL ?>/user/knowledge-base.php" class="btn btn-sm btn-outline-light">Close View &times;</a>
        </div>
        <div class="ts-card-body">
          <div class="d-flex gap-2 mb-3">
            <span class="badge bg-light text-dark border"><?= e($singleProblem['category_name']) ?></span>
            <?= difficultyBadge($singleProblem['difficulty']) ?>
            <span class="badge bg-light text-secondary border"><i class="bi bi-clock me-1"></i><?= e($singleProblem['estimated_time']) ?></span>
          </div>

          <p class="text-muted leading-relaxed"><?= e($singleProblem['description']) ?></p>

          <?php if (!empty($singleProblem['warning'])): ?>
            <div class="alert alert-warning py-2 px-3 small d-flex align-items-center gap-2 mb-3">
              <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0"></i>
              <div><strong>Notice:</strong> <?= e($singleProblem['warning']) ?></div>
            </div>
          <?php endif; ?>

          <?php if (!empty($singleProblem['causes'])): ?>
            <h6 class="fw-bold text-navy mb-2"><i class="bi bi-question-circle text-primary me-1"></i> Possible Causes:</h6>
            <ul class="mb-3 ps-3 small text-secondary">
              <?php foreach ($singleProblem['causes'] as $cs): ?>
                <li><?= e($cs) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <?php if (!empty($singleProblem['solution_description'])): ?>
            <div class="p-3 bg-light rounded-ts border mb-3">
              <div class="fw-bold text-success small mb-1"><i class="bi bi-wrench me-1"></i> Recommended Solution:</div>
              <div class="small text-navy"><?= e($singleProblem['solution_description']) ?></div>
            </div>
          <?php endif; ?>

          <?php if (!empty($singleProblem['steps'])): ?>
            <h6 class="fw-bold text-navy mb-2"><i class="bi bi-list-ol text-primary me-1"></i> Verified Troubleshooting Steps:</h6>
            <div class="d-flex flex-column gap-2 mb-3">
              <?php foreach ($singleProblem['steps'] as $st): ?>
                <div class="p-2 px-3 bg-light rounded border small d-flex align-items-start gap-2">
                  <span class="badge bg-primary px-2"><?= $st['step_number'] ?></span>
                  <span><?= e($st['instruction']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        </div>
      </div>
    <?php endif; ?>

    <!-- Results Grid -->
    <div class="row g-3 mb-4">
      <?php if (!empty($problems)): ?>
        <?php foreach ($problems as $p): ?>
          <div class="col-md-6 col-lg-4">
            <div class="ts-card h-100 d-flex flex-column">
              <div class="ts-card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <span class="badge bg-light text-dark border"><?= e($p['category_name']) ?></span>
                  <?= difficultyBadge($p['difficulty']) ?>
                </div>
                <h6 class="fw-bold text-navy mb-2">
                  <a href="<?= BASE_URL ?>/user/knowledge-base.php?problem_id=<?= $p['id'] ?>" class="text-decoration-none text-navy">
                    <?= e($p['problem_name']) ?>
                  </a>
                </h6>
                <p class="text-muted small flex-grow-1 mb-3">
                  <?= e(substr($p['description'], 0, 110)) ?><?= strlen($p['description']) > 110 ? '...' : '' ?>
                </p>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                  <span class="text-muted" style="font-size: 0.75rem;">
                    <i class="bi bi-clock me-1"></i><?= e($p['estimated_time']) ?>
                  </span>
                  <a href="<?= BASE_URL ?>/user/knowledge-base.php?problem_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                    View Guide &rarr;
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="ts-card text-center p-5">
            <i class="bi bi-search fs-1 text-muted d-block mb-2"></i>
            <h5 class="fw-bold text-navy">No Troubleshooting Guides Found</h5>
            <p class="text-muted small">Try broadening your search query or removing category filters.</p>
            <a href="<?= BASE_URL ?>/user/knowledge-base.php" class="btn btn-sm btn-outline-secondary">Clear Filters</a>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
      <div class="d-flex justify-content-center">
        <?= renderPagination($pagination['page'], $pagination['total_pages'], BASE_URL . '/user/knowledge-base.php?q=' . urlencode($q) . '&category_id=' . $category_id . '&difficulty=' . urlencode($difficulty)) ?>
      </div>
    <?php endif; ?>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
// ============================================================
// TechSense — Category Directory & Dedicated Category Views
// ============================================================
$pageTitle = 'IT Problem Categories';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$catId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$activeCategory = null;
$categoryProblems = [];

if ($catId > 0) {
    $cStmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND status = 'active'");
    $cStmt->execute([$catId]);
    $activeCategory = $cStmt->fetch();

    if ($activeCategory) {
        $pStmt = $pdo->prepare("SELECT p.*, sol.solution_description 
                                FROM problems p 
                                LEFT JOIN solutions sol ON p.id = sol.problem_id 
                                WHERE p.category_id = ? AND p.status = 'active' 
                                ORDER BY p.problem_name ASC");
        $pStmt->execute([$catId]);
        $categoryProblems = $pStmt->fetchAll();
    }
}

// Fetch all categories for general grid
$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM problems p WHERE p.category_id = c.id AND p.status = 'active') as problem_count 
                           FROM categories c 
                           WHERE c.status = 'active' 
                           ORDER BY c.id ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/user-sidebar.php'; ?>

  <main class="ts-content">
    
    <?php if ($activeCategory): ?>
      <!-- Dedicated Category View -->
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="ts-logo-icon"><i class="bi <?= e($activeCategory['icon'] ?: 'bi-tools') ?>"></i></span>
            <h1 class="h3 fw-bold text-navy mb-0"><?= e($activeCategory['category_name']) ?> Problems</h1>
          </div>
          <div class="ts-breadcrumb">
            <a href="<?= BASE_URL ?>/user/categories.php">All Categories</a> &rsaquo;
            <span><?= e($activeCategory['category_name']) ?></span>
          </div>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= BASE_URL ?>/user/categories.php" class="btn btn-outline-secondary btn-sm">
            &larr; View All Categories
          </a>
          <a href="<?= BASE_URL ?>/user/troubleshoot.php" class="btn btn-ts-primary btn-sm">
            <i class="bi bi-play-circle me-1"></i> Diagnose in this Category
          </a>
        </div>
      </div>

      <div class="p-3 bg-white rounded-ts border mb-4 shadow-sm">
        <p class="text-muted mb-0"><?= e($activeCategory['description']) ?></p>
      </div>

      <div class="row g-3">
        <?php if (!empty($categoryProblems)): ?>
          <?php foreach ($categoryProblems as $p): ?>
            <div class="col-md-6">
              <div class="ts-card h-100 p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h5 class="fw-bold text-navy mb-0 fs-6"><?= e($p['problem_name']) ?></h5>
                  <?= difficultyBadge($p['difficulty']) ?>
                </div>
                <p class="text-muted small mb-3"><?= e($p['description']) ?></p>
                
                <?php if (!empty($p['warning'])): ?>
                  <div class="alert alert-warning py-1 px-2 small mb-2" style="font-size:0.75rem;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($p['warning']) ?>
                  </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-auto">
                  <span class="text-muted small"><i class="bi bi-clock me-1"></i><?= e($p['estimated_time']) ?></span>
                  <a href="<?= BASE_URL ?>/user/knowledge-base.php?problem_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                    View Troubleshooting Steps &rarr;
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12 text-center py-5 text-muted">
            No troubleshooting guides documented for this category yet.
          </div>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <!-- All Categories Overview Grid -->
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
          <h1 class="h3 fw-bold text-navy mb-1">Supported IT Problem Domains</h1>
          <p class="text-muted small mb-0">Browse troubleshooting pathways categorized by IT system layer.</p>
        </div>
        <div>
          <a href="<?= BASE_URL ?>/user/troubleshoot.php" class="btn btn-ts-primary btn-sm">
            <i class="bi bi-cpu-fill me-1"></i> Start Interactive Wizard
          </a>
        </div>
      </div>

      <div class="row g-4">
        <?php foreach ($categories as $cat): ?>
          <div class="col-sm-6 col-lg-4">
            <div class="ts-cat-card h-100" onclick="window.location.href='<?= BASE_URL ?>/user/categories.php?id=<?= $cat['id'] ?>'">
              <div class="ts-cat-icon">
                <i class="bi <?= e($cat['icon'] ?: 'bi-tools') ?>"></i>
              </div>
              <h5 class="fw-bold text-navy mb-2"><?= e($cat['category_name']) ?></h5>
              <p class="text-muted small mb-3"><?= e($cat['description']) ?></p>
              <div class="badge bg-primary-subtle text-primary border px-3 py-1">
                <?= (int)$cat['problem_count'] ?> Documented Problems
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

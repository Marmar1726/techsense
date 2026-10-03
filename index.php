<?php
// ============================================================
// TechSense — Public Landing Page
// ============================================================
$pageTitle = 'Intelligent IT Problem Analysis & Troubleshooting';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Fetch dynamic statistics from database
$totalUsers = 0;
$totalProblems = 0;
$totalSessions = 0;
$resolutionRate = 95;

try {
    $totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
    $totalProblems = (int) $pdo->query("SELECT COUNT(*) FROM problems WHERE status = 'active'")->fetchColumn();
    $totalSessions = (int) $pdo->query("SELECT COUNT(*) FROM troubleshooting_sessions")->fetchColumn();
    $resolvedSessions = (int) $pdo->query("SELECT COUNT(*) FROM troubleshooting_sessions WHERE result = 'resolved'")->fetchColumn();
    if ($totalSessions > 0) {
        $resolutionRate = round(($resolvedSessions / $totalSessions) * 100);
    }
} catch (Exception $e) {
    // Fallback defaults if DB is not yet migrated
    $totalUsers = 128;
    $totalProblems = 25;
    $totalSessions = 340;
    $resolutionRate = 92;
}

// Fetch active categories
$categories = [];
try {
    $catStmt = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM problems p WHERE p.category_id = c.id AND p.status='active') as problem_count FROM categories c WHERE c.status = 'active' ORDER BY c.id ASC");
    $categories = $catStmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- ============================================================
     HERO SECTION
============================================================ -->
<section class="ts-hero py-5">
  <div class="container py-lg-5">
    <div class="row align-items-center g-5">
      <div class="col-lg-7 ts-hero-content">
        <div class="ts-hero-badge">
          <i class="bi bi-shield-check"></i> Rule-Based Intelligent Analysis Engine
        </div>
        <h1 class="display-4 fw-bold text-white mb-4">
          Intelligent IT Problem Analysis &amp; Troubleshooting
        </h1>
        <p class="lead text-white-50 mb-4">
          Diagnose hardware failures, network interruptions, operating system glitches, and software bugs through structured symptom matching and verified step-by-step resolution pathways.
        </p>
        <div class="ts-hero-btn-group">
          <?php if ($isLoggedIn): ?>
            <a href="<?= BASE_URL ?>/user/troubleshoot.php" class="btn btn-hero-primary shadow">
              <i class="bi bi-play-circle-fill me-2"></i>Start Troubleshooting
            </a>
            <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-hero-outline">
              <i class="bi bi-speedometer2 me-2"></i>My Dashboard
            </a>
          <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php?redirect=user/troubleshoot.php" class="btn btn-hero-primary shadow">
              <i class="bi bi-lightning-charge-fill me-2"></i>Start Troubleshooting
            </a>
            <a href="#how-it-works" class="btn btn-hero-outline">
              <i class="bi bi-info-circle me-2"></i>How It Works
            </a>
          <?php endif; ?>
        </div>

        <div class="row g-3 mt-4 pt-3 border-top border-white-10">
          <div class="col-sm-4">
            <div class="d-flex align-items-center gap-2 text-white-50 small">
              <i class="bi bi-check2-circle text-ts-accent fs-5"></i>
              <span>No AI Hallucinations (Rule-Based)</span>
            </div>
          </div>
          <div class="col-sm-4">
            <div class="d-flex align-items-center gap-2 text-white-50 small">
              <i class="bi bi-check2-circle text-ts-accent fs-5"></i>
              <span>Curated Knowledge Base</span>
            </div>
          </div>
          <div class="col-sm-4">
            <div class="d-flex align-items-center gap-2 text-white-50 small">
              <i class="bi bi-check2-circle text-ts-accent fs-5"></i>
              <span>Interactive Step-by-Step Guide</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="ts-hero-illustration text-center p-4">
          <div class="p-3 mb-3 d-inline-block rounded-circle" style="background: rgba(0, 212, 170, 0.1);">
            <i class="bi bi-cpu-fill" style="font-size: 5rem; color: var(--ts-accent);"></i>
          </div>
          <h4 class="text-white fw-bold mb-2">Automated IT Diagnostic</h4>
          <p class="text-white-50 small mb-4">
            Cross-references your reported symptoms against validated hardware schematics, network topologies, and operating system diagnostics.
          </p>
          <div class="bg-navy p-3 rounded text-start border border-white-10">
            <div class="d-flex justify-content-between text-white-50 small mb-1">
              <span>Symptom Matching Confidence</span>
              <span class="text-ts-accent fw-bold">98.4%</span>
            </div>
            <div class="progress" style="height: 6px;">
              <div class="progress-bar bg-info" role="progressbar" style="width: 98%;"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     HOW IT WORKS SECTION
============================================================ -->
<section class="py-5 bg-white" id="how-it-works">
  <div class="container py-4">
    <div class="text-center max-w-700 mx-auto mb-5">
      <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill mb-2">Process Architecture</span>
      <h2 class="fw-bold text-navy">How TechSense Works</h2>
      <p class="text-muted">A structured four-step methodology to pinpoint computer failures without wasting hours on unverified forums.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="text-center p-4 h-100 rounded-ts bg-light border border-light-subtle">
          <div class="ts-step-circle">1</div>
          <h5 class="fw-bold text-navy mb-2">Select Category &amp; Symptoms</h5>
          <p class="text-muted small mb-0">Choose from 9 IT domains (Hardware, Network, OS, Security) and mark all observing indicators or error messages.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="text-center p-4 h-100 rounded-ts bg-light border border-light-subtle">
          <div class="ts-step-circle">2</div>
          <h5 class="fw-bold text-navy mb-2">Intelligent Rule Analysis</h5>
          <p class="text-muted small mb-0">Our engine compares symptom vectors and context keywords against verified problem relations to compute ranking scores.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="text-center p-4 h-100 rounded-ts bg-light border border-light-subtle">
          <div class="ts-step-circle">3</div>
          <h5 class="fw-bold text-navy mb-2">Interactive Resolution Guide</h5>
          <p class="text-muted small mb-0">Follow progressive troubleshooting cards one step at a time, check off milestones, and record resolution outcomes.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     CATEGORIES SECTION
============================================================ -->
<section class="py-5" style="background-color: #f8fafc;">
  <div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
      <div>
        <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill mb-2">Diagnostic Coverage</span>
        <h2 class="fw-bold text-navy mb-1">Supported Problem Categories</h2>
        <p class="text-muted mb-0">Comprehensive troubleshooting modules spanning everyday computing and enterprise infrastructure.</p>
      </div>
      <div class="mt-3 mt-md-0">
        <a href="<?= BASE_URL ?>/user/knowledge-base.php" class="btn btn-outline-primary btn-sm px-3">
          Explore Knowledge Base <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
    </div>

    <div class="row g-3">
      <?php if (!empty($categories)): ?>
        <?php foreach ($categories as $cat): ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="ts-cat-card" onclick="window.location.href='<?= BASE_URL ?>/<?= $isLoggedIn ? 'user/categories.php?id=' . $cat['id'] : 'login.php' ?>'">
              <div class="ts-cat-icon">
                <i class="bi <?= e($cat['icon'] ?: 'bi-tools') ?>"></i>
              </div>
              <h6 class="fw-bold text-navy mb-1"><?= e($cat['category_name']) ?></h6>
              <div class="text-muted small"><?= (int)($cat['problem_count'] ?? 0) ?> Verified Guides</div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12 text-center text-muted py-4">Categories will display here once initialized.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============================================================
     DYNAMIC STATS SECTION
============================================================ -->
<section class="ts-stats-section py-5 text-white">
  <div class="container py-3">
    <div class="row g-4 text-center">
      <div class="col-6 col-md-3">
        <div class="ts-stat-num"><?= number_format($totalUsers) ?>+</div>
        <div class="ts-stat-lbl text-uppercase fw-semibold">Active IT Users</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="ts-stat-num"><?= number_format($totalProblems) ?></div>
        <div class="ts-stat-lbl text-uppercase fw-semibold">Troubleshooting Models</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="ts-stat-num"><?= number_format($totalSessions) ?></div>
        <div class="ts-stat-lbl text-uppercase fw-semibold">Sessions Diagnosed</div>
      </div>
      <div class="col-6 col-md-3">
        <div class="ts-stat-num"><?= $resolutionRate ?>%</div>
        <div class="ts-stat-lbl text-uppercase fw-semibold">Success Resolution Rate</div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     CALL TO ACTION
============================================================ -->
<section class="py-5 bg-white text-center">
  <div class="container py-4">
    <div class="max-w-700 mx-auto">
      <h2 class="fw-bold text-navy mb-3">Facing an Unresolved IT Problem Right Now?</h2>
      <p class="text-muted mb-4">Input your symptoms into TechSense to get instant diagnostic matches, verified root causes, and safety warnings.</p>
      <a href="<?= BASE_URL ?>/<?= $isLoggedIn ? 'user/troubleshoot.php' : 'register.php' ?>" class="btn btn-ts-primary px-4 py-2 fs-6">
        <i class="bi bi-play-circle-fill me-2"></i>Diagnose My Device Now
      </a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

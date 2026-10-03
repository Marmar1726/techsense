<?php
// ============================================================
// TechSense — Intelligent Troubleshooting Wizard
// ============================================================
$pageTitle = 'Start Troubleshooting';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Fetch categories for Step 1
$categories = [];
try {
    $stmt = $pdo->query("SELECT id, category_name, description, icon FROM categories WHERE status = 'active' ORDER BY id ASC");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/user-sidebar.php'; ?>

  <main class="ts-content" id="ts-wizard">

    <div class="ts-page-header">
      <h1>Intelligent IT Diagnostic Wizard</h1>
      <div class="ts-breadcrumb">
        <a href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a> &rsaquo;
        <span>Troubleshooting Wizard</span>
      </div>
    </div>

    <!-- Wizard Steps Visual Breadcrumb -->
    <div class="ts-wizard-steps">
      <div class="ts-wizard-step active" id="wizardStep1">
        <div class="ts-step-num">1</div>
        <div class="ts-step-label">Select Category</div>
      </div>
      <div class="ts-wizard-step" id="wizardStep2">
        <div class="ts-step-num">2</div>
        <div class="ts-step-label">Describe Problem</div>
      </div>
      <div class="ts-wizard-step" id="wizardStep3">
        <div class="ts-step-num">3</div>
        <div class="ts-step-label">Identify Symptoms</div>
      </div>
      <div class="ts-wizard-step" id="wizardStep4">
        <div class="ts-step-num">4</div>
        <div class="ts-step-label">Intelligent Analysis</div>
      </div>
    </div>

    <!-- The Wizard Form -->
    <form id="wizardForm" method="POST" action="<?= BASE_URL ?>/user/analysis.php">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" id="selectedCategory" name="category_id" value="">

      <!-- ============================================================
           STEP 1: SELECT CATEGORY
      ============================================================ -->
      <div class="ts-wizard-panel" id="step1">
        <div class="ts-card p-4">
          <div class="text-center max-w-700 mx-auto mb-4">
            <h4 class="fw-bold text-navy mb-1">Step 1: What type of IT problem are you facing?</h4>
            <p class="text-muted small">Select the hardware, network, or software category that corresponds to the malfunction.</p>
          </div>

          <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
              <div class="col-sm-6 col-md-4">
                <div class="ts-cat-card ts-category-select-card h-100" data-cat-id="<?= $cat['id'] ?>">
                  <div class="ts-cat-icon">
                    <i class="bi <?= e($cat['icon'] ?: 'bi-tools') ?>"></i>
                  </div>
                  <h6 class="fw-bold text-navy mb-1"><?= e($cat['category_name']) ?></h6>
                  <p class="text-muted small mb-0" style="font-size: 0.78rem;"><?= e($cat['description']) ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="d-flex justify-content-end mt-4 pt-3 border-top">
            <button type="button" class="btn btn-ts-primary px-4 py-2" id="nextToStep2" disabled>
              Continue to Step 2 &rarr;
            </button>
          </div>
        </div>
      </div>

      <!-- ============================================================
           STEP 2: DESCRIBE THE PROBLEM
      ============================================================ -->
      <div class="ts-wizard-panel d-none" id="step2">
        <div class="ts-card p-4 max-w-800 mx-auto">
          <div class="mb-4">
            <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill mb-2">Step 2 of 4</span>
            <h4 class="fw-bold text-navy mb-1">Describe what is happening</h4>
            <p class="text-muted small">Enter a detailed description of the error behavior, error codes, or physical reactions.</p>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold text-navy">What problem are you experiencing?</label>
            <textarea class="form-control ts-form-control" id="problemDescription" name="description" rows="5" placeholder="Example: My computer turns on and fans spin, but there is no display on the monitor. It beeps once and the screen says No Signal." required></textarea>
            <div class="form-text text-muted small mt-2">
              <i class="bi bi-lightbulb-fill text-warning me-1"></i> Tip: Mentioning specific sounds (beeps, clicks), error dialog text, or physical behavior helps the rule matching engine calculate a higher confidence score.
            </div>
          </div>

          <div class="d-flex justify-content-between mt-4 pt-3 border-top">
            <button type="button" class="btn btn-outline-secondary px-3" onclick="goToStep(1)">
              &larr; Back to Categories
            </button>
            <button type="button" class="btn btn-ts-primary px-4" id="nextToStep3">
              Proceed to Symptoms &rarr;
            </button>
          </div>
        </div>
      </div>

      <!-- ============================================================
           STEP 3: SELECT SYMPTOMS
      ============================================================ -->
      <div class="ts-wizard-panel d-none" id="step3">
        <div class="ts-card p-4 max-w-900 mx-auto">
          <div class="mb-4">
            <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill mb-2">Step 3 of 4</span>
            <h4 class="fw-bold text-navy mb-1">Select Observed Symptoms</h4>
            <p class="text-muted small">Check all symptoms that match your computer or device state. You may pick multiple.</p>
          </div>

          <!-- Loading indicator -->
          <div id="loadingSymptoms" class="text-center py-5 d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted small mt-2">Retrieving symptom library for this category...</p>
          </div>

          <!-- Symptom checklist chips container -->
          <div id="symptomsBox" class="d-flex flex-wrap gap-2 mb-4 py-2">
            <!-- Dynamically populated via AJAX in main.js -->
          </div>

          <div class="d-flex justify-content-between mt-4 pt-3 border-top">
            <button type="button" class="btn btn-outline-secondary px-3" onclick="goToStep(2)">
              &larr; Back to Description
            </button>
            <button type="button" class="btn btn-ts-accent px-4 py-2 fw-bold" id="nextToStep4">
              <i class="bi bi-cpu-fill me-1"></i> Run Intelligent Analysis &rarr;
            </button>
          </div>
        </div>
      </div>

    </form>
  </main>
</div>

<style>
.ts-category-select-card {
  transition: all 0.2s ease;
  border: 2px solid transparent;
}
.ts-category-select-card:hover {
  border-color: var(--ts-blue);
  transform: translateY(-2px);
}
.ts-category-select-card.selected {
  border-color: var(--ts-accent);
  background-color: #f0fdf4;
}
.ts-category-select-card.selected .ts-cat-icon {
  background: var(--ts-navy);
  color: var(--ts-accent);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
// ============================================================
// TechSense — About TechSense
// ============================================================
$pageTitle = 'About TechSense';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-navy text-white text-center">
  <div class="container py-4">
    <span class="badge bg-primary-subtle text-info fw-semibold px-3 py-2 rounded-pill mb-3">Academic Project</span>
    <h1 class="display-5 fw-bold mb-3">About TechSense</h1>
    <p class="lead text-white-50 max-w-700 mx-auto">
      An Intelligent IT Problem Analysis and Troubleshooting System designed to replace unstructured online searching
      with structured, rule-based diagnostic evaluation.
    </p>
  </div>
</div>

<div class="py-5 bg-white">
  <div class="container py-3">
    <div class="row g-5 align-items-center">
      <div class="col-lg-6">
        <h3 class="fw-bold text-navy mb-3">Project Purpose &amp; Overview</h3>
        <p class="text-muted leading-relaxed">
          Modern end-users and IT technicians often face recurring hardware malfunctions, connectivity drops, and
          operating system faults. Searching on generic search engines or message boards often results in outdated
          suggestions, dangerous commands, or conflicting instructions.
        </p>
        <p class="text-muted leading-relaxed">
          <strong>TechSense</strong> was conceptualized and developed as a 4th-Year Bachelor of Science in Information
          Technology (BSIT) project. It bridges the gap between chaotic forum searching and expert IT technical support
          by enforcing structured symptom matching, calculated correlation confidence, and verified step-by-step
          procedures.
        </p>
        <div class="alert alert-info border-0 rounded-ts p-3">
          <div class="fw-semibold mb-1"><i class="bi bi-info-circle-fill me-1"></i> Note on Technology &amp; Philosophy:
          </div>
          <small class="text-muted">
            TechSense intentionally does NOT claim to use unvetted artificial intelligence models or opaque black-box
            machine learning. Instead, it utilizes an auditable, rule-based recommendation system anchored in verified
            troubleshooting relations and relational database schemas.
          </small>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="p-4 rounded-ts border bg-light shadow-sm">
          <h5 class="fw-bold text-navy mb-3">Technical Architecture</h5>
          <ul class="list-group list-group-flush bg-transparent">
            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0">
              <span class="text-secondary"><i class="bi bi-server text-primary me-2"></i> Backend Engine</span>
              <span class="badge bg-primary">PHP 8.x (OOP &amp; PDO)</span>
            </li>
            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0">
              <span class="text-secondary"><i class="bi bi-database text-info me-2"></i> Relational Database</span>
              <span class="badge bg-info">MySQL / MariaDB</span>
            </li>
            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0">
              <span class="text-secondary"><i class="bi bi-window-sidebar text-success me-2"></i> UI Framework</span>
              <span class="badge bg-success">Bootstrap 5.3 &amp; CSS3</span>
            </li>
            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0">
              <span class="text-secondary"><i class="bi bi-pie-chart text-warning me-2"></i> Analytics
                Visualization</span>
              <span class="badge bg-warning text-dark">Chart.js 4.4</span>
            </li>
            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0">
              <span class="text-secondary"><i class="bi bi-cpu text-danger me-2"></i> Recommendation Logic</span>
              <span class="badge bg-danger">Symptom Vector Jaccard/Overlap</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="py-5" style="background-color: #f8fafc;">
  <div class="container py-3">
    <div class="text-center max-w-700 mx-auto mb-5">
      <h3 class="fw-bold text-navy">Core System Pillars</h3>
      <p class="text-muted">Built from the ground up for technical reliability and practical usability.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="ts-card p-4 h-100">
          <div class="ts-stat-icon bg-primary-subtle text-primary mb-3">
            <i class="bi bi-diagram-3-fill"></i>
          </div>
          <h5 class="fw-bold text-navy mb-2">Relational Knowledge Base</h5>
          <p class="text-muted small mb-0">Every issue is connected to discreet causes, ordered chronological steps, and
            safety advisories maintained via administrative tools.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="ts-card p-4 h-100">
          <div class="ts-stat-icon bg-success-subtle text-success mb-3">
            <i class="bi bi-signpost-split-fill"></i>
          </div>
          <h5 class="fw-bold text-navy mb-2">Progressive Walkthroughs</h5>
          <p class="text-muted small mb-0">Instead of overwhelming the user with lengthy blocks of text, steps are
            presented sequentially with active resolution checkpoints.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="ts-card p-4 h-100">
          <div class="ts-stat-icon bg-warning-subtle text-warning mb-3">
            <i class="bi bi-graph-up-arrow"></i>
          </div>
          <h5 class="fw-bold text-navy mb-2">Continuous Quality Feedback</h5>
          <p class="text-muted small mb-0">Users evaluate troubleshooting efficacy upon completion, enabling
            administrators to refine procedures and isolate unhelpful steps.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
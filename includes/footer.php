<!-- ============================================================
     TechSense — Shared Footer Partial
============================================================ -->
<footer class="ts-footer mt-auto py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span class="ts-logo-icon"><i class="bi bi-cpu-fill"></i></span>
          <span class="fw-bold fs-5 text-white">Tech<span class="text-ts-accent">Sense</span></span>
        </div>
        <p class="text-white-50 small">An intelligent rule-based IT problem analysis and troubleshooting system for
          diagnosing and resolving common IT issues.</p>
        <p class="text-white-50 small mb-0">Developed as a 4th-Year BSIT Project.</p>
      </div>
      <div class="col-sm-6 col-lg-2 offset-lg-1">
        <h6 class="text-white fw-semibold mb-3">Quick Links</h6>
        <ul class="list-unstyled mb-0">
          <li><a href="<?= BASE_URL ?>/" class="footer-link">Home</a></li>
          <li><a href="<?= BASE_URL ?>/about.php" class="footer-link">About</a></li>
          <li><a href="<?= BASE_URL ?>/how-it-works.php" class="footer-link">How It Works</a></li>
          <li><a href="<?= BASE_URL ?>/login.php" class="footer-link">Login</a></li>
          <li><a href="<?= BASE_URL ?>/register.php" class="footer-link">Register</a></li>
        </ul>
      </div>
      <div class="col-sm-6 col-lg-2">
        <h6 class="text-white fw-semibold mb-3">Categories</h6>
        <ul class="list-unstyled mb-0">
          <li><a href="<?= BASE_URL ?>/user/categories.php?id=1" class="footer-link">Computer Hardware</a></li>
          <li><a href="<?= BASE_URL ?>/user/categories.php?id=2" class="footer-link">Operating System</a></li>
          <li><a href="<?= BASE_URL ?>/user/categories.php?id=4" class="footer-link">Internet/Network</a></li>
          <li><a href="<?= BASE_URL ?>/user/categories.php?id=8" class="footer-link">Security</a></li>
          <li><a href="<?= BASE_URL ?>/user/knowledge-base.php" class="footer-link">All Categories →</a></li>
        </ul>
      </div>
      <div class="col-sm-6 col-lg-3">
        <h6 class="text-white fw-semibold mb-3">System Info</h6>
        <ul class="list-unstyled mb-0 small text-white-50">
          <li><i class="bi bi-shield-check me-2 text-ts-accent"></i>Rule-Based AI Recommendations</li>
          <li><i class="bi bi-database me-2 text-ts-accent"></i>Knowledge Base Driven</li>
          <li><i class="bi bi-phone me-2 text-ts-accent"></i>Responsive Design</li>
          <li><i class="bi bi-lock me-2 text-ts-accent"></i>Secure Authentication</li>
        </ul>
      </div>
    </div>
    <hr class="border-white-10 mt-4 mb-3" />
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
      <span class="text-white-50 small">&copy; <?= date('Y') ?> TechSense. All rights reserved.</span>
      <span class="text-white-50 small">Version 1.0.0 &mdash; BSIT Project</span>
    </div>
  </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<!-- TechSense Main JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>

</html>
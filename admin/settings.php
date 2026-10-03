<?php
// ============================================================
// TechSense — Admin System Settings
// ============================================================
$pageTitle = 'System Settings';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed.');
    } else {
        $allowed = ['site_name', 'admin_email', 'session_timeout', 'items_per_page'];
        foreach ($allowed as $key) {
            if (isset($_POST[$key])) {
                $val = sanitize($_POST[$key]);
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $val, $val]);
            }
        }
        setFlash('success', 'System settings saved successfully.');
    }
    redirect(BASE_URL . '/admin/settings.php');
}

// Fetch current settings
$settings = [];
try {
    $rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
    foreach ($rows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }
} catch (Exception $e) {
    $settings = [];
}

// Fallbacks
$siteName       = $settings['site_name'] ?? 'TechSense';
$adminEmail     = $settings['admin_email'] ?? 'admin@techsense.local';
$sessionTimeout = $settings['session_timeout'] ?? '3600';
$itemsPerPage   = $settings['items_per_page'] ?? '10';

// Server diagnostic info
$phpVersion    = phpversion();
$mysqlVersion  = $pdo->query("SELECT VERSION()")->fetchColumn();
$serverSoftware= $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/XAMPP';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="ts-content">

    <div class="ts-page-header">
      <h1>System Settings &amp; Configuration</h1>
      <div class="ts-breadcrumb">
        <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a> &rsaquo;
        <span>System Settings</span>
      </div>
    </div>

    <div class="row g-4">
      
      <!-- Settings Form -->
      <div class="col-lg-8">
        <div class="ts-card">
          <div class="ts-card-header">
            <i class="bi bi-sliders text-primary"></i>
            <span>General Platform Preferences</span>
          </div>
          <div class="ts-card-body">
            <form method="POST" action="<?= BASE_URL ?>/admin/settings.php">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

              <div class="mb-3">
                <label class="form-label small fw-semibold">Application / Site Name</label>
                <input type="text" name="site_name" class="form-control ts-form-control" value="<?= e($siteName) ?>" required>
                <div class="form-text small text-muted">Displayed in branding elements, email headers, and portal title bars.</div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold">System Administrator Contact Email</label>
                <input type="email" name="admin_email" class="form-control ts-form-control" value="<?= e($adminEmail) ?>" required>
                <div class="form-text small text-muted">Technical support and system notification recipient.</div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">User Session Timeout</label>
                  <select name="session_timeout" class="form-select ts-form-control">
                    <option value="1800" <?= $sessionTimeout == '1800' ? 'selected' : '' ?>>30 Minutes</option>
                    <option value="3600" <?= $sessionTimeout == '3600' ? 'selected' : '' ?>>1 Hour (Recommended)</option>
                    <option value="7200" <?= $sessionTimeout == '7200' ? 'selected' : '' ?>>2 Hours</option>
                    <option value="28800" <?= $sessionTimeout == '28800' ? 'selected' : '' ?>>8 Hours</option>
                  </select>
                </div>
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">Table Pagination (Rows Per Page)</label>
                  <select name="items_per_page" class="form-select ts-form-control">
                    <option value="10" <?= $itemsPerPage == '10' ? 'selected' : '' ?>>10 Records</option>
                    <option value="25" <?= $itemsPerPage == '25' ? 'selected' : '' ?>>25 Records</option>
                    <option value="50" <?= $itemsPerPage == '50' ? 'selected' : '' ?>>50 Records</option>
                  </select>
                </div>
              </div>

              <div class="pt-3 border-top text-end">
                <button type="submit" class="btn btn-ts-primary px-4">
                  <i class="bi bi-save me-1"></i> Update System Settings
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Environment / Server Info -->
      <div class="col-lg-4">
        <div class="ts-card">
          <div class="ts-card-header">
            <i class="bi bi-hdd-rack text-primary"></i>
            <span>Runtime Environment</span>
          </div>
          <div class="ts-card-body p-0">
            <ul class="list-group list-group-flush small">
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">PHP Engine:</span>
                <span class="fw-semibold text-navy">v<?= e($phpVersion) ?></span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Database:</span>
                <span class="fw-semibold text-navy">MySQL <?= e($mysqlVersion) ?></span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Server Software:</span>
                <span class="fw-semibold text-navy text-truncate" style="max-width: 160px;"><?= e($serverSoftware) ?></span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Server Time:</span>
                <span class="fw-semibold text-navy"><?= date('M d, Y h:i A') ?></span>
              </li>
              <li class="list-group-item d-flex justify-content-between">
                <span class="text-muted">Antivirus Rule Engine:</span>
                <span class="badge bg-success-subtle text-success">Active &bull; Rule-Based</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

    </div>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

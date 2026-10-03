<?php
// ============================================================
// TechSense — Admin Sidebar Partial
// ============================================================
$currentScript = basename($_SERVER['PHP_SELF']);
?>
<aside class="ts-sidebar" id="adminSidebar">
  <div class="ts-sidebar-header">
    <div class="d-flex align-items-center gap-2">
      <span class="ts-logo-icon" style="background: linear-gradient(135deg, #e74c3c, #f39c12);"><i class="bi bi-shield-lock-fill"></i></span>
      <div>
        <div class="fw-bold text-white fs-6">Tech<span class="text-ts-accent">Sense</span></div>
        <div class="badge bg-danger text-uppercase" style="font-size: 0.6rem; letter-spacing: 0.05em;">Admin Portal</div>
      </div>
    </div>
  </div>

  <div class="ts-sidebar-label">OVERVIEW</div>
  <nav class="nav flex-column mb-2">
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="ts-nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
      <i class="bi bi-grid-1x2-fill"></i> <span>Dashboard</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/reports.php" class="ts-nav-link <?= $currentScript === 'reports.php' ? 'active' : '' ?>">
      <i class="bi bi-bar-chart-fill"></i> <span>Reports & Analytics</span>
    </a>
  </nav>

  <div class="ts-sidebar-label">KNOWLEDGE BASE</div>
  <nav class="nav flex-column mb-2">
    <a href="<?= BASE_URL ?>/admin/problems.php" class="ts-nav-link <?= $currentScript === 'problems.php' ? 'active' : '' ?>">
      <i class="bi bi-tools"></i> <span>Problems & Solutions</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/categories.php" class="ts-nav-link <?= $currentScript === 'categories.php' ? 'active' : '' ?>">
      <i class="bi bi-folder2-open"></i> <span>Categories</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/symptoms.php" class="ts-nav-link <?= $currentScript === 'symptoms.php' ? 'active' : '' ?>">
      <i class="bi bi-activity"></i> <span>Symptoms</span>
    </a>
  </nav>

  <div class="ts-sidebar-label">ACTIVITY & USERS</div>
  <nav class="nav flex-column mb-2">
    <a href="<?= BASE_URL ?>/admin/sessions.php" class="ts-nav-link <?= $currentScript === 'sessions.php' ? 'active' : '' ?>">
      <i class="bi bi-clock-history"></i> <span>User Sessions</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/feedback.php" class="ts-nav-link <?= $currentScript === 'feedback.php' ? 'active' : '' ?>">
      <i class="bi bi-chat-square-quote-fill"></i> <span>Feedback</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/users.php" class="ts-nav-link <?= $currentScript === 'users.php' ? 'active' : '' ?>">
      <i class="bi bi-people-fill"></i> <span>Manage Users</span>
    </a>
  </nav>

  <div class="ts-sidebar-label">SYSTEM</div>
  <nav class="nav flex-column mb-auto">
    <a href="<?= BASE_URL ?>/admin/notifications.php" class="ts-nav-link <?= $currentScript === 'notifications.php' ? 'active' : '' ?>">
      <i class="bi bi-bell-fill"></i> <span>Announcements</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/settings.php" class="ts-nav-link <?= $currentScript === 'settings.php' ? 'active' : '' ?>">
      <i class="bi bi-gear-fill"></i> <span>System Settings</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/profile.php" class="ts-nav-link <?= $currentScript === 'profile.php' ? 'active' : '' ?>">
      <i class="bi bi-person-circle"></i> <span>Profile</span>
    </a>
    <a href="<?= BASE_URL ?>/logout.php" class="ts-nav-link text-danger">
      <i class="bi bi-box-arrow-right text-danger"></i> <span>Logout</span>
    </a>
  </nav>

  <div class="p-3 border-top border-white-10 text-white-50 small mt-auto">
    <div class="d-flex align-items-center gap-2">
      <div class="ts-avatar" style="background: linear-gradient(135deg, #e74c3c, #f39c12);"><?= strtoupper(substr($curUser['full_name'] ?? 'A', 0, 1)) ?></div>
      <div class="overflow-hidden">
        <div class="text-white text-truncate fw-semibold" style="font-size: 0.82rem;"><?= e($curUser['full_name'] ?? 'Admin') ?></div>
        <div class="text-white-50 text-truncate" style="font-size: 0.72rem;">Administrator</div>
      </div>
    </div>
  </div>
</aside>

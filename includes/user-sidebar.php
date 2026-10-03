<?php
// ============================================================
// TechSense — User Sidebar Partial
// ============================================================
$currentScript = basename($_SERVER['PHP_SELF']);
?>
<aside class="ts-sidebar" id="userSidebar">
  <div class="ts-sidebar-header">
    <div class="d-flex align-items-center gap-2">
      <span class="ts-logo-icon"><i class="bi bi-cpu-fill"></i></span>
      <div>
        <div class="fw-bold text-white fs-6">Tech<span class="text-ts-accent">Sense</span></div>
        <div class="text-white-50" style="font-size: 0.7rem;">User Portal</div>
      </div>
    </div>
  </div>

  <div class="ts-sidebar-label">MAIN NAVIGATION</div>
  <nav class="nav flex-column mb-3">
    <a href="<?= BASE_URL ?>/user/dashboard.php" class="ts-nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
      <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
    </a>
    <a href="<?= BASE_URL ?>/user/troubleshoot.php" class="ts-nav-link <?= in_array($currentScript, ['troubleshoot.php', 'analysis.php', 'guide.php']) ? 'active' : '' ?>">
      <i class="bi bi-search"></i> <span>Troubleshoot</span>
    </a>
    <a href="<?= BASE_URL ?>/user/knowledge-base.php" class="ts-nav-link <?= $currentScript === 'knowledge-base.php' ? 'active' : '' ?>">
      <i class="bi bi-book"></i> <span>Knowledge Base</span>
    </a>
    <a href="<?= BASE_URL ?>/user/categories.php" class="ts-nav-link <?= $currentScript === 'categories.php' ? 'active' : '' ?>">
      <i class="bi bi-grid"></i> <span>Categories</span>
    </a>
    <a href="<?= BASE_URL ?>/user/history.php" class="ts-nav-link <?= in_array($currentScript, ['history.php', 'history-detail.php']) ? 'active' : '' ?>">
      <i class="bi bi-clock-history"></i> <span>My History</span>
    </a>
  </nav>

  <div class="ts-sidebar-label">ACCOUNT</div>
  <nav class="nav flex-column mb-auto">
    <a href="<?= BASE_URL ?>/user/notifications.php" class="ts-nav-link <?= $currentScript === 'notifications.php' ? 'active' : '' ?>">
      <i class="bi bi-bell"></i> <span>Notifications</span>
      <?php if (!empty($unreadN) && $unreadN > 0): ?>
        <span class="badge bg-danger ms-auto"><?= $unreadN ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= BASE_URL ?>/user/profile.php" class="ts-nav-link <?= $currentScript === 'profile.php' ? 'active' : '' ?>">
      <i class="bi bi-person-circle"></i> <span>Profile</span>
    </a>
    <a href="<?= BASE_URL ?>/logout.php" class="ts-nav-link text-danger">
      <i class="bi bi-box-arrow-right text-danger"></i> <span>Logout</span>
    </a>
  </nav>

  <div class="p-3 border-top border-white-10 text-white-50 small mt-auto">
    <div class="d-flex align-items-center gap-2">
      <div class="ts-avatar"><?= strtoupper(substr($curUser['full_name'] ?? 'U', 0, 1)) ?></div>
      <div class="overflow-hidden">
        <div class="text-white text-truncate fw-semibold" style="font-size: 0.82rem;"><?= e($curUser['full_name'] ?? 'User') ?></div>
        <div class="text-white-50 text-truncate" style="font-size: 0.72rem;"><?= e($curUser['email'] ?? '') ?></div>
      </div>
    </div>
  </div>
</aside>

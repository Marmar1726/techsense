<?php
// ============================================================
// TechSense — User Notifications View
// ============================================================
$pageTitle = 'Notifications';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$userId = (int)$_SESSION['user_id'];

// Mark all as read when user visits this panel
$upd = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$upd->execute([$userId]);

// Fetch all notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/user-sidebar.php'; ?>

  <main class="ts-content">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 class="h3 fw-bold text-navy mb-1">System Notifications</h1>
        <div class="ts-breadcrumb">
          <a href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a> &rsaquo;
          <span>Notifications</span>
        </div>
      </div>
      <div>
        <span class="badge bg-light text-secondary border px-3 py-2">
          <i class="bi bi-check2-all text-success me-1"></i> All Marked as Read
        </span>
      </div>
    </div>

    <div class="ts-card max-w-800 mx-auto shadow-sm">
      <div class="ts-card-header d-flex justify-content-between align-items-center">
        <div class="fw-semibold text-navy">
          <i class="bi bi-bell-fill text-primary me-2"></i> Recent Alerts &amp; Updates
        </div>
        <span class="badge bg-secondary"><?= count($notifications) ?> Total</span>
      </div>

      <div class="ts-card-body p-0">
        <?php if (!empty($notifications)): ?>
          <div class="list-group list-group-flush">
            <?php foreach ($notifications as $n): ?>
              <div class="ts-notification-item d-flex gap-3 align-items-start">
                <div class="ts-logo-icon bg-primary-subtle text-primary flex-shrink-0" style="width: 38px; height: 38px;">
                  <i class="bi bi-info-circle-fill"></i>
                </div>
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between align-items-baseline mb-1">
                    <h6 class="fw-bold text-navy mb-0 fs-6"><?= e($n['title']) ?></h6>
                    <small class="text-muted" style="font-size: 0.72rem;"><?= timeAgo($n['created_at']) ?></small>
                  </div>
                  <p class="text-muted small mb-1"><?= e($n['message']) ?></p>
                  <span class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-calendar3 me-1"></i><?= formatDate($n['created_at']) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="text-center py-5 text-muted">
            <i class="bi bi-bell-slash fs-1 d-block mb-2 text-secondary"></i>
            You have no notifications at this time.
          </div>
        <?php endif; ?>
      </div>
    </div>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

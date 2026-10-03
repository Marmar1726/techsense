<?php
// ============================================================
// TechSense — Admin Announcements & Notifications
// ============================================================
$pageTitle = 'Broadcast Announcements';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$adminId = (int)$_SESSION['user_id'];

// Handle Broadcast Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed.');
    } else {
        $title   = sanitize($_POST['title'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        $target  = $_POST['target'] ?? 'all';

        if (empty($title) || empty($message)) {
            setFlash('danger', 'Title and message cannot be empty.');
        } else {
            if ($target === 'all') {
                $users = $pdo->query("SELECT id FROM users WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
                $ins = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                foreach ($users as $uid) {
                    $ins->execute([$uid, $title, $message]);
                }
                setFlash('success', "Announcement broadcasted to " . count($users) . " active user(s).");
            } else {
                $targetUserId = (int)$target;
                if ($targetUserId > 0) {
                    $ins = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                    $ins->execute([$targetUserId, $title, $message]);
                    setFlash('success', "Notification dispatched to user #$targetUserId.");
                }
            }
        }
    }
    redirect(BASE_URL . '/admin/notifications.php');
}

// Fetch all registered users for dropdown
$users = $pdo->query("SELECT id, full_name, email, role FROM users WHERE status = 'active' ORDER BY full_name ASC")->fetchAll();

// Fetch recent announcements
$recentNotifs = $pdo->query("SELECT n.*, u.full_name, u.username 
                             FROM notifications n 
                             JOIN users u ON n.user_id = u.id 
                             ORDER BY n.created_at DESC LIMIT 20")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="ts-content">

    <div class="ts-page-header">
      <h1>Announcements &amp; System Notifications</h1>
      <div class="ts-breadcrumb">
        <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a> &rsaquo;
        <span>Broadcast Notifications</span>
      </div>
    </div>

    <div class="row g-4">
      
      <!-- Send Announcement Form -->
      <div class="col-lg-5">
        <div class="ts-card">
          <div class="ts-card-header">
            <i class="bi bi-megaphone-fill text-primary"></i>
            <span>Broadcast New Announcement</span>
          </div>
          <div class="ts-card-body">
            <form method="POST" action="<?= BASE_URL ?>/admin/notifications.php">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

              <div class="mb-3">
                <label class="form-label small fw-semibold">Target Audience</label>
                <select name="target" class="form-select ts-form-control" required>
                  <option value="all">Broadcast to All Users (System-wide)</option>
                  <optgroup label="Specific Individual User">
                    <?php foreach ($users as $u): ?>
                      <option value="<?= $u['id'] ?>">
                        <?= e($u['full_name']) ?> (<?= e($u['role']) ?> &bull; <?= e($u['email']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </optgroup>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold">Announcement Title</label>
                <input type="text" name="title" class="form-control ts-form-control" placeholder="e.g. Scheduled Maintenance or KB Updates" required>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-semibold">Message Body</label>
                <textarea name="message" class="form-control ts-form-control" rows="4" placeholder="Type notification content here..." required></textarea>
              </div>

              <button type="submit" class="btn btn-ts-primary w-100 py-2">
                <i class="bi bi-send-fill me-1"></i> Dispatch Notification
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Recent Notifications Log -->
      <div class="col-lg-7">
        <div class="ts-card">
          <div class="ts-card-header d-flex justify-content-between align-items-center">
            <div class="fw-semibold text-navy">
              <i class="bi bi-bell text-primary me-1"></i> Dispatched Notifications Log
            </div>
            <span class="badge bg-secondary"><?= count($recentNotifs) ?> Displayed</span>
          </div>
          <div class="ts-card-body p-0">
            <div class="table-responsive">
              <table class="ts-table mb-0">
                <thead>
                  <tr>
                    <th>Recipient</th>
                    <th>Subject &amp; Body</th>
                    <th>Status</th>
                    <th>Sent</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($recentNotifs)): ?>
                    <?php foreach ($recentNotifs as $rn): ?>
                      <tr>
                        <td class="small">
                          <div class="fw-semibold text-navy"><?= e($rn['full_name']) ?></div>
                          <div class="text-muted" style="font-size: 0.72rem;">@<?= e($rn['username']) ?></div>
                        </td>
                        <td>
                          <div class="fw-semibold text-navy small"><?= e($rn['title']) ?></div>
                          <div class="text-muted small text-truncate" style="max-width: 250px;"><?= e($rn['message']) ?></div>
                        </td>
                        <td>
                          <span class="badge <?= $rn['is_read'] ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?>">
                            <?= $rn['is_read'] ? 'Read' : 'Unread' ?>
                          </span>
                        </td>
                        <td class="text-muted small"><?= timeAgo($rn['created_at']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="4" class="text-center py-4 text-muted">No notifications sent yet.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

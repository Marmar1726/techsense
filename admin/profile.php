<?php
$pageTitle = 'Admin Profile';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();
$userId = (int) $_SESSION['user_id'];

$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$userData = $userStmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_profile'])) {
  if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    $errors[] = 'Security validation failed.';
  } else {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $username = sanitize($_POST['username'] ?? '');

    if (strlen($full_name) < 2)
      $errors[] = 'Full name must be at least 2 characters.';
    if (!validateEmail($email))
      $errors[] = 'Valid email is required.';
    if (strlen($username) < 3)
      $errors[] = 'Username must be at least 3 characters.';

    if (empty($errors)) {
      $chk = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
      $chk->execute([$email, $username, $userId]);
      if ($chk->fetch()) {
        $errors[] = 'That email or username is already taken.';
      } else {
        $upd = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, username = ? WHERE id = ?");
        $upd->execute([$full_name, $email, $username, $userId]);

        $_SESSION['full_name'] = $full_name;
        $_SESSION['email'] = $email;
        $_SESSION['username'] = $username;

        setFlash('success', 'Admin profile updated.');
        redirect(BASE_URL . '/admin/profile.php');
      }
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_password'])) {
  if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    $errors[] = 'Security validation failed.';
  } else {
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (!verifyPassword($current_pass, $userData['password'])) {
      $errors[] = 'Current password is incorrect.';
    } elseif (!validatePassword($new_pass)) {
      $errors[] = 'New password must be at least 8 characters with 1 uppercase, 1 lowercase, and 1 number.';
    } elseif ($new_pass !== $confirm_pass) {
      $errors[] = 'New passwords do not match.';
    } else {
      $newHash = hashPassword($new_pass);
      $pUpd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
      $pUpd->execute([$newHash, $userId]);
      setFlash('success', 'Admin password changed successfully.');
      redirect(BASE_URL . '/admin/profile.php');
    }
  }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="ts-content">

    <div class="ts-page-header">
      <h1>Administrator Profile</h1>
      <div class="ts-breadcrumb">
        <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a> &rsaquo;
        <span>Admin Profile</span>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $e): ?>
            <li><?= e($e) ?></li>
          <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="ts-card text-center p-4">
          <div class="ts-avatar mx-auto mb-3"
            style="width: 70px; height: 70px; font-size: 1.75rem; background: linear-gradient(135deg, #e74c3c, #f39c12);">
            <?= strtoupper(substr($userData['full_name'], 0, 1)) ?>
          </div>
          <h5 class="fw-bold text-navy mb-1"><?= e($userData['full_name']) ?></h5>
          <p class="text-muted small mb-3">@<?= e($userData['username']) ?></p>
          <span class="badge bg-danger text-uppercase px-3 py-1 mb-4">
            System Administrator
          </span>
          <div class="border-top pt-3 text-muted small text-start">
            <div><strong>Email:</strong> <?= e($userData['email']) ?></div>
            <div><strong>Role:</strong> Administrator (Full Access)</div>
            <div><strong>Active Since:</strong> <?= formatDate($userData['created_at'], 'M d, Y') ?></div>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="ts-card mb-4">
          <div class="ts-card-header">
            <i class="bi bi-person-gear text-primary"></i>
            <span>Administrator Credentials</span>
          </div>
          <div class="ts-card-body">
            <form method="POST" action="<?= BASE_URL ?>/admin/profile.php">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action_profile" value="1">

              <div class="mb-3">
                <label class="form-label small fw-semibold">Full Name</label>
                <input type="text" name="full_name" class="form-control ts-form-control"
                  value="<?= e($userData['full_name']) ?>" required>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">Username</label>
                  <input type="text" name="username" class="form-control ts-form-control"
                    value="<?= e($userData['username']) ?>" required>
                </div>
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">Email Address</label>
                  <input type="email" name="email" class="form-control ts-form-control"
                    value="<?= e($userData['email']) ?>" required>
                </div>
              </div>

              <div class="text-end pt-2">
                <button type="submit" class="btn btn-primary px-4">
                  <i class="bi bi-save me-1"></i> Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="ts-card">
          <div class="ts-card-header">
            <i class="bi bi-key-fill text-warning"></i>
            <span>Change Security Password</span>
          </div>
          <div class="ts-card-body">
            <form method="POST" action="<?= BASE_URL ?>/admin/profile.php">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action_password" value="1">

              <div class="mb-3">
                <label class="form-label small fw-semibold">Current Password</label>
                <input type="password" name="current_password" class="form-control ts-form-control"
                  placeholder="Enter existing password" required>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">New Password</label>
                  <input type="password" name="new_password" class="form-control ts-form-control"
                    placeholder="Min. 8 chars, 1 uppercase, 1 number" required>
                </div>
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold">Confirm Password</label>
                  <input type="password" name="confirm_password" class="form-control ts-form-control"
                    placeholder="Re-type new password" required>
                </div>
              </div>

              <div class="text-end pt-2">
                <button type="submit" class="btn btn-warning px-4 text-dark fw-semibold">
                  <i class="bi bi-shield-lock me-1"></i> Update Admin Password
                </button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>

  </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
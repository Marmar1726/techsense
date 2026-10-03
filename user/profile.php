<?php
// ============================================================
// TechSense — User Profile Management
// ============================================================
$pageTitle = 'My Profile';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$userId = (int)$_SESSION['user_id'];

$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$userData = $userStmt->fetch();

$errors = [];
$successMsg = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_profile'])) {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid.';
    } else {
        $full_name = sanitize($_POST['full_name'] ?? '');
        $email     = sanitize($_POST['email'] ?? '');
        $username  = sanitize($_POST['username'] ?? '');

        if (strlen($full_name) < 2) $errors[] = 'Full name must be at least 2 characters.';
        if (!validateEmail($email)) $errors[] = 'Valid email is required.';
        if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';

        if (empty($errors)) {
            $chk = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
            $chk->execute([$email, $username, $userId]);
            if ($chk->fetch()) {
                $errors[] = 'That email or username is already taken.';
            } else {
                $upd = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, username = ? WHERE id = ?");
                $upd->execute([$full_name, $email, $username, $userId]);
                
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email']     = $email;
                $_SESSION['username']  = $username;

                setFlash('success', 'Profile updated successfully.');
                redirect(BASE_URL . '/user/profile.php');
            }
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_password'])) {
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid.';
    } else {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass     = $_POST['new_password'] ?? '';
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
            setFlash('success', 'Password changed successfully.');
            redirect(BASE_URL . '/user/profile.php');
        }
    }
}

// Session counts for user profile card
$sessCount = (int)$pdo->query("SELECT COUNT(*) FROM troubleshooting_sessions WHERE user_id = $userId")->fetchColumn();
$resCount  = (int)$pdo->query("SELECT COUNT(*) FROM troubleshooting_sessions WHERE user_id = $userId AND result = 'resolved'")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="ts-layout">
  <?php require_once __DIR__ . '/../includes/user-sidebar.php'; ?>

  <main class="ts-content">

    <div class="ts-page-header">
      <h1>Account Settings &amp; Profile</h1>
      <div class="ts-breadcrumb">
        <a href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a> &rsaquo;
        <span>My Profile</span>
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
      
      <!-- Left Column: User Summary Card -->
      <div class="col-lg-4">
        <div class="ts-card text-center p-4">
          <div class="ts-avatar mx-auto mb-3" style="width: 70px; height: 70px; font-size: 1.75rem;">
            <?= strtoupper(substr($userData['full_name'], 0, 1)) ?>
          </div>
          <h5 class="fw-bold text-navy mb-1"><?= e($userData['full_name']) ?></h5>
          <p class="text-muted small mb-3">@<?= e($userData['username']) ?></p>
          <span class="badge bg-primary-subtle text-primary border px-3 py-1 mb-4">
            <?= ucfirst($userData['role']) ?> Account
          </span>

          <div class="row g-2 text-start border-top pt-3">
            <div class="col-6">
              <div class="text-muted small">Total Sessions</div>
              <div class="fw-bold fs-5 text-navy"><?= $sessCount ?></div>
            </div>
            <div class="col-6">
              <div class="text-muted small">Resolved</div>
              <div class="fw-bold fs-5 text-success"><?= $resCount ?></div>
            </div>
            <div class="col-12 mt-2">
              <div class="text-muted small">Member Since</div>
              <div class="small fw-semibold text-navy"><?= formatDate($userData['created_at'], 'M d, Y') ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Edit Forms -->
      <div class="col-lg-8">
        
        <!-- Edit Profile Details -->
        <div class="ts-card mb-4">
          <div class="ts-card-header">
            <i class="bi bi-person-gear text-primary"></i>
            <span>Personal Information</span>
          </div>
          <div class="ts-card-body">
            <form method="POST" action="<?= BASE_URL ?>/user/profile.php">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action_profile" value="1">

              <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Full Name</label>
                <input type="text" name="full_name" class="form-control ts-form-control" value="<?= e($userData['full_name']) ?>" required>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold text-secondary">Username</label>
                  <input type="text" name="username" class="form-control ts-form-control" value="<?= e($userData['username']) ?>" required>
                </div>
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold text-secondary">Email Address</label>
                  <input type="email" name="email" class="form-control ts-form-control" value="<?= e($userData['email']) ?>" required>
                </div>
              </div>

              <div class="text-end pt-2">
                <button type="submit" class="btn btn-ts-primary px-4">
                  <i class="bi bi-save me-1"></i> Save Profile Changes
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Change Password -->
        <div class="ts-card">
          <div class="ts-card-header">
            <i class="bi bi-key-fill text-warning"></i>
            <span>Change Security Password</span>
          </div>
          <div class="ts-card-body">
            <form method="POST" action="<?= BASE_URL ?>/user/profile.php">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action_password" value="1">

              <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Current Password</label>
                <input type="password" name="current_password" class="form-control ts-form-control" placeholder="Enter existing password" required>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold text-secondary">New Password</label>
                  <input type="password" name="new_password" class="form-control ts-form-control" placeholder="Min. 8 chars, 1 uppercase, 1 number" required>
                </div>
                <div class="col-sm-6">
                  <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                  <input type="password" name="confirm_password" class="form-control ts-form-control" placeholder="Re-type new password" required>
                </div>
              </div>

              <div class="text-end pt-2">
                <button type="submit" class="btn btn-warning px-4 text-dark fw-semibold">
                  <i class="bi bi-shield-lock me-1"></i> Update Password
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

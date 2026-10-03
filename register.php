<?php
// ============================================================
// TechSense — User Registration
// ============================================================
$pageTitle = 'Create Account';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

redirectIfLoggedIn();

$errors = [];
$full_name = '';
$email     = '';
$username  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token            = $_POST['csrf_token'] ?? '';
    $full_name        = sanitize($_POST['full_name'] ?? '');
    $email            = sanitize($_POST['email'] ?? '');
    $username         = sanitize($_POST['username'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!validateCsrf($token)) {
        $errors[] = 'Security token invalid. Please refresh the page.';
    }

    if (empty($full_name) || strlen($full_name) < 2) {
        $errors[] = 'Full name must be at least 2 characters.';
    }

    if (!validateEmail($email)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($username) || strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username must be at least 3 characters and contain only letters, numbers, and underscores.';
    }

    if (!validatePassword($password)) {
        $errors[] = 'Password must be at least 8 characters with at least 1 uppercase, 1 lowercase, and 1 number.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        try {
            // Check uniqueness
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
            $checkStmt->execute([$email, $username]);
            if ($checkStmt->fetch()) {
                $errors[] = 'An account with that email or username already exists.';
            } else {
                $hash = hashPassword($password);
                $ins = $pdo->prepare("INSERT INTO users (full_name, email, username, password, role, status) VALUES (?, ?, ?, ?, 'user', 'active')");
                $ins->execute([$full_name, $email, $username, $hash]);
                $newUserId = (int)$pdo->lastInsertId();

                // Send welcome notification
                $notif = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                $notif->execute([
                    $newUserId,
                    'Welcome to TechSense!',
                    'Your account is ready. Begin analyzing computer hardware, network, and system errors.'
                ]);

                setFlash('success', 'Account registered successfully! You can now log in.');
                redirect(BASE_URL . '/login.php');
            }
        } catch (Exception $e) {
            $errors[] = 'Registration failed due to a server error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Register — TechSense</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css" />
</head>
<body class="ts-auth-page py-5">

<div class="container d-flex justify-content-center align-items-center">
  <div class="ts-auth-card" style="max-width: 520px;">
    
    <div class="text-center mb-4">
      <a href="<?= BASE_URL ?>/" class="d-inline-flex align-items-center gap-2 mb-2 text-decoration-none">
        <span class="ts-logo-icon" style="width: 42px; height: 42px;"><i class="bi bi-cpu-fill fs-5"></i></span>
        <span class="fw-bold fs-4 text-navy">Tech<span class="text-ts-accent">Sense</span></span>
      </a>
      <h5 class="fw-bold text-navy mb-1">Create an Account</h5>
      <p class="text-muted small">Join TechSense to analyze and log IT troubleshooting sessions</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger alert-dismissible fade show small" role="alert">
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/register.php" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>" />

      <div class="mb-3">
        <label class="form-label small fw-semibold text-secondary">Full Name</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
          <input type="text" name="full_name" class="form-control ts-form-control border-start-0 ps-0" placeholder="e.g. John Doe" value="<?= e($full_name) ?>" required />
        </div>
      </div>

      <div class="row g-2 mb-3">
        <div class="col-sm-6">
          <label class="form-label small fw-semibold text-secondary">Username</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-at text-muted"></i></span>
            <input type="text" name="username" class="form-control ts-form-control border-start-0 ps-0" placeholder="johndoe" value="<?= e($username) ?>" required />
          </div>
        </div>
        <div class="col-sm-6">
          <label class="form-label small fw-semibold text-secondary">Email Address</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
            <input type="email" name="email" class="form-control ts-form-control border-start-0 ps-0" placeholder="john@example.com" value="<?= e($email) ?>" required />
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label small fw-semibold text-secondary">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
          <input type="password" id="regPass" name="password" class="form-control ts-form-control border-start-0 ps-0" placeholder="Min. 8 chars, 1 uppercase, 1 number" required oninput="checkStrength(this.value)" />
        </div>
        <div class="progress mt-2" style="height: 4px;">
          <div class="progress-bar" id="strengthBar" role="progressbar" style="width: 0%;"></div>
        </div>
        <small id="strengthText" class="text-muted" style="font-size: 0.72rem;">Password strength</small>
      </div>

      <div class="mb-4">
        <label class="form-label small fw-semibold text-secondary">Confirm Password</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-lock text-muted"></i></span>
          <input type="password" name="confirm_password" class="form-control ts-form-control border-start-0 ps-0" placeholder="Re-type your password" required />
        </div>
      </div>

      <button type="submit" class="btn btn-ts-primary w-100 py-2 fw-semibold">
        <i class="bi bi-person-plus-fill me-1"></i> Register Account
      </button>
    </form>

    <div class="text-center mt-4 pt-3 border-top">
      <span class="text-muted small">Already registered? </span>
      <a href="<?= BASE_URL ?>/login.php" class="small fw-semibold text-primary">Sign In</a>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function checkStrength(val) {
  const bar = document.getElementById('strengthBar');
  const txt = document.getElementById('strengthText');
  let score = 0;
  if (val.length >= 8) score += 25;
  if (/[A-Z]/.test(val)) score += 25;
  if (/[a-z]/.test(val)) score += 25;
  if (/[0-9]/.test(val)) score += 25;

  bar.style.width = score + '%';
  if (score < 50) {
    bar.className = 'progress-bar bg-danger';
    txt.textContent = 'Weak password';
    txt.className = 'text-danger';
  } else if (score < 100) {
    bar.className = 'progress-bar bg-warning';
    txt.textContent = 'Moderate password';
    txt.className = 'text-warning';
  } else {
    bar.className = 'progress-bar bg-success';
    txt.textContent = 'Strong password';
    txt.className = 'text-success';
  }
}
</script>
</body>
</html>
